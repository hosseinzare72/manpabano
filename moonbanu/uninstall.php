<?php
/**
 * حذف پلاگین ماه‌بانو.
 *
 * پیش‌فرض محافظه‌کارانه است: تنظیمات، نقش‌ها، کرون و کش پاک می‌شوند اما
 * جدول‌های داده کاربران دست‌نخورده می‌مانند. اگر در تنظیمات گزینه
 * «حذف کامل داده‌ها هنگام حذف پلاگین» روشن باشد، همه‌چیز پاک می‌شود.
 *
 * @package moonbanu
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

$mb_settings = get_option( 'mb_settings', array() );
$mb_purge    = is_array( $mb_settings ) && ! empty( $mb_settings['delete_data'] );

/* کرون. */
foreach ( array( 'mb_daily_06', 'mb_nightly', 'mb_hourly_reminders', 'mb_prune', 'mb_sla_digest' ) as $mb_hook ) {
	$mb_ts = wp_next_scheduled( $mb_hook );
	while ( $mb_ts ) {
		wp_unschedule_event( $mb_ts, $mb_hook );
		$mb_ts = wp_next_scheduled( $mb_hook );
	}
}

/* نقش‌ها. */
remove_role( 'mb_woman' );
remove_role( 'mb_partner' );
$mb_admin = get_role( 'administrator' );
if ( $mb_admin ) {
	$mb_admin->remove_cap( 'mb_use_app' );
	$mb_admin->remove_cap( 'mb_manage' );
	$mb_admin->remove_cap( 'mb_partner_view' );
}
$mb_sub_role = get_role( 'subscriber' );
if ( $mb_sub_role ) {
	$mb_sub_role->remove_cap( 'mb_use_app' );
}

/* گزینه‌ها. */
foreach ( array( 'mb_settings', 'mb_version', 'mb_manifest', 'mb_manifest_built', 'mb_safe_mode', 'mb_site_hash', 'mb_install_token', 'mb_cron_token', 'mb_cats_seeded', 'mb_quizzes_seeded', 'mb_daily_offset', 'mb_reminder_offset', 'mb_faq_seeded', 'mb_articles_seeded', 'mb_screenings_seeded', 'mb_prune_report' ) as $mb_option ) {
	delete_option( $mb_option );
}

/* کش‌های transient. */
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mb_%' OR option_name LIKE '_transient_timeout_mb_%'" );

if ( $mb_purge ) {
	/* متای کاربران. */
	foreach ( array( 'mb_saved_articles', 'mb_reset_hash', 'mb_reset_exp', 'mb_reset_tries', 'mb_coupons_used', 'mb_terms_at', 'mb_signup_source', 'mb_partner_state', 'mb_onboard_step', 'mb_partner_of', 'mb_mobile', 'mb_reminder_prefs', 'mb_meds_reminder', 'mb_ref_code', 'mb_pregnancy_checklist', 'mb_couple_id' ) as $mb_meta ) {
		delete_metadata( 'user', 0, $mb_meta, '', true );
	}

	/* محتوای راهنما (نوع محتوا در زمان حذف ثبت نشده است، پس با SQL امن پاک می‌شود). */
	$mb_post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('mb_article','mb_faq')" );
	foreach ( (array) $mb_post_ids as $mb_post_id ) {
		$mb_post_id = (int) $mb_post_id;
		$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $mb_post_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->term_relationships, array( 'object_id' => $mb_post_id ), array( '%d' ) );
		$wpdb->delete( $wpdb->posts, array( 'ID' => $mb_post_id ), array( '%d' ) );
	}

	/* تاکسونومی. */
	$mb_terms = get_terms( array( 'taxonomy' => 'mb_cat', 'hide_empty' => false, 'fields' => 'ids' ) );
	if ( ! is_wp_error( $mb_terms ) ) {
		foreach ( (array) $mb_terms as $mb_term_id ) {
			wp_delete_term( (int) $mb_term_id, 'mb_cat' );
		}
	}

	/* جدول‌ها. */
	foreach ( array( 'profile', 'logs', 'cycles', 'invites', 'notifications', 'questions', 'subscriptions', 'coupons', 'security_log', 'tickets', 'ticket_messages', 'pregnancy', 'community', 'quizzes', 'quiz_results', 'referrals', 'health_profile', 'emergency_log' ) as $mb_table ) {
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'mb_' . $mb_table );
	}
}
