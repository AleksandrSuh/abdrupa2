commonController = {
    /**
     * @var allowable file extensions for uploaded file
     */
    allowableFilesExtensions: ['jpg', 'jpeg', 'png', 'doc', 'pdf', 'docx', 'xls', 'xlsx'],
    /**
     * Moscow region bounds.
     */
    page: 1,
    /**
     *   Browser detection
     */
    browser: '',
    version: 0,
    /**
     * Function inits application
     */
    appInit: function() {
        var _self = this;
        $(function() {
            _self.oldBrowserCheck();
        });

        /*$(document).ready(function() {
             //Placeholder for IE
            
            if (_self.browser == 'ie') {
                if (_self.version <= 9) {
                    var styleText = '';
                    $("form").find("input[type='text'], input[type='password']").each(function(num) {
                        var $input = $(this);
                        var $parent = $input.parent();
                        if(!!!$input.attr('placeholder')){
                            return;
                        }
                        var className = 'placeholder_fix_'+num;
                        $parent.addClass(className);
                        $parent.on('click', function(){
                            $input.focus();
                        });
                        styleText += '.'+className+'{\n\
                            overflow:hidden;\n\
                            position:relative;\n\
                        }\n';
                        styleText += '.'+className+'.placeholder_fix_hide:after{\n\
                            content:\'\';\n\
                        }\n';
                        styleText += '.'+className+':after{\n\
                            content:\''+$input.attr('placeholder')+'\';\n\
                            display:block;\n\
                            z-index:10;\n\
                            position:absolute;\n\
                            left:'+($input.position().left)+'px;\n\
                            top:'+($input.position().top+($input.innerHeight()/2))+'px;\n\
                        }\n';
                        if($input.val()){
                            $parent.addClass('placeholder_fix_hide');
                        }
                        
                    }).focusin(function() {
                        var $parent = $(this).closest('[class*="placeholder_fix_"]');
                        $parent.addClass('placeholder_fix_hide');
                    }).focusout(function() {
                        var $parent = $(this).closest('[class*="placeholder_fix_"]');
                        if(!$(this).val()){
                            $parent.removeClass('placeholder_fix_hide');
                        }
                    });
                    $('<style type="text/css" class="js-placeholder_fix">' + 
                        styleText +
                    '</style>').appendTo('body');
                }
            }
            
        });*/
    },
    /**
     * Function checks if browrer version is not supported by application
     * @returns void
     */
    oldBrowserCheck: function() {
        var _self = this;
        if (_self.getCookie('noOldBrowserCheck')) {
            return;
        }

        var userAgent = navigator.userAgent.toLowerCase(),
                browser = '',
                version = 0;

        $.browser.chrome = /chrome/.test(navigator.userAgent.toLowerCase());

        if ($.browser.msie) {
            userAgent = $.browser.version;
            userAgent = userAgent.substring(0, userAgent.indexOf('.'));
            version = userAgent;
            browser = "ie";
        }

        if ($.browser.chrome) {
            userAgent = userAgent.substring(userAgent.indexOf('chrome/') + 7);
            userAgent = userAgent.substring(0, userAgent.indexOf('.'));
            version = userAgent;
            $.browser.safari = false;
            browser = "chrome";
        }

        if ($.browser.safari) {
            userAgent = userAgent.substring(userAgent.indexOf('safari/') + 7);
            version = parseFloat(userAgent);
            browser = "safari";
        }

        if ($.browser.mozilla) {
            if (navigator.userAgent.toLowerCase().indexOf('firefox') != -1) {
                userAgent = userAgent.substring(userAgent.indexOf('firefox/') + 8);
                userAgent = userAgent.substring(0, userAgent.indexOf('.'));
                version = userAgent;
                browser = "firefox"
            }
            else {
                browser = "mozilla"
            }
        }

        if ($.browser.opera) {
            browser = "opera";
            if (userAgent.indexOf('version/') == -1) {
                version = 0;
            } else {
                userAgent = userAgent.substring(userAgent.indexOf('version/') + 8);
                userAgent = userAgent.substring(0, userAgent.indexOf('.'));
                version = userAgent;
                if(!version){
                    userAgent = navigator.userAgent;
                    version = userAgent.substring(userAgent.indexOf('OPR/')+4, userAgent.indexOf('OPR/')+6);
                }
            }
        }

        _self.browser = browser;
        _self.version = version;

        if ((browser == 'ie' && version < 8)
            || (browser == 'firefox' && version < 12)
            || (browser == 'chrome' && version < 18)
            || (browser == 'opera' && version < 11)
            || (browser == 'safari' && version < 534.52))
        {
            $('#badBrowserAlert').show();
            $('#badBrowserAlert span').on('click', function(){
                var date = new Date();
                date.setTime(date.getTime()+(30*60*1000));
                document.cookie = "noOldBrowserCheck=1; path=/; expires="+date.toGMTString();
                $('#badBrowserAlert').hide();
            });
        }
    },
    /**
     * Function returns cookie value by name.
     * @param {string} name Cookie variable name
     * @returns {string} cookie value
     */
    getCookie: function(name) {
        var matches = document.cookie.match(new RegExp(
                "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
                ));
        return matches ? decodeURIComponent(matches[1]) : undefined;
    },
    /**
     * Funtion shows waiting cover
     * @param {object} $container element to append waiting cover;
     * @param {Function} callback Function;
     * @return void
     */
    showWaitingCover: function($container, callback) {
        this.hideWaitingCover($container)
        $container.prepend($('<div class="waiting js-waiting"></div>'));
        if (typeof callback == 'function')
            callback();
    },
    /**
     * Funtion hides waiting cover
     * @param {object} $container element to remove waiting cover;
     * @param {Function} callback Function;
     * @return void
     */
    hideWaitingCover: function($container, callback) {
        $container.children(".js-waiting").remove();
        if (typeof callback == 'function')
            callback();
    },
    parseUrl: function parseUrl(url) {
        var a = document.createElement('a');
        a.href = url;
        return a;
    },
    resetFileInputValue:function($input){
        $input.wrap('<form>').closest('form').get(0).reset();
        $input.unwrap();
    }
};

/*CommonRIController = new commonRIController();*/
commonController.appInit();

