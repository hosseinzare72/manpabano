<?php
/**
 * #/community — انجمن ناشناس (M7). خواندن رایگان.
 *
 * هیچ شناسهٔ کاربری در این صفحه وجود ندارد؛ فقط برچسب ناشناس.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_items = (array) ( $data['items'] ?? array() );
?>
<div class="scr community">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">انجمن ناشناس</h1>
				<p class="small">پرسش‌های بانوان و پاسخ متخصص، بدون هیچ نامی</p>
			</div>
			<?php echo MB_UI::icon( 'people', 24, 1.7, 'hdr-ic' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<div class="chips pickable">
			<button type="button" class="chip pick<?php echo '' === (string) $data['cat'] ? ' on' : ''; ?>" data-mb="community-cat" data-value="">همه</button>
			<?php foreach ( (array) $data['cats'] as $mb_slug => $mb_label ) : ?>
				<button type="button" class="chip pick<?php echo (string) $data['cat'] === (string) $mb_slug ? ' on' : ''; ?>" data-mb="community-cat" data-value="<?php echo esc_attr( (string) $mb_slug ); ?>">
					<?php echo esc_html( (string) $mb_label ); ?>
				</button>
			<?php endforeach; ?>
		</div>

		<div id="mb-community-list">
			<?php if ( empty( $mb_items ) ) : ?>
				<div class="glass card center">
					<?php echo MB_UI::icon( 'people', 26, 1.7 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<h2 class="h2">هنوز پرسش منتشرشده‌ای نیست</h2>
					<p class="small">پرسش‌ها بعد از پاسخ متخصص و تأیید مدیر، به‌صورت ناشناس اینجا منتشر می‌شوند.</p>
				</div>
			<?php else : ?>
				<?php foreach ( $mb_items as $mb_item ) : ?>
					<article class="glass card qa">
						<div class="qa-top">
							<span class="anon"><?php echo MB_UI::icon( 'user', 12, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( (string) $mb_item['anon'] ); ?></span></span>
							<?php echo MB_UI::chip( (string) $mb_item['cat_label'], 'dark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
						<p class="qa-q"><?php echo esc_html( (string) $mb_item['question'] ); ?></p>
						<?php if ( '' !== (string) $mb_item['answer'] ) : ?>
							<div class="qa-a">
								<span class="qa-badge"><?php echo MB_UI::icon( 'shield', 12, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>پاسخ متخصص</span></span>
								<p><?php echo esc_html( (string) $mb_item['answer'] ); ?></p>
							</div>
						<?php endif; ?>
						<p class="tiny muted"><?php echo esc_html( (string) $mb_item['date_fa'] ); ?></p>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>

		<?php if ( ! empty( $data['can_ask'] ) ) : ?>
			<?php echo MB_UI::btn( 'پرسش از متخصص', 'gold wide', 'goto', array( 'icon' => 'send', 'data' => array( 'route' => 'health/ask' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>

		<?php echo MB_UI::pnote( 'در انتشار ناشناس هیچ نام، ایمیل، شماره یا شناسهٔ کاربری منتشر نمی‌شود.', 'good', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'community' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
