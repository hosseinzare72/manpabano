<?php
/**
 * M4 — گزارش پزشک (نسخهٔ پیشرفته).
 *
 * فقط و فقط دادهٔ خود کاربر درخواست‌کننده. هیچ پارامتر user_id از بیرون
 * پذیرفته نمی‌شود؛ شناسه همیشه از سشن گرفته می‌شود. دانلود CSV با nonce و
 * گیت Pro سمت سرور محافظت شده است.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Report {

	const NONCE = 'mb_report_csv';

	/** حداکثر بازهٔ گزارش (ماه). */
	const MAX_MONTHS = 12;

	public static function init(): void {
		add_action( 'template_redirect', array( __CLASS__, 'maybe_serve_csv' ), 5 );
	}

	/* --------------------------------------------------------------------- */
	/* دادهٔ گزارش                                                           */
	/* --------------------------------------------------------------------- */

	/**
	 * دادهٔ کامل گزارش برای کاربر جاری.
	 *
	 * @param int $user_id شناسهٔ کاربر (همیشه از سشن، نه از ورودی).
	 * @param int $months  بازهٔ گزارش به ماه.
	 */
	public static function data( int $user_id, int $months = 6 ): array {
		$months = max( 1, min( self::MAX_MONTHS, $months ) );
		$to     = MB_Jalali::today();
		$from   = MB_Jalali::add_days( $to, -1 * ( $months * 30 ) );

		$logs = MB_DB::get_logs_range( $user_id, $from, $to );
		$user = get_userdata( $user_id );

		return array(
			'user_id'    => $user_id,
			'name'       => $user ? ( $user->first_name ? $user->first_name : $user->display_name ) : '',
			'months'     => $months,
			'from'       => $from,
			'to'         => $to,
			'range_fa'   => MB_Jalali::range_fa( $from, $to ),
			'generated'  => MB_Jalali::format_fa( $to, 'full' ),
			'profile'    => MB_DB::get_profile( $user_id ),
			'cycles'     => self::cycle_rows( $user_id ),
			'logs'       => self::log_rows( $logs ),
			'symptoms'   => self::symptom_frequency( $logs ),
			'stats'      => self::stats( $user_id, $logs ),
			'irregular'  => MB_Irregular::status( $user_id ),
			'pregnancy'  => MB_Pregnancy::get( $user_id ),
			'disclaimer' => MB_Plugin::disclaimer(),
		);
	}

	private static function cycle_rows( int $user_id ): array {
		$rows = MB_DB::get_cycles( $user_id, self::MAX_MONTHS );
		$out  = array();
		foreach ( $rows as $row ) {
			$out[] = array(
				'start'    => (string) $row['start_date'],
				'start_fa' => MB_Jalali::format_fa( (string) $row['start_date'], 'long' ),
				'length'   => (int) $row['length'],
				'period'   => (int) $row['period_length'],
			);
		}
		return $out;
	}

	private static function log_rows( array $logs ): array {
		$symptom_map = MB_DB::symptom_map();
		$bleeds      = array( 'none' => 'ندارم', 'spot' => 'لکه‌بینی', 'light' => 'کم', 'med' => 'متوسط', 'heavy' => 'زیاد' );
		$mucus       = MB_DB::mucus_options();

		$out = array();
		foreach ( $logs as $date => $row ) {
			$labels = array();
			foreach ( (array) $row['symptoms'] as $key ) {
				if ( isset( $symptom_map[ $key ]['label'] ) ) {
					$labels[] = (string) $symptom_map[ $key ]['label'];
				}
			}
			$out[] = array(
				'date'      => (string) $date,
				'date_fa'   => MB_Jalali::format_fa( (string) $date, 'long' ),
				'bleeding'  => (string) ( $bleeds[ $row['bleeding'] ] ?? '—' ),
				'pain'      => (int) $row['pain'],
				'mood'      => null === $row['mood'] ? '' : (int) $row['mood'],
				'symptoms'  => $labels,
				'bbt'       => null === $row['bbt'] ? '' : (float) $row['bbt'],
				'mucus'     => 'none' === ( $row['mucus'] ?? 'none' ) ? '' : (string) ( $mucus[ $row['mucus'] ] ?? '' ),
				'weight'    => null === $row['weight_kg'] ? '' : (float) $row['weight_kg'],
				'sleep'     => null === $row['sleep_h'] ? '' : (float) $row['sleep_h'],
				'water'     => null === $row['water_cups'] ? '' : (int) $row['water_cups'],
				'exercise'  => null === $row['exercise_min'] ? '' : (int) $row['exercise_min'],
				'ovulation' => (int) ( $row['ovulation_confirmed'] ?? 0 ),
			);
		}
		// جدید به قدیم برای خوانایی پزشک.
		return array_reverse( $out );
	}

	private static function symptom_frequency( array $logs ): array {
		$map   = MB_DB::symptom_map();
		$count = array();
		$total = max( 1, count( $logs ) );

		foreach ( $logs as $row ) {
			foreach ( (array) $row['symptoms'] as $key ) {
				$count[ $key ] = ( $count[ $key ] ?? 0 ) + 1;
			}
		}
		arsort( $count );

		$out = array();
		foreach ( $count as $key => $times ) {
			$out[] = array(
				'key'     => (string) $key,
				'label'   => (string) ( $map[ $key ]['label'] ?? $key ),
				'times'   => (int) $times,
				'percent' => (int) round( ( $times / $total ) * 100 ),
			);
		}
		return $out;
	}

	private static function stats( int $user_id, array $logs ): array {
		$pains  = array();
		$sleeps = array();
		$moods  = array();
		foreach ( $logs as $row ) {
			if ( (int) $row['pain'] > 0 ) {
				$pains[] = (int) $row['pain'];
			}
			if ( null !== $row['sleep_h'] ) {
				$sleeps[] = (float) $row['sleep_h'];
			}
			if ( null !== $row['mood'] ) {
				$moods[] = (int) $row['mood'];
			}
		}

		return array(
			'logged_days' => count( $logs ),
			'avg_cycle'   => MB_Cycle_Engine::avg_cycle( $user_id ),
			'avg_period'  => MB_Cycle_Engine::avg_period_len( $user_id ),
			'cycle_count' => MB_Cycle_Engine::cycle_count( $user_id ),
			'avg_pain'    => empty( $pains ) ? 0 : round( array_sum( $pains ) / count( $pains ), 1 ),
			'avg_sleep'   => empty( $sleeps ) ? 0 : round( array_sum( $sleeps ) / count( $sleeps ), 1 ),
			'avg_mood'    => empty( $moods ) ? 0 : round( array_sum( $moods ) / count( $moods ), 1 ),
		);
	}

	/* --------------------------------------------------------------------- */
	/* CSV                                                                   */
	/* --------------------------------------------------------------------- */

	public static function csv_url( int $months = 6 ): string {
		return add_query_arg(
			array(
				'mb_csv'    => 1,
				'months'    => max( 1, min( self::MAX_MONTHS, $months ) ),
				'_mb_nonce' => wp_create_nonce( self::NONCE ),
			),
			MB_UI::app_url()
		);
	}

	/**
	 * سرو فایل CSV. سه گیت پشت‌سرهم: ورود، nonce، و اشتراک Pro.
	 * شناسهٔ کاربر فقط از سشن خوانده می‌شود.
	 */
	public static function maybe_serve_csv(): void {
		if ( ! isset( $_GET['mb_csv'] ) ) {
			return;
		}

		$nonce = isset( $_GET['_mb_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_mb_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			MB_Plugin::log_security( 'report_csv_bad_nonce', array( 'ip' => MB_DB::client_ip() ) );
			wp_die( 'درخواست معتبر نیست.', 'خطا', array( 'response' => 403 ) );
		}

		$user_id = get_current_user_id();
		if ( $user_id <= 0 || MB_Privacy::is_partner( $user_id ) ) {
			wp_die( 'دسترسی مجاز نیست.', 'خطا', array( 'response' => 403 ) );
		}

		if ( ! MB_Subscription::can( $user_id, 'report' ) ) {
			wp_die( 'گزارش پزشک بخشی از نسخهٔ پیشرفته است.', 'خطا', array( 'response' => 403 ) );
		}

		if ( ! MB_License::rate_limit( 'csv_' . $user_id, 10, 600 ) ) {
			wp_die( 'درخواست‌های زیادی فرستاده شد. چند دقیقه بعد دوباره تلاش کن.', 'خطا', array( 'response' => 429 ) );
		}

		$months = isset( $_GET['months'] ) ? (int) $_GET['months'] : 6;
		$data   = self::data( $user_id, $months );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="moonbanu-report-' . gmdate( 'Ymd' ) . '.csv"' );
		header( 'X-Content-Type-Options: nosniff' );

		$out = fopen( 'php://output', 'w' );
		// BOM تا اکسل فارسی را درست باز کند.
		fwrite( $out, "\xEF\xBB\xBF" );

		fputcsv( $out, array( 'گزارش ماه‌بانو', $data['range_fa'] ) );
		fputcsv( $out, array( MB_Plugin::disclaimer() ) );
		fputcsv( $out, array() );

		fputcsv( $out, array( 'چرخه‌ها' ) );
		fputcsv( $out, array( 'شروع (شمسی)', 'طول چرخه', 'طول قاعدگی' ) );
		foreach ( $data['cycles'] as $row ) {
			fputcsv( $out, array( $row['start_fa'], $row['length'], $row['period'] ) );
		}
		fputcsv( $out, array() );

		fputcsv( $out, array( 'ثبت‌های روزانه' ) );
		fputcsv(
			$out,
			array( 'تاریخ (شمسی)', 'خونریزی', 'درد (۰-۵)', 'خلق (۱-۵)', 'نشانه‌ها', 'دمای پایه', 'مخاط', 'وزن (kg)', 'خواب (ساعت)', 'آب (لیوان)', 'ورزش (دقیقه)', 'تخمک‌گذاری تأییدشده' )
		);
		foreach ( $data['logs'] as $row ) {
			fputcsv(
				$out,
				array(
					$row['date_fa'],
					$row['bleeding'],
					$row['pain'],
					$row['mood'],
					implode( ' / ', $row['symptoms'] ),
					$row['bbt'],
					$row['mucus'],
					$row['weight'],
					$row['sleep'],
					$row['water'],
					$row['exercise'],
					$row['ovulation'] ? 'بله' : '',
				)
			);
		}
		fputcsv( $out, array() );

		fputcsv( $out, array( 'فراوانی نشانه‌ها' ) );
		fputcsv( $out, array( 'نشانه', 'تعداد روز', 'درصد روزهای ثبت‌شده' ) );
		foreach ( $data['symptoms'] as $row ) {
			fputcsv( $out, array( $row['label'], $row['times'], $row['percent'] . '%' ) );
		}

		fclose( $out );
		exit;
	}
}
