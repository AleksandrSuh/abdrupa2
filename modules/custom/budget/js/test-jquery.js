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
  var data = {"id":"44","token":"febb517bdafef95b5dac928bbd47b398","appViewTitle":"11. Бюджетные инвестиции (исполнение)","appViewMetaData":{"field":[{"id":"78","data_type":"VARCHAR2","title":"Порядок"},{"id":"73","data_type":"VARCHAR2","title":"КФСР"},{"id":"74","data_type":"VARCHAR2","title":"Наименование КФСР"},{"id":"75","data_type":"NUMBER","title":"Утверждено ассигнований"},{"id":"76","data_type":"NUMBER","title":"Исполнено на"},{"id":"77","data_type":"NUMBER","title":"% исполнения"},{"id":"79","data_type":"DATE","title":"Дата исполнения"}]},
    "data":[{
    "row":{
      "field":[{
        "id":"78",
        "value":"0100"}, {
        "id":"73","value":"0100"},{
        "id":"74","value":"ОБЩЕГОСУДАРСТВЕННЫЕ ВОПРОСЫ"},{
        "id":"75","value":"1201.78"},{
        "id":"76","value":"949.15"},{
        "id":"77","value":"79"},{
        "id":"79","value":"31.12.2025"}]}},
    {"row":{
      "field":[{
        "id":"78","value":"0113"},{
        "id":"73","value":"0113"},{
        "id":"74","value":"Другие общегосударственные вопросы"},{
        "id":"75","value":"1201.78"},{
        "id":"76","value":"949.15"},{
        "id":"77","value":"79"},{
        "id":"79","value":"31.12.2025"}]}},
    {"row":{
      "field":[{
        "id":"78","value":"0300"},{
        "id":"73","value":"0300"},{
        "id":"74","value":"НАЦИОНАЛЬНАЯ БЕЗОПАСНОСТЬ И ПРАВООХРАНИТЕЛЬНАЯ ДЕЯТЕЛЬНОСТЬ"},{
        "id":"75","value":"32.28"},{
        "id":"76","value":"32.16"},{
        "id":"77","value":"99.6"},{
        "id":"79","value":"31.12.2025"}]}},
    {"row":{
      "field":[{
        "id":"78","value":"0310"},{
        "id":"73","value":"0310"},{
        "id":"74","value":"Обеспечение пожарной безопасности"},{
        "id":"75","value":"18.88"},{
        "id":"76","value":"18.76"},{
        "id":"77","value":"99.4"},{
        "id":"79","value":"31.12.2025"}]}},
    {"row":{
      "field":[{
        "id":"78","value":"0314"},{
        "id":"73","value":"0314"},{
        "id":"74","value":"Другие вопросы в области национальной безопасности и правоохранительной деятельности"},{
        "id":"75","value":"13.4"},{
        "id":"76","value":"13.4"},{
        "id":"77","value":"100"},{
        "id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0400"},{"id":"73","value":"0400"},{"id":"74","value":"НАЦИОНАЛЬНАЯ ЭКОНОМИКА"},{"id":"75","value":"6020.72"},{"id":"76","value":"6489.7"},{"id":"77","value":"107.8"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0409"},{"id":"73","value":"0409"},{"id":"74","value":"Дорожное хозяйство (дорожные фонды)"},{"id":"75","value":"6020.72"},{"id":"76","value":"6489.7"},{"id":"77","value":"107.8"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0500"},{"id":"73","value":"0500"},{"id":"74","value":"ЖИЛИЩНО-КОММУНАЛЬНОЕ ХОЗЯЙСТВО"},{"id":"75","value":"2021.98"},{"id":"76","value":"1526.54"},{"id":"77","value":"75.5"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0501"},{"id":"73","value":"0501"},{"id":"74","value":"Жилищное хозяйство"},{"id":"75","value":"386.01"},{"id":"76","value":"396.61"},{"id":"77","value":"102.7"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0502"},{"id":"73","value":"0502"},{"id":"74","value":"Коммунальное хозяйство"},{"id":"75","value":"1262.19"},{"id":"76","value":"856.8"},{"id":"77","value":"67.9"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0503"},{"id":"73","value":"0503"},{"id":"74","value":"Благоустройство"},{"id":"75","value":"156.83"},{"id":"76","value":"156.79"},{"id":"77","value":"100"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0505"},{"id":"73","value":"0505"},{"id":"74","value":"Другие вопросы в области жилищно-коммунального хозяйства"},{"id":"75","value":"216.95"},{"id":"76","value":"116.34"},{"id":"77","value":"53.6"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0700"},{"id":"73","value":"0700"},{"id":"74","value":"ОБРАЗОВАНИЕ"},{"id":"75","value":"2497.27"},{"id":"76","value":"3189.72"},{"id":"77","value":"127.7"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0701"},{"id":"73","value":"0701"},{"id":"74","value":"Дошкольное образование"},{"id":"75","value":"693.35"},{"id":"76","value":"849.3"},{"id":"77","value":"122.5"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0702"},{"id":"73","value":"0702"},{"id":"74","value":"Общее образование"},{"id":"75","value":"1504.78"},{"id":"76","value":"2045.9"},{"id":"77","value":"136"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0709"},{"id":"73","value":"0709"},{"id":"74","value":"Другие вопросы в области образования"},{"id":"75","value":"299.14"},{"id":"76","value":"294.51"},{"id":"77","value":"98.5"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0800"},{"id":"73","value":"0800"},{"id":"74","value":"КУЛЬТУРА, КИНЕМАТОГРАФИЯ"},{"id":"75","value":"3.77"},{"id":"76","value":"3.77"},{"id":"77","value":"100"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"0801"},{"id":"73","value":"0801"},{"id":"74","value":"Культура"},{"id":"75","value":"3.77"},{"id":"76","value":"3.77"},{"id":"77","value":"100"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"1100"},{"id":"73","value":"1100"},{"id":"74","value":"ФИЗИЧЕСКАЯ КУЛЬТУРА И СПОРТ"},{"id":"75","value":"244.33"},{"id":"76","value":"244.33"},{"id":"77","value":"100"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"1101"},{"id":"73","value":"1101"},{"id":"74","value":"Физическая культура"},{"id":"75","value":"232.28"},{"id":"76","value":"232.28"},{"id":"77","value":"100"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"1103"},{"id":"73","value":"1103"},{"id":"74","value":"Спорт высших достижений"},{"id":"75","value":"12.05"},{"id":"76","value":"12.05"},{"id":"77","value":"100"},{"id":"79","value":"31.12.2025"}]}},{"row":{"field":[{"id":"78","value":"9999"},{"id":"73","value":"Итого"},{"id":"74","value":""},{"id":"75","value":"12022.13"},{"id":"76","value":"12435.36"},{"id":"77","value":"103.4"},{"id":"79","value":"31.12.2025"}]}}]};


})(Drupal, drupalSettings);
