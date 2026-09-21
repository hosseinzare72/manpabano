<?php
/**
 * ورود، ثبت‌نام و بازیابی رمز — کاملاً درون اپلیکیشن.
 *
 * هیچ مسیری به wp-login.php نمی‌رود: همه فرم‌ها درون SPA رندر می‌شوند و با
 * REST کار می‌کنند. برای کاربران اپ، wp-login.php به اپ برگردانده می‌شود.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Auth {

	/** نام اکشن nonce فرم‌های مهمان. */
	const NONCE = 'mb_auth';

	/** طول حداقلی رمز. */
	const MIN_PASS = 8;

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );

		// کاربران اپ هرگز صفحهٔ وردپرس را نمی‌بینند.
		add_action( 'login_init', array( __CLASS__, 'maybe_divert_login' ) );
		add_filter( 'login_url', array( __CLASS__, 'filter_login_url' ), 10, 3 );
		add_filter( 'register_url', array( __CLASS__, 'filter_register_url' ) );
		add_filter( 'lostpassword_url', array( __CLASS__, 'filter_lost_url' ), 10, 2 );
		add_filter( 'logout_redirect', array( __CLASS__, 'filter_logout_redirect' ), 10, 3 );

		// نقش اپ هیچ دسترسی‌ای به داشبورد ندارد.
		add_action( 'admin_init', array( __CLASS__, 'block_dashboard' ) );
		add_filter( 'show_admin_bar', array( __CLASS__, 'filter_admin_bar' ) );
	}

	/* --------------------------------------------------------------------- */
	/* مسیرهای REST                                                          */
	/* --------------------------------------------------------------------- */

	public static function register_routes(): void {
		$open = '__return_true';
		$ns   = MB_Api::NS;

		register_rest_route( $ns, '/auth/login', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_login' ), 'permission_callback' => $open ) );
		register_rest_route( $ns, '/auth/register', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_register' ), 'permission_callback' => $open ) );
		register_rest_route( $ns, '/auth/forgot', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_forgot' ), 'permission_callback' => $open ) );
		register_rest_route( $ns, '/auth/reset', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_reset' ), 'permission_callback' => $open ) );
		register_rest_route( $ns, '/auth/logout', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_logout' ), 'permission_callback' => array( 'MB_Api', 'can_use' ) ) );
		register_rest_route( $ns, '/auth/account', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_account' ), 'permission_callback' => array( 'MB_Api', 'can_use' ) ) );
	}

	/* --------------------------------------------------------------------- */
	/* کمکی‌ها                                                               */
	/* --------------------------------------------------------------------- */

	/** nonce تازه برای فرم‌های مهمان. */
	public static function nonce(): string {
		return wp_create_nonce( self::NONCE );
	}

	/**
	 * بررسی nonce فرم مهمان — نسخهٔ عمومی.
	 *
	 * مسیرهای OTP در MB_OTP هیچ بررسی nonce نداشتند در حالی‌که همهٔ مسیرهای
	 * احراز هویت دیگر داشتند؛ این متد همان قاعده را در اختیارشان می‌گذارد.
	 */
	public static function verify_guest_nonce( WP_REST_Request $r ): bool {
		return self::check_nonce( $r );
	}

	/** بررسی nonce فرم مهمان. */
	private static function check_nonce( WP_REST_Request $r ): bool {
		$nonce = (string) $r->get_param( 'mb_nonce' );
		if ( '' === $nonce ) {
			$nonce = (string) $r->get_header( 'x_mb_nonce' );
		}
		return (bool) wp_verify_nonce( $nonce, self::NONCE );
	}

	/** پاسخ استاندارد پس از احراز موفق: nonce تازهٔ REST برای سشن جدید. */
	private static function session_response( WP_User $user, string $message ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'ok'         => true,
				'message'    => $message,
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'auth_nonce' => self::nonce(),
				'user'       => array(
					'name'  => $user->first_name ? $user->first_name : $user->display_name,
					'email' => $user->user_email,
				),
				'onboarded'  => MB_DB::is_onboarded( (int) $user->ID ) ? 1 : 0,
				'redirect'   => MB_Privacy::is_partner( (int) $user->ID ) ? 'partner' : ( MB_DB::is_onboarded( (int) $user->ID ) ? 'home' : 'onboarding' ),
			),
			200
		);
	}

	/**
	 * ورود کاربر در همین درخواست.
	 *
	 * نکتهٔ مهم: wp_set_auth_cookie آرایهٔ $_COOKIE را به‌روز نمی‌کند، پس
	 * wp_get_session_token() هنوز توکن قدیمی (یا خالی) را می‌خواند و nonce ای
	 * که بعد از ورود می‌سازیم با درخواست بعدی هم‌خوان نمی‌شد. کوکی تازه را
	 * درجا در $_COOKIE می‌نشانیم تا nonce برگشتی معتبر باشد.
	 */
	private static function sign_in( WP_User $user, bool $remember = true ): void {
		$capture = static function ( $cookie ) {
			if ( defined( 'LOGGED_IN_COOKIE' ) ) {
				$_COOKIE[ LOGGED_IN_COOKIE ] = $cookie;
			}
		};
		add_action( 'set_logged_in_cookie', $capture, 10, 1 );

		wp_set_current_user( (int) $user->ID, $user->user_login );
		wp_set_auth_cookie( (int) $user->ID, $remember );

		remove_action( 'set_logged_in_cookie', $capture, 10 );

		do_action( 'wp_login', $user->user_login, $user );
	}

	/** پیام خطای یکسان تا حساب‌های موجود لو نروند. */
	/**
	 * M6 — ورود پس از تأیید موفق کد یک‌بارمصرف.
	 * تنها MB_OTP این متد را صدا می‌زند و شناسه را فقط بعد از hash_equals می‌دهد.
	 */
	public static function sign_in_via_otp( int $user_id ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			// پیام رمز عبور برای جریان کد یک‌بارمصرف بی‌ربط بود.
			return new WP_Error( 'mb_auth', 'کد درست نیست یا منقضی شده است.', array( 'status' => 401 ) );
		}
		self::sign_in( $user, true );
		MB_Plugin::log_security( 'otp_login_ok', array( 'user' => $user_id ) );
		return self::session_response( $user, 'خوش آمدی' );
	}

	private static function generic_error(): WP_Error {
		return new WP_Error( 'mb_auth', 'ایمیل یا رمز عبور درست نیست.', array( 'status' => 401 ) );
	}

	/** نرمال‌سازی موبایل ایرانی به ۱۱ رقم. */
	public static function normalize_mobile( string $raw ): string {
		$digits = preg_replace( '/[^0-9]/', '', MB_Jalali::en_num( $raw ) );
		if ( '' === $digits ) {
			return '';
		}
		if ( 0 === strpos( $digits, '0098' ) ) {
			$digits = '0' . substr( $digits, 4 );
		} elseif ( 0 === strpos( $digits, '98' ) && 12 === strlen( $digits ) ) {
			$digits = '0' . substr( $digits, 2 );
		} elseif ( 10 === strlen( $digits ) && '9' === $digits[0] ) {
			$digits = '0' . $digits;
		}
		return preg_match( '/^09[0-9]{9}$/', $digits ) ? $digits : '';
	}

	/** آیا این موبایل روی حساب دیگری ثبت شده است؟ */
	public static function mobile_taken( string $mobile, int $except_user_id = 0 ): bool {
		if ( '' === $mobile ) {
			return false;
		}
		$found = get_users(
			array(
				'meta_key'    => 'mb_mobile',
				'meta_value'  => $mobile,
				'number'      => 2,
				'fields'      => 'ID',
				'count_total' => false,
			)
		);
		foreach ( (array) $found as $id ) {
			if ( (int) $id !== $except_user_id ) {
				return true;
			}
		}
		return false;
	}

	/** نام کاربری یکتا از ایمیل. */
	private static function unique_login( string $email ): string {
		$base = sanitize_user( (string) strstr( $email, '@', true ), true );
		if ( '' === $base || strlen( $base ) < 3 ) {
			$base = 'mb' . wp_rand( 1000, 9999 );
		}
		$login = $base;
		$i     = 1;
		while ( username_exists( $login ) ) {
			$login = $base . $i;
			++$i;
			if ( $i > 200 ) {
				$login = $base . wp_generate_password( 6, false, false );
				break;
			}
		}
		return $login;
	}

	/** بررسی قدرت رمز. */
	private static function password_error( string $pass ): ?string {
		if ( mb_strlen( $pass ) < self::MIN_PASS ) {
			return 'رمز عبور باید دست‌کم ' . MB_Jalali::fa_num( self::MIN_PASS ) . ' نویسه باشد.';
		}
		if ( ! preg_match( '/[A-Za-z\x{0600}-\x{06FF}]/u', $pass ) || ! preg_match( '/[0-9]/', $pass ) ) {
			return 'رمز عبور باید هم حرف و هم عدد داشته باشد.';
		}
		return null;
	}

	/* --------------------------------------------------------------------- */
	/* ورود                                                                  */
	/* --------------------------------------------------------------------- */

	public static function ep_login( WP_REST_Request $r ) {
		if ( is_user_logged_in() ) {
			$current = wp_get_current_user();
			return self::session_response( $current, 'قبلاً وارد شده‌ای.' );
		}
		if ( ! self::check_nonce( $r ) ) {
			return new WP_Error( 'mb_nonce', 'صفحه قدیمی است. یک‌بار نوسازی کن و دوباره تلاش کن.', array( 'status' => 403 ) );
		}
		if ( ! MB_License::rate_limit( 'login', 12, 600 ) ) {
			return new WP_Error( 'mb_rate', 'تلاش‌های ناموفق زیاد بود. چند دقیقه بعد دوباره امتحان کن.', array( 'status' => 429 ) );
		}

		$ident = trim( (string) $r->get_param( 'identity' ) );
		$pass  = (string) $r->get_param( 'password' );
		if ( '' === $ident || '' === $pass ) {
			return new WP_Error( 'mb_auth', 'ایمیل و رمز عبور را وارد کن.', array( 'status' => 400 ) );
		}

		$user = self::find_user( $ident );
		if ( ! $user ) {
			MB_Plugin::log_security( 'login_unknown_identity', array( 'ident' => substr( $ident, 0, 40 ) ) );
			return self::generic_error();
		}

		if ( ! wp_check_password( $pass, $user->user_pass, (int) $user->ID ) ) {
			MB_Plugin::log_security( 'login_bad_password', array( 'user' => (int) $user->ID ) );
			return self::generic_error();
		}

		self::sign_in( $user, (bool) $r->get_param( 'remember' ) );
		MB_Plugin::log_security( 'login_ok', array( 'user' => (int) $user->ID ) );
		return self::session_response( $user, 'خوش آمدی' );
	}

	/** پیدا کردن کاربر با ایمیل، نام کاربری یا موبایل. */
	private static function find_user( string $ident ): ?WP_User {
		if ( is_email( $ident ) ) {
			$user = get_user_by( 'email', sanitize_email( $ident ) );
			return $user ? $user : null;
		}
		$mobile = self::normalize_mobile( $ident );
		if ( '' !== $mobile ) {
			$found = get_users(
				array(
					'meta_key'   => 'mb_mobile',
					'meta_value' => $mobile,
					'number'     => 1,
					'fields'     => 'ID',
				)
			);
			if ( ! empty( $found ) ) {
				$user = get_userdata( (int) $found[0] );
				return $user ? $user : null;
			}
		}
		$user = get_user_by( 'login', sanitize_user( $ident ) );
		return $user ? $user : null;
	}

	/* --------------------------------------------------------------------- */
	/* ثبت‌نام                                                               */
	/* --------------------------------------------------------------------- */

	public static function ep_register( WP_REST_Request $r ) {
		if ( is_user_logged_in() ) {
			return new WP_Error( 'mb_auth', 'در حساب دیگری وارد هستی. ابتدا خارج شو.', array( 'status' => 400 ) );
		}
		if ( ! self::check_nonce( $r ) ) {
			return new WP_Error( 'mb_nonce', 'صفحه قدیمی است. یک‌بار نوسازی کن و دوباره تلاش کن.', array( 'status' => 403 ) );
		}
		if ( '' !== trim( (string) $r->get_param( 'website' ) ) ) {
			// تله ربات: فیلد مخفی باید خالی بماند.
			MB_Plugin::log_security( 'register_honeypot', array() );
			return new WP_Error( 'mb_auth', 'ثبت‌نام انجام نشد.', array( 'status' => 400 ) );
		}
		if ( ! MB_License::rate_limit( 'register', 6, 1800 ) ) {
			return new WP_Error( 'mb_rate', 'تعداد ثبت‌نام از این دستگاه زیاد است. کمی بعد تلاش کن.', array( 'status' => 429 ) );
		}
		if ( ! (int) MB_Plugin::setting( 'allow_registration', 1 ) ) {
			return new WP_Error( 'mb_auth', 'ثبت‌نام تازه موقتاً بسته است.', array( 'status' => 403 ) );
		}

		$name   = sanitize_text_field( (string) $r->get_param( 'name' ) );
		$email  = sanitize_email( (string) $r->get_param( 'email' ) );
		$pass   = (string) $r->get_param( 'password' );
		$mobile = self::normalize_mobile( (string) $r->get_param( 'mobile' ) );
		$terms  = (bool) $r->get_param( 'terms' );

		if ( mb_strlen( $name ) < 2 ) {
			return new WP_Error( 'mb_auth', 'نام خود را وارد کن.', array( 'status' => 400 ) );
		}
		if ( ! is_email( $email ) ) {
			return new WP_Error( 'mb_auth', 'ایمیل معتبر نیست.', array( 'status' => 400 ) );
		}
		if ( email_exists( $email ) ) {
			return new WP_Error( 'mb_auth', 'این ایمیل قبلاً ثبت شده است. از «ورود» استفاده کن.', array( 'status' => 409 ) );
		}
		$pass_error = self::password_error( $pass );
		if ( null !== $pass_error ) {
			return new WP_Error( 'mb_auth', $pass_error, array( 'status' => 400 ) );
		}
		if ( ! $terms ) {
			return new WP_Error( 'mb_auth', 'برای ساخت حساب، شرایط و حریم خصوصی را بپذیر.', array( 'status' => 400 ) );
		}
		if ( '' !== (string) $r->get_param( 'mobile' ) && '' === $mobile ) {
			return new WP_Error( 'mb_auth', 'شماره موبایل معتبر نیست (مثل ۰۹۱۲۳۴۵۶۷۸۹).', array( 'status' => 400 ) );
		}
		// موبایل باید یکتا باشد: find_user() و ورود با کد یک‌بارمصرف با شمارهٔ
		// تکراری به یک حساب دلخواه می‌رسیدند.
		if ( '' !== $mobile && self::mobile_taken( $mobile, 0 ) ) {
			return new WP_Error( 'mb_auth', 'این شماره موبایل قبلاً ثبت شده است.', array( 'status' => 409 ) );
		}

		$user_id = wp_insert_user(
			array(
				'user_login'   => self::unique_login( $email ),
				'user_email'   => $email,
				'user_pass'    => $pass,
				'first_name'   => $name,
				'display_name' => $name,
				'role'         => MB_ROLE_WOMAN,
			)
		);
		if ( is_wp_error( $user_id ) ) {
			return new WP_Error( 'mb_auth', 'ساخت حساب ممکن نشد. کمی بعد تلاش کن.', array( 'status' => 500 ) );
		}

		if ( '' !== $mobile ) {
			update_user_meta( (int) $user_id, 'mb_mobile', $mobile );
		}
		update_user_meta( (int) $user_id, 'mb_terms_at', current_time( 'mysql' ) );
		update_user_meta( (int) $user_id, 'mb_signup_source', 'app' );

		$user = get_userdata( (int) $user_id );
		if ( ! $user ) {
			return new WP_Error( 'mb_auth', 'ساخت حساب ممکن نشد.', array( 'status' => 500 ) );
		}

		// M11 — کد معرف: از بدنهٔ درخواست یا کوکی لینک اشتراکی.
		$ref = MB_Referral::sanitize_code( (string) $r->get_param( 'ref' ) );
		if ( '' === $ref && isset( $_COOKIE['mb_ref'] ) ) {
			$ref = MB_Referral::sanitize_code( (string) wp_unslash( $_COOKIE['mb_ref'] ) );
		}
		if ( '' !== $ref ) {
			MB_Referral::attach( (int) $user_id, $ref );
		}

		self::sign_in( $user, true );
		MB_DB::add_notification( (int) $user_id, 'welcome', array( 'title' => 'خوش آمدی به ماه‌بانو', 'body' => 'چرخه‌ات را تنظیم کن تا پیش‌بینی‌ها دقیق شود.' ) );
		MB_Notify::admin_alert( 'کاربر تازه در ماه‌بانو', 'یک حساب تازه با ایمیل ' . $email . ' ساخته شد.' );
		MB_Plugin::log_security( 'register_ok', array( 'user' => (int) $user_id ) );

		return self::session_response( $user, 'حسابت ساخته شد' );
	}

	/* --------------------------------------------------------------------- */
	/* فراموشی رمز                                                           */
	/* --------------------------------------------------------------------- */

	public static function ep_forgot( WP_REST_Request $r ) {
		if ( ! self::check_nonce( $r ) ) {
			return new WP_Error( 'mb_nonce', 'صفحه قدیمی است. یک‌بار نوسازی کن و دوباره تلاش کن.', array( 'status' => 403 ) );
		}
		if ( ! MB_License::rate_limit( 'forgot', 6, 1800 ) ) {
			return new WP_Error( 'mb_rate', 'درخواست‌های زیاد. کمی بعد تلاش کن.', array( 'status' => 429 ) );
		}

		$ident = trim( (string) $r->get_param( 'identity' ) );
		$user  = '' !== $ident ? self::find_user( $ident ) : null;

		// پاسخ همیشه یکسان است تا وجود حساب لو نرود.
		$ok = new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => 'اگر این حساب وجود داشته باشد، کد بازیابی به ایمیلش فرستاده شد.',
				'step'    => 'reset',
			),
			200
		);
		if ( ! $user ) {
			return $ok;
		}

		$code = (string) wp_rand( 100000, 999999 );
		update_user_meta( (int) $user->ID, 'mb_reset_hash', wp_hash_password( $code ) );
		update_user_meta( (int) $user->ID, 'mb_reset_exp', time() + 20 * MINUTE_IN_SECONDS );
		update_user_meta( (int) $user->ID, 'mb_reset_tries', 0 );

		MB_Notify::reset_code( (int) $user->ID, $code );
		MB_Plugin::log_security( 'reset_requested', array( 'user' => (int) $user->ID ) );
		return $ok;
	}

	public static function ep_reset( WP_REST_Request $r ) {
		if ( ! self::check_nonce( $r ) ) {
			return new WP_Error( 'mb_nonce', 'صفحه قدیمی است. یک‌بار نوسازی کن و دوباره تلاش کن.', array( 'status' => 403 ) );
		}
		if ( ! MB_License::rate_limit( 'reset', 12, 1800 ) ) {
			return new WP_Error( 'mb_rate', 'تلاش‌های زیاد. کمی بعد دوباره تلاش کن.', array( 'status' => 429 ) );
		}

		$ident = trim( (string) $r->get_param( 'identity' ) );
		$code  = preg_replace( '/[^0-9]/', '', MB_Jalali::en_num( (string) $r->get_param( 'code' ) ) );
		$pass  = (string) $r->get_param( 'password' );

		$user = '' !== $ident ? self::find_user( $ident ) : null;
		if ( ! $user ) {
			return new WP_Error( 'mb_auth', 'کد بازیابی درست نیست یا منقضی شده است.', array( 'status' => 400 ) );
		}

		$hash  = (string) get_user_meta( (int) $user->ID, 'mb_reset_hash', true );
		$exp   = (int) get_user_meta( (int) $user->ID, 'mb_reset_exp', true );
		$tries = (int) get_user_meta( (int) $user->ID, 'mb_reset_tries', true );

		if ( '' === $hash || $exp < time() ) {
			return new WP_Error( 'mb_auth', 'کد بازیابی منقضی شده است. دوباره درخواست بده.', array( 'status' => 400 ) );
		}
		if ( $tries >= 5 ) {
			delete_user_meta( (int) $user->ID, 'mb_reset_hash' );
			return new WP_Error( 'mb_auth', 'تلاش‌های ناموفق زیاد بود. کد تازه بگیر.', array( 'status' => 429 ) );
		}
		if ( ! wp_check_password( $code, $hash ) ) {
			update_user_meta( (int) $user->ID, 'mb_reset_tries', $tries + 1 );
			MB_Plugin::log_security( 'reset_bad_code', array( 'user' => (int) $user->ID ) );
			return new WP_Error( 'mb_auth', 'کد بازیابی درست نیست.', array( 'status' => 400 ) );
		}

		$pass_error = self::password_error( $pass );
		if ( null !== $pass_error ) {
			return new WP_Error( 'mb_auth', $pass_error, array( 'status' => 400 ) );
		}

		if ( function_exists( 'reset_password' ) ) {
			reset_password( $user, $pass );
		} else {
			wp_set_password( $pass, (int) $user->ID );
			do_action( 'password_reset', $user, $pass );
		}
		delete_user_meta( (int) $user->ID, 'mb_reset_hash' );
		delete_user_meta( (int) $user->ID, 'mb_reset_exp' );
		delete_user_meta( (int) $user->ID, 'mb_reset_tries' );

		$fresh = get_userdata( (int) $user->ID );
		if ( ! $fresh ) {
			return new WP_Error( 'mb_auth', 'بازیابی انجام نشد.', array( 'status' => 500 ) );
		}
		self::sign_in( $fresh, true );
		MB_Plugin::log_security( 'reset_ok', array( 'user' => (int) $user->ID ) );
		return self::session_response( $fresh, 'رمز عبور تازه ثبت شد' );
	}

	/* --------------------------------------------------------------------- */
	/* خروج و حساب                                                           */
	/* --------------------------------------------------------------------- */

	public static function ep_logout( WP_REST_Request $r ) {
		wp_logout();
		return new WP_REST_Response(
			array(
				'ok'         => true,
				'message'    => 'از حساب خارج شدی',
				'auth_nonce' => self::nonce(),
				'reload'     => MB_UI::app_url( '#/login' ),
			),
			200
		);
	}

	/** ویرایش نام، موبایل و رمز از داخل اپ. */
	public static function ep_account( WP_REST_Request $r ) {
		$uid  = get_current_user_id();
		$user = get_userdata( $uid );
		if ( ! $user ) {
			return new WP_Error( 'mb_auth', 'حساب پیدا نشد.', array( 'status' => 404 ) );
		}
		if ( ! MB_License::rate_limit( 'account_' . $uid, 20, 600 ) ) {
			return new WP_Error( 'mb_rate', 'درخواست‌های زیاد.', array( 'status' => 429 ) );
		}

		$fields = array();
		$name   = sanitize_text_field( (string) $r->get_param( 'name' ) );
		if ( '' !== $name ) {
			if ( mb_strlen( $name ) < 2 ) {
				return new WP_Error( 'mb_auth', 'نام کوتاه است.', array( 'status' => 400 ) );
			}
			$fields['first_name']   = $name;
			$fields['display_name'] = $name;
		}

		$mobile_raw = (string) $r->get_param( 'mobile' );
		if ( '' !== $mobile_raw ) {
			$mobile = self::normalize_mobile( $mobile_raw );
			if ( '' === $mobile ) {
				return new WP_Error( 'mb_auth', 'شماره موبایل معتبر نیست.', array( 'status' => 400 ) );
			}
			if ( self::mobile_taken( $mobile, $uid ) ) {
				return new WP_Error( 'mb_auth', 'این شماره موبایل روی حساب دیگری ثبت شده است.', array( 'status' => 409 ) );
			}
			update_user_meta( $uid, 'mb_mobile', $mobile );
		}

		$new_pass = (string) $r->get_param( 'new_password' );
		if ( '' !== $new_pass ) {
			$current = (string) $r->get_param( 'current_password' );
			if ( ! wp_check_password( $current, $user->user_pass, $uid ) ) {
				return new WP_Error( 'mb_auth', 'رمز فعلی درست نیست.', array( 'status' => 403 ) );
			}
			$pass_error = self::password_error( $new_pass );
			if ( null !== $pass_error ) {
				return new WP_Error( 'mb_auth', $pass_error, array( 'status' => 400 ) );
			}
			$fields['user_pass'] = $new_pass;
		}

		if ( ! empty( $fields ) ) {
			$fields['ID'] = $uid;
			$result       = wp_update_user( $fields );
			if ( is_wp_error( $result ) ) {
				return new WP_Error( 'mb_auth', 'ذخیره نشد.', array( 'status' => 500 ) );
			}
			if ( isset( $fields['user_pass'] ) ) {
				// تغییر رمز سشن را باطل می‌کند؛ کوکی تازه می‌گذاریم تا اپ باز بماند.
				$fresh = get_userdata( $uid );
				if ( $fresh ) {
					self::sign_in( $fresh, true );
				}
			}
		}

		return new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => 'حساب به‌روزرسانی شد',
				'nonce'   => wp_create_nonce( 'wp_rest' ),
			),
			200
		);
	}

	/* --------------------------------------------------------------------- */
	/* جلوگیری از رسیدن کاربر اپ به وردپرس                                    */
	/* --------------------------------------------------------------------- */

	/** آیا کاربر فقط کاربر اپ است؟ */
	public static function is_app_only_user( int $user_id ): bool {
		if ( $user_id <= 0 || user_can( $user_id, 'manage_options' ) || user_can( $user_id, 'edit_posts' ) ) {
			return false;
		}
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}
		$roles = (array) $user->roles;
		foreach ( $roles as $role ) {
			if ( ! in_array( $role, array( MB_ROLE_WOMAN, MB_ROLE_PARTNER, 'subscriber' ), true ) ) {
				return false;
			}
		}
		return true;
	}

	/** wp-login.php را به اپ برمی‌گرداند (به‌جز logout، action های سیستمی و مدیر). */
	public static function maybe_divert_login(): void {
		if ( ! (int) MB_Plugin::setting( 'inapp_auth', 1 ) ) {
			return;
		}
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : 'login';
		$keep   = array( 'logout', 'postpass', 'confirmaction', 'rp', 'resetpass', 'lostpassword', 'retrievepassword', 'register' );
		if ( in_array( $action, $keep, true ) ) {
			return;
		}
		if ( 'POST' === strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) ) {
			return; // فرم‌های داخلی وردپرس (مثل ورود مدیر) دست‌نخورده می‌مانند.
		}
		// راه فرار همیشگی مدیر، تا هیچ‌وقت از سایت خودش قفل نشود.
		if ( isset( $_GET['mb_wp'] ) || isset( $_GET['interim-login'] ) || isset( $_GET['loggedout'] ) ) {
			return;
		}
		// مسیر ورود به داشبورد هرگز منحرف نمی‌شود؛ وگرنه مدیر بیرون می‌ماند.
		$redirect = isset( $_GET['redirect_to'] ) ? (string) wp_unslash( $_GET['redirect_to'] ) : '';
		if ( '' !== $redirect && false !== strpos( $redirect, 'wp-admin' ) ) {
			return;
		}
		// کاربری که دسترسی مدیریتی دارد هم به صفحهٔ وردپرس می‌رسد.
		if ( is_user_logged_in() && ! self::is_app_only_user( get_current_user_id() ) ) {
			return;
		}
		$target = MB_UI::app_url( is_user_logged_in() ? '#/home' : '#/login' );
		wp_safe_redirect( $target, 302 );
		exit;
	}

	public static function filter_login_url( string $url, string $redirect, bool $force_reauth ) {
		if ( ! (int) MB_Plugin::setting( 'inapp_auth', 1 ) || is_admin() ) {
			return $url;
		}
		return MB_UI::app_url( '#/login' );
	}

	public static function filter_register_url( string $url ) {
		if ( ! (int) MB_Plugin::setting( 'inapp_auth', 1 ) ) {
			return $url;
		}
		return MB_UI::app_url( '#/register' );
	}

	public static function filter_lost_url( string $url, string $redirect ) {
		if ( ! (int) MB_Plugin::setting( 'inapp_auth', 1 ) ) {
			return $url;
		}
		return MB_UI::app_url( '#/forgot' );
	}

	public static function filter_logout_redirect( string $to, string $requested, $user ) {
		if ( $user instanceof WP_User && self::is_app_only_user( (int) $user->ID ) ) {
			return MB_UI::app_url( '#/login' );
		}
		return $to;
	}

	public static function block_dashboard(): void {
		if ( wp_doing_ajax() || ! is_user_logged_in() ) {
			return;
		}
		if ( self::is_app_only_user( get_current_user_id() ) ) {
			wp_safe_redirect( MB_UI::app_url( '#/home' ) );
			exit;
		}
	}

	public static function filter_admin_bar( $show ) {
		if ( is_user_logged_in() && self::is_app_only_user( get_current_user_id() ) ) {
			return false;
		}
		return $show;
	}
}
