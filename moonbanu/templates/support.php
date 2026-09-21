<?php
/**
 * #/support و #/support/{id} — پشتیبانی، FAQ و گفت‌وگوی تیکت (N8, N9).
 *
 * دو حالت:
 *   - بدون آرگومان: SLA + سوالات متداول + فهرست درخواست‌های من + فرم درخواست تازه.
 *   - #/support/{id}: سرصفحهٔ تیکت + رشتهٔ پیام‌ها + کادر پاسخ + بستن درخواست.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_faqs    = isset( $data['faq_grouped'] ) ? (array) $data['faq_grouped'] : array();
$mb_sla     = isset( $data['sla_text'] ) ? (string) $data['sla_text'] : 'زمان پاسخ معمولاً بین ۱۰ دقیقه تا ۲ ساعت است.';
$mb_ticket  = ( isset( $data['ticket'] ) && is_array( $data['ticket'] ) ) ? $data['ticket'] : null;
$mb_msgs    = isset( $data['messages'] ) ? (array) $data['messages'] : array();
$mb_tickets = isset( $data['tickets'] ) ? (array) $data['tickets'] : array();
$mb_topics  = MB_Support::topics();

/** نگاشت وضعیت تیکت به رنگ چیپ. */
$mb_chip_variant = static function ( string $status ): string {
	switch ( $status ) {
		case 'answered':
			return 'fertile';
		case 'pending':
			return 'gold';
		case 'closed':
			return 'dark';
		default:
			return 'norm';
	}
};
?>
<div class="scr support">
	<div class="pad">

		<?php if ( null !== $mb_ticket ) : ?>

			<?php /* ---------------------- گفت‌وگوی یک درخواست ---------------------- */ ?>
			<header class="hdr">
				<button type="button" class="ic n" data-mb="goto" data-route="support" aria-label="بازگشت به فهرست درخواست‌ها"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<div class="day-head">
					<h1 class="h1"><?php echo esc_html( (string) $mb_ticket['subject'] ); ?></h1>
					<p class="small"><?php echo esc_html( 'کد ' . MB_UI::num( (int) $mb_ticket['id'] ) . ' · ' . (string) $mb_ticket['created_fa'] ); ?></p>
				</div>
				<?php echo MB_UI::chip( (string) $mb_ticket['status_fa'], $mb_chip_variant( (string) $mb_ticket['status'] ), 'msg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			</header>

			<section class="glass card ticket-meta">
				<div class="chip-row">
					<?php echo MB_UI::chip( (string) $mb_ticket['topic'], 'dark', 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php
					$mb_prio = MB_Support::priorities();
					echo MB_UI::chip( 'اولویت: ' . ( $mb_prio[ (string) $mb_ticket['priority'] ] ?? 'معمولی' ), 'dark', 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
					<?php echo MB_UI::chip( 'آخرین به‌روزرسانی: ' . (string) $mb_ticket['updated_fa'], 'dark', 'cal' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>

			<div class="chat-thread" id="mb-ticket-thread">
				<?php if ( empty( $mb_msgs ) ) : ?>
					<?php echo MB_UI::pnote( 'پیامی در این درخواست ثبت نشده است.', 'info', 'msg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php else : ?>
					<?php foreach ( $mb_msgs as $mb_msg ) : ?>
						<?php $mb_staff = 'staff' === (string) ( $mb_msg['role'] ?? 'user' ); ?>
						<article class="chat-bubble <?php echo $mb_staff ? 'staff' : 'mine'; ?>">
							<div class="chat-who">
								<?php echo MB_UI::icon( $mb_staff ? 'shield' : 'user', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
								<span><?php echo esc_html( $mb_staff ? 'تیم پشتیبانی ماه‌بانو' : 'تو' ); ?></span>
								<time><?php echo esc_html( (string) ( $mb_msg['time_fa'] ?? '' ) ); ?></time>
							</div>
							<div class="chat-body"><?php echo esc_html( (string) ( $mb_msg['body'] ?? '' ) ); ?></div>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>

			<?php if ( 'closed' === (string) $mb_ticket['status'] ) : ?>
				<section class="glass card">
					<?php echo MB_UI::pnote( 'این درخواست بسته شده است. اگر موضوع تازه‌ای داری، یک درخواست جدید ثبت کن.', 'info', 'check' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php echo MB_UI::btn( 'ثبت درخواست تازه', 'gold wide', 'goto', array( 'icon' => 'plus', 'data' => array( 'route' => 'support' ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</section>
			<?php else : ?>
				<section class="glass card">
					<h2 class="h2">پاسخ تو</h2>
					<label class="fld">
						<span>پیام</span>
						<textarea id="mb-ticket-reply" rows="4" placeholder="توضیح تازه، نتیجهٔ راهنمایی یا پرسش بعدی…"></textarea>
					</label>
					<div class="btn-col">
						<?php echo MB_UI::btn( 'ارسال پاسخ', 'gold wide', 'ticket-reply', array( 'icon' => 'send', 'data' => array( 'id' => (int) $mb_ticket['id'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<?php echo MB_UI::btn( 'مشکلم حل شد، ببند', 'ghost wide', 'ticket-close', array( 'icon' => 'check', 'data' => array( 'id' => (int) $mb_ticket['id'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
				</section>
			<?php endif; ?>

			<section class="sec">
				<div class="glass card">
					<?php echo MB_UI::support_contact(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>

		<?php else : ?>

			<?php /* ------------------------- فهرست و FAQ ------------------------- */ ?>
			<header class="hdr">
				<button type="button" class="ic n" data-mb="back" aria-label="بازگشت"><?php echo MB_UI::icon( 'next', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
				<h1 class="h1">پشتیبانی</h1>
				<span class="ic n" aria-hidden="true"><?php echo MB_UI::icon( 'msg', 20, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			</header>

			<!-- SLA Message (N8) -->
			<section class="glass card info-box">
				<p class="small"><?php echo esc_html( $mb_sla ); ?></p>
			</section>

			<!-- درخواست‌های من -->
			<section class="sec">
				<h2 class="h2">درخواست‌های من</h2>
				<div class="glass card list">
					<?php if ( empty( $mb_tickets ) ) : ?>
						<?php echo MB_UI::pnote( 'هنوز درخواستی ثبت نکرده‌ای. از فرم پایین همین صفحه شروع کن.', 'info', 'msg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<?php else : ?>
						<?php foreach ( $mb_tickets as $mb_row ) : ?>
							<?php
							$mb_sub = (string) $mb_row['topic'] . ' · ' . (string) $mb_row['status_fa'] . ' · ' . (string) $mb_row['updated_fa'];
							echo MB_UI::lrow(
								(string) $mb_row['subject'],
								$mb_sub,
								! empty( $mb_row['unread'] ) ? 'bell' : 'msg',
								array(
									'action' => 'goto',
									'data'   => array( 'route' => 'support/' . (int) $mb_row['id'] ),
									'right'  => ! empty( $mb_row['unread'] )
										? '<span class="badge">' . esc_html( MB_UI::num( 1 ) ) . '</span>'
										: MB_UI::chip( (string) $mb_row['status_fa'], $mb_chip_variant( (string) $mb_row['status'] ) ),
								)
							); // phpcs:ignore WordPress.Security.EscapeOutput
							?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>
			</section>

			<!-- FAQ Search Bar -->
			<div class="search-bar">
				<input type="text" id="faq-search" placeholder="جستجو در سوالات متداول…" data-mb="faq-search" autocomplete="off">
			</div>

			<!-- FAQ Accordion (N9) -->
			<section class="glass card accordion-wrapper">
				<?php if ( ! empty( $mb_faqs ) ) : ?>
					<?php foreach ( $mb_faqs as $mb_cat => $mb_items ) : ?>
						<div class="accordion-group">
							<h3 class="accordion-title"><?php echo esc_html( (string) $mb_cat ); ?></h3>
							<?php foreach ( (array) $mb_items as $mb_faq ) : ?>
								<div class="accordion-item">
									<button type="button" class="accordion-trigger" data-mb="accordion-toggle" aria-expanded="false">
										<span><?php echo esc_html( (string) $mb_faq['question'] ); ?></span>
										<?php echo MB_UI::icon( 'chevron', 18, 1.5 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									</button>
									<div class="accordion-body" hidden>
										<p><?php echo esc_html( (string) $mb_faq['answer'] ); ?></p>
										<?php if ( ! empty( $mb_faq['source'] ) ) : ?>
											<p class="source"><?php echo esc_html( 'منبع: ' . (string) $mb_faq['source'] ); ?></p>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endforeach; ?>
				<?php else : ?>
					<?php echo MB_UI::pnote( 'سوالات متداول هنوز آماده نشدند.', 'info', 'msg' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
			</section>

			<!-- فرم درخواست تازه -->
			<section class="glass card">
				<h2 class="h2">نیاز به کمک بیشتر؟</h2>
				<p class="small">پاسخ را همین‌جا، داخل اپ می‌بینی؛ به ایمیل هم خبر می‌دهیم.</p>

				<label class="fld">
					<span>موضوع درخواست</span>
					<input type="text" id="mb-ticket-subject" name="subject" placeholder="مثلاً پرداخت انجام شد ولی اشتراک فعال نشد" maxlength="120">
				</label>

				<label class="fld">
					<span>دسته</span>
					<select id="mb-ticket-topic" name="topic">
						<?php foreach ( $mb_topics as $mb_key => $mb_label ) : ?>
							<option value="<?php echo esc_attr( (string) $mb_key ); ?>"<?php selected( 'general', (string) $mb_key ); ?>><?php echo esc_html( (string) $mb_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<label class="fld">
					<span>اولویت</span>
					<select id="mb-ticket-priority" name="priority">
						<?php foreach ( MB_Support::priorities() as $mb_key => $mb_label ) : ?>
							<option value="<?php echo esc_attr( (string) $mb_key ); ?>"<?php selected( 'normal', (string) $mb_key ); ?>><?php echo esc_html( (string) $mb_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>

				<label class="fld">
					<span>راه تماس (اختیاری)</span>
					<input type="text" id="mb-ticket-contact" name="contact" dir="ltr" placeholder="ایمیل یا موبایل، اگر با حسابت متفاوت است">
				</label>

				<label class="fld">
					<span>توضیح</span>
					<textarea id="mb-ticket-body" name="body" rows="5" placeholder="توضیح مشکل یا درخواست… (دست‌کم ۱۵ نویسه)"></textarea>
				</label>

				<div class="btn-col">
					<?php echo MB_UI::btn( 'ثبت درخواست', 'gold wide', 'ticket-create', array( 'icon' => 'send' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>

			<section class="sec">
				<div class="glass card">
					<?php echo MB_UI::support_contact(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
			</section>

		<?php endif; ?>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'support', MB_Privacy::is_partner( (int) ( $data['user_id'] ?? 0 ) ) ? 'partner' : 'woman' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
