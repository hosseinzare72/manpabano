<?php
/**
 * M8 — تست‌های خودشناسی (کوییز).
 *
 * سه کوییز پایه در نصب seed می‌شوند: شدت PMS، آگاهی PCOS، کیفیت خواب.
 * هر کوییز باند امتیاز، توصیه، و برچسب «غربالگری است نه تشخیص» دارد.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Quiz {

	const SCREENING_NOTE = 'این تست غربالگری آموزشی است، نه تشخیص پزشکی.';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'maybe_seed' ), 30 );
	}

	/* --------------------------------------------------------------------- */
	/* seed                                                                  */
	/* --------------------------------------------------------------------- */

	public static function maybe_seed(): void {
		if ( get_option( 'mb_quizzes_seeded' ) ) {
			return;
		}
		global $wpdb;
		foreach ( self::defaults() as $quiz ) {
			$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . MB_DB::t( 'quizzes' ) . ' WHERE slug = %s', $quiz['slug'] ) );
			if ( $exists ) {
				continue;
			}
			$wpdb->insert(
				MB_DB::t( 'quizzes' ),
				array(
					'slug'       => $quiz['slug'],
					'title'      => $quiz['title'],
					'intro'      => $quiz['intro'],
					'questions'  => wp_json_encode( $quiz['questions'] ),
					'bands'      => wp_json_encode( $quiz['bands'] ),
					'cat'        => $quiz['cat'],
					'disclaimer' => self::SCREENING_NOTE,
					'active'     => 1,
					'sort_order' => (int) $quiz['sort_order'],
				)
			);
		}
		update_option( 'mb_quizzes_seeded', 1 );
	}

	/* --------------------------------------------------------------------- */
	/* امتیازدهی                                                             */
	/* --------------------------------------------------------------------- */

	/** حداکثر امتیاز ممکن یک کوییز. */
	public static function max_score( array $quiz ): int {
		$max = 0;
		foreach ( (array) $quiz['questions'] as $question ) {
			$scores = array();
			foreach ( (array) ( $question['options'] ?? array() ) as $option ) {
				$scores[] = (int) ( $option['score'] ?? 0 );
			}
			$max += empty( $scores ) ? 0 : max( $scores );
		}
		return $max;
	}

	/**
	 * محاسبهٔ نتیجه از پاسخ‌ها.
	 *
	 * @param array $answers نگاشت اندیس پرسش => اندیس گزینه.
	 * @return array{score:int,max:int,band:array,percent:int,answers:array}|null
	 */
	public static function score( array $quiz, array $answers ): ?array {
		$questions = (array) $quiz['questions'];
		if ( empty( $questions ) ) {
			return null;
		}

		$clean = array();
		$score = 0;
		foreach ( $questions as $index => $question ) {
			$options = (array) ( $question['options'] ?? array() );
			$picked  = isset( $answers[ $index ] ) ? (int) $answers[ $index ] : -1;
			if ( $picked < 0 || ! isset( $options[ $picked ] ) ) {
				return null; // همهٔ پرسش‌ها باید پاسخ داشته باشند.
			}
			$clean[ $index ] = $picked;
			$score          += (int) ( $options[ $picked ]['score'] ?? 0 );
		}

		$max  = self::max_score( $quiz );
		$band = self::band_for( $quiz, $score, $max );

		return array(
			'score'   => $score,
			'max'     => $max,
			'band'    => $band,
			'percent' => $max > 0 ? (int) round( ( $score / $max ) * 100 ) : 0,
			'answers' => $clean,
		);
	}

	/** یافتن باند امتیاز. باندها بر پایهٔ درصد امتیاز تعریف می‌شوند. */
	public static function band_for( array $quiz, int $score, int $max ): array {
		$percent = $max > 0 ? ( $score / $max ) * 100 : 0;
		$bands   = (array) $quiz['bands'];
		$fallback = array( 'key' => 'unknown', 'label' => 'نتیجهٔ نامشخص', 'advice' => 'برای بررسی دقیق‌تر با پزشک مشورت کن.', 'cat' => (string) ( $quiz['cat'] ?? 'period' ) );

		foreach ( $bands as $band ) {
			$min = (float) ( $band['min'] ?? 0 );
			$to  = (float) ( $band['max'] ?? 100 );
			if ( $percent >= $min && $percent <= $to ) {
				return wp_parse_args( $band, $fallback );
			}
		}
		return $fallback;
	}

	/* --------------------------------------------------------------------- */
	/* محتوای پیش‌فرض                                                        */
	/* --------------------------------------------------------------------- */

	private static function freq_options(): array {
		return array(
			array( 'label' => 'هرگز', 'score' => 0 ),
			array( 'label' => 'به‌ندرت', 'score' => 1 ),
			array( 'label' => 'بعضی ماه‌ها', 'score' => 2 ),
			array( 'label' => 'بیشتر ماه‌ها', 'score' => 3 ),
		);
	}

	private static function yesno_options(): array {
		return array(
			array( 'label' => 'خیر', 'score' => 0 ),
			array( 'label' => 'مطمئن نیستم', 'score' => 1 ),
			array( 'label' => 'بله', 'score' => 2 ),
		);
	}

	public static function defaults(): array {
		$freq  = self::freq_options();
		$yesno = self::yesno_options();

		return array(
			array(
				'slug'       => 'pms-severity',
				'title'      => 'شدت نشانه‌های پیش از قاعدگی',
				'intro'      => 'ده پرسش کوتاه دربارهٔ روزهای پیش از قاعدگی. به حال معمول چند ماه گذشته فکر کن.',
				'cat'        => 'period',
				'sort_order' => 1,
				'questions'  => array(
					array( 'q' => 'پیش از قاعدگی زودرنج یا عصبی می‌شوی؟', 'options' => $freq ),
					array( 'q' => 'حس غم یا گریهٔ بی‌دلیل داری؟', 'options' => $freq ),
					array( 'q' => 'سینه‌هایت درد می‌گیرد یا حساس می‌شود؟', 'options' => $freq ),
					array( 'q' => 'نفخ یا احساس سنگینی شکم داری؟', 'options' => $freq ),
					array( 'q' => 'سردرد می‌گیری؟', 'options' => $freq ),
					array( 'q' => 'اشتهایت به‌شدت تغییر می‌کند؟', 'options' => $freq ),
					array( 'q' => 'خوابت به‌هم می‌ریزد؟', 'options' => $freq ),
					array( 'q' => 'تمرکز کردن سخت‌تر می‌شود؟', 'options' => $freq ),
					array( 'q' => 'این نشانه‌ها روی کار یا رابطه‌هایت اثر می‌گذارد؟', 'options' => $freq ),
					array( 'q' => 'با شروع قاعدگی نشانه‌ها سریع بهتر می‌شوند؟', 'options' => $freq ),
				),
				'bands'      => array(
					array( 'key' => 'mild', 'min' => 0, 'max' => 33, 'label' => 'نشانه‌های خفیف', 'advice' => 'وضعیت تو در محدودهٔ خفیف است. خواب منظم، حرکت سبک و کاهش کافئین معمولاً همین حد را هم کمتر می‌کند.', 'cat' => 'period' ),
					array( 'key' => 'moderate', 'min' => 34, 'max' => 66, 'label' => 'نشانه‌های متوسط', 'advice' => 'نشانه‌هایت قابل‌توجه است. ثبت روزانه در همین اپ به تو و پزشکت کمک می‌کند الگو را ببینید.', 'cat' => 'period' ),
					array( 'key' => 'severe', 'min' => 67, 'max' => 100, 'label' => 'نشانه‌های شدید', 'advice' => 'شدت نشانه‌ها بالاست و بهتر است با پزشک مطرح کنی؛ گزارش پزشک همین اپ می‌تواند مستند خوبی باشد.', 'cat' => 'warning' ),
				),
			),
			array(
				'slug'       => 'pcos-awareness',
				'title'      => 'آگاهی از نشانه‌های تخمدان پلی‌کیستیک',
				'intro'      => 'این تست فقط آگاهی می‌دهد و هیچ تشخیصی نمی‌گذارد. تشخیص PCOS نیاز به معاینه، آزمایش و سونوگرافی دارد.',
				'cat'        => 'warning',
				'sort_order' => 2,
				'questions'  => array(
					array( 'q' => 'چرخه‌هایت بیشتر از ۳۵ روز طول می‌کشد؟', 'options' => $yesno ),
					array( 'q' => 'در سال کمتر از ۹ بار قاعده می‌شوی؟', 'options' => $yesno ),
					array( 'q' => 'موی زائد صورت یا بدن داری که آزارت می‌دهد؟', 'options' => $yesno ),
					array( 'q' => 'آکنهٔ مقاوم به درمان داری؟', 'options' => $yesno ),
					array( 'q' => 'ریزش مو با الگوی مردانه داری؟', 'options' => $yesno ),
					array( 'q' => 'افزایش وزن بی‌دلیل یا سختی در کاهش وزن داری؟', 'options' => $yesno ),
					array( 'q' => 'در خانواده‌ات سابقهٔ PCOS یا دیابت هست؟', 'options' => $yesno ),
					array( 'q' => 'لکه‌های تیره روی گردن یا زیر بغل دیده‌ای؟', 'options' => $yesno ),
				),
				'bands'      => array(
					array( 'key' => 'low', 'min' => 0, 'max' => 30, 'label' => 'نشانه‌های کم', 'advice' => 'نشانه‌های شاخص کمی داری. ثبت منظم چرخه را ادامه بده.', 'cat' => 'period' ),
					array( 'key' => 'watch', 'min' => 31, 'max' => 60, 'label' => 'ارزش پیگیری دارد', 'advice' => 'چند نشانهٔ قابل‌توجه داری. بد نیست در ویزیت بعدی با پزشک مطرح کنی.', 'cat' => 'warning' ),
					array( 'key' => 'high', 'min' => 61, 'max' => 100, 'label' => 'بهتر است بررسی شود', 'advice' => 'تعداد نشانه‌ها زیاد است. مراجعه به متخصص زنان برای بررسی هورمونی توصیه می‌شود. این نتیجه تشخیص نیست.', 'cat' => 'warning' ),
				),
			),
			array(
				'slug'       => 'sleep-quality',
				'title'      => 'کیفیت خواب',
				'intro'      => 'خواب روی خلق، درد و نظم چرخه اثر مستقیم دارد. به دو هفتهٔ گذشته فکر کن.',
				'cat'        => 'mental',
				'sort_order' => 3,
				'questions'  => array(
					array( 'q' => 'برای خوابیدن بیشتر از نیم ساعت وقت می‌گذاری؟', 'options' => $freq ),
					array( 'q' => 'شب‌ها چند بار از خواب می‌پری؟', 'options' => $freq ),
					array( 'q' => 'صبح‌ها خسته بیدار می‌شوی؟', 'options' => $freq ),
					array( 'q' => 'روزها خواب‌آلوده‌ای؟', 'options' => $freq ),
					array( 'q' => 'پیش از خواب از موبایل استفاده می‌کنی؟', 'options' => $freq ),
					array( 'q' => 'ساعت خوابت هر شب متفاوت است؟', 'options' => $freq ),
					array( 'q' => 'بعدازظهر کافئین مصرف می‌کنی؟', 'options' => $freq ),
				),
				'bands'      => array(
					array( 'key' => 'good', 'min' => 0, 'max' => 30, 'label' => 'خواب خوب', 'advice' => 'الگوی خوابت سالم است. همین نظم را نگه دار.', 'cat' => 'mental' ),
					array( 'key' => 'fair', 'min' => 31, 'max' => 60, 'label' => 'خواب متوسط', 'advice' => 'با ثابت‌کردن ساعت خواب و کنار گذاشتن موبایل نیم‌ساعت پیش از خواب، تفاوت را در چند هفته حس می‌کنی.', 'cat' => 'mental' ),
					array( 'key' => 'poor', 'min' => 61, 'max' => 100, 'label' => 'خواب نامناسب', 'advice' => 'کیفیت خوابت پایین است و این می‌تواند نشانه‌های چرخه را بدتر کند. اگر چند هفته ادامه داشت با پزشک مطرح کن.', 'cat' => 'warning' ),
				),
			),
		);
	}
}
