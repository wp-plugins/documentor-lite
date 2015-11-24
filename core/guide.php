<?php 
class DocumentorLiteGuide{
		public $docid;
		public $doc_title='';
		public $settings='';		
		function __construct($id=0) {
			$this->docid=$id;
			if($this->docid>0) {
				global $table_prefix, $wpdb;
				$postid = $this->get_guide_post_id($this->docid);
				if( isset($postid) and intval($postid)>0 ) {
					$title = $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM ".$table_prefix."posts WHERE ID = %d", $postid ) );		
					$settings=get_post_meta($postid,'_doc_settings',true);				
					$sections_order=get_post_meta($postid,'_doc_sections_order',true);				
					$row=(object)array(
						'doc_id'=>$this->docid,					
						'sections_order'=>$sections_order,
						'settings'=>$settings,
						'doc_title'=>$title 
					);				
					$this->settings=$row->settings;
					$this->sections_order=$row->sections_order;
					$this->doc_title=$row->doc_title;	
					$this->title=$row->doc_title;		
				}
				else{//if guide post type did not get created for this guide
					$guide = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$table_prefix.DOCUMENTORLITE_TABLE." WHERE doc_id = %d", $this->docid ) );
					if( count($guide) > 0 ) {
						$doc = new DocumentorLite();
						$default_documentor_settings = $doc->default_documentor_settings;
						if($wpdb->get_var("SHOW COLUMNS FROM ".$table_prefix.DOCUMENTORLITE_TABLE." LIKE 'doc_title'") != 'doc_title'){ //First installation 1.3 and plus - neither post is created nor the Documentor table has any data. Populating default data
							$guide->doc_title='Documentor Guide';
							$guide->settings=$default_documentor_settings;
							$guide->rel_id=0;
							$guide->rel_title='Relevant Links';
							$guide->sections_order='';
							$curr_settings = $guide->settings;
						}
						else {
							$curr_settings = json_decode( $guide->settings, true );
						} //End of 1.3 and plus
						$post= array(
								'post_title'=>$guide->doc_title,
								'post_type'=>'guide',
								'post_status'=>'publish',
								'post_content'=>'[documentor '.$guide->doc_id.']',
								'post_date'=>date('Y-m-d H:i:s', strtotime("now"))
								);
						$post_id=wp_insert_post( $post );
						$wpdb->update( 
								$table_prefix.DOCUMENTORLITE_TABLE, 
								array( 
									'post_id' => $post_id	
								), 
								array( 'doc_id' => $guide->doc_id ), 
								array( 
									'%d'
								), 
								array( '%d' ) 
								);	
						
						//Merging default settings with skin specific settings
						$skin=$curr_settings['skin'];
						$skin_defaults_str='default_settings_'.$skin;
						require_once(dirname(dirname (__FILE__)) . '/skins/'.$skin.'/settings.php');
						$default_settings=array_merge($default_documentor_settings, ${$skin_defaults_str});
						$curr_settings=array_merge($default_settings, $curr_settings);
						
						$curr_settings = json_encode($curr_settings);
						update_post_meta($post_id,'_doc_settings',$curr_settings);
						update_post_meta($post_id,'_doc_sections_order',$guide->sections_order);
						update_post_meta($post_id,'_doc_rel_id',$guide->rel_id);
						update_post_meta($post_id,'_doc_rel_title',$guide->rel_title);
						$this->title=$guide->doc_title;
						$this->doc_title=$guide->doc_title;
						$this->settings=$curr_settings;
						$this->sections_order=$guide->sections_order;
					}//If guide row is present
				}
			} 
		}		
		function get_guide_post_id($docid){ 
		   global $wpdb,$table_prefix;
		   $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM ".$table_prefix.DOCUMENTORLITE_TABLE." WHERE doc_id = %d", $docid ) );
		   return  $post_id;
		}
		//get guide
		function get_guide( $docid ) {
			global $table_prefix, $wpdb;
			$postid = $this->get_guide_post_id($docid);						
			$row = $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE ID = %d", $postid ) );	
			$settings=get_post_meta($postid,'_doc_settings',true);				
			$sections_order=get_post_meta($postid,'_doc_sections_order',true);
			$rel_id=get_post_meta($postid,'_doc_rel_id',true);
			$rel_title=get_post_meta($postid,'_doc_rel_title',true);							
			$guide= (object) array(
				'post_id'=>$postid,
				'doc_id'=>$this->docid,
				'doc_title'=>$row,
				'sections_order'=>$sections_order,
				'settings'=>$settings,
				'rel_id'=>$rel_id,
				'rel_title'=>$rel_title 
			);
			return $guide;				
		}
	    	//update settings       
		function update_settings( $setting, $newtitle ) {
			global $wpdb, $table_prefix;			
			$postid = $this->get_guide_post_id($this->docid);			
			$update_post=array( 
					 'ID' => $postid,
					'post_title' => $newtitle	
					);
			wp_update_post( $update_post );
			update_post_meta($postid,'_doc_settings',$setting);
		}
		//get settings
		function get_settings() {
			global $table_prefix, $wpdb;			
			$postid = $this->get_guide_post_id($this->docid);
			$result=get_post_meta($postid,'_doc_settings',true);	
			if( $result != NULL ) {
				$documentor_curr = json_decode($result, true);
				$documentor_curr = $this->populate_documentor_current($documentor_curr);		
				return $documentor_curr;
			}
		}
		//get sections of document
		function get_sections() {
			global $table_prefix, $wpdb;
			$result = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$table_prefix.DOCUMENTORLITE_SECTIONS." WHERE doc_id = %d",$this->docid ) ); 			
			return $result;			
		}
		//show document on front 
		function view() {
			$html = '';
			$settings_arr = $this->get_settings();
			if( count( $settings_arr ) > 0 ) {
				require_once(dirname(dirname (__FILE__)) . '/skins/'.$settings_arr["skin"].'/index.php');
				$classname = 'DocumentorLiteDisplay'.$settings_arr["skin"];
				$displayobj = new $classname( $this->docid );
				$html = $displayobj->display();
			}
			$html=apply_filters('guide_html', $html);
			return $html;
		}
		//build sections html on admin
		function buildItem($obj) {
			if(isset($this->docid)) {
				if( class_exists( 'DocumentorLiteSection' ) && is_admin() ) {
					$id = $this->docid;
					$ds = new DocumentorLiteSection( $id, $obj->id);
				}
			}
			$html = "";
			if( $ds != null ) {
			$sectiondata = $ds->getdata();
			
			foreach( $sectiondata as $secdata ) {	
				
			
			if( $secdata->type == 0 ) {
				$type = 'Inline';
			} else if( $secdata->type == 1 ) {
				$type = 'Post';
			} else if( $secdata->type == 2 ) {
				$type = 'Page';
			} else if( $secdata->type == 3 ) {
				$type = 'Link';
			}
			$menutitle = '';
			$slug = $secdata->slug;
			$postid = $secdata->post_id;
			//WPML
			if( function_exists('icl_plugin_action_links') ) {	
				if( $secdata->type == 0 ) $type = 'documentor-sections';
				else if( $secdata->type == 1 ) $type = 'post';
				else if( $secdata->type == 2 ) $type = 'page';
				$lang_post_id = icl_object_id( $postid , $type, true, ICL_LANGUAGE_CODE );
				$postdata = get_post( $lang_post_id );
				$postid = $lang_post_id;
			} else {
				$postdata = get_post( $postid );
			}
			if( $secdata->type != 3 ) {
				$menutitle = get_post_meta( $postid, '_documentor_menutitle', true );
			} else if( $secdata->type == 3 ) {
				if( $postdata != NULL )
					$menutitle = $postdata->post_title;
			}
			$sectiontitle = get_post_meta( $postid, '_documentor_sectiontitle', true );
			$html .= '<li class="table-row oldrow close" data-id="'. $obj->id . '" id="' . $obj->id . '">';
			//if sec parameter present in URL change button image to open else close image
			$html .= '<div class="doc-list"><button class="sectiont_img close dd-nodrag" type="button" ></button>';
			$html .= '<div class="table-col slide-title">
					<p class="this-title" >'.$menutitle;
					$html.= '<span class="item-controls">
							<span class="item-type">'.$type.'</span>
						</span>
					</p>
				  </div>';
				  $html .= '<div class="section-form dd-nodrag" style="display:none;">';
					//if not link section and user having capability to edit post
					$ptype = strtolower( $type );
					if( $type == 'Inline' ) $ptype = 'documentor-sections';
					if( post_type_exists($ptype) ) {
						if( ( $secdata->type != 3 ) && current_user_can('edit_post', $postid) ) {  
							$edtlink = get_edit_post_link($postid);
							$html .= '<a href="'.$edtlink.'" target="_blank" class="section-editlink">'. __('Edit','documentor-lite').'</a>';
						}
					}
					$html .= '<div class="sections-div">
						<label class="titles">'. __('Menu Title','documentor-lite').'</label>
						<input type="text" name="menutitle" class="txts menutitle" placeholder="'. __('Enter Menu Title','documentor-lite').'" value="'.esc_attr($menutitle).'" />';
						if( $secdata->type != 3 ) { //if not link section
							$html .='<label class="titles">'. __('Section Title','documentor-lite').'</label>
							<input type="text" name="sectiontitle" class="txts sectiontitle" placeholder="'. __('Enter Section Title','documentor-lite').'" value="'.esc_attr($sectiontitle).'" />';
						}
						if( $secdata->type == 3 ) { //if link section
							$content = unserialize( $postdata->post_content );
							$html.='<label class="titles">'. __('Link','documentor-lite').'</label>
							<input type="text" name="linkurl" class="txts linkurl" placeholder="http://" value="'.esc_url($content['link']).'" />';
							$targetwval = ( $content['new_window'] != '0' ) ? "1":"0";
							$newwindow = ( $content['new_window'] != '0' ) ? 'checked="checked"':"";
							$html.='<label class="titles">'. __('Open in new window','documentor-lite').'</label><input type="checkbox" name="new_window" class="new_window" '.$newwindow.' /><input type="hidden" name="targetw" class="targetw" value="'.esc_attr($targetwval).'">';
						}
						$html.='<div class="clrleft"></div>
						<div class="sections-div">
							<label class="titles">'. __('Slug','documentor-lite').'</label>
							<input type="text" name="slug" class="txts sec-slug" placeholder="'. __('Enter slug','documentor-lite').'" value="'.apply_filters( 'editable_slug', $slug ).'" />
						</div>
						<div class="description-wide submitbox">
								<input type="hidden" name="section_id" class="section_id" value="'.esc_attr($secdata->sec_id).'">
								<input type="hidden" name="post_id" class="post-id" value="'.esc_attr($postid).'">
								<input type="hidden" name="type" class="ptype" value="'.esc_attr($secdata->type).'">
							   	<input type="hidden" name="docid" class="docid" value="'.esc_attr($secdata->doc_id).'">
								<input type="submit" name="update_section" class="update-section link-button" value="'. __('Save','documentor-lite').'" />
								<span class="meta-sep hide-if-no-js"> | </span>
								<a class="remove-section link-button" href="#confirmdelete-'.$secdata->sec_id.'" >'. __('Remove','documentor-lite').'</a> 
								<span class="meta-sep hide-if-no-js"> | </span>
								<input type="submit" name="cancel_section" class="cancel-section link-button" value="'. __('Cancel','documentor-lite').'" />
								<span class="docloader" style="width:20px;height: 20px;"></span>
								<div id="confirmdelete-'.$secdata->sec_id.'" class="confirmdelete" >
									<div class="doc-popupcontent text">'. __('Do you want to delete all children sections ?','documentor-lite').'</div> <div class="doc-popupcontent"><button class="delete_child btn-delete">'. __('Delete children','documentor-lite').'</button><button class="keep_child btn-cancel">'. __('Keep children','documentor-lite').'</button></div></div>	
								<div class="validation-msg"></div>
						</div>
					
					</div>
				</div></div>';					
			if ( isset( $obj->children ) && $obj->children ) {
				$html .= '<ol class="dd-list">';
				foreach( $obj->children as $child ) {
				    $html .= $this->buildItem($child);
				}
				$html .= '</ol>';
			}
			$html .= '</li>';
			}			
			}
			return $html;
		}
		//create inline css array from settings
		function get_inline_css() {
			$settings = $this->get_settings();
			$cssarr = array(
					'navmenu' => '',
					'sectitle' => '',
					'sectioncontent'=>'',
					'guidetitle' => '',
				);
			$style_start= 'style="';
			$style_end= '"';
			$objfonts = new DocumentorLiteFonts();
			//section title
			//check for use theme default option
			if( $settings['sectitle_default'] == 0 ) {
				if ($settings['sectitle_fstyle'] == "bold" or $settings['sectitle_fstyle'] == "bold italic" ){
					$sectitle_fweight = "bold";
				} else {
					$sectitle_fweight = "normal";
				}
				if ($settings['sectitle_fstyle'] == "italic" or $settings['sectitle_fstyle'] == "bold italic"){
					$sectitle_fstyle = "italic";
				} else {
					$sectitle_fstyle = "normal";
				}			
				if( $settings['sect_font'] == 'regular' ) {
					$sect_font = $settings['sectitle_font'].', helvetica, Helvetica, sans-serif';
					$pt_fontw = $sectitle_fweight;
					$pt_fontst = $sectitle_fstyle;
				} else if( $settings['sect_font'] == 'google' ) {
					$sectitle_fontg = isset($settings['sectitle_fontg']) ? trim($settings['sectitle_fontg']) : '';
					$pgfont = $objfonts->get_google_font($settings['sectitle_fontg']);
					( isset( $pgfont['category'] ) ) ? $ptfamily = $pgfont['category'] : '';
					( isset( $settings['sectitle_fontgw'] ) ) ? $ptfontw = $settings['sectitle_fontgw'] : ''; 
					if (strpos($ptfontw,'italic') !== false) {
						$pt_fontst = 'italic';
					} else {
						$pt_fontst = 'normal';
					}
					if( strpos($ptfontw,'italic') > 0 ) { 
						$len = strpos($ptfontw,'italic');
						$ptfontw = substr( $ptfontw, 0, $len );
					}
					if( strpos($ptfontw,'regular') !== false ) { 
						$ptfontw = 'normal';
					}
					if( isset($settings['sectitle_fontgw']) && !empty($settings['sectitle_fontgw']) ) {
						$currfontw=$settings['sectitle_fontgw'];
						$gfonturl = $pgfont['urls'][$currfontw];
			
					}  else {
						$gfonturl = 'http://fonts.googleapis.com/css?family='.$settings['sectitle_fontg'];
					}
					if( isset($settings['sectitle_fontgsubset']) && !empty($settings['sectitle_fontgsubset']) ) {
						$strsubset = implode(",",$settings['sectitle_fontgsubset']);
						$gfonturl = $gfonturl.'&subset='.$strsubset;
					} 
					if(!empty($sectitle_fontg)) {
						wp_enqueue_style( 'documentor_sectitle', $gfonturl,array(),DOCUMENTORLITE_VER);
						$sectitle_fontg=$pgfont['name'];
						$sect_font = $sectitle_fontg.','.$ptfamily;
						$pt_fontw = $ptfontw;	
					}
					else { //if not set google font fall back to default font
				
						$sect_font = 'helvetica, Helvetica, sans-serif';
						$pt_fontw = 'normal';
						$pt_fontst = 'normal';
					}
				} else if( $settings['sect_font'] == 'custom' ) {
					$sect_font = $settings['sectitle_custom'];
					$pt_fontw = $sectitle_fweight;
					$pt_fontst = $sectitle_fstyle;
				}
				$cssarr['sectitle']=$style_start.'clear:none;line-height:'. ($settings['sectitle_fsize'] + 5) .'px;font-family:'. $sect_font.';font-size:'.$settings['sectitle_fsize'].'px;font-weight:'.$pt_fontw.';font-style:'.$pt_fontst.';color:'.$settings['sectitle_color'].';'.$style_end;
			}
			//navigation menu
			//check for use theme default option
			if( $settings['navmenu_default'] == 0 ) {
				if ($settings['navmenu_fstyle'] == "bold" or $settings['navmenu_fstyle'] == "bold italic" ){
					$navmenu_fweight = "bold";
				} else {
					$navmenu_fweight = "normal";
				}
				if ($settings['navmenu_fstyle'] == "italic" or $settings['navmenu_fstyle'] == "bold italic"){
					$navmenu_fstyle = "italic";
				} else {
					$navmenu_fstyle = "normal";
				}
			
				if( $settings['navt_font'] == 'regular' ) {
					$navt_font = $settings['navmenu_tfont'].', helvetica, Helvetica, sans-serif';
					$pt_fontw = $navmenu_fweight;
					$pt_fontst = $navmenu_fstyle;
				} else if( $settings['navt_font'] == 'google' ) {
					$navmenu_tfontg = isset($settings['navmenu_tfontg']) ? trim($settings['navmenu_tfontg']) : '';
					$pgfont = $objfonts->get_google_font($settings['navmenu_tfontg']);
					( isset( $pgfont['category'] ) ) ? $ptfamily = $pgfont['category'] : '';
					( isset( $settings['navmenu_tfontgw'] ) ) ? $ptfontw = $settings['navmenu_tfontgw'] : ''; 
					if (strpos($ptfontw,'italic') !== false) {
						$pt_fontst = 'italic';
					} else {
						$pt_fontst = 'normal';
					}
					if( strpos($ptfontw,'italic') > 0 ) { 
						$len = strpos($ptfontw,'italic');
						$ptfontw = substr( $ptfontw, 0, $len );
					}
					if( strpos($ptfontw,'regular') !== false ) { 
						$ptfontw = 'normal';
					}
					if( isset($settings['navmenu_tfontgw']) && !empty($settings['navmenu_tfontgw']) ) {
						$currfontw=$settings['navmenu_tfontgw'];
						$gfonturl = $pgfont['urls'][$currfontw];
			
					}  else {
						$gfonturl = 'http://fonts.googleapis.com/css?family='.$settings['navmenu_tfontg'];
					}
					if( isset($settings['navmenu_tfontgsubset']) && !empty($settings['navmenu_tfontgsubset']) ) {
						$strsubset = implode(",",$settings['navmenu_tfontgsubset']);
						$gfonturl = $gfonturl.'&subset='.$strsubset;
					} 
					if(!empty($navmenu_tfontg)) {
						wp_enqueue_style( 'documentor_navmenutitle', $gfonturl,array(),DOCUMENTORLITE_VER);
						$navmenu_tfontg=$pgfont['name'];
						$navt_font = $navmenu_tfontg.','.$ptfamily;
						$pt_fontw = $ptfontw;	
					}
					else { //if not set google font fall back to default font
				
						$navt_font = 'helvetica, Helvetica, sans-serif';
						$pt_fontw = 'normal';
						$pt_fontst = 'normal';
					}
				} else if( $settings['navt_font'] == 'custom' ) {
					$navt_font = $settings['navmenu_custom'];
					$pt_fontw = $navmenu_fweight;
					$pt_fontst = $navmenu_fstyle;
				}
				$cssarr['navmenu']=$style_start.'clear:none;line-height:'. ($settings['navmenu_fsize'] + 5) .'px;font-family:'. $navt_font.';font-size:'.$settings['navmenu_fsize'].'px;font-weight:'.$pt_fontw.';font-style:'.$pt_fontst.';color:'.$settings['navmenu_color'].';'.$style_end;
			}
			//section content
			//check for use theme default option
			if( $settings['seccont_default'] == 0 ) {
				if ($settings['seccont_fstyle'] == "bold" or $settings['seccont_fstyle'] == "bold italic" ){
					$sectitle_fweight = "bold";
				} else {
					$sectitle_fweight = "normal";
				}
				if ($settings['seccont_fstyle'] == "italic" or $settings['seccont_fstyle'] == "bold italic"){
					$seccont_fstyle = "italic";
				} else {
					$seccont_fstyle = "normal";
				}
			
				if( $settings['secc_font'] == 'regular' ) {
					$secc_font = $settings['seccont_font'].', helvetica, Helvetica, sans-serif';
					$pt_fontw = $sectitle_fweight;
					$pt_fontst = $seccont_fstyle;
				} else if( $settings['secc_font'] == 'google' ) {
					$seccont_fontg = isset($settings['seccont_fontg']) ? trim($settings['seccont_fontg']) : '';
					$pgfont = $objfonts->get_google_font($settings['seccont_fontg']);
					( isset( $pgfont['category'] ) ) ? $ptfamily = $pgfont['category'] : '';
					( isset( $settings['seccont_fontgw'] ) ) ? $ptfontw = $settings['seccont_fontgw'] : ''; 
					if (strpos($ptfontw,'italic') !== false) {
						$pt_fontst = 'italic';
					} else {
						$pt_fontst = 'normal';
					}
					if( strpos($ptfontw,'italic') > 0 ) { 
						$len = strpos($ptfontw,'italic');
						$ptfontw = substr( $ptfontw, 0, $len );
					}
					if( strpos($ptfontw,'regular') !== false ) { 
						$ptfontw = 'normal';
					}
					if( isset($settings['seccont_fontgw']) && !empty($settings['seccont_fontgw']) ) {
						$currfontw=$settings['seccont_fontgw'];
						$gfonturl = $pgfont['urls'][$currfontw];
			
					}  else {
						$gfonturl = 'http://fonts.googleapis.com/css?family='.$settings['seccont_fontg'];
					}
					if( isset($settings['seccont_fontgsubset']) && !empty($settings['seccont_fontgsubset']) ) {
						$strsubset = implode(",",$settings['seccont_fontgsubset']);
						$gfonturl = $gfonturl.'&subset='.$strsubset;
					} 
					if(!empty($seccont_fontg)) {
						wp_enqueue_style( 'documentor_seccontent', $gfonturl,array(),DOCUMENTORLITE_VER);
						$seccont_fontg=$pgfont['name'];
						$secc_font = $seccont_fontg.','.$ptfamily;
						$pt_fontw = $ptfontw;	
					}
					else { //if not set google font fall back to default font
				
						$secc_font = 'helvetica, Helvetica, sans-serif';
						$pt_fontw = 'normal';
						$pt_fontst = 'normal';
					}
				} else if( $settings['secc_font'] == 'custom' ) {
					$secc_font = $settings['seccont_custom'];
					$pt_fontw = $sectitle_fweight;
					$pt_fontst = $seccont_fstyle;
				}
				$cssarr['sectioncontent']=$style_start.'clear:none;line-height:'. ($settings['seccont_fsize'] + 5) .'px;font-family:'. $secc_font.';font-size:'.$settings['seccont_fsize'].'px;font-weight:'.$pt_fontw.';font-style:'.$pt_fontst.';color:'.$settings['seccont_color'].';'.$style_end;
			}
			//guide title css
			if( $settings['guidet_default'] == 0 ) {
				if ($settings['guidet_fstyle'] == "bold" or $settings['guidet_fstyle'] == "bold italic" ){
					$guidet_fweight = "bold";
				} else {
					$guidet_fweight = "normal";
				}
				if ($settings['guidet_fstyle'] == "italic" or $settings['guidet_fstyle'] == "bold italic"){
					$guidet_fstyle = "italic";
				} else {
					$guidet_fstyle = "normal";
				}
			
				if( $settings['guidet_font'] == 'regular' ) {
					$guidetfont = $settings['guidetitle_font'].', helvetica, Helvetica, sans-serif';
					$gt_fontw = $guidet_fweight;
					$gt_fontst = $guidet_fstyle;
				} else if( $settings['guidet_font'] == 'google' ) {
					$guidet_fontg = isset($settings['guidet_fontg']) ? trim($settings['guidet_fontg']) : '';
					$pgfont = $objfonts->get_google_font($settings['guidet_fontg']);
					( isset( $pgfont['category'] ) ) ? $ptfamily = $pgfont['category'] : '';
					( isset( $settings['guidet_fontgw'] ) ) ? $ptfontw = $settings['guidet_fontgw'] : ''; 
					if (strpos($ptfontw,'italic') !== false) {
						$gt_fontst = 'italic';
					} else {
						$gt_fontst = 'normal';
					}
					if( strpos($ptfontw,'italic') > 0 ) { 
						$len = strpos($ptfontw,'italic');
						$ptfontw = substr( $ptfontw, 0, $len );
					}
					if( strpos($ptfontw,'regular') !== false ) { 
						$ptfontw = 'normal';
					}
					if( isset($settings['guidet_fontgw']) && !empty($settings['guidet_fontgw']) ) {
						$currfontw=$settings['guidet_fontgw'];
						$gfonturl = $pgfont['urls'][$currfontw];
			
					}  else {
						$gfonturl = 'http://fonts.googleapis.com/css?family='.$settings['guidet_fontg'];
					}
					if( isset($settings['guidet_fontgsubset']) && !empty($settings['guidet_fontgsubset']) ) {
						$strsubset = implode(",",$settings['guidet_fontgsubset']);
						$gfonturl = $gfonturl.'&subset='.$strsubset;
					} 
					if(!empty($guidet_fontg)) {
						wp_enqueue_style( 'documentor_guidetitle', $gfonturl,array(),DOCUMENTORLITE_VER);
						$guidet_fontg=$pgfont['name'];
						$guidetfont = $guidet_fontg.','.$ptfamily;
						$gt_fontw = $ptfontw;	
					}
					else { //if not set google font fall back to default font
				
						$guidetfont = 'helvetica, Helvetica, sans-serif';
						$gt_fontw = 'normal';
						$gt_fontst = 'normal';
					}
				} else if( $settings['guidet_font'] == 'custom' ) {
					$guidetfont = $settings['ptfont_custom'];
					$gt_fontw = $guidet_fweight;
					$gt_fontst = $guidet_fstyle;
				}
				$lineheight = $settings['guidet_fsize'] + 5;
				$cssarr['guidetitle']=$style_start.'clear:none;line-height:'. $lineheight .'px;font-family:'. $guidetfont.';font-size:'.$settings['guidet_fsize'].'px;font-weight:'.$gt_fontw.';font-style:'.$gt_fontst.';color:'.$settings['guidet_color'].';'.$style_end;
			}
			return $cssarr;
		}
		//get all sections html at admin panel
		function get_sections_html() {
			global $table_prefix, $wpdb;
			$html='<input type="hidden" value="'.esc_attr($this->docid).'" name="docsid" />';
			$doc = new DocumentorLite();
			$settings = $doc->default_documentor_settings;
			$guides = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM ".$table_prefix.DOCUMENTORLITE_SECTIONS." WHERE doc_id = %d",$this->docid ) );
			$html = '';
			if( $guides ) {
			 $i = 1;
			 $obj = $this->sections_order;
				if( !empty($obj) ) {
					$jsonObj = json_decode($obj);
					$html.='<ol class="dd-list">';
					foreach( $jsonObj as $jobj ) {
						$html.= $this->buildItem($jobj);
					}
					$html.='</ol><textarea name="reorders-output" id="reorders-output">'.$this->sections_order.'</textarea>';
				} 
			}
			echo $html;
			die();
		}
		//get children sections
		function get_childrens( $element, $html, $docid ) {
			$guide = new DocumentorLiteGuide( $docid );
			foreach( $element as $valueKey => $value ) {
				foreach ( $value as $k => $v ) {
					if( $k == 'id' ) {
						 $html .= $v.",";
					} else if( $k == 'children' ) {
						$html = $guide->get_childrens( $v, $html, $docid );
					}
				}
			}
			return $html;
		}
		//save all sections of guide
		public static function save_sections() {
			check_ajax_referer( 'documentor-sections-nonce', 'sections_nonce' );
			global $table_prefix, $wpdb;
			$sorders = ( isset( $_POST['reorders-output'] ) ) ? sanitize_text_field($_POST['reorders-output']) : '';
			$docid = ( isset( $_POST['docid'] ) ) ? intval($_POST['docid']) : '';
			$docpostid= ( isset( $_POST['doc_postid'] ) ) ? intval($_POST['doc_postid']) : '0';
			$sectionsarr = ( isset( $_POST['sectionObj'] ) ) ? $_POST['sectionObj'] : '';
			$doc_title = ( isset( $_POST['guidename'] ) ) ? sanitize_text_field($_POST['guidename']) : '';
			if( empty( $doc_title ) ) {
				_e("Warning: Guide name could not be blank","documentorlite");
			} else if( !empty( $docid ) ) { 
				//update sections order in documentor table
				$jarr = json_decode( stripslashes($sorders), true );
				if( count($jarr) > 0 ) {
					$sections_order = stripslashes_deep( $sorders );
				} else {
					$sections_order = '';
				}
				update_post_meta($docpostid,'_doc_sections_order',$sections_order);
				//delete sections from sections table which are not in section order of documentor table			
				$sorders=$sections_order;
				$jarr = json_decode( $sorders, true );	
				if( count($jarr) > 0 ) {
					$idstr = '';
					$guide = new DocumentorLiteGuide( $docid );
					foreach($jarr as $elementKey => $element) {
					    foreach($element as $valueKey => $value) {
						if( $valueKey == 'id' ){
							$idstr .= $value.",";
						} else if( $valueKey == 'children' ) {
							$idstr = $guide->get_childrens( $value, $idstr, $docid );
						}
					    }
					}
					$idstr = rtrim( $idstr , ',' );
					//delete post of custom post type sections(type= 0) if not in order
					$query = "SELECT * FROM ".$table_prefix.DOCUMENTORLITE_SECTIONS." WHERE sec_id NOT IN(".$idstr.") AND type = 0 ";
					$results = $wpdb->get_results( $query );
					if( count( $results ) > 0 ) {
						foreach( $results as $result ) {
							wp_delete_post( $result->post_id, true );
						}
					}
					$delsql = "DELETE FROM ".$table_prefix.DOCUMENTORLITE_SECTIONS." WHERE sec_id NOT IN(".$idstr.") AND doc_id = ".$docid;
					$wpdb->query($delsql);
				} else {
					$wpdb->delete( $table_prefix.DOCUMENTORLITE_SECTIONS, array( 'doc_id' => $docid ), array( '%d' ) );
				}						
				//save all sections of guide
				foreach( $sectionsarr as $sectionarr ) {
					$postid = intval($sectionarr['postid' ]);
					$sectype = intval($sectionarr['type']);
					if( empty( $sectionarr['slug'] ) ) {
						_e("Warning: slug could not be blank.","documentorlite"); die();
					}
					if( $sectype != 3 ) { //if not link section
						if( empty( $sectionarr['menutitle'] ) ) {
							_e("Warning: menu title could not be blank.","documentorlite"); die();
						} else if( empty( $sectionarr['sectiontitle'] ) && $sectionarr['type'] == 0 ) {
							_e("Warning: section title could not be blank.","documentorlite"); die();
						} else {
							//update post meta
							update_post_meta( $postid, '_documentor_sectiontitle', $sectionarr['sectiontitle'] );
							update_post_meta( $postid, '_documentor_menutitle', $sectionarr['menutitle'] );
						}
					} else if( $sectype == 3 ) { //link section
						if( empty( $sectionarr['linkurl'] ) ) {
							_e("Warning: link url could not be blank.","documentorlite"); die();
						} else {
							$arr = array(
								'link' => $sectionarr['linkurl'],
								'new_window' => intval($sectionarr['new_window'])
							);
							$content = serialize( $arr ); 
						
							//update nav_menu item post
							$post = array(
								      'ID'           => $postid,
								      'post_title'   => $sectionarr['menutitle'],
								      'post_content' => $content
								);
							wp_update_post( $post );
						}
					}
					//update slug in sections table
					$wpdb->update( 
						$table_prefix.DOCUMENTORLITE_SECTIONS, 
						array( 
							'slug' => sanitize_title( $sectionarr['slug'] )
						), 
						array( 'sec_id' => $sectionarr['section_id'] ), 
						array( 
							'%s'
						), 
						array( '%d' ) 
					);
				}
				//update guide title
				$update_post= array( 
					 	'ID' => $docpostid,
						'post_title' => $doc_title	
						);
				wp_update_post( $update_post );				
			}
			die();
		}
		//Admin View of Guide
		function admin_view() {
				$documentor_curr = $this->get_settings();
				$guide = $this->get_guide( $this->docid );
				$tabindex = (isset( $_GET['tab'] )) ? $_GET['tab'] : '';
				$class0 = $class1 = $class2 = "";
				if( !empty( $tabindex ) ) {
					if( $tabindex == 'sections' ) {
						$class0 = 'nav_tab_active';
					} else if( $tabindex == 'settings' ) {
						$class1 = 'nav_tab_active';
					} else if( $tabindex == 'embedcode' ) {
						$class2 = 'nav_tab_active';
					} 
				} else {
					$class0 = 'nav_tab_active';
				}		
				
				if( $tabindex != 'add-sections' ) { ?>
					<div id="documentor_tabs" class="documentor_editguide"> 
						<div class="edit-guidetitle"><span class="dashicons dashicons-welcome-write-blog editguide-icon"></span> <?php _e('Edit Guide','documentor-lite'); ?> </div>
						
						<input type="text" id="documentor-name" class="docname" value="<?php echo esc_attr($guide->doc_title);?>" />
					</div>
					<div class="doc-success-msg"></div>
					  <h2 class="nav-tab-wrapper"> 
						  <a id="options-group-1-tab" class="nav-tab sections-tab <?php if( isset( $class0 ) ) echo $class0; ?>" title="Sections" href="<?php echo esc_url(admin_url('admin.php?page=documentor-admin&action=edit&id='.$this->docid.'&tab=sections')); ?>"><?php _e('Sections','documentor-lite'); ?></a> 
						  <a id="options-group-2-tab" class="nav-tab settings-tab <?php if( isset( $class1 ) ) echo $class1; ?>" title="Settings" href="<?php echo esc_url(admin_url('admin.php?page=documentor-admin&action=edit&id='.$this->docid.'&tab=settings')); ?>"><?php _e('Settings','documentor-lite'); ?></a>
						  <a id="options-group-3-tab" class="nav-tab embedcode-tab <?php if( isset( $class2 ) ) echo $class2; ?>" title="Embed code" href="<?php echo esc_url(admin_url('admin.php?page=documentor-admin&action=edit&id='.$this->docid.'&tab=embedcode')); ?>"><?php _e('Embed Code','documentor-lite'); ?></a>
						   <a id="options-group-4-tab" class="nav-tab pro-tab" title="<?php _e('Documentor Pro','documentor-lite'); ?>" href="https://documentor.in/" target="_blank"><?php _e('Documentor Pro','documentor-lite'); ?></a>
					</h2>
				<?php } 
				if( ( isset( $tabindex ) && $tabindex == 'sections' ) || empty( $tabindex )) { ?>
					<div id="options-group-1" class="group sections">
						<div id="addsections" class="documentor-newdoc">
							<a href="<?php echo esc_url(admin_url('admin.php?page=documentor-admin&action=edit&id='.$this->docid.'&tab=add-sections')); ?>" title="Add Section" class="create-btn"><?php _e('Add Section','documentor-lite'); ?></a>
							<input type="hidden" value="<?php echo esc_attr($this->docid); ?>" name="docsid" />
							<input type="hidden" name="documentor-loader" value="<?php echo esc_url(admin_url('images/loading.gif'));?>" />
							<form name="guide_secform" class="guide-secform" method="post">
								<input type="hidden" value="<?php echo esc_attr($this->docid); ?>" name="docid" />
								<input type="hidden" value="<?php echo esc_attr($this->get_guide_post_id($this->docid)); ?>" name="doc_postid" id="doc_postid" />
								<div id="reorders" class="reorders" >
											
						
								</div>
								<p>
								<?php $guide = $this->get_guide( $this->docid ); ?>
								<input type="hidden" name="guidename" class="guidename" value="<?php echo esc_attr($guide->doc_title);?>">
								<input type="hidden" name="documentor-sections-nonce" value="<?php echo wp_create_nonce( 'documentor-sections-nonce' ); ?>">

								<input type="submit" name="save_sections" class="save-sections button-primary" value="<?php echo esc_attr_e('Save','documentor-lite');?>" style="display: none;" />
								</p>
								
								<?php 
								$documentor_global_options = 'documentor_global_options';
								$documentor_global_curr = get_option('documentor_global_options');
								 ?>
								<input type="hidden" name="<?php echo $documentor_global_options;?>[reviewme]" class="hidden_check" id="hidden_reviewme" value="<?php echo esc_attr($documentor_global_curr['reviewme']);?>">
								<?php 
						$now=strtotime("now");
						$reviewme=$documentor_global_curr['reviewme'];
						       if($reviewme!=0 and $reviewme<$now) {
						echo "<div id='reviewme' style='border:1px solid #ccc;padding:10px;background:#fff;margin-top:2%;float: left;width: 95%;'>
						<p>".__('Hey, I noticed you have created an awesome document using Documentor Lite and using it for more than a week. Could you please do me a BIG favor and give it a 5-star rating on WordPress? Just to help us spread the word and boost our motivation.', 'documentor-lite')."</p>
						<p>".__("~ Tejaswini from Documentor","documentorlite")."</p>
						<ul><li><a href='https://wordpress.org/support/view/plugin-reviews/documentor-lite?filter=5' target='_blank' title='".__('Documentor Lite', 'documentor-lite')."'>".__('Please review and rate Documentor Lite on WordPress.org', 'documentor-lite')."</a></li>
						<li><a id='later' href='#' title='".__('Rate Documentor Lite at some other time!', 'documentor-lite')."'>".__('Rate Documentor Lite at some other time!', 'documentor-lite')."</a></li>
						<li><a id='already' href='#' title='".__('Click this if you have already rated us 5-star!', 'documentor-lite')."'>".__('Click this if you have already rated us 5-star!','documentor-lite'). "</a></li></ul></div>";
						 }
					?>
					</form>
					</div>
					</div> <!--tab group-1 ends -->
				<?php } else if( isset( $tabindex ) && $tabindex == 'settings' ) { ?>
				<div id="options-group-2" class="group settings">
				<form method="post" name="documentor-settings" class="documentor-settings">
				<input type="hidden" value="<?php echo esc_attr($this->docid); ?>" name="docsid" />
				<input type="hidden" name="documentor-loader" value="<?php echo esc_url(admin_url('images/loading.gif'));?>" />
				<div id="basic" class="doc-settingsdiv">
				<div class="sub_settings toggle_settings">
				<?php $documentor = new DocumentorLite(); ?>
				<h2 class="sub-heading"><?php _e('Basic Settings','documentor-lite'); ?><span class="toggle_img"></span></h2> 				
				<?php
				$documentor_options = 'documentor_options'; ?>
				<table class="form-table">
				<tr valign="top">
				<th scope="row"><?php _e('Skin','documentor-lite'); ?></th>
				<td><select name="<?php echo $documentor_options;?>[skin]" id="doc-skin" onchange="">
				<?php 
				$directory = DOCUMENTORLITE_CSS_DIR;
				if ($handle = opendir($directory)) {
					while (false !== ($file = readdir($handle))) { 
					 if($file != '.' and $file != '..') {  ?>	
						<option value="<?php echo esc_attr($file);?>" <?php if ($documentor_curr['skin'] == $file){ echo "selected";}?> ><?php echo $file;?></option>
				<?php		
				} }
					closedir($handle);
				}
				?>
				</select>
				</td>
				</tr>				
				<tr valign="top">
					<th scope="row"><?php _e('Section Animation','documentor-lite'); ?></th>
					<td>
						<?php $animation = $documentor_curr['animation']; ?>
						<select name="<?php echo $documentor_options;?>[animation]">
							<option value="">Select animation</option>
							<optgroup label="<?php _e('Attention Seekers','documentor-lite'); ?>">
							  <option value="bounce" <?php selected( $animation, "bounce" ); ?> ><?php _e('bounce','documentor-lite'); ?></option>
							  <option value="flash" <?php selected( $animation, "flash" ); ?> ><?php _e('flash','documentor-lite'); ?></option>
							  <option value="pulse" <?php selected( $animation, "pulse" ); ?> ><?php _e('pulse','documentor-lite'); ?></option>
							  <option value="rubberBand" <?php selected( $animation, "rubberBand" ); ?> ><?php _e('rubberBand','documentor-lite'); ?></option>
							  <option value="shake" <?php selected( $animation, "shake" ); ?> ><?php _e('shake','documentor-lite'); ?></option>
							  <option value="swing" <?php selected( $animation, "swing" ); ?> ><?php _e('swing','documentor-lite'); ?></option>
							  <option value="tada" <?php selected( $animation, "tada" ); ?> ><?php _e('tada','documentor-lite'); ?></option>
							  <option value="wobble" <?php selected( $animation, "wobble" ); ?> ><?php _e('wobble','documentor-lite'); ?></option>
							</optgroup>
							<optgroup label="<?php _e('Bouncing Entrances','documentor-lite'); ?>">
							  <option value="bounceIn" <?php selected( $animation, "bounceIn" ); ?> ><?php _e('bounceIn','documentor-lite'); ?></option>
							  <option value="bounceInDown" <?php selected( $animation, "bounceInDown" ); ?> ><?php _e('bounceInDown','documentor-lite'); ?></option>
							  <option value="bounceInLeft" <?php selected( $animation, "bounceInLeft" ); ?> ><?php _e('bounceInLeft','documentor-lite'); ?></option>
							  <option value="bounceInRight" <?php selected( $animation, "bounceInRight" ); ?> ><?php _e('bounceInRight','documentor-lite'); ?></option>
							  <option value="bounceInUp" <?php selected( $animation, "bounceInUp" ); ?> ><?php _e('bounceInUp','documentor-lite'); ?></option>
							</optgroup>

						       <optgroup label="<?php _e('Fading Entrances','documentor-lite'); ?>">
							  <option value="fadeIn" <?php selected( $animation, "fadeIn" ); ?> ><?php _e('fadeIn','documentor-lite'); ?></option>
							  <option value="fadeInDown" <?php selected( $animation, "fadeInDown" ); ?> ><?php _e('fadeInDown','documentor-lite'); ?></option>
							  <option value="fadeInDownBig"<?php selected( $animation, "fadeInDownBig" ); ?> ><?php _e('fadeInDownBig','documentor-lite'); ?></option>
							  <option value="fadeInLeft" <?php selected( $animation, "fadeInLeft" ); ?> ><?php _e('fadeInLeft','documentor-lite'); ?></option>
							  <option value="fadeInLeftBig" <?php selected( $animation, "fadeInLeftBig" ); ?> ><?php _e('fadeInLeftBig','documentor-lite'); ?></option>
							  <option value="fadeInRight" <?php selected( $animation, "fadeInRight" ); ?> ><?php _e('fadeInRight','documentor-lite'); ?></option>
							  <option value="fadeInRightBig" <?php selected( $animation, "fadeInRightBig" ); ?> ><?php _e('fadeInRightBig','documentor-lite'); ?></option>
							  <option value="fadeInUp" <?php selected( $animation, "fadeInUp" ); ?> ><?php _e('fadeInUp','documentor-lite'); ?></option>
							  <option value="fadeInUpBig" <?php selected( $animation, "fadeInUpBig" ); ?> ><?php _e('fadeInUpBig','documentor-lite'); ?></option>
							</optgroup>

						       <optgroup label="<?php _e('Flippers','documentor-lite'); ?>">
							  <option value="flip" <?php selected( $animation, "flip" ); ?> ><?php _e('flip','documentor-lite'); ?></option>
							  <option value="flipInX" <?php selected( $animation, "flipInX" ); ?> ><?php _e('flipInX','documentor-lite'); ?></option>
							  <option value="flipInY" <?php selected( $animation, "flipInY" ); ?> ><?php _e('flipInY','documentor-lite'); ?></option>
						       </optgroup>

							<optgroup label="<?php _e('Lightspeed','documentor-lite'); ?>">
							  <option value="lightSpeedIn" <?php selected( $animation, "lightSpeedIn" ); ?> ><?php _e('lightSpeedIn','documentor-lite'); ?></option>
							</optgroup>

							<optgroup label="<?php _e('Rotating Entrances','documentor-lite'); ?>">
							  <option value="rotateIn" <?php selected( $animation, "rotateIn" ); ?> ><?php _e('rotateIn','documentor-lite'); ?></option>
							  <option value="rotateInDownLeft" <?php selected( $animation, "rotateInDownLeft" ); ?> ><?php _e('rotateInDownLeft','documentor-lite'); ?></option>
							  <option value="rotateInDownRight" <?php selected( $animation, "rotateInDownRight" ); ?> ><?php _e('rotateInDownRight','documentor-lite'); ?></option>
							  <option value="rotateInUpLeft" <?php selected( $animation, "rotateInUpLeft" ); ?> ><?php _e('rotateInUpLeft','documentor-lite'); ?></option>
							  <option value="rotateInUpRight" <?php selected( $animation, "rotateInUpRight" ); ?> ><?php _e('rotateInUpRight','documentor-lite'); ?></option>
							</optgroup>

							<optgroup label="<?php _e('Specials','documentor-lite'); ?>">
							  <option value="hinge" <?php selected( $animation, "hinge" ); ?> ><?php _e('hinge','documentor-lite'); ?></option>
							  <option value="rollIn" <?php selected( $animation, "rollIn" ); ?> ><?php _e('rollIn','documentor-lite'); ?></option>
							</optgroup>

							<optgroup label="<?php _e('Zoom Entrances','documentor-lite'); ?>">
							  <option value="zoomIn" <?php selected( $animation, "zoomIn" ); ?> ><?php _e('zoomIn','documentor-lite'); ?></option>
							  <option value="zoomInDown" <?php selected( $animation, "zoomInDown" ); ?> ><?php _e('zoomInDown','documentor-lite'); ?></option>
							  <option value="zoomInLeft" <?php selected( $animation, "zoomInLeft" ); ?> ><?php _e('zoomInLeft','documentor-lite'); ?></option>
							  <option value="zoomInRight" <?php selected( $animation, "zoomInRight" ); ?> ><?php _e('zoomInRight','documentor-lite'); ?></option>
							  <option value="zoomInUp" <?php selected( $animation, "zoomInUp" ); ?> ><?php _e('zoomInUp','documentor-lite'); ?></option>
							</optgroup>

							 <optgroup label="<?php _e('Slide Entrances','documentor-lite'); ?>">
							  <option value="slideInDown" <?php selected( $animation, "slideInDown" ); ?> ><?php _e('slideInDown','documentor-lite'); ?></option>
							  <option value="slideInLeft" <?php selected( $animation, "slideInLeft" ); ?> ><?php _e('slideInLef','documentor-lite'); ?></option>
							  <option value="slideInRight" <?php selected( $animation, "slideInRight" ); ?> ><?php _e('slideInRight','documentor-lite'); ?></option>
							  <option value="slideInUp" <?php selected( $animation, "slideInUp" ); ?> ><?php _e('slideInUp','documentor-lite'); ?></option>
							 </optgroup>
						</select>
					</td>
				</tr>				
				<tr valign="top">
					<th scope="row"><?php _e('Indexing Format','documentor-lite'); ?></th>
					<td>
						<div class="eb-switch eb-switchnone havemoreinfo indexswitch"">
							<input type="hidden" name="<?php echo $documentor_options;?>[indexformat]" id="documentor_indexformat" class="hidden_check" value="<?php echo esc_attr($documentor_curr['indexformat']);?>">
							<input id="indexformat" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['indexformat']); ?>>
							<label for="indexformat"></label>
						</div>
						<?php						 
						 $ind_display= $documentor_curr['indexformat']==1?"display:inline":"display:none";
						 ?>
						<a href="#format-index" id="index_format" rel="leanModal" style="<?php echo $ind_display; ?>" title="Guide Title Formatting " ><?php _e('Format','documentor');?></a>
					<script type="text/javascript">
	  	 			jQuery( document ).ready( function() {
		  	 			jQuery('.indexswitch').on("change",function(){ 
				      		var val_checkbox = jQuery("#indexformat").attr("checked");			      		
				      		if(val_checkbox=='checked'){
				      			console.log(val_checkbox);
				      			jQuery('#index_format').show();
				      		}else {
				      		  console.log("no checked");
				      		  jQuery('#index_format').hide();
				      		}
	  	 			  });
	  	 			 });
	  	 			</script>				
					</td>
				</tr>				
				<tr valign="top">
					<th scope="row"><?php _e('Guide Title','documentor-lite'); ?></th>
					<td>
					<div class="eb-switch eb-switchnone">
						<input type="hidden" name="<?php echo $documentor_options;?>[guidetitle]" id="documentor_guidetitle" class="hidden_check" value="<?php echo esc_attr($documentor_curr['guidetitle']);?>">
						<input id="guidetitle" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['guidetitle']); ?>>
						<label for="guidetitle"></label>
					</div>
					<a href="#options-guidetitle" rel="leanModal" title="Guide Title Formatting" ><?php _e('Options','documentor-lite');?></a>
					</td>
				</tr>				
				<tr valign="top">
				<th scope="row"><?php _e('Scrolling','documentor-lite'); ?></th>
				<td>
				<?php $documentor_curr['scrolling'] = ( !isset( $documentor_curr['scrolling'] )  ) ? 1 : $documentor_curr['scrolling']; ?>
				<div class="eb-switch eb-switchnone havemoreinfo">
					<input type="hidden" name="<?php echo $documentor_options;?>[scrolling]" id="doc-enable-scroll" class="hidden_check" value="<?php echo esc_attr($documentor_curr['scrolling']);?>">
					<input id="enable-scroll" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['scrolling']); ?>>
					<label for="enable-scroll"></label>
				</div>
				</td>
				</tr>				
				<tr valign="top">
				<th scope="row"><?php _e('Fixed Menu','documentor-lite'); ?></th>
				<td>
				<?php $documentor_curr['fixmenu'] = ( !isset( $documentor_curr['fixmenu'] )  ) ? 1 : $documentor_curr['fixmenu']; ?>
				<div class="eb-switch eb-switchnone havemoreinfo">
					<input type="hidden" name="<?php echo $documentor_options;?>[fixmenu]" id="doc-enable-fixmenu" class="hidden_check" value="<?php echo esc_attr($documentor_curr['fixmenu']);?>">
					<input id="enable-fixmenu" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['fixmenu']); ?>>
					<label for="enable-fixmenu"></label>
				</div>
				</td>
				</tr>				
				<tr valign="top" class="menuTop" style="<?php echo ( !isset( $documentor_curr['fixmenu'] )  or $documentor_curr['fixmenu']=='0' ) ? 'display:none;' : ''; ?>">
				<th scope="row"><?php _e('Top Margin for Menu','documentor'); ?></th>
				<td>
					<input type="number" name="<?php echo $documentor_options;?>[menuTop]" id="menuTop" class="small-text" value="<?php echo esc_attr($documentor_curr['menuTop']); ?>" min="0" />&nbsp;<?php _e('px','documentor'); ?>
				</td>
				</tr>				
				<tr valign="top">
					<?php
						//new field added in v1.1
						$documentor_curr['menu_position'] = isset($documentor_curr['menu_position']) ? $documentor_curr['menu_position'] : 'left'; 
					?>
					<th scope="row"><?php _e('Menu Position','documentor-lite'); ?></th>
					<td>
						<select name="<?php echo $documentor_options;?>[menu_position]" >
							<option value="left" <?php if ($documentor_curr['menu_position'] == "left"){ echo "selected";}?> >Left</option>
							<option value="right" <?php if ($documentor_curr['menu_position'] == "right"){ echo "selected";}?> >Right</option>
						</select>
					</td>
				</tr>				
				<tr valign="top">
					<th scope="row"><?php _e('Toggle child menu','documentor-lite'); ?></th>
					<td>
					<div class="eb-switch eb-switchnone havemoreinfo">
						<input type="hidden" name="<?php echo $documentor_options;?>[togglemenu]" id="doc-enable-togglemenu" class="hidden_check" value="<?php echo esc_attr($documentor_curr['togglemenu']);?>">
						<input id="enable-togglemenu" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['togglemenu']); ?>>
						<label for="enable-togglemenu"></label>
					</div>
					</td>
				</tr>
			
				</table>
				<p class="submit">
				<input type="submit" name="save-settings" class="button-primary" value="<?php _e('Save Changes','documentor-lite'); ?>" />
				</p>
				</div>
				</div> <!--Basic ends-->
				<div id="formating" class="doc-settingsdiv" >
				<div class="sub_settings toggle_settings">
				<h2 class="sub-heading"><?php _e('Formatting','documentor-lite'); ?><span class="toggle_img"></span></h2>
				<span scope="row" class="doc-settingtitle"><?php _e('Nav Menu Title','documentor-lite'); ?></span>
				<table class="form-table settings-tbl"  >
				<tr valign="top" >
				<th scope="row" ><?php _e('Use theme default','documentor-lite'); ?></th>
				<td>
				<div class="eb-switch eb-switchnone havemoreinfo">
					<input type="hidden" name="<?php echo $documentor_options;?>[navmenu_default]" id="navmenu-default" class="hidden_check" value="<?php echo esc_attr($documentor_curr['navmenu_default']);?>">
					<input id="navmenu-def" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['navmenu_default']); ?>>
					<label for="navmenu-def"></label>
				</div>
				</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Color','documentor-lite'); ?></th>
					<td><input type="color" name="<?php echo $documentor_options;?>[navmenu_color]" id="navmenu_color" value="<?php echo esc_attr($documentor_curr['navmenu_color']); ?>" class="wp-color-picker-field" data-default-color="#D8E7EE" /></td>
			    </tr>			    
				<tr valign="top">
				<th scope="row"><?php _e('Font','documentor-lite'); ?></th>
				<td>
				<input type="hidden" value="navmenu_tfont" class="ftype_rname">
				<input type="hidden" value="navmenu_tfontg" class="ftype_gname">
				<input type="hidden" value="navmenu_custom" class="ftype_cname">				
				<select name="<?php echo $documentor_options;?>[navt_font]" id="navt_font" class="main-font">
	
					<option value="regular" <?php selected( $documentor_curr['navt_font'], "regular" ); ?> > Regular Fonts </option>
					<option value="google" <?php selected( $documentor_curr['navt_font'], "google" ); ?> > Google Fonts </option>
					<option value="custom" <?php selected( $documentor_curr['navt_font'], "custom" ); ?> > Custom Fonts </option>
				</select>
				</td>
				</tr>
				<tr><td class="load-fontdiv" colspan="2"></td></tr>
				<tr valign="top">
				<th scope="row"><?php _e('Font Size','documentor-lite'); ?></th>
				<td><input type="number" name="<?php echo $documentor_options;?>[navmenu_fsize]" id="navmenu_fsize" class="small-text" value="<?php echo esc_attr($documentor_curr['navmenu_fsize']); ?>" min="1" />&nbsp;<?php _e('px','documentor-lite'); ?></td>
				</tr>
				<tr valign="top" class="font-style">
				<th scope="row"><?php _e('Font Style','documentor-lite'); ?></th>
				<td><select name="<?php echo $documentor_options;?>[navmenu_fstyle]" id="navmenu_fstyle" class="font-style" >
				<option value="bold" <?php if ($documentor_curr['navmenu_fstyle'] == "bold"){ echo "selected";}?> ><?php _e('Bold','documentor-lite'); ?></option>
				<option value="bold italic" <?php if ($documentor_curr['navmenu_fstyle'] == "bold italic"){ echo "selected";}?> ><?php _e('Bold Italic','documentor-lite'); ?></option>
				<option value="italic" <?php if ($documentor_curr['navmenu_fstyle'] == "italic"){ echo "selected";}?> ><?php _e('Italic','documentor-lite'); ?></option>
				<option value="normal" <?php if ($documentor_curr['navmenu_fstyle'] == "normal"){ echo "selected";}?> ><?php _e('Normal','documentor-lite'); ?></option>
				</select>
				</td>
				</tr>
				</table>
				<p class="submit">
					<input type="submit" name="save-settings" class="button-primary" value="<?php _e('Save Changes','documentor-lite'); ?>" />
				</p>
				<span scope="row" class="doc-settingtitle" ><?php _e('Active Nav Menu Background','documentor-lite'); ?></span>
				<table class="form-table settings-tbl"  >
				<tr valign="top">
				<th scope="row"><?php _e('Use theme default','documentor-lite'); ?></th>
				<td>
				<div class="eb-switch eb-switchnone havemoreinfo">
					<input type="hidden" name="<?php echo $documentor_options;?>[actnavbg_default]" id="actnav-background" class="hidden_check" value="<?php echo esc_attr($documentor_curr['actnavbg_default']);?>">
					<input id="actnav-bg" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['actnavbg_default']); ?>>
					<label for="actnav-bg"></label>
				</div>
				</td>
				</tr>
				<tr valign="top">
				<th scope="row"><?php _e('Color','documentor-lite'); ?></th>
				<td><input type="color" name="<?php echo $documentor_options;?>[actnavbg_color]" id="actnavbg-color" value="<?php echo esc_attr($documentor_curr['actnavbg_color']); ?>" class="wp-color-picker-field" data-default-color="#D8E7EE" /></td>
				</tr>				
				</table>
				<p class="submit">
					<input type="submit" name="save-settings" class="button-primary" value="<?php _e('Save Changes','documentor-lite'); ?>" />
				</p>
				<span scope="row" class="doc-settingtitle"><?php _e('Section Title','documentor-lite'); ?></span>
				<table class="form-table settings-tbl"  >
				<tr valign="top">
				<th scope="row"><?php _e('Element','documentor-lite'); ?>
				</th>
				<td><select name="<?php echo $documentor_options;?>[section_element]" >
				<option value="1" <?php if ($documentor_curr['section_element'] == "1"){ echo "selected";}?> >h1</option>
				<option value="2" <?php if ($documentor_curr['section_element'] == "2"){ echo "selected";}?> >h2</option>
				<option value="3" <?php if ($documentor_curr['section_element'] == "3"){ echo "selected";}?> >h3</option>
				<option value="4" <?php if ($documentor_curr['section_element'] == "4"){ echo "selected";}?> >h4</option>
				<option value="5" <?php if ($documentor_curr['section_element'] == "5"){ echo "selected";}?> >h5</option>
				<option value="6" <?php if ($documentor_curr['section_element'] == "6"){ echo "selected";}?> >h6</option>
				</select>
				</td>
				</tr>
				<tr valign="top">
				<th scope="row"><?php _e('Use theme default','documentor-lite'); ?></th>
				<td>
				<div class="eb-switch eb-switchnone havemoreinfo">
					<input type="hidden" name="<?php echo $documentor_options;?>[sectitle_default]" id="sectitle-default" class="hidden_check" value="<?php echo esc_attr($documentor_curr['sectitle_default']);?>">
					<input id="sectitle-def" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['sectitle_default']); ?>>
					<label for="sectitle-def"></label>
				</div>
				</td>
				</tr>
				<tr valign="top">
				<th scope="row"><?php _e('Color','documentor-lite'); ?></th>
				<td><input type="color" name="<?php echo $documentor_options;?>[sectitle_color]" id="sectitle-color" value="<?php echo esc_attr($documentor_curr['sectitle_color']); ?>" class="wp-color-picker-field" data-default-color="#D8E7EE" /></td>
				</tr>
				<tr valign="top">
				<th scope="row"><?php _e('Font','documentor-lite'); ?></th>
				<td>
				<input type="hidden" value="sectitle_font" class="ftype_rname">
				<input type="hidden" value="sectitle_fontg" class="ftype_gname">
				<input type="hidden" value="sectitle_custom" class="ftype_cname">
				<select name="<?php echo $documentor_options;?>[sect_font]" id="sect_font" class="main-font">
					<option value="regular" <?php selected( $documentor_curr['sect_font'], "regular" ); ?> > Regular Fonts </option>
					<option value="google" <?php selected( $documentor_curr['sect_font'], "google" ); ?> > Google Fonts </option>
					<option value="custom" <?php selected( $documentor_curr['sect_font'], "custom" ); ?> > Custom Fonts </option>
				</select>
				</td>
				</tr>
				<tr><td class="load-fontdiv" colspan="2"></td></tr>
				<tr valign="top">
				<th scope="row"><?php _e('Font Size','documentor-lite'); ?></th>
				<td><input type="number" name="<?php echo $documentor_options;?>[sectitle_fsize]" id="sectitle_fsize" class="small-text" value="<?php echo esc_attr($documentor_curr['sectitle_fsize']); ?>" min="1" />&nbsp;<?php _e('px','documentor-lite'); ?></td>
				</tr>
				<tr valign="top" class="font-style">
				<th scope="row"><?php _e('Font Style','documentor-lite'); ?></th>
				<td><select name="<?php echo $documentor_options;?>[sectitle_fstyle]" id="sectitle_fstyle" class="font-style" >
				<option value="bold" <?php if ($documentor_curr['sectitle_fstyle'] == "bold"){ echo "selected";}?> ><?php _e('Bold','documentor-lite'); ?></option>
				<option value="bold italic" <?php if ($documentor_curr['sectitle_fstyle'] == "bold italic"){ echo "selected";}?> ><?php _e('Bold Italic','documentor-lite'); ?></option>
				<option value="italic" <?php if ($documentor_curr['sectitle_fstyle'] == "italic"){ echo "selected";}?> ><?php _e('Italic','documentor-lite'); ?></option>
				<option value="normal" <?php if ($documentor_curr['sectitle_fstyle'] == "normal"){ echo "selected";}?> ><?php _e('Normal','documentor-lite'); ?></option>
				</select>
				</td>
				</tr>				
				</table>				
				<p class="submit">
					<input type="submit" name="save-settings" class="button-primary" value="<?php _e('Save Changes','documentor-lite'); ?>" />
				</p>
				<span scope="row" class="doc-settingtitle"><?php _e('Section Content','documentor-lite'); ?></span>
				<table class="form-table settings-tbl"  >
				<tr valign="top">
				<th scope="row"><?php _e('Use theme default','documentor-lite'); ?></th>
				<td>
				<div class="eb-switch eb-switchnone havemoreinfo">
					<input type="hidden" name="<?php echo $documentor_options;?>[seccont_default]" id="seccont-default" class="hidden_check" value="<?php echo esc_attr($documentor_curr['seccont_default']);?>">
					<input id="seccont-def" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['seccont_default']); ?>>
					<label for="seccont-def"></label>
				</div>
				</td>
				</tr>
				<tr valign="top">
				<th scope="row"><?php _e('Color','documentor-lite'); ?></th>
				<td><input type="color" name="<?php echo $documentor_options;?>[seccont_color]" id="seccont_color" value="<?php echo esc_attr($documentor_curr['seccont_color']); ?>" class="wp-color-picker-field" data-default-color="#D8E7EE" /></td>
				</tr>
				<tr valign="top">
				<th scope="row"><?php _e('Font','documentor-lite'); ?></th>
				<td>
				<input type="hidden" value="seccont_font" class="ftype_rname">
				<input type="hidden" value="seccont_fontg" class="ftype_gname">
				<input type="hidden" value="seccont_custom" class="ftype_cname">
				<select name="<?php echo $documentor_options;?>[secc_font]" id="secc_font" class="main-font">
					<option value="regular" <?php selected( $documentor_curr['secc_font'], "regular" ); ?> > Regular Fonts </option>
					<option value="google" <?php selected( $documentor_curr['secc_font'], "google" ); ?> > Google Fonts </option>
					<option value="custom" <?php selected( $documentor_curr['secc_font'], "custom" ); ?> > Custom Fonts </option>
				</select>
				</td>
				</tr>
				<tr><td class="load-fontdiv" colspan="2"></td></tr>
				<tr valign="top">
				<th scope="row"><?php _e('Font Size','documentor-lite'); ?></th>
				<td><input type="number" name="<?php echo $documentor_options;?>[seccont_fsize]" id="seccont-fsize" class="small-text" value="<?php echo esc_attr($documentor_curr['seccont_fsize']); ?>" min="1" />&nbsp;<?php _e('px','documentor-lite'); ?></td>
				</tr>
				<tr valign="top" class="font-style">
				<th scope="row"><?php _e('Font Style','documentor-lite'); ?></th>
				<td><select name="<?php echo $documentor_options;?>[seccont_fstyle]" id="seccont-fstyle" class="font-style" >
				<option value="bold" <?php if ($documentor_curr['seccont_fstyle'] == "bold"){ echo "selected";}?> ><?php _e('Bold','documentor-lite'); ?></option>
				<option value="bold italic" <?php if ($documentor_curr['seccont_fstyle'] == "bold italic"){ echo "selected";}?> ><?php _e('Bold Italic','documentor-lite'); ?></option>
				<option value="italic" <?php if ($documentor_curr['seccont_fstyle'] == "italic"){ echo "selected";}?> ><?php _e('Italic','documentor-lite'); ?></option>
				<option value="normal" <?php if ($documentor_curr['seccont_fstyle'] == "normal"){ echo "selected";}?> ><?php _e('Normal','documentor-lite'); ?></option>
				</select>
				</td>
				</tr>				
				<tr valign="top">
					<th scope="row"><?php _e('Last Updated Date','documentor-lite'); ?></th>
					<td>
						<?php 
						//new field added in v1.1
						$documentor_curr['updated_date'] = isset( $documentor_curr['updated_date'] ) ? $documentor_curr['updated_date'] : 0;
						?>
						<div class="eb-switch eb-switchnone">
							<input type="hidden" name="<?php echo $documentor_options;?>[updated_date]" id="sec_updated_date" class="hidden_check" value="<?php echo esc_attr($documentor_curr['updated_date']);?>">
							<input id="updated_date" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['updated_date']); ?>>
							<label for="updated_date"></label>
						</div>
					</td>
				</tr>
				</table>
				<p class="submit">
				<input type="submit" name="save-settings" class="button-primary" value="<?php _e('Save Changes','documentor-lite'); ?>" />
				</p>
				<span scope="row" class="doc-settingtitle"><?php _e('Scrollbar','documentor-lite'); ?></span>
				<table class="form-table settings-tbl"  >
					<?php 
						//new settings for scrollbar v1.1
						$scrollsize = isset( $documentor_curr['scroll_size'] ) ? $documentor_curr['scroll_size'] : 3;
						$scrollcolor = isset( $documentor_curr['scroll_color'] ) ? $documentor_curr['scroll_color'] : '#F45349';
						$scrollopacity = isset( $documentor_curr['scroll_opacity'] ) ? $documentor_curr['scroll_opacity'] : 0.4;
					?>
					<tr valign="top">
						<th scope="row"><?php _e('size','documentor-lite'); ?></th>
						<td>
							<input type="number" min="0" class="small-text" name="<?php echo $documentor_options;?>[scroll_size]" id="scroll_size" value="<?php echo esc_attr($scrollsize);?>">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Color','documentor-lite'); ?></th>
						<td>
							<input type="color" name="<?php echo $documentor_options;?>[scroll_color]" id="scroll_color" value="<?php echo esc_attr($scrollcolor); ?>" class="wp-color-picker-field" data-default-color="#2c3e50" />
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Opacity','documentor-lite'); ?></th>
						<td>
							<input type="number" class="small-text" name="<?php echo $documentor_options;?>[scroll_opacity]" id="scroll_opacity" value="<?php echo esc_attr($scrollopacity); ?>" min="0" max="1" step="any" />
						</td>
					</tr>
				</table>				
				<p class="submit">
				<input type="submit" name="save-settings" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>				
				</div>
				<?php // do_action('pointelle_addon_settings',$cntr,$documentor_options,$documentor_curr);?>
				</div> <!--Formatting -->
				<div id="advance-settings" class="doc-settingsdiv">
				<div class="sub_settings toggle_settings">
				<h2 class="sub-heading"><?php _e('Advance Settings','documentor-lite'); ?><span class="toggle_img"></span></h2> 
				<table class="form-table">
				<tr valign="top">
				<th scope="row"><?php _e('Guide Manager','documentor-lite'); ?></th>
				<td>
				<select name="<?php echo $documentor_options;?>[guide][]" id="documentor_guide_manager" multiple>
				<?php $users = array_merge( get_users('role=administrator'), get_users('role=editor') );
				$i = 0;
				foreach( $users as $user ) { ?>
					<option value="<?php echo esc_attr($user->ID);?>" <?php if ( !isset( $documentor_curr['guide'] ) && $i == 0 ){ echo "selected";} else if(isset($documentor_curr['guide'][$i]) && $documentor_curr['guide'][$i] == $user->ID ){ echo "selected";} ?> ><?php echo $user->display_name; ?></option>
				<?php	
					$i++;
				 }
				?>
				</select>
				</td>
				</tr>				
				<tr valign="top">
				<th scope="row"><?php _e('Search Box','documentor-lite'); ?></th>
				<td>
				<div class="eb-switch eb-switchnone">
					<input type="hidden" name="<?php echo $documentor_options;?>[search_box]" id="search-box" class="hidden_check" value="<?php echo esc_attr($documentor_curr['search_box']);?>">
					<input id="search_box" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['search_box']); ?>>
					<label for="search_box"></label>
				</div>
				</td>
				</tr>				
				<tr valign="top">
					<th scope="row"><?php _e('RTL Support','documentor-lite'); ?></th>
					<td>
						<?php $documentor_curr['rtl_support'] = isset($documentor_curr['rtl_support']) ? $documentor_curr['rtl_support'] : '0'; ?>
						<div class="eb-switch eb-switchnone havemoreinfo">
							<input type="hidden" name="<?php echo $documentor_options;?>[rtl_support]" id="related-document" class="hidden_check" value="<?php echo esc_attr($documentor_curr['rtl_support']);?>">
							<input id="rtl_support" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['rtl_support']); ?>>
							<label for="rtl_support"></label>
						</div>
					</td>
				</tr>				
				<tr valign="top">
					<th scope="row"><?php _e('Back to Top button','documentor-lite'); ?></th>
					<td>
						<?php $documentor_curr['scrolltop'] = isset($documentor_curr['scrolltop']) ? $documentor_curr['scrolltop'] : '1'; ?>
						<div class="eb-switch eb-switchnone havemoreinfo">
							<input type="hidden" name="<?php echo $documentor_options;?>[scrolltop]" id="related-document" class="hidden_check" value="<?php echo esc_attr($documentor_curr['scrolltop']);?>">
							<input id="scrolltop" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['scrolltop']); ?>>
							<label for="scrolltop"></label>
						</div>
					</td>
				</tr>				
				<tr valign="top">
					<th scope="row"><?php _e('Social Sharing','documentor-lite'); ?></th>
					<td>
						<div class="eb-switch eb-switchnone">
							<input type="hidden" name="<?php echo $documentor_options;?>[socialshare]" id="related-document" class="hidden_check" value="<?php echo esc_attr($documentor_curr['socialshare']);?>">
							<input id="socialshare" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['socialshare']); ?>>
							<label for="socialshare"></label>
						</div>
						<span class="doc-format">
							<a href="#format-social" rel="leanModal" title="Social Share Format" ><?php _e('Format','documentor-lite');?></a>
						</span>
					</td>
				</tr>				
				</table>				
				<!-- options of social share buttons -->
				<div id="format-social" class="format-form"> 
					<div id="format-ct">
						<div class="frm-heading"><?php _e('Social Share Options','documentor-lite');?></div>
						<div id="format-header">
							<p class="format-heading"><?php _e('Select Social buttons','documentor-lite');?></p>
							<a class="modal_close" href="#"></a>
						</div>
						<div class="txt-fld">
							<label for="name" class="lbl"><?php _e('Facebook','documentor-lite'); ?></label>
							<div class="eb-switch eb-switchnone">
								<input type="hidden" name="<?php echo $documentor_options;?>[socialbuttons][0]" class="hidden_check" value="<?php echo esc_attr($documentor_curr['socialbuttons'][0]);?>">
								<input id="socialbuttons-select1" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['socialbuttons'][0]); ?>>
								<label for="socialbuttons-select1"></label>
							</div>
						</div>
						<div class="txt-fld">
							<label for="name" class="lbl"><?php _e('Twitter','documentor-lite'); ?></label>
							<div class="eb-switch eb-switchnone">
								<input type="hidden" name="<?php echo $documentor_options;?>[socialbuttons][1]" class="hidden_check" value="<?php echo esc_attr($documentor_curr['socialbuttons'][1]);?>">
								<input id="socialbuttons-select2" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['socialbuttons'][1]); ?>>
								<label for="socialbuttons-select2"></label>
							</div>
						</div>
						<div class="txt-fld">
							<label for="name" class="lbl"><?php _e('Google Plus','documentor-lite'); ?></label>
							<div class="eb-switch eb-switchnone">
								<input type="hidden" name="<?php echo $documentor_options;?>[socialbuttons][2]" class="hidden_check" value="<?php echo esc_attr($documentor_curr['socialbuttons'][2]);?>">
								<input id="socialbuttons-select3" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['socialbuttons'][2]); ?>>
								<label for="socialbuttons-select3"></label>
							</div>
							<?php if( !function_exists('curl_version') ) { ?>
								<label><?php _e("To get the count of Google Plus shares, please enable the curl extension of PHP","");?></label>
							<?php }?>
						</div>
						<div class="txt-fld">
							<label for="name" class="lbl"><?php _e('Pinterest','documentor-lite'); ?></label>
							<div class="eb-switch eb-switchnone">
								<input type="hidden" name="<?php echo $documentor_options;?>[socialbuttons][3]" class="hidden_check" value="<?php echo esc_attr($documentor_curr['socialbuttons'][3]);?>">
								<input id="socialbuttons-select4" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['socialbuttons'][3]); ?>>
								<label for="socialbuttons-select4"></label>
							</div>
						</div>
						<div id="format-header">
							<p class="format-heading"><?php _e('Select Format','documentor-lite');?></p>
						</div>
						<div class="txt-fld">
							<label>
								<input type="radio" name="<?php echo $documentor_options;?>[sbutton_style]" <?php checked("square",$documentor_curr['sbutton_style'] );?> value="square" >
								<img src="<?php echo DOCLITE_URLPATH.'core/images/square.png'; ?>">
							</label>
							<label>
								<input type="radio" name="<?php echo $documentor_options;?>[sbutton_style]" <?php checked("round",$documentor_curr['sbutton_style'] );?> value="round" >
								<img src="<?php echo DOCLITE_URLPATH.'core/images/round.png'; ?>">
							</label>
							<label>
								<input type="radio" name="<?php echo $documentor_options;?>[sbutton_style]" <?php checked("squarecount",$documentor_curr['sbutton_style'] );?> value="squarecount" >
								<img src="<?php echo DOCLITE_URLPATH.'core/images/squarecount.png'; ?>">
							</label>
							<label>
								<input type="radio" name="<?php echo $documentor_options;?>[sbutton_style]" <?php checked("squareround",$documentor_curr['sbutton_style'] );?> value="squareround" >
								<img src="<?php echo DOCLITE_URLPATH.'core/images/squareround.png'; ?>">
							</label>
						</div>
						<div id="format-header">
							<p class="format-heading"><?php _e('Display Share Count','documentor-lite');?></p>
						</div>
						<div class="txt-fld">
							<label for="name" class="lbl"><?php _e('Share Count','documentor-lite'); ?></label>
							<div class="eb-switch eb-switchnone">
								<input type="hidden" name="<?php echo $documentor_options;?>[sharecount]" class="hidden_check" value="<?php echo esc_attr($documentor_curr['sharecount']);?>">
								<input id="sharecount" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['sharecount']); ?>>
								<label for="sharecount"></label>
							</div>
						</div>
						<div id="format-header">
							<p class="format-heading"><?php _e('Position','documentor-lite');?></p>
						</div>
						<div class="txt-fld">
							<label>
								<input type="radio" name="<?php echo $documentor_options;?>[sbutton_position]" <?php checked("top",$documentor_curr['sbutton_position'] );?> value="top" ><?php _e('Top','documentor-lite');?>
							</label>
							<label>
								<input type="radio" name="<?php echo $documentor_options;?>[sbutton_position]" <?php checked("bottom",$documentor_curr['sbutton_position'] );?> value="bottom" style="margin-left: 20px;"><?php _e('Bottom','documentor-lite');?>
							</label>
						</div>
						<div class="btn-fld">
							<input type="submit" name="save-settings" class="button-primary" value="Save">
						</div>
					</div>
				</div>
				<!--Indexing Formats -->				
				<div id="format-index" class="format-form">
					<div id="format-ct">
						<div class="frm-heading"><?php _e('Index Formatting','documentor');?></div>
						<table class="form-table settings-tbl">	
							<tr valign="top">
								<th scope="row"><?php _e('Parent Index Format','documentor'); ?></th>
								<td>
									<select name="<?php echo $documentor_options;?>[pif]" >
										<option value="decimal" <?php if ($documentor_curr['pif'] == "decimal"){ echo "selected";}?> >Decimal</option>
										<option value="decimal-leading-zero" <?php if ($documentor_curr['pif'] == "decimal-leading-zero"){ echo "selected";}?> >Decimal leading zero</option>
										<option value="lower-roman" <?php if ($documentor_curr['pif'] == "lower-roman"){ echo "selected";}?> >Lower Roman</option>
										<option value="upper-roman" <?php if ($documentor_curr['pif'] == "upper-roman"){ echo "selected";}?> >Upper Roman</option>
										<option value="lower-alpha" <?php if ($documentor_curr['pif'] == "lower-alpha"){ echo "selected";}?> >Lower Alphabets</option>
										<option value="upper-alpha" <?php if ($documentor_curr['pif'] == "upper-alpha"){ echo "selected";}?> >Upper Alphabets</option>
									</select>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e('Child Index Format','documentor'); ?></th>
								<td>
									<select name="<?php echo $documentor_options;?>[cif]" >
										<option value="decimal" <?php if ($documentor_curr['cif'] == "decimal"){ echo "selected";}?> >Decimal</option>
										<option value="decimal-leading-zero" <?php if ($documentor_curr['cif'] == "decimal-leading-zero"){ echo "selected";}?> >Decimal leading zero</option>
										<option value="lower-roman" <?php if ($documentor_curr['cif'] == "lower-roman"){ echo "selected";}?> >Lower Roman</option>
										<option value="upper-roman" <?php if ($documentor_curr['cif'] == "upper-roman"){ echo "selected";}?> >Upper Roman</option>
										<option value="lower-alpha" <?php if ($documentor_curr['cif'] == "lower-alpha"){ echo "selected";}?> >Lower Alphabets</option>
										<option value="upper-alpha" <?php if ($documentor_curr['cif'] == "upper-alpha"){ echo "selected";}?> >Upper Alphabets</option>
									</select>
								</td>
							</tr>
						</table>
						<p>
							<input type="submit" name="save-settings" class="button-primary" value="Save">
						</p>
					</div>
				</div>						
				<!-- Guide title options -->
				<div id="options-guidetitle" class="format-form">
					<div id="format-ct">
						<div class="frm-heading"><?php _e('Guide Title Formatting','documentor-lite');?></div>
						<table class="form-table settings-tbl">	
							<tr valign="top">
								<th scope="row"><?php _e('Element','documentor-lite'); ?></th>
								<td>
									<select name="<?php echo $documentor_options;?>[guidet_element]" >
										<option value="1" <?php if ($documentor_curr['guidet_element'] == "1"){ echo "selected";}?> >h1</option>
										<option value="2" <?php if ($documentor_curr['guidet_element'] == "2"){ echo "selected";}?> >h2</option>
										<option value="3" <?php if ($documentor_curr['guidet_element'] == "3"){ echo "selected";}?> >h3</option>
										<option value="4" <?php if ($documentor_curr['guidet_element'] == "4"){ echo "selected";}?> >h4</option>
										<option value="5" <?php if ($documentor_curr['guidet_element'] == "5"){ echo "selected";}?> >h5</option>
										<option value="6" <?php if ($documentor_curr['guidet_element'] == "6"){ echo "selected";}?> >h6</option>
									</select>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e('Use theme default','documentor-lite'); ?></th>
								<td>
									<div class="eb-switch eb-switchnone havemoreinfo">
										<input type="hidden" name="<?php echo $documentor_options;?>[guidet_default]" id="guidet-default" class="hidden_check" value="<?php echo esc_attr($documentor_curr['guidet_default']);?>">
										<input id="guidet-def" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked('1', $documentor_curr['guidet_default']); ?>>
										<label for="guidet-def"></label>
									</div>
								</td>
							</tr>				
							<tr valign="top">
								<th scope="row"><?php _e('Color','documentor-lite'); ?></th>
								<td>
									<input type="color" name="<?php echo $documentor_options;?>[guidet_color]" id="guidet_color" value="<?php echo esc_attr($documentor_curr['guidet_color']); ?>" class="wp-color-picker-field" data-default-color="#D8E7EE" />
								</td>
						   	</tr>						    
							<tr valign="top">
								<th scope="row"><?php _e('Font','documentor-lite'); ?></th>
								<td>
									<input type="hidden" value="guidetitle_font" class="ftype_rname">
									<input type="hidden" value="guidet_fontg" class="ftype_gname">
									<input type="hidden" value="guidet_custom" class="ftype_cname">
									<select name="<?php echo $documentor_options;?>[guidet_font]" id="guidet_font" class="main-font">
	
										<option value="regular" <?php selected( $documentor_curr['guidet_font'], "regular" ); ?> > Regular Fonts </option>
										<option value="google" <?php selected( $documentor_curr['guidet_font'], "google" ); ?> > Google Fonts </option>
										<option value="custom" <?php selected( $documentor_curr['guidet_font'], "custom" ); ?> > Custom Fonts </option>
									</select>
								</td>
							</tr>
							<tr><td class="load-fontdiv" colspan="2"></td></tr>
							<tr valign="top">
							<th scope="row"><?php _e('Font Size','documentor-lite'); ?></th>
							<td><input type="number" name="<?php echo $documentor_options;?>[guidet_fsize]" id="guidet_fsize" class="small-text" value="<?php echo esc_attr($documentor_curr['guidet_fsize']); ?>" min="1" />&nbsp;<?php _e('px','documentor-lite'); ?></td>
							</tr>
							<tr valign="top" class="font-style">
								<th scope="row"><?php _e('Font Style','documentor-lite'); ?></th>
								<td>
									<select name="<?php echo $documentor_options;?>[guidet_fstyle]" id="guidet_fstyle" class="font-style" >
									<option value="bold" <?php if ($documentor_curr['guidet_fstyle'] == "bold"){ echo "selected";}?> ><?php _e('Bold','documentor-lite'); ?></option>
									<option value="bold italic" <?php if ($documentor_curr['guidet_fstyle'] == "bold italic"){ echo "selected";}?> ><?php _e('Bold Italic','documentor-lite'); ?></option>
									<option value="italic" <?php if ($documentor_curr['guidet_fstyle'] == "italic"){ echo "selected";}?> ><?php _e('Italic','documentor-lite'); ?></option>
									<option value="normal" <?php if ($documentor_curr['guidet_fstyle'] == "normal"){ echo "selected";}?> ><?php _e('Normal','documentor-lite'); ?></option>
									</select>
								</td>
							</tr>
						</table>
						<p>
							<input type="submit" name="save-settings" class="button-primary" value="Save">
						</p>
					</div>
				</div>				
				<input type="hidden" name="guidename" class="guidename" value="<?php echo esc_attr($guide->doc_title);?>">
				<input type="hidden" name="hidden_urlpage" class="documentor_urlpage" value="<?php echo esc_attr($_GET['page']);?>" />
				<input type="hidden" name="documentor-settings-nonce" value="<?php echo wp_create_nonce( 'documentor-settings-nonce' ); ?>" />
				<p class="submit">
				<input type="submit" name="save-settings" class="button-primary" value="<?php _e('Save Changes','documentor-lite'); ?>" />
				</p>
				</div>				
				</form>
				</div> <!--advance settings -->
				<?php
				//added
				?>
				</div> <!--tab group-2 ends -->
				<?php } // if tab is 1
				else if( isset( $tabindex ) && $tabindex == 'embedcode' ) { ?>	
				<div id="options-group-3" class="group embedcode">
				<table class="form-table" id="embedcode">
				<input type="hidden" value="<?php echo esc_attr($this->docid); ?>" name="docsid" />
				<?php
				if ( isset($this->docid ) ) {
					$doc_id = $this->docid;
				}
				?>
					<tr valign="top">
						<th scope="row"><?php _e('Shortcode','documentor-lite'); ?></th>
						<td>
						<div><code> <?php echo '[documentor '.$doc_id.']' ?></code> </div>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php _e('Template Tag','documentor-lite'); ?></th>
						<td>
						<div> <?php echo "<code>&lt;?php if(function_exists('get_documentor')){ get_documentor('".$doc_id."'); }?&gt;</code>"; ?></div>
						</td>
					</tr>
				</table>
				</div> <!--tab group-3 ends -->
				<?php } //if tab is 2 
				else if( $tabindex == 'add-sections' ) { ?>				
					<div id="doc-add-sections" class="doc-add-sections">					
						<div class="edit-guidetitle"><span class="dashicons dashicons-plus-alt addsec-icon"></span>Add New Section<a class="create-btn edit-guidebtn" href="<?php echo esc_url(admin_url('admin.php?page=documentor-admin&action=edit&id='.$this->docid.'&tab=sections')); ?>"><?php _e('Edit','documentor-lite'); ?></a></div>
						<div class="doc-success-msg"></div>
						<form method="post" id="addsecform" name="addsecform" class="addsecform">
							<input type="hidden" value="<?php echo esc_attr($this->docid); ?>" name="docsid" />
							<div class="eb-cs-left">
								<?php 
								//if custom post is enabled then only add inline sections
								$global_settings_curr = get_option('documentor_global_options');
								if( isset( $global_settings_curr['custom_post'] ) && $global_settings_curr['custom_post'] == '1' ) { ?>
								<div class="eb-cs-tab eb-cs-blank doc-active"> <span class="dashicons dashicons-editor-alignleft"></span> <?php _e('Inline','documentor-lite'); ?></div>
								<?php } ?>
								<div class="eb-cs-tab eb-cs-post" id="post" ><span class="dashicons dashicons-admin-post"></span> <?php _e('Posts','documentor-lite'); ?></div>
								<div class="eb-cs-tab eb-cs-post" id="page" ><span class="dashicons dashicons-admin-page"></span> <?php _e('Pages','documentor-lite'); ?></div>
								<div class="eb-cs-tab eb-cs-links" id="attachment"><span class="dashicons dashicons-admin-links"></span> <?php _e('Links','documentor-lite'); ?></div>
							</div>
							<div class="eb-cs-right-wrap" style="float: left;width: 80%;">			
								<?php 
								//if custom post is enabled then only add inline sections
								if( isset( $global_settings_curr['custom_post'] ) && $global_settings_curr['custom_post'] == '1' ) { ?>
									<div style="margin-left: 20px;" class="addinlinesecform">
											<div class="docfrm-div">
												<label class="titles"> <?php _e('Menu Title','documentor-lite'); ?> </label>
												<input type="text" name="menutitle" class="txts menutitle" placeholder="<?php _e('Enter Menu Title','documentor-lite'); ?>" value="" />
											</div>
											<div class="docfrm-div">
												<label class="titles"> <?php _e('Section Title','documentor-lite'); ?> </label>
												<input type="text" name="sectiontitle" class="txts sectiontitle" placeholder="<?php _e('Enter Section Title','documentor-lite'); ?>" value="" />
											</div>
											<div class="docfrm-div">
												<label class="titles"> <?php _e('Content','documentor-lite'); ?> </label>
												<?php $documentor = new DocumentorLite(); 
																						$content = '';
												$editor_id = 'content';
												$settings =   array(
												    'wpautop' => true, // use wpautop?
												    'media_buttons' => true, // show insert/upload button(s)
												    'textarea_name' => $editor_id, // set the textarea name to something different, square brackets [] can be used here
												    'textarea_rows' => 15, // rows="..."
												    'tabindex' => '',
												    'editor_css' => '', // intended for extra styles for both visual and HTML editors buttons, needs to include the <style> tags, can use "scoped".
												    'editor_class' => '', // add extra class(es) to the editor textarea
												    'teeny' => false, // output the minimal editor config used in Press This
												    'dfw' => false, // replace the default fullscreen with DFW (supported on the front-end in WordPress 3.4)
												    'tinymce' => true, // load TinyMCE, can be used to pass settings directly to TinyMCE using an array()
												    'quicktags' => true // load Quicktags, can be used to pass settings directly to Quicktags using an array()
												);
												echo '<div style="width:99%;height:auto;">';
													wp_editor( $content, $editor_id, $settings );
												echo '</div>';
												?>
											</div>
											<div class="clrleft"></div>
											<p><input type="submit" name="add_section" class="button-primary add-inlinesectionbtn" value="<?php _e('Insert','documentor-lite'); ?>" /></p>
											<input type="hidden" name="post_type" value="inline" />
									</div>
								<?php }?>							
								<div class="eb-cs-right">						
								</div>
							</div>
							<input type="hidden" name="documentor-sections-nonce" value="<?php echo wp_create_nonce( 'documentor-sections-nonce' ); ?>">
							<a class="create-btn edit-guidebtn" style="float: right;margin-right: 96px;" href="<?php echo esc_url(admin_url('admin.php?page=documentor-admin&action=edit&id='.$this->docid.'&tab=sections')); ?>"><span class="dashicons dashicons-undo doc-back"></span><?php _e('Back to Edit','documentor-lite'); ?></a>
						</form>					
					</div>
				<?php }//if tab ends							
		} //function admin_view ends			
		//Prashant
		public static function doc_show_posts() {
			check_ajax_referer( 'documentor-sections-nonce', 'sections_nonce' );
			global $paged,$wpdb,$post; 
			$pages = '';
			$paged = isset($_POST['paged'])?intval($_POST['paged']):'';
			$post_type = isset($_POST['post_type'])?sanitize_text_field($_POST['post_type']):'';
			$docid = isset($_POST['docid'])?intval($_POST['docid']):'';
			$stext = isset($_POST['search_text'])?sanitize_text_field($_POST['search_text']):'';
			$range = 10;
			$html = '';
			$showitems = ($range * 2)+1; 
			if(empty($paged)) $paged = 1;
			$sec = new DocumentorLiteSection();
			$pidarr = $sec->get_addedposts( $docid );
			if( count( $pidarr ) > 0 ) {
				//$posts_not_in = "'post__not_in' => ".$pidarr;
				$args = array(
					'post_type' => $post_type,
					'posts_per_page'=>10,	
					'post_status'   => 'publish',
					'paged'=>$paged,
					's'=>$stext,
					'post__not_in' => $pidarr
				);
			} else {
				$args = array(
					'post_type' => $post_type,
					'posts_per_page'=>10,	
					'post_status'   => 'publish',
					'paged'=>$paged,
					's'=>$stext,
				);
			}
			$the_query = new WP_Query( $args );
			$i=0;
			// The Loop
			if ( $the_query->have_posts() ) {
				$html .= '<div style="margin-left: 20px;" >';
				$html .= '<h3 class="nav-tab-wrapper p-tabs"> 
						  <a id="recent-tabcontent-tab" class="nav-tab recent-tabcontent-tab" title="Recent '.$post_type.'s" href="#recent-tabcontent">Recent '.$post_type.'s</a> 
						  <a id="search-tabcontent-tab" class="nav-tab search-tabcontent-tab" title="'. __('Search','documentor-lite').'" href="#search-tabcontent">'. __('Search','documentor-lite').'</a>
					</h3>';
				$html .= '<!--<form name="eb-wp-posts" id="eb-wp-posts" method="post" >-->
					<div id="recent-tabcontent" class="pgroup recent-tabcontent">
					';
				$html .= '<table class="wp-list-table widefat sliders" >';
				$html .= '<col width="10%">
					<col width="70%">
					<col width="20%">
						<thead>
						<tr>
							<th class="docpost-id">'. __('ID','documentor-lite').'</th>
							<th class="docpost-title">'. __('Name','documentor-lite').'</th>	
							<th class="docpost-editlnk">'. __('Edit Link','documentor-lite').'</th>
						</tr>
						</thead>';				
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$i++;
					$html .= '<tr>';
					$html .= '<td><input type="checkbox" name="post_id[]" value="'.esc_attr(get_the_ID()).'"></td>';
					$permalink = get_permalink( get_the_ID() );
					$html .= '<td><a href="'.esc_url($permalink).'" target="_blank">' . get_the_title() . '</a></td>';
					$editlink = '';
					if( post_type_exists($post_type) ) { 
						if( current_user_can('edit_post', get_the_ID()) ) {
							$edtlink = get_edit_post_link(get_the_ID());
							$editlink = '<a href="'.esc_url($edtlink).'" target="_blank" class="section-editlink">'. __('Edit','documentor-lite').'</a>';
						}
					}
					$html .= '<td>'.$editlink.'</td>';
					$html .= '</tr>';
				}
				$html .= '</table>';
				if($pages == '') {
					$pages = $the_query->max_num_pages;
					if(!$pages) {
						$pages = 1;
					}
				}  
				if(1 != $pages)
				{
					if($paged > 1 ) $prev = ($paged - 1); else $prev = 1;
					$html .= "<div class=\"eb-cs-pagination\"><span>". __('Page','documentor-lite')." ".$paged.__('of','documentor-lite')." ".$pages."</span>";
					$html .= "<a id='1' class='pageclk' >&laquo; ".__('First','documentor-lite')."</a>";
					$html .= "<a id='".$prev."' class='pageclk' >&lsaquo; ".__('Previous','documentor-lite')."</a>";

					for ($i=1; $i <= $pages; $i++) {
						if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems )) {
							$html .= ($paged == $i)? "<span class=\"current\">".$i."</span>":"<a id=\"$i\" class=\"inactive pageclk\">".$i."</a>";
						}
					}
					$html .= "<a id=\"".($paged + 1) ."\" class='pageclk' >".__('Next','documentor-lite')." &rsaquo;</a>"; 
					$html .= "<a id='".$pages."' class='pageclk' >".__('Last','documentor-lite')." &raquo;</a>";
					$html .= "</div>\n";
				}
				$html .= "<p><input type='submit' name='add_posts' value='".__('Insert','documentor-lite')."' class='btn_save add_posts' /></p>\n";
				$html .= '<input type="hidden" name="docid" value="'. esc_attr($docid).'" />';
				$html .= '<input type="hidden" name="post_type" class="post_type" value="'. esc_attr($post_type).'" />';
				$html .= '</div>';
				$html .= '<div id="search-tabcontent" class="pgroup search-tabcontent">';
				$html .= '<input type="text" name="search-input" class="search-input" placeholder="'.__('Enter search text','documentor-lite').'" />';
				$html .= '<div class="load-searchresults"></div><!--</form>--></div>';
				echo $html;
				/* Restore original Post Data */
				wp_reset_postdata();
			} else {
				_e('no posts found','documentor-lite');
			}
			die();
		}
		//show search results of page/posts
		public static function show_search_results() {
			check_ajax_referer( 'documentor-sections-nonce', 'sections_nonce' );
			global $paged,$wpdb,$post; 
			$pages = '';
			$paged = isset($_POST['paged'])?intval($_POST['paged']):'';
			$post_type = isset($_POST['post_type'])?sanitize_text_field($_POST['post_type']):'';
			$docid = isset($_POST['docid'])?intval($_POST['docid']):'';
			$stext = isset($_POST['search_text'])?sanitize_text_field($_POST['search_text']):'';
			$range = 10;
			$html = '';
			$showitems = ($range * 2)+1; 
			if(empty($paged)) $paged = 1;
			$args = array(
				'post_type' => $post_type,
				'posts_per_page'=>10,	
				'post_status'   => 'publish',
				'paged'=>$paged,
				's'=>$stext
			);
			$the_query = new WP_Query( $args );
			$i=0;
			// The Loop
			if ( $the_query->have_posts() ) {
				$html .= '<table class="wp-list-table widefat sliders" >';
				$html .= '<col width="10%">
					<col width="70%">
					<col width="20%">
						<thead>
						<tr>
							<th class="sliderid-column">'.__('ID','documentor-lite').'</th>
							<th class="slidername-column">'.__('Name','documentor-lite').'</th>
							<th class="docpost-editlnk">'. __('Edit Link','documentor-lite').'</th>
						</tr>
						</thead>';
				
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$i++;
					$html .= '<tr>';
					$html .= '<td><input type="checkbox" name="post_id[]" value="'.esc_attr(get_the_ID()).'"></td>';
					$html .= '<td>' . get_the_title() . '</td>';
					$editlink = '';
					if( post_type_exists($post_type) ) { 
						if( current_user_can('edit_post', get_the_ID()) ) {
							$edtlink = get_edit_post_link(get_the_ID());
							$editlink = '<a href="'.esc_url($edtlink).'" target="_blank" class="section-editlink">'. __('Edit','documentor-lite').'</a>';
						}
					}
					$html .= '<td>'.$editlink.'</td>';
					$html .= '</tr>';
				}
				$html .= '</table>';
				if($pages == '') {
					$pages = $the_query->max_num_pages;
					if(!$pages) {
						$pages = 1;
					}
				}  
				if(1 != $pages) {
					if($paged > 1 ) $prev = ($paged - 1); else $prev = 1;
					$html .= "<div class=\"eb-cs-pagination\"><span>".__('Page','documentor-lite')." ".$paged." ".__('of','documentor-lite')." ".$pages."</span>";
					$html .= "<a id='1' class='pageclk-search' >&laquo; ".__('First','documentor-lite')."</a>";
					$html .= "<a id='".$prev."' class='pageclk-search' >&lsaquo; ".__('Previous','documentor-lite')."</a>";

					for ($i=1; $i <= $pages; $i++) {
						if (1 != $pages &&( !($i >= $paged+$range+1 || $i <= $paged-$range-1) || $pages <= $showitems )) {
							$html .= ($paged == $i)? "<span class=\"current\">".$i."</span>":"<a id=\"$i\" class=\"inactive pageclk-search\">".$i."</a>";
						}
					}
					$html .= "<a id=\"".($paged + 1) ."\" class='pageclk-search' >".__('Next','documentor-lite')." &rsaquo;</a>"; 
					$html .= "<a id='".$pages."' class='pageclk-search' >".__('Last','documentor-lite')." &raquo;</a>";
					$html .= "</div>\n";
				}
				$html .= "<p><input type='submit' name='add_posts' value='".__('Insert','documentor-lite')."' class='btn_save add_posts' /></p>\n";
				echo $html;
			} else {
				_e('no posts found','documentor-lite');
			}	
			die();
		}
		//set setting fields empty if not present in current array
		function populate_documentor_current( $documentor_curr ) {
			$doc = new DocumentorLite();
			$default_documentor_settings = $doc->default_documentor_settings;
			foreach( $default_documentor_settings as $key => $value ){
				if( !isset( $documentor_curr[$key] ) ) $documentor_curr[$key] = $value;
			}
			return $documentor_curr;
		}
		/* Search in document */
		public static function get_search_results() {
			$term = strtolower( $_REQUEST['term'] );
			$docid = isset( $_REQUEST['docid'] ) ? $_REQUEST['docid'] : '';
			$suggestions = array();
			if( !empty( $docid ) ) {
				global $wpdb,$table_prefix;
				$postids = $wpdb->get_col('SELECT post_id FROM '.$table_prefix.DOCUMENTORLITE_SECTIONS.' WHERE doc_id = '.$docid);
				$includearr =  array();
				if( $postids ) $includearr = $postids;
				$args = array(
					'post_type' => array( 'post', 'page', 'documentor-sections'),
					'posts_per_page' => 10,	
					'post_status'   => 'publish',
					's'=> $term,
			 	);
			 	$the_query = new WP_Query( $args );
			 	while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$suggestion = array();
					if( in_array( get_the_ID(), $includearr ) ) {
						$lbl = get_post_meta(get_the_ID(),'_documentor_sectiontitle', true);
						$suggestion['label'] = $lbl;
						$slug = $wpdb->get_var('SELECT slug FROM '.$table_prefix.DOCUMENTORLITE_SECTIONS.' WHERE post_id = '.get_the_ID());
						$suggestion['slug'] = $slug;
						$suggestions[] = $suggestion;
					}
				}
				wp_reset_postdata();
			}
			// JSON encode and echo
			$response = $_GET["callback"] . "(" . json_encode($suggestions) . ")";
			echo $response;
			die();
		}
		/* function to get social share buttons */
		public function get_social_buttons( $settings, $sharetitle, $sharelink ) {
			$html = '';
			$btnposition = $settings['sbutton_position'];
			$html .='<div class="doc-socialshare doc-noprint '.$btnposition.'">
				<div class="doc-sharelink" data-sharelink="'.urlencode($sharelink).'"></div>';
			$btnclass = $settings['sbutton_style'];
			$i = 1;
			//facebook button
			if( $settings['socialbuttons'][0] == 1 ) {
				$fbtnclass = '';
				if( $i == 1 ) $fbtnclass = ' doc-fsbtn';
				$i++;
				$html .='<div class="sbutton doc-fb-share '.$btnclass.$fbtnclass.'" id="doc_fb_share"><a rel="nofollow" href="http://www.facebook.com/sharer.php?u='. urlencode($sharelink) .'&amp;t='. htmlspecialchars(urlencode(html_entity_decode($sharetitle.' - '.$sharelink, ENT_COMPAT, 'UTF-8')), ENT_COMPAT, 'UTF-8') .'" title="Share to Facebook" onclick="window.open(this.href,\'targetWindow\',\'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=700,height=450\');return false;"><i class="cs c-icon-doc-facebook"></i></a>';
				if( $settings['sharecount'] == 1 ) {
					$html .='<span class="doc-socialcount" id="doc-fb-count"><i class="cs c-icon-doc-spinner animate-spin"></i></span>';
				}
				$html .='</div>';
			}
			//twitter button
			if( $settings['socialbuttons'][1] == 1 ) {
				$fbtnclass = '';
				if( $i == 1 ) $fbtnclass = ' doc-fsbtn';
				$i++;
				$html .='<div class="sbutton doc-twitter-share '.$btnclass.$fbtnclass.'" id="doc_twitter_share"><a rel="nofollow" href="http://twitter.com/share?text='. htmlspecialchars(urlencode(html_entity_decode($sharetitle.' - ', ENT_COMPAT, 'UTF-8')), ENT_COMPAT, 'UTF-8') .'&amp;url='. urlencode($sharelink) .'" title="Share to Twitter" onclick="window.open(this.href,\'targetWindow\',\'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=700,height=450\');return false;"><i class="cs c-icon-doc-twitter"></i></a>';
				if( $settings['sharecount'] == 1 ) {
					$html .= '<span class="doc-socialcount" id="doc-twitter-count"><i class="cs c-icon-doc-spinner animate-spin"></i></span>';
				}
				$html .= '</div>';
			}
			//google plus button
			if( $settings['socialbuttons'][2] == 1 ) {
				$fbtnclass = '';
				if( $i == 1 ) $fbtnclass = ' doc-fsbtn';
				$i++;
				$html .='<div class="sbutton doc-gplus-share '.$btnclass.$fbtnclass.'" id="doc_gplus_share"><a rel="nofollow" href="https://plus.google.com/share?url='.urlencode($sharelink).'" title="Share to Google Plus" onclick="window.open(this.href,\'targetWindow\',\'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=700,height=450\');return false;"><i class="cs c-icon-doc-gplus"></i></a>';
				if( $settings['sharecount'] == 1 ) {
					$gpluscount = $this->get_plusones( $sharelink );
					$html .= '<span class="doc-socialcount" id="doc-gplus-count" data-gpluscnt="'.$gpluscount.'"><i class="cs c-icon-doc-spinner animate-spin"></i></span>';
				}
				$html .= '</div>';
			}
			//pinterest button
			if( $settings['socialbuttons'][3] == 1 ) {
				$fbtnclass = '';
				if( $i == 1 ) $fbtnclass = ' doc-fsbtn';
				$i++;
				$html .='<div class="sbutton doc-pin-share '.$btnclass.$fbtnclass.'" id="doc_pin_share"><a rel="nofollow" href="http://pinterest.com/pin/create/bookmarklet/?url='.urlencode($sharelink) .'&amp;description='. htmlspecialchars(urlencode(html_entity_decode($sharetitle.' - '.$sharelink, ENT_COMPAT, 'UTF-8')), ENT_COMPAT, 'UTF-8') .'" title="Share to Pinterest" onclick="window.open(this.href,\'targetWindow\',\'toolbar=no,location=no,status=no,menubar=no,scrollbars=yes,resizable=yes,width=700,height=450\');return false;"><i class="cs c-icon-doc-pinterest"></i></a>';
				if( $settings['sharecount'] == 1 ) {
					$html .= '<span class="doc-socialcount" id="doc-pin-count"><i class="cs c-icon-doc-spinner animate-spin"></i></span>';
				}
				$html .= '</div>';
			}
			$html .='</div>';
			return $html;
		}
		public static function update_review_me() {	         
			$doc_arr=array();
			$doc_arr = get_option('documentor_global_options');
			$reviewme=(isset($_POST['reviewme']))?($_POST['reviewme']):(strtotime("now"));
			if($reviewme>0){
				$updated_reviewme=$doc_arr['reviewme']=strtotime("+1 week", $reviewme);
			}
			else{
				$updated_reviewme=$doc_arr['reviewme']=$reviewme;	
			}
			update_option('documentor_global_options',$doc_arr);
			die();
		}
		/* Get google plus share count */
		public function get_plusones( $url )  {
			if( function_exists('curl_version') ) {
				$curl = curl_init();
				curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, '[{"method":"pos.plusones.get","id":"p","params":{"nolog":true,"id":"'.rawurldecode($url).'","source":"widget","userId":"@viewer","groupId":"@self"},"jsonrpc":"2.0","key":"p","apiVersion":"v1"}]');
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_URL, "https://clients6.google.com/rpc?key=AIzaSyCKSbrvQasunBoV16zDH9R33D88CeLr9gQ");
				$curl_results = curl_exec ($curl);
				curl_close ($curl);
				$json = json_decode($curl_results, true);
				return isset($json[0]['result']['metadata']['globalCounts']['count'])?intval( $json[0]['result']['metadata']['globalCounts']['count'] ):0;
			} else {
				return 0;
			}
		}
}// class DocumentorLiteGuide ends
?>
