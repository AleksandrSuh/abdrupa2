(function (Drupal, drupalSettings) {
  'use strict';

  function loadHighchartsIfNeeded(callback) {
    console.log('Checking Highcharts...');

    if (typeof Highcharts !== 'undefined') {
      //console.log('Highcharts already loaded');
      callback();
      return;
    }

    //console.log('Highcharts not found, loading from CDN...');

    var localScript = document.createElement('script');
    localScript.src = '/sites/default/files/budget/js/highcharts/highcharts.js';
    localScript.onload = function () {
      //console.log('Highcharts loaded from local file');
      callback();
    };
    document.head.appendChild(localScript);
  }

  console.log(drupalSettings.budgetIncomesData);

  // Основной код
  document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
      console.error('jQuery not found');
      return;
    }

    var $ = jQuery;

    loadHighchartsIfNeeded(function() {
      //console.log('All dependencies loaded, starting chart...');
      //console.log('Highcharts version:', Highcharts.version);

        $(function () {
          var series = [],
            table = [],
            data = {"id":"4","token":"8b6605db8e7afeb7cf3ebba64dfb70d2","appViewTitle":"04. Доходы бюджета (бюджет)","appViewMetaData":{"field":[{"id":"1","data_type":"NUMBER","title":"1"},{"id":"2","data_type":"NUMBER","title":"2025"},{"id":"3","data_type":"NUMBER","title":"2026"},{"id":"4","data_type":"NUMBER","title":"2027"}]},"data":[{"row":{"field":[{"id":"31","value":"ВСЕГО, в т.ч."},{"id":"32","value":"94285000"},{"id":"33","value":"90612000"},{"id":"34","value":"94541000"}]}},{"row":{"field":[{"id":"31","value":"Налог на доходы физических лиц"},{"id":"32","value":"22661000"},{"id":"33","value":"23010000"},{"id":"34","value":"25035000"}]}},{"row":{"field":[{"id":"31","value":"Налог, взимаемый в связи с применением упрощенной системы налогообложения"},{"id":"32","value":"7597000"},{"id":"33","value":"7975000"},{"id":"34","value":"8972000"}]}},{"row":{"field":[{"id":"31","value":"Земельный налог"},{"id":"32","value":"2954000"},{"id":"33","value":"2954000"},{"id":"34","value":"2954000"}]}},{"row":{"field":[{"id":"31","value":"Доходы, получаемые в виде арендной платы за земельные участки, а также средства от продажи права на заключение договоров аренды земельных участков"},{"id":"32","value":"2262000"},{"id":"33","value":"2262000"},{"id":"34","value":"2262000"}]}},{"row":{"field":[{"id":"31","value":"Другие доходы от использования имущества, находящегося в государственной и муниципальной собственности"},{"id":"32","value":"1097000"},{"id":"33","value":"1099000"},{"id":"34","value":"1101000"}]}},{"row":{"field":[{"id":"31","value":"Доходы от продажи материальных и нематериальных активов"},{"id":"32","value":"1552000"},{"id":"33","value":"1261000"},{"id":"34","value":"1261000"}]}},{"row":{"field":[{"id":"31","value":"Другие доходы"},{"id":"32","value":"12748000"},{"id":"33","value":"12504000"},{"id":"34","value":"13042000"}]}},{"row":{"field":[{"id":"31","value":"Безвозмездные поступления"},{"id":"32","value":"43414000"},{"id":"33","value":"39547000"},{"id":"34","value":"39914000"}]}}]},
            categories = [];
          if (typeof data != 'undefined' && typeof data.data != 'undefined' ) {
            $.each(data.data, function( key, val ) {
              var name = jsonfld(val, 0);
              var dy = [
                {y: parseFloat(jsonfld(val, 1) / 1e3), isSum: true},
                {y: parseFloat(jsonfld(val, 2) / 1e3), isSum: true},
                {y: parseFloat(jsonfld(val, 3) / 1e3), isSum: true}
              ];
              //table.push([name, dy[0], dy[1], dy[2]]);
              if (key != 0) {
                series.push({
                  name: name,
                  data: dy
                });
              }
            });
            for (var i = 1; i < 4; i++) {
              categories.push(data.appViewMetaData.field[i].title);
            }
            var opts = {
              xAxis: {
                categories: categories
              },
              yAxis: {
                stackLabels: {
                  enabled:true,
                  formatter: function () {
                    return formatNumber(this.total, 0);
                  }
                }
              },
              tooltip: {
                formatter: function () {
                  return this.series.name + ': ' + formatNumber(this.y, 0);
                }
              },
              series: series
            };
            $('#infographics-1').highcharts($.extend(true, optionsStackedColumn(), opts));
            //fillTable($('#infotable'),table,undefined,[0]);
            $('#ajaxLoader').removeClass('ajaxLoader');
          }
        });


    });
  });



})(Drupal, drupalSettings);
