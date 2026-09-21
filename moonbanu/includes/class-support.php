<?php
/**
 * پشتیبانی درون‌اپ: تیکت، گفت‌وگو و پاسخ مدیر.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Support {

	const MAX_OPEN = 5;

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes(): void {
		$ns  = MB_Api::NS;
		$any = array( 'MB_Api', 'can_use' );

		// GET و POST در یک ثبت، تا وابسته به رفتار ادغام register_rest_route نباشیم.
		register_rest_route(
			$ns,
			'/tickets',
			array(
				array( 'methods' => 'GET', 'callback' => array( __CLASS__, 'ep_list' ), 'permission_callback' => $any ),
				array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_create' ), 'permission_callback' => $any ),
			)
		);
		register_rest_route( $ns, '/tickets/reply', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_reply' ), 'permission_callback' => $any ) );
		register_rest_route( $ns, '/tickets/close', array( 'methods' => 'POST', 'callback' => array( __CLASS__, 'ep_close' ), 'permission_callback' => $any ) );
	}

	/* --------------------------------------------------------------------- */
	/* موضوع‌ها و برچسب‌ها                                                   */
	/* --------------------------------------------------------------------- */

	public static function topics(): array {
		return array(
			'payment'  => 'پرداخت و اشتراک',
			'account'  => 'حساب و ورود',
			'bug'      => 'خطا یا مشکل فنی',
			'cycle'    => 'داده‌های چرخه',
			'partner'  => 'همراهی همسر',
			'idea'     => 'پیشنهاد و انتقاد',
			'general'  => 'سایر موارد',
		);
	}

	public static function statuses(): array {
		return array(
			'open'     => 'باز',
			'pending'  => 'در انتظار پاسخ شما',
			'answered' => 'پاسخ داده شد',
			'closed'   => 'بسته',
		);
	}

	public static function priorities(): array {
		return array( 'low' => 'کم', 'normal' => 'معمولی', 'high' => 'فوری' );
	}

	/* --------------------------------------------------------------------- */
	/* خواندن                                                               */
	/* --------------------------------------------------------------------- */

	/** تیکت‌های یک کاربر. */
	public static function user_tickets( int $user_id, int $limit = 30 ): array {
		if ( ! self::tables_ready() ) {
			return array();
		}
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . MB_DB::t( 'tickets' ) . ' WHERE user_id = %d ORDER BY FIELD(status,"answered","open","pending","closed"), updated_at DESC LIMIT %d',
				$user_id,
				max( 1, $limit )
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	public static function get_ticket( int $ticket_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . MB_DB::t( 'tickets' ) . ' WHERE id = %d', $ticket_id ), ARRAY_A );
		return $row ? $row : null;
	}

	public static function messages( int $ticket_id, int $limit = 100 ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . MB_DB::t( 'ticket_messages' ) . ' WHERE ticket_id = %d ORDER BY id ASC LIMIT %d',
				$ticket_id,
				max( 1, $limit )
			),
			ARRAY_A
		);
		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * آیا جدول تیکت‌ها ساخته شده؟
	 *
	 * در لحظهٔ ارتقا از نسخه‌های قدیمی، ممکن است کوئری پیش از dbDelta اجرا شود؛
	 * این نگهبان جلوی خطای دیتابیس را می‌گیرد.
	 */
	public static function tables_ready(): bool {
		static $ready = null;
		if ( null !== $ready ) {
			return $ready;
		}
		global $wpdb;
		$table = MB_DB::t( 'tickets' );
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		$ready = ( $found === $table );
		return $ready;
	}

	/** تعداد پاسخ‌های خوانده‌نشدهٔ کاربر. */
	public static function unread_for_user( int $user_id ): int {
		if ( ! self::tables_ready() ) {
			return 0;
		}
		global $wpdb;
		return (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . MB_DB::t( 'tickets' ) . ' WHERE user_id = %d AND unread_user = 1', $user_id )
		);
	}

	/** تعداد تیکت‌های منتظر مدیر (برای نشان منو). */
	public static function unread_for_admin(): int {
		if ( ! self::tables_ready() ) {
			return 0;
		}
		global $wpdb;
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . MB_DB::t( 'tickets' ) . ' WHERE unread_admin = 1' );
	}

	/** فهرست مدیر با فیلتر. */
	public static function admin_list( string $status = '', string $search = '', int $limit = 40, int $offset = 0 ): array {
		if ( ! self::tables_ready() ) {
			return array();
		}
		global $wpdb;
		$where  = array( '1=1' );
		$params = array();
		if ( array_key_exists( $status, self::statuses() ) ) {
			$where[]  = 'status = %s';
			$params[] = $status;
		}
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where[]  = '(subject LIKE %s OR contact LIKE %s)';
			$params[] = $like;
			$params[] = $like;
		}
		$sql      = 'SELECT * FROM ' . MB_DB::t( 'tickets' ) . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY unread_admin DESC, updated_at DESC LIMIT %d OFFSET %d';
		$params[] = max( 1, $limit );
		$params[] = max( 0, $offset );
		$rows     = $wpdb->get_results( $wpdb->prepare( $sql, $params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
		return is_array( $rows ) ? $rows : array();
	}

	public static function admin_count( string $status = '' ): int {
		if ( ! self::tables_ready() ) {
			return 0;
		}
		global $wpdb;
		if ( array_key_exists( $status, self::statuses() ) ) {
			return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . MB_DB::t( 'tickets' ) . ' WHERE status = %s', $status ) );
		}
		return (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . MB_DB::t( 'tickets' ) );
	}

	/* --------------------------------------------------------------------- */
	/* نوشتن                                                                */
	/* --------------------------------------------------------------------- */

	/** ساخت تیکت تازه. */
	public static function create( int $user_id, array $args ) {
		global $wpdb;

		$subject = trim( sanitize_text_field( (string) ( $args['subject'] ?? '' ) ) );
		$body    = trim( sanitize_textarea_field( (string) ( $args['body'] ?? '' ) ) );
		$topic   = array_key_exists( (string) ( $args['topic'] ?? '' ), self::topics() ) ? (string) $args['topic'] : 'general';
		$prio    = array_key_exists( (string) ( $args['priority'] ?? '' ), self::priorities() ) ? (string) $args['priority'] : 'normal';
		$contact = trim( sanitize_text_field( (string) ( $args['contact'] ?? '' ) ) );

		if ( mb_strlen( $subject ) < 3 ) {
			return new WP_Error( 'mb_ticket', 'عنوان درخواست را کامل‌تر بنویس.' );
		}
		if ( mb_strlen( $body ) < 15 ) {
			return new WP_Error( 'mb_ticket', 'توضیح درخواست را کمی کامل‌تر بنویس (دست‌کم ۱۵ نویسه).' );
		}
		if ( mb_strlen( $body ) > 4000 ) {
			return new WP_Error( 'mb_ticket', 'متن درخواست بیش از حد بلند است.' );
		}
		if ( '' === $contact ) {
			$user    = get_userdata( $user_id );
			$contact = $user ? (string) $user->user_email : '';
		}

		$open = (int) $wpdb->get_var(
			$wpdb->prepare( 'SELECT COUNT(*) FROM ' . MB_DB::t( 'tickets' ) . ' WHERE user_id = %d AND status <> "closed"', $user_id )
		);
		if ( $open >= self::MAX_OPEN ) {
			return new WP_Error( 'mb_ticket', 'چند درخواست باز داری. ابتدا یکی را ببند یا منتظر پاسخ بمان.' );
		}

		$now = current_time( 'mysql' );
		$wpdb->insert(
			MB_DB::t( 'tickets' ),
			array(
				'user_id'      => $user_id,
				'subject'      => $subject,
				'topic'        => $topic,
				'priority'     => $prio,
				'status'       => 'open',
				'contact'      => $contact,
				'unread_user'  => 0,
				'unread_admin' => 1,
				'created_at'   => $now,
				'updated_at'   => $now,
			)
		);
		$ticket_id = (int) $wpdb->insert_id;
		if ( ! $ticket_id ) {
			return new WP_Error( 'mb_ticket', 'ثبت درخواست ممکن نشد.' );
		}

		self::add_message( $ticket_id, $user_id, 'user', $body );
		MB_Notify::ticket_admin_alert( $ticket_id, $subject, $body );
		MB_Plugin::log_security( 'ticket_created', array( 'user' => $user_id, 'ticket' => $ticket_id ) );

		return $ticket_id;
	}

	/** افزودن پیام به گفت‌وگو. */
	public static function add_message( int $ticket_id, int $author_id, string $role, string $body ): int {
		global $wpdb;
		$role = 'staff' === $role ? 'staff' : 'user';
		$wpdb->insert(
			MB_DB::t( 'ticket_messages' ),
			array(
				'ticket_id'   => $ticket_id,
				'author_id'   => $author_id,
				'author_role' => $role,
				'body'        => $body,
				'created_at'  => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	/** پاسخ کاربر. */
	public static function user_reply( int $user_id, int $ticket_id, string $body ) {
		global $wpdb;
		$ticket = self::get_ticket( $ticket_id );
		if ( ! $ticket || (int) $ticket['user_id'] !== $user_id ) {
			return new WP_Error( 'mb_ticket', 'درخواست پیدا نشد.' );
		}
		if ( 'closed' === $ticket['status'] ) {
			return new WP_Error( 'mb_ticket', 'این درخواست بسته شده است. یک درخواست تازه بساز.' );
		}
		$body = trim( sanitize_textarea_field( $body ) );
		if ( mb_strlen( $body ) < 2 ) {
			return new WP_Error( 'mb_ticket', 'پیامت خالی است.' );
		}
		if ( mb_strlen( $body ) > 4000 ) {
			return new WP_Error( 'mb_ticket', 'متن پیام بیش از حد بلند است.' );
		}

		self::add_message( $ticket_id, $user_id, 'user', $body );
		$wpdb->update(
			MB_DB::t( 'tickets' ),
			array( 'status' => 'open', 'unread_admin' => 1, 'unread_user' => 0, 'updated_at' => current_time( 'mysql' ) ),
			array( 'id' => $ticket_id )
		);
		MB_Notify::ticket_admin_alert( $ticket_id, (string) $ticket['subject'], $body, true );
		return true;
	}

	/** پاسخ مدیر. */
	public static function staff_reply( int $ticket_id, string $body, string $status = 'answered' ) {
		global $wpdb;
		$ticket = self::get_ticket( $ticket_id );
		if ( ! $ticket ) {
			return new WP_Error( 'mb_ticket', 'تیکت پیدا نشد.' );
		}
		$body = trim( sanitize_textarea_field( $body ) );
		if ( '' !== $body ) {
			self::add_message( $ticket_id, get_current_user_id(), 'staff', $body );
		}
		$status = array_key_exists( $status, self::statuses() ) ? $status : 'answered';

		$wpdb->update(
			MB_DB::t( 'tickets' ),
			array(
				'status'       => $status,
				'unread_admin' => 0,
				'unread_user'  => '' !== $body ? 1 : (int) $ticket['unread_user'],
				'updated_at'   => current_time( 'mysql' ),
			),
			array( 'id' => $ticket_id )
		);

		if ( '' !== $body ) {
			MB_DB::add_notification(
				(int) $ticket['user_id'],
				'ticket_reply',
				array(
					'title'  => 'پاسخ پشتیبانی آمد',
					'body'   => 'برای «' . (string) $ticket['subject'] . '» پاسخ تازه‌ای ثبت شد.',
					'ticket' => $ticket_id,
				)
			);
			MB_Notify::ticket_user_reply( (int) $ticket['user_id'], $ticket_id, (string) $ticket['subject'], $body );
		}
		return true;
	}

	public static function set_status( int $ticket_id, string $status ): void {
		global $wpdb;
		if ( ! array_key_exists( $status, self::statuses() ) ) {
			return;
		}
		$wpdb->update(
			MB_DB::t( 'tickets' ),
			array( 'status' => $status, 'updated_at' => current_time( 'mysql' ), 'unread_admin' => 0 ),
			array( 'id' => $ticket_id )
		);
	}

	public static function delete( int $ticket_id ): void {
		global $wpdb;
		$wpdb->delete( MB_DB::t( 'ticket_messages' ), array( 'ticket_id' => $ticket_id ), array( '%d' ) );
		$wpdb->delete( MB_DB::t( 'tickets' ), array( 'id' => $ticket_id ), array( '%d' ) );
	}

	/** خوانده‌شدن پاسخ‌ها توسط کاربر. */
	public static function mark_read_by_user( int $user_id, int $ticket_id ): void {
		global $wpdb;
		$wpdb->update( MB_DB::t( 'tickets' ), array( 'unread_user' => 0 ), array( 'id' => $ticket_id, 'user_id' => $user_id ) );
	}

	/** پاک‌سازی داده‌های پشتیبانی یک کاربر (حذف حساب). */
	public static function purge_user( int $user_id ): void {
		global $wpdb;
		$ids = $wpdb->get_col( $wpdb->prepare( 'SELECT id FROM ' . MB_DB::t( 'tickets' ) . ' WHERE user_id = %d', $user_id ) );
		foreach ( (array) $ids as $id ) {
			self::delete( (int) $id );
		}
	}

	/* --------------------------------------------------------------------- */
	/* اندپوینت‌ها                                                          */
	/* --------------------------------------------------------------------- */

	public static function ep_list( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		$id  = (int) $r->get_param( 'id' );
		if ( $id > 0 ) {
			$ticket = self::get_ticket( $id );
			if ( ! $ticket || (int) $ticket['user_id'] !== $uid ) {
				return new WP_Error( 'mb_ticket', 'درخواست پیدا نشد.', array( 'status' => 404 ) );
			}
			self::mark_read_by_user( $uid, $id );
			return new WP_REST_Response(
				array( 'ticket' => self::public_row( $ticket ), 'messages' => self::public_messages( $id ) ),
				200
			);
		}
		$rows = array();
		foreach ( self::user_tickets( $uid ) as $row ) {
			$rows[] = self::public_row( $row );
		}
		return new WP_REST_Response( array( 'items' => $rows ), 200 );
	}

	public static function ep_create( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_License::rate_limit( 'ticket_' . $uid, 8, 3600 ) ) {
			return new WP_Error( 'mb_rate', 'تعداد درخواست‌ها زیاد است. کمی بعد تلاش کن.', array( 'status' => 429 ) );
		}
		$res = self::create(
			$uid,
			array(
				'subject'  => (string) $r->get_param( 'subject' ),
				'body'     => (string) $r->get_param( 'body' ),
				'topic'    => (string) $r->get_param( 'topic' ),
				'priority' => (string) $r->get_param( 'priority' ),
				'contact'  => (string) $r->get_param( 'contact' ),
			)
		);
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'mb_ticket', $res->get_error_message(), array( 'status' => 400 ) );
		}
		return new WP_REST_Response( array( 'ok' => true, 'id' => (int) $res, 'message' => 'درخواست پشتیبانی ثبت شد. پاسخ را همین‌جا می‌بینی.' ), 200 );
	}

	public static function ep_reply( WP_REST_Request $r ) {
		$uid = get_current_user_id();
		if ( ! MB_License::rate_limit( 'ticket_reply_' . $uid, 30, 3600 ) ) {
			return new WP_Error( 'mb_rate', 'تعداد پیام‌ها زیاد است.', array( 'status' => 429 ) );
		}
		$res = self::user_reply( $uid, (int) $r->get_param( 'id' ), (string) $r->get_param( 'body' ) );
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'mb_ticket', $res->get_error_message(), array( 'status' => 400 ) );
		}
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'پیامت ثبت شد' ), 200 );
	}

	public static function ep_close( WP_REST_Request $r ) {
		$uid    = get_current_user_id();
		$id     = (int) $r->get_param( 'id' );
		$ticket = self::get_ticket( $id );
		if ( ! $ticket || (int) $ticket['user_id'] !== $uid ) {
			return new WP_Error( 'mb_ticket', 'درخواست پیدا نشد.', array( 'status' => 404 ) );
		}
		self::set_status( $id, 'closed' );
		return new WP_REST_Response( array( 'ok' => true, 'message' => 'درخواست بسته شد' ), 200 );
	}

	/* --------------------------------------------------------------------- */
	/* خروجی عمومی                                                          */
	/* --------------------------------------------------------------------- */

	public static function public_row( array $row ): array {
		$topics = self::topics();
		return array(
			'id'          => (int) $row['id'],
			'subject'     => (string) $row['subject'],
			'topic'       => $topics[ (string) $row['topic'] ] ?? 'سایر موارد',
			'status'      => (string) $row['status'],
			'status_fa'   => self::statuses()[ (string) $row['status'] ] ?? '',
			'priority'    => (string) $row['priority'],
			'unread'      => (int) $row['unread_user'],
			'created_fa'  => MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $row['created_at'] ) ), 'long' ),
			'updated_fa'  => MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $row['updated_at'] ) ), 'long' ),
		);
	}

	public static function public_messages( int $ticket_id ): array {
		$out = array();
		foreach ( self::messages( $ticket_id ) as $msg ) {
			$out[] = array(
				'id'      => (int) $msg['id'],
				'role'    => (string) $msg['author_role'],
				'body'    => (string) $msg['body'],
				'time_fa' => MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $msg['created_at'] ) ), 'short' )
					. ' · ' . MB_Jalali::fa_num( gmdate( 'H:i', strtotime( (string) $msg['created_at'] ) ) ),
			);
		}
		return $out;
	}
}
