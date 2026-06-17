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
    /*$input.change(function () {
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
    });*/

    var availableDates = settings.budgetDates || [];

    function getMaxValue(array) {
      var v = Math.max.apply(Math, array.map(function(internalData){
        return Math.max.apply(Math, internalData.filter(function(intElement){
          return Number.isFinite(intElement)
        }));}));
      return v;
    }
    function getValue(arr){
      var res = [];
      for(var j = 0; j < arr.length; j++)
      {
        for(var anItem in arr[j])
        {
          res.push(parseFloat(arr[j][0]));
        }
      }
      return res;
    }
    function gap(num) {
      var n = num+(num/100*25);
      return parseFloat(n);
    }
    $(document).ready(function() {
      var globalData;
      var measure;
      var sort;
      var max;
      ajaxChart();
      $('.budget__show').on('submit', function () {
        $('.city_toggle').attr('toggle',1);
        $('.city_toggle').removeClass('town-active');
        ajaxChart();
        return false;
      });
      // получаем данные для графика отправляя выбранные данные формы
      function ajaxChart() {
        var category = $("#category :selected").val();
        var year = ($("#year :selected").val());
        $.ajax({
          type: "post",
          url: "/monitoring/ComparisonFilter",
          data: {
            category: category,
            year: parseInt(year)
          },
          dataType: 'text',
          success: function (data) {
            var chartData = JSON.parse(data);
            console.log(chartData);
            var firstElement = ffirstElement(chartData);
            var measureVal = firstElement[firstElement.length - 1];
            function ffirstElement(obj) {
              for (var el in obj) {
                return obj[el];
              }
            }
            $('.year').html(year);
            measure = measureVal;
            max = Math.max.apply(Math,getValue(chartData));
            max = gap(max);
            chart(chartData, measure,max);
            globalData = chartData;
            sort = Object.create(globalData);
          },
          error: function (xhr, ajaxOptions, thrownError) {
            console.log("Error: " + thrownError);
          }
        });
      }
      // Включение и выключение города по клику на ссылку.
      $('.city_toggle').on('click', function ()
      {
        var toggleCheck = $(this).attr('toggle');
        var city = $(this).attr('city');
        if ($(this).hasClass('town-active'))
        {
          $(this).removeClass('town-active');
        }
        else
        {
          $(this).addClass('town-active');
        }
        //поиск индекса элемента в изменяемом массиве
        var index = search(sort,city);
        //поиск индекса элемента в изначальном массиве
        var globalIndex = search(globalData,city);
        if (index == false && toggleCheck == 0)
        {
          sort.push(globalData[globalIndex]);
          $(this).attr('toggle',1);

        }
        if (index != false && toggleCheck == 1)
        {
          $(this).attr('toggle',0);
          sort.splice(index,1);
          if (sort.length == 0 ) {
            sort.push(globalData[globalIndex]);
            $(this).attr('toggle',1);
            $(this).removeClass('town-active');
          }
        }
        else if (index == 0 && sort.length > 0 && toggleCheck == 1)
        {
          $(this).attr('toggle',0);
          sort.shift();
          if (sort.length == 0 ) {
            sort.push(globalData[globalIndex]);
            $(this).attr('toggle',1);
            $(this).removeClass('town-active');
          }
        }
        else if (sort.length == 0) {
          sort = globalData;
        }
        chart(sort,measure,max);
      });

      function search(data, el) {
        var res;
        res = false;
        for (var i = 0; i < data.length; i++) {
          for (var v = 0; v < data[i].length; v++)
          {
            if (data[i][v] == el) {
              res = i;
            }
          }
        }
        return res;
      }
    });

    function chart(data,measure,max) {
      var myChart = Highcharts.setOptions({
        colors: ['rgb(237,147,30)', 'rgb(50,160,206)', 'rgb(66,192,97)', 'rgb(202,73,83)', 'rgb(254,232,78)', 'rgb(129,214,78)', 'rgb(63,72,160)', 'rgb(249, 166, 50)', 'rgb(244, 79, 44)', 'rgb(12, 109, 164)', 'rgb(190, 33, 77)', 'rgb(70, 230, 133)', 'rgb(0, 190, 184)'],
        // lang: {numericSymbols: [" 000", " 000 000"]}
      });

      //var measure = decodeURIComponent("//");
      myChart = Highcharts.chart('container', {
        chart: {
          type: 'bar',
          plotBorderColor: '#e5e5e5',
          plotBorderWidth: 1,
          marginTop: 1,
          marginRight: 1,
          marginLeft: 1
        },

        title: {
          text: ''
        },

        tooltip: {
          enabled: false
        },

        xAxis: [{
          tickWidth: 0,
          labels: {
            enabled:false,
            y : 20, rotation: -45, align: 'right'
          },
        }],

        yAxis: [{
          // offset: 10,
          // className: 'highcharts-yaxis',
          min: 0,
          max: max,
          allowDecimals: true,
          title: {
            text: '',
          },
          labels: {
            enabled: false
          }

        }],

        credits: {
          enabled: false
        },

        legend:{
          enabled: false
        },

        plotOptions: {
          bar: {
            dataLabels: {
              enabled: true,
              format: '<span style="font-weight: normal">{point.name}</span><br>{point.y} '.concat(measure)
            }
          }
        },
        series: [{
          keys: ['y', 'name'],
          data: data,
          colorByPoint: true
        }]
      });
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


