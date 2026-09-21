<?php
/**
 * #/about — دربارهٔ اپ، سازنده و راه‌های تماس.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_dev_url = MB_Plugin::brand( 'dev_url' );
$mb_studio  = MB_Plugin::brand( 'studio' );
?>
<div class="scr about">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">درباره</h1>
				<p class="small">نسخهٔ <?php echo esc_html( MB_UI::num( MB_VERSION ) ); ?></p>
			</div>
		</header>

		<section class="glass card center">
			<div class="brand gold-text"><?php echo esc_html( MB_Plugin::brand( 'name' ) ); ?></div>
			<p class="body">تقویم چرخه، تحلیل شخصی و همراهی محترمانه — کاملاً فارسی، با تقویم شمسی.</p>
		</section>

		<section class="sec">
			<h2 class="h2">تماس و پشتیبانی</h2>
			<div class="glass card">
				<?php echo MB_UI::support_contact(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo MB_UI::lrow( 'ثبت درخواست پشتیبانی', 'پاسخ را داخل همین اپ ببین', 'msg', array( 'action' => 'goto', 'data' => array( 'route' => 'support' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</section>

		<section class="sec">
			<h2 class="h2">سازنده</h2>
			<div class="glass card">
				<div class="dev-card">
					<div class="dev-ic"><?php echo MB_UI::icon( 'spark', 22, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
					<div class="dev-body">
						<span class="h2"><?php echo esc_html( MB_Plugin::brand( 'credit' ) ); ?></span>
						<?php if ( '' !== $mb_studio ) : ?>
							<span class="small"><?php echo esc_html( $mb_studio ); ?></span>
						<?php endif; ?>
					</div>
				</div>
				<?php if ( '' !== $mb_dev_url ) : ?>
					<?php echo MB_UI::btn( 'دیگر برنامه‌های ما', 'gold wide', '', array( 'icon' => 'next', 'href' => $mb_dev_url ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<p class="small center" dir="ltr"><?php echo esc_html( $mb_dev_url ); ?></p>
				<?php endif; ?>
			</div>
		</section>

		<section class="sec">
			<h2 class="h2">حریم خصوصی</h2>
			<div class="glass card">
				<?php echo MB_UI::pnote( 'یادداشت‌های خصوصی و نشانه‌ها رمزنگاری‌شده روی سرور خودت ذخیره می‌شود و برای هیچ سرویس بیرونی فرستاده نمی‌شود.', 'good', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php
				$mb_privacy = (string) MB_Plugin::setting( 'privacy_url', '' );
				$mb_terms   = (string) MB_Plugin::setting( 'terms_url', '' );
				if ( '' !== $mb_privacy ) {
					echo MB_UI::lrow( 'سیاست حریم خصوصی', '', 'shield', array( 'right' => '', 'data' => array() ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo '<p class="small center"><a href="' . esc_url( $mb_privacy ) . '" target="_blank" rel="noopener">' . esc_html( $mb_privacy ) . '</a></p>';
				}
				if ( '' !== $mb_terms ) {
					echo '<p class="small center"><a href="' . esc_url( $mb_terms ) . '" target="_blank" rel="noopener">قوانین استفاده</a></p>';
				}
				?>
			</div>
		</section>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo MB_UI::brand_footer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'me' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
