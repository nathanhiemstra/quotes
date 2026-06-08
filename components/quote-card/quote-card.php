<?php
/**
 * Quote card component.
 *
 * Expected args:
 * - quote: string
 * - date: string
 * - author: string
 * - author_url: string
 * - author_count: int|string
 * - context: string
 * - repeater: array<int,array{author:string,author_url:string,quote:string}>
 * - modifier: string
 * - context_position: string
 */

$args = wp_parse_args($args ?? [], [
    'quote' => '',
    'date' => '',
    'author' => '',
    'author_url' => '',
    'author_count' => '',
    'context' => '',
    'repeater' => [],
    'modifier' => '',
    'context_position' => 'after',
]);

$author_name = trim((string) $args['author']);
$author_url = trim((string) $args['author_url']);
$author_count = trim((string) $args['author_count']);
$modifier = trim((string) $args['modifier']);
$context_position = trim((string) $args['context_position']);
$classes = ['quote-card'];

if ($modifier) {
    $classes[] = sanitize_html_class("quote-card--{$modifier}");
}
?>

<article class="<?php echo esc_attr(implode(' ', $classes)); ?>">
    <?php if ($args['date']) : ?>
        <time class="quote-card__date"><?php echo esc_html($args['date']); ?></time>
    <?php endif; ?>

    <?php if ('before_quote' === $context_position && $args['context']) : ?>
        <p class="quote-card__context quote-card__context--before"><?php echo esc_html($args['context']); ?>:</p>
    <?php endif; ?>

    <?php if ($args['quote']) : ?>
        <h2 class="quote-card__quote"><?php echo esc_html($args['quote']); ?></h2>
    <?php endif; ?>

    <?php if ($author_name) : ?>
        <p class="quote-card__author">
            <span aria-hidden="true">- </span>
            <?php if ($author_url) : ?>
                <a href="<?php echo esc_url($author_url); ?>"><?php echo esc_html($author_name); ?> </a>
            <?php else : ?>
                <?php echo esc_html($author_name); ?>
            <?php endif; ?>
            <?php if ($author_count) : ?>
                <sup><?php echo esc_html($author_count); ?></sup>
            <?php endif; ?>
        </p>
    <?php endif; ?>

    <?php if (! empty($args['repeater'])) : ?>
        <div class="quote-card__repeater">
            <?php foreach ($args['repeater'] as $row) : ?>
                <?php
                $row_author = trim((string) ($row['author'] ?? ''));
                $row_author_url = trim((string) ($row['author_url'] ?? ''));
                $row_quote = trim((string) ($row['quote'] ?? ''));
                ?>
                <?php if (in_array($modifier, ['stacked', 'stacked-large', 'quote-26', 'author-first'], true)) : ?>
                    <div class="quote-card__repeater-item">
                        <?php if ('author-first' === $modifier && $row_author) : ?>
                            <p class="quote-card__repeater-author quote-card__repeater-author--first">
                                <?php if ($row_author_url) : ?>
                                    <a href="<?php echo esc_url($row_author_url); ?>"><?php echo esc_html($row_author); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html($row_author); ?>
                                <?php endif; ?>
                                <span aria-hidden="true">:</span>
                            </p>
                        <?php elseif ($row_quote) : ?>
                            <p class="quote-card__repeater-quote"><?php echo esc_html($row_quote); ?></p>
                        <?php endif; ?>

                        <?php if ('author-first' === $modifier && $row_quote) : ?>
                            <p class="quote-card__repeater-quote"><?php echo esc_html($row_quote); ?></p>
                        <?php elseif ($row_author) : ?>
                            <p class="quote-card__repeater-author">
                                <span aria-hidden="true">- </span>
                                <?php if ($row_author_url) : ?>
                                    <a href="<?php echo esc_url($row_author_url); ?>"><?php echo esc_html($row_author); ?></a>
                                <?php else : ?>
                                    <?php echo esc_html($row_author); ?>
                                <?php endif; ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else : ?>
                    <div class="quote-card__repeater-author">
                        <?php if ($row_author_url) : ?>
                            <a href="<?php echo esc_url($row_author_url); ?>"><?php echo esc_html($row_author); ?></a>
                        <?php else : ?>
                            <?php echo esc_html($row_author); ?>
                        <?php endif; ?>
                        <?php if ($row_author) : ?>
                            <span aria-hidden="true">:</span>
                        <?php endif; ?>
                    </div>
                    <div class="quote-card__repeater-quote"><?php echo esc_html($row_quote); ?></div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ('before_quote' !== $context_position && $args['context']) : ?>
        <p class="quote-card__context"><?php echo esc_html($args['context']); ?></p>
    <?php endif; ?>
</article>
