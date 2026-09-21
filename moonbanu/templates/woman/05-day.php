<?php
/**
 * #/day/:date — جزئیات روز.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_snap = (array) $data['snap'];
$mb_date = (string) $data['date'];
$mb_idx  = null !== $mb_snap['day_index'] ? (int) $mb_snap['day_index'] : 0;
?>
<div class="scr day">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1"><?php echo esc_html( MB_Jalali::format_fa( $mb_date, 'full' ) ); ?></h1>
				<p class="small">روز <?php echo esc_html( MB_UI::num( $mb_idx ) ); ?> از چرخه</p>
			</div>
			<?php echo MB_UI::chip( (string) $mb_snap['phase_label'], (string) $mb_snap['phase'] ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<section class="glass card hero gold-line">
			<div class="hero-ring">
				<?php echo MB_UI::ring( 132, (array) $data['segments'], $mb_idx, array( 'num' => MB_UI::num( $mb_idx ), 'sub' => 'روز چرخه' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<div>
				<h2 class="h2">در بدن تو چه می‌گذرد؟</h2>
				<p class="body"><?php echo esc_html( (string) $mb_snap['hormone'] ); ?></p>
			</div>
		</section>

		<div class="chips">
			<?php
			echo MB_UI::chip( 'تا قاعدگی: ' . ( null !== $mb_snap['days_left'] ? MB_UI::num( max( 0, (int) $mb_snap['days_left'] ) ) . ' روز' : '—' ), 'dark', 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput
			$mb_bleed = array(
				'none'  => 'بدون خونریزی',
				'spot'  => 'لکه‌بینی',
				'light' => 'کم',
				'med'   => 'متوسط',
				'heavy' => 'زیاد',
			);
			$mb_b = $data['log'] ? (string) $data['log']['bleeding'] : 'none';
			echo MB_UI::chip( 'خونریزی: ' . ( $mb_bleed[ $mb_b ] ?? '—' ), 'dark', 'drop' ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo MB_UI::chip( 'احتمال بارداری: ' . MB_UI::pct( (int) $mb_snap['pregnancy'] ), 'dark', 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>

		<!-- N11: Private Note Card -->
		<?php if ( isset( $data['note_private'] ) && ! empty( $data['note_private'] ) ) : ?>
			<section class="glass card private-note">
				<h3 class="h3">یادداشت خصوصی</h3>
				<p class="body"><?php echo esc_html( $data['note_private'] ); ?></p>
			</section>
		<?php endif; ?>

		<section class="sec">
			<h2 class="h2">احتمال تجربهٔ نشانه‌ها</h2>
			<?php if ( empty( $data['can_prob'] ) ) : ?>
				<?php echo MB_UI::pro_gate( 'احتمال نشانه‌ها', 'ماه‌بانو از الگوی شخصی تو احتمال هر نشانه در این روز چرخه را حساب می‌کند.' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php elseif ( empty( $data['probs'] ) ) : ?>
				<?php echo MB_UI::pnote( 'برای ساختن الگوی شخصی، چند روز نشانه‌هایت را ثبت کن.', 'warn', 'spark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php else : ?>
				<div class="glass card">
					<?php foreach ( (array) $data['probs'] as $mb_p ) : ?>
						<?php echo MB_UI::bar( (string) $mb_p['label'], (int) $mb_p['percent'], 'personal' === $mb_p['source'] ? 'gold' : 'norm' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endforeach; ?>
					<p class="small"><?php echo esc_html( 'personal' === ( $data['probs'][0]['source'] ?? '' ) ? 'بر پایه ثبت‌های خودت' : 'میانگین نوعی؛ با ثبت بیشتر شخصی می‌شود' ); ?></p>
				</div>
			<?php endif; ?>
		</section>

		<section class="sec">
			<h2 class="h2">مراقبت امروز</h2>
			<div class="glass card list">
				<?php foreach ( (array) $data['care'] as $mb_c ) : ?>
					<?php echo MB_UI::lrow( (string) $mb_c['title'], (string) $mb_c['text'], (string) $mb_c['icon'], array( 'right' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endforeach; ?>
			</div>
		</section>

		<div class="btn-col">
			<?php echo MB_UI::btn( 'ثبت نشانهٔ این روز', 'gold wide', 'goto', array( 'icon' => 'plus', 'data' => array( 'route' => 'log/' . $mb_date ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>

		<!-- N4: SOS Button -->
		<div class="btn-col sos-section">
			<?php echo MB_UI::btn( 'اضطراری (SOS)', 'sos wide', 'sos', array( 'icon' => 'alert' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
