<?php
// Optional administrator feedback reminder. No visitor tracking or remote requests.
if (!defined('ABSPATH')) exit;

function sv_vader_review_start() {
	if (current_user_can('manage_options') && get_option('sv_vader_review_started_at', false) === false) {
		add_option('sv_vader_review_started_at', time(), '', false);
	}
}

function sv_vader_review_notice_due(?int $timestamp = null): bool {
	$started = get_option('sv_vader_review_started_at', 0);
	return is_numeric($started) && (int)$started > 0
		&& ($timestamp ?? time()) - (int)$started >= 14 * DAY_IN_SECONDS
		&& !get_option('sv_vader_review_dismissed', false);
}

function sv_vader_review_notice() {
	// The query only selects a whitelisted informational screen, never a write.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$page = isset($_GET['page']) && is_string($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
	if (!current_user_can('manage_options') || !in_array($page, ['sv-vader','sv-vader-alerts','sv-vader-shortcodes','sv-vader-performance'], true) || !sv_vader_review_notice_due()) return;
	?>
	<div class="notice notice-info svv-review-notice">
		<p><strong><?php esc_html_e('Share your experience', 'spelhubben-weather'); ?></strong></p>
		<p><?php esc_html_e('Have you tried Spelhubben Weather? We would appreciate an honest rating or review on WordPress.org. Your feedback helps us improve the plugin.', 'spelhubben-weather'); ?></p>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
			<input type="hidden" name="action" value="svv_dismiss_review_notice">
			<?php wp_nonce_field('svv_review_dismiss'); ?>
			<p><a class="button button-secondary" href="https://wordpress.org/plugins/spelhubben-weather/#reviews" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Leave a review', 'spelhubben-weather'); ?></a>
			<button type="submit" class="button button-link"><?php esc_html_e('No thanks — do not show again', 'spelhubben-weather'); ?></button></p>
		</form>
	</div>
	<?php
}

function sv_vader_dismiss_review_notice() {
	if (!current_user_can('manage_options')) {
		wp_die(esc_html__('You do not have permission to dismiss this notice.', 'spelhubben-weather'), '', ['response'=>403]);
		return;
	}
	check_admin_referer('svv_review_dismiss');
	update_option('sv_vader_review_dismissed', 1, false);
	wp_safe_redirect(admin_url('admin.php?page=sv-vader'), 303);
	exit;
}

add_action('admin_init', 'sv_vader_review_start');
add_action('admin_notices', 'sv_vader_review_notice');
add_action('admin_post_svv_dismiss_review_notice', 'sv_vader_dismiss_review_notice');
