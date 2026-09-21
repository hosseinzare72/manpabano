<?php
/**
 * N3 — پروفایل سلامت و اطلاعات اضطراری.
 *
 * جدول mb_health_profile. دو فیلد حساس (کد ملی و یادداشت پزشکی) با MB_Crypto
 * رمزنگاری می‌شوند و هرگز در هیچ خروجی همسر یا API عمومی نمی‌آیند.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Health_Profile {

	/** گروه‌های خونی مجاز (ENUM جدول). */
	public static function blood_types(): array {
		return array(
			''     => 'نامشخص',
			'A+'   => 'A+',
			'A-'   => 'A−',
			'B+'   => 'B+',
			'B-'   => 'B−',
			'AB+'  => 'AB+',
			'AB-'  => 'AB−',
			'O+'   => 'O+',
			'O-'   => 'O−',
		);
	}

	/* --------------------------------------------------------------------- */
	/* خواندن و نوشتن                                                        */
	/* --------------------------------------------------------------------- */

	/** ردیف خالی پیش‌فرض. */
	private static function blank( int $user_id ): array {
		return array(
			'user_id'         => $user_id,
			'height_cm'       => null,
			'weight_kg'       => null,
			'bmi'             => null,
			'waist_cm'        => null,
			'blood_type'      => '',
			'national_id'     => '',
			'emergency_name'  => '',
			'emergency_phone' => '',
			'medical_note'    => '',
			'emergency_alert' => 0,
			'updated_at'      => '',
		);
	}

	/**
	 * پروفایل سلامت مالک.
	 *
	 * @param bool $decrypt اگر false، فیلدهای حساس خالی برمی‌گردند (برای گزارش و لاگ).
	 */
	public static function get( int $user_id, bool $decrypt = true ): array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . MB_DB::t( 'health_profile' ) . ' WHERE user_id = %d', $user_id ), ARRAY_A );
		if ( ! $row ) {
			return self::blank( $user_id );
		}

		$out = array(
			'user_id'         => (int) $row['user_id'],
			'height_cm'       => null === $row['height_cm'] ? null : (int) $row['height_cm'],
			'weight_kg'       => null === $row['weight_kg'] ? null : (float) $row['weight_kg'],
			'bmi'             => null === $row['bmi'] ? null : (float) $row['bmi'],
			'waist_cm'        => null === $row['waist_cm'] ? null : (int) $row['waist_cm'],
			'blood_type'      => (string) $row['blood_type'],
			'national_id'     => $decrypt ? MB_Crypto::decrypt( $row['national_id'] ) : '',
			'emergency_name'  => (string) $row['emergency_name'],
			'emergency_phone' => (string) $row['emergency_phone'],
			'medical_note'    => $decrypt ? MB_Crypto::decrypt( $row['medical_note'] ) : '',
			'emergency_alert' => (int) $row['emergency_alert'],
			'updated_at'      => (string) $row['updated_at'],
		);
		return $out;
	}

	/**
	 * ذخیرهٔ پروفایل. فقط کلیدهای فرستاده‌شده نوشته می‌شوند.
	 *
	 * @return array{ok:bool,error:string,bmi:?float,bmi_label:string}
	 */
	public static function save( int $user_id, array $data ): array {
		global $wpdb;
		$current = self::get( $user_id );

		$height = array_key_exists( 'height_cm', $data ) ? self::int_or_null( $data['height_cm'], 100, 230 ) : $current['height_cm'];
		$weight = array_key_exists( 'weight_kg', $data ) ? self::float_or_null( $data['weight_kg'], 25, 250 ) : $current['weight_kg'];
		$waist  = array_key_exists( 'waist_cm', $data ) ? self::int_or_null( $data['waist_cm'], 40, 200 ) : $current['waist_cm'];

		$blood = array_key_exists( 'blood_type', $data ) ? (string) $data['blood_type'] : $current['blood_type'];
		$blood = array_key_exists( $blood, self::blood_types() ) ? $blood : '';

		$nid = array_key_exists( 'national_id', $data )
			? preg_replace( '/[^0-9]/', '', MB_Jalali::en_num( (string) $data['national_id'] ) )
			: $current['national_id'];
		if ( '' !== (string) $nid && 10 !== strlen( (string) $nid ) ) {
			return array( 'ok' => false, 'error' => 'کد ملی باید ده رقم باشد.', 'bmi' => $current['bmi'], 'bmi_label' => '' );
		}

		$ename = array_key_exists( 'emergency_name', $data ) ? mb_substr( sanitize_text_field( (string) $data['emergency_name'] ), 0, 60 ) : $current['emergency_name'];
		$ephone = array_key_exists( 'emergency_phone', $data )
			? MB_Auth::normalize_mobile( (string) $data['emergency_phone'] )
			: $current['emergency_phone'];
		if ( array_key_exists( 'emergency_phone', $data ) && '' !== trim( (string) $data['emergency_phone'] ) && '' === $ephone ) {
			return array( 'ok' => false, 'error' => 'شمارهٔ تماس اضطراری معتبر نیست.', 'bmi' => $current['bmi'], 'bmi_label' => '' );
		}

		$note  = array_key_exists( 'medical_note', $data ) ? mb_substr( sanitize_textarea_field( (string) $data['medical_note'] ), 0, 1000 ) : $current['medical_note'];
		$alert = array_key_exists( 'emergency_alert', $data ) ? (int) (bool) $data['emergency_alert'] : (int) $current['emergency_alert'];

		// هشدار اضطراری بدون شمارهٔ مقصد بی‌معنی است.
		if ( 1 === $alert && '' === $ephone ) {
			return array( 'ok' => false, 'error' => 'برای روشن‌کردن هشدار اضطراری، شمارهٔ تماس اضطراری لازم است.', 'bmi' => $current['bmi'], 'bmi_label' => '' );
		}

		$bmi = self::bmi( $height, $weight );

		$row = array(
			'user_id'         => $user_id,
			'height_cm'       => $height,
			'weight_kg'       => $weight,
			'bmi'             => $bmi,
			'waist_cm'        => $waist,
			'blood_type'      => $blood,
			'national_id'     => '' === (string) $nid ? '' : MB_Crypto::encrypt( (string) $nid ),
			'emergency_name'  => $ename,
			'emergency_phone' => $ephone,
			'medical_note'    => '' === $note ? '' : MB_Crypto::encrypt( $note ),
			'emergency_alert' => $alert,
			'updated_at'      => current_time( 'mysql' ),
		);

		$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM ' . MB_DB::t( 'health_profile' ) . ' WHERE user_id = %d', $user_id ) );
		if ( $exists ) {
			$id = $row['user_id'];
			unset( $row['user_id'] );
			$wpdb->update( MB_DB::t( 'health_profile' ), $row, array( 'user_id' => $user_id ) );
		} else {
			$wpdb->insert( MB_DB::t( 'health_profile' ), $row );
		}

		// هر تغییر توگل اضطراری لاگ می‌شود (بدون هیچ دادهٔ پزشکی).
		if ( (int) $current['emergency_alert'] !== $alert ) {
			MB_Plugin::log_security( 'emergency_toggle', array( 'user' => $user_id, 'on' => $alert ) );
		}

		return array( 'ok' => true, 'error' => '', 'bmi' => $bmi, 'bmi_label' => null === $bmi ? '' : self::bmi_category( $bmi )['label'] );
	}

	/* --------------------------------------------------------------------- */
	/* BMI طبق WHO                                                           */
	/* --------------------------------------------------------------------- */

	/** BMI = وزن (kg) / قد (m)^2؛ یک رقم اعشار. */
	public static function bmi( $height_cm, $weight_kg ): ?float {
		if ( null === $height_cm || null === $weight_kg ) {
			return null;
		}
		$h = (float) $height_cm / 100;
		if ( $h <= 0 ) {
			return null;
		}
		return round( (float) $weight_kg / ( $h * $h ), 1 );
	}

	/**
	 * دستهٔ BMI طبق سازمان جهانی بهداشت (WHO).
	 * <18.5 کم‌وزن · 18.5–24.9 طبیعی · 25–29.9 اضافه‌وزن · ≥30 چاقی (۳ درجه).
	 */
	public static function bmi_category( float $bmi ): array {
		if ( $bmi < 18.5 ) {
			return array( 'key' => 'under', 'label' => 'کم‌وزن', 'note' => 'وزنت کمتر از محدودهٔ طبیعی است؛ تغذیهٔ کافی روی نظم چرخه اثر مستقیم دارد.', 'color' => 'norm' );
		}
		if ( $bmi < 25 ) {
			return array( 'key' => 'normal', 'label' => 'طبیعی', 'note' => 'در محدودهٔ طبیعی هستی. همین روند را نگه دار.', 'color' => 'fertile' );
		}
		if ( $bmi < 30 ) {
			return array( 'key' => 'over', 'label' => 'اضافه‌وزن', 'note' => 'کمی بالاتر از محدودهٔ طبیعی. حرکت روزانهٔ سبک و منظم بیشترین اثر را دارد.', 'color' => 'pms' );
		}
		if ( $bmi < 35 ) {
			return array( 'key' => 'obese1', 'label' => 'چاقی درجهٔ ۱', 'note' => 'گفت‌وگو با متخصص تغذیه کمک می‌کند؛ وزن روی هورمون‌ها و نظم چرخه اثر دارد.', 'color' => 'period' );
		}
		if ( $bmi < 40 ) {
			return array( 'key' => 'obese2', 'label' => 'چاقی درجهٔ ۲', 'note' => 'بررسی تخصصی توصیه می‌شود؛ این عدد یک نشانه است، نه تشخیص.', 'color' => 'period' );
		}
		return array( 'key' => 'obese3', 'label' => 'چاقی درجهٔ ۳', 'note' => 'بررسی تخصصی توصیه می‌شود؛ این عدد یک نشانه است، نه تشخیص.', 'color' => 'period' );
	}

	/** جدول مرجع WHO برای نمایش در صفحه. */
	public static function bmi_table(): array {
		return array(
			array( 'range' => 'کمتر از ۱۸٫۵', 'label' => 'کم‌وزن' ),
			array( 'range' => '۱۸٫۵ تا ۲۴٫۹', 'label' => 'طبیعی' ),
			array( 'range' => '۲۵ تا ۲۹٫۹', 'label' => 'اضافه‌وزن' ),
			array( 'range' => '۳۰ تا ۳۴٫۹', 'label' => 'چاقی درجهٔ ۱' ),
			array( 'range' => '۳۵ تا ۳۹٫۹', 'label' => 'چاقی درجهٔ ۲' ),
			array( 'range' => '۴۰ و بالاتر', 'label' => 'چاقی درجهٔ ۳' ),
		);
	}

	/* --------------------------------------------------------------------- */
	/* کمکی                                                                  */
	/* --------------------------------------------------------------------- */

	/** آیا هشدار اضطراری این کاربر روشن است؟ (تنها مرجع تصمیم N4) */
	public static function alert_enabled( int $user_id ): bool {
		global $wpdb;
		return 1 === (int) $wpdb->get_var( $wpdb->prepare( 'SELECT emergency_alert FROM ' . MB_DB::t( 'health_profile' ) . ' WHERE user_id = %d', $user_id ) );
	}

	/** شمارهٔ تماس اضطراری (بدون هیچ فیلد دیگری). */
	public static function emergency_contact( int $user_id ): array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT emergency_name,emergency_phone FROM ' . MB_DB::t( 'health_profile' ) . ' WHERE user_id = %d', $user_id ), ARRAY_A );
		return array(
			'name'  => $row ? (string) $row['emergency_name'] : '',
			'phone' => $row ? (string) $row['emergency_phone'] : '',
		);
	}

	private static function int_or_null( $value, int $min, int $max ) {
		if ( null === $value || '' === $value ) {
			return null;
		}
		$value = MB_Jalali::en_num( (string) $value );
		if ( ! is_numeric( $value ) ) {
			return null;
		}
		$num = (int) round( (float) $value );
		return ( $num < $min || $num > $max ) ? null : $num;
	}

	private static function float_or_null( $value, float $min, float $max ) {
		if ( null === $value || '' === $value ) {
			return null;
		}
		$value = str_replace( array( '٫', '،' ), '.', MB_Jalali::en_num( (string) $value ) );
		if ( ! is_numeric( $value ) ) {
			return null;
		}
		$num = round( (float) $value, 2 );
		return ( $num < $min || $num > $max ) ? null : $num;
	}

	/** حذف کامل پروفایل سلامت یک کاربر. */
	public static function delete( int $user_id ): void {
		global $wpdb;
		$wpdb->delete( MB_DB::t( 'health_profile' ), array( 'user_id' => $user_id ), array( '%d' ) );
	}
}
