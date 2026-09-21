<?php
/**
 * M2 — حالت تلاش برای بارداری (TTC) — نسخهٔ پیشرفته.
 *
 * دمای پایهٔ بدن (BBT)، کیفیت مخاط دهانهٔ رحم، و تأیید تخمک‌گذاری.
 *
 * قاعدهٔ حریم خصوصی: هیچ‌یک از فیلدهای این ماژول (bbt, mucus, sex) در هیچ
 * شرایطی به خروجی همسر نمی‌رود. لیست FORBIDDEN_FOR_PARTNER پایین همین کلاس
 * مرجع تست واحد MB_Selftest است.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_TTC {

	/** حد صعود دما برای تأیید تخمک‌گذاری (درجهٔ سلسیوس). */
	const RISE_THRESHOLD = 0.2;

	/** تعداد روزهای پایه برای میانگین. */
	const BASELINE_DAYS = 3;

	/** فیلدهایی که هرگز و تحت هیچ شرطی به همسر نمی‌رسند. */
	const FORBIDDEN_FOR_PARTNER = array(
		'bbt',
		'mucus',
		'sex',
		'weight_kg',
		'sleep_h',
		'water_cups',
		'exercise_min',
		'meds',
		'note_private',
		'mood',
		'pain',
		'symptoms',
		'ovulation_confirmed',
	);

	/* --------------------------------------------------------------------- */
	/* تحلیل                                                                 */
	/* --------------------------------------------------------------------- */

	/**
	 * سری دمای پایه و تأیید تخمک‌گذاری.
	 *
	 * الگوریتم: برای هر روزی که دما ثبت شده، میانگین سه ثبت دمایی قبلی محاسبه
	 * می‌شود. اگر دمای امروز حداقل ۰٫۲ درجه بالاتر از آن میانگین باشد، همان روز
	 * به‌عنوان روز صعود (تأیید تخمک‌گذاری) علامت می‌خورد.
	 *
	 * @return array{points:array,confirmed:array,coverage:int,last_rise:?string,baseline:?float}
	 */
	public static function analyze( int $user_id, int $days = 40 ): array {
		$logs = MB_DB::recent_logs( $user_id, $days );

		$points = array();
		foreach ( $logs as $date => $row ) {
			if ( null === $row['bbt'] || '' === $row['bbt'] ) {
				continue;
			}
			$points[] = array(
				'date'   => (string) $date,
				'jalali' => MB_Jalali::format_fa( (string) $date, 'd' ),
				'bbt'    => (float) $row['bbt'],
				'mucus'  => (string) ( $row['mucus'] ?? 'none' ),
				'rise'   => false,
			);
		}

		$detected  = self::detect_rises( array_column( $points, 'bbt' ) );
		$confirmed = array();
		$baseline  = $detected['baseline'];

		foreach ( $detected['rises'] as $i ) {
			$points[ $i ]['rise'] = true;
			$confirmed[]          = $points[ $i ]['date'];
		}
		$count = count( $points );

		// فلگ را در دیتابیس هم ثبت می‌کنیم تا گزارش پزشک و ادمین به آن دسترسی داشته باشند.
		foreach ( $points as $point ) {
			$stored = (int) ( $logs[ $point['date'] ]['ovulation_confirmed'] ?? 0 );
			$should = $point['rise'] ? 1 : 0;
			if ( $stored !== $should ) {
				MB_DB::set_ovulation_confirmed( $user_id, $point['date'], (bool) $should );
			}
		}

		return array(
			'points'    => $points,
			'confirmed' => $confirmed,
			'coverage'  => $count,
			'last_rise' => empty( $confirmed ) ? null : (string) end( $confirmed ),
			'baseline'  => $baseline,
		);
	}

	/** آیا در چرخهٔ جاری تخمک‌گذاری تأیید شده است؟ */
	public static function ovulation_confirmed_this_cycle( int $user_id ): ?string {
		$start = MB_Cycle_Engine::cycle_start_for( $user_id, MB_Jalali::today() );
		if ( ! $start ) {
			return null;
		}
		$analysis = self::analyze( $user_id, 45 );
		foreach ( array_reverse( $analysis['confirmed'] ) as $date ) {
			if ( MB_Jalali::diff_days( $start, (string) $date ) >= 0 ) {
				return (string) $date;
			}
		}
		return null;
	}

	/**
	 * تشخیص خالص روزهای صعود دما. ورودی فقط آرایهٔ مرتبِ دماها است، بنابراین
	 * این تابع بدون دیتابیس قابل تست است (تست پذیرش ۸٫۳ همین را صدا می‌زند).
	 *
	 * @param array<int,float> $temps دماها به ترتیب زمانی.
	 * @return array{rises:array<int,int>,baseline:?float}
	 */
	public static function detect_rises( array $temps ): array {
		$temps    = array_values( array_map( 'floatval', $temps ) );
		$count    = count( $temps );
		$rises    = array();
		$baseline = null;

		for ( $i = self::BASELINE_DAYS; $i < $count; $i++ ) {
			$sum = 0.0;
			for ( $k = 1; $k <= self::BASELINE_DAYS; $k++ ) {
				$sum += $temps[ $i - $k ];
			}
			$mean = $sum / self::BASELINE_DAYS;
			// گردکردن به دو رقم تا خطای ممیز شناور فلگ را از بین نبرد.
			if ( round( $temps[ $i ] - $mean, 2 ) >= self::RISE_THRESHOLD ) {
				$rises[]  = $i;
				$baseline = round( $mean, 2 );
			}
		}

		return array( 'rises' => $rises, 'baseline' => $baseline );
	}

	/* --------------------------------------------------------------------- */
	/* نمودار SVG (بدون هیچ کتابخانهٔ بیرونی)                                */
	/* --------------------------------------------------------------------- */

	/**
	 * نمودار خطی دمای پایه. مقیاس عمودی خودکار با حاشیهٔ ۰٫۲ درجه.
	 */
	public static function chart_svg( array $points ): string {
		$points = array_values( $points );
		$n      = count( $points );
		if ( $n < 2 ) {
			return '<p class="small">برای رسم نمودار حداقل دو روز ثبت دما لازم است.</p>';
		}

		$w   = 320;
		$h   = 160;
		$pl  = 34;  // حاشیهٔ راست/چپ برای برچسب دما.
		$pb  = 22;
		$pt  = 12;
		$iw  = $w - $pl - 10;
		$ih  = $h - $pb - $pt;

		$temps = array_column( $points, 'bbt' );
		$min   = min( $temps ) - 0.2;
		$max   = max( $temps ) + 0.2;
		$span  = max( 0.4, $max - $min );

		$x = static function ( int $i ) use ( $iw, $n, $pl ): float {
			return round( $pl + ( $iw * ( $n > 1 ? $i / ( $n - 1 ) : 0 ) ), 2 );
		};
		$y = static function ( float $t ) use ( $ih, $pt, $min, $span ): float {
			return round( $pt + $ih - ( ( ( $t - $min ) / $span ) * $ih ), 2 );
		};

		$line = '';
		$dots = '';
		foreach ( $points as $i => $point ) {
			$px    = $x( (int) $i );
			$py    = $y( (float) $point['bbt'] );
			$line .= ( '' === $line ? 'M' : ' L' ) . $px . ' ' . $py;
			$cls   = ! empty( $point['rise'] ) ? 'bbt-dot rise' : 'bbt-dot';
			$dots .= '<circle class="' . $cls . '" cx="' . $px . '" cy="' . $py . '" r="' . ( ! empty( $point['rise'] ) ? '4.2' : '2.6' ) . '"><title>'
				. esc_html( $point['jalali'] . ' — ' . MB_Jalali::fa_num( number_format( (float) $point['bbt'], 1 ) ) . '°' )
				. '</title></circle>';
		}

		// خطوط راهنمای افقی و برچسب دما.
		$grid = '';
		for ( $g = 0; $g <= 3; $g++ ) {
			$t   = $min + ( $span * $g / 3 );
			$gy  = $y( $t );
			$grid .= '<line class="bbt-grid" x1="' . $pl . '" y1="' . $gy . '" x2="' . ( $w - 10 ) . '" y2="' . $gy . '"/>';
			$grid .= '<text class="bbt-lbl" x="' . ( $pl - 6 ) . '" y="' . ( $gy + 3.4 ) . '" text-anchor="end">'
				. esc_html( MB_Jalali::fa_num( number_format( $t, 1 ) ) ) . '</text>';
		}

		$first = esc_html( (string) $points[0]['jalali'] );
		$last  = esc_html( (string) $points[ $n - 1 ]['jalali'] );

		return '<div class="bbt-wrap"><svg class="bbt-chart" viewBox="0 0 ' . $w . ' ' . $h . '" role="img" aria-label="نمودار دمای پایهٔ بدن" preserveAspectRatio="none">'
			. $grid
			. '<path class="bbt-line" d="' . esc_attr( $line ) . '" fill="none"/>'
			. $dots
			. '<text class="bbt-lbl" x="' . $pl . '" y="' . ( $h - 6 ) . '">' . $first . '</text>'
			. '<text class="bbt-lbl" x="' . ( $w - 10 ) . '" y="' . ( $h - 6 ) . '" text-anchor="end">' . $last . '</text>'
			. '</svg></div>';
	}

	/* --------------------------------------------------------------------- */
	/* متن‌های کمکی                                                          */
	/* --------------------------------------------------------------------- */

	public static function mucus_hint( string $mucus ): string {
		$map = array(
			'none'     => 'ثبت مخاط به دقت پیش‌بینی کمک می‌کند.',
			'dry'      => 'مخاط خشک معمولاً دور از پنجرهٔ باروری است.',
			'sticky'   => 'مخاط چسبنده اغلب اول یا آخر پنجرهٔ باروری دیده می‌شود.',
			'creamy'   => 'مخاط کرمی نشانهٔ نزدیک‌شدن به پنجرهٔ باروری است.',
			'eggwhite' => 'مخاط شبیه سفیدهٔ تخم‌مرغ معمولاً بارورترین حالت است.',
			'watery'   => 'مخاط آبکی هم از نشانه‌های نزدیکی به تخمک‌گذاری است.',
		);
		return $map[ $mucus ] ?? $map['none'];
	}

	public static function summary_text( array $analysis ): string {
		if ( empty( $analysis['points'] ) ) {
			return 'هنوز دمای پایه ثبت نکرده‌ای. هر روز صبح پیش از بلندشدن از تخت اندازه بگیر.';
		}
		if ( empty( $analysis['confirmed'] ) ) {
			return 'تا این‌جا صعود دمایی معناداری دیده نشده. ثبت روزانه را ادامه بده تا الگو مشخص شود.';
		}
		$last = MB_Jalali::format_fa( (string) $analysis['last_rise'], 'long' );
		return 'آخرین صعود دمایی در ' . $last . ' دیده شد؛ این نشانهٔ احتمالی تخمک‌گذاری است.';
	}
}
