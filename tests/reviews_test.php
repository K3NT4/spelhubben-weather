<?php
// Reminder timing, scope and permanent dismissal without WordPress/network.
define('ABSPATH', __DIR__ . '/../');
define('DAY_IN_SECONDS', 86400);
function add_action($name, $callback) { $GLOBALS['hooks'][$name] = $callback; }
function current_user_can($cap) { return $cap === 'manage_options' && $GLOBALS['admin']; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function add_option($key, $value, $unused = '', $autoload = null) { $GLOBALS['options'][$key] = $value; }
function update_option($key, $value, $autoload = null) { $GLOBALS['options'][$key] = $value; }
function sanitize_key($value) { return strtolower(preg_replace('/[^a-z0-9_-]/i', '', $value)); }
function wp_unslash($value) { return stripslashes($value); }
function esc_html__($value, $domain = null) { return $value; }
function esc_html_e($value, $domain = null) { echo $value; }
function esc_url($value) { return $value; }
function admin_url($path) { return '/wp-admin/' . $path; }
function wp_nonce_field($action) { echo '<input type="hidden" name="_wpnonce" value="test">'; }
function check_admin_referer($action) { if ($action !== 'svv_review_dismiss' || empty($GLOBALS['nonce'])) throw new RuntimeException('nonce'); }
function wp_die($message, $title, $args) { throw new RuntimeException((string)$args['response']); }
function wp_safe_redirect($url, $status) { if ($status !== 303) throw new RuntimeException('status'); throw new RuntimeException('redirect'); }
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
require __DIR__ . '/../includes/reviews.php';
$GLOBALS['admin'] = false; $GLOBALS['options'] = [];
sv_vader_review_start(); check(!$GLOBALS['options'], 'Non-admin does not initialize reminder');
$GLOBALS['admin'] = true; sv_vader_review_start();
$started = $GLOBALS['options']['sv_vader_review_started_at'];
check(!sv_vader_review_notice_due($started + 14*DAY_IN_SECONDS - 1), 'No early notice');
check(sv_vader_review_notice_due($started + 14*DAY_IN_SECONDS), 'Notice due after 14 days');
sv_vader_review_start(); check($GLOBALS['options']['sv_vader_review_started_at'] === $started, 'Start is not reset');
$GLOBALS['options']['sv_vader_review_started_at'] = time()-15*DAY_IN_SECONDS;
$_GET['page'] = 'dashboard'; ob_start(); sv_vader_review_notice(); check(ob_get_clean() === '', 'No notice on unrelated admin pages');
$_GET['page'] = 'sv-vader'; ob_start(); sv_vader_review_notice(); $html = ob_get_clean();
check(strpos($html, 'svv-review-notice') !== false && strpos($html, 'https://wordpress.org/plugins/spelhubben-weather/#reviews') !== false, 'Own screen has neutral review link');
check(strpos($html, '_wpnonce') !== false, 'Dismiss form includes nonce');
$GLOBALS['admin'] = false;
try { sv_vader_dismiss_review_notice(); } catch (RuntimeException $e) { check($e->getMessage() === '403', 'Unauthorized dismissal denied'); }
check(!get_option('sv_vader_review_dismissed'), 'Unauthorized request does not dismiss');
$GLOBALS['admin'] = true;
try { sv_vader_dismiss_review_notice(); } catch (RuntimeException $e) { check($e->getMessage() === 'nonce', 'Invalid nonce denied'); }
check(!get_option('sv_vader_review_dismissed'), 'Invalid nonce does not dismiss');
$GLOBALS['nonce'] = true;
try { sv_vader_dismiss_review_notice(); } catch (RuntimeException $e) { check($e->getMessage() === 'redirect', 'Valid dismissal redirects'); }
check(!sv_vader_review_notice_due(time()+365*DAY_IN_SECONDS), 'Dismissal persists permanently');
ob_start(); sv_vader_review_notice(); check(ob_get_clean() === '', 'Dismissed reminder stays hidden');
check(!isset($GLOBALS['hooks']['admin_post_nopriv_svv_dismiss_review_notice']), 'No guest dismissal endpoint');
echo "Review reminder checks passed.\n";
