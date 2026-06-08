<?php
/**
 * Apply June 8, 2026 quote content fixes.
 *
 * Run with WP-CLI from a WordPress root:
 * wp eval-file wp-content/migrations/2026-06-08-content-fixes.php
 */

if (! defined('ABSPATH')) {
    fwrite(STDERR, "Run this through WordPress/WP-CLI.\n");
    exit(1);
}

$log = [];

function quotes_migration_term($name) {
    $term = get_term_by('name', $name, 'quote_author');

    if ($term && ! is_wp_error($term)) {
        return $term;
    }

    $created = wp_insert_term($name, 'quote_author', ['slug' => sanitize_title($name)]);

    if (is_wp_error($created)) {
        throw new RuntimeException($created->get_error_message());
    }

    return get_term((int) $created['term_id'], 'quote_author');
}

function quotes_migration_main_author($post_id, $author_name) {
    $term = quotes_migration_term($author_name);
    wp_set_post_terms($post_id, [(int) $term->term_id], 'quote_author', false);
    update_post_meta($post_id, '_legacy_post_author_display_name', $term->name);

    return $term;
}

function quotes_migration_dialog_rows($post_id, $rows) {
    $term_ids = [];
    $normalized = [];

    foreach ($rows as $index => $row) {
        $author_name = (string) ($row['author'] ?? '');
        $term = '' !== $author_name ? quotes_migration_term($author_name) : null;

        if ($term) {
            $term_ids[] = (int) $term->term_id;
        }

        $normalized[] = [
            'index' => (int) $index,
            'quote' => (string) ($row['quote'] ?? ''),
            'author' => $term ? $term->name : $author_name,
            'author_slug' => $term ? $term->slug : sanitize_title($author_name),
            'show_age' => (string) ($row['show_age'] ?? ''),
        ];

        update_post_meta($post_id, sprintf('quote_and_name_%d_quote', $index), $normalized[$index]['quote']);
        update_post_meta($post_id, sprintf('quote_and_name_%d_quote_author_name', $index), $normalized[$index]['author']);
        update_post_meta($post_id, sprintf('quote_and_name_%d_quote_author_slug', $index), $normalized[$index]['author_slug']);
    }

    update_post_meta($post_id, '_quotes_repeater_rows', wp_json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $term_ids;
}

function quotes_migration_find_post($slugs) {
    foreach ((array) $slugs as $slug) {
        $post = get_page_by_path($slug, OBJECT, 'post');

        if ($post) {
            return $post;
        }
    }

    return null;
}

function quotes_migration_update_post($args) {
    $result = wp_update_post($args, true);

    if (is_wp_error($result)) {
        throw new RuntimeException($result->get_error_message());
    }

    clean_post_cache((int) $args['ID']);

    return (int) $args['ID'];
}

$tat = quotes_migration_find_post([
    'how-can-i-explain-the-contradiction-tat-i-am-both-pro-choice-and-anti-capital-punishment',
    'how-can-i-explain-the-contradiction-that-i-am-both-pro-choice-and-anti-capital-punishment',
]);

if ($tat) {
    quotes_migration_update_post([
        'ID' => $tat->ID,
        'post_title' => 'How can I explain the contradiction that I am both pro choice and anti capital punishment?',
        'post_content' => 'How can I explain the contradiction that I am both pro choice and anti capital punishment?',
        'post_name' => 'how-can-i-explain-the-contradiction-that-i-am-both-pro-choice-and-anti-capital-punishment',
    ]);
    $log[] = 'fixed tat quote #' . $tat->ID;
}

$comfort = quotes_migration_find_post('comfortably-straddling');

if ($comfort) {
    quotes_migration_dialog_rows($comfort->ID, [
        ['quote' => 'What side of the fence do you expect to be on?', 'author' => 'Nayt Hiemstra'],
        ['quote' => 'Comfortably straddling.', 'author' => 'Sarah Holtschlag'],
    ]);
    $log[] = 'fixed comfortably straddling #' . $comfort->ID;
}

$phone = quotes_migration_find_post('can-i-borrow-your-phone');

if ($phone) {
    quotes_migration_dialog_rows($phone->ID, [
        ['quote' => 'Who you gonna call?', 'author' => 'Nayt Hiemstra'],
        ['quote' => 'Ghostbusters. (deadpan)', 'author' => 'Amanda Janik'],
        ['quote' => 'Goddamn it. I knew you were gonna say that.', 'author' => 'Nayt Hiemstra'],
        ['quote' => 'What else is there to say?', 'author' => 'Amanda Janik'],
    ]);
    $log[] = 'fixed can i borrow your phone #' . $phone->ID;
}

$dustin = quotes_migration_find_post('i-like-that-you-put-your-hat-on-before-your-pants');

if ($dustin) {
    quotes_migration_update_post([
        'ID' => $dustin->ID,
        'post_title' => 'I like that you put your hat on before your pants.',
        'post_content' => 'I like that you put your hat on before your pants.',
        'post_excerpt' => 'Talking to Pat Zant at the camp staff dorm.',
    ]);
    quotes_migration_main_author($dustin->ID, 'Dustin Strasser');
    update_post_meta($dustin->ID, '_quote_context_position', 'below');
    $log[] = 'updated Dustin quote #' . $dustin->ID;
}

$camp = quotes_migration_find_post('camp-founders');

if ($camp) {
    quotes_migration_update_post([
        'ID' => $camp->ID,
        'post_title' => 'Camp founders',
        'post_content' => 'See the three guys in the middle picture? They bought this camp and started it 50 years ago.',
        'post_excerpt' => 'At camp.',
        'post_date' => '2000-06-24 00:00:00',
        'post_date_gmt' => '2000-06-24 00:00:00',
    ]);
    $nayt = quotes_migration_main_author($camp->ID, 'Nayt Hiemstra');
    $dialog_terms = quotes_migration_dialog_rows($camp->ID, [
        ['quote' => 'So, they’re dead? (9 years old)', 'author' => 'Sinjin Ownby'],
    ]);
    wp_set_post_terms($camp->ID, array_values(array_unique(array_merge([(int) $nayt->term_id], $dialog_terms))), 'quote_author', false);
    update_post_meta($camp->ID, '_quote_context_position', 'below');
    $log[] = 'updated camp founders #' . $camp->ID;
}

$graham = quotes_migration_find_post('bitch-gets-me-every-time');

if ($graham) {
    $graham_id = $graham->ID;
    quotes_migration_update_post([
        'ID' => $graham_id,
        'post_title' => 'Bitch gets me every time.',
        'post_content' => 'Bitch gets me every time.',
        'post_excerpt' => 'About the mannequin hanging from the camp staff dorm door.',
        'post_date' => '2000-07-18 00:00:00',
        'post_date_gmt' => '2000-07-18 00:00:00',
    ]);
    $log[] = 'updated Graham quote #' . $graham_id;
} else {
    $graham_id = wp_insert_post([
        'post_type' => 'post',
        'post_status' => 'publish',
        'post_title' => 'Bitch gets me every time.',
        'post_content' => 'Bitch gets me every time.',
        'post_excerpt' => 'About the mannequin hanging from the camp staff dorm door.',
        'post_name' => 'bitch-gets-me-every-time',
        'post_date' => '2000-07-18 00:00:00',
        'post_date_gmt' => '2000-07-18 00:00:00',
    ], true);

    if (is_wp_error($graham_id)) {
        throw new RuntimeException($graham_id->get_error_message());
    }

    $log[] = 'added Graham quote #' . $graham_id;
}

quotes_migration_main_author($graham_id, 'Graham Mason');
update_post_meta($graham_id, '_quote_context_position', 'above');

$courtney = quotes_migration_find_post('fingers-and-toes-are-weird-they-look-like-fringe');

if ($courtney) {
    quotes_migration_update_post([
        'ID' => $courtney->ID,
        'post_title' => 'Fingers and toes are weird. They look like fringe.',
        'post_content' => 'Fingers and toes are weird. They look like fringe.',
        'post_date' => '2000-07-30 00:00:00',
        'post_date_gmt' => '2000-07-30 00:00:00',
    ]);
    quotes_migration_main_author($courtney->ID, 'Courtney Douglas');
    $log[] = 'updated Courtney quote #' . $courtney->ID;
}

$escape_ids = [401, 2113, 2136, 968, 1238, 2160, 1503, 2214, 2216, 5163];
$escape_map = [
    'â€™' => '’',
    'â€˜' => '‘',
    'â€œ' => '“',
    'â€' => '”',
    'â€“' => '–',
    'â€”' => '—',
    'â€¦' => '…',
    'u2019' => '’',
    'u2018' => '‘',
    'u201c' => '“',
    'u201d' => '”',
    'u2013' => '–',
    'u2014' => '—',
    'u2026' => '…',
    'Â ' => ' ',
    'Â' => '',
];

foreach ($escape_ids as $id) {
    foreach (get_post_meta($id) as $key => $values) {
        foreach ($values as $value) {
            if (! is_string($value) || is_serialized($value)) {
                continue;
            }

            $cleaned = str_replace(array_keys($escape_map), array_values($escape_map), $value);

            if ($cleaned !== $value) {
                update_post_meta($id, $key, $cleaned);
                $log[] = "cleaned meta {$id}:{$key}";
            }
        }
    }

    clean_post_cache($id);
}

flush_rewrite_rules(false);

echo wp_json_encode(['ok' => true, 'log' => $log], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
