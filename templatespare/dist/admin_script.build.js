!function(e){var t={};function a(s){if(t[s])return t[s].exports;var p=t[s]={i:s,l:!1,exports:{}};return e[s].call(p.exports,p,p.exports,a),p.l=!0,p.exports}a.m=e,a.c=t,a.d=function(e,t,s){a.o(e,t)||Object.defineProperty(e,t,{enumerable:!0,get:s})},a.r=function(e){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(e,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(e,"__esModule",{value:!0})},a.t=function(e,t){if(1&t&&(e=a(e)),8&t)return e;if(4&t&&"object"==typeof e&&e&&e.__esModule)return e;var s=Object.create(null);if(a.r(s),Object.defineProperty(s,"default",{enumerable:!0,value:e}),2&t&&"string"!=typeof e)for(var p in e)a.d(s,p,function(t){return e[t]}.bind(null,p));return s},a.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return a.d(t,"a",t),t},a.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},a.p="",a(a.s=28)}({28:function(e,t){jQuery(document).ready((function(e){"use strict";var t={requiredTheme:"false",requiredPlugins:"false",init:function(){e(document).on("click",".templatespare-purchase-btn",(function(t){var a=e(this).attr("data-theme-slug"),s=(e(this).attr("data-image"),e(this).attr("data-name"),"https://afthemes.com/products/"+a);window.open(s,"_blank").focus()})),e("body").on("click",".template-spare-modal",(function(a){var s=e(this),p=e(this).attr("data-theme");t.verifyTheme(p,s)})),e("body").on("click",".template-spare-close",(function(){e(".ReactModalPortal").find(".templatespare-popup-inner").find("a").removeAttr("data-theme-status")})),e("body").on("click",".templatespare-open-iframe",(function(){var t=e(this),a=t.attr("data-pro"),s=t.attr("data-src"),p=t.parents(".templatespare-main-demo"),i=t.attr("data-theme-slug"),r=t.attr("data-image"),n=t.attr("data-name"),o="Details";"pro"===a&&(o="Purchase");var l="https://afthemes.com/products/"+i;p.append("<div class='templatespare-demo-iframe'><iframe src="+s+" ></iframe ><div class='templatespare-iframe-footer-wrapper'> <a href='' class='templatespare-close-iframe'><i class='dashicons dashicons-no-alt'></i></a><div class='theme-details'><a class='templatespare-logo-link' href='https://afthemes.com/all-themes-plan/' target='_blank'><img src='"+afobDash.aflogo+"'/></a><a class='templatespare-theme-title' href="+l+" target='_blank'>"+n+"</a></div> <div class='responsive-view'><span class='active desktop'><i class='dashicons dashicons-desktop'></i></span><span class='tablet'><i class='dashicons dashicons-tablet'></i></span><span class='mobile'><i class='dashicons dashicons-smartphone'></i></span></div><div class='templatespare-plans'><button class='templatespare-single-plan single-plan' plan-type='single' data-slug="+i+" data-image="+r+" data-name="+n+" >"+o+"  </button> <button class='templatespare-single-plan all-plan' plan-type='all' data-slug="+i+" data-image="+r+" data-name="+n+" > All Themes Plan</button ></div></div ></div > "),p.find(".templatespare-demo-iframe").addClass("desktop")})),e("body").on("click",".responsive-view span",(function(){e(this).parent(".responsive-view").find("span").removeClass("active");var t=e(this).attr("class");e(this).addClass("active"),e(this).parents(".templatespare-demo-iframe").removeClass("desktop tablet mobile").addClass(t)})),e("body").on("click",".templatespare-close-iframe",(function(t){t.preventDefault(),e(this).parents(".templatespare-main-demo").find(".templatespare-demo-iframe").remove()})),e("body").on("click",".templatespare-kit-single",(function(a){a.preventDefault();var s=e(this).parents(".ReactModal__Content").find(".templatespare-popup-inner").find(".templatespare-import-kit-popup-wrap");s.fadeIn(),e(this).fadeOut(),s.find(".progress-bar").fadeIn(),e(this).parents(".ReactModal__Content").find(".templatespare-popup-inner").find(".templatespare-popup-footer").fadeOut(),e(this).parents(".ReactModal__Content").find(".templatespare-popup-inner").find(".templatespare-popup-header").find(".template-spare-close").fadeOut(),e(this).parents(".ReactModal__Content").find(".templatespare-popup-inner").find(".templatespare-import-kit-popup").find("strong").addClass("templatespare-process-msg"),t.importTemplatesKit(e(this).attr("data-kit-id"))})),e("body").on("click",".templatespare-single-plan",(function(t){var a=e(this).attr("data-slug"),s=e(this).attr("plan-type"),p=(e(this).attr("data-image"),e(this).attr("data-name"),"");p="all"===s?"https://afthemes.com/all-themes-plan/":"https://afthemes.com/products/"+a,window.open(p,"_blank").focus()})),e(".templatespare-dismiss-notice").on("click",(function(){e.ajax({type:"POST",url:ajaxurl,data:{action:"templatespare_notice_dismiss",security:afobDash.ajax_nonce},success:function(t){"success"==t.status&&e(".templatespare-notice-content-wrapper").remove()}})}))},purchase:function(t,a,s,p){e.ajax({type:"POST",url:ajaxurl,data:{action:"templatespare_get_plan_details",slug:t,plaType:a},success:function(e){if(!0===e.success){FS.Checkout.configure({plugin_id:e.data.productid,plan_id:e.data.planid,public_key:e.data.publickey,image:s}).open({name:p,licenses:1,purchaseCompleted:function(e){},success:function(e){}})}}})},verifyTheme:function(t,a){e.ajax({type:"POST",url:ajaxurl,data:{action:"templatespare_get_theme_status",security:afobDash.ajax_nonce,re_theme:t},success:function(t){e(".ReactModalPortal").find(".templatespare-popup-inner").find("a").attr("data-theme-status",t.data.status)},error:function(e,t,a){}})},importTemplatesKit:function(a){t.importProgressBar("Loading"),t.installRequiredTheme(a,(function(s){"success"===s&&setTimeout(()=>{t.installRequiredPlugins(a,(function(s){"success"===s&&setTimeout(()=>{t.importProgressBar("importing-2"),e(".ReactModal__Content").find(".templatespare-popup-inner").removeClass("templatespare-import-success"),function a(s){var p=e('.templatespare-kit-single[data-kit-id="'+s+'"]'),i=p.data("theme-folder"),r=p.data("verify-child");e.ajax({type:"POST",url:ajaxurl,data:{action:"AFTMLS_import_demo_data",templatespare_templates_kit:s,security:afobDash.ajax_nonce,selectedTheme:i,isChild:r},success:function(e){void 0!==e.status&&"newAJAX"===e.status?(t.importProgressBar("importing-"+e.ajaxCall),a(s)):void 0!==e.message&&(t.importProgressBar("finish"),p.parents(".ReactModal__Content").find(".templatespare-popup-inner").find(".templatespare-popup-footer").fadeIn(),p.parents(".ReactModal__Content").find(".templatespare-popup-inner").find(".templatespare-import-kit-popup").find("strong").removeClass("templatespare-process-msg"))},error:function(e,t,a){}})}(a)},2e3)}))})}))},pageSettings:function(t,a){t=e('.templatespare-kit-single[data-kit-id="'+a+'"]').data("theme");e.ajax({type:"POST",url:ajaxurl,data:{action:"templatespare_elementor_final_setup",kitID:a,selectedTheme:t},success:function(e){e.success}})},installRequiredTheme:function(a,s){t.importProgressBar("theme");var p=e('.templatespare-kit-single[data-kit-id="'+a+'"]'),i=p.data("theme-status"),r=p.data("theme");"req-theme-active"===i?(t.requiredTheme="true",s("success")):"req-theme-inactive"===i?e.post(ajaxurl,{action:"templatespare_activate_required_theme",theme:r,security:afobDash.ajax_nonce},(function(){t.requiredTheme="true",s("success")})):wp.updates.installTheme({slug:r,success:function(){e.post(ajaxurl,{action:"templatespare_activate_required_theme",theme:r,security:afobDash.ajax_nonce},(function(){t.requiredTheme="true",s("success")}))},error:function(){console.error("Theme installation failed"),s("error")}})},installRequiredPlugins:function(a,s){t.importProgressBar("plugins");var p=e('.templatespare-kit-single[data-kit-id="'+a+'"]');if("no"!==p.data("builder")){var i=p.data("builder"),r=[];r.push(...i.split(",")),t.installRequiredPluginsViaAjax(r,(function(e){s(e)}))}else s("success")},installRequiredPluginsViaAjax:function(t,a){t&&e.ajax({type:"POST",url:ajaxurl,data:{action:"templatespare_install_require_plugins",plugins:t,security:afobDash.ajax_nonce},success:function(e){a(e)}})},importProgressBar:function(t){if("theme"===t)e(".templatespare-import-kit-popup .progress-wrap strong").html(' <span class="dot-flashing"></span> <span>Installing/Activating Theme </span>');else if("plugins"===t)e(".templatespare-import-kit-popup .progress-bar").animate({width:"50%"},500),e(".templatespare-import-kit-popup .progress-wrap strong").html(' <span class="dot-flashing"></span> <span>Installing/Activating  Plugins</span>');else if("importing-2"===t)e(".templatespare-import-kit-popup .progress-bar").animate({width:"75%"},500),e(".templatespare-import-kit-popup .progress-wrap strong").html(' <span class="dot-flashing"></span> <span>Importing Demo Content</span>');else if("importing-3"===t)e(".templatespare-import-kit-popup .progress-bar").animate({width:"85%"},500),e(".templatespare-import-kit-popup .progress-wrap strong").html('<span class="dot-flashing"></span> <span>Importing Widgets</span>');else if("importing-4"===t)e(".templatespare-import-kit-popup .progress-bar").animate({width:"90%"},500),e(".templatespare-import-kit-popup .progress-wrap strong").html(' <span class="dot-flashing"></span> <span>Importing Frontpage Settings</span>');else if("importing-5"===t)e(".templatespare-import-kit-popup .progress-bar").animate({width:"99%"},500),e(".templatespare-import-kit-popup .progress-wrap strong").html('<span class="dot-flashing"></span> <span>Importing Customizer Settings</span>');else if("finish"===t){var a=window.location.href,s=a.indexOf("/wp-admin"),p=a.substring(0,s);e(".templatespare-import-kit-popup .progress-bar").animate({width:"100%"},500),e(".templatespare-import-kit-popup .content").children("p").remove(),e(".templatespare-import-kit-popup .progress-wrap strong").html("That's it, all done! <a href=\""+p+'" target="_blank">Visit Site</a>'),e(".templatespare-import-kit-popup header h3").text("Import was Successfull!"),e(".templatespare-popup-inner .templatespare-popup-header .template-spare-close").show(),e(".templatespare-popup-inner").addClass("templatespare-import-success")}}};t.init()}))}});