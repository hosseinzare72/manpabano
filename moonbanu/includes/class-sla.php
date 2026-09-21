<?php
/**
 * N8 — SLA پاسخ پشتیبانی.
 *
 *  - متن ثابت «۱۰ دقیقه تا ۲ ساعت» در پشتیبانی و انجمن.
 *  - تایمر هر تیکت در ادمین + هشدار قرمز اگر بیش از دو ساعت بی‌پاسخ مانده باشد.
 *  - digest ایمیلی هر ۳۰ دقیقه، فقط وقتی موردی در انتظار پاسخ هست.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_SLA {

	/** آستانهٔ قرمز. */
	const LIMIT = 2 * HOUR_IN_SECONDS;

	const HOOK = 'mb_sla_digest';

	const TEXT = 'زمان پاسخ معمولاً بین ۱۰ دقیقه تا ۲ ساعت است.';

	public static function init(): void {
		add_filter( 'cron_schedules', array( __CLASS__, 'add_interval' ) );
		add_action( self::HOOK, array( __CLASS__, 'run_digest' ) );
	}

	public static function add_interval( $schedules ) {
		$schedules = is_array( $schedules ) ? $schedules : array();
		if ( ! isset( $schedules['mb_half_hour'] ) ) {
			$schedules['mb_half_hour'] = array( 'interval' => 30 * MINUTE_IN_SECONDS, 'display' => 'هر ۳۰ دقیقه (ماه‌بانو)' );
		}
		return $schedules;
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + 300, 'mb_half_hour', self::HOOK );
		}
	}

	public static function unschedule(): void {
		$ts = wp_next_scheduled( self::HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::HOOK );
		}
	}

	/* --------------------------------------------------------------------- */
	/* تایمر تیکت                                                            */
	/* --------------------------------------------------------------------- */

	/**
	 * وضعیت SLA یک تیکت.
	 *
	 * @return array{waiting:bool,seconds:int,label:string,breach:bool}
	 */
	public static function ticket_state( array $ticket ): array {
		$status  = (string) ( $ticket['status'] ?? 'open' );
		$waiting = in_array( $status, array( 'open', 'pending' ), true ) || 1 === (int) ( $ticket['unread_admin'] ?? 0 );

		if ( ! $waiting || 'closed' === $status ) {
			return array( 'waiting' => false, 'seconds' => 0, 'label' => '—', 'breach' => false );
		}

		$since   = strtotime( (string) ( $ticket['updated_at'] ?? $ticket['created_at'] ?? '' ) );
		$seconds = $since ? max( 0, current_time( 'timestamp' ) - $since ) : 0;

		return array(
			'waiting' => true,
			'seconds' => $seconds,
			'label'   => self::duration_fa( $seconds ),
			'breach'  => $seconds > self::LIMIT,
		);
	}

	public static function duration_fa( int $seconds ): string {
		if ( $seconds < MINUTE_IN_SECONDS ) {
			return 'همین الان';
		}
		if ( $seconds < HOUR_IN_SECONDS ) {
			return MB_Jalali::fa_num( (int) floor( $seconds / MINUTE_IN_SECONDS ) ) . ' دقیقه';
		}
		$hours   = (int) floor( $seconds / HOUR_IN_SECONDS );
		$minutes = (int) floor( ( $seconds % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
		if ( $hours < 24 ) {
			return MB_Jalali::fa_num( $hours ) . ' ساعت' . ( $minutes > 0 ? ' و ' . MB_Jalali::fa_num( $minutes ) . ' دقیقه' : '' );
		}
		return MB_Jalali::fa_num( (int) floor( $hours / 24 ) ) . ' روز و ' . MB_Jalali::fa_num( $hours % 24 ) . ' ساعت';
	}

	/* --------------------------------------------------------------------- */
	/* تیکت‌های در انتظار                                                    */
	/* --------------------------------------------------------------------- */

	/** تیکت‌های بی‌پاسخ، تازه‌ترین اول. */
	public static function pending_tickets( int $limit = 50 ): array {
		global $wpdb;
		if ( ! MB_Support::tables_ready() ) {
			return array();
		}
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . MB_DB::t( 'tickets' ) . " WHERE status IN ('open','pending') ORDER BY updated_at ASC LIMIT %d",
				max( 1, min( 200, $limit ) )
			),
			ARRAY_A
		);
	}

	/** تعداد تیکت‌هایی که از آستانهٔ دو ساعت گذشته‌اند. */
	public static function breach_count(): int {
		$count = 0;
		foreach ( self::pending_tickets( 200 ) as $ticket ) {
			if ( self::ticket_state( $ticket )['breach'] ) {
				++$count;
			}
		}
		return $count;
	}

	/* --------------------------------------------------------------------- */
	/* digest نیم‌ساعته                                                      */
	/* --------------------------------------------------------------------- */

	/** ایمیل خلاصه؛ اگر هیچ موردی در انتظار نباشد هیچ ایمیلی فرستاده نمی‌شود. */
	public static function run_digest(): void {
		$pending = self::pending_tickets( 30 );
		if ( empty( $pending ) ) {
			return;
		}

		$rows    = '';
		$breach  = 0;
		foreach ( $pending as $ticket ) {
			$state = self::ticket_state( $ticket );
			if ( $state['breach'] ) {
				++$breach;
			}
			$user = get_userdata( (int) $ticket['user_id'] );
			$rows .= '<tr>'
				. '<td>#' . (int) $ticket['id'] . '</td>'
				. '<td>' . esc_html( (string) $ticket['subject'] ) . '</td>'
				. '<td>' . esc_html( $user ? $user->display_name : '—' ) . '</td>'
				. '<td style="color:' . ( $state['breach'] ? '#c0392b' : '#555' ) . '">' . esc_html( $state['label'] ) . '</td>'
				. '</tr>';
		}

		$subject = sprintf(
			'ماه‌بانو — %s مورد در انتظار پاسخ%s',
			MB_Jalali::fa_num( count( $pending ) ),
			$breach > 0 ? ' (' . MB_Jalali::fa_num( $breach ) . ' مورد از ۲ ساعت گذشته)' : ''
		);

		$html = '<p>' . esc_html( self::TEXT ) . '</p>'
			. '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%">'
			. '<thead><tr><th>شناسه</th><th>موضوع</th><th>کاربر</th><th>مدت انتظار</th></tr></thead>'
			. '<tbody>' . $rows . '</tbody></table>'
			. '<p><a href="' . esc_url( add_query_arg( array( 'page' => 'moonbanu', 'tab' => 'support' ), admin_url( 'admin.php' ) ) ) . '">باز کردن میز پشتیبانی</a></p>';

		MB_Notify::admin_alert( $subject, $html );
	}
}
