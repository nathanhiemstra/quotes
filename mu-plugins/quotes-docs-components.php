<?php

/**
 * Plugin Name: Quotes Component Docs
 * Description: Local component docs for the Quotes site.
 */

function quotes_component_path($component, $file) {
    return WP_CONTENT_DIR . sprintf('/components/%s/%s', $component, $file);
}

function quotes_render_component($component, $args = []) {
    $template = quotes_component_path($component, "{$component}.php");

    if (! file_exists($template)) {
        return '';
    }

    ob_start();
    include $template;
    return ob_get_clean();
}

add_action('init', function () {
    $docs = get_page_by_path('docs', OBJECT, 'page');

    if (! $docs) {
        $docs_id = wp_insert_post([
            'post_title' => 'Docs',
            'post_name' => 'docs',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => '[quotes_component_docs]',
        ]);
    } else {
        $docs_id = $docs->ID;

        if ('[quotes_component_docs]' !== trim((string) $docs->post_content)) {
            wp_update_post([
                'ID' => $docs_id,
                'post_content' => '[quotes_component_docs]',
            ]);
        }
    }

    if (! get_page_by_path('docs/components', OBJECT, 'page')) {
        wp_insert_post([
            'post_title' => 'Components',
            'post_name' => 'components',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_parent' => $docs_id,
            'post_content' => '[quotes_component_docs]',
        ]);
    }
});

add_action('wp_enqueue_scripts', function () {
    if (! is_page(['docs', 'components'])) {
        return;
    }

    $css_file = quotes_component_path('quote-card', 'quote-card.css');

    if (! file_exists($css_file)) {
        return;
    }

    wp_enqueue_style(
        'quotes-quote-card',
        content_url('components/quote-card/quote-card.css'),
        [],
        filemtime($css_file)
    );
});

add_action('template_redirect', function () {
    if (! is_page('components')) {
        return;
    }

    wp_safe_redirect(home_url('/docs/'), 301);
    exit;
});

add_shortcode('quotes_component_docs', function () {
    $base_card = [
        'date' => 'Month 00, 0000',
        'quote' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor.',
        'author' => 'Author N.',
        'author_url' => '/author/author-name/',
        'author_count' => 3,
        'context' => 'A sentence giving context',
        'repeater' => [
            [
                'author' => 'Author N. 2',
                'author_url' => '/author/author-name-2/',
                'quote' => 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor.',
            ],
            [
                'author' => 'Author N. 3',
                'author_url' => '/author/author-name-3/',
                'quote' => 'Lorem ipsum dolor sit amet consectetur.',
            ],
        ],
    ];

    $sections = [
        [
            'heading' => '',
            'examples' => [
                'Basic' => array_merge($base_card, [
                    'author' => '',
                    'author_url' => '',
                    'author_count' => '',
                    'context' => '',
                    'repeater' => [],
                ]),
                'With Author' => array_merge($base_card, [
                    'context' => '',
                    'repeater' => [],
                ]),
            ],
        ],
        [
            'heading' => 'Context',
            'examples' => [
                'Context below' => array_merge($base_card, [
                    'repeater' => [],
                ]),
                'Context above' => array_merge($base_card, [
                    'author' => 'Author N. 3',
                    'author_url' => '/author/author-name-3/',
                    'author_count' => '',
                    'repeater' => [],
                    'context_position' => 'before_quote',
                ]),
            ],
        ],
        [
            'heading' => 'Multiple Authors',
            'examples' => [
                'Columns' => array_merge($base_card, [
                    'context' => '',
                    'repeater' => array_slice($base_card['repeater'], 0, 1),
                ]),
                '' => $base_card,
                'Rows' => array_merge($base_card, [
                    'context' => '',
                    'modifier' => 'stacked',
                ]),
                'Rows - Same size quotes' => array_merge($base_card, [
                    'context' => '',
                    'modifier' => 'stacked-large',
                ]),
                'Rows - Same size quotes but smaller' => array_merge($base_card, [
                    'context' => '',
                    'modifier' => 'quote-26',
                ]),
            ],
        ],
    ];

    $quote_cards = '';

    foreach ($sections as $section_index => $section) {
        if ($section_index > 0) {
            $quote_cards .= '<hr class="quotes-component-docs__separator">';
        }

        if ($section['heading']) {
            $quote_cards .= sprintf(
                '<h2 class="quotes-component-docs__section-heading">%s</h2>',
                esc_html($section['heading'])
            );
        }

        foreach ($section['examples'] as $label => $args) {
            $heading = $label ? sprintf('<h3>%s</h3>', esc_html($label)) : '';
            $quote_cards .= sprintf(
                '<section class="quotes-component-docs__example">%s%s</section>',
                $heading,
                quotes_render_component('quote-card', $args)
            );
        }
    }

    return sprintf(
        '<main class="quotes-component-docs"><section><h2>Quote Card</h2>%s</section></main>',
        $quote_cards
    );
});
