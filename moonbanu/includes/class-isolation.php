<?php
/**
 * جداسازی کامل اپ از قالب و افزونه‌های دیگر.
 *
 * روی برگهٔ اپ:
 *  - همهٔ style/script بیگانه از صف خارج می‌شوند (با فهرست مجاز قابل تنظیم).
 *  - manifest، theme-color و apple-touch-icon قالب حذف و نسخهٔ اپ جای آن می‌نشیند.
 *  - ثبت service worker قالب اجرا نمی‌شود تا SW اپ با scope اختصاصی خودش
 *    تنها کنترل‌کنندهٔ مسیر اپ باشد (SW سایت دست‌نخورده باقی می‌ماند).
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Isolation {

	/** دستگیره‌های خودی که هرگز حذف نمی‌شوند. */
	private static array $own = array( 'moonbanu', 'moonbanu-app', 'moonbanu-qr' );

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'purge' ), 9999 );
		add_action( 'wp_print_styles', array( __CLASS__, 'purge' ), 9999 );
		add_action( 'wp_print_footer_scripts', array( __CLASS__, 'purge_footer' ), 1 );

		// قالب‌هایی که بیرون از صف، CSS/PWA تزریق می‌کنند.
		add_action( 'template_redirect', array( __CLASS__, 'unhook_foreign_pwa' ), 5 );

		// نوار مدیر و emoji در اپ جایی ندارند.
		add_filter( 'show_admin_bar', array( __CLASS__, 'hide_admin_bar' ), 99 );
	}

	/** آیا در حال رندر اپ هستیم؟ */
	private static function active(): bool {
		return ! is_admin() && MB_Assets::needed() && (int) MB_Plugin::setting( 'isolate_app', 1 ) === 1;
	}

	/* --------------------------------------------------------------------- */
	/* پاک‌سازی صف                                                          */
	/* --------------------------------------------------------------------- */

	/** فهرست مجاز اضافی از تنظیمات (هر خط یک handle). */
	private static function allowlist(): array {
		$raw   = (string) MB_Plugin::setting( 'isolate_allow', '' );
		$out   = self::$own;
		foreach ( preg_split( '/[\r\n,]+/', $raw ) as $handle ) {
			$handle = trim( $handle );
			if ( '' !== $handle ) {
				$out[] = $handle;
			}
		}
		/**
		 * افزودن handle به فهرست مجاز اپ ماه‌بانو.
		 *
		 * @param array $out فهرست handle های مجاز.
		 */
		return array_unique( (array) apply_filters( 'moonbanu_isolation_allowlist', $out ) );
	}

	public static function purge(): void {
		if ( ! self::active() ) {
			return;
		}
		$allow = self::allowlist();

		$styles = wp_styles();
		if ( $styles instanceof WP_Styles ) {
			foreach ( array_keys( (array) $styles->registered ) as $handle ) {
				if ( self::is_allowed( (string) $handle, $allow ) ) {
					continue;
				}
				wp_dequeue_style( $handle );
			}
		}

		$scripts = wp_scripts();
		if ( $scripts instanceof WP_Scripts ) {
			foreach ( array_keys( (array) $scripts->registered ) as $handle ) {
				if ( self::is_allowed( (string) $handle, $allow ) ) {
					continue;
				}
				wp_dequeue_script( $handle );
			}
		}

		// دارایی‌های همیشگی وردپرس که در اپ لازم نیستند.
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	}

	public static function purge_footer(): void {
		if ( ! self::active() ) {
			return;
		}
		self::purge();
	}

	/** مجاز است اگر خودی، وابستهٔ خودی، یا در فهرست مجاز باشد. */
	private static function is_allowed( string $handle, array $allow ): bool {
		if ( in_array( $handle, $allow, true ) ) {
			return true;
		}
		// اسکریپت‌های هسته که ممکن است وابستگی اپ باشند.
		if ( in_array( $handle, array( 'jquery-core', 'jquery-migrate', 'jquery', 'wp-polyfill', 'wp-hooks', 'wp-i18n' ), true ) ) {
			return (bool) apply_filters( 'moonbanu_isolation_keep_core', false, $handle );
		}
		return false;
	}

	/* --------------------------------------------------------------------- */
	/* PWA بیگانه                                                           */
	/* --------------------------------------------------------------------- */

	/**
	 * هوک‌های PWA قالب/افزونه‌ها را روی برگهٔ اپ برمی‌دارد.
	 * فقط روی این برگه؛ PWA سایت در بقیهٔ صفحه‌ها کامل سر جای خودش است.
	 */
	public static function unhook_foreign_pwa(): void {
		if ( ! self::active() ) {
			return;
		}
		foreach ( array( 'wp_head', 'wp_footer', 'wp_body_open' ) as $tag ) {
			self::strip_hook_by_keyword(
				$tag,
				array( 'pwa', 'manifest', 'service_worker', 'serviceworker', 'sw_register', 'app_install', 'add_to_home' )
			);
		}
	}

	/** حذف callback هایی که نامشان کلیدواژه دارد و متعلق به ماه‌بانو نیستند. */
	private static function strip_hook_by_keyword( string $tag, array $keywords ): void {
		global $wp_filter;
		if ( ! isset( $wp_filter[ $tag ] ) || ! $wp_filter[ $tag ] instanceof WP_Hook ) {
			return;
		}
		foreach ( (array) $wp_filter[ $tag ]->callbacks as $priority => $callbacks ) {
			foreach ( (array) $callbacks as $id => $callback ) {
				$name = self::callback_name( $id, $callback );
				if ( '' === $name || false !== stripos( $name, 'moonbanu' ) || 0 === stripos( $name, 'MB_' ) ) {
					continue;
				}
				foreach ( $keywords as $needle ) {
					if ( false !== stripos( $name, $needle ) ) {
						remove_action( $tag, $callback['function'], (int) $priority );
						break;
					}
				}
			}
		}
	}

	/** نام خوانا از شناسهٔ callback. */
	private static function callback_name( $id, array $callback ): string {
		$fn = $callback['function'] ?? null;
		if ( is_string( $fn ) ) {
			return $fn;
		}
		if ( is_array( $fn ) && 2 === count( $fn ) ) {
			$class = is_object( $fn[0] ) ? get_class( $fn[0] ) : (string) $fn[0];
			return $class . '::' . (string) $fn[1];
		}
		return is_string( $id ) ? $id : '';
	}

	/* --------------------------------------------------------------------- */
	/* پاک‌سازی خروجی head و footer                                          */
	/* --------------------------------------------------------------------- */

	/**
	 * حذف manifest/theme-color/آیکن بیگانه از head.
	 * تگ‌های ماه‌بانو با data-mb="1" علامت‌گذاری شده‌اند و دست‌نخورده می‌مانند.
	 */
	public static function clean_head( string $html ): string {
		if ( ! self::active() ) {
			return $html;
		}
		$patterns = array(
			'#<link[^>]+rel=(["\'])[^"\']*manifest[^"\']*\1[^>]*>#i',
			'#<meta[^>]+name=(["\'])theme-color\1[^>]*>#i',
			'#<link[^>]+rel=(["\'])apple-touch-icon(?:-precomposed)?\1[^>]*>#i',
			'#<meta[^>]+name=(["\'])(?:apple-mobile-web-app-[a-z-]+|mobile-web-app-capable|msapplication-[a-z-]+)\1[^>]*>#i',
		);
		foreach ( $patterns as $pattern ) {
			$html = (string) preg_replace_callback(
				$pattern,
				static function ( array $m ): string {
					return false !== stripos( $m[0], 'data-mb="1"' ) ? $m[0] : '';
				},
				$html
			);
		}
		// استایل‌های درون‌خطی قالب که با !important رنگ و پس‌زمینه را عوض می‌کنند.
		$html = (string) preg_replace_callback(
			'#<style[^>]*>.*?</style>#is',
			static function ( array $m ): string {
				return false !== stripos( $m[0], 'data-mb="1"' ) ? $m[0] : '';
			},
			$html
		);
		return $html;
	}

	/** حذف ثبت service worker بیگانه از footer. */
	public static function clean_footer( string $html ): string {
		if ( ! self::active() ) {
			return $html;
		}
		return (string) preg_replace_callback(
			'#<script[^>]*>.*?</script>#is',
			static function ( array $m ): string {
				$chunk = $m[0];
				if ( false !== stripos( $chunk, 'data-mb="1"' ) || false !== stripos( $chunk, 'MB_BOOT' ) ) {
					return $chunk;
				}
				if ( false !== stripos( $chunk, 'serviceWorker' ) && false !== stripos( $chunk, 'register' ) ) {
					return '';
				}
				return $chunk;
			},
			$html
		);
	}

	public static function hide_admin_bar( $show ) {
		return self::active() ? false : $show;
	}
}
