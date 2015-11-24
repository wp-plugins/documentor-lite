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
//social share functions to get share count
// Facebook Shares Count
function docfacebookShares($URL) {
	if ( jQuery('#doc_fb_share').hasClass('doc-fb-share') ) {
		jQuery.getJSON('https://graph.facebook.com/?id=' + $URL, function (fbdata) {
			jQuery('#doc-fb-count').text( ReplaceNumberWithCommas(fbdata.shares || 0) );
		});
	} 
}
// Twitter Shares Count
function doctwitterShares($URL) {
	if ( jQuery('#doc_twitter_share').hasClass('doc-twitter-share') ) {
		jQuery.getJSON('https://cdn.api.twitter.com/1/urls/count.json?url=' + $URL + '&callback=?', function (twitdata) {
			jQuery('#doc-twitter-count').text( ReplaceNumberWithCommas(twitdata.count) );
		});
	} 
}
// Pinterest Shares Count
function docpinterestShares($URL) {
	if ( jQuery('#doc_pin_share').hasClass('doc-pin-share') ) {
		jQuery.getJSON('https://api.pinterest.com/v1/urls/count.json?url=' + $URL + '&callback=?', function (pindata) {
			jQuery('#doc-pin-count').text( ReplaceNumberWithCommas(pindata.count) );
		});
	} 
}
function ReplaceNumberWithCommas(shareNumber) {
	 if (shareNumber >= 1000000000) {
		return (shareNumber / 1000000000).toFixed(1).replace(/\.0$/, '') + 'G';
	 }
	 if (shareNumber >= 1000000) {
		return (shareNumber / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
	 }
	 if (shareNumber >= 1000) {
		return (shareNumber / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
	 }
	 return shareNumber;
}
;(function($){
	jQuery.fn.documentor=function(args){
		var defaults= {
			docid		: '1',
			animation	: '',
			indexformat	: '1',
			pformat         : 'decimal',
			cformat		: 'decimal',
			secstyle	: '',
			actnavbg_default: '0',
			actnavbg_color	: '#f3b869',
			scrolling	: "1",
			fixmenu		: "1",
			skin		: "default",
			scrollBarSize	: "3",
			scrollBarColor	: "#F45349",
			scrollBarOpacity: "0.4",
			menuTop: '0'
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
			countercss = "#"+documentHandle+" .doc-menu ol.doc-list-front > li:before {content: counter(item,"+options.pformat+") \".\";counter-increment: item;"+options.secstyle+";}.doc-menu ol ol li:before {content: counter(item,"+options.pformat+")\".\"counters(childitem, \".\", "+options.cformat+") \".\";counter-increment: childitem;"+options.secstyle;
			
			if( options.skin == 'broad') {
				countercss = "#"+documentHandle+" .doc-menu ol.doc-list-front > li:before {content: counter(item,"+options.pformat+") \".\";counter-increment: item;"+options.secstyle;
			}
		
			jQuery("head").append("<style type=\"text/css\"> #"+documentHandle+" .doc-menu ol.doc-list-front {counter-reset: item ;}.doc-menu ol ol {counter-reset: childitem;}#"+documentHandle+" ol.doc-menu {margin-top: 20px;}#"+documentHandle+" .doc-menu ol li {display: block;}"+countercss+"}</style>");
		} else {
			jQuery("head").append("<style type=\"text/css\">#"+documentHandle+" ol {list-style: none;}#"+documentHandle+" li {list-style: none;}</style>");
		}
		if(options.actnavbg_default != '1' && options.actnavbg_color.length > 0 && options.skin != 'broad') {
			jQuery("head").append("<style type=\"text/css\">#"+documentHandle+" .doc-menu ol > li.doc-acta{background-color: "+options.actnavbg_color+"}</style>");
		}
		if(options.actnavbg_default != '1' && options.actnavbg_color.length > 0 && options.skin == 'broad') {
			jQuery("head").append("<style type=\"text/css\">#"+documentHandle+" .doc-menu li.activeli > a, #"+documentHandle+" .doc-menu .documentor-relatedtitle{background-color: "+options.actnavbg_color+"; color: #fff !important;}</style>");
		}
		if( options.fixmenu == 1 ) {
			var docEnd = jQuery("#"+documentHandle+"-end").position(); //cache the position
			jQuery.lockfixed("#"+documentHandle+" .doc-menu",{offset: {top: options.menuTop, bottom: (document.body.clientHeight - docEnd.top)}});
		}
		if( options.skin == 'broad' ) {
			jQuery("#"+documentHandle+" ol.doc-list-front li:first").addClass('activeli');
		}
		//js
		jQuery(this).find(".doc-menu a.documentor-menu:first, .doc-menu li.doc-actli:first").addClass('doc-acta');
		/* Search in document */
		jQuery("#"+documentHandle+" .search-document").autocomplete({
			source: function(req, response){
				req['docid'] = options.documentid;
				jQuery.getJSON(DocAjax.docajaxurl+'?callback=?&action=doc_search_results', req, response);
			},
			select: function(event, ui) {
				var thref = ui.item.slug;
				jQuery("#"+documentHandle+" a[href=#"+thref+"]")[0].click();
			},
			delay: 200,
			minLength: 3
		}).autocomplete( "widget" ).addClass( "doc-sautocomplete" );
			
		/**
		 * This part causes smooth scrolling using scrollto function
		*/
		jQuery(this).find(".doc-menu a.documentor-menu").click(function(evn){
			if( options.scrolling == 1 ) {
				evn.preventDefault();
			}
			jQuery(this).parents('.doc-menu:first').find('a.documentor-menu, li.doc-actli').removeClass('doc-acta');
			jQuery(this).addClass('doc-acta');
			jQuery(this).parents('li.doc-actli:first').addClass('doc-acta');
			//for broad skin
			if( options.skin == 'broad' ) {
				if( options.togglechild == 1 ) {
					jQuery('.doc-menu li ol:not(:has(.doc-acta))').hide();
					jQuery(this).parents('.doc-actli:last').find('ol').show();
				}
				jQuery("#"+documentHandle+" .doc-menu li").removeClass('activeli');
				jQuery( "#"+documentHandle+" a.doc-acta" ).parents("li:last").addClass('activeli');
				var mwrapcnt = jQuery( this ).data('mwrapcnt');
				if( typeof mwrapcnt === 'undefined' ) {
					mwrapcnt = jQuery(this).parents("li.doc-actli:last").find("a").data('mwrapcnt');
				}
				if( typeof mwrapcnt !== 'undefined' ) {
					jQuery("#"+documentHandle+" .doc-sectionwrap").hide();
					jQuery("#"+documentHandle+" .doc-sectionwrap[data-wrapcnt="+mwrapcnt+"]").fadeIn( 400 );
				}
			}
			/* Do not apply animation effect if click on menu item */
			jQuery("#"+documentHandle).find(".documentor-section").css({"visibility":"visible","-webkit-animation":"none"});
			if( jQuery(this.hash).length > 0 && options.scrolling == 1 ) {
			 	jQuery('html,body').docuScrollTo( this.hash, this.hash ); 
			}
			var visiblemheight = jQuery("#"+documentHandle+" .doc-menu ol.doc-list-front").height();
			if( jQuery("#"+documentHandle+" .documentor-related").length > 0 ) {
				visiblemheight = visiblemheight + jQuery("#"+documentHandle+" .documentor-related").height()+40;
			}
			jQuery("#"+documentHandle+" .doc-sec-container").css('min-height',visiblemheight+'px');
		
		});
		/* For broad skin - if link with hash value of section is opened in window */
		if( location.hash != "" && options.skin == 'broad' ) {
			var hashval = location.hash;
			jQuery("a.documentor-menu[data-href='"+hashval+"']").trigger("click");
		}      
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
						var divPos = jQuery(theID).offset().top - (windowHeight*0.15); // get the offset of the div from the top of page
						var divHeight = jQuery(theID).outerHeight(true); // get the height of the div in question
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
		//right positioned menu
		jQuery(window).scroll(function(){
			if( jQuery("#"+documentHandle+" .doc-menuright.doc-menufixed").length > 0 ) {
				var mleft = jQuery("#"+documentHandle).outerWidth()-jQuery("#"+documentHandle+" .doc-menuright.doc-menufixed").outerWidth();
				jQuery("#"+documentHandle+" .doc-menuright.doc-menufixed").css('margin-left',mleft+'px');
			} else {
				jQuery("#"+documentHandle+" .doc-menuright").css('margin-left','0px');
			}
		});
		/*show section link*/
		jQuery("#"+documentHandle).find(".doc-section").hover(function(){
			jQuery(this).find(".doc-sec-link").stop(true,true).animate({'opacity':'0.8'},1000);
		},function() {
			jQuery(this).find(".doc-sec-link").stop(true,true).animate({'opacity':'0'},1000);
		}); 
		/*show scrolltop button*/
		jQuery("body").hover(function(){
			jQuery("#"+documentHandle+" .scrollup").stop(true,true).animate({'opacity':'0.8'},1000);
		},function() {
			jQuery("#"+documentHandle+" .scrollup").stop(true,true).animate({'opacity':'0'},1000);
		});	 
		/*scrolltop*/
		jQuery("#"+documentHandle).find(".scrollup").on('click', function() {
			jQuery("html, body").animate({scrollTop:jQuery("#"+documentHandle).offset().top-50}, 600);
		});
		//scroll bar js
		 jQuery("#"+documentHandle+" .doc-menurelated").slimScroll({
			  size: options.scrollBarSize+'px', 
			  height: '100%', 
			  color: options.scrollBarColor, 
			  opacity: options.scrollBarOpacity,
		});
		/* Expand / collapse menus */
		jQuery("#"+documentHandle+" .doc-menu.toggle .doc-mtoggle").on('click', function() {
			jQuery(this).toggleClass('expand');
			jQuery(this).parent('.doc-actli').find('ol:first').slideToggle('slow');
		});	
		/* Call social sharing count functtions on load */
		if( options.socialshare == 1 && options.sharecount == 1 ) {
			var sharelink = jQuery("#"+documentHandle+" .doc-sharelink").data('sharelink');
			if( sharelink != '' ) {
				if( options.fbshare == 1 ) {
					docfacebookShares( sharelink );
				}
				if( options.twittershare == 1 ) {
					doctwitterShares( sharelink );
				}
				if( options.gplusshare == 1 ) {
					if ( jQuery('#doc_gplus_share').hasClass('doc-gplus-share') ) {
						// Google Plus Shares Count
						var googleplusShares = jQuery('#doc-gplus-count').data('gpluscnt');
						jQuery('#doc-gplus-count').text( ReplaceNumberWithCommas(googleplusShares) )
					}
				}
				if( options.pinshare == 1 ) {
					docpinterestShares( sharelink );
				}
			}
		}
	}
})(jQuery);

/*! Copyright (c) 2011 Piotr Rochala (http://rocha.la)
 * Dual licensed under the MIT (http://www.opensource.org/licenses/mit-license.php)
 * and GPL (http://www.opensource.org/licenses/gpl-license.php) licenses.
 *
 * Version: 1.3.0
 *
 */
(function(f) {
    jQuery.fn.extend({
        slimScroll: function(h) {
            var a = f.extend({
                width: "auto",
                height: "250px",
                size: "7px",
                color: "#000",
                position: "right",
                distance: "0px",
                start: "top",
                opacity: 0.4,
                alwaysVisible: !1,
                disableFadeOut: !1,
                railVisible: !1,
                railColor: "#333",
                railOpacity: 0.2,
                railDraggable: !0,
                railClass: "slimScrollRail",
                barClass: "slimScrollBar",
                wrapperClass: "slimScrollDiv",
                allowPageScroll: !1,
                wheelStep: 20,
                touchScrollStep: 200,
                borderRadius: "7px",
                railBorderRadius: "7px"
            }, h);
            this.each(function() {
                function r(d) {
                    if (s) {
                        d = d ||
                            window.event;
                        var c = 0;
                        d.wheelDelta && (c = -d.wheelDelta / 120);
                        d.detail && (c = d.detail / 3);
                        f(d.target || d.srcTarget || d.srcElement).closest("." + a.wrapperClass).is(b.parent()) && m(c, !0);
                        d.preventDefault && !k && d.preventDefault();
                        k || (d.returnValue = !1)
                    }
                }

                function m(d, f, h) {
                    k = !1;
                    var e = d,
                        g = b.outerHeight() - c.outerHeight();
                    f && (e = parseInt(c.css("top")) + d * parseInt(a.wheelStep) / 100 * c.outerHeight(), e = Math.min(Math.max(e, 0), g), e = 0 < d ? Math.ceil(e) : Math.floor(e), c.css({
                        top: e + "px"
                    }));
                    l = parseInt(c.css("top")) / (b.outerHeight() - c.outerHeight());
                    e = l * (b[0].scrollHeight - b.outerHeight());
                    h && (e = d, d = e / b[0].scrollHeight * b.outerHeight(), d = Math.min(Math.max(d, 0), g), c.css({
                        top: d + "px"
                    }));
                    b.scrollTop(e);
                    b.trigger("slimscrolling", ~~e);
                    v();
                    p()
                }

                function C() {
                    window.addEventListener ? (this.addEventListener("DOMMouseScroll", r, !1), this.addEventListener("mousewheel", r, !1), this.addEventListener("MozMousePixelScroll", r, !1)) : document.attachEvent("onmousewheel", r)
                }

                function w() {
                    u = Math.max(b.outerHeight() / b[0].scrollHeight * b.outerHeight(), D);
                    c.css({
                        height: "20%"
                    });
                    var a = u == b.outerHeight() ? "none" : "block";
                    c.css({
                        display: a
                    })
                }

                function v() {
                    w();
                    clearTimeout(A);
                    l == ~~l ? (k = a.allowPageScroll, B != l && b.trigger("slimscroll", 0 == ~~l ? "top" : "bottom")) : k = !1;
                    B = l;
                    u >= b.outerHeight() ? k = !0 : (c.stop(!0, !0).fadeIn("fast"), a.railVisible && g.stop(!0, !0).fadeIn("fast"))
                }

                function p() {
                    a.alwaysVisible || (A = setTimeout(function() {
                        a.disableFadeOut && s || (x || y) || (c.fadeOut("slow"), g.fadeOut("slow"))
                    }, 1E3))
                }
                var s, x, y, A, z, u, l, B, D = 30,
                    k = !1,
                    b = f(this);
                if (b.parent().hasClass(a.wrapperClass)) {
                    var n = b.scrollTop(),
                        c = b.parent().find("." + a.barClass),
                        g = b.parent().find("." + a.railClass);
                    w();
                    if (f.isPlainObject(h)) {
                        if ("height" in h && "auto" == h.height) {
                            b.parent().css("height", "auto");
                            b.css("height", "auto");
                            var q = b.parent().parent().height();
                            b.parent().css("height", q);
                            b.css("height", q)
                        }
                        if ("scrollTo" in h) n = parseInt(a.scrollTo);
                        else if ("scrollBy" in h) n += parseInt(a.scrollBy);
                        else if ("destroy" in h) {
                            c.remove();
                            g.remove();
                            b.unwrap();
                            return
                        }
                        m(n, !1, !0)
                    }
                } else {
                    a.height = "auto" == a.height ? b.parent().height() : a.height;
                    n = f("<div></div>").addClass(a.wrapperClass).css({
                        position: "relative",
                        overflow: "hidden",
                        width: a.width,
                        height: a.height
                    });
                    b.css({
                        overflow: "hidden",
                        width: a.width,
                        height: a.height
                    });
                    var g = f("<div></div>").addClass(a.railClass).css({
                            width: a.size,
                            height: "100%",
                            position: "absolute",
                            top: 0,
                            display: a.alwaysVisible && a.railVisible ? "block" : "none",
                            "border-radius": a.railBorderRadius,
                            background: a.railColor,
                            opacity: a.railOpacity,
                            zIndex: 90
                        }),
                        c = f("<div></div>").addClass(a.barClass).css({
                            background: a.color,
                            width: a.size,
                            position: "absolute",
                            top: 0,
                            opacity: a.opacity,
                            display: a.alwaysVisible ?
                                "block" : "none",
                            "border-radius": a.borderRadius,
                            BorderRadius: a.borderRadius,
                            MozBorderRadius: a.borderRadius,
                            WebkitBorderRadius: a.borderRadius,
                            zIndex: 99
                        }),
                        q = "right" == a.position ? {
                            right: a.distance
                        } : {
                            left: a.distance
                        };
                    g.css(q);
                    c.css(q);
                    b.wrap(n);
                    b.parent().append(c);
                    b.parent().append(g);
                    a.railDraggable && c.bind("mousedown", function(a) {
                        var b = f(document);
                        y = !0;
                        t = parseFloat(c.css("top"));
                        pageY = a.pageY;
                        b.bind("mousemove.slimscroll", function(a) {
                            currTop = t + a.pageY - pageY;
                            c.css("top", currTop);
                            m(0, c.position().top, !1)
                        });
                        b.bind("mouseup.slimscroll", function(a) {
                            y = !1;
                            p();
                            b.unbind(".slimscroll")
                        });
                        return !1
                    }).bind("selectstart.slimscroll", function(a) {
                        a.stopPropagation();
                        a.preventDefault();
                        return !1
                    });
                    g.hover(function() {
                        v()
                    }, function() {
                        p()
                    });
                    c.hover(function() {
                        x = !0
                    }, function() {
                        x = !1
                    });
                    b.hover(function() {
                        s = !0;
                        v();
                        p()
                    }, function() {
                        s = !1;
                        p()
                    });
                    b.bind("touchstart", function(a, b) {
                        a.originalEvent.touches.length && (z = a.originalEvent.touches[0].pageY)
                    });
                    b.bind("touchmove", function(b) {
                        k || b.originalEvent.preventDefault();
                        b.originalEvent.touches.length &&
                            (m((z - b.originalEvent.touches[0].pageY) / a.touchScrollStep, !0), z = b.originalEvent.touches[0].pageY)
                    });
                    w();
                    "bottom" === a.start ? (c.css({
                        top: b.outerHeight() - c.outerHeight()
                    }), m(0, !0)) : "top" !== a.start && (m(f(a.start).position().top, null, !0), a.alwaysVisible || c.hide());
                    C()
                }
            });
            return this
        }
    });
    jQuery.fn.extend({
        slimscroll: jQuery.fn.slimScroll
    })
})(jQuery);

