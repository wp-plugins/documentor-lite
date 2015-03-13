//scrollTo function
jQuery.docuScrollTo = jQuery.fn.docuScrollTo = function(x, y, options){
    if (!(this instanceof jQuery)) return jQuery.fn.docuScrollTo.apply(jQuery('html,body'), arguments);

    options = jQuery.extend({}, {
        gap: {
            x: 0,
            y: 0
        },
        animation: {
            easing: 'swing',
            duration: 1000,
            complete: jQuery.noop,
            step: jQuery.noop
        }
    }, options);

    return this.each(function(){
        var elem = jQuery(this);
	elem.stop().animate({
            scrollLeft: !isNaN(Number(x)) ? x : jQuery(y).offset().left + options.gap.x,
            scrollTop: !isNaN(Number(y)) ? y : jQuery(y).offset().top + options.gap.y
	}, options.animation);
    });
};
;(function($){
	jQuery.fn.documentor=function(args){
		var defaults= {
			docid		: '1',
			animation	: '',
			indexformat	: '1',
			secstyle	: '',
			actnavbg_default: '0',
			actnavbg_color	: '#f3b869',
		}
		
		var options=jQuery.extend({},defaults,args);
		var documentHandle = options.docid;
		if(options.animation.length > 0 ) {
			wow = new WOW({
				boxClass:     "wow",      
				animateClass: "documentor-animated", 
				offset:       0,          
				mobile:       true,       
				live:         true        
			});
			wow.init();
		}
		if(options.indexformat == '1') {
			jQuery("head").append("<style type=\"text/css\"> #"+documentHandle+" ol {counter-reset: item ;}#"+documentHandle+" ol.doc-menu {margin-top: 20px;}#"+documentHandle+" ol li {display: block;}#"+documentHandle+" ol li:before {content: counters(item, \".\") \".\";counter-increment: item;"+options.secstyle+"}</style>");
		} else {
			jQuery("head").append("<style type=\"text/css\">#"+documentHandle+" ol {list-style: none;}#"+documentHandle+" li {list-style: none;}</style>");
		}
		if(options.actnavbg_default != '1' && options.actnavbg_color.length > 0 ) {
			jQuery("head").append("<style type=\"text/css\">#"+documentHandle+" .doc-menu ol > li.doc-acta{background-color: "+options.actnavbg_color+"}</style>");
		}
		var docEnd = jQuery("#"+documentHandle+"-end").position(); //cache the position
		jQuery.lockfixed("#"+documentHandle+" .doc-menu",{offset: {top: 0, bottom: (document.body.clientHeight - docEnd.top)}});
		
		//js
		jQuery(this).find(".doc-menu a.documentor-menu:first, .doc-menu li.doc-actli:first").addClass('doc-acta');
			
		/**
		 * This part causes smooth scrolling using scrollto function
		*/
		jQuery(this).find(".doc-menu a.documentor-menu").click(function(evn){
			evn.preventDefault();
			jQuery(this).parents('.doc-menu:first').find('a.documentor-menu, li.doc-actli').removeClass('doc-acta');
			jQuery(this).addClass('doc-acta');
			jQuery(this).parents('li.doc-actli:first').addClass('doc-acta');
			/* Do not apply animation effect if click on menu item */
			jQuery("#"+documentHandle).find(".documentor-section").css({"visibility":"visible","-webkit-animation":"none"});
			/**/
			if( options.enable_ajax == '1' ) {
				var secid = jQuery( this ).attr("data-section-id");
				var docid = jQuery( this ).parents(".documentor-wrap:first").find(".hidden_docid").val();
				var data = {
					'action': 'doc_get_ajaxcontent',
					'secid': secid,
					'docid': docid
				};
				//display loader
				jQuery("#"+documentHandle).find(".doc-sec-container").empty();
				jQuery("#"+documentHandle).find(".doc-sec-container").append('<div class="doc-loader"></div>');
				jQuery.post(DocAjax.docajaxurl, data, function(response) {
					if( response != "0" ) {
						jQuery("#"+documentHandle).find('.doc-sec-container').html(response);
					}
				}).always( function() { 
					var cnxt=jQuery("#"+documentHandle).find('#section-'+data['secid']);
				   	bindsectionBehaviour(cnxt);
				 });
			 }
			 if( jQuery(this.hash).length > 0 ) {
			 	jQuery('html,body').docuScrollTo( this.hash, this.hash ); 
			}
		
		});
		           
		/**
		 * This part handles the highlighting functionality.
		 */
		var aChildren = jQuery(this).find(".doc-menu li.doc-actli").children('a.documentor-menu'); // find the a children of the list items
		var aArray = []; // create the empty aArray
		for (var i=0; i < aChildren.length; i++) {    
			var aChild = aChildren[i];
			var ahref = jQuery(aChild).attr('href');
			aArray.push(ahref);
		} // this for loop fills the aArray with attribute href values
		jQuery(window).scroll(function(){
			var window_top = jQuery(window).scrollTop() + 12; // the "12" should equal the margin-top value for nav.stick
			var windowPos = jQuery(window).scrollTop(); // get the offset of the window from the top of page
			var windowHeight = jQuery(window).height(); // get the height of the window
			var docHeight = jQuery(document).height();
	
			if(windowPos + windowHeight == docHeight) {
				if (!jQuery("#"+documentHandle+" .doc-menu li:last-child a").hasClass("doc-acta")) {
				    var navActiveCurrent = jQuery("#"+documentHandle+" .doc-acta").attr("href");
				    jQuery("#"+documentHandle+" a[href='" + navActiveCurrent + "']").removeClass("doc-acta");
				    jQuery("#"+documentHandle+" .doc-menu li:last-child a").addClass("doc-acta");
				}
			}
			
			clearTimeout(jQuery.data(this, 'scrollTimer'));
			jQuery.data(this, 'scrollTimer', setTimeout(function() {
				// do something
				for (var i=0; i < aArray.length; i++) {
					if( jQuery(aArray[i]).length > 0 ) {
						var theID = aArray[i];
						var divPos = jQuery(theID).offset().top; // get the offset of the div from the top of page
						var divHeight = jQuery(theID).height(); // get the height of the div in question
						if (windowPos >= divPos && windowPos < (divPos + divHeight)) {
							jQuery("#"+documentHandle+" a[href='" + theID + "']").addClass("doc-acta");
						} else {
							jQuery("#"+documentHandle+" a[href='" + theID + "']").removeClass("doc-acta");
						}
					}
				}
				if(jQuery("#"+documentHandle+" a.doc-acta").length<=0) {
					jQuery("#"+documentHandle+" .doc-menu a.documentor-menu:first").addClass("doc-acta");
				}
				jQuery("#"+documentHandle+" .doc-menu a.documentor-menu.doc-acta").parent('li').addClass("doc-acta");
				jQuery("#"+documentHandle+" .doc-menu a:not(.doc-acta)").parent('li').removeClass("doc-acta");
			}, 200));
		});
		/*show scrolltop button*/
		jQuery("#"+documentHandle).find(".doc-section").hover(function(){
			jQuery(this).find(".scrollup").stop(true,true).animate({'opacity':'0.8'},1000);
			jQuery(this).find(".doc-sec-link").stop(true,true).animate({'opacity':'0.8'},1000);
		},function() {
			jQuery(this).find(".scrollup").stop(true,true).animate({'opacity':'0'},1000);
			jQuery(this).find(".doc-sec-link").stop(true,true).animate({'opacity':'0'},1000);
		});  
		/*scrolltop*/
		jQuery("#"+documentHandle).find(".scrollup").on('click', function() {
			jQuery("html, body").animate({scrollTop:jQuery("#"+documentHandle).offset().top-50}, 600);
		});
	}
})(jQuery);
