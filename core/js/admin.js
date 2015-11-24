var searchTimer;
function confirmDocDelete()
        {
            var agree=confirm("Delete this Document??");
            if (agree)
            return true ;
            else
            return false ;
        }
//function to get current url parameter
function getUrlParameter(sParam)
{
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++) 
    {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam) 
        {
            return sParameterName[1];
        }
    }
} 

jQuery(document).ready(function() {

		 jQuery('#later').on("click", function(){
			//alert("later");
			var r = jQuery('#hidden_reviewme').val();
			//alert(r);
			var data = {};
			data['reviewme'] = parseInt(r);
			data['action'] = 'update_review_me';
			jQuery.post(ajaxurl, data, function(response) {
			if(response) {
				alert(jQuery('#hidden_reviewme').val(response));
				jQuery('#hidden_reviewme').val(response);
			}
			jQuery('#reviewme').remove();
			});
		});
		jQuery('#already').on("click", function(){
			//alert("already");
			var data = {};
			data['reviewme'] = 0;
			data['action'] = 'update_review_me';
			jQuery.post(ajaxurl, data, function(response) {
				if(response) {
					jQuery('#hidden_reviewme').val(response);
				}
				jQuery('#reviewme').remove();
			});
		});
	//global variable docid
	var docid = jQuery("input[name='docsid']").val();

	//apply leanmodal popup
	jQuery("a[rel*=leanModal]").leanModal();

	// wp color picker
	jQuery('.wp-color-picker-field').wpColorPicker();

 	//create new form validation
	jQuery(".documentor-newform").submit( function() {
		if( jQuery(".doc-form-input").val() == '' ) {
			jQuery(".error_msg").html("Please enter guide name");
			return false;
		}
	});

	// added for checkbox
	jQuery('body').on('click', '.eb-toggle-round', function() {
	        if(jQuery(this).prop("checked")==true) {
	               jQuery(this).prev('.hidden_check').val(1);
		} else {
	               jQuery(this).prev('.hidden_check').val(0);
		}
		if( jQuery(this).attr('id') == 'enable-fixmenu' ) {
			if( jQuery("#doc-enable-fixmenu").val() == '1' ) {
				jQuery(".menuTop").show();
			}
			else{
				jQuery(".menuTop").hide();
			}
		}
	}); 
	jQuery(".documentor_editguide .docname").on('input keyup', function() {
		jQuery("input.guidename").val(jQuery(this).val());
	});
	// Added for Google fonts
	/* This function loads second level of fonts on load depending on first level of fonts - start */
	jQuery( ".main-font" ).each(function() {
		var font_type = jQuery(this).val();
		var nm;
		if(font_type == 'regular') nm = jQuery(this).siblings(".ftype_rname").val();
		if(font_type == 'google') nm = jQuery(this).siblings(".ftype_gname").val();
		if(font_type == 'custom') nm = jQuery(this).siblings(".ftype_cname").val();
		var parentid = jQuery(this).attr('id');
		var settings_nonce = jQuery("input[name='documentor-settings-nonce']").val();
		var data = {
			'action': 'documentor_load_fontsdiv',
			'font_type': font_type,
			'parentid': parentid,
			'docid' : docid,
			'nm':nm,
			'settings_nonce': settings_nonce
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#"+data['parentid']).parents(".settings-tbl").find(".load-fontdiv").html(response);
			if( data['font_type'] == 'google' ) {
				jQuery("#"+data['parentid']).parents(".settings-tbl").find(".font-style").css('display','none');
			}
			else {
				jQuery("#"+data['parentid']).parents(".settings-tbl").find(".font-style").css('display','table-row');
			}
		}).always( function() { 
			var cnxt=jQuery("#"+data['parentid']).parents(".settings-tbl").find(".load-fontdiv");
		   	bindgoogleBehaviour(cnxt);
		});
	});
	/* This function loads second level of fonts on load depending on first level of fonts - end */

	/* This function loads second level of fonts on change of first level of fonts - start */
	jQuery(".main-font").change(function(){
		var font_type = jQuery(this).val();
		var nm;
		if(font_type == 'regular') nm = jQuery(this).siblings(".ftype_rname").val();
		if(font_type == 'google') nm = jQuery(this).siblings(".ftype_gname").val();
		if(font_type == 'custom') nm = jQuery(this).siblings(".ftype_cname").val();
		var parentid = jQuery(this).attr('id');
		var settings_nonce = jQuery("input[name='documentor-settings-nonce']").val();
		var data = {
			'action': 'documentor_load_fontsdiv',
			'font_type': font_type,
			'parentid': parentid,
			'docid' : docid,
			'nm':nm,
			'settings_nonce': settings_nonce
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#"+data['parentid']).parents(".settings-tbl").find(".load-fontdiv").html(response);
			if( data['font_type'] == 'google' ) {
				jQuery("#"+data['parentid']).parents(".settings-tbl").find(".font-style").css('display','none');
			}
			else {
				jQuery("#"+data['parentid']).parents(".settings-tbl").find(".font-style").css('display','table-row');
			}
		}).always( function() { 
			var cnxt=jQuery("#"+data['parentid']).parents(".settings-tbl").find(".load-fontdiv");
		   	bindgoogleBehaviour(cnxt);
		 });
	});
	/* This function loads second level of fonts on change of first level of fonts - end */

	/* This function loads second level of google fonts on change of first level of google fonts - start */
	var bindgoogleBehaviour = function(scope) {
		jQuery(".google-fonts", scope).change(function(){
			var font = jQuery(this).val();
			var parentid = jQuery(this).attr('id');
			var fname = jQuery(this).parents(".settings-tbl").find(".google-fw").attr('name');
			var fid = jQuery(this).parents(".settings-tbl").find(".google-fw").attr('id');
			var fsubsetnm = jQuery(this).parents(".settings-tbl").find(".google-fsubset").attr('name');
			var fsubsetid = jQuery(this).parents(".settings-tbl").find(".google-fsubset").attr('id');
			var settings_nonce = jQuery("input[name='documentor-settings-nonce']").val();
			var data = {
				'action': 'documentor_disp_gfweight',
				'font': font,
				'fname': fname,
				'fid': fid,
				'parentid': parentid,
				'fsubsetnm': fsubsetnm,
				'fsubsetid': fsubsetid,
				'settings_nonce': settings_nonce
			};
			jQuery.post(ajaxurl, data, function(response) {
				var res = JSON.parse(response);
				jQuery("#"+data['parentid']).parents(".settings-tbl").find(".google-fontsweight").html(res[0]);
				jQuery("#"+data['parentid']).parents(".settings-tbl").find(".google-fontsubset").html(res[1]);
			});
		});
	}
	/* This function loads second level of google fonts on change of first level of google fonts - end */

	jQuery('.sub_settings').find("h2.sub-heading").on("click", function(){
	var wrap=jQuery(this).parent('.toggle_settings'),
	tabcontent=wrap.find("p, table, code, span.doc-settingtitle, div, div.settingsdiv");
	jQuery(this).toggleClass("closed");
	tabcontent.toggle();
	});
		
	//bindBehaviors
	var bindBehaviors = function(scope) { 
		//pagination for recent posts/pages
		jQuery(".pageclk", scope).click(function() {
			paged = jQuery(this).attr("id");	
			type = jQuery(".eb-cs-right").find(".post_type").val();
			var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
			var data = {
				'action': 'doc_show_posts',
				'post_type': type,
				'docid': docid,
				'paged': paged,
				'sections_nonce':sections_nonce
			};
			jQuery.post(ajaxurl, data, function(response) { 
				jQuery(".eb-cs-right").html(response);
			}).always(function() {
			   	var cnxt=jQuery(".eb-cs-right");
		   		bindBehaviors(cnxt);
			});
			return false;
		});
		//pagination for search results
		jQuery(".pageclk-search", scope).click(function() {
			var stext = jQuery(".eb-cs-right").find(".search-input").val();
			paged = jQuery(this).attr("id");	
			type = jQuery(".eb-cs-right").find(".post_type").val();
			var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
			var data = {
				'action': 'doc_show_search_results',
				'post_type': type,
				'docid': docid,
				'search_text': stext,
				'paged': paged,
				'sections_nonce':sections_nonce
			};
			jQuery.post(ajaxurl, data, function(response) { 
				jQuery(".load-searchresults").html(response);
			}).always(function() {
			   	var cnxt=jQuery(".eb-cs-right");
		   		bindBehaviors(cnxt);
			});
			return false;
		});
		//add sections from posts and pages
		jQuery(".add_posts", scope).click(function(){
			var data = {};
			var posts = new Array();
			jQuery('#addsecform').serializeArray().map(function(item) {
				if(item.name == "post_id[]")
					posts.push(item.value);
				else  data[item.name] = item.value;
			});
			data['post_id[]'] = posts;
			data['docid'] = docid;
			data['action'] = 'doc_create_section';
			var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
			data['sections_nonce'] = sections_nonce;
			jQuery.post(ajaxurl, data, function(response) {
				var current_tabid = jQuery(".eb-cs-left .doc-active").attr('id');
				if( current_tabid == 'post' ) {
					jQuery("#post.eb-cs-post").click();
				} else {
					jQuery("#page.eb-cs-post").click();
				}
				jQuery('.doc-success-msg').html(response).show();
			});
			return false;
		});
		//tabs in show posts/pages			
		jQuery('.pgroup').hide();
		var active_tab = '';
		if (typeof(localStorage) != 'undefined' ) {
			active_tab = localStorage.getItem("inner_active_tab");
		}
		if (active_tab != '' && jQuery(active_tab).length ) {
			jQuery(active_tab).fadeIn();
		} else {
			jQuery('.pgroup:first').fadeIn();
		}
		jQuery('.pgroup .collapsed').each(function(){
			jQuery(this).find('input:checked').parent().parent().parent().nextAll().each( 
				function(){
					if (jQuery(this).hasClass('last')) {
						jQuery(this).removeClass('hidden');
							return false;
						}
					jQuery(this).filter('.hidden').removeClass('hidden');
				});
		});
		if (active_tab != '' && jQuery(active_tab + '-tab').length ) {
			jQuery(active_tab + '-tab').addClass('nav-tab-active');
		}
		else {
			jQuery('.p-tabs a:first').addClass('nav-tab-active');
		}
		jQuery('.p-tabs a').click(function(evt) {
			jQuery('.p-tabs a').removeClass('nav-tab-active');
			jQuery(this).addClass('nav-tab-active').blur();
			var clicked_group = jQuery(this).attr('href');
			if (typeof(localStorage) != 'undefined' ) {
				localStorage.setItem("inner_active_tab", jQuery(this).attr('href'));
			}
			jQuery('.pgroup').hide();
			jQuery(clicked_group).fadeIn();
			evt.preventDefault();	
					// Editor Height (needs improvement)
			jQuery('.wp-editor-wrap').each(function() {
				var editor_iframe = jQuery(this).find('iframe');
				if ( editor_iframe.height() < 30 ) {
					editor_iframe.css({'height':'auto'});
				}
			});

		});
		//search post/page to add in section
		jQuery(".search-input").keypress(function(e) {
			var stext = jQuery(this).val();
			if( 13 == e.which ) {
				updateSearchResults( stext );
				return false;
			}
			if( searchTimer ) {
				clearTimeout(searchTimer);
				searchTimer = null;
			}
			searchTimer = setTimeout(function(){
				updateSearchResults( stext );
			}, 500);

			
		}).attr('autocomplete','off');
		function updateSearchResults( stext ) {
			var type = jQuery(".eb-cs-right").find(".post_type").val(); 
			var minSearchLength = 3;
			if( stext.length < minSearchLength ) return;
			var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
			var data = {
				'action': 'doc_show_search_results',
				'search_text': stext,
				'post_type': type,
				'docid': docid,
				'sections_nonce':sections_nonce
			};
			jQuery.post(ajaxurl, data, function(response) {
				jQuery(".load-searchresults").html(response);
			}).always(function() {
		   		var cnxt=jQuery(".eb-cs-right");
		   		bindBehaviors(cnxt);
			});
		}
		//add link section
		jQuery(".add-linksectionbtn", scope).click(function(){
			var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
			var data = {
				'docid': docid,
				'action': 'doc_create_section',
				'sections_nonce':sections_nonce
			};
			jQuery('.addsecform').serializeArray().map(function(item) {
					data[item.name] = item.value;
			});
			jQuery.post(ajaxurl, data, function(response) {
				//check if error message is set else reset reorder div
				var res = response.substring(0, 5);
				if( res == 'error' ) {
					jQuery('.doc-success-msg').html("<div class='validation-msg'>"+response+"</div>").show();
				} else {
					jQuery('.doc-success-msg').html(response).show();
					jQuery(".addsecform .txts").val('');
					jQuery(".addsecform input[type='checkbox']").removeProp('checked');
				}
			});
			return false;
		});
		//onchange of new window checkbox
		jQuery('.new_window').on('change', function() {
			if(jQuery(this).is(':checked') == true ) {
				jQuery(this).siblings('.targetw').val('1');	
			}
			else {
				jQuery(this).siblings('.targetw').val('0');
			}
		});
		//change background color on hover of post/pages table row
		jQuery(".eb-cs-right tbody tr").hover(
			function() {
				jQuery(this).css({'background-color':'#DDDDDD'});
			}, 
			function() {
				jQuery(this).css({'background-color':'#ffffff'});
			}
		);
	};
	//add inline section
	var inlinecntx = jQuery(".eb-cs-right-wrap");
	jQuery(".add-inlinesectionbtn", inlinecntx).click(function(){
		if( inlinecntx.find("#wp-content-wrap").hasClass('html-active')) {
			var icontent = inlinecntx.find("#content").val();
		} else {
			var icontent = tinyMCE.activeEditor.getContent();
		}
		var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
		var data = {
			'docid': docid,
			'action': 'doc_create_section',
			'icontent': icontent,
			'sections_nonce': sections_nonce
		};
		jQuery('.addsecform').serializeArray().map(function(item) {
				data[item.name] = item.value;
		});
		jQuery.post(ajaxurl, data, function(response) {
			//check if error message is set else reset reorder div
			var res = response.substring(0, 5);
			if( res == 'error' ) {
				jQuery('.doc-success-msg').html("<div class='validation-msg'>"+response+"</div>").show();
			} else {
				jQuery('.doc-success-msg').html(response).show();
				jQuery(".addinlinesecform .txts").val('');
				if( inlinecntx.find("#wp-content-wrap").hasClass('html-active')) {
					var icontent = inlinecntx.find("#content").val('');
				} else {
					var icontent = inlinecntx.find("#content_ifr").contents().find("body").html('');
				}
			}
		});
		return false;
	});

	//set active tab
	jQuery(".eb-cs-tab").click(function() {
		jQuery('.doc-success-msg').html('').hide();
		jQuery(".eb-cs-tab").removeClass("doc-active");
		jQuery(this).addClass("doc-active");
	});
	//show posts/pages to add in sections
	jQuery(".eb-cs-post").click(function(){
		jQuery('.addinlinesecform').css('display','none');
		var type = jQuery(this).attr("id");
		var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
		var data = {
			'post_type': type,
			'docid': docid,
			'action': 'doc_show_posts',
			'sections_nonce': sections_nonce
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery(".eb-cs-right").html(response);
		}).always(function() {
			var cnxt=jQuery(".eb-cs-right");
		   	bindBehaviors(cnxt);
		});
	});
	//add inline section
	jQuery(".eb-cs-blank").click(function(){
		jQuery('.addinlinesecform').css('display','block');
		jQuery(".eb-cs-right").empty();
	});
	jQuery(".eb-cs-links").click(function(){
		jQuery('.addinlinesecform').css('display','none');
		var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
		var data = {
			'action': 'doc_section_add_linkform',
			'docid': docid,
			'sections_nonce': sections_nonce
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery(".eb-cs-right").html(response);
		}).always(function() {
			var cnxt=jQuery(".eb-cs-right");
		   	bindBehaviors(cnxt);
		});
	});
	/* if Custom Posts are disabled from global settings then display posts tab contents */
	if( jQuery('.eb-cs-blank').length < 1 && jQuery('.eb-cs-post').length > 0 ) {
		jQuery('#post.eb-cs-post').trigger('click');
	}
	var bindReorder = function(scope) {
		/* Reorder */
		var updateOutput = function(e)
		{
			var list   = e.length ? e : jQuery(e.target),
			output = list.data('output');
			if( typeof output !== "undefined" ) {
				if (window.JSON) {
				   output.val(window.JSON.stringify(list.nestable('serialize')));//, null, 2));
				} else {
				    output.val('JSON browser support required for this demo.');
				}
			}
		};
		jQuery( "#reorders" ).nestable({
			 rootClass       : 'reorders',					
			 itemClass       : 'table-row',
			 handleClass	 : 'doc-list',
			 expandBtnHTML   : '',
            		 collapseBtnHTML : '',
		}).on('change', updateOutput);
		jQuery(window).mousemove(function (e) {
		    if (jQuery('.dd-dragel') && jQuery('.dd-dragel').length > 0 && !jQuery('html, body').is(':animated')) {
			var bottom = jQuery(window).height() - 50,
			    top = 50;
			
			if (e.clientY > bottom && (jQuery(window).scrollTop() + jQuery(window).height() < jQuery(document).height() - 100))
			 {
			    jQuery('html, body').animate({
				scrollTop: jQuery(window).scrollTop() + 300
			    }, 600);
			    
			}
			else if (e.clientY < top && jQuery(window).scrollTop() > 0) {
			    jQuery('html, body').animate({
				scrollTop: jQuery(window).scrollTop() - 300
			    }, 600);
			   
			} else {
			    jQuery('html, body').finish();
			}
		    } 
		});
		// output initial serialised data
		if( jQuery('#reorders').length > 0 ) {
			updateOutput(jQuery('#reorders').data('output', jQuery('#reorders-output')));
		}
		//onchange of new window checkbox
		jQuery('.new_window').on('change', function() {
			if(jQuery(this).is(':checked') == true ) {
				jQuery(this).siblings('.targetw').val('1');	
			}
			else {
				jQuery(this).siblings('.targetw').val('0');
			}
		});

		//update section
		jQuery(".update-section",scope).click(function(){
			var item_type = jQuery(this).parents('.table-row:first').find('.item-type:first').text();
			var linkurl = new_window = menutitle = sectiontitle = '';
			menutitle = jQuery(this).parents('.table-row:first').find('.menutitle:first').val();
			var type = jQuery(this).parents('.table-row:first').find('.ptype:first').val();
			var docid = jQuery(this).parents('.table-row:first').find('.docid:first').val();
			var section_id = jQuery(this).parents('.table-row:first').find('.section_id:first').val();
			var postid = jQuery(this).parents('.table-row:first').find('.post-id:first').val();
			var slug = jQuery(this).parents('.table-row:first').find('.sec-slug:first').val();
			if( item_type == 'Link' ) {
				linkurl = jQuery(this).parents('.table-row:first').find('.linkurl:first').val();
				new_window = jQuery(this).parents('.table-row:first').find('.targetw:first').val();
					
			} else {
				sectiontitle = jQuery(this).parents('.table-row:first').find('.sectiontitle:first').val();
			}
			var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
			var secdata = {
				'action': 'doc_update_section',
				'menutitle': menutitle,
				'sectiontitle': sectiontitle,
				'linkurl': linkurl,
				'new_window': new_window,
				'type': type,
				'docid': docid,
				'section_id': section_id,
				'post_id': postid,
				'slug' : slug,
				'sections_nonce': sections_nonce
			};
			var docloader = jQuery("input[name='documentor-loader']").val();
			jQuery(this).siblings(".docloader").append('<div class="doc-loader" style="background: url('+docloader+') 50% 50% ;background-repeat: no-repeat;width: 100px;height: 20px;margin: 5px auto;"></div>');
			jQuery.post(ajaxurl, secdata, function(response) {
				var res = JSON.parse(response);
				if( typeof res['error'] !== 'undefined' ) {
					jQuery('#'+secdata['section_id']).find('.validation-msg:first').html(res['error']);
				} 
				if( typeof res['slug'] !== 'undefined' ) {
					jQuery('#'+secdata['section_id']).find('.sec-slug:first').val(res['slug']);
				} 
				jQuery('#'+secdata['section_id']).find(".doc-loader:first").remove();
			});
			return false;
		});
		//remove section
		jQuery(".remove-section").leanModal({ top : 200, overlay : 0.4, closeButton: ".modal_close" });
		jQuery(".remove-section",scope).on("click", function(e) {
			if( jQuery(this).parents(".table-row:first").find('.dd-list').length > 0 ) {
				jQuery(this).leanModal({ top : 200, overlay : 0.4, closeButton: ".modal_close" });
			}
			else{
				//change text of delete section popup
				jQuery(this).siblings(".confirmdelete").html('<div class="doc-popupcontent text">Do you want to delete section ?</div> <div class="doc-popupcontent"><button class="delete_section btn-delete">Delete</button><button class="keep_section btn-cancel">Cancel</button></div>');
				jQuery(this).leanModal({ top : 200, overlay : 0.4, closeButton: ".modal_close" });
			}
		});
		//events in case of section having children sections
		jQuery(".delete_child",scope).on("click", function() {
			jQuery(this).parents(".table-row:first").remove();
			updateOutput(jQuery('#reorders').data('output', jQuery('#reorders-output')));
			jQuery("#lean_overlay").fadeOut(200);
			jQuery(".confirmdelete").css({
			    "display" : "none"
			});
			return false;
		});
		jQuery(".keep_child",scope).on("click", function() {
			jQuery(this).parents(".table-row:first").find('.dd-list:first').find('.table-row:first').unwrap().unwrap();
			jQuery(this).parents(".doc-list:first").remove();
			updateOutput(jQuery('#reorders').data('output', jQuery('#reorders-output')));
			jQuery("#lean_overlay").fadeOut(200);
			jQuery(".confirmdelete").css({
			    "display" : "none"
			});
			return false;
		});
		//events in case of section does not have children sections
		jQuery(".confirmdelete",scope).on("click",".delete_section", function() {
			jQuery(this).parents(".table-row:first").remove();
			updateOutput(jQuery('#reorders').data('output', jQuery('#reorders-output')));
			jQuery("#lean_overlay").fadeOut(200);
			jQuery(".confirmdelete").css({
			    "display" : "none"
			});
			return false;
		});
		jQuery(".confirmdelete").on("click",".keep_section", function() {
			jQuery("#lean_overlay").fadeOut(200);
			jQuery(".confirmdelete").css({
			    "display" : "none"
			});
			return false;
		});
		
		
		
		//save all sections
		jQuery(".save-sections",scope).click(function() {
			var sectionObj = {};
			jQuery( ".table-row" ).each(function( idx ) {
				var item_type = jQuery(this).find('.item-type:first').text();
				var linkurl = new_window = menutitle = sectiontitle = content = postid = '';
				menutitle = jQuery(this).find('.menutitle:first').val();
				var type = jQuery(this).find('.ptype:first').val();
				var docid = jQuery(this).find('.docid:first').val();
				var postid = jQuery(this).find('.post-id:first').val();
				var section_id = jQuery(this).find('.section_id:first').val();
				var slug = jQuery(this).find('.sec-slug:first').val();
				if( item_type == 'Link' ) {
					linkurl = jQuery(this).find('.linkurl:first').val();
					new_window = jQuery(this).find('.targetw:first').val();
				} else {
					sectiontitle = jQuery(this).find('.sectiontitle:first').val();
				}
				if( item_type == 'Inline' ) {
					content = jQuery(this).find('.wp-editor-area:first').val();
				}
				var sections = {};
				sections['menutitle']=menutitle;
				sections['sectiontitle']=sectiontitle;
				sections['content']=content;
				sections['linkurl']=linkurl;
				sections['new_window']=new_window;
				sections['type']=type;
				sections['docid']=docid;
				sections['section_id']=section_id;
				sections['postid']=postid;
				sections['slug'] = slug;
				sectionObj[idx] = sections;
			});
			var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
			var data = {
				'action': 'doc_save_sections',
				'sectionObj': sectionObj,
				'sections_nonce': sections_nonce
			};
			jQuery('.guide-secform').serializeArray().map(function(item) {
					data[item.name] = item.value;
			});
			//display loader
			jQuery("#reorders").empty();
			var docloader = jQuery("input[name='documentor-loader']").val();
			jQuery("#reorders").append('<div class="doc-loader" style="background: url('+docloader+') 50% 50% ;background-repeat: no-repeat;width: 100px;height: 20px;margin: 15px auto;"></div>');
			jQuery.post(ajaxurl, data, function(response) {
				var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
				var res = response.substring(0, 7);
				if( res == 'Warning' ) {
					jQuery('.doc-success-msg').html("<div class='validation-msg'>"+response+"</div>").show();
				} 
				else {
					jQuery('.doc-success-msg').html("").hide();
				}
				var data = {
					'action': 'documentor_show',
					'docid': docid,
					'sections_nonce':sections_nonce
				};
				jQuery.post(ajaxurl, data, function(response) {
					jQuery("#reorders").find(".doc-loader").remove();
					jQuery("#reorders").html(response);			
				}).always(function() {
					var cnxt = jQuery("#reorders");
					bindReorder(cnxt);
				});
				//jQuery("#savesecpopup").trigger("click");
				setTimeout(function(){ jQuery("#lean_overlay").trigger("click"); }, 1000);
			});
			return false;
		});
		//cancel section edit
		jQuery(".cancel-section").on('click', function(e) {
			e.preventDefault();
			jQuery( this ).parents(".doc-list:first").find(".section-form:first").slideUp( "slow" );
			jQuery( this ).parents(".doc-list:first").find(".sectiont_img").removeClass( "open" ).addClass( "close" );
		});
		//edit section
		jQuery(".sectiont_img").on('click', function() {
			if(jQuery( this ).hasClass( "close" ))
			{
				jQuery( this ).siblings(".section-form").slideDown( "slow" );						
				jQuery( this ).removeClass( "close" );
				jQuery( this ).addClass( "open" );
				
			} else{
				jQuery( this ).siblings(".section-form").slideUp( "slow" );
				jQuery( this ).removeClass( "open" );
				jQuery( this ).addClass( "close" );
				
			}
			return false;
		});
		//if sec parameter present in URL change button image to open else close image
		if( getUrlParameter('tab') == 0 && getUrlParameter('sec') != 'undefined' ) {
			var sec = getUrlParameter('sec');
			jQuery(".guide-secform #"+sec).find(".sectiont_img:first").removeClass('close').addClass('open');
			jQuery(".guide-secform #"+sec).find(".section-form:first").show();
		}
		//hide/display save sections button
		if( jQuery('.guide-secform .reorders').find('.doc-list').length == 0 ) {
			jQuery('.save-sections').hide();
		} else {
			jQuery('.save-sections').show();
		}
	};
	if(docid != undefined ) {
		var sections_nonce = jQuery("input[name='documentor-sections-nonce']").val();
		var data = {
			'action': 'documentor_show',
			'docid': docid,
			'sections_nonce':sections_nonce
		};
		jQuery.post(ajaxurl, data, function(response) {
			jQuery("#reorders").html(response);
		}).always(function() {
		   	var cnxt=jQuery(".guide-secform");
	   		bindReorder(cnxt);
			var cnxt=jQuery(".eb-cs-right");
			bindBehaviors(cnxt);
		});
	 } 
	 
	
});



