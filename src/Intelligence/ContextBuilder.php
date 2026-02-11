<?php
namespace BugSneak\Intelligence;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Builds context for error classification.
 */
class ContextBuilder
{
    /**
     * Build context array.
     *
     * @return array Context data.
     */
    public static function build(): array
    {
        return [
            'php_version'   => PHP_VERSION,
            'wp_version'    => get_bloginfo('version'),
            'is_multisite'  => is_multisite(),
            'is_rest'       => defined('REST_REQUEST') && REST_REQUEST,
            'is_admin'      => is_admin(),
            'memory_limit'  => ini_get('memory_limit'),
        ];
    }
}
