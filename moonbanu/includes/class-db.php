<?php
/**
 * لایه دیتابیس ماه‌بانو: ساخت جداول، رمزنگاری و همه پرس‌وجوها با prepare().
 *
 * نکته: ستون‌های ساختار JSON از نوع LONGTEXT ساخته می‌شوند تا dbDelta در
 * MariaDB (که JSON را به LONGTEXT alias می‌کند) در هر بار اجرا جدول را دستکاری نکند.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_DB {

	public static function t( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'mb_' . $name;
	}

	/* --------------------------------------------------------------------- */
	/* نصب                                                                   */
	/* --------------------------------------------------------------------- */

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();

		$sql = array();

		$sql[] = "CREATE TABLE " . self::t( 'profile' ) . " (
			user_id bigint(20) unsigned NOT NULL,
			cycle_len tinyint(3) unsigned NOT NULL DEFAULT 28,
			period_len tinyint(3) unsigned NOT NULL DEFAULT 5,
			luteal_len tinyint(3) unsigned NOT NULL DEFAULT 14,
			last_period_start date DEFAULT NULL,
			onboarded tinyint(1) NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (user_id)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'logs' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			log_date date NOT NULL,
			jalali varchar(16) NOT NULL DEFAULT '',
			mood tinyint(3) unsigned DEFAULT NULL,
			bleeding enum('none','spot','light','med','heavy') NOT NULL DEFAULT 'none',
			pain tinyint(3) unsigned NOT NULL DEFAULT 0,
			symptoms longtext NULL,
			note_private text NULL,
			bbt decimal(3,1) DEFAULT NULL,
			mucus enum('none','dry','sticky','creamy','eggwhite','watery') NOT NULL DEFAULT 'none',
			sex enum('none','protected','unprotected') NOT NULL DEFAULT 'none',
			weight_kg decimal(5,2) DEFAULT NULL,
			sleep_h decimal(3,1) DEFAULT NULL,
			water_cups tinyint(3) unsigned DEFAULT NULL,
			exercise_min smallint(5) unsigned DEFAULT NULL,
			meds longtext NULL,
			ovulation_confirmed tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY user_day (user_id,log_date),
			KEY user_date (user_id,log_date)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'cycles' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			start_date date NOT NULL,
			length tinyint(3) unsigned NOT NULL DEFAULT 28,
			period_length tinyint(3) unsigned NOT NULL DEFAULT 5,
			PRIMARY KEY  (id),
			UNIQUE KEY user_start (user_id,start_date),
			KEY user_idx (user_id)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'invites' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			wife_id bigint(20) unsigned NOT NULL,
			token char(12) NOT NULL,
			channel enum('email','sms') NOT NULL DEFAULT 'email',
			target varchar(190) NOT NULL DEFAULT '',
			expires_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			revoked tinyint(1) NOT NULL DEFAULT 0,
			share_pms tinyint(1) NOT NULL DEFAULT 1,
			share_period tinyint(1) NOT NULL DEFAULT 1,
			share_support tinyint(1) NOT NULL DEFAULT 1,
			share_fertility tinyint(1) NOT NULL DEFAULT 0,
			share_status tinyint(1) NOT NULL DEFAULT 0,
			accepted_by bigint(20) unsigned DEFAULT NULL,
			accepted_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY token (token),
			KEY wife (wife_id),
			KEY accepted (accepted_by)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'notifications' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			type varchar(40) NOT NULL DEFAULT '',
			payload longtext NULL,
			is_read tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_read (user_id,is_read),
			KEY user_type (user_id,type,created_at)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'questions' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			body text NOT NULL,
			status enum('pending','answered') NOT NULL DEFAULT 'pending',
			answer text NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_idx (user_id,created_at)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'subscriptions' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			couple_id varchar(32) NOT NULL DEFAULT '',
			plan enum('free','pro') NOT NULL DEFAULT 'free',
			term_months tinyint(3) unsigned NOT NULL DEFAULT 1,
			status enum('pending','active','grace','expired','canceled') NOT NULL DEFAULT 'pending',
			gateway varchar(40) NOT NULL DEFAULT 'zibal',
			ref_id varchar(64) NOT NULL DEFAULT '',
			authority varchar(80) NOT NULL DEFAULT '',
			amount int(11) unsigned NOT NULL DEFAULT 0,
			coupon varchar(40) DEFAULT NULL,
			site_hash varchar(64) NOT NULL DEFAULT '',
			install_token varchar(64) NOT NULL DEFAULT '',
			started_at datetime DEFAULT NULL,
			expires_at datetime DEFAULT NULL,
			last_verify_at datetime DEFAULT NULL,
			remind tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			UNIQUE KEY ref_id (ref_id),
			KEY user_status (user_id,status),
			KEY couple_idx (couple_id,status),
			KEY authority (authority),
			KEY expires (expires_at)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'coupons' ) . " (
			code varchar(40) NOT NULL,
			percent tinyint(3) unsigned NOT NULL DEFAULT 0,
			amount_off int(11) unsigned NOT NULL DEFAULT 0,
			min_amount int(11) unsigned NOT NULL DEFAULT 0,
			active tinyint(1) NOT NULL DEFAULT 1,
			note varchar(190) NOT NULL DEFAULT '',
			expires_at datetime DEFAULT NULL,
			max_uses int(11) unsigned NOT NULL DEFAULT 0,
			uses int(11) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (code),
			KEY active_idx (active)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'tickets' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			subject varchar(190) NOT NULL DEFAULT '',
			topic varchar(40) NOT NULL DEFAULT 'general',
			priority enum('low','normal','high') NOT NULL DEFAULT 'normal',
			status enum('open','answered','pending','closed') NOT NULL DEFAULT 'open',
			contact varchar(190) NOT NULL DEFAULT '',
			unread_user tinyint(1) NOT NULL DEFAULT 0,
			unread_admin tinyint(1) NOT NULL DEFAULT 1,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_idx (user_id,status),
			KEY status_idx (status,updated_at)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'ticket_messages' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) unsigned NOT NULL,
			author_id bigint(20) unsigned NOT NULL DEFAULT 0,
			author_role enum('user','staff') NOT NULL DEFAULT 'user',
			body text NOT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY ticket_idx (ticket_id,id)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'security_log' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event varchar(60) NOT NULL DEFAULT '',
			detail longtext NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY event_idx (event,created_at)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'pregnancy' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			lmp_date date NOT NULL,
			due_date date NOT NULL,
			active tinyint(1) NOT NULL DEFAULT 1,
			share_pregnancy tinyint(1) NOT NULL DEFAULT 0,
			ended_at datetime DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_active (user_id,active),
			KEY user_idx (user_id)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'community' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			question_id bigint(20) unsigned NOT NULL DEFAULT 0,
			question text NOT NULL,
			answer text NULL,
			cat varchar(40) NOT NULL DEFAULT 'period',
			status enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
			anon_label varchar(40) NOT NULL DEFAULT '',
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY status_idx (status,created_at),
			KEY cat_idx (cat,status),
			KEY question_ref (question_id)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'quizzes' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			slug varchar(40) NOT NULL,
			title varchar(190) NOT NULL DEFAULT '',
			intro text NULL,
			questions longtext NULL,
			bands longtext NULL,
			cat varchar(40) NOT NULL DEFAULT 'period',
			disclaimer varchar(190) NOT NULL DEFAULT '',
			active tinyint(1) NOT NULL DEFAULT 1,
			sort_order tinyint(3) unsigned NOT NULL DEFAULT 0,
			PRIMARY KEY  (id),
			UNIQUE KEY slug (slug),
			KEY active_idx (active,sort_order)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'quiz_results' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			quiz_id bigint(20) unsigned NOT NULL,
			score smallint(5) unsigned NOT NULL DEFAULT 0,
			max_score smallint(5) unsigned NOT NULL DEFAULT 0,
			band varchar(40) NOT NULL DEFAULT '',
			answers longtext NULL,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_quiz (user_id,quiz_id,created_at)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'health_profile' ) . " (
			user_id bigint(20) unsigned NOT NULL,
			height_cm smallint(5) unsigned DEFAULT NULL,
			weight_kg decimal(5,1) unsigned DEFAULT NULL,
			bmi decimal(4,1) unsigned DEFAULT NULL,
			waist_cm smallint(5) unsigned DEFAULT NULL,
			blood_type enum('','A+','A-','B+','B-','AB+','AB-','O+','O-') NOT NULL DEFAULT '',
			national_id varchar(255) NOT NULL DEFAULT '',
			emergency_name varchar(120) NOT NULL DEFAULT '',
			emergency_phone varchar(20) NOT NULL DEFAULT '',
			medical_note text NULL,
			emergency_alert tinyint(1) NOT NULL DEFAULT 0,
			updated_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (user_id)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'emergency_log' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			reason varchar(32) NOT NULL DEFAULT 'sos',
			result varchar(32) NOT NULL DEFAULT 'sent',
			sms_to varchar(20) NOT NULL DEFAULT '',
			partner_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (id),
			KEY user_idx (user_id,created_at)
		) $c;";

		$sql[] = "CREATE TABLE " . self::t( 'referrals' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			referrer bigint(20) unsigned NOT NULL,
			referee bigint(20) unsigned NOT NULL,
			sub_id bigint(20) unsigned NOT NULL DEFAULT 0,
			coupon varchar(40) NOT NULL DEFAULT '',
			rewarded tinyint(1) NOT NULL DEFAULT 0,
			created_at datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
			rewarded_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY referee (referee),
			KEY referrer_idx (referrer,rewarded)
		) $c;";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}

	/* --------------------------------------------------------------------- */
	/* رمزنگاری یادداشت خصوصی (AES-256-CBC، کلید مشتق از AUTH_KEY)            */
	/* --------------------------------------------------------------------- */

	private static function crypt_key(): string {
		$salt = defined( 'AUTH_KEY' ) ? AUTH_KEY : 'moonbanu-fallback-key';
		$salt .= defined( 'AUTH_SALT' ) ? AUTH_SALT : '';
		return hash( 'sha256', 'mb-note|' . $salt, true );
	}

	public static function encrypt( string $plain ): string {
		if ( '' === $plain ) {
			return '';
		}
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			return 'plain:' . base64_encode( $plain );
		}
		$iv     = random_bytes( 16 );
		$cipher = openssl_encrypt( $plain, 'aes-256-cbc', self::crypt_key(), OPENSSL_RAW_DATA, $iv );
		if ( false === $cipher ) {
			return '';
		}
		return 'v1:' . base64_encode( $iv . $cipher );
	}

	public static function decrypt( ?string $stored ): string {
		$stored = (string) $stored;
		if ( '' === $stored ) {
			return '';
		}
		if ( 0 === strpos( $stored, 'plain:' ) ) {
			return (string) base64_decode( substr( $stored, 6 ), true );
		}
		if ( 0 !== strpos( $stored, 'v1:' ) ) {
			return '';
		}
		$raw = base64_decode( substr( $stored, 3 ), true );
		if ( false === $raw || strlen( $raw ) <= 16 || ! function_exists( 'openssl_decrypt' ) ) {
			return '';
		}
		$iv    = substr( $raw, 0, 16 );
		$data  = substr( $raw, 16 );
		$plain = openssl_decrypt( $data, 'aes-256-cbc', self::crypt_key(), OPENSSL_RAW_DATA, $iv );
		return false === $plain ? '' : $plain;
	}

	/* --------------------------------------------------------------------- */
	/* پروفایل                                                               */
	/* --------------------------------------------------------------------- */

	public static function get_profile( int $user_id ): array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'profile' ) . ' WHERE user_id = %d', $user_id ), ARRAY_A );
		if ( ! $row ) {
			$row = array(
				'user_id'           => $user_id,
				'cycle_len'         => 28,
				'period_len'        => 5,
				'luteal_len'        => 14,
				'last_period_start' => null,
				'onboarded'         => 0,
			);
		}
		$row['cycle_len']  = (int) $row['cycle_len'];
		$row['period_len'] = (int) $row['period_len'];
		$row['luteal_len'] = (int) $row['luteal_len'];
		$row['onboarded']  = (int) $row['onboarded'];
		return $row;
	}

	public static function save_profile( int $user_id, array $data ): void {
		global $wpdb;
		$current = self::get_profile( $user_id );
		$row     = array(
			'user_id'           => $user_id,
			'cycle_len'         => isset( $data['cycle_len'] ) ? max( 21, min( 35, (int) $data['cycle_len'] ) ) : $current['cycle_len'],
			'period_len'        => isset( $data['period_len'] ) ? max( 2, min( 7, (int) $data['period_len'] ) ) : $current['period_len'],
			'luteal_len'        => isset( $data['luteal_len'] ) ? max( 10, min( 16, (int) $data['luteal_len'] ) ) : $current['luteal_len'],
			'last_period_start' => isset( $data['last_period_start'] ) ? MB_Jalali::sanitize_date( $data['last_period_start'] ) : $current['last_period_start'],
			'onboarded'         => isset( $data['onboarded'] ) ? (int) (bool) $data['onboarded'] : $current['onboarded'],
			'updated_at'        => current_time( 'mysql' ),
		);

		$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM ' . self::t( 'profile' ) . ' WHERE user_id = %d', $user_id ) );
		if ( $exists ) {
			unset( $row['user_id'] );
			$wpdb->update( self::t( 'profile' ), $row, array( 'user_id' => $user_id ) );
		} else {
			$wpdb->insert( self::t( 'profile' ), $row );
		}
	}

	public static function is_onboarded( int $user_id ): bool {
		$p = self::get_profile( $user_id );
		return 1 === (int) $p['onboarded'] && ! empty( $p['last_period_start'] );
	}

	/* --------------------------------------------------------------------- */
	/* ثبت روزانه                                                            */
	/* --------------------------------------------------------------------- */

	public static function save_log( int $user_id, array $data ): int {
		global $wpdb;
		$date     = MB_Jalali::sanitize_date( $data['log_date'] ?? MB_Jalali::today() );
		$bleeding = in_array( $data['bleeding'] ?? 'none', array( 'none', 'spot', 'light', 'med', 'heavy' ), true ) ? $data['bleeding'] : 'none';
		$symptoms = array();
		foreach ( (array) ( $data['symptoms'] ?? array() ) as $s ) {
			$s = sanitize_key( (string) $s );
			if ( '' !== $s && in_array( $s, array_keys( self::symptom_map() ), true ) ) {
				$symptoms[] = $s;
			}
		}
		$mood = isset( $data['mood'] ) && '' !== $data['mood'] && null !== $data['mood'] ? max( 1, min( 5, (int) $data['mood'] ) ) : null;
		$note = isset( $data['note_private'] ) ? sanitize_textarea_field( (string) $data['note_private'] ) : '';

		$row = array(
			'user_id'      => $user_id,
			'log_date'     => $date,
			'jalali'       => MB_Jalali::format_fa( $date, 'num' ),
			'mood'         => $mood,
			'bleeding'     => $bleeding,
			'pain'         => max( 0, min( 5, (int) ( $data['pain'] ?? 0 ) ) ),
			'symptoms'     => wp_json_encode( array_values( array_unique( $symptoms ) ) ),
			'note_private' => self::encrypt( $note ),
			'created_at'   => current_time( 'mysql' ),
		);

		// ردیاب‌های روزانه و فیلدهای TTC: همه با اعتبارسنجی بازه‌ای سمت سرور.
		// هر مقدار خارج از بازه به NULL تبدیل می‌شود، نه به مقدار بُرش‌خورده،
		// تا داده‌ای که کاربر واقعاً ثبت نکرده در تحلیل و گزارش پزشک وارد نشود.
		$row = array_merge( $row, self::sanitize_tracker_fields( $data ) );

		$existing = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::t( 'logs' ) . ' WHERE user_id = %d AND log_date = %s', $user_id, $date ) );
		if ( $existing ) {
			unset( $row['created_at'] );
			$wpdb->update( self::t( 'logs' ), $row, array( 'id' => (int) $existing ) );
			$id = (int) $existing;
		} else {
			$wpdb->insert( self::t( 'logs' ), $row );
			$id = (int) $wpdb->insert_id;
		}

		self::sync_cycles_from_bleeding( $user_id, $date, $bleeding );
		delete_transient( 'mb_stats_' . $user_id );
		return $id;
	}

	public static function get_log( int $user_id, string $date ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'logs' ) . ' WHERE user_id = %d AND log_date = %s', $user_id, MB_Jalali::sanitize_date( $date ) ), ARRAY_A );
		if ( ! $row ) {
			return null;
		}
		$row['symptoms'] = json_decode( (string) $row['symptoms'], true );
		if ( ! is_array( $row['symptoms'] ) ) {
			$row['symptoms'] = array();
		}
		$row['meds'] = json_decode( (string) ( $row['meds'] ?? '' ), true );
		if ( ! is_array( $row['meds'] ) ) {
			$row['meds'] = array();
		}
		return $row;
	}

	/** یادداشت خصوصی فقط با این متد و فقط برای مالک. */
	public static function get_private_note( int $user_id, string $date ): string {
		global $wpdb;
		$stored = $wpdb->get_var( $wpdb->prepare( 'SELECT note_private FROM ' . self::t( 'logs' ) . ' WHERE user_id = %d AND log_date = %s', $user_id, MB_Jalali::sanitize_date( $date ) ) );
		return self::decrypt( is_string( $stored ) ? $stored : '' );
	}

	/** لاگ‌های یک بازه (بدون یادداشت خصوصی). */
	public static function get_logs_range( int $user_id, string $from, string $to ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT id,log_date,jalali,mood,bleeding,pain,symptoms,bbt,mucus,sex,weight_kg,sleep_h,water_cups,exercise_min,meds,ovulation_confirmed FROM ' . self::t( 'logs' ) . ' WHERE user_id = %d AND log_date BETWEEN %s AND %s ORDER BY log_date ASC',
				$user_id,
				MB_Jalali::sanitize_date( $from ),
				MB_Jalali::sanitize_date( $to )
			),
			ARRAY_A
		);
		$out = array();
		foreach ( (array) $rows as $r ) {
			$mb_syms             = json_decode( (string) $r['symptoms'], true );
			$r['symptoms']       = is_array( $mb_syms ) ? $mb_syms : array();
			$mb_meds             = json_decode( (string) ( $r['meds'] ?? '' ), true );
			$r['meds']           = is_array( $mb_meds ) ? $mb_meds : array();
			$out[ $r['log_date'] ] = $r;
		}
		return $out;
	}

	public static function count_logs( int $user_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::t( 'logs' ) . ' WHERE user_id = %d', $user_id ) );
	}

	/** زنجیره ثبت پیاپی تا امروز. */
	public static function streak( int $user_id ): int {
		global $wpdb;
		$dates = $wpdb->get_col( $wpdb->prepare( 'SELECT log_date FROM ' . self::t( 'logs' ) . ' WHERE user_id = %d ORDER BY log_date DESC LIMIT 400', $user_id ) );
		if ( empty( $dates ) ) {
			return 0;
		}
		$today  = MB_Jalali::today();
		$cursor = in_array( $today, $dates, true ) ? $today : MB_Jalali::add_days( $today, -1 );
		if ( ! in_array( $cursor, $dates, true ) ) {
			return 0;
		}
		$streak = 0;
		while ( in_array( $cursor, $dates, true ) ) {
			++$streak;
			$cursor = MB_Jalali::add_days( $cursor, -1 );
		}
		return $streak;
	}

	public static function symptom_map(): array {
		return array(
			'cramps'   => array( 'label' => 'گرفتگی عضلات', 'icon' => 'spark' ),
			'headache' => array( 'label' => 'سردرد', 'icon' => 'alert' ),
			'backache' => array( 'label' => 'کمردرد', 'icon' => 'wave' ),
			'bloating' => array( 'label' => 'نفخ', 'icon' => 'drop' ),
			'nausea'   => array( 'label' => 'تهوع', 'icon' => 'leaf' ),
			'insomnia' => array( 'label' => 'بی‌خوابی', 'icon' => 'moon' ),
			'breast'   => array( 'label' => 'حساسیت سینه', 'icon' => 'heart' ),
			'fatigue'  => array( 'label' => 'خستگی', 'icon' => 'battery' ),
			'acne'     => array( 'label' => 'جوش پوستی', 'icon' => 'sun' ),
			'craving'  => array( 'label' => 'هوس غذایی', 'icon' => 'cup' ),
		);
	}

	/* --------------------------------------------------------------------- */
	/* چرخه‌ها                                                                */
	/* --------------------------------------------------------------------- */

	/** اگر خونریزی واقعی ثبت شد و فاصله از آخرین شروع ≥ ۱۵ روز بود، چرخه جدید ثبت می‌شود. */
	private static function sync_cycles_from_bleeding( int $user_id, string $date, string $bleeding ): void {
		if ( in_array( $bleeding, array( 'none', 'spot' ), true ) ) {
			return;
		}
		$last = self::last_cycle_start( $user_id );
		if ( $last && MB_Jalali::diff_days( $last, $date ) < 15 ) {
			return;
		}
		self::add_cycle( $user_id, $date );
	}

	public static function add_cycle( int $user_id, string $start ): void {
		global $wpdb;
		$start   = MB_Jalali::sanitize_date( $start );
		$profile = self::get_profile( $user_id );
		$exists  = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::t( 'cycles' ) . ' WHERE user_id = %d AND start_date = %s', $user_id, $start ) );
		if ( ! $exists ) {
			$wpdb->insert(
				self::t( 'cycles' ),
				array(
					'user_id'       => $user_id,
					'start_date'    => $start,
					'length'        => (int) $profile['cycle_len'],
					'period_length' => (int) $profile['period_len'],
				),
				array( '%d', '%s', '%d', '%d' )
			);
		}
		self::recalc_cycle_lengths( $user_id );
		self::save_profile( $user_id, array( 'last_period_start' => $start ) );
		delete_transient( 'mb_stats_' . $user_id );
	}

	/** طول هر چرخه = فاصله تا شروع چرخه بعدی. */
	public static function recalc_cycle_lengths( int $user_id ): void {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id,start_date FROM ' . self::t( 'cycles' ) . ' WHERE user_id = %d ORDER BY start_date ASC', $user_id ), ARRAY_A );
		$n    = count( $rows );
		for ( $i = 0; $i < $n - 1; $i++ ) {
			$len = MB_Jalali::diff_days( $rows[ $i ]['start_date'], $rows[ $i + 1 ]['start_date'] );
			if ( $len >= 15 && $len <= 60 ) {
				$wpdb->update( self::t( 'cycles' ), array( 'length' => $len ), array( 'id' => (int) $rows[ $i ]['id'] ), array( '%d' ), array( '%d' ) );
			}
		}
	}

	public static function get_cycles( int $user_id, int $limit = 12 ): array {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'cycles' ) . ' WHERE user_id = %d ORDER BY start_date DESC LIMIT %d', $user_id, max( 1, $limit ) ), ARRAY_A );
		return array_reverse( (array) $rows );
	}

	public static function last_cycle_start( int $user_id ): ?string {
		global $wpdb;
		$v = $wpdb->get_var( $wpdb->prepare( 'SELECT start_date FROM ' . self::t( 'cycles' ) . ' WHERE user_id = %d ORDER BY start_date DESC LIMIT 1', $user_id ) );
		return $v ? (string) $v : null;
	}

	/* --------------------------------------------------------------------- */
	/* دعوت‌نامه‌ها                                                           */
	/* --------------------------------------------------------------------- */

	public static function create_invite( int $wife_id, string $channel, string $target, array $shares ): array {
		global $wpdb;
		$token = self::unique_token();
		$row   = array(
			'wife_id'         => $wife_id,
			'token'           => $token,
			'channel'         => in_array( $channel, array( 'email', 'sms' ), true ) ? $channel : 'email',
			'target'          => sanitize_text_field( $target ),
			'expires_at'      => gmdate( 'Y-m-d H:i:s', time() + 72 * HOUR_IN_SECONDS ),
			'revoked'         => 0,
			'share_pms'       => empty( $shares['share_pms'] ) ? 0 : 1,
			'share_period'    => empty( $shares['share_period'] ) ? 0 : 1,
			'share_support'   => empty( $shares['share_support'] ) ? 0 : 1,
			'share_fertility' => empty( $shares['share_fertility'] ) ? 0 : 1,
			'share_status'    => empty( $shares['share_status'] ) ? 0 : 1,
			'created_at'      => current_time( 'mysql' ),
		);
		$wpdb->insert( self::t( 'invites' ), $row );
		$row['id'] = (int) $wpdb->insert_id;
		return $row;
	}

	public static function unique_token(): string {
		global $wpdb;
		do {
			$token  = substr( str_replace( array( '0', 'O', 'l', 'I' ), array( 'a', 'b', 'c', 'd' ), wp_generate_password( 16, false, false ) ), 0, 12 );
			$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::t( 'invites' ) . ' WHERE token = %s', $token ) );
		} while ( $exists );
		return $token;
	}

	public static function get_invite_by_token( string $token ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'invites' ) . ' WHERE token = %s', sanitize_text_field( $token ) ), ARRAY_A );
		return $row ? $row : null;
	}

	/** آخرین دعوت‌نامه فعال یا پذیرفته‌شده بانو. */
	public static function get_active_invite( int $wife_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'invites' ) . ' WHERE wife_id = %d AND revoked = 0 ORDER BY id DESC LIMIT 1', $wife_id ), ARRAY_A );
		return $row ? $row : null;
	}

	public static function get_invite_for_partner( int $partner_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'invites' ) . ' WHERE accepted_by = %d AND revoked = 0 ORDER BY id DESC LIMIT 1', $partner_id ), ARRAY_A );
		return $row ? $row : null;
	}

	public static function update_invite( int $id, array $data ): void {
		global $wpdb;
		$wpdb->update( self::t( 'invites' ), $data, array( 'id' => $id ) );
	}

	public static function revoke_invites( int $wife_id ): int {
		global $wpdb;
		return (int) $wpdb->query( $wpdb->prepare( 'UPDATE ' . self::t( 'invites' ) . ' SET revoked = 1 WHERE wife_id = %d AND revoked = 0', $wife_id ) );
	}

	/**
	 * لغو فقط دعوت‌نامه‌های پذیرفته‌نشده.
	 * ساخت لینک تازه نباید دسترسی همسری که قبلاً پذیرفته را قطع کند.
	 */
	public static function revoke_pending_invites( int $wife_id ): int {
		global $wpdb;
		return (int) $wpdb->query( $wpdb->prepare( 'UPDATE ' . self::t( 'invites' ) . ' SET revoked = 1 WHERE wife_id = %d AND revoked = 0 AND accepted_by IS NULL', $wife_id ) );
	}

	/* --------------------------------------------------------------------- */
	/* اعلان‌ها                                                              */
	/* --------------------------------------------------------------------- */

	public static function add_notification( int $user_id, string $type, array $payload = array() ): int {
		global $wpdb;
		$wpdb->insert(
			self::t( 'notifications' ),
			array(
				'user_id'    => $user_id,
				'type'       => sanitize_key( $type ),
				'payload'    => wp_json_encode( $payload ),
				'is_read'    => 0,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%d', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	public static function sent_today( int $user_id, string $type ): bool {
		global $wpdb;
		$since = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - 20 * HOUR_IN_SECONDS );
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::t( 'notifications' ) . ' WHERE user_id = %d AND type = %s AND created_at >= %s LIMIT 1', $user_id, sanitize_key( $type ), $since ) );
	}

	public static function get_notifications( int $user_id, int $limit = 30 ): array {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'notifications' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d', $user_id, max( 1, $limit ) ), ARRAY_A );
		foreach ( $rows as &$r ) {
			$r['payload'] = is_array( json_decode( (string) $r['payload'], true ) ) ? json_decode( (string) $r['payload'], true ) : array();
		}
		return (array) $rows;
	}

	public static function unread_count( int $user_id ): int {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . self::t( 'notifications' ) . ' WHERE user_id = %d AND is_read = 0', $user_id ) );
	}

	public static function mark_notifications_read( int $user_id ): void {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . self::t( 'notifications' ) . ' SET is_read = 1 WHERE user_id = %d AND is_read = 0', $user_id ) );
	}

	/* --------------------------------------------------------------------- */
	/* پرسش از متخصص                                                         */
	/* --------------------------------------------------------------------- */

	public static function add_question( int $user_id, string $body ): int {
		global $wpdb;
		$wpdb->insert(
			self::t( 'questions' ),
			array(
				'user_id'    => $user_id,
				'body'       => sanitize_textarea_field( $body ),
				'status'     => 'pending',
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%s', '%s' )
		);
		return (int) $wpdb->insert_id;
	}

	public static function get_questions( int $user_id, int $limit = 10 ): array {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'questions' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d', $user_id, max( 1, $limit ) ), ARRAY_A );
	}

	/* --------------------------------------------------------------------- */
	/* لاگ امنیت                                                             */
	/* --------------------------------------------------------------------- */

	public static function add_security_log( string $event, array $detail = array() ): void {
		global $wpdb;
		$detail['ip'] = isset( $detail['ip'] ) ? $detail['ip'] : self::client_ip();
		$wpdb->insert(
			self::t( 'security_log' ),
			array(
				'event'      => sanitize_key( $event ),
				'detail'     => wp_json_encode( $detail ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s' )
		);
	}

	public static function get_security_log( int $limit = 100 ): array {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'security_log' ) . ' ORDER BY id DESC LIMIT %d', max( 1, $limit ) ), ARRAY_A );
	}

	public static function client_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '0.0.0.0';
	}

	/* --------------------------------------------------------------------- */
	/* حذف کامل داده‌های یک کاربر (WP Privacy / uninstall)                   */
	/* --------------------------------------------------------------------- */

	public static function delete_user_data( int $user_id ): void {
		global $wpdb;
		foreach ( array( 'profile', 'logs', 'cycles', 'notifications', 'questions', 'subscriptions', 'pregnancy', 'quiz_results', 'health_profile', 'emergency_log' ) as $table ) {
			$wpdb->delete( self::t( $table ), array( 'user_id' => $user_id ), array( '%d' ) );
		}
		$wpdb->delete( self::t( 'referrals' ), array( 'referrer' => $user_id ), array( '%d' ) );
		$wpdb->delete( self::t( 'referrals' ), array( 'referee' => $user_id ), array( '%d' ) );
		$wpdb->delete( self::t( 'invites' ), array( 'wife_id' => $user_id ), array( '%d' ) );
		$wpdb->delete( self::t( 'invites' ), array( 'accepted_by' => $user_id ), array( '%d' ) );
		delete_user_meta( $user_id, 'mb_partner_of' );
		delete_user_meta( $user_id, 'mb_mobile' );
		delete_user_meta( $user_id, 'mb_saved_articles' );
		delete_user_meta( $user_id, 'mb_partner_state' );
		delete_user_meta( $user_id, 'mb_onboard_step' );
		delete_user_meta( $user_id, 'mb_reminder_prefs' );
		delete_user_meta( $user_id, 'mb_meds_reminder' );
		delete_user_meta( $user_id, 'mb_ref_code' );
		delete_user_meta( $user_id, 'mb_pregnancy_checklist' );
		delete_user_meta( $user_id, 'mb_couple_id' );
		// متاهای امنیتی/مالی که پیش از این باقی می‌ماندند.
		delete_user_meta( $user_id, 'mb_reset_hash' );
		delete_user_meta( $user_id, 'mb_reset_exp' );
		delete_user_meta( $user_id, 'mb_reset_tries' );
		delete_user_meta( $user_id, 'mb_coupons_used' );
		delete_user_meta( $user_id, 'mb_terms_at' );
		delete_user_meta( $user_id, 'mb_signup_source' );
		delete_transient( 'mb_stats_' . $user_id );
		delete_transient( 'mb_pro_' . $user_id );
		delete_transient( 'mb_otp_' . $user_id );
		delete_transient( 'mb_psess_' . $user_id );
		delete_transient( 'mb_lifecycle_' . $user_id );
	}

	/* --------------------------------------------------------------------- */
	/* ردیاب‌های روزانه و فیلدهای TTC (اعتبارسنجی بازه‌ای سمت سرور)           */
	/* --------------------------------------------------------------------- */

	public static function mucus_options(): array {
		return array(
			'none'     => 'ثبت نشده',
			'dry'      => 'خشک',
			'sticky'   => 'چسبنده',
			'creamy'   => 'کرمی',
			'eggwhite' => 'شبیه سفیدهٔ تخم‌مرغ',
			'watery'   => 'آبکی',
		);
	}

	public static function sex_options(): array {
		return array(
			'none'        => 'ثبت نشده',
			'protected'   => 'با پیشگیری',
			'unprotected' => 'بدون پیشگیری',
		);
	}

	/**
	 * عدد اعشاری در بازهٔ مجاز، یا null.
	 * خارج از بازه => null (هیچ‌وقت clamp نمی‌کنیم تا دادهٔ ساختگی تولید نشود).
	 */
	private static function num_in_range( $value, float $min, float $max, int $decimals = 1 ) {
		if ( null === $value || '' === $value ) {
			return null;
		}
		$value = MB_Jalali::en_num( (string) $value );
		if ( ! is_numeric( $value ) ) {
			return null;
		}
		$num = round( (float) $value, $decimals );
		if ( $num < $min || $num > $max ) {
			return null;
		}
		return $num;
	}

	private static function int_in_range( $value, int $min, int $max ) {
		if ( null === $value || '' === $value ) {
			return null;
		}
		$value = MB_Jalali::en_num( (string) $value );
		if ( ! is_numeric( $value ) ) {
			return null;
		}
		$num = (int) $value;
		return ( $num < $min || $num > $max ) ? null : $num;
	}

	/** آرایهٔ ستون‌های آمادهٔ درج برای فیلدهای جدید لاگ. */
	public static function sanitize_tracker_fields( array $data ): array {
		$mucus = (string) ( $data['mucus'] ?? 'none' );
		$sex   = (string) ( $data['sex'] ?? 'none' );

		$meds = array();
		foreach ( (array) ( $data['meds'] ?? array() ) as $item ) {
			if ( is_array( $item ) ) {
				$name = sanitize_text_field( (string) ( $item['name'] ?? '' ) );
				$done = ! empty( $item['taken'] ) ? 1 : 0;
			} else {
				$name = sanitize_text_field( (string) $item );
				$done = 1;
			}
			$name = mb_substr( $name, 0, 60 );
			if ( '' !== $name ) {
				$meds[] = array( 'name' => $name, 'taken' => $done );
			}
			if ( count( $meds ) >= 12 ) {
				break;
			}
		}

		return array(
			'bbt'          => self::num_in_range( $data['bbt'] ?? null, 34.0, 42.0, 1 ),
			'mucus'        => array_key_exists( $mucus, self::mucus_options() ) ? $mucus : 'none',
			'sex'          => array_key_exists( $sex, self::sex_options() ) ? $sex : 'none',
			'weight_kg'    => self::num_in_range( $data['weight_kg'] ?? null, 25.0, 250.0, 2 ),
			'sleep_h'      => self::num_in_range( $data['sleep_h'] ?? null, 0.0, 24.0, 1 ),
			'water_cups'   => self::int_in_range( $data['water_cups'] ?? null, 0, 30 ),
			'exercise_min' => self::int_in_range( $data['exercise_min'] ?? null, 0, 600 ),
			'meds'         => wp_json_encode( $meds ),
		);
	}

	/** به‌روزرسانی فلگ تأیید تخمک‌گذاری برای یک روز (فقط توسط MB_TTC). */
	public static function set_ovulation_confirmed( int $user_id, string $date, bool $flag ): void {
		global $wpdb;
		$wpdb->update(
			self::t( 'logs' ),
			array( 'ovulation_confirmed' => $flag ? 1 : 0 ),
			array( 'user_id' => $user_id, 'log_date' => MB_Jalali::sanitize_date( $date ) ),
			array( '%d' ),
			array( '%d', '%s' )
		);
	}

	/** آخرین N روز لاگ برای تحلیل BBT / ردیاب‌ها. */
	public static function recent_logs( int $user_id, int $limit = 30 ): array {
		$to   = MB_Jalali::today();
		$from = MB_Jalali::add_days( $to, -1 * max( 1, min( 400, $limit ) ) );
		return self::get_logs_range( $user_id, $from, $to );
	}

	/* --------------------------------------------------------------------- */
	/* بارداری (M1)                                                          */
	/* --------------------------------------------------------------------- */

	public static function get_active_pregnancy( int $user_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::t( 'pregnancy' ) . ' WHERE user_id = %d AND active = 1 ORDER BY id DESC LIMIT 1', $user_id ),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	public static function start_pregnancy( int $user_id, string $lmp, string $due ): int {
		global $wpdb;
		self::end_pregnancy( $user_id );
		$wpdb->insert(
			self::t( 'pregnancy' ),
			array(
				'user_id'         => $user_id,
				'lmp_date'        => MB_Jalali::sanitize_date( $lmp ),
				'due_date'        => MB_Jalali::sanitize_date( $due ),
				'active'          => 1,
				'share_pregnancy' => 0,
				'created_at'      => current_time( 'mysql' ),
			)
		);
		delete_transient( 'mb_stats_' . $user_id );
		return (int) $wpdb->insert_id;
	}

	public static function end_pregnancy( int $user_id ): void {
		global $wpdb;
		$wpdb->update(
			self::t( 'pregnancy' ),
			array( 'active' => 0, 'ended_at' => current_time( 'mysql' ) ),
			array( 'user_id' => $user_id, 'active' => 1 ),
			array( '%d', '%s' ),
			array( '%d', '%d' )
		);
		delete_transient( 'mb_stats_' . $user_id );
	}

	public static function set_pregnancy_share( int $user_id, bool $on ): void {
		global $wpdb;
		$wpdb->update(
			self::t( 'pregnancy' ),
			array( 'share_pregnancy' => $on ? 1 : 0 ),
			array( 'user_id' => $user_id, 'active' => 1 ),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}

	/* --------------------------------------------------------------------- */
	/* انجمن ناشناس (M7)                                                     */
	/* --------------------------------------------------------------------- */

	/**
	 * ثبت رکورد انجمن. توجه: هیچ user_id ذخیره نمی‌شود؛ تنها question_id برای
	 * موردسازی در ادمین نگه داشته می‌شود و هرگز در خروجی عمومی نمی‌آید.
	 */
	public static function add_community( int $question_id, string $question, string $cat ): int {
		global $wpdb;
		$wpdb->insert(
			self::t( 'community' ),
			array(
				'question_id' => $question_id,
				'question'    => sanitize_textarea_field( $question ),
				'answer'      => null,
				'cat'         => sanitize_key( $cat ),
				'status'      => 'pending',
				'anon_label'  => self::anon_label(),
				'created_at'  => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	/** برچسب ناشناس تصادفی و بی‌ربط به هویت کاربر. */
	public static function anon_label(): string {
		$words = array( 'بانوی ماه', 'همسفر ماه', 'مهتاب', 'ستاره', 'شب‌تاب', 'نیلوفر', 'سپیده', 'پروانه' );
		return $words[ random_int( 0, count( $words ) - 1 ) ] . ' ' . MB_Jalali::fa_num( random_int( 10, 99 ) );
	}

	/** خروجی عمومی: فقط approved، فقط ستون‌های بی‌هویت. */
	public static function get_community_public( string $cat = '', int $limit = 30, int $offset = 0 ): array {
		global $wpdb;
		$limit  = max( 1, min( 50, $limit ) );
		$offset = max( 0, $offset );
		$table  = self::t( 'community' );
		if ( '' !== $cat ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT id,question,answer,cat,anon_label,created_at FROM {$table} WHERE status = 'approved' AND cat = %s ORDER BY id DESC LIMIT %d OFFSET %d", sanitize_key( $cat ), $limit, $offset ),
				ARRAY_A
			);
		} else {
			$rows = $wpdb->get_results(
				$wpdb->prepare( "SELECT id,question,answer,cat,anon_label,created_at FROM {$table} WHERE status = 'approved' ORDER BY id DESC LIMIT %d OFFSET %d", $limit, $offset ),
				ARRAY_A
			);
		}
		return (array) $rows;
	}

	public static function get_community_admin( string $status = '', int $limit = 50 ): array {
		global $wpdb;
		$table = self::t( 'community' );
		$limit = max( 1, min( 200, $limit ) );
		if ( '' !== $status ) {
			return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE status = %s ORDER BY id DESC LIMIT %d", $status, $limit ), ARRAY_A );
		}
		return (array) $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} ORDER BY id DESC LIMIT %d", $limit ), ARRAY_A );
	}

	public static function set_community_status( int $id, string $status ): void {
		global $wpdb;
		if ( ! in_array( $status, array( 'pending', 'approved', 'rejected' ), true ) ) {
			return;
		}
		$wpdb->update( self::t( 'community' ), array( 'status' => $status ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
	}

	public static function set_community_answer( int $id, string $answer ): void {
		global $wpdb;
		$wpdb->update( self::t( 'community' ), array( 'answer' => sanitize_textarea_field( $answer ) ), array( 'id' => $id ), array( '%s' ), array( '%d' ) );
	}

	public static function delete_community( int $id ): void {
		global $wpdb;
		$wpdb->delete( self::t( 'community' ), array( 'id' => $id ), array( '%d' ) );
	}

	public static function count_community( string $status = 'pending' ): int {
		global $wpdb;
		$table = self::t( 'community' );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) );
	}

	/** انتشار ناشناس پاسخ یک پرسش متخصص، در صورت opt-in کاربر. */
	public static function publish_question_anonymously( int $question_id, string $answer ): void {
		global $wpdb;
		$table = self::t( 'community' );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table} WHERE question_id = %d LIMIT 1", $question_id ), ARRAY_A );
		if ( ! $row ) {
			return;
		}
		$wpdb->update( $table, array( 'answer' => sanitize_textarea_field( $answer ) ), array( 'id' => (int) $row['id'] ), array( '%s' ), array( '%d' ) );
	}

	/* --------------------------------------------------------------------- */
	/* کوییزها (M8)                                                          */
	/* --------------------------------------------------------------------- */

	public static function get_quizzes( bool $only_active = true ): array {
		global $wpdb;
		$table = self::t( 'quizzes' );
		$sql   = $only_active ? "SELECT * FROM {$table} WHERE active = 1 ORDER BY sort_order ASC, id ASC" : "SELECT * FROM {$table} ORDER BY sort_order ASC, id ASC";
		$rows  = (array) $wpdb->get_results( $sql, ARRAY_A );
		foreach ( $rows as &$row ) {
			$row['questions'] = self::json_array( $row['questions'] ?? '' );
			$row['bands']     = self::json_array( $row['bands'] ?? '' );
		}
		return $rows;
	}

	public static function get_quiz( string $slug ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'quizzes' ) . ' WHERE slug = %s AND active = 1', sanitize_key( $slug ) ), ARRAY_A );
		if ( ! $row ) {
			return null;
		}
		$row['questions'] = self::json_array( $row['questions'] ?? '' );
		$row['bands']     = self::json_array( $row['bands'] ?? '' );
		return $row;
	}

	private static function json_array( $raw ): array {
		$decoded = json_decode( (string) $raw, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	public static function save_quiz_result( int $user_id, int $quiz_id, int $score, int $max, string $band, array $answers ): int {
		global $wpdb;
		$wpdb->insert(
			self::t( 'quiz_results' ),
			array(
				'user_id'    => $user_id,
				'quiz_id'    => $quiz_id,
				'score'      => max( 0, $score ),
				'max_score'  => max( 0, $max ),
				'band'       => sanitize_text_field( $band ),
				'answers'    => wp_json_encode( array_map( 'intval', $answers ) ),
				'created_at' => current_time( 'mysql' ),
			)
		);
		return (int) $wpdb->insert_id;
	}

	public static function last_quiz_result( int $user_id, int $quiz_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare( 'SELECT * FROM ' . self::t( 'quiz_results' ) . ' WHERE user_id = %d AND quiz_id = %d ORDER BY id DESC LIMIT 1', $user_id, $quiz_id ),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/* --------------------------------------------------------------------- */
	/* معرف (M11)                                                            */
	/* --------------------------------------------------------------------- */

	public static function get_referral_by_referee( int $referee ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::t( 'referrals' ) . ' WHERE referee = %d LIMIT 1', $referee ), ARRAY_A );
		return $row ? $row : null;
	}

	public static function add_referral( int $referrer, int $referee ): bool {
		global $wpdb;
		if ( $referrer <= 0 || $referee <= 0 || $referrer === $referee ) {
			return false;
		}
		if ( self::get_referral_by_referee( $referee ) ) {
			return false;
		}
		return (bool) $wpdb->insert(
			self::t( 'referrals' ),
			array(
				'referrer'   => $referrer,
				'referee'    => $referee,
				'created_at' => current_time( 'mysql' ),
			)
		);
	}

	public static function mark_referral_rewarded( int $id, int $sub_id, string $coupon ): void {
		global $wpdb;
		$wpdb->update(
			self::t( 'referrals' ),
			array( 'rewarded' => 1, 'sub_id' => $sub_id, 'coupon' => $coupon, 'rewarded_at' => current_time( 'mysql' ) ),
			array( 'id' => $id ),
			array( '%d', '%d', '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function referrals_of( int $referrer, int $limit = 50 ): array {
		global $wpdb;
		return (array) $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . self::t( 'referrals' ) . ' WHERE referrer = %d ORDER BY id DESC LIMIT %d', $referrer, max( 1, min( 100, $limit ) ) ),
			ARRAY_A
		);
	}

}
