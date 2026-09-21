<?php
/**
 * #/log — ثبت روزانه.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_log   = (array) ( $data['log'] ?? array() );
$mb_syms  = (array) ( $mb_log['symptoms'] ?? array() );
$mb_pain  = (int) ( $mb_log['pain'] ?? 0 );
$mb_mood  = isset( $mb_log['mood'] ) && null !== $mb_log['mood'] ? (int) $mb_log['mood'] : 0;
$mb_bleed = (string) ( $mb_log['bleeding'] ?? 'none' );
$mb_moods = array(
	1 => array( 'ic' => '😞', 'label' => 'بد' ),
	2 => array( 'ic' => '😕', 'label' => 'کم‌حوصله' ),
	3 => array( 'ic' => '😐', 'label' => 'معمولی' ),
	4 => array( 'ic' => '🙂', 'label' => 'خوب' ),
	5 => array( 'ic' => '😄', 'label' => 'عالی' ),
);
$mb_bleeds = array(
	'none'  => 'ندارم',
	'spot'  => 'لکه‌بینی',
	'light' => 'کم',
	'med'   => 'متوسط',
	'heavy' => 'زیاد',
);
$mb_pain_labels = array( 1 => 'خیلی کم', 2 => 'کم', 3 => 'متوسط', 4 => 'زیاد', 5 => 'خیلی زیاد' );
?>
<div class="scr log">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">ثبت امروز</h1>
				<p class="small"><?php echo esc_html( (string) $data['date_fa'] ); ?></p>
			</div>
			<span class="badge streak"><?php echo esc_html( MB_UI::num( (int) $data['streak'] ) . ' روز پیاپی ثبت' ); ?></span>
		</header>

		<form class="mb-form" id="mb-log-form" data-date="<?php echo esc_attr( (string) $data['date'] ); ?>" novalidate>

			<section class="sec">
				<h2 class="h2">خلق امروز <span class="small">(اختیاری)</span></h2>
				<div class="emoj-row">
					<?php foreach ( $mb_moods as $mb_v => $mb_m ) : ?>
						<button type="button" class="emoj<?php echo $mb_mood === $mb_v ? ' on' : ''; ?>" data-mb="mood" data-value="<?php echo (int) $mb_v; ?>" aria-label="<?php echo esc_attr( $mb_m['label'] ); ?>" aria-pressed="<?php echo $mb_mood === $mb_v ? 'true' : 'false'; ?>">
							<span aria-hidden="true"><?php echo esc_html( $mb_m['ic'] ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="sec">
				<h2 class="h2">خونریزی</h2>
				<div class="chips pickable">
					<?php foreach ( $mb_bleeds as $mb_k => $mb_label ) : ?>
						<button type="button" class="chip pick<?php echo $mb_bleed === $mb_k ? ' on' : ''; ?>" data-mb="bleeding" data-value="<?php echo esc_attr( $mb_k ); ?>"><?php echo esc_html( $mb_label ); ?></button>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="sec">
				<h2 class="h2">نشانه‌ها</h2>
				<div class="sym-grid">
					<?php foreach ( (array) $data['symptoms'] as $mb_key => $mb_sym ) : ?>
						<button type="button" class="sym<?php echo in_array( $mb_key, $mb_syms, true ) ? ' on' : ''; ?>" data-mb="symptom" data-value="<?php echo esc_attr( $mb_key ); ?>" aria-pressed="<?php echo in_array( $mb_key, $mb_syms, true ) ? 'true' : 'false'; ?>">
							<?php echo MB_UI::icon( (string) $mb_sym['icon'], 16, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span><?php echo esc_html( (string) $mb_sym['label'] ); ?></span>
						</button>
					<?php endforeach; ?>
				</div>
			</section>

			<section class="sec">
				<h2 class="h2">شدت درد</h2>
				<div class="pain-row">
					<?php for ( $mb_p = 1; $mb_p <= 5; $mb_p++ ) : ?>
						<button type="button" class="pain<?php echo $mb_pain === $mb_p ? ' on' : ''; ?>" data-mb="pain" data-value="<?php echo (int) $mb_p; ?>"><?php echo esc_html( MB_UI::num( $mb_p ) ); ?></button>
					<?php endfor; ?>
					<span class="chip pms" id="mb-pain-label"><?php echo esc_html( $mb_pain > 0 ? ( $mb_pain_labels[ $mb_pain ] ?? '' ) : 'بدون درد' ); ?></span>
				</div>
			</section>

			<section class="sec">
				<h2 class="h2">ردیاب‌های روزانه <span class="small">(اختیاری)</span></h2>
				<div class="glass card track-grid">
					<label class="fld">
						<span><?php echo MB_UI::icon( 'scale', 13, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> وزن (کیلوگرم)</span>
						<input type="text" id="mb-weight" inputmode="decimal" maxlength="6" placeholder="۶۲٫۵" value="<?php echo esc_attr( null === ( $mb_log['weight_kg'] ?? null ) ? '' : (string) $mb_log['weight_kg'] ); ?>">
					</label>
					<label class="fld">
						<span><?php echo MB_UI::icon( 'moon', 13, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> خواب (ساعت)</span>
						<input type="text" id="mb-sleep" inputmode="decimal" maxlength="4" placeholder="۷٫۵" value="<?php echo esc_attr( null === ( $mb_log['sleep_h'] ?? null ) ? '' : (string) $mb_log['sleep_h'] ); ?>">
					</label>
					<label class="fld">
						<span><?php echo MB_UI::icon( 'water', 13, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> آب (لیوان)</span>
						<input type="text" id="mb-water" inputmode="numeric" maxlength="2" placeholder="۸" value="<?php echo esc_attr( null === ( $mb_log['water_cups'] ?? null ) ? '' : (string) $mb_log['water_cups'] ); ?>">
					</label>
					<label class="fld">
						<span><?php echo MB_UI::icon( 'run', 13, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> ورزش (دقیقه)</span>
						<input type="text" id="mb-exercise" inputmode="numeric" maxlength="3" placeholder="۳۰" value="<?php echo esc_attr( null === ( $mb_log['exercise_min'] ?? null ) ? '' : (string) $mb_log['exercise_min'] ); ?>">
					</label>
				</div>

				<?php if ( ! empty( $data['meds_list'] ) ) : ?>
					<div class="glass card">
						<h3 class="h3">داروها و مکمل‌های امروز</h3>
						<?php
						$mb_taken = array();
						foreach ( (array) ( $mb_log['meds'] ?? array() ) as $mb_m ) {
							if ( ! empty( $mb_m['taken'] ) ) {
								$mb_taken[] = (string) $mb_m['name'];
							}
						}
						?>
						<div class="chips pickable" id="mb-meds">
							<?php foreach ( (array) $data['meds_list'] as $mb_med ) : ?>
								<button type="button" class="chip pick<?php echo in_array( (string) $mb_med['name'], $mb_taken, true ) ? ' on' : ''; ?>"
									data-mb="med-toggle" data-value="<?php echo esc_attr( (string) $mb_med['name'] ); ?>">
									<?php echo esc_html( (string) $mb_med['name'] . ' · ' . MB_UI::num( (string) $mb_med['time'] ) ); ?>
								</button>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endif; ?>
			</section>

			<?php if ( ! empty( $data['can_ttc'] ) && empty( $data['pregnancy'] ) ) : ?>
				<section class="sec">
					<h2 class="h2">تلاش برای بارداری</h2>
					<div class="glass card">
						<label class="fld">
							<span><?php echo MB_UI::icon( 'temp', 13, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?> دمای پایهٔ بدن (۳۴ تا ۴۲ درجه)</span>
							<input type="text" id="mb-bbt" inputmode="decimal" maxlength="4" placeholder="۳۶٫۵" value="<?php echo esc_attr( null === ( $mb_log['bbt'] ?? null ) ? '' : (string) $mb_log['bbt'] ); ?>">
							<span class="tiny muted">صبح، پیش از بلندشدن از تخت، اندازه بگیر.</span>
						</label>
						<label class="fld">
							<span>مخاط دهانهٔ رحم</span>
							<select id="mb-mucus">
								<?php foreach ( (array) $data['mucus_map'] as $mb_k => $mb_l ) : ?>
									<option value="<?php echo esc_attr( (string) $mb_k ); ?>"<?php echo (string) ( $mb_log['mucus'] ?? 'none' ) === (string) $mb_k ? ' selected' : ''; ?>><?php echo esc_html( (string) $mb_l ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="fld">
							<span>رابطهٔ جنسی</span>
							<select id="mb-sex">
								<?php foreach ( (array) $data['sex_map'] as $mb_k => $mb_l ) : ?>
									<option value="<?php echo esc_attr( (string) $mb_k ); ?>"<?php echo (string) ( $mb_log['sex'] ?? 'none' ) === (string) $mb_k ? ' selected' : ''; ?>><?php echo esc_html( (string) $mb_l ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<?php echo MB_UI::pnote( 'دما، مخاط و رابطهٔ جنسی خصوصی مطلق‌اند: هرگز به هیچ صفحه یا اعلان همسر نمی‌روند.', 'good', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</section>
			<?php endif; ?>

			<section class="sec">
				<h2 class="h2">یادداشت خصوصی</h2>
				<div class="glass card">
					<label class="fld">
						<span>فقط برای خودت</span>
						<textarea id="mb-note" rows="3" maxlength="1200" placeholder="هر چیزی که می‌خواهی یادت بماند…"><?php echo esc_textarea( (string) $data['note'] ); ?></textarea>
					</label>
					<?php echo MB_UI::pnote( 'این یادداشت رمزنگاری می‌شود و هرگز به همسر نمایش داده نمی‌شود.', 'good', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>

			<?php echo MB_UI::btn( 'ذخیره در دفترچهٔ سلامت', 'gold wide', 'save-log' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</form>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'log' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
