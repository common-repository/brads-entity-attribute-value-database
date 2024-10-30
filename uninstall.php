<?php 
	global $wpdb;

	remove_shortcode('eva_tbl');
	remove_shortcode('eav_startadd');
	remove_shortcode('eav_endadd');
	remove_shortcode('eav_add');
	remove_shortcode('eav_field');
	remove_shortcode('eav_subrec');
	remove_shortcode('eav_apache');

	//execute the query deleting the table
	$sql = "DROP TABLE " . $wpdb->base_prefix . "eav_entity;";
	$wpdb->query($sql);
	$wpdb->show_errors();
	$wpdb->flush();
	
	$sql = "DROP TABLE " . $wpdb->base_prefix . "eav_attrib;";
	$wpdb->query($sql);
	$wpdb->show_errors();
	$wpdb->flush();
	
	$sql = "DROP TABLE " . $wpdb->base_prefix . "eav_tbl;";
	$wpdb->query($sql);
	$wpdb->show_errors();
	$wpdb->flush();
	
	$sql = "DROP TABLE " . $wpdb->base_prefix . "eav_layout;";
	$wpdb->query($sql);
	$wpdb->show_errors();
	$wpdb->flush();
	
	remove_menu_page( 'eav_main_menu'); 
?>
