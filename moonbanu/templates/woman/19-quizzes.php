<?php
/**
 * #/quizzes — فهرست تست‌های خودشناسی (M8، رایگان).
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="scr quizzes">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">تست‌های خودشناسی</h1>
				<p class="small">کوتاه، رایگان، و فقط برای آگاهی خودت</p>
			</div>
			<?php echo MB_UI::icon( 'quiz', 24, 1.7, 'hdr-ic' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<?php echo MB_UI::pnote( (string) $data['note'], 'warn', 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php if ( empty( $data['quizzes'] ) ) : ?>
			<div class="glass card center"><p class="small">هنوز تستی تنظیم نشده است.</p></div>
		<?php else : ?>
			<?php foreach ( (array) $data['quizzes'] as $mb_q ) : ?>
				<a class="glass card quiz-card" href="#/quiz/<?php echo esc_attr( (string) $mb_q['slug'] ); ?>">
					<div class="qc-top">
						<h2 class="h2"><?php echo esc_html( (string) $mb_q['title'] ); ?></h2>
						<?php echo MB_UI::icon( 'back', 16, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
					<p class="small"><?php echo esc_html( (string) $mb_q['intro'] ); ?></p>
					<div class="chips">
						<?php
						echo MB_UI::chip( MB_UI::num( (int) $mb_q['count'] ) . ' پرسش', 'default', 'quiz' ); // phpcs:ignore WordPress.Security.EscapeOutput
						if ( '' !== (string) $mb_q['last_band'] ) {
							echo MB_UI::chip( 'قبلاً پاسخ داده‌ای', 'gold', 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput
						}
						?>
					</div>
				</a>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'quizzes' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
