<?php
/**
 * #/reminders — یادآورهای سفارشی و یادآور دارو (M5 + M3).
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_prefs = (array) ( $data['prefs'] ?? array() );
$mb_chans = (array) ( $data['channels'] ?? array() );
$mb_meds  = (array) ( $data['meds'] ?? array() );
$mb_is_p  = ! empty( $data['is_partner'] );
?>
<div class="scr reminders">
	<div class="pad">

		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head">
				<h1 class="h1">یادآورها</h1>
				<p class="small">ساعت و کانال هر یادآور را خودت تعیین کن</p>
			</div>
			<?php echo MB_UI::icon( 'bell', 24, 1.7, 'hdr-ic' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>

		<?php foreach ( $mb_prefs as $mb_key => $mb_pref ) : ?>
			<?php
			// یادآور همراه فقط برای بانو معنا دارد و یادآور بانو برای همسر.
			if ( $mb_is_p && 'partner' !== $mb_pref['audience'] ) {
				continue;
			}
			if ( ! $mb_is_p && 'woman' !== $mb_pref['audience'] && 'partner' !== $mb_pref['audience'] ) {
				continue;
			}
			?>
			<section class="glass card rem" data-trigger="<?php echo esc_attr( (string) $mb_key ); ?>">
				<div class="rem-head">
					<div>
						<h2 class="h2"><?php echo esc_html( (string) $mb_pref['label'] ); ?></h2>
						<p class="tiny muted"><?php echo esc_html( (string) $mb_pref['hint'] ); ?></p>
					</div>
					<button type="button" class="sw<?php echo ! empty( $mb_pref['on'] ) ? ' on' : ''; ?>" data-mb="rem-toggle"
						role="switch" aria-checked="<?php echo ! empty( $mb_pref['on'] ) ? 'true' : 'false'; ?>"
						aria-label="<?php echo esc_attr( 'روشن/خاموش ' . (string) $mb_pref['label'] ); ?>"><i></i></button>
				</div>
				<div class="rem-body">
					<label class="fld third">
						<span>ساعت</span>
						<select data-mb="rem-hour">
							<?php for ( $mb_h = 0; $mb_h <= 23; $mb_h++ ) : ?>
								<option value="<?php echo (int) $mb_h; ?>"<?php echo (int) $mb_pref['hour'] === $mb_h ? ' selected' : ''; ?>>
									<?php echo esc_html( MB_UI::num( sprintf( '%02d:00', $mb_h ) ) ); ?>
								</option>
							<?php endfor; ?>
						</select>
					</label>
					<label class="fld third">
						<span>کانال</span>
						<select data-mb="rem-channel">
							<?php foreach ( $mb_chans as $mb_cv => $mb_cl ) : ?>
								<option value="<?php echo esc_attr( (string) $mb_cv ); ?>"<?php echo (string) $mb_pref['channel'] === (string) $mb_cv ? ' selected' : ''; ?>>
									<?php echo esc_html( (string) $mb_cl ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</label>
				</div>
			</section>
		<?php endforeach; ?>

		<?php if ( empty( $data['can_sms'] ) ) : ?>
			<?php echo MB_UI::pnote( 'کانال پیامک بخشی از نسخهٔ پیشرفته است؛ اگر انتخابش کنی، فعلاً ایمیل فرستاده می‌شود.', 'warn', 'crown' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php endif; ?>

		<?php if ( ! $mb_is_p ) : ?>
			<section class="sec">
				<h2 class="h2">یادآور قرص و مکمل</h2>
				<div class="glass card">
					<?php if ( empty( $mb_meds ) ) : ?>
						<p class="small">هنوز دارویی اضافه نکرده‌ای.</p>
					<?php else : ?>
						<ul class="med-list" id="mb-med-list">
							<?php foreach ( $mb_meds as $mb_i => $mb_med ) : ?>
								<li class="med-row">
									<?php echo MB_UI::icon( 'pill', 15, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<span class="med-n"><?php echo esc_html( (string) $mb_med['name'] ); ?></span>
									<span class="med-t"><?php echo esc_html( MB_UI::num( (string) $mb_med['time'] ) ); ?></span>
									<button type="button" class="ic n sm" data-mb="med-remove" data-index="<?php echo (int) $mb_i; ?>" aria-label="حذف">
										<?php echo MB_UI::icon( 'x', 14, 2 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									</button>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>

					<div class="med-add">
						<label class="fld"><span>نام دارو یا مکمل</span><input type="text" id="mb-med-name" maxlength="60" placeholder="مثلاً فولیک‌اسید"></label>
						<label class="fld third"><span>ساعت</span><input type="text" id="mb-med-time" inputmode="numeric" maxlength="5" placeholder="۲۲:۰۰"></label>
					</div>
					<?php echo MB_UI::btn( 'افزودن یادآور دارو', 'ghost wide', 'med-add', array( 'icon' => 'plus' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo MB_UI::pnote( 'یادآور قرص باید همراه با تنظیم تریگر «یادآور قرص و مکمل» روشن باشد.', 'warn', 'bell' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>
		<?php endif; ?>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'reminders' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
