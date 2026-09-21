<?php
/**
 * M10 — تشخیص چرخهٔ نامنظم / نشانه‌های احتمالی PCOS.
 *
 * فلگ نامنظم وقتی روشن می‌شود که انحراف معیار طول چرخه‌ها ≥ ۴ روز باشد، یا
 * دست‌کم دو چرخه خارج از بازهٔ ۲۱ تا ۳۵ روز ثبت شده باشد.
 *
 * وقتی فلگ روشن است، هیچ تاریخ تک‌نقطه‌ای به کاربر نشان داده نمی‌شود؛ همهٔ
 * پیش‌بینی‌ها به بازه (± انحراف) تبدیل می‌شوند. این یک انتخاب طراحی صادقانه
 * است: با دادهٔ پرنوسان، تاریخ دقیق دادن گمراه‌کننده است.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Irregular {

	/** حد انحراف معیار طول چرخه (روز). */
	const STDEV_LIMIT = 4.0;

	/** بازهٔ طبیعی طول چرخه. */
	const NORMAL_MIN = 21;
	const NORMAL_MAX = 35;

	/** حداقل تعداد چرخه برای قضاوت. */
	const MIN_CYCLES = 3;

	/**
	 * وضعیت نظم چرخه.
	 *
	 * @return array{irregular:bool,stdev:float,outliers:int,cycles:int,reason:string,spread:int,manual?:bool}
	 */
	public static function status( int $user_id ): array {
		$lengths = (array) MB_Cycle_Engine::recent_lengths( $user_id, 12 );
		return self::evaluate( $lengths, $user_id );
	}

	/**
	 * ارزیابی خالص از فهرست طول چرخه‌ها (بدون دیتابیس؛ مرجع تست پذیرش ۸٫۷).
	 *
	 * @param array<int,int> $lengths طول چرخه‌ها.
	 * @param int            $user_id شناسهٔ کاربر (برای خواندن فلگ دستی؛ ۰ = بدون بررسی دستی).
	 * @return array{irregular:bool,stdev:float,outliers:int,cycles:int,reason:string,spread:int,manual?:bool}
	 */
	public static function evaluate( array $lengths, int $user_id = 0 ): array {
		$lengths = array_values( array_filter( array_map( 'intval', $lengths ) ) );
		$count   = count( $lengths );

		$out = array(
			'irregular' => false,
			'stdev'     => 0.0,
			'outliers'  => 0,
			'cycles'    => $count,
			'reason'    => '',
			'spread'    => 0,
		);

		if ( $count < self::MIN_CYCLES ) {
			$out['reason'] = 'not_enough_data';
			if ( $user_id > 0 ) {
				$out['manual']    = self::manual( $user_id );
				$out['irregular'] = $out['manual'];
			}
			return $out;
		}

		$stdev    = round( MB_Cycle_Engine::stdev( $lengths ), 2 );
		$outliers = 0;
		foreach ( $lengths as $len ) {
			if ( $len < self::NORMAL_MIN || $len > self::NORMAL_MAX ) {
				++$outliers;
			}
		}

		$out['stdev']    = $stdev;
		$out['outliers'] = $outliers;

		if ( $stdev >= self::STDEV_LIMIT ) {
			$out['irregular'] = true;
			$out['reason']    = 'stdev';
		} elseif ( $outliers >= 2 ) {
			$out['irregular'] = true;
			$out['reason']    = 'outliers';
		}

		// بررسی فلگ دستی اگر user_id داریم
		if ( $user_id > 0 && self::manual( $user_id ) ) {
			$out['irregular'] = true;
			$out['manual']    = true;
			if ( empty( $out['reason'] ) ) {
				$out['reason'] = 'manual';
			}
		}

		// پهنای بازهٔ پیش‌بینی: دست‌کم ۲ روز، حداکثر ۷ روز در هر طرف.
		$out['spread'] = (int) max( 2, min( 7, (int) ceil( $stdev ) ) );

		return $out;
	}

	public static function is_irregular( int $user_id ): bool {
		if ( self::manual( $user_id ) ) {
			return true;
		}
		$status = self::status( $user_id );
		return (bool) $status['irregular'];
	}

	/** N6 — انتخاب دستی «حالت نامنظم» در ویرایشگر چرخه. */
	public static function manual( int $user_id ): bool {
		return '1' === (string) get_user_meta( $user_id, 'mb_irregular_manual', true );
	}

	public static function set_manual( int $user_id, bool $on ): void {
		if ( $on ) {
			update_user_meta( $user_id, 'mb_irregular_manual', '1' );
			return;
		}
		delete_user_meta( $user_id, 'mb_irregular_manual' );
	}

	/**
	 * تبدیل یک تاریخ پیش‌بینی به بازهٔ شمسی خوانا.
	 *
	 * @return array{from:string,to:string,label:string}
	 */
	public static function range_for( string $date, int $spread ): array {
		$from = MB_Jalali::add_days( $date, -1 * $spread );
		$to   = MB_Jalali::add_days( $date, $spread );
		return array(
			'from'  => $from,
			'to'    => $to,
			'label' => MB_Jalali::range_fa( $from, $to ),
		);
	}

	/** متن توضیح برای بنر آموزشی. */
	public static function banner_text( array $status ): string {
		if ( 'stdev' === $status['reason'] ) {
			return 'طول چرخه‌هایت نوسان زیادی دارد (انحراف حدود '
				. MB_Jalali::fa_num( (string) $status['stdev'] )
				. ' روز). به همین دلیل پیش‌بینی‌ها را به‌صورت بازه نشان می‌دهیم، نه یک تاریخ دقیق.';
		}
		return 'چند چرخهٔ تو خارج از بازهٔ معمول ۲۱ تا ۳۵ روز بوده است. پیش‌بینی‌ها به‌صورت بازه نمایش داده می‌شوند.';
	}

	public static function education_text(): string {
		return 'چرخهٔ نامنظم می‌تواند دلایل خیلی سادهٔ موقتی داشته باشد (استرس، تغییر وزن، کم‌خوابی، سفر) و می‌تواند نشانهٔ شرایطی مثل سندرم تخمدان پلی‌کیستیک یا اختلال تیروئید باشد. این اپ نمی‌تواند تشخیص بدهد؛ اگر نامنظمی ادامه‌دار است با پزشک مطرح کن.';
	}
}