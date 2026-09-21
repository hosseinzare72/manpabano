<?php
/**
 * #/account — صفحهٔ حساب (همراه/همسر): ویرایش نام و موبایل، تغییر رمز،
 * اشتراک خانواده، پشتیبانی و خروج.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_user   = isset( $data['user'] ) ? (array) $data['user'] : array();
$mb_sub    = isset( $data['subscription'] ) ? (array) $data['subscription'] : array();
$mb_unread = (int) ( $data['support_unread'] ?? 0 );

$mb_name   = (string) ( $mb_user['name'] ?? '' );
$mb_email  = (string) ( $mb_user['email'] ?? '' );
$mb_mobile = (string) ( $mb_user['mobile'] ?? '' );
$mb_joined = (string) ( $mb_user['joined'] ?? '' );
?>
<div class="scr account partner-account">
	<div class="pad">
		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">حساب من</h1>
				<?php if ( '' !== $mb_joined ) : ?>
					<p class="small"><?php echo esc_html( 'عضو از ' . $mb_joined ); ?></p>
				<?php endif; ?>
			</div>
			<?php echo MB_UI::bell( (int) ( $data['unread'] ?? 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<!-- خلاصهٔ حساب -->
		<section class="glass card acc-card">
			<span class="ic n" aria-hidden="true"><?php echo MB_UI::icon( 'user', 18, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<div class="msg-card">
				<h2 class="h2"><?php echo esc_html( '' !== $mb_name ? $mb_name : 'بدون نام' ); ?></h2>
				<p class="small" dir="ltr"><?php echo esc_html( '' !== $mb_email ? $mb_email : '—' ); ?></p>
				<?php if ( '' !== $mb_mobile ) : ?>
					<p class="small" dir="ltr"><?php echo esc_html( MB_UI::num( $mb_mobile ) ); ?></p>
				<?php endif; ?>
			</div>
		</section>

		<!-- ویرایش اطلاعات حساب -->
		<section class="glass card">
			<h3 class="h3">اطلاعات من</h3>
			<div class="mb-form">
				<label class="fld">
					<span>نام</span>
					<input type="text" id="mb-acc-name" value="<?php echo esc_attr( $mb_name ); ?>" autocomplete="given-name" placeholder="مثلاً رضا" maxlength="60">
				</label>

				<label class="fld">
					<span>شمارهٔ موبایل</span>
					<input type="tel" id="mb-acc-mobile" dir="ltr" inputmode="tel" autocomplete="tel" value="<?php echo esc_attr( $mb_mobile ); ?>" placeholder="09123456789">
					<span class="small">برای بازیابی رمز، ورود با کد پیامکی و یادآورهای همراهی استفاده می‌شود.</span>
				</label>

				<label class="fld">
					<span>ایمیل</span>
					<input type="email" dir="ltr" value="<?php echo esc_attr( $mb_email ); ?>" disabled>
					<span class="small">تغییر ایمیل از راه پشتیبانی انجام می‌شود.</span>
				</label>

				<div class="btn-col">
					<?php echo MB_UI::btn( 'ذخیرهٔ اطلاعات', 'gold wide', 'account-save', array( 'icon' => 'check' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
		</section>

		<!-- تغییر رمز عبور -->
		<section class="glass card">
			<h3 class="h3">تغییر رمز عبور</h3>
			<div class="mb-form">
				<label class="fld">
					<span>رمز فعلی</span>
					<span class="pass-wrap">
						<input type="password" id="mb-acc-cur" dir="ltr" autocomplete="current-password" placeholder="••••••••">
						<button type="button" class="pass-eye" data-mb="toggle-pass" data-target="mb-acc-cur" aria-label="نمایش رمز"><?php echo MB_UI::icon( 'eye', 16, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					</span>
				</label>

				<label class="fld">
					<span>رمز تازه</span>
					<span class="pass-wrap">
						<input type="password" id="mb-acc-new" dir="ltr" autocomplete="new-password" placeholder="دست‌کم ۸ نویسه، حرف و عدد">
						<button type="button" class="pass-eye" data-mb="toggle-pass" data-target="mb-acc-new" aria-label="نمایش رمز"><?php echo MB_UI::icon( 'eye', 16, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					</span>
				</label>

				<div class="btn-col">
					<?php echo MB_UI::btn( 'ثبت رمز تازه', 'ghost wide', 'account-pass', array( 'icon' => 'lock' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</div>
		</section>

		<!-- اشتراک (N2) — همراه هم می‌تواند بخرد و هدیه بدهد -->
		<section class="glass card">
			<h3 class="h3"><?php echo esc_html( ! empty( $mb_sub['is_family'] ) ? 'اشتراک خانوادهٔ ماه‌بانو' : 'اشتراک' ); ?></h3>
			<?php if ( 'pro' === (string) ( $mb_sub['plan'] ?? 'free' ) ) : ?>
				<p class="small">
					<?php
					$mb_left = $mb_sub['days_left'] ?? null;
					echo esc_html( null !== $mb_left ? 'فعال · ' . MB_UI::num( max( 0, (int) $mb_left ) ) . ' روز باقی' : 'فعال' );
					?>
				</p>
				<?php echo MB_UI::btn( 'تمدید اشتراک', 'ghost norm', 'goto', array( 'icon' => 'crown', 'data' => array( 'route' => 'checkout' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php else : ?>
				<p class="small">با خرید اشتراک خانواده، هم صفحه‌های همراهی برای تو باز می‌شود و هم نسخهٔ پیشرفته هدیهٔ همسرت می‌شود.</p>
				<?php echo MB_UI::btn( 'خرید اشتراک خانواده', 'gold norm', 'goto', array( 'icon' => 'crown', 'data' => array( 'route' => 'checkout' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		</section>

		<!-- پشتیبانی و درباره -->
		<section class="sec">
			<h2 class="h2">پشتیبانی و درباره</h2>
			<div class="glass card">
				<?php
				echo MB_UI::lrow(
					'پشتیبانی',
					$mb_unread > 0 ? MB_UI::num( $mb_unread ) . ' پاسخ تازه داری' : 'ثبت درخواست و پیگیری پاسخ',
					$mb_unread > 0 ? 'bell' : 'msg',
					array( 'action' => 'goto', 'data' => array( 'route' => 'support' ) )
				); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::lrow( 'درباره و سازنده', MB_Plugin::brand( 'credit' ), 'spark', array( 'action' => 'goto', 'data' => array( 'route' => 'about' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
		</section>

		<!-- خروج (N1) -->
		<section class="sec">
			<div class="btn-col">
				<?php echo MB_UI::btn( 'خروج از حساب', 'ghost wide', 'logout', array( 'icon' => 'logout' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</div>
		</section>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo MB_UI::brand_footer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'partner', 'partner' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
