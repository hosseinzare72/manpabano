<?php
/**
 * #/home — صفحهٔ خانه (زن).
 *
 * @package moonbanu
 * @var array $data
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$mb_today      = isset( $data['today'] ) && is_array( $data['today'] ) ? $data['today'] : array();
$mb_cycle      = isset( $data['cycle'] ) && is_array( $data['cycle'] ) ? $data['cycle'] : array();
$mb_has_anchor = null !== ( $mb_today['day_index'] ?? null );
$mb_phase      = (string) ( $mb_today['phase'] ?? 'normal' );
// نگهبان کلید care: اگر کنترل‌کننده نفرستاد، از موتور چرخه ساخته می‌شود (بدون warning).
$mb_care       = ( isset( $data['care'] ) && is_array( $data['care'] ) && ! empty( $data['care'] ) )
	? $data['care']
	: MB_Cycle_Engine::day_care( $mb_phase );
// B4 — «سلام [نام کوچک]»؛ اگر نامی نبود، فقط «سلام».
$mb_name  = trim( (string) ( $data['user_name'] ?? $data['name'] ?? '' ) );
$mb_hello = '' !== $mb_name ? 'سلام ' . $mb_name : 'سلام';
?>
<div class="scr home">
	<div class="pad">
		<header class="hdr">
			<h1 class="h1"><?php echo esc_html( $mb_hello ); ?></h1>
			<?php echo MB_UI::bell( (int) ( $data['unread'] ?? 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>
		<?php if ( ! $mb_has_anchor ) : ?>
			<!-- بدون لنگر چرخه: به‌جای اعداد جعلی، کارت تنظیم چرخه -->
			<section class="glass card gold-line">
				<h2 class="h2">چرخه‌ات را تنظیم کن</h2>
				<p class="body">برای دیدن وضعیت امروز، پیش‌بینی قاعدگی و مراقبت‌های روزانه، اول تاریخ آخرین قاعدگی را ثبت کن.</p>
				<div class="btn-col">
					<?php echo MB_UI::btn( 'تنظیم چرخه', 'gold wide', 'goto', array( 'icon' => 'edit', 'data' => array( 'route' => 'cycle-editor' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>
		<?php else : ?>
			<!-- Today Status (Ring + Hormone) -->
			<section class="glass card hero gold-line">
				<div class="hero-ring">
					<?php
					$mb_idx = (int) $mb_today['day_index'];
					echo MB_UI::ring( 132, (array) ( $data['segments'] ?? array() ), $mb_idx, array( 'num' => MB_UI::num( $mb_idx ), 'sub' => 'روز چرخه' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>
				<div>
					<h2 class="h2"><?php echo esc_html( (string) ( $mb_today['phase_label'] ?? 'آغاز' ) ); ?></h2>
					<p class="body"><?php echo esc_html( (string) ( $mb_today['hormone'] ?? 'تعادل هورمونی' ) ); ?></p>
				</div>
			</section>
			<!-- Quick Stats -->
			<div class="chips">
				<?php
				$mb_days_left = $mb_today['days_left'] ?? null;
				if ( null !== $mb_days_left ) {
					echo MB_UI::chip( 'تا قاعدگی: ' . MB_UI::num( max( 0, (int) $mb_days_left ) ) . ' روز', 'dark', 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				echo MB_UI::chip( 'احتمال بارداری: ' . MB_UI::pct( (int) ( $mb_today['pregnancy'] ?? 0 ) ), 'dark', 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
		<?php endif; ?>
		<?php /* اشتراک‌گذاری نیاز به حمایت با همسر؛ پیش از این can_support محاسبه می‌شد ولی هیچ دکمه‌ای نداشت. */ ?>
		<?php if ( ! empty( $data['can_support'] ) ) : ?>
			<section class="glass card">
				<h2 class="h2">امروز به همراهی نیاز داری؟</h2>
				<p class="small">یک پیام کوتاه برای همسرت می‌رود، بدون هیچ جزئیات پزشکی یا نشانه‌ای.</p>
				<div class="btn-col">
					<?php echo MB_UI::btn( 'به همسرم خبر بده', 'ghost wide', 'support-send', array( 'icon' => 'heart' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>
		<?php endif; ?>

		<!-- SOS Button (N4) - Red Framed -->
		<div class="btn-col sos-section">
			<?php echo MB_UI::btn( 'اضطراری (SOS)', 'sos wide', 'sos', array( 'icon' => 'alert' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<!-- Care Today -->
		<?php if ( ! empty( $mb_care ) ) : ?>
			<section class="sec">
				<h2 class="h2">مراقبت امروز</h2>
				<div class="glass card list">
					<?php foreach ( $mb_care as $mb_c ) : ?>
						<?php echo MB_UI::lrow( (string) ( $mb_c['title'] ?? '' ), (string) ( $mb_c['text'] ?? '' ), (string) ( $mb_c['icon'] ?? 'spark' ), array( 'right' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
		<!-- Quick Navigation -->
		<section class="sec">
			<div class="chips">
				<?php echo MB_UI::btn( 'تقویم', 'ghost norm', 'goto', array( 'data' => array( 'route' => 'calendar' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo MB_UI::btn( 'ثبت', 'ghost norm', 'goto', array( 'data' => array( 'route' => 'log/' . date( 'Y-m-d' ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo MB_UI::btn( 'تحلیل', 'ghost norm', 'goto', array( 'data' => array( 'route' => 'analysis' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</section>
		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>