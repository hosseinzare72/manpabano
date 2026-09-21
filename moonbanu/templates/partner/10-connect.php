<?php
/**
 * #/connect (و #/me) — دید بانو: اتصال همسر، اشتراک‌گذاری، اشتراک.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_invite  = $data['invite'];
$mb_status  = (array) $data['status'];
$mb_profile = (array) $data['profile'];
$mb_defaults = MB_Api::share_defaults( (int) $data['user_id'] );
$mb_shares  = array(
	'share_period'    => array( 'برنامهٔ قاعدگی (تاریخ‌ها)', 'تاریخ تخمینی شروع و پایان' ),
	'share_pms'       => array( 'هشدار دورهٔ پیش‌از‌قاعدگی', 'فقط «از فردا وارد PMS می‌شود»' ),
	'share_support'   => array( 'پیشنهادهای حمایتی روزانه', 'کارهای کوچک بدون جزئیات خصوصی' ),
	'share_fertility' => array( 'پنجره باروری و نشانه‌ها', 'همیشه خصوصی؛ روشن کردن فقط بازه زمانی را می‌دهد، نه نشانه‌ها' ),
);
?>
<div class="scr connect">
	<div class="pad">

		<header class="hdr">
			<div>
				<h1 class="h1">همراه ماه</h1>
				<p class="small">دسترسی همسر، یک‌طرفه و هر لحظه قابل لغو</p>
			</div>
			<?php echo MB_UI::chip( 'حریم خصوصی کامل', 'gold', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<?php if ( empty( $data['can_partner'] ) ) : ?>
			<?php echo MB_UI::pro_gate( 'همراهی همسر', 'دعوت همسر و چهار صفحهٔ همراه با نسخهٔ پیشرفته فعال می‌شود.' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php else : ?>

			<?php if ( $mb_invite && ! empty( $mb_invite['accepted_by'] ) ) : ?>
				<section class="glass card">
					<h2 class="h2">همسر متصل است</h2>
					<p class="body"><?php echo esc_html( $data['partner'] ? $data['partner']->display_name : '' ); ?> به صفحه‌های همراه دسترسی دارد و فقط داده‌های مجاز را می‌بیند.</p>
					<?php echo MB_UI::btn( 'لغو دسترسی همسر', 'dark sm', 'invite-revoke', array( 'icon' => 'x' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</section>
			<?php else : ?>
				<section class="glass card center qr-card">
					<?php if ( '' !== (string) $data['link'] ) : ?>
						<div class="qr" id="mb-qr" data-link="<?php echo esc_attr( (string) $data['link'] ); ?>" aria-label="کد QR دعوت"></div>
					<?php else : ?>
						<div class="qr-ph" aria-hidden="true">
							<?php echo MB_UI::icon( 'qr', 40, 1.5 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<span class="tiny muted">کد QR پس از ساخت دعوت اینجا می‌آید</span>
						</div>
					<?php endif; ?>
					<?php if ( '' !== (string) $data['link'] ) : ?>
						<div class="link-row">
							<span class="chip gold link-chip" id="mb-invite-link"><?php echo esc_html( (string) $data['link'] ); ?></span>
							<?php echo MB_UI::btn( 'کپی', 'ghost xs', 'copy-link', array( 'icon' => 'copy' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						</div>
					<?php else : ?>
						<p class="body">برای ساخت لینک دعوت، یکی از دکمه‌های زیر را بزن.</p>
					<?php endif; ?>

					<label class="fld">
						<span>ایمیل یا شماره همسر</span>
						<input type="text" id="mb-invite-target" dir="ltr" placeholder="name@mail.com یا ۰۹۱۲…">
					</label>

					<div class="btn-row">
						<?php
						echo MB_UI::btn( 'ارسال دعوت‌نامه', 'gold sm', 'invite-send', array( 'icon' => 'send', 'data' => array( 'channel' => 'email' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						echo MB_UI::btn( 'پیامک', 'ghost sm', 'invite-send', array( 'icon' => 'msg', 'data' => array( 'channel' => 'sms' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					</div>
					<p class="small">اعتبار دعوت‌نامه ۷۲ ساعت است، یک‌بار پذیرش می‌شود و هر لحظه می‌توانی لغوش کنی.</p>
				</section>
			<?php endif; ?>

			<section class="sec">
				<h2 class="h2">چه چیزی دیده شود؟</h2>
				<div class="glass card">
					<?php foreach ( $mb_shares as $mb_key => $mb_row ) : ?>
						<?php
						$mb_on = $mb_invite ? 1 === (int) $mb_invite[ $mb_key ] : 1 === (int) $mb_defaults[ $mb_key ];
						echo MB_UI::toggle( $mb_row[0], $mb_row[1], (bool) $mb_on, 'share-toggle', array( 'key' => $mb_key ) ); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					<?php endforeach; ?>
					<?php echo MB_UI::pnote( 'نشانه‌ها، خلق، درد، یادداشت خصوصی و سلامت جنسی در هیچ حالتی به همسر نمایش داده نمی‌شود.', 'good', 'lock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>
		<?php endif; ?>

		<section class="sec">
			<h2 class="h2">اشتراک من</h2>
			<div class="glass card">
				<?php
				echo MB_UI::lrow(
					'pro' === $mb_status['plan'] ? 'نسخهٔ پیشرفته فعال است' : 'نسخهٔ رایگان',
					'pro' === $mb_status['plan'] ? ( '' !== $mb_status['expires_fa'] ? 'تا ' . $mb_status['expires_fa'] : '' ) : 'قیمت ماهانه: ' . $mb_status['price_fa'],
					'crown',
					array( 'action' => 'goto', 'data' => array( 'route' => 'checkout' ) )
				); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::lrow( 'مرکز سلامت', 'راهنماها و پرسش از متخصص', 'book', array( 'action' => 'goto', 'data' => array( 'route' => 'health' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::lrow( 'چرخهٔ من', 'نقشهٔ کامل فازها', 'chart', array( 'action' => 'goto', 'data' => array( 'route' => 'cycle' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::lrow( 'تنظیم چرخه', 'طول چرخه: ' . MB_UI::num( (int) $mb_profile['cycle_len'] ) . ' روز · قاعدگی: ' . MB_UI::num( (int) $mb_profile['period_len'] ) . ' روز', 'cal', array( 'action' => 'goto', 'data' => array( 'route' => 'setup' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
			<?php if ( ! empty( $mb_status['in_grace'] ) ) : ?>
				<?php echo MB_UI::pnote( 'اشتراکت تمام شده و در مهلت ارفاق سه‌روزه هستی. برای قطع‌نشدن امکانات، تمدید کن.', 'warn', 'alert' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php endif; ?>
		</section>

		<section class="sec">
			<h2 class="h2">حساب و پشتیبانی</h2>
			<div class="glass card">
				<?php
				$mb_unread = (int) ( $data['support_unread'] ?? 0 );
				echo MB_UI::lrow( 'حساب من', 'نام، موبایل و رمز عبور', 'user', array( 'action' => 'goto', 'data' => array( 'route' => 'account' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::lrow(
					'پشتیبانی',
					$mb_unread > 0 ? MB_UI::num( $mb_unread ) . ' پاسخ تازه داری' : 'ثبت درخواست و پیگیری پاسخ',
					$mb_unread > 0 ? 'bell' : 'msg',
					array( 'action' => 'goto', 'data' => array( 'route' => 'support' ) )
				); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::lrow( 'درباره و سازنده', MB_Plugin::brand( 'credit' ), 'spark', array( 'action' => 'goto', 'data' => array( 'route' => 'about' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				echo MB_UI::lrow( 'خروج از حساب', '', 'x', array( 'action' => 'auth-logout' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
				?>
			</div>
		</section>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php echo MB_UI::brand_footer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'me' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
