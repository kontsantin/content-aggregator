<?php
/**
 * Plugin Name: Content Aggregator
 * Plugin URI: http://example.com/content-aggregator
 * Description: Простой плагин для парсинга и постинга контента с других сайтов.
 * Version: 1.0
 * Author: Ваше Имя
 * Author URI: http://example.com
 */

// Подключение файлов
require_once plugin_dir_path(__FILE__) . 'includes/load-html.php';
require_once plugin_dir_path(__FILE__) . 'includes/parse-content.php';
require_once plugin_dir_path(__FILE__) . 'includes/create-post.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';

// Регистрация кастомного типа постов и таксономии
function custom_news_post_type() {
    $labels = array(
        'name'               => 'News',
        'singular_name'      => 'News',
        'menu_name'          => 'News',
        'name_admin_bar'     => 'News',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New News',
        'new_item'           => 'New News',
        'edit_item'          => 'Edit News',
        'view_item'          => 'View News',
        'all_items'          => 'All News',
        'search_items'       => 'Search News',
        'parent_item_colon'  => 'Parent News:',
        'not_found'          => 'No news found.',
        'not_found_in_trash' => 'No news found in Trash.'
    );

    $args = array(
        'labels'             => $labels,
        'description'        => 'News posts for the site',
        'public'             => true,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-welcome-widgets-menus',
        'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
        'has_archive'        => true,
        'rewrite'            => array( 'slug' => 'news' ),
        'taxonomies'         => array( 'region' ),
    );

    register_post_type( 'news', $args );
}
add_action( 'init', 'custom_news_post_type' );

function register_region_taxonomy() {
    $labels = array(
        'name'                       => 'Regions',
        'singular_name'              => 'Region',
        'search_items'               => 'Search Regions',
        'all_items'                  => 'All Regions',
        'edit_item'                  => 'Edit Region',
        'update_item'                => 'Update Region',
        'add_new_item'               => 'Add New Region',
        'new_item_name'              => 'New Region Name',
        'menu_name'                  => 'Regions',
        'not_found'                  => 'No regions found',
        'popular_items'              => 'Popular Regions',
        'separate_items_with_commas' => 'Separate regions with commas',
        'add_or_remove_items'        => 'Add or remove regions',
        'choose_from_most_used'      => 'Choose from the most used regions',
        'back_to_items'              => 'Back to Regions',
    );

    $args = array(
        'labels'            => $labels,
        'public'            => true,
        'show_in_nav_menus' => true,
        'show_admin_column' => true,
        'hierarchical'      => true,
        'rewrite'           => array( 'slug' => 'region' ),
    );

    register_taxonomy( 'region', 'news', $args );
}
add_action( 'init', 'register_region_taxonomy' );
?>