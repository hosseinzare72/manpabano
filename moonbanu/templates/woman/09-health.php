<?php
/**
 * #/health — مرکز سلامت.
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_cats  = (array) $data['cats'];
$mb_icons = array(
	'period'        => 'drop',
	'contraception' => 'shield',
	'nutrition'     => 'cup',
	'mental'        => 'moon',
	'warning'       => 'alert',
	'pregnancy'     => 'heart',
);
$mb_feat = $data['featured'];
?>
<div class="scr health">
	<div class="pad">

		<header class="hdr">
			<div>
				<h1 class="h1">مرکز سلامت</h1>
				<p class="small">راهنماهای کوتاه و علمی</p>
			</div>
			<button type="button" class="ic n" data-mb="health-search" aria-label="جست‌وجو"><?php echo MB_UI::icon( 'search', 17, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?></button>
		</header>

		<div class="cal-search" id="mb-health-search" hidden>
			<input type="text" id="mb-health-q" placeholder="جست‌وجوی راهنما…">
			<button type="button" class="btn dark sm" data-mb="health-query">جست‌وجو</button>
		</div>

		<?php if ( $mb_feat ) : ?>
			<section class="glass card feat-week">
				<div class="feat-week-head">
					<h2 class="h2"><?php echo esc_html( (string) $mb_feat['title'] ); ?></h2>
					<?php echo MB_UI::chip( MB_UI::num( max( 1, (int) $mb_feat['minutes'] ) ) . ' دقیقه مطالعه', 'dark', 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<p class="body"><?php echo esc_html( (string) $mb_feat['excerpt'] ); ?></p>
				<div class="btn-row">
					<?php
					echo MB_UI::btn( 'مطالعهٔ راهنما', 'gold sm', 'article-open', array( 'data' => array( 'id' => (int) $mb_feat['id'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					echo MB_UI::btn( 'ذخیره', 'ghost sm', 'article-save', array( 'icon' => 'bookmark', 'data' => array( 'id' => (int) $mb_feat['id'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					?>
				</div>
			</section>
		<?php endif; ?>

		<section class="sec">
			<h2 class="h2">دسته‌ها</h2>
			<div class="cat-grid">
				<?php foreach ( $mb_cats as $mb_slug => $mb_label ) : ?>
					<button type="button" class="glass card cat" data-mb="goto" data-route="health/<?php echo esc_attr( $mb_slug ); ?>">
						<?php echo MB_UI::icon( $mb_icons[ $mb_slug ] ?? 'book', 18, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
						<span class="h3"><?php echo esc_html( $mb_label ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="sec" id="mb-article-list">
			<h2 class="h2">راهنماها</h2>
			<?php if ( empty( $data['articles'] ) ) : ?>
				<?php echo MB_UI::pnote( 'هنوز راهنمایی منتشر نشده است. مدیر سایت می‌تواند از پنل «ماه‌بانو → راهنماهای سلامت» راهنما اضافه کند.', 'warn', 'book' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php else : ?>
				<div class="glass card list">
					<?php foreach ( (array) $data['articles'] as $mb_a ) : ?>
						<?php
						echo MB_UI::lrow(
							(string) $mb_a['title'],
							MB_UI::num( max( 1, (int) $mb_a['minutes'] ) ) . ' دقیقه · ' . wp_trim_words( (string) $mb_a['excerpt'], 12 ),
							'book',
							array( 'action' => 'article-open', 'data' => array( 'id' => (int) $mb_a['id'] ) )
						); // phpcs:ignore WordPress.Security.EscapeOutput
						?>
					<?php endforeach; ?>
				</div>
				<?php if ( empty( $data['can_full'] ) ) : ?>
					<?php echo MB_UI::pro_gate( 'مرکز سلامت کامل', 'با نسخهٔ پیشرفته همه راهنماها، ذخیره‌سازی و پرسش از متخصص باز می‌شود.' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				<?php endif; ?>
			<?php endif; ?>
		</section>

		<div class="glass card article-view" id="mb-article-view" hidden></div>

		<section class="glass card warn-card">
			<h2 class="h2">کی باید به پزشک مراجعه کنی؟</h2>
			<ul class="bullets">
				<?php foreach ( (array) $data['warnings'] as $mb_w ) : ?>
					<li><?php echo MB_UI::icon( 'alert', 13, 1.9 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span><?php echo esc_html( $mb_w ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		</section>

		<section class="sec">
			<?php if ( empty( $data['can_ask'] ) ) : ?>
				<?php echo MB_UI::pro_gate( 'پرسش از متخصص زنان', 'پرسش محرمانه‌ات را بفرست و پاسخ را در همین بخش ببین.' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
			<?php else : ?>
				<div class="glass card">
					<?php echo MB_UI::lrow( 'پرسش از متخصص زنان', 'محرمانه و فقط برای تیم پاسخ‌دهنده', 'msg', array( 'action' => 'ask-toggle' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					<div id="mb-ask-form" hidden>
						<label class="fld"><span>پرسش تو</span><textarea id="mb-question" rows="3" maxlength="800" placeholder="مثلاً: چرخه‌ام سه ماه نامنظم شده، چه زمانی باید معاینه شوم؟"></textarea></label>
						<label class="fld">
							<span>دستهٔ پرسش</span>
							<select id="mb-question-cat">
								<?php foreach ( MB_Plugin::categories() as $mb_cs => $mb_cl ) : ?>
									<option value="<?php echo esc_attr( (string) $mb_cs ); ?>"><?php echo esc_html( (string) $mb_cl ); ?></option>
								<?php endforeach; ?>
							</select>
						</label>
						<label class="chk">
							<input type="checkbox" id="mb-question-anon">
							<span>پس از پاسخ، به‌صورت ناشناس در انجمن منتشر شود</span>
						</label>
						<p class="tiny muted">در انتشار ناشناس هیچ نام، ایمیل، شماره یا شناسهٔ کاربری همراه پرسش نمی‌رود و انتشار فقط پس از تأیید مدیر انجام می‌شود.</p>
						<?php echo MB_UI::btn( 'ارسال محرمانه', 'gold sm', 'ask-send' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</div>
					<?php foreach ( (array) $data['questions'] as $mb_q ) : ?>
						<div class="qa">
							<p class="h3"><?php echo esc_html( wp_trim_words( (string) $mb_q['body'], 14 ) ); ?></p>
							<?php if ( 'answered' === $mb_q['status'] ) : ?>
								<p class="body"><?php echo esc_html( (string) $mb_q['answer'] ); ?></p>
							<?php else : ?>
								<?php echo MB_UI::chip( 'در انتظار پاسخ', 'dark', 'clock' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>

		<section class="sec">
			<h2 class="h2">بیشتر بدان</h2>
			<div class="shortcut-grid">
				<a class="glass card shortcut" href="#/quizzes"><?php echo MB_UI::icon( 'quiz', 19, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>تست‌های خودشناسی</span></a>
				<a class="glass card shortcut" href="#/community"><?php echo MB_UI::icon( 'people', 19, 1.8 ); // phpcs:ignore WordPress.Security.EscapeOutput ?><span>انجمن ناشناس</span></a>
			</div>
		</section>

		<?php echo MB_UI::disclaimer(); // phpcs:ignore WordPress.Security.EscapeOutput ?>
	</div>
	<?php echo MB_UI::tabbar( 'health' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
</div>
