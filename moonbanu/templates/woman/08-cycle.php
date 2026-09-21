<?php
/**
 * #/cycle — چرخهٔ من (نقشه کامل فازها).
 *
 * @package moonbanu
 * @var array $data
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$mb_snap = isset( $data['snap'] ) && is_array( $data['snap'] ) ? $data['snap'] : array();
$mb_idx  = null !== ( $mb_snap['day_index'] ?? null ) ? (int) $mb_snap['day_index'] : 0;
$mb_map  = isset( $data['map'] ) && is_array( $data['map'] ) ? $data['map'] : array();
$mb_var  = isset( $data['variation'] ) && is_array( $data['variation'] ) ? (int) ( $data['variation']['delta'] ?? 0 ) : 0;
?>
<div class="scr cycle">
	<div class="pad">
		<header class="hdr">
			<div>
				<h1 class="h1">چرخهٔ من</h1>
				<p class="small"><?php echo esc_html( (string) ( $mb_snap['jalali_full'] ?? '' ) ); ?></p>
			</div>
			<?php echo MB_UI::chip( 'نسخهٔ پیشرفته', 'gold', 'crown' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>
		<div class="center">
			<?php
			echo MB_UI::ring(
				158,
				(array) ( $data['segments'] ?? array() ),
				$mb_idx,
				array(
					'num'  => MB_UI::num( $mb_idx ),
					'sub'  => 'روز چرخه',
					'chip' => MB_UI::chip( (string) ( $mb_snap['phase_label'] ?? '—' ), (string) ( $mb_snap['phase'] ?? 'normal' ) ),
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>
		<div class="kpi-grid three">
			<div class="glass card kpi-card"><span class="small">طول چرخه</span><span class="kpi"><?php echo esc_html( MB_UI::num( (int) ( $mb_snap['cycle_len'] ?? 28 ) ) ); ?></span></div>
			<div class="glass card kpi-card"><span class="small">میانگین</span><span class="kpi"><?php echo esc_html( MB_UI::num( (int) ( $data['avg'] ?? 0 ) ) ); ?></span></div>
			<div class="glass card kpi-card"><span class="small">نوسان</span><span class="kpi">±<?php echo esc_html( MB_UI::num( $mb_var ) ); ?></span></div>
		</div>
		<section class="sec">
			<h2 class="h2">نقشهٔ کامل چرخه</h2>
			<?php if ( empty( $mb_map ) ) : ?>
				<div class="glass card gold-line">
					<p class="body">برای ساخت نقشهٔ فازها، اول تاریخ آخرین قاعدگی را ثبت کن.</p>
					<div class="btn-col">
						<?php echo MB_UI::btn( 'تنظیم چرخه', 'gold wide', 'goto', array( 'icon' => 'edit', 'data' => array( 'route' => 'cycle-editor' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>
			<?php else : ?>
				<div class="map-grid">
					<?php foreach ( $mb_map as $mb_span ) : ?>
						<div class="glass card map-card">
							<span class="map-dot <?php echo esc_attr( MB_Privacy::phase_color_key( (string) ( $mb_span['phase'] ?? 'normal' ) ) ); ?>"></span>
							<h3 class="h3"><?php echo esc_html( (string) ( $mb_span['label'] ?? '' ) ); ?></h3>
							<p class="small"><?php echo esc_html( MB_Jalali::range_fa( (string) ( $mb_span['from'] ?? '' ), (string) ( $mb_span['to'] ?? '' ) ) ); ?></p>
							<p class="small"><?php echo esc_html( MB_UI::num( (int) ( $mb_span['days'] ?? 0 ) ) . ' روز (روز ' . MB_UI::num( (int) ( $mb_span['from_idx'] ?? 0 ) ) . ' تا ' . MB_UI::num( (int) ( $mb_span['to_idx'] ?? 0 ) ) . ')' ); ?></p>
							<p class="body"><?php echo esc_html( (string) ( $mb_span['hormone'] ?? '' ) ); ?></p>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php echo MB_UI::pnote( 'تاریخ‌ها تخمینی‌اند؛ با ادامه ثبت روزانه دقیق‌تر می‌شوند.', 'warn', 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'analysis' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>