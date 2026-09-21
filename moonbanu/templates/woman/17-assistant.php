<?php
/**
 * #/assistant — دستیار ماه (M9، پیشرفته). قاعده‌مند، بدون هیچ تماس بیرونی.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_snap = (array) ( $data['snap'] ?? array() );
?>
<div class="scr assistant">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">دستیار ماه</h1>
				<p class="small"><?php echo esc_html( 'فاز فعلی: ' . (string) ( $mb_snap['phase_label'] ?? '' ) ); ?></p>
			</div>
			<?php echo MB_UI::icon( 'chat', 24, 1.7, 'hdr-ic' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<?php echo MB_UI::pnote( (string) $data['label'], 'warn', 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<div class="chat-log" id="mb-chat-log" aria-live="polite">
			<div class="bubble bot">
				<p>سلام. هر چه دربارهٔ چرخه، درد، خلق، خواب، پیشگیری، بارداری یا PMS می‌خواهی بپرس. پاسخ را از فاز فعلی و ثبت‌های خودت می‌سازم.</p>
			</div>
		</div>

		<div class="chips pickable" id="mb-chat-chips">
			<?php foreach ( (array) $data['topics'] as $mb_key => $mb_topic ) : ?>
				<button type="button" class="chip pick" data-mb="assistant-chip" data-value="<?php echo esc_attr( (string) $mb_topic['title'] ); ?>">
					<?php echo esc_html( (string) $mb_topic['title'] ); ?>
				</button>
			<?php endforeach; ?>
		</div>

		<div class="glass card chat-input">
			<label class="fld">
				<span>پرسشت را بنویس</span>
				<textarea id="mb-chat-q" rows="2" maxlength="400" placeholder="مثلاً: چرا این روزها انقدر بی‌حوصله‌ام؟"></textarea>
			</label>
			<?php echo MB_UI::btn( 'بپرس', 'gold wide', 'assistant-ask', array( 'icon' => 'send' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'assistant' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
