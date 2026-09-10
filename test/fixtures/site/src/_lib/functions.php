<?php
/**
 * prepros.includes entry — loaded once before any page renders.
 * Exercises two core extension points with no external dependency:
 *   PREPROS::registerTag()  → a custom <uppercase> HTML tag
 *   PREPROS::registerHook() → a post_render token swap
 */

PREPROS::registerTag('uppercase', function ($tag, $attrs, $body) {
    return '<span class="up">' . strtoupper(trim($body)) . '</span>';
});

PREPROS::registerHook('post_render', function ($html) {
    return str_replace('{{built-by}}', 'KiriBuild', $html);
});
