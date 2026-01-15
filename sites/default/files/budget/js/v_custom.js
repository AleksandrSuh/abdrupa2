//Фильтрация Блоков с описанием файлов в Нормативной базе==================
//=========================================================================
function chosenLawtype()
{
    if($('#chosen_lawtype').length > 0)
    {
        $('#chosen_lawtype').change(function (e) {
            var type = $(e.target).val();
            if (type == 0) {
                $('.g-content__switcher__hidden .norm-base__item').each(function () {
                    $(this).removeClass('display-none');
                });
            }
            else {
                $('.g-content__switcher__hidden .norm-base__item').each(function () {
                    $(this).addClass('display-none');
                });
                $('.g-content__switcher__hidden .g-content__switcher__hidden__item .lawtype' + type).each(function () {
                    $(this).removeClass('display-none');
                });
            }
        });
    }
}

function YearsDiagramsContentTabs()
{
    if ($('#infotable').length > 0) {
        var budgetPage = true
    }
    if($('.table-diagrams_years').length > 0)
    {
        $('.table-diagrams_years-item').each(function() {
            var tab = $(this);
            tab.click(function() {
                var parentClass = tab.parent().attr('class');
                $('.' + parentClass + ' .table-diagrams_years-item').removeClass('active');
                tab.addClass('active');
                var n = +tab.attr('id').replace('table-diagrams_years-item','') - 1;
                if (n < 3) {
                    if (budgetPage) {
                        $('.infographics').children().removeClass('active');
                        $('#infographics-'+(2014+n)).addClass('active');
                    }
                    else {
                        fillBudgetMainWidget(n);
                    }
                }
            });
        });
        if (!budgetPage) {
            budgetMainWidget();         
        }       
    }
}

function budgetMainWidget() {
    $.ajax({
        url: budgetJsonUrl+'/dictionary/284?format=json' ///dictionary/284
    })
    .done(function(data) {
        $.each(data.data, function( key, val ) {
            bMainIncomes.push(+val.row.field[1].value);
            bMainExpenses.push(+val.row.field[2].value);
            bMainDeficits.push(((+val.row.field[1].value) - (+val.row.field[2].value)))
        });
        bMainIncomes.splice(0,2);
        bMainExpenses.splice(0,2);
        bMainDeficits.splice(0,2);
        $('#eMainIncomesPlan').html(currencyFormat(bMainIncomes[0])+'<span>млн руб.</span>');
        $('#eMainExpensesPlan').html(currencyFormat(bMainExpenses[0])+'<span>млн руб.</span>');
        fillBudgetMainWidget(0);
    })
    $.ajax({
        url: budgetJsonUrl+"/jsonexecution/index?date=31.12.2014"
    })
    .done(function(data) {
        if (typeof data[0] != "undefined") {
            if (typeof data[0].INC != "undefined")
                eMainDatas[0] = +(parseFloat(data[0].INC)).toFixed(1);
            if (typeof data[0].EXP != "undefined")
                eMainDatas[1] = +(parseFloat(data[0].EXP)).toFixed(1);
        }
        if (typeof data[1] != "undefined") {
            if (typeof data[1].INC != "undefined")
                eMainDatas[2] = +(parseFloat(data[1].INC)).toFixed(1);
            if (typeof data[1].DEF != "undefined")
                eMainDatas[3] = +(parseFloat(data[1].DEF)).toFixed(1);
        }
		//eMainDatas[2] = Math.round(+data[0].DEF/100000)/10;
		/*eMainDatas[0] = data[0].INC;
		eMainDatas[1] = data[0].EXP;
		eMainDatas[2] = data[0].DEF;*/
        function fillExecutionMainWidget() {
            if (bMainIncomes[0] != undefined) {
                clearInterval(fillExecutionMainWidgetInterval);
            }
            //var eMainIncomesP = eMainDatas[0]/bMainIncomes[0];
            //var eMainExpensesP = eMainDatas[1]/bMainExpenses[0];
			var eMainIncomesP = percent(eMainDatas[2], eMainDatas[0]);
			var eMainExpensesP = percent(eMainDatas[3], eMainDatas[1]);
			$('#eMainIncomes').html(currencyFormat(eMainDatas[2])+'<span>'+currencyFormat(eMainIncomesP)+'%</span>');
			$('#eMainExpenses').html(currencyFormat(eMainDatas[3])+'<span>'+currencyFormat(eMainExpensesP)+'%</span>');
            $('#eMainIncomesH').css('height', 20+eMainIncomesP+'px');
            $('#eMainExpensesH').css('height', 20+eMainExpensesP+'px');
            //$('#eMainIncomes').html(currencyFormat(eMainDatas[0])+'<span>'+Math.round(eMainIncomesP*100)+'%</span>');
            //$('#eMainExpenses').html(currencyFormat(eMainDatas[1])+'<span>'+Math.round(eMainExpensesP*100)+'%</span>');
        }
        fillExecutionMainWidgetInterval = setInterval(fillExecutionMainWidget, 300);
    })    
}

function fillBudgetMainWidget(n) {
    var s1 = '<span>млн руб.</span>', s2 = '<span>100%</span>';
    $('#bMainIncomesPlan').html(currencyFormat(bMainIncomes[n])+s1);
    $('#bMainExpensesPlan').html(currencyFormat(bMainExpenses[n])+s1);  
    $('#bMainDeficitsPlan').html(currencyFormat(bMainDeficits[n])+s1);  
    $('#bMainIncomes').html(currencyFormat(bMainIncomes[n])+s2);
    $('#bMainExpenses').html(currencyFormat(bMainExpenses[n])+s2); 
    $('#bMainDeficits').html(currencyFormat(bMainDeficits[n])+s2); 
}

function initDatepicker(obj) {
    $('body').addClass('custom-calendar');
    $(window).load(function() {
        $('.datepick-ui.datepick').datepicker($.extend({
            // datepicker settings
            dateFormat: 'dd.mm.yy',
            onSelect: function(dateText) {
                if(!dateText.match(/\d\d\.\d\d\.\d\d\d\d/)) {
                    return;
                }
                window.date = dateText;
                if (obj) {
                    $('#infotable').html('');
                    if(obj.highcharts()!=undefined)
                        obj.highcharts().destroy();
                    getAjax();
                }
                else {
                    window.location.href = window.feedbackUrl + '?filter=interval&from=' + $('#dateFrom').val() + '&to=' + $('#dateTo').val();
                }
            }                
        }, $.datepicker.regional['ru']));
    });
}

function testBrowser() {
    testChromeSafari()
    var badBrowser = false
    if(($.browser.mozilla && +$.browser.version < 11) || ($.browser.opera && +$.browser.version < 12) || ($.browser.msie && +$.browser.version < 8) || ($.browser.chrome && +$.browser.version < 21) || ($.browser.safari && +$.browser.version < 5))
        badBrowser = true
    //alert(badBrowser + ' ' + +$.browser.version + ', $.browser.webkit:' + $.browser.webkit);
    if (badBrowser && !$.cookie('badBrowser'))
        $('#badBrowserAlert').addClass('display-block');
    $('#badBrowserAlert span').on('click', function() {
        $('#badBrowserAlert').removeClass('display-block');
        var date = new Date();
        var minutes = 30;
        date.setTime(date.getTime() + (minutes * 60 * 1000));
        $.cookie('badBrowser', '1', { expires: date });
    });
}

function testChromeSafari() {
    var userAgent = navigator.userAgent.toLowerCase(); 
    $.browser.chrome = /chrome/.test(navigator.userAgent.toLowerCase()); 

    if($.browser.chrome){
      userAgent = userAgent.substring(userAgent.indexOf('chrome/') +7);
      userAgent = userAgent.substring(0,userAgent.indexOf('.'));
      $.browser.version = userAgent;
      $.browser.safari = false;
    }

    if($.browser.safari){
      userAgent = userAgent.substring(userAgent.indexOf('version/') +8);
      userAgent = userAgent.substring(0,userAgent.indexOf('.'));
      $.browser.version = userAgent;
    }
}
function showLoading(state) {
    if ($('.loading_block').length == 0) {
        $('body').append($('<div class="loading_block"></div>'));
    }

    if (state) {
        $('.loading_block').show();
    } else {
        $('.loading_block').hide();
    }
}
function formatNumber(nStr, toFixed) {
    if (!nStr)
        return 0;
    nStr = parseFloat(nStr);
    if(toFixed !== 0){
        toFixed = toFixed || 0
    }
    nStr = nStr.toFixed(toFixed);

    nStr += '';
    var x = nStr.split('.');
    var x1 = x[0];
    var x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ' ' + '$2');
    }
    var out = x1 + x2;
    out = out.replace('.', ',');
    return out;
};
var bMainIncomes = [];
var bMainExpenses = [];
var bMainDeficits = [];
var eMainDatas = [ 0, 0, 0 ];
var fillExecutionMainWidgetInterval;
$(function()
{
    testBrowser();
    chosenLawtype();
    YearsDiagramsContentTabs();
});
