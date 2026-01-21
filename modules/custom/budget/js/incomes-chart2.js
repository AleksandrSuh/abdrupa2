(function (Drupal, drupalSettings) {
  'use strict';

  function loadHighchartsIfNeeded(callback) {
    //console.log('Checking Highcharts...');

    if (typeof Highcharts !== 'undefined') {
      callback();
      return;
    }

    var localScript = document.createElement('script');
    localScript.src = '/themes/custom/budget_theme/js/highcharts/highcharts.js';
    localScript.onload = function () {
      callback();
    };
    document.head.appendChild(localScript);
  }

  // Функция для форматирования чисел
  function formatNumber(value, decimals) {
    return Highcharts.numberFormat(value, decimals, '.', ' ');
  }

  // Функция для извлечения значения из структуры данных
  function jsonfld(val, index) {
    if (val && val.row && val.row.field && val.row.field[index]) {
      return val.row.field[index].value;
    }
    return 0;
  }

  // Основная функция для построения графика
  function buildChart(data) {
    var series = [],
      categories = [];

    if (typeof data !== 'undefined' && typeof data.data !== 'undefined') {
      $.each(data.data, function(key, val) {
        var name = jsonfld(val, 0);

        // Пропускаем первую строку "ВСЕГО" если нужно
        if (key === 0 && name.includes('ВСЕГО')) {
          return true; // continue
        }

        // Динамически определяем количество лет
        var dy = [];
        for (var i = 1; i < val.row.field.length; i++) {
          dy.push({
            y: parseFloat(jsonfld(val, i) / 1e3),
            isSum: true
          });
        }

        series.push({
          name: name,
          data: dy
        });
      });

      // Создаем категории (годы) из метаданных
      for (var i = 1; i < data.appViewMetaData.field.length; i++) {
        categories.push(data.appViewMetaData.field[i].title);
      }

      var opts = {
        xAxis: {
          categories: categories
        },
        yAxis: {
          stackLabels: {
            enabled: true,
            formatter: function() {
              return formatNumber(this.total, 0);
            }
          },
          /*title: {
            text: 'Тысячи рублей'
          }*/
        },
        tooltip: {
          formatter: function() {
            // Форматируем полную сумму (умножаем на 1000, т.к. мы делили на 1e3)
            var fullValue = this.y * 1000;
            return '<b>' + this.series.name + ' ' + formatNumber(fullValue, 0) + ' руб.</b>';
          }
        },
        series: series,
        /*title: {
          text: data.appViewTitle || 'Доходы бюджета'
        }*/
      };

      // Объединяем с базовыми настройками
      var chartOptions = $.extend(true, optionsStackedColumn(), opts);

      // Строим график
      $('#infographics-1').highcharts(chartOptions);

      // Убираем loader
      $('#ajaxLoader').removeClass('ajaxLoader');
    }
  }

  // Функция для загрузки данных через AJAX
  function loadBudgetData() {
    var settings = drupalSettings.budget || {};
    var jsonUrl = settings.ajaxUrl || Drupal.url('api/budget/incomes?format=json');
    //var jsonUrl = Drupal.url('admin/budget/data?format=json');
    console.log('Используем URL:', jsonUrl);
    $.ajax({
      url: jsonUrl,
      type: 'GET',
      dataType: 'json',
      cache: false, // Отключаем кеш для актуальных данных
      success: function(response) {
        console.log('Данные успешно загружены:', response);
        buildChart(response);
      },
      error: function(xhr, status, error) {
        console.error('Ошибка загрузки данных:', error);

        // Fallback на статические данные из drupalSettings
        if (typeof drupalSettings.budgetIncomesData !== 'undefined') {
          console.log('Использую статические данные из drupalSettings');
          buildChart(drupalSettings.budgetIncomesData);
        } else {
          // Или совсем fallback данные
          var fallbackData = {
            "id": "4",
            "token": "fallback",
            "appViewTitle": "04. Доходы бюджета (бюджет)",
            "appViewMetaData": {
              "field": [
                {"id": "1", "data_type": "NUMBER", "title": "1"},
                {"id": "2", "data_type": "NUMBER", "title": "2025"},
                {"id": "3", "data_type": "NUMBER", "title": "2026"},
                {"id": "4", "data_type": "NUMBER", "title": "2027"}
              ]
            },
            "data": [
              {"row": {"field": [
                    {"id": "31", "value": "ВСЕГО, в т.ч."},
                    {"id": "32", "value": "0"},
                    {"id": "33", "value": "0"},
                    {"id": "34", "value": "0"}
                  ]}}
            ]
          };
          buildChart(fallbackData);
        }
      }
    });
  }

  // Основной код
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
      console.error('jQuery not found');
      return;
    }

    var $ = jQuery;

    // Загружаем Highcharts и затем данные
    loadHighchartsIfNeeded(function() {
      // Проверяем, есть ли элемент для графика
      if ($('#infographics-1').length === 0) {
        console.log('Элемент #infographics-1 не найден');
        return;
      }

      // Загружаем данные
      loadBudgetData();
    });
  });

})(Drupal, drupalSettings);
