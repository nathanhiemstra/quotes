<?php
/**
 * Plugin Name: Quotes Migration Taxonomies
 * Description: Registers migration taxonomies required before importing the Quotes WXR file.
 */

add_action('init', function () {
    register_taxonomy('quote_author', ['post'], [
        'labels' => [
            'name' => 'Quote Authors',
            'singular_name' => 'Quote Author',
            'search_items' => 'Search Quote Authors',
            'all_items' => 'All Quote Authors',
            'edit_item' => 'Edit Quote Author',
            'update_item' => 'Update Quote Author',
            'add_new_item' => 'Add New Quote Author',
            'new_item_name' => 'New Quote Author Name',
            'menu_name' => 'Quote Authors',
        ],
        'public' => true,
        'hierarchical' => false,
        'show_admin_column' => true,
        'show_in_rest' => true,
        'meta_box_cb' => false,
        'rewrite' => ['slug' => 'author'],
    ]);

    add_rewrite_rule('^author/([^/]+)/?$', 'index.php?quote_author=$matches[1]', 'top');
});
