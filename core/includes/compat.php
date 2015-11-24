<?php 
//Added for : TablePress tables not appearing in generated PDF
add_action( 'init', 'doclite_load_tablepress_in_the_admin', 11 );
function doclite_load_tablepress_in_the_admin() {
	if ( defined( 'DOING_AJAX' ) && DOING_AJAX && class_exists('TablePress')) {
	    TablePress::$controller = TablePress::load_controller( 'frontend' );
	}
}
//Added 1.4 : Crayon Syntax Highlighter Compatability
add_filter('guide_html', 'doclite_add_crayons_to_guide_html');
function doclite_add_crayons_to_guide_html($content) {
	if(class_exists('CrayonWP')) {
		return CrayonWP::highlight($content);
	}
	else {
		return $content;
	}
}
?>