<?php
/**
 * Plugin Name: Quotes Homepage Order
 * Description: Shows the main Quotes homepage blog loop from oldest to newest and displays quote context/author.
 */

add_action('pre_get_posts', function ($query) {
    if (is_admin() || ! $query->is_main_query()) {
        return;
    }

    if ($query->is_home()) {
        $months_by_year = quotes_get_archive_months_by_year();
        $oldest_year = ! empty($months_by_year) ? (int) array_key_first($months_by_year) : 0;

        if ($oldest_year) {
            $query->set('year', $oldest_year);
        }

        $query->set('posts_per_page', -1);
        $query->set('nopaging', true);
        $query->set('orderby', 'date');
        $query->set('order', 'ASC');
        return;
    }

    if ($query->is_tax('quote_author')) {
        $query->set('posts_per_page', -1);
        $query->set('nopaging', true);
        $query->set('orderby', 'date');
        $query->set('order', 'DESC');
        return;
    }

    if ($query->is_date()) {
        $query->set('orderby', 'date');
        $query->set('order', 'ASC');
        $query->set('posts_per_page', -1);
        $query->set('nopaging', true);
    }
});

add_action('template_redirect', function () {
    if (! is_home() || is_paged()) {
        return;
    }

    $months_by_year = quotes_get_archive_months_by_year();

    if (empty($months_by_year)) {
        return;
    }

    wp_safe_redirect(get_year_link((int) array_key_first($months_by_year)), 302);
    exit;
});

add_action('init', function () {
    if ('My Collection of Quotes' !== get_option('blogname')) {
        update_option('blogname', 'My Collection of Quotes');
    }

    $permalink_structure = '/%year%/%monthnum%/%postname%/';

    if ($permalink_structure !== get_option('permalink_structure')) {
        update_option('permalink_structure', $permalink_structure);
        update_option('quotes_migration_rewrite_flushed', 0, false);
    }

    if (! get_option('quotes_migration_rewrite_flushed')) {
        flush_rewrite_rules();
        update_option('quotes_migration_rewrite_flushed', 1, false);
    }

    if (! get_option('quotes_migration_author_slug_flushed_v3')) {
        flush_rewrite_rules();
        update_option('quotes_migration_author_slug_flushed_v3', 1, false);
    }

    if (! get_page_by_path('authors', OBJECT, 'page')) {
        wp_insert_post([
            'post_title' => 'Authors',
            'post_name' => 'authors',
            'post_type' => 'page',
            'post_status' => 'publish',
            'post_content' => '[quotes_authors]',
        ]);
    }

    if (get_option('quotes_migration_default_content_removed')) {
        return;
    }

    $sample_page = get_page_by_path('sample-page', OBJECT, 'page');

    if ($sample_page) {
        wp_delete_post($sample_page->ID, true);
    }

    update_option('quotes_migration_default_content_removed', 1, false);
});

function quotes_get_quote_text($post_id) {
    $quote = trim(wp_strip_all_tags((string) get_post_field('post_content', $post_id)));
    $quote = preg_replace('/\s+/', ' ', $quote);

    return $quote ?: '';
}

function quotes_get_repeater_rows($post_id) {
    $rows = json_decode((string) get_post_meta($post_id, '_quotes_repeater_rows', true), true);

    if (is_array($rows)) {
        return $rows;
    }

    $meta = get_post_meta($post_id);
    $indexes = [];

    foreach (array_keys($meta) as $key) {
        if (preg_match('/^quote_and_name_(\d+)_(quote|quote_author_name|quote_author_slug|author-user|author)$/', $key, $matches)) {
            $indexes[] = (int) $matches[1];
        }
    }

    $indexes = array_values(array_unique($indexes));
    sort($indexes);
    $rows = [];

    foreach ($indexes as $index) {
        $quote = (string) get_post_meta($post_id, sprintf('quote_and_name_%d_quote', $index), true);
        $author_name = (string) get_post_meta($post_id, sprintf('quote_and_name_%d_quote_author_name', $index), true);
        $author_slug = (string) get_post_meta($post_id, sprintf('quote_and_name_%d_quote_author_slug', $index), true);

        if ('' === $quote && '' === $author_name && '' === $author_slug) {
            continue;
        }

        $rows[] = [
            'index' => $index,
            'quote' => $quote,
            'author' => $author_name,
            'author_slug' => $author_slug,
        ];
    }

    return $rows;
}

add_filter('the_title', function ($title, $post_id = 0) {
    if (is_admin() || 'post' !== get_post_type($post_id)) {
        return $title;
    }

    $quote = quotes_get_quote_text($post_id);

    return $quote ?: $title;
}, 10, 2);

add_filter('the_content', function ($content) {
    if (is_page('authors')) {
        return do_shortcode($content);
    }

    if (is_admin() || 'post' !== get_post_type()) {
        return $content;
    }

    $output = '';
    $context = trim((string) get_post_field('post_excerpt', get_the_ID()));
    $authors = get_the_terms(get_the_ID(), 'quote_author');
    $main_author_lines = [];
    $repeater_lines = [];

    if (! is_tax('quote_author') && ! empty($authors) && ! is_wp_error($authors)) {
        $legacy_author_name = trim((string) get_post_meta(get_the_ID(), '_legacy_post_author_display_name', true));
        $main_authors = [];

        foreach ($authors as $author) {
            if ('' !== $legacy_author_name && $author->name !== $legacy_author_name) {
                continue;
            }

            $main_authors[] = $author;
        }

        if (empty($main_authors) && 1 === count($authors)) {
            $main_authors = $authors;
        }

        foreach ($main_authors as $author) {
            if ('Nayt Hiemstra' === $author->name) {
                continue;
            }

            $url = get_term_link($author);

            if (is_wp_error($url)) {
                $main_author_lines[] = esc_html($author->name);
                continue;
            }

            $main_author_lines[] = sprintf(
                '<a href="%s">%s </a><sup>%d</sup>',
                esc_url($url),
                esc_html($author->name),
                (int) $author->count
            );
        }
    }

    $repeater_rows = quotes_get_repeater_rows(get_the_ID());

    if (is_array($repeater_rows)) {
        foreach ($repeater_rows as $row) {
            $row_index = isset($row['index']) ? (int) $row['index'] : -1;
            $quote = trim(wp_strip_all_tags((string) ($row['quote'] ?? '')));

            if ('' === $quote && $row_index >= 0) {
                $quote = trim(wp_strip_all_tags((string) get_post_meta(get_the_ID(), sprintf('quote_and_name_%d_quote', $row_index), true)));
            }

            $name = trim(wp_strip_all_tags((string) ($row['author'] ?? '')));

            if ('' === $quote && '' === $name) {
                continue;
            }

            $author_html = esc_html($name);
            $slug = sanitize_title((string) ($row['author_slug'] ?? ''));

            if ('' !== $slug) {
                $term = get_term_by('slug', $slug, 'quote_author');

                if ($term && ! is_wp_error($term)) {
                    $url = get_term_link($term);

                    if (! is_wp_error($url)) {
                        $author_html = sprintf(
                            '<a href="%s">%s</a>',
                            esc_url($url),
                            esc_html($term->name)
                        );
                    }
                }
            }

            if ('' !== $quote && '' !== $author_html) {
                $repeater_lines[] = sprintf(
                    '<div class="quotes-repeater__author">%s<span aria-hidden="true">:</span></div><div class="quotes-repeater__quote">%s</div>',
                    $author_html,
                    esc_html($quote)
                );
            } elseif ('' !== $quote) {
                $repeater_lines[] = sprintf(
                    '<div class="quotes-repeater__author"></div><div class="quotes-repeater__quote">%s</div>',
                    esc_html($quote)
                );
            } elseif ('' !== $author_html) {
                $repeater_lines[] = sprintf(
                    '<div class="quotes-repeater__author">%s</div><div class="quotes-repeater__quote"></div>',
                    $author_html
                );
            }
        }
    }

    if (! empty($main_author_lines)) {
        $output .= sprintf(
            '<p class="quote-author"><span aria-hidden="true">&#45; </span>%s</p>',
            implode(', ', $main_author_lines)
        );
    }

    if (! empty($repeater_lines)) {
        $output .= sprintf(
            '<div class="quotes-repeater">%s</div>',
            implode('', $repeater_lines)
        );
    }

    if ('' !== $context) {
        $context_text = wp_strip_all_tags($context);

        if ('' !== $context_text) {
            $output .= sprintf(
                '<p class="quote-context">%s</p>',
                esc_html($context_text)
            );
        }
    }

    return $output;
}, 20);

add_shortcode('quotes_authors', function () {
    $authors = get_terms([
        'taxonomy' => 'quote_author',
        'hide_empty' => true,
        'orderby' => 'name',
        'order' => 'ASC',
    ]);

    if (empty($authors) || is_wp_error($authors)) {
        return '<p>No authors found.</p>';
    }

    $output = '<ul class="quotes-authors-list">';

    foreach ($authors as $author) {
        $url = get_term_link($author);

        if (is_wp_error($url)) {
            continue;
        }

        $output .= sprintf(
            '<li><a href="%s">%s</a> <sup>%d</sup></li>',
            esc_url($url),
            esc_html(function_exists('quotes_custom_format_author_name') ? quotes_custom_format_author_name($author->name) : $author->name),
            (int) $author->count
        );
    }

    $output .= '</ul>';

    return $output;
});

function quotes_get_archive_months_by_year() {
    global $wpdb;

    $cache_key = 'quotes_archive_months_by_year';
    $cached = wp_cache_get($cache_key, 'quotes');

    if (false !== $cached) {
        return $cached;
    }

    $rows = $wpdb->get_results(
        "SELECT YEAR(post_date) AS year, MONTH(post_date) AS month, COUNT(*) AS count
        FROM {$wpdb->posts}
        WHERE post_type = 'post'
            AND post_status = 'publish'
        GROUP BY year, month
        ORDER BY year ASC, month ASC"
    );
    $months_by_year = [];

    foreach ($rows as $row) {
        $year = (int) $row->year;
        $month = (int) $row->month;

        if (! $year || ! $month) {
            continue;
        }

        if (empty($months_by_year[$year])) {
            $months_by_year[$year] = [
                'total' => 0,
                'months' => [],
            ];
        }

        $count = (int) $row->count;

        $months_by_year[$year]['months'][$month] = $count;
        $months_by_year[$year]['total'] += $count;
    }

    wp_cache_set($cache_key, $months_by_year, 'quotes', HOUR_IN_SECONDS);

    return $months_by_year;
}

function quotes_current_archive_year_month($months_by_year) {
    $year = (int) get_query_var('year');
    $month = (int) get_query_var('monthnum');

    if (! $year || empty($months_by_year[$year]['months'])) {
        $year = (int) array_key_first($months_by_year);
    }

    if (! is_month() || ! $month || empty($months_by_year[$year]['months'][$month])) {
        $month = 0;
    }

    return [$year, $month];
}

function quotes_render_date_navigation() {
    if (is_admin()) {
        return;
    }

    $months_by_year = quotes_get_archive_months_by_year();

    if (empty($months_by_year)) {
        return;
    }

    [$current_year, $current_month] = quotes_current_archive_year_month($months_by_year);
    $highlight_current_date = ! is_tax('quote_author');
    $month_labels = [
        1 => 'Jan',
        2 => 'Feb',
        3 => 'Mar',
        4 => 'Apr',
        5 => 'May',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Aug',
        9 => 'Sep',
        10 => 'Oct',
        11 => 'Nov',
        12 => 'Dec',
    ];
    ?>
    <nav class="quotes-date-nav" aria-label="Quotes by date">
        <ul class="quotes-date-nav__years">
            <?php foreach ($months_by_year as $year => $year_data) : ?>
                <li>
                    <a
                        href="<?php echo esc_url(get_year_link($year)); ?>"
                        title="<?php echo esc_attr($year_data['total']); ?>"
                        aria-label="<?php echo esc_attr($year); ?>"
                        <?php echo $highlight_current_date && (int) $year === $current_year ? 'aria-current="page"' : ''; ?>
                    >
                        <?php echo esc_html($year); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>

        <?php if ((is_year() || is_month()) && ! empty($months_by_year[$current_year]['months'])) : ?>
            <ul class="quotes-date-nav__months">
                <?php foreach ($months_by_year[$current_year]['months'] as $month => $count) : ?>
                    <li>
                        <a
                            href="<?php echo esc_url(get_month_link($current_year, (int) $month)); ?>"
                            title="<?php echo esc_attr($count); ?>"
                            aria-label="<?php echo esc_attr($month_labels[(int) $month]); ?>"
                            <?php echo $highlight_current_date && 0 !== $current_month && (int) $month === $current_month ? 'aria-current="page"' : ''; ?>
                        >
                            <?php echo esc_html($month_labels[(int) $month]); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </nav>
    <?php
}

add_action('wp_body_open', 'quotes_render_date_navigation');

function quotes_render_author_archive_heading() {
    if (! is_tax('quote_author')) {
        return;
    }

    $term = get_queried_object();

    if (! $term || empty($term->name)) {
        return;
    }
    ?>
    <h1 class="quotes-author-heading"><?php echo esc_html($term->name); ?></h1>
    <?php
}

add_action('wp_body_open', 'quotes_render_author_archive_heading', 11);

function quotes_get_adjacent_year($current_year, $direction) {
    $years = array_map('intval', array_keys(quotes_get_archive_months_by_year()));
    $index = array_search((int) $current_year, $years, true);

    if (false === $index) {
        return null;
    }

    $adjacent_index = 'previous' === $direction ? $index - 1 : $index + 1;

    return $years[$adjacent_index] ?? null;
}

function quotes_get_adjacent_month($current_year, $current_month, $direction) {
    $months_by_year = quotes_get_archive_months_by_year();
    $months = [];

    foreach ($months_by_year as $year => $year_data) {
        foreach (array_keys($year_data['months']) as $month) {
            $months[] = [
                'year' => (int) $year,
                'month' => (int) $month,
            ];
        }
    }

    foreach ($months as $index => $item) {
        if ((int) $current_year === $item['year'] && (int) $current_month === $item['month']) {
            $adjacent_index = 'previous' === $direction ? $index - 1 : $index + 1;

            return $months[$adjacent_index] ?? null;
        }
    }

    return null;
}

function quotes_render_bottom_archive_navigation() {
    if (is_admin() || (! is_year() && ! is_month())) {
        return;
    }

    $current_year = (int) get_query_var('year');
    $current_month = (int) get_query_var('monthnum');
    $previous = null;
    $next = null;

    if (is_month()) {
        $previous = quotes_get_adjacent_month($current_year, $current_month, 'previous');
        $next = quotes_get_adjacent_month($current_year, $current_month, 'next');
    } else {
        $previous_year = quotes_get_adjacent_year($current_year, 'previous');
        $next_year = quotes_get_adjacent_year($current_year, 'next');
        $previous = $previous_year ? ['year' => $previous_year] : null;
        $next = $next_year ? ['year' => $next_year] : null;
    }

    if (! $previous && ! $next) {
        return;
    }

    $format_label = function ($item) {
        if (empty($item['month'])) {
            return (string) $item['year'];
        }

        return date_i18n('M Y', mktime(0, 0, 0, $item['month'], 1, $item['year']));
    };
    $format_url = function ($item) {
        if (empty($item['month'])) {
            return get_year_link((int) $item['year']);
        }

        return get_month_link((int) $item['year'], (int) $item['month']);
    };
    ?>
    <nav class="quotes-adjacent-nav" aria-label="Adjacent quote archives">
        <div>
            <?php if ($previous) : ?>
                <a href="<?php echo esc_url($format_url($previous)); ?>" rel="prev">
                    <span aria-hidden="true">&larr;</span>
                    <?php echo esc_html($format_label($previous)); ?>
                </a>
            <?php endif; ?>
        </div>
        <div>
            <?php if ($next) : ?>
                <a href="<?php echo esc_url($format_url($next)); ?>" rel="next">
                    <?php echo esc_html($format_label($next)); ?>
                    <span aria-hidden="true">&rarr;</span>
                </a>
            <?php endif; ?>
        </div>
    </nav>
    <?php
}

add_action('wp_footer', 'quotes_render_bottom_archive_navigation', 5);

add_action('wp_head', function () {
    ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Platypi:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        header.wp-block-template-part .wp-block-group.alignwide {
            justify-content: center !important;
            text-align: center;
        }

        header.wp-block-template-part {
            display: none !important;
        }

        header.wp-block-template-part .wp-block-site-title {
            margin-left: auto;
            margin-right: auto;
            text-align: center;
        }

        header.wp-block-template-part .wp-block-site-title + .wp-block-group {
            display: none !important;
        }

        main {
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
            padding-left: var(--wp--preset--spacing--50, 1.5rem);
            padding-right: var(--wp--preset--spacing--50, 1.5rem);
        }

        main > .wp-block-heading.has-text-align-left:first-child {
            display: none !important;
        }

        body.single-post .wp-block-group:has(.wp-block-post-author-name),
        body.single-post .wp-block-group:has(.taxonomy-category) {
            display: none !important;
        }

        main .wp-block-query.alignfull {
            box-sizing: border-box;
            max-width: 800px;
            width: 100%;
            margin-left: auto !important;
            margin-right: auto !important;
        }

        main .wp-block-post-template.alignfull {
            max-width: 100%;
            margin-left: 0 !important;
            margin-right: 0 !important;
        }

        .wp-block-post-template > .wp-block-post + .wp-block-post {
            margin-top: 25px;
        }

        .wp-block-post-template .wp-block-post > .wp-block-group {
            align-items: center;
            background: #eeeeee9c;
            border-radius: 0.75rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: var(--wp--preset--spacing--50, 1.5rem) !important;
            text-align: center;
        }

        .wp-block-post-template .wp-block-post-date {
            order: 1;
            text-align: center;
            margin-top: 0 !important;
            margin-bottom: var(--wp--preset--spacing--30, 1rem);
        }

        .wp-block-post-template .wp-block-post-title {
            order: 2;
            text-align: center;
            font-family: "Platypi", Georgia, serif;
        }

        .quotes-author-heading {
            font-family: "Platypi", Georgia, serif;
            font-size: var(--wp--preset--font-size--x-large);
            font-weight: 400;
            line-height: 1.2;
            margin: 1rem auto 2rem;
            max-width: 800px;
            padding: 0 var(--wp--preset--spacing--50, 1.5rem);
            text-align: center;
        }

        .wp-block-post-template .wp-block-post-content {
            order: 3;
            text-align: center;
        }

        .quote-context,
        .quote-author {
            text-align: center;
        }

        .quotes-repeater {
            align-self: stretch;
            display: grid;
            gap: 0.6rem 1rem;
            grid-template-columns: max-content minmax(0, 1fr);
            margin-top: 1rem;
            text-align: left;
        }

        .quotes-repeater__author {
            text-align: left;
            white-space: nowrap;
        }

        .quotes-repeater__author span[aria-hidden="true"] {
            margin-left: 0.05em;
        }

        .quotes-repeater__quote {
            font-family: "Platypi", Georgia, serif;
            text-align: left;
        }

        .quotes-date-nav {
            display: grid;
            gap: 0.75rem;
            font-size: 14px;
            margin: 1.5rem auto 2rem;
            max-width: 800px;
            text-align: left;
        }

        .quotes-date-nav ul {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem 0.75rem;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .quotes-date-nav a {
            color: inherit;
            text-decoration: none;
        }

        .quotes-date-nav a[aria-current="page"] {
            font-weight: 700;
        }

        .wp-block-query-title {
            border: 0;
            clip: rect(1px, 1px, 1px, 1px);
            clip-path: inset(50%);
            height: 1px;
            margin: -1px;
            overflow: hidden;
            padding: 0;
            position: absolute;
            white-space: nowrap;
            width: 1px;
        }

        .quotes-adjacent-nav {
            display: flex;
            font-size: 14px;
            justify-content: space-between;
            margin: 2rem auto;
            max-width: 800px;
            padding: 0 var(--wp--preset--spacing--50, 1.5rem);
        }

        .quotes-adjacent-nav a {
            color: inherit;
            text-decoration: none;
        }

        .quotes-authors-list {
            columns: 2;
            font-size: 16px;
            list-style: none;
            margin: 2rem 0;
            padding: 0;
        }

        .quotes-authors-list li {
            break-inside: avoid;
            margin-bottom: 0.5rem;
        }

        .quotes-authors-list a {
            color: inherit;
            text-decoration: none;
        }

        .wp-block-query-pagination,
        footer.wp-block-template-part {
            display: none !important;
        }
    </style>
    <?php
});

add_action('wp_footer', function () {
    if (! is_singular('post')) {
        return;
    }
    ?>
    <script>
        document.querySelectorAll('.wp-block-post-author-name').forEach(function (authorBlock) {
            const byline = authorBlock.closest('.wp-block-group');

            if (byline) {
                byline.remove();
            }
        });
    </script>
    <?php
});
