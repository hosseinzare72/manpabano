<?php
/**
 * #/calendar — تقویم شمسی ماه.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_snap   = (array) $data['snap'];
$mb_cells  = (array) $data['cells'];
$mb_offset = (int) $data['offset'];
$mb_legend = array(
	'period'     => 'قاعدگی',
	'foll'       => 'فولیکولار',
	'fertile'    => 'باروری',
	'ovul'       => 'تخمک‌گذاری',
	'luteal'     => 'لوتئال',
	'pms'        => 'پیش‌از‌قاعدگی',
	'norm'       => 'معمول',
);
?>
<div class="scr cal">
	<div class="pad">

		<header class="hdr">
			<div class="cal-nav">
				<button type="button" class="ic n" data-mb="goto" data-route="calendar/<?php echo esc_attr( (string) $data['next_m'] ); ?>" aria-label="ماه بعد"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<h1 class="h1"><?php echo esc_html( (string) $data['title'] ); ?></h1>
				<button type="button" class="ic n" data-mb="goto" data-route="calendar/<?php echo esc_attr( (string) $data['prev'] ); ?>" aria-label="ماه قبل"><?php echo MB_UI::icon( 'back', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			</div>
			<button type="button" class="ic n" data-mb="cal-search" aria-label="جست‌وجو"><?php echo MB_UI::icon( 'search', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</header>

		<div class="cal-search" id="mb-cal-search" hidden>
			<input type="text" inputmode="numeric" dir="ltr" id="mb-cal-jump" placeholder="۱۴۰۵/۰۶/۲۷">
			<button type="button" class="btn dark sm" data-mb="cal-jump">برو به روز</button>
		</div>

		<div class="glass card cal-card">
			<div class="cal-week">
				<?php foreach ( MB_Jalali::week_days_short() as $mb_w ) : ?>
					<span class="cal-wd"><?php echo esc_html( $mb_w ); ?></span>
				<?php endforeach; ?>
			</div>
			<div class="cal-grid">
				<?php for ( $mb_o = 0; $mb_o < $mb_offset; $mb_o++ ) : ?>
					<span class="cell dim" aria-hidden="true"></span>
				<?php endfor; ?>

				<?php
				foreach ( $mb_cells as $mb_cell ) :
					$mb_cls = 'cell ' . MB_Privacy::phase_color_key( (string) $mb_cell['phase'] );
					if ( ! empty( $mb_cell['today'] ) ) {
						$mb_cls .= ' today';
					}
					if ( ! empty( $mb_cell['predicted'] ) ) {
						$mb_cls .= ' pred';
					}
					?>
					<button type="button" class="<?php echo esc_attr( $mb_cls ); ?>" data-mb="goto" data-route="day/<?php echo esc_attr( (string) $mb_cell['date'] ); ?>">
						<span class="cell-num"><?php echo esc_html( MB_UI::num( (int) $mb_cell['day'] ) ); ?></span>
						<i class="cell-bar"></i>
						<?php if ( ! empty( $mb_cell['logged'] ) ) : ?><i class="cell-dot"></i><?php endif; ?>
					</button>
				<?php endforeach; ?>
			</div>
		</div>

		<div class="legend">
			<?php foreach ( $mb_legend as $mb_key => $mb_label ) : ?>
				<span class="lg"><i class="lg-dot <?php echo esc_attr( $mb_key ); ?>"></i><?php echo esc_html( $mb_label ); ?></span>
			<?php endforeach; ?>
		</div>

		<section class="glass card next-card gold-line">
			<div>
				<h2 class="h2">پریود بعدی</h2>
				<p class="body"><?php echo esc_html( $mb_snap['next_period'] ? MB_Jalali::format_fa( (string) $mb_snap['next_period'], 'full' ) : 'برای پیش‌بینی، تاریخ آخرین قاعدگی را ثبت کن' ); ?></p>
			</div>
			<?php if ( null !== $mb_snap['days_left'] ) : ?>
				<?php echo MB_UI::chip( MB_UI::num( max( 0, (int) $mb_snap['days_left'] ) ) . ' روز دیگر', 'gold', 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		</section>

		<?php echo MB_UI::pnote( ( (int) ( $mb_snap['cycle_count'] ?? 0 ) < 2 ) ? 'الگوی تو در حال یادگیری است؛ با ثبت شروع قاعدگی‌ها پیش‌بینی‌ها شخصی‌تر می‌شوند.' : 'پیش‌بینی‌ها بر پایه الگوی ثبت‌شدهٔ تو ساخته شده‌اند.', 'good', 'spark' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

		<section class="sec">
			<h2 class="h2">پیش‌بینی ۳ ماه آینده</h2>
			<?php if ( empty( $data['can_predict'] ) ) : ?>
				<?php echo MB_UI::pro_gate( 'پیش‌بینی سه‌چرخه‌ای', 'با نسخهٔ پیشرفته، تاریخ قاعدگی، باروری و تخمک‌گذاری سه چرخه آینده را می‌بینی.' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php else : ?>
				<div class="pred-list">
					<?php foreach ( (array) $data['predicted'] as $mb_i => $mb_p ) : ?>
						<div class="glass card pred">
							<div class="pred-head">
								<span class="chip dark">چرخه <?php echo esc_html( MB_UI::num( $mb_i + 2 ) ); ?></span>
								<span class="h3"><?php echo esc_html( MB_Jalali::range_fa( (string) $mb_p['start'], (string) $mb_p['end'] ) ); ?></span>
							</div>
							<div class="pred-rows">
								<span class="small">باروری: <?php echo esc_html( MB_Jalali::range_fa( (string) $mb_p['fertile']['from'], (string) $mb_p['fertile']['to'] ) ); ?></span>
								<span class="small">تخمک‌گذاری: <?php echo esc_html( MB_Jalali::format_fa( (string) $mb_p['ovulation'], 'short' ) ); ?></span>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'calendar' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
