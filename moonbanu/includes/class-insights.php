<?php
/**
 * N7 — تحلیل دقیق‌تر (همه Pro و کاملاً سمت سرور).
 *
 *  - همبستگی نشانه × فاز: سه مورد برتر با درصد.
 *  - روند طول چرخه: شیب خط رگرسیون شش چرخهٔ آخر.
 *  - هشدار ناهنجاری: تغییر ناگهانی طول ≥ ۷ روز، یا خونریزی ≥ ۷ روز.
 *  - پنجرهٔ PMS شخصی.
 *  - نمودار خواب و انرژی از لاگ‌های روزانه.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Insights {

	/** آستانهٔ ناهنجاری: تغییر طول چرخه و روزهای خونریزی. */
	const ANOMALY_DAYS = 7;

	/** کمینهٔ ثبت لازم برای اعتماد به همبستگی. */
	const MIN_LOGS = 12;

	/* --------------------------------------------------------------------- */
	/* همبستگی نشانه × فاز                                                   */
	/* --------------------------------------------------------------------- */

	/**
	 * سه جفت «نشانه در فاز» با بالاترین درصد.
	 *
	 * درصد = تعداد روزهایی که نشانه در آن فاز ثبت شده،
	 *        تقسیم بر کل روزهای ثبت‌شده در همان فاز.
	 *
	 * @return array<int,array{symptom:string,label:string,phase:string,phase_label:string,percent:int,days:int,of:int}>
	 */
	public static function symptom_phase_top( int $user_id, int $limit = 3, int $days = 180 ): array {
		$to   = MB_Jalali::today();
		$from = MB_Jalali::add_days( $to, -1 * max( 30, min( 400, $days ) ) );
		$logs = MB_DB::get_logs_range( $user_id, $from, $to );
		if ( count( $logs ) < self::MIN_LOGS ) {
			return array();
		}

		$map        = MB_DB::symptom_map();
		$phase_days = array();
		$pairs      = array();

		foreach ( $logs as $date => $log ) {
			$phase = MB_Cycle_Engine::phase_of( $user_id, (string) $date );
			$phase = self::group_phase( $phase );
			if ( '' === $phase ) {
				continue;
			}
			$phase_days[ $phase ] = ( $phase_days[ $phase ] ?? 0 ) + 1;

			foreach ( (array) $log['symptoms'] as $symptom ) {
				$symptom = (string) $symptom;
				if ( ! isset( $map[ $symptom ] ) ) {
					continue;
				}
				$key           = $phase . '|' . $symptom;
				$pairs[ $key ] = ( $pairs[ $key ] ?? 0 ) + 1;
			}
		}

		$out = array();
		foreach ( $pairs as $key => $count ) {
			list( $phase, $symptom ) = explode( '|', $key, 2 );
			$of = (int) ( $phase_days[ $phase ] ?? 0 );
			// زیر سه روز ثبت در یک فاز، درصد گمراه‌کننده است.
			if ( $of < 3 ) {
				continue;
			}
			$out[] = array(
				'symptom'     => $symptom,
				'label'       => (string) $map[ $symptom ]['label'],
				'icon'        => (string) $map[ $symptom ]['icon'],
				'phase'       => $phase,
				'phase_label' => self::phase_group_label( $phase ),
				'percent'     => (int) round( ( $count / $of ) * 100 ),
				'days'        => (int) $count,
				'of'          => $of,
			);
		}

		usort(
			$out,
			static function ( $a, $b ) {
				if ( $a['percent'] === $b['percent'] ) {
					return $b['days'] <=> $a['days'];
				}
				return $b['percent'] <=> $a['percent'];
			}
		);

		return array_slice( $out, 0, max( 1, $limit ) );
	}

	/** فازهای ریز به چهار گروه خوانا تبدیل می‌شوند. */
	private static function group_phase( string $phase ): string {
		$map = array(
			'period'     => 'period',
			'follicular' => 'follicular',
			'fertile'    => 'fertile',
			'ovulation'  => 'fertile',
			'luteal'     => 'luteal',
			'pms'        => 'pms',
		);
		return $map[ $phase ] ?? '';
	}

	public static function phase_group_label( string $phase ): string {
		$map = array(
			'period'     => 'روزهای قاعدگی',
			'follicular' => 'فاز فولیکولی',
			'fertile'    => 'پنجرهٔ باروری',
			'luteal'     => 'فاز لوتئال',
			'pms'        => 'روزهای پیش از قاعدگی',
		);
		return $map[ $phase ] ?? $phase;
	}

	/* --------------------------------------------------------------------- */
	/* روند طول چرخه                                                         */
	/* --------------------------------------------------------------------- */

	/**
	 * شیب خط رگرسیون شش طول آخر (روز به ازای هر چرخه).
	 *
	 * @return array{ok:bool,slope:float,direction:string,label:string,lengths:array,avg:int}
	 */
	public static function length_trend( int $user_id, int $limit = 6 ): array {
		$lengths = array_values( array_map( 'intval', (array) MB_Cycle_Engine::recent_lengths( $user_id, $limit ) ) );
		$n       = count( $lengths );
		if ( $n < 3 ) {
			return array( 'ok' => false, 'slope' => 0.0, 'direction' => 'flat', 'label' => 'برای دیدن روند، دست‌کم سه چرخهٔ کامل لازم است.', 'lengths' => $lengths, 'avg' => 0 );
		}

		// رگرسیون خطی سادهٔ کم‌ترین مربعات روی اندیس ۰..n-1.
		$sum_x  = 0.0;
		$sum_y  = 0.0;
		$sum_xy = 0.0;
		$sum_xx = 0.0;
		foreach ( $lengths as $i => $value ) {
			$sum_x  += $i;
			$sum_y  += $value;
			$sum_xy += $i * $value;
			$sum_xx += $i * $i;
		}
		$denom = ( $n * $sum_xx ) - ( $sum_x * $sum_x );
		$slope = 0.0 === $denom ? 0.0 : round( ( ( $n * $sum_xy ) - ( $sum_x * $sum_y ) ) / $denom, 2 );

		if ( $slope >= 0.5 ) {
			$direction = 'up';
			$label     = 'چرخه‌هایت رو به بلندتر شدن است: به‌طور میانگین ' . MB_Jalali::fa_num( abs( $slope ) ) . ' روز در هر چرخه.';
		} elseif ( $slope <= -0.5 ) {
			$direction = 'down';
			$label     = 'چرخه‌هایت رو به کوتاه‌تر شدن است: به‌طور میانگین ' . MB_Jalali::fa_num( abs( $slope ) ) . ' روز در هر چرخه.';
		} else {
			$direction = 'flat';
			$label     = 'طول چرخه‌هایت پایدار است؛ روند معناداری دیده نمی‌شود.';
		}

		return array(
			'ok'        => true,
			'slope'     => $slope,
			'direction' => $direction,
			'label'     => $label,
			'lengths'   => $lengths,
			'avg'       => (int) round( $sum_y / $n ),
		);
	}

	/* --------------------------------------------------------------------- */
	/* هشدار ناهنجاری                                                        */
	/* --------------------------------------------------------------------- */

	/**
	 * دو قاعده: جهش طول چرخه ≥ ۷ روز، خونریزی پیوستهٔ ≥ ۷ روز.
	 *
	 * @return array<int,array{key:string,title:string,text:string,cat:string}>
	 */
	public static function anomalies( int $user_id ): array {
		$out = array();

		// ۱) جهش طول چرخه.
		$lengths = array_values( array_map( 'intval', (array) MB_Cycle_Engine::recent_lengths( $user_id, 6 ) ) );
		$count   = count( $lengths );
		if ( $count >= 2 ) {
			$last = (int) $lengths[ $count - 1 ];
			$prev = (int) $lengths[ $count - 2 ];
			$jump = abs( $last - $prev );
			if ( $jump >= self::ANOMALY_DAYS ) {
				$out[] = array(
					'key'   => 'length_jump',
					'title' => 'تغییر ناگهانی طول چرخه',
					'text'  => 'طول آخرین چرخه‌ات ' . MB_Jalali::fa_num( $jump ) . ' روز با چرخهٔ پیش از آن تفاوت دارد ('
						. MB_Jalali::fa_num( $prev ) . ' به ' . MB_Jalali::fa_num( $last )
						. '). یک جهش این‌قدر بزرگ می‌تواند دلیل گذرا داشته باشد (استرس، سفر، بیماری) ولی اگر تکرار شد ارزش بررسی دارد.',
					'cat'   => 'warning',
				);
			}
		}

		// ۲) خونریزی پیوستهٔ طولانی.
		$streak = self::longest_bleeding_streak( $user_id, 120 );
		if ( $streak['days'] >= self::ANOMALY_DAYS ) {
			$out[] = array(
				'key'   => 'long_bleeding',
				'title' => 'خونریزی طولانی',
				'text'  => 'یک دورهٔ ' . MB_Jalali::fa_num( $streak['days'] ) . ' روزهٔ خونریزی پیوسته ثبت شده است'
					. ( '' !== $streak['from'] ? ' (از ' . MB_Jalali::format_fa( $streak['from'], 'long' ) . ')' : '' )
					. '. خونریزی بیش از هفت روز یکی از نشانه‌هایی است که باید با پزشک مطرح شود.',
				'cat'   => 'warning',
			);
		}

		return $out;
	}

	/** بلندترین رشتهٔ روزهای خونریزی واقعی (light/med/heavy) در بازهٔ اخیر. */
	public static function longest_bleeding_streak( int $user_id, int $days = 120 ): array {
		$to   = MB_Jalali::today();
		$from = MB_Jalali::add_days( $to, -1 * max( 30, min( 400, $days ) ) );
		$logs = MB_DB::get_logs_range( $user_id, $from, $to );

		$best     = array( 'days' => 0, 'from' => '' );
		$current  = 0;
		$start    = '';
		$cursor   = $from;

		while ( strtotime( $cursor ) <= strtotime( $to ) ) {
			$bleeding = isset( $logs[ $cursor ] ) ? (string) $logs[ $cursor ]['bleeding'] : 'none';
			if ( in_array( $bleeding, array( 'light', 'med', 'heavy' ), true ) ) {
				if ( 0 === $current ) {
					$start = $cursor;
				}
				++$current;
				if ( $current > $best['days'] ) {
					$best = array( 'days' => $current, 'from' => $start );
				}
			} else {
				$current = 0;
			}
			$cursor = MB_Jalali::add_days( $cursor, 1 );
		}

		return $best;
	}

	/* --------------------------------------------------------------------- */
	/* پنجرهٔ PMS شخصی                                                       */
	/* --------------------------------------------------------------------- */

	/**
	 * پنجرهٔ شخصی PMS بر پایهٔ روزی که نشانه‌های PMS واقعاً شروع می‌شوند.
	 *
	 * @return array{start_day:int,from:string,to:string,range_fa:string,personal:bool}
	 */
	public static function pms_window( int $user_id ): array {
		$start   = (int) MB_Cycle_Engine::personal_pms_start( $user_id );
		$range   = MB_Cycle_Engine::pms_range( $user_id );
		$default = (int) MB_Cycle_Engine::cycle_len( $user_id ) - (int) MB_Cycle_Engine::luteal_len( $user_id );

		return array(
			'start_day' => $start,
			'from'      => (string) ( $range['from'] ?? '' ),
			'to'        => (string) ( $range['to'] ?? '' ),
			'range_fa'  => ! empty( $range['from'] ) && ! empty( $range['to'] ) ? MB_Jalali::range_fa( (string) $range['from'], (string) $range['to'] ) : '',
			'personal'  => $start !== $default,
		);
	}

	/* --------------------------------------------------------------------- */
	/* نمودار خواب و انرژی                                                   */
	/* --------------------------------------------------------------------- */

	/**
	 * سری روزانهٔ خواب (ساعت) و انرژی (۰ تا ۱۰۰) برای نمودار.
	 *
	 * @return array{points:array,has_sleep:bool,avg_sleep:?float,avg_energy:?int}
	 */
	public static function sleep_energy( int $user_id, int $days = 30 ): array {
		$days = max( 7, min( 90, $days ) );
		$to   = MB_Jalali::today();
		$from = MB_Jalali::add_days( $to, -1 * ( $days - 1 ) );
		$logs = MB_DB::get_logs_range( $user_id, $from, $to );

		$points     = array();
		$sleep_vals = array();
		$energy_sum = 0;
		$energy_n   = 0;
		$cursor     = $from;

		while ( strtotime( $cursor ) <= strtotime( $to ) ) {
			$log    = $logs[ $cursor ] ?? null;
			$sleep  = ( $log && null !== $log['sleep_h'] ) ? (float) $log['sleep_h'] : null;
			$energy = MB_Cycle_Engine::energy_level( $user_id, $cursor );

			if ( null !== $sleep ) {
				$sleep_vals[] = $sleep;
			}
			$energy_sum += (int) $energy;
			++$energy_n;

			$points[] = array(
				'date'    => $cursor,
				'day_fa'  => MB_Jalali::format_fa( $cursor, 'd' ),
				'sleep'   => $sleep,
				// ارتفاع میله: ۹ ساعت = ۱۰۰٪.
				'sleep_pct' => null === $sleep ? null : (int) max( 0, min( 100, round( ( $sleep / 9 ) * 100 ) ) ),
				'energy'  => (int) $energy,
				'phase'   => MB_Cycle_Engine::phase_of( $user_id, $cursor ),
			);
			$cursor = MB_Jalali::add_days( $cursor, 1 );
		}

		return array(
			'points'     => $points,
			'has_sleep'  => ! empty( $sleep_vals ),
			'avg_sleep'  => empty( $sleep_vals ) ? null : round( array_sum( $sleep_vals ) / count( $sleep_vals ), 1 ),
			'avg_energy' => $energy_n > 0 ? (int) round( $energy_sum / $energy_n ) : null,
		);
	}

	/* --------------------------------------------------------------------- */
	/* بستهٔ کامل برای صفحهٔ تحلیل                                            */
	/* --------------------------------------------------------------------- */

	public static function bundle( int $user_id ): array {
		return array(
			'correlations' => self::symptom_phase_top( $user_id, 3 ),
			'trend'        => self::length_trend( $user_id, 6 ),
			'anomalies'    => self::anomalies( $user_id ),
			'pms_window'   => self::pms_window( $user_id ),
			'sleep_energy' => self::sleep_energy( $user_id, 30 ),
			'logs_count'   => MB_DB::count_logs( $user_id ),
			'min_logs'     => self::MIN_LOGS,
		);
	}
}
