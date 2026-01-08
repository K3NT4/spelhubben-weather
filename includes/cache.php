<?php
// includes/cache.php
if (!defined('ABSPATH')) exit;

/**
 * Simple plugin-local cache wrapper to centralize cache behaviour.
 */
if (!function_exists('sv_vader_cache_key')) {
    function sv_vader_cache_key(string $k): string {
        // Normalize key and include plugin cache salt so we can invalidate all keys
        $salt = sv_vader_cache_salt();
        return 'spelhubben_' . $salt . '_' . $k;
    }
}

if (!function_exists('sv_vader_cache_get')) {
    function sv_vader_cache_get(string $key) {
        return get_transient(sv_vader_cache_key($key));
    }
}

if (!function_exists('sv_vader_cache_set')) {
    function sv_vader_cache_set(string $key, $value, int $seconds) : bool {
        return set_transient(sv_vader_cache_key($key), $value, $seconds);
    }
}

if (!function_exists('sv_vader_cache_delete')) {
    function sv_vader_cache_delete(string $key) : bool {
        return delete_transient(sv_vader_cache_key($key));
    }
}

if (!function_exists('sv_vader_cache_invalidate_all')) {
    function sv_vader_cache_invalidate_all() : void {
        // Rotate salt to effectively invalidate all cached keys without touching DB directly.
        $opts = get_option('sv_vader_options', []);
        if (!is_array($opts)) $opts = [];
        $opts['cache_salt'] = (string) time();
        update_option('sv_vader_options', $opts, false);
    }
}
