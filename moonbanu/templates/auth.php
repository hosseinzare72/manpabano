<?php
/**
 * #/login · #/register · #/forgot — احراز هویت کاملاً درون اپ.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_mode   = (string) ( $data['mode'] ?? 'login' );
$mb_otp_on = ! empty( $data['otp_enabled'] );
$mb_nonce  = (string) ( $data['nonce'] ?? '' );
$mb_signup = ! empty( $data['can_signup'] );
$mb_terms  = (string) ( $data['terms_url'] ?? '' );
$mb_priv   = (string) ( $data['privacy_url'] ?? '' );
$mb_brand  = MB_Plugin::brand( 'name' );
?>
<div class="scr auth" data-mb-auth="<?php echo esc_attr( $mb_mode ); ?>">
	<div class="pad">

		<header class="auth-head">
			<div class="brand gold-text"><?php echo esc_html( $mb_brand ); ?></div>
			<p class="tagline">همراه آرام چرخهٔ تو، با تقویم شمسی</p>
		</header>

		<div class="auth-tabs" role="tablist">
			<button type="button" class="auth-tab<?php echo 'login' === $mb_mode ? ' on' : ''; ?>" data-mb="auth-mode" data-mode="login" role="tab" aria-selected="<?php echo 'login' === $mb_mode ? 'true' : 'false'; ?>">ورود</button>
			<?php if ( $mb_signup ) : ?>
				<button type="button" class="auth-tab<?php echo 'register' === $mb_mode ? ' on' : ''; ?>" data-mb="auth-mode" data-mode="register" role="tab" aria-selected="<?php echo 'register' === $mb_mode ? 'true' : 'false'; ?>">ثبت‌نام</button>
			<?php endif; ?>
		</div>

		<form class="glass card auth-card" id="mb-auth-form" data-nonce="<?php echo esc_attr( $mb_nonce ); ?>" autocomplete="on" novalidate>

			<?php /* ---------------------------- ورود ---------------------------- */ ?>
			<div class="auth-panel<?php echo 'login' === $mb_mode ? ' on' : ''; ?>" data-panel="login">
				<h1 class="h1">ورود به حساب</h1>
				<p class="body">با ایمیل یا شمارهٔ موبایلی که ثبت کرده‌ای وارد شو.</p>

				<label class="fld">
					<span>ایمیل یا موبایل</span>
					<input type="text" name="identity" id="mb-login-identity" dir="ltr" inputmode="email" autocomplete="username" placeholder="name@mail.com">
				</label>

				<label class="fld">
					<span>رمز عبور</span>
					<span class="pass-wrap">
						<input type="password" name="password" id="mb-login-pass" dir="ltr" autocomplete="current-password" placeholder="••••••••">
						<button type="button" class="pass-eye" data-mb="toggle-pass" data-target="mb-login-pass" aria-label="نمایش رمز"><?php echo MB_UI::icon( 'eye', 16, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					</span>
				</label>

				<div class="auth-row">
					<label class="mini-check"><input type="checkbox" name="remember" id="mb-remember" checked><span>مرا به خاطر بسپار</span></label>
					<button type="button" class="link-btn" data-mb="auth-mode" data-mode="forgot">رمزم را فراموش کردم</button>
				</div>

				<?php echo MB_UI::btn( 'ورود', 'gold wide', 'auth-login', array( 'icon' => 'check' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

				<?php if ( $mb_otp_on ) : ?>
					<p class="small center"><button type="button" class="link-btn" data-mb="auth-mode" data-mode="otp">ورود با کد یک‌بارمصرف (بدون رمز)</button></p>
				<?php endif; ?>

				<?php if ( $mb_signup ) : ?>
					<p class="small center">حساب نداری؟ <button type="button" class="link-btn" data-mb="auth-mode" data-mode="register">همین‌جا ثبت‌نام کن</button></p>
				<?php endif; ?>
			</div>

			<?php /* ------------------- M6 — ورود با کد یک‌بارمصرف ------------------- */ ?>
			<?php if ( $mb_otp_on ) : ?>
			<div class="auth-panel<?php echo 'otp' === $mb_mode ? ' on' : ''; ?>" data-panel="otp">
				<h1 class="h1">ورود با کد یک‌بارمصرف</h1>
				<p class="body">شمارهٔ موبایل یا ایمیلت را بنویس؛ یک کد شش‌رقمی می‌فرستیم و بدون رمز وارد می‌شوی.</p>

				<label class="fld">
					<span>موبایل یا ایمیل</span>
					<input type="text" name="identity" id="mb-otp-identity" dir="ltr" inputmode="tel" autocomplete="username" placeholder="09123456789">
				</label>

				<?php echo MB_UI::btn( 'ارسال کد ورود', 'gold wide', 'otp-request', array( 'icon' => 'send' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

				<div class="otp-step" id="mb-otp-step2" hidden>
					<label class="fld">
						<span>کد ورود</span>
						<input type="text" name="code" id="mb-otp-code" dir="ltr" inputmode="numeric" maxlength="6" autocomplete="one-time-code" class="code-input" placeholder="------">
					</label>
					<p class="small">کد ۲۰ دقیقه اعتبار دارد. اگر پیامک نرسید، ایمیلت را هم ببین.</p>
					<?php echo MB_UI::btn( 'ورود به حساب', 'gold wide', 'otp-verify', array( 'icon' => 'check' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>

				<p class="small center"><button type="button" class="link-btn" data-mb="auth-mode" data-mode="login">ورود با رمز عبور</button></p>
			</div>
			<?php endif; ?>

			<?php /* --------------------------- ثبت‌نام --------------------------- */ ?>
			<?php if ( $mb_signup ) : ?>
			<div class="auth-panel<?php echo 'register' === $mb_mode ? ' on' : ''; ?>" data-panel="register">
				<h1 class="h1">ساخت حساب تازه</h1>
				<p class="body">همه‌چیز داخل همین اپ انجام می‌شود. داده‌های چرخه‌ات خصوصی و رمزنگاری‌شده ذخیره می‌شود.</p>

				<label class="fld">
					<span>نام کوچک</span>
					<input type="text" name="name" id="mb-reg-name" autocomplete="given-name" placeholder="مثلاً مریم">
				</label>

				<label class="fld">
					<span>ایمیل</span>
					<input type="email" name="email" id="mb-reg-email" dir="ltr" inputmode="email" autocomplete="email" placeholder="name@mail.com">
				</label>

				<label class="fld">
					<span>موبایل (اختیاری، برای بازیابی و پیامک)</span>
					<input type="tel" name="mobile" id="mb-reg-mobile" dir="ltr" inputmode="tel" autocomplete="tel" placeholder="09123456789">
				</label>

				<label class="fld">
					<span>رمز عبور</span>
					<span class="pass-wrap">
						<input type="password" name="password" id="mb-reg-pass" dir="ltr" autocomplete="new-password" placeholder="دست‌کم ۸ نویسه، حرف و عدد">
						<button type="button" class="pass-eye" data-mb="toggle-pass" data-target="mb-reg-pass" aria-label="نمایش رمز"><?php echo MB_UI::icon( 'eye', 16, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					</span>
					<span class="pass-meter" id="mb-pass-meter" aria-hidden="true"><i></i></span>
					<span class="small" id="mb-pass-hint">رمز باید دست‌کم ۸ نویسه و شامل حرف و عدد باشد.</span>
				</label>

				<label class="mini-check terms-check">
					<input type="checkbox" name="terms" id="mb-terms">
					<span>
						<?php if ( '' !== $mb_terms || '' !== $mb_priv ) : ?>
							<?php if ( '' !== $mb_terms ) : ?>
								<a href="<?php echo esc_url( $mb_terms ); ?>" target="_blank" rel="noopener">قوانین استفاده</a>
							<?php endif; ?>
							<?php echo ( '' !== $mb_terms && '' !== $mb_priv ) ? ' و ' : ''; ?>
							<?php if ( '' !== $mb_priv ) : ?>
								<a href="<?php echo esc_url( $mb_priv ); ?>" target="_blank" rel="noopener">حریم خصوصی</a>
							<?php endif; ?>
							را می‌پذیرم.
						<?php else : ?>
							قوانین استفاده و حریم خصوصی را می‌پذیرم.
						<?php endif; ?>
					</span>
				</label>

				<input type="text" name="website" id="mb-hp" class="mb-hp" tabindex="-1" autocomplete="off" aria-hidden="true">

				<?php echo MB_UI::btn( 'ساخت حساب و ورود', 'gold wide', 'auth-register', array( 'icon' => 'spark' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p class="small center">حساب داری؟ <button type="button" class="link-btn" data-mb="auth-mode" data-mode="login">وارد شو</button></p>
			</div>
			<?php endif; ?>

			<?php /* ------------------------- فراموشی رمز ------------------------- */ ?>
			<div class="auth-panel<?php echo 'forgot' === $mb_mode ? ' on' : ''; ?>" data-panel="forgot">
				<h1 class="h1">بازیابی رمز عبور</h1>
				<p class="body">ایمیل یا موبایلت را بنویس؛ یک کد شش‌رقمی می‌فرستیم.</p>

				<label class="fld">
					<span>ایمیل یا موبایل</span>
					<input type="text" name="identity" id="mb-forgot-identity" dir="ltr" inputmode="email" placeholder="name@mail.com">
				</label>

				<?php echo MB_UI::btn( 'ارسال کد بازیابی', 'gold wide', 'auth-forgot', array( 'icon' => 'send' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p class="small center"><button type="button" class="link-btn" data-mb="auth-mode" data-mode="login">بازگشت به ورود</button></p>
			</div>

			<?php /* -------------------------- ثبت رمز نو -------------------------- */ ?>
			<div class="auth-panel<?php echo 'reset' === $mb_mode ? ' on' : ''; ?>" data-panel="reset">
				<h1 class="h1">ثبت رمز تازه</h1>
				<p class="body">کد شش‌رقمی ارسال‌شده و رمز تازه‌ات را وارد کن. کد ۲۰ دقیقه اعتبار دارد.</p>

				<label class="fld">
					<span>ایمیل یا موبایل</span>
					<input type="text" name="identity" id="mb-reset-identity" dir="ltr" placeholder="name@mail.com">
				</label>

				<label class="fld">
					<span>کد بازیابی</span>
					<input type="text" name="code" id="mb-reset-code" dir="ltr" inputmode="numeric" maxlength="6" class="code-input" placeholder="------">
				</label>

				<label class="fld">
					<span>رمز تازه</span>
					<span class="pass-wrap">
						<input type="password" name="password" id="mb-reset-pass" dir="ltr" autocomplete="new-password" placeholder="دست‌کم ۸ نویسه، حرف و عدد">
						<button type="button" class="pass-eye" data-mb="toggle-pass" data-target="mb-reset-pass" aria-label="نمایش رمز"><?php echo MB_UI::icon( 'eye', 16, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
					</span>
				</label>

				<?php echo MB_UI::btn( 'ثبت رمز و ورود', 'gold wide', 'auth-reset', array( 'icon' => 'lock' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<p class="small center"><button type="button" class="link-btn" data-mb="auth-mode" data-mode="forgot">کد را دوباره بفرست</button></p>
			</div>

			<div class="auth-msg" id="mb-auth-msg" role="alert" aria-live="assertive"></div>
		</form>

		<div class="glass card auth-trust">
			<?php echo MB_UI::lrow( 'حریم خصوصی کامل', 'یادداشت‌ها و نشانه‌ها رمزنگاری‌شده ذخیره می‌شود', 'lock', array( 'right' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo MB_UI::lrow( 'بدون تمدید خودکار', 'اشتراک فقط با تأیید خودت تمدید می‌شود', 'shield', array( 'right' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php echo MB_UI::lrow( 'پشتیبانی فارسی', 'پاسخ درخواست‌ها داخل همین اپ', 'msg', array( 'right' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo MB_UI::brand_footer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
</div>
