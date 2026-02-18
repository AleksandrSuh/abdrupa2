(function ($, Drupal, once) {
  Drupal.behaviors.vboFix = {
    attach: function(context, settings) {
      const elements = once('vboFix', '#edit-select-all', context);

      elements.forEach(function(element) {
        var $container = $(element).closest('.views-bulk-actions__item');

        if ($container.length) {
          var labelText = $container.find('label').text() || 'Выбрать все записи на этой странице';

          // Удаляем старый
          $(element).remove();

          // Создаем новый
          var newCheckbox = $('<input>', {
            type: 'checkbox',
            id: 'edit-select-all',
            name: 'select_all',
            value: '1',
            class: 'form-checkbox form-boolean form-boolean--type-checkbox'
          });

          // Добавляем обработчик
          newCheckbox.on('click', function(e) {
            var checked = $(this).prop('checked');

            $('.views-table input[type="checkbox"]', context).each(function() {
              this.checked = checked;
              console.log(this.id);
            });

            // Обновляем состояние кнопки удаления
            $('input[data-vbo-action]').prop('disabled', !checked);
          });

          $container.empty();
          $container.append(newCheckbox);
          $container.append(' <label for="edit-select-all" class="form-item__label option">' + labelText + '</label>');
        }
      });
    }
  };
})(jQuery, Drupal, once);
