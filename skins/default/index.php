<?php 
class DocumentorLiteDisplaydefault{
	function __construct($id=0) {
		$this->docid=$id;
	}
	//build menues for front end
	function buildFrontMenus($obj) {
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
			$mtitle = '';
			foreach( $sectiondata as $secdata ) {
				$postid = $secdata->post_id;
				if( $secdata->type == 3 ) { //if link section
					$postdata = get_post( $postid );
					if( $postdata != NULL ) {
						$jarr = unserialize( $postdata->post_content );
						$target = '';
						if( $jarr['new_window'] == '1' ) {
							$target = 'target="_blank"';
						}
						$mtitle ='<a href="'.$jarr['link'].'" '.$target.' '.$cssarr['navmenu'].' >'.$postdata->post_title.'</a>'; 
					}
				} else { //if section is post or page or inline
					//WPML
					if( function_exists('icl_plugin_action_links') ) {	
						if( $secdata->type == 0 ) $type = 'documentor-sections';
						else if( $secdata->type == 1 ) $type = 'post';
						else if( $secdata->type == 2 ) $type = 'page';
						$lang_post_id = icl_object_id( $postid , $type, true, ICL_LANGUAGE_CODE );
						$menu_title = get_post_meta( $lang_post_id, '_documentor_menutitle', true );
					} else {
						$menu_title = get_post_meta( $postid, '_documentor_menutitle', true );
					}
					$mtitle = $menu_title;
				}
				$liactiveclass = '';
				if( $secdata->type == 3 ) {
					$html .= '<li class="doc-actli">'.$mtitle;
				} else {
					//pretty links
					$linkhref = '#section-'.$secdata->sec_id;
					if( !empty( $secdata->slug ) ) {
						$linkhref = apply_filters( 'editable_slug', $secdata->slug );
						$linkhref = '#'.$linkhref;
					} 
					$html .= '<li class="doc-actli"><a class="documentor-menu" href="'.$linkhref.'" data-section-id="'.$secdata->sec_id.'" '.$cssarr['navmenu'].' >'.$mtitle.'</a>';
				}
				if ( isset( $obj->children ) && $obj->children ) {
					$html .= '<ol>';
					foreach( $obj->children as $child ) {
					    $html .= $this->buildFrontMenus($child);
					}
					$html .= '</ol>';
				}
				$html .= '</li>';
			}
		}
		
		return $html;
	}
	//build sections to display on front
	function buildFrontSections($obj, $i) {
		$documentor = new DocumentorLite();
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
			$scrolltopimg = $documentor->documentor_plugin_url( $root."/images/top.png" );
			$linkpng = $documentor->documentor_plugin_url( $root."/images/link.png" );
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
					$currsecurl = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']."#section-".$secdata->sec_id : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
					
					$currsecurl = $currsecurl."#".$sectionid;
					
					$shtml.= $starttag.' class="doc-sec-title" '.$cssarr['sectitle'].'><span class="doc-sec-link"><img src="'.esc_url( $linkpng ).'" onclick="prompt(\'Press Ctrl + C, then Enter to copy to clipboard\',\''.$currsecurl.'\')"></span> '.$section_title.$endtag;
					//front-end edit section
					if( post_type_exists($type) ) { 
						if ( is_user_logged_in() && current_user_can('edit_post', $postid)) {
							$edtlink = get_edit_post_link($postid);
							$shtml.= '<span><a href="'.esc_url($edtlink).'" target="_blank">'. __('Edit','documentorlite').'</a></span>';
						}
					}
				} 
				$shtml.= '<input type="hidden" name="secid" class="hidden_secid" value="'.esc_attr($secdata->sec_id).'">';
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
								$html.='<div class="doc-mdate">'.__('Last updated on','documentorlite').' '.$modified_date.'</div>';
							}
						$html.='</div>';
				}
				$html .= '</div>';
				if ( isset( $obj->children ) && $obj->children ) {
					foreach( $obj->children as $child ) {
					    $i++;
					    $html .= $this->buildFrontSections($child, $i);
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
		$documentor = new DocumentorLite();
		//enqueue required files
		wp_enqueue_script( 'doc_fixedjs', $documentor->documentor_plugin_url( 'core/js/jquery.lockfixed.js' ), array('jquery'), DOCUMENTORLITE_VER, false);
		wp_enqueue_script( 'doc_js', $documentor->documentor_plugin_url( 'core/js/documentor.js' ), array( 'jquery' ) );
		wp_localize_script( 'doc_js', 'DocAjax', array( 'docajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		
		//create html structure
		$html = "";
		if( !empty( $guideobj->sections_order ) ) {
			//new field added in v1.1
			$settings['scrolltop'] = isset( $settings['scrolltop'] ) ? $settings['scrolltop'] : 1;
			//include skin stylesheet
			$html .="<link rel='stylesheet' href='".$documentor->documentor_plugin_url( 'skins/default/style.css' )."' type='text/css' media='all' />";
			$rtl_support = isset($settings['rtl_support']) ? $settings['rtl_support'] : '0'; 
			$wrapclass = '';
			if( $rtl_support == '1' ) $wrapclass = ' documentor-rtl';
			//wrap div
			$html .= '<div id="documentor-'.$this->docid.'" class="documentor-'.$settings['skin'].' documentor-wrap'.$wrapclass.'" data-docid = "'.$this->docid.'" >';
			$html .= '<input type="hidden" name="docid" class="hidden_docid" value="'.$this->docid.'">';
			$menupos = isset($settings['menu_position']) ? $settings['menu_position'] : 'left'; 
			$menuclass = $sec_containerclass = '';
			if( $menupos == 'right' ) {
				$menuclass = ' doc-menuright';
				$sec_containerclass = ' doc-seccontainer-left';
			}
			$html .= ' 	<div class="doc-menu'.$menuclass.'" ><div class="doc-menuinner">';
			$obj = $guideobj->sections_order;
			if( !empty( $obj ) ) {
				$jsonObj = json_decode( $obj );
				$html.='<ol class="doc-list-front">';
				foreach( $jsonObj as $jobj ) {
					$html.= $this->buildFrontMenus($jobj);
				}
				$html.='</ol>';
			} 
			$html.=	'</div></div>';
			if( !empty( $obj ) ) {
				$jsonObj = json_decode( $obj );
				$html.='<div class="doc-sec-container'.$sec_containerclass.'" id="documentor_seccontainer">';
				$i = 0;
				foreach( $jsonObj as $jobj ) {
					$i++;
					$html.= $this->buildFrontSections($jobj, $i);
				}
				$html.='</div>';
			}     
			$clearclass = '';
			if( $rtl_support == '1' ) { $clearclass = ' cleardiv-rtl'; }    
			if( $settings['scrolltop'] == '1' ) {
				$html .='<a class="scrollup" style="display: block;"><span class="icon-untitled"></span></a>';
			}
			$html .='</div><div class="cleardiv'.$clearclass.'"> </div><div id="documentor-'.$this->docid.'-end"></div>' ;
			$secstyle ='';
			if( $settings['indexformat'] == 1 ) {
				$css = $guideobj->get_inline_css();
				$reptxt = 'style="';
				$secstyle = str_replace($reptxt,"",$css['navmenu']);
				$secstyle = rtrim($secstyle, '"');
			}
			if( !empty ( $settings['animation'] ) ) {
				wp_enqueue_script( 'documentor_wowjs', $documentor->documentor_plugin_url( 'core/js/wow.js' ), array('jquery'), DOCUMENTORLITE_VER, false);
			}
			$settings['scrolling'] = ( !isset( $settings['scrolling'] )  ) ? 1 : $settings['scrolling']; 
			$settings['fixmenu'] = ( !isset( $settings['fixmenu'] )  ) ? 1 : $settings['fixmenu'];
			$settings['scroll_size'] = ( !isset( $settings['scroll_size'] )  ) ? 3 : $settings['scroll_size']; 
			$settings['scroll_color'] = ( !isset( $settings['scroll_color'] )  ) ? '#F45349' : $settings['scroll_color']; 
			$settings['scroll_opacity'] = ( !isset( $settings['scroll_opacity'] )  ) ? 0.4 : $settings['scroll_opacity'];  
			$script =  '<script type="text/javascript">
			jQuery(document).ready(function(){
				jQuery("#documentor-'.$this->docid.'").documentor({
					docid		: "documentor-'.$this->docid.'",
					animation	: "'.$settings['animation'].'",
					indexformat	: "'.$settings['indexformat'].'",
					secstyle	: "'.$secstyle.'",
					actnavbg_default: "'.$settings['actnavbg_default'].'",
					actnavbg_color	: "'.$settings['actnavbg_color'].'",
					scrolling	: "'.$settings['scrolling'].'",
					fixmenu		: "'.$settings['fixmenu'].'",
					skin		: "default",
					scrollBarSize	: "'.$settings['scroll_size'].'",
					scrollBarColor	: "'.$settings['scroll_color'].'",
					scrollBarOpacity: "'.$settings['scroll_opacity'].'"
				});	
			});</script>'; 
			$html .= $script;
			return $html;
		}//if section order is present
	}//function display ends
}//class ends
?>
