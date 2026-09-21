<?php
/**
 * موتور چرخه ماه‌بانو: فازها، پیش‌بینی‌ها، دقت و الگوی شخصی.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Cycle_Engine {

	const LUTEAL_FIXED = 14;

	/* --------------------------------------------------------------------- */
	/* پارامترهای پایه                                                       */
	/* --------------------------------------------------------------------- */

	/** طول چرخه مؤثر: میانه ۳ تا ۶ چرخه آخر با clamp 21..35. */
	public static function cycle_len( int $user_id ): int {
		$profile = MB_DB::get_profile( $user_id );
		$lengths = self::recent_lengths( $user_id, 6 );
		if ( count( $lengths ) >= 3 ) {
			$median = self::median( array_slice( $lengths, -6 ) );
			return (int) max( 21, min( 35, (int) round( $median ) ) );
		}
		return (int) max( 21, min( 35, (int) $profile['cycle_len'] ) );
	}

	public static function period_len( int $user_id ): int {
		$profile = MB_DB::get_profile( $user_id );
		return (int) max( 2, min( 7, (int) $profile['period_len'] ) );
	}

	public static function luteal_len( int $user_id ): int {
		$profile = MB_DB::get_profile( $user_id );
		$len     = (int) $profile['luteal_len'];
		return $len > 0 ? $len : self::LUTEAL_FIXED;
	}

	/** طول چرخه‌های کامل ثبت‌شده (قدیمی → جدید). */
	public static function recent_lengths( int $user_id, int $limit = 6 ): array {
		return array_map(
			static function ( $row ) {
				return (int) $row['length'];
			},
			self::recent_length_rows( $user_id, $limit )
		);
	}

	/**
	 * طول چرخه‌ها همراه با تاریخ شروع هر چرخه (قدیمی → جدید).
	 * برچسب نمودار تحلیل از همین می‌آید تا با ستون‌ها جابه‌جا نشود.
	 *
	 * @return array<int,array{start:string,length:int}>
	 */
	public static function recent_length_rows( int $user_id, int $limit = 6 ): array {
		$cycles = MB_DB::get_cycles( $user_id, $limit + 2 );
		$out    = array();
		$count  = count( $cycles );
		for ( $i = 0; $i < $count - 1; $i++ ) {
			$len = MB_Jalali::diff_days( $cycles[ $i ]['start_date'], $cycles[ $i + 1 ]['start_date'] );
			if ( $len >= 15 && $len <= 60 ) {
				$out[] = array(
					'start'  => (string) $cycles[ $i ]['start_date'],
					'length' => (int) $len,
				);
			}
		}
		return array_slice( $out, -$limit );
	}

	/** لنگر چرخه: تاریخ شروع آخرین قاعدگی ثبت‌شده. */
	public static function anchor( int $user_id ): ?string {
		$profile = MB_DB::get_profile( $user_id );
		if ( ! empty( $profile['last_period_start'] ) ) {
			return (string) $profile['last_period_start'];
		}
		return MB_DB::last_cycle_start( $user_id );
	}

	/** شروع چرخه‌ای که تاریخ داده‌شده در آن قرار دارد. */
	public static function cycle_start_for( int $user_id, string $date ): ?string {
		$anchor = self::anchor( $user_id );
		if ( ! $anchor ) {
			return null;
		}
		$len   = self::cycle_len( $user_id );
		$diff  = MB_Jalali::diff_days( $anchor, $date );
		$steps = (int) floor( $diff / $len );

		// اگر چرخه واقعی ثبت‌شده‌ای نزدیک‌تر بود، همان ترجیح دارد.
		if ( $diff >= 0 && $diff < $len ) {
			return $anchor;
		}
		if ( $diff < 0 ) {
			foreach ( array_reverse( MB_DB::get_cycles( $user_id, 24 ) ) as $c ) {
				if ( MB_Jalali::diff_days( $c['start_date'], $date ) >= 0 && MB_Jalali::diff_days( $c['start_date'], $date ) < $len ) {
					return (string) $c['start_date'];
				}
			}
		}
		return MB_Jalali::add_days( $anchor, $steps * $len );
	}

	/** روز چرخه (۱-based). */
	public static function day_index( int $user_id, string $date ): ?int {
		$start = self::cycle_start_for( $user_id, $date );
		if ( ! $start ) {
			return null;
		}
		return MB_Jalali::diff_days( $start, $date ) + 1;
	}

	/* --------------------------------------------------------------------- */
	/* بازه‌ها                                                               */
	/* --------------------------------------------------------------------- */

	public static function period_range( int $user_id, ?string $date = null ): array {
		$date  = $date ?: MB_Jalali::today();
		$start = self::cycle_start_for( $user_id, $date );
		if ( ! $start ) {
			return array( 'from' => null, 'to' => null );
		}
		return array(
			'from' => $start,
			'to'   => MB_Jalali::add_days( $start, self::period_len( $user_id ) - 1 ),
		);
	}

	public static function ovulation_day( int $user_id ): int {
		$len  = self::cycle_len( $user_id );
		$ovul = $len - self::luteal_len( $user_id );
		// تخمک‌گذاری هرگز داخل روزهای خونریزی یا بیرون چرخه نمی‌افتد.
		return (int) max( self::period_len( $user_id ) + 1, min( $len - 1, max( 7, $ovul ) ) );
	}

	public static function ovulation_date( int $user_id, ?string $date = null ): ?string {
		$start = self::cycle_start_for( $user_id, $date ?: MB_Jalali::today() );
		if ( ! $start ) {
			return null;
		}
		return MB_Jalali::add_days( $start, self::ovulation_day( $user_id ) - 1 );
	}

	public static function fertile_range( int $user_id, ?string $date = null ): array {
		$ovul = self::ovulation_date( $user_id, $date );
		if ( ! $ovul ) {
			return array( 'from' => null, 'to' => null );
		}
		return array(
			'from' => MB_Jalali::add_days( $ovul, -5 ),
			'to'   => MB_Jalali::add_days( $ovul, 1 ),
		);
	}

	public static function pms_range( int $user_id, ?string $date = null ): array {
		$start = self::cycle_start_for( $user_id, $date ?: MB_Jalali::today() );
		if ( ! $start ) {
			return array( 'from' => null, 'to' => null );
		}
		$len       = self::cycle_len( $user_id );
		$pms_start = self::personal_pms_start( $user_id );
		return array(
			'from' => MB_Jalali::add_days( $start, $pms_start - 1 ),
			'to'   => MB_Jalali::add_days( $start, $len - 1 ),
		);
	}

	/** روز شروع PMS در چرخه: پیش‌فرض cycleLen−7+1 با شخصی‌سازی از ۳ چرخه آخر. */
	public static function personal_pms_start( int $user_id ): int {
		$len     = self::cycle_len( $user_id );
		$default = max( 2, $len - 7 + 1 );

		$cycles = MB_DB::get_cycles( $user_id, 4 );
		if ( count( $cycles ) < 2 ) {
			return $default;
		}
		$firsts = array();
		$count  = count( $cycles );
		for ( $i = 0; $i < $count - 1; $i++ ) {
			$from = $cycles[ $i ]['start_date'];
			$to   = MB_Jalali::add_days( $cycles[ $i + 1 ]['start_date'], -1 );
			$logs = MB_DB::get_logs_range( $user_id, $from, $to );
			$best = null;
			foreach ( $logs as $day => $log ) {
				$idx = MB_Jalali::diff_days( $from, $day ) + 1;
				if ( $idx <= (int) floor( $len / 2 ) ) {
					continue; // نشانه‌های نیمه اول چرخه مربوط به PMS نیست.
				}
				$has = ! empty( $log['symptoms'] ) || (int) $log['pain'] >= 2 || ( null !== $log['mood'] && (int) $log['mood'] <= 2 );
				if ( $has && ( null === $best || $idx < $best ) ) {
					$best = $idx;
				}
			}
			if ( null !== $best ) {
				$firsts[] = $best;
			}
		}
		if ( count( $firsts ) < 2 ) {
			return $default;
		}
		$avg = (int) round( array_sum( $firsts ) / count( $firsts ) );
		return max( (int) floor( $len / 2 ) + 1, min( $len, $avg ) );
	}

	/**
	 * M1 — در حالت بارداری همهٔ پیش‌بینی‌های چرخه خاموش می‌شوند.
	 * هر مصرف‌کنندهٔ پیش‌بینی باید اول این را بپرسد.
	 */
	public static function predictions_disabled( int $user_id ): bool {
		return MB_Pregnancy::is_active( $user_id );
	}

	/**
	 * M10 — نمایش پیش‌بینی: تاریخ تک‌نقطه‌ای برای چرخهٔ منظم، بازه برای نامنظم،
	 * و «خاموش» در حالت بارداری.
	 *
	 * @return array{mode:string,date:?string,label:string,from:?string,to:?string,spread:int}
	 */
	public static function prediction_display( int $user_id, ?string $date = null ): array {
		if ( self::predictions_disabled( $user_id ) ) {
			return array( 'mode' => 'off', 'date' => null, 'label' => 'در حالت بارداری پیش‌بینی چرخه خاموش است.', 'from' => null, 'to' => null, 'spread' => 0 );
		}

		$next = self::next_period( $user_id, $date );
		if ( ! $next ) {
			return array( 'mode' => 'none', 'date' => null, 'label' => 'برای پیش‌بینی، چند چرخه ثبت لازم است.', 'from' => null, 'to' => null, 'spread' => 0 );
		}

		$status = MB_Irregular::status( $user_id );
		if ( empty( $status['irregular'] ) ) {
			return array(
				'mode'   => 'exact',
				'date'   => $next,
				'label'  => MB_Jalali::format_fa( $next, 'long' ),
				'from'   => $next,
				'to'     => $next,
				'spread' => 0,
			);
		}

		$range = MB_Irregular::range_for( $next, (int) $status['spread'] );
		return array(
			'mode'   => 'range',
			'date'   => $next,
			'label'  => $range['label'],
			'from'   => $range['from'],
			'to'     => $range['to'],
			'spread' => (int) $status['spread'],
		);
	}

	public static function next_period( int $user_id, ?string $date = null ): ?string {
		$date  = $date ?: MB_Jalali::today();
		$start = self::cycle_start_for( $user_id, $date );
		if ( ! $start ) {
			return null;
		}
		return MB_Jalali::add_days( $start, self::cycle_len( $user_id ) );
	}

	public static function days_to_next_period( int $user_id, ?string $date = null ): ?int {
		$date = $date ?: MB_Jalali::today();
		$next = self::next_period( $user_id, $date );
		return $next ? MB_Jalali::diff_days( $date, $next ) : null;
	}

	/* --------------------------------------------------------------------- */
	/* فازها                                                                 */
	/* --------------------------------------------------------------------- */

	public static function phases(): array {
		return array(
			'period'     => array( 'label' => 'قاعدگی', 'color' => 'period', 'hormone' => 'افت استروژن و پروژسترون؛ ریزش دیواره رحم و پایین‌ترین سطح انرژی بدن.' ),
			'follicular' => array( 'label' => 'فولیکولار', 'color' => 'foll', 'hormone' => 'استروژن آرام‌آرام بالا می‌رود؛ خلق و انرژی روبه‌بهبود و تمرکز بیشتر.' ),
			'fertile'    => array( 'label' => 'پنجره باروری', 'color' => 'fertile', 'hormone' => 'اوج استروژن و افزایش LH؛ بالاترین احتمال بارداری در این بازه است.' ),
			'ovulation'  => array( 'label' => 'تخمک‌گذاری', 'color' => 'ovul', 'hormone' => 'جهش LH و آزادسازی تخمک؛ اوج انرژی، میل و دمای پایه کمی بالاتر.' ),
			'luteal'     => array( 'label' => 'لوتئال', 'color' => 'luteal', 'hormone' => 'پروژسترون غالب است؛ بدن آرام‌تر می‌شود و اشتها و خواب تغییر می‌کند.' ),
			'pms'        => array( 'label' => 'پیش‌از‌قاعدگی', 'color' => 'pms', 'hormone' => 'افت هم‌زمان پروژسترون و استروژن؛ نوسان خلق، حساسیت و خستگی شایع است.' ),
			'normal'     => array( 'label' => 'روزهای معمول', 'color' => 'norm', 'hormone' => 'هورمون‌ها در وضعیت متعادل؛ روزهای پایدار چرخه.' ),
		);
	}

	public static function phase_of( int $user_id, string $date ): string {
		$idx = self::day_index( $user_id, $date );
		if ( null === $idx ) {
			return 'normal';
		}
		return self::phase_of_index( $user_id, $idx );
	}

	public static function phase_of_index( int $user_id, int $idx ): string {
		$len       = self::cycle_len( $user_id );
		$period    = self::period_len( $user_id );
		$ovul      = self::ovulation_day( $user_id );
		$pms_start = self::personal_pms_start( $user_id );

		if ( $idx < 1 ) {
			return 'normal';
		}
		if ( $idx <= $period ) {
			return 'period';
		}
		if ( $idx === $ovul ) {
			return 'ovulation';
		}
		if ( $idx >= $ovul - 5 && $idx <= $ovul + 1 ) {
			return 'fertile';
		}
		if ( $idx >= $pms_start ) {
			return 'pms';
		}
		if ( $idx > $ovul ) {
			return 'luteal';
		}
		if ( $idx > $period && $idx < $ovul - 5 ) {
			return 'follicular';
		}
		return 'normal';
	}

	public static function phase_label( string $phase ): string {
		$phases = self::phases();
		return isset( $phases[ $phase ] ) ? $phases[ $phase ]['label'] : 'روزهای معمول';
	}

	public static function phase_hormone( string $phase ): string {
		$phases = self::phases();
		return isset( $phases[ $phase ] ) ? $phases[ $phase ]['hormone'] : '';
	}

	/* --------------------------------------------------------------------- */
	/* دقت و آمار                                                            */
	/* --------------------------------------------------------------------- */

	/** درصد دقت نمایشی از واریانس طول چرخه‌ها. */
	public static function accuracy( int $user_id ): int {
		$lengths = self::recent_lengths( $user_id, 6 );
		$logs    = MB_DB::count_logs( $user_id );
		if ( count( $lengths ) < 2 ) {
			$base = 78 + min( 8, (int) floor( $logs / 6 ) );
			return (int) max( 55, min( 88, $base ) );
		}
		$sd  = self::stdev( $lengths );
		$acc = 96 - ( $sd * 4.5 );
		$acc += min( 4, count( $lengths ) );
		$acc += min( 3, (int) floor( $logs / 20 ) );
		return (int) max( 55, min( 96, (int) round( $acc ) ) );
	}

	/** نوسان ±روز و منظم/نامنظم. */
	public static function variation( int $user_id ): array {
		$lengths = self::recent_lengths( $user_id, 6 );
		if ( count( $lengths ) < 2 ) {
			return array( 'delta' => 0, 'regular' => true, 'label' => 'در حال یادگیری' );
		}
		$delta   = (int) round( max( $lengths ) - min( $lengths ) );
		$regular = $delta <= 4;
		return array(
			'delta'   => $delta,
			'regular' => $regular,
			'label'   => $regular ? 'منظم' : 'نامنظم',
		);
	}

	/** تعداد شروع‌های چرخهٔ ثبت‌شده، برای حالت «در حال یادگیری». */
	public static function cycle_count( int $user_id ): int {
		return count( MB_DB::get_cycles( $user_id, 100 ) );
	}

	public static function avg_cycle( int $user_id ): int {
		$lengths = self::recent_lengths( $user_id, 6 );
		if ( empty( $lengths ) ) {
			return self::cycle_len( $user_id );
		}
		return (int) round( array_sum( $lengths ) / count( $lengths ) );
	}

	/** میانگین طول واقعی خونریزی از لاگ‌ها. */
	public static function avg_period_len( int $user_id ): int {
		$cycles = MB_DB::get_cycles( $user_id, 6 );
		if ( empty( $cycles ) ) {
			return self::period_len( $user_id );
		}
		$vals = array();
		foreach ( $cycles as $c ) {
			$logs  = MB_DB::get_logs_range( $user_id, $c['start_date'], MB_Jalali::add_days( $c['start_date'], 9 ) );
			$count = 0;
			foreach ( $logs as $log ) {
				if ( in_array( $log['bleeding'], array( 'light', 'med', 'heavy' ), true ) ) {
					++$count;
				}
			}
			if ( $count > 0 ) {
				$vals[] = $count;
			}
		}
		if ( empty( $vals ) ) {
			return self::period_len( $user_id );
		}
		return (int) max( 2, min( 9, (int) round( array_sum( $vals ) / count( $vals ) ) ) );
	}

	/** احتمال تجربه هر نشانه در روز مشخص چرخه (از الگوی شخصی، پنجره ±۱ روز). */
	public static function symptom_probability( int $user_id, int $day ): array {
		$cycles = MB_DB::get_cycles( $user_id, 7 );
		$map    = MB_DB::symptom_map();
		$counts = array();
		$total  = 0;

		$count_cycles = count( $cycles );
		for ( $i = 0; $i < $count_cycles; $i++ ) {
			$from = $cycles[ $i ]['start_date'];
			$to   = isset( $cycles[ $i + 1 ] ) ? MB_Jalali::add_days( $cycles[ $i + 1 ]['start_date'], -1 ) : MB_Jalali::add_days( $from, self::cycle_len( $user_id ) - 1 );
			$logs = MB_DB::get_logs_range( $user_id, $from, $to );
			$hit  = false;
			foreach ( $logs as $date => $log ) {
				$idx = MB_Jalali::diff_days( $from, $date ) + 1;
				if ( abs( $idx - $day ) > 1 ) {
					continue;
				}
				$hit = true;
				foreach ( (array) $log['symptoms'] as $s ) {
					if ( ! isset( $counts[ $s ] ) ) {
						$counts[ $s ] = 0;
					}
					++$counts[ $s ];
				}
				if ( (int) $log['pain'] >= 3 ) {
					$counts['pain_high'] = ( $counts['pain_high'] ?? 0 ) + 1;
				}
			}
			if ( $hit ) {
				++$total;
			}
		}

		if ( $total < 1 ) {
			return self::symptom_probability_fallback( $user_id, $day );
		}

		$out = array();
		foreach ( $counts as $key => $n ) {
			$label = 'pain_high' === $key ? 'درد متوسط تا شدید' : ( $map[ $key ]['label'] ?? $key );
			$out[] = array(
				'key'     => $key,
				'label'   => $label,
				'percent' => (int) max( 5, min( 97, (int) round( ( $n / $total ) * 100 ) ) ),
				'source'  => 'personal',
			);
		}
		usort(
			$out,
			static function ( $a, $b ) {
				return $b['percent'] <=> $a['percent'];
			}
		);
		return array_slice( $out, 0, 5 );
	}

	/** تا وقتی داده شخصی کافی نیست، احتمال‌های نوعی بر پایه فاز. */
	private static function symptom_probability_fallback( int $user_id, int $day ): array {
		$phase = self::phase_of_index( $user_id, $day );
		$base  = array(
			'period'     => array( array( 'گرفتگی عضلات', 72 ), array( 'خستگی', 58 ), array( 'کمردرد', 44 ) ),
			'pms'        => array( array( 'نوسان خلق', 68 ), array( 'نفخ', 52 ), array( 'حساسیت سینه', 47 ) ),
			'luteal'     => array( array( 'خستگی', 41 ), array( 'هوس غذایی', 35 ), array( 'بی‌خوابی', 22 ) ),
			'ovulation'  => array( array( 'افزایش انرژی', 63 ), array( 'درد یک‌طرفه لگن', 28 ) ),
			'fertile'    => array( array( 'افزایش ترشحات', 55 ), array( 'انرژی بالا', 48 ) ),
			'follicular' => array( array( 'انرژی روبه‌رشد', 52 ), array( 'تمرکز بهتر', 44 ) ),
			'normal'     => array( array( 'بدون نشانه غالب', 60 ) ),
		);
		$rows = $base[ $phase ] ?? $base['normal'];
		$out  = array();
		foreach ( $rows as $r ) {
			$out[] = array(
				'key'     => sanitize_key( 'typical_' . md5( $r[0] ) ),
				'label'   => $r[0],
				'percent' => (int) $r[1],
				'source'  => 'typical',
			);
		}
		return $out;
	}

	/** نشانه‌های غالب کاربر در کل داده‌ها. */
	public static function dominant_symptoms( int $user_id, int $limit = 4 ): array {
		$from  = MB_Jalali::add_days( MB_Jalali::today(), -180 );
		$logs  = MB_DB::get_logs_range( $user_id, $from, MB_Jalali::today() );
		$map   = MB_DB::symptom_map();
		$count = array();
		$days  = max( 1, count( $logs ) );
		foreach ( $logs as $log ) {
			foreach ( (array) $log['symptoms'] as $s ) {
				$count[ $s ] = ( $count[ $s ] ?? 0 ) + 1;
			}
		}
		arsort( $count );
		$out = array();
		foreach ( array_slice( $count, 0, $limit, true ) as $key => $n ) {
			$out[] = array(
				'label'       => $map[ $key ]['label'] ?? $key,
				'percent'     => (int) max( 3, min( 99, (int) round( ( $n / $days ) * 100 ) ) ),
				'correlation' => self::symptom_phase_correlation( $user_id, $key, $logs ),
			);
		}
		return $out;
	}

	/** همبستگی نشانه با فاز غالب. */
	private static function symptom_phase_correlation( int $user_id, string $symptom, array $logs ): string {
		$byphase = array();
		foreach ( $logs as $date => $log ) {
			if ( ! in_array( $symptom, (array) $log['symptoms'], true ) ) {
				continue;
			}
			$phase             = self::phase_of( $user_id, $date );
			$byphase[ $phase ] = ( $byphase[ $phase ] ?? 0 ) + 1;
		}
		if ( empty( $byphase ) ) {
			return '';
		}
		arsort( $byphase );
		$top   = array_key_first( $byphase );
		$share = (int) round( ( $byphase[ $top ] / max( 1, array_sum( $byphase ) ) ) * 100 );
		return sprintf( 'بیشتر در فاز %s (%s٪)', self::phase_label( $top ), MB_Jalali::fa_num( $share ) );
	}

	/* --------------------------------------------------------------------- */
	/* نمودارها و نوارهای خانه                                               */
	/* --------------------------------------------------------------------- */

	/** احتمال بارداری امروز (٪) بر پایه فاصله از تخمک‌گذاری. */
	public static function pregnancy_chance( int $user_id, ?string $date = null ): int {
		$date = $date ?: MB_Jalali::today();
		$ovul = self::ovulation_date( $user_id, $date );
		if ( ! $ovul ) {
			return 0;
		}
		$d     = MB_Jalali::diff_days( $ovul, $date );
		$curve = array( -6 => 4, -5 => 10, -4 => 16, -3 => 24, -2 => 31, -1 => 33, 0 => 30, 1 => 14, 2 => 4 );
		return isset( $curve[ $d ] ) ? (int) $curve[ $d ] : 1;
	}

	/** انرژی پیش‌بینی‌شده (٪). */
	public static function energy_level( int $user_id, ?string $date = null ): int {
		$phase = self::phase_of( $user_id, $date ?: MB_Jalali::today() );
		$map   = array(
			'period'     => 38,
			'follicular' => 72,
			'fertile'    => 86,
			'ovulation'  => 92,
			'luteal'     => 61,
			'pms'        => 44,
			'normal'     => 65,
		);
		return (int) ( $map[ $phase ] ?? 65 );
	}

	/** کیفیت خواب پیش‌بینی‌شده (٪) با اثر لاگ‌های بی‌خوابی. */
	/**
	 * M3 — اول از ساعت خواب واقعی ثبت‌شده (میانگین ۷ روز) استفاده می‌کند؛
	 * تنها در نبود داده به تخمین فازی برمی‌گردد.
	 */
	public static function sleep_quality( int $user_id, ?string $date = null ): int {
		$logged = self::logged_sleep_quality( $user_id );
		if ( null !== $logged ) {
			return $logged;
		}
		return self::predicted_sleep_quality( $user_id, $date );
	}

	/** آیا عدد نوار خواب از دادهٔ واقعی آمده است؟ */
	public static function sleep_is_logged( int $user_id ): bool {
		return null !== self::logged_sleep_quality( $user_id );
	}

	/**
	 * کیفیت خواب از میانگین sleep_h هفت روز گذشته.
	 * ۷٫۵ ساعت = ۱۰۰٪؛ کمتر و بیشتر به‌نسبت کاهش می‌یابد.
	 */
	private static function logged_sleep_quality( int $user_id ): ?int {
		$to   = MB_Jalali::today();
		$logs = MB_DB::get_logs_range( $user_id, MB_Jalali::add_days( $to, -7 ), $to );

		$hours = array();
		foreach ( $logs as $row ) {
			if ( null !== ( $row['sleep_h'] ?? null ) && '' !== $row['sleep_h'] ) {
				$hours[] = (float) $row['sleep_h'];
			}
		}
		if ( empty( $hours ) ) {
			return null;
		}

		$avg   = array_sum( $hours ) / count( $hours );
		$ideal = 7.5;
		$ratio = $avg <= $ideal ? ( $avg / $ideal ) : ( 1 - ( ( $avg - $ideal ) / 12 ) );
		return (int) max( 20, min( 100, round( $ratio * 100 ) ) );
	}

	private static function predicted_sleep_quality( int $user_id, ?string $date = null ): int {
		$phase = self::phase_of( $user_id, $date ?: MB_Jalali::today() );
		$map   = array(
			'period'     => 55,
			'follicular' => 78,
			'fertile'    => 80,
			'ovulation'  => 76,
			'luteal'     => 66,
			'pms'        => 52,
			'normal'     => 72,
		);
		$value = (int) ( $map[ $phase ] ?? 72 );
		$logs  = MB_DB::get_logs_range( $user_id, MB_Jalali::add_days( MB_Jalali::today(), -30 ), MB_Jalali::today() );
		$ins   = 0;
		foreach ( $logs as $log ) {
			if ( in_array( 'insomnia', (array) $log['symptoms'], true ) ) {
				++$ins;
			}
		}
		return (int) max( 25, min( 95, $value - min( 20, $ins * 3 ) ) );
	}

	/** نقشه کامل چرخه جاری: ۶ فاز با بازه شمسی. */
	public static function cycle_map( int $user_id, ?string $date = null ): array {
		$date  = $date ?: MB_Jalali::today();
		$start = self::cycle_start_for( $user_id, $date );
		if ( ! $start ) {
			return array();
		}
		$len = self::cycle_len( $user_id );

		// مرزها از خود phase_of_index ساخته می‌شوند تا هرگز با آن اختلاف نداشته باشند.
		// پیش از این بازه‌ها دستی نوشته شده بودند و وقتی $pms <= $ovul + 2 بود
		// (مثلاً چرخهٔ ۲۸ روزه) روزِ ovul+1 هم «باروری» و هم «پیش‌از‌قاعدگی»
		// می‌شد و لوتئال به یک روز می‌چسبید که phase_of آن را pms می‌نامید.
		$spans   = array();
		$current = '';
		$from    = 1;
		for ( $i = 1; $i <= $len; $i++ ) {
			$phase = self::phase_of_index( $user_id, $i );
			if ( $phase !== $current ) {
				if ( '' !== $current ) {
					$spans[] = array( $current, $from, $i - 1 );
				}
				$current = $phase;
				$from    = $i;
			}
		}
		if ( '' !== $current ) {
			$spans[] = array( $current, $from, $len );
		}

		$out = array();
		foreach ( $spans as $span ) {
			list( $phase, $a, $b ) = $span;
			$a = (int) max( 1, min( $len, $a ) );
			$b = (int) max( $a, min( $len, $b ) );
			$out[] = array(
				'phase'    => $phase,
				'label'    => self::phase_label( $phase ),
				'hormone'  => self::phase_hormone( $phase ),
				'from'     => MB_Jalali::add_days( $start, $a - 1 ),
				'to'       => MB_Jalali::add_days( $start, $b - 1 ),
				'days'     => $b - $a + 1,
				'from_idx' => $a,
				'to_idx'   => $b,
			);
		}
		return $out;
	}

	/** پیش‌بینی n چرخه آینده (برای تقویم و نمای ۳ ماهه Pro). */
	public static function predicted_cycles( int $user_id, int $count = 3 ): array {
		$start = self::cycle_start_for( $user_id, MB_Jalali::today() );
		if ( ! $start ) {
			return array();
		}
		$len = self::cycle_len( $user_id );
		$out = array();
		for ( $i = 1; $i <= max( 1, $count ); $i++ ) {
			$s = MB_Jalali::add_days( $start, $i * $len );
			$out[] = array(
				'start'     => $s,
				'end'       => MB_Jalali::add_days( $s, self::period_len( $user_id ) - 1 ),
				'ovulation' => MB_Jalali::add_days( $s, self::ovulation_day( $user_id ) - 1 ),
				'fertile'   => array(
					'from' => MB_Jalali::add_days( $s, self::ovulation_day( $user_id ) - 6 ),
					'to'   => MB_Jalali::add_days( $s, self::ovulation_day( $user_id ) ),
				),
			);
		}
		return $out;
	}

	/** فاز هر روز از یک بازه (برای تقویم و نوار ۷ روز همسر). */
	public static function phase_series( int $user_id, string $from, string $to ): array {
		$out    = array();
		$cursor = $from;
		$guard  = 0;
		while ( MB_Jalali::diff_days( $cursor, $to ) >= 0 && $guard < 400 ) {
			$out[ $cursor ] = self::phase_of( $user_id, $cursor );
			$cursor         = MB_Jalali::add_days( $cursor, 1 );
			++$guard;
		}
		return $out;
	}

	/** آیا این تاریخ در چرخه‌های پیش‌بینی‌شده (آینده) است؟ */
	public static function is_predicted( int $user_id, string $date ): bool {
		if ( ! self::anchor( $user_id ) ) {
			return true;
		}
		return MB_Jalali::diff_days( MB_Jalali::today(), $date ) > 0;
	}

	/* --------------------------------------------------------------------- */
	/* توصیه‌های فازی                                                        */
	/* --------------------------------------------------------------------- */

	public static function daily_plan( string $phase ): array {
		$plans = array(
			'period'     => array(
				array( 'icon' => 'cup', 'title' => 'تغذیه', 'text' => 'آهن و ویتامین C: عدس، خرما، مرکبات. چای کم‌رنگ و آب گرم زیاد.' ),
				array( 'icon' => 'wave', 'title' => 'حرکت', 'text' => 'پیاده‌روی سبک یا کشش لگن ۱۰ دقیقه؛ ورزش سنگین را کنار بگذار.' ),
				array( 'icon' => 'moon', 'title' => 'استراحت', 'text' => 'کیسه آب گرم روی کمر و خواب نیم‌ساعت زودتر از معمول.' ),
			),
			'follicular' => array(
				array( 'icon' => 'cup', 'title' => 'تغذیه', 'text' => 'پروتئین و سبزیجات برگ‌سبز؛ بدن الان بهتر جذب می‌کند.' ),
				array( 'icon' => 'spark', 'title' => 'حرکت', 'text' => 'بهترین زمان تمرین قدرتی یا شروع یک عادت ورزشی تازه.' ),
				array( 'icon' => 'sun', 'title' => 'کار', 'text' => 'کارهای خلاقانه و تصمیم‌های مهم را همین روزها بگذار.' ),
			),
			'fertile'    => array(
				array( 'icon' => 'heart', 'title' => 'آگاهی', 'text' => 'احتمال بارداری بالاست؛ اگر قصد بارداری نداری روش پیشگیری را جدی بگیر.' ),
				array( 'icon' => 'spark', 'title' => 'حرکت', 'text' => 'انرژی در اوج است؛ تمرین هوازی شدت‌متوسط عالی است.' ),
				array( 'icon' => 'drop', 'title' => 'بدن', 'text' => 'ترشحات شفاف و کشدار طبیعی است و نشانه پنجره باروری.' ),
			),
			'ovulation'  => array(
				array( 'icon' => 'heart', 'title' => 'آگاهی', 'text' => 'روز تخمک‌گذاری؛ بالاترین شانس باروری همین امروز و دو روز قبل است.' ),
				array( 'icon' => 'cup', 'title' => 'تغذیه', 'text' => 'آب کافی و آنتی‌اکسیدان؛ کافئین را محدود کن.' ),
				array( 'icon' => 'wave', 'title' => 'بدن', 'text' => 'درد خفیف یک‌طرفه لگن شایع و بی‌خطر است.' ),
			),
			'luteal'     => array(
				array( 'icon' => 'cup', 'title' => 'تغذیه', 'text' => 'منیزیم و کربوهیدرات پیچیده: موز، بادام، جو دوسر برای مهار هوس غذایی.' ),
				array( 'icon' => 'wave', 'title' => 'حرکت', 'text' => 'یوگا، شنا یا پیاده‌روی؛ شدت را کمی پایین بیاور.' ),
				array( 'icon' => 'moon', 'title' => 'خواب', 'text' => 'اتاق خنک‌تر و نور کمتر؛ کیفیت خواب این روزها افت می‌کند.' ),
			),
			'pms'        => array(
				array( 'icon' => 'cup', 'title' => 'تغذیه', 'text' => 'نمک و شکر را کم کن؛ منیزیم و B6 نفخ و نوسان خلق را آرام می‌کند.' ),
				array( 'icon' => 'moon', 'title' => 'آرامش', 'text' => 'تنفس ۴-۷-۸ و ده دقیقه بی‌گوشی؛ کارهای پرتنش را جابه‌جا کن.' ),
				array( 'icon' => 'heart', 'title' => 'مهربانی', 'text' => 'انتظار بازدهی کامل از خودت نداشته باش؛ این افت هورمونی است نه ضعف.' ),
			),
			'normal'     => array(
				array( 'icon' => 'cup', 'title' => 'تغذیه', 'text' => 'آب کافی، صبحانه پروتئینی و میان‌وعده سالم.' ),
				array( 'icon' => 'wave', 'title' => 'حرکت', 'text' => 'نیم‌ساعت پیاده‌روی؛ ساده‌ترین کار مؤثر امروز.' ),
				array( 'icon' => 'spark', 'title' => 'ثبت', 'text' => 'ثبت روزانه را ادامه بده تا پیش‌بینی‌ها دقیق‌تر شود.' ),
			),
		);
		return $plans[ $phase ] ?? $plans['normal'];
	}

	/** مراقبت امروز: تغذیه/حرکت/پیشگیری. */
	public static function day_care( string $phase ): array {
		$plan = self::daily_plan( $phase );
		$care = array();
		foreach ( $plan as $p ) {
			$care[] = $p;
		}
		$care[] = array( 'icon' => 'shield', 'title' => 'پیشگیری', 'text' => 'در همه فازها روش پیشگیری انتخابی‌ات را پیوسته استفاده کن؛ «روز ناامن» مطلق وجود ندارد.' );
		return array_slice( $care, 0, 3 );
	}

	/** پیام آماده و بی‌جزئیات برای همسر. */
	public static function support_suggestions( string $phase ): array {
		$map = array(
			'period'     => array( 'یک نوشیدنی گرم بیاور', 'کارهای خانه امروز را خودت بردار', 'زودتر چراغ‌ها را خاموش کن' ),
			'pms'        => array( 'صدایت را پایین‌تر و آرام‌تر نگه دار', 'بدون نصیحت فقط گوش بده', 'برنامه شلوغ امشب را لغو کن' ),
			'luteal'     => array( 'یک میان‌وعده دلخواهش بخر', 'پیشنهاد پیاده‌روی کوتاه بده', 'شب زودتر آماده خواب شوید' ),
			'ovulation'  => array( 'برای بیرون‌رفتن کوتاه پیشنهاد بده', 'یک قدردانی ساده بگو' ),
			'fertile'    => array( 'وقت باکیفیت بگذار', 'یک برنامه دونفره کوچک بچین' ),
			'follicular' => array( 'در برنامه‌های تازه‌اش همراه شو', 'یک تعریف صادقانه بگو' ),
			'normal'     => array( 'یک پیام کوتاه محبت‌آمیز بفرست', 'کاری از فهرست کارهایش را بردار' ),
		);
		return $map[ $phase ] ?? $map['normal'];
	}

	/* --------------------------------------------------------------------- */
	/* ریاضیات کمکی                                                          */
	/* --------------------------------------------------------------------- */

	public static function median( array $values ): float {
		// array_filter بدون callback مقدار صفر را هم حذف می‌کرد.
		$values = array_values( array_map( 'intval', $values ) );
		if ( empty( $values ) ) {
			return 0.0;
		}
		sort( $values );
		$n   = count( $values );
		$mid = (int) floor( ( $n - 1 ) / 2 );
		return 0 === $n % 2 ? ( ( $values[ $mid ] + $values[ $mid + 1 ] ) / 2 ) : (float) $values[ $mid ];
	}

	public static function stdev( array $values ): float {
		$values = array_values( array_map( 'floatval', $values ) );
		$n      = count( $values );
		if ( $n < 2 ) {
			return 0.0;
		}
		$mean = array_sum( $values ) / $n;
		$sum  = 0.0;
		foreach ( $values as $v ) {
			$sum += ( $v - $mean ) ** 2;
		}
		return sqrt( $sum / ( $n - 1 ) );
	}

	/** مجموعه داده مشترک برای تمام صفحه‌ها. */
	public static function snapshot( int $user_id, ?string $date = null ): array {
		$date  = MB_Jalali::sanitize_date( $date ?: MB_Jalali::today() );
		$idx   = self::day_index( $user_id, $date );
		$phase = self::phase_of( $user_id, $date );
		return array(
			'date'        => $date,
			'jalali'      => MB_Jalali::format_fa( $date, 'long' ),
			'jalali_full' => MB_Jalali::format_fa( $date, 'full' ),
			'day_index'   => $idx,
			'cycle_count' => self::cycle_count( $user_id ),
			'cycle_len'   => self::cycle_len( $user_id ),
			'period_len'  => self::period_len( $user_id ),
			'luteal_len'  => self::luteal_len( $user_id ),
			'phase'       => $phase,
			'phase_label' => self::phase_label( $phase ),
			'hormone'     => self::phase_hormone( $phase ),
			'period'      => self::period_range( $user_id, $date ),
			'fertile'     => self::fertile_range( $user_id, $date ),
			'pms'         => self::pms_range( $user_id, $date ),
			'ovulation'   => self::ovulation_date( $user_id, $date ),
			'next_period' => self::next_period( $user_id, $date ),
			'days_left'   => self::days_to_next_period( $user_id, $date ),
			'accuracy'    => self::accuracy( $user_id ),
			'pregnancy'   => self::pregnancy_chance( $user_id, $date ),
			'energy'      => self::energy_level( $user_id, $date ),
			'sleep'       => self::sleep_quality( $user_id, $date ),
			'pms_start'   => self::personal_pms_start( $user_id ),
		);
	}
}
