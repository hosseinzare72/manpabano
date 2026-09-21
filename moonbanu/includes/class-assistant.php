<?php
/**
 * M9 — دستیار ماه (نسخهٔ پیشرفته).
 *
 * یک دستیار «قاعده‌مند» است: هیچ مدل زبانی و هیچ تماس بیرونی ندارد. پاسخ‌ها از
 * تطبیق کلیدواژه + فاز فعلی چرخه + آخرین لاگ‌های خود کاربر ساخته می‌شوند.
 * اگر کلیدواژه‌ای پیدا نشود، کاربر به تیکت پشتیبانی راهنمایی می‌شود.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Assistant {

	const LABEL = 'این دستیار قاعده‌مند است، نه مشاورهٔ پزشکی.';

	/** حداکثر طول پرسش. */
	const MAX_LEN = 400;

	/* --------------------------------------------------------------------- */
	/* موضوع‌ها و کلیدواژه‌ها                                                 */
	/* --------------------------------------------------------------------- */

	public static function topics(): array {
		return array(
			'pain'          => array(
				'keywords' => array( 'درد', 'دل درد', 'دل‌درد', 'دل پیچه', 'کرامپ', 'گرفتگی', 'کمردرد', 'قولنج', 'اسپاسم' ),
				'title'    => 'درد قاعدگی',
				'cat'      => 'period',
			),
			'bleeding'      => array(
				'keywords' => array( 'خونریزی', 'لکه', 'لکه‌بینی', 'لکه بینی', 'پریود شدید', 'خون زیاد', 'کم خونی' ),
				'title'    => 'خونریزی',
				'cat'      => 'period',
			),
			'mood'          => array(
				'keywords' => array( 'خلق', 'عصبی', 'گریه', 'افسرده', 'اضطراب', 'استرس', 'بی‌حوصله', 'بی حوصله', 'زودرنج' ),
				'title'    => 'خلق و حال',
				'cat'      => 'mental',
			),
			'sleep'         => array(
				'keywords' => array( 'خواب', 'بی‌خوابی', 'بی خوابی', 'خسته', 'خستگی', 'بیدار' ),
				'title'    => 'خواب',
				'cat'      => 'mental',
			),
			'contraception' => array(
				'keywords' => array( 'پیشگیری', 'قرص', 'کاندوم', 'آی یو دی', 'iud', 'جلوگیری', 'اورژانسی' ),
				'title'    => 'پیشگیری',
				'cat'      => 'contraception',
			),
			'pregnancy'     => array(
				'keywords' => array( 'بارداری', 'باردار', 'حامله', 'تست بارداری', 'جنین', 'موعد', 'لقاح', 'تخمک‌گذاری', 'تخمک گذاری' ),
				'title'    => 'بارداری',
				'cat'      => 'pregnancy',
			),
			'pms'           => array(
				'keywords' => array( 'pms', 'پی ام اس', 'پیش از قاعدگی', 'سندرم پیش', 'نفخ', 'سینه درد', 'حساسیت سینه' ),
				'title'    => 'نشانه‌های پیش از قاعدگی',
				'cat'      => 'period',
			),
		);
	}

	/** یافتن موضوع از متن پرسش. */
	public static function match_topic( string $text ): string {
		$text = self::normalize( $text );
		foreach ( self::topics() as $key => $topic ) {
			foreach ( $topic['keywords'] as $word ) {
				if ( false !== mb_strpos( $text, self::normalize( $word ) ) ) {
					return $key;
				}
			}
		}
		return '';
	}

	/** یکسان‌سازی «ی/ک» عربی و فاصلهٔ مجازی برای تطبیق بهتر. */
	private static function normalize( string $text ): string {
		$text = str_replace( array( 'ي', 'ك', 'ۀ', 'أ', 'إ', 'ؤ', "\u{200c}", "\u{200f}" ), array( 'ی', 'ک', 'ه', 'ا', 'ا', 'و', ' ', '' ), $text );
		$text = MB_Jalali::en_num( $text );
		$text = preg_replace( '/\s+/u', ' ', $text );
		return trim( mb_strtolower( (string) $text ) );
	}

	/* --------------------------------------------------------------------- */
	/* ساخت پاسخ                                                             */
	/* --------------------------------------------------------------------- */

	/**
	 * پاسخ دستیار.
	 *
	 * @return array{topic:string,title:string,phase:string,phase_label:string,answer:string,facts:array,article:?array,fallback:bool,label:string}
	 */
	public static function answer( int $user_id, string $question ): array {
		$question = mb_substr( sanitize_textarea_field( $question ), 0, self::MAX_LEN );
		$topic    = self::match_topic( $question );
		$today    = MB_Jalali::today();
		$phase    = MB_Cycle_Engine::phase_of( $user_id, $today );

		$out = array(
			'topic'       => $topic,
			'title'       => '',
			'phase'       => $phase,
			'phase_label' => MB_Cycle_Engine::phase_label( $phase ),
			'answer'      => '',
			'facts'       => self::facts( $user_id, $today, $phase ),
			'article'     => null,
			'fallback'    => false,
			'label'       => self::LABEL,
		);

		if ( '' === $topic ) {
			$out['fallback'] = true;
			$out['title']    = 'مطمئن نیستم';
			$out['answer']   = 'این پرسش را نتوانستم به موضوع‌های شناخته‌شده وصل کنم. برای پاسخ دقیق‌تر یک تیکت پشتیبانی بزن یا از «پرسش از متخصص» استفاده کن.';
			return $out;
		}

		$topics        = self::topics();
		$out['title']  = (string) $topics[ $topic ]['title'];
		$out['answer'] = self::compose( $user_id, $topic, $phase );
		$out['article'] = self::article_for( (string) $topics[ $topic ]['cat'] );

		return $out;
	}

	/** واقعیت‌های شخصی کاربر که در پاسخ استفاده می‌شوند. */
	private static function facts( int $user_id, string $today, string $phase ): array {
		$log  = MB_DB::get_log( $user_id, $today );
		$logs = MB_DB::recent_logs( $user_id, 14 );

		$pains  = array();
		$moods  = array();
		$sleeps = array();
		foreach ( $logs as $row ) {
			if ( (int) $row['pain'] > 0 ) {
				$pains[] = (int) $row['pain'];
			}
			if ( null !== $row['mood'] ) {
				$moods[] = (int) $row['mood'];
			}
			if ( null !== $row['sleep_h'] ) {
				$sleeps[] = (float) $row['sleep_h'];
			}
		}

		return array(
			'day_index'   => MB_Cycle_Engine::day_index( $user_id, $today ),
			'days_to'     => MB_Cycle_Engine::days_to_next_period( $user_id, $today ),
			'today_pain'  => $log ? (int) $log['pain'] : 0,
			'today_mood'  => $log && null !== $log['mood'] ? (int) $log['mood'] : 0,
			'avg_pain'    => empty( $pains ) ? 0 : round( array_sum( $pains ) / count( $pains ), 1 ),
			'avg_mood'    => empty( $moods ) ? 0 : round( array_sum( $moods ) / count( $moods ), 1 ),
			'avg_sleep'   => empty( $sleeps ) ? 0 : round( array_sum( $sleeps ) / count( $sleeps ), 1 ),
			'logged_days' => count( $logs ),
			'pregnant'    => MB_Pregnancy::is_active( $user_id ),
			'irregular'   => MB_Irregular::is_irregular( $user_id ),
		);
	}

	/** متن پاسخ: فاز + دادهٔ کاربر + توصیهٔ موضوعی. */
	private static function compose( int $user_id, string $topic, string $phase ): string {
		$facts = self::facts( $user_id, MB_Jalali::today(), $phase );
		$label = MB_Cycle_Engine::phase_label( $phase );

		$head = 'الان در فاز «' . $label . '» هستی';
		if ( null !== $facts['day_index'] ) {
			$head .= ' (روز ' . MB_Jalali::fa_num( (string) $facts['day_index'] ) . ' چرخه)';
		}
		$head .= '. ';

		if ( $facts['pregnant'] ) {
			$head = 'حالت بارداری فعال است، پس پیش‌بینی چرخه خاموش است. ';
		}

		$body = '';
		switch ( $topic ) {
			case 'pain':
				$body = 'برای درد قاعدگی، گرمای موضعی روی شکم یا کمر، حرکت سبک و آب کافی معمولاً کمک‌کننده‌اند.';
				if ( $facts['avg_pain'] >= 3.5 ) {
					$body .= ' میانگین دردی که دو هفتهٔ گذشته ثبت کرده‌ای ' . MB_Jalali::fa_num( (string) $facts['avg_pain'] ) . ' از ۵ است؛ این عدد بالاست و ارزش مطرح‌کردن با پزشک را دارد.';
				} elseif ( $facts['today_pain'] >= 4 ) {
					$body .= ' امروز درد ' . MB_Jalali::fa_num( (string) $facts['today_pain'] ) . ' از ۵ ثبت کرده‌ای؛ اگر مسکن معمول جواب نداد، معطل نکن.';
				}
				$body .= ' دردی که تو را از کار و خواب بیندازد یا با مسکن کنترل نشود، نشانهٔ مراجعه است.';
				break;

			case 'bleeding':
				$body = 'خونریزی معمول بین ۲ تا ۷ روز طول می‌کشد.';
				if ( 'period' === $phase ) {
					$body .= ' الان در روزهای قاعدگی هستی، پس خونریزی طبیعی است.';
				}
				$body .= ' نشانه‌های هشدار: پرشدن یک پد در کمتر از یک ساعت برای چند ساعت پیوسته، خونریزی بیش از ۷ روز، لخته‌های بزرگ‌تر از یک سکه، یا خونریزی بین دو قاعدگی. این‌ها را با پزشک مطرح کن.';
				break;

			case 'mood':
				$body = 'نوسان خلق در فاز لوتئال و پیش از قاعدگی شایع و هورمونی است.';
				if ( $facts['avg_mood'] > 0 && $facts['avg_mood'] <= 2.5 ) {
					$body .= ' میانگین خلقی که ثبت کرده‌ای ' . MB_Jalali::fa_num( (string) $facts['avg_mood'] ) . ' از ۵ است؛ پایین‌تر از حد معمول.';
				}
				$body .= ' خواب منظم، حرکت روزانه و نور طبیعی بیشترین اثر ثابت‌شده را دارند. اگر غم یا بی‌انگیزگی بیشتر از دو هفته ماند و به روزهای چرخه وصل نبود، حتماً کمک حرفه‌ای بگیر.';
				break;

			case 'sleep':
				$body = 'خواب و چرخه دوطرفه روی هم اثر می‌گذارند.';
				if ( $facts['avg_sleep'] > 0 ) {
					$body .= ' میانگین خواب ثبت‌شده‌ات ' . MB_Jalali::fa_num( (string) $facts['avg_sleep'] ) . ' ساعت است.';
					if ( $facts['avg_sleep'] < 6.5 ) {
						$body .= ' این کمتر از نیاز معمول است و می‌تواند درد و زودرنجی را بدتر کند.';
					}
				} else {
					$body .= ' هنوز ساعت خواب ثبت نکرده‌ای؛ از صفحهٔ ثبت روزانه اضافه‌اش کن تا الگو را نشانت بدهم.';
				}
				$body .= ' ساعت خواب ثابت و کنار گذاشتن صفحه‌نمایش نیم‌ساعت پیش از خواب، دو تغییر کم‌هزینه با اثر زیاد است.';
				break;

			case 'contraception':
				$body = 'انتخاب روش پیشگیری کاملاً شخصی است و به سابقهٔ پزشکی، شیردهی و برنامهٔ بارداری تو بستگی دارد. این اپ نسخه نمی‌پیچد و روش خاصی را توصیه نمی‌کند؛ انتخاب روش باید با پزشک باشد.';
				$body .= ' اگر یادآور قرص می‌خواهی، از تنظیمات یادآورها فعالش کن.';
				break;

			case 'pregnancy':
				if ( $facts['pregnant'] ) {
					$body = 'حالت بارداری‌ات فعال است؛ کارت هفته و چک‌لیست مراقبت‌ها در صفحهٔ بارداری است.';
				} else {
					$body = 'پنجرهٔ باروری معمولاً چند روز پیش از تخمک‌گذاری و روز خودش است.';
					if ( $facts['irregular'] ) {
						$body .= ' چون چرخه‌هایت نوسان دارد، این پنجره را به‌صورت بازه در نظر بگیر، نه یک روز دقیق.';
					}
					$body .= ' برای تلاش هدفمند، ثبت دمای پایه و مخاط در نسخهٔ پیشرفته الگو را دقیق‌تر می‌کند. تست بارداری را از روز اول تأخیر قاعدگی بزن.';
				}
				break;

			case 'pms':
				$body = 'نشانه‌های پیش از قاعدگی معمولاً چند روز قبل شروع می‌شوند و با آمدن خونریزی سریع فروکش می‌کنند.';
				if ( null !== $facts['days_to'] && $facts['days_to'] >= 0 && $facts['days_to'] <= 5 ) {
					$body .= ' تا قاعدگی بعدی حدود ' . MB_Jalali::fa_num( (string) $facts['days_to'] ) . ' روز مانده، پس این نشانه‌ها با تقویم تو هم‌خوان است.';
				}
				$body .= ' کم‌کردن نمک و کافئین، حرکت سبک و خواب کافی بیشترین کمک را می‌کنند. اگر نشانه‌ها زندگی روزمره‌ات را به‌هم می‌ریزد، تست شدت PMS همین اپ را بزن و نتیجه‌اش را با پزشک ببر.';
				break;
		}

		return $head . $body;
	}

	/** یک مقالهٔ مرتبط از دستهٔ موضوع. */
	private static function article_for( string $cat ): ?array {
		$articles = MB_Api::query_articles( $cat, '', 1 );
		return empty( $articles ) ? null : (array) $articles[0];
	}
}
