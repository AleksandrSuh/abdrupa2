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
    console.log(availableDates);
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
    if (typeof data !=  'undefined') {
      var table = [],
        series = [
          {name: "Бюджетные назначения", data: []},
          {name: "Зачисления", data: []}
        ],
        plan = 0, fact = 0,  name = "",
        categories = [],
        categoriesTwo = [];

      if (data.length) {
        $.each(data, function( key, val ) {
          name = jsonfld(val, 0);
          plan = parseFloat(jsonfld(val, 1)) / 1e3;
          fact = parseFloat(jsonfld(val, 2)) / 1e3;
          /*table.push([
           name, plan,fact, (fact / plan * 100)
           ]);*/
          if (typeof name != "undefined" && name.toLowerCase() != 'итого') {
            categories.push(name);
            categoriesTwo.push(formatNumber(plan));
            series[0].data.push({y: 100, currency: plan});
            series[1].data.push({y: fact / plan * 100, currency: fact});
          }
        });
      }
      else {
        series = [
          {name: "Бюджетные назначения", data: [0,0]},
          {name: "Остаток зачисления", data: [0,0]}
        ];
        categories = ['', ''];
        categoriesTwo = ['', ''];
      }


      //fillTable($('#infotable'), table, undefined, ['last']);
      var opts = {
        chart: {
          height: 500
        },
        xAxis: [
          {categories: categories},
          {categories: categoriesTwo}
        ],
        series: series
      };
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


