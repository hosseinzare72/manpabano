<?php
/**
 * #/pregnancy — حالت بارداری (M1، رایگان).
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_preg  = $data['pregnancy'] ?? null;
$mb_list  = (array) ( $data['checklist'] ?? array() );
$mb_tri   = array( 1 => 'سه‌ماههٔ اول', 2 => 'سه‌ماههٔ دوم', 3 => 'سه‌ماههٔ سوم' );
?>
<div class="scr pregnancy">
	<div class="pad">

		<header class="hdr">
			<div class="day-head">
				<h1 class="h1 gold-text">حالت بارداری</h1>
				<p class="small"><?php echo esc_html( (string) $data['today_fa'] ); ?></p>
			</div>
			<?php echo MB_UI::icon( 'baby', 26, 1.7, 'hdr-ic' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<?php if ( ! $mb_preg ) : ?>

			<section class="glass card">
				<h2 class="h2">تاریخ اولین روز آخرین قاعدگی</h2>
				<p class="small">تاریخ تقریبی زایمان با قاعدهٔ ناگل حساب می‌شود: اولین روز آخرین قاعدگی + ۲۸۰ روز.</p>
				<div class="jdate-row">
					<label class="fld"><span>سال</span><input type="text" id="mb-preg-jy" inputmode="numeric" maxlength="4" placeholder="۱۴۰۵"></label>
					<label class="fld"><span>ماه</span><input type="text" id="mb-preg-jm" inputmode="numeric" maxlength="2" placeholder="۶"></label>
					<label class="fld"><span>روز</span><input type="text" id="mb-preg-jd" inputmode="numeric" maxlength="2" placeholder="۷"></label>
				</div>
				<?php echo MB_UI::pnote( 'با فعال‌شدن حالت بارداری، پیش‌بینی چرخه و یادآورهای قاعدگی خاموش می‌شوند. هر زمان بخواهی می‌توانی برگردی.', 'warn', 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo MB_UI::btn( 'فعال‌سازی حالت بارداری', 'gold wide', 'preg-start', array( 'icon' => 'baby' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</section>

			<section class="glass card">
				<h2 class="h2">این حالت چه چیزی به تو می‌دهد؟</h2>
				<ul class="pro-bullets">
					<li><?php echo MB_UI::icon( 'check', 14, 2.4 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>هفتهٔ جاری، تری‌مستر و شمارش معکوس تا موعد</span></li>
					<li><?php echo MB_UI::icon( 'check', 14, 2.4 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>کارت هفته: اندازهٔ تقریبی جنین و تغییرات بدن</span></li>
					<li><?php echo MB_UI::icon( 'check', 14, 2.4 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>چک‌لیست مراقبت‌ها و نشانه‌های هشدار هر هفته</span></li>
				</ul>
			</section>

		<?php else : ?>

			<?php echo MB_UI::pnote( 'حالت بارداری فعال است؛ پیش‌بینی چرخه خاموش شده.', 'good', 'baby' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

			<section class="glass card center preg-hero">
				<?php
				echo MB_UI::progress_ring(
					148,
					(int) $mb_preg['progress'],
					'هفتهٔ ' . MB_UI::num( (int) $mb_preg['week'] ),
					(string) ( $mb_tri[ (int) $mb_preg['trimester'] ] ?? '' ),
					MB_UI::num( (int) $mb_preg['progress'] ) . '٪'
				); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
				<div class="chips center-chips">
					<?php
					echo MB_UI::chip( 'روز ' . MB_UI::num( (int) $mb_preg['day_of_week'] ) . ' هفته', 'default', 'cal' ); // phpcs:ignore WordPress.Security.EscapeOutput
					if ( (int) $mb_preg['days_left'] >= 0 ) {
						echo MB_UI::chip( MB_UI::num( (int) $mb_preg['days_left'] ) . ' روز تا موعد', 'gold', 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput
					} else {
						echo MB_UI::chip( 'از موعد گذشته', 'pms', 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput
					}
					?>
				</div>
				<p class="small">تاریخ تقریبی زایمان: <strong><?php echo esc_html( (string) $mb_preg['due_jalali'] ); ?></strong></p>
				<p class="tiny muted">اولین روز آخرین قاعدگی: <?php echo esc_html( (string) $mb_preg['lmp_jalali'] ); ?></p>
			</section>

			<section class="sec">
				<h2 class="h2">کارت هفتهٔ <?php echo esc_html( MB_UI::num( (int) $mb_preg['week'] ) ); ?></h2>
				<div class="glass card">
					<?php
					echo MB_UI::lrow( 'اندازهٔ تقریبی جنین', (string) $mb_preg['card']['size'], 'baby' ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo MB_UI::lrow( 'تغییرات بدن تو', (string) $mb_preg['card']['body'], 'heart' ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
					<?php echo MB_UI::pnote( (string) $mb_preg['card']['warn'], 'warn', 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>

			<section class="sec">
				<h2 class="h2">چک‌لیست مراقبت‌ها</h2>
				<div class="glass card">
					<?php foreach ( $mb_list as $mb_key => $mb_item ) : ?>
						<button type="button" class="check-row<?php echo ! empty( $mb_item['done'] ) ? ' on' : ''; ?>"
							data-mb="preg-check" data-key="<?php echo esc_attr( (string) $mb_key ); ?>"
							aria-pressed="<?php echo ! empty( $mb_item['done'] ) ? 'true' : 'false'; ?>">
							<span class="cbox"><?php echo MB_UI::icon( 'check', 13, 2.6 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
							<span class="cr-t"><?php echo esc_html( (string) $mb_item['label'] ); ?></span>
							<span class="cr-w">هفتهٔ <?php echo esc_html( MB_UI::num( (int) $mb_item['week'] ) ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			</section>

			<?php if ( ! empty( $data['has_partner'] ) && ! empty( $data['can_partner'] ) ) : ?>
				<section class="sec">
					<h2 class="h2">اشتراک با همسر</h2>
					<?php
					echo MB_UI::toggle(
						'نمایش بارداری به همسر',
						'فقط شمارهٔ هفته و تاریخ موعد. هیچ نشانه، خلق، درد یا یادداشتی فرستاده نمی‌شود.',
						1 === (int) $mb_preg['share'],
						'preg-share'
					); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</section>
			<?php endif; ?>

			<section class="sec">
				<?php echo MB_UI::btn( 'خاموش‌کردن حالت بارداری', 'ghost wide', 'preg-stop' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</section>

		<?php endif; ?>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo MB_UI::brand_footer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'pregnancy' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
