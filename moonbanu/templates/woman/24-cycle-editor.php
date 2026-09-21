<?php
/**
 * #/cycle-editor — ویرایشگر چرخه (N6/B2): یک سوییچ تمیز، بدون کنترل تکراری.
 *
 * @package moonbanu
 * @var array $data
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$mb_cycle  = isset( $data['cycle'] ) ? (array) $data['cycle'] : array();
$mb_starts = isset( $data['starts_history'] ) ? (array) $data['starts_history'] : array();
$mb_last   = (string) ( $data['last_period_jalali'] ?? '' );
$mb_irr    = ! empty( $mb_cycle['is_irregular'] );
?>
<style>
.mb-switch{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 0;}
.mb-switch .sw-txt{flex:1;}
.mb-switch .sw-txt b{display:block;font-size:15px;margin-bottom:4px;}
.mb-switch .sw-txt span{font-size:12px;color:var(--muted);line-height:1.8;}
.mb-switch input{position:absolute;opacity:0;width:46px;height:26px;margin:0;cursor:pointer;}
.mb-switch .track{position:relative;width:46px;height:26px;border-radius:13px;background:var(--surface);border:1px solid var(--line);transition:.2s;flex:none;}
.mb-switch .track::after{content:"";position:absolute;top:3px;left:3px;width:18px;height:18px;border-radius:50%;background:var(--muted);transition:.2s;}
.mb-switch input:checked + .track{background:rgba(217,180,91,.25);border-color:var(--gold);}
.mb-switch input:checked + .track::after{left:23px;background:var(--gold);}
.mb-switch input:focus-visible + .track{outline:2px solid var(--gold);outline-offset:2px;}
</style>
<div class="scr cycle-editor">
	<div class="pad">
		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<h1 class="h1">تنظیم چرخه</h1>
		</header>
		<form id="cycle-form" class="glass card" onsubmit="return false;">
			<div class="mb-form">
				<label class="fld">
					<span>طول چرخه (روز)</span>
					<input type="number" id="cycle-len" inputmode="numeric" min="21" max="35" value="<?php echo esc_attr( (string) (int) ( $mb_cycle['length'] ?? 28 ) ); ?>">
					<span class="small">نرمال: ۲۱ تا ۳۵ روز</span>
				</label>
				<label class="fld">
					<span>مدت قاعدگی (روز)</span>
					<input type="number" id="cycle-period-len" inputmode="numeric" min="2" max="7" value="<?php echo esc_attr( (string) (int) ( $mb_cycle['period_length'] ?? 5 ) ); ?>">
					<span class="small">نرمال: ۲ تا ۷ روز</span>
				</label>
				<label class="fld">
					<span>آغاز آخرین قاعدگی</span>
					<input type="text" id="cycle-last" dir="ltr" value="<?php echo esc_attr( $mb_last ); ?>" placeholder="1405/06/07">
					<span class="small">تاریخ شمسی به شکل سال/ماه/روز. پیش‌بینی بلافاصله بازمحاسبه می‌شود.</span>
				</label>
				<label class="mb-switch">
					<span class="sw-txt">
						<b>حالت نامنظم</b>
						<span>اگر طول چرخه‌هایت خیلی متفاوت است، این حالت را روشن کن؛ پیش‌بینی‌ها به‌صورت بازه نمایش داده می‌شوند و دقیق‌ترند.</span>
					</span>
					<input type="checkbox" id="cycle-irregular" <?php checked( $mb_irr ); ?>>
					<span class="track" aria-hidden="true"></span>
				</label>
				<div class="btn-col">
					<?php echo MB_UI::btn( 'ذخیره', 'gold wide', 'cycle-save', array( 'icon' => 'check' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
		</form>
		<section class="sec">
			<h2 class="h2">تاریخچهٔ آغازها</h2>
			<?php if ( empty( $mb_starts ) ) : ?>
				<?php echo MB_UI::pnote( 'هنوز تاریخچه‌ای ثبت نشده؛ با ثبت هر قاعدگی جدید، این فهرست پر می‌شود.', 'info', 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php else : ?>
				<div class="glass card list">
					<?php foreach ( $mb_starts as $mb_s ) : ?>
						<?php echo MB_UI::lrow( MB_Jalali::format_fa( (string) ( $mb_s['start_date'] ?? '' ), 'long' ), 'آغاز چرخه', 'cal', array( 'right' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php echo MB_UI::pnote( 'پس از ذخیره، خانه و تقویم بلافاصله با اعداد تازه باز می‌شوند.', 'info', 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>