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
					$html .= '<li class="doc-actli"><a class="documentor-menu" href="#section-'.$secdata->sec_id.'" data-section-id="'.$secdata->sec_id.'" '.$cssarr['navmenu'].' >'.$mtitle.'</a>';
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
			$tran_class = "";
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
			foreach( $sectiondata as $secdata ) {	
				//$html .='fffffffffffttttt';
				$shtml = "";
				$postid = $secdata->post_id;
				if( $secdata->type == 0 ) $type = 'documentor-sections';
				else if( $secdata->type == 1 ) $type = 'post';
				else if( $secdata->type == 2 ) $type = 'page';
				else if( $secdata->type == 3 ) $type = 'link';
				if( $secdata->type != 3 ) { //If not a link section
					//WPML
					if( function_exists('icl_plugin_action_links') ) {	
						$lang_post_id = icl_object_id( $postid , $type, true, ICL_LANGUAGE_CODE );
						$section_title = get_post_meta( $lang_post_id, '_documentor_sectiontitle', true );
						$postid = $lang_post_id;
					} else {
						$section_title = get_post_meta( $postid, '_documentor_sectiontitle', true );
					}
					$currsecurl = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']."#section-".$secdata->sec_id : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']."#section-".$secdata->sec_id;
					
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
				$html.= '<div class="doc-section '.esc_attr($tran_class).'" id="section-'.esc_attr($secdata->sec_id).'" data-section-id="'.esc_attr($secdata->sec_id).'">';
				$html .= $shtml;
				$html .= '<div class="doc-sec-content" '.$cssarr['sectioncontent'].'>';
				if( $secdata->type != 3 ) { //not link section
					//WPML
					if( function_exists('icl_plugin_action_links') ) {	
						if( $secdata->type == 0 ) $type = 'documentor-sections';
						else if( $secdata->type == 1 ) $type = 'post';
						else if( $secdata->type == 2 ) $type = 'page';
						$lang_post_id = icl_object_id( $postid , $type, true, ICL_LANGUAGE_CODE );
						$post = get_post( $lang_post_id );
					} else {
						$post = get_post( $postid );
					}
					if( $post != null ) {
						$pcontent = $post->post_content;
						$html .= apply_filters( 'the_content' , $pcontent );
					}
				} 
				$html .= '</div>';
				if( $secdata->type != 3	) { //not link section
					$html.= '<div class="doc-help"> 
							<a class="scrollup" style="display: block;"><span class="icon-untitled"></span></a>
						</div>';
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
		wp_enqueue_style( 'doc_'.$settings['skin'].'_css', $documentor->documentor_plugin_url( 'skins/default/style.css' ), false, DOCUMENTORLITE_VER, 'all');	
		wp_enqueue_script( 'doc_fixedjs', $documentor->documentor_plugin_url( 'core/js/jquery.lockfixed.js' ), array('jquery'), DOCUMENTORLITE_VER, false);
		wp_enqueue_script( 'doc_js', $documentor->documentor_plugin_url( 'core/js/documentor.js' ), array( 'jquery' ) );
		wp_localize_script( 'doc_js', 'DocAjax', array( 'docajaxurl' => admin_url( 'admin-ajax.php' ) ) );
		
		//create html structure
		$html = "";
		if( !empty( $guideobj->sections_order ) ) {
			//style
			$html .= '<style>.documentor-default .doc-menu{ width: 30%;float: left;}.documentor-default .doc-sec-container {width: 65%;float: right; }</style>';
			//wrap div
			$html .= '<div id="documentor-'.$this->docid.'" class="documentor-'.$settings['skin'].' documentor-wrap" data-docid = "'.$this->docid.'" >';
			$html .= '<input type="hidden" name="docid" class="hidden_docid" value="'.$this->docid.'">';
			$html .= ' 	<div class="doc-menu" >';
			$obj = $guideobj->sections_order;
			if( !empty( $obj ) ) {
				$jsonObj = json_decode( $obj );
				$html.='<ol class="doc-list-front">';
				foreach( $jsonObj as $jobj ) {
					$html.= $this->buildFrontMenus($jobj);
				}
				$html.='</ol>';
			} 
			$html.=	'</div>';
			if( !empty( $obj ) ) {
				$jsonObj = json_decode( $obj );
				$html.='<div class="doc-sec-container" id="documentor_seccontainer">';
				$i = 0;
				foreach( $jsonObj as $jobj ) {
					$i++;
					$html.= $this->buildFrontSections($jobj, $i);
				}
				$html.='</div>';
			}        
			$html .='</div><div class="cleardiv"> </div><div id="documentor-'.$this->docid.'-end"></div>' ;
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
					fixmenu		: "'.$settings['fixmenu'].'"
				});	
			});</script>'; 
			$html .= $script;
			return $html;
		}//if section order is present
	}//function display ends
}//class ends
?>
