<?php // Hook for adding admin menus
if( !class_exists( 'DocumentorLiteAdmin' ) ) {
	class DocumentorLiteAdmin extends DocumentorLite {
		function __construct() {
			if ( is_admin() ) { // admin actions
				add_action( 'admin_menu', array(&$this, 'documentor_admin_menu') );
				add_action( 'admin_init', array(&$this, 'documentor_admin_resources') );
				add_action( 'admin_init', array( &$this, 'register_global_settings' ) ); 
				//hook for updating custom fields
				add_action( 'publish_post', array( &$this, 'update_custom_fields' ) );
				add_action( 'publish_page', array( &$this, 'update_custom_fields' ) );
				add_action( 'edit_post', array( &$this, 'update_custom_fields' ) );
				add_action( 'edit_attachment', array( &$this, 'update_custom_fields' ) );
				//delete section when post is deleted
				add_action( 'wp_trash_post', array( &$this, 'doc_delete_section' ) );
			}
		}
		// function for adding guides page to wp-admin
		function documentor_admin_menu() {
			// Add a new submenu under Options
			add_menu_page( 'Documentor', 'Documentor', 'manage_options','documentor-admin', array(&$this, 'documentor_guides_page'));	
			add_submenu_page( 'documentor-admin', 'Global Settings', 'Global Settings', 'manage_options','documentor-global-settings', array(&$this, 'documentor_global_settings'));
			//add meta box if WPML is active
			if( isset($_GET['post']) && isset($_GET['action']) && $_GET['action'] == 'edit') {
				$pid = $_GET['post'];
				/*global $wpdb, $table_prefix;
				$table_name = $table_prefix.DOCUMENTORLITE_SECTIONS;
				$result = $wpdb->get_var( $wpdb->prepare( "SELECT sec_id FROM $table_name WHERE post_id = %d", $pid ) );*/
				if( function_exists( 'add_meta_box' ) && function_exists('icl_plugin_action_links') ) {
					$post_types = get_post_types(); 
					foreach($post_types as $post_type) {
						add_meta_box( 'documentor_box', __( 'Documentor' , 'documentorlite'), array(&$this, 'documentor_custom_box'), $post_type, 'advanced' );
					}
				}
			}
		}
		//update custom fields
		function update_custom_fields( $post_id ) {
			//menu title
			if( isset( $_POST['_documentor_menutitle'] ) ) {
				$documentor_menutitle = get_post_meta( $post_id, '_documentor_menutitle', true );
				$post_documentor_menutitle = $_POST['_documentor_menutitle'];
				if( $documentor_menutitle != $post_documentor_menutitle ) {
				  update_post_meta( $post_id, '_documentor_menutitle', $post_documentor_menutitle );	
				}
			}
			//section title
			if( isset( $_POST['_documentor_sectiontitle'] ) ) {
				$documentor_sectiontitle = get_post_meta( $post_id, '_documentor_sectiontitle', true );
				$post_documentor_sectiontitle = $_POST['_documentor_sectiontitle'];
				if( $documentor_sectiontitle != $post_documentor_sectiontitle ) {
				  update_post_meta( $post_id, '_documentor_sectiontitle', $post_documentor_sectiontitle );	
				}
			}
		}
		//add metabox callback function
		function documentor_custom_box() {
			global $post;
			$post_id = $post->ID;
			$documentor_menutitle = get_post_meta($post_id, '_documentor_menutitle', true);
			$documentor_sectiontitle = get_post_meta($post_id, '_documentor_sectiontitle', true);			
		?>
			<table class="form-table" style="margin: 0;">
				<tr valign="top">
					<td scope="row">
						<label for="documentor_menutitle"><?php _e('Menu Title ','documentorlite'); ?></label>
					</td>
					<td>
						<input type="text" name="_documentor_menutitle" class="documentor_menutitle" value="<?php echo esc_attr($documentor_menutitle);?>" size="50" />
					</td>
				</tr>
				<tr valign="top">
					<td scope="row">
						<label for="documentor_sectiontitle"><?php _e('Section Title ','documentorlite'); ?></label>
					</td>
					<td>
						<input type="text" name="_documentor_sectiontitle" class="documentor_sectiontitle" value="<?php echo esc_attr($documentor_sectiontitle);?>" size="50" />
					</td>
				</tr>
			</table>
		<?php }
		function documentor_admin_resources() {
			if ( isset($_GET['page']) && ( $_GET['page'] == 'documentor-admin' || $_GET['page'] == 'documentor-global-settings' ) ) {
				wp_register_script('jquery', false, false, false, false);
				wp_enqueue_script( 'jquery-ui-tabs' );
				wp_enqueue_script( 'jquery-ui-core' );
				wp_enqueue_script( 'jquery-nestable', DocumentorLite::documentor_plugin_url( 'core/js/jquery.nestable.js' ), array('jquery'), DOCUMENTORLITE_VER, false);
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_style( 'documentor-admin-css', DocumentorLite::documentor_plugin_url( 'core/css/admin.css' ), false, DOCUMENTORLITE_VER, 'all');
					
				wp_enqueue_script( 'documentor-admin-js', DocumentorLite::documentor_plugin_url( 'core/js/admin.js' ),array('jquery'), DOCUMENTORLITE_VER, false);
				wp_enqueue_script( 'documentor-modal-js', DocumentorLite::documentor_plugin_url( 'core/js/jquery.leanModal.min.js' ),array('jquery'), DOCUMENTORLITE_VER, false);
				wp_enqueue_style( 'documentor_fontawesome_css', DocumentorLite::documentor_plugin_url( 'core/includes/font-awesome/css/font-awesome.min.css' ),
				false, DOCUMENTORLITE_VER, 'all');
			}	
		}
		//$documentor_curr=get_option($documentor_options);
		function documentor_guides_page() {
			// Edit Document
			$id = 1;
			$guide=new DocumentorLiteGuide($id);
			if(isset($_POST['save-settings'])) {
				$numarr = array('indexformat', 'navmenu_default', 'navmenu_fsize', 'actnavbg_default', 'sectitle_default', 'sectitle_fsize', 'seccont_default', 'seccont_fsize');
				foreach($_POST['documentor_options'] as $key=>$value) {
					if(in_array($key,$numarr)) {
						$value = intval($value);
					} else {
						if( is_string( $value ) ) {
							$value = stripslashes($value);
							$value = sanitize_text_field($value);	
						}
					}
					$new_settings_value[$key]=$value;
				}
				$newsettings = json_encode($new_settings_value);
				$newtitle = ( isset( $_POST['guidename'] ) ) ? sanitize_text_field($_POST['guidename']) : ''; 
				$guide->update_settings( $newsettings , $newtitle );
			} 
			$guide->admin_view();
		}
		//global settings
		function documentor_global_settings() { 
			$documentor_global_curr = get_option('documentor_global_options');
			$doc = new DocumentorLite();
			$global_options = $doc->documentor_global_options;
			$group='documentor-global-group';
			$documentor_global_options = 'documentor_global_options';
			foreach( $global_options as $key=>$value ) {
				if( !isset( $documentor_global_curr[$key] ) ) 
					$documentor_global_curr[$key]='';
			}
			?>
			<div class="global_settings">
				<h2> <?php _e('Global Settings','documentorlite'); ?> </h2>
				<form name="documentor_global_settings" method="post" action="options.php">
					<?php settings_fields($group); ?>
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e('Custom Post','documentorlite'); ?></th>
							<td>
								<div class="eb-switch eb-switchnone">
									<input type="hidden" name="<?php echo $documentor_global_options;?>[custom_post]" class="hidden_check" id="documentor_custom_post" value="<?php echo esc_attr($documentor_global_curr['custom_post']);?>">
									<input id="documentor_custompost" class="cmn-toggle eb-toggle-round" type="checkbox" <?php checked("1", $documentor_global_curr['custom_post']); ?> >
									<label for="documentor_custompost"></label>
								</div>
							</td>
						</tr>
					</table>
					<p class="submit">
						<input type="submit" name="Save" class="button-primary" value="<?php _e('Save Changes', 'documentorlite');?>">
					</p>
				</form>	
			</div>
		<?php
		}
		function register_global_settings() {
			register_setting( 'documentor-global-group', 'documentor_global_options' );
		}
		//delete post from sections table if deleted from posts table
		function doc_delete_section( $pid ) {
			global $wpdb,$table_prefix;
			$post = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$table_prefix."posts WHERE ID = %d", $pid ) ); 
			if( $post != NULL ) {
				$wpdb->delete( $table_prefix.DOCUMENTORLITE_SECTIONS, array( 'post_id' => $pid ), array( '%d' ) );		
			}
		}
			
	}//end class
}//end if
new DocumentorLiteAdmin();
?>
