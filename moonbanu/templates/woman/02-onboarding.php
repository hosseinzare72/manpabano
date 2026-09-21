<?php
/**
 * #/onboarding و #/setup — سه اسلاید معرفی + تنظیم اولیه.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_step    = (int) ( $data['step'] ?? 1 );
$mb_profile = (array) ( $data['profile'] ?? array() );
$mb_slides  = array(
	array(
		'title' => 'پیش‌بینی تاریخ‌به‌تاریخ',
		'text'  => 'با تقویم شمسی، روز چرخه و فاز امروزت را دقیق می‌بینی و می‌دانی قاعدگی بعدی چه روزی است.',
		'icon'  => 'cal',
	),
	array(
		'title' => 'ثبت نشانه‌ها و تحلیل',
		'text'  => 'خلق، خونریزی، درد و نشانه‌ها را ثبت می‌کنی و ماه‌بانو الگوی شخصی‌ات را می‌سازد.',
		'icon'  => 'chart',
	),
	array(
		'title' => 'اطلاع‌رسانی محترمانه به همسر',
		'text'  => 'اگر بخواهی، همسرت فقط رنگ روزها و پیشنهادهای حمایتی را می‌بیند؛ نشانه‌ها هرگز.',
		'icon'  => 'heart',
	),
);

$mb_last = ! empty( $mb_profile['last_period_start'] ) ? MB_Jalali::en_num( MB_Jalali::format_fa( (string) $mb_profile['last_period_start'], 'num' ) ) : '';
?>
<div class="scr onb">
	<div class="pad">
		<div class="onb-top">
				<button type="button" class="btn ghost xs" data-mb="goto" data-route="setup">رد کردن</button>
		</div>

		<?php if ( $mb_step <= 3 ) : ?>
			<?php $mb_slide = $mb_slides[ $mb_step - 1 ]; ?>
			<div class="center">
				<?php echo MB_UI::ring( 186, MB_UI::cycle_segments( (int) $data['user_id'] ), $mb_step * 6, array( 'top' => 'گام ' . MB_UI::num( $mb_step ), 'num' => MB_UI::num( $mb_step ) . '/۳' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>

			<div class="onb-cards">
				<?php foreach ( $mb_slides as $mb_i => $mb_card ) : ?>
					<div class="glass card feat<?php echo $mb_i === $mb_step - 1 ? ' on' : ''; ?>">
						<span class="feat-ic"><?php echo MB_UI::icon( $mb_card['icon'], 18, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
						<div>
							<h2 class="h2"><?php echo esc_html( $mb_card['title'] ); ?></h2>
							<p class="body"><?php echo esc_html( $mb_card['text'] ); ?></p>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="dots" role="tablist">
				<?php for ( $mb_d = 1; $mb_d <= 3; $mb_d++ ) : ?>
					<button type="button" class="dot<?php echo $mb_d === $mb_step ? ' on' : ''; ?>" data-mb="goto" data-route="onboarding/<?php echo (int) $mb_d; ?>" aria-label="اسلاید <?php echo esc_attr( MB_UI::num( $mb_d ) ); ?>"></button>
				<?php endfor; ?>
			</div>

			<?php
			$mb_next = $mb_step < 3 ? 'onboarding/' . ( $mb_step + 1 ) : 'setup';
			echo MB_UI::btn( $mb_step < 3 ? 'بعدی' : 'شروع کنیم', 'gold wide', 'goto', array( 'data' => array( 'route' => $mb_next ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>

		<?php else : ?>
			<h1 class="h1">تنظیم اولیه</h1>
			<p class="body">برای شروع سه چیز لازم است. بعداً هم می‌توانی تغییرش بدهی.</p>

			<form class="glass card mb-form" id="mb-setup" novalidate>
				<label class="fld">
					<span>تاریخ اولین روز آخرین قاعدگی (شمسی)</span>
					<input type="text" inputmode="numeric" dir="ltr" id="mb-last-period" name="last_period_jalali" placeholder="۱۴۰۵/۰۶/۰۷" value="<?php echo esc_attr( MB_Jalali::fa_num( $mb_last ) ); ?>" required>
					<small class="small">قالب: سال/ماه/روز</small>
				</label>

				<div class="jpick" id="mb-jpick" data-today="<?php echo esc_attr( MB_Jalali::en_num( MB_Jalali::format_fa( $data['today'], 'num' ) ) ); ?>">
					<div class="jpick-row">
						<label class="fld"><span>سال</span><input type="number" id="mb-jy" min="1300" max="1500" value="<?php echo esc_attr( (string) MB_Jalali::g2j( $data['today'] )[0] ); ?>"></label>
						<label class="fld"><span>ماه</span>
							<select id="mb-jm">
								<?php foreach ( MB_Jalali::months() as $mb_mi => $mb_mn ) : ?>
									<option value="<?php echo (int) $mb_mi; ?>"<?php selected( (int) MB_Jalali::g2j( $data['today'] )[1], (int) $mb_mi ); ?>><?php echo esc_html( $mb_mn ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="fld"><span>روز</span><input type="number" id="mb-jd" min="1" max="31" value="<?php echo esc_attr( (string) MB_Jalali::g2j( $data['today'] )[2] ); ?>"></label>
					</div>
					<button type="button" class="btn dark sm" data-mb="jpick-apply">گذاشتن این تاریخ</button>
				</div>

				<label class="fld">
					<span>طول چرخه (روز)</span>
					<input type="range" class="slider" id="mb-cycle-len" name="cycle_len" min="21" max="35" value="<?php echo esc_attr( (string) ( $mb_profile['cycle_len'] ?? 28 ) ); ?>">
					<output class="kpi" id="mb-cycle-out"><?php echo esc_html( MB_UI::num( $mb_profile['cycle_len'] ?? 28 ) ); ?></output>
				</label>

				<label class="fld">
					<span>طول قاعدگی (روز)</span>
					<input type="range" class="slider" id="mb-period-len" name="period_len" min="2" max="7" value="<?php echo esc_attr( (string) ( $mb_profile['period_len'] ?? 5 ) ); ?>">
					<output class="kpi" id="mb-period-out"><?php echo esc_html( MB_UI::num( $mb_profile['period_len'] ?? 5 ) ); ?></output>
				</label>

				<label class="fld">
					<span>شماره موبایل (اختیاری، برای پیامک)</span>
					<input type="tel" dir="ltr" name="mobile" inputmode="numeric" placeholder="۰۹۱۲۳۴۵۶۷۸۹">
				</label>

				<button type="button" class="btn gold wide" data-mb="save-profile">ذخیره و ورود</button>
			</form>
		<?php endif; ?>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
</div>
