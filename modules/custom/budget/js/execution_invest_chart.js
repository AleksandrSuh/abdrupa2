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
        parseData(response.data);
        showLoading(false);
        $('.for-date').html(dateText);
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

  var parseData = function(data) {
    /*var series = [
            {name: "НАЦИОНАЛЬНАЯ БЕЗОПАСНОСТЬ И ПРАВООХРАНИТЕЛЬНАЯ ДЕЯТЕЛЬНОСТЬ", data: [0, 0]},
            {name: "НАЦИОНАЛЬНАЯ ЭКОНОМИКА", data: [0, 0]},
            {name: "ЖИЛИЩНО-КОММУНАЛЬНОЕ ХОЗЯЙСТВО", data: [0, 0]},
            {name: "ОБРАЗОВАНИЕ", data: [0, 0]},
            {name: "КУЛЬТУРА, КИНЕМАТОГРАФИЯ", data: [0, 0]},
            {name: "ЗДРАВООХРАНЕНИЕ", data: [0, 0]},
            {name: "ФИЗИЧЕСКАЯ КУЛЬТУРА И СПОРТ", data: [0, 0]}
            ];*/
    var table = [],
      series = [
        {name: "Всего расходов", data: []},
        {name: "Исполнено", data: []}

      ],
      categoriesOne = [],
      categoriesTwo = [];

    $.each(data, function(key, val) {
      //console.log(val);
      var code = jsonfld(val, 1),
        name = jsonfld(val, 2),
        plan = parseFloat(jsonfld(val, 3)),
        fact = parseFloat(jsonfld(val, 4)),
        percentage = +jsonfld(val, 5);
      table.push([code, name, plan, fact, percentage]);

      console.log(code, name, plan, fact, percentage);
      // не добавляем ИТОГО
      //if (code.substr(2) == '00') {
        console.log('добавили', name);
        categoriesOne.push(name);
        categoriesTwo.push(formatNumber(plan));
        series[0].data.push({y: 100, currency: plan});
        series[1].data.push({y: percentage, currency: fact});
      //}
    });
    //console.log(series);

    var opts = {
      chart: {
        height: 600
      },
      xAxis: [
        {categories: categoriesOne},
        {
          categories: categoriesTwo,
          title: {
            text: 'Утверждено ассигнований, млн руб.'
          }
        }
      ],
      series: series
    };
    console.log(opts);
    $('#infographics-1').highcharts($.extend(true, horizontalPercentColChart(), opts));
    //fillTable($('#infotable'), table, undefined, [0, 3, 6, 11, 16, 18, 20, 'last']);
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


