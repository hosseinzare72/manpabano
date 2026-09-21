<?php
/**
 * #/screening/:key — نتیجهٔ غربالگری (N10).
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_result = isset( $data['result'] ) ? (array) $data['result'] : array();
$mb_band = isset( $data['band'] ) ? (string) $data['band'] : 'none';
$mb_score = isset( $data['score'] ) ? (int) $data['score'] : 0;
$mb_label = isset( $data['label'] ) ? (string) $data['label'] : '—';
$mb_message = isset( $data['message'] ) ? (string) $data['message'] : '';
$mb_article = isset( $data['related_article'] ) ? (array) $data['related_article'] : array();
?>
<div class="scr screening-result">
	<div class="pad">
		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<h1 class="h1"><?php echo esc_html( $mb_result['name'] ?? 'نتیجه' ); ?></h1>
		</header>

		<?php echo MB_UI::pnote( 'محتوای آموزشی — جایگزین نظر متخصص نیست', 'info', 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<!-- Score Display -->
		<section class="glass card hero gold-line">
			<div style="text-align: center;">
				<p class="small">امتیاز</p>
				<h2 class="h2" style="font-size: 3em;">
					<?php echo esc_html( MB_UI::num( $mb_score ) ); ?>
				</h2>
			</div>
		</section>

		<!-- Band Label -->
		<section class="glass card">
			<h3 class="h3"><?php echo esc_html( $mb_label ); ?></h3>
			<?php if ( ! empty( $mb_message ) ) : ?>
				<p class="body"><?php echo esc_html( $mb_message ); ?></p>
			<?php endif; ?>
		</section>

		<!-- Care Path -->
		<?php if ( isset( $data['care_text'] ) ) : ?>
			<section class="glass card">
				<h3 class="h3">مسیر مراقبت</h3>
				<p class="body"><?php echo esc_html( $data['care_text'] ); ?></p>
				<?php
				if ( isset( $data['expert_link'] ) && ! empty( $data['expert_link'] ) ) {
					echo MB_UI::btn( 'مشورهٔ متخصص', 'ghost norm', 'href', array( 'href' => esc_url( $data['expert_link'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</section>
		<?php endif; ?>

		<!-- Related Article -->
		<?php if ( ! empty( $mb_article ) ) : ?>
			<section class="glass card">
				<h3 class="h3"><?php echo esc_html( $mb_article['title'] ?? '—' ); ?></h3>
				<p class="small"><?php echo esc_html( substr( $mb_article['excerpt'] ?? '', 0, 200 ) . '...' ); ?></p>
				<?php echo MB_UI::btn( 'مطالعهٔ کامل', 'ghost norm', 'goto', array( 'data' => array( 'route' => 'article/' . esc_attr( $mb_article['id'] ?? '' ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</section>
		<?php endif; ?>

		<!-- Disclaimer -->
		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'health' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
