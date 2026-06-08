<?php
/**
 * Plugin Name: Quotes Admin Columns
 * Description: Customizes the Posts list table for quote management.
 */

add_filter('manage_post_posts_columns', function ($columns) {
    unset($columns['author'], $columns['categories'], $columns['tags'], $columns['comments']);

    $new_columns = [];

    foreach ($columns as $key => $label) {
        if ('date' === $key) {
            $new_columns['quotes_date'] = 'Date';
            continue;
        }

        $new_columns[$key] = $label;

        if ('title' === $key) {
            $new_columns['quotes_context'] = 'Context';
            $new_columns['quotes_dialog'] = 'Dialog';
        }
    }

    return $new_columns;
}, 20);

add_action('manage_post_posts_custom_column', function ($column, $post_id) {
    if ('quotes_context' === $column) {
        $context = trim((string) get_post_field('post_excerpt', $post_id));
        echo '' !== $context ? esc_html(wp_trim_words($context, 18)) : '<span aria-hidden="true">—</span>';
        return;
    }

    if ('quotes_dialog' === $column) {
        $rows = function_exists('quotes_get_repeater_rows') ? quotes_get_repeater_rows($post_id) : [];
        $count = is_array($rows) ? count(array_filter($rows, function ($row) {
            return '' !== trim((string) ($row['quote'] ?? '')) || '' !== trim((string) ($row['author'] ?? ''));
        })) : 0;

        if ($count > 0) {
            printf('<strong>Yes</strong> <span aria-label="%1$d dialog rows">(%1$d)</span>', (int) $count);
            return;
        }

        echo '<span aria-hidden="true">—</span>';
        return;
    }

    if ('quotes_date' === $column) {
        $status = get_post_status($post_id);
        $date = get_the_date('M j, Y', $post_id);

        if ('publish' !== $status) {
            printf(
                '<span class="post-state">%s</span><br>',
                esc_html(get_post_status_object($status)->label ?? ucfirst((string) $status))
            );
        }

        echo esc_html($date);
    }
}, 10, 2);

add_filter('manage_edit-post_sortable_columns', function ($columns) {
    unset($columns['author'], $columns['categories'], $columns['tags'], $columns['comments']);
    $columns['quotes_date'] = 'date';

    return $columns;
}, 20);

add_filter('edit_posts_per_page', function ($posts_per_page, $post_type) {
    return 'post' === $post_type ? 500 : $posts_per_page;
}, 10, 2);

add_action('admin_head-edit.php', function () {
    $screen = get_current_screen();

    if (! $screen || 'edit-post' !== $screen->id) {
        return;
    }
    ?>
    <style>
        .column-quotes_context {
            width: 24%;
        }

        .column-quotes_dialog {
            width: 8em;
        }
    </style>
    <?php
});
