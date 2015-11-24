<?php 
class DocumentorLiteDisplaybroad {
	function __construct($id=0) {
		$this->docid=$id;
	}
	//build menues to display at front
	function buildFrontMenus($obj, $i, $k, $j) {
		if(isset($this->docid)) {
			if( class_exists( 'DocumentorLiteSection' ) ) {
				$id = $this->docid;
				$ds = new DocumentorLiteSection( $id, $obj->id);
			}
		}
		$html = "";
		if( $ds != null ) {
		$guide = new DocumentorLiteGuide( $this->docid );
		$settings = $guide->get_settings();
		$cssarr = $guide->get_inline_css();
		$sectiondata = $ds->getdata();
		$aattr = '';
		if( $k == 'parent' ) {
			$aattr = 'data-mwrapcnt="'.$i.'"';
		}
		foreach( $sectiondata as $secdata ) {
			$postid = $secdata->post_id;
			$mtitle = '';
			if( $k == 'parent' ) {
				$scnt = $i;
			}
			else {
				$scnt = $i.'.'.$j;
			}
			$i = $scnt;
			if( $secdata->type == 3 ) { //if link section
				$postdata = get_post( $postid );
				if( $postdata != NULL ) {
					$jarr = unserialize( $postdata->post_content );
					$target = '';
					if( $jarr['new_window'] == '1' ) {
						$target = 'target="_blank"';
					}
					$mtitle ='<a href="'.esc_url($jarr['link']).'" '.$target.' '.$cssarr['navmenu'].' data-href="'.esc_url($jarr['link']).'">'.$postdata->post_title.'</a>'; 
				}
			} else { //if section is post or page or inline
				//WPML
				if( function_exists('icl_plugin_action_links') ) {	
					if( $secdata->type == 0 ) $type = 'documentor-sections';
					else if( $secdata->type == 1 ) $type = 'post';
					else if( $secdata->type == 2 ) $type = 'page';
					else if( $secdata->type == 4 ) {
						$type = get_post_type( $postid );
					}
					$lang_post_id = icl_object_id( $postid , $type, true, ICL_LANGUAGE_CODE );
					$menu_title = get_post_meta( $lang_post_id, '_documentor_menutitle', true );
				} else {
					$menu_title = get_post_meta( $postid, '_documentor_menutitle', true );
				}
				$mtitle = $menu_title;
			}
			$liactiveclass = '';
			if( $secdata->type == 3 ) {
				$html .= '<li class="doc-actli" '.$aattr.'>';
				$html .= $mtitle;
			} else {
				//pretty links
				$linkhref = '#section-'.$secdata->sec_id;
				if( !empty( $secdata->slug ) ) {
					$linkhref = apply_filters( 'editable_slug', $secdata->slug );
					$linkhref = '#'.$linkhref;
				} 
				$html .= '<li class="doc-actli">';
 				$html .= '<a class="documentor-menu" href="'.$linkhref.'" data-section-id="'.esc_attr($secdata->sec_id).'" '.$cssarr['navmenu'].' data-sec-counter="'.$scnt.'" data-href="'.$linkhref.'" '.$aattr.'>'.$mtitle.'</a>';
			}
			if ( isset( $obj->children ) && $obj->children ) {
				$html .= '<span class="doc-mtoggle expand"></span>';
				$html .= '<ol>';
				$j = 0;
				foreach( $obj->children as $child ) {
					$j++;$k = 'child';
				    	$html .= $this->buildFrontMenus($child, $i, $k, $j);
				}
				$html .= '</ol>';
			}
			$html .= '</li>';
		}
	}
		return $html;
	}
	//build sections to display on front
	function buildFrontSections($obj, $i, $k, $j) {
		if(isset($this->docid)) {
			if( class_exists( 'DocumentorLiteSection' ) ) {
				$id = $this->docid;
				$ds = new DocumentorLiteSection( $id, $obj->id);
			}
		}
		$html = "";
		if( $ds != null ) {
			$guide = new DocumentorLiteGuide( $this->docid );
			$cssarr = $guide->get_inline_css();
			$sectiondata = $ds->getdata();
			$settings = $guide->get_settings();
			$root = 'skins/'.$settings['skin'];
			$tran_class = $sectionid = "";
			$scrolltopimg = DocumentorLite::documentor_plugin_url( $root."/images/top.png" );
			$linkpng = DocumentorLite::documentor_plugin_url( $root."/images/link.png" );
			if( !empty( $settings['animation'] ) ) {
				$tran_class = "wow documentor-animated documentor-".$settings['animation'];
			}
			$starttag = '<h3'; 
			$endtag = '</h3>'; 
			if( isset( $settings['section_element'] ) ) {
				for( $h = 1; $h <= 6; $h++ ) {
					if( $settings['section_element'] == $h ) {
						$starttag = '<h'.$h; 
						$endtag = '</h'.$h.'>'; 
					} 
				}
			}
			global $wpdb;
			//new field added in v1.1
			$settings['updated_date'] = isset( $settings['updated_date'] ) ? $settings['updated_date'] : 0;
			foreach( $sectiondata as $secdata ) {	
				$shtml = "";
				$postid = $secdata->post_id;
				if( $secdata->type == 0 ) $type = 'documentor-sections';
				else if( $secdata->type == 1 ) $type = 'post';
				else if( $secdata->type == 2 ) $type = 'page';
				else if( $secdata->type == 3 ) $type = 'link';
				if( $secdata->type != 3 ) { //If not a link section
					//pretty links
					$sectionid = 'section-'.$secdata->sec_id;
					if( !empty( $secdata->slug ) ) {
						$sectionid = apply_filters( 'editable_slug', $secdata->slug );
					}
					//WPML
					if( function_exists('icl_plugin_action_links') ) {	
						$lang_post_id = icl_object_id( $postid , $type, true, ICL_LANGUAGE_CODE );
						$section_title = get_post_meta( $lang_post_id, '_documentor_sectiontitle', true );
						$postid = $lang_post_id;
					} else {
						$section_title = get_post_meta( $postid, '_documentor_sectiontitle', true );
					}
					//1.4 :fix for NGINX server
					$servername=$_SERVER['SERVER_NAME'];
					if( strpos($servername, '*') === false ){
						$currsecurl = (!empty($_SERVER['HTTPS'])) ? "https://".$servername.$_SERVER['REQUEST_URI'] : "http://".$servername.$_SERVER['REQUEST_URI'];
					}
					else{
						$currsecurl = get_permalink();
					}
					
					$currsecurl = $currsecurl."#".$sectionid;
					
					$shtml.= $starttag.' class="doc-sec-title" '.$cssarr['sectitle'].'><span class="doc-sec-link"><img src="'.esc_url( $linkpng ).'" onclick="prompt(\'Press Ctrl + C, then Enter to copy to clipboard\',\''.$currsecurl.'\')"></span> '.$section_title.$endtag;
					//front-end edit section
					if( post_type_exists($type) ) { 
						if ( is_user_logged_in() && current_user_can('edit_post', $postid)) {
							$edtlink = get_edit_post_link($postid);
							$shtml.= '<span><a href="'.esc_url($edtlink).'" target="_blank">'. __('Edit','documentor-lite').'</a></span>';
						}
					}
				} 
				$html.= '<div class="doc-section '.esc_attr($tran_class).'" id="'.esc_attr($sectionid).'" data-section-id="'.esc_attr($secdata->sec_id).'">';
				$html .= $shtml;
				$html .= '<div class="doc-sec-content" '.$cssarr['sectioncontent'].'>';
				if( $secdata->type != 3 ) { //not link section
					$post = get_post( $postid );
					if( $post != null ) {
						$pcontent = $post->post_content;
						$html .= apply_filters( 'the_content' , $pcontent );
					}
				} 
				$html .= '</div>';
				//get last modified date
				$modified_date = $wpdb->get_var("SELECT post_modified FROM {$wpdb->posts} WHERE ID = ".$postid);
				$modified_date = date_create($modified_date);
				$modified_date = date_format($modified_date, 'M d, Y');
				if( $secdata->type != 3	) { //not link section
					$html.= '<div class="doc-help">';
							if( $settings['updated_date'] == 1 ) {
								$html.='<div class="doc-mdate">'.__('Last updated on','documentor-lite').' '.$modified_date.'</div>';
							}
						$html.='</div>';
				}
				$html .= '</div>';
				if ( isset( $obj->children ) && $obj->children ) {
					$j = 0;
					foreach( $obj->children as $child ) {
					    $j++;$k = 'child';
					    $html .= $this->buildFrontSections($child, $i, $k, $j);
					}				
				}
			}
		}
		return $html;
	}
	//function to display document at front-end
	function display() {
		$guideobj = new DocumentorLiteGuide( $this->docid );
		$settings = $guideobj->get_settings();
		$currentlink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
		$cssarr = $guideobj->get_inline_css();
		//new field added in v1.1
		$settings['scrolltop'] = isset( $settings['scrolltop'] ) ? $settings['scrolltop'] : 1;
		//enqueue required files
		wp_enqueue_script( 'doc_fixedjs', DocumentorLite::documentor_plugin_url( 'core/js/jquery.lockfixed.js' ), array('jquery'), DOCUMENTORLITE_VER, false);
		wp_enqueue_script( 'doc_js', DocumentorLite::documentor_plugin_url( 'core/js/documentor.js' ), array( 'jquery','jquery-ui-autocomplete' ), DOCUMENTORLITE_VER );
		wp_localize_script( 'doc_js', 'DocAjax', array( 'docajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		if( $settings['socialshare'] == 1 ) {
			wp_enqueue_style( 'doc_socialshare', DocumentorLite::documentor_plugin_url( 'core/css/socialshare_fonts.css' ), false, DOCUMENTORLITE_VER);	
		} 
		
		//create html structure
		$html = "";
		if( !empty( $guideobj->sections_order ) ) {
			//include skin stylesheet
			global $doc_style_counter_broad;
			if( !isset( $doc_style_counter_broad ) or $doc_style_counter_broad < 1 ) {
				$html .="<link rel='stylesheet' href='".DocumentorLite::documentor_plugin_url( 'skins/broad/style.css' )."' type='text/css' media='all' />";
				$doc_style_counter_broad++;
			}
			$rtl_support = isset($settings['rtl_support']) ? $settings['rtl_support'] : '0'; 
			$wrapclass = '';
			if( $rtl_support == '1' ) $wrapclass = ' documentor-rtl';
			//wrap div
			$html .= '<div id="documentor-'.$this->docid.'" class="documentor-'.$settings['skin'].' documentor-wrap'.$wrapclass.'" data-docid = "'.$this->docid.'" >';
			$root = 'skins/'.$settings['skin'];
			$sericonurl = DocumentorLite::documentor_plugin_url( $root."/images/search.png" );
			
			if( isset( $settings['search_box'] ) && $settings['search_box'] == '1' ) {
				$sericonurl = DocumentorLite::documentor_plugin_url( 'skins/'.$settings['skin']."/images/search.png" );
				$html .= '<div class="dcumentor-topicons doc-noprint">';
				$html .= '<span class="doc-search">
						<input type="text" name="search_document" class="search-document" placeholder="'.__('Search in Document','documentor').'" />
						<img src="'.$sericonurl.'" />
					</span></div>';
			}
			//Guide Title
			if( $settings['guidetitle'] == 1 ) {
				$starttag = '<h2'; 
				$endtag = '</h2>'; 
				if( isset( $settings['guidet_element'] ) ) {
					for( $h = 1; $h <= 6; $h++ ) {
						if( $settings['guidet_element'] == $h ) {
							$starttag = '<h'.$h; 
							$endtag = '</h'.$h.'>'; 
						} 
					}
				} 
				if(isset($guideobj->doc_title)){
					$html .= '<div class="doc-guidetitle">'.$starttag.' class="doc-title" '.$cssarr['guidetitle'].'>'.$guideobj->doc_title.$endtag.'</div>';
				}
			}
			$html .= '<div class="doc-document-wrapper">';
			//documentation menu
			$menupos = isset($settings['menu_position']) ? $settings['menu_position'] : 'left'; 
			$menuclass = $sec_containerclass = '';
			if( $menupos == 'right' ) {
				$menuclass = ' doc-menuright';
				$sec_containerclass = ' doc-seccontainer-left';
			}
			if( $settings['togglemenu'] == 1 ) {
				$menuclass .= ' toggle';
			} 
			$html .= ' 	<div class="doc-menu'.$menuclass.' doc-noprint" ><div class="doc-menurelated">';
			$obj = $guideobj->sections_order;
			if( !empty( $obj ) ) {
				$jsonObj = json_decode( $obj );
				$html.='<ol class="doc-list-front">';
				$i = 0;
				foreach( $jsonObj as $jobj ) {
					$i++;$k = 'parent';$j = '';
					$html.= $this->buildFrontMenus($jobj, $i, $k, $j);
				}
				$html.='</ol>';
			} 
			$html.=	'</div></div>';
			if( !empty( $obj ) ) {
				$jsonObj = json_decode( $obj );
				$html.='<div class="doc-sec-container'.$sec_containerclass.'" id="documentor_seccontainer">';
				$i = 0;
				//add social share buttons at top of the document
				if( $settings['socialshare'] == 1 && $settings['sbutton_position'] == 'top' ) {
					$guidetitle = $guideobj->doc_title;
					$html .= $guideobj->get_social_buttons( $settings, $guidetitle, $currentlink ); 
				}  
				foreach( $jsonObj as $jobj ) {
					$i++;$k = 'parent';$j = $inlinestyle = '';
					if( $i == 1 ) {
						$inlinestyle = 'style="display: block;"';
					}
					$html.= '<div class="doc-sectionwrap" data-wrapcnt="'.$i.'" '.$inlinestyle.'>';
					$html.= $this->buildFrontSections($jobj, $i, $k, $j);
					$html.= '</div>';
				}
				//add social share buttons at bottom of the document
				if( $settings['socialshare'] == 1 && $settings['sbutton_position'] == 'bottom' ) {
					$guidetitle = $guideobj->doc_title;
					$html .= $guideobj->get_social_buttons( $settings, $guidetitle, $currentlink ); 
				}  
				$html.='</div><!--.doc-sec-container-->';
			}    
			$clearclass = '';
			if( $rtl_support == '1' ) { $clearclass = ' cleardiv-rtl'; } 
			if( $settings['scrolltop'] == '1' ) {
				$html .='<a class="scrollup doc-noprint" style="display: block;"><span class="icon-top"></span></a>';
			}
			$html .='</div><!--.doc-document-wrapper--></div><div class="cleardiv'.$clearclass.'"> </div><div id="documentor-'.$this->docid.'-end"></div>' ;
			$secstyle ='';
			if( $settings['indexformat'] == 1 ) {
				$reptxt = 'style="';
				$secstyle = str_replace($reptxt,"",$cssarr['navmenu']);
				$secstyle = rtrim($secstyle, '"');
			}
			if( !empty ( $settings['animation'] ) ) {
				wp_enqueue_script( 'documentor_wowjs', DocumentorLite::documentor_plugin_url( 'core/js/wow.js' ), array('jquery'), DOCUMENTORLITE_VER, false);
			}
			$settings['scrolling'] = ( !isset( $settings['scrolling'] )  ) ? 1 : $settings['scrolling']; 
			$settings['fixmenu'] = ( !isset( $settings['fixmenu'] )  ) ? 1 : $settings['fixmenu']; 
			$settings['menuTop'] = ( !isset( $settings['menuTop'] )  ) ? '0' : $settings['menuTop'];
			$settings['scroll_size'] = ( !isset( $settings['scroll_size'] )  ) ? 3 : $settings['scroll_size']; 
			$settings['scroll_color'] = ( !isset( $settings['scroll_color'] )  ) ? '#F45349' : $settings['scroll_color']; 
			$settings['scroll_opacity'] = ( !isset( $settings['scroll_opacity'] )  ) ? 0.4 : $settings['scroll_opacity']; 
			$script =  '<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery("#documentor-'.$this->docid.'").documentor({
					documentid	: '.$this->docid.',
					docid		: "documentor-'.$this->docid.'",
					animation	: "'.$settings['animation'].'",
					indexformat	: "'.$settings['indexformat'].'",					
					pformat		: "'.$settings['pif'].'",
					cformat		: "'.$settings['cif'].'",
					secstyle	: "'.$secstyle.'",
					actnavbg_default: "'.$settings['actnavbg_default'].'",
					actnavbg_color	: "'.$settings['actnavbg_color'].'",
					scrolling	: "'.$settings['scrolling'].'",
					fixmenu		: "'.$settings['fixmenu'].'",
					menuTop		: "'.$settings['menuTop'].'",
					skin		: "broad",
					scrollBarSize	: "'.$settings['scroll_size'].'",
					scrollBarColor	: "'.$settings['scroll_color'].'",
					scrollBarOpacity: "'.$settings['scroll_opacity'].'",
					socialshare	: '.$settings['socialshare'].',
					sharecount	: '.$settings['sharecount'].',
					fbshare		: '.$settings['socialbuttons'][0].',
					twittershare	: '.$settings['socialbuttons'][1].',
					gplusshare	: '.$settings['socialbuttons'][2].',
					pinshare	: '.$settings['socialbuttons'][3].',
					togglechild	: '.$settings['togglemenu'].'
				});	
			});</script>'; 
			$html .= $script;
			return $html;
		}//if section order is present
	}//function display 
}//class ends
?>
