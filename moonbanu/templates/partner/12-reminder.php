<?php
/**
 * #/reminder — پیام یادآوری برای همسر (+ فهرست پیام‌ها در #/messages).
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_p     = (array) $data['payload'];
$mb_state = (array) $data['state'];
$mb_name  = (string) ( $mb_p['wife_name'] ?? '' );
?>
<div class="scr reminder" data-theme="partner">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">یادآوری این روزها</h1>
				<p class="small"><?php echo esc_html( (string) $data['today_fa'] ); ?></p>
			</div>
			<?php echo ! empty( $mb_p['pms_alert'] ) ? MB_UI::chip( 'PMS از فردا', 'pms', 'alert' ) : MB_UI::chip( 'روزهای معمول', 'dark', 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<section class="glass card gold-line msg-card">
			<h2 class="h2">پیام ماه‌بانو</h2>
			<p class="body">
				<?php
				echo esc_html(
					! empty( $mb_p['pms_alert'] )
						? sprintf( 'این روزها %s به آرامش و انرژی کمتر نیاز دارد. تغییر خلق طبیعی و هورمونی است، نه تصمیم و نه بی‌مهری. حضور آرام تو بیشتر از هر حرفی کمک می‌کند.', $mb_name )
						: sprintf( 'امروز روز خاصی در چرخه %s نیست؛ همین همراهی ساده و پیوسته بهترین کار است.', $mb_name )
				);
				?>
			</p>
			<div class="chips">
				<?php
				echo MB_UI::chip( 'امروز: ' . MB_Jalali::format_fa( (string) $data['today'], 'short' ), 'dark', 'cal' ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::chip( 'نیاز اصلی: آرامش', 'dark', 'moon' ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::chip( 'خواب بیشتر', 'dark', 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
			<div class="btn-row">
				<?php
				echo MB_UI::btn( empty( $mb_state['ack'] ) ? 'فهمیدم، حواسم هست' : 'ثبت شد', 'gold sm', 'partner-ack', array( 'icon' => 'check' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::btn( 'یادآوری فردا', 'ghost sm', 'partner-snooze', array( 'icon' => 'clock' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
			<?php if ( ! empty( $mb_state['snooze_until'] ) ) : ?>
				<p class="small">یادآوری بعدی: <?php echo esc_html( MB_Jalali::format_fa( (string) $mb_state['snooze_until'], 'long' ) ); ?></p>
			<?php endif; ?>
		</section>

		<div class="plan-grid two">
			<div class="glass card plan">
				<span class="plan-ic"><?php echo MB_UI::icon( 'moon', 16, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<h3 class="h3">آرام باش</h3>
				<p class="body">صدا و سرعت خودت را پایین بیاور، بحث‌های مهم را به چند روز بعد بسپار.</p>
			</div>
			<div class="glass card plan">
				<span class="plan-ic"><?php echo MB_UI::icon( 'shield', 16, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
				<h3 class="h3">کمک کن</h3>
				<p class="body">یک کار مشخص را کامل خودت بردار؛ پرسیدن «کاری هست؟» بار را کم نمی‌کند.</p>
			</div>
		</div>

		<?php if ( ! empty( $data['messages'] ) ) : ?>
			<section class="sec">
				<h2 class="h2">پیام‌ها</h2>
				<div class="glass card list">
					<?php foreach ( (array) $data['messages'] as $mb_m ) : ?>
						<?php
						echo MB_UI::lrow(
							(string) ( $mb_m['payload']['title'] ?? 'اعلان' ),
							(string) ( $mb_m['payload']['body'] ?? '' ),
							'bell',
							array( 'right' => '' )
						); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<section class="glass card">
			<h2 class="h2">حواسش باشد، ولی تبدیل به مراقب نشو</h2>
			<ul class="bullets">
				<li><?php echo MB_UI::icon( 'check', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>هر ناراحتی را به چرخه نسبت نده.</span></li>
				<li><?php echo MB_UI::icon( 'check', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>درباره جزئیات بدنش پرس‌وجو نکن؛ اگر خواست، خودش می‌گوید.</span></li>
				<li><?php echo MB_UI::icon( 'check', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>نقش پزشک نگیر؛ در نشانه‌های هشدار فقط پیشنهاد مراجعه بده.</span></li>
			</ul>
			<?php echo MB_UI::btn( '۵ کار مؤثر امشب', 'ghost wide', 'goto', array( 'data' => array( 'route' => 'calm' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</section>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'reminder', 'partner' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
