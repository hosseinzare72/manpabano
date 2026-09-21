<?php
/**
 * #/support — پشتیبانی: ثبت تیکت، فهرست درخواست‌ها، سوالات متداول.
 *
 * @package moonbanu
 * @var array $data
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$mb_ticket   = isset( $data['ticket'] ) && is_array( $data['ticket'] ) ? $data['ticket'] : null;
$mb_messages = isset( $data['messages'] ) ? (array) $data['messages'] : array();
$mb_tickets  = isset( $data['tickets'] ) ? (array) $data['tickets'] : array();
$mb_faq      = isset( $data['faq_grouped'] ) ? (array) $data['faq_grouped'] : array();
$mb_sla      = (string) ( $data['sla_text'] ?? 'زمان پاسخ معمولاً بین ۱۰ دقیقه تا ۲ ساعت است.' );
?>
<div class="scr support">
	<div class="pad">
		<header class="hdr">
			<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
			<div class="day-head"><h1 class="h1">پشتیبانی</h1></div>
			<?php echo MB_UI::bell( (int) ( $data['unread'] ?? 0 ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</header>
		<?php echo MB_UI::pnote( $mb_sla, 'info', 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		<?php if ( $mb_ticket ) : ?>
			<section class="glass card">
				<h3 class="h3"><?php echo esc_html( (string) ( $mb_ticket['subject'] ?? 'درخواست' ) ); ?></h3>
				<div class="chat-thread" id="mb-ticket-thread">
					<?php foreach ( $mb_messages as $mb_m ) : ?>
						<?php $mb_mine = ( ! empty( $mb_m['is_user'] ) || ! empty( $mb_m['mine'] ) || 'user' === (string) ( $mb_m['who'] ?? '' ) ); ?>
						<div class="chat-bubble <?php echo $mb_mine ? 'mine' : 'staff'; ?>">
							<span class="chat-who"><?php echo esc_html( $mb_mine ? 'شما' : 'پشتیبانی' ); ?></span>
							<p class="body"><?php echo esc_html( (string) ( $mb_m['body'] ?? $mb_m['message'] ?? '' ) ); ?></p>
							<span class="small"><?php echo esc_html( (string) ( $mb_m['date_fa'] ?? $mb_m['date'] ?? '' ) ); ?></span>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="mb-form">
					<label class="fld"><span>پاسخ شما</span>
						<textarea id="mb-ticket-reply" rows="3" placeholder="پیامت را بنویس…"></textarea>
					</label>
					<div class="btn-col">
						<?php echo MB_UI::btn( 'ثبت پاسخ', 'gold wide', 'ticket-reply', array( 'icon' => 'msg', 'data' => array( 'id' => (int) ( $mb_ticket['id'] ?? 0 ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo MB_UI::btn( 'بستن درخواست', 'ghost wide', 'ticket-close', array( 'icon' => 'check', 'data' => array( 'id' => (int) ( $mb_ticket['id'] ?? 0 ) ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>
			</section>
		<?php else : ?>
			<section class="glass card">
				<h3 class="h3">درخواست تازه</h3>
				<div class="mb-form">
					<label class="fld"><span>عنوان</span>
						<input type="text" id="mb-ticket-subject" maxlength="90" placeholder="مثلاً مشکل در ذخیرهٔ ثبت روز">
					</label>
					<label class="fld"><span>توضیح</span>
						<textarea id="mb-ticket-body" rows="4" placeholder="کامل بنویس تا سریع‌تر جواب بگیری (دست‌کم ۱۵ نویسه)."></textarea>
					</label>
					<label class="fld"><span>موضوع</span>
						<select id="mb-ticket-topic">
							<option value="general">عمومی</option>
							<option value="technical">فنی و اپلیکیشن</option>
							<option value="billing">اشتراک و پرداخت</option>
							<option value="privacy">حریم خصوصی و همسر</option>
						</select>
					</label>
					<label class="fld"><span>اولویت</span>
						<select id="mb-ticket-priority">
							<option value="normal">عادی</option>
							<option value="high">زیاد</option>
						</select>
					</label>
					<label class="fld"><span>راه تماس (اختیاری)</span>
						<input type="text" id="mb-ticket-contact" dir="ltr" placeholder="ایمیل یا شماره موبایل">
					</label>
					<div class="btn-col">
						<?php echo MB_UI::btn( 'ثبت درخواست', 'gold wide', 'ticket-create', array( 'icon' => 'msg' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</div>
			</section>
			<section class="sec">
				<h2 class="h2">درخواست‌های من</h2>
				<?php if ( empty( $mb_tickets ) ) : ?>
					<?php echo MB_UI::pnote( 'هنوز درخواستی ثبت نکرده‌ای. اولین درخواستت پس از ثبت این‌جا دیده می‌شود.', 'info', 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else : ?>
					<div class="glass card list">
						<?php foreach ( $mb_tickets as $mb_t ) : ?>
							<?php
							echo MB_UI::lrow(
								(string) ( $mb_t['subject'] ?? 'درخواست' ),
								(string) ( $mb_t['status_label'] ?? '' ) . ' · ' . (string) ( $mb_t['date_fa'] ?? '' ),
								'msg',
								array( 'action' => 'goto', 'data' => array( 'route' => 'support/' . (int) ( $mb_t['id'] ?? 0 ) ) )
							); // phpcs:ignore WordPress.Security.EscapeOutput
							?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</section>
		<?php endif; ?>
		<section class="sec">
			<h2 class="h2">سوالات متداول</h2>
			<label class="fld"><span>جست‌وجو در سوال‌ها</span>
				<input type="search" id="faq-search" placeholder="یک کلمه بنویس…">
			</label>
			<?php if ( empty( $mb_faq ) ) : ?>
				<?php echo MB_UI::pnote( 'سوالات متداول در حال تکمیل است. اگر جوابت را پیدا نکردی، یک درخواست ثبت کن؛ معمولاً بین ۱۰ دقیقه تا ۲ ساعت پاسخ می‌دهیم.', 'info', 'info' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php else : ?>
				<?php foreach ( $mb_faq as $mb_cat => $mb_items ) : ?>
					<div class="accordion-group">
						<?php if ( is_string( $mb_cat ) && '' !== $mb_cat ) : ?>
							<h3 class="h3"><?php echo esc_html( $mb_cat ); ?></h3>
						<?php endif; ?>
						<?php foreach ( (array) $mb_items as $mb_f ) : ?>
							<div class="accordion-item">
								<button type="button" class="accordion-trigger" data-mb="accordion-toggle" aria-expanded="false">
									<span><?php echo esc_html( (string) ( $mb_f['question'] ?? $mb_f['q'] ?? $mb_f['title'] ?? '' ) ); ?></span>
								</button>
								<div class="accordion-body" hidden>
									<p class="body"><?php echo esc_html( (string) ( $mb_f['answer'] ?? $mb_f['a'] ?? $mb_f['content'] ?? '' ) ); ?></p>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</section>
		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'account' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>