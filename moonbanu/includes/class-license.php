<?php
/**
 * لایه‌های ضدتقلب و یکپارچگی (L1..L8).
 *
 * یادداشت صادقانه: هدف این لایه‌ها «گران‌تر کردن کرک از خرید» است، نه ناممکن
 * کردن مطلق آن. هر کدی که روی سرور مشتری اجرا می‌شود در نهایت قابل تغییر است؛
 * این ساختار فقط کار را پرهزینه، پرریسک و قابل‌ردیابی می‌کند.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_License {

	/** فایل‌هایی که دست‌کاری‌شان حالت ایمن را فعال می‌کند. */
	const CRITICAL = array(
		'class-subscription.php',
		'class-license.php',
		'class-gateway.php',
		'class-gateway-zarinpal.php',
		'class-gateway-zibal.php',
		'class-privacy.php',
		// نسخهٔ ۳٫۰ — فایل‌هایی که دست‌کاری‌شان یا داده لو می‌دهد یا گیت Pro را دور می‌زند.
		'class-report.php',
		'class-otp.php',
		'class-referral.php',
		'class-ttc.php',
	);

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'maybe_verify_integrity' ) );
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'on_upgrade' ), 10, 2 );
	}

	/* --------------------------------------------------------------------- */
	/* L4: یکپارچگی فایل                                                     */
	/* --------------------------------------------------------------------- */

	public static function file_hashes(): array {
		$out   = array();
		$files = glob( MB_DIR . 'includes/*.php' );
		foreach ( (array) $files as $file ) {
			$out[ basename( $file ) ] = hash_file( 'sha256', $file );
		}
		ksort( $out );
		return $out;
	}

	public static function rebuild_manifest(): void {
		update_option( 'mb_manifest', self::file_hashes(), false );
		update_option( 'mb_manifest_built', current_time( 'mysql' ), false );
	}

	public static function on_upgrade( $upgrader, $hook_extra ): void {
		if ( ! is_array( $hook_extra ) || ( isset( $hook_extra['type'] ) && 'plugin' !== $hook_extra['type'] ) ) {
			return;
		}
		self::rebuild_manifest();
		update_option( 'mb_safe_mode', 0 );
	}

	/**
	 * هوک admin_init: بررسی یکپارچگی، اما نه در هر درخواست.
	 *
	 * پیش از این هر بار بارگذاری هر صفحهٔ مدیریت، sha256 همهٔ فایل‌های includes
	 * (بیش از ۴۰ فایل) را حساب می‌کرد. اکنون حداکثر هر ۱۵ دقیقه یک بار اجرا
	 * می‌شود؛ بررسی‌های صریح (تب وضعیت و دکمهٔ بازسازی) همیشه فوری‌اند.
	 */
	public static function maybe_verify_integrity(): void {
		if ( get_transient( 'mb_integrity_checked' ) ) {
			return;
		}
		set_transient( 'mb_integrity_checked', 1, 15 * MINUTE_IN_SECONDS );
		self::verify_integrity();
	}

	/** مقایسه هش‌ها؛ فقط تفاوت فایل‌های حساس حالت ایمن را فعال می‌کند. */
	public static function verify_integrity(): array {
		$stored = get_option( 'mb_manifest', array() );
		if ( ! is_array( $stored ) || empty( $stored ) ) {
			self::rebuild_manifest();
			return array( 'ok' => true, 'changed' => array() );
		}
		$now     = self::file_hashes();
		$changed = array();
		foreach ( $stored as $file => $hash ) {
			if ( ! isset( $now[ $file ] ) || ! hash_equals( (string) $hash, (string) $now[ $file ] ) ) {
				$changed[] = $file;
			}
		}
		foreach ( $now as $file => $hash ) {
			if ( ! isset( $stored[ $file ] ) ) {
				$changed[] = $file;
			}
		}
		$changed  = array_values( array_unique( $changed ) );
		$critical = array_values( array_intersect( $changed, self::CRITICAL ) );

		if ( ! empty( $critical ) ) {
			if ( ! get_option( 'mb_safe_mode' ) ) {
				update_option( 'mb_safe_mode', 1 );
				MB_Plugin::log_security( 'integrity_failed', array( 'files' => $critical ) );
				MB_Notify::admin_alert( 'هشدار یکپارچگی ماه‌بانو', 'فایل‌های حساس تغییر کرده‌اند: ' . implode( ', ', $critical ) . '. سایت به حالت ایمن رفت (Pro خاموش). هیچ داده‌ای حذف نشده است.' );
			}
		} elseif ( ! empty( $changed ) ) {
			MB_Plugin::log_security( 'integrity_minor', array( 'files' => $changed ) );
			self::rebuild_manifest();
		}

		return array( 'ok' => empty( $critical ), 'changed' => $changed, 'critical' => $critical );
	}

	public static function safe_mode(): bool {
		return (bool) get_option( 'mb_safe_mode', 0 );
	}

	public static function clear_safe_mode(): void {
		self::rebuild_manifest();
		delete_transient( 'mb_integrity_checked' );
		update_option( 'mb_safe_mode', 0 );
		MB_Plugin::log_security( 'safe_mode_cleared', array() );
	}

	public static function admin_notice(): void {
		if ( ! current_user_can( 'manage_options' ) || ! self::safe_mode() ) {
			return;
		}
		echo '<div class="notice notice-error"><p><strong>ماه‌بانو در حالت ایمن است.</strong> فایل‌های حساس پلاگین تغییر کرده‌اند، بنابراین امکانات Pro موقتاً خاموش شده است. داده‌ها دست‌نخورده‌اند. از تب «دعوت‌ها و امنیت» می‌توانید پس از بازگرداندن فایل‌های اصلی، حالت ایمن را پاک کنید.</p></div>';
	}

	/* --------------------------------------------------------------------- */
	/* L2: توکن امضاشده سشن صفحه‌های Pro                                     */
	/* --------------------------------------------------------------------- */

	private static function secret(): string {
		// هر سه نمک وردپرس، نه فقط AUTH_KEY؛ و بدون fallback ثابت و حدس‌زدنی.
		// توکن‌ها ۱۵ دقیقه عمر دارند، پس تغییر مشتق‌سازی فقط توکن‌های در جریان را
		// باطل می‌کند و هیچ دادهٔ ذخیره‌شده‌ای را از دست نمی‌دهد.
		$key = ( defined( 'AUTH_KEY' ) ? (string) AUTH_KEY : '' )
			. ( defined( 'AUTH_SALT' ) ? (string) AUTH_SALT : '' )
			. ( defined( 'NONCE_SALT' ) ? (string) NONCE_SALT : '' );
		return hash( 'sha256', 'mb-license|' . $key . '|' . (string) get_option( 'mb_install_token' ) );
	}

	public static function issue_token( int $user_id ): string {
		$exp     = time() + 15 * MINUTE_IN_SECONDS;
		$payload = $user_id . '|' . (string) get_option( 'mb_site_hash' ) . '|' . $exp;
		$sig     = hash_hmac( 'sha256', $payload, self::secret() );
		return base64_encode( $payload . '|' . $sig );
	}

	public static function verify_token( string $token, int $user_id ): bool {
		$raw = base64_decode( $token, true );
		if ( ! $raw ) {
			return false;
		}
		$parts = explode( '|', $raw );
		if ( 4 !== count( $parts ) ) {
			return false;
		}
		list( $uid, $site, $exp, $sig ) = $parts;
		if ( (int) $uid !== $user_id ) {
			return false;
		}
		if ( ! hash_equals( (string) get_option( 'mb_site_hash' ), $site ) ) {
			return false;
		}
		if ( (int) $exp < time() ) {
			return false;
		}
		$expected = hash_hmac( 'sha256', $uid . '|' . $site . '|' . $exp, self::secret() );
		return hash_equals( $expected, $sig );
	}

	/* --------------------------------------------------------------------- */
	/* L5: اتصال نصب و سشن همسر                                              */
	/* --------------------------------------------------------------------- */

	public static function binding_ok( array $sub ): bool {
		$site = (string) get_option( 'mb_site_hash' );
		if ( empty( $sub['site_hash'] ) ) {
			return true; // اشتراک‌های قدیمی/دستی.
		}
		if ( ! hash_equals( $site, (string) $sub['site_hash'] ) ) {
			return false;
		}
		$allowed = array_filter( array_map( 'trim', (array) preg_split( '/[\r\n,]+/', (string) MB_Plugin::setting( 'allowed_domains', '' ) ) ) );
		if ( ! empty( $allowed ) ) {
			$host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
			if ( '' === $host ) {
				return false;
			}
			$hit = false;
			foreach ( $allowed as $domain ) {
				// تطبیق زیررشته‌ای یک راه دور زدن بود: دامنهٔ مجاز «example.com» با
				// میزبان «example.com.attacker.tld» هم جور می‌شد. فقط دامنهٔ دقیق
				// یا زیردامنهٔ واقعی آن پذیرفته می‌شود.
				$domain = strtolower( ltrim( trim( (string) $domain ), '.' ) );
				$domain = (string) preg_replace( '#^[a-z]+://#', '', $domain );
				$domain = (string) preg_replace( '#[/?].*$#', '', $domain );
				if ( '' === $domain ) {
					continue;
				}
				if ( $host === $domain || substr( $host, -( strlen( $domain ) + 1 ) ) === '.' . $domain ) {
					$hit = true;
					break;
				}
			}
			if ( ! $hit ) {
				return false;
			}
		}
		return true;
	}

	/** حداکثر ۲ سشن هم‌زمان برای همسر + heartbeat. */
	public static function partner_heartbeat( int $partner_id ): bool {
		$key      = 'mb_psess_' . $partner_id;
		$sessions = get_transient( $key );
		if ( ! is_array( $sessions ) ) {
			$sessions = array();
		}
		$fp  = hash( 'sha256', MB_DB::client_ip() . '|' . ( isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '' ) );
		$now = time();
		foreach ( $sessions as $hash => $seen ) {
			if ( $now - (int) $seen > 30 * MINUTE_IN_SECONDS ) {
				unset( $sessions[ $hash ] );
			}
		}
		if ( ! isset( $sessions[ $fp ] ) && count( $sessions ) >= 2 ) {
			MB_Plugin::log_security( 'partner_session_limit', array( 'partner' => $partner_id ) );
			return false;
		}
		$sessions[ $fp ] = $now;
		set_transient( $key, $sessions, HOUR_IN_SECONDS );
		return true;
	}

	/* --------------------------------------------------------------------- */
	/* L6: نرخ‌محدود                                                         */
	/* --------------------------------------------------------------------- */

	/** true = مجاز، false = قفل. */
	public static function rate_limit( string $bucket, int $max, int $window ): bool {
		$key   = 'mb_rl_' . md5( $bucket . '|' . MB_DB::client_ip() );
		$state = get_transient( $key );
		if ( ! is_array( $state ) ) {
			$state = array( 'n' => 0, 'start' => time(), 'locked' => 0 );
		}
		// payload های قدیمی/ناقص باعث اخطار «Undefined array key» در PHP 8 می‌شدند.
		$state = array(
			'n'      => isset( $state['n'] ) ? (int) $state['n'] : 0,
			'start'  => isset( $state['start'] ) ? (int) $state['start'] : time(),
			'locked' => isset( $state['locked'] ) ? (int) $state['locked'] : 0,
		);
		if ( $state['locked'] > time() ) {
			return false;
		}
		if ( time() - (int) $state['start'] > $window ) {
			$state = array( 'n' => 0, 'start' => time(), 'locked' => 0 );
		}
		++$state['n'];
		if ( $state['n'] > $max ) {
			$state['locked'] = time() + 15 * MINUTE_IN_SECONDS;
			set_transient( $key, $state, 30 * MINUTE_IN_SECONDS );
			MB_Plugin::log_security( 'rate_locked', array( 'bucket' => $bucket ) );
			return false;
		}
		set_transient( $key, $state, max( $window, 900 ) );
		return true;
	}

	/** شماره خطاهای اعتبارسنجی (۲۰ خطا → قفل). */
	public static function note_validation_error( string $bucket ): void {
		self::rate_limit( 'err_' . $bucket, 20, 900 );
	}

	/* --------------------------------------------------------------------- */
	/* وضعیت برای پنل ادمین                                                  */
	/* --------------------------------------------------------------------- */

	public static function status(): array {
		$integrity = self::verify_integrity();
		return array(
			'safe_mode'      => self::safe_mode(),
			'manifest_built' => (string) get_option( 'mb_manifest_built', '' ),
			'changed'        => $integrity['changed'],
			'critical'       => $integrity['critical'] ?? array(),
			'site_hash'      => substr( (string) get_option( 'mb_site_hash' ), 0, 16 ) . '…',
			'install_token'  => substr( (string) get_option( 'mb_install_token' ), 0, 8 ) . '…',
		);
	}
}
