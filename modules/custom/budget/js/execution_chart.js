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
    var jsonUrl = settings.ajaxUrl,
      $input = $('#from_data');
    //var jsonUrl = Drupal.url('admin/budget/data?format=json');
    console.log('loadBudgetData, Используем URL:', jsonUrl);

    //getAjax(data);
    $input.change(function () {
      showLoading(true);
      var dateText = $(this).val();
      //console.log(dateText);
      $.ajax({
        url: jsonUrl,
        type: 'post',
        dataType: 'json',
        data: {
          isAjaxRequest: 1,
          date: dateText
        }
      }).done(function(response) {
        //console.log(response.data);
        getAjax(response.data);
        showLoading(false);
      });
    });

    var availableDates = settings.budgetDates || [];
    $input.datepicker(
      $.extend(
        {showMonthAfterYear:false},
        {
          'dateFormat':'dd.mm.yy',
          beforeShowDay: function(date) {
            var dateString = date.toDateString();
            var isAvailable = $.inArray(dateString, availableDates) !== -1;
            return [isAvailable, isAvailable ? 'available-date' : ''];
          },
        },
        $.datepicker.regional['ru']
      ));
    if (availableDates.length > 0) {
      var firstDate = new Date(availableDates[0]);
      $input.datepicker('setDate', firstDate).trigger('change');
    }

  }

  var getAjax = function(data) {
    if (typeof data !=  'undefined' && typeof data != 'undefined' ) {
      // Подпись строки определяется кодом категории, а не позицией в ответе:
      // названия в базе меняются (встречаются и "НАЛОГОВЫЕ И НЕНАЛОГОВЫЕ ДОХОДЫ",
      // и "Налоговые/неналоговые доходы"), а порядок строк базой не гарантирован.
      var LABELS = {
          '10000000000000': 'НАЛОГОВЫЕ и НЕНАЛОГОВЫЕ ДОХОДЫ',
          '20000000000000': 'БЕЗВОЗМЕЗДНЫЕ ПОСТУПЛЕНИЯ'
        },
        ORDER = ['10000000000000', '20000000000000'],
        rows = [];

      $.each(data, function( key, val ) {
        // Код категории появился в ответе вместе с этой правкой: если сервер его ещё
        // не отдаёт, берём пустую строку и подписываем строку названием из базы (см. ниже).
        var codeField = (val.row && val.row.field) ? val.row.field[4] : null,
          code = (codeField && typeof codeField.value != 'undefined') ? codeField.value : '',
          plan = parseFloat(jsonfld(val, 1)) / 1e6,
          fact = parseFloat(jsonfld(val, 2)) / 1e6;

        rows.push({
          code: String(code),
          name: jsonfld(val, 0),
          plan: isNaN(plan) ? 0 : plan,
          fact: isNaN(fact) ? 0 : fact
        });
      });

      // Явный порядок отображения: сначала налоговые, затем безвозмездные.
      // Строки с незнакомым кодом не теряются - идут в конце под своим названием из базы.
      rows.sort(function(a, b) {
        var ia = ORDER.indexOf(a.code),
          ib = ORDER.indexOf(b.code);
        if (ia === -1) { ia = ORDER.length; }
        if (ib === -1) { ib = ORDER.length; }
        if (ia !== ib) { return ia - ib; }
        return a.code < b.code ? -1 : (a.code > b.code ? 1 : 0);
      });

      var categories = [],
        secondCat = [],
        planData = [],
        factData = [];

      $.each(rows, function(i, row) {
        // Если код незнакомый - показываем название из базы, чтобы число
        // никогда не оказалось под чужой подписью.
        categories.push(LABELS[row.code] || row.name);
        secondCat.push(formatNumber(row.plan));
        planData.push({y: 100, currency: row.plan});
        factData.push({y: row.plan > 0 ? (row.fact / row.plan * 100) : 0, currency: row.fact});
      });

      var series = [
        {name: "План", data: planData},
        {name: "Факт", data: factData}
      ];

      var opts = {
        chart: {
          height: 300
        },
        xAxis: [
          {categories: categories},
          {categories: secondCat}
        ],
        yAxis: {
          title: { text: ''},
          max: 100,
          labels: {
            formatter: function(){ return this.value + '%'; }
          }
        },
        series: series
      }

      $('#infographics-1').highcharts($.extend(true, horizontalPercentColChart(), opts));
    }
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


    // jQuery('#openDialog').dialog({'title':'','autoOpen':false,'modal':true,'width':250});

  });

})(Drupal, drupalSettings);


