(function (Drupal, drupalSettings) {
  'use strict';

  var $ = jQuery;

  console.log('=== HIGHCHARTS CONTEXT TEST ===');
  console.log('1. window.Highcharts:', typeof window.Highcharts);
  console.log('2. Highcharts:', typeof Highcharts);
  console.log('3. $.fn.highcharts:', typeof $.fn.highcharts);
  console.log('4. $.highcharts:', typeof $.highcharts);
  console.log('5. jQuery.fn.highcharts:', typeof jQuery.fn.highcharts);
  console.log('6. Is Highcharts in window?', 'Highcharts' in window);
  console.log('7. Is $.fn.highcharts function?', typeof $.fn.highcharts === 'function');

  // Проверим цепочку прототипов
  console.log('8. $.fn keys:', Object.keys($.fn).filter(k => k.includes('high') || k.includes('chart')));


  // 1. Проверьте загруженные скрипты
  console.log('Loaded scripts:');
  performance.getEntriesByType('resource')
    .filter(r => r.initiatorType === 'script')
    .forEach(r => console.log(r.name));

// 2. Проверьте глобальные переменные
  console.log('Globals:');
  console.log('- jQuery:', typeof jQuery);
  console.log('- $:', typeof $);
  console.log('- Highcharts:', typeof Highcharts);
  console.log('- Drupal:', typeof Drupal);

// 3. Проверьте Drupal settings
  console.log('Drupal settings:', drupalSettings);


})(Drupal, drupalSettings);
