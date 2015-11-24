<?php 
if( !class_exists( 'DocumentorLiteSection' ) ) {
	class DocumentorLiteSection {
		public $secid,$docid;
		public $sectitle='';
		public $menutitle='';
		public $content='';
		public $type='';
		function __construct($id=0 , $secid=0){
			$this->doc_id = $id;
			$this->secid = $secid;
		}
		//create new section
		public static function create(){
			check_ajax_referer( 'documentor-sections-nonce', 'sections_nonce' );
			$ptype = ( $_POST['post_type'] ) ? sanitize_text_field($_POST['post_type']) : '';
			if ( $ptype == 'inline' ) {	
				$type = 0;	//0 for inline
				$menutitle = ( $_POST['menutitle'] ) ? $_POST['menutitle'] : '';
				$sectiontitle = ( $_POST['sectiontitle'] ) ? $_POST['sectiontitle'] : '';
				$icontent = ( $_POST['icontent'] ) ? $_POST['icontent'] : '';
				$docid = isset( $_POST['docid'] ) ? intval($_POST['docid']) : '';
				if( empty( $menutitle ) ) {
					echo 'error: Please enter menu title';
					die();
				} else if( empty( $sectiontitle ) ) {
					echo 'error: Please enter section title';
					die();
				} else {
					if( !empty( $menutitle ) && !empty( $docid ) ) {
						global $table_prefix, $wpdb;
						$post = array(
							'post_title'    => $sectiontitle,
							'post_content'  => $icontent,
							'post_type'	=> 'documentor-sections',
							'post_status'	=> 'publish'
						);
						//insert custom post
						$post_id = wp_insert_post( $post );
						//add meta fields for section title and menu title
						update_post_meta($post_id, '_documentor_menutitle', $menutitle);
						update_post_meta($post_id, '_documentor_sectiontitle', $sectiontitle);			
						//get slug of post
						$dpost = get_post($post_id); 
						$slug = $dpost->post_name;
						//insert section in sections table
						$wpdb->insert( 
							$table_prefix.DOCUMENTORLITE_SECTIONS, 
							array(
								'doc_id' => $docid,
								'post_id' => $post_id,
								'type'	=> $type,
								'slug' => $slug
							), 
							array( 
								'%d',
								'%d', 
								'%s',
								'%s'
							) 
						);
						//update order of sections in documentor table
						$sectionid = $wpdb->insert_id;
						$doctable = $table_prefix.DOCUMENTORLITE_TABLE;						
						$postid = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $doctable WHERE doc_id = %d", $docid ) );
						$getorder = get_post_meta($postid,'_doc_sections_order',true); //ver1.4end
						$secjarray = array();					
						if( !empty( $getorder ) ) {
							$secjarray = json_decode( $getorder, true );
							$secjarray[] = (object) array('id' => $sectionid);
						} else {
							$secjarray[] = (object) array('id' => $sectionid);
						}
						if( count( $secjarray ) > 0 ) {
							$jsonstr = json_encode($secjarray);
							update_post_meta($postid,'_doc_sections_order',$jsonstr);
						}
						_e("Section added successfully!!!",'documentor-lite');
						die();
					}
				}
				
			} else if ( $ptype == 'post' || $ptype == 'page' ) {
				if( $ptype == 'post' ) {
					$type = 1;	// 1 for post
				} else if( $ptype == 'page' ) {
					$type = 2;	// 2 for page
				}
				$docptype = isset( $_POST['post_type'] ) ? $_POST['post_type']: "";
				global $wpdb, $table_prefix;
				$table_name = $table_prefix.DOCUMENTORLITE_SECTIONS;
				$docid = isset( $_POST['docid'] ) ? intval($_POST['docid']) : '';
				if( !empty( $docid ) ) {
					if( !isset( $_POST['post_id'] ) ) {
						_e('Please select any '.$docptype,'documentor-lite');
						die();
					}
					$count = count($_POST['post_id']);
					$values = '';
					for($i = 0; $i < $count; $i++ ) {
						$id = intval($_POST['post_id'][$i]);
						$post = get_post($id); 
						$title = $post->post_title;
						$pid = $id;	//save post/page id in content column
						$sec = new DocumentorLiteSection();
						if(!$sec->is_sectionpresent($pid,$docid)) {	//check if post/page is already added 
							$slug = $post->post_name;
							if($i == $count-1) {
								$values .= "('$docid', '$pid', '$type', '$slug')";
							} else {
								$values .= "('$docid', '$pid', '$type', '$slug'),";
							}
							//add meta fields for section title and menu title
							update_post_meta($pid, '_documentor_menutitle', $title);
							update_post_meta($pid, '_documentor_sectiontitle', $title);	
						}
					}
					if( !empty( $values ) ) {
						//insert sections in sections table
						$sql = "INSERT INTO $table_name (doc_id, post_id, type, slug) VALUES $values";
						$wpdb->query($sql);
						$sectionid = $wpdb->insert_id;
						$lastid = $wpdb->get_var("SELECT MAX(sec_id) FROM $table_name");
						
						//update order of sections in documentor table
						$secarr = array();
						for( $j = $sectionid; $j <= $lastid; $j++ ) {
							$secarr[] = (object) array( 'id' => $j );
						}
						$doctable = $table_prefix.DOCUMENTORLITE_TABLE;						
						$postid = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $doctable WHERE doc_id = %d", $docid ) );
						$getorder = get_post_meta($postid,'_doc_sections_order',true); //ver1.4end
						$secjarray = array();					
						if( !empty( $getorder ) ) {
							$secjarray = json_decode( $getorder, true );
							$secjarray = array_merge( $secjarray, $secarr );
						} else {
							$secjarray = array_merge( $secjarray, $secarr );
						}
						if( count( $secjarray ) > 0 ) {
							$jsonstr = json_encode($secjarray);						
							update_post_meta($postid,'_doc_sections_order',$jsonstr);
						}
					}
				}
				_e("Section added successfully!!!",'documentor-lite');
				die();
			} else if ( $ptype == 'link' ) {
				$type = 3;	//3 for links
				$menutitle = ( isset( $_POST['menutitle'] ) ) ? $_POST['menutitle'] : '';
				$linkurl = ( isset( $_POST['linkurl'] ) ) ? $_POST['linkurl'] : '#';
				$newwindow = ( isset( $_POST['targetw'] ) ) ? intval($_POST['targetw']) : '0';
				if( empty( $menutitle ) ) {
					echo 'error: Please enter menu title'; 
					die();
				} else if( empty( $linkurl ) ) {
					echo 'error: Please enter link url';
					die();
				} else {
					$arr = array(
						'link'=>$linkurl,
						'new_window'=>$newwindow
						);
					$content = serialize($arr); 
					$post = array(
							'post_title'    => $menutitle,
							'post_content'  => $content,
							'post_type'	=> 'nav_menu_item',
							'post_status'	=> 'publish'
						);
					//insert custom post
					$post_id = wp_insert_post( $post );
					//get slug of post
					$dpost = get_post($post_id); 
					$slug = $dpost->post_name;					
					//insert section in sections table
					$docid = isset( $_POST['docid'] ) ? intval($_POST['docid']) : '';
					global $table_prefix, $wpdb;
					$wpdb->insert( 
						$table_prefix.DOCUMENTORLITE_SECTIONS, 
						array(
							'doc_id' => $docid,
							'post_id' => $post_id,
							'type'	=> $type,
							'slug'	=> $slug
						), 
						array( 
							'%d',
							'%d', 
							'%s',
							'%s'
						) 
					);
					//update order of sections in documentor table
					$sectionid = $wpdb->insert_id;
					$doctable = $table_prefix.DOCUMENTORLITE_TABLE;
					if( !empty( $docid ) ) {
						$postid = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $doctable WHERE doc_id = %d", $docid ) );
						$getorder = get_post_meta($postid,'_doc_sections_order',true); //ver1.4end
						$secjarray = array();					
						if( !empty( $getorder ) ) {
							$secjarray = json_decode( $getorder, true );
							$secjarray[] = (object) array('id' => $sectionid);
						} else {
							$secjarray[] = (object) array('id' => $sectionid);
						}
						if( count( $secjarray ) > 0 ) {
							$jsonstr = json_encode($secjarray);						
							update_post_meta($postid,'_doc_sections_order',$jsonstr);
						}
					} 
					_e("Section added successfully!!!",'documentor-lite');
					die();
				}
			}
		}
		//update section
		public static function update() {
			check_ajax_referer( 'documentor-sections-nonce', 'sections_nonce' );
			$type = ( isset( $_POST['type'] ) ) ? intval($_POST['type']) : 0;
			$secid = ( isset( $_POST['section_id'] ) ) ? intval($_POST['section_id']) : '';
			$mtitle = ( isset( $_POST['menutitle'] ) ) ? $_POST['menutitle'] : '';
			$stitle = ( isset( $_POST['sectiontitle'] ) ) ? $_POST['sectiontitle'] : '';
			$postid = ( isset( $_POST['post_id'] ) ) ? intval($_POST['post_id']) : '';
			$slug = ( isset( $_POST['slug'] ) ) ? sanitize_title($_POST['slug']) : '';
			global $wpdb, $table_prefix;
			$sections_table = $table_prefix.DOCUMENTORLITE_SECTIONS;
			$response = array();
			//menu title is compolsary field
			if( empty($mtitle) ) {
				$response['error'] = "Please add menu title";
				echo json_encode($response);
				die();
			}
			else if( empty( $stitle ) && $type == 0 ) {
				$response['error'] = "Please add section title";
				echo json_encode($response);
				die();
			}
			if( empty($slug) ) {
				$response['error'] = "Please add slug";
				echo json_encode($response);
				die();
			}
			if( !empty( $secid ) && ( !empty( $postid ) ) ) {
				//inline or post or page section
				if( $type == 0 || $type == 1 || $type == 2 )  {
					//update post if inline section
					if( $type == 0 ) {
						$post = array(
						      'ID'           => $postid,
						      'post_title' => $stitle
						);
						wp_update_post( $post );
					}
					//update meta fields for menu title and section title
					$menu_title = get_post_meta($postid,'_documentor_menutitle',true);
					if( $menu_title != $mtitle ) {
						update_post_meta($postid, '_documentor_menutitle', $mtitle);
					}
					$section_title = get_post_meta($postid,'_documentor_sectiontitle',true);
					if( $section_title != $stitle ) {
						update_post_meta($postid, '_documentor_sectiontitle', $stitle);	
					}
				} else { //link section
					$linkurl = ( isset( $_POST['linkurl'] ) ) ? $_POST['linkurl'] : '#';
					$newwindow = ( isset( $_POST['new_window'] ) ) ? intval($_POST['new_window']) : '0';
					if( empty( $linkurl ) ) {
						echo "error: Please add link url";
						die();
					}
					$arr = array(
						'link'=>$linkurl,
						'new_window'=>$newwindow
						);
					$content = serialize($arr); 
					//update nav_menu item post
					$post = array(
						      'ID'           => $postid,
						      'post_title'   => $mtitle,
						      'post_content' => $content
						);
					wp_update_post( $post );
				}
				//update slug in sections table
				$secobj = new DocumentorLiteSection();
				//check for duplicate slug if present make it unique 
				$check_sql = "SELECT slug FROM $sections_table WHERE slug = %s AND sec_id != %d";
				$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $slug, $secid ) );
				if ( $slug_check ) {
					$suffix = 2;
					do {
						$alt_slug = $secobj->truncate_slug( $slug, 200 - ( strlen( $suffix ) + 1 ) ) . "-$suffix";
						$slug_check = $wpdb->get_var( $wpdb->prepare( $check_sql, $alt_slug, $secid ) );
						$suffix++;
					} while ( $slug_check );
					$slug = $alt_slug;
				}
				//update slug
				$wpdb->update( 
					$sections_table, 
					array( 
						'slug' => $slug	
					), 
					array( 'sec_id' => $secid ), 
					array( 
						'%s'
					), 
					array( '%d' ) 
				);
				$response['slug'] = apply_filters( 'editable_slug', $slug );
				echo json_encode($response);
			}	
			die();
		}
		//get all data of particular section
		function getdata() {
			global $wpdb, $table_prefix;
			$table_name = $table_prefix.DOCUMENTORLITE_SECTIONS;
			$results = $wpdb->get_results($wpdb->prepare( "SELECT * FROM $table_name WHERE sec_id = %d", $this->secid ));
			return $results;
		}
		//function 
		function get_addedposts( $docid ) {
			global $wpdb, $table_prefix;
			$pids = array();
			$table_name = $table_prefix.DOCUMENTORLITE_SECTIONS;
			if( !empty( $docid ) ) {
				$results = $wpdb->get_results($wpdb->prepare( "SELECT * FROM $table_name WHERE doc_id = %d AND (type = %d OR type = %d)", $docid, 1, 2 ));
				foreach( $results as $result ) {
					$pids[] = $result->post_id;	
				}
			}
			return $pids;
		}
		//function show() {
		public static function show() {
			check_ajax_referer( 'documentor-sections-nonce', 'sections_nonce' );
			// Edit Document
			if(isset($_POST['docid'])) {
				$id = intval($_POST['docid']);
			} else {
				$id = 1;
			}
			$guide=new DocumentorLiteGuide($id);
			$guide->get_sections_html();
		}
		// check whether section(post/page) already added
		function is_sectionpresent( $id, $docid ) {
			global $wpdb, $table_prefix;
			$table_name = $table_prefix.DOCUMENTORLITE_SECTIONS;
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT sec_id FROM $table_name WHERE post_id = %d AND doc_id = %d", $id, $docid ) );
			if( $result == NULL ) { 
				return FALSE; 
			}
			else { 
				return TRUE; 
			}	
		}	
		// add links section form
		public static function section_add_linkform() {
			check_ajax_referer( 'documentor-sections-nonce', 'sections_nonce' );
			$html = '';
			$html.='<form method="post" id="addlink-section" class="addsecform">
					<div style="margin-left: 20px;">
						<div class="docfrm-div">
							<label class="titles"> '.__('Menu Title','documentor-lite').' </label>
							<input type="text" name="menutitle" class="txts menutitle" placeholder="'.__('Enter Menu Title','documentor-lite').'" value="" />
						</div>
						<div class="docfrm-div">
							<label class="titles"> '.__('Link URL','documentor-lite').' </label>
							<input type="text" name="linkurl" class="txts linkurl" placeholder="http://" value="" />
						</div>
						<div class="docfrm-div">
							<input type="checkbox" name="new_window" class="new_window" />
							<input type="hidden" name="targetw" class="targetw">
							<label class="linklabel"> '.__('Open in new window','documentor-lite').' </label>';
						$html.='</div><div class="clrleft"></div>
						<input type="submit" name="add_section" class="button-primary add-linksectionbtn" value="'.__('Insert','documentor-lite').'" />
						<input type="hidden" name="post_type" value="link" />
					</div>
				</form>';
			echo $html;
			die();
		}
		/**
		 * Truncate a post slug.
		 * @param string $slug   The slug to truncate.
		 * @param int    $length Optional. Max length of the slug. Default 200 (characters).
		 * @return string The truncated slug.
		 */
		function truncate_slug( $slug, $length = 200 ) {
			if ( strlen( $slug ) > $length ) {
				$decoded_slug = urldecode( $slug );
				if ( $decoded_slug === $slug )
					$slug = substr( $slug, 0, $length );
				else
					$slug = utf8_uri_encode( $decoded_slug, $length );
			}

			return rtrim( $slug, '-' );
		}
	} //End Class DocumentorLiteSection
} // End If
?>
