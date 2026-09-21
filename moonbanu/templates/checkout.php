<?php
/**
 * #/checkout — خرید و تمدید اشتراک.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_status = (array) $data['status'];
$mb_result = (string) ( $data['result'] ?? '' );
$mb_free   = array( 'ثبت روزانه نشانه‌ها', 'تقویم چرخه جاری', 'خانهٔ پایه', 'اعلان درون‌اپ' );
$mb_pro    = array( 'پیش‌بینی ۳ چرخه آینده', 'تحلیل و الگوی شخصی', 'نقشهٔ کامل چرخه', 'احتمال نشانه‌ها', 'همراهی همسر (۴ صفحه)', 'پیامک', 'مرکز سلامت کامل + پرسش از متخصص', 'ذخیرهٔ راهنماها', 'بج دقت' );
$mb_plans  = (array) ( $mb_status['plans'] ?? MB_Subscription::plans() );
$mb_selected = (int) ( $mb_status['term_months'] ?? 1 );
?>
<div class="scr checkout">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">نسخهٔ پیشرفته</h1>
				<p class="small">اشتراک ماهانه، بدون تمدید خودکار</p>
			</div>
			<?php echo MB_UI::chip( 'pro' === $mb_status['plan'] ? 'فعال' : 'رایگان', 'pro' === $mb_status['plan'] ? 'gold' : 'dark', 'crown' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<?php if ( 'ok' === $mb_result ) : ?>
			<?php echo MB_UI::pnote( 'پرداخت با موفقیت تأیید شد و اشتراک فعال است.', 'good', 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php elseif ( 'failed' === $mb_result ) : ?>
			<?php echo MB_UI::pnote( 'پرداخت کامل نشد. اگر مبلغی کسر شده باشد، تا ۷۲ ساعت به حساب برمی‌گردد.', 'warn', 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>

		<section class="glass card gold-card center price-card">
			<span class="small">مدت اشتراک</span>
			<div class="plan-choices" role="radiogroup" aria-label="انتخاب مدت اشتراک">
				<?php foreach ( $mb_plans as $mb_plan ) : ?>
					<?php $mb_on = (int) $mb_plan['months'] === ( $mb_selected ?: 1 ); ?>
					<button type="button" class="plan-choice<?php echo $mb_on ? ' on' : ''; ?>" data-mb="plan-select" data-plan="<?php echo (int) $mb_plan['months']; ?>" role="radio" aria-checked="<?php echo $mb_on ? 'true' : 'false'; ?>">
						<span><strong><?php echo esc_html( (string) $mb_plan['label'] ); ?></strong><small class="small"><?php echo esc_html( MB_UI::num( number_format_i18n( (int) $mb_plan['price'] ) ) ); ?> تومان</small></span><input type="radio" tabindex="-1"<?php checked( $mb_on ); ?> aria-hidden="true">
					</button>
				<?php endforeach; ?>
			</div>
			<span class="small">تمدید دستی، بدون تمدید خودکار</span>
			<?php if ( 'pro' === $mb_status['plan'] && '' !== $mb_status['expires_fa'] ) : ?>
				<p class="body">اشتراک فعلی تا <?php echo esc_html( (string) $mb_status['expires_fa'] ); ?> اعتبار دارد. تمدید به همین تاریخ اضافه می‌شود.</p>
			<?php endif; ?>

			<label class="fld">
				<span>کد تخفیف (اختیاری)</span>
				<span class="coupon-row">
					<input type="text" id="mb-coupon" dir="ltr" placeholder="MOON20" autocapitalize="characters" autocomplete="off">
					<?php echo MB_UI::btn( 'بررسی', 'ghost xs', 'coupon-apply', array( 'icon' => 'check' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</span>
			</label>
			<div class="pnote good" id="mb-coupon-result" hidden><span></span></div>

			<?php echo MB_UI::btn( 'pro' === $mb_status['plan'] ? 'تمدید اشتراک' : 'پرداخت و فعال‌سازی', 'gold wide', 'checkout', array( 'icon' => 'crown' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<p class="small">پرداخت از درگاه تنظیم‌شده انجام می‌شود. درگاه‌های ایرانی پرداخت خودکار دوره‌ای ندارند، بنابراین تمدید همیشه با تأیید خودت است.</p>
		</section>

		<div class="plan-compare">
			<div class="glass card">
				<h2 class="h2">رایگان</h2>
				<ul class="bullets good">
					<?php foreach ( $mb_free as $mb_f ) : ?>
						<li><?php echo MB_UI::icon( 'check', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $mb_f ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="glass card gold-line">
				<h2 class="h2 gold-text">پیشرفته</h2>
				<ul class="bullets good">
					<?php foreach ( $mb_pro as $mb_f ) : ?>
						<li><?php echo MB_UI::icon( 'check', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $mb_f ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>

		<?php echo MB_UI::pnote( 'پس از پایان اشتراک، سه روز مهلت ارفاق داری و بعد از آن همه داده‌هایت حفظ می‌شود؛ فقط امکانات پیشرفته خاموش می‌شود.', 'good', 'shield' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<section class="sec">
			<h2 class="h2">پرداخت کردی ولی فعال نشد؟</h2>
			<div class="glass card">
				<p class="body">اگر مبلغ از حسابت کم شده اما اشتراک فعال نیست، یک‌بار دکمهٔ زیر را بزن؛ پرداخت را مستقیم از درگاه استعلام می‌کنیم.</p>
				<?php echo MB_UI::btn( 'بررسی و بازیابی پرداخت', 'ghost wide', 'checkout-recover', array( 'icon' => 'shield' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php echo MB_UI::lrow( 'همچنان مشکل داری؟', 'ثبت درخواست پشتیبانی', 'msg', array( 'action' => 'goto', 'data' => array( 'route' => 'support' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</section>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo MB_UI::brand_footer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'me' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
