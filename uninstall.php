<?php 
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}
delete_option('documentorlite_db_version');
if( !defined( 'DOCUMENTOR_PLUGIN_BASENAME' ) ) {
	global $wpdb, $table_prefix;
	$documentor_table = $table_prefix.'documentor';
	$sections_table = $table_prefix.'documentor_sections';
	$sql = "DROP TABLE $documentor_table;";
	$wpdb->query($sql);
	$sql = "DROP TABLE $sections_table;";
	$wpdb->query($sql);
}
?>
