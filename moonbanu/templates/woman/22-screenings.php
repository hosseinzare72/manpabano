<?php
/**
 * #/screenings — فهرست ابزارهای غربالگری (N10).
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_screenings = isset( $data['screenings'] ) ? (array) $data['screenings'] : array();
$mb_open       = ( isset( $data['open'] ) && is_array( $data['open'] ) ) ? $data['open'] : null;
?>
<div class="scr screenings">
	<div class="pad">
		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<h1 class="h1">ابزارهای سلامت</h1>
		</header>

		<?php echo MB_UI::pnote( 'محتوای آموزشی — جایگزین نظر متخصص نیست', 'info', 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php if ( null !== $mb_open ) : ?>
			<!-- N10 — پرسش‌نامهٔ ابزار باز -->
			<section class="glass card">
				<h2 class="h2"><?php echo esc_html( (string) ( $mb_open['title'] ?? '' ) ); ?></h2>
				<p class="body"><?php echo esc_html( (string) ( $mb_open['intro'] ?? '' ) ); ?></p>
				<form id="screening-form" data-tool="<?php echo esc_attr( (string) ( $mb_open['slug'] ?? '' ) ); ?>">
					<?php foreach ( (array) ( $mb_open['questions'] ?? array() ) as $mb_i => $mb_q ) : ?>
						<fieldset class="form-group qz">
							<legend class="h3"><?php echo esc_html( MB_UI::num( (int) $mb_i + 1 ) . '. ' . (string) ( $mb_q['q'] ?? '' ) ); ?></legend>
							<div class="chips">
								<?php foreach ( (array) ( $mb_q['options'] ?? array() ) as $mb_oi => $mb_o ) : ?>
									<label class="chip pick">
										<input type="radio" name="q_<?php echo (int) $mb_i; ?>" value="<?php echo (int) $mb_oi; ?>">
										<span><?php echo esc_html( (string) ( $mb_o['label'] ?? '' ) ); ?></span>
									</label>
								<?php endforeach; ?>
							</div>
						</fieldset>
					<?php endforeach; ?>
					<div class="btn-col">
						<?php echo MB_UI::btn( 'دیدن نتیجه', 'gold wide', 'screening-submit' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</form>
				<p class="small"><?php echo esc_html( (string) ( $mb_open['source'] ?? '' ) ); ?></p>
			</section>
		<?php elseif ( empty( $mb_screenings ) ) : ?>
			<section class="glass card empty-state">
				<?php echo MB_UI::icon( 'quiz', 28, 1.6 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<h2 class="h2">ابزاری در دسترس نیست</h2>
				<p class="body">ابزارهای غربالگری هنوز آماده نشده‌اند. بعداً سر بزن.</p>
			</section>
		<?php else : ?>
			<section class="glass card">
				<div class="screening-list">
					<?php foreach ( $mb_screenings as $mb_s ) : ?>
						<div class="screening-card">
							<h3 class="h3"><?php echo esc_html( (string) ( $mb_s['title'] ?? $mb_s['short'] ?? '' ) ); ?></h3>
							<p class="small"><?php echo esc_html( (string) ( $mb_s['intro'] ?? '—' ) ); ?></p>
							<?php if ( ! empty( $mb_s['last_band'] ) ) : ?>
								<p class="tiny muted"><?php echo esc_html( 'آخرین نتیجه: ' . (string) ( $mb_s['last_date'] ?? '' ) ); ?></p>
							<?php endif; ?>
							<?php echo MB_UI::btn( 'شروع', 'ghost norm', 'goto', array( 'data' => array( 'route' => 'screening/' . (string) ( $mb_s['slug'] ?? '' ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'health' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
