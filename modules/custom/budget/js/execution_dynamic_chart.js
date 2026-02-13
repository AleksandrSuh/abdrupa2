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
    var settings = drupalSettings.budgetDates || {};

    //console.log('loadBudgetData ', settings);
    var incomes = settings.income,
      expenses = settings.expense,
      opts = {
        xAxis: {
          categories: []
        },
        yAxis: {
          title: '',
          labels: {
            formatter: function(){ return this.value + '%'; }
          }
        }
      },
      iopts = { series: [] },
      eopts = { series: [] };

    for(var i=1; i<=2; i++){
      var iserie = {data:[], name:''},
        eserie = {data:[], name:''},
        ivals,
        evals;
      for(var n=0; n<incomes.length; n++){
        if(i===1){
          iserie.name = 'Налоговые и неналоговые доходы (млн руб.)';
          eserie.name = 'Объем расходов местного бюджета (млн руб.)';
          ivals = parseFloat(incomes[n][0]);
          evals = parseFloat(expenses[n][0]);
          //console.log(incomes[n][0]);
        }
        else{
          iserie.name = 'Объем безвозмездных поступлений (млн руб.)';
          eserie.name = 'Объем межбюджетных трансфертов (млн руб.)';
          ivals = parseFloat(incomes[n][1]);
          evals = parseFloat(expenses[n][1]);
        }
        console.log(incomes[n]);
        iserie.data[n] = [incomes[n][2],ivals];
        eserie.data[n] = [expenses[n][2],evals];
      }
      iopts.series.push(iserie);
      eopts.series.push(eserie);
    }

    $('.js-incomes-chart').highcharts($.extend(true, columnChart(), iopts, opts));
    $('.js-expenses-chart').highcharts($.extend(true, columnChart(), eopts, opts));
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
