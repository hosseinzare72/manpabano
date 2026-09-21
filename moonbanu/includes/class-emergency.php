<?php
/**
 * N4 — موتور اضطراری.
 *
 * سه محرک:
 *  ۱) خونریزی heavy در سه روز متوالی.
 *  ۲) خونریزی heavy همراه با درد ≥ ۴.
 *  ۳) فشردن دکمهٔ SOS توسط کاربر.
 *
 * شرط لازم برای ارسال: emergency_alert = 1 در پروفایل سلامت.
 * پیام هرگز هیچ جزئیات پزشکی ندارد: فقط نام و درخواست تماس.
 * cooldown شش‌ساعته برای هر کاربر. همهٔ رویدادها لاگ می‌شوند.
 *
 * D1 — پیام‌های بحرانی همیشه fallback پیامکی دارند؛ اعلان درون‌اپ مکمل است، نه جایگزین.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Emergency {

	/** فاصلهٔ اجباری میان دو هشدار یک کاربر. */
	const COOLDOWN = 6 * HOUR_IN_SECONDS;

	/** آستانهٔ درد همراه با خونریزی زیاد. */
	const PAIN_THRESHOLD = 4;

	/** روزهای متوالی خونریزی زیاد. */
	const HEAVY_DAYS = 3;

	/* --------------------------------------------------------------------- */
	/* ارزیابی پس از هر ثبت روزانه                                           */
	/* --------------------------------------------------------------------- */

	/**
	 * پس از ذخیرهٔ هر لاگ صدا زده می‌شود.
	 *
	 * @return array{triggered:bool,reason:string,sent:bool}
	 */
	public static function evaluate_log( int $user_id, string $date, string $bleeding, int $pain ): array {
		$reason = '';

		if ( 'heavy' === $bleeding && $pain >= self::PAIN_THRESHOLD ) {
			$reason = 'heavy_pain';
		} elseif ( 'heavy' === $bleeding && self::heavy_streak( $user_id, $date ) >= self::HEAVY_DAYS ) {
			$reason = 'heavy_streak';
		}

		if ( '' === $reason ) {
			return array( 'triggered' => false, 'reason' => '', 'sent' => false );
		}

		$sent = self::fire( $user_id, $reason );
		return array( 'triggered' => true, 'reason' => $reason, 'sent' => $sent );
	}

	/** شمارش روزهای متوالی heavy تا همین تاریخ (شامل خودش). */
	private static function heavy_streak( int $user_id, string $date ): int {
		global $wpdb;
		$date = MB_Jalali::sanitize_date( $date );
		$from = MB_Jalali::add_days( $date, -1 * ( self::HEAVY_DAYS + 3 ) );
		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT log_date,bleeding FROM ' . MB_DB::t( 'logs' ) . ' WHERE user_id = %d AND log_date BETWEEN %s AND %s ORDER BY log_date DESC',
				$user_id,
				$from,
				$date
			),
			ARRAY_A
		);

		$map = array();
		foreach ( $rows as $row ) {
			$map[ (string) $row['log_date'] ] = (string) $row['bleeding'];
		}

		$streak = 0;
		$cursor = $date;
		while ( isset( $map[ $cursor ] ) && 'heavy' === $map[ $cursor ] ) {
			++$streak;
			$cursor = MB_Jalali::add_days( $cursor, -1 );
		}
		return $streak;
	}

	/* --------------------------------------------------------------------- */
	/* SOS دستی                                                              */
	/* --------------------------------------------------------------------- */

	/**
	 * دکمهٔ SOS.
	 *
	 * @return array{ok:bool,sent:bool,message:string}
	 */
	public static function sos( int $user_id ): array {
		if ( ! MB_Health_Profile::alert_enabled( $user_id ) ) {
			return array(
				'ok'      => false,
				'sent'    => false,
				'message' => 'برای ارسال هشدار، ابتدا در «پروفایل سلامت» هشدار اضطراری را روشن کن و شمارهٔ تماس اضطراری را وارد کن.',
			);
		}

		$cool = self::cooldown_left( $user_id );
		if ( $cool > 0 ) {
			return array(
				'ok'      => false,
				'sent'    => false,
				'message' => 'هشدار قبلی کمتر از شش ساعت پیش فرستاده شد. ' . MB_Jalali::fa_num( (int) ceil( $cool / MINUTE_IN_SECONDS ) ) . ' دقیقه دیگر می‌توانی دوباره بفرستی.',
			);
		}

		$sent = self::fire( $user_id, 'sos' );
		return array(
			'ok'      => true,
			'sent'    => $sent,
			'message' => $sent
				? 'هشدار برای مخاطب اضطراری و همسرت فرستاده شد. هیچ جزئیات پزشکی ارسال نشد.'
				: 'ارسال پیامک ممکن نشد؛ رویداد ثبت شد. اگر حالت خوب نیست با اورژانس ۱۱۵ تماس بگیر.',
		);
	}

	/* --------------------------------------------------------------------- */
	/* ارسال                                                                 */
	/* --------------------------------------------------------------------- */

	/**
	 * ارسال واقعی هشدار با همهٔ نگهبان‌ها.
	 *
	 * @return bool true اگر دست‌کم یک کانال موفق بود.
	 */
	private static function fire( int $user_id, string $reason ): bool {
		if ( ! MB_Health_Profile::alert_enabled( $user_id ) ) {
			self::log( $user_id, $reason, 'skipped_disabled', false );
			return false;
		}
		if ( self::cooldown_left( $user_id ) > 0 ) {
			self::log( $user_id, $reason, 'skipped_cooldown', false );
			return false;
		}

		$user = get_userdata( $user_id );
		$name = $user ? ( $user->first_name ? $user->first_name : $user->display_name ) : 'کاربر ماه‌بانو';
		$text = self::message( $name );

		$contact = MB_Health_Profile::emergency_contact( $user_id );
		$partner = MB_Privacy::partner_of( $user_id );

		$ok_sms = false;

		// ۱) مخاطب اضطراری — فقط پیامک (ممکن است کاربر اپ نداشته باشد).
		if ( '' !== $contact['phone'] ) {
			$result = MB_Notify::sms( $contact['phone'], $text );
			$ok_sms = $ok_sms || ! is_wp_error( $result );
		}

		// ۲) همسر متصل — پیامک + اعلان درون‌اپ. مستقل از share_status.
		if ( $partner > 0 ) {
			MB_DB::add_notification( $partner, 'emergency', array( 'name' => $name, 'text' => $text ) );
			$mobile = (string) get_user_meta( $partner, 'mb_mobile', true );
			if ( '' !== $mobile ) {
				$result = MB_Notify::sms( $mobile, $text );
				$ok_sms = $ok_sms || ! is_wp_error( $result );
			}
		}

		// ۳) خود کاربر: رسید درون‌اپ تا بداند هشدار رفته است.
		MB_DB::add_notification( $user_id, 'emergency_sent', array( 'reason' => $reason ) );

		self::log( $user_id, $reason, $ok_sms ? 'sent' : 'sms_failed', $ok_sms );
		self::mark_sent( $user_id );

		return $ok_sms;
	}

	/** متن ثابت هشدار — هیچ جزئیات پزشکی، هیچ نشانه، هیچ عددی. */
	public static function message( string $name ): string {
		return 'شرایط اضطراری برای ' . $name . '؛ لطفاً تماس بگیرید.';
	}

	/* --------------------------------------------------------------------- */
	/* cooldown و لاگ                                                        */
	/* --------------------------------------------------------------------- */

	/** ثانیه‌های باقی‌ماندهٔ cooldown؛ صفر یعنی آزاد. */
	public static function cooldown_left( int $user_id ): int {
		$last = (int) get_user_meta( $user_id, 'mb_emergency_last', true );
		if ( $last <= 0 ) {
			return 0;
		}
		$left = ( $last + self::COOLDOWN ) - time();
		return $left > 0 ? $left : 0;
	}

	private static function mark_sent( int $user_id ): void {
		update_user_meta( $user_id, 'mb_emergency_last', time() );
	}

	/** ثبت رویداد در جدول اختصاصی (بدون هیچ فیلد پزشکی). */
	private static function log( int $user_id, string $reason, string $result, bool $ok ): void {
		global $wpdb;
		$wpdb->insert(
			MB_DB::t( 'emergency_log' ),
			array(
				'user_id'    => $user_id,
				'reason'     => sanitize_key( $reason ),
				'result'     => sanitize_key( $result ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
		MB_Plugin::log_security( 'emergency_' . $result, array( 'user' => $user_id, 'reason' => $reason, 'ok' => $ok ? 1 : 0 ) );
	}

	/** آخرین رویدادهای اضطراری کاربر (برای صفحهٔ پروفایل سلامت). */
	public static function recent( int $user_id, int $limit = 5 ): array {
		global $wpdb;
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT reason,result,created_at FROM ' . MB_DB::t( 'emergency_log' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d',
				$user_id,
				max( 1, min( 20, $limit ) )
			),
			ARRAY_A
		);
	}

	public static function reason_label( string $reason ): string {
		$map = array(
			'sos'          => 'دکمهٔ SOS',
			'heavy_pain'   => 'خونریزی زیاد همراه با درد',
			'heavy_streak' => 'خونریزی زیاد سه روز متوالی',
		);
		return $map[ $reason ] ?? $reason;
	}

	public static function result_label( string $result ): string {
		$map = array(
			'sent'              => 'فرستاده شد',
			'sms_failed'        => 'پیامک نرفت (ثبت شد)',
			'skipped_cooldown'  => 'در فاصلهٔ شش‌ساعته نادیده گرفته شد',
			'skipped_disabled'  => 'هشدار خاموش بود',
		);
		return $map[ $result ] ?? $result;
	}
}
