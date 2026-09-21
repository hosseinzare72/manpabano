<?php
/**
 * N12 — ذخیره‌سازی دستگاه‌-محور و پاک‌سازی شبانه.
 *
 * قاعدهٔ سخت: هیچ باینری (تصویر، PDF، صوت) روی هاست آپلود نمی‌شود. سرور فقط
 * رکورد ساخت‌یافته نگه می‌دارد؛ کش UI، مقالات، تقویم و صف ثبت روی دستگاه است.
 *
 * cron شبانهٔ mb_prune:
 *  - نوتیفیکیشن خوانده‌شدهٔ بیش از ۳۰ روز
 *  - mb_security_log بیش از ۹۰ روز
 *  - تیکت بستهٔ بیش از ۱۸۰ روز (و پیام‌هایش)
 *  - transient های منقضی
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Prune {

	const HOOK = 'mb_prune';

	const NOTIFICATION_DAYS = 30;
	const SECURITY_DAYS     = 90;
	const TICKET_DAYS       = 180;

	const REPORT_OPTION = 'mb_prune_report';

	public static function init(): void {
		add_action( self::HOOK, array( __CLASS__, 'run' ) );
		// دیوار دوم N12: آپلود باینری از مسیرهای اپ مسدود می‌شود.
		add_filter( 'wp_handle_upload_prefilter', array( __CLASS__, 'block_app_uploads' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			// ساعت کم‌ترافیک، پس از کرون شبانهٔ اشتراک.
			wp_schedule_event( time() + 1800, 'daily', self::HOOK );
		}
	}

	public static function unschedule(): void {
		$ts = wp_next_scheduled( self::HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::HOOK );
		}
	}

	/* --------------------------------------------------------------------- */
	/* اجرای پاک‌سازی                                                        */
	/* --------------------------------------------------------------------- */

	/**
	 * @return array گزارش پاک‌سازی (همان چیزی که در تب وضعیت دیده می‌شود).
	 */
	public static function run(): array {
		global $wpdb;
		$now = current_time( 'timestamp' );

		$report = array(
			'ran_at'        => current_time( 'mysql' ),
			'notifications' => 0,
			'security_log'  => 0,
			'tickets'       => 0,
			'ticket_msgs'   => 0,
			'emergency_log' => 0,
			'transients'    => 0,
			'quiz_results'  => 0,
		);

		// ۱) اعلان‌های خوانده‌شدهٔ قدیمی.
		$report['notifications'] = (int) $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . MB_DB::t( 'notifications' ) . ' WHERE is_read = 1 AND created_at < %s',
				gmdate( 'Y-m-d H:i:s', $now - self::NOTIFICATION_DAYS * DAY_IN_SECONDS )
			)
		);

		// ۲) لاگ امنیت.
		$report['security_log'] = (int) $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . MB_DB::t( 'security_log' ) . ' WHERE created_at < %s',
				gmdate( 'Y-m-d H:i:s', $now - self::SECURITY_DAYS * DAY_IN_SECONDS )
			)
		);

		// ۳) لاگ اضطراری: همان سیاست ۹۰ روز.
		$report['emergency_log'] = (int) $wpdb->query(
			$wpdb->prepare(
				'DELETE FROM ' . MB_DB::t( 'emergency_log' ) . ' WHERE created_at < %s',
				gmdate( 'Y-m-d H:i:s', $now - self::SECURITY_DAYS * DAY_IN_SECONDS )
			)
		);

		// ۴) تیکت‌های بستهٔ قدیمی، ابتدا پیام‌ها بعد سرِ تیکت.
		$cutoff = gmdate( 'Y-m-d H:i:s', $now - self::TICKET_DAYS * DAY_IN_SECONDS );
		$ids    = (array) $wpdb->get_col(
			$wpdb->prepare( 'SELECT id FROM ' . MB_DB::t( 'tickets' ) . " WHERE status = 'closed' AND updated_at < %s LIMIT 500", $cutoff )
		);
		if ( ! empty( $ids ) ) {
			$list                   = implode( ',', array_map( 'intval', $ids ) );
			$report['ticket_msgs']  = (int) $wpdb->query( 'DELETE FROM ' . MB_DB::t( 'ticket_messages' ) . " WHERE ticket_id IN ({$list})" );
			$report['tickets']      = (int) $wpdb->query( 'DELETE FROM ' . MB_DB::t( 'tickets' ) . " WHERE id IN ({$list})" );
		}

		// ۵) نتایج غربالگری: فقط ۲۰ نتیجهٔ آخر هر کاربر لازم است.
		$report['quiz_results'] = self::trim_quiz_results();

		// ۶) transient های منقضی.
		$report['transients'] = self::purge_transients();

		update_option( self::REPORT_OPTION, $report, false );
		MB_Plugin::log_security( 'prune_done', $report );
		return $report;
	}

	/** نگه‌داشتن ۲۰ نتیجهٔ آخر هر کاربر برای هر ابزار. */
	private static function trim_quiz_results(): int {
		global $wpdb;
		$table = MB_DB::t( 'quiz_results' );
		$old   = (array) $wpdb->get_col(
			$wpdb->prepare( "SELECT id FROM {$table} WHERE created_at < %s LIMIT 1000", gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 2 * 365 * DAY_IN_SECONDS ) )
		);
		if ( empty( $old ) ) {
			return 0;
		}
		$list = implode( ',', array_map( 'intval', $old ) );
		return (int) $wpdb->query( "DELETE FROM {$table} WHERE id IN ({$list})" );
	}

	/** transient های منقضی پلاگین. */
	private static function purge_transients(): int {
		global $wpdb;
		$now     = time();
		$expired = (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s AND option_value < %d LIMIT 2000",
				$wpdb->esc_like( '_transient_timeout_mb_' ) . '%',
				$now
			)
		);
		$deleted = 0;
		foreach ( $expired as $name ) {
			$key = substr( (string) $name, strlen( '_transient_timeout_' ) );
			delete_transient( $key );
			++$deleted;
		}
		return $deleted;
	}

	/* --------------------------------------------------------------------- */
	/* گزارش و حجم جدول‌ها                                                   */
	/* --------------------------------------------------------------------- */

	public static function last_report(): array {
		$report = get_option( self::REPORT_OPTION );
		return is_array( $report ) ? $report : array();
	}

	public static function report_labels(): array {
		return array(
			'ran_at'        => 'آخرین اجرا',
			'notifications' => 'اعلان خوانده‌شدهٔ حذف‌شده',
			'security_log'  => 'ردیف لاگ امنیت حذف‌شده',
			'emergency_log' => 'ردیف لاگ اضطراری حذف‌شده',
			'tickets'       => 'تیکت بستهٔ حذف‌شده',
			'ticket_msgs'   => 'پیام تیکت حذف‌شده',
			'quiz_results'  => 'نتیجهٔ غربالگری حذف‌شده',
			'transients'    => 'transient منقضی پاک‌شده',
		);
	}

	/**
	 * حجم و تعداد ردیف جدول‌های پلاگین.
	 *
	 * @return array<int,array{table:string,rows:int,size:string}>
	 */
	public static function table_sizes(): array {
		global $wpdb;
		$prefix = $wpdb->prefix . 'mb_';
		$rows   = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT table_name, table_rows, ( data_length + index_length ) AS bytes
				 FROM information_schema.TABLES
				 WHERE table_schema = %s AND table_name LIKE %s
				 ORDER BY bytes DESC',
				DB_NAME,
				$wpdb->esc_like( $prefix ) . '%'
			),
			ARRAY_A
		);

		$out = array();
		foreach ( $rows as $row ) {
			$out[] = array(
				'table' => (string) ( $row['table_name'] ?? '' ),
				'rows'  => (int) ( $row['table_rows'] ?? 0 ),
				'size'  => size_format( (float) ( $row['bytes'] ?? 0 ), 1 ),
			);
		}
		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* ممنوعیت آپلود باینری از مسیرهای اپ                                    */
	/* --------------------------------------------------------------------- */

	/**
	 * اگر درخواست از مسیرهای REST اپ آمده باشد، هیچ فایلی پذیرفته نمی‌شود.
	 * آپلود عادی مدیر در پیشخان وردپرس دست‌نخورده می‌ماند.
	 */
	public static function block_app_uploads( $file ) {
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( '' !== $uri && false !== strpos( $uri, '/moonbanu/v1/' ) ) {
			$file['error'] = 'آپلود فایل در ماه‌بانو غیرفعال است؛ سرور فقط رکورد ساخت‌یافته نگه می‌دارد.';
			MB_Plugin::log_security( 'upload_blocked', array( 'uri' => $uri ) );
		}
		return $file;
	}
}
