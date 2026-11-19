<?php
// includes/presets.php
if (!defined('ABSPATH')) exit;

/**
 * Visual style presets for the frontend card.
 *
 * Keys are CSS-safe and map to .sv-vader.svv-style-<key> in assets/style.css.
 * The empty key is the default style and must not change the existing look.
 */
if (!function_exists('sv_vader_style_presets')) {
	function sv_vader_style_presets() : array {
		return [
			''           => __( 'Default', 'spelhubben-weather' ),
			'light'      => __( 'Light', 'spelhubben-weather' ),
			'dark'       => __( 'Dark', 'spelhubben-weather' ),
			'neutral'    => __( 'Neutral', 'spelhubben-weather' ),
			'spelhubben' => __( 'Spelhubben', 'spelhubben-weather' ),
			'compact'    => __( 'Compact', 'spelhubben-weather' ),
		];
	}
}

/**
 * Normalize a style preset value to a known key.
 *
 * Accepts scalars only, trims and lowercases strings, and returns '' for
 * invalid input. Arrays, objects, null and unknown values are never echoed back.
 */
if (!function_exists('sv_vader_normalize_style_preset')) {
	function sv_vader_normalize_style_preset($value) : string {
		if (!is_scalar($value)) {
			return '';
		}
		$key = strtolower(trim((string) $value));
		$presets = sv_vader_style_presets();
		return array_key_exists($key, $presets) ? $key : '';
	}
}

/**
 * Layout presets exposed by the renderer, block and widget.
 *
 * Keeping the canonical names here avoids drifting whitelists between those
 * integrations. An empty value means "no layout preset".
 */
if (!function_exists('sv_vader_layout_presets')) {
	function sv_vader_layout_presets() : array {
		return [
			'mini'           => __( 'Mini', 'spelhubben-weather' ),
			'hero'           => __( 'Hero', 'spelhubben-weather' ),
			'sidebar'        => __( 'Sidebar', 'spelhubben-weather' ),
			'dashboard'      => __( 'Dashboard', 'spelhubben-weather' ),
			'forecast-strip' => __( 'Forecast strip', 'spelhubben-weather' ),
		];
	}
}
