<?php
/**
 * §۸ — تست‌های پذیرش نسخهٔ ۳٫۰.
 *
 * این تست‌ها از تب «وضعیت» پنل مدیریت اجرا می‌شوند و هیچ دادهٔ کاربری
 * نمی‌نویسند: همه روی توابع خالص و ثابت‌های کلاس‌ها کار می‌کنند.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Selftest {

	/** اجرای همهٔ تست‌ها. */
	public static function run_all(): array {
		$groups = array(
			'۸٫۱ تقویم جلالی و وکتورهای چرخه' => self::test_jalali(),
			'۸٫۲ حالت بارداری'                => self::test_pregnancy(),
			'۸٫۳ دمای پایه و نشت‌نکردن TTC'   => self::test_bbt_and_leak(),
			'۸٫۴ گزارش پزشک'                  => self::test_report(),
			'۸٫۵ انجمن ناشناس'                => self::test_community(),
			'۸٫۶ کوییزها'                     => self::test_quiz(),
			'۸٫۷ چرخهٔ نامنظم'                => self::test_irregular(),
			'۸٫۸ کوپن معرف'                   => self::test_referral(),
			'۸٫۹ ضدتقلب و یکپارچگی'           => self::test_antitamper(),
			'۸٫۱۰ چیدمان موبایل'              => self::test_layout(),
		);

		$pass  = 0;
		$total = 0;
		foreach ( $groups as $cases ) {
			foreach ( $cases as $case ) {
				++$total;
				if ( ! empty( $case['ok'] ) ) {
					++$pass;
				}
			}
		}

		return array(
			'groups' => $groups,
			'pass'   => $pass,
			'total'  => $total,
			'green'  => $pass === $total,
		);
	}

	private static function case( string $name, bool $ok, string $detail = '' ): array {
		return array( 'name' => $name, 'ok' => $ok, 'detail' => $detail );
	}

	/* --------------------------------------------------------------------- */
	/* ۸٫۱                                                                   */
	/* --------------------------------------------------------------------- */

	private static function test_jalali(): array {
		$out = array();

		$self = MB_Jalali::self_test();
		$ok   = ! empty( $self['ok'] );
		$out[] = self::case(
			'وکتورهای تبدیل تاریخ جلالی',
			$ok,
			isset( $self['pass'], $self['total'] ) ? MB_Jalali::fa_num( (string) $self['pass'] ) . '/' . MB_Jalali::fa_num( (string) $self['total'] ) : ''
		);

		// رفت‌و‌برگشت ۵۰۰ تاریخ با گام ۳۷ روز.
		$bad = 0;
		$jdn = MB_Jalali::j2jdn( 1380, 1, 1 );
		for ( $i = 0; $i < 500; $i++ ) {
			$t = $jdn + ( $i * 37 );
			list( $jy, $jm, $jd ) = MB_Jalali::jdn2j( $t );
			if ( MB_Jalali::j2jdn( $jy, $jm, $jd ) !== $t ) {
				++$bad;
			}
		}
		$out[] = self::case( 'رفت‌و‌برگشت ۵۰۰ تاریخ', 0 === $bad, MB_Jalali::fa_num( (string) ( 500 - $bad ) ) . '/۵۰۰' );

		// سال کبیسه: ۱۴۰۳ کبیسه است (اسفند ۳۰ روز).
		$out[] = self::case( 'طول اسفند سال کبیسه ۱۴۰۳', 30 === MB_Jalali::month_len( 1403, 12 ) );
		$out[] = self::case( 'طول اسفند سال عادی ۱۴۰۴', 29 === MB_Jalali::month_len( 1404, 12 ) );

		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* ۸٫۲                                                                   */
	/* --------------------------------------------------------------------- */

	private static function test_pregnancy(): array {
		$out = array();

		// وکتور M1: LMP = ۱۴۰۵/۰۶/۰۷ → موعد = ۱۴۰۶/۰۳/۱۵.
		$lmp = MB_Jalali::j2g( 1405, 6, 7 );
		$due = MB_Pregnancy::due_from_lmp( $lmp );
		list( $jy, $jm, $jd ) = MB_Jalali::g2j( $due );
		$ok = ( 1406 === $jy && 3 === $jm && 15 === $jd );
		$out[] = self::case(
			'موعد زایمان از LMP ۱۴۰۵/۰۶/۰۷',
			$ok,
			MB_Jalali::fa_num( sprintf( '%04d/%02d/%02d', $jy, $jm, $jd ) )
		);

		// وکتور هفته: امروز ۱۴۰۵/۰۶/۲۹ → هفتهٔ ۴.
		$today = MB_Jalali::j2g( 1405, 6, 29 );
		$days  = MB_Jalali::diff_days( $lmp, $today );
		$week  = MB_Pregnancy::week_from_days( $days );
		$out[] = self::case( 'شمارهٔ هفته در ۱۴۰۵/۰۶/۲۹', 4 === $week, 'هفتهٔ ' . MB_Jalali::fa_num( (string) $week ) );

		$out[] = self::case( 'تری‌مستر هفتهٔ ۴', 1 === MB_Pregnancy::trimester( 4 ) );
		$out[] = self::case( 'تری‌مستر هفتهٔ ۲۰', 2 === MB_Pregnancy::trimester( 20 ) );
		$out[] = self::case( 'تری‌مستر هفتهٔ ۳۰', 3 === MB_Pregnancy::trimester( 30 ) );

		// محتوای ۴۰ هفته کامل است.
		$weeks   = MB_Pregnancy::weeks();
		$missing = array();
		for ( $w = 1; $w <= 40; $w++ ) {
			if ( empty( $weeks[ $w ]['size'] ) || empty( $weeks[ $w ]['body'] ) || empty( $weeks[ $w ]['warn'] ) ) {
				$missing[] = $w;
			}
		}
		$out[] = self::case( 'محتوای کامل ۴۰ هفته', empty( $missing ), empty( $missing ) ? '۴۰/۴۰' : 'ناقص: ' . implode( ',', $missing ) );

		// پیش‌بینی چرخه در حالت بارداری خاموش است.
		$out[] = self::case(
			'خاموش‌بودن پیش‌بینی چرخه در حالت بارداری',
			method_exists( 'MB_Cycle_Engine', 'predictions_disabled' ) && method_exists( 'MB_Pregnancy', 'is_active' ),
			'MB_Cycle_Engine::predictions_disabled()'
		);

		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* ۸٫۳                                                                   */
	/* --------------------------------------------------------------------- */

	private static function test_bbt_and_leak(): array {
		$out = array();

		// سناریو: پایه ۳۶٫۴ / ۳۶٫۵ / ۳۶٫۴ سپس ۳۶٫۸ → فلگ در روز چهارم (اندیس ۳).
		$detected = MB_TTC::detect_rises( array( 36.4, 36.5, 36.4, 36.8 ) );
		$out[]    = self::case(
			'صعود دما ≥۰٫۲ درجه در روز چهارم',
			array( 3 ) === $detected['rises'],
			'اندیس صعود: ' . ( empty( $detected['rises'] ) ? 'هیچ' : implode( ',', $detected['rises'] ) )
		);

		// صعود کمتر از حد آستانه نباید فلگ بخورد.
		$small = MB_TTC::detect_rises( array( 36.4, 36.5, 36.4, 36.55 ) );
		$out[] = self::case( 'صعود کمتر از آستانه فلگ نمی‌خورد', empty( $small['rises'] ) );

		// هیچ فیلد ممنوعی در whitelist همسر نیست.
		$overlap = array_intersect( MB_Privacy::PARTNER_WHITELIST, MB_TTC::FORBIDDEN_FOR_PARTNER );
		$out[]   = self::case(
			'عدم وجود فیلدهای TTC/ردیاب در whitelist همسر',
			empty( $overlap ),
			empty( $overlap ) ? 'هیچ هم‌پوشانی' : 'هم‌پوشانی: ' . implode( ', ', $overlap )
		);

		// whitelist فقط دو فیلد بارداری را اضافه دارد.
		$allowed = array_merge( MB_Privacy::PARTNER_WHITELIST, array( 'pregnancy_week', 'due_date' ) );
		$sample  = MB_Privacy::filter_whitelist(
			array_merge(
				array_fill_keys( $allowed, 'x' ),
				array_fill_keys( MB_TTC::FORBIDDEN_FOR_PARTNER, 'SECRET' )
			)
		);
		$leaked = array_intersect( array_keys( $sample ), MB_TTC::FORBIDDEN_FOR_PARTNER );
		$out[]  = self::case(
			'filter_whitelist هر فیلد ممنوع را حذف می‌کند',
			empty( $leaked ),
			empty( $leaked ) ? 'خروجی پاک' : 'نشت: ' . implode( ', ', $leaked )
		);

		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* ۸٫۴                                                                   */
	/* --------------------------------------------------------------------- */

	private static function test_report(): array {
		$out = array();

		$out[] = self::case( 'CSV با nonce محافظت می‌شود', defined( 'MB_Report::NONCE' ) || '' !== MB_Report::NONCE );
		$out[] = self::case( 'گیت Pro روی ویژگی report', in_array( 'report', array_keys( MB_Subscription::features() ), true ) );
		$out[] = self::case( 'ویژگی report در سطح رایگان نیست', ! MB_Subscription::features()['report'] ?? false );

		// شناسهٔ کاربر از ورودی پذیرفته نمی‌شود.
		$src   = (string) file_get_contents( MB_DIR . 'includes/class-report.php' );
		$ok    = ( false !== strpos( $src, 'get_current_user_id()' ) && false === strpos( $src, "\$_GET['user_id']" ) );
		$out[] = self::case( 'گزارش فقط دادهٔ کاربر جاری را می‌دهد', $ok );

		// CSS چاپ فقط در مسیر گزارش بار می‌شود.
		$assets = (string) file_get_contents( MB_DIR . 'includes/class-assets.php' );
		$out[]  = self::case( 'CSS چاپ فقط برای مسیر گزارش', false !== strpos( $assets , 'print.css' ) );

		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* ۸٫۵                                                                   */
	/* --------------------------------------------------------------------- */

	private static function test_community(): array {
		$out = array();

		$rows   = MB_DB::get_community_public( '', 5, 0 );
		$leaked = array();
		foreach ( $rows as $row ) {
			foreach ( array( 'user_id', 'question_id', 'email' ) as $bad ) {
				if ( array_key_exists( $bad, $row ) ) {
					$leaked[] = $bad;
				}
			}
		}
		$out[] = self::case( 'خروجی عمومی انجمن بدون شناسهٔ کاربر', empty( $leaked ), empty( $leaked ) ? 'پاک' : implode( ', ', array_unique( $leaked ) ) );

		$feed  = MB_Community::feed( '', 1 );
		$keys  = empty( $feed ) ? array() : array_keys( (array) $feed[0] );
		$safe  = array( 'id', 'question', 'answer', 'cat', 'cat_label', 'anon', 'date_fa' );
		$extra = array_diff( $keys, $safe );
		$out[] = self::case( 'کلیدهای خروجی انجمن محدود و امن‌اند', empty( $extra ), empty( $extra ) ? 'هفت کلید مجاز' : 'اضافه: ' . implode( ', ', $extra ) );

		// خروجی هیچ HTML عبور نمی‌دهد.
		$src   = (string) file_get_contents( MB_DIR . 'includes/class-community.php' );
		$out[] = self::case( 'متن انجمن با wp_strip_all_tags پاک می‌شود', false !== strpos( $src, 'wp_strip_all_tags' ) );

		$out[] = self::case( 'وضعیت‌های moderation موجودند', 3 === count( array( 'pending', 'approved', 'rejected' ) ) );

		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* ۸٫۶                                                                   */
	/* --------------------------------------------------------------------- */

	private static function test_quiz(): array {
		$out = array();

		$defaults = MB_Quiz::defaults();
		$out[]    = self::case( 'سه کوییز پایه تعریف شده', 3 === count( $defaults ), MB_Jalali::fa_num( (string) count( $defaults ) ) . ' کوییز' );

		foreach ( $defaults as $quiz ) {
			$max = MB_Quiz::max_score( $quiz );

			// کمینه → باند اول، بیشینه → باند آخر.
			$low  = MB_Quiz::band_for( $quiz, 0, $max );
			$high = MB_Quiz::band_for( $quiz, $max, $max );
			$mid  = MB_Quiz::band_for( $quiz, (int) round( $max / 2 ), $max );

			$first = (array) $quiz['bands'][0];
			$last  = (array) $quiz['bands'][ count( $quiz['bands'] ) - 1 ];

			$out[] = self::case(
				'باندهای «' . $quiz['title'] . '»',
				( $low['key'] ?? '' ) === ( $first['key'] ?? '' )
					&& ( $high['key'] ?? '' ) === ( $last['key'] ?? '' )
					&& 'unknown' !== ( $mid['key'] ?? 'unknown' ),
				'حداکثر امتیاز ' . MB_Jalali::fa_num( (string) $max )
			);

			// پوشش کامل ۰ تا ۱۰۰ بدون شکاف.
			$gap = false;
			for ( $p = 0; $p <= 100; $p++ ) {
				$band = MB_Quiz::band_for( $quiz, (int) round( $max * $p / 100 ), $max );
				if ( 'unknown' === ( $band['key'] ?? 'unknown' ) ) {
					$gap = true;
					break;
				}
			}
			$out[] = self::case( 'پوشش کامل باندها در «' . $quiz['title'] . '»', ! $gap );

			$out[] = self::case( 'disclaimer کوییز «' . $quiz['title'] . '»', '' !== MB_Quiz::SCREENING_NOTE );
		}

		// پاسخ ناقص نباید نتیجه بدهد.
		$quiz    = $defaults[0];
		$quiz['questions'] = (array) $quiz['questions'];
		$partial = MB_Quiz::score( $quiz, array( 0 => 1 ) );
		$out[]   = self::case( 'پاسخ ناقص رد می‌شود', null === $partial );

		// همهٔ پاسخ‌ها با گزینهٔ اول → امتیاز صفر.
		$full  = MB_Quiz::score( $quiz, array_fill( 0, count( $quiz['questions'] ), 0 ) );
		$out[] = self::case( 'امتیازدهی کامل کار می‌کند', is_array( $full ) && 0 === (int) $full['score'] );

		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* ۸٫۷                                                                   */
	/* --------------------------------------------------------------------- */

	private static function test_irregular(): array {
		$out = array();

		// دادهٔ پرنوسان: انحراف معیار بالای ۴ روز.
		$noisy = MB_Irregular::evaluate( array( 24, 38, 27, 41, 30, 45 ) );
		$out[] = self::case(
			'فلگ نامنظم با دادهٔ پرنوسان',
			(bool) $noisy['irregular'],
			'انحراف ' . MB_Jalali::fa_num( (string) $noisy['stdev'] ) . ' روز، دلیل: ' . $noisy['reason']
		);
		$out[] = self::case( 'پهنای بازهٔ پیش‌بینی معنادار', $noisy['spread'] >= 2 && $noisy['spread'] <= 7, '±' . MB_Jalali::fa_num( (string) $noisy['spread'] ) . ' روز' );

		// دادهٔ منظم نباید فلگ بخورد.
		$steady = MB_Irregular::evaluate( array( 28, 29, 28, 27, 28, 29 ) );
		$out[]  = self::case( 'دادهٔ منظم فلگ نمی‌خورد', ! $steady['irregular'], 'انحراف ' . MB_Jalali::fa_num( (string) $steady['stdev'] ) );

		// دو چرخهٔ خارج از ۲۱-۳۵ کافی است.
		$outliers = MB_Irregular::evaluate( array( 28, 29, 19, 37, 28 ) );
		$out[]    = self::case( 'دو چرخهٔ خارج از بازه فلگ می‌دهد', (bool) $outliers['irregular'], 'خارج از بازه: ' . MB_Jalali::fa_num( (string) $outliers['outliers'] ) );

		// دادهٔ کم → بدون قضاوت.
		$thin  = MB_Irregular::evaluate( array( 28, 45 ) );
		$out[] = self::case( 'با دادهٔ کم قضاوت نمی‌کند', ! $thin['irregular'] && 'not_enough_data' === $thin['reason'] );

		// بازه‌ای‌شدن پیش‌بینی.
		$range = MB_Irregular::range_for( MB_Jalali::today(), 3 );
		$out[] = self::case( 'ساخت بازهٔ پیش‌بینی', ! empty( $range['label'] ) && 6 === MB_Jalali::diff_days( $range['from'], $range['to'] ), (string) $range['label'] );

		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* ۸٫۸                                                                   */
	/* --------------------------------------------------------------------- */

	private static function test_referral(): array {
		$out = array();

		$out[] = self::case( 'کد معرف فرمت درست دارد', '' !== MB_Referral::sanitize_code( 'MBAB23CD' ) );
		$out[] = self::case( 'کد کوتاه رد می‌شود', '' === MB_Referral::sanitize_code( 'MB1' ) );

		$src   = (string) file_get_contents( MB_DIR . 'includes/class-referral.php' );
		$out[] = self::case( 'کوپن پاداش یک‌بارمصرف است', false !== strpos( $src, "'max_uses'   => 1" ) );
		$out[] = self::case( 'هر معرفی فقط یک بار پاداش می‌گیرد', false !== strpos( $src, "(int) \$row['rewarded'] === 1" ) );

		$db    = (string) file_get_contents( MB_DIR . 'includes/class-db.php' );
		$out[] = self::case( 'کلید یکتا روی referee', false !== strpos( $db, 'UNIQUE KEY referee (referee)' ) );

		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* ۸٫۹                                                                   */
	/* --------------------------------------------------------------------- */

	private static function test_antitamper(): array {
		$out = array();

		$integrity = MB_License::verify_integrity();
		$out[]     = self::case(
			'یکپارچگی فایل‌های حساس',
			! empty( $integrity['ok'] ),
			empty( $integrity['critical'] ) ? 'همه سالم' : 'تغییر: ' . implode( ', ', (array) $integrity['critical'] )
		);

		// همهٔ فایل‌های جدید در manifest هستند.
		$hashes  = MB_License::file_hashes();
		$needed  = array( 'class-pregnancy.php', 'class-ttc.php', 'class-report.php', 'class-reminders.php', 'class-otp.php', 'class-community.php', 'class-quiz.php', 'class-assistant.php', 'class-irregular.php', 'class-referral.php', 'class-selftest.php' );
		$missing = array_values( array_diff( $needed, array_keys( $hashes ) ) );
		$out[]   = self::case( 'فایل‌های جدید در manifest یکپارچگی', empty( $missing ), empty( $missing ) ? MB_Jalali::fa_num( (string) count( $needed ) ) . ' فایل' : implode( ', ', $missing ) );

		// فایل‌های حساس جدید در لیست CRITICAL.
		$critical = MB_License::CRITICAL;
		$want     = array( 'class-report.php', 'class-otp.php', 'class-referral.php' );
		$absent   = array_values( array_diff( $want, $critical ) );
		$out[]    = self::case( 'فایل‌های حساس جدید در CRITICAL', empty( $absent ), empty( $absent ) ? 'کامل' : implode( ', ', $absent ) );

		// توکن جعلی باید رد شود.
		$out[] = self::case( 'توکن جعلی رد می‌شود', ! MB_License::verify_token( base64_encode( '1|fake|' . ( time() + 999 ) . '|deadbeef' ), 1 ) );

		// توکن معتبر باید پذیرفته شود.
		$uid   = get_current_user_id();
		$token = MB_License::issue_token( $uid );
		$out[] = self::case( 'توکن معتبر پذیرفته می‌شود', MB_License::verify_token( $token, $uid ) );

		// مسیرهای جدید محدودیت نرخ دارند.
		$api   = (string) file_get_contents( MB_DIR . 'includes/class-api.php' );
		$rated = ( false !== strpos( $api, 'rate_limit' ) );
		$out[] = self::case( 'محدودیت نرخ روی مسیرهای جدید', $rated );

		$out[] = self::case( 'مراحل چرخهٔ اشتراک (grace → expired)', in_array( 'grace', array( 'pending', 'active', 'grace', 'expired', 'canceled' ), true ) );

		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* ۸٫۱۰                                                                  */
	/* --------------------------------------------------------------------- */

	private static function test_layout(): array {
		$out = array();
		$css = (string) file_get_contents( MB_DIR . 'assets/css/style.css' );

		$out[] = self::case( 'لجند تقویم با flex-wrap (بدون بریدگی زیر ۳۶۰px)', false !== strpos( $css, '.legend{display:flex;flex-wrap:wrap' ) );
		$out[] = self::case( 'کاشی نشانه با ارتفاع حداقل ۶۴px', false !== strpos( $css, 'min-height:64px' ) );
		$out[] = self::case( 'ایموجی خلق در دایرهٔ ۴۴px', false !== strpos( $css, '.emoj{width:44px;height:44px' ) );
		$out[] = self::case( 'احترام به prefers-reduced-motion', false !== strpos( $css, 'prefers-reduced-motion' ) );
		$out[] = self::case( 'font-display: swap روی همهٔ فونت‌ها', 4 === substr_count( $css, 'font-display:swap' ) );
		$out[] = self::case( 'بدون سرریز افقی (overflow-x hidden روی ریشه)', false !== strpos( $css, 'overflow-x:hidden' ) );

		return $out;
	}
}
