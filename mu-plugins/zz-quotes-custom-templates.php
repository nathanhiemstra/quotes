<?php
/**
 * Plugin Name: Quotes Custom Templates
 * Description: Renders quote views with clean project-owned markup.
 */

add_action('init', function () {
    remove_action('wp_body_open', 'quotes_render_date_navigation');
    remove_action('wp_body_open', 'quotes_render_author_archive_heading', 11);
    remove_action('wp_footer', 'quotes_render_bottom_archive_navigation', 5);
});

add_filter('template_include', function ($template) {
    if (
        is_home()
        || is_year()
        || is_month()
        || is_tax('quote_author')
        || is_singular('post')
        || is_page(['docs', 'components'])
    ) {
        return __DIR__ . '/quotes-templates/quotes-template.php';
    }

    return $template;
}, 100);

function quotes_custom_site_title() {
    printf(
        '<p class="quotes-site-title"><a href="%s" rel="home">%s</a></p>',
        esc_url(home_url('/')),
        esc_html(get_bloginfo('name'))
    );
}

function quotes_custom_main_author_terms($post_id) {
    $authors = get_the_terms($post_id, 'quote_author');

    if (empty($authors) || is_wp_error($authors)) {
        return [];
    }

    $legacy_author_name = trim((string) get_post_meta($post_id, '_legacy_post_author_display_name', true));
    $main_authors = [];

    foreach ($authors as $author) {
        if ('' !== $legacy_author_name && $author->name !== $legacy_author_name) {
            continue;
        }

        if ('Nayt Hiemstra' === $author->name) {
            continue;
        }

        $main_authors[] = $author;
    }

    if (empty($main_authors) && 1 === count($authors) && 'Nayt Hiemstra' !== $authors[0]->name) {
        $main_authors = $authors;
    }

    return $main_authors;
}

function quotes_custom_author_link($author, $show_count = true) {
    $url = get_term_link($author);
    $display_name = quotes_custom_format_author_term_name($author);

    if (is_wp_error($url)) {
        return esc_html($display_name);
    }

    $label = sprintf(
        '<a href="%s">%s%s</a>',
        esc_url($url),
        esc_html($display_name),
        $show_count ? ' ' : ''
    );

    if ($show_count) {
        $label .= sprintf('<sup>%d</sup>', (int) $author->count);
    }

    return $label;
}

function quotes_custom_format_author_term_name($author) {
    $mode = get_term_meta($author->term_id, 'quotes_author_display_mode', true);
    $format = [
        'full_name' => 'full',
        'first_l' => 'first_last_initial',
        'initials' => 'initials',
        'anonymous' => 'anonymous',
    ][$mode] ?? 'first_last_initial';

    return quotes_custom_format_author_name($author->name, $format);
}

function quotes_custom_format_author_name($name, $format = 'first_last_initial') {
    $name = trim((string) $name);

    if ('' === $name) {
        return '';
    }

    $author_name_corrections = [
        'Bev Spangler' => 'Beverly Spangler',
        'Brennan' => 'Brennan Sang',
        'Brennan sang' => 'Brennan Sang',
        'Britney mojo' => 'Brittany Mojo',
        'Brittany mojo' => 'Brittany Mojo',
        'CCatherine Belling' => 'Catherine Belling',
        'Charlie' => 'Charlie Barton',
        'CharlotteMacintire' => 'Charlotte Macintire',
        'CourtneyDouglas' => 'Courtney Douglas',
        'Dan leu' => 'Dan Leu',
        'Dawn Coverse' => 'Dawn Converse',
        'Dera' => 'Dera White',
        'Fiona white' => 'Fiona White',
        'Frances 11' => 'Frances White',
        'Frances white' => 'Frances White',
        'giulia doninelli' => 'Giulia Doninelli',
        'Heather' => 'Heather Moore',
        'Jenn' => 'Jenn Hawe',
        'Jenn H.' => 'Jenn Hawe',
        'jennn' => 'Jenn Hawe',
        'Jenni G.' => 'Jenni Grant',
        'Jenni grant' => 'Jenni Grant',
        'jim Jacoby' => 'Jim Jacoby',
        'JohnnyKochmanski' => 'Johnny Kochmanski',
        'KerryBarney' => 'Kerry Barney',
        'Julia' => 'Julia Spiegel',
        'liz Allen' => 'Liz Allen',
        'LysaSperlich' => 'Lyssa Sperlich',
        'LyssaSperlich' => 'Lyssa Sperlich',
        'M glasses' => 'Anonymous',
        'Neve' => 'Neve White',
        'Neve white' => 'Neve White',
        'Nicole gawromski' => 'Nicole Gawronski',
        'Paulie' => 'Paulie Malone',
        'Sarah Mitchel' => 'Sarah Mitchell',
        'Sarah mitchell' => 'Sarah Mitchell',
        'Terry' => 'Terry White',
        'Terry W' => 'Terry White',
        'Terry white' => 'Terry White',
        'Zolt' => 'Zolt Brown',
    ];

    if (isset($author_name_corrections[$name])) {
        $name = $author_name_corrections[$name];
    }

    if ('anonymous' === $format) {
        return 'Anonymous';
    }

    $full_name_exceptions = [
        '12 year old kid',
        '16 yo girl at a chili outdoor concert',
        '16/yo wearing bucket hat',
        '3 Year Old',
        '3-year-old',
        '7 year old at a pool party',
        '8 Year Old Kid',
        '8-year-old-kid',
        '9 Year Old Kid',
        '9-year-old-kid',
        'A Guy With A Civic Accord',
        'Adam\'s Friend',
        'Anonymous Camper',
        'Beverly Spangler',
        'Blonde Haired Kid',
        'Brennan Sang',
        'Brittany Mojo',
        'Catherine Belling',
        'Charlie Barton',
        'Charlotte Macintire',
        'Courtney Douglas',
        'Dan Leu',
        'Deb Street',
        'Dera White',
        'Dawn Converse',
        'Drunk Guy',
        'Drunk high school girl behind me at a Bright Eyes concert',
        'Dungeons and Dragons 16/yo with huge long hair',
        'Early 20s Waitress',
        'Fiona White',
        'Fish Center call guys',
        'Forty-something Woman',
        'Frances White',
        'Geoff Carter',
        'Giulia Doninelli',
        'Gly Brown',
        'Guy',
        'Guy Dressed As A Green Pope',
        'Guy I passed as I left a Wilco concert',
        'Guy In Line At Triangle Photo',
        'Guy On the Phone',
        'Guy Reading In Traffic',
        'Guy at the next table at breakfast',
        'Heather Moore',
        'Homeless Guy In Golden Gate Park',
        'Husband setting up beach umbrella',
        'Jenn Hawe',
        'Jim Jacoby',
        'Jodie Lucy',
        'Johnny Kochmanski',
        'Kerry Barney',
        'Late 20s woman',
        'Julia Spiegel',
        'Liz Allen',
        'Lyssa Sperlich',
        'Little Girl',
        'Man In His 70s',
        'Mark Wetzel',
        'Marielle School',
        'Nayt Hiemstra',
        'Nicole Gawronski',
        'One Of Several Ladies',
        'One of several ladies walking back from the beach after a gorgeous sunset',
        'Passerby at antique store',
        'Paulie Malone',
        'Rana Siegel',
        'Regular Guys',
        'Sarah Mitchell',
        'Some Camper',
        'some-camper',
        'Stranger in the restaurant booth next to me',
        'Sue Ehry\'s Doctor',
        'Teen Girl',
        'Terry Johnson',
        'Terry White',
        'Waiter',
        'Wife setting up beach umbrella',
        'Woman In Elevator',
        'Woman at grocery store',
        'Woman in front of me at the coffee shop',
        'Woman in the booth next to me',
        'Zolt Brown',
    ];

    if ('first_last_initial' === $format && in_array(strtolower($name), array_map('strtolower', $full_name_exceptions), true)) {
        return $name;
    }

    if (preg_match('/^Anonymous(?:\\s+#?\\d+)?$/i', $name)) {
        return $name;
    }

    $parts = preg_split('/\\s+/', $name);
    $first_letter = function ($part) {
        return function_exists('mb_substr') ? mb_substr($part, 0, 1) : substr($part, 0, 1);
    };

    if ('initials' === $format) {
        $initials = array_map(
            fn($part) => $first_letter($part),
            array_filter($parts)
        );

        return $initials ? implode('.', $initials) . '.' : $name;
    }

    if ('full' === $format || count($parts) < 2) {
        return $name;
    }

    $first = $parts[0];
    $last = end($parts);

    return sprintf('%s %s.', $first, $first_letter($last));
}

function quotes_custom_render_author_line($post_id, $hide_author = false) {
    if ($hide_author) {
        return;
    }

    $author_links = array_map(
        fn($author) => quotes_custom_author_link($author),
        quotes_custom_main_author_terms($post_id)
    );

    if (empty($author_links)) {
        return;
    }

    printf(
        '<footer class="quote-card__footer"><cite class="quote-card__author"><span aria-hidden="true">- </span>%s</cite></footer>',
        implode(', ', $author_links)
    );
}

function quotes_custom_render_repeater($post_id) {
    if (! function_exists('quotes_get_repeater_rows')) {
        return;
    }

    $rows = quotes_get_repeater_rows($post_id);

    if (empty($rows) || ! is_array($rows)) {
        return;
    }

    echo '<div class="quote-card__repeater">';

    foreach ($rows as $row) {
        $quote = trim(wp_strip_all_tags((string) ($row['quote'] ?? '')));
        $name = trim(wp_strip_all_tags((string) ($row['author'] ?? '')));
        $slug = sanitize_title((string) ($row['author_slug'] ?? ''));

        if (preg_match('/^\d+$/', $name) && $quote === quotes_get_quote_text($post_id)) {
            continue;
        }

        $author_html = esc_html($name);

        if ('' !== $slug) {
            $term = get_term_by('slug', $slug, 'quote_author');

            if ($term && ! is_wp_error($term)) {
                $author_html = quotes_custom_author_link($term, false);
            }
        } elseif ('' !== $name) {
            $author_html = esc_html(quotes_custom_format_author_name($name));
        }

        if ('' === $quote && '' === trim(wp_strip_all_tags($author_html))) {
            continue;
        }

        printf(
            '<blockquote class="quote-card__repeater-item">%s%s</blockquote>',
            '' !== $quote ? sprintf('<p class="quote-card__repeater-quote">%s</p>', esc_html($quote)) : '',
            '' !== trim(wp_strip_all_tags($author_html)) ? sprintf(
                '<footer><cite class="quote-card__repeater-author"><span aria-hidden="true">- </span>%s</cite></footer>',
                $author_html
            ) : ''
        );
    }

    echo '</div>';
}

function quotes_custom_render_card($post_id, $args = []) {
    $args = wp_parse_args($args, [
        'hide_author' => false,
        'heading_level' => is_singular('post') ? 1 : 2,
        'link_title' => ! is_singular('post'),
    ]);
    $quote = function_exists('quotes_get_quote_text') ? quotes_get_quote_text($post_id) : wp_strip_all_tags(get_post_field('post_content', $post_id));
    $context = trim((string) get_post_field('post_excerpt', $post_id));
    $context_position = get_post_meta($post_id, '_quote_context_position', true) ?: 'below';
    $date = get_the_date('', $post_id);
    $permalink = get_permalink($post_id);
    $heading_tag = 'h' . max(1, min(6, (int) $args['heading_level']));
    $card_classes = ['quote-card'];
    $has_repeater_rows = function_exists('quotes_get_repeater_rows') && ! empty(quotes_get_repeater_rows($post_id));

    if ($has_repeater_rows) {
        $card_classes[] = 'quote-card--quote-26';
    }

    $quote_length = function_exists('mb_strlen') ? mb_strlen($quote) : strlen($quote);

    if ($quote_length >= 90) {
        $card_classes[] = 'quote-card--long';
    }

    if ($has_repeater_rows) {
        $args['hide_author'] = false;
    }
    ?>
    <article class="<?php echo esc_attr(implode(' ', $card_classes)); ?>">
        <?php if ($date) : ?>
            <time class="quote-card__date" datetime="<?php echo esc_attr(get_the_date('c', $post_id)); ?>"><?php echo esc_html($date); ?></time>
        <?php endif; ?>

        <?php if ($context && 'above' === $context_position) : ?>
            <p class="quote-card__context"><?php echo esc_html(wp_strip_all_tags($context)); ?></p>
        <?php endif; ?>

        <blockquote class="quote-card__blockquote">
            <<?php echo esc_html($heading_tag); ?> class="quote-card__quote">
                <?php if ($args['link_title']) : ?>
                    <a href="<?php echo esc_url($permalink); ?>"><?php echo esc_html($quote); ?></a>
                <?php else : ?>
                    <?php echo esc_html($quote); ?>
                <?php endif; ?>
            </<?php echo esc_html($heading_tag); ?>>

            <?php quotes_custom_render_author_line($post_id, (bool) $args['hide_author']); ?>
        </blockquote>

        <?php quotes_custom_render_repeater($post_id); ?>

        <?php if ($context && 'above' !== $context_position) : ?>
            <p class="quote-card__context"><?php echo esc_html(wp_strip_all_tags($context)); ?></p>
        <?php endif; ?>
    </article>
    <?php
}

function quotes_custom_archive_heading() {
    if (is_tax('quote_author')) {
        $term = get_queried_object();
        return $term && ! empty($term->name) ? sprintf('Quotes by %s', quotes_custom_format_author_term_name($term)) : '';
    }

    if (is_month()) {
        $year = (int) get_query_var('year');
        $month = (int) get_query_var('monthnum');

        if ($year && $month) {
            return sprintf('Quotes from %s', date_i18n('F Y', mktime(0, 0, 0, $month, 1, $year)));
        }
    }

    if (is_year() || is_home()) {
        $year = (int) get_query_var('year');

        if (! $year && function_exists('quotes_get_archive_months_by_year')) {
            $months_by_year = quotes_get_archive_months_by_year();
            $year = ! empty($months_by_year) ? (int) array_key_first($months_by_year) : 0;
        }

        return $year ? sprintf('Quotes from %d', $year) : '';
    }

    return '';
}

function quotes_custom_get_random_quote_url($exclude_post_id = 0) {
    $args = [
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'orderby' => 'rand',
        'fields' => 'ids',
        'no_found_rows' => true,
    ];

    if ($exclude_post_id) {
        $args['post__not_in'] = [(int) $exclude_post_id];
    }

    $posts = get_posts($args);

    return ! empty($posts[0]) ? get_permalink((int) $posts[0]) : '';
}

function quotes_custom_get_adjacent_quote($post_id, $direction) {
    global $wpdb;

    $post = get_post($post_id);

    if (! $post) {
        return null;
    }

    $operator = 'previous' === $direction ? '<' : '>';
    $order = 'previous' === $direction ? 'DESC' : 'ASC';

    $adjacent_id = $wpdb->get_var($wpdb->prepare(
        "SELECT ID
        FROM {$wpdb->posts}
        WHERE post_type = 'post'
            AND post_status = 'publish'
            AND post_date {$operator} %s
        ORDER BY post_date {$order}, ID {$order}
        LIMIT 1",
        $post->post_date
    ));

    return $adjacent_id ? get_post((int) $adjacent_id) : null;
}

function quotes_custom_current_archive_navigation_items() {
    if (! function_exists('quotes_get_adjacent_year') || ! function_exists('quotes_get_adjacent_month')) {
        return [null, null];
    }

    $current_year = (int) get_query_var('year');
    $current_month = (int) get_query_var('monthnum');

    if (is_month()) {
        $previous = quotes_get_adjacent_month($current_year, $current_month, 'previous');
        $next = quotes_get_adjacent_month($current_year, $current_month, 'next');
    } else {
        $previous_year = quotes_get_adjacent_year($current_year, 'previous');
        $next_year = quotes_get_adjacent_year($current_year, 'next');
        $previous = $previous_year ? ['year' => $previous_year] : null;
        $next = $next_year ? ['year' => $next_year] : null;
    }

    $format_label = function ($item) {
        if (empty($item['month'])) {
            return sprintf('%d', (int) $item['year']);
        }

        return date_i18n('M Y', mktime(0, 0, 0, (int) $item['month'], 1, (int) $item['year']));
    };

    $format_url = function ($item) {
        if (empty($item['month'])) {
            return get_year_link((int) $item['year']);
        }

        return get_month_link((int) $item['year'], (int) $item['month']);
    };

    return [
        $previous ? [
            'url' => $format_url($previous),
            'label' => is_month() ? sprintf('Previous month: %s', $format_label($previous)) : sprintf('Previous year: %s', $format_label($previous)),
            'rel' => 'prev',
        ] : null,
        $next ? [
            'url' => $format_url($next),
            'label' => is_month() ? sprintf('Next month: %s', $format_label($next)) : sprintf('Next year: %s', $format_label($next)),
            'rel' => 'next',
        ] : null,
    ];
}

function quotes_custom_render_intentional_navigation() {
    if (is_admin() || is_page(['docs', 'components'])) {
        return;
    }

    $previous = null;
    $next = null;
    $random_url = quotes_custom_get_random_quote_url(is_singular('post') ? get_the_ID() : 0);

    if (is_singular('post')) {
        $previous_post = quotes_custom_get_adjacent_quote(get_the_ID(), 'previous');
        $next_post = quotes_custom_get_adjacent_quote(get_the_ID(), 'next');

        $previous = $previous_post ? [
            'url' => get_permalink($previous_post),
            'label' => 'Previous quote',
            'rel' => 'prev',
        ] : null;
        $next = $next_post ? [
            'url' => get_permalink($next_post),
            'label' => 'Next quote',
            'rel' => 'next',
        ] : null;
    } elseif (is_year() || is_month()) {
        [$previous, $next] = quotes_custom_current_archive_navigation_items();
    }

    if (! $previous && ! $next && ! $random_url) {
        return;
    }
    ?>
    <nav class="quotes-intentional-nav" aria-label="Quote navigation">
        <div class="quotes-intentional-nav__item quotes-intentional-nav__item--previous">
            <?php if ($previous) : ?>
                <a href="<?php echo esc_url($previous['url']); ?>" rel="<?php echo esc_attr($previous['rel']); ?>">
                    <span aria-hidden="true">&larr;</span>
                    <?php echo esc_html($previous['label']); ?>
                </a>
            <?php endif; ?>
        </div>

        <div class="quotes-intentional-nav__item quotes-intentional-nav__item--random">
            <?php if ($random_url) : ?>
                <a href="<?php echo esc_url($random_url); ?>">Random quote</a>
            <?php endif; ?>
        </div>

        <div class="quotes-intentional-nav__item quotes-intentional-nav__item--next">
            <?php if ($next) : ?>
                <a href="<?php echo esc_url($next['url']); ?>" rel="<?php echo esc_attr($next['rel']); ?>">
                    <?php echo esc_html($next['label']); ?>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>
    <?php
}

add_action('wp_head', function () {
    ?>
    <link rel="icon" href='data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><circle cx="32" cy="32" r="24" fill="%237c3aed"/></svg>'>
    <style>
        header.wp-block-template-part,
        footer.wp-block-template-part {
            display: none !important;
        }

        .quotes-container {
            max-width: 635px;
            margin-inline: auto;
            padding: 15px;
        }

        .quotes-list {
            display: grid;
            gap: 50px;
        }

        .quotes-site-title {
            font-weight: 700;
            margin: 0 0 1.5rem;
            text-align: center;
        }

        .quotes-site-title a,
        .quote-card a,
        .quotes-intentional-nav a {
            color: inherit;
            text-decoration: none;
        }

        .quote-card {
            align-items: center;
            background: #eeeeee9c;
            border-radius: 0.75rem;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 32px 40px 48px;
            text-align: center;
        }

        .quote-card__date {
            display: block;
            font-size: 0.875rem;
            margin-bottom: 1rem;
        }

        .quote-card__blockquote {
            margin: 0;
        }

        .quote-card__quote {
            font-family: "Platypi", Georgia, serif;
            font-size: var(--wp--preset--font-size--x-large, 2rem);
            font-weight: 400;
            line-height: 1.2;
            margin: 0;
            text-align: center;
        }

        .quote-card__footer,
        .quote-card__context {
            margin: 1rem 0 0;
            text-align: center;
        }

        .quote-card__author,
        .quote-card__repeater-author {
            font-style: normal;
        }

        .quote-card__context {
            font-style: italic;
        }

        .quote-card__repeater {
            align-self: stretch;
            display: grid;
            gap: 0.6rem 1rem;
            grid-template-columns: max-content minmax(0, 1fr);
            margin-top: 1.5rem;
            text-align: left;
        }

        .quote-card__repeater-author,
        .quote-card__repeater-quote {
            text-align: left;
        }

        .quote-card__repeater-item {
            margin: 0;
        }

        .quote-card__repeater-author {
            white-space: nowrap;
        }

        .quote-card__repeater-author span[aria-hidden="true"] {
            margin-left: 0.05em;
        }

        .quote-card__repeater-quote {
            font-family: "Platypi", Georgia, serif;
        }

        .quote-card--quote-26 .quote-card__repeater {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            text-align: center;
        }

        .quote-card--quote-26 .quote-card__repeater-item {
            text-align: center;
        }

        .quote-card--quote-26 .quote-card__repeater-author,
        .quote-card--quote-26 .quote-card__repeater-quote {
            margin: 0.5rem 0 0;
            text-align: center;
            white-space: normal;
        }

        .quote-card--quote-26 .quote-card__quote,
        .quote-card--quote-26 .quote-card__repeater-quote {
            font-size: 26px;
            line-height: 1.25;
        }

        .quotes-archive-heading {
            font-family: "Platypi", Georgia, serif;
            font-size: var(--wp--preset--font-size--x-large, 2rem);
            font-weight: 400;
            line-height: 1.2;
            margin: 0 0 2rem;
            text-align: center;
        }

        .quotes-intentional-nav {
            display: grid;
            font-size: 14px;
            gap: 1rem;
            grid-template-columns: 1fr auto 1fr;
            margin: 2rem auto 0;
            max-width: 635px;
            padding: 0 15px 2rem;
        }

        .quotes-intentional-nav__item--random {
            text-align: center;
        }

        .quotes-intentional-nav__item--next {
            text-align: right;
        }

        @media (max-width: 480px) {
            .quote-card {
                padding: 32px 28px 44px;
            }

            .quote-card--long .quote-card__quote {
                font-size: clamp(1.45rem, 7vw, 1.8rem);
                line-height: 1.18;
            }

            .quote-card--quote-26 .quote-card__quote,
            .quote-card--quote-26 .quote-card__repeater-quote {
                font-size: clamp(1.35rem, 6vw, 1.625rem);
            }

            .quotes-intentional-nav {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .quotes-intentional-nav__item--next {
                text-align: center;
            }
        }
    </style>
    <?php
}, 30);

add_action('wp_footer', function () {
    if (is_admin() || is_page(['docs', 'components'])) {
        return;
    }
    ?>
    <script>
        (() => {
            const previousLink = document.querySelector('.quotes-intentional-nav a[rel="prev"]');
            const nextLink = document.querySelector('.quotes-intentional-nav a[rel="next"]');
            const editableSelector = 'input, textarea, select, [contenteditable=""], [contenteditable="true"]';

            if (!previousLink && !nextLink) {
                return;
            }

            document.addEventListener('keydown', (event) => {
                if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
                    return;
                }

                if (event.target instanceof Element && event.target.closest(editableSelector)) {
                    return;
                }

                if ('ArrowLeft' === event.key && previousLink) {
                    event.preventDefault();
                    previousLink.click();
                }

                if ('ArrowRight' === event.key && nextLink) {
                    event.preventDefault();
                    nextLink.click();
                }
            });
        })();
    </script>
    <?php
}, 30);
