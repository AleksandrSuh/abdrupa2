Number.prototype.numberFormat = function(decimals, dec_point, thousands_sep) {
    dec_point = typeof dec_point !== 'undefined' ? dec_point : '.';
    thousands_sep = typeof thousands_sep !== 'undefined' ? thousands_sep : ',';
    var parts = this.toFixed(decimals).toString().split('.');
    parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, thousands_sep);
    return parts.join(dec_point);
}

function currencyFormat(val, mlnFlg) {
    //var res;
    var mlnStr = mlnFlg ? ' млн руб.' : '';
    if (typeof val == 'undefined')
        val = 0;
    return val.numberFormat(0,',',' ') + mlnStr;
    //if(num>=1e6) // На будущее
        //res = Math.round(num/1e5)*10 + ' млн руб.';
    //else if(num>=1e3)
        //res = Math.round(num/1e2)*10 + ' млн руб.';
    //else
        //res = Math.round(num/10.0)*10 + ' руб.';
}

function yFormatter() {
    return currencyFormat(this.y);
}

function categoryFormatter() {
    return this.point.name + ': <b>' + currencyFormat(this.y, true) + '</b>';
}

function seriesFormatter() {
    return this.series.name + ': <b>' + currencyFormat(this.y, true) + '</b>';
}

function baseOptionsSetUp() {
    Highcharts.setOptions({colors:['#0C6DA4','#00BFD9','#00BEB8','#33D8AA','#46E685','#89DE88','#AAD475','#FEE18E','#FCCD34','#F9A632','#F44F2C','#F14242','#BE214D','#97197D'],
        legend: { 
            borderWidth: 0,
            backgroundColor: 'rgba(255,255,255,0)',
            itemStyle: {
                fontSize: '14px',
                width: 700
            }
        },
        tooltip: {
            formatter: seriesFormatter,
            style: { fontSize: '14px' }
        },
        xAxis: {
            title: { text: null },
            labels: {
                style: {
                    fontSize: '14px',
                }
            }
        },
        yAxis: {
            title: { text: 'млн руб.' },
            labels: {
                formatter: function() { return this.value.numberFormat(0,',',' '); },
                style: {
                    fontSize: '14px'
                }
            },
            stackLabels: {
                formatter: function() { return currencyFormat(this.total); }
            }
        },
        title: { text : null }        
    });
}

function optionsColumn() {
    baseOptionsSetUp();
    return {
        chart: {
            type: 'column',
            backgroundColor: null
        },
        credits: {
            enabled: false
        },
        plotOptions: {
            column: {
                dataLabels: {
                    enabled: true,
                    formatter: function() { return currencyFormat(this.y); },
                    //style: {
                        //textShadow: '0 0 3px black, 0 0 3px black'
                    //}
                }
            }
        }
    };
}

function optionsStackedColumn() {
    baseOptionsSetUp();
    return {
        chart: {
            type: 'column',
            backgroundColor: null,
            height: 600
        },
	    legend: {
		    layout: 'vertical'
	    },
	    yAxis: {
		    title: '',
		    min: 0,
		    max: 105,
		    endOnTick: false,
		    labels: {
			    formatter: function() {
				    return this.value + '%';
			    }
		    },
            stackLabels: {
                enabled: true,
                style: {
                    fontWeight: 'bold',
                    color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'
                }
            }
        },
	    tooltip: {
		    formatter: function () {
			    return this.series.name + ': ' + currencyFormat(this.y) + ' млн руб.'
		    },
		    useHTML: true
	    },
        plotOptions: {
            column: {
                stacking: 'percent',
                dataLabels: {
                    enabled: false
                }
            }
        },
        credits: {
            enabled: false
        }
    };
}

function optionsPie() {
    baseOptionsSetUp();
    return {
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            backgroundColor: null,
            height: 600
        },
        tooltip: {
            formatter: categoryFormatter
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                showInLegend: true,
                dataLabels: {
                    enabled: true,
                    color: '#000000',
                    connectorColor: '#000000',
                    formatter: yFormatter
                }
            }
        },
        series: [{
            type: 'pie',
            name: 'Browser share',
            data: [
                ['Firefox',   45.0],
                ['IE',       26.8],
                {
                    name: 'Chrome',
                    y: 12.8,
                    sliced: true,
                    selected: true
                },
                ['Safari',    8.5],
                ['Opera',     6.2],
                ['Others',   0.7]
            ]
        }]
    };
}

function optionsPieLegend() {
    baseOptionsSetUp();
    return {
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                plotShadow: false,
                backgroundColor: null
            },
            tooltip: {
                formatter: categoryFormatter
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    dataLabels: {
                        enabled: false
                    },
                    showInLegend: true
                }
            },
            series: [{
                type: 'pie',
                name: 'Browser share',
                data: [
                    ['Firefox',   45.0],
                    ['IE',       26.8],
                    {
                        name: 'Chrome',
                        y: 12.8,
                        sliced: true,
                        selected: true
                    },
                    ['Safari',    8.5],
                    ['Opera',     6.2],
                    ['Others',   0.7]
                ]
            }]
        }
}

function optionsBar() {
    baseOptionsSetUp();
    return {
            chart: {
                type: 'bar',
                backgroundColor: null
            },
            xAxis: {
                categories: ['Africa', 'America', 'Asia', 'Europe', 'Oceania']
            },
            yAxis: {
                min: 0,
                labels: {
                    overflow: 'justify'
                }
            },
            plotOptions: {
                bar: {
                    dataLabels: {
                        formatter: function() { return currencyFormat(this.y); },
                        enabled: true
                    }
                }
            },
            credits: {
                enabled: false
            }
        }
}

function columnChart() {
    baseOptionsSetUp();
    return {
	    colors: ['#FCEFA1', '#5897fb'],
        chart: {
            type: 'column', marginTop:-40 
        },
        title: {
            x: -50
        },
        xAxis: {
            categories: []
        },
        yAxis: {
            min: 0 , max:105,
            title: {
                text: ''
            },
        stackLabels: {
        enabled: true
        }
,          style: {           fontWeight: 'bold',           color: (Highcharts.theme && Highcharts.theme.textColor) || 'gray'          }
        },
        plotOptions: {
            column: {
                pointPadding: 0.3,
                borderWidth: 0,
                groupPadding: 0,
                shadow: false,
	            dataLabels: {
		            enabled: true,
		            color: 'black',
                    formatter: function() { return this.y.numberFormat(0, ',', ' '); }
	            }
            },
            series: {
              stacking: 'percent'  
            }
        },
        legend: {
            enabled: true
        },
        series: []
    }
}

function horizontalPercentColChart() {
    baseOptionsSetUp();
    return {
	    colors:['#A9A8A9','#0C6DA4'],
	    chart: {
            type: 'bar',
            backgroundColor: null
        },
	    tooltip: {
		    formatter: function() {
			    return this.series.name + ': <b>' + currencyFormat(this.point.currency) + ' млн руб. (' + this.y.numberFormat(0) + '%)</b>';
		    },
		    style: { fontSize: '14px' }
	    },
        xAxis: [
            {},
            {
                opposite: true,
                linkedTo: 0,
                title: {
                    text: 'Утверждено, млн руб.'
                }
                      }
        ],
        yAxis: {
            title: { text: ''},
            max: 100,
            labels: {
                formatter: function(){ return this.value + '%'; }
            }
        },
	    plotOptions: {
		    series: {
			    stacking: 'normal'
		    }
	    },
        title: {
            text: ''
        }
    }
}


function optionsStackedBar() {
    baseOptionsSetUp();
        return {
            chart: {
                type: 'bar',
                backgroundColor: null
            },
            xAxis: {
                categories: ['Apples', 'Oranges', 'Pears', 'Grapes', 'Bananas']
            },
            yAxis: {
                min: 0
            },
            plotOptions: {
                series: {
                    stacking: 'normal'
                }
            },
                series: [{
                name: 'John',
                data: [5, 3, 4, 7, 2]
            }, {
                name: 'Jane',
                data: [2, 2, 3, 2, 1]
            }, {
                name: 'Joe',
                data: [3, 4, 4, 2, 5]
            }]
        };
}

function fillTable(tbody, array, headTagFlg, boldRowArr) {
    var td = headTagFlg ? 'th' : 'td';
    tbody.html('');
    for(var i = 0; i < array.length; i++) {
        var tr = '<tr ' + ((((i + 1) + 10) % 2 == 1) ? 'class="odd_row"' : '') + '><'+td+'>' + array[i][0] + '</'+td+'>'
        for(var j = 1; j < array[i].length; j++) {
                var val = array[i][j];
                var is_num = typeof val==='number';
                var aligment = !(is_num||val=='-') ? '' : ' class="to_right"' ;
                if (is_num) val = val.numberFormat(0,',',' ');
                if (val=='-') val = '&mdash;';
                tr += '<'+td + aligment + '>' + val + '</'+td+'>';
        }
        tr += '</tr>';
        tbody.append(tr);
    }
    if (boldRowArr) {
        $.each(boldRowArr, function(index, number){
            if (number == 'last') {
                number = tbody.children().length - 1;
            }
            tbody.children().eq(number).addClass('bold');
        });
    }
}

function fillColumnChart(data, chartContainer, customOpts, ignoreColNum) {
    if (typeof data == 'undefined') {
        return;
    }

    var opts = {
        series: [],
        xAxis: {
            categories: []
        },
        yAxis: {
            title: {text:'млн руб.'}
        }
    };

    for (var col = 2; col <= 4; col++) {
        var i = 0;
        opts.xAxis.categories.push(data.appViewMetaData.field[col].title);
        $.each(data.data, function(key, val){
            if (key == ignoreColNum)
                return;
            if(!opts.series[i]) opts.series[i] = {data:[]};
            opts.series[i].name = val.row.field[1].value
            opts.series[i].data.push([val.row.field[1].value, (parseInt(val.row.field[col].value) / 1e3)]);
            i++;
        });
    }

    $(chartContainer).highcharts($.extend(true, columnChart(), opts, customOpts));
}

function jsonmetadata(metadata,index) {
    return metadata.field[index].title;
}

function jsonfld(val,index) {
    return val.row.field[index].value;
}

function jsonfldm(val,index) {
    return Math.round(+jsonfld(val,index)/1e5)/10;
}

function fldm(val) {
    return Math.round(+val/1e5)/10;
}

function percent(val, plan) {
    if(plan==0) return 0;
    return Math.round(val/plan*1000)/10;
}

function cutTextForTooltip(text, symbolCount) {
    if (typeof symbolCount == 'undefined')
        symbolCount = 70;
    var length = text.length,
        newText = '',
        brArray = [];
    if (length > symbolCount) {
        for (var i = symbolCount; i < length; i = i + symbolCount) {
            for (var k = i; k >= 0; k--) {
                if (text[k] == " ") {
                    text = [text.slice(0, k), '\n', text.slice(k + 1)].join('');
                    i = k;
                    break;
                }
            }
        }
        text = text.replace(/\n/g, "<br />");
    }
    return text;
}
