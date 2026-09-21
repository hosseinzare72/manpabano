<?php
/**
 * اعلان‌ها: درون‌اپ، ایمیل (قالب RTL فارسی) و پیامک (درگاه داخلی).
 * کرون روزانه ساعت ۰۶:۰۰ به وقت سایت.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Notify {

	public static function init(): void {
		add_action( 'mb_daily_06', array( __CLASS__, 'run_daily' ) );
	}

	public static function mail_content_type( $type = '' ): string {
		return 'text/html';
	}

	/**
	 * ارسال ایمیل HTML؛ فیلتر content-type فقط دور همین ارسال فعال می‌شود تا
	 * ایمیل‌های بقیه پلاگین‌ها و خود وردپرس HTML اجباری نشوند.
	 */
	public static function mail_html( $to, string $subject, string $html ): bool {
		add_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
		$sent = wp_mail( $to, $subject, $html );
		remove_filter( 'wp_mail_content_type', array( __CLASS__, 'mail_content_type' ) );
		return (bool) $sent;
	}

	/* --------------------------------------------------------------------- */
	/* زمان‌بندی                                                             */
	/* --------------------------------------------------------------------- */

	public static function schedule(): void {
		if ( wp_next_scheduled( 'mb_daily_06' ) ) {
			return;
		}
		$tz    = wp_timezone();
		$now   = new DateTimeImmutable( 'now', $tz );
		$six   = $now->setTime( 6, 0, 0 );
		if ( $six <= $now ) {
			$six = $six->modify( '+1 day' );
		}
		wp_schedule_event( $six->getTimestamp(), 'daily', 'mb_daily_06' );
	}

	public static function unschedule(): void {
		$ts = wp_next_scheduled( 'mb_daily_06' );
		if ( $ts ) {
			wp_unschedule_event( $ts, 'mb_daily_06' );
		}
	}

	/* --------------------------------------------------------------------- */
	/* کرون روزانه                                                           */
	/* --------------------------------------------------------------------- */

	const BATCH = 200;

	/** پردازش دسته‌ای تا کرون روی سایت‌های پرکاربر تایم‌اوت نکند. */
	public static function run_daily(): void {
		global $wpdb;
		$offset = (int) get_option( 'mb_daily_offset', 0 );
		$users  = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT user_id FROM ' . MB_DB::t( 'profile' ) . ' WHERE onboarded = 1 ORDER BY user_id ASC LIMIT %d OFFSET %d',
				self::BATCH,
				$offset
			)
		);

		foreach ( (array) $users as $uid ) {
			$uid = (int) $uid;
			self::woman_triggers( $uid );
			self::partner_triggers( $uid );
		}

		if ( count( (array) $users ) < self::BATCH ) {
			update_option( 'mb_daily_offset', 0, false );
			return;
		}
		update_option( 'mb_daily_offset', $offset + self::BATCH, false );
		// ادامه دسته بعدی در همان روز.
		wp_schedule_single_event( time() + 120, 'mb_daily_06' );
	}

	/**
	 * از نسخهٔ ۳٫۰ تریگرهای period-2 / period-0 / pms-2 به MB_Reminders منتقل
	 * شده‌اند تا ساعت و کانال دلخواه کاربر رعایت شود. این متد فقط برای کاربرانی
	 * که هیچ تنظیمی ندارند نگهبان عقب‌افتادگی است و به‌خاطر sent_today هرگز
	 * پیام تکراری نمی‌فرستد.
	 */
	private static function woman_triggers( int $uid ): void {
		$prefs = get_user_meta( $uid, 'mb_reminder_prefs', true );
		if ( is_array( $prefs ) && ! empty( $prefs ) ) {
			return; // مالکیت با MB_Reminders است.
		}
		if ( MB_Pregnancy::is_active( $uid ) ) {
			return; // در حالت بارداری پیش‌بینی چرخه خاموش است.
		}
		MB_Reminders::run_for_user( $uid, (int) current_time( 'G' ) );
	}

	private static function partner_triggers( int $wife_id ): void {
		if ( ! MB_Subscription::is_pro( $wife_id ) ) {
			return;
		}
		if ( MB_Pregnancy::is_active( $wife_id ) ) {
			return; // در حالت بارداری هیچ پیام چرخه‌ای برای همسر نمی‌رود.
		}
		$invite = MB_DB::get_active_invite( $wife_id );
		if ( ! $invite || empty( $invite['accepted_by'] ) ) {
			return;
		}
		$partner = (int) $invite['accepted_by'];
		$today   = MB_Jalali::today();
		$wife    = get_userdata( $wife_id );
		$name    = $wife ? ( $wife->first_name ? $wife->first_name : $wife->display_name ) : 'همسرتان';

		// هشدار PMS (share_pms)
		if ( ! empty( $invite['share_pms'] ) ) {
			$pms = MB_Cycle_Engine::pms_range( $wife_id, $today );
			if ( ! empty( $pms['from'] ) && 2 === MB_Jalali::diff_days( $today, $pms['from'] ) && ! MB_DB::sent_today( $partner, 'p_pms' ) ) {
				$title = $name . ' از دو روز آینده وارد دوره پیش‌از‌قاعدگی می‌شود';
				$body  = 'این روزها به آرامش بیشتر و انرژی کمتر نیاز دارد. جزئیات خصوصی نمایش داده نمی‌شود.';
				MB_DB::add_notification( $partner, 'p_pms', array( 'title' => $title, 'body' => $body ) );
				self::email_user( $partner, $title, $body );
				self::sms_user( $partner, $title );
			}
		}

		// روز اول و آخر قاعدگی (share_period)
		if ( ! empty( $invite['share_period'] ) ) {
			$period = MB_Cycle_Engine::period_range( $wife_id, $today );
			if ( ! empty( $period['from'] ) && $period['from'] === $today && ! MB_DB::sent_today( $partner, 'p_period_start' ) ) {
				MB_DB::add_notification( $partner, 'p_period_start', array( 'title' => 'امروز روز اول قاعدگی ' . $name . ' است', 'body' => 'کمی سبک‌تر کردن برنامه امروز کمک بزرگی است.' ) );
			}
			if ( ! empty( $period['to'] ) && $period['to'] === $today && ! MB_DB::sent_today( $partner, 'p_period_end' ) ) {
				MB_DB::add_notification( $partner, 'p_period_end', array( 'title' => 'امروز آخرین روز تخمینی قاعدگی است', 'body' => 'انرژی کم‌کم برمی‌گردد.' ) );
			}
		}

		// پیشنهاد حمایت روزانه از نسخهٔ ۳٫۰ توسط تریگر partner-daily در
		// MB_Reminders فرستاده می‌شود (با ساعت و کانال دلخواه بانو).
	}

	/* --------------------------------------------------------------------- */
	/* رویدادهای موردی                                                       */
	/* --------------------------------------------------------------------- */

	public static function renewal_reminder( int $uid, int $days ): void {
		$type = 'renew_' . $days;
		if ( MB_DB::sent_today( $uid, $type ) ) {
			return;
		}
		$title = 0 === $days ? 'امروز روز تمدید اشتراک ماه‌بانو است' : sprintf( '%s روز تا پایان اشتراک ماه‌بانو', MB_Jalali::fa_num( $days ) );
		$body  = 'با تمدید، پیش‌بینی سه‌چرخه‌ای، تحلیل شخصی و همراهی همسر فعال می‌ماند. پس از پایان، سه روز مهلت ارفاق داری.';
		MB_DB::add_notification( $uid, $type, array( 'title' => $title, 'body' => $body ) );
		self::email_user( $uid, $title, $body . '<br><a href="' . esc_url( MB_UI::app_url( '#/checkout' ) ) . '">تمدید اشتراک</a>' );
	}

	public static function invite_expiring( int $wife_id ): void {
		if ( MB_DB::sent_today( $wife_id, 'invite_expiring' ) ) {
			return;
		}
		MB_DB::add_notification( $wife_id, 'invite_expiring', array( 'title' => 'دعوت‌نامه همراه تا ۶ ساعت دیگر منقضی می‌شود', 'body' => 'اگر لازم است، دعوت‌نامه تازه بساز.' ) );
	}

	/** ارسال دعوت‌نامه با ایمیل یا پیامک. */
	public static function send_invite( array $invite, string $channel, string $target ) {
		$link = add_query_arg( 'mb_invite', $invite['token'], home_url( '/' ) );
		$wife = wp_get_current_user();
		$name = $wife->first_name ? $wife->first_name : $wife->display_name;

		if ( 'sms' === $channel ) {
			$text = sprintf( 'ماه‌بانو: %s شما را برای همراهی محترمانه دعوت کرده است. %s (اعتبار ۷۲ ساعت)', $name, $link );
			return self::sms( $target, $text );
		}

		$body = self::email_wrap(
			'دعوت‌نامه همراه ماه',
			sprintf(
				'<p>%s شما را دعوت کرده تا در روزهای چرخه همراهش باشید.</p><p>شما فقط رنگ روزهای پیش‌رو، تاریخ تخمینی قاعدگی بعدی و پیشنهادهای حمایتی را می‌بینید. هیچ نشانه، یادداشت یا داده خصوصی نمایش داده نمی‌شود.</p><p style="text-align:center"><a class="mb-btn" href="%s">پذیرش دعوت</a></p><p style="font-size:12px;color:#8a8194">اعتبار این لینک ۷۲ ساعت است و هر لحظه قابل لغو است.</p>',
				esc_html( $name ),
				esc_url( $link )
			)
		);
		return self::mail_html( sanitize_email( $target ), 'دعوت‌نامه همراه ماه | ماه‌بانو', $body );
	}

	/** پیام حمایت دستی از سمت بانو. */
	public static function support_ping( int $wife_id, int $partner_id, string $text ): bool {
		$wife = get_userdata( $wife_id );
		$name = $wife ? ( $wife->first_name ? $wife->first_name : $wife->display_name ) : '';
		$text = '' !== $text ? $text : 'امروز به کمی همراهی بیشتر نیاز دارم.';
		MB_DB::add_notification( $partner_id, 'support_ping', array( 'title' => 'پیامی از ' . $name, 'body' => sanitize_text_field( $text ) ) );
		self::email_user( $partner_id, 'پیامی از ' . $name, esc_html( $text ) );
		return true;
	}

	/** فاکتور اشتراک. */
	public static function send_invoice( int $sub_id, string $payment_ref = '' ): void {
		$sub = MB_Subscription::get_by_id( $sub_id );
		if ( ! $sub ) {
			return;
		}
		$user = get_userdata( (int) $sub['user_id'] );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return;
		}
		$html = MB_UI::render_template(
			'invoice',
			array(
				'sub'         => $sub,
				'user'        => $user,
				'payment_ref' => $payment_ref,
			)
		);
		self::mail_html( $user->user_email, 'فاکتور اشتراک ماه‌بانو', self::email_wrap( 'فاکتور اشتراک', $html ) );
	}

	/* --------------------------------------------------------------------- */
	/* احراز هویت درون‌اپ                                                     */
	/* --------------------------------------------------------------------- */

	/** ارسال کد بازیابی رمز (ایمیل + پیامک اگر تنظیم شده باشد). */
	public static function reset_code( int $uid, string $code ): void {
		$user = get_userdata( $uid );
		if ( ! $user ) {
			return;
		}
		$fa   = MB_Jalali::fa_num( $code );
		$html = '<p>سلام ' . esc_html( $user->first_name ? $user->first_name : $user->display_name ) . '،</p>'
			. '<p>کد بازیابی رمز عبور تو در ' . esc_html( MB_Plugin::brand( 'name' ) ) . ':</p>'
			. '<p style="font-size:30px;font-weight:800;letter-spacing:8px;direction:ltr;text-align:center;margin:18px 0">' . esc_html( $code ) . '</p>'
			. '<p>این کد ۲۰ دقیقه اعتبار دارد و فقط یک‌بار استفاده می‌شود. اگر خودت درخواست نداده‌ای، این ایمیل را نادیده بگیر.</p>'
			. '<p><a href="' . esc_url( MB_UI::app_url( '#/forgot' ) ) . '">بازگشت به اپ</a></p>';

		self::mail_html( $user->user_email, 'کد بازیابی رمز ' . MB_Plugin::brand( 'name' ), self::email_wrap( 'بازیابی رمز عبور', $html ) );

		$mobile = (string) get_user_meta( $uid, 'mb_mobile', true );
		if ( '' !== $mobile ) {
			self::sms( $mobile, 'کد بازیابی ' . MB_Plugin::brand( 'name' ) . ': ' . $code );
		}
		unset( $fa );
	}

	/* --------------------------------------------------------------------- */
	/* پشتیبانی                                                             */
	/* --------------------------------------------------------------------- */

	/** اطلاع مدیر از تیکت تازه یا پیام تازهٔ کاربر. */
	public static function ticket_admin_alert( int $ticket_id, string $subject, string $body, bool $is_reply = false ): void {
		$to = sanitize_email( (string) MB_Plugin::setting( 'support_email', MB_Plugin::setting( 'admin_email', get_option( 'admin_email' ) ) ) );
		if ( ! is_email( $to ) ) {
			return;
		}
		$title = $is_reply ? 'پیام تازه در درخواست پشتیبانی' : 'درخواست پشتیبانی تازه';
		$link  = admin_url( 'admin.php?page=moonbanu&tab=support&ticket=' . $ticket_id );
		$html  = '<p><strong>' . esc_html( $title ) . ' #' . (int) $ticket_id . '</strong></p>'
			. '<p>موضوع: ' . esc_html( $subject ) . '</p>'
			. '<p style="white-space:pre-line;background:#f6f5f8;padding:12px;border-radius:8px">' . esc_html( wp_html_excerpt( $body, 900, '…' ) ) . '</p>'
			. '<p><a href="' . esc_url( $link ) . '">پاسخ در پنل مدیریت</a></p>';

		self::mail_html( $to, $title . ' #' . $ticket_id, self::email_wrap( $title, $html ) );
	}

	/** اطلاع کاربر از پاسخ پشتیبانی. */
	public static function ticket_user_reply( int $uid, int $ticket_id, string $subject, string $body ): void {
		$user = get_userdata( $uid );
		if ( ! $user ) {
			return;
		}
		$html = '<p>سلام ' . esc_html( $user->first_name ? $user->first_name : $user->display_name ) . '،</p>'
			. '<p>برای درخواست «' . esc_html( $subject ) . '» پاسخ تازه‌ای ثبت شد:</p>'
			. '<p style="white-space:pre-line;background:#f6f5f8;padding:12px;border-radius:8px">' . esc_html( wp_html_excerpt( $body, 900, '…' ) ) . '</p>'
			. '<p><a href="' . esc_url( MB_UI::app_url( '#/support/' . $ticket_id ) ) . '">دیدن گفت‌وگو در اپ</a></p>';

		self::mail_html( $user->user_email, 'پاسخ پشتیبانی ' . MB_Plugin::brand( 'name' ), self::email_wrap( 'پاسخ پشتیبانی', $html ) );
	}

	public static function admin_alert( string $subject, string $message ): void {
		$to = sanitize_email( (string) MB_Plugin::setting( 'admin_email', get_option( 'admin_email' ) ) );
		if ( ! is_email( $to ) ) {
			return;
		}
		self::mail_html( $to, $subject, self::email_wrap( $subject, '<p>' . esc_html( $message ) . '</p>' ) );
	}

	/* --------------------------------------------------------------------- */
	/* کانال‌ها                                                              */
	/* --------------------------------------------------------------------- */

	public static function email_user( int $uid, string $subject, string $body_html ): bool {
		$user = get_userdata( $uid );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}
		return self::mail_html( $user->user_email, $subject, self::email_wrap( $subject, '<p>' . $body_html . '</p>' ) );
	}

	public static function sms_user( int $uid, string $text ): bool {
		$mobile = (string) get_user_meta( $uid, 'mb_mobile', true );
		if ( '' === $mobile ) {
			return false;
		}
		return self::sms( $mobile, $text );
	}

	/** پیامک با درگاه داخلی (کاوه‌نگار یا ملی‌پیامک). */
	public static function sms( string $to, string $text ) {
		$provider = (string) MB_Plugin::setting( 'sms_provider', 'melipayamak' );
		$api      = (string) MB_Plugin::setting_secret( 'sms_api_key', '' );
		$sender   = (string) MB_Plugin::setting( 'sms_sender', '' );
		$to       = preg_replace( '/[^0-9]/', '', MB_Jalali::en_num( $to ) );

		if ( '' === $to ) {
			return new WP_Error( 'mb_sms_cfg', 'شماره مقصد پیامک تنظیم نشده است.' );
		}

		if ( 'melipayamak' === $provider ) {
			$username = (string) MB_Plugin::setting_secret( 'sms_username', '' );
			if ( '' === $username || '' === $api || '' === $sender ) {
				return new WP_Error( 'mb_sms_cfg', 'نام کاربری، API Key/رمز عبور و شماره فرستنده ملی‌پیامک را کامل کنید.' );
			}
			$res = wp_remote_post(
				'https://rest.payamak-panel.com/api/SendSMS/SendSMS',
				array(
					'timeout' => 20,
					'headers' => array(
						'Accept'       => 'application/json, text/plain, */*',
						'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
					),
					'body'    => array(
						'username' => $username,
						'password' => $api,
						'to'       => $to,
						'from'     => $sender,
						'text'     => $text,
					),
				)
			);
		} else {
			if ( '' === $api || '' === $sender ) {
				return new WP_Error( 'mb_sms_cfg', 'API Key و شماره فرستنده پیامک را کامل کنید.' );
			}
			$url = sprintf( 'https://api.kavenegar.com/v1/%s/sms/send.json', rawurlencode( $api ) );
			$res = wp_remote_post(
				$url,
				array(
					'timeout' => 20,
					'body'    => array(
						'receptor' => $to,
						'sender'   => $sender,
						'message'  => $text,
					),
				)
			);
		}

		if ( is_wp_error( $res ) ) {
			MB_Plugin::log_security( 'sms_failed', array( 'msg' => $res->get_error_message() ) );
			return $res;
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		if ( $code < 200 || $code >= 300 ) {
			return new WP_Error( 'mb_sms_http', 'سرویس پیامک پاسخ خطا داد (کد ' . $code . ').' );
		}
		if ( 'melipayamak' === $provider ) {
			$body = trim( (string) wp_remote_retrieve_body( $res ) );
			// ملی‌پیامک بسیاری از خطاها را با HTTP 200 و یک کد عددی برمی‌گرداند.
			$error_codes = array( 0, 2, 3, 4, 5, 6, 7, 9, 10, 11, 12, 14, 15, 16, 17, 18, 35, 108, 109, 110, 111 );
			if ( preg_match( '/^-?\d+$/', $body ) && in_array( (int) $body, $error_codes, true ) ) {
				return new WP_Error( 'mb_sms_provider', 'ملی‌پیامک ارسال را نپذیرفت (کد ' . (int) $body . ').' );
			}
		}
		return true;
	}

	/** قالب ایمیل RTL. */
	public static function email_wrap( string $title, string $content ): string {
		$logo = esc_html( 'ماه‌بانو' );
		return '<!DOCTYPE html><html dir="rtl" lang="fa"><head><meta charset="utf-8"><title>' . esc_html( $title ) . '</title></head>
<body style="margin:0;padding:24px;background:#0B0713;font-family:Tahoma,system-ui,sans-serif;color:#F8F3F9">
<div style="max-width:520px;margin:0 auto;background:#150E22;border:1px solid rgba(255,255,255,.105);border-radius:24px;padding:24px;direction:rtl;text-align:right">
<div style="font-size:20px;font-weight:800;color:#D9B45B;text-align:center;margin-bottom:6px">' . $logo . '</div>
<h1 style="font-size:17px;margin:0 0 14px;color:#F8F3F9">' . esc_html( $title ) . '</h1>
<div style="font-size:13px;line-height:2;color:#ABA0BC">' . $content . '</div>
<div style="margin-top:18px;padding-top:14px;border-top:1px solid rgba(255,255,255,.105);font-size:11px;color:#7C7191">' . esc_html( MB_Plugin::disclaimer() ) . '</div>
</div>
<style>.mb-btn{display:inline-block;background:linear-gradient(135deg,#FDEFC4,#E7C877 38%,#C79A3E 78%,#9C7228);color:#2A1D04 !important;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:16px;margin:10px 0}</style>
</body></html>';
	}
}
