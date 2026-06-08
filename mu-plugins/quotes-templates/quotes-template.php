<?php
/**
 * Clean template for quote views.
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<main id="wp--skip-link--target" class="quotes-container">
    <?php quotes_custom_site_title(); ?>
    <?php quotes_render_date_navigation(); ?>

    <?php if (quotes_custom_archive_heading()) : ?>
        <h1 class="quotes-archive-heading"><?php echo esc_html(quotes_custom_archive_heading()); ?></h1>
    <?php endif; ?>

    <?php if (is_page(['docs', 'components'])) : ?>
        <?php echo do_shortcode('[quotes_component_docs]'); ?>
    <?php elseif (is_singular('post')) : ?>
        <?php
        while (have_posts()) :
            the_post();
            quotes_custom_render_card(get_the_ID(), [
                'heading_level' => 1,
                'link_title' => false,
            ]);
        endwhile;
        ?>
    <?php else : ?>
        <div class="quotes-list">
            <?php
            while (have_posts()) :
                the_post();
                quotes_custom_render_card(get_the_ID(), [
                    'hide_author' => is_tax('quote_author'),
                ]);
            endwhile;
            ?>
        </div>
    <?php endif; ?>
</main>

<?php
if (! is_page(['docs', 'components']) && function_exists('quotes_custom_render_intentional_navigation')) {
    quotes_custom_render_intentional_navigation();
}

wp_footer();
?>
</body>
</html>
