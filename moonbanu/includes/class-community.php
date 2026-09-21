<?php
/**
 * M7 — انجمن ناشناس.
 *
 * چرخهٔ کار: کاربر در «پرسش از متخصص» تیک انتشار ناشناس را می‌زند → رکورد
 * انجمن با وضعیت pending ساخته می‌شود → مدیر پاسخ را تأیید می‌کند → رکورد در
 * اپ با برچسب ناشناس دیده می‌شود.
 *
 * قاعدهٔ سخت: خروجی عمومی هیچ user_id، ایمیل، یا نام ندارد و کاملاً escape
 * می‌شود (هیچ HTML عبور نمی‌کند).
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Community {

	/** حداکثر رکورد در هر صفحه. */
	const PER_PAGE = 20;

	/** ثبت پرسش برای انتشار ناشناس (پس از پاسخ متخصص). */
	public static function submit( int $question_id, string $question, string $cat ): int {
		$cat = sanitize_key( $cat );
		if ( ! array_key_exists( $cat, MB_Plugin::categories() ) ) {
			$cat = 'period';
		}
		return MB_DB::add_community( $question_id, $question, $cat );
	}

	/**
	 * فهرست عمومی. خروجی فقط شامل متن escape‌شده و برچسب ناشناس است.
	 *
	 * @return array<int,array{id:int,question:string,answer:string,cat:string,cat_label:string,anon:string,date_fa:string}>
	 */
	public static function feed( string $cat = '', int $page = 1 ): array {
		$page   = max( 1, $page );
		$offset = ( $page - 1 ) * self::PER_PAGE;
		$rows   = MB_DB::get_community_public( $cat, self::PER_PAGE, $offset );
		$cats   = MB_Plugin::categories();

		$out = array();
		foreach ( $rows as $row ) {
			// هیچ کلیدی جز این هفت مورد بیرون نمی‌رود.
			$out[] = array(
				'id'        => (int) $row['id'],
				'question'  => wp_strip_all_tags( (string) $row['question'] ),
				'answer'    => wp_strip_all_tags( (string) ( $row['answer'] ?? '' ) ),
				'cat'       => (string) $row['cat'],
				'cat_label' => (string) ( $cats[ $row['cat'] ] ?? '' ),
				'anon'      => (string) $row['anon_label'],
				'date_fa'   => MB_Jalali::format_fa( substr( (string) $row['created_at'], 0, 10 ), 'long' ),
			);
		}
		return $out;
	}

	public static function categories_with_counts(): array {
		$out = array();
		foreach ( MB_Plugin::categories() as $slug => $label ) {
			$out[ $slug ] = $label;
		}
		return $out;
	}

	/** آیا رکوردی برای نمایش هست؟ */
	public static function has_content(): bool {
		return MB_DB::count_community( 'approved' ) > 0;
	}
}
