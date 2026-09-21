<?php
/**
 * موتور تقویم جلالی (شمسی) بر پایه روز جولیَن (JDN) با مبدأ 1948320.
 * هیچ وابستگی بیرونی ندارد.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Jalali {

	const JDN_EPOCH = 1948320; // JDN متناظر با روز صفرم تقویم جلالی (۱/۱/۱ = 1948321).
	const OFFSET    = 1721060; // ثابت کالیبراسیون میان شمارش روز جلالی و JDN گریگوری.

	/** نام ماه‌های شمسی. */
	public static function months(): array {
		return array( 1 => 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
	}

	/** نام روزهای هفته، شنبه = ۰. */
	public static function week_days(): array {
		return array( 'شنبه', 'یک‌شنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنج‌شنبه', 'جمعه' );
	}

	public static function week_days_short(): array {
		return array( 'ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج' );
	}

	/* --------------------------------------------------------------------- */
	/* تبدیل‌های پایه                                                        */
	/* --------------------------------------------------------------------- */

	/** JDN تاریخ گریگوری. */
	public static function g2jdn( int $gy, int $gm, int $gd ): int {
		$a = intdiv( 14 - $gm, 12 );
		$y = $gy + 4800 - $a;
		$m = $gm + 12 * $a - 3;
		return $gd + intdiv( 153 * $m + 2, 5 ) + 365 * $y + intdiv( $y, 4 ) - intdiv( $y, 100 ) + intdiv( $y, 400 ) - 32045;
	}

	/** تاریخ گریگوری از JDN. */
	public static function jdn2g( int $jdn ): array {
		$a  = $jdn + 32044;
		$b  = intdiv( 4 * $a + 3, 146097 );
		$c  = $a - intdiv( 146097 * $b, 4 );
		$d  = intdiv( 4 * $c + 3, 1461 );
		$e  = $c - intdiv( 1461 * $d, 4 );
		$m  = intdiv( 5 * $e + 2, 153 );
		$gd = $e - intdiv( 153 * $m + 2, 5 ) + 1;
		$gm = $m + 3 - 12 * intdiv( $m, 10 );
		$gy = 100 * $b + $d - 4800 + intdiv( $m, 10 );
		return array( (int) $gy, (int) $gm, (int) $gd );
	}

	/** شمارش روز داخلی تقویم جلالی (بدون افست). */
	private static function jalali_days( int $jy, int $jm, int $jd ): int {
		$y     = $jy + 1595;
		$month = ( $jm < 7 ) ? ( $jm - 1 ) * 31 : ( ( $jm - 7 ) * 30 + 186 );
		return -355668 + 365 * $y + intdiv( $y, 33 ) * 8 + intdiv( ( $y % 33 ) + 3, 4 ) + $jd + $month;
	}

	/** JDN تاریخ شمسی. */
	public static function j2jdn( int $jy, int $jm, int $jd ): int {
		return self::jalali_days( $jy, $jm, $jd ) + self::OFFSET;
	}

	/** تاریخ شمسی از JDN. */
	public static function jdn2j( int $jdn ): array {
		$days = $jdn - self::OFFSET;
		$jy   = intdiv( $days + 355669, 365 ) - 1595;
		while ( self::jalali_days( $jy, 1, 1 ) > $days ) {
			--$jy;
		}
		while ( self::jalali_days( $jy + 1, 1, 1 ) <= $days ) {
			++$jy;
		}
		$rem = $days - self::jalali_days( $jy, 1, 1 );
		if ( $rem < 186 ) {
			$jm = intdiv( $rem, 31 ) + 1;
			$jd = ( $rem % 31 ) + 1;
		} else {
			$r  = $rem - 186;
			$jm = 7 + intdiv( $r, 30 );
			$jd = ( $r % 30 ) + 1;
		}
		return array( (int) $jy, (int) $jm, (int) $jd );
	}

	/* --------------------------------------------------------------------- */
	/* رابط‌های راحت (رشته Y-m-d گریگوری ↔ آرایه شمسی)                       */
	/* --------------------------------------------------------------------- */

	/** '2026-09-18' → array(1405,6,27) */
	public static function g2j( string $gdate ): array {
		list( $gy, $gm, $gd ) = self::split( $gdate );
		return self::jdn2j( self::g2jdn( $gy, $gm, $gd ) );
	}

	/** (1405,6,27) → '2026-09-18' */
	public static function j2g( int $jy, int $jm, int $jd ): string {
		list( $gy, $gm, $gd ) = self::jdn2g( self::j2jdn( $jy, $jm, $jd ) );
		return sprintf( '%04d-%02d-%02d', $gy, $gm, $gd );
	}

	/** روز هفته: شنبه = ۰ … جمعه = ۶. */
	public static function day_of_week( string $gdate ): int {
		list( $gy, $gm, $gd ) = self::split( $gdate );
		return (int) ( ( self::g2jdn( $gy, $gm, $gd ) + 2 ) % 7 );
	}

	/** افزودن (یا کاستن) روز به تاریخ گریگوری. */
	public static function add_days( string $gdate, int $days ): string {
		list( $gy, $gm, $gd ) = self::split( $gdate );
		list( $y, $m, $d )    = self::jdn2g( self::g2jdn( $gy, $gm, $gd ) + $days );
		return sprintf( '%04d-%02d-%02d', $y, $m, $d );
	}

	/** فاصله روزها: b − a. */
	public static function diff_days( string $a, string $b ): int {
		list( $ay, $am, $ad ) = self::split( $a );
		list( $by, $bm, $bd ) = self::split( $b );
		return self::g2jdn( $by, $bm, $bd ) - self::g2jdn( $ay, $am, $ad );
	}

	/** تعداد روزهای ماه شمسی. */
	public static function month_len( int $jy, int $jm ): int {
		if ( $jm <= 6 ) {
			return 31;
		}
		if ( $jm <= 11 ) {
			return 30;
		}
		return self::is_leap( $jy ) ? 30 : 29;
	}

	public static function is_leap( int $jy ): bool {
		return self::jalali_days( $jy + 1, 1, 1 ) - self::jalali_days( $jy, 1, 1 ) === 366;
	}

	/* --------------------------------------------------------------------- */
	/* قالب‌بندی                                                             */
	/* --------------------------------------------------------------------- */

	/**
	 * قالب‌بندی فارسی.
	 * الگوها: 'full' = شنبه ۲۷ شهریور ۱۴۰۵ | 'long' = ۲۷ شهریور ۱۴۰۵ |
	 * 'short' = ۲۷ شهریور | 'num' = ۱۴۰۵/۰۶/۲۷ | 'md' = ۲۷/۰۶ | 'ym' = شهریور ۱۴۰۵ | 'd' = ۲۷
	 */
	public static function format_fa( string $gdate, string $pattern = 'long' ): string {
		list( $jy, $jm, $jd ) = self::g2j( $gdate );
		$months = self::months();
		$mname  = $months[ $jm ];
		switch ( $pattern ) {
			case 'full':
				$w = self::week_days()[ self::day_of_week( $gdate ) ];
				return $w . ' ' . self::fa_num( $jd ) . ' ' . $mname . ' ' . self::fa_num( $jy );
			case 'short':
				return self::fa_num( $jd ) . ' ' . $mname;
			case 'num':
				return self::fa_num( sprintf( '%04d/%02d/%02d', $jy, $jm, $jd ) );
			case 'md':
				return self::fa_num( sprintf( '%02d/%02d', $jd, $jm ) );
			case 'ym':
				return $mname . ' ' . self::fa_num( $jy );
			case 'd':
				return self::fa_num( $jd );
			case 'long':
			default:
				return self::fa_num( $jd ) . ' ' . $mname . ' ' . self::fa_num( $jy );
		}
	}

	/** ارقام فارسی. */
	public static function fa_num( $value ): string {
		$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
		return str_replace( $en, $fa, (string) $value );
	}

	/** ارقام لاتین از ارقام فارسی/عربی (برای ورودی کاربر). */
	public static function en_num( $value ): string {
		$fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹', '٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩' );
		$en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
		return str_replace( $fa, $en, (string) $value );
	}

	/* --------------------------------------------------------------------- */
	/* کمکی‌ها                                                               */
	/* --------------------------------------------------------------------- */

	/** اعتبارسنجی و شکستن Y-m-d. */
	public static function split( string $gdate ): array {
		$gdate = trim( self::en_num( $gdate ) );
		if ( ! preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $gdate, $m ) ) {
			$gdate = self::today();
			preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $gdate, $m );
		}
		return array( (int) $m[1], (int) $m[2], (int) $m[3] );
	}

	/** تاریخ امروز بر اساس منطقه زمانی وردپرس. */
	public static function today(): string {
		return current_time( 'Y-m-d' );
	}

	/** اعتبارسنجی تاریخ گریگوری. */
	public static function is_valid( string $gdate ): bool {
		$gdate = trim( self::en_num( $gdate ) );
		if ( ! preg_match( '/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $gdate, $m ) ) {
			return false;
		}
		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
	}

	/** پاک‌سازی ورودی تاریخ؛ در صورت نامعتباری امروز. */
	public static function sanitize_date( $value ): string {
		$value = is_string( $value ) ? $value : '';
		if ( ! self::is_valid( $value ) ) {
			return self::today();
		}
		// بدون strtotime تا منطقه زمانی سرور تاریخ را یک روز جابه‌جا نکند.
		list( $gy, $gm, $gd ) = self::split( $value );
		return sprintf( '%04d-%02d-%02d', $gy, $gm, $gd );
	}

	/** تبدیل تاریخ شمسی ارسالی کاربر (1405/06/27 یا 1405-6-27). */
	/** yyyy/mm/dd شمسی از یک تاریخ میلادی (ارقام لاتین، برای مقدار input). */
	public static function to_jalali_string( string $gdate ): string {
		$gdate = self::sanitize_date( $gdate );
		if ( '' === $gdate ) {
			return '';
		}
		list( $jy, $jm, $jd ) = self::g2j( $gdate );
		return sprintf( '%04d/%02d/%02d', (int) $jy, (int) $jm, (int) $jd );
	}

	public static function parse_jalali( $value ): ?string {
		$value = self::en_num( (string) $value );
		if ( ! preg_match( '/^(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})$/', trim( $value ), $m ) ) {
			return null;
		}
		$jy = (int) $m[1];
		$jm = (int) $m[2];
		$jd = (int) $m[3];
		if ( $jy < 1300 || $jy > 1500 || $jm < 1 || $jm > 12 || $jd < 1 || $jd > self::month_len( $jy, $jm ) ) {
			return null;
		}
		return self::j2g( $jy, $jm, $jd );
	}

	/** بازه شمسی خوانا: «۱۵ تا ۲۱ شهریور». */
	public static function range_fa( string $from, string $to ): string {
		list( , $fm, $fd ) = self::g2j( $from );
		list( , $tm, $td ) = self::g2j( $to );
		$months = self::months();
		if ( $fm === $tm ) {
			return self::fa_num( $fd ) . ' تا ' . self::fa_num( $td ) . ' ' . $months[ $tm ];
		}
		return self::fa_num( $fd ) . ' ' . $months[ $fm ] . ' تا ' . self::fa_num( $td ) . ' ' . $months[ $tm ];
	}

	/** خودآزمون: ۵۰۰ تاریخ نمونه رفت‌وبرگشت. برای صفحه «وضعیت» ادمین. */
	public static function self_test(): array {
		$fail = 0;
		$jdn0 = self::j2jdn( 1400, 1, 1 );
		for ( $i = 0; $i < 500; $i++ ) {
			$jdn = $jdn0 + $i * 7;
			list( $jy, $jm, $jd ) = self::jdn2j( $jdn );
			if ( self::j2jdn( $jy, $jm, $jd ) !== $jdn ) {
				++$fail;
			}
		}
		$vector = ( '2026-09-18' === self::j2g( 1405, 6, 27 ) ) && ( array( 1405, 6, 27 ) === self::g2j( '2026-09-18' ) );
		return array(
			'roundtrip_failures' => $fail,
			'epoch_ok'           => self::j2jdn( 1, 1, 1 ) === self::JDN_EPOCH,
			'vector_ok'          => $vector,
			'samples'            => 500,
		);
	}
}
