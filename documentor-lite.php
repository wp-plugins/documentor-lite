<?php /*********************************************************
Plugin Name: Documentor Lite
Plugin URI: http://documentor.in/
Description: Best plugin to create online documentation or product guide on WordPress.
Text Domain: documentor-lite
Version: 1.3.1
Author: WebFanzine Media
Author URI: http://www.webfanzine.com/
Wordpress version supported: 3.6 and above
*----------------------------------------------------------------*
* Copyright 2015  WebFanzine Media  (email : support@documentor.in)
* Active Developers: (Tejaswini, Vrushali) WebFanzine Media
* Tested by: (Sagar, Sanjeev) WebFanzine Media
*****************************************************************/
class DocumentorLite{
	var $documentor;
	public $default_documentor_settings;
	public $documentor_global_options;
	function __construct()
	{
		$this->_define_constants();
		$this->default_documentor_settings = array(
			'skin' => 'default',
			'animation' => '',
			'indexformat'=> 1,
			'pif' =>'decimal',
			'cif' =>'decimal',
			'scrolling' => 1,
			'fixmenu' => 1, 
			'menuTop' => '0',
			'navmenu_default' => 1,
			'navt_font' =>'regular',
			'navmenu_tfont' => 'Arial,Helvetica,sans-serif',
			'navmenu_tfontg' => '',
			'navmenu_tfontgw' => '',
			'navmenu_tfontgsubset' => '',
			'navmenu_custom' => '',
			'navmenu_color' => '#000',
			'navmenu_fsize' => 14,
			'navmenu_fstyle' => 'normal',
			'actnavbg_default' => 1,
			'actnavbg_color' =>'#cccccc',
			'section_element' => '3',
			'sectitle_default' => 1,
			'sect_font' => 'regular',
			'sectitle_color' => '#000',
			'sectitle_font' => 'Helvetica,Arial,sans-serif',
			'sectitle_fontg' => '',
			'sectitle_fontgw' => '',
			'sectitle_fontgsubset' => '',
			'sectitle_custom' => '',
			'sectitle_fsize' => 28,
			'sectitle_fstyle' => 'normal',
			'seccont_default' => 1,
			'seccont_color' => '#000',
			'secc_font' => 'regular',
			'seccont_font' => 'Arial,Helvetica,sans-serif',
			'seccont_fontg' => '',
			'seccont_fontgw' => '',
			'seccont_fontgsubset' => '',
			'seccont_custom' => '',
			'seccont_fsize' => 14,
			'seccont_fstyle' => 'normal',
			'guide' => array(),
			'scroll_size' => '3', 
			'scroll_color' => '#F45349', 
			'scroll_opacity' => '0.4',
			'rtl_support' => '0',
			'menu_position' => 'left',
			'updated_date' => '0',
			'scrolltop' => '1',
			'search_box' => '0',
			'socialshare' => '0',
			'sharecount' => '1', 
			'socialbuttons' => array('1','1','1','1'),
			'sbutton_style' => 'square',
			'sbutton_position' => 'bottom',
			'togglemenu' => '0',
			'guidetitle' => '0',
			'guidet_element' => '2',
			'guidet_default' => 1,
			'guidet_font' => 'regular',
			'guidet_color' => '#000000',
			'guidetitle_font' => 'Arial,Helvetica,sans-serif',
			'guidet_fontg' => '',
			'guidet_fontgw' => '',
			'guidet_fontgsubset' => '',
			'guidet_custom' => '',
			'guidet_fsize' => '38',
			'guidet_fstyle' => 'normal',			
		);
		$this->documentor_global_options = array( 'custom_post' => 1,
							'custom_styles' => '',
							'user_level' => 'publish_posts',
							'reviewme' => strtotime("+1 week")
						   );
		$this->_register_hooks();
		$this->include_files();
		$this->create_custom_post();
	}
	// Create Text Domain For Translations	
	function _define_constants()
	{
		if ( ! defined( 'DOCUMENTORLITE_TABLE' ) ) define('DOCUMENTORLITE_TABLE','documentor'); //Documentor TABLE NAME
		if ( ! defined( 'DOCUMENTORLITE_SECTIONS' ) ) define('DOCUMENTORLITE_SECTIONS','documentor_sections'); //sections TABLE NAME
		if ( ! defined( 'DOCUMENTORLITE_VER' ) ) define("DOCUMENTORLITE_VER","1.3.1",false);//Current Version of Documentor
		if ( ! defined( 'DOCUMENTORLITE_PLUGIN_BASENAME' ) )
			define( 'DOCUMENTORLITE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		if ( ! defined( 'DOCUMENTORLITE_CSS_DIR' ) )
			define( 'DOCUMENTORLITE_CSS_DIR', WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).'/skins/' );
		if ( ! defined( 'DOCLITE_PATH' ) )
			define( 'DOCLITE_PATH', WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) );
		if ( ! defined( 'DOCLITE_URLPATH' ) )
			define('DOCLITE_URLPATH', trailingslashit( WP_PLUGIN_URL . '/' . plugin_basename( dirname(__FILE__) ) ) );
	}
	function _register_hooks()
	{
		add_action('plugins_loaded', array(&$this, 'documentor_update_db_check'));
		add_action('wp_footer', array(&$this, 'documentor_custom_styles') );
		load_plugin_textdomain('documentor-lite', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
		if (!shortcode_exists( 'documentor' ) ) add_shortcode('documentor', array(&$this,'shortcode'));
	}
	function install_documentor(){
		global $wpdb, $table_prefix;
		$documentorlite_db_version = DOCUMENTORLITE_VER;
		$installed_ver = get_option( "documentorlite_db_version" );
		if( $installed_ver != $documentorlite_db_version ) {
			$table_name = $table_prefix.DOCUMENTORLITE_TABLE;
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {				
				$sql = "CREATE TABLE $table_name (
					doc_id int(5) NOT NULL AUTO_INCREMENT,
					post_id int(5) NOT NULL,
					UNIQUE KEY doc_id(doc_id)
				);";
				$rs = $wpdb->query($sql);		 
				$wpdb->insert( 
						$table_name, 
						array(
							'doc_id' => 1,
							'post_id' => 0							
						), 
						array( 
							'%d',
							'%d'
						)
					);
			} else { // (If documentor table is already present)			
				if($wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'post_id'") != 'post_id'){
					  $sql = "ALTER TABLE $table_name
						ADD COLUMN post_id INT(5) NOT NULL";
					  $rs5 = $wpdb->query($sql);
					}
				//Update code deleted  
			} // Else (If documentor table is already present)				
			//alter table to change collation of column doc_title : 1.0.1
			$row = $wpdb->get_row("SHOW FULL COLUMNS FROM $table_name LIKE 'doc_title'" );
			$collation = ( isset( $row->Collation ) ) ? $row->Collation : '';
			if( !empty( $collation ) && $collation != 'utf8_general_ci' ) {
				$sql = "ALTER TABLE $table_name
				MODIFY doc_title VARCHAR(50) CHARACTER SET utf8 COLLATE utf8_general_ci";
				$rs5 = $wpdb->query($sql);
			}
			$table_name = $table_prefix.DOCUMENTORLITE_SECTIONS;
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				$sql = "CREATE TABLE $table_name (
							sec_id int(5) NOT NULL AUTO_INCREMENT,
							doc_id int(5) NOT NULL,
							post_id bigint(20) NOT NULL,
							type varchar(50) NOT NULL,
							upvote int(5) NOT NULL,
							downvote int(5) NOT NULL,
							slug varchar(200) NOT NULL,
							UNIQUE KEY sec_id(sec_id)
						);";
				$rs = $wpdb->query($sql);
			}
			//add column for slug in sections table v-1.1
			if($wpdb->get_var("SHOW COLUMNS FROM $table_name LIKE 'slug'") != 'slug') {
				// Add Column
				$sql = "ALTER TABLE $table_name
				ADD COLUMN slug varchar(200) NOT NULL";
				$rs5 = $wpdb->query($sql);
				
				//update slug column of sections table with post_name column from posts table
				$posts_table = $table_prefix."posts";
				$sqlsel = "SELECT * FROM $table_name sec, $posts_table post WHERE sec.post_id = post.ID;";
				$results = $wpdb->get_results($sqlsel);
				if( $wpdb->num_rows > 0 ) {
					$sqlupdate = "UPDATE $table_name
						      SET slug = CASE post_id";
					foreach( $results as $result ) {
						$sqlupdate .= " WHEN $result->ID THEN '$result->post_name'";
					}
					$sqlupdate .= " END;";
					$rs5 = $wpdb->query($sqlupdate);
				}
			}
			update_option( "documentorlite_db_version", $documentorlite_db_version );
			//global setting
			$global_settings = $this->documentor_global_options;
			$global_settings_curr = get_option('documentor_global_options');
			if( !$global_settings_curr ) {
				$global_settings_curr = array();
			}
			foreach($global_settings as $key=>$value) {
				if(!isset($global_settings_curr[$key])) {
					$global_settings_curr[$key] = $value;
				}
			}
			update_option('documentor_global_options',$global_settings_curr);
		}//end of if db version change
	}
	function shortcode( $atts ) {
		$doc_id = isset($atts[0])?$atts[0]:'';
		$id = intVal($doc_id);
		$guide = new DocumentorLiteGuide( $id );
		$html = $guide->view();
		return $html;
	}	
	function include_files() { 
		require_once (dirname (__FILE__) . '/core/includes/fonts.php');
		require_once (dirname (__FILE__) . '/core/admin.php');
		require_once (dirname (__FILE__) . '/core/guide.php');
		require_once (dirname (__FILE__) . '/core/section.php');
		require_once (dirname (__FILE__) . '/core/ajax.php');
		require_once (dirname (__FILE__) . '/core/includes/compat.php');
	}	
	public static function documentor_plugin_url( $path = '' ) {
		return plugins_url( $path, __FILE__ );
	}
	public static function documentor_admin_url( $query = array() ) {
		global $plugin_page;

		if ( ! isset( $query['page'] ) )
			$query['page'] = $plugin_page;

		$path = 'admin.php';

		if ( $query = build_query( $query ) )
			$path .= '?' . $query;

		$url = admin_url( $path );

		return esc_url_raw( $url );
	}
	/* Added for auto update - start */
	function documentor_update_db_check() {
		$documentorlite_db_version = DOCUMENTORLITE_VER;
		if (get_site_option('documentorlite_db_version') != $documentorlite_db_version) {
			$this->install_documentor();
		}
	}
	function create_custom_post() {
		//New Custom Post Type
		$global_settings_curr = get_option('documentor_global_options');
		if( isset( $global_settings_curr['custom_post'] ) && $global_settings_curr['custom_post'] == '1' && !post_type_exists('documentor-sections') ){
			add_action( 'init', array( &$this, 'section_post_type'), 11);
			//add filter to ensure the text Sections, or Section, is displayed when user updates a Section 
			add_filter('post_updated_messages', array( &$this, 'section_updated_messages') );
		} //if custom_post is true
		if(!post_type_exists('guide')){		
		  add_action( 'init', array( &$this, 'guide_post_type'), 11);
		}
	}
	function section_post_type() {
		$labels = array(
		'name' => _x('Sections', 'post type general name'),
		'singular_name' => _x('Section', 'post type singular name'),
		'add_new' => _x('Add New', 'documentor'),
		'add_new_item' => __('Add New Documentor Section'),
		'edit_item' => __('Edit Documentor Section'),
		'new_item' => __('New Documentor Section'),
		'all_items' => __('All Documentor Sections'),
		'view_item' => __('View Documentor Section'),
		'search_items' => __('Search Documentor Sections'),
		'not_found' =>  __('No Documentor sections found'),
		'not_found_in_trash' => __('No Documentor section found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'Sections'
		);
		$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'show_in_menu' => false,
		'show_in_nav_menus' => false, 
		'query_var' => true,
		'rewrite' => array('slug' => 'section'),
		'capability_type' => 'post',
		'has_archive' => false, 
		'hierarchical' => false,
		'can_export' => true,
		'menu_position' => null,
		'supports' => array('title','editor','thumbnail','excerpt','custom-fields')
		); 
		register_post_type('documentor-sections',$args);
	}
	function guide_post_type() {
		$labels = array(
		'name' => _x('Guides', 'post type general name'),
		'singular_name' => _x('Guide', 'post type singular name'),
		'add_new' => _x('Add New', 'documentor'),
		'add_new_item' => __('Add New Documentor Guide'),
		'edit_item' => __('Edit Documentor Guide'),
		'new_item' => __('New Documentor Guide'),
		'all_items' => __('All Documentor Guides'),
		'view_item' => __('View Documentor Guide'),
		'search_items' => __('Search Documentor Guides'),
		'not_found' =>  __('No Documentor guides found'),
		'not_found_in_trash' => __('No Documentor guides found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'Guides'
		);
		$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => false, 
		'show_in_menu' => false, 
		'show_in_nav_menus' => false,
		'query_var' => true,
		'rewrite' => array('slug' => 'guide'),
		'capability_type' => 'post',
		'has_archive' => true, 
		'hierarchical' => false,
		'menu_position' => null,
		'can_export' => true,
		'supports' => array('title','editor','thumbnail','excerpt','custom-fields')
		); 
		register_post_type('guide',$args); //ver1.4 end
	}
	function section_updated_messages( $messages ) {
		global $post, $post_ID;
		$messages['document'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Documentor Section updated. <a href="%s">View Documentor section</a>'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __('Documentor Section updated.'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Documentor section restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Documentor Section published. <a href="%s">View Documentor section</a>'), esc_url( get_permalink($post_ID) ) ),
		7 => __('Section saved.'),
		8 => sprintf( __('Documentor Section submitted. <a target="_blank" href="%s">Preview Documentor Section</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('Documentor Sections scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Documentor Section</a>'),
		  // translators: Publish box date format, see http://php.net/date
		  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Documentor Section draft updated. <a target="_blank" href="%s">Preview Documentor Section</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
		return $messages;
	}
	function documentor_custom_styles() {
		global $doclite_customstyles;
		if( !isset( $doclite_customstyles ) or $doclite_customstyles < 1 ) {
			$global_curr = get_option('documentor_global_options');
			if( !empty( $global_curr['custom_styles'] ) ) {  ?>
				<style type="text/css"><?php echo $global_curr['custom_styles'];?></style>
			<?php }
			$doclite_customstyles++;
		}
	}
}

if(!function_exists('get_documentor')){
	function get_documentor( $id=0 ) {
		$guide = new DocumentorLiteGuide( $id );
		$html = $guide->view();
		echo $html;
	}
}
if( class_exists( 'DocumentorLite' ) ) {
  $cn = new DocumentorLite();
  // Register for activation
  register_activation_hook( __FILE__, array( &$cn, 'install_documentor') );
}
?>
