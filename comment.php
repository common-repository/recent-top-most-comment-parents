<?php 
/*
  Plugin Name: Recent Top Most Comment Parents
  Plugin URI: https://facebook.com/libin.prasanth.7
	Tags: comment,comment parent
	Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5X2LYVG55XWQA&source=url
  Description: This plugin will return recently created comment parent.
  Version: 1.0
  Author: Libin Prasanth
  Author URI: https://www.linkedin.com/in/libinprasanth/
	
  License: GPLv2+
  Text Domain: comment-parent
*/ 
if ( ! defined( 'ABSPATH' ) ) exit;
define('TMCP_DIR_URL', plugin_dir_url( __FILE__ ).'');
define('TMCP_DIR_PATH', dirname(__FILE__));

/** 
 * Create comment field when plugin activate 
 **/
function tmcp_comment_activate() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . "comments"; 
  $row = $wpdb->get_results( "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name = '$table_name' AND column_name = 'comment_top_parent'"  );
	  
	if(empty($row)){
		$wpdb->query("ALTER TABLE $table_name ADD comment_top_parent BIGINT(6) NOT NULL DEFAULT 0");
	}
}
 
register_activation_hook( __FILE__, 'tmcp_comment_activate' );	
/** 
 * Update top most parent 
 **/
function tmcp_update_comment_top_parent( $comment_ID, $commentdata ) {
	global $wpdb;
	$table_name = $wpdb->prefix . "comments"; 
	
	$comment = get_comment( $comment_ID );
	$comment_parent = $comment->comment_parent; 
	if($comment_parent != 0){
		$comment_parent = tmcp_get_top_most_parent($comment_parent); 
	} 
	if($comment_parent == 0){
		$comment_parent = $comment_ID;
	}  
	
	$wpdb->query("UPDATE $table_name SET comment_top_parent='$comment_parent' WHERE comment_ID=$comment_ID");
}

add_action( 'comment_post', 'tmcp_update_comment_top_parent', 11, 2 );
/** 
 * Get top most parent 
 **/
function tmcp_get_top_most_parent( $parent ) {
	// Start from the current term
	$old_comment = $parent;
	$comment  = get_comment( $parent );
	$parent   = $comment->comment_parent;
  
	// Climb up the hierarchy until we reach a term with parent = '0'
	while ( $parent != '0' ) { 
		$comment     = get_comment( $parent );
		$parent      = $comment->comment_parent;
		if(empty($parent)){
			$parent = 0;
		}
		if($parent != '0'){
			$old_comment = $comment->comment_parent; 
		} else {
			$old_comment = $comment->comment_top_parent; 
		}
	}  
	
	return $old_comment;
}
/** 
 * Get top most comment 
 **/
function tmcp_get_comments($post_id, $count = 3){ 
	global $wpdb;
	$table_name = $wpdb->prefix . "comments"; 
	
	$cmds = $wpdb->get_results( "SELECT comment_top_parent FROM `$table_name` WHERE `comment_post_ID`=$post_id AND `comment_approved`=1 ORDER BY `comment_date` DESC", ARRAY_A); 
	// $count
	$comments = array();
	$cmds = array_map("unserialize", array_unique(array_map("serialize", $cmds)));
	$cmds = array_slice($cmds, 0, $count); 
	
	if(count($cmds) > 0){
		foreach($cmds as $c){ 
			$cid = $c['comment_top_parent'];
			$cmds = $wpdb->get_results( "SELECT * FROM `$table_name` WHERE `comment_ID`=$cid");
			if(count($cmds) > 0){
				array_push($comments, $cmds[0]);
			} 
		}
	}
	return $comments;
}