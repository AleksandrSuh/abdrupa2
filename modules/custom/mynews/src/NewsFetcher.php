<?php

namespace Drupal\mynews;

/**
 * Класс для получения и кэширования новостей с сайта-донора
 */
class NewsFetcher {

  private string $donorUrl;
  private string $cacheFilePath;
  private string $logFilePath;
  private int $timeout;

  /**
   * Конструктор
   */
  public function __construct() {
    $this->donorUrl = 'https://xn--80acgfbsl1azdqr.xn--p1ai/news?type=list';

    // Используем публичную файловую систему Drupal
    $projectRoot = '/var/www/html';

    $this->cacheFilePath = $projectRoot . '/web/news_cache/latest_news.json';

    $this->logFilePath = $projectRoot . '/web/news_cache/logs/news_fetcher.log';

    $this->timeout = 10;

    //$this->ensureDirectoriesExist();
  }

  /**
   * Создание необходимых директорий
   */
  /*private function ensureDirectoriesExist(): void {
    $publicPath = \Drupal::service('file_system')->realpath('public://');
    $this->cacheFilePath = $publicPath . '/news_cache/latest_news.json';
    $this->logFilePath = $publicPath . '/news_cache/logs/news_fetcher.log';

    $cacheDir = dirname($this->cacheFilePath);
    if (!is_dir($cacheDir)) {
      mkdir($cacheDir, 0755, true);
    }

    $logDir = dirname($this->logFilePath);
    if (!is_dir($logDir)) {
      mkdir($logDir, 0755, true);
    }
  }*/

  /**
   * Логирование сообщений
   */
  private function log(string $message, string $type = 'INFO'): void {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;

    file_put_contents($this->logFilePath, $logMessage, FILE_APPEND | LOCK_EX);

    // Также логируем в Drupal watchdog
    switch ($type) {
      case 'ERROR':
        \Drupal::logger('mynews')->error($message);
        break;
      case 'WARNING':
        \Drupal::logger('mynews')->warning($message);
        break;
      default:
        \Drupal::logger('mynews')->info($message);
    }
  }

  /**
   * Получение HTML страницы новостей
   */
  private function fetchHtml(): ?string {
    $ch = curl_init();

    curl_setopt_array($ch, [
      CURLOPT_URL => $this->donorUrl,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS => 5,
      CURLOPT_TIMEOUT => $this->timeout,
      CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; DrupalBot/1.0; +' . \Drupal::request()->getHost() . ')',
      CURLOPT_SSL_VERIFYPEER => false, // Для продакшена лучше true
      CURLOPT_SSL_VERIFYHOST => false,
      CURLOPT_HEADER => false,
      CURLOPT_ENCODING => 'gzip,deflate',
    ]);

    $html = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($error || $httpCode !== 200) {
      $this->log("Ошибка загрузки: HTTP $httpCode, cURL ошибка: $error", 'ERROR');
      return null;
    }

    return $html;
  }

  /**
   * Парсинг HTML и извлечение новостей
   */
  private function parseNews(string $html): array {
    $news = [];

    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    $xpath = new \DOMXPath($dom);

    // Ищем все блоки новостей - у них класс "asrow" внутри "news-list"
    $newsItems = $xpath->query("//div[contains(@class, 'news-list')]//div[contains(@class, 'asrow')]");

    //$this->log("Найдено блоков новостей: " . $newsItems->length, 'INFO');

    foreach ($newsItems as $item) {
      if (count($news) >= 4) break;

      // Поиск заголовка и ссылки - они в теге <a> с классом "news-link"
      $linkNode = $xpath->query(".//a[contains(@class, 'news-link')]", $item);

      $title = '';
      $link = '';

      if ($linkNode->length > 0) {
        $title = trim($linkNode->item(0)->nodeValue);
        $link = $linkNode->item(0)->getAttribute('href');

        //$this->log("Найден заголовок: $title", 'INFO');

        // Преобразуем относительные ссылки в абсолютные
        if (strpos($link, 'http') !== 0) {
          $baseUrl = 'https://xn--80acgfbsl1azdqr.xn--p1ai';
          $link = rtrim($baseUrl, '/') . '/' . ltrim($link, '/');
        }
      }

      // Поиск даты - она в span с классом "news-date"
      $dateNode = $xpath->query(".//span[contains(@class, 'news-date')]", $item);
      $date = $dateNode->length > 0 ? trim($dateNode->item(0)->nodeValue) : '';

      //$this->log("Найдена дата: $date", 'INFO');

      // Поиск краткого описания - в теге <p> (опционально)
      $descriptionNode = $xpath->query(".//p", $item);
      $description = $descriptionNode->length > 0 ? trim($descriptionNode->item(0)->nodeValue) : '';

      if (!empty($title) && !empty($link)) {
        $news[] = [
          'title' => $title,
          'link' => $link,
          'date' => $date,
          'description' => $description, // Можно добавить, если нужно
          'timestamp' => time(),
        ];
      }
    }

    // Если не нашли через точный селектор, пробуем запасной вариант
    /*if (empty($news)) {
      $this->log("Не найдено через точный селектор, пробуем запасной", 'WARNING');

      // Просто ищем все ссылки внутри news-list
      $linkNodes = $xpath->query("//div[contains(@class, 'news-list')]//a[contains(@class, 'news-link')]");

      foreach ($linkNodes as $index => $linkNode) {
        if ($index >= 4) break;

        $title = trim($linkNode->nodeValue);
        $link = $linkNode->getAttribute('href');

        if (strpos($link, 'http') !== 0) {
          $baseUrl = 'https://xn--80acgfbsl1azdqr.xn--p1ai';
          $link = rtrim($baseUrl, '/') . '/' . ltrim($link, '/');
        }

        // Пытаемся найти дату рядом (в предыдущем элементе с классом news-date)
        $parent = $linkNode->parentNode;
        $dateNode = $xpath->query(".//span[contains(@class, 'news-date')]", $parent);
        $date = $dateNode->length > 0 ? trim($dateNode->item(0)->nodeValue) : '';

        $news[] = [
          'title' => $title,
          'link' => $link,
          'date' => $date,
          'timestamp' => time(),
        ];
      }
    }*/

    //$this->log("Всего найдено новостей: " . count($news), 'INFO');
    return $news;
  }

  /**
   * Сохранение новостей в файл
   */
  private function saveNews(array $news): bool {
    if (empty($news)) {
      return false;
    }

    $data = [
      'fetched_at' => date('Y-m-d H:i:s'),
      'count' => count($news),
      'news' => $news
    ];

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if ($json === false) {
      return false;
    }

    return file_put_contents($this->cacheFilePath, $json, LOCK_EX) !== false;
  }

  /**
   * Основной метод для обновления новостей
   */
  public function updateNews(): bool {
    $html = $this->fetchHtml();
    if ($html === null) {
      return false;
    }

    $news = $this->parseNews($html);

    if (empty($news)) {
      $this->log("Не удалось найти новости на странице", 'ERROR');
      return false;
    }

    $result = $this->saveNews($news);

    if ($result) {
      //$this->log("Сохранено " . count($news) . " новостей");
    }

    return $result;
  }

  /**
   * Получение сохраненных новостей
   */
  public function getCachedNews(): array {
    if (!file_exists($this->cacheFilePath)) {
      return [];
    }

    $content = file_get_contents($this->cacheFilePath);
    if ($content === false) {
      return [];
    }

    $data = json_decode($content, true);
    return $data['news'] ?? [];
  }
}
