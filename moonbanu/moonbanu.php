<?php
/**
 * Plugin Name:       ماه‌بانو | Moonbanu
 * Plugin URI:        https://kafezare.ir/zsa-studio/
 * Description:       اپلیکیشن کامل سلامت بانوان (تقویم شمسی، پیش‌بینی چرخه، تحلیل شخصی، همراهی همسر، اشتراک ماهیانه، پشتیبانی درون‌اپ) به‌صورت یک PWA کاملاً مستقل و جدا از قالب سایت.
 * Version:           3.1.4
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            حسین زارع — ZSA Studio
 * Author URI:        https://kafezare.ir/zsa-studio/
 * Text Domain:       moonbanu
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'MB_VERSION', '3.1.4' );
define( 'MB_FILE', __FILE__ );
define( 'MB_DIR', plugin_dir_path( __FILE__ ) );
define( 'MB_URL', plugin_dir_url( __FILE__ ) );
define( 'MB_BASENAME', plugin_basename( __FILE__ ) );
define( 'MB_DISCLAIMER', 'این اپ جایگزین تشخیص یا درمان پزشکی نیست.' );
define( 'MB_ROLE_WOMAN', 'mb_woman' );
define( 'MB_ROLE_PARTNER', 'mb_partner' );
define( 'MB_DEV_CREDIT', 'طراحی توسط حسین زارع' );
define( 'MB_DEV_URL', 'https://kafezare.ir/zsa-studio/' );
define( 'MB_DEV_STUDIO', 'ZSA Studio' );
define( 'MB_SUPPORT_PHONE', '09351063668' );
define( 'MB_SUPPORT_EMAIL', 'hosseinzarec@gmail.com' );

require_once MB_DIR . 'includes/class-jalali.php';
require_once MB_DIR . 'includes/class-db.php';
require_once MB_DIR . 'includes/class-cycle-engine.php';
require_once MB_DIR . 'includes/class-privacy.php';
require_once MB_DIR . 'includes/class-subscription.php';
require_once MB_DIR . 'includes/class-gateway.php';
require_once MB_DIR . 'includes/class-gateway-zarinpal.php';
require_once MB_DIR . 'includes/class-gateway-zibal.php';
require_once MB_DIR . 'includes/class-license.php';
require_once MB_DIR . 'includes/class-notify.php';
require_once MB_DIR . 'includes/class-support.php';
require_once MB_DIR . 'includes/class-api.php';
require_once MB_DIR . 'includes/class-auth.php';
require_once MB_DIR . 'includes/class-admin.php';
require_once MB_DIR . 'includes/class-admin-users.php';
require_once MB_DIR . 'includes/class-assets.php';
require_once MB_DIR . 'includes/class-isolation.php';

// ماژول‌های نسخهٔ ۳٫۰
require_once MB_DIR . 'includes/class-pregnancy.php';
require_once MB_DIR . 'includes/class-ttc.php';
require_once MB_DIR . 'includes/class-irregular.php';
require_once MB_DIR . 'includes/class-report.php';
require_once MB_DIR . 'includes/class-reminders.php';
require_once MB_DIR . 'includes/class-otp.php';
require_once MB_DIR . 'includes/class-community.php';
require_once MB_DIR . 'includes/class-quiz.php';
require_once MB_DIR . 'includes/class-assistant.php';
require_once MB_DIR . 'includes/class-referral.php';
require_once MB_DIR . 'includes/class-selftest.php';

// ماژول‌های نسخهٔ ۳٫۱
require_once MB_DIR . 'includes/class-crypto.php';
require_once MB_DIR . 'includes/class-couple.php';
require_once MB_DIR . 'includes/class-health-profile.php';
require_once MB_DIR . 'includes/class-emergency.php';
require_once MB_DIR . 'includes/class-screening.php';
require_once MB_DIR . 'includes/class-insights.php';
require_once MB_DIR . 'includes/class-faq.php';
require_once MB_DIR . 'includes/class-sla.php';
require_once MB_DIR . 'includes/class-prune.php';
require_once MB_DIR . 'includes/class-article-seed.php';

/**
 * هسته پلاگین ماه‌بانو.
 */
final class MB_Plugin {

	private static ?MB_Plugin $instance = null;

	public static function instance(): MB_Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		register_activation_hook( MB_FILE, array( __CLASS__, 'activate' ) );
		register_deactivation_hook( MB_FILE, array( __CLASS__, 'deactivate' ) );

		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'register_content_types' ) );
		add_action( 'init', array( $this, 'maybe_upgrade' ) );
		add_action( 'init', array( MB_Subscription::class, 'maybe_expire_current_user' ), 20 );

		add_shortcode( 'moonbanu_app', array( $this, 'render_app' ) );

		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'template_redirect', array( $this, 'handle_public_endpoints' ) );

		MB_Assets::init();
		MB_Isolation::init();
		MB_Api::init();
		MB_Auth::init();
		MB_Support::init();
		MB_Admin::init();
		MB_Notify::init();
		MB_License::init();
		MB_Privacy::init();
		MB_Subscription::hooks();

		// نسخهٔ ۳٫۰
		MB_Quiz::init();
		MB_Reminders::init();
		MB_OTP::init();
		MB_Report::init();

		// نسخهٔ ۳٫۱
		MB_Crypto::init();
		MB_FAQ::init();
		MB_Screening::init();
		MB_SLA::init();
		MB_Prune::init();
		MB_Article_Seed::init();
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'moonbanu', false, dirname( MB_BASENAME ) . '/languages' );
	}

	/* --------------------------------------------------------------------- */
	/* فعال‌سازی و غیرفعال‌سازی                                              */
	/* --------------------------------------------------------------------- */

	public static function activate(): void {
		MB_DB::install();
		self::register_roles();

		if ( ! get_option( 'mb_install_token' ) ) {
			add_option( 'mb_install_token', wp_generate_password( 40, false, false ) );
		}
		if ( ! get_option( 'mb_site_hash' ) ) {
			add_option( 'mb_site_hash', hash( 'sha256', home_url( '/' ) ) );
		}
		if ( ! get_option( 'mb_settings' ) ) {
			add_option( 'mb_settings', MB_Admin::default_settings() );
		}
		self::ensure_app_page();
		if ( ! get_option( 'mb_cron_token' ) ) {
			add_option( 'mb_cron_token', wp_generate_password( 32, false, false ) );
		}

		MB_License::rebuild_manifest();
		MB_Notify::schedule();
		MB_Subscription::schedule();
		MB_Reminders::schedule();
		MB_SLA::schedule();
		MB_Prune::schedule();
		MB_Screening::ensure_rows();
		update_option( 'mb_version', MB_VERSION );
		// کش Pro باید پس از ارتقا تازه شود.
		self::flush_pro_cache();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		MB_Notify::unschedule();
		MB_Subscription::unschedule();
		MB_Reminders::unschedule();
		MB_SLA::unschedule();
		MB_Prune::unschedule();
		flush_rewrite_rules();
	}

	public function maybe_upgrade(): void {
		if ( get_option( 'mb_version' ) === MB_VERSION ) {
			return;
		}
		MB_DB::install();
		self::register_roles();
		self::ensure_app_page();
		MB_License::rebuild_manifest();
		MB_Notify::schedule();
		MB_Subscription::schedule();
		MB_Reminders::schedule();
		MB_SLA::schedule();
		MB_Prune::schedule();
		MB_Screening::ensure_rows();
		update_option( 'mb_version', MB_VERSION );
		self::flush_pro_cache();
	}

	/** پاک‌کردن کش وضعیت Pro همه کاربران (پس از ارتقا یا تغییر تنظیمات). */
	public static function flush_pro_cache(): void {
		global $wpdb;
		$wpdb->query(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mb_pro_%' OR option_name LIKE '_transient_timeout_mb_pro_%' OR option_name LIKE '_transient_mb_lifecycle_%' OR option_name LIKE '_transient_timeout_mb_lifecycle_%'"
		);
	}

	/** برگهٔ اپ را در نصب تازه می‌سازد تا PWA scope اختصاصی داشته باشد. */
	public static function ensure_app_page(): void {
		$s = (array) get_option( 'mb_settings', array() );
		$current = isset( $s['app_page'] ) ? (int) $s['app_page'] : 0;
		if ( $current > 0 && get_post( $current ) ) {
			return;
		}
		$page = get_page_by_path( 'moonbanu' );
		if ( ! $page ) {
			$id = wp_insert_post(
				array(
					'post_title'   => 'ماه‌بانو',
					'post_name'    => 'moonbanu',
					'post_content' => '[moonbanu_app]',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				),
				true
			);
			$page = is_wp_error( $id ) ? null : get_post( $id );
		}
		if ( $page instanceof WP_Post ) {
			$s['app_page'] = (int) $page->ID;
			update_option( 'mb_settings', wp_parse_args( $s, MB_Admin::default_settings() ) );
		}
	}

	public static function register_roles(): void {
		add_role( MB_ROLE_WOMAN, 'بانوی ماه‌بانو', array( 'read' => true, 'mb_use_app' => true ) );
		add_role( MB_ROLE_PARTNER, 'همراه ماه', array( 'read' => true, 'mb_partner_view' => true ) );

		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'mb_use_app' );
			$admin->add_cap( 'mb_manage' );
			$admin->add_cap( 'mb_partner_view' );
		}
		$sub = get_role( 'subscriber' );
		if ( $sub ) {
			$sub->add_cap( 'mb_use_app' );
		}
	}

	/* --------------------------------------------------------------------- */
	/* CPT و تاکسونومی راهنما                                                */
	/* --------------------------------------------------------------------- */

	public function register_content_types(): void {
		register_post_type(
			'mb_article',
			array(
				'labels'       => array(
					'name'          => 'راهنماهای سلامت',
					'singular_name' => 'راهنمای سلامت',
					'add_new_item'  => 'افزودن راهنمای جدید',
					'edit_item'     => 'ویرایش راهنما',
				),
				'public'       => false,
				'show_ui'      => true,
				'show_in_menu' => false,
				'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'has_archive'  => false,
				'rewrite'      => false,
				'menu_icon'    => 'dashicons-heart',
			)
		);

		register_taxonomy(
			'mb_cat',
			array( 'mb_article' ),
			array(
				'labels'       => array( 'name' => 'دسته‌های راهنما', 'singular_name' => 'دسته راهنما' ),
				'public'       => false,
				'show_ui'      => true,
				'hierarchical' => true,
				'rewrite'      => false,
			)
		);

		register_post_meta( 'mb_article', 'read_minutes', array( 'type' => 'integer', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'absint', 'auth_callback' => array( __CLASS__, 'meta_auth' ) ) );
		register_post_meta( 'mb_article', 'featured_week', array( 'type' => 'integer', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'absint', 'auth_callback' => array( __CLASS__, 'meta_auth' ) ) );

		self::seed_taxonomy();
	}

	public static function meta_auth(): bool {
		return current_user_can( 'edit_posts' );
	}

	public static function seed_taxonomy(): void {
		if ( get_option( 'mb_cats_seeded' ) ) {
			return;
		}
		foreach ( self::categories() as $slug => $label ) {
			if ( ! term_exists( $slug, 'mb_cat' ) ) {
				wp_insert_term( $label, 'mb_cat', array( 'slug' => $slug ) );
			}
		}
		update_option( 'mb_cats_seeded', 1 );
	}

	public static function categories(): array {
		return array(
			'period'        => 'قاعدگی',
			'contraception' => 'پیشگیری',
			'nutrition'     => 'تغذیه',
			'mental'        => 'سلامت روان',
			'warning'       => 'نشانه‌های هشدار',
			'pregnancy'     => 'بارداری',
		);
	}

	/* --------------------------------------------------------------------- */
	/* نقاط ورود عمومی: دعوت‌نامه و کالبک پرداخت                             */
	/* --------------------------------------------------------------------- */

	public function query_vars( array $vars ): array {
		$vars[] = 'mb_invite';
		$vars[] = 'mb_verify';
		$vars[] = 'mb_sw';
		$vars[] = 'mb_manifest';
		$vars[] = 'mb_ref';
		$vars[] = 'mb_csv';
		return $vars;
	}

	public function handle_public_endpoints(): void {
		// کد معرف از لینک اشتراکی: تا ۳۰ روز در کوکی می‌ماند تا هنگام ثبت‌نام خوانده شود.
		if ( isset( $_GET['mb_ref'] ) && ! is_user_logged_in() ) {
			$ref = MB_Referral::sanitize_code( (string) wp_unslash( $_GET['mb_ref'] ) );
			if ( '' !== $ref && ! headers_sent() ) {
				setcookie( 'mb_ref', $ref, time() + ( 30 * DAY_IN_SECONDS ), COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
			}
		}

		if ( isset( $_GET['mb_sw'] ) ) {
			MB_Assets::serve_service_worker();
			exit;
		}
		if ( isset( $_GET['mb_manifest'] ) ) {
			MB_Assets::serve_manifest();
			exit;
		}

		$invite = isset( $_GET['mb_invite'] ) ? sanitize_text_field( wp_unslash( $_GET['mb_invite'] ) ) : '';
		if ( '' !== $invite ) {
			MB_Privacy::render_invite_landing( $invite );
			exit;
		}

		$verify = isset( $_GET['mb_verify'] ) ? sanitize_text_field( wp_unslash( $_GET['mb_verify'] ) ) : '';
		if ( '' !== $verify ) {
			MB_Subscription::handle_gateway_callback( $verify, $_GET );
			exit;
		}
	}

	/* --------------------------------------------------------------------- */
	/* رندر اپ                                                               */
	/* --------------------------------------------------------------------- */

	public function render_app( $atts = array() ): string {
		MB_Assets::mark_needed();
		$atts = shortcode_atts( array( 'route' => '' ), (array) $atts, 'moonbanu_app' );

		$logged     = is_user_logged_in();
		$user_id    = $logged ? get_current_user_id() : 0;
		$is_partner = $logged && MB_Privacy::is_partner( $user_id );
		$onboarded  = $logged && MB_DB::is_onboarded( $user_id );

		$route = (string) $atts['route'];
		if ( '' === $route ) {
			if ( ! $logged ) {
				$route = 'login';
			} else {
				$route = $is_partner ? 'partner' : ( $onboarded ? 'home' : 'onboarding' );
			}
		}

		$html = MB_Api::render_route( $route, $user_id );

		ob_start();
		?>
		<div class="mb-app" id="mb-app" dir="rtl" lang="fa"
			data-brand-logo="<?php echo MB_Plugin::setting( 'brand_logo_url', '' ) ? '1' : '0'; ?>"
			data-theme="<?php echo $is_partner ? 'partner' : 'woman'; ?>"
			data-rest="<?php echo esc_url( rest_url( 'moonbanu/v1' ) ); ?>"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>"
			data-auth-nonce="<?php echo esc_attr( MB_Auth::nonce() ); ?>"
			data-logged="<?php echo $logged ? '1' : '0'; ?>"
			data-boot="<?php echo esc_attr( $route ); ?>"
			data-onboarded="<?php echo $onboarded ? '1' : '0'; ?>"
			style="<?php echo esc_attr( self::app_style() ); ?>">
			<div class="mb-bg" aria-hidden="true"></div>
			<div class="mb-view" id="mb-view"><?php echo $html; // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
			<div class="mb-toast" id="mb-toast" role="status" aria-live="polite"></div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/* --------------------------------------------------------------------- */
	/* برند                                                                  */
	/* --------------------------------------------------------------------- */

	/**
	 * مقدار برند با بازگشت به ثابت‌های پلاگین.
	 *
	 * @param string $key name|phone|email|credit|studio|dev_url
	 */
	public static function brand( string $key ): string {
		$map = array(
			'name'    => array( 'brand_name', 'ماه‌بانو' ),
			'phone'   => array( 'support_phone', MB_SUPPORT_PHONE ),
			'email'   => array( 'support_email', MB_SUPPORT_EMAIL ),
			'credit'  => array( 'dev_credit', MB_DEV_CREDIT ),
			'studio'  => array( 'dev_studio', MB_DEV_STUDIO ),
			'dev_url' => array( 'dev_url', MB_DEV_URL ),
		);
		if ( ! isset( $map[ $key ] ) ) {
			return '';
		}
		list( $option, $fallback ) = $map[ $key ];
		$value = trim( (string) self::setting( $option, $fallback ) );
		if ( '' === $value ) {
			$value = (string) $fallback;
		}
		return 'dev_url' === $key ? esc_url_raw( $value ) : $value;
	}

	/* --------------------------------------------------------------------- */
	/* ابزارهای عمومی                                                        */
	/* --------------------------------------------------------------------- */

	/** متن سلب مسئولیت (قابل تغییر در ادمین، با بازگشت به متن ثابت). */
	public static function disclaimer(): string {
		$text = trim( (string) self::setting( 'disclaimer', MB_DISCLAIMER ) );
		return '' !== $text ? $text : MB_DISCLAIMER;
	}

	/** متن‌های جایگزین‌شده توسط مدیر؛ هر خط با «متن اصلی => متن جدید». */
	public static function text_overrides(): array {
		$raw = (string) self::setting( 'text_overrides', '' );
		$out = array();
		foreach ( preg_split( '/\r\n|\r|\n/', $raw ) as $line ) {
			$parts = explode( '=>', $line, 2 );
			if ( 2 !== count( $parts ) ) {
				continue;
			}
			$from = trim( (string) $parts[0] );
			$to   = trim( (string) $parts[1] );
			if ( '' !== $from ) {
				$out[ $from ] = wp_kses_post( $to );
			}
		}
		return $out;
	}

	public static function apply_text_overrides( string $html ): string {
		$map = self::text_overrides();
		return empty( $map ) ? $html : strtr( $html, $map );
	}

	/** متغیرهای برند برای رنگ‌ها و تصویر پس‌زمینهٔ قابل مدیریت در پنل. */
	public static function app_style(): string {
		$defaults = MB_Admin::default_settings();
		$vars = array(
			'--bg0' => 'color_bg', '--bg1' => 'color_surface', '--txt' => 'color_text',
			'--muted' => 'color_muted', '--gold2' => 'color_accent', '--period' => 'color_period',
			'--fertile' => 'color_fertile', '--ovul' => 'color_ovulation', '--pms' => 'color_pms',
		);
		// رنگ متن فرعی از رنگ متن اصلی مشتق می‌شود تا کنتراست همیشه خوانا بماند.
		$style = '';
		foreach ( $vars as $css => $key ) {
			$value = sanitize_hex_color( (string) self::setting( $key, $defaults[ $key ] ?? '' ) );
			if ( $value ) {
				$style .= $css . ':' . $value . ';';
			}
		}
		$logo = esc_url_raw( (string) self::setting( 'brand_logo_url', '' ) );
		$logo = str_replace( array( '(', ')', '"', "'" ), '', $logo );
		if ( '' !== $logo ) {
			$style .= '--mb-logo-image:url(' . $logo . ');';
		}
		$image = esc_url_raw( (string) self::setting( 'background_image_url', '' ) );
		$image = str_replace( array( '(', ')', '"', "'" ), '', $image );
		if ( '' !== $image ) {
			$style .= '--mb-bg-image:url(' . $image . ');';
		}
		return $style;
	}

	public static function setting( string $key, $default = '' ) {
		$s = get_option( 'mb_settings', array() );
		if ( ! is_array( $s ) ) {
			$s = array();
		}
		return array_key_exists( $key, $s ) ? $s[ $key ] : $default;
	}

	public static function log_security( string $event, array $detail = array() ): void {
		MB_DB::add_security_log( $event, $detail );
	}
}

MB_Plugin::instance();
