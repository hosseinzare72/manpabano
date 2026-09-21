<?php
/**
 * #/splash — صفحه برند.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_user_id  = (int) $data['user_id'];
$mb_segments = MB_UI::cycle_segments( $mb_user_id );
?>
<div class="scr splash" data-splash="1">
	<div class="pad center splash-in">
		<div class="splash-ring">
			<?php echo MB_UI::ring( 300, $mb_segments, 0, array( 'top' => 'ماه‌بانو', 'sub' => 'همراه چرخه تو' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>

		<h1 class="brand brand-xl gold-text"><?php echo esc_html( MB_Plugin::brand( 'name' ) ); ?></h1>
		<p class="tagline">تقویم چرخه، تحلیل شخصی و همراهی محترمانه</p>

		<div class="chips center-chips">
			<?php
			echo MB_UI::chip( 'حریم خصوصی کامل', 'dark', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo MB_UI::chip( 'نسخهٔ ' . MB_UI::num( MB_VERSION ), 'gold', 'spark' ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>

		<div class="splash-cta">
			<?php
			echo MB_UI::btn( $data['onboarded'] ? 'ورود به اپ' : 'شروع کنیم', 'gold wide', 'goto', array( 'data' => array( 'route' => $data['onboarded'] ? 'home' : 'onboarding' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo MB_UI::brand_footer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
</div>
