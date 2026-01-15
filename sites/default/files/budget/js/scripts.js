/*
 Автор: Пучкин В.
 puchkin@prognoz.ru
 */
//Функция открытия попапа
var margin_top;
function popup(id, action)
{
	if(action == 'open')
	{
		$('.popups_bg').fadeIn();
		$('#' + id)
			.fadeIn()
			.addClass('open');

		window.setTimeout(function()
		{
			margin_top = -$('#' + id).innerHeight() / 2;
			$('#' + id).css('margin-top', margin_top);
		}, 30);
	}
	else if(action == 'close')
	{
		if(id == 'all')
		{
			$('.popups_bg').fadeOut();

			$('.popup.open')
				.fadeOut(function () {
                    $('[data-step]').hide();
                    $('[data-step="1"]').show();
                    $('.popup').find('input, textarea').val("");
                    $('.popup').find('.error-line').hide();
                })
				.removeClass('open');
		}
		else
		{
			$('.popups_bg').fadeOut();
			$('#' + id)
				.fadeOut(function () {
                    $('#' + id).find('[data-step]').hide();
                    $('#' + id).find('[data-step="1"]').show();
                    $('#' + id).find('input, textarea').val("");
                    $('#' + id).find('.error-line').hide();
                })
				.removeClass('open');

		}

	}
}

//При загрузке страницы====================================================
//=========================================================================
//=========================================================================

function initAll() {
    //Фокус на строке поиска===================================================
    //=========================================================================
    if($('.g-header__right__search').length > 0)
    {
        var e = $('.g-header__right__search');
        var input = $('.g-header__right__search > input[type="text"]');

        input.focus(function()
        {
            e.addClass('g-header__right__search--focus');
        }).blur(function()
        {
            e.removeClass('g-header__right__search--focus');
        });
    }

    //Эмуляция placeholder для IE==============================================
    //=========================================================================
    // Для старых браузеров эмулируем placeholder на js
    if(navigator.userAgent.search(/MSIE/) > 0)
    {
        $('input[type="text"]').focus(function()
        {
            var input = $(this);
            if (input.val() == input.attr('placeholder'))
            {
                input.val('');
                input.removeClass('placeholder');
            }
        }).blur(function()
        {
            var input = $(this);
            if (input.val() == '' || input.val() == input.attr('placeholder'))
            {
                input.addClass('placeholder');
                input.val(input.attr('placeholder'));
            }
        }).blur();
    }

    //Переключение вкладок в контентной части==================================
    //=========================================================================
    updateContentTabsChosen = function(obj) {
        var lawtype;
        for (var i = 1; i < 9; i++) {
            lawtype = $(obj).find('article').hasClass('lawtype'+i);
            $("#chosen_lawtype option[value='"+i+"']").attr('disabled', false);
            if (!lawtype) {
                $("#chosen_lawtype option[value='"+i+"']").attr('disabled', true);
            }
        }
        $('#chosen_lawtype').trigger("liszt:updated");
    }
    if($('.g-content__switcher').length > 0)
    {
        $('.g-content__switcher__tabs__tab').each(function()
        {
            var tab = $(this);

            var hidden = tab.closest('.g-content__switcher').children('.g-content__switcher__hidden').children('.g-content__switcher__hidden__item').eq(tab.index());

            hidden.hide();

            if(tab.hasClass('active'))
            {
                hidden.show();
            }

            tab.click(function()
            {
                $('.g-content__switcher__hidden__item').hide();
                hidden.show();

                $('.g-content__switcher__tabs__tab').removeClass('active');
                tab.addClass('active');

                $('.g-content__switcher__hidden .norm-base__item').each(function () {
                    $(this).removeClass('display-none');
                });

                $('#chosen_lawtype option').eq(0).attr('selected', 'selected');
                if ($('#chosen_lawtype').length > 0)
                    $('#chosen_lawtype').chosen().change();
                $('#chosen_lawtype').trigger('liszt:updated');

                updateContentTabsChosen(hidden);
            });
        });
        updateContentTabsChosen($('.g-content__switcher .g-content__switcher__hidden .g-content__switcher__hidden__item:first'));
    }

    //Создать обращение========================================================
    //=========================================================================
    if($('#treatment').length > 0)
    {
        //Показывает и скрывает хинт
        $('#treatment').each(function()
        {
            var e = $(this);
            var button = e.children('.g-create-treatment__button');
            var hint = e.children('.g-create-treatment__hint');
            var cross = hint.children('header').children('div');
            var send = $('#send');
            var sendAgain = $('.send_again');

            button.click(function()
            {
                button.toggleClass('active');
                hint.eq(0).fadeToggle();
            });

            cross.click(function()
            {
                button.removeClass('active');
                hint.fadeOut();
            });

            send.click(function() {
                hint.eq(1).fadeToggle().siblings('.g-create-treatment__hint').fadeOut();
            });

            sendAgain.click(function(e) {
                hint.fadeOut();
                $(this).parent().parent().parent().find('.g-create-treatment__hint').eq(0).fadeIn();
            });
        });

        //Считает символы в текстовом поле
        $('.g-form__grid__line__right > textarea').each(function()
        {
            var e = $(this);
            var counter = e.closest('.g-form__grid__line__right').children('.g-signs-count').children('span');

            count = 1000;
            e.keyup(function()
            {
                count = 1000 - e.val().length;
                counter.text(count + ' символов');
            });
        });
    }

    // стилизует чекбоксы
    $('.custom-checkbox').each(function() {
        if ($(this).find('input').attr('checked')) {
            $(this).addClass('custom-checkbox-checked');
        } else {
            $(this).removeClass('custom-checkbox-checked');
        }

        $(this).on('click', function() {
            if (!$(this).hasClass('custom-checkbox-checked')) {
                $(this).addClass('custom-checkbox-checked');
            } else {
                $(this).removeClass('custom-checkbox-checked');
            }
        });
    });

    // показывает окно сообщения
    $('.btn-message').each(function() {
        button = $(this);
        hint = $('.selected-treatment');
        cross = hint.children('header').children('div');

        button.on('click', function() {
            if (!hint.is(':visible')) {
                hint.fadeIn();
                if (hint.find('.create-chosen-popup').length > 0) {
                    hint.find('.create-chosen-popup').chosen({disable_search_threshold: 10});
                }
            }
        });

        cross.on('click', function() {
            hint.fadeOut();
        });
    });

    //Нарвех
    if($('.btnScroll_top').length > 0)
    {
        var	$to_top = $('.btnScroll_top');

        $to_top.click(function()
        {
            $('html, body').animate({
                scrollTop: 0
            }, 300);
        });
    }

    //События при клике на крестик и «отмена» для попапов, а также при клике на элементы, открывающие попапы
    if($('.popup').length > 0)
    {
        //Открытие
        $('[data-popup-open]').each(function()
        {
            var	$e = $(this),
                popup_id = $e.attr('data-popup-open');

            $e.click(function()
            {
                popup(popup_id, 'open');
            });
        });

        //Крестик и отмена
        $('.popup').each(function()
        {
            var	$e = $(this),
                $cross = $e.find('.cross'),
                $cancel = $e.find('[data-action="close"]');

            $cross.click(function()
            {
                popup($e.attr('id'), 'close');
            });
            $cancel.click(function()
            {
                popup($e.attr('id'), 'close');
            });
        });

        $('.popups_bg').click(function()
        {
            popup('all', 'close');
        });
    }

    //Переключение блоков в попапе (форма → сообшение принято)
    if($('[data-step]').length > 0)
    {
        //Скрывает неактивные при загрузке страницы
        $('[data-step]').each(function()
        {
            var	$e = $(this);

            if($e.attr('data-open-first') != 'true')
            {
                $e.hide();
            }
        });

        //Переключает на следующий шаг
        $('[data-step-open]').each(function()
        {
            var	$e = $(this),
                step = $e.attr('data-step-open');

            $e.click(function()
            {
                $('[data-step]').hide();
                $('[data-step="' + step + '"]').fadeIn();
            });
        });
    }

    //Выбор года
    $('.nMB_comb .title').on('click', function ()
    {
        var _this = $(this),
            _parent = _this.closest('.nMB_comb');

        if ( _parent.hasClass('active') ) {

            _parent.removeClass('active');

        } else {

            _parent.addClass('active');

        }
    });
    $('.nMB_comb .content div').on('click', function ()
    {
        var _this = $(this),
            _parent = _this.closest('.nMB_comb')
        _title = _parent.find('.title');

        _title.find('h3').text(_this.text());
        _parent.removeClass('active');
    });

    //Клик по букве в «Азбуке бюджета»
    if($('.azbuka_plate').length > 0)
    {
        $('.letter').each(function()
        {
            var	$e = $(this),
                $plate = $('.azbuka_plate'),
                letter = $e.children('span').text(),
                $goal = $('.azbuka_container .AC_letter:contains("' + letter + '")')

            $e.click(function()
            {
                if(!$plate.hasClass('fixed'))
                {
                    $('html, body').animate({
                        scrollTop: $goal.offset().top - $plate.innerHeight() * 2
                    }, 200);
                }
                else
                {
                    $('html, body').animate({
                        scrollTop: $goal.offset().top - $plate.innerHeight()
                    }, 200);
                }
            });
        });
    }

    //Показ и скрытие ответов на странице обращений
    $('.answer-vis').click(function(e) {
        e.preventDefault();
        var time = 300;
        var $this = $(this);
        if (!$this.hasClass('answer-vis-open')) {
            $this.addClass('answer-vis-open');
            $this.text("Скрыть ответ");
            $this.next('.treats_item').slideDown(time);
        } else {
            $this.removeClass('answer-vis-open');
            $this.text("Показать ответ");
            $this.next('.treats_item').slideUp(time);
        }
        return false;
    });

    //Стилизация комбобоксов========================================================
    //==============================================================================
    if($('.create-chosen').length > 0) {
        $('.create-chosen').chosen({disable_search_threshold: 10});
    }
    if($('.chosen').length > 0)	{
        $('.chosen').chosen();
    }
}
$(document).ready(function()
{
    initAll();
});


//При скролле страницы=====================================================
//=========================================================================
//=========================================================================
$(window).scroll(function()
{
	//Прилипание блока с буквами к верху страницы при скорлле=======================
	//==============================================================================
	if($('.azbuka_plate').length > 0)
	{
		var	$plate = $('.azbuka_plate'),
			plate_offset_load = 349,
            scrollTop = $(window).scrollTop();

        var totalHeight = $('header').height() + $('section').height();
		if(scrollTop > plate_offset_load && scrollTop < totalHeight) {
			$plate.addClass('fixed');
            $plate.css('left', $('.CG_right').offset().left - $(window).scrollLeft());
		}
		else {
			$plate.removeClass('fixed');
            $plate.css('left', '');
		}
	}
});
