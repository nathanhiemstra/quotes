<?php
/**
 * Plugin Name: Quotes ACF Fields
 * Description: Registers local ACF fields for quote dialog rows and author display settings.
 */

add_action('acf/init', function () {
    if (! function_exists('acf_add_local_field_group')) {
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_quotes_quote_details',
        'title' => 'Quote Details',
        'fields' => [
            [
                'key' => 'field_quotes_main_author',
                'label' => 'Main Author',
                'name' => 'main_author',
                'type' => 'taxonomy',
                'taxonomy' => 'quote_author',
                'field_type' => 'select',
                'allow_null' => 1,
                'add_term' => 1,
                'save_terms' => 0,
                'load_terms' => 0,
                'return_format' => 'id',
                'ui' => 1,
                'required' => 0,
            ],
            [
                'key' => 'field_quotes_context',
                'label' => 'Context',
                'name' => 'quote_context',
                'type' => 'textarea',
                'rows' => 3,
                'new_lines' => '',
                'required' => 0,
            ],
            [
                'key' => 'field_quotes_context_position',
                'label' => 'Context Position',
                'name' => 'quote_context_position',
                'type' => 'radio',
                'choices' => [
                    'above' => 'Above Quote',
                    'below' => 'Below Quote',
                ],
                'default_value' => 'below',
                'layout' => 'horizontal',
                'return_format' => 'value',
                'required' => 0,
            ],
            [
                'key' => 'field_quotes_dialog',
                'label' => 'Dialog',
                'name' => 'quote_and_name',
                'type' => 'repeater',
                'instructions' => 'Additional quote/author lines that appear below the main quote.',
                'required' => 0,
                'layout' => 'block',
                'button_label' => 'Add dialog line',
                'sub_fields' => [
                    [
                        'key' => 'field_quotes_dialog_quote',
                        'label' => 'Quote',
                        'name' => 'quote',
                        'type' => 'textarea',
                        'rows' => 3,
                        'new_lines' => '',
                        'required' => 0,
                    ],
                    [
                        'key' => 'field_quotes_dialog_author',
                        'label' => 'Author',
                        'name' => 'quote_author',
                        'type' => 'taxonomy',
                        'taxonomy' => 'quote_author',
                        'field_type' => 'select',
                        'allow_null' => 1,
                        'add_term' => 1,
                        'save_terms' => 0,
                        'load_terms' => 0,
                        'return_format' => 'id',
                        'ui' => 1,
                        'required' => 0,
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'post',
                ],
            ],
        ],
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ]);

    acf_add_local_field_group([
        'key' => 'group_quotes_author_display',
        'title' => 'Author Display',
        'fields' => [
            [
                'key' => 'field_quotes_author_display_mode',
                'label' => 'Display Name',
                'name' => 'quotes_author_display_mode',
                'type' => 'radio',
                'choices' => [
                    'full_name' => 'Full Name',
                    'first_l' => 'First L.',
                    'initials' => 'F.L.',
                    'anonymous' => 'Anonymous',
                ],
                'default_value' => 'first_l',
                'layout' => 'horizontal',
                'return_format' => 'value',
                'required' => 1,
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'taxonomy',
                    'operator' => '==',
                    'value' => 'quote_author',
                ],
            ],
        ],
        'position' => 'normal',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'active' => true,
    ]);
});

add_filter('acf/load_value/key=field_quotes_context', function ($value, $post_id) {
    if ('' !== (string) $value || ! is_numeric($post_id)) {
        return $value;
    }

    return (string) get_post_field('post_excerpt', (int) $post_id);
}, 10, 2);

add_filter('acf/load_value/key=field_quotes_context_position', function ($value, $post_id) {
    if ('' !== (string) $value || ! is_numeric($post_id)) {
        return $value;
    }

    return get_post_meta((int) $post_id, '_quote_context_position', true) ?: 'below';
}, 10, 2);

add_filter('acf/load_value/key=field_quotes_main_author', function ($value, $post_id) {
    if (! empty($value) || ! is_numeric($post_id)) {
        return $value;
    }

    $main_author = trim((string) get_post_meta((int) $post_id, '_legacy_post_author_display_name', true));

    if ('' === $main_author) {
        return $value;
    }

    $term = term_exists($main_author, 'quote_author');

    if (! $term) {
        $term = wp_insert_term($main_author, 'quote_author', [
            'slug' => sanitize_title($main_author),
        ]);
    }

    if (is_wp_error($term)) {
        return $value;
    }

    return (int) (is_array($term) ? $term['term_id'] : $term);
}, 10, 2);

add_filter('acf/load_value/key=field_quotes_dialog', function ($value, $post_id) {
    if (! empty($value) || ! is_numeric($post_id)) {
        return $value;
    }

    if (function_exists('quotes_get_repeater_rows')) {
        $legacy_rows = quotes_get_repeater_rows((int) $post_id);
    } else {
        $legacy_rows = json_decode((string) get_post_meta((int) $post_id, '_quotes_repeater_rows', true), true);
    }

    if (empty($legacy_rows) || ! is_array($legacy_rows)) {
        return $value;
    }

    $rows = [];

    foreach ($legacy_rows as $row) {
        $quote = trim((string) ($row['quote'] ?? ''));
        $author = trim((string) ($row['author'] ?? ''));
        $author_slug = sanitize_title((string) ($row['author_slug'] ?? ''));
        $term_id = 0;

        if ('' !== $author_slug) {
            $term = get_term_by('slug', $author_slug, 'quote_author');
            $term_id = $term && ! is_wp_error($term) ? (int) $term->term_id : 0;
        }

        if ($term_id <= 0 && '' !== $author) {
            $term = term_exists($author, 'quote_author');

            if (! $term) {
                $term = wp_insert_term($author, 'quote_author', [
                    'slug' => sanitize_title($author),
                ]);
            }

            if (! is_wp_error($term)) {
                $term_id = (int) (is_array($term) ? $term['term_id'] : $term);
            }
        }

        if ('' === $quote && '' === $author) {
            continue;
        }

        $rows[] = [
            'field_quotes_dialog_quote' => $quote,
            'field_quotes_dialog_author' => $term_id ?: '',
        ];
    }

    return $rows ?: $value;
}, 10, 2);

add_action('acf/save_post', function ($post_id) {
    if (! function_exists('get_field') || ! is_numeric($post_id) || 'post' !== get_post_type((int) $post_id)) {
        return;
    }

    $post_id = (int) $post_id;
    $main_author_term_id = isset($_POST['acf']['field_quotes_main_author']) ? (int) $_POST['acf']['field_quotes_main_author'] : 0;
    $context = isset($_POST['acf']['field_quotes_context']) ? trim((string) $_POST['acf']['field_quotes_context']) : null;
    $context_position = isset($_POST['acf']['field_quotes_context_position']) ? (string) $_POST['acf']['field_quotes_context_position'] : 'below';
    $dialog_rows = $_POST['acf']['field_quotes_dialog'] ?? null;

    if (null !== $context) {
        global $wpdb;

        $wpdb->update(
            $wpdb->posts,
            ['post_excerpt' => $context],
            ['ID' => $post_id],
            ['%s'],
            ['%d']
        );
        clean_post_cache($post_id);
    }

    update_post_meta($post_id, '_quote_context_position', in_array($context_position, ['above', 'below'], true) ? $context_position : 'below');

    $rows = [];
    $term_ids = [];
    $main_author = '';

    if ($main_author_term_id > 0) {
        $main_term = get_term($main_author_term_id, 'quote_author');

        if ($main_term && ! is_wp_error($main_term)) {
            $main_author = $main_term->name;
            $term_ids[] = (int) $main_term->term_id;
            update_post_meta($post_id, '_legacy_post_author_display_name', $main_author);
        }
    } else {
        delete_post_meta($post_id, '_legacy_post_author_display_name');
    }

    if (! is_array($dialog_rows)) {
        if (! empty($term_ids)) {
            wp_set_post_terms($post_id, $term_ids, 'quote_author', false);
        }
        return;
    }

    foreach ($dialog_rows as $index => $row) {
        $quote = trim((string) ($row['quote'] ?? ''));
        $author_term_id = isset($row['quote_author']) ? (int) $row['quote_author'] : 0;
        $author_name = '';
        $author_slug = '';

        if ($author_term_id > 0) {
            $term_object = get_term($author_term_id, 'quote_author');

            if ($term_object && ! is_wp_error($term_object)) {
                $author_name = $term_object->name;
                $author_slug = $term_object->slug;
                $term_ids[] = (int) $term_object->term_id;
            }
        }

        if ('' === $quote && '' === $author_name) {
            continue;
        }

        $rows[] = [
            'index' => (int) $index,
            'quote' => $quote,
            'author' => $author_name,
            'author_slug' => $author_slug,
        ];

        update_post_meta($post_id, sprintf('quote_and_name_%d_quote', $index), $quote);
        update_post_meta($post_id, sprintf('quote_and_name_%d_quote_author_name', $index), $author_name);
        update_post_meta($post_id, sprintf('quote_and_name_%d_quote_author_slug', $index), $author_slug);
    }

    if (! empty($rows)) {
        update_post_meta($post_id, '_quotes_repeater_rows', wp_json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    } else {
        delete_post_meta($post_id, '_quotes_repeater_rows');
    }

    $term_ids = array_values(array_unique(array_filter($term_ids)));

    if (! empty($term_ids)) {
        wp_set_post_terms($post_id, $term_ids, 'quote_author', false);
    }
}, 20);
