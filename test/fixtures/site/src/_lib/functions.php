<?php
/**
 * prepros.includes entry — loaded once before any page renders.
 * Exercises two core extension points with no external dependency:
 *   register_tag()  → a custom <uppercase> HTML tag
 *   register_hook() → a post_render token swap
 */

register_tag('uppercase', function (string $tag, array $attrs, string $body): string {
    return '<span class="up">' . strtoupper(trim($body)) . '</span>';
});

register_hook('post_render', function (string $html): string {
    return str_replace('{{built-by}}', 'KiriBuild', $html);
});
