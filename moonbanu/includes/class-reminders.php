<?php
/**
 * M5 — یادآورهای سفارشی + M3 یادآور قرص.
 *
 * هر تریگر ساعت و کانال مستقل دارد و در user_meta «mb_reminder_prefs» ذخیره
 * می‌شود. کرون ساعتی است و فقط تریگرهایی را اجرا می‌کند که ساعتشان با ساعت
 * فعلی سایت (با منطقهٔ زمانی وردپرس) برابر باشد.
 *
 * کانال پیامک بخشی از نسخهٔ پیشرفته است؛ اگر کاربر Pro نباشد به ایمیل و
 * اعلان درون‌اپ برمی‌گردد.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Reminders {

	const HOOK  = 'mb_hourly_reminders';
	const BATCH = 200;

	public static function init(): void {
		add_action( self::HOOK, array( __CLASS__, 'run_hourly' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + 300, 'hourly', self::HOOK );
		}
	}

	public static function unschedule(): void {
		$ts = wp_next_scheduled( self::HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::HOOK );
		}
	}

	/* --------------------------------------------------------------------- */
	/* تعریف تریگرها                                                         */
	/* --------------------------------------------------------------------- */

	public static function triggers(): array {
		return array(
			'period-2'      => array(
				'label'   => 'دو روز پیش از قاعدگی',
				'hint'    => 'برای آماده‌بودن پیش از شروع.',
				'hour'    => 9,
				'channel' => 'inapp',
				'on'      => true,
				'audience' => 'woman',
			),
			'period-0'      => array(
				'label'   => 'روز تخمینی شروع قاعدگی',
				'hint'    => 'یادآوری ثبت روز اول.',
				'hour'    => 9,
				'channel' => 'inapp',
				'on'      => true,
				'audience' => 'woman',
			),
			'pms-2'         => array(
				'label'   => 'دو روز پیش از دورهٔ PMS',
				'hint'    => 'برای سبک‌تر کردن برنامه.',
				'hour'    => 20,
				'channel' => 'inapp',
				'on'      => true,
				'audience' => 'woman',
			),
			'pill'          => array(
				'label'   => 'یادآور قرص و مکمل',
				'hint'    => 'ساعت‌های دقیق هر دارو را پایین تنظیم کن.',
				'hour'    => 22,
				'channel' => 'inapp',
				'on'      => false,
				'audience' => 'woman',
			),
			'partner-daily' => array(
				'label'   => 'پیام روزانهٔ همراه',
				'hint'    => 'یک کار کوچک حمایتی برای همسر.',
				'hour'    => 10,
				'channel' => 'inapp',
				'on'      => true,
				'audience' => 'partner',
			),
		);
	}

	public static function channels(): array {
		return array(
			'inapp' => 'اعلان درون‌اپ',
			'email' => 'ایمیل',
			'sms'   => 'پیامک',
		);
	}

	/* --------------------------------------------------------------------- */
	/* تنظیمات کاربر                                                         */
	/* --------------------------------------------------------------------- */

	public static function prefs( int $user_id ): array {
		$saved = get_user_meta( $user_id, 'mb_reminder_prefs', true );
		$saved = is_array( $saved ) ? $saved : array();

		$out = array();
		foreach ( self::triggers() as $key => $def ) {
			$row = is_array( $saved[ $key ] ?? null ) ? $saved[ $key ] : array();
			$out[ $key ] = array(
				'label'    => $def['label'],
				'hint'     => $def['hint'],
				'audience' => $def['audience'],
				'on'       => array_key_exists( 'on', $row ) ? (bool) $row['on'] : (bool) $def['on'],
				'hour'     => self::clean_hour( $row['hour'] ?? $def['hour'] ),
				'channel'  => self::clean_channel( $row['channel'] ?? $def['channel'] ),
			);
		}
		return $out;
	}

	private static function clean_hour( $hour ): int {
		$hour = (int) MB_Jalali::en_num( (string) $hour );
		return max( 0, min( 23, $hour ) );
	}

	private static function clean_channel( $channel ): string {
		$channel = sanitize_key( (string) $channel );
		return array_key_exists( $channel, self::channels() ) ? $channel : 'inapp';
	}

	/** ذخیرهٔ یک تریگر. */
	public static function save_pref( int $user_id, string $trigger, array $data ): bool {
		$trigger = sanitize_key( $trigger );
		if ( ! array_key_exists( $trigger, self::triggers() ) ) {
			return false;
		}
		$saved = get_user_meta( $user_id, 'mb_reminder_prefs', true );
		$saved = is_array( $saved ) ? $saved : array();

		$current = $saved[ $trigger ] ?? array();
		$saved[ $trigger ] = array(
			'on'      => array_key_exists( 'on', $data ) ? (bool) $data['on'] : (bool) ( $current['on'] ?? true ),
			'hour'    => self::clean_hour( $data['hour'] ?? ( $current['hour'] ?? 9 ) ),
			'channel' => self::clean_channel( $data['channel'] ?? ( $current['channel'] ?? 'inapp' ) ),
		);

		update_user_meta( $user_id, 'mb_reminder_prefs', $saved );
		return true;
	}

	/* --------------------------------------------------------------------- */
	/* یادآور دارو (M3)                                                      */
	/* --------------------------------------------------------------------- */

	public static function meds( int $user_id ): array {
		$saved = get_user_meta( $user_id, 'mb_meds_reminder', true );
		$saved = is_array( $saved ) ? $saved : array();

		$out = array();
		foreach ( $saved as $item ) {
			$name = sanitize_text_field( (string) ( $item['name'] ?? '' ) );
			$time = self::clean_time( (string) ( $item['time'] ?? '' ) );
			if ( '' !== $name && '' !== $time ) {
				$out[] = array( 'name' => mb_substr( $name, 0, 60 ), 'time' => $time );
			}
		}
		return $out;
	}

	private static function clean_time( string $time ): string {
		$time = MB_Jalali::en_num( trim( $time ) );
		if ( ! preg_match( '/^(\d{1,2}):(\d{2})$/', $time, $m ) ) {
			return '';
		}
		$h = (int) $m[1];
		$i = (int) $m[2];
		if ( $h < 0 || $h > 23 || $i < 0 || $i > 59 ) {
			return '';
		}
		return sprintf( '%02d:%02d', $h, $i );
	}

	public static function add_med( int $user_id, string $name, string $time ): bool {
		$name = mb_substr( sanitize_text_field( $name ), 0, 60 );
		$time = self::clean_time( $time );
		if ( '' === $name || '' === $time ) {
			return false;
		}
		$list = self::meds( $user_id );
		if ( count( $list ) >= 10 ) {
			return false;
		}
		$list[] = array( 'name' => $name, 'time' => $time );
		update_user_meta( $user_id, 'mb_meds_reminder', $list );
		return true;
	}

	public static function remove_med( int $user_id, int $index ): bool {
		$list = self::meds( $user_id );
		if ( ! isset( $list[ $index ] ) ) {
			return false;
		}
		unset( $list[ $index ] );
		update_user_meta( $user_id, 'mb_meds_reminder', array_values( $list ) );
		return true;
	}

	/* --------------------------------------------------------------------- */
	/* کرون ساعتی                                                            */
	/* --------------------------------------------------------------------- */

	public static function run_hourly(): void {
		global $wpdb;

		$hour   = (int) current_time( 'G' );
		$offset = (int) get_option( 'mb_reminder_offset', 0 );

		$users = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT user_id FROM ' . MB_DB::t( 'profile' ) . ' WHERE onboarded = 1 ORDER BY user_id ASC LIMIT %d OFFSET %d',
				self::BATCH,
				$offset
			)
		);

		foreach ( (array) $users as $uid ) {
			self::run_for_user( (int) $uid, $hour );
		}

		if ( count( (array) $users ) < self::BATCH ) {
			update_option( 'mb_reminder_offset', 0, false );
			return;
		}
		update_option( 'mb_reminder_offset', $offset + self::BATCH, false );
		wp_schedule_single_event( time() + 90, self::HOOK );
	}

	public static function run_for_user( int $uid, int $hour ): void {
		$prefs = self::prefs( $uid );
		$today = MB_Jalali::today();

		// در حالت بارداری، یادآورهای چرخه معنا ندارند.
		$pregnant = MB_Pregnancy::is_active( $uid );

		foreach ( $prefs as $trigger => $pref ) {
			if ( empty( $pref['on'] ) || (int) $pref['hour'] !== $hour ) {
				continue;
			}
			if ( $pregnant && in_array( $trigger, array( 'period-2', 'period-0', 'pms-2' ), true ) ) {
				continue;
			}
			self::fire( $uid, (string) $trigger, (string) $pref['channel'], $today );
		}

		// یادآور دارو ساعت‌های خودش را دارد (مستقل از ساعت تریگر).
		if ( ! empty( $prefs['pill']['on'] ) ) {
			self::fire_meds( $uid, (string) $prefs['pill']['channel'], $hour );
		}
	}

	private static function fire( int $uid, string $trigger, string $channel, string $today ): void {
		switch ( $trigger ) {
			case 'period-2':
			case 'period-0':
				$next = MB_Cycle_Engine::next_period( $uid, $today );
				if ( ! $next ) {
					return;
				}
				$days = MB_Jalali::diff_days( $today, $next );
				$want = 'period-2' === $trigger ? 2 : 0;
				if ( $days !== $want ) {
					return;
				}
				$type  = 'period-2' === $trigger ? 'period_soon' : 'period_today';
				$title = 0 === $want ? 'امروز احتمال شروع قاعدگی است' : 'دو روز تا قاعدگی';
				$body  = 0 === $want
					? 'بر اساس چرخه‌ات امروز روز تخمینی شروع قاعدگی است. ثبت امروز را فراموش نکن.'
					: 'دو روز دیگر قاعدگی تخمینی شروع می‌شود؛ وسایل لازم را آماده داشته باش.';
				if ( MB_Irregular::is_irregular( $uid ) ) {
					$body .= ' چون چرخه‌هایت نوسان دارد، این یک تخمین بازه‌ای است.';
				}
				self::dispatch( $uid, $type, $title, $body, $channel );
				return;

			case 'pms-2':
				$pms = MB_Cycle_Engine::pms_range( $uid, $today );
				if ( empty( $pms['from'] ) || 2 !== MB_Jalali::diff_days( $today, $pms['from'] ) ) {
					return;
				}
				self::dispatch(
					$uid,
					'pms_soon',
					'دو روز تا شروع دورهٔ پیش‌از‌قاعدگی',
					'از پس‌فردا وارد فاز PMS می‌شوی. خواب کافی و برنامهٔ سبک‌تر کمک می‌کند.',
					$channel
				);
				return;

			case 'partner-daily':
				self::partner_daily( $uid, $channel, $today );
				return;
		}
	}

	/** پیام روزانهٔ همراه: روی حساب همسرِ متصل به این بانو فرستاده می‌شود. */
	private static function partner_daily( int $wife_id, string $channel, string $today ): void {
		if ( ! MB_Subscription::is_pro( $wife_id ) ) {
			return;
		}
		$invite = MB_DB::get_active_invite( $wife_id );
		if ( ! $invite || empty( $invite['accepted_by'] ) || empty( $invite['share_support'] ) ) {
			return;
		}
		$partner = (int) $invite['accepted_by'];
		$phase   = MB_Cycle_Engine::phase_of( $wife_id, $today );
		$tips    = MB_Cycle_Engine::support_suggestions( $phase );

		// هیچ دادهٔ خصوصی در متن نیست؛ فقط پیشنهاد عمومی فاز.
		self::dispatch( $partner, 'p_support', 'کار کوچک امروز', (string) ( $tips[0] ?? 'یک پیام محبت‌آمیز بفرست.' ), $channel );
	}

	private static function fire_meds( int $uid, string $channel, int $hour ): void {
		foreach ( self::meds( $uid ) as $index => $med ) {
			if ( (int) substr( $med['time'], 0, 2 ) !== $hour ) {
				continue;
			}
			$type = 'med_' . $index;
			self::dispatch(
				$uid,
				$type,
				'یادآور ' . $med['name'],
				'ساعت ' . MB_Jalali::fa_num( $med['time'] ) . ' — یادت نرود. بعد از مصرف، در ثبت روزانه تیک بزن.',
				$channel
			);
		}
	}

	/** ارسال روی کانال انتخابی + اعلان درون‌اپ همیشه (تا تاریخچه بماند). */
	private static function dispatch( int $uid, string $type, string $title, string $body, string $channel ): void {
		if ( MB_DB::sent_today( $uid, $type ) ) {
			return;
		}
		MB_DB::add_notification( $uid, $type, array( 'title' => $title, 'body' => $body ) );

		if ( 'email' === $channel ) {
			MB_Notify::email_user( $uid, $title, $body );
			return;
		}
		if ( 'sms' === $channel ) {
			// پیامک بخشی از نسخهٔ پیشرفته است.
			$owner = MB_Privacy::is_partner( $uid ) ? MB_Privacy::wife_of( $uid ) : $uid;
			if ( $owner > 0 && MB_Subscription::is_pro( $owner ) ) {
				MB_Notify::sms_user( $uid, $title );
			} else {
				MB_Notify::email_user( $uid, $title, $body );
			}
		}
	}
}
