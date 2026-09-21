<?php
/**
 * #/ttc — تلاش برای بارداری: دمای پایه و تأیید تخمک‌گذاری (M2، پیشرفته).
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_an    = (array) ( $data['analysis'] ?? array() );
$mb_pts   = (array) ( $mb_an['points'] ?? array() );
$mb_fert  = (array) ( $data['fertile'] ?? array() );
?>
<div class="scr ttc">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">تلاش برای بارداری</h1>
				<p class="small"><?php echo esc_html( (string) $data['today_fa'] ); ?></p>
			</div>
			<?php echo MB_UI::icon( 'temp', 24, 1.7, 'hdr-ic' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<section class="glass card">
			<h2 class="h2">دمای پایهٔ بدن</h2>
			<?php echo $data['chart']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<div class="legend">
				<span class="lg"><i class="dot rise"></i>روز صعود دما</span>
				<span class="lg"><i class="dot plain"></i>ثبت روزانه</span>
			</div>
			<p class="body"><?php echo esc_html( (string) $data['summary'] ); ?></p>
		</section>

		<section class="sec">
			<h2 class="h2">وضعیت این چرخه</h2>
			<div class="glass card">
				<?php
				if ( ! empty( $data['confirmed'] ) ) {
					echo MB_UI::lrow( 'تخمک‌گذاری تأییدشده', MB_Jalali::format_fa( (string) $data['confirmed'], 'long' ), 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput
				} else {
					echo MB_UI::lrow( 'تخمک‌گذاری تأییدشده', 'هنوز صعود دمایی ثبت نشده', 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				if ( ! empty( $mb_fert['from'] ) && ! empty( $mb_fert['to'] ) ) {
					echo MB_UI::lrow( 'پنجرهٔ باروری تخمینی', MB_Jalali::range_fa( (string) $mb_fert['from'], (string) $mb_fert['to'] ), 'spark' ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				echo MB_UI::lrow( 'روزهای دارای ثبت دما', MB_UI::num( (int) ( $mb_an['coverage'] ?? 0 ) ) . ' روز', 'chart' ); // phpcs:ignore WordPress.Security.EscapeOutput
				if ( null !== ( $mb_an['baseline'] ?? null ) ) {
					echo MB_UI::lrow( 'میانگین پایهٔ آخرین صعود', MB_UI::num( number_format( (float) $mb_an['baseline'], 1 ) ) . '°', 'temp' ); // phpcs:ignore WordPress.Security.EscapeOutput
				}
				?>
			</div>
		</section>

		<section class="sec">
			<h2 class="h2">آخرین ثبت‌ها</h2>
			<div class="glass card">
				<?php if ( empty( $mb_pts ) ) : ?>
					<p class="small">هنوز دمایی ثبت نشده. از صفحهٔ ثبت روزانه، بخش «تلاش برای بارداری» را پر کن.</p>
				<?php else : ?>
					<div class="tbl-wrap">
						<table class="mb-tbl">
							<thead><tr><th>تاریخ</th><th>دما</th><th>مخاط</th></tr></thead>
							<tbody>
							<?php foreach ( array_slice( array_reverse( $mb_pts ), 0, 10 ) as $mb_p ) : ?>
								<tr<?php echo ! empty( $mb_p['rise'] ) ? ' class="rise-row"' : ''; ?>>
									<td><?php echo esc_html( MB_Jalali::format_fa( (string) $mb_p['date'], 'long' ) ); ?></td>
									<td><?php echo esc_html( MB_UI::num( number_format( (float) $mb_p['bbt'], 1 ) ) ); ?>°</td>
									<td><?php echo esc_html( (string) ( $data['mucus_map'][ $mb_p['mucus'] ] ?? '—' ) ); ?></td>
								</tr>
							<?php endforeach; ?>
							</tbody>
						</table>
					</div>
				<?php endif; ?>
			</div>
		</section>

		<?php echo MB_UI::pnote( 'دما، مخاط و رابطهٔ جنسی از خصوصی‌ترین داده‌های تو هستند و هرگز، در هیچ حالتی، به صفحهٔ همسر نمی‌روند.', 'good', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php echo MB_UI::btn( 'ثبت دمای امروز', 'gold wide', 'goto', array( 'icon' => 'plus', 'data' => array( 'route' => 'log' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'ttc' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
