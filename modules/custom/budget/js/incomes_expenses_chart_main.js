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

    $(function () {
      var incomesData = {
          "id": "4",
          "token": "8b6605db8e7afeb7cf3ebba64dfb70d2",
          "appViewTitle": "04. Доходы бюджета (бюджет)",
          "appViewMetaData": {
            "field": [{"id": "1", "data_type": "NUMBER", "title": "1"}, {
              "id": "2",
              "data_type": "NUMBER",
              "title": "2025"
            }, {"id": "3", "data_type": "NUMBER", "title": "2026"}, {"id": "4", "data_type": "NUMBER", "title": "2027"}]
          },
          "data": [{
            "row": {
              "field": [{"id": "31", "value": "ВСЕГО, в т.ч."}, {
                "id": "32",
                "value": "94285000"
              }, {"id": "33", "value": "90612000"}, {"id": "34", "value": "94541000"}]
            }
          }, {
            "row": {
              "field": [{"id": "31", "value": "Налог на доходы физических лиц"}, {
                "id": "32",
                "value": "22661000"
              }, {"id": "33", "value": "23010000"}, {"id": "34", "value": "25035000"}]
            }
          }, {
            "row": {
              "field": [{
                "id": "31",
                "value": "Налог, взимаемый в связи с применением упрощенной системы налогообложения"
              }, {"id": "32", "value": "7597000"}, {"id": "33", "value": "7975000"}, {"id": "34", "value": "8972000"}]
            }
          }, {
            "row": {
              "field": [{"id": "31", "value": "Земельный налог"}, {"id": "32", "value": "2954000"}, {
                "id": "33",
                "value": "2954000"
              }, {"id": "34", "value": "2954000"}]
            }
          }, {
            "row": {
              "field": [{
                "id": "31",
                "value": "Доходы, получаемые в виде арендной платы за земельные участки, а также средства от продажи права на заключение договоров аренды земельных участков"
              }, {"id": "32", "value": "2262000"}, {"id": "33", "value": "2262000"}, {"id": "34", "value": "2262000"}]
            }
          }, {
            "row": {
              "field": [{
                "id": "31",
                "value": "Другие доходы от использования имущества, находящегося в государственной и муниципальной собственности"
              }, {"id": "32", "value": "1097000"}, {"id": "33", "value": "1099000"}, {"id": "34", "value": "1101000"}]
            }
          }, {
            "row": {
              "field": [{
                "id": "31",
                "value": "Доходы от продажи материальных и нематериальных активов"
              }, {"id": "32", "value": "1552000"}, {"id": "33", "value": "1261000"}, {"id": "34", "value": "1261000"}]
            }
          }, {
            "row": {
              "field": [{"id": "31", "value": "Другие доходы"}, {"id": "32", "value": "12748000"}, {
                "id": "33",
                "value": "12504000"
              }, {"id": "34", "value": "13042000"}]
            }
          }, {
            "row": {
              "field": [{"id": "31", "value": "Безвозмездные поступления"}, {
                "id": "32",
                "value": "43414000"
              }, {"id": "33", "value": "39547000"}, {"id": "34", "value": "39914000"}]
            }
          }]
        },
        expensesData = {
          "id": "1",
          "token": "58ed38709f2f8786427de5578a1ba3d6",
          "appViewTitle": "05. Расходы в разрезе отраслей (бюджет)",
          "appViewMetaData": {
            "field": [{"id": "1", "data_type": "NUMBER", "title": "1"}, {
              "id": "2",
              "data_type": "NUMBER",
              "title": "2025"
            }, {"id": "3", "data_type": "NUMBER", "title": "2026"}, {"id": "4", "data_type": "NUMBER", "title": "2027"}]
          },
          "data": [{
            "row": {
              "field": [{"id": "1", "value": "0100"}, {
                "id": "2",
                "value": "ОБЩЕГОСУДАРСТВЕННЫЕ ВОПРОСЫ"
              }, {"id": "3", "value": "7654000"}, {"id": "4", "value": "7038000"}, {"id": "5", "value": "7182000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": "0300"}, {
                "id": "2",
                "value": "НАЦИОНАЛЬНАЯ БЕЗОПАСНОСТЬ И ПРАВООХРАНИТЕЛЬНАЯ ДЕЯТЕЛЬНОСТЬ"
              }, {"id": "3", "value": "400000"}, {"id": "4", "value": "385000"}, {"id": "5", "value": "381000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": "0400"}, {
                "id": "2",
                "value": "НАЦИОНАЛЬНАЯ ЭКОНОМИКА"
              }, {"id": "3", "value": "31995000"}, {"id": "4", "value": "22596000"}, {"id": "5", "value": "19484000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": "0500"}, {
                "id": "2",
                "value": "ЖИЛИЩНО-КОММУНАЛЬНОЕ ХОЗЯЙСТВО"
              }, {"id": "3", "value": "6551000"}, {"id": "4", "value": "3625000"}, {"id": "5", "value": "4477000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": "0600"}, {
                "id": "2",
                "value": "ОХРАНА ОКРУЖАЮЩЕЙ СРЕДЫ"
              }, {"id": "3", "value": "228000"}, {"id": "4", "value": "248000"}, {"id": "5", "value": "259000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": "0700"}, {"id": "2", "value": "ОБРАЗОВАНИЕ"}, {
                "id": "3",
                "value": "46798000"
              }, {"id": "4", "value": "48703000"}, {"id": "5", "value": "51334000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": "0800"}, {
                "id": "2",
                "value": "КУЛЬТУРА, КИНЕМАТОГРАФИЯ"
              }, {"id": "3", "value": "2593000"}, {"id": "4", "value": "2467000"}, {"id": "5", "value": "2461000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": "1000"}, {"id": "2", "value": "СОЦИАЛЬНАЯ ПОЛИТИКА"}, {
                "id": "3",
                "value": "5085000"
              }, {"id": "4", "value": "4522000"}, {"id": "5", "value": "4654000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": "1100"}, {
                "id": "2",
                "value": "ФИЗИЧЕСКАЯ КУЛЬТУРА И СПОРТ"
              }, {"id": "3", "value": "2631000"}, {"id": "4", "value": "2559000"}, {"id": "5", "value": "2509000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": "1200"}, {
                "id": "2",
                "value": "СРЕДСТВА МАССОВОЙ ИНФОРМАЦИИ"
              }, {"id": "3", "value": "134000"}, {"id": "4", "value": "129000"}, {"id": "5", "value": "132000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": "1300"}, {
                "id": "2",
                "value": "ОБСЛУЖИВАНИЕ ГОСУДАРСТВЕННОГО И МУНИЦИПАЛЬНОГО ДОЛГА"
              }, {"id": "3", "value": "0"}, {"id": "4", "value": "2000"}, {"id": "5", "value": "50000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": "2000"}, {"id": "2", "value": "ПРОЧИЕ РАСХОДЫ"}, {
                "id": "3",
                "value": "0"
              }, {"id": "4", "value": "1352000"}, {"id": "5", "value": "2790000"}]
            }
          }, {
            "row": {
              "field": [{"id": "1", "value": ""}, {"id": "2", "value": "ИТОГО"}, {
                "id": "3",
                "value": "104069000"
              }, {"id": "4", "value": "93626000"}, {"id": "5", "value": "95713000"}]
            }
          }]
        };

      drawPieChart(incomesData, 'incomes_chart');
      drawPieChart(expensesData, 'expenses_chart', 1, 'last');
    });

    function drawPieChart(data, containerId, indexOffset, totalRowNum) {
      var series = [],
        sum = 0,
        name = '';
      if (typeof indexOffset == 'undefined')
        indexOffset = 0;

      if (typeof data == 'undefined' || typeof data.data == 'undefined')
        return;

      if (typeof totalRowNum == 'undefined')
        totalRowNum = 0;
      else if (totalRowNum == 'last')
        totalRowNum = data.data.length - 1;

      $.each(data.data, function (key, val) {
        name = jsonfld(val, 0 + indexOffset).toLowerCase();
        name = name.substr(0, 1).toUpperCase() + name.substr(1);
        name = name;

        if (key != totalRowNum) {
          sum = parseFloat(jsonfld(val, 1 + indexOffset)) / 1e3;
          series.push([name, parseInt(sum)]);
        }
      });

      var opts = {
        chart: {
          height: 440
        },
        plotOptions: {
          pie: {showInLegend: true}
        },
        tooltip: {
          formatter: function () {
            return this.point.name + ': <b>' + this.y.numberFormat(0, ',', ' ') + ' млн руб.</b>'
          },
          useHTML: true
        },
        series: [{
          data: series
        }],
        legend: {
          itemDistance: 2,
          padding: 0,
          itemStyle: {
            fontSize: '10px',
            itemWidth: 30
          },
          labelFormatter: function () {
            return cutTextForTooltip(this.name, 58);
          },
          useHTML: true,
          itemWidth: 200,
          width: 342,
          symbolWidth: 12,
          symbolPadding: 3,
          margin: 0
        }
      };
      $('#' + containerId).highcharts($.extend(true, optionsPieLegend(), opts));
    }

    $('#program_chart').highcharts({
      colors: ['#00BEB8', '#33D8AA', '#46E685', '#89DE88', '#AAD475', '#FEE18E', '#FCCD34', '#F9A632', '#F44F2C', '#F14242', '#BE214D', '#97197D'],
      chart: {
        plotBackgroundColor: null,
        plotBorderWidth: null,
        plotShadow: false
      },
      title: {
        text: ''
      },
      tooltip: {
        enabled: false
      },
      plotOptions: {
        pie: {
          startAngle: 185,
          allowPointSelect: true,
          cursor: 'pointer',
          dataLabels: {
            enabled: true,
            format: '<h1>{y}%</h1><br>{point.name}',
            useHTML: true,
            style: {
              color: 'black'
            }
          }
        }
      },
      series: [{
        type: 'pie',
        data: [
          ['Программный<br>бюджет', 90],
          {
            name: 'Непрограммный<br>бюджет',
            y: 10,
            sliced: true,
            selected: true
          }
        ]
      }]
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


