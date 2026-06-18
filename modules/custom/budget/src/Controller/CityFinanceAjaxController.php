<?php

namespace Drupal\budget\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Контроллер для AJAX-запросов страницы сравнения городов.
 */
class CityFinanceAjaxController extends ControllerBase {

  /**
   * Возвращает данные для графика в формате JSON.
   */
  public function getComparisonData(Request $request) {
    // Получаем параметры из POST-запроса
    $category = $request->request->get('category');
    $year = $request->request->get('year');

    // Если параметры не переданы — возвращаем ошибку
    if (!$category || !$year) {
      return new JsonResponse(['error' => 'Missing parameters'], 400);
    }

    // Загружаем данные из БД
    $storage = $this->entityTypeManager()->getStorage('city_finance');
    $entities = $storage->loadByProperties(['year' => $year]);

    // Сортируем города в нужном порядке
    $city_order = ['Екатеринбург', 'Ростов-на-Дону', 'Пермь', 'Казань', 'Нижний Новгород', 'Челябинск', 'Новосибирск'];

    // Группируем данные по городам
    $data_by_city = [];
    foreach ($entities as $entity) {
      $city = $entity->get('city')->value;
      $data_by_city[$city] = [
        'income' => $entity->get('income')->value,
        'expense' => $entity->get('expense')->value,
      ];
    }

    // Формируем массив для Highcharts
    $result = [];
    foreach ($city_order as $city) {
      if (isset($data_by_city[$city])) {
        $value = ($category == 'Доходы')
          ? $data_by_city[$city]['income']
          : $data_by_city[$city]['expense'];
        $result[] = [(float) $value, $city];
      }
    }

    // Определяем единицу измерения (берём из первого элемента)
    // В JS ожидается, что последний элемент первого массива — это measure
    // Добавляем "тыс. руб." как единицу измерения
    $measure = 'млн. руб.';

    // Добавляем measure в первый элемент
    if (!empty($result)) {
      $result[0][] = $measure;
    }

    // Возвращаем JSON
    return new JsonResponse($result);
  }

}
