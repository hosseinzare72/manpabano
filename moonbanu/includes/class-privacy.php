<?php
/**
 * لایه حریم خصوصی: قاعده غیرقابل نقض «داده خصوصی بانو هرگز به همسر نمی‌رسد».
 * تنها جایی که خروجی همسر ساخته می‌شود همین کلاس است (whitelist سخت).
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Privacy {

	/** فیلدهای مجاز برای همسر؛ هر فیلد دیگری حذف می‌شود. */
	const PARTNER_WHITELIST = array(
		'wife_name',
		'week_colors',
		'next_period_date',
		'next_period_jalali',
		'days_to_period',
		'pms_alert',
		'pms_start_jalali',
		'pms_start_g',
		'support_suggestions',
		'ack',
		'snooze_until',
		'checklist',
		'fertility_window',
		// N5 — فقط باندها؛ هیچ عدد، نشانه یا یادداشتی در این کلید نیست.
		'today_status',
		'disclaimer',
		// فقط در صورت روشن‌بودن share_pregnancy پر می‌شوند (M1).
		'pregnancy_week',
		'due_date',
	);

	public static function init(): void {
		add_filter( 'wp_privacy_personal_data_exporters', array( __CLASS__, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( __CLASS__, 'register_eraser' ) );
		add_action( 'delete_user', array( __CLASS__, 'on_delete_user' ) );
	}

	/* --------------------------------------------------------------------- */
	/* نقش‌ها و پیوند بانو ↔ همسر                                            */
	/* --------------------------------------------------------------------- */

	public static function is_partner( int $user_id ): bool {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return false;
		}
		if ( in_array( MB_ROLE_PARTNER, (array) $user->roles, true ) ) {
			return true;
		}
		return (bool) get_user_meta( $user_id, 'mb_partner_of', true ) && ! in_array( MB_ROLE_WOMAN, (array) $user->roles, true );
	}

	/** شناسه بانوی متصل به این همسر. */
	public static function wife_of( int $partner_id ): int {
		$wife = (int) get_user_meta( $partner_id, 'mb_partner_of', true );
		if ( ! $wife ) {
			return 0;
		}
		$invite = MB_DB::get_invite_for_partner( $partner_id );
		if ( ! $invite || (int) $invite['wife_id'] !== $wife ) {
			return 0; // دسترسی لغو شده است.
		}
		return $wife;
	}

	/** شناسه همسر متصل به بانو. */
	public static function partner_of( int $wife_id ): int {
		$invite = MB_DB::get_active_invite( $wife_id );
		return $invite && ! empty( $invite['accepted_by'] ) ? (int) $invite['accepted_by'] : 0;
	}

	/** آیا این همسر همین الان اجازه دیدن دارد؟ (اشتراک بانو + عدم لغو) */
	public static function partner_access( int $partner_id ): array {
		$wife = self::wife_of( $partner_id );
		if ( ! $wife ) {
			return array( 'ok' => false, 'reason' => 'revoked' );
		}
		if ( ! MB_Subscription::is_pro( $wife ) ) {
			return array( 'ok' => false, 'reason' => 'no_pro' );
		}
		$invite = MB_DB::get_invite_for_partner( $partner_id );
		if ( ! $invite || (int) $invite['revoked'] === 1 ) {
			return array( 'ok' => false, 'reason' => 'revoked' );
		}
		return array( 'ok' => true, 'wife_id' => $wife, 'invite' => $invite );
	}

	/* --------------------------------------------------------------------- */
	/* ساخت داده همسر (تنها منبع مجاز)                                       */
	/* --------------------------------------------------------------------- */

	/**
	 * خروجی کامل صفحه‌های همسر. هیچ نشانه، خلق، درد، یادداشت یا داده سلامت جنسی
	 * در این آرایه وجود ندارد؛ فیلدها هم در پایان با whitelist فیلتر می‌شوند.
	 */
	public static function partner_payload( int $wife_id, array $invite ): array {
		$today = MB_Jalali::today();
		$wife  = get_userdata( $wife_id );
		$name  = $wife ? ( $wife->first_name ? $wife->first_name : $wife->display_name ) : 'همسر شما';

		$series = MB_Cycle_Engine::phase_series( $wife_id, $today, MB_Jalali::add_days( $today, 6 ) );
		$colors = array();
		$i      = 0;
		foreach ( $series as $date => $phase ) {
			// رنگ‌ها فقط دسته فاز را می‌گویند، نه هیچ نشانه‌ای.
			$visible = $phase;
			if ( in_array( $phase, array( 'fertile', 'ovulation' ), true ) && empty( $invite['share_fertility'] ) ) {
				$visible = 'norm';
			}
			if ( 'period' === $phase && empty( $invite['share_period'] ) ) {
				$visible = 'norm';
			}
			if ( 'pms' === $phase && empty( $invite['share_pms'] ) ) {
				$visible = 'norm';
			}
			// فاز فولیکولار/لوتئال دقیقاً مرز قاعدگی را لو می‌دهد؛ اگر بانو
			// اشتراک قاعدگی را خاموش کرده، این دو هم باید خنثی شوند.
			if ( in_array( $phase, array( 'follicular', 'luteal' ), true ) && empty( $invite['share_period'] ) ) {
				$visible = 'norm';
			}
			$colors[] = array(
				'jalali'  => MB_Jalali::format_fa( $date, 'd' ),
				'weekday' => MB_Jalali::week_days_short()[ MB_Jalali::day_of_week( $date ) ],
				'color'   => self::phase_color_key( $visible ),
				'today'   => 0 === $i,
			);
			++$i;
		}

		$pms_range  = MB_Cycle_Engine::pms_range( $wife_id, $today );
		$pms_starts = $pms_range['from'] ? MB_Jalali::diff_days( $today, $pms_range['from'] ) : null;
		// پیش از این فقط ۰ تا ۲ روز *پیش از* PMS هشدار می‌داد و در خودِ روزهای
		// PMS خاموش می‌شد، یعنی همسر درست در روزهای مهم چیزی نمی‌دید.
		$in_pms     = 'pms' === MB_Cycle_Engine::phase_of( $wife_id, $today );
		$pms_alert  = ! empty( $invite['share_pms'] ) && ( $in_pms || ( null !== $pms_starts && $pms_starts >= 0 && $pms_starts <= 2 ) );

		$next  = MB_Cycle_Engine::next_period( $wife_id, $today );
		$phase = MB_Cycle_Engine::phase_of( $wife_id, $today );
		$state = self::partner_state( (int) $invite['accepted_by'] );

		$payload = array(
			'wife_name'           => $name,
			'week_colors'         => $colors,
			'next_period_date'    => ! empty( $invite['share_period'] ) ? $next : null,
			'next_period_jalali'  => ! empty( $invite['share_period'] ) && $next ? MB_Jalali::format_fa( $next, 'long' ) : null,
			'days_to_period'      => ! empty( $invite['share_period'] ) ? MB_Cycle_Engine::days_to_next_period( $wife_id, $today ) : null,
			'pms_alert'           => (bool) $pms_alert,
			'pms_start_jalali'    => $pms_alert && $pms_range['from'] ? MB_Jalali::format_fa( $pms_range['from'], 'long' ) : null,
			'pms_start_g'         => $pms_alert && $pms_range['from'] ? (string) $pms_range['from'] : '',
			'support_suggestions' => ! empty( $invite['share_support'] ) ? MB_Cycle_Engine::support_suggestions( $phase ) : array(),
			'ack'                 => ! empty( $state['ack'] ),
			'snooze_until'        => $state['snooze_until'] ?? '',
			'checklist'           => $state['checklist'] ?? array(),
			'fertility_window'    => ! empty( $invite['share_fertility'] ) ? self::fertility_window_range( $wife_id ) : null,
			'today_status'        => ! empty( $invite['share_status'] ) ? self::today_status( $wife_id ) : null,
			'disclaimer'          => MB_Plugin::disclaimer(),
		);

		// M1: اگر و تنها اگر توگل مستقل بارداری روشن باشد، دو فیلد اضافه می‌شود.
		$payload = array_merge( $payload, MB_Pregnancy::partner_fields( $wife_id ) );

		return self::filter_whitelist( $payload );
	}

	/**
	 * N5 — کارت «وضعیت امروز» برای همسر: فقط سه باند.
	 *
	 * هیچ عدد، هیچ نشانه و هیچ یادداشتی از این متد بیرون نمی‌رود؛ خروجی
	 * تنها سه برچسب متنی و کلید رنگ فاز است.
	 */
	public static function today_status( int $wife_id ): array {
		$today = MB_Jalali::today();
		$phase = MB_Cycle_Engine::phase_of( $wife_id, $today );
		$log   = MB_DB::get_log( $wife_id, $today );

		// امتیاز داخلی فقط برای باندبندی است و هرگز ارسال نمی‌شود.
		$score = (int) MB_Cycle_Engine::energy_level( $wife_id, $today );
		if ( $log ) {
			$sleep = isset( $log['sleep_h'] ) && null !== $log['sleep_h'] ? (float) $log['sleep_h'] : null;
			if ( null !== $sleep && $sleep < 5 ) {
				$score -= 12;
			} elseif ( null !== $sleep && $sleep >= 7.5 ) {
				$score += 6;
			}
			$pain = (int) ( $log['pain'] ?? 0 );
			if ( $pain >= 6 ) {
				$score -= 14;
			} elseif ( $pain >= 4 ) {
				$score -= 7;
			}
			$mood = isset( $log['mood'] ) && null !== $log['mood'] ? (int) $log['mood'] : null;
			if ( null !== $mood ) {
				$score += ( $mood - 3 ) * 5;
			}
		}
		$score = max( 0, min( 100, $score ) );

		if ( $score < 45 ) {
			$energy = array( 'low', 'کم' );
		} elseif ( $score < 70 ) {
			$energy = array( 'med', 'متوسط' );
		} else {
			$energy = array( 'high', 'بالا' );
		}

		// نیاز به حمایت، وارونهٔ انرژی با وزن فاز.
		$need = 100 - $score;
		if ( in_array( $phase, array( 'period', 'pms' ), true ) ) {
			$need += 10;
		}
		if ( $need >= 62 ) {
			$support = array( 'high', 'بیشتر از همیشه' );
		} elseif ( $need >= 38 ) {
			$support = array( 'med', 'کمی بیشتر' );
		} else {
			$support = array( 'low', 'معمولی' );
		}

		return array(
			'energy_band'   => $energy[0],
			'energy_label'  => $energy[1],
			'support_band'  => $support[0],
			'support_label' => $support[1],
			'phase_color'   => self::phase_color_key( $phase ),
		);
	}

	/** فقط بازه زمانی باروری (بدون هیچ نشانه‌ای) و تنها اگر توگل چهارم روشن باشد. */
	private static function fertility_window_range( int $wife_id ): ?string {
		$f = MB_Cycle_Engine::fertile_range( $wife_id, MB_Jalali::today() );
		if ( empty( $f['from'] ) || empty( $f['to'] ) ) {
			return null;
		}
		return MB_Jalali::range_fa( $f['from'], $f['to'] );
	}

	/** حذف هر کلید غیرمجاز، به‌صورت بازگشتی روی سطح اول. */
	public static function filter_whitelist( array $payload ): array {
		// گام ۱: هر فیلد ممنوع، حتی اگر روزی اشتباهی به whitelist اضافه شود،
		// اینجا گرفته و با شدت critical لاگ می‌شود.
		self::guard_forbidden( $payload );

		$out = array();
		foreach ( self::PARTNER_WHITELIST as $key ) {
			if ( array_key_exists( $key, $payload ) ) {
				$out[ $key ] = $payload[ $key ];
			}
		}
		return $out;
	}

	/**
	 * نگهبان فیلدهای ممنوع.
	 *
	 * اگر هر یک از فیلدهای خصوصی مطلق در payloadِ همسر ظاهر شود، رویداد با
	 * شدت critical در لاگ امنیتی ثبت و به مدیر ایمیل می‌شود. این دومین لایه
	 * پشت whitelist است، نه جایگزین آن.
	 */
	public static function guard_forbidden( array $payload ): void {
		$found = array_values( array_intersect( array_keys( $payload ), MB_TTC::FORBIDDEN_FOR_PARTNER ) );
		if ( empty( $found ) ) {
			return;
		}
		MB_Plugin::log_security(
			'partner_forbidden_field',
			array(
				'severity' => 'critical',
				'fields'   => $found,
				'ip'       => MB_DB::client_ip(),
			)
		);
		MB_Notify::admin_alert(
			'هشدار حریم خصوصی ماه‌بانو',
			'تلاش برای عبور فیلد(های) خصوصی از مسیر همسر گرفته شد: ' . implode( ', ', $found ) . '. هیچ داده‌ای ارسال نشد.'
		);
	}

	public static function phase_color_key( string $phase ): string {
		$map = array(
			'period'     => 'period',
			'fertile'    => 'fertile',
			'ovulation'  => 'ovul',
			'pms'        => 'pms',
			'luteal'     => 'luteal',
			'follicular' => 'foll',
			'normal'     => 'norm',
			'norm'       => 'norm',
		);
		return $map[ $phase ] ?? 'norm';
	}

	/* --------------------------------------------------------------------- */
	/* چک‌لیست و وضعیت همسر                                                  */
	/* --------------------------------------------------------------------- */

	public static function partner_state( int $partner_id ): array {
		$state = get_user_meta( $partner_id, 'mb_partner_state', true );
		if ( ! is_array( $state ) ) {
			$state = array();
		}
		return wp_parse_args(
			$state,
			array(
				'ack'          => 0,
				'snooze_until' => '',
				'checklist'    => array(),
				'heartbeat'    => 0,
			)
		);
	}

	public static function save_partner_state( int $partner_id, array $state ): void {
		$clean = array(
			'ack'          => empty( $state['ack'] ) ? 0 : 1,
			'snooze_until' => isset( $state['snooze_until'] ) ? sanitize_text_field( (string) $state['snooze_until'] ) : '',
			'heartbeat'    => isset( $state['heartbeat'] ) ? (int) $state['heartbeat'] : time(),
			'checklist'    => array(),
		);
		foreach ( (array) ( $state['checklist'] ?? array() ) as $key => $done ) {
			$clean['checklist'][ sanitize_key( (string) $key ) ] = empty( $done ) ? 0 : 1;
		}
		update_user_meta( $partner_id, 'mb_partner_state', $clean );
	}

	/** ۵ کار مؤثر امشب (ثابت و بی‌جزئیات خصوصی). */
	public static function calm_tasks(): array {
		return array(
			'tea'    => array( 'icon' => 'cup', 'label' => 'یک نوشیدنی گرم بی‌درخواست بیاور' ),
			'chores' => array( 'icon' => 'shield', 'label' => 'یک کار خانه را کامل خودت انجام بده' ),
			'quiet'  => array( 'icon' => 'moon', 'label' => 'محیط را کم‌نور و آرام کن' ),
			'listen' => array( 'icon' => 'heart', 'label' => 'ده دقیقه فقط گوش بده، بدون راه‌حل دادن' ),
			'plan'   => array( 'icon' => 'spark', 'label' => 'برنامه شلوغ امشب را سبک‌تر کن' ),
		);
	}

	/* --------------------------------------------------------------------- */
	/* صفحه فرود دعوت‌نامه (site/?mb_invite=TOKEN)                            */
	/* --------------------------------------------------------------------- */

	public static function render_invite_landing( string $token ): void {
		$error   = '';
		$success = false;
		$invite  = MB_DB::get_invite_by_token( $token );

		if ( isset( $_POST['mb_accept'] ) ) {
			if ( ! MB_License::rate_limit( 'invite_accept', 20, 900 ) ) {
				$error = 'تلاش‌های بیش از حد. چند دقیقه بعد دوباره امتحان کن.';
			} elseif ( ! isset( $_POST['mb_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_nonce'] ) ), 'mb_invite_' . $token ) ) {
				$error = 'اعتبار فرم منقضی شده است. صفحه را دوباره باز کن.';
			} else {
				$result = self::accept_invite(
					$token,
					array(
						'name'     => isset( $_POST['mb_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mb_name'] ) ) : '',
						'email'    => isset( $_POST['mb_email'] ) ? sanitize_email( wp_unslash( $_POST['mb_email'] ) ) : '',
						'password' => isset( $_POST['mb_pass'] ) ? (string) wp_unslash( $_POST['mb_pass'] ) : '',
					)
				);
				if ( is_wp_error( $result ) ) {
					$error = $result->get_error_message();
				} else {
					$success = true;
				}
			}
		}

		$state = self::invite_state( $invite );
		status_header( 200 );
		nocache_headers();
		?>
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>دعوت‌نامه همراه ماه | ماه‌بانو</title>
<link rel="stylesheet" href="<?php echo esc_url( MB_URL . 'assets/css/style.css?v=' . MB_VERSION ); ?>">
</head>
<body class="mb-standalone" data-theme="partner">
<div class="mb-app" data-theme="partner">
	<div class="mb-bg" aria-hidden="true"></div>
	<div class="mb-view">
		<div class="scr">
			<div class="pad">
				<div class="glass card center">
					<div class="brand-sm gold-text">ماه‌بانو</div>
					<h1 class="h1">همراه ماه</h1>
					<?php if ( $success ) : ?>
						<p class="body">دسترسی شما فعال شد. حالا می‌توانید صفحه همراه را ببینید.</p>
						<a class="btn gold wide" href="<?php echo esc_url( MB_UI::app_url( '#/partner' ) ); ?>">ورود به صفحه همراه</a>
					<?php elseif ( 'ok' !== $state ) : ?>
						<p class="body"><?php echo esc_html( self::invite_state_message( $state ) ); ?></p>
						<a class="btn ghost wide" href="<?php echo esc_url( home_url( '/' ) ); ?>">بازگشت</a>
					<?php else : ?>
						<p class="body">همسرتان شما را برای همراهی محترمانه دعوت کرده است. شما فقط رنگ روزهای پیش‌رو، تاریخ تخمینی قاعدگی بعدی و پیشنهادهای حمایتی را می‌بینید؛ هیچ نشانه، یادداشت یا داده خصوصی دیگری نمایش داده نمی‌شود.</p>
						<?php if ( '' !== $error ) : ?>
							<div class="pnote warn"><?php echo esc_html( $error ); ?></div>
						<?php endif; ?>
						<form method="post" class="mb-form">
							<?php wp_nonce_field( 'mb_invite_' . $token, 'mb_nonce' ); ?>
							<input type="hidden" name="mb_accept" value="1">
							<?php if ( is_user_logged_in() ) : ?>
								<p class="small">با حساب فعلی (<?php echo esc_html( wp_get_current_user()->user_email ); ?>) دعوت را می‌پذیرید.</p>
							<?php else : ?>
								<label class="fld"><span>نام شما</span><input type="text" name="mb_name" required maxlength="60"></label>
								<label class="fld"><span>ایمیل</span><input type="email" name="mb_email" required></label>
								<label class="fld"><span>گذرواژه (اگر حساب دارید، همان گذرواژه)</span><input type="password" name="mb_pass" required minlength="6"></label>
							<?php endif; ?>
							<button type="submit" class="btn gold wide">می‌پذیرم و همراه می‌شوم</button>
						</form>
						<p class="small">اعتبار این دعوت‌نامه ۷۲ ساعت است و همسرتان هر لحظه می‌تواند دسترسی را لغو کند.</p>
					<?php endif; ?>
					<p class="small disclaimer"><?php echo esc_html( MB_Plugin::disclaimer() ); ?></p>
				</div>
			</div>
		</div>
	</div>
</div>
</body>
</html>
		<?php
	}

	public static function invite_state( ?array $invite ): string {
		if ( ! $invite ) {
			return 'missing';
		}
		if ( (int) $invite['revoked'] === 1 ) {
			return 'revoked';
		}
		if ( ! empty( $invite['accepted_by'] ) ) {
			return 'used';
		}
		if ( strtotime( (string) $invite['expires_at'] ) < time() ) {
			return 'expired';
		}
		if ( ! MB_Subscription::is_pro( (int) $invite['wife_id'] ) ) {
			return 'no_pro';
		}
		return 'ok';
	}

	public static function invite_state_message( string $state ): string {
		$map = array(
			'missing' => 'این دعوت‌نامه پیدا نشد.',
			'revoked' => 'این دسترسی توسط صاحب حساب لغو شده است.',
			'used'    => 'این دعوت‌نامه قبلاً استفاده شده است.',
			'expired' => 'اعتبار این دعوت‌نامه (۷۲ ساعت) تمام شده است.',
			'no_pro'  => 'اشتراک همراهی روی حساب دعوت‌کننده فعال نیست.',
		);
		return $map[ $state ] ?? 'این دعوت‌نامه معتبر نیست.';
	}

	/* --------------------------------------------------------------------- */
	/* پذیرش دعوت                                                            */
	/* --------------------------------------------------------------------- */

	public static function accept_invite( string $token, array $args ) {
		$invite = MB_DB::get_invite_by_token( $token );
		$state  = self::invite_state( $invite );
		if ( 'ok' !== $state ) {
			MB_Plugin::log_security( 'invite_accept_failed', array( 'state' => $state ) );
			return new WP_Error( 'mb_invite', self::invite_state_message( $state ) );
		}

		if ( is_user_logged_in() ) {
			$partner_id = get_current_user_id();
			if ( (int) $invite['wife_id'] === $partner_id ) {
				return new WP_Error( 'mb_invite', 'نمی‌توانید دعوت‌نامه خودتان را بپذیرید.' );
			}
		} else {
			$email = sanitize_email( $args['email'] ?? '' );
			$pass  = (string) ( $args['password'] ?? '' );
			if ( ! is_email( $email ) || '' === $pass ) {
				return new WP_Error( 'mb_invite', 'ایمیل یا گذرواژه معتبر نیست.' );
			}
			$existing = get_user_by( 'email', $email );
			if ( $existing ) {
				// این مسیر یک اوراکل گذرواژه بود: بدون محدودیت نرخ و با پیام
				// متفاوت، وجود حساب و درستی رمز را لو می‌داد.
				if ( ! MB_License::rate_limit( 'invite_pass', 8, 900 ) ) {
					return new WP_Error( 'mb_invite', 'تلاش‌های بیش از حد. چند دقیقه بعد دوباره امتحان کن.' );
				}
				$auth = wp_authenticate( $existing->user_login, $pass );
				if ( is_wp_error( $auth ) ) {
					MB_License::note_validation_error( 'invite_pass' );
					MB_Plugin::log_security( 'invite_login_failed', array( 'user' => (int) $existing->ID ) );
					return new WP_Error( 'mb_invite', 'ایمیل یا گذرواژه معتبر نیست.' );
				}
				$partner_id = (int) $existing->ID;
			} else {
				// حساب تازه باید همان سیاست رمز اپ را رعایت کند (۸ نویسه، حرف و عدد).
				if ( mb_strlen( $pass ) < MB_Auth::MIN_PASS || ! preg_match( '/[0-9]/', $pass ) || ! preg_match( '/[^0-9]/', $pass ) ) {
					return new WP_Error( 'mb_invite', 'گذرواژه باید دست‌کم ' . MB_Jalali::fa_num( MB_Auth::MIN_PASS ) . ' نویسه و شامل حرف و عدد باشد.' );
				}
				$login = self::unique_login( $email );
				$new   = wp_insert_user(
					array(
						'user_login'   => $login,
						'user_email'   => $email,
						'user_pass'    => $pass,
						'display_name' => sanitize_text_field( $args['name'] ?? $login ),
						'first_name'   => sanitize_text_field( $args['name'] ?? '' ),
						'role'         => MB_ROLE_PARTNER,
					)
				);
				if ( is_wp_error( $new ) ) {
					return new WP_Error( 'mb_invite', 'ساخت حساب ممکن نشد: ' . $new->get_error_message() );
				}
				$partner_id = (int) $new;
			}
			wp_set_current_user( $partner_id );
			wp_set_auth_cookie( $partner_id, false );
		}

		$user = get_userdata( $partner_id );
		if ( $user && ! in_array( MB_ROLE_PARTNER, (array) $user->roles, true ) && ! user_can( $partner_id, 'manage_options' ) ) {
			$user->add_role( MB_ROLE_PARTNER );
		}

		update_user_meta( $partner_id, 'mb_partner_of', (int) $invite['wife_id'] );
		MB_DB::update_invite(
			(int) $invite['id'],
			array(
				'accepted_by' => $partner_id,
				'accepted_at' => current_time( 'mysql' ),
			)
		);
		// N2 — اشتراک خانوادگی: از این لحظه couple_id هر دو یکی است.
		MB_Couple::link( (int) $invite['wife_id'], $partner_id );

		MB_DB::add_notification( (int) $invite['wife_id'], 'partner_joined', array( 'name' => $user ? $user->display_name : '' ) );
		MB_Plugin::log_security( 'invite_accepted', array( 'wife' => (int) $invite['wife_id'], 'partner' => $partner_id ) );

		return true;
	}

	private static function unique_login( string $email ): string {
		$base  = sanitize_user( current( explode( '@', $email ) ), true );
		$base  = '' !== $base ? $base : 'mbpartner';
		$login = $base;
		$i     = 1;
		// حلقه پیش از این سقف نداشت و می‌توانست درخواست را قفل کند.
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

	/** لغو کامل دسترسی همسر (یک‌طرفه و فوری). */
	public static function revoke_all( int $wife_id ): void {
		// N2: قطع پیوند زوج پیش از لغو دعوت‌نامه‌ها تا رابطه هنوز خوانده شود.
		$mb_partner = self::partner_of( $wife_id );
		if ( $mb_partner > 0 ) {
			MB_Couple::unlink( $mb_partner );
		}
		global $wpdb;
		$partner = self::partner_of( $wife_id );
		if ( ! $partner ) {
			$partner = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT accepted_by FROM ' . MB_DB::t( 'invites' ) . ' WHERE wife_id = %d AND accepted_by IS NOT NULL ORDER BY id DESC LIMIT 1', $wife_id ) );
		}
		MB_DB::revoke_invites( $wife_id );
		if ( $partner ) {
			delete_user_meta( $partner, 'mb_partner_of' );
			delete_transient( 'mb_psess_' . $partner );
		}
		MB_Plugin::log_security( 'invite_revoked', array( 'wife' => $wife_id, 'partner' => $partner ) );
	}

	/* --------------------------------------------------------------------- */
	/* WP Privacy API                                                        */
	/* --------------------------------------------------------------------- */

	public static function register_exporter( array $exporters ): array {
		$exporters['moonbanu'] = array(
			'exporter_friendly_name' => 'ماه‌بانو',
			'callback'               => array( __CLASS__, 'export_data' ),
		);
		return $exporters;
	}

	public static function register_eraser( array $erasers ): array {
		$erasers['moonbanu'] = array(
			'eraser_friendly_name' => 'ماه‌بانو',
			'callback'             => array( __CLASS__, 'erase_data' ),
		);
		return $erasers;
	}

	public static function export_data( string $email, int $page = 1 ): array {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return array( 'data' => array(), 'done' => true );
		}
		$uid     = (int) $user->ID;
		$profile = MB_DB::get_profile( $uid );
		// صفحه‌بندی واقعی: پیش از این همهٔ ثبت‌ها در یک صفحه بیرون می‌آمد و
		// روی حساب‌های قدیمی می‌توانست حافظه/زمان اجرا را تمام کند.
		$page     = max( 1, $page );
		$per_page = 400;
		$to       = MB_Jalali::add_days( MB_Jalali::today(), 1 - ( ( $page - 1 ) * $per_page ) );
		$from     = MB_Jalali::add_days( $to, -$per_page );
		$logs     = MB_DB::get_logs_range( $uid, $from, $to );

		$items = array(
			array(
				'group_id'    => 'mb_profile',
				'group_label' => 'پروفایل ماه‌بانو',
				'item_id'     => 'mb_profile',
				'data'        => array(
					array( 'name' => 'طول چرخه', 'value' => $profile['cycle_len'] ),
					array( 'name' => 'طول قاعدگی', 'value' => $profile['period_len'] ),
					array( 'name' => 'آخرین قاعدگی', 'value' => (string) $profile['last_period_start'] ),
				),
			),
		);

		foreach ( $logs as $date => $log ) {
			$items[] = array(
				'group_id'    => 'mb_logs',
				'group_label' => 'ثبت‌های روزانه ماه‌بانو',
				'item_id'     => 'mb_log_' . $log['id'],
				'data'        => array(
					array( 'name' => 'تاریخ', 'value' => $log['jalali'] ),
					array( 'name' => 'خونریزی', 'value' => $log['bleeding'] ),
					array( 'name' => 'درد', 'value' => $log['pain'] ),
					array( 'name' => 'نشانه‌ها', 'value' => implode( ', ', (array) $log['symptoms'] ) ),
					array( 'name' => 'یادداشت خصوصی', 'value' => MB_DB::get_private_note( $uid, $date ) ),
				),
			);
		}

		return array( 'data' => $items, 'done' => empty( $logs ) );
	}

	public static function erase_data( string $email, int $page = 1 ): array {
		$user = get_user_by( 'email', $email );
		if ( ! $user ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}
		MB_DB::delete_user_data( (int) $user->ID );
		return array( 'items_removed' => true, 'items_retained' => false, 'messages' => array( 'داده‌های ماه‌بانو حذف شد.' ), 'done' => true );
	}

	public static function on_delete_user( int $user_id ): void {
		// ترتیب مهم است: delete_user_data دعوت‌نامه‌ها را پاک می‌کند، پس
		// partner_of() بعد از آن همیشه صفر برمی‌گشت و پیوند همسر برای همیشه
		// روی یک حساب حذف‌شده باقی می‌ماند.
		$partner = self::partner_of( $user_id );
		if ( $partner > 0 ) {
			MB_Couple::unlink( $partner );
			delete_user_meta( $partner, 'mb_partner_of' );
			delete_transient( 'mb_psess_' . $partner );
			delete_transient( 'mb_pro_' . $partner );
		}
		MB_DB::delete_user_data( $user_id );
	}
}
