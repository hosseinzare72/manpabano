<?php
/**
 * #/health — تنظیم پروفایل سلامت (N3).
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_health = isset( $data['health'] ) ? (array) $data['health'] : array();
$mb_blood_types = isset( $data['blood_types'] ) ? (array) $data['blood_types'] : array();
?>
<div class="scr health">
	<div class="pad">
		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<h1 class="h1">پروفایل سلامت</h1>
		</header>

		<section class="glass card">
			<form id="health-form" data-mb="health-save">
				<!-- Height -->
				<div class="form-group">
					<label for="health-height">قد (سانتی‌متر)</label>
					<input type="number" id="health-height" name="height_cm" min="100" max="230" value="<?php echo isset( $mb_health['height_cm'] ) ? esc_attr( $mb_health['height_cm'] ) : ''; ?>" placeholder="170">
				</div>

				<!-- Weight -->
				<div class="form-group">
					<label for="health-weight">وزن (کیلوگرم)</label>
					<input type="number" id="health-weight" name="weight_kg" min="25" max="250" step="0.1" value="<?php echo isset( $mb_health['weight_kg'] ) ? esc_attr( $mb_health['weight_kg'] ) : ''; ?>" placeholder="65">
				</div>

				<!-- Waist Circumference -->
				<div class="form-group">
					<label for="health-waist">دور کمر (سانتی‌متر)</label>
					<input type="number" id="health-waist" name="waist_cm" min="40" max="200" step="0.5" value="<?php echo isset( $mb_health['waist_cm'] ) ? esc_attr( $mb_health['waist_cm'] ) : ''; ?>" placeholder="75">
				</div>

				<!-- Blood Type -->
				<div class="form-group">
					<label for="health-blood">گروه خونی</label>
					<select id="health-blood" name="blood_type">
						<option value="">انتخاب کن</option>
						<?php foreach ( $mb_blood_types as $mb_bt => $mb_btl ) : ?>
							<option value="<?php echo esc_attr( $mb_bt ); ?>" <?php selected( isset( $mb_health['blood_type'] ) ? $mb_health['blood_type'] : '', $mb_bt ); ?>><?php echo esc_html( $mb_btl ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<!-- National ID (Optional, Encrypted) -->
				<div class="form-group">
					<label for="health-nid">شماره ملی (اختیاری)</label>
					<input type="text" id="health-nid" name="national_id" maxlength="10" value="<?php echo isset( $mb_health['national_id'] ) ? esc_attr( '***' ) : ''; ?>" placeholder="1234567890">
					<p class="small">محلی رمزنگاری می‌شود.</p>
				</div>

				<!-- Emergency Contact -->
				<div class="form-group">
					<label for="health-ename">نام تماس اضطراری</label>
					<input type="text" id="health-ename" name="emergency_name" maxlength="100" value="<?php echo isset( $mb_health['emergency_name'] ) ? esc_attr( $mb_health['emergency_name'] ) : ''; ?>" placeholder="نام">
				</div>

				<!-- Emergency Phone -->
				<div class="form-group">
					<label for="health-ephone">شماره تماس اضطراری</label>
					<input type="tel" id="health-ephone" name="emergency_phone" value="<?php echo isset( $mb_health['emergency_phone'] ) ? esc_attr( $mb_health['emergency_phone'] ) : ''; ?>" placeholder="09xxxxxxxxx">
					<p class="small">برای اطلاع‌رسانی خودکار در موارد اضطراری.</p>
				</div>

				<!-- Medical Note (Optional, Encrypted) -->
				<div class="form-group">
					<label for="health-note">یادداشت پزشکی (اختیاری)</label>
					<textarea id="health-note" name="medical_note" maxlength="1000" placeholder="الرژی‌ها، دارو‌ها، شرایط خاص..."><?php echo isset( $mb_health['medical_note'] ) ? esc_html( $mb_health['medical_note'] ) : ''; ?></textarea>
					<p class="small">محلی رمزنگاری می‌شود.</p>
				</div>

				<!-- Emergency Alert Toggle -->
				<div class="form-group toggle-row">
					<label for="health-alert">فعال‌سازی اطلاع‌رسانی اضطراری</label>
					<input type="checkbox" id="health-alert" name="emergency_alert" <?php checked( isset( $mb_health['emergency_alert'] ) ? $mb_health['emergency_alert'] : false ); ?>>
					<label for="health-alert" class="toggle-label"></label>
				</div>
				<p class="small">زمانی که فعال باشد، در صورت وضعیت بحرانی پیامک و اطلاع فرستاده می‌شود.</p>

				<div class="btn-col">
					<?php echo MB_UI::btn( 'ذخیره', 'gold wide', 'health-save' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</form>
		</section>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'account' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
