<?php
/**
 * #/quiz/<slug> — اجرای یک کوییز و نمایش نتیجه (M8).
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_quiz = (array) ( $data['quiz'] ?? array() );
$mb_qs   = (array) ( $mb_quiz['questions'] ?? array() );
?>
<div class="scr quiz">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1"><?php echo esc_html( (string) $mb_quiz['title'] ); ?></h1>
				<p class="small"><?php echo esc_html( MB_UI::num( count( $mb_qs ) ) . ' پرسش' ); ?></p>
			</div>
		</header>

		<p class="body"><?php echo esc_html( (string) $mb_quiz['intro'] ); ?></p>
		<?php echo MB_UI::pnote( (string) $mb_quiz['disclaimer'], 'warn', 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<form class="mb-form quiz-form" id="mb-quiz-form" data-slug="<?php echo esc_attr( (string) $mb_quiz['slug'] ); ?>" novalidate>
			<?php foreach ( $mb_qs as $mb_i => $mb_q ) : ?>
				<fieldset class="glass card qz" data-index="<?php echo (int) $mb_i; ?>">
					<legend class="h2"><?php echo esc_html( MB_UI::num( (int) $mb_i + 1 ) . '. ' . (string) $mb_q['q'] ); ?></legend>
					<div class="qz-opts">
						<?php foreach ( (array) ( $mb_q['options'] ?? array() ) as $mb_oi => $mb_opt ) : ?>
							<button type="button" class="qz-opt" data-mb="quiz-pick" data-q="<?php echo (int) $mb_i; ?>" data-value="<?php echo (int) $mb_oi; ?>">
								<span class="qz-dot"></span>
								<span><?php echo esc_html( (string) $mb_opt['label'] ); ?></span>
							</button>
						<?php endforeach; ?>
					</div>
				</fieldset>
			<?php endforeach; ?>

			<?php echo MB_UI::btn( 'دیدن نتیجه', 'gold wide', 'quiz-submit', array( 'icon' => 'check' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</form>

		<div id="mb-quiz-result" class="quiz-result" hidden></div>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'quiz' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
