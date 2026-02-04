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


  function loadBudgetData() {
    var settings = drupalSettings.budget || {};
    var jsonUrl = settings.ajaxUrl;
    //var jsonUrl = Drupal.url('admin/budget/data?format=json');
    console.log('loadBudgetData, Используем URL:', jsonUrl);
    $.ajax({
      url: jsonUrl,
      type: 'GET',
      dataType: 'json',
      cache: false, // Отключаем кеш для актуальных данных
      success: function(response) {
        console.log('Данные успешно загружены:', response);
        //buildChart(response);
        var categories = [],
          series = [];
        $('#ajaxLoader').addClass('ajaxLoader');
        $(document).ready(function() {
            $.each(response.data, function(key, val) {
              var name = jsonfld(val, 0);
              if (key === 0 && name.includes('ВСЕГО')) {
                return true; // continue
              }
              var dy = [+jsonfld(val, 1) / 1000,
                +jsonfld(val, 2) / 1000,
                +jsonfld(val, 3) / 1000]
              //table.push([name, dy[0], dy[1], dy[2]]);
                //if (key < response.data.length - 1) {
                series.push({
                  name: name,
                  data: dy
                });
              //}
            });

            for (var i = 1; i < 4; i++) {
              categories.push(response.appViewMetaData.field[i].title);
            }

            var opts = {
              xAxis: {
                categories: categories
              },
              series: series
            };
            $('#infographics-1').highcharts($.extend(true, optionsStackedColumn(), opts));
            //fillTable($('#infotable'), table, undefined, ['last']);
            $('#ajaxLoader').removeClass('ajaxLoader');
        });
      },
      error: function(xhr, status, error) {
        console.error('Ошибка загрузки данных:', error);

      }
    });


  }


  document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
      console.error('jQuery not found');
      return;
    }

    var $ = jQuery;

    // Загружаем Highcharts и затем данные
    loadHighchartsIfNeeded(function() {


      // Загружаем данные
      loadBudgetData();
    });
  });

})(Drupal, drupalSettings);


