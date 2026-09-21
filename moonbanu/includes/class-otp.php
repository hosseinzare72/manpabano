<?php
/**
 * M6 — ورود / بازیابی با رمز یک‌بارمصرف (OTP).
 *
 * پیامک از طریق متد SendOtp ملی‌پیامک ارسال می‌شود
 * (POST https://rest.payamak-panel.com/api/SendSMS/SendOtp با username, password, to, from, code).
 * اگر پیامک در دسترس نباشد، کد با ایمیل فرستاده می‌شود (فال‌بک).
 *
 * امنیت:
 *  - کد هرگز به‌صورت خام ذخیره نمی‌شود؛ فقط HMAC آن نگه داشته می‌شود.
 *  - اعتبار ۲۰ دقیقه، حداکثر ۵ تلاش، و محدودیت نرخ روی درخواست کد.
 *  - پاسخ‌ها عمداً مبهم‌اند تا شماره‌های ثبت‌شدهٔ سایت لو نرود.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_OTP {

	/** اعتبار کد (ثانیه). */
	const TTL = 1200; // ۲۰ دقیقه.

	/** حداکثر تلاش برای هر کد. */
	const MAX_ATTEMPTS = 5;

	/** فاصلهٔ لازم میان دو درخواست کد (ثانیه). */
	const RESEND_WAIT = 90;

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route(
			MB_Api::NS,
			'/auth/otp/request',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'ep_request' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			MB_Api::NS,
			'/auth/otp/verify',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'ep_verify' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/* --------------------------------------------------------------------- */
	/* ذخیره‌سازی                                                            */
	/* --------------------------------------------------------------------- */

	private static function key_for( int $user_id ): string {
		return 'mb_otp_' . $user_id;
	}

	private static function hash_code( int $user_id, string $code ): string {
		$secret = ( defined( 'AUTH_KEY' ) ? AUTH_KEY : 'moonbanu' ) . (string) get_option( 'mb_install_token' );
		return hash_hmac( 'sha256', $user_id . '|' . $code, $secret );
	}

	private static function store( int $user_id, string $code ): void {
		set_transient(
			self::key_for( $user_id ),
			array(
				'hash'       => self::hash_code( $user_id, $code ),
				'attempts'   => 0,
				'sent_at'    => time(),
				// انقضای مطلق در خود payload نگه داشته می‌شود تا شمارش تلاش‌های
				// ناموفق نتواند عمر کد را تمدید کند.
				'expires_at' => time() + self::TTL,
			),
			self::TTL
		);
	}

	private static function generate(): string {
		return (string) random_int( 100000, 999999 );
	}

	/* --------------------------------------------------------------------- */
	/* ارسال                                                                 */
	/* --------------------------------------------------------------------- */

	/**
	 * درخواست کد برای یک شناسه (موبایل یا ایمیل).
	 *
	 * @return array{ok:bool,message:string,channel:string}
	 */
	public static function request( string $identity ): array {
		$identity = trim( wp_unslash( $identity ) );
		$generic  = array(
			'ok'      => true,
			'message' => 'اگر این شناسه در سامانه ثبت شده باشد، کد ورود برایش فرستاده شد.',
			'channel' => '',
		);

		if ( '' === $identity ) {
			return array( 'ok' => false, 'message' => 'شماره موبایل یا ایمیل را وارد کن.', 'channel' => '' );
		}

		// محدودیت نرخ بر پایهٔ IP: حداکثر ۵ درخواست در ۱۵ دقیقه.
		if ( ! MB_License::rate_limit( 'otp_request', 5, 900 ) ) {
			return array( 'ok' => false, 'message' => 'درخواست‌های زیادی فرستاده شد. چند دقیقه صبر کن.', 'channel' => '' );
		}

		$user = self::find_user( $identity );
		if ( ! $user ) {
			// پاسخ مبهم: وجود یا نبود حساب لو نمی‌رود.
			return $generic;
		}

		$existing = get_transient( self::key_for( (int) $user->ID ) );
		if ( is_array( $existing ) && ( time() - (int) ( $existing['sent_at'] ?? 0 ) ) < self::RESEND_WAIT ) {
			// پاسخ عمداً همان پاسخ عمومی است: پیام متفاوت، وجود حساب را لو می‌داد.
			return $generic;
		}

		$code = self::generate();
		self::store( (int) $user->ID, $code );

		$mobile = (string) get_user_meta( (int) $user->ID, 'mb_mobile', true );
		$sent   = false;
		$channel = '';

		if ( '' !== $mobile ) {
			$result = self::send_sms( $mobile, $code );
			if ( true === $result ) {
				$sent    = true;
				$channel = 'sms';
			}
		}

		if ( ! $sent ) {
			$ok = MB_Notify::email_user(
				(int) $user->ID,
				'کد ورود ماه‌بانو',
				'<p>کد ورود یک‌بارمصرف تو:</p><p style="font-size:24px;font-weight:700;letter-spacing:4px;direction:ltr">'
				. esc_html( $code ) . '</p><p>این کد ۲۰ دقیقه اعتبار دارد. اگر خودت درخواست نکرده‌ای، نادیده بگیر.</p>'
			);
			if ( $ok ) {
				$sent    = true;
				$channel = 'email';
			}
		}

		if ( ! $sent ) {
			delete_transient( self::key_for( (int) $user->ID ) );
			MB_Plugin::log_security( 'otp_send_failed', array( 'user' => (int) $user->ID ) );
			return array( 'ok' => false, 'message' => 'ارسال کد ممکن نشد. از راه رمز عبور وارد شو یا با پشتیبانی تماس بگیر.', 'channel' => '' );
		}

		$generic['channel'] = $channel;
		return $generic;
	}

	/** ارسال کد با متد SendOtp ملی‌پیامک؛ در غیر این صورت پیامک معمولی. */
	private static function send_sms( string $mobile, string $code ) {
		$provider = (string) MB_Plugin::setting( 'sms_provider', 'melipayamak' );
		$mobile   = preg_replace( '/[^0-9]/', '', MB_Jalali::en_num( $mobile ) );
		if ( '' === $mobile ) {
			return new WP_Error( 'mb_otp_mobile', 'شماره موبایل معتبر نیست.' );
		}

		if ( 'melipayamak' !== $provider ) {
			return MB_Notify::sms( $mobile, 'کد ورود ماه‌بانو: ' . $code );
		}

		$username = (string) MB_Plugin::setting( 'sms_username', '' );
		$password = (string) MB_Plugin::setting( 'sms_api_key', '' );
		$from     = (string) MB_Plugin::setting( 'sms_sender', '' );
		if ( '' === $username || '' === $password || '' === $from ) {
			return new WP_Error( 'mb_otp_cfg', 'تنظیمات پیامک کامل نیست.' );
		}

		$res = wp_remote_post(
			'https://rest.payamak-panel.com/api/SendSMS/SendOtp',
			array(
				'timeout' => 20,
				'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8' ),
				'body'    => array(
					'username' => $username,
					'password' => $password,
					'to'       => $mobile,
					'from'     => $from,
					'code'     => $code,
				),
			)
		);

		if ( is_wp_error( $res ) ) {
			MB_Plugin::log_security( 'otp_sms_error', array( 'msg' => $res->get_error_message() ) );
			return $res;
		}

		$http = (int) wp_remote_retrieve_response_code( $res );
		if ( $http < 200 || $http >= 300 ) {
			return new WP_Error( 'mb_otp_http', 'سرویس پیامک پاسخ خطا داد (کد ' . $http . ').' );
		}

		// ملی‌پیامک خطاها را با HTTP 200 و یک کد عددی کوچک برمی‌گرداند؛
		// recId موفق عددی بزرگ (بیش از ۸ رقم) است.
		$body   = trim( (string) wp_remote_retrieve_body( $res ) );
		$digits = preg_replace( '/[^0-9\-]/', '', $body );
		if ( '' !== $digits && preg_match( '/^-?\d+$/', $digits ) && strlen( ltrim( $digits, '-' ) ) <= 4 ) {
			MB_Plugin::log_security( 'otp_sms_rejected', array( 'code' => $digits ) );
			return new WP_Error( 'mb_otp_provider', 'ارسال کد پذیرفته نشد (کد ' . $digits . ').' );
		}

		return true;
	}

	/* --------------------------------------------------------------------- */
	/* بررسی                                                                 */
	/* --------------------------------------------------------------------- */

	/**
	 * بررسی کد. در صورت درستی، شناسهٔ کاربر برگردانده می‌شود.
	 *
	 * @return array{ok:bool,message:string,user_id:int}
	 */
	public static function verify( string $identity, string $code ): array {
		$fail = array( 'ok' => false, 'message' => 'کد درست نیست یا منقضی شده است.', 'user_id' => 0 );

		$code = preg_replace( '/[^0-9]/', '', MB_Jalali::en_num( $code ) );
		if ( 6 !== strlen( (string) $code ) ) {
			return $fail;
		}

		$user = self::find_user( trim( wp_unslash( $identity ) ) );
		if ( ! $user ) {
			return $fail;
		}

		$key  = self::key_for( (int) $user->ID );
		$data = get_transient( $key );
		if ( ! is_array( $data ) || empty( $data['hash'] ) ) {
			return $fail;
		}

		// انقضای مطلق، مستقل از TTL ذخیره‌ساز.
		if ( (int) ( $data['expires_at'] ?? 0 ) > 0 && time() > (int) $data['expires_at'] ) {
			delete_transient( $key );
			return $fail;
		}

		$attempts = (int) ( $data['attempts'] ?? 0 );
		if ( $attempts >= self::MAX_ATTEMPTS ) {
			delete_transient( $key );
			MB_Plugin::log_security( 'otp_attempts_exceeded', array( 'user' => (int) $user->ID ) );
			return array( 'ok' => false, 'message' => 'تعداد تلاش‌ها بیش از حد شد. کد تازه درخواست کن.', 'user_id' => 0 );
		}

		if ( ! hash_equals( (string) $data['hash'], self::hash_code( (int) $user->ID, (string) $code ) ) ) {
			$data['attempts'] = $attempts + 1;
			// TTL باقی‌مانده حفظ می‌شود تا شمارش تلاش با هر خطا ریست نشود.
			// پیش از این self::TTL دوباره نوشته می‌شد و هر تلاش ناموفق عمر کد را
			// ۲۰ دقیقهٔ کامل تمدید می‌کرد.
			$remaining = (int) ( $data['expires_at'] ?? 0 ) - time();
			if ( $remaining <= 0 ) {
				delete_transient( $key );
				return $fail;
			}
			set_transient( $key, $data, $remaining );
			MB_License::note_validation_error( 'otp_verify' );
			return $fail;
		}

		delete_transient( $key );
		return array( 'ok' => true, 'message' => 'کد تأیید شد.', 'user_id' => (int) $user->ID );
	}

	/** یافتن کاربر با موبایل، ایمیل یا نام کاربری. */
	private static function find_user( string $identity ): ?WP_User {
		if ( is_email( $identity ) ) {
			$user = get_user_by( 'email', $identity );
			return $user ? $user : null;
		}

		$mobile = MB_Auth::normalize_mobile( $identity );
		if ( '' !== $mobile ) {
			$users = get_users(
				array(
					'meta_key'    => 'mb_mobile',
					'meta_value'  => $mobile,
					'number'      => 1,
					'fields'      => 'ID',
					'count_total' => false,
				)
			);
			if ( ! empty( $users ) ) {
				$user = get_userdata( (int) $users[0] );
				return $user ? $user : null;
			}
		}

		$user = get_user_by( 'login', sanitize_user( $identity, true ) );
		return $user ? $user : null;
	}

	/* --------------------------------------------------------------------- */
	/* Endpoints                                                             */
	/* --------------------------------------------------------------------- */

	public static function ep_request( WP_REST_Request $r ) {
		if ( ! MB_Auth::verify_guest_nonce( $r ) ) {
			return new WP_Error( 'mb_nonce', 'صفحه قدیمی است. یک‌بار نوسازی کن و دوباره تلاش کن.', array( 'status' => 403 ) );
		}
		$result = self::request( (string) $r->get_param( 'identity' ) );
		if ( ! $result['ok'] ) {
			// ۴۰۰ برای ورودی ناقص، ۴۲۹ فقط برای محدودیت نرخ.
			$status = '' === trim( (string) $r->get_param( 'identity' ) ) ? 400 : 429;
			return new WP_Error( 'mb_otp_request', $result['message'], array( 'status' => $status ) );
		}
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => $result['message'],
				'channel' => $result['channel'],
				'ttl'     => self::TTL,
			),
			200
		);
	}

	public static function ep_verify( WP_REST_Request $r ) {
		if ( ! MB_Auth::verify_guest_nonce( $r ) ) {
			return new WP_Error( 'mb_nonce', 'صفحه قدیمی است. یک‌بار نوسازی کن و دوباره تلاش کن.', array( 'status' => 403 ) );
		}
		if ( ! MB_License::rate_limit( 'otp_verify', 20, 900 ) ) {
			return new WP_Error( 'mb_rate', 'تلاش‌های زیاد. چند دقیقه بعد دوباره امتحان کن.', array( 'status' => 429 ) );
		}
		$result = self::verify( (string) $r->get_param( 'identity' ), (string) $r->get_param( 'code' ) );
		if ( ! $result['ok'] || $result['user_id'] <= 0 ) {
			return new WP_Error( 'mb_otp_verify', $result['message'], array( 'status' => 401 ) );
		}
		return MB_Auth::sign_in_via_otp( $result['user_id'] );
	}
}
