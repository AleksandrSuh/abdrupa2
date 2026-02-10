(function (Drupal, drupalSettings) {
  'use strict';

  var dates = ["Wed Dec 31 2025","Mon Dec 01 2025","Sat Nov 01 2025","Wed Oct 01 2025","Mon Sep 01 2025","Fri Aug 01 2025","Tue Jul 01 2025","Sun Jun 01 2025","Thu May 01 2025","Tue Apr 01 2025","Sat Mar 01 2025","Sat Feb 01 2025","Tue Dec 31 2024","Sun Dec 01 2024","Fri Nov 01 2024","Tue Oct 01 2024","Sun Sep 01 2024","Thu Aug 01 2024","Mon Jul 01 2024","Sat Jun 01 2024","Wed May 01 2024","Mon Apr 01 2024","Fri Mar 01 2024","Thu Feb 01 2024","Sun Dec 31 2023","Fri Dec 01 2023","Wed Nov 01 2023","Sun Oct 01 2023","Fri Sep 01 2023","Tue Aug 01 2023","Sat Jul 01 2023","Thu Jun 01 2023","Mon May 01 2023","Sat Apr 01 2023","Wed Mar 01 2023","Wed Feb 01 2023","Sat Dec 31 2022","Thu Dec 01 2022","Tue Nov 01 2022","Sat Oct 01 2022","Thu Sep 01 2022","Mon Aug 01 2022","Fri Jul 01 2022","Wed Jun 01 2022","Sun May 01 2022","Fri Apr 01 2022","Tue Mar 01 2022","Tue Feb 01 2022","Fri Dec 31 2021","Wed Dec 01 2021","Mon Nov 01 2021","Fri Oct 01 2021","Wed Sep 01 2021","Sun Aug 01 2021","Thu Jul 01 2021","Tue Jun 01 2021","Sat May 01 2021","Thu Apr 01 2021","Mon Mar 01 2021","Mon Feb 01 2021","Thu Dec 31 2020","Tue Dec 01 2020","Sun Nov 01 2020","Thu Oct 01 2020","Tue Sep 01 2020","Sat Aug 01 2020","Wed Jul 01 2020","Mon Jun 01 2020","Fri May 01 2020","Wed Apr 01 2020","Sun Mar 01 2020","Sat Feb 01 2020","Tue Dec 31 2019","Sun Dec 01 2019","Fri Nov 01 2019","Tue Oct 01 2019","Sun Sep 01 2019","Thu Aug 01 2019","Mon Jul 01 2019","Sat Jun 01 2019","Wed May 01 2019","Mon Apr 01 2019","Fri Mar 01 2019","Fri Feb 01 2019","Mon Dec 31 2018","Sat Dec 01 2018","Thu Nov 01 2018","Mon Oct 01 2018","Sat Sep 01 2018","Wed Aug 01 2018","Sun Jul 01 2018","Fri Jun 01 2018","Tue May 01 2018","Sun Apr 01 2018","Thu Mar 01 2018","Thu Feb 01 2018","Sun Dec 31 2017","Fri Dec 01 2017","Wed Nov 01 2017","Sun Oct 01 2017","Fri Sep 01 2017","Tue Aug 01 2017","Sat Jul 01 2017","Thu Jun 01 2017","Mon May 01 2017","Sat Apr 01 2017","Wed Mar 01 2017","Wed Feb 01 2017","Sat Dec 31 2016","Thu Dec 01 2016","Tue Nov 01 2016","Sat Oct 01 2016","Thu Sep 01 2016","Mon Aug 01 2016","Fri Jul 01 2016","Wed Jun 01 2016","Sun May 01 2016","Fri Apr 01 2016","Tue Mar 01 2016","Mon Feb 01 2016","Thu Dec 31 2015","Tue Dec 01 2015","Sun Nov 01 2015","Thu Oct 01 2015","Tue Sep 01 2015","Sat Aug 01 2015","Wed Jul 01 2015","Mon Jun 01 2015","Fri May 01 2015","Wed Apr 01 2015","Sun Mar 01 2015","Sun Feb 01 2015","Wed Dec 31 2014","Mon Dec 01 2014","Sat Nov 01 2014"];

  document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
      console.error('jQuery not found');
      return;
    }

    var $ = jQuery;

    $('.js-header-date').html($('.date_select').val());
    $(document).on('change', '.date_select',function () {
      showLoading(true);
      var dateText = $(this).val();
      $('.js-header-date').html(dateText);
      console.log('AJAX запрос с датой:', dateText);
      $.ajax({
        url: "/budget/execution/ajax",
        type: 'post',
        data: {
          isAjaxRequest: 1,
          date: dateText
        },
        dataType: 'json'
      }).done(function(response) {
        showLoading(false);
        console.log(response);
        if (response.success && response.content) {
          $('.js-aj_content').html($(response.content).find('.js-aj_content').html());
          $('.js-aj_content #from_data').datepicker($.extend({showMonthAfterYear:false},$.datepicker.regional['ru'],{'dateFormat':'dd.mm.yy','beforeShowDay':function(d) {
              var hasDate = $.inArray(d.toDateString(), dates)>=0;
              return [hasDate, hasDate?"existing-date":""];
            }}));

        } else {
          console.error('Ошибка в ответе сервера');
        }
      }).fail(function(jqXHR, textStatus, errorThrown) {
        showLoading(false);
        console.error('AJAX ошибка:', textStatus, errorThrown);
      });
    });

  });

  jQuery(function($) {
    jQuery('#from_data').datepicker(jQuery.extend({showMonthAfterYear:false},jQuery.datepicker.regional['ru'],{'dateFormat':'dd.mm.yy','beforeShowDay':function(d) {
        var hasDate = $.inArray(d.toDateString(), dates)>=0;
        return [hasDate, hasDate?"existing-date":""];
      }}));

  });


})(Drupal, drupalSettings);
