<?php
/**
 * #/calm — چک‌لیست و راهنمای رفتاری همسر.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_tasks = (array) $data['tasks'];
$mb_state = (array) $data['state'];
$mb_done  = count( array_filter( (array) ( $mb_state['checklist'] ?? array() ) ) );
$mb_name  = (string) ( $data['payload']['wife_name'] ?? '' );
?>
<div class="scr calm" data-theme="partner">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">۵ کار مؤثر امشب</h1>
				<p class="small">ساده، عملی، بی‌جزئیات خصوصی</p>
			</div>
			<?php echo '' !== $mb_name ? MB_UI::chip( $mb_name, 'dark', 'heart' ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<section class="glass card">
			<div class="calm-head">
				<h2 class="h2">چک‌لیست امشب</h2>
				<?php echo MB_UI::chip( MB_UI::num( $mb_done ) . ' از ' . MB_UI::num( count( $mb_tasks ) ) . ' انجام شد', 'dark', 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
			<div class="calm-grid">
				<?php foreach ( $mb_tasks as $mb_key => $mb_task ) : ?>
					<?php $mb_on = ! empty( $mb_state['checklist'][ $mb_key ] ); ?>
					<button type="button" class="sym calm-tile<?php echo $mb_on ? ' on' : ''; ?>" data-mb="partner-toggle" data-key="<?php echo esc_attr( $mb_key ); ?>" aria-pressed="<?php echo $mb_on ? 'true' : 'false'; ?>">
						<?php echo MB_UI::icon( (string) $mb_task['icon'], 16, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span><?php echo esc_html( (string) $mb_task['label'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="glass card">
			<h2 class="h2">چه بگویی</h2>
			<ul class="bullets good">
				<li><?php echo MB_UI::icon( 'check', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>«امشب هر کاری لازم داری من هستم.»</span></li>
				<li><?php echo MB_UI::icon( 'check', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>«حق داری خسته باشی؛ لازم نیست توضیح بدهی.»</span></li>
				<li><?php echo MB_UI::icon( 'check', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>«شام و بچه‌ها با من، تو استراحت کن.»</span></li>
			</ul>
		</section>

		<section class="glass card">
			<h2 class="h2">چه چیزهایی نگو</h2>
			<ul class="bullets bad">
				<li><?php echo MB_UI::icon( 'alert', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>«بازم که پریودی…»</span></li>
				<li><?php echo MB_UI::icon( 'alert', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>«اینقدر حساس نباش.»</span></li>
				<li><?php echo MB_UI::icon( 'alert', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>«بقیه هم دارند، مشکلی نیست.»</span></li>
			</ul>
		</section>

		<section class="sec">
			<h2 class="h2">کمک عملی این هفته</h2>
			<div class="chips">
				<?php
				echo MB_UI::chip( 'خرید خانه', 'dark', 'cup' ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::chip( 'رساندن به مطب', 'dark', 'cal' ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::chip( 'یک شب مرخصی از کارهای خانه', 'dark', 'moon' ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::chip( 'برنامه سبک آخر هفته', 'dark', 'spark' ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
		</section>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'calm', 'partner' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
