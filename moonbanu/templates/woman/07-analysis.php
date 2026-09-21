<?php
/**
 * #/analysis — بینش شخصی (N7): کامل‌شده با empty-state و بخش‌های دادهٔ موجود.
 *
 * @package moonbanu
 * @var array $data
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$mb_ins   = isset( $data['insights'] ) ? (array) $data['insights'] : array();
$mb_snap  = isset( $data['snap'] ) ? (array) $data['snap'] : array();
$mb_var   = isset( $data['variation'] ) ? (array) $data['variation'] : array();
$mb_rows  = isset( $data['length_rows'] ) ? (array) $data['length_rows'] : array();
$mb_dom   = isset( $data['dominant'] ) ? (array) $data['dominant'] : array();
$mb_has   = ( ! empty( $mb_ins['symptom_phase'] ) || isset( $mb_ins['length_trend'] ) || ! empty( $mb_ins['anomalies'] ) );
$mb_pms   = isset( $mb_ins['pms_window'] ) ? (array) $mb_ins['pms_window'] : array();
$mb_pms_ok = ( (int) ( $mb_pms['start'] ?? 0 ) > 0 );
?>
<div class="scr analysis">
	<div class="pad">
		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<h1 class="h1">بینش شخصی</h1>
		</header>
		<div class="kpi-grid three">
			<div class="glass card kpi-card"><span class="small">میانگین چرخه</span><span class="kpi"><?php echo esc_html( MB_UI::num( (int) ( $data['avg'] ?? 0 ) ) . ' روز' ); ?></span></div>
			<div class="glass card kpi-card"><span class="small">میانگین قاعدگی</span><span class="kpi"><?php echo esc_html( MB_UI::num( (int) ( $data['avg_period'] ?? 0 ) ) . ' روز' ); ?></span></div>
			<div class="glass card kpi-card"><span class="small">نوسان</span><span class="kpi"><?php echo esc_html( (string) ( $mb_var['label'] ?? 'در حال یادگیری' ) ); ?></span></div>
		</div>
		<?php if ( ! $mb_has ) : ?>
			<section class="glass card gold-line">
				<h2 class="h2">چطور بینش کامل باز می‌شود؟</h2>
				<div class="list">
					<?php echo MB_UI::lrow( 'ثبت روزانه', 'هر روز نشانه‌ها، خواب و خلق را ثبت کن تا الگوی شخصی ساخته شود.', 'spark', array( 'right' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo MB_UI::lrow( 'دو چرخهٔ کامل', 'رابطهٔ نشانه با فاز و روند طول چرخه از چرخهٔ دوم به بعد باز می‌شود.', 'cycle', array( 'right' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo MB_UI::lrow( 'تنظیم چرخه', 'تاریخ آخرین قاعدگی دقیق، همهٔ پیش‌بینی‌ها را دقیق‌تر می‌کند.', 'edit', array( 'right' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<div class="btn-col">
					<?php echo MB_UI::btn( 'ثبت امروز', 'gold wide', 'goto', array( 'icon' => 'plus', 'data' => array( 'route' => 'log/' . date( 'Y-m-d' ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>
		<?php endif; ?>
		<?php if ( ! empty( $mb_ins['symptom_phase'] ) ) : ?>
			<section class="sec">
				<h2 class="h2">رابطهٔ نشانه و فاز</h2>
				<div class="glass card">
					<?php foreach ( $mb_ins['symptom_phase'] as $mb_sp ) : ?>
						<div class="insight-row">
							<span><?php echo esc_html( (string) ( $mb_sp['symptom'] ?? '' ) . ' — ' . (string) ( $mb_sp['phase'] ?? '' ) ); ?></span>
							<span class="badge"><?php echo esc_html( MB_UI::pct( (int) ( $mb_sp['percent'] ?? 0 ) ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
		<?php if ( isset( $mb_ins['length_trend'] ) ) : ?>
			<section class="sec">
				<h2 class="h2">روند طول چرخه</h2>
				<div class="glass card">
					<p class="body">
					<?php
					$mb_dir = (string) ( $mb_ins['length_trend']['direction'] ?? '' );
					if ( 'up' === $mb_dir ) {
						echo esc_html( '📈 در حال افزایش' );
					} elseif ( 'down' === $mb_dir ) {
						echo esc_html( '📉 در حال کاهش' );
					} else {
						echo esc_html( '→ ثابت' );
					}
					?>
					</p>
					<?php if ( ! empty( $mb_rows ) ) : ?>
						<div class="list">
							<?php foreach ( $mb_rows as $mb_r ) : ?>
								<?php echo MB_UI::lrow( MB_Jalali::format_fa( (string) ( $mb_r['start'] ?? '' ), 'long' ), MB_UI::num( (int) ( $mb_r['length'] ?? 0 ) ) . ' روز', 'cal', array( 'right' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			</section>
		<?php endif; ?>
		<?php if ( ! empty( $mb_dom ) ) : ?>
			<section class="sec">
				<h2 class="h2">نشانه‌های غالب تو</h2>
				<div class="glass card">
					<?php foreach ( $mb_dom as $mb_d ) : ?>
						<div class="insight-row">
							<span><?php echo esc_html( (string) ( $mb_d['label'] ?? '' ) ); ?></span>
							<span class="badge"><?php echo esc_html( MB_UI::pct( (int) ( $mb_d['percent'] ?? 0 ) ) ); ?></span>
						</div>
						<?php if ( '' !== (string) ( $mb_d['correlation'] ?? '' ) ) : ?>
							<p class="small"><?php echo esc_html( (string) $mb_d['correlation'] ); ?></p>
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
		<?php if ( ! empty( $mb_ins['anomalies'] ) ) : ?>
			<section class="sec">
				<h2 class="h2">هشدارها</h2>
				<div class="glass card warning-box">
					<?php foreach ( $mb_ins['anomalies'] as $mb_a ) : ?>
						<div class="warning-item">
							<p class="small"><?php echo esc_html( (string) ( $mb_a['text'] ?? '' ) ); ?></p>
							<?php echo MB_UI::btn( 'بیشتر بدانید', 'ghost norm', 'goto', array( 'data' => array( 'route' => 'article/' . esc_attr( (string) ( $mb_a['article_id'] ?? '' ) ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>
		<?php if ( $mb_pms_ok ) : ?>
			<section class="sec">
				<h2 class="h2">پنجرهٔ PMS</h2>
				<div class="glass card">
					<p class="body"><?php echo esc_html( 'روزهای ' . MB_UI::num( (int) $mb_pms['start'] ) . ' تا ' . MB_UI::num( (int) $mb_pms['end'] ) . ' از چرخه' ); ?></p>
					<p class="small">زمان احتمالی پیش‌درمانیٔ قاعدگی</p>
				</div>
			</section>
		<?php endif; ?>
		<?php if ( ! empty( $mb_ins['sleep_energy'] ) ) : ?>
			<section class="sec">
				<h2 class="h2">خواب و انرژی (۳۰ روز)</h2>
				<div class="glass card">
					<div class="chart-placeholder" data-type="sleep-energy" data-series="<?php echo esc_attr( wp_json_encode( $mb_ins['sleep_energy'] ) ); ?>"></div>
					<p class="small">خطوط آبی = خواب، قرمز = انرژی</p>
				</div>
			</section>
		<?php else : ?>
			<?php echo MB_UI::pnote( 'نمودار خواب و انرژی پس از چند روز ثبت روزانه فعال می‌شود.', 'info', 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>
		<div class="btn-col">
			<?php echo MB_UI::btn( 'ویرایش چرخه', 'ghost wide', 'goto', array( 'icon' => 'edit', 'data' => array( 'route' => 'cycle-editor' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>