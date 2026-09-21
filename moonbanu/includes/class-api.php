<?php
/**
 * REST API و کنترلر مسیرهای SPA.
 * namespace: moonbanu/v1 — همه مسیرها permission_callback دارند.
 *
 * @package moonbanu
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
final class MB_Api {
	const NS = 'moonbanu/v1';

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/* --------------------------------------------------------------------- */
	/* مجوزها                                                                */
	/* --------------------------------------------------------------------- */
	public static function can_use(): bool {
		return is_user_logged_in();
	}

	public static function can_woman(): bool {
		return is_user_logged_in() && ! MB_Privacy::is_partner( get_current_user_id() );
	}

	/**
	 * دسترسی مسیرهای «همراه».
	 *
	 * امنیت: توکن دعوت یک گذرواژهٔ دائمی نیست. اکنون فقط سشن ورود معتبر است؛
	 * پذیرش دعوت خودش کاربر را وارد می‌کند، پس جریان محصول تغییری نمی‌کند.
	 */
	public static function can_partner( WP_REST_Request $request ): bool {
		if ( is_user_logged_in() && MB_Privacy::is_partner( get_current_user_id() ) ) {
			return true;
		}
		if ( '' !== trim( (string) $request->get_param( 'token' ) ) ) {
			MB_Plugin::log_security( 'partner_token_rejected', array( 'route' => (string) $request->get_route() ) );
			MB_License::note_validation_error( 'partner_token' );
		}
		return false;
	}

	/** خطای استاندارد گیت Pro (۴۰۳ سمت سرور). */
	private static function pro_denied(): WP_Error {
		return new WP_Error( 'mb_pro_required', 'این بخش بخشی از نسخهٔ پیشرفته است.', array( 'status' => 403 ) );
	}

	/* --------------------------------------------------------------------- */
	/* ثبت مسیرها                                                            */
	/* --------------------------------------------------------------------- */
	public static function register_routes(): void {
		$woman   = array( __CLASS__, 'can_woman' );
		$any     = array( __CLASS__, 'can_use' );
		$partner = array( __CLASS__, 'can_partner' );
		register_rest_route( self::NS, '/view', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_view' ), 'permission_callback' => '__return_true', 'args' => array( 'route' => array( 'type' => 'string', 'required' => true ) ) ) );
		register_rest_route( self::NS, '/log', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_log' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/profile', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_profile' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/today', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_today' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/calendar', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_calendar' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/day', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_day' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/analysis', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_analysis' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/cycle-map', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_cycle_map' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/invite', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_invite_create' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/invite/revoke', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_invite_revoke' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/invite/status', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_invite_status' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/invite/accept', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_invite_accept' ), 'permission_callback' => '__return_true' ) );
		register_rest_route( self::NS, '/invite/toggle', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_invite_toggle' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/partner/home', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_partner_home' ), 'permission_callback' => $partner ) );
		register_rest_route( self::NS, '/partner/ack', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_partner_ack' ), 'permission_callback' => $partner ) );
		register_rest_route( self::NS, '/partner/snooze', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_partner_snooze' ), 'permission_callback' => $partner ) );
		register_rest_route( self::NS, '/partner/toggle', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_partner_toggle' ), 'permission_callback' => $partner ) );
		register_rest_route( self::NS, '/notifications', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_notifications' ), 'permission_callback' => $any ) );
		register_rest_route( self::NS, '/notifications/read', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_notifications_read' ), 'permission_callback' => $any ) );
		register_rest_route( self::NS, '/support/send', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_support_send' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/articles', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_articles' ), 'permission_callback' => $any ) );
		register_rest_route( self::NS, '/articles/save', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_article_save' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/question', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_question' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/checkout/create', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_checkout_create' ), 'permission_callback' => $any ) );
		register_rest_route( self::NS, '/checkout/verify', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_checkout_verify' ), 'permission_callback' => $any ) );
		register_rest_route( self::NS, '/subscription/status', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_subscription_status' ), 'permission_callback' => $any ) );
		register_rest_route( self::NS, '/checkout/coupon', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_coupon_preview' ), 'permission_callback' => $any ) );
		register_rest_route( self::NS, '/checkout/recover', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_checkout_recover' ), 'permission_callback' => $any ) );
		/* ---------------- نسخهٔ ٫۰ ---------------- */
		register_rest_route( self::NS, '/pregnancy/start', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_pregnancy_start' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/pregnancy/stop', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_pregnancy_stop' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/pregnancy/share', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_pregnancy_share' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/pregnancy/checklist', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_pregnancy_checklist' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/reminders/save', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_reminder_save' ), 'permission_callback' => $any ) );
		register_rest_route( self::NS, '/reminders/med', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_med_add' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/reminders/med/remove', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_med_remove' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/community', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_community' ), 'permission_callback' => $any ) );
		register_rest_route( self::NS, '/quiz/submit', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_quiz_submit' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/assistant', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_assistant' ), 'permission_callback' => $woman ) );
		/* ---------------- نسخهٔ ۳٫۱ (B1/B2 — مسیرهای گم‌شده) ---------------- */
		register_rest_route( self::NS, '/emergency/sos', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_emergency_sos' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/emergency/status', array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_emergency_status' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/health-profile/save', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_health_profile_save' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/cycles/editor/save', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_cycle_editor_save' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/screenings/submit', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_screening_submit' ), 'permission_callback' => $woman ) );
		register_rest_route( self::NS, '/privacy/(?P<key>[a-z_]+)', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_privacy_toggle' ), 'permission_callback' => $woman ) );
	}

	/* --------------------------------------------------------------------- */
	/* کنترلر مسیر (رندر سمت سرور همه صفحه‌ها)                                */
	/* --------------------------------------------------------------------- */
	public static function render_route( string $route, int $user_id ): string {
		$route = trim( strtolower( preg_replace( '#[^a-z0-9/\-]#i', '', $route ) ), '/' );
		$parts = explode( '/', $route );
		$base  = $parts[0] ?? 'home';
		$arg   = $parts[1] ?? '';
		if ( ! is_user_logged_in() || $user_id <= 0 ) {
			if ( 'about' === $base ) {
				return MB_UI::render_template( 'about', array() );
			}
			$guest = in_array( $base, array( 'login', 'register', 'forgot', 'reset', 'otp' ), true ) ? $base : 'login';
			return MB_UI::login_gate( $guest );
		}
		if ( in_array( $base, array( 'login', 'register', 'forgot', 'reset', 'otp' ), true ) ) {
			$base = MB_Privacy::is_partner( $user_id ) ? 'partner' : ( MB_DB::is_onboarded( $user_id ) ? 'home' : 'onboarding' );
			$arg  = '';
		}
		$is_partner       = MB_Privacy::is_partner( $user_id );
		$partner_routes   = array( 'partner', 'reminder', 'calm', 'messages' );
		$always_open      = array( 'onboarding', 'setup', 'support', 'about', 'account' );
		if ( ! $is_partner && ! MB_DB::is_onboarded( $user_id ) && ! in_array( $base, $always_open, true ) ) {
			$base = 'onboarding';
			$arg  = '1';
		}
		if ( $is_partner && ! in_array( $base, $partner_routes, true ) && ! in_array( $base, array( 'support', 'about', 'account', 'checkout', 'subscription-ok', 'subscription-failed' ), true ) ) {
			$base = 'partner';
		}
		if ( ! $is_partner && in_array( $base, array( 'partner', 'reminder', 'calm', 'messages' ), true ) ) {
			$base = 'connect';
		}
		$token = MB_License::issue_token( $user_id );
		switch ( $base ) {
			case 'splash':
				return MB_UI::render_template( 'woman/01-splash', self::data_common( $user_id, $token ) );
			case 'onboarding':
			case 'setup':
				$data            = self::data_common( $user_id, $token );
				$data['step']    = 'setup' === $base ? 4 : max( 1, min( 3, (int) $arg ) );
				$data['profile'] = MB_DB::get_profile( $user_id );
				return MB_UI::render_template( 'woman/02-onboarding', $data );
			case 'home':
				return MB_UI::render_template( 'woman/03-home', self::data_home( $user_id, $token ) );
			case 'calendar':
				return MB_UI::render_template( 'woman/04-calendar', self::data_calendar( $user_id, $arg, $token ) );
			case 'day':
				return MB_UI::render_template( 'woman/05-day', self::data_day( $user_id, $arg, $token ) );
			case 'log':
				return MB_UI::render_template( 'woman/06-log', self::data_log( $user_id, $arg, $token ) );
			case 'analysis':
				if ( ! MB_Subscription::can( $user_id, 'analysis' ) ) {
					return self::gate_screen( 'analysis', $user_id, $token );
				}
				return MB_UI::render_template( 'woman/07-analysis', self::data_analysis( $user_id, $token ) );
			case 'cycle':
				if ( ! MB_Subscription::can( $user_id, 'cycle_map' ) ) {
					return self::gate_screen( 'cycle', $user_id, $token );
				}
				return MB_UI::render_template( 'woman/08-cycle', self::data_cycle( $user_id, $token ) );
			case 'health-profile':
				return MB_UI::render_template( 'woman/10-health', self::data_health_profile( $user_id, $token ) );
			case 'cycle-editor':
				return MB_UI::render_template( 'woman/24-cycle-editor', self::data_cycle_editor( $user_id, $token ) );
			case 'screenings':
				return MB_UI::render_template( 'woman/22-screenings', self::data_screenings( $user_id, $token ) );
			case 'screening':
				return MB_UI::render_template( 'woman/22-screenings', self::data_screenings( $user_id, $token, $arg ) );
			case 'screening-result':
				$data = self::data_screening_result( $user_id, $arg, $token );
				return MB_UI::render_template( null === $data['result'] ? 'woman/22-screenings' : 'woman/23-screening-result', $data );
			case 'faq':
				return MB_UI::render_template( 'support', self::data_support( $user_id, '', $token ) );
			case 'health':
				return MB_UI::render_template( 'woman/09-health', self::data_health( $user_id, $arg, $token ) );
			case 'pregnancy':
				return MB_UI::render_template( 'woman/14-pregnancy', self::data_pregnancy( $user_id, $token ) );
			case 'ttc':
				if ( ! MB_Subscription::can( $user_id, 'ttc' ) ) {
					return self::gate_screen( 'ttc', $user_id, $token );
				}
				return MB_UI::render_template( 'woman/15-ttc', self::data_ttc( $user_id, $token ) );
			case 'report':
				if ( ! MB_Subscription::can( $user_id, 'report' ) ) {
					return self::gate_screen( 'report', $user_id, $token );
				}
				return MB_UI::render_template( 'woman/16-report', self::data_report( $user_id, $arg, $token ) );
			case 'assistant':
				if ( ! MB_Subscription::can( $user_id, 'assistant' ) ) {
					return self::gate_screen( 'assistant', $user_id, $token );
				}
				return MB_UI::render_template( 'woman/17-assistant', self::data_assistant( $user_id, $token ) );
			case 'community':
				return MB_UI::render_template( 'woman/18-community', self::data_community( $user_id, $arg, $token ) );
			case 'quizzes':
				return MB_UI::render_template( 'woman/19-quizzes', self::data_quizzes( $user_id, $token ) );
			case 'quiz':
				$data = self::data_quiz( $user_id, $arg, $token );
				return MB_UI::render_template( null === $data['quiz'] ? 'woman/19-quizzes' : 'woman/20-quiz', $data );
			case 'reminders':
				return MB_UI::render_template( 'woman/21-reminders', self::data_reminders( $user_id, $token ) );
			case 'connect':
			case 'me':
				return MB_UI::render_template( 'partner/10-connect', self::data_connect( $user_id, $token ) );
			case 'partner':
				return MB_UI::render_template( 'partner/11-partner', self::data_partner( $user_id, $token ) );
			case 'reminder':
				return MB_UI::render_template( 'partner/12-reminder', self::data_partner( $user_id, $token ) );
			case 'calm':
				return MB_UI::render_template( 'partner/13-calm', self::data_partner( $user_id, $token ) );
			case 'messages':
				$data             = self::data_partner( $user_id, $token );
				$data['messages'] = MB_DB::get_notifications( $user_id, 20 );
				return MB_UI::render_template( 'partner/12-reminder', $data );
			case 'support':
				$mb_ob = ob_get_level();
				try {
					return MB_UI::render_template( 'support', self::data_support( $user_id, $arg, $token ) );
				} catch ( \Throwable $mb_e ) {
					while ( ob_get_level() > $mb_ob ) {
						ob_end_clean();
					}
					error_log( 'MB support render failed: ' . $mb_e->getMessage() . ' @ ' . $mb_e->getFile() . ':' . $mb_e->getLine() );
					return self::support_fallback( $user_id, $token );
				}
			case 'about':
				return MB_UI::render_template( 'about', self::data_common( $user_id, $token ) );
			case 'account':
				return MB_UI::render_template(
					$is_partner ? 'partner/00-account' : 'account',
					self::data_account( $user_id, $token )
				);
			case 'checkout':
			case 'subscription-ok':
			case 'subscription-failed':
				$data           = self::data_common( $user_id, $token );
				$data['status'] = MB_Subscription::status_data( $user_id );
				$data['result'] = 'subscription-ok' === $base ? 'ok' : ( 'subscription-failed' === $base ? 'failed' : '' );
				return MB_UI::render_template( 'checkout', $data );
			default:
				return MB_UI::render_template( 'woman/03-home', self::data_home( $user_id, $token ) );
		}
	}

	private static function gate_screen( string $active, int $user_id, string $token ): string {
		$titles = array(
			'analysis'  => array(
				'تحلیل و الگوی شخصی',
				'میانگین چرخه، نوسان، نشانه‌های غالب و پیش‌بینی این چرخه با نسخهٔ پیشرفته فعال می‌شود.',
				array( 'میانگین و نوسان طول چرخه', 'نشانه‌های غالب هر فاز', 'پیش‌بینی سه چرخهٔ آینده' ),
				'analysis',
			),
			'cycle'     => array(
				'نقشهٔ کامل چرخه',
				'شش فاز چرخه با بازهٔ تاریخ شمسی و توضیح هورمونی، در نسخهٔ پیشرفته.',
				array( 'شش فاز با بازهٔ شمسی', 'توضیح هورمونی هر فاز', 'برنامهٔ پیشنهادی روزانه' ),
				'cycle',
			),
			'ttc'       => array(
				'تلاش برای بارداری',
				'ثبت دمای پایه و مخاط، نمودار دما و تأیید تخمک‌گذاری در نسخهٔ پیشرفته.',
				array( 'نمودار دمای پایهٔ بدن', 'تأیید تخمک‌گذاری با صعود دما', 'ثبت مخاط و پنجرهٔ باروری' ),
				'ttc',
			),
			'report'    => array(
				'گزارش پزشک',
				'یک گزارش مرتب و قابل چاپ از چرخه‌ها، ثبت‌ها و فراوانی نشانه‌ها با تاریخ شمسی.',
				array( 'جدول چرخه‌ها و ثبت‌های روزانه', 'فراوانی نشانه‌ها با درصد', 'چاپ و خروجی CSV' ),
				'report',
			),
			'assistant' => array(
				'دستیار ماه',
				'پاسخ‌های قاعده‌مند بر پایهٔ فاز فعلی و ثبت‌های خودت، با لینک مقالهٔ مرتبط.',
				array( 'پاسخ بر پایهٔ فاز و دادهٔ خودت', 'راهنمای درد، خلق، خواب و PMS', 'لینک مقالهٔ مرتبط' ),
				'assistant',
			),
		);
		list( $title, $text, $bullets, $sample ) = $titles[ $active ] ?? array( 'نسخهٔ پیشرفته', 'این بخش در نسخهٔ پیشرفته فعال است.', array(), '' );
		return '<div class="scr"><div class="pad">'
			. '<div class="hdr"><div><h1 class="h1">' . esc_html( $title ) . '</h1><p class="small">' . esc_html( MB_Jalali::format_fa( MB_Jalali::today(), 'full' ) ) . '</p></div></div>'
			. MB_UI::pro_gate( $title, $text, (array) $bullets, (string) $sample )
			. MB_UI::disclaimer()
			. '</div>' . MB_UI::tabbar( $active ) . '</div>';
	}

	/* --------------------------------------------------------------------- */
	/* آماده‌سازی داده صفحه‌ها                                                */
	/* --------------------------------------------------------------------- */
	private static function data_common( int $user_id, string $token ): array {
		$user = get_userdata( $user_id );
		return array(
			'user_id'        => $user_id,
			'name'           => $user ? ( $user->first_name ? $user->first_name : $user->display_name ) : '',
			'user_name'      => $user ? trim( (string) ( '' !== trim( (string) $user->first_name ) ? $user->first_name : $user->display_name ) ) : '',
			'token'          => $token,
			'today'          => MB_Jalali::today(),
			'today_fa'       => MB_Jalali::format_fa( MB_Jalali::today(), 'full' ),
			'unread'         => MB_DB::unread_count( $user_id ),
			'accuracy'       => MB_Cycle_Engine::accuracy( $user_id ),
			'onboarded'      => MB_DB::is_onboarded( $user_id ),
			'is_pro'         => MB_Subscription::is_pro( $user_id ),
			'notices'        => MB_DB::get_notifications( $user_id, 6 ),
			'support_unread' => MB_Support::unread_for_user( $user_id ),
		);
	}

	private static function data_home( int $user_id, string $token ): array {
		$data                = self::data_common( $user_id, $token );
		$snap                = MB_Cycle_Engine::snapshot( $user_id );
		$data['snap']        = $snap;
		$data['segments']    = MB_UI::cycle_segments( $user_id );
		$data['plan']        = MB_Cycle_Engine::daily_plan( $snap['phase'] );
		$invite              = MB_DB::get_active_invite( $user_id );
		$data['invite']      = $invite;
		$data['can_support'] = $invite && ! empty( $invite['accepted_by'] ) && ! empty( $invite['share_support'] ) && MB_Subscription::can( $user_id, 'partner' );
		$data['streak']      = MB_DB::streak( $user_id );
		$data['pregnancy']   = MB_Pregnancy::get( $user_id );
		$data['prediction']  = MB_Cycle_Engine::prediction_display( $user_id );
		$data['irregular']   = MB_Irregular::status( $user_id );
		$data['sleep_logged'] = MB_Cycle_Engine::sleep_is_logged( $user_id );
		$data['today']       = $snap;
		$data['cycle']       = MB_DB::get_profile( $user_id );
		$data['care']        = MB_Cycle_Engine::day_care( (string) $snap['phase'] );
		$data['sos']         = array(
			'enabled'  => MB_Health_Profile::alert_enabled( $user_id ),
			'cooldown' => MB_Emergency::cooldown_left( $user_id ),
		);
		return $data;
	}

	private static function data_calendar( int $user_id, string $ym, string $token ): array {
		$data = self::data_common( $user_id, $token );
		list( $jy, $jm ) = self::parse_ym( $ym );
		$len   = MB_Jalali::month_len( $jy, $jm );
		$first = MB_Jalali::j2g( $jy, $jm, 1 );
		$cells = array();
		for ( $d = 1; $d <= $len; $d++ ) {
			$date    = MB_Jalali::j2g( $jy, $jm, $d );
			$phase   = MB_Cycle_Engine::phase_of( $user_id, $date );
			$cells[] = array(
				'day'       => $d,
				'date'      => $date,
				'phase'     => $phase,
				'today'     => MB_Jalali::today() === $date,
				'predicted' => MB_Cycle_Engine::is_predicted( $user_id, $date ),
				'logged'    => false,
			);
		}
		$logs = MB_DB::get_logs_range( $user_id, $first, MB_Jalali::j2g( $jy, $jm, $len ) );
		foreach ( $cells as &$cell ) {
			$cell['logged'] = isset( $logs[ $cell['date'] ] );
		}
		unset( $cell );
		$data['jy']          = $jy;
		$data['jm']          = $jm;
		$data['title']       = MB_Jalali::months()[ $jm ] . ' ' . MB_Jalali::fa_num( $jy );
		$data['offset']      = MB_Jalali::day_of_week( $first );
		$data['cells']       = $cells;
		$data['snap']        = MB_Cycle_Engine::snapshot( $user_id );
		$data['prev']        = self::shift_month( $jy, $jm, -1 );
		$data['next_m']      = self::shift_month( $jy, $jm, 1 );
		$data['can_predict'] = MB_Subscription::can( $user_id, 'predict_3' );
		$data['predicted']   = $data['can_predict'] ? MB_Cycle_Engine::predicted_cycles( $user_id, 3 ) : array();
		return $data;
	}

	private static function data_day( int $user_id, string $arg, string $token ): array {
		$data               = self::data_common( $user_id, $token );
		$date               = ( '' === $arg || 'today' === $arg ) ? MB_Jalali::today() : MB_Jalali::sanitize_date( $arg );
		$snap               = MB_Cycle_Engine::snapshot( $user_id, $date );
		$data['snap']       = $snap;
		$data['date']       = $date;
		$data['log']        = MB_DB::get_log( $user_id, $date );
		$data['care']       = MB_Cycle_Engine::day_care( $snap['phase'] );
		$data['can_prob']   = MB_Subscription::can( $user_id, 'symptom_prob' );
		$data['probs']      = $data['can_prob'] && null !== $snap['day_index'] ? MB_Cycle_Engine::symptom_probability( $user_id, (int) $snap['day_index'] ) : array();
		$data['segments']   = MB_UI::cycle_segments( $user_id );
		$data['note_private'] = MB_DB::get_private_note( $user_id, $date );
		return $data;
	}

	private static function data_log( int $user_id, string $arg, string $token ): array {
		$data           = self::data_common( $user_id, $token );
		$date           = ( '' === $arg || 'today' === $arg ) ? MB_Jalali::today() : MB_Jalali::sanitize_date( $arg );
		$data['date']   = $date;
		$data['date_fa'] = MB_Jalali::format_fa( $date, 'full' );
		$data['log']    = MB_DB::get_log( $user_id, $date );
		$data['note']   = MB_DB::get_private_note( $user_id, $date );
		$data['streak'] = MB_DB::streak( $user_id );
		$data['snap']   = MB_Cycle_Engine::snapshot( $user_id, $date );
		$data['symptoms'] = MB_DB::symptom_map();
		$data['can_ttc']   = MB_Subscription::can( $user_id, 'ttc' );
		$data['mucus_map'] = MB_DB::mucus_options();
		$data['sex_map']   = MB_DB::sex_options();
		$data['meds_list'] = MB_Reminders::meds( $user_id );
		$data['pregnancy'] = MB_Pregnancy::get( $user_id );
		return $data;
	}

	private static function data_analysis( int $user_id, string $token ): array {
		$data              = self::data_common( $user_id, $token );
		$data['snap']      = MB_Cycle_Engine::snapshot( $user_id );
		$data['variation'] = MB_Cycle_Engine::variation( $user_id );
		$data['avg']       = MB_Cycle_Engine::avg_cycle( $user_id );
		$data['avg_period'] = MB_Cycle_Engine::avg_period_len( $user_id );
		$data['lengths']   = MB_Cycle_Engine::recent_lengths( $user_id, 6 );
		$data['length_rows'] = MB_Cycle_Engine::recent_length_rows( $user_id, 6 );
		$data['cycles']    = MB_DB::get_cycles( $user_id, 7 );
		$data['dominant']  = MB_Cycle_Engine::dominant_symptoms( $user_id, 4 );
		$data['pms_start'] = MB_Cycle_Engine::personal_pms_start( $user_id );
		$data['next3']     = MB_Cycle_Engine::predicted_cycles( $user_id, 3 );
		$data['insights']  = MB_Insights::bundle( $user_id );
		return $data;
	}

	private static function data_cycle( int $user_id, string $token ): array {
		$data              = self::data_common( $user_id, $token );
		$data['snap']      = MB_Cycle_Engine::snapshot( $user_id );
		$data['map']       = MB_Cycle_Engine::cycle_map( $user_id );
		$data['segments']  = MB_UI::cycle_segments( $user_id );
		$data['avg']       = MB_Cycle_Engine::avg_cycle( $user_id );
		$data['variation'] = MB_Cycle_Engine::variation( $user_id );
		return $data;
	}

	private static function data_health( int $user_id, string $arg, string $token ): array {
		$data              = self::data_common( $user_id, $token );
		$data['cats']      = MB_Plugin::categories();
		$data['featured']  = self::featured_article();
		$data['can_full']  = MB_Subscription::can( $user_id, 'health_full' );
		$data['can_ask']   = MB_Subscription::can( $user_id, 'ask_expert' );
		$data['questions'] = $data['can_ask'] ? MB_DB::get_questions( $user_id, 5 ) : array();
		$data['saved']     = (array) get_user_meta( $user_id, 'mb_saved_articles', true );
		$data['articles']  = self::query_articles( '' !== $arg ? $arg : '', '' );
		$data['warnings']  = array(
			'خونریزی بیش از ۷ روز یا نیاز به تعویض نوار بهداشتی در هر ۱ تا ۲ ساعت',
			'درد شدیدی که با مسکن معمول آرام نمی‌شود و مانع کار روزانه است',
			'قطع قاعدگی بیش از ۳ ماه بدون بارداری',
			'خونریزی بین دو قاعدگی یا پس از رابطه',
			'تب، ترشح بدبو یا درد لگنی پیوسته',
		);
		return $data;
	}

	private static function data_connect( int $user_id, string $token ): array {
		$data               = self::data_common( $user_id, $token );
		$invite             = MB_DB::get_active_invite( $user_id );
		$data['invite']     = $invite;
		$data['link']       = $invite ? add_query_arg( 'mb_invite', $invite['token'], home_url( '/' ) ) : '';
		$data['partner_id'] = MB_Privacy::partner_of( $user_id );
		$data['partner']    = $data['partner_id'] ? get_userdata( $data['partner_id'] ) : null;
		$data['can_partner'] = MB_Subscription::can( $user_id, 'partner' );
		$data['status']     = MB_Subscription::status_data( $user_id );
		$data['profile']    = MB_DB::get_profile( $user_id );
		return $data;
	}

	/** دادهٔ صفحهٔ پشتیبانی (فهرست یا یک گفت‌وگو) — با نگهبان: هیچ جزء گم‌شده نباید صفحه را ۵۰۰ کند. */
	private static function data_support( int $user_id, string $arg, string $token ): array {
		$data             = self::data_common( $user_id, $token );
		$data['tickets']  = array();
		$data['ticket']   = null;
		$data['messages'] = array();
		$data['faq_grouped'] = ( class_exists( 'MB_FAQ' ) && method_exists( 'MB_FAQ', 'grouped' ) ) ? (array) MB_FAQ::grouped( '' ) : array();
		$data['sla_text']    = defined( 'MB_SLA::TEXT' ) ? (string) MB_SLA::TEXT : 'زمان پاسخ معمولاً بین ۱۰ دقیقه تا ۲ ساعت است.';
		$ticket_id = (int) preg_replace( '/[^0-9]/', '', $arg );
		if ( $ticket_id > 0 ) {
			$ticket = MB_Support::get_ticket( $ticket_id );
			if ( $ticket && (int) $ticket['user_id'] === $user_id ) {
				MB_Support::mark_read_by_user( $user_id, $ticket_id );
				$data['ticket']   = MB_Support::public_row( $ticket );
				$data['messages'] = MB_Support::public_messages( $ticket_id );
				return $data;
			}
		}
		foreach ( MB_Support::user_tickets( $user_id ) as $row ) {
			$data['tickets'][] = MB_Support::public_row( $row );
		}
		return $data;
	}

	/** رندر اضطراری پشتیبانی: اگر قالب کرش کرد، صفحه باز می‌ماند و خطا لاگ می‌شود. */
	private static function support_fallback( int $user_id, string $token ): string {
		$out  = '<div class="scr"><div class="pad">';
		$out .= '<div class="hdr"><h1 class="h1">' . esc_html( 'پشتیبانی' ) . '</h1></div>';
		$out .= MB_UI::pnote( 'زمان پاسخ معمولاً بین ۱۰ دقیقه تا ۲ ساعت است.', 'info', 'info' );
		$out .= MB_UI::disclaimer();
		$out .= '</div>' . MB_UI::tabbar( 'account' ) . '</div>';
		return $out;
	}

	private static function data_account( int $user_id, string $token ): array {
		$data              = self::data_common( $user_id, $token );
		$user              = get_userdata( $user_id );
		$data['email']     = $user ? (string) $user->user_email : '';
		$data['mobile']    = (string) get_user_meta( $user_id, 'mb_mobile', true );
		$data['status']    = MB_Subscription::status_data( $user_id );
		$data['joined_fa'] = $user ? MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $user->user_registered ) ), 'long' ) : '';
		$data['price_fa']  = MB_Subscription::price_fa();
		$data['referral']  = MB_Referral::summary( $user_id );
		$data['pregnancy'] = MB_Pregnancy::get( $user_id );
		$data['user']      = array(
			'name'   => (string) ( $data['user_name'] ?? '' ),
			'email'  => (string) $data['email'],
			'mobile' => (string) $data['mobile'],
			'joined' => (string) $data['joined_fa'],
		);
		$data['role']         = MB_Privacy::is_partner( $user_id ) ? 'partner' : 'woman';
		$data['subscription'] = $data['status'];
		$data['health']       = MB_Health_Profile::get( $user_id, false );
		$defaults             = self::share_defaults( $user_id );
		$data['share_status'] = ! empty( $defaults['share_status'] );
		return $data;
	}

	private static function data_partner( int $user_id, string $token ): array {
		$data   = self::data_common( $user_id, $token );
		$access = MB_Privacy::partner_access( $user_id );
		if ( ! empty( $access['ok'] ) && ! MB_License::partner_heartbeat( $user_id ) ) {
			$access = array( 'ok' => false, 'reason' => 'sessions' );
		}
		$data['access']  = $access;
		$data['payload'] = array();
		if ( ! empty( $access['ok'] ) ) {
			$data['payload'] = MB_Privacy::partner_payload( (int) $access['wife_id'], (array) $access['invite'] );
		}
		$data['tasks'] = MB_Privacy::calm_tasks();
		$data['state'] = MB_Privacy::partner_state( $user_id );
		$payload               = (array) $data['payload'];
		$data['snap']          = isset( $payload['snapshot'] ) ? (array) $payload['snapshot'] : array();
		$data['today_status']  = isset( $payload['today_status'] ) && is_array( $payload['today_status'] ) ? $payload['today_status'] : array();
		$data['share_status']  = ! empty( $data['today_status'] );
		$data['ack_history']   = isset( $payload['ack_history'] ) ? (array) $payload['ack_history'] : array();
		$data['messages']      = array();
		return $data;
	}

	/** N3 — پروفایل سلامت. کد ملی و یادداشت پزشکی هرگز به قالب نمی‌روند. */
	private static function data_health_profile( int $user_id, string $token ): array {
		$data                  = self::data_common( $user_id, $token );
		$profile               = MB_Health_Profile::get( $user_id, false );
		$data['health']        = $profile;
		$data['blood_types']   = MB_Health_Profile::blood_types();
		$data['bmi_table']     = MB_Health_Profile::bmi_table();
		$mb_hp_full            = (array) MB_Health_Profile::get( $user_id );
		$data['has_nid']       = '' !== (string) ( $mb_hp_full['national_id'] ?? '' );
		$data['has_note']      = '' !== (string) ( $mb_hp_full['medical_note'] ?? '' );
		$data['emergency_log'] = MB_Emergency::recent( $user_id, 5 );
		$data['cooldown']      = MB_Emergency::cooldown_left( $user_id );
		return $data;
	}

	/** N6 — ویرایشگر چرخه (B2). */
	private static function data_cycle_editor( int $user_id, string $token ): array {
		$data    = self::data_common( $user_id, $token );
		$profile = MB_DB::get_profile( $user_id );
		$last    = (string) ( $profile['last_period_start'] ?? '' );
		$data['cycle'] = array(
			'length'        => (int) ( $profile['cycle_len'] ?? 28 ),
			'period_length' => (int) ( $profile['period_len'] ?? 5 ),
			'is_irregular'  => MB_Irregular::manual( $user_id ) || MB_Irregular::is_irregular( $user_id ),
		);
		$data['last_period_start']  = $last;
		$data['last_period_jalali'] = '' !== $last ? MB_Jalali::to_jalali_string( $last ) : '';
		$data['last_period_fa']     = '' !== $last ? MB_Jalali::format_fa( $last, 'long' ) : '';
		$data['starts_history']     = MB_DB::get_cycles( $user_id, 8 );
		$data['irregular']          = MB_Irregular::status( $user_id );
		return $data;
	}

	private static function data_screenings( int $user_id, string $token, string $slug = '' ): array {
		$data               = self::data_common( $user_id, $token );
		$data['screenings'] = MB_Screening::listing( $user_id );
		$data['open']       = '' !== $slug ? MB_Screening::get( sanitize_key( $slug ) ) : null;
		$data['note']       = MB_Screening::NOTE;
		return $data;
	}

	private static function data_screening_result( int $user_id, string $arg, string $token ): array {
		$data              = self::data_common( $user_id, $token );
		$data['result']    = null;
		$data['band']      = 'none';
		$data['score']     = 0;
		$data['max']       = 0;
		$data['label']     = '';
		$data['message']   = '';
		$data['care_text'] = '';
		$data['expert_link'] = '';
		$data['related_article'] = array();
		$data['note']      = MB_Screening::NOTE;
		$row = MB_Screening::last_result( $user_id, sanitize_key( $arg ) );
		if ( ! $row ) {
			$data['screenings'] = MB_Screening::listing( $user_id );
			return $data;
		}
		$data['result']    = $row;
		$data['band']      = (string) ( $row['band'] ?? 'none' );
		$data['score']     = (int) ( $row['score'] ?? 0 );
		$data['max']       = (int) ( $row['max'] ?? 0 );
		$data['label']     = (string) ( $row['label'] ?? '' );
		$data['message']   = (string) ( $row['advice'] ?? '' );
		$data['care_text'] = (string) ( $row['refer'] ?? '' );
		$data['detail']    = (string) ( $row['detail'] ?? '' );
		$data['flag']      = (string) ( $row['flag'] ?? '' );
		$data['percent']   = (int) ( $row['percent'] ?? 0 );
		$data['source']    = (string) ( $row['source'] ?? '' );
		$slug = (string) ( $row['slug'] ?? '' );
		if ( '' !== $slug ) {
			$article = MB_Screening::related_article( $slug );
			$data['related_article'] = is_array( $article ) ? $article : array();
		}
		return $data;
	}

	/* --------------------------------------------------------------------- */
	/* اندپوینت‌های نسخهٔ ۳٫۱                                                 */
	/* --------------------------------------------------------------------- */
	public static function ep_emergency_sos( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_License::rate_limit( 'sos_' . $uid, 5, 600 ) ) {
			return new WP_Error( 'mb_rate', 'تلاش‌های زیاد. کمی بعد دوباره امتحان کن.', array( 'status' => 429 ) );
		}
		if ( ! MB_Health_Profile::alert_enabled( $uid ) ) {
			return new WP_REST_Response(
				array(
					'ok'       => false,
					'sent'     => false,
					'setup'    => true,
					'route'    => 'health-profile',
					'message'  => 'برای ارسال هشدار، ابتدا در «پروفایل سلامت» هشدار اضطراری را روشن کن و شمارهٔ تماس اضطراری را وارد کن.',
					'cooldown' => 0,
				),
				200
			);
		}
		$result             = MB_Emergency::sos( $uid );
		$result['setup']    = false;
		$result['cooldown'] = MB_Emergency::cooldown_left( $uid );
		return new WP_REST_Response( $result, 200 );
	}

	public static function ep_emergency_status( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		return new WP_REST_Response(
			array(
				'ok'       => true,
				'enabled'  => MB_Health_Profile::alert_enabled( $uid ),
				'cooldown' => MB_Emergency::cooldown_left( $uid ),
				'recent'   => MB_Emergency::recent( $uid, 5 ),
			),
			200
		);
	}

	public static function ep_health_profile_save( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_License::rate_limit( 'hp_' . $uid, 30, 600 ) ) {
			return new WP_Error( 'mb_rate', 'تلاش‌های زیاد. کمی بعد دوباره امتحان کن.', array( 'status' => 429 ) );
		}
		$payload = array();
		foreach ( array( 'height_cm', 'weight_kg', 'waist_cm', 'blood_type', 'emergency_name', 'emergency_phone', 'medical_note', 'emergency_alert' ) as $key ) {
			if ( null !== $r->get_param( $key ) ) {
				$payload[ $key ] = $r->get_param( $key );
			}
		}
		$nid = $r->get_param( 'national_id' );
		if ( null !== $nid && '***' !== trim( (string) $nid ) ) {
			$payload['national_id'] = (string) $nid;
		}
		$result = MB_Health_Profile::save( $uid, $payload );
		if ( empty( $result['ok'] ) ) {
			return new WP_Error( 'mb_health', (string) $result['error'], array( 'status' => 400 ) );
		}
		return new WP_REST_Response(
			array(
				'ok'        => true,
				'message'   => 'پروفایل سلامت ذخیره شد',
				'bmi'       => $result['bmi'],
				'bmi_fa'    => null === $result['bmi'] ? '' : MB_Jalali::fa_num( number_format_i18n( (float) $result['bmi'], 1 ) ),
				'bmi_label' => (string) $result['bmi_label'],
				'alert_on'  => MB_Health_Profile::alert_enabled( $uid ),
			),
			200
		);
	}

	public static function ep_cycle_editor_save( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_License::rate_limit( 'cycedit_' . $uid, 30, 600 ) ) {
			return new WP_Error( 'mb_rate', 'تلاش‌های زیاد. کمی بعد دوباره امتحان کن.', array( 'status' => 429 ) );
		}
		$raw  = trim( (string) $r->get_param( 'last_period_jalali' ) );
		if ( '' === $raw ) {
			$raw = trim( (string) $r->get_param( 'last_period_start' ) );
		}
		$date = '';
		if ( '' !== $raw ) {
			$parsed = MB_Jalali::parse_jalali( $raw );
			if ( null === $parsed ) {
				$parsed = MB_Jalali::sanitize_date( $raw );
				if ( '' === (string) $parsed || $raw !== (string) $parsed ) {
					MB_License::note_validation_error( 'cycle_editor' );
					return new WP_Error( 'mb_date', 'تاریخ شمسی معتبر نیست. نمونه: ۱۴۴/۰۷/۰۵', array( 'status' => 400 ) );
				}
			}
			$date = (string) $parsed;
			if ( $date > MB_Jalali::today() ) {
				return new WP_Error( 'mb_date', 'تاریخ آغاز قاعدگی نمی‌تواند در آینده باشد.', array( 'status' => 400 ) );
			}
		}
		$profile = array( 'onboarded' => 1 );
		$cyc     = $r->get_param( 'cycle_length' );
		$per     = $r->get_param( 'period_length' );
		if ( null !== $cyc && '' !== (string) $cyc ) {
			$profile['cycle_len'] = (int) MB_Jalali::en_num( (string) $cyc );
		}
		if ( null !== $per && '' !== (string) $per ) {
			$profile['period_len'] = (int) MB_Jalali::en_num( (string) $per );
		}
		if ( '' !== $date ) {
			$profile['last_period_start'] = $date;
		}
		MB_DB::save_profile( $uid, $profile );
		if ( '' !== $date ) {
			MB_DB::add_cycle( $uid, $date );
		}
		MB_Irregular::set_manual( $uid, (bool) $r->get_param( 'is_irregular' ) );
		delete_transient( 'mb_stats_' . $uid );
		MB_Plugin::flush_pro_cache();
		$snap = MB_Cycle_Engine::snapshot( $uid );
		return new WP_REST_Response(
			array(
				'ok'          => true,
				'message'     => 'چرخه ذخیره و بازمحاسبه شد',
				'redirect'    => 'home',
				'day_index'   => $snap['day_index'],
				'phase_label' => (string) $snap['phase_label'],
				'next_period' => $snap['next_period'] ? MB_Jalali::format_fa( (string) $snap['next_period'], 'long' ) : '',
				'accuracy'    => (int) $snap['accuracy'],
			),
			200
		);
	}

	public static function ep_screening_submit( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_License::rate_limit( 'scr_' . $uid, 20, 600 ) ) {
			return new WP_Error( 'mb_rate', 'تلاش‌های زیاد. کمی بعد دوباره امتحان کن.', array( 'status' => 429 ) );
		}
		$slug = sanitize_key( (string) $r->get_param( 'tool' ) );
		if ( '' === $slug || null === MB_Screening::get( $slug ) ) {
			return new WP_Error( 'mb_tool', 'ابزار غربالگری معتبر نیست.', array( 'status' => 400 ) );
		}
		$answers = (array) $r->get_param( 'answers' );
		$result  = MB_Screening::score( $slug, $answers );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		$saved_id           = MB_Screening::save_result( $uid, (array) $result );
		$out                = (array) $result;
		$out['ok']          = true;
		$out['message']     = 'پاسخ‌هایت ثبت شد';
		$out['note']        = MB_Screening::NOTE;
		$out['result_id']   = $saved_id;
		$out['slug']        = $slug;
		unset( $out['answers'] );
		return new WP_REST_Response( $out, 200 );
	}

	public static function ep_privacy_toggle( WP_REST_Request $r ) {
		$key = (string) $r->get_param( 'key' );
		if ( ! in_array( $key, array( 'share_pms', 'share_period', 'share_support', 'share_fertility', 'share_status' ), true ) ) {
			return new WP_Error( 'mb_key', 'کلید نامعتبر.', array( 'status' => 400 ) );
		}
		$r->set_param( 'value', (bool) $r->get_param( $key ) );
		return self::ep_invite_toggle( $r );
	}

	/* --------------------------------------------------------------------- */
	/* مقالات                                                                */
	/* --------------------------------------------------------------------- */
	public static function query_articles( string $cat = '', string $search = '', int $limit = 12 ): array {
		$args = array(
			'post_type'      => 'mb_article',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'no_found_rows'  => true,
		);
		if ( '' !== $cat ) {
			$args['tax_query'] = array( array( 'taxonomy' => 'mb_cat', 'field' => 'slug', 'terms' => sanitize_key( $cat ) ) );
		}
		if ( '' !== $search ) {
			$args['s'] = sanitize_text_field( $search );
		}
		$q   = new WP_Query( $args );
		$out = array();
		foreach ( $q->posts as $post ) {
			$out[] = self::article_row( $post );
		}
		return $out;
	}

	private static function article_row( WP_Post $post ): array {
		return array(
			'id'      => (int) $post->ID,
			'title'   => get_the_title( $post ),
			'excerpt' => wp_strip_all_tags( get_the_excerpt( $post ) ),
			'minutes' => (int) get_post_meta( $post->ID, 'read_minutes', true ),
			'content' => apply_filters( 'the_content', $post->post_content ),
		);
	}

	private static function featured_article(): ?array {
		$q = new WP_Query(
			array(
				'post_type'      => 'mb_article',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_key'       => 'featured_week',
				'orderby'        => 'meta_value_num',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
		if ( empty( $q->posts ) ) {
			return null;
		}
		return self::article_row( $q->posts[0] );
	}

	/* --------------------------------------------------------------------- */
	/* اندپوینت‌ها                                                           */
	/* --------------------------------------------------------------------- */
	public static function ep_view( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		return new WP_REST_Response(
			array(
				'html'   => self::render_route( (string) $r->get_param( 'route' ), $uid ),
				'logged' => $uid > 0 ? 1 : 0,
				'unread' => $uid > 0 ? MB_DB::unread_count( $uid ) : 0,
			),
			200
		);
	}

	public static function ep_coupon_preview( WP_REST_Request $r ) {
		$uid  = get_current_user_id();
		if ( ! MB_License::rate_limit( 'coupon_' . $uid, 30, 600 ) ) {
			return new WP_Error( 'mb_rate', 'تلاش‌های زیاد. کمی بعد امتحان کن.', array( 'status' => 429 ) );
		}
		$term = absint( $r->get_param( 'plan' ) );
		$plan = MB_Subscription::plan_by_months( $term > 0 ? $term : 1 );
		if ( ! $plan ) {
			return new WP_Error( 'mb_plan', 'پلن معتبر نیست.', array( 'status' => 400 ) );
		}
		$priced = MB_Subscription::apply_coupon( (int) $plan['price'], (string) $r->get_param( 'coupon' ), $uid );
		if ( '' !== $priced['error'] ) {
			return new WP_Error( 'mb_coupon', $priced['error'], array( 'status' => 400 ) );
		}
		return new WP_REST_Response(
			array(
				'ok'        => true,
				'base'      => (int) $plan['price'],
				'base_fa'   => MB_Jalali::fa_num( number_format_i18n( (int) $plan['price'] ) ),
				'amount'    => (int) $priced['amount'],
				'amount_fa' => MB_Jalali::fa_num( number_format_i18n( (int) $priced['amount'] ) ),
				'off_fa'    => MB_Jalali::fa_num( number_format_i18n( (int) $priced['off'] ) ),
				'percent'   => (int) $priced['percent'],
				'message'   => $priced['coupon'] ? 'کد تخفیف اعمال شد' : 'کد تخفیفی وارد نشده',
			),
			200
		);
	}

	public static function ep_log( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_License::rate_limit( 'log_' . $uid, 60, 60 ) ) {
			return new WP_Error( 'mb_rate', 'تعداد درخواست‌ها زیاد است.', array( 'status' => 429 ) );
		}
		$id = MB_DB::save_log(
			$uid,
			array(
				'log_date'     => (string) $r->get_param( 'date' ),
				'mood'         => $r->get_param( 'mood' ),
				'bleeding'     => (string) $r->get_param( 'bleeding' ),
				'pain'         => (int) $r->get_param( 'pain' ),
				'symptoms'     => (array) $r->get_param( 'symptoms' ),
				'note_private' => (string) $r->get_param( 'note' ),
				'weight_kg'    => $r->get_param( 'weight_kg' ),
				'sleep_h'      => $r->get_param( 'sleep_h' ),
				'water_cups'   => $r->get_param( 'water_cups' ),
				'exercise_min' => $r->get_param( 'exercise_min' ),
				'meds'         => (array) $r->get_param( 'meds' ),
				'bbt'          => MB_Subscription::can( $uid, 'ttc' ) ? $r->get_param( 'bbt' ) : null,
				'mucus'        => MB_Subscription::can( $uid, 'ttc' ) ? $r->get_param( 'mucus' ) : 'none',
				'sex'          => MB_Subscription::can( $uid, 'ttc' ) ? $r->get_param( 'sex' ) : 'none',
			)
		);
		$emergency = MB_Emergency::evaluate_log(
			$uid,
			(string) $r->get_param( 'date' ),
			(string) $r->get_param( 'bleeding' ),
			(int) $r->get_param( 'pain' )
		);
		$ovulation = null;
		if ( MB_Subscription::can( $uid, 'ttc' ) && null !== $r->get_param( 'bbt' ) ) {
			$analysis  = MB_TTC::analyze( $uid, 45 );
			$ovulation = $analysis['last_rise'];
		}
		return new WP_REST_Response(
			array(
				'ok'        => true,
				'id'        => $id,
				'message'   => 'در دفترچهٔ سلامت ذخیره شد',
				'streak'    => MB_DB::streak( $uid ),
				'ovulation' => $ovulation ? MB_Jalali::format_fa( (string) $ovulation, 'long' ) : null,
				'emergency' => isset( $emergency['message'] ) ? (string) $emergency['message'] : '',
			),
			200
		);
	}

	public static function ep_profile( WP_REST_Request $r ) {
		$uid    = get_current_user_id();
		$jalali = (string) $r->get_param( 'last_period_jalali' );
		$date   = '' !== $jalali ? MB_Jalali::parse_jalali( $jalali ) : null;
		if ( '' !== $jalali && null === $date ) {
			MB_License::note_validation_error( 'profile' );
			return new WP_Error( 'mb_date', 'تاریخ شمسی معتبر نیست.', array( 'status' => 400 ) );
		}
		$data = array( 'onboarded' => 1 );
		if ( null !== $r->get_param( 'cycle_len' ) && '' !== (string) $r->get_param( 'cycle_len' ) ) {
			$data['cycle_len'] = (int) MB_Jalali::en_num( (string) $r->get_param( 'cycle_len' ) );
		}
		if ( null !== $r->get_param( 'period_len' ) && '' !== (string) $r->get_param( 'period_len' ) ) {
			$data['period_len'] = (int) MB_Jalali::en_num( (string) $r->get_param( 'period_len' ) );
		}
		if ( $date ) {
			$data['last_period_start'] = $date;
		}
		MB_DB::save_profile( $uid, $data );
		if ( $date ) {
			MB_DB::add_cycle( $uid, $date );
		}
		$mobile = (string) $r->get_param( 'mobile' );
		if ( '' !== $mobile ) {
			update_user_meta( $uid, 'mb_mobile', preg_replace( '/[^0-9]/', '', MB_Jalali::en_num( $mobile ) ) );
		}
		delete_transient( 'mb_stats_' . $uid );
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'تنظیمات ذخیره شد' ), 200 );
	}

	public static function ep_today( WP_REST_Request $r ) {
		return new WP_REST_Response( MB_Cycle_Engine::snapshot( get_current_user_id() ), 200 );
	}

	public static function ep_calendar( WP_REST_Request $r ) {
		$uid  = get_current_user_id();
		$data = self::data_calendar( $uid, (string) $r->get_param( 'm' ), MB_License::issue_token( $uid ) );
		unset( $data['is_pro'], $data['token'] );
		return new WP_REST_Response( $data, 200 );
	}

	public static function ep_day( WP_REST_Request $r ) {
		$uid  = get_current_user_id();
		$data = self::data_day( $uid, (string) $r->get_param( 'd' ), MB_License::issue_token( $uid ) );
		$data['note'] = MB_DB::get_private_note( $uid, (string) $data['date'] );
		if ( is_array( $data['log'] ) ) {
			unset( $data['log']['note_private'] );
		}
		unset( $data['is_pro'], $data['token'] );
		return new WP_REST_Response( $data, 200 );
	}

	public static function ep_analysis( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_Subscription::can( $uid, 'analysis' ) ) {
			return self::pro_denied();
		}
		$data = self::data_analysis( $uid, MB_License::issue_token( $uid ) );
		unset( $data['is_pro'], $data['token'] );
		return new WP_REST_Response( $data, 200 );
	}

	public static function ep_cycle_map( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_Subscription::can( $uid, 'cycle_map' ) ) {
			return self::pro_denied();
		}
		return new WP_REST_Response( array( 'map' => MB_Cycle_Engine::cycle_map( $uid ) ), 200 );
	}

	public static function ep_invite_create( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_Subscription::can( $uid, 'partner' ) ) {
			return self::pro_denied();
		}
		if ( ! MB_License::rate_limit( 'invite_' . $uid, 20, 60 ) ) {
			return new WP_Error( 'mb_rate', 'تعداد درخواست‌ها زیاد است. کمی بعد تلاش کن.', array( 'status' => 429 ) );
		}
		$channel = 'sms' === (string) $r->get_param( 'channel' ) ? 'sms' : 'email';
		if ( 'sms' === $channel && ! MB_Subscription::can( $uid, 'sms' ) ) {
			return self::pro_denied();
		}
		$target = (string) $r->get_param( 'target' );
		if ( 'email' === $channel && '' !== $target && ! is_email( $target ) ) {
			return new WP_Error( 'mb_email', 'ایمیل معتبر نیست.', array( 'status' => 400 ) );
		}
		$current  = MB_DB::get_active_invite( $uid );
		$defaults = self::share_defaults( $uid );
		$shares   = array();
		foreach ( array( 'share_pms', 'share_period', 'share_support', 'share_fertility', 'share_status' ) as $mb_key ) {
			if ( null !== $r->get_param( $mb_key ) ) {
				$shares[ $mb_key ] = (int) (bool) $r->get_param( $mb_key );
			} elseif ( $current ) {
				$shares[ $mb_key ] = (int) $current[ $mb_key ];
			} else {
				$shares[ $mb_key ] = (int) $defaults[ $mb_key ];
			}
		}
		MB_DB::revoke_pending_invites( $uid );
		$invite = MB_DB::create_invite( $uid, $channel, $target, $shares );
		update_user_meta( $uid, 'mb_share_defaults', $shares );
		$sent = null;
		if ( '' !== $target ) {
			$sent = MB_Notify::send_invite( $invite, $channel, $target );
		}
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'link'    => add_query_arg( 'mb_invite', $invite['token'], home_url( '/' ) ),
				'sent'    => is_wp_error( $sent ) ? false : (bool) $sent,
				'error'   => is_wp_error( $sent ) ? $sent->get_error_message() : '',
				'message' => is_wp_error( $sent ) ? $sent->get_error_message() : 'دعوت‌نامه ساخته شد',
			),
			200
		);
	}

	public static function ep_invite_revoke( WP_REST_Request $r ) {
		MB_Privacy::revoke_all( get_current_user_id() );
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'دسترسی همسر لغو شد' ), 200 );
	}

	public static function ep_invite_status( WP_REST_Request $r ) {
		$uid    = get_current_user_id();
		$invite = MB_DB::get_active_invite( $uid );
		if ( ! $invite ) {
			return new WP_REST_Response( array( 'active' => false ), 200 );
		}
		return new WP_REST_Response(
			array(
				'active'     => true,
				'accepted'   => ! empty( $invite['accepted_by'] ),
				'expires_fa' => MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $invite['expires_at'] ) ), 'long' ),
				'shares'     => array(
					'share_pms'       => (int) $invite['share_pms'],
					'share_period'    => (int) $invite['share_period'],
					'share_support'   => (int) $invite['share_support'],
					'share_fertility' => (int) $invite['share_fertility'],
					'share_status'    => (int) $invite['share_status'],
				),
			),
			200
		);
	}

	public static function ep_invite_toggle( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		$key = (string) $r->get_param( 'key' );
		if ( ! in_array( $key, array( 'share_pms', 'share_period', 'share_support', 'share_fertility', 'share_status' ), true ) ) {
			return new WP_Error( 'mb_key', 'کلید نامعتبر.', array( 'status' => 400 ) );
		}
		$value    = (int) (bool) $r->get_param( 'value' );
		$defaults = self::share_defaults( $uid );
		$defaults[ $key ] = $value;
		update_user_meta( $uid, 'mb_share_defaults', $defaults );
		$invite = MB_DB::get_active_invite( $uid );
		if ( $invite ) {
			MB_DB::update_invite( (int) $invite['id'], array( $key => $value ) );
		}
		return new WP_REST_Response( array( 'ok' => true, 'key' => $key, 'value' => $value, 'message' => 'تنظیم اشتراک‌گذاری ذخیره شد' ), 200 );
	}

	public static function ep_invite_accept( WP_REST_Request $r ) {
		$token = (string) $r->get_param( 'token' );
		if ( ! MB_License::rate_limit( 'accept', 20, 900 ) ) {
			return new WP_Error( 'mb_rate', 'تلاش‌های بیش از حد.', array( 'status' => 429 ) );
		}
		$res = MB_Privacy::accept_invite(
			$token,
			array(
				'name'     => (string) $r->get_param( 'name' ),
				'email'    => (string) $r->get_param( 'email' ),
				'password' => (string) $r->get_param( 'password' ),
			)
		);
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'mb_invite', $res->get_error_message(), array( 'status' => 400 ) );
		}
		return new WP_REST_Response( array( 'ok' => true, 'redirect' => MB_UI::app_url( '#/partner' ) ), 200 );
	}

	/* ----------------------------- همسر ---------------------------------- */
	private static function partner_id_from( WP_REST_Request $r ): int {
		if ( is_user_logged_in() && MB_Privacy::is_partner( get_current_user_id() ) ) {
			return get_current_user_id();
		}
		return 0;
	}

	private static function partner_gate( WP_REST_Request $r ) {
		$pid = self::partner_id_from( $r );
		if ( ! $pid ) {
			return new WP_Error( 'mb_partner', 'دسترسی معتبر نیست.', array( 'status' => 403 ) );
		}
		$access = MB_Privacy::partner_access( $pid );
		if ( empty( $access['ok'] ) ) {
			return new WP_Error(
				'mb_partner',
				'no_pro' === ( $access['reason'] ?? '' ) ? 'اشتراک همراهی فعال نیست.' : 'دسترسی لغو شده است.',
				array( 'status' => 403 )
			);
		}
		return $pid;
	}

	public static function ep_partner_home( WP_REST_Request $r ) {
		$pid = self::partner_id_from( $r );
		if ( ! $pid ) {
			return new WP_Error( 'mb_partner', 'دسترسی معتبر نیست.', array( 'status' => 403 ) );
		}
		if ( ! MB_License::partner_heartbeat( $pid ) ) {
			return new WP_Error( 'mb_sessions', 'این حساب هم‌زمان روی دستگاه‌های زیادی باز است.', array( 'status' => 429 ) );
		}
		$access = MB_Privacy::partner_access( $pid );
		if ( empty( $access['ok'] ) ) {
			return new WP_Error( 'mb_partner', 'no_pro' === ( $access['reason'] ?? '' ) ? 'اشتراک همراهی فعال نیست.' : 'دسترسی لغو شده است.', array( 'status' => 403 ) );
		}
		return new WP_REST_Response( MB_Privacy::partner_payload( (int) $access['wife_id'], (array) $access['invite'] ), 200 );
	}

	public static function ep_partner_ack( WP_REST_Request $r ) {
		$pid = self::partner_gate( $r );
		if ( is_wp_error( $pid ) ) {
			return $pid;
		}
		$state        = MB_Privacy::partner_state( $pid );
		$state['ack'] = 1;
		MB_Privacy::save_partner_state( $pid, $state );
		$wife = MB_Privacy::wife_of( $pid );
		if ( $wife ) {
			MB_DB::add_notification( $wife, 'partner_ack', array( 'title' => 'همسرت پیام حمایت را دید', 'body' => 'گفت حواسش هست.' ) );
		}
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'ثبت شد' ), 200 );
	}

	public static function ep_partner_snooze( WP_REST_Request $r ) {
		$pid = self::partner_gate( $r );
		if ( is_wp_error( $pid ) ) {
			return $pid;
		}
		$state                 = MB_Privacy::partner_state( $pid );
		$state['snooze_until'] = MB_Jalali::add_days( MB_Jalali::today(), 1 );
		MB_Privacy::save_partner_state( $pid, $state );
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'فردا یادآوری می‌شود' ), 200 );
	}

	public static function ep_partner_toggle( WP_REST_Request $r ) {
		$pid = self::partner_gate( $r );
		if ( is_wp_error( $pid ) ) {
			return $pid;
		}
		$key = sanitize_key( (string) $r->get_param( 'key' ) );
		if ( ! array_key_exists( $key, MB_Privacy::calm_tasks() ) ) {
			return new WP_Error( 'mb_key', 'کلید نامعتبر.', array( 'status' => 400 ) );
		}
		$state = MB_Privacy::partner_state( $pid );
		$done  = ! empty( $state['checklist'][ $key ] );
		$state['checklist'][ $key ] = $done ? 0 : 1;
		MB_Privacy::save_partner_state( $pid, $state );
		$count = count( array_filter( (array) $state['checklist'] ) );
		return new WP_REST_Response( array( 'ok' => true, 'key' => $key, 'value' => $done ? 0 : 1, 'done' => $count, 'total' => count( MB_Privacy::calm_tasks() ) ), 200 );
	}

	/* --------------------------- اعلان‌ها -------------------------------- */
	public static function ep_notifications( WP_REST_Request $r ) {
		$uid  = get_current_user_id();
		$rows = MB_DB::get_notifications( $uid, 25 );
		$out  = array();
		foreach ( $rows as $row ) {
			$out[] = array(
				'id'     => (int) $row['id'],
				'type'   => $row['type'],
				'title'  => (string) ( $row['payload']['title'] ?? self::notification_title( (string) $row['type'] ) ),
				'body'   => (string) ( $row['payload']['body'] ?? '' ),
				'read'   => (int) $row['is_read'],
				'date'   => MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $row['created_at'] ) ), 'short' ),
			);
		}
		return new WP_REST_Response( array( 'items' => $out, 'unread' => MB_DB::unread_count( $uid ) ), 200 );
	}

	private static function notification_title( string $type ): string {
		$map = array(
			'sub_active'     => 'اشتراک فعال شد',
			'sub_grace'      => 'اشتراک در مهلت ارفاق است',
			'sub_expired'    => 'اشتراک تمام شد',
			'partner_joined' => 'همسرت دعوت را پذیرفت',
			'partner_ack'    => 'همسرت پیام را دید',
			'support_ping'   => 'پیام حمایت',
		);
		return $map[ $type ] ?? 'اعلان ماه‌بانو';
	}

	public static function ep_notifications_read( WP_REST_Request $r ) {
		MB_DB::mark_notifications_read( get_current_user_id() );
		return new WP_REST_Response( array( 'ok' => true, 'unread' => 0 ), 200 );
	}

	public static function ep_support_send( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_Subscription::can( $uid, 'partner' ) ) {
			return self::pro_denied();
		}
		$partner = MB_Privacy::partner_of( $uid );
		$invite  = MB_DB::get_active_invite( $uid );
		if ( ! $partner || ! $invite || empty( $invite['share_support'] ) ) {
			return new WP_Error( 'mb_partner', 'همسری متصل نیست یا اشتراک‌گذاری پیام خاموش است.', array( 'status' => 400 ) );
		}
		if ( ! MB_License::rate_limit( 'support_' . $uid, 20, 3600 ) ) {
			return new WP_Error( 'mb_rate', 'تعداد پیام‌ها زیاد است.', array( 'status' => 429 ) );
		}
		MB_Notify::support_ping( $uid, $partner, (string) $r->get_param( 'text' ) );
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'پیام برای همسرت فرستاده شد' ), 200 );
	}

	/* --------------------------- محتوا ---------------------------------- */
	public static function ep_articles( WP_REST_Request $r ) {
		$uid  = get_current_user_id();
		$cat  = (string) $r->get_param( 'cat' );
		$s    = (string) $r->get_param( 's' );
		$id   = (int) $r->get_param( 'id' );
		if ( $id > 0 ) {
			$post = get_post( $id );
			$rows = ( $post instanceof WP_Post && 'mb_article' === $post->post_type && 'publish' === $post->post_status )
				? array( self::article_row( $post ) )
				: array();
		} else {
			$rows = self::query_articles( $cat, $s );
		}
		if ( ! MB_Subscription::can( $uid, 'health_full' ) ) {
			$rows = array_slice( $rows, 0, 2 );
			foreach ( $rows as &$row ) {
				$row['content'] = '';
			}
			unset( $row );
		}
		return new WP_REST_Response( array( 'items' => $rows, 'locked' => ! MB_Subscription::can( $uid, 'health_full' ) ), 200 );
	}

	public static function ep_article_save( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_Subscription::can( $uid, 'save_articles' ) ) {
			return self::pro_denied();
		}
		$id    = (int) $r->get_param( 'id' );
		$post  = get_post( $id );
		if ( ! $post || 'mb_article' !== $post->post_type ) {
			return new WP_Error( 'mb_article', 'راهنما پیدا نشد.', array( 'status' => 404 ) );
		}
		$saved = (array) get_user_meta( $uid, 'mb_saved_articles', true );
		if ( in_array( $id, array_map( 'intval', $saved ), true ) ) {
			$saved = array_values( array_diff( array_map( 'intval', $saved ), array( $id ) ) );
			$msg   = 'از ذخیره‌ها حذف شد';
		} else {
			$saved[] = $id;
			$msg     = 'ذخیره شد';
		}
		update_user_meta( $uid, 'mb_saved_articles', array_values( array_unique( array_map( 'intval', $saved ) ) ) );
		return new WP_REST_Response( array( 'ok' => true, 'saved' => $saved, 'message' => $msg ), 200 );
	}

	public static function ep_question( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_Subscription::can( $uid, 'ask_expert' ) ) {
			return self::pro_denied();
		}
		$body = trim( (string) $r->get_param( 'body' ) );
		if ( mb_strlen( $body ) < 10 ) {
			return new WP_Error( 'mb_question', 'پرسش را کمی کامل‌تر بنویس.', array( 'status' => 400 ) );
		}
		if ( ! MB_License::rate_limit( 'question_' . $uid, 10, 3600 ) ) {
			return new WP_Error( 'mb_rate', 'تعداد پرسش‌ها زیاد است.', array( 'status' => 429 ) );
		}
		$id = MB_DB::add_question( $uid, $body );
		$share = (bool) $r->get_param( 'share_anon' );
		if ( $share ) {
			MB_Community::submit( $id, $body, (string) $r->get_param( 'cat' ) );
		}
		MB_Notify::admin_alert( 'پرسش تازه در ماه‌بانو', 'یک پرسش محرمانه تازه ثبت شد (شناسه ' . $id . ').' );
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => $share
					? 'پرسش ثبت شد. پس از پاسخ و تأیید، به‌صورت ناشناس در انجمن منتشر می‌شود.'
					: 'پرسش محرمانه ثبت شد؛ پاسخ در همین بخش نمایش داده می‌شود.',
			),
			200
		);
	}

	/* --------------------------- اشتراک --------------------------------- */
	public static function ep_checkout_create( WP_REST_Request $r ) {
		$uid  = get_current_user_id();
		$term = absint( $r->get_param( 'plan' ) );
		if ( 0 === $term ) {
			$term = 1;
		}
		$res = MB_Subscription::create_checkout( $uid, (string) $r->get_param( 'coupon' ), $term );
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'mb_checkout', $res->get_error_message(), array( 'status' => 400 ) );
		}
		return new WP_REST_Response( $res, 200 );
	}

	public static function ep_checkout_verify( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		MB_Subscription::transition( $uid );
		delete_transient( 'mb_pro_' . $uid );
		return new WP_REST_Response( MB_Subscription::status_data( $uid ), 200 );
	}

	public static function ep_checkout_recover( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_License::rate_limit( 'recover_' . $uid, 6, 600 ) ) {
			return new WP_Error( 'mb_rate', 'کمی بعد دوباره تلاش کن.', array( 'status' => 429 ) );
		}
		$result = MB_Subscription::recover_pending( $uid );
		delete_transient( 'mb_pro_' . $uid );
		return new WP_REST_Response(
			array(
				'ok'        => $result['activated'] > 0,
				'activated' => (int) $result['activated'],
				'message'   => (string) $result['message'],
			),
			200
		);
	}

	public static function ep_subscription_status( WP_REST_Request $r ) {
		return new WP_REST_Response( MB_Subscription::status_data( get_current_user_id() ), 200 );
	}

	/* --------------------------------------------------------------------- */
	/* کمکی                                                                  */
	/* --------------------------------------------------------------------- */
	public static function share_defaults( int $user_id ): array {
		$saved = get_user_meta( $user_id, 'mb_share_defaults', true );
		$base  = array(
			'share_pms'       => 1,
			'share_period'    => 1,
			'share_support'   => 1,
			'share_fertility' => 0,
			'share_status'    => 0,
		);
		if ( ! is_array( $saved ) ) {
			return $base;
		}
		foreach ( $base as $key => $default ) {
			$base[ $key ] = isset( $saved[ $key ] ) ? (int) (bool) $saved[ $key ] : $default;
		}
		return $base;
	}

	private static function parse_ym( string $ym ): array {
		$ym = MB_Jalali::en_num( $ym );
		if ( preg_match( '/^(\d{4})-(\d{1,2})$/', $ym, $m ) ) {
			$jy = (int) $m[1];
			$jm = (int) $m[2];
			if ( $jy >= 1300 && $jy <= 1500 && $jm >= 1 && $jm <= 12 ) {
				return array( $jy, $jm );
			}
		}
		list( $jy, $jm ) = MB_Jalali::g2j( MB_Jalali::today() );
		return array( (int) $jy, (int) $jm );
	}

	private static function shift_month( int $jy, int $jm, int $delta ): string {
		$jm += $delta;
		while ( $jm > 12 ) {
			$jm -= 12;
			++$jy;
		}
		while ( $jm < 1 ) {
			$jm += 12;
			--$jy;
		}
		return sprintf( '%04d-%02d', $jy, $jm );
	}

	/* --------------------------------------------------------------------- */
	/* دادهٔ صفحه‌های نسخهٔ ۳٫۰                                               */
	/* --------------------------------------------------------------------- */
	private static function data_pregnancy( int $user_id, string $token ): array {
		$data              = self::data_common( $user_id, $token );
		$data['pregnancy'] = MB_Pregnancy::get( $user_id );
		$data['checklist'] = MB_Pregnancy::get_checklist( $user_id );
		$data['can_partner'] = MB_Subscription::can( $user_id, 'partner' );
		$data['has_partner'] = MB_Privacy::partner_of( $user_id ) > 0;
		return $data;
	}

	private static function data_ttc( int $user_id, string $token ): array {
		$data              = self::data_common( $user_id, $token );
		$data['analysis']  = MB_TTC::analyze( $user_id, 45 );
		$data['chart']     = MB_TTC::chart_svg( $data['analysis']['points'] );
		$data['summary']   = MB_TTC::summary_text( $data['analysis'] );
		$data['fertile']   = MB_Cycle_Engine::fertile_range( $user_id, MB_Jalali::today() );
		$data['mucus_map'] = MB_DB::mucus_options();
		$data['confirmed'] = MB_TTC::ovulation_confirmed_this_cycle( $user_id );
		return $data;
	}

	private static function data_report( int $user_id, string $arg, string $token ): array {
		$months         = '' === $arg ? 6 : max( 1, min( 12, (int) $arg ) );
		$data           = self::data_common( $user_id, $token );
		$data['report'] = MB_Report::data( $user_id, $months );
		$data['csv']    = MB_Report::csv_url( $months );
		$data['months'] = $months;
		return $data;
	}

	private static function data_assistant( int $user_id, string $token ): array {
		$data          = self::data_common( $user_id, $token );
		$data['topics'] = MB_Assistant::topics();
		$data['label'] = MB_Assistant::LABEL;
		$data['snap']  = MB_Cycle_Engine::snapshot( $user_id );
		return $data;
	}

	private static function data_community( int $user_id, string $arg, string $token ): array {
		$cat  = sanitize_key( $arg );
		$cats = MB_Plugin::categories();
		if ( '' !== $cat && ! array_key_exists( $cat, $cats ) ) {
			$cat = '';
		}
		$data          = self::data_common( $user_id, $token );
		$data['cat']   = $cat;
		$data['cats']  = $cats;
		$data['items'] = MB_Community::feed( $cat, 1 );
		$data['can_ask'] = MB_Subscription::can( $user_id, 'ask_expert' );
		return $data;
	}

	private static function data_quizzes( int $user_id, string $token ): array {
		$data            = self::data_common( $user_id, $token );
		$quizzes         = MB_DB::get_quizzes( true );
		$data['quizzes'] = array();
		foreach ( $quizzes as $quiz ) {
			$last = MB_DB::last_quiz_result( $user_id, (int) $quiz['id'] );
			$data['quizzes'][] = array(
				'slug'      => (string) $quiz['slug'],
				'title'     => (string) $quiz['title'],
				'intro'     => (string) $quiz['intro'],
				'count'     => count( (array) $quiz['questions'] ),
				'last_band' => $last ? (string) $last['band'] : '',
			);
		}
		$data['note'] = MB_Quiz::SCREENING_NOTE;
		return $data;
	}

	private static function data_quiz( int $user_id, string $arg, string $token ): array {
		$data         = self::data_quizzes( $user_id, $token );
		$quiz         = MB_DB::get_quiz( $arg );
		$data['quiz'] = $quiz;
		if ( $quiz ) {
			$data['max']  = MB_Quiz::max_score( $quiz );
			$data['last'] = MB_DB::last_quiz_result( $user_id, (int) $quiz['id'] );
		}
		return $data;
	}

	private static function data_reminders( int $user_id, string $token ): array {
		$data               = self::data_common( $user_id, $token );
		$data['prefs']      = MB_Reminders::prefs( $user_id );
		$data['channels']   = MB_Reminders::channels();
		$data['meds']       = MB_Reminders::meds( $user_id );
		$data['is_partner'] = MB_Privacy::is_partner( $user_id );
		$data['can_sms']    = MB_Subscription::can( $user_id, 'sms' );
		return $data;
	}

	/* --------------------------------------------------------------------- */
	/* Endpoints نسخهٔ ۳٫۰                                                   */
	/* --------------------------------------------------------------------- */
	private static function guard_rate( string $bucket, int $max, int $window ) {
		if ( ! MB_License::rate_limit( $bucket, $max, $window ) ) {
			return new WP_Error( 'mb_rate', 'تعداد درخواست‌ها زیاد است. کمی بعد تلاش کن.', array( 'status' => 429 ) );
		}
		return null;
	}

	public static function ep_pregnancy_start( WP_REST_Request $r ) {
		$uid  = get_current_user_id();
		$rate = self::guard_rate( 'preg_' . $uid, 10, 3600 );
		if ( $rate ) {
			return $rate;
		}
		$raw = (string) $r->get_param( 'lmp' );
		$lmp = MB_Jalali::parse_jalali( $raw );
		if ( ! $lmp ) {
			$lmp = MB_Jalali::sanitize_date( $raw );
		}
		$result = MB_Pregnancy::start( $uid, (string) $lmp );
		if ( empty( $result['ok'] ) ) {
			return new WP_Error( 'mb_pregnancy', $result['message'], array( 'status' => 400 ) );
		}
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'message' => $result['message'],
				'due'     => MB_Jalali::format_fa( (string) $result['due'], 'long' ),
			),
			200
		);
	}

	public static function ep_pregnancy_stop( WP_REST_Request $r ) {
		$uid    = get_current_user_id();
		$result = MB_Pregnancy::stop( $uid );
		return new WP_REST_Response( array( 'ok' => true, 'message' => $result['message'] ), 200 );
	}

	public static function ep_pregnancy_share( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_Pregnancy::is_active( $uid ) ) {
			return new WP_Error( 'mb_pregnancy', 'حالت بارداری فعال نیست.', array( 'status' => 400 ) );
		}
		$on = (bool) $r->get_param( 'on' );
		MB_DB::set_pregnancy_share( $uid, $on );
		MB_Plugin::log_security( 'pregnancy_share', array( 'user' => $uid, 'on' => $on ? 1 : 0 ) );
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'on'      => $on,
				'message' => $on
					? 'همسرت فقط شمارهٔ هفته و تاریخ موعد را می‌بیند.'
					: 'اشتراک بارداری با همسر خاموش شد.',
			),
			200
		);
	}

	public static function ep_pregnancy_checklist( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		$ok  = MB_Pregnancy::toggle_checklist( $uid, (string) $r->get_param( 'key' ), (bool) $r->get_param( 'done' ) );
		if ( ! $ok ) {
			return new WP_Error( 'mb_checklist', 'این مورد شناسایی نشد.', array( 'status' => 400 ) );
		}
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	public static function ep_reminder_save( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		$ok  = MB_Reminders::save_pref(
			$uid,
			(string) $r->get_param( 'trigger' ),
			array(
				'on'      => (bool) $r->get_param( 'on' ),
				'hour'    => $r->get_param( 'hour' ),
				'channel' => (string) $r->get_param( 'channel' ),
			)
		);
		if ( ! $ok ) {
			return new WP_Error( 'mb_reminder', 'این یادآور شناسایی نشد.', array( 'status' => 400 ) );
		}
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'یادآور ذخیره شد' ), 200 );
	}

	public static function ep_med_add( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		$ok  = MB_Reminders::add_med( $uid, (string) $r->get_param( 'name' ), (string) $r->get_param( 'time' ) );
		if ( ! $ok ) {
			return new WP_Error( 'mb_med', 'نام و ساعت را درست وارد کن (حداکثر ۱۰ مورد).', array( 'status' => 400 ) );
		}
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'یادآور دارو اضافه شد', 'meds' => MB_Reminders::meds( $uid ) ), 200 );
	}

	public static function ep_med_remove( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		$ok  = MB_Reminders::remove_med( $uid, (int) $r->get_param( 'index' ) );
		if ( ! $ok ) {
			return new WP_Error( 'mb_med', 'این مورد پیدا نشد.', array( 'status' => 400 ) );
		}
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'حذف شد', 'meds' => MB_Reminders::meds( $uid ) ), 200 );
	}

	public static function ep_community( WP_REST_Request $r ) {
		$uid  = get_current_user_id();
		$rate = self::guard_rate( 'community_' . $uid, 60, 300 );
		if ( $rate ) {
			return $rate;
		}
		$cat  = sanitize_key( (string) $r->get_param( 'cat' ) );
		$page = max( 1, (int) $r->get_param( 'page' ) );
		return new WP_REST_Response( array( 'ok' => true, 'items' => MB_Community::feed( $cat, $page ) ), 200 );
	}

	public static function ep_quiz_submit( WP_REST_Request $r ) {
		$uid  = get_current_user_id();
		$rate = self::guard_rate( 'quiz_' . $uid, 30, 600 );
		if ( $rate ) {
			return $rate;
		}
		$quiz = MB_DB::get_quiz( (string) $r->get_param( 'slug' ) );
		if ( ! $quiz ) {
			return new WP_Error( 'mb_quiz', 'این تست پیدا نشد.', array( 'status' => 404 ) );
		}
		$answers = array();
		foreach ( (array) $r->get_param( 'answers' ) as $index => $pick ) {
			$answers[ (int) $index ] = (int) $pick;
		}
		$result = MB_Quiz::score( $quiz, $answers );
		if ( null === $result ) {
			return new WP_Error( 'mb_quiz', 'به همهٔ پرسش‌ها پاسخ بده.', array( 'status' => 400 ) );
		}
		MB_DB::save_quiz_result( $uid, (int) $quiz['id'], (int) $result['score'], (int) $result['max'], (string) ( $result['band']['key'] ?? '' ), $result['answers'] );
		$article = MB_Api::query_articles( (string) ( $result['band']['cat'] ?? $quiz['cat'] ), '', 1 );
		return new WP_REST_Response(
			array(
				'ok'      => true,
				'score'   => (int) $result['score'],
				'max'     => (int) $result['max'],
				'percent' => (int) $result['percent'],
				'label'   => (string) ( $result['band']['label'] ?? '' ),
				'advice'  => (string) ( $result['band']['advice'] ?? '' ),
				'note'    => MB_Quiz::SCREENING_NOTE,
				'article' => empty( $article ) ? null : $article[0],
			),
			200
		);
	}

	public static function ep_assistant( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_Subscription::can( $uid, 'assistant' ) ) {
			return self::pro_denied();
		}
		$rate = self::guard_rate( 'assistant_' . $uid, 30, 600 );
		if ( $rate ) {
			return $rate;
		}
		$question = (string) $r->get_param( 'q' );
		if ( mb_strlen( trim( $question ) ) < 2 ) {
			return new WP_Error( 'mb_assistant', 'پرسشت را بنویس.', array( 'status' => 400 ) );
		}
		$answer = MB_Assistant::answer( $uid, $question );
		return new WP_REST_Response(
			array(
				'ok'       => true,
				'title'    => $answer['title'],
				'answer'   => $answer['answer'],
				'phase'    => $answer['phase_label'],
				'article'  => $answer['article'],
				'fallback' => (bool) $answer['fallback'],
				'label'    => $answer['label'],
			),
			200
		);
	}
}