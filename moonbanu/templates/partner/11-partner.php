<?php
/**
 * #/partner — داشبورد شریک (N5 share_status).
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_snap = isset( $data['snap'] ) ? (array) $data['snap'] : array();
$mb_today = isset( $data['today_status'] ) ? (array) $data['today_status'] : array();
?>
<div class="scr partner">
	<div class="pad">

		<header class="hdr">
			<?php
			$mb_pname  = trim( (string) ( $data['user_name'] ?? $data['name'] ?? '' ) );
			$mb_phello = '' !== $mb_pname ? 'سلام ' . $mb_pname : 'سلام';
			?>
			<h1 class="h1"><?php echo esc_html( $mb_phello ); ?></h1>
			<button type="button" class="ic n" aria-label="منو"><?php echo MB_UI::icon( 'menu', 22, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</header>

		<!-- Today Status Card (N5) - Energy/Support Bands Only -->
		<?php if ( isset( $data['share_status'] ) && $data['share_status'] && ! empty( $mb_today ) ) : ?>
			<section class="glass card today-status">
				<h2 class="h2">وضعیت امروز</h2>
				<div class="status-bands">
					<!-- Energy Band -->
					<div class="band-group">
						<span class="label">انرژی</span>
						<span class="badge" data-band="<?php echo esc_attr( $mb_today['energy_band'] ?? 'low' ); ?>">
							<?php echo esc_html( $mb_today['energy_label'] ?? 'کم' ); ?>
						</span>
					</div>
					<!-- Support Need Band -->
					<div class="band-group">
						<span class="label">نیاز حمایت</span>
						<span class="badge" data-band="<?php echo esc_attr( $mb_today['support_band'] ?? 'none' ); ?>">
							<?php echo esc_html( $mb_today['support_label'] ?? 'ندارد' ); ?>
						</span>
					</div>
					<!-- Phase Color (Informational) -->
					<?php if ( isset( $mb_today['phase_color'] ) ) : ?>
						<div class="band-group">
							<span class="label">فاز</span>
							<span class="badge" style="background-color: <?php echo esc_attr( $mb_today['phase_color'] ); ?>;">
								<?php echo esc_html( $mb_snap['phase_label'] ?? '—' ); ?>
							</span>
						</div>
					<?php endif; ?>
				</div>
				<p class="small">بدون اطلاعات پزشکی</p>
			</section>
		<?php else : ?>
			<section class="glass card">
				<?php echo MB_UI::pnote( 'اشتراک وضعیت توسط شریک فعال نشده‌است.', 'info', 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</section>
		<?php endif; ?>

		<!-- Acknowledgment History (Existing 7-day bar is optional here) -->
		<?php if ( isset( $data['ack_history'] ) && ! empty( $data['ack_history'] ) ) : ?>
			<section class="glass card">
				<h3 class="h3">تاریخچهٔ تأیید</h3>
				<div class="ack-bar">
					<?php foreach ( $data['ack_history'] as $mb_a ) : ?>
						<div class="ack-item" title="<?php echo esc_attr( $mb_a['date'] ); ?>">
							<?php echo isset( $mb_a['acked'] ) && $mb_a['acked'] ? '✓' : '—'; ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- Navigation -->
		<section class="sec">
			<div class="chips">
				<?php echo MB_UI::btn( 'ارتباط', 'ghost norm', 'goto', array( 'data' => array( 'route' => 'connect' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo MB_UI::btn( 'راهنمایی', 'ghost norm', 'goto', array( 'data' => array( 'route' => 'calm' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</section>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'partner' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
