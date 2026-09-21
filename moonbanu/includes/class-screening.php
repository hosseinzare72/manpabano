<?php
/**
 * N10 + D2 — غربالگری‌های معتبر.
 *
 * شش ابزار استاندارد، جایگزین کوییزهای غیررسمی نسخهٔ ۳٫۰:
 *  PHQ-9 (خلق) · GAD-7 (اضطراب) · PSQI-4 (خواب کوتاه)
 *  چک‌لیست PMDD طبق DSM-5 · PCOS طبق معیارهای روتردام · نشانه‌های کم‌خونی طبق WHO
 *
 * قواعد ثابت:
 *  - نمره‌دهی فقط سمت سرور. هیچ باند یا آستانه‌ای در کلاینت نیست.
 *  - لحن نتیجه همدلانه و بدون برچسب منفی؛ هیچ‌جا واژهٔ «شدید» به‌عنوان برچسب کاربر نمی‌آید.
 *  - هر نتیجه: مسیر ارجاع + مقالهٔ مرتبط + سلب مسئولیت ثابت.
 *  - نام‌های یکتا: PSQI-4 و PMDD (هیچ PSQ-4 و PMDS در کد نیست).
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Screening {

	const NOTE = 'این ابزار غربالگری آموزشی است، نه تشخیص پزشکی. تشخیص فقط با ارزیابی متخصص انجام می‌شود.';

	/** نوع نمره‌دهی: sum = جمع امتیاز، count = شمارش نشانه، criteria = معیار روتردام. */
	const SCORING_SUM      = 'sum';
	const SCORING_COUNT    = 'count';
	const SCORING_CRITERIA = 'criteria';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'ensure_rows' ), 30 );
	}

	/* --------------------------------------------------------------------- */
	/* گزینه‌های استاندارد                                                    */
	/* --------------------------------------------------------------------- */

	/** مقیاس چهارگانهٔ رسمی PHQ-9 و GAD-7 (۰ تا ۳). */
	private static function opt_phq(): array {
		return array(
			array( 'label' => 'به‌هیچ‌وجه', 'score' => 0 ),
			array( 'label' => 'چند روز', 'score' => 1 ),
			array( 'label' => 'بیشتر از نصف روزها', 'score' => 2 ),
			array( 'label' => 'تقریباً هر روز', 'score' => 3 ),
		);
	}

	/** بله/خیر برای چک‌لیست‌های نشانه‌ای. */
	private static function opt_yes(): array {
		return array(
			array( 'label' => 'خیر', 'score' => 0 ),
			array( 'label' => 'بله', 'score' => 1 ),
		);
	}

	/* --------------------------------------------------------------------- */
	/* تعریف ابزارها                                                         */
	/* --------------------------------------------------------------------- */

	/** همهٔ ابزارها با پرسش رسمی، باندها و متن نتیجه. */
	public static function instruments(): array {
		$phq = self::opt_phq();
		$yes = self::opt_yes();

		return array(

			/* ------------------------------ PHQ-9 ------------------------------ */
			'phq9' => array(
				'slug'       => 'phq9',
				'title'      => 'غربالگری خلق — PHQ-9',
				'short'      => 'PHQ-9',
				'intro'      => 'در دو هفتهٔ گذشته، هر یک از موارد زیر چقدر آزارت داده است؟ نُه پرسش رسمی، همان‌طور که در مطب‌ها استفاده می‌شود.',
				'cat'        => 'mental',
				'source'     => 'منبع: PHQ-9 · NHS / CDC',
				'article'    => 'mental',
				'sort_order' => 1,
				'scoring'    => self::SCORING_SUM,
				'questions'  => array(
					array( 'q' => 'کم‌علاقگی یا بی‌لذتی در انجام کارها', 'options' => $phq ),
					array( 'q' => 'احساس دل‌گرفتگی، افسردگی یا ناامیدی', 'options' => $phq ),
					array( 'q' => 'سختی در به‌خواب‌رفتن یا در خواب‌ماندن، یا خواب بیش از حد', 'options' => $phq ),
					array( 'q' => 'احساس خستگی یا کم‌انرژی بودن', 'options' => $phq ),
					array( 'q' => 'کم‌اشتهایی یا پرخوری', 'options' => $phq ),
					array( 'q' => 'احساس بدی نسبت به خودت — این‌که شکست خورده‌ای یا خودت و خانواده‌ات را ناامید کرده‌ای', 'options' => $phq ),
					array( 'q' => 'سختی در تمرکز روی کارها، مثل خواندن یا تماشای تلویزیون', 'options' => $phq ),
					array( 'q' => 'آن‌قدر کند حرکت‌کردن یا حرف‌زدن که دیگران متوجه شوند؛ یا برعکس، آن‌قدر بی‌قراری که بیشتر از معمول در حرکت باشی', 'options' => $phq ),
					array( 'q' => 'این فکر که بهتر بود نباشی، یا فکر آسیب‌زدن به خودت', 'options' => $phq ),
				),
				// باندهای رسمی PHQ-9 روی امتیاز خام ۰ تا ۲۷.
				'bands'      => array(
					array( 'key' => 'none', 'min' => 0, 'max' => 4, 'label' => 'بدون نشانهٔ قابل‌توجه', 'advice' => 'پاسخ‌هایت نشانهٔ قابل‌توجهی از افسردگی نشان نمی‌دهد. خواب منظم، حرکت روزانه و ارتباط انسانی همین وضعیت را نگه می‌دارد.', 'refer' => 'ارجاع لازم نیست؛ اگر حالت عوض شد دوباره این تست را بزن.' ),
					array( 'key' => 'mild', 'min' => 5, 'max' => 9, 'label' => 'نشانه‌های خفیف', 'advice' => 'چند نشانهٔ خفیف داری. این خیلی رایج است و معنایش ضعف نیست. ثبت روزانهٔ خلق در همین اپ کمک می‌کند الگو را ببینی.', 'refer' => 'اگر دو هفتهٔ دیگر هم همین بود، یک گفت‌وگو با روان‌شناس ارزشش را دارد.' ),
					array( 'key' => 'moderate', 'min' => 10, 'max' => 14, 'label' => 'نشانه‌هایت توجه می‌خواهد', 'advice' => 'نشانه‌هایت در حدی است که خودبه‌خود کم نمی‌شود. این یک اطلاع است، نه برچسب.', 'refer' => 'گفت‌وگو با روان‌شناس یا پزشک عمومی کمک می‌کند. گزارش پزشک همین اپ می‌تواند شروع خوبی برای آن گفت‌وگو باشد.' ),
					array( 'key' => 'moderate_high', 'min' => 15, 'max' => 19, 'label' => 'نشانه‌هایت توجه بیشتر می‌خواهد', 'advice' => 'نشانه‌هایت پررنگ است و باری روی دوشت گذاشته. کمک‌گرفتن در این نقطه مؤثرترین کار است.', 'refer' => 'گفت‌وگو با متخصص سلامت روان توصیه می‌شود؛ هر چه زودتر، بهتر.' ),
					array( 'key' => 'high', 'min' => 20, 'max' => 27, 'label' => 'نشانه‌هایت به کمک تخصصی نیاز دارد', 'advice' => 'نشانه‌هایت زیاد و فراگیر است. این وضعیت درمان‌پذیر است و تو مجبور نیستی تنها از آن بگذری.', 'refer' => 'مراجعه به روان‌پزشک یا روان‌شناس در اولین فرصت توصیه می‌شود.' ),
				),
				// پرسش ۹ (فکر آسیب به خود) همیشه پیام مراقبتی جدا می‌گیرد.
				'flag_item'  => 8,
				'flag_text'  => 'در پرسش آخر جوابی دادی که نمی‌خواهیم از کنارش رد شویم. اگر فکر آسیب‌زدن به خودت داری، همین الان با کسی که دوستش داری حرف بزن یا با اورژانس ۱۱۵ یا صدای مشاور ۱۴۸۰ تماس بگیر. تنها نمان.',
			),

			/* ------------------------------ GAD-7 ------------------------------ */
			'gad7' => array(
				'slug'       => 'gad7',
				'title'      => 'غربالگری اضطراب — GAD-7',
				'short'      => 'GAD-7',
				'intro'      => 'در دو هفتهٔ گذشته، هر یک از موارد زیر چقدر آزارت داده است؟ هفت پرسش رسمی.',
				'cat'        => 'mental',
				'source'     => 'منبع: GAD-7 · NHS',
				'article'    => 'mental',
				'sort_order' => 2,
				'scoring'    => self::SCORING_SUM,
				'questions'  => array(
					array( 'q' => 'احساس عصبی بودن، اضطراب یا کلافگی', 'options' => $phq ),
					array( 'q' => 'ناتوانی در متوقف‌کردن یا کنترل نگرانی', 'options' => $phq ),
					array( 'q' => 'نگرانی زیاد دربارهٔ چیزهای مختلف', 'options' => $phq ),
					array( 'q' => 'سختی در آرام‌شدن', 'options' => $phq ),
					array( 'q' => 'آن‌قدر بی‌قرار بودن که نشستن سرجا سخت شود', 'options' => $phq ),
					array( 'q' => 'زود دلخور یا زودرنج شدن', 'options' => $phq ),
					array( 'q' => 'احساس ترس، مثل این‌که اتفاق بدی در راه است', 'options' => $phq ),
				),
				'bands'      => array(
					array( 'key' => 'none', 'min' => 0, 'max' => 4, 'label' => 'بدون نشانهٔ قابل‌توجه', 'advice' => 'اضطراب قابل‌توجهی در پاسخ‌هایت دیده نمی‌شود.', 'refer' => 'ارجاع لازم نیست.' ),
					array( 'key' => 'mild', 'min' => 5, 'max' => 9, 'label' => 'اضطراب خفیف', 'advice' => 'اضطراب خفیفی داری. تمرین تنفس شمرده و کاهش کافئین در همین سطح اثر محسوس دارد.', 'refer' => 'اگر ادامه‌دار شد، یک جلسه مشاوره کافی است تا مسیر روشن شود.' ),
					array( 'key' => 'moderate', 'min' => 10, 'max' => 14, 'label' => 'اضطرابت توجه می‌خواهد', 'advice' => 'اضطرابت در حدی است که روی روزت اثر می‌گذارد. این قابل‌مدیریت است.', 'refer' => 'گفت‌وگو با روان‌شناس یا پزشک عمومی توصیه می‌شود.' ),
					array( 'key' => 'high', 'min' => 15, 'max' => 21, 'label' => 'اضطرابت توجه بیشتر می‌خواهد', 'advice' => 'اضطرابت پررنگ است و انرژی زیادی از تو می‌گیرد. درمان‌های مؤثر و کوتاه‌مدت برای همین وضعیت وجود دارد.', 'refer' => 'مراجعه به متخصص سلامت روان در اولین فرصت توصیه می‌شود.' ),
				),
			),

			/* ----------------------------- PSQI-4 ------------------------------ */
			'psqi4' => array(
				'slug'       => 'psqi4',
				'title'      => 'کیفیت خواب — PSQI-4',
				'short'      => 'PSQI-4',
				'intro'      => 'فرم کوتاه چهارسؤالهٔ پرسشنامهٔ کیفیت خواب پیتسبورگ. به یک ماه گذشته فکر کن.',
				'cat'        => 'mental',
				'source'     => 'منبع: Pittsburgh Sleep Quality Index (فرم کوتاه) · NHS',
				'article'    => 'mental',
				'sort_order' => 3,
				'scoring'    => self::SCORING_SUM,
				'questions'  => array(
					array(
						'q'       => 'در یک ماه گذشته، معمولاً چند دقیقه طول می‌کشد تا خوابت ببرد؟',
						'options' => array(
							array( 'label' => 'تا ۱۵ دقیقه', 'score' => 0 ),
							array( 'label' => '۱۶ تا ۳۰ دقیقه', 'score' => 1 ),
							array( 'label' => '۳۱ تا ۶۰ دقیقه', 'score' => 2 ),
							array( 'label' => 'بیشتر از ۶۰ دقیقه', 'score' => 3 ),
						),
					),
					array(
						'q'       => 'در یک ماه گذشته، شب‌ها چند ساعت واقعاً خوابیده‌ای؟',
						'options' => array(
							array( 'label' => 'بیشتر از ۷ ساعت', 'score' => 0 ),
							array( 'label' => '۶ تا ۷ ساعت', 'score' => 1 ),
							array( 'label' => '۵ تا ۶ ساعت', 'score' => 2 ),
							array( 'label' => 'کمتر از ۵ ساعت', 'score' => 3 ),
						),
					),
					array(
						'q'       => 'در یک ماه گذشته، چند بار در هفته نیمه‌شب یا خیلی زود از خواب پریده‌ای؟',
						'options' => array(
							array( 'label' => 'هیچ‌وقت', 'score' => 0 ),
							array( 'label' => 'کمتر از یک بار در هفته', 'score' => 1 ),
							array( 'label' => 'یک یا دو بار در هفته', 'score' => 2 ),
							array( 'label' => 'سه بار یا بیشتر در هفته', 'score' => 3 ),
						),
					),
					array(
						'q'       => 'در یک ماه گذشته، کیفیت کلی خوابت را چطور توصیف می‌کنی؟',
						'options' => array(
							array( 'label' => 'خیلی خوب', 'score' => 0 ),
							array( 'label' => 'نسبتاً خوب', 'score' => 1 ),
							array( 'label' => 'نسبتاً بد', 'score' => 2 ),
							array( 'label' => 'خیلی بد', 'score' => 3 ),
						),
					),
				),
				'bands'      => array(
					array( 'key' => 'good', 'min' => 0, 'max' => 3, 'label' => 'خواب خوب', 'advice' => 'کیفیت خوابت خوب است. ساعت خواب ثابت بهترین چیزی است که می‌توانی برای چرخه‌ات نگه داری.', 'refer' => 'ارجاع لازم نیست.' ),
					array( 'key' => 'fair', 'min' => 4, 'max' => 6, 'label' => 'خواب قابل‌قبول با نقطهٔ بهبود', 'advice' => 'خوابت بد نیست ولی جا برای بهتر شدن دارد. یک ساعت بی‌نمایشگر پیش از خواب بیشترین تأثیر را می‌گذارد.', 'refer' => 'ارجاع لازم نیست؛ دو هفته بعد دوباره بسنج.' ),
					array( 'key' => 'poor', 'min' => 7, 'max' => 9, 'label' => 'خوابت توجه می‌خواهد', 'advice' => 'الگوی خوابت به‌هم‌ریخته است و این روی خلق، درد و نظم چرخه اثر می‌گذارد.', 'refer' => 'اگر دو هفته با بهداشت خواب بهتر نشد، با پزشک مطرح کن.' ),
					array( 'key' => 'very_poor', 'min' => 10, 'max' => 12, 'label' => 'خوابت توجه بیشتر می‌خواهد', 'advice' => 'کم‌خوابی در این حد خودش عامل خستگی، اضطراب و بی‌نظمی چرخه می‌شود. این قابل‌درمان است.', 'refer' => 'گفت‌وگو با پزشک دربارهٔ اختلال خواب توصیه می‌شود.' ),
				),
			),

			/* ------------------------- PMDD طبق DSM-5 -------------------------- */
			'pmdd' => array(
				'slug'       => 'pmdd',
				'title'      => 'چک‌لیست PMDD طبق DSM-5',
				'short'      => 'PMDD',
				'intro'      => 'یازده نشانهٔ فهرست DSM-5 برای اختلال نارسایی پیش‌از‌قاعدگی. فقط نشانه‌هایی را بله بزن که در هفتهٔ پیش از قاعدگی پیدا می‌شوند و با شروع قاعدگی فروکش می‌کنند.',
				'cat'        => 'period',
				'source'     => 'منبع: DSM-5 · ACOG',
				'article'    => 'period',
				'sort_order' => 4,
				'scoring'    => self::SCORING_COUNT,
				'threshold'  => 5,
				// چهار نشانهٔ هستهٔ معیار B در DSM-5 (اندیس ۰ تا ۳).
				'core'       => array( 0, 1, 2, 3 ),
				'questions'  => array(
					array( 'q' => 'نوسان شدید خلق: ناگهان غمگین یا اشک‌آلود می‌شوی، یا به طردشدن حساس می‌شوی', 'options' => $yes ),
					array( 'q' => 'زودرنجی یا خشم پررنگ، یا بیشتر شدن تنش با اطرافیان', 'options' => $yes ),
					array( 'q' => 'خلق پایین پررنگ، ناامیدی یا افکار خودسرزنشگر', 'options' => $yes ),
					array( 'q' => 'اضطراب، تنش یا حس «کوک‌بودن» و لبهٔ پرتگاه بودن', 'options' => $yes ),
					array( 'q' => 'کم‌شدن علاقه به کارهای همیشگی', 'options' => $yes ),
					array( 'q' => 'سختی در تمرکز', 'options' => $yes ),
					array( 'q' => 'بی‌حالی، زودخستگی یا افت محسوس انرژی', 'options' => $yes ),
					array( 'q' => 'تغییر محسوس اشتها، پرخوری یا هوس غذایی خاص', 'options' => $yes ),
					array( 'q' => 'پرخوابی یا بی‌خوابی', 'options' => $yes ),
					array( 'q' => 'حس از دست دادن کنترل یا زیر بار بودن', 'options' => $yes ),
					array( 'q' => 'نشانه‌های بدنی: حساسیت یا تورم سینه، درد مفصل یا عضله، نفخ، افزایش وزن', 'options' => $yes ),
				),
				'bands'      => array(
					array( 'key' => 'negative', 'min' => 0, 'max' => 4, 'label' => 'غربالگری منفی', 'advice' => 'تعداد نشانه‌هایت به آستانهٔ PMDD نمی‌رسد. ممکن است PMS معمول داشته باشی که با خواب منظم و حرکت سبک بهتر می‌شود.', 'refer' => 'ارجاع لازم نیست؛ ثبت روزانه را دو چرخه ادامه بده.' ),
					array( 'key' => 'positive', 'min' => 5, 'max' => 11, 'label' => 'غربالگری مثبت — نشانه‌هایت توجه بیشتر می‌خواهد', 'advice' => 'تعداد نشانه‌هایت به آستانهٔ غربالگری PMDD رسیده است. این یعنی ارزش بررسی دارد، نه این‌که تشخیص گذاشته شده. تأیید PMDD به ثبت روزانهٔ دو چرخهٔ کامل و ارزیابی متخصص نیاز دارد.', 'refer' => 'با متخصص زنان یا روان‌پزشک مطرح کن و گزارش پزشک همین اپ را همراهت ببر.' ),
				),
			),

			/* ---------------------- PCOS طبق روتردام -------------------------- */
			'pcos' => array(
				'slug'       => 'pcos',
				'title'      => 'غربالگری PCOS طبق معیارهای روتردام',
				'short'      => 'PCOS',
				'intro'      => 'معیار روتردام سه محور دارد و تشخیص با احراز دو محور از سه محور مطرح می‌شود. محور سوم (سونوگرافی) را فقط اگر پزشک گفته است بله بزن.',
				'cat'        => 'warning',
				'source'     => 'منبع: Rotterdam criteria · ACOG / WHO',
				'article'    => 'warning',
				'sort_order' => 5,
				'scoring'    => self::SCORING_CRITERIA,
				'threshold'  => 2,
				/**
				 * سه گروه معیار. یک «بله» در هر گروه، آن معیار را محقق می‌کند.
				 * A: اختلال تخمک‌گذاری · B: نشانه‌های آندروژن بالا · C: سونوگرافی
				 */
				'groups'     => array(
					'A' => array( 'label' => 'اختلال تخمک‌گذاری', 'items' => array( 0, 1, 2 ) ),
					'B' => array( 'label' => 'نشانه‌های آندروژن بالا', 'items' => array( 3, 4, 5 ) ),
					'C' => array( 'label' => 'یافتهٔ سونوگرافی', 'items' => array( 6 ) ),
				),
				'questions'  => array(
					array( 'q' => 'چرخه‌هایت معمولاً بیشتر از ۳۵ روز طول می‌کشد', 'options' => $yes ),
					array( 'q' => 'در سال کمتر از نُه بار قاعده می‌شوی', 'options' => $yes ),
					array( 'q' => 'بیشتر از سه ماه بدون بارداری قاعده نشده‌ای', 'options' => $yes ),
					array( 'q' => 'موی زائد صورت، سینه یا شکم داری که آزارت می‌دهد', 'options' => $yes ),
					array( 'q' => 'آکنهٔ مقاوم به درمان داری', 'options' => $yes ),
					array( 'q' => 'ریزش موی سر با الگوی مردانه داری', 'options' => $yes ),
					array( 'q' => 'پزشک در سونوگرافی تخمدان پلی‌کیستیک را گزارش کرده است', 'options' => $yes ),
				),
				'bands'      => array(
					array( 'key' => 'negative', 'min' => 0, 'max' => 1, 'label' => 'غربالگری منفی', 'advice' => 'کمتر از دو محور روتردام محقق شده است. ادامهٔ ثبت چرخه بهترین کاری است که می‌توانی بکنی.', 'refer' => 'ارجاع لازم نیست؛ اگر چرخه‌ات بی‌نظم شد دوباره بسنج.' ),
					array( 'key' => 'positive', 'min' => 2, 'max' => 3, 'label' => 'غربالگری مثبت — بررسی تخصصی ارزشش را دارد', 'advice' => 'دو محور یا بیشتر از معیار روتردام در پاسخ‌هایت محقق شده است. PCOS با آزمایش هورمونی و معاینه تأیید یا رد می‌شود؛ این نتیجه تشخیص نیست و درمان‌های مؤثری دارد.', 'refer' => 'مراجعه به متخصص زنان برای آزمایش هورمونی و سونوگرافی توصیه می‌شود.' ),
				),
			),

			/* --------------------- کم‌خونی طبق WHO ---------------------------- */
			'anemia' => array(
				'slug'       => 'anemia',
				'title'      => 'نشانه‌های کم‌خونی طبق WHO',
				'short'      => 'کم‌خونی',
				'intro'      => 'کم‌خونی فقر آهن در بانوان با قاعدگی سنگین شایع است. به یک ماه گذشته فکر کن.',
				'cat'        => 'nutrition',
				'source'     => 'منبع: WHO · CDC',
				'article'    => 'nutrition',
				'sort_order' => 6,
				'scoring'    => self::SCORING_COUNT,
				'threshold'  => 4,
				'questions'  => array(
					array( 'q' => 'خستگی یا بی‌حالی مداوم داری', 'options' => $yes ),
					array( 'q' => 'با فعالیت معمول نفست تنگ می‌شود', 'options' => $yes ),
					array( 'q' => 'سرگیجه یا سیاهی‌رفتن چشم داری', 'options' => $yes ),
					array( 'q' => 'تپش قلب بی‌دلیل حس می‌کنی', 'options' => $yes ),
					array( 'q' => 'پوست، پلک یا لثه‌ات رنگ‌پریده شده است', 'options' => $yes ),
					array( 'q' => 'دست و پایت زودتر از بقیه سرد می‌شود', 'options' => $yes ),
					array( 'q' => 'ناخن‌هایت شکننده شده یا موهایت بیشتر می‌ریزد', 'options' => $yes ),
					array( 'q' => 'سردرد مکرر داری', 'options' => $yes ),
					array( 'q' => 'هوس خوردن یخ، خاک یا چیزهای غیرخوراکی داری', 'options' => $yes ),
					array( 'q' => 'قاعدگی‌ات سنگین است: هر ۱ تا ۲ ساعت نیاز به تعویض، یا بیش از ۷ روز خونریزی', 'options' => $yes ),
				),
				'bands'      => array(
					array( 'key' => 'low', 'min' => 0, 'max' => 3, 'label' => 'نشانه‌های کم', 'advice' => 'نشانه‌های شاخص کمی داری. تغذیهٔ حاوی آهن (گوشت کم‌چرب، حبوبات، سبزی برگ‌سبز) همراه با ویتامین C جذب را بهتر می‌کند.', 'refer' => 'ارجاع لازم نیست.' ),
					array( 'key' => 'watch', 'min' => 4, 'max' => 6, 'label' => 'ارزش آزمایش دادن دارد', 'advice' => 'چند نشانهٔ هم‌زمان داری. یک آزمایش سادهٔ خون (CBC و فریتین) جواب روشنی می‌دهد.', 'refer' => 'از پزشک عمومی درخواست آزمایش CBC و فریتین کن.' ),
					array( 'key' => 'high', 'min' => 7, 'max' => 10, 'label' => 'نشانه‌هایت توجه بیشتر می‌خواهد', 'advice' => 'تعداد نشانه‌هایت زیاد است. کم‌خونی فقر آهن با درمان ساده برمی‌گردد، ولی خوددرمانی با مکمل بدون آزمایش درست نیست.', 'refer' => 'مراجعه به پزشک و انجام آزمایش خون در اولین فرصت توصیه می‌شود.' ),
				),
			),
		);
	}

	public static function get( string $slug ): ?array {
		$all = self::instruments();
		$slug = sanitize_key( $slug );
		return isset( $all[ $slug ] ) ? $all[ $slug ] : null;
	}

	/** فهرست کوتاه برای صفحهٔ غربالگری‌ها. */
	public static function listing( int $user_id ): array {
		$out = array();
		foreach ( self::instruments() as $slug => $inst ) {
			$row = self::row_for( $slug );
			$last = $row ? MB_DB::last_quiz_result( $user_id, (int) $row['id'] ) : null;
			$out[] = array(
				'slug'      => $slug,
				'title'     => $inst['title'],
				'short'     => $inst['short'],
				'intro'     => $inst['intro'],
				'cat'       => $inst['cat'],
				'source'    => $inst['source'],
				'count'     => count( $inst['questions'] ),
				'last_band' => $last ? (string) $last['band'] : '',
				'last_date' => $last ? MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $last['created_at'] ) ), 'long' ) : '',
			);
		}
		return $out;
	}

	/* --------------------------------------------------------------------- */
	/* نمره‌دهی سمت سرور                                                      */
	/* --------------------------------------------------------------------- */

	/**
	 * محاسبهٔ نتیجه.
	 *
	 * @param array $answers نگاشت اندیس پرسش => اندیس گزینه.
	 * @return array|WP_Error
	 */
	public static function score( string $slug, array $answers ) {
		$inst = self::get( $slug );
		if ( ! $inst ) {
			return new WP_Error( 'mb_screen', 'این غربالگری پیدا نشد.', array( 'status' => 404 ) );
		}

		$clean = array();
		$sum   = 0;
		$count = 0;
		foreach ( (array) $inst['questions'] as $index => $question ) {
			$options = (array) $question['options'];
			$picked  = isset( $answers[ $index ] ) ? (int) $answers[ $index ] : -1;
			if ( $picked < 0 || ! isset( $options[ $picked ] ) ) {
				return new WP_Error( 'mb_screen', 'به همهٔ پرسش‌ها پاسخ بده.', array( 'status' => 400 ) );
			}
			$clean[ $index ] = $picked;
			$points          = (int) $options[ $picked ]['score'];
			$sum            += $points;
			if ( $points > 0 ) {
				++$count;
			}
		}

		$max = self::max_score( $inst );

		switch ( (string) $inst['scoring'] ) {
			case self::SCORING_COUNT:
				$value = $count;
				$scale = count( (array) $inst['questions'] );
				break;

			case self::SCORING_CRITERIA:
				$value = self::criteria_met( $inst, $clean );
				$scale = count( (array) $inst['groups'] );
				break;

			default:
				$value = $sum;
				$scale = $max;
				break;
		}

		// معیار B در DSM-5 دست‌کم یک نشانهٔ هسته را لازم می‌داند و متن نتیجه هم
		// همین را می‌گوید، اما شمارش خام بدون آن به باند مثبت می‌رسید: پنج نشانهٔ
		// غیرهسته‌ای نباید نتیجهٔ مثبت PMDD بدهد.
		if ( self::SCORING_COUNT === (string) $inst['scoring'] && ! empty( $inst['core'] ) ) {
			$core_hit = 0;
			foreach ( (array) $inst['core'] as $core_index ) {
				if ( ! empty( $clean[ (int) $core_index ] ) ) {
					++$core_hit;
				}
			}
			if ( 0 === $core_hit ) {
				$threshold = (int) ( $inst['threshold'] ?? 0 );
				if ( $threshold > 1 ) {
					$value = min( $value, $threshold - 1 );
				}
			}
		}

		$band = self::band_for( $inst, $value );

		$result = array(
			'slug'      => (string) $inst['slug'],
			'title'     => (string) $inst['title'],
			'scoring'   => (string) $inst['scoring'],
			'score'     => $value,
			'max'       => $scale,
			'raw_sum'   => $sum,
			'percent'   => $scale > 0 ? (int) round( ( $value / $scale ) * 100 ) : 0,
			'band'      => (string) $band['key'],
			'label'     => (string) $band['label'],
			'advice'    => (string) $band['advice'],
			'refer'     => (string) $band['refer'],
			'source'    => (string) $inst['source'],
			'note'      => self::NOTE,
			'flag'      => '',
			'detail'    => '',
			'answers'   => $clean,
		);

		// پیام مراقبتی پرسش ۹ در PHQ-9.
		if ( isset( $inst['flag_item'] ) ) {
			$idx = (int) $inst['flag_item'];
			if ( isset( $clean[ $idx ] ) && $clean[ $idx ] > 0 ) {
				$result['flag'] = (string) $inst['flag_text'];
			}
		}

		// توضیح معیار برای PMDD و PCOS.
		if ( self::SCORING_COUNT === (string) $inst['scoring'] ) {
			$result['detail'] = MB_Jalali::fa_num( $value ) . ' نشانه از ' . MB_Jalali::fa_num( $scale ) . ' مورد فهرست';
			if ( ! empty( $inst['core'] ) ) {
				$core_hit = 0;
				foreach ( (array) $inst['core'] as $core_index ) {
					if ( ! empty( $clean[ $core_index ] ) ) {
						++$core_hit;
					}
				}
				$result['detail'] .= $core_hit > 0
					? ' · دست‌کم یک نشانهٔ هستهٔ DSM-5 هم داری'
					: ' · هیچ نشانهٔ هستهٔ DSM-5 علامت نخورده؛ برای PMDD وجود دست‌کم یکی از چهار نشانهٔ خلقی هسته لازم است';
			}
		} elseif ( self::SCORING_CRITERIA === (string) $inst['scoring'] ) {
			$names = array();
			foreach ( (array) $inst['groups'] as $key => $group ) {
				if ( self::group_met( $group, $clean ) ) {
					$names[] = (string) $group['label'];
				}
			}
			$result['detail'] = MB_Jalali::fa_num( $value ) . ' معیار از ' . MB_Jalali::fa_num( $scale )
				. ( empty( $names ) ? '' : ' · ' . implode( ' + ', $names ) );
		} else {
			$result['detail'] = 'امتیاز ' . MB_Jalali::fa_num( $value ) . ' از ' . MB_Jalali::fa_num( $scale );
		}

		return $result;
	}

	public static function max_score( array $inst ): int {
		$max = 0;
		foreach ( (array) $inst['questions'] as $question ) {
			$scores = array();
			foreach ( (array) $question['options'] as $option ) {
				$scores[] = (int) $option['score'];
			}
			$max += empty( $scores ) ? 0 : max( $scores );
		}
		return $max;
	}

	/** باند بر پایهٔ مقدار خام (نه درصد) — همان چیزی که ابزارهای رسمی می‌گویند. */
	public static function band_for( array $inst, int $value ): array {
		foreach ( (array) $inst['bands'] as $band ) {
			if ( $value >= (int) $band['min'] && $value <= (int) $band['max'] ) {
				return $band;
			}
		}
		$last = end( $inst['bands'] );
		return is_array( $last ) ? $last : array( 'key' => 'unknown', 'label' => 'نتیجهٔ نامشخص', 'advice' => '', 'refer' => '' );
	}

	private static function group_met( array $group, array $answers ): bool {
		foreach ( (array) $group['items'] as $index ) {
			if ( ! empty( $answers[ $index ] ) ) {
				return true;
			}
		}
		return false;
	}

	private static function criteria_met( array $inst, array $answers ): int {
		$met = 0;
		foreach ( (array) $inst['groups'] as $group ) {
			if ( self::group_met( $group, $answers ) ) {
				++$met;
			}
		}
		return $met;
	}

	/* --------------------------------------------------------------------- */
	/* ردیف جدول (برای شناسه و تاریخچه)                                      */
	/* --------------------------------------------------------------------- */

	/**
	 * هر ابزار یک ردیف در mb_quizzes دارد تا quiz_id برای تاریخچهٔ نتایج پایدار
	 * بماند. متن و باندها همیشه از همین کلاس خوانده می‌شوند، نه از دیتابیس.
	 */
	public static function ensure_rows(): void {
		global $wpdb;
		if ( (string) get_option( 'mb_screening_rows' ) === MB_VERSION ) {
			return;
		}
		$table = MB_DB::t( 'quizzes' );
		foreach ( self::instruments() as $slug => $inst ) {
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE slug = %s", $slug ) );
			$row    = array(
				'title'      => (string) $inst['title'],
				'intro'      => (string) $inst['intro'],
				'questions'  => wp_json_encode( array_map( static function ( $q ) { return array( 'q' => $q['q'] ); }, (array) $inst['questions'] ) ),
				'bands'      => wp_json_encode( array_map( static function ( $b ) { return array( 'key' => $b['key'], 'label' => $b['label'] ); }, (array) $inst['bands'] ) ),
				'cat'        => (string) $inst['cat'],
				'disclaimer' => self::NOTE,
				'active'     => 1,
				'sort_order' => (int) $inst['sort_order'],
			);
			if ( $exists ) {
				$wpdb->update( $table, $row, array( 'id' => (int) $exists ) );
			} else {
				$row['slug'] = $slug;
				$wpdb->insert( $table, $row );
			}
		}

		// کوییزهای غیررسمی نسخهٔ ۳٫۰ غیرفعال می‌شوند (تاریخچهٔ کاربران دست‌نخورده می‌ماند).
		$legacy = array( 'pms-severity', 'pcos-awareness', 'sleep-quality' );
		foreach ( $legacy as $slug ) {
			$wpdb->update( $table, array( 'active' => 0 ), array( 'slug' => $slug ), array( '%d' ), array( '%s' ) );
		}

		update_option( 'mb_screening_rows', MB_VERSION );
	}

	public static function row_for( string $slug ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT id,slug FROM ' . MB_DB::t( 'quizzes' ) . ' WHERE slug = %s', sanitize_key( $slug ) ), ARRAY_A );
		return $row ? $row : null;
	}

	/** ذخیرهٔ نتیجه در تاریخچه. */
	public static function save_result( int $user_id, array $result ): int {
		$row = self::row_for( (string) $result['slug'] );
		if ( ! $row ) {
			return 0;
		}
		// کلید باند ذخیره می‌شود (نه برچسب)، تا صفحهٔ نتیجه رنگ و شدت را درست بسازد.
		$id = MB_DB::save_quiz_result(
			$user_id,
			(int) $row['id'],
			(int) $result['score'],
			(int) $result['max'],
			(string) $result['band'],
			(array) $result['answers']
		);

		// نمایش نتیجه بدون حدس‌زدن ستون‌های دیتابیس؛ پاسخ‌ها ذخیره نمی‌شوند.
		$keep = $result;
		unset( $keep['answers'] );
		$keep['id']         = $id;
		$keep['created_at'] = current_time( 'mysql' );
		update_user_meta( $user_id, 'mb_screening_last_' . sanitize_key( (string) $result['slug'] ), $keep );
		update_user_meta( $user_id, 'mb_screening_last', sanitize_key( (string) $result['slug'] ) );
		return $id;
	}

	/** آخرین نتیجهٔ یک ابزار برای صفحهٔ نتیجه (N10). */
	public static function last_result( int $user_id, string $slug = '' ): ?array {
		$slug = sanitize_key( '' !== $slug ? $slug : (string) get_user_meta( $user_id, 'mb_screening_last', true ) );
		if ( '' === $slug ) {
			return null;
		}
		$row = get_user_meta( $user_id, 'mb_screening_last_' . $slug, true );
		return is_array( $row ) && ! empty( $row ) ? $row : null;
	}

	/** مقالهٔ مرتبط با یک ابزار. */
	public static function related_article( string $slug ): ?array {
		$inst = self::get( $slug );
		if ( ! $inst ) {
			return null;
		}
		$items = MB_Api::query_articles( (string) $inst['article'], '', 1 );
		return empty( $items ) ? null : $items[0];
	}

	/**
	 * وکتورهای نمونه برای خودآزمایی ادمین (D3: سه وکتور به‌ازای هر تست).
	 *
	 * @return array<string,array<int,array{answers:array,expect:string}>>
	 */
	public static function test_vectors(): array {
		return array(
			'phq9' => array(
				array( 'answers' => array_fill( 0, 9, 0 ), 'expect' => 'none' ),
				array( 'answers' => array( 1, 1, 1, 1, 1, 1, 1, 1, 0 ), 'expect' => 'mild' ),
				array( 'answers' => array_fill( 0, 9, 3 ), 'expect' => 'high' ),
			),
			'gad7' => array(
				array( 'answers' => array_fill( 0, 7, 0 ), 'expect' => 'none' ),
				array( 'answers' => array( 2, 2, 2, 1, 1, 1, 1 ), 'expect' => 'moderate' ),
				array( 'answers' => array_fill( 0, 7, 3 ), 'expect' => 'high' ),
			),
			'psqi4' => array(
				array( 'answers' => array( 0, 0, 0, 0 ), 'expect' => 'good' ),
				array( 'answers' => array( 2, 1, 1, 1 ), 'expect' => 'fair' ),
				array( 'answers' => array( 3, 3, 3, 3 ), 'expect' => 'very_poor' ),
			),
			'pmdd' => array(
				array( 'answers' => array_fill( 0, 11, 0 ), 'expect' => 'negative' ),
				array( 'answers' => array( 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0 ), 'expect' => 'negative' ),
				array( 'answers' => array( 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0 ), 'expect' => 'positive' ),
			),
			'pcos' => array(
				array( 'answers' => array_fill( 0, 7, 0 ), 'expect' => 'negative' ),
				array( 'answers' => array( 1, 1, 0, 0, 0, 0, 0 ), 'expect' => 'negative' ),
				array( 'answers' => array( 1, 0, 0, 1, 0, 0, 0 ), 'expect' => 'positive' ),
			),
			'anemia' => array(
				array( 'answers' => array_fill( 0, 10, 0 ), 'expect' => 'low' ),
				array( 'answers' => array( 1, 1, 1, 1, 0, 0, 0, 0, 0, 0 ), 'expect' => 'watch' ),
				array( 'answers' => array_fill( 0, 10, 1 ), 'expect' => 'high' ),
			),
		);
	}

	/** اجرای وکتورها؛ خروجی متن خوانا برای تب وضعیت. */
	public static function run_self_test(): array {
		$out = array();
		foreach ( self::test_vectors() as $slug => $vectors ) {
			$pass = 0;
			$fail = array();
			foreach ( $vectors as $vector ) {
				$result = self::score( $slug, (array) $vector['answers'] );
				if ( is_wp_error( $result ) ) {
					$fail[] = 'خطا: ' . $result->get_error_message();
					continue;
				}
				if ( (string) $result['band'] === (string) $vector['expect'] ) {
					++$pass;
				} else {
					$fail[] = 'انتظار ' . $vector['expect'] . '، نتیجه ' . $result['band'];
				}
			}
			$inst        = self::get( $slug );
			$out[ $slug ] = array(
				'title' => $inst ? (string) $inst['short'] : $slug,
				'pass'  => $pass,
				'total' => count( $vectors ),
				'fail'  => $fail,
			);
		}
		return $out;
	}
}
