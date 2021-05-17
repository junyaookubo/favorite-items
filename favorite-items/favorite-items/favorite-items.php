<?php
/**
 * Plugin Name: Favorite Items
 * Description: Favorite Itemsは、ユーザーがお気に入り商品を登録できるプラグインです.
 * Version: 1.0.0
 * Author: JUNYA AKAHORI - World Utility Inc.
 */

/*
Favorite Items is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Favorite Items is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Favorite Items.
*/


global $jal_db_version;
$jal_db_version = '1.0';

global $favorite_page_slug;
$favorite_page_slug = 'favorite';

////////////////////////////////////////////////////////////
// Function is run when plugin's activate
////////////////////////////////////////////////////////////

function jal_install() {
	global $wpdb;
	global $jal_db_version;

	$table_name = $wpdb->prefix . 'favorite_items';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id mediumint(9),
		time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
		item varchar(55) NOT NULL,
		user varchar(55) NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'jal_db_version', $jal_db_version );
}

// The function that we delete Pluain
function delete_plugin_database_table(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'favorite_items';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
}

// Create favorite archive page in WP
function create_archive_page(){
    global $favorite_page_slug;
    if( empty(get_page_by_path($favorite_page_slug)) ){
        wp_insert_post(array(
            'post_title'    => 'お気に入り一覧',
            'post_name'     => 'favorite',
            'post_status'   => 'publish',
            'post_type'     => 'page'
        ));
    }
}

// Delete favorite archive page in WP
function delete_archive_page(){
    global $favorite_page_slug;
    if( !empty(get_page_by_path($favorite_page_slug)) ){
        wp_delete_post(get_page_by_path($favorite_page_slug)->ID , true);
    }
}


// Activate Plugin
register_activation_hook( __FILE__, 'jal_install' );
register_activation_hook( __FILE__, 'create_archive_page' );

// Delete Plugin
register_uninstall_hook(__FILE__, 'delete_plugin_database_table');
register_uninstall_hook(__FILE__, 'delete_archive_page');


////////////////////////////////////////////////////////////
// Content of favorite archive page
////////////////////////////////////////////////////////////
function add_favorite_page_content($content) {
    global $post, $favorite_page_slug, $html, $wpdb, $usces, $archive_sql, $favorite_post, $member_id;
    $table_name = $wpdb->prefix . 'favorite_items';
    $member_id = usces_memberinfo('ID', 'return');

    $html = '';

    if( $post->post_name == $favorite_page_slug ){
        $html = '<div class="favorite-item-archive">';
        $html .= '<div class="wrap">';


        $archive_sql = "SELECT post_id FROM $table_name WHERE user = %d ORDER BY time DESC";
        $result = $wpdb->get_results($wpdb->prepare($archive_sql, $member_id));
        if( $result ): 
            $html .= '<ul class="favorite-item-list">';
            foreach( $result as $favorite_id ):
                $favorite_id = $favorite_id->post_id;
                $favorite_post = get_post($favorite_id);
                usces_the_item();
                $img = usces_the_itemImageURL( 0, 'return', $favorite_post );
                if($img == ''){
                    $img = add_customize_favorite_no_img();
                }
                $html .= '<li class="favorite-item-list-li">';
                $html .= '<a href="'.get_the_permalink($favorite_id).'">';
                $html .= '<div class="img">';
                $html .= '<img src="'.$img.'" alt="'.get_the_title($favorite_id).'"/>';
                $html .= '</div>';
                $html .= '<div class="text-box">';
                $html .= '<h4 class="item-name">'.get_the_title($favorite_id).'</h4>';
                $html .= '<p class="price">'.usces_the_firstPriceCr('return', $favorite_post).' '.usces_guid_tax('return').'</p>';
                $html .= '<div class="excerpt">'.$favorite_post->post_excerpt.'</div>';
                $html .= '</div>';
                $html .= '</a>';
                $html .= '</li>';
            endforeach;
            $html .= '</ul>';
        else:
            $html .= '<p class="text" style="text-align:center;">お気に入りの商品が登録されていません。</p>';
        endif;
        $html .= '</div>';
        $html .= '</div>';

        return $html .= $content;
    }
    return $content;
}
add_filter('the_content', 'add_favorite_page_content');


////////////////////////////////////////////////////////////
// Filter Hook
////////////////////////////////////////////////////////////
function add_customize_favorite_no_img(){
    $img = apply_filters('customize_favorite_no_img', $url);
    return $img;
}
function change_favorite_no_img($url){
    $url = plugins_url( '/public/img/noimage.jpg', __FILE__ );
    return $url;
}
add_filter('customize_favorite_no_img', 'change_favorite_no_img');

////////////////////////////////////////////////////////////
// Add CSS
////////////////////////////////////////////////////////////
add_action('wp_enqueue_scripts','add_favorite_css');
function add_favorite_css() {
    wp_enqueue_style( 'favorite-items', plugins_url( '/public/css/favorite-items.css', __FILE__ ));
}


////////////////////////////////////////////////////////////
// Add JS
////////////////////////////////////////////////////////////
add_action('wp_enqueue_scripts','add_favorite_js');
function add_favorite_js() {
    global $usces;
    wp_enqueue_script( 'favorite-items', plugins_url( '/public/js/favorite-items.js', __FILE__ ), array(), false, true);
    wp_localize_script( 'favorite-items', 'usces_favorite_array', array('ID' => usces_memberinfo('ID', 'return')));
}



////////////////////////////////////////////////////////////
// Creat Favorite Btn Shortcode
////////////////////////////////////////////////////////////

function display_favorite_btn(){
    global $btn_html;
    if( check_db() ){
        $btn_html .= '<div class="favorite-btn-wrap already">';
    }else{
        $btn_html .= '<div class="favorite-btn-wrap">';
    }
    $btn_html .= '<button class="favorite-btn fav-in"><i class="fa fa-star"></i>お気に入りに登録する</button>';
    $btn_html .= '<button class="favorite-btn fav-out"><i class="fa fa-trash"></i>お気に入りから削除する</button>';
    $btn_html .= '</div>';
    if(usces_is_login()){
        return $btn_html;
    }else{
        $btn_html = '';
        return $btn_html;
    }
}
add_shortcode('favorite-btn','display_favorite_btn');


////////////////////////////////////////////////////////////
// Check wether the item exists in database
////////////////////////////////////////////////////////////
function check_db(){
    global $wpdb, $post;
    $table_name = $wpdb->prefix . 'favorite_items';
    if( $wpdb->query($wpdb->prepare("SELECT * FROM $table_name WHERE item = %s AND user = %d", $post->post_title, usces_memberinfo('ID', 'return'))) ){
        // EXISTS
        return true;
    }else{
        // NOT EXISTS
        return false;
    }
}


////////////////////////////////////////////////////////////
// Ajax
////////////////////////////////////////////////////////////

function ajax_favorite(){

    global $wpdb, $favorite_item, $favorite_user;

    $favorite_item = get_the_title($_POST['post_id']);
    $favorite_user = $_POST['member_id'];

    $table_name = $wpdb->prefix . 'favorite_items';

    if($_POST['type'] == 'fav-in'){
        $insert_sql = "INSERT INTO $table_name ( post_id , item , user ) SELECT %d , %s , %d FROM DUAL WHERE 0 = ( SELECT COUNT(*) FROM $table_name WHERE $table_name.item = %s AND $table_name.user = %d )";
        if( $wpdb->query($wpdb->prepare($insert_sql, $_POST['post_id'], $favorite_item, $favorite_user, $favorite_item, $favorite_user)) == FALSE ){
            echo 'insert_error';
        }else{
            echo 'insert_success';
        }
    }else if($_POST['type'] == 'fav-out'){
        $delete_sql = "DELETE FROM $table_name WHERE $table_name.item = %s AND $table_name.user = %d";
        $wpdb->query($wpdb->prepare($delete_sql, $favorite_item, $favorite_user));
        echo 'delete_success';
    }

    die();
}
add_action( 'wp_ajax_nopriv_ajax_favorite', 'ajax_favorite' );
add_action( 'wp_ajax_ajax_favorite', 'ajax_favorite' );