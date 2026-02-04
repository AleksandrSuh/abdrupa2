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
    var data = {
      "id":"43",
      "token":"98144da8b3bf6432901a54aac491a1a8",
      "appViewTitle":"07. Доходы бюджета (исполнение)",
      "appViewMetaData":{
        "field":[{
          "id":"68",
          "data_type":"VARCHAR2",
          "title":"Код"},
          {
            "id":"69",
            "data_type":"CLOB",
            "title":"Наименование доходов"},
          {
            "id":"70",
            "data_type":"NUMBER",
            "title":"План"},
          {
            "id":"71",
            "data_type":"CLOB",
            "title":"Факт"},
          {
            "id":"72",
            "data_type":"DATE",
            "title":"Дата"}]
      },
      "data":[{
        "row":{
          "field":[
            {
              "id":"68",
              "value":"10000000000000"},
            {
              "id":"69",
              "value":"НАЛОГОВЫЕ И НЕНАЛОГОВЫЕ ДОХОДЫ"},
            {
              "id":"70",
              "value":"50870954000"},
            {
              "id":"71",
              "value":"47131226170"},
            {
              "id":"72",
              "value":"01.12.2025"}]
        }
      },
      {
        "row":{
          "field":[
            {
              "id":"68",
              "value":"20000000000000"},
            {
              "id":"69",
              "value":"БЕЗВОЗМЕЗДНЫЕ ПОСТУПЛЕНИЯ"},
            {
              "id":"70",
              "value":"43414161000"},
            {
              "id":"71",
              "value":"36914163576"},
            {
              "id":"72",
              "value":"01.12.2025"}
          ]
        }
      }]};
    getAjax(data);
    $('.date_select').change(function () {
      showLoading(true);
      var dateText = $(this).val();
      console.log(dateText);
      $.ajax({
        url: "",
        type: 'post',
        dataType: 'json',
        data: {
          isAjaxRequest: 1,
          date: dateText
        }
      }).done(function(data) {
        console.log(data);
        getAjax(data);
        showLoading(false);
      });
    });



  }

  var getAjax = function(data) {
    if (typeof data !=  'undefined' && typeof data.data != 'undefined' ) {
      var table = [],
        series = [],
        categories = ['НАЛОГОВЫЕ и НЕНАЛОГОВЫЕ ДОХОДЫ', 'БЕЗВОЗМЕЗДНЫЕ ПОСТУПЛЕНИЯ'],
        secondCat = [],
        plan = 0, fact = 0, code = 0,
        total_fact_1 = 0, total_plan_1 = 0,
        total_prcntg = 0, total_fact_2 = 0, total_plan_2 = 0,
        name = "";

      $.each(data.data, function( key, val ) {
        name = jsonfld(val, 1);
        plan = parseFloat(jsonfld(val, 2)) / 1e6;
        fact = parseFloat(jsonfld(val, 3)) / 1e6;

        /*table.push([
            name,
            plan,
            fact,
            fact / plan * 100
        ]);*/

        code = parseInt(jsonfld(val, 0) / 1e13);
        if (code != 2 && code != 0) {
          total_fact_1 += fact;
          total_plan_1 += plan;
        } else if (code == 2) {
//	                categories.push(name);
          total_plan_2 = plan;
          total_fact_2 = fact;
        }
      });
      secondCat.push(formatNumber(total_plan_1));
      secondCat.push(formatNumber(total_plan_2));
      series = [
        {name: "План", data: [
            {y: 100, currency: total_plan_1},
            {y: 100, currency: total_plan_2}
          ]},
        {name: "Факт", data: [
            {y: (total_fact_1 / total_plan_1 * 100), currency: total_fact_1},
            {y: (total_fact_2 / total_plan_2 * 100), currency: total_fact_2}
          ]}
      ];

      //fillTable($('#infotable'), table, undefined, [0, 'last']);
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
    jQuery('#from_data').datepicker(jQuery.extend({showMonthAfterYear:false},jQuery.datepicker.regional['ru'],{'dateFormat':'dd.mm.yy','beforeShowDay':function(d) {
        var dates = ["Mon Dec 01 2025","Sat Nov 01 2025","Wed Oct 01 2025","Mon Sep 01 2025","Fri Aug 01 2025","Tue Jul 01 2025","Sun Jun 01 2025","Thu May 01 2025","Tue Apr 01 2025","Sat Mar 01 2025","Sat Feb 01 2025","Tue Dec 31 2024","Sun Dec 01 2024","Fri Nov 01 2024","Tue Oct 01 2024","Sun Sep 01 2024","Thu Aug 01 2024","Mon Jul 01 2024","Sat Jun 01 2024","Wed May 01 2024","Mon Apr 01 2024","Fri Mar 01 2024","Thu Feb 01 2024","Sun Dec 31 2023","Fri Dec 01 2023","Wed Nov 01 2023","Sun Oct 01 2023","Fri Sep 01 2023","Tue Aug 01 2023","Sat Jul 01 2023","Thu Jun 01 2023","Mon May 01 2023","Sat Apr 01 2023","Wed Mar 01 2023","Wed Feb 01 2023","Sat Dec 31 2022","Thu Dec 01 2022","Tue Nov 01 2022","Sat Oct 01 2022","Thu Sep 01 2022","Mon Aug 01 2022","Fri Jul 01 2022","Wed Jun 01 2022","Sun May 01 2022","Fri Apr 01 2022","Tue Mar 01 2022","Tue Feb 01 2022","Fri Dec 31 2021","Wed Dec 01 2021","Mon Nov 01 2021","Fri Oct 01 2021","Wed Sep 01 2021","Sun Aug 01 2021","Thu Jul 01 2021","Tue Jun 01 2021","Sat May 01 2021","Thu Apr 01 2021","Mon Mar 01 2021","Mon Feb 01 2021","Thu Dec 31 2020","Tue Dec 01 2020","Sun Nov 01 2020","Thu Oct 01 2020","Tue Sep 01 2020","Sat Aug 01 2020","Wed Jul 01 2020","Mon Jun 01 2020","Fri May 01 2020","Wed Apr 01 2020","Sun Mar 01 2020","Sat Feb 01 2020","Tue Dec 31 2019","Sun Dec 01 2019","Fri Nov 01 2019","Tue Oct 01 2019","Sun Sep 01 2019","Thu Aug 01 2019","Mon Jul 01 2019","Sat Jun 01 2019","Wed May 01 2019","Mon Apr 01 2019","Fri Mar 01 2019","Fri Feb 01 2019","Mon Dec 31 2018","Sat Dec 01 2018","Thu Nov 01 2018","Mon Oct 01 2018","Sat Sep 01 2018","Wed Aug 01 2018","Sun Jul 01 2018","Fri Jun 01 2018","Tue May 01 2018","Sun Apr 01 2018","Thu Mar 01 2018","Thu Feb 01 2018","Sun Dec 31 2017","Fri Dec 01 2017","Wed Nov 01 2017","Sun Oct 01 2017","Fri Sep 01 2017","Tue Aug 01 2017","Sat Jul 01 2017","Thu Jun 01 2017","Mon May 01 2017","Sat Apr 01 2017","Wed Mar 01 2017","Wed Feb 01 2017","Sat Dec 31 2016","Thu Dec 01 2016","Tue Nov 01 2016","Sat Oct 01 2016","Thu Sep 01 2016","Mon Aug 01 2016","Fri Jul 01 2016","Wed Jun 01 2016","Sun May 01 2016","Fri Apr 01 2016","Tue Mar 01 2016","Mon Feb 01 2016","Thu Dec 31 2015","Tue Dec 01 2015","Sun Nov 01 2015","Thu Oct 01 2015","Tue Sep 01 2015","Sat Aug 01 2015","Wed Jul 01 2015","Mon Jun 01 2015","Fri May 01 2015","Wed Apr 01 2015","Sun Mar 01 2015","Sun Feb 01 2015","Wed Dec 31 2014","Mon Dec 01 2014","Sat Nov 01 2014"];
        var hasDate = $.inArray(d.toDateString(), dates)>=0;
        return [hasDate, hasDate?"existing-date":""];
      }}));
    jQuery('#openDialog').dialog({'title':'','autoOpen':false,'modal':true,'width':250});

  });

})(Drupal, drupalSettings);


