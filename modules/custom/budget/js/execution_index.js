(function (Drupal, drupalSettings) {
  'use strict';




   document.addEventListener('DOMContentLoaded', function() {
    if (typeof jQuery === 'undefined') {
      console.error('jQuery not found');
      return;
    }

    var $ = jQuery;


    var settings = drupalSettings.budget || {};
    var jsonUrl = settings.ajaxUrl,
      $input = $('#from_data');
    //console.log('loadBudgetData, Используем URL:', jsonUrl, $input.val() );
    var availableDates = settings.budgetDates || [];


    $('.js-header-date').html($input.val());
    $(document).on('change', '#from_data',function () {
      showLoading(true);
      var dateText = $(this).val();
      $('.js-header-date').html(dateText);
      //console.log('AJAX запрос с датой:', dateText);
      $.ajax({
        url: jsonUrl, //"/budget/execution/ajax"
        type: 'post',
        data: {
          isAjaxRequest: 1,
          date: dateText
        },
        dataType: 'json'
      }).done(function(response) {
        showLoading(false);
        //console.log(response);
        if (response.success && response.content) {
          $('.js-aj_content').html($(response.content).find('.js-aj_content').html());
          $('.js-aj_content #from_data').datepicker($.extend({showMonthAfterYear:false},$.datepicker.regional['ru'],{'dateFormat':'dd.mm.yy','beforeShowDay':function(d) {
              var hasDate = $.inArray(d.toDateString(), availableDates)>=0;
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

    jQuery(function($) {
      $input.datepicker(jQuery.extend({showMonthAfterYear:false},jQuery.datepicker.regional['ru'],{'dateFormat':'dd.mm.yy','beforeShowDay':function(d) {
          var hasDate = $.inArray(d.toDateString(), availableDates)>=0;
          return [hasDate, hasDate?"existing-date":""];
        }}));

    });


    if (availableDates.length > 0) {
      var firstDate = new Date(availableDates[0]);
      $input.datepicker('setDate', firstDate).trigger('change');
    }

  });



})(Drupal, drupalSettings);
