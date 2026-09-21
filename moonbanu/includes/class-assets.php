<?php
/**
 * بارگذاری دارایی‌ها + کتابخانه رندر UI (حلقه‌ها، چیپ‌ها، نوارها، آیکن‌ها).
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Assets {

	private static bool $needed = false;

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_head', array( __CLASS__, 'head_meta' ), 1 );
		add_action( 'wp_footer', array( __CLASS__, 'footer_pwa' ), 99 );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
	}

	/** برگهٔ اپ یک shell مستقل دارد، اما wp_head/wp_footer قالب را حفظ می‌کند. */
	public static function template_include( string $template ): string {
		if ( self::page_has_app() ) {
			return MB_DIR . 'templates/app-shell.php';
		}
		return $template;
	}

	public static function mark_needed(): void {
		self::$needed = true;
		if ( ! wp_style_is( 'moonbanu', 'enqueued' ) ) {
			wp_enqueue_style( 'moonbanu' );
		}
		if ( ! wp_script_is( 'moonbanu-app', 'enqueued' ) ) {
			wp_enqueue_script( 'moonbanu-qr' );
			wp_enqueue_script( 'moonbanu-app' );
		}
	}

	public static function needed(): bool {
		return self::$needed || self::page_has_app();
	}

	public static function page_has_app(): bool {
		if ( ! is_singular() ) {
			return false;
		}
		$post = get_post();
		return $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'moonbanu_app' );
	}

	public static function enqueue(): void {
		wp_register_style( 'moonbanu', MB_URL . 'assets/css/style.css', array(), MB_VERSION );
		wp_register_script( 'moonbanu-qr', MB_URL . 'assets/js/qr.js', array(), MB_VERSION, true );
		wp_register_script( 'moonbanu-app', MB_URL . 'assets/js/app.js', array( 'moonbanu-qr' ), MB_VERSION, true );

		wp_localize_script(
			'moonbanu-app',
			'MB_BOOT',
			array(
				'rest'  => esc_url_raw( rest_url( 'moonbanu/v1' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
				'sw'    => esc_url_raw( self::sw_url() ),
				// CSS چاپ تنبل است: app.js تنها در مسیر #/report آن را تزریق می‌کند.
				'printCss'   => esc_url_raw( MB_URL . 'assets/css/print.css?v=' . MB_VERSION ),
				'lazyRoutes' => array( 'pregnancy', 'report', 'assistant', 'community', 'ttc', 'quiz', 'quizzes', 'reminders' ),
				'scope' => esc_url_raw( self::scope_path() ),
				'copy'  => MB_Plugin::text_overrides(),
				'authNonce' => MB_Auth::nonce(),
				'logged'    => is_user_logged_in() ? 1 : 0,
				'appUrl'    => esc_url_raw( self::app_start_url() ),
				'brand'     => array(
					'name'    => MB_Plugin::brand( 'name' ),
					'phone'   => MB_Plugin::brand( 'phone' ),
					'email'   => MB_Plugin::brand( 'email' ),
					'credit'  => MB_Plugin::brand( 'credit' ),
					'studio'  => MB_Plugin::brand( 'studio' ),
					'devUrl'  => MB_Plugin::brand( 'dev_url' ),
				),
				'i18n'  => array(
					'saved'   => 'ذخیره شد',
					'error'   => 'مشکلی پیش آمد. دوباره تلاش کن.',
					'copied'  => 'لینک کپی شد',
					'loading' => 'در حال بارگذاری…',
					'offline' => 'اینترنت قطع است. تغییرات ذخیره نشد.',
					'printing' => 'در حال آماده‌سازی چاپ…',
					'thinking' => 'در حال بررسی…',
				),
			)
		);

		if ( self::page_has_app() ) {
			wp_enqueue_style( 'moonbanu' );
			wp_enqueue_script( 'moonbanu-qr' );
			wp_enqueue_script( 'moonbanu-app' );
		}
	}

	/** نشانی service worker از ریشه سایت تا scope کل سایت مجاز باشد. */
	public static function sw_url(): string {
		return add_query_arg( array( 'mb_sw' => MB_VERSION ), self::app_start_url() );
	}

	public static function manifest_url(): string {
		return add_query_arg( array( 'mb_manifest' => MB_VERSION ), self::app_start_url() );
	}

	public static function app_start_url(): string {
		$page = (int) MB_Plugin::setting( 'app_page', 0 );
		$url  = $page ? get_permalink( $page ) : home_url( '/' );
		return $url ? $url : home_url( '/' );
	}

	/** Scope اختصاصی برگهٔ اپ، تا service worker اپ دیگری را در ریشهٔ دامنه لمس نکند. */
	public static function scope_path(): string {
		$path = (string) wp_parse_url( self::app_start_url(), PHP_URL_PATH );
		return trailingslashit( '' !== $path ? $path : '/' );
	}

	/** سرو فایل sw.js از ریشه با هدر Service-Worker-Allowed. */
	public static function serve_service_worker(): void {
		$file = MB_DIR . 'assets/sw.js';
		$body = file_exists( $file ) ? (string) file_get_contents( $file ) : '';
		$body = str_replace(
			array( '{{MB_ASSETS}}', '{{MB_VERSION}}' ),
			array( MB_URL . 'assets/', MB_VERSION ),
			$body
		);

		nocache_headers();
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: ' . self::scope_path() );
		echo $body; // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/** manifest با نشانی‌های مطلق. */
	public static function serve_manifest(): void {
		$start = self::app_start_url();
		$name  = MB_Plugin::brand( 'name' );
		$short = (string) MB_Plugin::setting( 'pwa_short_name', $name );
		$theme = self::pwa_color( 'pwa_theme_color' );
		$bg    = self::pwa_color( 'pwa_bg_color' );

		$manifest = array(
			// id یکتا و مستقل، تا نصب اپ هیچ ربطی به PWA سایت نداشته باشد.
			'id'               => self::scope_path() . '?mb_app=1',
			'name'             => '' !== $name ? $name : 'ماه‌بانو',
			'short_name'       => '' !== $short ? $short : ( '' !== $name ? $name : 'ماه‌بانو' ),
			'description'      => 'تقویم چرخه، تحلیل شخصی و همراهی محترمانه — با تقویم شمسی.',
			'lang'             => 'fa-IR',
			'dir'              => 'rtl',
			'start_url'        => $start ? $start : home_url( '/' ),
			'scope'            => self::scope_path(),
			'display'          => 'standalone',
			'display_override' => array( 'standalone', 'minimal-ui' ),
			'orientation'      => 'portrait',
			'background_color' => $bg,
			'theme_color'      => $theme,
			'categories'       => array( 'health', 'lifestyle' ),
			'prefer_related_applications' => false,
			'shortcuts'        => array(
				array( 'name' => 'ثبت امروز', 'url' => $start . '#/log' ),
				array( 'name' => 'تقویم', 'url' => $start . '#/calendar' ),
				array( 'name' => 'پشتیبانی', 'url' => $start . '#/support' ),
			),
			'icons'            => array(
				array( 'src' => MB_URL . 'assets/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ),
				array( 'src' => MB_URL . 'assets/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ),
				array( 'src' => MB_URL . 'assets/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ),
			),
		);

		nocache_headers();
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		echo wp_json_encode( $manifest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	/** رنگ PWA با بازگشت به پیش‌فرض. */
	public static function pwa_color( string $key ): string {
		$fallback = '#05030A';
		$value    = sanitize_hex_color( (string) MB_Plugin::setting( $key, $fallback ) );
		return $value ? $value : $fallback;
	}

	public static function head_meta(): void {
		if ( ! self::needed() ) {
			return;
		}
		$name = MB_Plugin::brand( 'name' );
		// data-mb="1" نشانهٔ تگ‌های خودی است؛ لایهٔ جداسازی فقط تگ‌های بیگانه را حذف می‌کند.
		echo '<meta name="theme-color" content="' . esc_attr( self::pwa_color( 'pwa_theme_color' ) ) . '" data-mb="1">' . "\n";
		echo '<link rel="manifest" href="' . esc_url( self::manifest_url() ) . '" data-mb="1">' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( MB_URL . 'assets/apple-touch-icon.png' ) . '" data-mb="1">' . "\n";
		echo '<meta name="apple-mobile-web-app-capable" content="yes" data-mb="1">' . "\n";
		echo '<meta name="mobile-web-app-capable" content="yes" data-mb="1">' . "\n";
		echo '<meta name="apple-mobile-web-app-title" content="' . esc_attr( $name ) . '" data-mb="1">' . "\n";
		echo '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent" data-mb="1">' . "\n";
		echo '<meta name="color-scheme" content="dark" data-mb="1">' . "\n";
		echo '<meta name="robots" content="noindex,nofollow" data-mb="1">' . "\n";
	}

	public static function footer_pwa(): void {
		if ( ! self::needed() ) {
			return;
		}
		?>
		<script data-mb="1">
		(function(){
			if(!('serviceWorker' in navigator) || !window.MB_BOOT){ return; }
			window.addEventListener('load',function(){
				/* SW اپ فقط با scope خودش ثبت می‌شود؛ SW سایت دست‌نخورده می‌ماند و
				   برای مسیرهای این اپ، scope دقیق‌تر اولویت دارد. */
				navigator.serviceWorker.register(MB_BOOT.sw,{scope:MB_BOOT.scope}).then(function(reg){
					if(reg && reg.update){ try{ reg.update(); }catch(e){} }
				}).catch(function(){});
			});
		})();
		</script>
		<?php
	}
}

/**
 * کتابخانه رندر رابط کاربری (سمت سرور).
 */
final class MB_UI {

	/* --------------------------------------------------------------------- */
	/* مسیرها و قالب‌ها                                                      */
	/* --------------------------------------------------------------------- */

	public static function app_url( string $hash = '' ): string {
		$page_id = (int) MB_Plugin::setting( 'app_page', 0 );
		$base    = $page_id ? get_permalink( $page_id ) : home_url( '/' );
		if ( ! $base ) {
			$base = home_url( '/' );
		}
		return $base . $hash;
	}

	/** رندر یک قالب با داده. */
	public static function render_template( string $name, array $data = array() ): string {
		$file = MB_DIR . 'templates/' . $name . '.php';
		if ( ! file_exists( $file ) ) {
			return '<div class="pnote warn">قالب یافت نشد.</div>';
		}
		ob_start();
		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable
		include $file;
		return MB_Plugin::apply_text_overrides( (string) ob_get_clean() );
	}

	/* --------------------------------------------------------------------- */
	/* اعداد و متن                                                           */
	/* --------------------------------------------------------------------- */

	public static function num( $v ): string {
		return MB_Jalali::fa_num( $v );
	}

	public static function pct( $v ): string {
		return MB_Jalali::fa_num( (int) $v ) . '٪';
	}

	public static function disclaimer(): string {
		return '<p class="disclaimer">' . esc_html( MB_Plugin::disclaimer() ) . '</p>';
	}

	/* --------------------------------------------------------------------- */
	/* آیکن‌ها (SVG درون‌خطی، viewBox 24، stroke 1.7)                         */
	/* --------------------------------------------------------------------- */

	public static function icon_paths(): array {
		return array(
			'heart'    => '<path d="M12 20s-7-4.35-7-9.5A4.5 4.5 0 0 1 12 7a4.5 4.5 0 0 1 7 3.5C19 15.65 12 20 12 20Z"/>',
			'moon'     => '<path d="M20 14.5A8 8 0 0 1 9.5 4a7 7 0 1 0 10.5 10.5Z"/>',
			'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 3v2M12 19v2M3 12h2M19 12h2M5.6 5.6l1.4 1.4M17 17l1.4 1.4M18.4 5.6 17 7M7 17l-1.4 1.4"/>',
			'spark'    => '<path d="M12 3l1.8 5.2L19 10l-5.2 1.8L12 17l-1.8-5.2L5 10l5.2-1.8Z"/>',
			'drop'     => '<path d="M12 3s5 5.5 5 9a5 5 0 0 1-10 0c0-3.5 5-9 5-9Z"/>',
			'leaf'     => '<path d="M5 19c0-7 4-12 14-13 1 10-4 14-11 14H5Z"/><path d="M5 19c3-3 6-5 10-6"/>',
			'wave'     => '<path d="M3 12c2-3 4-3 6 0s4 3 6 0 4-3 6 0"/><path d="M3 17c2-3 4-3 6 0s4 3 6 0 4-3 6 0"/>',
			'cup'      => '<path d="M5 8h11v5a5 5 0 0 1-5 5H10a5 5 0 0 1-5-5Z"/><path d="M16 9h2a2 2 0 0 1 0 5h-2"/><path d="M4 21h13"/>',
			'battery'  => '<rect x="3" y="8" width="15" height="9" rx="2.5"/><path d="M21 11.5v2"/><path d="M7 11.5v2.5"/>',
			'bell'     => '<path d="M18 16H6l1.2-2V11a4.8 4.8 0 0 1 9.6 0v3Z"/><path d="M10 19a2 2 0 0 0 4 0"/>',
			'cal'      => '<rect x="3.5" y="5" width="17" height="15" rx="3"/><path d="M8 3.5v3M16 3.5v3M3.5 10h17"/>',
			'chart'    => '<path d="M5 19V9M12 19V5M19 19v-7"/>',
			'user'     => '<circle cx="12" cy="9" r="3.4"/><path d="M5 20c1.2-3.4 3.8-5 7-5s5.8 1.6 7 5"/>',
			'plus'     => '<path d="M12 6v12M6 12h12"/>',
			'lock'     => '<rect x="5" y="10.5" width="14" height="9.5" rx="2.6"/><path d="M8.5 10.5V8a3.5 3.5 0 0 1 7 0v2.5"/>',
			'shield'   => '<path d="M12 3.5 19 6v6c0 4-3 7-7 8.5C8 19 5 16 5 12V6Z"/>',
			'check'    => '<path d="M5 12.5 10 17.5 19 7.5"/>',
			'alert'    => '<path d="M12 4.5 20.5 19H3.5Z"/><path d="M12 10v4M12 16.5v.6"/>',
			'search'   => '<circle cx="11" cy="11" r="6"/><path d="M15.5 15.5 20 20"/>',
			'back'     => '<path d="M14 5l-7 7 7 7"/>',
			'next'     => '<path d="M10 5l7 7-7 7"/>',
			'copy'     => '<rect x="8" y="8" width="11" height="11" rx="2.6"/><path d="M5 15V6.5A1.5 1.5 0 0 1 6.5 5H15"/>',
			'send'     => '<path d="M4 12l16-7-6 16-3-6Z"/>',
			'qr'       => '<rect x="4" y="4" width="6" height="6" rx="1.4"/><rect x="14" y="4" width="6" height="6" rx="1.4"/><rect x="4" y="14" width="6" height="6" rx="1.4"/><path d="M14 14h3v3h-3zM19 19h1M19 14h1M14 19h1"/>',
			'msg'      => '<path d="M5 6h14v9H9l-4 4Z"/>',
			'book'     => '<path d="M5 5h6a3 3 0 0 1 3 3v11H8a3 3 0 0 0-3 3Z"/><path d="M19 5h-5v14h5Z"/>',
			'star'     => '<path d="M12 4l2.3 5 5.7.6-4.2 3.8 1.2 5.6L12 16.3 7 19l1.2-5.6L4 9.6 9.7 9Z"/>',
			'clock'    => '<circle cx="12" cy="12" r="7.5"/><path d="M12 8v4.5l3 2"/>',
			'bookmark' => '<path d="M6 4h12v16l-6-4-6 4Z"/>',
			'crown'    => '<path d="M4 17 6 8l4 4 2-6 2 6 4-4 2 9Z"/>',
			'eye'      => '<path d="M2.5 12S6 6.5 12 6.5 21.5 12 21.5 12 18 17.5 12 17.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.6"/>',
			'x'        => '<path d="M6 6l12 12M18 6 6 18"/>',
			// نسخهٔ ۳٫۰
			'baby'     => '<circle cx="12" cy="8.5" r="4"/><path d="M10 7.6v.1M14 7.6v.1M10.4 10.4c.9.7 2.3.7 3.2 0"/><path d="M6 20c1-3.2 3.2-4.8 6-4.8s5 1.6 6 4.8"/>',
			'pill'     => '<rect x="4" y="9" width="16" height="6" rx="3"/><path d="M9.4 14.6 14.6 9.4"/>',
			'temp'     => '<path d="M12 14.2V5.6a2 2 0 1 1 4 0v8.6a3.4 3.4 0 1 1-4 0Z"/><path d="M8 8h2M8 11h2"/>',
			'quiz'     => '<rect x="4.5" y="3.6" width="15" height="16.8" rx="3"/><path d="M9 9h6M9 12.5h6M9 16h3.5"/>',
			'people'   => '<circle cx="9" cy="9.2" r="2.9"/><path d="M3.6 19c.9-2.7 2.9-4 5.4-4s4.5 1.3 5.4 4"/><path d="M16 6.6a2.9 2.9 0 0 1 0 5.6M17.4 15.2c1.6.6 2.6 1.9 3 3.8"/>',
			'print'    => '<path d="M7 9V4h10v5"/><rect x="4" y="9" width="16" height="7" rx="2.4"/><path d="M7 16h10v4H7z"/>',
			'chat'     => '<path d="M20 12.6c0 3.4-3.6 6.1-8 6.1a9.6 9.6 0 0 1-2.6-.35L5 20l1.2-3.1A5.8 5.8 0 0 1 4 12.6c0-3.4 3.6-6.1 8-6.1s8 2.7 8 6.1Z"/>',
			'water'    => '<path d="M12 3.5s5.4 5.9 5.4 9.6a5.4 5.4 0 0 1-10.8 0C6.6 9.4 12 3.5 12 3.5Z"/><path d="M9.5 13.6a2.6 2.6 0 0 0 2.5 2.6"/>',
			'run'      => '<circle cx="14.4" cy="5.2" r="1.9"/><path d="M13 8.4 9.6 11l2.6 2.2-1 4.6M12.2 13.2l3.6 1.4 1.4 3.6M9.6 11 6 10.4"/>',
			'scale'    => '<rect x="4" y="5" width="16" height="14" rx="3.4"/><path d="M9 9.4c1.6-1.2 4.4-1.2 6 0"/><path d="M12 13.6V11"/>',
			// نسخهٔ ۳٫۱ — کلیدهایی که در قالب‌ها صدا زده می‌شدند و به spark می‌افتادند.
			'menu'     => '<path d="M4 7h16M4 12h16M4 17h11"/>',
			'edit'     => '<path d="M5 19h3l9.5-9.5a2.1 2.1 0 0 0-3-3L5 16Z"/><path d="M14.5 6.5 17.5 9.5"/>',
			'logout'   => '<path d="M14 5H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h7"/><path d="M17 8.5 20.5 12 17 15.5M20.5 12H10"/>',
			'chevron'  => '<path d="M15.5 7.5 9.5 12l6 4.5"/>',
			'info'     => '<circle cx="12" cy="12" r="8"/><path d="M12 11v5.5M12 7.9v.6"/>',
		);
	}

	public static function icon( string $name, int $size = 18, float $stroke = 1.7, string $class = '' ): string {
		$paths = self::icon_paths();
		$d     = $paths[ $name ] ?? $paths['spark'];
		return sprintf(
			'<svg class="mb-ic %1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="%3$s" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">%4$s</svg>',
			esc_attr( $class ),
			(int) $size,
			esc_attr( (string) $stroke ),
			$d
		);
	}

	/**
	 * دکمهٔ زنگ اعلان‌ها با نشان تعداد خوانده‌نشده.
	 * اکشن notifications در app.js پنل اعلان‌ها را باز می‌کند و همه را خوانده می‌کند.
	 */
	public static function bell( int $unread = 0 ): string {
		$badge = $unread > 0
			? '<span class="badge">' . esc_html( self::num( min( 99, $unread ) ) ) . '</span>'
			: '';
		return sprintf(
			'<button type="button" class="ic n bell" data-mb="notifications" aria-label="%s">%s%s</button>',
			esc_attr( $unread > 0 ? 'اعلان‌ها، ' . self::num( $unread ) . ' تازه' : 'اعلان‌ها' ),
			self::icon( 'bell', 19, 1.9 ),
			$badge
		);
	}

	/** دکمه آیکنی گرد (ic n). */
	public static function ic_btn( string $icon, string $action = '', string $label = '' ): string {
		return sprintf(
			'<button type="button" class="ic n" data-mb="%s" aria-label="%s">%s</button>',
			esc_attr( $action ),
			esc_attr( $label ),
			self::icon( $icon, 17, 1.8 )
		);
	}

	/* --------------------------------------------------------------------- */
	/* چیپ، دکمه، نوار                                                       */
	/* --------------------------------------------------------------------- */

	public static function chip( string $text, string $variant = 'default', string $icon = '' ): string {
		$allowed = array( 'default', 'period', 'fertile', 'ovul', 'pms', 'gold', 'dark', 'luteal', 'foll', 'norm' );
		$variant = in_array( $variant, $allowed, true ) ? $variant : 'default';
		$ic      = '' !== $icon ? self::icon( $icon, 12, 1.9 ) : '';
		return '<span class="chip ' . esc_attr( $variant ) . '">' . $ic . '<span>' . esc_html( $text ) . '</span></span>';
	}

	public static function btn( string $text, string $variant = 'gold', string $action = '', array $args = array() ): string {
		$classes = array( 'btn' );
		foreach ( explode( ' ', $variant ) as $v ) {
			$v = sanitize_html_class( $v );
			if ( '' !== $v ) {
				$classes[] = $v;
			}
		}
		$icon  = isset( $args['icon'] ) ? self::icon( (string) $args['icon'], 15, 2.2 ) : '';
		$attrs = '';
		foreach ( (array) ( $args['data'] ?? array() ) as $k => $v ) {
			$attrs .= ' data-' . sanitize_key( $k ) . '="' . esc_attr( (string) $v ) . '"';
		}
		if ( ! empty( $args['href'] ) ) {
			return '<a class="' . esc_attr( implode( ' ', $classes ) ) . '" href="' . esc_url( (string) $args['href'] ) . '"' . $attrs . '>' . $icon . '<span>' . esc_html( $text ) . '</span></a>';
		}
		return '<button type="button" class="' . esc_attr( implode( ' ', $classes ) ) . '" data-mb="' . esc_attr( $action ) . '"' . $attrs . '>' . $icon . '<span>' . esc_html( $text ) . '</span></button>';
	}

	public static function bar( string $label, int $percent, string $color = 'gold', string $value_text = '' ): string {
		$percent = (int) max( 0, min( 100, $percent ) );
		$value   = 'hide' === $value_text ? '' : ( '' !== $value_text ? $value_text : self::pct( $percent ) );
		return '<div class="bar-row">
			<div class="bar-head"><span class="h3">' . esc_html( $label ) . '</span><span class="bar-val">' . esc_html( $value ) . '</span></div>
			<div class="bar"><i class="bar-fill ' . esc_attr( sanitize_html_class( $color ) ) . '" style="width:' . $percent . '%"></i></div>
		</div>';
	}

	public static function lrow( string $title, string $text = '', string $icon = 'spark', array $args = array() ): string {
		$right = isset( $args['right'] ) ? (string) $args['right'] : self::icon( 'next', 15, 1.8, 'lrow-arrow' );
		$tag   = ! empty( $args['action'] ) ? 'button' : 'div';
		$attr  = ! empty( $args['action'] ) ? ' type="button" data-mb="' . esc_attr( (string) $args['action'] ) . '"' : '';
		foreach ( (array) ( $args['data'] ?? array() ) as $k => $v ) {
			$attr .= ' data-' . sanitize_key( $k ) . '="' . esc_attr( (string) $v ) . '"';
		}
		return '<' . $tag . ' class="lrow"' . $attr . '>
			<span class="lrow-ic">' . self::icon( $icon, 16, 1.8 ) . '</span>
			<span class="lrow-body"><span class="lrow-t">' . esc_html( $title ) . '</span>' . ( '' !== $text ? '<span class="lrow-s">' . esc_html( $text ) . '</span>' : '' ) . '</span>
			<span class="lrow-end">' . $right . '</span>
		</' . $tag . '>';
	}

	public static function pnote( string $text, string $variant = 'warn', string $icon = 'alert' ): string {
		$variant = in_array( $variant, array( 'warn', 'good', 'info' ), true ) ? $variant : 'warn';
		return '<div class="pnote ' . esc_attr( $variant ) . '">' . self::icon( $icon, 14, 1.9 ) . '<span>' . esc_html( $text ) . '</span></div>';
	}

	public static function toggle( string $label, string $sub, bool $on, string $action, array $data = array() ): string {
		$attr = '';
		foreach ( $data as $k => $v ) {
			$attr .= ' data-' . sanitize_key( $k ) . '="' . esc_attr( (string) $v ) . '"';
		}
		return '<div class="tgl-row">
			<div class="tgl-body"><span class="h3">' . esc_html( $label ) . '</span>' . ( '' !== $sub ? '<span class="small">' . esc_html( $sub ) . '</span>' : '' ) . '</div>
			<button type="button" class="toggle' . ( $on ? ' on' : '' ) . '" role="switch" aria-checked="' . ( $on ? 'true' : 'false' ) . '" data-mb="' . esc_attr( $action ) . '"' . $attr . '><i></i></button>
		</div>';
	}

	/* --------------------------------------------------------------------- */
	/* حلقه چرخه (SVG، ۲۸ بخش، شروع ساعت ۱۲، ساعتگرد)                        */
	/* --------------------------------------------------------------------- */

	/**
	 * @param int    $size       قطر (64/74/92/132/140/158/186/200/300).
	 * @param array  $segments   آرایه فاز به ترتیب روزهای چرخه.
	 * @param int    $current    روز جاری (۱-based) برای نشانگر طلایی.
	 * @param array  $center     آرایه top/num/sub برای متن میانی.
	 */
	public static function ring( int $size, array $segments, int $current = 0, array $center = array() ): string {
		$size      = max( 56, min( 320, $size ) );
		$thickness = (int) round( max( 11, min( 15, $size / 12 ) ) );
		$r         = ( $size - $thickness ) / 2 - 1;
		$cx        = $size / 2;
		$cy        = $size / 2;
		$count     = max( 1, count( $segments ) );
		$step      = 360 / $count;
		$gap       = $step * 0.55; // فاصله ۰٫۵۵ روز میان بخش‌ها، طبق سند طراحی.

		$svg = '<svg class="ring_svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" role="img" aria-label="حلقه چرخه">';
		$svg .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="none" stroke="rgba(255,255,255,.06)" stroke-width="' . $thickness . '"/>';

		$i = 0;
		foreach ( $segments as $phase ) {
			$a1 = -90 + $i * $step + $gap / 2;
			$a2 = -90 + ( $i + 1 ) * $step - $gap / 2;
			$svg .= '<path d="' . self::arc_path( $cx, $cy, $r, $a1, $a2 ) . '" fill="none" stroke-linecap="round" stroke-width="' . $thickness . '" class="seg seg-' . esc_attr( MB_Privacy::phase_color_key( (string) $phase ) ) . '"/>';
			++$i;
		}

		if ( $current > 0 && $current <= $count ) {
			$angle = -90 + ( $current - 0.5 ) * $step;
			$mx    = $cx + $r * cos( deg2rad( $angle ) );
			$my    = $cy + $r * sin( deg2rad( $angle ) );
			$svg  .= '<circle cx="' . round( $mx, 2 ) . '" cy="' . round( $my, 2 ) . '" r="' . max( 4, $thickness / 2 + 1 ) . '" fill="none" stroke="#FAEFC8" stroke-width="2.2"/>';
		}

		$svg .= '</svg>';

		$inner = '';
		if ( ! empty( $center ) ) {
			$inner .= '<div class="ring-center">';
			if ( ! empty( $center['top'] ) ) {
				$inner .= '<span class="ring-top">' . esc_html( (string) $center['top'] ) . '</span>';
			}
			if ( isset( $center['num'] ) && '' !== $center['num'] ) {
				$inner .= '<span class="ring-num">' . esc_html( (string) $center['num'] ) . '</span>';
			}
			if ( ! empty( $center['sub'] ) ) {
				$inner .= '<span class="ring-sub">' . esc_html( (string) $center['sub'] ) . '</span>';
			}
			if ( ! empty( $center['chip'] ) ) {
				$inner .= (string) $center['chip'];
			}
			$inner .= '</div>';
		}

		return '<div class="ring" style="width:' . $size . 'px;height:' . $size . 'px">' . $svg . $inner . '</div>';
	}

	/** حلقه پیشرفت ساده (درصدی) برای بج دقت. */
	public static function progress_ring( int $size, int $percent, string $label = '', string $sub = '', string $value_text = '' ): string {
		$percent   = (int) max( 0, min( 100, $percent ) );
		$thickness = (int) round( max( 7, min( 13, $size / 9 ) ) );
		$r         = ( $size - $thickness ) / 2 - 1;
		$circ      = 2 * M_PI * $r;
		$dash      = $circ * $percent / 100;

		// شناسه یکتا: چند حلقه در یک صفحه نباید گرادیان هم را بازنویسی کنند.
		$gid = 'mbGold' . wp_unique_id();
		$svg = '<svg class="ring_svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '" role="img" aria-label="' . esc_attr( $label ) . '">';
		$svg .= '<defs><linearGradient id="' . esc_attr( $gid ) . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FDEFC4"/><stop offset=".55" stop-color="#D9B45B"/><stop offset="1" stop-color="#9C7228"/></linearGradient></defs>';
		$svg .= '<circle cx="' . $size / 2 . '" cy="' . $size / 2 . '" r="' . $r . '" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="' . $thickness . '"/>';
		$svg .= '<circle cx="' . $size / 2 . '" cy="' . $size / 2 . '" r="' . $r . '" fill="none" stroke="url(#' . esc_attr( $gid ) . ')" stroke-width="' . $thickness . '" stroke-linecap="round" stroke-dasharray="' . round( $dash, 2 ) . ' ' . round( $circ, 2 ) . '" transform="rotate(-90 ' . $size / 2 . ' ' . $size / 2 . ')"/>';
		$svg .= '</svg>';

		$value = 'hide' === $value_text ? '' : ( '' !== $value_text ? $value_text : self::pct( $percent ) );
		return '<div class="ring" style="width:' . $size . 'px;height:' . $size . 'px">' . $svg . '<div class="ring-center">' . ( '' !== $value ? '<span class="ring-num">' . esc_html( $value ) . '</span>' : '' ) . ( '' !== $sub ? '<span class="ring-sub">' . esc_html( $sub ) . '</span>' : '' ) . '</div></div>';
	}

	private static function arc_path( float $cx, float $cy, float $r, float $a1, float $a2 ): string {
		$x1    = $cx + $r * cos( deg2rad( $a1 ) );
		$y1    = $cy + $r * sin( deg2rad( $a1 ) );
		$x2    = $cx + $r * cos( deg2rad( $a2 ) );
		$y2    = $cy + $r * sin( deg2rad( $a2 ) );
		$large = ( $a2 - $a1 ) > 180 ? 1 : 0;
		return sprintf( 'M %s %s A %s %s 0 %d 1 %s %s', round( $x1, 2 ), round( $y1, 2 ), round( $r, 2 ), round( $r, 2 ), $large, round( $x2, 2 ), round( $y2, 2 ) );
	}

	/** آرایه فاز روزهای یک چرخه کامل. */
	public static function cycle_segments( int $user_id ): array {
		$len  = MB_Cycle_Engine::cycle_len( $user_id );
		$segs = array();
		for ( $i = 1; $i <= $len; $i++ ) {
			$segs[] = MB_Cycle_Engine::phase_of_index( $user_id, $i );
		}
		return $segs;
	}

	/* --------------------------------------------------------------------- */
	/* تب‌بار                                                                */
	/* --------------------------------------------------------------------- */

	public static function tabbar( string $active, string $mode = 'woman' ): string {
		if ( 'partner' === $mode ) {
			$tabs = array(
				array( 'partner', 'خانه', 'heart' ),
				array( 'reminder', 'تقویم', 'cal' ),
				array( 'messages', 'پیام‌ها', 'msg' ),
				array( 'calm', 'راهنما', 'book' ),
			);
		} else {
			$tabs = array(
				array( 'home', 'خانه', 'heart' ),
				array( 'calendar', 'تقویم', 'cal' ),
				array( 'log', '', 'plus' ),
				array( 'analysis', 'تحلیل', 'chart' ),
				array( 'me', 'من', 'user' ),
			);
		}

		// مسیرهای نسخهٔ ۳٫۰ به نزدیک‌ترین تب نگاشت می‌شوند تا همیشه یک تب روشن باشد.
		$alias = array(
			'ttc'       => 'analysis',
			'report'    => 'analysis',
			'assistant' => 'analysis',
			'pregnancy' => 'home',
			'quizzes'   => 'health',
			'quiz'      => 'health',
			'community' => 'health',
			'reminders' => 'me',
			'account'   => 'me',
			'checkout'  => 'me',
			'connect'   => 'me',
		);
		$active = $alias[ $active ] ?? $active;

		$html = '<nav class="tabbar" aria-label="ناوبری اصلی">';
		foreach ( $tabs as $tab ) {
			list( $route, $label, $icon ) = $tab;
			if ( '' === $label ) {
				$html .= '<a class="fab" href="#/log" aria-label="ثبت نشانه">' . self::icon( $icon, 22, 2.4 ) . '</a>';
				continue;
			}
			$is    = $active === $route ? ' on' : '';
			$html .= '<a class="tab' . $is . '" href="#/' . esc_attr( $route ) . '">' . self::icon( $icon, 19, 1.8 ) . '<span>' . esc_html( $label ) . '</span></a>';
		}
		$html .= '</nav>';
		return $html;
	}

	/* --------------------------------------------------------------------- */
	/* صفحه‌های کمکی                                                         */
	/* --------------------------------------------------------------------- */

	/** صفحهٔ احراز هویت درون‌اپ (ورود / ثبت‌نام / بازیابی). */
	public static function login_gate( string $mode = 'login' ): string {
		return self::render_template(
			'auth',
			array(
				'mode'       => in_array( $mode, array( 'login', 'register', 'forgot', 'reset', 'otp' ), true ) ? $mode : 'login',
				'nonce'      => MB_Auth::nonce(),
				// M6 — پنل کد یک‌بارمصرف فقط وقتی نشان داده می‌شود که راهی برای
				// ارسال کد وجود داشته باشد (پیامک تنظیم‌شده یا ایمیل سایت).
				'otp_enabled' => (bool) (int) MB_Plugin::setting( 'otp_login', 1 ),
				'can_signup' => (bool) (int) MB_Plugin::setting( 'allow_registration', 1 ),
				'terms_url'  => (string) MB_Plugin::setting( 'terms_url', '' ),
				'privacy_url' => (string) MB_Plugin::setting( 'privacy_url', '' ),
			)
		);
	}

	/** بلوک اعتبار طراح، در پای صفحه‌های اپ. */
	public static function brand_footer(): string {
		$credit = MB_Plugin::brand( 'credit' );
		$studio = MB_Plugin::brand( 'studio' );
		$url    = MB_Plugin::brand( 'dev_url' );

		$html = '<div class="mb-credit">';
		if ( '' !== $credit ) {
			$html .= '<span class="mb-credit-t">' . esc_html( $credit ) . '</span>';
		}
		if ( '' !== $url ) {
			$label = '' !== $studio ? $studio : 'برنامه‌های ما';
			$html .= '<a class="mb-credit-link" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">'
				. self::icon( 'spark', 12, 2 ) . '<span>' . esc_html( $label ) . ' · دیگر برنامه‌های ما</span></a>';
		}
		$html .= '</div>';
		return $html;
	}

	/** کارت تماس پشتیبانی. */
	public static function support_contact(): string {
		$phone = MB_Plugin::brand( 'phone' );
		$email = MB_Plugin::brand( 'email' );
		$hours = (string) MB_Plugin::setting( 'support_hours', '' );

		$html = '<div class="contact-row">';
		if ( '' !== $phone ) {
			$html .= '<a class="contact-chip" href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $phone ) ) . '">'
				. self::icon( 'msg', 14, 1.9 ) . '<span dir="ltr">' . esc_html( $phone ) . '</span></a>';
		}
		if ( '' !== $email ) {
			$html .= '<a class="contact-chip" href="mailto:' . esc_attr( $email ) . '">'
				. self::icon( 'send', 14, 1.9 ) . '<span dir="ltr">' . esc_html( $email ) . '</span></a>';
		}
		$html .= '</div>';
		if ( '' !== $hours ) {
			$html .= '<p class="small">ساعت پاسخ‌گویی: ' . esc_html( $hours ) . '</p>';
		}
		return $html;
	}

	public static function render_standalone_result( bool $ok, string $message, string $back_url ): void {
		status_header( 200 );
		nocache_headers();
		?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo $ok ? 'پرداخت موفق' : 'پرداخت ناموفق'; ?> | ماه‌بانو</title>
<link rel="stylesheet" href="<?php echo esc_url( MB_URL . 'assets/css/style.css?v=' . MB_VERSION ); ?>">
</head>
<body class="mb-standalone">
<div class="mb-app" dir="rtl">
	<div class="mb-bg" aria-hidden="true"></div>
	<div class="mb-view"><div class="scr"><div class="pad"><div class="glass card center">
		<div class="brand-sm gold-text">ماه‌بانو</div>
		<div class="result-ic <?php echo $ok ? 'good' : 'bad'; ?>"><?php echo self::icon( $ok ? 'check' : 'x', 30, 2.2 ); ?></div>
		<h1 class="h1"><?php echo $ok ? 'اشتراک فعال شد' : 'پرداخت کامل نشد'; ?></h1>
		<p class="body"><?php echo esc_html( $message ); ?></p>
		<a class="btn gold wide" href="<?php echo esc_url( $back_url ); ?>">بازگشت به اپ</a>
		<p class="small disclaimer"><?php echo esc_html( MB_Plugin::disclaimer() ); ?></p>
	</div></div></div></div>
</div>
</body>
</html>
		<?php
	}

	/**
	 * بلوک قفل Pro.
	 *
	 * خروجی شامل سه بولت ویژگی، دو چیپ اعتماد، و یک نمونهٔ تار (blur 6px) از
	 * شکل واقعی خروجی است. نمونه فقط اسکلت بصری است و هیچ دادهٔ واقعی Pro در
	 * HTML وجود ندارد — گیت کاملاً سمت سرور اعمال می‌شود.
	 *
	 * @param string   $title   عنوان.
	 * @param string   $text    توضیح.
	 * @param string[] $bullets سه ویژگی کلیدی.
	 * @param string   $sample  کلید نمونهٔ بصری (analysis|cycle|ttc|report|assistant).
	 */
	public static function pro_gate( string $title, string $text, array $bullets = array(), string $sample = '' ): string {
		if ( empty( $bullets ) ) {
			$bullets = array( 'تحلیل شخصی بر پایهٔ ثبت‌های خودت', 'پیش‌بینی دقیق‌تر سه چرخهٔ آینده', 'گزارش قابل ارائه به پزشک' );
		}
		$bullets = array_slice( array_values( $bullets ), 0, 3 );

		$list = '<ul class="pro-bullets">';
		foreach ( $bullets as $bullet ) {
			$list .= '<li>' . self::icon( 'check', 14, 2.4 ) . '<span>' . esc_html( (string) $bullet ) . '</span></li>';
		}
		$list .= '</ul>';

		// دو چیپ اعتماد: ثابت و صادقانه.
		$trust = '<div class="pro-trust">'
			. self::chip( 'لغو هر زمان، بدون تمدید خودکار', 'dark', 'shield' )
			. self::chip( 'دادهٔ خصوصی هرگز به همسر نمی‌رود', 'dark', 'lock' )
			. '</div>';

		return '<div class="glass card center pro-gate">
			' . self::icon( 'crown', 26, 1.9, 'pro-crown' ) . '
			<h2 class="h1">' . esc_html( $title ) . '</h2>
			<p class="body">' . esc_html( $text ) . '</p>
			' . $list . '
			' . self::pro_sample( $sample ) . '
			' . $trust . '
			' . self::btn( 'فعال‌سازی نسخهٔ پیشرفته', 'gold wide', 'goto', array( 'data' => array( 'route' => 'checkout' ) ) ) . '
			<p class="small">' . esc_html( 'قیمت ماهانه: ' . MB_Subscription::price_fa() ) . '</p>
		</div>';
	}

	/** نمونهٔ تار از شکل خروجی واقعی (بدون هیچ دادهٔ واقعی). */
	public static function pro_sample( string $key ): string {
		$rows = array(
			'analysis'  => array( array( 'میانگین چرخه', 78 ), array( 'نوسان', 42 ), array( 'نشانهٔ غالب', 64 ) ),
			'cycle'     => array( array( 'قاعدگی', 30 ), array( 'باروری', 55 ), array( 'لوتئال', 80 ) ),
			'ttc'       => array( array( 'دمای پایه', 62 ), array( 'پنجرهٔ باروری', 71 ), array( 'تخمک‌گذاری', 48 ) ),
			'report'    => array( array( 'چرخه‌ها', 88 ), array( 'ثبت‌های روزانه', 74 ), array( 'فراوانی نشانه‌ها', 59 ) ),
			'assistant' => array( array( 'پاسخ فاز فعلی', 70 ), array( 'آخرین ثبت‌ها', 52 ), array( 'مقالهٔ مرتبط', 81 ) ),
		);
		if ( ! isset( $rows[ $key ] ) ) {
			return '';
		}

		$html = '<div class="pro-sample" aria-hidden="true">';
		foreach ( $rows[ $key ] as $row ) {
			$html .= '<div class="ps-row"><span class="ps-lbl">' . esc_html( (string) $row[0] ) . '</span>'
				. '<span class="ps-bar"><i style="width:' . (int) $row[1] . '%"></i></span></div>';
		}
		$html .= '</div><p class="tiny center muted">نمونهٔ شکل خروجی — با فعال‌سازی، دادهٔ خودت را می‌بینی.</p>';
		return $html;
	}
}
