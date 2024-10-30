<?php
/*
Plugin Name: Multi-column Tag Map
Plugin URI: https://wordpress.org/plugins/multi-column-tag-map/
Description: Multi-column Tag Map displays a columnized and alphabetical (English) listing of all tags used in your site similar to the index pages of a book.
Version: 17.0.33
Author: Alan Jackson
Author URI: http://mctagmap.tugbucket.net
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/


// **************************
//
// Long code removed completely as of version 10.0.1 - it was deprecated as of version 4.0
//
// **************************

// the JS and CSS

	/*
    if (get_template_directory() === get_stylesheet_directory()) {
		echo parse_url(get_stylesheet_directory_uri(), PHP_URL_PATH);
    } else {
		echo parse_url(get_stylesheet_directory_uri(), PHP_URL_PATH);
    }
	*/

/* load the PHP*/
function sc_mcTagMap($atts, $content = null) {
	if(!is_admin()){
		$mctagmapCSSpath = $_SERVER['DOCUMENT_ROOT'].parse_url(get_stylesheet_directory_uri(), PHP_URL_PATH);
		if(file_exists($mctagmapCSSpath.'/multi-column-tag-map/mctagmap_functions.php')){
			include($mctagmapCSSpath.'/multi-column-tag-map/mctagmap_functions.php');
		} else {
			include('mctagmap_functions.php');
		}
		static $mctmcounter = 0;
		/* echo ++$mctmcounter; */
		++$mctmcounter;
		return str_replace('-mctmcounter-',$mctmcounter.'-',$list);
	}
}
add_shortcode("mctagmap", "sc_mcTagMap");
// end shortcode

/* admin page */
add_action( 'admin_menu', 'sc_mcTagMap_menu' );
function sc_mcTagMap_menu() {
    add_options_page( 'MC Tag Map', 'MC Tag Map', 'manage_options', 'mctm-options', 'sc_mcTagMap_plugin_options' );
}
function sc_mcTagMap_plugin_options() {
    if ( !current_user_can( 'manage_options' ) )  {
        wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
    }
    include dirname(__FILE__)."/mctagmap-options.php";
} 




function mctagmap_donate($links, $file) {
$plugin = plugin_basename(__FILE__);
// create link
if ($file == $plugin) {
return array_merge( $links, array( sprintf( '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=GX8RH7F2LR74J" target="_blank">Donate to mctagmap development</a>', $plugin, __('Donate') ) ));
}
return $links;
}
add_filter( 'plugin_row_meta', 'mctagmap_donate', 10, 2 );




/* show the excerpt outside of the loop */
function mctm_get_the_excerpt_here($post_id){
  	global $wpdb;
 	$query = "SELECT post_excerpt FROM $wpdb->posts WHERE ID = $post_id LIMIT 1";
 	$result = $wpdb->get_results($query, ARRAY_A);
  	return $result[0]['post_excerpt'];
}


/* Page Excerpt by: Jeremy Massel */
//add_action( 'edit_page_form', 'mctm_pe_add_box');
add_action('init', 'mctm_pe_init');

function mctm_pe_init() {
	if(function_exists("add_post_type_support")){ //support 3.1 and greater
		add_post_type_support( 'page', 'excerpt' );
	}
}
function mctm_pe_page_excerpt_meta_box($post) {
?>
<label class="hidden" for="excerpt"><?php _e('Excerpt') ?></label><textarea rows="1" cols="40" name="excerpt" tabindex="6" id="excerpt"><?php echo $post->post_excerpt ?></textarea>
<p><?php _e('Excerpts are optional hand-crafted summaries of your content. You can <a href="http://codex.wordpress.org/Template_Tags/the_excerpt" target="_blank">use them in your template</a>'); ?></p>
<?php
}

function mctm_pe_add_box()
{
	if(!function_exists("add_post_type_support")) //legacy
	{		add_meta_box('postexcerpt', __('Page Excerpt'), 'mctm_pe_page_excerpt_meta_box', 'page', 'advanced', 'core');
	}
}
/* END - Page Excerpt by: Jeremy Massel */

// overwrite single_tag_title()
add_filter('single_tag_title', 'mctagmap_single_tag_title', 1, 2);
function mctagmap_single_tag_title($prefix = '', $display = false) {
	global $wp_query;
	if ( !is_tag() )
		return;

	$tag = $wp_query->get_queried_object();

	if ( ! $tag )
		return;

	$my_tag_name = str_replace('|', '', $tag->name);
	if ( !empty($my_tag_name) ) {
		if ( $display)
			echo $prefix . $my_tag_name;
		else
			return $my_tag_name;
	}
}

// overwrite single_tag_title()
add_filter('the_tags', 'mctagmap_the_tags');
function mctagmap_the_tags($mctagmapTheTags) {
    return str_replace('|', '', $mctagmapTheTags);
}

/* modify wp_query */
/* https://wordpress.stackexchange.com/questions/108288/how-to-return-only-certain-fields-using-get-posts */
function get_posts_fields_mctm231( $args = array() ) {
  $valid_fields = array(
    'ID'=>'%d', 'post_author'=>'%d',
    'post_type'=>'%s', 'post_mime_type'=>'%s',
    'post_title'=>false, 'post_name'=>'%s', 
    'post_date'=>'%s', 'post_modified'=>'%s',
    'menu_order'=>'%d', 'post_parent'=>'%d', 
    'post_excerpt'=>false, 'post_content'=>false,
    'post_status'=>'%s', 'comment_status'=>false, 'ping_status'=>false,
    'to_ping'=>false, 'pinged'=>false, 'comment_count'=>'%s'
  );
  $defaults = array(
    'post_type' => 'post',
    'post_status' => 'publish',
    'orderby' => 'post_date',
    'order' => 'DESC',
    'posts_per_page' => get_option('posts_per_page'),
  );
  global $wpdb;
  $args = wp_parse_args($args, $defaults);
  $where = "";
  foreach ( $valid_fields as $field => $can_query ) {
    if ( isset($args[$field]) && $can_query ) {
      if ( $where != "" )  $where .= " AND ";
      $where .= $wpdb->prepare( $field . " = " . $can_query, $args[$field] );
    }
  }
  if ( isset($args['search']) && is_string($args['search']) ) {
      if ( $where != "" )  $where .= " AND ";
      $where .= $wpdb->prepare("post_title LIKE %s", "%" . $args['search'] . "%");
  }
  if ( isset($args['include']) ) {
     if ( is_string($args['include']) ) $args['include'] = explode(',', $args['include']); 
     if ( is_array($args['include']) ) {
      $args['include'] = array_map('intval', $args['include']); 
      if ( $where != "" )  $where .= " OR ";
      $where .= "ID IN (" . implode(',', $args['include'] ). ")";
    }
  }
  if ( isset($args['exclude']) ) {
     if ( is_string($args['exclude']) ) $args['exclude'] = explode(',', $args['exclude']); 
     if ( is_array($args['exclude']) ) {
      $args['exclude'] = array_map('intval', $args['exclude']);
      if ( $where != "" ) $where .= " AND "; 
      $where .= "ID NOT IN (" . implode(',', $args['exclude'] ). ")";
    }
  }
  extract($args);
  $iscol = false;
  if ( isset($fields) ) { 
    if ( is_string($fields) ) $fields = explode(',', $fields);
    if ( is_array($fields) ) {
      $fields = array_intersect($fields, array_keys($valid_fields)); 
      if( count($fields) == 1 ) $iscol = true;
      $fields = implode(',', $fields);
    }
  }
  if ( empty($fields) ) $fields = '*';
  if ( ! in_array($orderby, $valid_fields) ) $orderby = 'post_date';
  if ( ! in_array( strtoupper($order), array('ASC','DESC')) ) $order = 'DESC';
  if ( ! intval($posts_per_page) && $posts_per_page != -1)
     $posts_per_page = $defaults['posts_per_page'];
  if ( $where == "" ) $where = "1";
  $q = "SELECT $fields FROM $wpdb->posts WHERE " . $where;
  $q .= " ORDER BY $orderby $order";
  if ( $posts_per_page != -1) $q .= " LIMIT $posts_per_page";
  return $iscol ? $wpdb->get_col($q) : $wpdb->get_results($q);
}
?>