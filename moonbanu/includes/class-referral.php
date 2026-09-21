<?php
/**
 * M11 — کوپن معرف.
 *
 * هر بانو یک کد معرف یکتا دارد. اگر کاربر تازه با آن کد ثبت‌نام کند و بعداً
 * نسخهٔ پیشرفته را فعال کند، برای معرف یک کوپن یک‌بارمصرف ساخته می‌شود.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Referral {

	/** درصد تخفیف کوپن پاداش. */
	const REWARD_PERCENT = 30;

	/** اعتبار کوپن پاداش (روز). */
	const REWARD_DAYS = 90;

	/* --------------------------------------------------------------------- */
	/* کد معرف                                                               */
	/* --------------------------------------------------------------------- */

	/** کد معرف کاربر؛ در اولین درخواست ساخته می‌شود. */
	public static function code_for( int $user_id ): string {
		$code = (string) get_user_meta( $user_id, 'mb_ref_code', true );
		if ( '' !== $code ) {
			return $code;
		}
		$code = self::generate_code();
		update_user_meta( $user_id, 'mb_ref_code', $code );
		return $code;
	}

	private static function generate_code(): string {
		$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // بدون I,O,0,1 برای خوانایی.
		for ( $try = 0; $try < 12; $try++ ) {
			$code = 'MB';
			for ( $i = 0; $i < 6; $i++ ) {
				$code .= $alphabet[ random_int( 0, strlen( $alphabet ) - 1 ) ];
			}
			if ( 0 === self::user_by_code( $code ) ) {
				return $code;
			}
		}
		return 'MB' . strtoupper( wp_generate_password( 6, false, false ) );
	}

	/** یافتن معرف از کد. */
	public static function user_by_code( string $code ): int {
		$code = self::sanitize_code( $code );
		if ( '' === $code ) {
			return 0;
		}
		$users = get_users(
			array(
				'meta_key'    => 'mb_ref_code',
				'meta_value'  => $code,
				'number'      => 1,
				'fields'      => 'ID',
				'count_total' => false,
			)
		);
		return empty( $users ) ? 0 : (int) $users[0];
	}

	public static function sanitize_code( string $code ): string {
		$code = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', MB_Jalali::en_num( $code ) ) );
		return strlen( $code ) >= 6 && strlen( $code ) <= 16 ? $code : '';
	}

	/* --------------------------------------------------------------------- */
	/* ثبت معرفی                                                             */
	/* --------------------------------------------------------------------- */

	/** در زمان ثبت‌نام: پیوند معرف ↔ معرف‌شده. */
	public static function attach( int $referee, string $code ): bool {
		$referrer = self::user_by_code( $code );
		if ( $referrer <= 0 || $referrer === $referee ) {
			return false;
		}
		return MB_DB::add_referral( $referrer, $referee );
	}

	/**
	 * پس از فعال‌شدن Pro برای معرف‌شده: ساخت کوپن یک‌بارمصرف برای معرف.
	 * هر معرفی فقط یک بار پاداش می‌گیرد (ستون rewarded).
	 */
	public static function on_pro_activated( int $referee, int $sub_id ): void {
		$row = MB_DB::get_referral_by_referee( $referee );
		if ( ! $row || (int) $row['rewarded'] === 1 ) {
			return;
		}

		$referrer = (int) $row['referrer'];
		$coupon   = self::create_reward_coupon( $referrer );
		if ( '' === $coupon ) {
			return;
		}

		MB_DB::mark_referral_rewarded( (int) $row['id'], $sub_id, $coupon );
		MB_DB::add_notification(
			$referrer,
			'referral_reward',
			array(
				'coupon'  => $coupon,
				'percent' => self::REWARD_PERCENT,
			)
		);
		MB_Notify::email_user(
			$referrer,
			'هدیهٔ معرفی ماه‌بانو',
			'<p>کسی که با کد تو ثبت‌نام کرده بود نسخهٔ پیشرفته را فعال کرد.</p>'
			. '<p>کوپن یک‌بارمصرف تو: <strong>' . esc_html( $coupon ) . '</strong> — '
			. esc_html( MB_Jalali::fa_num( (string) self::REWARD_PERCENT ) ) . '٪ تخفیف.</p>'
		);
	}

	/** ساخت کوپن یک‌بارمصرف در جدول کوپن‌ها. */
	private static function create_reward_coupon( int $referrer ): string {
		global $wpdb;
		$code = 'REF' . strtoupper( wp_generate_password( 7, false, false ) );
		$ok   = $wpdb->insert(
			MB_DB::t( 'coupons' ),
			array(
				'code'       => $code,
				'percent'    => self::REWARD_PERCENT,
				'amount_off' => 0,
				'min_amount' => 0,
				'active'     => 1,
				'note'       => 'پاداش معرفی کاربر #' . $referrer,
				'expires_at' => gmdate( 'Y-m-d H:i:s', time() + ( self::REWARD_DAYS * DAY_IN_SECONDS ) ),
				'max_uses'   => 1,
				'uses'       => 0,
			)
		);
		return $ok ? $code : '';
	}

	/* --------------------------------------------------------------------- */
	/* نمایش در صفحهٔ من                                                     */
	/* --------------------------------------------------------------------- */

	public static function summary( int $user_id ): array {
		$rows     = MB_DB::referrals_of( $user_id, 50 );
		$rewarded = 0;
		foreach ( $rows as $row ) {
			if ( (int) $row['rewarded'] === 1 ) {
				++$rewarded;
			}
		}
		return array(
			'code'     => self::code_for( $user_id ),
			'link'     => add_query_arg( 'mb_ref', self::code_for( $user_id ), MB_UI::app_url() ),
			'total'    => count( $rows ),
			'rewarded' => $rewarded,
			'percent'  => self::REWARD_PERCENT,
		);
	}
}
