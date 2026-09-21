<?php
/**
 * MB_Crypto — رمزنگاری فیلدهای حساس با کلید اختصاصی.
 *
 * ترتیب کلید:
 *  ۱) ثابت MB_FIELD_KEY در wp-config.php (۶۴ نویسهٔ هگز = ۳۲ بایت).
 *  ۲) فال‌بک: HKDF-SHA256 از AUTH_KEY با info='moonbanu_field' + نوتیس ادمین.
 *
 * قواعد ثابت:
 *  - کلید هرگز در دیتابیس، options یا خروجی API نوشته نمی‌شود.
 *  - تغییر کلید = غیرقابل‌خواندن‌شدن همهٔ مقادیر رمزنگاری‌شدهٔ قبلی.
 *  - کلید جزء بک‌آپ اجباری است (README و تب وضعیت این را دائمی هشدار می‌دهند).
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Crypto {

	/** برچسب نسخهٔ فرمت ذخیره‌سازی. */
	const PREFIX = 'f1:';

	/** info ثابت HKDF؛ هرگز تغییر نکند. */
	const HKDF_INFO = 'moonbanu_field';

	public static function init(): void {
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
	}

	/* --------------------------------------------------------------------- */
	/* کلید                                                                  */
	/* --------------------------------------------------------------------- */

	/** آیا کلید اختصاصی به‌درستی تعریف شده است؟ */
	public static function has_dedicated_key(): bool {
		if ( ! defined( 'MB_FIELD_KEY' ) ) {
			return false;
		}
		$raw = trim( (string) constant( 'MB_FIELD_KEY' ) );
		return 64 === strlen( $raw ) && (bool) preg_match( '/^[0-9a-fA-F]{64}$/', $raw );
	}

	/**
	 * کلید ۳۲ بایتی خام.
	 *
	 * هیچ‌جا کش نمی‌شود جز حافظهٔ همین درخواست.
	 */
	private static function key(): string {
		static $cached = null;
		if ( null !== $cached ) {
			return $cached;
		}

		if ( self::has_dedicated_key() ) {
			$cached = (string) hex2bin( strtolower( trim( (string) constant( 'MB_FIELD_KEY' ) ) ) );
			return $cached;
		}

		// فال‌بک: HKDF از AUTH_KEY. امن است، ولی به نمک‌های وردپرس گره می‌خورد.
		$ikm  = defined( 'AUTH_KEY' ) ? (string) AUTH_KEY : 'moonbanu-fallback-ikm';
		$salt = defined( 'AUTH_SALT' ) ? (string) AUTH_SALT : '';
		$cached = self::hkdf( $ikm, $salt, self::HKDF_INFO, 32 );
		return $cached;
	}

	/** HKDF-SHA256 (RFC 5869) — بدون وابستگی به hash_hkdf برای PHP های سختگیر. */
	private static function hkdf( string $ikm, string $salt, string $info, int $length ): string {
		if ( function_exists( 'hash_hkdf' ) ) {
			return (string) hash_hkdf( 'sha256', $ikm, $length, $info, $salt );
		}
		$prk = hash_hmac( 'sha256', $ikm, '' === $salt ? str_repeat( "\0", 32 ) : $salt, true );
		$out = '';
		$t   = '';
		for ( $i = 1; strlen( $out ) < $length; $i++ ) {
			$t    = hash_hmac( 'sha256', $t . $info . chr( $i ), $prk, true );
			$out .= $t;
		}
		return substr( $out, 0, $length );
	}

	/* --------------------------------------------------------------------- */
	/* رمزنگاری / رمزگشایی                                                   */
	/* --------------------------------------------------------------------- */

	/**
	 * AES-256-GCM در صورت پشتیبانی، وگرنه AES-256-CBC + HMAC-SHA256.
	 * قالب خروجی: f1:base64(mode|iv|tag|cipher)
	 */
	public static function encrypt( string $plain ): string {
		if ( '' === $plain ) {
			return '';
		}
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			// بدون openssl هیچ متن حساسی خام ذخیره نمی‌شود.
			MB_Plugin::log_security( 'crypto_unavailable', array( 'severity' => 'critical' ) );
			return '';
		}

		$key = self::key();

		if ( in_array( 'aes-256-gcm', array_map( 'strtolower', (array) openssl_get_cipher_methods() ), true ) ) {
			$iv     = random_bytes( 12 );
			$tag    = '';
			$cipher = openssl_encrypt( $plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16 );
			if ( false === $cipher ) {
				return '';
			}
			return self::PREFIX . base64_encode( 'g' . $iv . $tag . $cipher );
		}

		$iv     = random_bytes( 16 );
		$cipher = openssl_encrypt( $plain, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		if ( false === $cipher ) {
			return '';
		}
		$mac = hash_hmac( 'sha256', $iv . $cipher, $key, true );
		return self::PREFIX . base64_encode( 'c' . $iv . $mac . $cipher );
	}

	/** رمزگشایی؛ در هر خطا رشتهٔ خالی برمی‌گردد (هیچ استثنایی به بالا نمی‌رود). */
	public static function decrypt( ?string $stored ): string {
		$stored = (string) $stored;
		if ( '' === $stored ) {
			return '';
		}
		if ( 0 !== strpos( $stored, self::PREFIX ) ) {
			// سازگاری با یادداشت‌های نسخهٔ قبل که با MB_DB رمزنگاری شده‌اند.
			return MB_DB::decrypt( $stored );
		}
		if ( ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}

		$raw = base64_decode( substr( $stored, strlen( self::PREFIX ) ), true );
		if ( false === $raw || strlen( $raw ) < 30 ) {
			return '';
		}

		$key  = self::key();
		$mode = substr( $raw, 0, 1 );

		if ( 'g' === $mode ) {
			$iv     = substr( $raw, 1, 12 );
			$tag    = substr( $raw, 13, 16 );
			$cipher = substr( $raw, 29 );
			$plain  = openssl_decrypt( $cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
			return false === $plain ? '' : $plain;
		}

		if ( 'c' === $mode ) {
			$iv     = substr( $raw, 1, 16 );
			$mac    = substr( $raw, 17, 32 );
			$cipher = substr( $raw, 49 );
			if ( ! hash_equals( $mac, hash_hmac( 'sha256', $iv . $cipher, $key, true ) ) ) {
				return '';
			}
			$plain = openssl_decrypt( $cipher, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
			return false === $plain ? '' : $plain;
		}

		return '';
	}

	/* --------------------------------------------------------------------- */
	/* نوتیس ادمین و متن هشدار                                               */
	/* --------------------------------------------------------------------- */

	public static function admin_notice(): void {
		if ( ! current_user_can( 'manage_options' ) || self::has_dedicated_key() ) {
			return;
		}
		echo '<div class="notice notice-warning"><p><strong>ماه‌بانو:</strong> کلید اختصاصی رمزنگاری فیلدهای حساس تعریف نشده است. '
			. 'خط زیر را در <code>wp-config.php</code> بگذارید (۶۴ نویسهٔ هگز تصادفی):</p>'
			. '<p><code>define( \'MB_FIELD_KEY\', \'' . esc_html( bin2hex( random_bytes( 32 ) ) ) . '\' );</code></p>'
			. '<p>تا آن زمان کلید از <code>AUTH_KEY</code> مشتق می‌شود. <strong>هشدار:</strong> تغییر کلید یا نمک‌های وردپرس، '
			. 'همهٔ فیلدهای رمزنگاری‌شدهٔ قبلی (کد ملی و یادداشت پزشکی) را غیرقابل‌خواندن می‌کند. کلید جزء بک‌آپ اجباری است.</p></div>';
	}

	/** متن هشدار ثابت برای تب وضعیت و README. */
	public static function warning_text(): string {
		return 'کلید رمزنگاری فیلدهای حساس جزء بک‌آپ اجباری است. تغییر MB_FIELD_KEY (یا AUTH_KEY در حالت فال‌بک) '
			. 'یعنی کد ملی و یادداشت پزشکی ذخیره‌شده دیگر خوانده نمی‌شوند و بازگردانی‌شان ممکن نیست.';
	}

	/** وضعیت کلید برای تب وضعیت ادمین. */
	public static function status(): string {
		return self::has_dedicated_key()
			? 'کلید اختصاصی MB_FIELD_KEY فعال است.'
			: 'فال‌بک HKDF از AUTH_KEY (کلید اختصاصی تعریف نشده).';
	}
}
