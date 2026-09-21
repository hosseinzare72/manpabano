<?php
/**
 * #/report — گزارش پزشک (M4، پیشرفته). CSS چاپ روشن و مجزا.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_r    = (array) ( $data['report'] ?? array() );
$mb_st   = (array) ( $mb_r['stats'] ?? array() );
$mb_irr  = (array) ( $mb_r['irregular'] ?? array() );
$mb_logs = (array) ( $mb_r['logs'] ?? array() );
?>
<div class="scr report" id="mb-report">
	<div class="pad">

		<header class="hdr no-print">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">گزارش پزشک</h1>
				<p class="small"><?php echo esc_html( (string) $mb_r['range_fa'] ); ?></p>
			</div>
			<?php echo MB_UI::icon( 'print', 24, 1.7, 'hdr-ic' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<div class="chips pickable no-print" id="mb-report-range">
			<?php foreach ( array( 3, 6, 12 ) as $mb_m ) : ?>
				<button type="button" class="chip pick<?php echo (int) $data['months'] === $mb_m ? ' on' : ''; ?>" data-mb="report-range" data-value="<?php echo (int) $mb_m; ?>">
					<?php echo esc_html( MB_UI::num( $mb_m ) . ' ماه' ); ?>
				</button>
			<?php endforeach; ?>
		</div>

		<div class="report-doc" id="mb-report-doc">

			<div class="rep-head">
				<h2 class="rep-title">گزارش سلامت چرخه — ماه‌بانو</h2>
				<p class="rep-meta">
					<?php echo esc_html( '' !== (string) $mb_r['name'] ? (string) $mb_r['name'] : 'کاربر ماه‌بانو' ); ?>
					· بازه: <?php echo esc_html( (string) $mb_r['range_fa'] ); ?>
					· تاریخ گزارش: <?php echo esc_html( (string) $mb_r['generated'] ); ?>
				</p>
				<p class="rep-disc"><?php echo esc_html( (string) $mb_r['disclaimer'] ); ?></p>
			</div>

			<?php if ( ! empty( $mb_r['pregnancy'] ) ) : ?>
				<p class="rep-note">حالت بارداری فعال — هفتهٔ <?php echo esc_html( MB_UI::num( (int) $mb_r['pregnancy']['week'] ) ); ?>، موعد تقریبی <?php echo esc_html( (string) $mb_r['pregnancy']['due_jalali'] ); ?>. پیش‌بینی چرخه در این بازه خاموش بوده است.</p>
			<?php endif; ?>

			<?php if ( ! empty( $mb_irr['irregular'] ) ) : ?>
				<p class="rep-note">چرخه نامنظم ارزیابی شده: انحراف معیار <?php echo esc_html( MB_UI::num( (string) $mb_irr['stdev'] ) ); ?> روز، <?php echo esc_html( MB_UI::num( (int) $mb_irr['outliers'] ) ); ?> چرخه خارج از بازهٔ ۲۱ تا ۳۵ روز.</p>
			<?php endif; ?>

			<section class="rep-sec">
				<h3 class="rep-h3">خلاصه</h3>
				<table class="rep-tbl">
					<tbody>
						<tr><th>تعداد روزهای ثبت‌شده</th><td><?php echo esc_html( MB_UI::num( (int) $mb_st['logged_days'] ) ); ?></td></tr>
						<tr><th>تعداد چرخه‌های ثبت‌شده</th><td><?php echo esc_html( MB_UI::num( (int) $mb_st['cycle_count'] ) ); ?></td></tr>
						<tr><th>میانگین طول چرخه</th><td><?php echo esc_html( MB_UI::num( (int) $mb_st['avg_cycle'] ) ); ?> روز</td></tr>
						<tr><th>میانگین طول قاعدگی</th><td><?php echo esc_html( MB_UI::num( (int) $mb_st['avg_period'] ) ); ?> روز</td></tr>
						<tr><th>میانگین شدت درد (۰ تا ۵)</th><td><?php echo esc_html( MB_UI::num( (string) $mb_st['avg_pain'] ) ); ?></td></tr>
						<tr><th>میانگین خواب</th><td><?php echo esc_html( $mb_st['avg_sleep'] > 0 ? MB_UI::num( (string) $mb_st['avg_sleep'] ) . ' ساعت' : '—' ); ?></td></tr>
						<tr><th>میانگین خلق (۱ تا ۵)</th><td><?php echo esc_html( $mb_st['avg_mood'] > 0 ? MB_UI::num( (string) $mb_st['avg_mood'] ) : '—' ); ?></td></tr>
					</tbody>
				</table>
			</section>

			<section class="rep-sec">
				<h3 class="rep-h3">چرخه‌ها</h3>
				<?php if ( empty( $mb_r['cycles'] ) ) : ?>
					<p class="rep-empty">چرخه‌ای ثبت نشده است.</p>
				<?php else : ?>
					<table class="rep-tbl">
						<thead><tr><th>شروع (شمسی)</th><th>طول چرخه</th><th>طول قاعدگی</th></tr></thead>
						<tbody>
						<?php foreach ( (array) $mb_r['cycles'] as $mb_c ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $mb_c['start_fa'] ); ?></td>
								<td><?php echo esc_html( MB_UI::num( (int) $mb_c['length'] ) ); ?> روز</td>
								<td><?php echo esc_html( MB_UI::num( (int) $mb_c['period'] ) ); ?> روز</td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>

			<section class="rep-sec">
				<h3 class="rep-h3">فراوانی نشانه‌ها</h3>
				<?php if ( empty( $mb_r['symptoms'] ) ) : ?>
					<p class="rep-empty">نشانه‌ای ثبت نشده است.</p>
				<?php else : ?>
					<table class="rep-tbl">
						<thead><tr><th>نشانه</th><th>تعداد روز</th><th>درصد روزهای ثبت‌شده</th></tr></thead>
						<tbody>
						<?php foreach ( (array) $mb_r['symptoms'] as $mb_s ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $mb_s['label'] ); ?></td>
								<td><?php echo esc_html( MB_UI::num( (int) $mb_s['times'] ) ); ?></td>
								<td><?php echo esc_html( MB_UI::pct( (int) $mb_s['percent'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</section>

			<section class="rep-sec">
				<h3 class="rep-h3">ثبت‌های روزانه</h3>
				<?php if ( empty( $mb_logs ) ) : ?>
					<p class="rep-empty">ثبتی در این بازه وجود ندارد.</p>
				<?php else : ?>
					<table class="rep-tbl compact">
						<thead>
							<tr><th>تاریخ</th><th>خونریزی</th><th>درد</th><th>خلق</th><th>نشانه‌ها</th><th>دما</th><th>خواب</th></tr>
						</thead>
						<tbody>
						<?php foreach ( array_slice( $mb_logs, 0, 120 ) as $mb_l ) : ?>
							<tr>
								<td><?php echo esc_html( (string) $mb_l['date_fa'] ); ?></td>
								<td><?php echo esc_html( (string) $mb_l['bleeding'] ); ?></td>
								<td><?php echo esc_html( $mb_l['pain'] > 0 ? MB_UI::num( (int) $mb_l['pain'] ) : '—' ); ?></td>
								<td><?php echo esc_html( '' !== (string) $mb_l['mood'] ? MB_UI::num( (string) $mb_l['mood'] ) : '—' ); ?></td>
								<td><?php echo esc_html( empty( $mb_l['symptoms'] ) ? '—' : implode( '، ', (array) $mb_l['symptoms'] ) ); ?></td>
								<td><?php echo esc_html( '' !== (string) $mb_l['bbt'] ? MB_UI::num( (string) $mb_l['bbt'] ) . '°' : '—' ); ?></td>
								<td><?php echo esc_html( '' !== (string) $mb_l['sleep'] ? MB_UI::num( (string) $mb_l['sleep'] ) : '—' ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
					<?php if ( count( $mb_logs ) > 120 ) : ?>
						<p class="rep-empty">۱۲۰ ثبت تازه‌تر نشان داده شد؛ فهرست کامل در خروجی CSV است.</p>
					<?php endif; ?>
				<?php endif; ?>
			</section>

			<p class="rep-foot"><?php echo esc_html( (string) $mb_r['disclaimer'] ); ?></p>
		</div>

		<div class="btn-row no-print">
			<?php
			echo MB_UI::btn( 'چاپ / ذخیرهٔ PDF', 'gold', 'report-print', array( 'icon' => 'print' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			echo MB_UI::btn( 'دانلود CSV', 'ghost', '', array( 'icon' => 'copy', 'href' => (string) $data['csv'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
		</div>

		<?php echo MB_UI::pnote( 'این گزارش فقط از دادهٔ حساب خودت ساخته می‌شود و روی سرور برای کسی دیگر قابل درخواست نیست.', 'good', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'report' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
