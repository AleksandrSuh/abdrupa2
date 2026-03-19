<?php

/**
 * Класс для получения и кэширования новостей с сайта-донора
 */


// ============================================================================
// Код для выполнения по расписанию (CRON)
// ============================================================================

// Создаем экземпляр класса
$fetcher = new NewsFetcher();

// Выполняем обновление
$result = $fetcher->updateNews();

// Выводим результат (для отладки при ручном запуске)
if (PHP_SAPI === 'cli') {
  if ($result) {
    echo "Новости успешно обновлены\n";
    print_r($fetcher->getCachedNews());
  } else {
    echo "Ошибка при обновлении новостей\n";
  }
}

// ============================================================================
// Пример вывода новостей на главной странице
// ============================================================================

/**
 * Функция для отображения новостей в шаблоне Drupal
 */
function displayNewsBlock() {
  $fetcher = new NewsFetcher();
  $news = $fetcher->getCachedNews();

  if (empty($news)) {
    return '<p>Новости временно недоступны</p>';
  }

  $output = '<div class="donor-news-block">';
  $output .= '<h2>Последние новости</h2>';
  $output .= '<ul class="news-list">';

  foreach ($news as $item) {
    $title = htmlspecialchars($item['title'] ?? 'Без заголовка');
    $link = htmlspecialchars($item['link'] ?? '#');
    $date = htmlspecialchars($item['date'] ?? '');

    $output .= '<li class="news-item">';
    $output .= '<span class="news-date">' . $date . '</span> ';
    $output .= '<a href="' . $link . '" target="_blank" rel="noopener noreferrer">' . $title . '</a>';
    $output .= '</li>';
  }

  $output .= '</ul>';
  $output .= '</div>';

  return $output;
}

?>
