<?php
/**
 * پنل مدیریت ماه‌بانو (یک منوی اصلی + ۶ تب).
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Admin {

	/** تنظیم‌هایی که خام در پایگاه‌داده ذخیره نمی‌شوند. */
	public const SENSITIVE_KEYS = array(
		'sms_api_key',
		'sms_username',
		'zibal_merchant',
		'zp_merchant',
	);

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_mb_save', array( __CLASS__, 'handle_post' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post_mb_article', array( __CLASS__, 'save_article_meta' ) );
	}

	/** آیا این کلید حساس مقدار قابل‌استفاده‌ای دارد؟ (برای نمایش placeholder) */
	private static function secret_is_set( array $s, string $key ): bool {
		$raw = (string) ( $s[ $key ] ?? '' );
		if ( '' === $raw ) {
			return false;
		}
		if ( 0 === strpos( $raw, MB_Crypto::PREFIX ) ) {
			return '' !== MB_Crypto::decrypt( $raw );
		}
		return true;
	}

	/**
	 * ذخیرهٔ کلیدهای حساس به‌صورت رمزنگاری‌شده.
	 *
	 * فیلدها از نوع password و همیشه خالی رندر می‌شوند، پس «خالی» یعنی «تغییر
	 * نده» و مقدار قبلی حفظ می‌شود. حذف عمدی فقط با تیک mb_clear ممکن است. اگر
	 * رمزنگاری شکست بخورد مقدار قبلی دست‌نخورده می‌ماند تا کلید خام جایش نرود.
	 */
	private static function persist_sensitive_settings( array $s, array $in, array $current, array $clear ): array {
		foreach ( self::SENSITIVE_KEYS as $key ) {
			if ( isset( $clear[ $key ] ) ) {
				$s[ $key ] = '';
				continue;
			}
			if ( ! array_key_exists( $key, $in ) ) {
				$s[ $key ] = (string) ( $current[ $key ] ?? '' );
				continue;
			}
			$plain = trim( sanitize_text_field( (string) $in[ $key ] ) );
			if ( '' === $plain ) {
				$s[ $key ] = (string) ( $current[ $key ] ?? '' );
				continue;
			}
			if ( 0 === strpos( $plain, MB_Crypto::PREFIX ) ) {
				$s[ $key ] = $plain;
				continue;
			}
			$enc = MB_Crypto::encrypt( $plain );
			if ( '' === $enc ) {
				$s[ $key ] = (string) ( $current[ $key ] ?? '' );
				MB_Plugin::log_security( 'settings_encrypt_failed', array( 'key' => $key ) );
				continue;
			}
			$s[ $key ] = $enc;
		}
		return $s;
	}

	public static function default_settings(): array {
		return array(
			'price'            => 250000,
			'price_month'      => 250000,
			'price_quarter'    => 500000,
			'price_halfyear'   => 1000000,
			'app_page'         => 0,
			'cycle_len'        => 28,
			'period_len'       => 5,
			'disclaimer'       => MB_DISCLAIMER,
			'gateway_provider' => 'zibal',
			'zp_merchant'      => '',
			'zp_sandbox'       => 0,
			'zibal_merchant'   => '',
			'gateway_currency' => 'IRR',
			'sms_provider'     => 'melipayamak',
			'sms_api_key'      => '',
			'sms_sender'       => '',
			'sms_username'     => '',
			'admin_email'      => get_option( 'admin_email' ),
			'allowed_domains'  => '',
			'delete_data'      => 0,
			'brand_logo_url'   => '',
			'background_image_url' => '',
			'color_bg'        => '#05030A',
			'color_surface'   => '#150E22',
			'color_text'      => '#F8F3F9',
			'color_muted'     => '#ABA0BC',
			'color_accent'    => '#D9B45B',
			'color_period'    => '#EA3F63',
			'color_fertile'   => '#12A594',
			'color_ovulation' => '#8B6BF5',
			'color_pms'       => '#EE9A2E',
			'text_overrides'  => '',

			// احراز هویت درون‌اپ و جداسازی.
			'inapp_auth'       => 1,
			'allow_registration' => 1,
			// M6 — ورود با کد یک‌بارمصرف (پیامک، با فال‌بک ایمیل).
			'otp_login'        => 1,
			'isolate_app'      => 1,
			'isolate_allow'    => '',

			// PWA اختصاصی.
			'pwa_name'         => 'ماه‌بانو',
			'pwa_short_name'   => 'ماه‌بانو',
			'pwa_theme_color'  => '#05030A',
			'pwa_bg_color'     => '#05030A',

			// برند و پشتیبانی.
			'brand_name'       => 'ماه‌بانو',
			'support_email'    => 'hosseinzarec@gmail.com',
			'support_phone'    => '09351063668',
			'support_hours'    => 'شنبه تا پنجشنبه، ۹ تا ۱۸',
			'dev_credit'       => 'طراحی توسط حسین زارع',
			'dev_url'          => 'https://kafezare.ir/zsa-studio/',
			'dev_studio'       => 'ZSA Studio',
			'terms_url'        => '',
			'privacy_url'      => '',
		);
	}

	public static function menu(): void {
		add_menu_page( 'ماه‌بانو', 'ماه‌بانو', 'manage_options', 'moonbanu', array( __CLASS__, 'page' ), 'dashicons-heart', 30 );
		add_submenu_page( 'moonbanu', 'تنظیمات ماه‌بانو', 'تنظیمات', 'manage_options', 'moonbanu', array( __CLASS__, 'page' ) );
		add_submenu_page( 'moonbanu', 'راهنماهای سلامت', 'راهنماهای سلامت', 'edit_posts', 'edit.php?post_type=mb_article' );
		add_submenu_page( 'moonbanu', 'دسته‌های راهنما', 'دسته‌های راهنما', 'manage_categories', 'edit-tags.php?taxonomy=mb_cat&post_type=mb_article' );
	}

	private static function tabs(): array {
		$open = class_exists( 'MB_Support' ) ? MB_Support::unread_for_admin() : 0;
		return array(
			'general'  => 'عمومی',
			'users'    => 'کاربران',
			'sub'      => 'اشتراک و درگاه',
			'support'  => 'پشتیبانی' . ( $open > 0 ? ' (' . MB_Jalali::fa_num( $open ) . ')' : '' ),
			'app'      => 'اپ و PWA',
			'sms'      => 'پیامک',
			'community' => 'انجمن و کوییز',
			'content'  => 'محتوا',
			'security' => 'دعوت‌ها و امنیت',
			'status'   => 'وضعیت',
		);
	}

	/* --------------------------------------------------------------------- */
	/* ذخیره                                                                 */
	/* --------------------------------------------------------------------- */

	public static function handle_post(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}
		check_admin_referer( 'mb_admin_save' );

		$tab      = isset( $_POST['mb_tab'] ) ? sanitize_key( wp_unslash( $_POST['mb_tab'] ) ) : 'general';
		$s        = wp_parse_args( (array) get_option( 'mb_settings', array() ), self::default_settings() );
		$previous = $s;
		$in       = isset( $_POST['mb'] ) && is_array( $_POST['mb'] ) ? wp_unslash( $_POST['mb'] ) : array();
		$clear    = array();
		if ( isset( $_POST['mb_clear'] ) && is_array( $_POST['mb_clear'] ) ) {
			$clear = array_fill_keys( array_map( 'sanitize_key', array_keys( (array) wp_unslash( $_POST['mb_clear'] ) ) ), true );
		}

		$map = array(
			'price'            => 'absint',
			'price_month'      => 'absint',
			'price_quarter'    => 'absint',
			'price_halfyear'   => 'absint',
			'app_page'         => 'absint',
			'cycle_len'        => 'absint',
			'period_len'       => 'absint',
			'gateway_provider' => 'sanitize_key',
			'zp_sandbox'       => 'absint',
			'delete_data'      => 'absint',
			'disclaimer'       => 'sanitize_text_field',
			'zp_merchant'      => 'sanitize_text_field',
			'zibal_merchant'   => 'sanitize_text_field',
			'gateway_currency' => 'sanitize_text_field',
			'sms_provider'     => 'sanitize_key',
			'sms_api_key'      => 'sanitize_text_field',
			'sms_sender'       => 'sanitize_text_field',
			'sms_username'     => 'sanitize_text_field',
			'admin_email'      => 'sanitize_email',
			'allowed_domains'  => 'sanitize_textarea_field',
			'brand_logo_url'   => 'esc_url_raw',
			'background_image_url' => 'esc_url_raw',
			'text_overrides'  => 'sanitize_textarea_field',
			'inapp_auth'      => 'absint',
			'allow_registration' => 'absint',
			'otp_login'       => 'absint',
			'isolate_app'     => 'absint',
			'isolate_allow'   => 'sanitize_textarea_field',
			'pwa_name'        => 'sanitize_text_field',
			'pwa_short_name'  => 'sanitize_text_field',
			'pwa_theme_color' => 'sanitize_text_field',
			'pwa_bg_color'    => 'sanitize_text_field',
			'brand_name'      => 'sanitize_text_field',
			'support_email'   => 'sanitize_email',
			'support_phone'   => 'sanitize_text_field',
			'support_hours'   => 'sanitize_text_field',
			'dev_credit'      => 'sanitize_text_field',
			'dev_url'         => 'esc_url_raw',
			'dev_studio'      => 'sanitize_text_field',
			'terms_url'       => 'esc_url_raw',
			'privacy_url'     => 'esc_url_raw',
		);
		// چک‌باکس خالی فقط وقتی صفر می‌شود که فرم همان تب ارسال شده باشد؛
		// وگرنه ذخیره تب «پیامک» تنظیمات سندباکس درگاه را خاموش می‌کرد.
		$checkbox_tabs = array(
			'zp_sandbox'         => 'sub',
			'delete_data'        => 'general',
			'inapp_auth'         => 'app',
			'allow_registration' => 'app',
			'otp_login'          => 'app',
			'isolate_app'        => 'app',
		);
		foreach ( $map as $key => $cb ) {
			if ( array_key_exists( $key, $in ) ) {
				$s[ $key ] = call_user_func( $cb, $in[ $key ] );
			} elseif ( isset( $checkbox_tabs[ $key ] ) && $checkbox_tabs[ $key ] === $tab && ! isset( $_POST['mb_action'] ) ) {
				$s[ $key ] = 0;
			}
		}
		$s['price']      = max( 1000, (int) $s['price'] );
		$s['cycle_len']  = max( 21, min( 35, (int) $s['cycle_len'] ) );
		$s['period_len'] = max( 2, min( 7, (int) $s['period_len'] ) );
		$s['price_month'] = max( 1000, (int) $s['price_month'] );
		$s['price_quarter'] = max( 1000, (int) $s['price_quarter'] );
		$s['price_halfyear'] = max( 1000, (int) $s['price_halfyear'] );
		$s['price'] = $s['price_month'];
		foreach ( array( 'color_bg', 'color_surface', 'color_text', 'color_muted', 'color_accent', 'color_period', 'color_fertile', 'color_ovulation', 'color_pms', 'pwa_theme_color', 'pwa_bg_color' ) as $color_key ) {
			$color = sanitize_hex_color( (string) $s[ $color_key ] );
			$s[ $color_key ] = $color ? $color : self::default_settings()[ $color_key ];
		}
		$s['gateway_provider'] = in_array( $s['gateway_provider'], array( 'zibal', 'zarinpal' ), true ) ? $s['gateway_provider'] : 'zibal';
		$s['gateway_currency'] = 'IRT' === strtoupper( (string) $s['gateway_currency'] ) ? 'IRT' : 'IRR';
		$s = self::persist_sensitive_settings( $s, (array) $in, $previous, $clear );
		update_option( 'mb_settings', $s );

		// اقدام‌های موردی.
		$action = isset( $_POST['mb_action'] ) ? sanitize_key( wp_unslash( $_POST['mb_action'] ) ) : '';
		$notice = 'saved';

		if ( 'coupon_add' === $action ) {
			global $wpdb;
			$code = strtoupper( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ?? '' ) ) );
			$code = preg_replace( '/[^A-Z0-9_-]/', '', MB_Jalali::en_num( $code ) );
			if ( '' !== $code ) {
				$percent = max( 0, min( 100, (int) ( $_POST['coupon_percent'] ?? 0 ) ) );
				$fixed   = max( 0, (int) ( $_POST['coupon_amount'] ?? 0 ) );
				if ( 0 === $percent && 0 === $fixed ) {
					$percent = 10;
				}
				$existing = (array) $wpdb->get_row( $wpdb->prepare( 'SELECT uses FROM ' . MB_DB::t( 'coupons' ) . ' WHERE code = %s', $code ), ARRAY_A );
				$wpdb->replace(
					MB_DB::t( 'coupons' ),
					array(
						'code'       => $code,
						'percent'    => $percent,
						'amount_off' => $fixed,
						'min_amount' => max( 0, (int) ( $_POST['coupon_min'] ?? 0 ) ),
						'active'     => isset( $_POST['coupon_active'] ) ? 1 : 0,
						'note'       => sanitize_text_field( wp_unslash( $_POST['coupon_note'] ?? '' ) ),
						'expires_at' => '' !== ( $_POST['coupon_expires'] ?? '' ) ? gmdate( 'Y-m-d H:i:s', (int) strtotime( sanitize_text_field( wp_unslash( $_POST['coupon_expires'] ) ) . ' 23:59:59' ) ) : null,
						'max_uses'   => max( 0, (int) ( $_POST['coupon_max'] ?? 0 ) ),
						'uses'       => isset( $existing['uses'] ) ? (int) $existing['uses'] : 0,
					)
				);
				$notice = 'coupon';
			}
		} elseif ( 'coupon_toggle' === $action ) {
			global $wpdb;
			$code = strtoupper( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ?? '' ) ) );
			if ( '' !== $code ) {
				$wpdb->query( $wpdb->prepare( 'UPDATE ' . MB_DB::t( 'coupons' ) . ' SET active = 1 - active WHERE code = %s', $code ) );
				$notice = 'coupon_toggled';
			}
		} elseif ( 0 === strpos( $action, 'user_' ) ) {
			$notice = MB_Admin_Users::handle_action( $action, wp_unslash( $_POST ) );
			if ( '' === $notice ) {
				$notice = 'saved';
			}
		} elseif ( 'ticket_reply' === $action ) {
			$tid  = absint( $_POST['ticket_id'] ?? 0 );
			$body = sanitize_textarea_field( wp_unslash( $_POST['ticket_body'] ?? '' ) );
			$stat = sanitize_key( wp_unslash( $_POST['ticket_status'] ?? 'answered' ) );
			$res  = MB_Support::staff_reply( $tid, $body, $stat );
			$notice = is_wp_error( $res ) ? 'ticket_error' : 'ticket_replied';
		} elseif ( 'ticket_status' === $action ) {
			MB_Support::set_status( absint( $_POST['ticket_id'] ?? 0 ), sanitize_key( wp_unslash( $_POST['ticket_status'] ?? 'closed' ) ) );
			$notice = 'ticket_status';
		} elseif ( 'ticket_delete' === $action ) {
			MB_Support::delete( absint( $_POST['ticket_id'] ?? 0 ) );
			$notice = 'ticket_deleted';
		} elseif ( 'coupon_del' === $action ) {
			global $wpdb;
			$wpdb->delete( MB_DB::t( 'coupons' ), array( 'code' => strtoupper( sanitize_text_field( wp_unslash( $_POST['coupon_code'] ?? '' ) ) ) ), array( '%s' ) );
			$notice = 'coupon_deleted';
		} elseif ( 'sub_status' === $action ) {
			MB_Subscription::set_status( (int) ( $_POST['sub_id'] ?? 0 ), sanitize_key( wp_unslash( $_POST['sub_status'] ?? 'expired' ) ) );
			$notice = 'sub';
		} elseif ( 'sub_grant' === $action ) {
			$user = get_user_by( 'email', sanitize_email( wp_unslash( $_POST['grant_email'] ?? '' ) ) );
			if ( $user ) {
				MB_Subscription::grant_manual( (int) $user->ID, max( 1, (int) ( $_POST['grant_days'] ?? 30 ) ) );
				$notice = 'granted';
			} else {
				$notice = 'nouser';
			}
		} elseif ( 'clear_safe' === $action ) {
			MB_License::clear_safe_mode();
			$notice = 'safe_cleared';
		} elseif ( 'rebuild_manifest' === $action ) {
			MB_License::rebuild_manifest();
			$notice = 'manifest';
		} elseif ( 'answer_question' === $action ) {
			global $wpdb;
			$qid    = (int) ( $_POST['question_id'] ?? 0 );
			$answer = sanitize_textarea_field( wp_unslash( $_POST['answer'] ?? '' ) );
			if ( $qid && '' !== $answer ) {
				$wpdb->update( MB_DB::t( 'questions' ), array( 'answer' => $answer, 'status' => 'answered' ), array( 'id' => $qid ), array( '%s', '%s' ), array( '%d' ) );
				// M7 — اگر کاربر انتشار ناشناس را پذیرفته بود، پاسخ روی رکورد انجمن هم
				// می‌نشیند. انتشار واقعی همچنان نیاز به تأیید در تب «انجمن و کوییز» دارد.
				MB_DB::publish_question_anonymously( $qid, $answer );
				$uid = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM ' . MB_DB::t( 'questions' ) . ' WHERE id = %d', $qid ) );
				if ( $uid ) {
					MB_DB::add_notification( $uid, 'question_answered', array( 'title' => 'پاسخ پرسش شما آمد', 'body' => 'در بخش مرکز سلامت ببین.' ) );
				}
				$notice = 'answered';
			}
		}

		$args = array( 'page' => 'moonbanu', 'tab' => $tab, 'mb_notice' => $notice );
		if ( 'users' === $tab && ! empty( $_POST['user_id'] ) && 'user_delete' !== $action ) {
			$args['user'] = absint( $_POST['user_id'] );
		}
		if ( 'support' === $tab && ! empty( $_POST['ticket_id'] ) && 'ticket_delete' !== $action ) {
			$args['ticket'] = absint( $_POST['ticket_id'] );
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	/* --------------------------------------------------------------------- */
	/* صفحه                                                                  */
	/* --------------------------------------------------------------------- */

	public static function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		$tab = array_key_exists( $tab, self::tabs() ) ? $tab : 'general';
		$s   = wp_parse_args( (array) get_option( 'mb_settings', array() ), self::default_settings() );

		echo '<div class="wrap" dir="rtl"><h1>ماه‌بانو</h1>';

		if ( isset( $_GET['mb_notice'] ) ) {
			$notices = array(
				'saved'          => 'تنظیمات ذخیره شد.',
				'coupon'         => 'کوپن ذخیره شد.',
				'coupon_deleted' => 'کوپن حذف شد.',
				'sub'            => 'وضعیت اشتراک تغییر کرد.',
				'granted'        => 'اشتراک دستی فعال شد.',
				'nouser'         => 'کاربری با این ایمیل پیدا نشد.',
				'safe_cleared'   => 'حالت ایمن پاک شد و manifest بازسازی شد.',
				'manifest'       => 'manifest بازسازی شد.',
				'answered'       => 'پاسخ ثبت شد.',
				'coupon_toggled' => 'وضعیت کد تخفیف عوض شد.',
				'extended'       => 'اشتراک تمدید شد.',
				'revoked'        => 'اشتراک پایان یافت.',
				'sub_deleted'    => 'ردیف اشتراک حذف شد.',
				'nosub'          => 'برای این کاربر اشتراکی ثبت نشده است.',
				'role'           => 'نقش کاربر ذخیره شد.',
				'onboarding_reset' => 'تنظیم چرخهٔ کاربر بازنشانی شد.',
				'reset_sent'     => 'کد بازیابی برای کاربر فرستاده شد.',
				'purged'         => 'دادهٔ اپ کاربر پاک شد.',
				'user_deleted'   => 'حساب کاربر حذف شد.',
				'nodelete'       => 'این حساب قابل حذف نیست.',
				'ticket_replied' => 'پاسخ پشتیبانی فرستاده شد.',
				'ticket_status'  => 'وضعیت درخواست عوض شد.',
				'ticket_deleted' => 'درخواست حذف شد.',
				'ticket_error'   => 'پاسخ ثبت نشد.',
			);
			$key = sanitize_key( wp_unslash( $_GET['mb_notice'] ) );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notices[ $key ] ?? 'انجام شد.' ) . '</p></div>';
		}

		echo '<h2 class="nav-tab-wrapper">';
		foreach ( self::tabs() as $key => $label ) {
			printf(
				'<a class="nav-tab %s" href="%s">%s</a>',
				$key === $tab ? 'nav-tab-active' : '',
				esc_url( add_query_arg( array( 'page' => 'moonbanu', 'tab' => $key ), admin_url( 'admin.php' ) ) ),
				esc_html( $label )
			);
		}
		echo '</h2><div style="max-width:980px">';

		switch ( $tab ) {
			case 'users':
				MB_Admin_Users::render();
				break;
			case 'support':
				self::tab_support();
				break;
			case 'app':
				self::tab_app( $s );
				break;
			case 'sub':
				self::tab_sub( $s );
				break;
			case 'community':
				self::tab_community();
				break;

			case 'sms':
				self::tab_sms( $s );
				break;
			case 'content':
				self::tab_content();
				break;
			case 'security':
				self::tab_security( $s );
				break;
			case 'status':
				self::tab_status();
				break;
			default:
				self::tab_general( $s );
		}

		echo '</div></div>';
	}

	/** دسترسی عمومی به بازکردن فرم برای کلاس‌های همراه. */
	public static function open_form( string $tab ): void {
		self::form_open( $tab );
	}

	private static function form_open( string $tab ): void {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'mb_admin_save' );
		echo '<input type="hidden" name="action" value="mb_save">';
		echo '<input type="hidden" name="mb_tab" value="' . esc_attr( $tab ) . '">';
	}

	private static function tab_general( array $s ): void {
		self::form_open( 'general' );
		echo '<table class="form-table"><tbody>';
		echo '<tr><th>برگهٔ اپ</th><td>';
		wp_dropdown_pages(
			array(
				'name'              => 'mb[app_page]',
				'selected'          => (int) $s['app_page'],
				'show_option_none'  => '— انتخاب کنید —',
				'option_none_value' => 0,
			)
		);
		echo '<p class="description">برگه‌ای که شورت‌کد <code>[moonbanu_app]</code> در آن قرار دارد.</p></td></tr>';
		echo '<tr><th>طول پیش‌فرض چرخه</th><td><input type="number" min="21" max="35" name="mb[cycle_len]" value="' . esc_attr( (string) $s['cycle_len'] ) . '"></td></tr>';
		echo '<tr><th>طول پیش‌فرض قاعدگی</th><td><input type="number" min="2" max="7" name="mb[period_len]" value="' . esc_attr( (string) $s['period_len'] ) . '"></td></tr>';
		echo '<tr><th>متن سلب مسئولیت</th><td><input type="text" class="large-text" name="mb[disclaimer]" value="' . esc_attr( (string) $s['disclaimer'] ) . '"></td></tr>';
		echo '<tr><th>ایمیل هشدار مدیر</th><td><input type="email" class="regular-text" name="mb[admin_email]" value="' . esc_attr( (string) $s['admin_email'] ) . '"></td></tr>';
		echo '<tr><th>حذف کامل داده‌ها هنگام حذف پلاگین</th><td><label><input type="checkbox" name="mb[delete_data]" value="1"' . checked( (int) $s['delete_data'], 1, false ) . '> بله، جدول‌ها و داده‌های کاربران هم پاک شود</label><p class="description">پیش‌فرض خاموش است تا حذف اتفاقی پلاگین باعث از دست رفتن داده نشود.</p></td></tr>';
		echo '</tbody></table>';
		submit_button( 'ذخیره' );
		echo '</form>';
	}

	private static function tab_sub( array $s ): void {
		$stats = MB_Subscription::admin_stats();
		echo '<div class="mb-cards" style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0">';
		foreach ( array(
			'اشتراک فعال'          => number_format_i18n( $stats['active'] ),
			'اشتراک این ماه'       => number_format_i18n( $stats['month_count'] ),
			'درآمد این ماه (تومان)' => number_format_i18n( $stats['month_sum'] ),
			'MRR تخمینی (تومان)'   => number_format_i18n( $stats['mrr'] ),
		) as $label => $value ) {
			echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:14px 18px;min-width:180px"><div style="color:#646970;font-size:12px">' . esc_html( $label ) . '</div><div style="font-size:20px;font-weight:700">' . esc_html( $value ) . '</div></div>';
		}
		echo '</div>';

		self::form_open( 'sub' );
		echo '<table class="form-table"><tbody>';
		echo '<tr><th>قیمت اشتراک یک‌ماهه (تومان)</th><td><input type="number" min="1000" step="1000" name="mb[price_month]" value="' . esc_attr( (string) $s['price_month'] ) . '"></td></tr>';
		echo '<tr><th>قیمت اشتراک سه‌ماهه (تومان)</th><td><input type="number" min="1000" step="1000" name="mb[price_quarter]" value="' . esc_attr( (string) $s['price_quarter'] ) . '"></td></tr>';
		echo '<tr><th>قیمت اشتراک شش‌ماهه (تومان)</th><td><input type="number" min="1000" step="1000" name="mb[price_halfyear]" value="' . esc_attr( (string) $s['price_halfyear'] ) . '"></td></tr>';
		echo '<tr><th>درگاه فعال</th><td><select name="mb[gateway_provider]"><option value="zibal"' . selected( $s['gateway_provider'], 'zibal', false ) . '>زیبال</option><option value="zarinpal"' . selected( $s['gateway_provider'], 'zarinpal', false ) . '>زرین‌پال</option></select><p class="description">برای درخواست فعلی، زیبال انتخاب شده است.</p></td></tr>';
		$zibal_set = self::secret_is_set( $s, 'zibal_merchant' );
		$zp_set    = self::secret_is_set( $s, 'zp_merchant' );
		echo '<tr><th>کد مرچنت زیبال</th><td><input type="password" class="regular-text" name="mb[zibal_merchant]" value="" autocomplete="new-password" placeholder="' . esc_attr( $zibal_set ? 'قبلاً ذخیره شده — برای تغییر مقدار جدید را بنویس' : '' ) . '"><label style="display:block;margin-top:6px"><input type="checkbox" name="mb_clear[zibal_merchant]" value="1"> حذف مقدار ذخیره‌شده</label><p class="description">برای تست می‌توانید merchant آزمایشی زیبال را طبق مستندات وارد کنید. خالی گذاشتن یعنی مقدار قبلی حفظ شود.</p></td></tr>';
		echo '<tr><th>merchant_id زرین‌پال</th><td><input type="password" class="regular-text" name="mb[zp_merchant]" value="" autocomplete="new-password" placeholder="' . esc_attr( $zp_set ? 'قبلاً ذخیره شده — برای تغییر مقدار جدید را بنویس' : '' ) . '"><label style="display:block;margin-top:6px"><input type="checkbox" name="mb_clear[zp_merchant]" value="1"> حذف مقدار ذخیره‌شده</label><p class="description">فقط اگر درگاه زرین‌پال را انتخاب کرده‌اید. خالی گذاشتن یعنی مقدار قبلی حفظ شود.</p></td></tr>';
		echo '<tr><th>واحد ارسالی به درگاه</th><td><select name="mb[gateway_currency]"><option value="IRR"' . selected( $s['gateway_currency'], 'IRR', false ) . '>ریال (قیمت × ۱۰)</option><option value="IRT"' . selected( $s['gateway_currency'], 'IRT', false ) . '>تومان</option></select></td></tr>';
		echo '<tr><th>حالت سندباکس</th><td><label><input type="checkbox" name="mb[zp_sandbox]" value="1"' . checked( (int) $s['zp_sandbox'], 1, false ) . '> فعال</label></td></tr>';
		echo '</tbody></table>';
		submit_button( 'ذخیره' );
		echo '</form>';

		echo '<h2>اشتراک‌ها</h2>';
		$filter = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		echo '<p>';
		foreach ( array( '' => 'همه', 'active' => 'فعال', 'grace' => 'ارفاق', 'expired' => 'منقضی', 'pending' => 'در انتظار', 'canceled' => 'لغو' ) as $key => $label ) {
			echo '<a class="button' . ( $filter === $key ? ' button-primary' : '' ) . '" style="margin-inline-end:6px" href="' . esc_url( add_query_arg( array( 'page' => 'moonbanu', 'tab' => 'sub', 'status' => $key ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</p>';

		echo '<table class="widefat striped"><thead><tr><th>#</th><th>کاربر</th><th>وضعیت</th><th>مبلغ</th><th>انقضا</th><th>درگاه</th><th>اقدام</th></tr></thead><tbody>';
		foreach ( MB_Subscription::list_subscriptions( $filter, 50 ) as $sub ) {
			$user = get_userdata( (int) $sub['user_id'] );
			echo '<tr><td>' . (int) $sub['id'] . '</td>';
			echo '<td>' . esc_html( $user ? $user->user_email : '#' . (int) $sub['user_id'] ) . '</td>';
			echo '<td>' . esc_html( (string) $sub['status'] ) . '</td>';
			echo '<td>' . esc_html( number_format_i18n( (int) $sub['amount'] ) ) . '</td>';
			echo '<td>' . esc_html( $sub['expires_at'] ? MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $sub['expires_at'] ) ), 'num' ) : '—' ) . '</td>';
			echo '<td>' . esc_html( (string) $sub['gateway'] ) . '</td>';
			echo '<td><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:flex;gap:4px">';
			wp_nonce_field( 'mb_admin_save' );
			echo '<input type="hidden" name="action" value="mb_save"><input type="hidden" name="mb_tab" value="sub"><input type="hidden" name="mb_action" value="sub_status"><input type="hidden" name="sub_id" value="' . (int) $sub['id'] . '">';
			echo '<select name="sub_status">';
			foreach ( array( 'active' => 'فعال', 'grace' => 'ارفاق', 'expired' => 'منقضی', 'canceled' => 'لغو' ) as $k => $l ) {
				echo '<option value="' . esc_attr( $k ) . '"' . selected( $sub['status'], $k, false ) . '>' . esc_html( $l ) . '</option>';
			}
			echo '</select><button class="button">اعمال</button></form></td></tr>';
		}
		echo '</tbody></table>';

		echo '<h2>فعال‌سازی دستی</h2>';
		self::form_open( 'sub' );
		echo '<input type="hidden" name="mb_action" value="sub_grant">';
		echo '<input type="email" name="grant_email" placeholder="ایمیل کاربر" required> <input type="number" name="grant_days" value="30" min="1" style="width:90px"> روز ';
		submit_button( 'فعال کن', 'secondary', 'submit', false );
		echo '</form>';

		echo '<h2>کوپن‌ها</h2>';
		global $wpdb;
		$coupons = (array) $wpdb->get_results( 'SELECT * FROM ' . MB_DB::t( 'coupons' ) . ' ORDER BY code ASC', ARRAY_A );
		echo '<table class="widefat striped"><thead><tr><th>کد</th><th>درصد</th><th>انقضا</th><th>سقف</th><th>استفاده</th><th></th></tr></thead><tbody>';
		foreach ( $coupons as $c ) {
			echo '<tr><td><code>' . esc_html( (string) $c['code'] ) . '</code></td><td>' . (int) $c['percent'] . '٪</td><td>' . esc_html( $c['expires_at'] ? gmdate( 'Y-m-d', strtotime( (string) $c['expires_at'] ) ) : '—' ) . '</td><td>' . (int) $c['max_uses'] . '</td><td>' . (int) $c['uses'] . '</td>';
			echo '<td><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'mb_admin_save' );
			echo '<input type="hidden" name="action" value="mb_save"><input type="hidden" name="mb_tab" value="sub"><input type="hidden" name="mb_action" value="coupon_del"><input type="hidden" name="coupon_code" value="' . esc_attr( (string) $c['code'] ) . '"><button class="button button-link-delete">حذف</button></form></td></tr>';
		}
		echo '</tbody></table>';

		self::form_open( 'sub' );
		echo '<input type="hidden" name="mb_action" value="coupon_add">';
		echo '<p><input type="text" name="coupon_code" placeholder="کد" required> <input type="number" name="coupon_percent" value="20" min="1" max="100" style="width:80px"> درصد، انقضا: <input type="date" name="coupon_expires"> سقف استفاده: <input type="number" name="coupon_max" value="0" min="0" style="width:80px"> ';
		submit_button( 'افزودن کوپن', 'secondary', 'submit', false );
		echo '</p></form>';
	}

	/* --------------------------------------------------------------------- */
	/* تب پشتیبانی                                                          */
	/* --------------------------------------------------------------------- */

	private static function tab_support(): void {
		$ticket_id = isset( $_GET['ticket'] ) ? absint( $_GET['ticket'] ) : 0;
		if ( $ticket_id > 0 && MB_Support::get_ticket( $ticket_id ) ) {
			self::support_thread( $ticket_id );
			return;
		}

		$status = isset( $_GET['st'] ) ? sanitize_key( wp_unslash( $_GET['st'] ) ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

		echo '<div class="mb-cards" style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0">';
		foreach ( array(
			'همه درخواست‌ها' => MB_Support::admin_count(),
			'باز'            => MB_Support::admin_count( 'open' ),
			'پاسخ داده شده'  => MB_Support::admin_count( 'answered' ),
			'بسته'           => MB_Support::admin_count( 'closed' ),
		) as $label => $value ) {
			echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:14px 18px;min-width:150px"><div style="color:#646970;font-size:12px">' . esc_html( $label ) . '</div><div style="font-size:20px;font-weight:700">' . esc_html( number_format_i18n( (int) $value ) ) . '</div></div>';
		}
		echo '</div>';

		echo '<p>';
		$filters = array( '' => 'همه' ) + MB_Support::statuses();
		foreach ( $filters as $key => $label ) {
			echo '<a class="button' . ( $status === $key ? ' button-primary' : '' ) . '" style="margin-inline-end:6px" href="'
				. esc_url( add_query_arg( array( 'page' => 'moonbanu', 'tab' => 'support', 'st' => $key ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</p>';

		echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '" style="margin:10px 0">';
		echo '<input type="hidden" name="page" value="moonbanu"><input type="hidden" name="tab" value="support">';
		echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="جست‌وجوی عنوان یا تماس" style="min-width:260px"> ';
		submit_button( 'جست‌وجو', 'secondary', 'submit', false );
		echo '</form>';

		$rows   = MB_Support::admin_list( $status, $search, 40 );
		$topics = MB_Support::topics();

		echo '<table class="widefat striped"><thead><tr><th>#</th><th>عنوان</th><th>کاربر</th><th>موضوع</th><th>اولویت</th><th>وضعیت</th><th>آخرین تغییر</th><th></th></tr></thead><tbody>';
		if ( empty( $rows ) ) {
			echo '<tr><td colspan="8">درخواستی با این شرایط نیست.</td></tr>';
		}
		foreach ( $rows as $row ) {
			$user = get_userdata( (int) $row['user_id'] );
			$link = add_query_arg( array( 'page' => 'moonbanu', 'tab' => 'support', 'ticket' => (int) $row['id'] ), admin_url( 'admin.php' ) );
			$new  = (int) $row['unread_admin'];
			echo '<tr' . ( $new ? ' style="background:#fff8e5"' : '' ) . '>';
			echo '<td>' . (int) $row['id'] . '</td>';
			echo '<td><strong><a href="' . esc_url( $link ) . '">' . esc_html( (string) $row['subject'] ) . '</a></strong>' . ( $new ? ' <span style="color:#b26b00;font-size:11px">تازه</span>' : '' ) . '</td>';
			echo '<td style="font-size:12px">' . esc_html( $user ? $user->user_email : '#' . (int) $row['user_id'] ) . '</td>';
			echo '<td style="font-size:12px">' . esc_html( $topics[ (string) $row['topic'] ] ?? '—' ) . '</td>';
			echo '<td style="font-size:12px">' . esc_html( MB_Support::priorities()[ (string) $row['priority'] ] ?? '' ) . '</td>';
			echo '<td style="font-size:12px">' . esc_html( MB_Support::statuses()[ (string) $row['status'] ] ?? '' ) . '</td>';
			echo '<td style="font-size:12px">' . esc_html( MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $row['updated_at'] ) ), 'num' ) ) . '</td>';
			echo '<td><a class="button button-small" href="' . esc_url( $link ) . '">باز کردن</a></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}

	/** گفت‌وگوی یک تیکت + فرم پاسخ. */
	private static function support_thread( int $ticket_id ): void {
		$ticket = MB_Support::get_ticket( $ticket_id );
		if ( ! $ticket ) {
			return;
		}
		$user = get_userdata( (int) $ticket['user_id'] );
		$back = add_query_arg( array( 'page' => 'moonbanu', 'tab' => 'support' ), admin_url( 'admin.php' ) );

		echo '<p><a class="button" href="' . esc_url( $back ) . '">‹ بازگشت به صندوق</a></p>';
		echo '<h2>#' . (int) $ticket_id . ' — ' . esc_html( (string) $ticket['subject'] ) . '</h2>';

		echo '<table class="form-table"><tbody>';
		echo '<tr><th>کاربر</th><td>';
		if ( $user ) {
			echo '<a href="' . esc_url( add_query_arg( array( 'page' => 'moonbanu', 'tab' => 'users', 'user' => (int) $user->ID ), admin_url( 'admin.php' ) ) ) . '">'
				. esc_html( $user->display_name . ' (' . $user->user_email . ')' ) . '</a>';
		} else {
			echo '#' . (int) $ticket['user_id'];
		}
		echo '</td></tr>';
		echo '<tr><th>راه تماس</th><td dir="ltr">' . esc_html( (string) $ticket['contact'] ) . '</td></tr>';
		echo '<tr><th>موضوع</th><td>' . esc_html( MB_Support::topics()[ (string) $ticket['topic'] ] ?? '—' ) . '</td></tr>';
		echo '<tr><th>اولویت</th><td>' . esc_html( MB_Support::priorities()[ (string) $ticket['priority'] ] ?? '' ) . '</td></tr>';
		echo '<tr><th>وضعیت</th><td>' . esc_html( MB_Support::statuses()[ (string) $ticket['status'] ] ?? '' ) . '</td></tr>';
		if ( $user ) {
			$sub = MB_Subscription::get_subscription( (int) $user->ID );
			echo '<tr><th>اشتراک کاربر</th><td>' . esc_html( $sub ? MB_Subscription::status_label( (string) $sub['status'] ) : 'رایگان' ) . '</td></tr>';
		}
		echo '</tbody></table>';

		echo '<h3>گفت‌وگو</h3><div style="max-width:760px">';
		foreach ( MB_Support::public_messages( $ticket_id ) as $msg ) {
			$staff = 'staff' === $msg['role'];
			echo '<div style="margin:10px 0;padding:12px 14px;border-radius:10px;border:1px solid '
				. ( $staff ? '#c6ddf0;background:#eef6fc' : '#dcdcde;background:#fff' ) . '">';
			echo '<div style="font-size:11px;color:#646970;margin-bottom:6px">'
				. esc_html( $staff ? 'پشتیبانی' : 'کاربر' ) . ' · ' . esc_html( (string) $msg['time_fa'] ) . '</div>';
			echo '<div style="white-space:pre-line;font-size:13px;line-height:2">' . esc_html( (string) $msg['body'] ) . '</div>';
			echo '</div>';
		}
		echo '</div>';

		echo '<h3>پاسخ</h3>';
		self::form_open( 'support' );
		echo '<input type="hidden" name="mb_action" value="ticket_reply"><input type="hidden" name="ticket_id" value="' . (int) $ticket_id . '">';
		echo '<textarea name="ticket_body" rows="6" class="large-text" placeholder="پاسخ خود را بنویسید…" style="max-width:760px"></textarea>';
		echo '<p><label>وضعیت پس از ارسال: <select name="ticket_status">';
		foreach ( MB_Support::statuses() as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( 'answered', $key, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label></p>';
		submit_button( 'ارسال پاسخ' );
		echo '</form>';

		echo '<div style="display:flex;gap:10px;flex-wrap:wrap">';
		self::form_open( 'support' );
		echo '<input type="hidden" name="mb_action" value="ticket_status"><input type="hidden" name="ticket_id" value="' . (int) $ticket_id . '"><input type="hidden" name="ticket_status" value="closed">';
		submit_button( 'بستن درخواست', 'secondary', 'submit', false );
		echo '</form>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'این درخواست و همهٔ پیام‌هایش حذف شود؟\')">';
		wp_nonce_field( 'mb_admin_save' );
		echo '<input type="hidden" name="action" value="mb_save"><input type="hidden" name="mb_tab" value="support"><input type="hidden" name="mb_action" value="ticket_delete"><input type="hidden" name="ticket_id" value="' . (int) $ticket_id . '">';
		echo '<button class="button button-link-delete">حذف درخواست</button></form>';
		echo '</div>';
	}

	/* --------------------------------------------------------------------- */
	/* تب اپ و PWA                                                          */
	/* --------------------------------------------------------------------- */

	private static function tab_app( array $s ): void {
		self::form_open( 'app' );

		echo '<h2>ورود و ثبت‌نام</h2>';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th>احراز هویت درون‌اپ</th><td><label><input type="checkbox" name="mb[inapp_auth]" value="1"' . checked( (int) $s['inapp_auth'], 1, false ) . '> فعال</label><p class="description">ورود، ثبت‌نام و بازیابی رمز داخل خود اپ انجام می‌شود و کاربر هرگز به <code>wp-login.php</code> نمی‌رود. برای ورود مدیر، نشانی <code>wp-login.php?mb_wp=1</code> همیشه باز است.</p></td></tr>';
		echo '<tr><th>ثبت‌نام تازه</th><td><label><input type="checkbox" name="mb[allow_registration]" value="1"' . checked( (int) $s['allow_registration'], 1, false ) . '> باز باشد</label></td></tr>';
		echo '<tr><th>ورود با کد یک‌بارمصرف</th><td><label><input type="checkbox" name="mb[otp_login]" value="1"' . checked( (int) ( $s['otp_login'] ?? 1 ), 1, false ) . '> فعال</label><p class="description">کاربر می‌تواند با کد شش‌رقمی پیامکی (یا ایمیلی، اگر پیامک تنظیم نشده باشد) بدون رمز وارد شود. برای کار کردن پیامک، تب «پیامک» را کامل کن.</p></td></tr>';
		echo '<tr><th>نشانی قوانین</th><td><input type="url" class="large-text" name="mb[terms_url]" value="' . esc_attr( (string) $s['terms_url'] ) . '" placeholder="https://..."></td></tr>';
		echo '<tr><th>نشانی حریم خصوصی</th><td><input type="url" class="large-text" name="mb[privacy_url]" value="' . esc_attr( (string) $s['privacy_url'] ) . '" placeholder="https://..."></td></tr>';
		echo '</tbody></table>';

		echo '<h2>جداسازی از قالب</h2>';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th>حالت جداسازی</th><td><label><input type="checkbox" name="mb[isolate_app]" value="1"' . checked( (int) $s['isolate_app'], 1, false ) . '> فعال</label><p class="description">روی برگهٔ اپ، CSS و JS قالب و افزونه‌های دیگر بارگذاری نمی‌شود و manifest/theme-color قالب حذف می‌شود. بقیهٔ سایت هیچ تغییری نمی‌کند.</p></td></tr>';
		echo '<tr><th>استثناها</th><td><textarea name="mb[isolate_allow]" rows="4" class="large-text" placeholder="handle-1&#10;handle-2">' . esc_textarea( (string) $s['isolate_allow'] ) . '</textarea><p class="description">هر خط یک handle که باید در اپ بارگذاری شود (مثلاً اسکریپت درگاه). معمولاً خالی بماند.</p></td></tr>';
		echo '</tbody></table>';

		echo '<h2>PWA اختصاصی اپ</h2>';
		echo '<p class="description">این PWA کاملاً از PWA سایت جداست: نام، آیکن، scope و کش مستقل دارد و فقط مسیر برگهٔ اپ را کنترل می‌کند.</p>';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th>نام کامل اپ</th><td><input type="text" class="regular-text" name="mb[pwa_name]" value="' . esc_attr( (string) $s['pwa_name'] ) . '"></td></tr>';
		echo '<tr><th>نام کوتاه (زیر آیکن)</th><td><input type="text" name="mb[pwa_short_name]" value="' . esc_attr( (string) $s['pwa_short_name'] ) . '" maxlength="12"></td></tr>';
		echo '<tr><th>رنگ نوار وضعیت</th><td><input type="text" name="mb[pwa_theme_color]" value="' . esc_attr( (string) $s['pwa_theme_color'] ) . '" pattern="#[0-9A-Fa-f]{6}" placeholder="#RRGGBB"></td></tr>';
		echo '<tr><th>رنگ صفحهٔ راه‌اندازی</th><td><input type="text" name="mb[pwa_bg_color]" value="' . esc_attr( (string) $s['pwa_bg_color'] ) . '" pattern="#[0-9A-Fa-f]{6}" placeholder="#RRGGBB"></td></tr>';
		echo '<tr><th>نشانی manifest</th><td><code dir="ltr">' . esc_html( MB_Assets::manifest_url() ) . '</code></td></tr>';
		echo '<tr><th>نشانی service worker</th><td><code dir="ltr">' . esc_html( MB_Assets::sw_url() ) . '</code></td></tr>';
		echo '<tr><th>Scope اپ</th><td><code dir="ltr">' . esc_html( MB_Assets::scope_path() ) . '</code></td></tr>';
		echo '</tbody></table>';

		echo '<h2>برند و پشتیبانی</h2>';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th>نام برند</th><td><input type="text" class="regular-text" name="mb[brand_name]" value="' . esc_attr( (string) $s['brand_name'] ) . '"></td></tr>';
		echo '<tr><th>ایمیل پشتیبانی</th><td><input type="email" class="regular-text" name="mb[support_email]" value="' . esc_attr( (string) $s['support_email'] ) . '"></td></tr>';
		echo '<tr><th>شماره پشتیبانی</th><td><input type="text" name="mb[support_phone]" value="' . esc_attr( (string) $s['support_phone'] ) . '" dir="ltr"></td></tr>';
		echo '<tr><th>ساعت پاسخ‌گویی</th><td><input type="text" class="regular-text" name="mb[support_hours]" value="' . esc_attr( (string) $s['support_hours'] ) . '"></td></tr>';
		echo '<tr><th>متن اعتبار طراح</th><td><input type="text" class="regular-text" name="mb[dev_credit]" value="' . esc_attr( (string) $s['dev_credit'] ) . '"></td></tr>';
		echo '<tr><th>نام استودیو</th><td><input type="text" name="mb[dev_studio]" value="' . esc_attr( (string) $s['dev_studio'] ) . '"></td></tr>';
		echo '<tr><th>لینک توسعه‌دهنده</th><td><input type="url" class="large-text" name="mb[dev_url]" value="' . esc_attr( (string) $s['dev_url'] ) . '" placeholder="https://..."></td></tr>';
		echo '</tbody></table>';

		submit_button( 'ذخیرهٔ تنظیمات اپ' );
		echo '</form>';
	}

	private static function tab_sms( array $s ): void {
		self::form_open( 'sms' );
		$api_set  = self::secret_is_set( $s, 'sms_api_key' );
		$user_set = self::secret_is_set( $s, 'sms_username' );
		echo '<table class="form-table"><tbody>';
		echo '<tr><th>سرویس</th><td><select name="mb[sms_provider]"><option value="kavenegar"' . selected( $s['sms_provider'], 'kavenegar', false ) . '>کاوه‌نگار</option><option value="melipayamak"' . selected( $s['sms_provider'], 'melipayamak', false ) . '>ملی‌پیامک</option></select></td></tr>';
		echo '<tr><th>api_key</th><td><input type="password" class="regular-text" name="mb[sms_api_key]" value="" autocomplete="new-password" placeholder="' . esc_attr( $api_set ? 'قبلاً ذخیره شده — برای تغییر مقدار جدید را بنویس' : '' ) . '"><label style="display:block;margin-top:6px"><input type="checkbox" name="mb_clear[sms_api_key]" value="1"> حذف مقدار ذخیره‌شده</label></td></tr>';
		echo '<tr><th>نام کاربری (ملی‌پیامک)</th><td><input type="password" class="regular-text" name="mb[sms_username]" value="" autocomplete="new-password" placeholder="' . esc_attr( $user_set ? 'قبلاً ذخیره شده — برای تغییر مقدار جدید را بنویس' : '' ) . '"><label style="display:block;margin-top:6px"><input type="checkbox" name="mb_clear[sms_username]" value="1"> حذف مقدار ذخیره‌شده</label></td></tr>';
		echo '<tr><th>شماره فرستنده</th><td><input type="text" name="mb[sms_sender]" value="' . esc_attr( (string) $s['sms_sender'] ) . '"></td></tr>';
		echo '</tbody></table>';
		submit_button( 'ذخیره' );
		echo '</form>';
		echo '<p class="description">برای ملی‌پیامک، api_key همان رمز عبور یا API Key پنل است و نام کاربری هم الزامی است. کلیدها فقط در دیتابیس سرور ذخیره می‌شوند و در خروجی اپ ظاهر نمی‌شوند.</p>';
	}

	private static function tab_content(): void {
		$s = wp_parse_args( (array) get_option( 'mb_settings', array() ), self::default_settings() );
		self::form_open( 'content' );
		echo '<h2>برند، رنگ و تصویر</h2><p>رنگ‌ها و تصویر پس‌زمینه از همین‌جا روی کل اپ اعمال می‌شوند. برای عکس‌های مقاله از تصویر شاخص وردپرس استفاده کن.</p>';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th>نشانی لوگو</th><td><input type="url" class="large-text" name="mb[brand_logo_url]" value="' . esc_attr( (string) $s['brand_logo_url'] ) . '" placeholder="https://..."></td></tr>';
		echo '<tr><th>نشانی تصویر پس‌زمینه</th><td><input type="url" class="large-text" name="mb[background_image_url]" value="' . esc_attr( (string) $s['background_image_url'] ) . '" placeholder="https://..."></td></tr>';
		foreach ( array( 'color_bg' => 'پس‌زمینه', 'color_surface' => 'سطح کارت', 'color_text' => 'متن اصلی', 'color_muted' => 'متن فرعی', 'color_accent' => 'طلایی/تأکید', 'color_period' => 'قاعدگی', 'color_fertile' => 'باروری', 'color_ovulation' => 'تخمک‌گذاری', 'color_pms' => 'PMS' ) as $key => $label ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td><input type="text" name="mb[' . esc_attr( $key ) . ']" value="' . esc_attr( (string) $s[ $key ] ) . '" pattern="#[0-9A-Fa-f]{6}" placeholder="#RRGGBB"></td></tr>';
		}
		echo '<tr><th>جایگزینی متن‌های اپ</th><td><textarea name="mb[text_overrides]" rows="8" class="large-text" placeholder="متن فعلی => متن جدید">' . esc_textarea( (string) $s['text_overrides'] ) . '</textarea><p class="description">هر خط یک جایگزینی است. HTML خام وارد نکن؛ این ابزار برای متن‌های نمایشی و پیام‌هاست.</p></td></tr>';
		echo '</tbody></table>';
		submit_button( 'ذخیره برند و متن‌ها' );
		echo '</form>';
		echo '<hr><h2>راهنماهای سلامت</h2><p>از نوع محتوای «راهنمای سلامت» ساخته می‌شوند. برای هر راهنما دستهٔ آن را از تاکسونومی «دسته‌های راهنما» انتخاب کنید و متای «زمان مطالعه» را پر کنید. برای معرفی راهنمای ویژهٔ هفته، متای «ویژهٔ هفته» را روی عددی بزرگ‌تر از بقیه بگذارید.</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'post-new.php?post_type=mb_article' ) ) . '">افزودن راهنمای تازه</a> <a class="button" href="' . esc_url( admin_url( 'edit.php?post_type=mb_article' ) ) . '">همه راهنماها</a></p>';

		echo '<h2>پرسش‌های محرمانه</h2>';
		global $wpdb;
		$rows = (array) $wpdb->get_results( 'SELECT * FROM ' . MB_DB::t( 'questions' ) . ' ORDER BY id DESC LIMIT 30', ARRAY_A );
		if ( empty( $rows ) ) {
			echo '<p>پرسشی ثبت نشده است.</p>';
			return;
		}
		echo '<table class="widefat striped"><thead><tr><th>#</th><th>پرسش</th><th>وضعیت</th><th>پاسخ</th></tr></thead><tbody>';
		foreach ( $rows as $q ) {
			echo '<tr><td>' . (int) $q['id'] . '</td><td style="max-width:360px">' . esc_html( (string) $q['body'] ) . '</td><td>' . esc_html( 'answered' === $q['status'] ? 'پاسخ داده شد' : 'در انتظار' ) . '</td><td>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
			wp_nonce_field( 'mb_admin_save' );
			echo '<input type="hidden" name="action" value="mb_save"><input type="hidden" name="mb_tab" value="content"><input type="hidden" name="mb_action" value="answer_question"><input type="hidden" name="question_id" value="' . (int) $q['id'] . '">';
			echo '<textarea name="answer" rows="2" style="width:100%">' . esc_textarea( (string) $q['answer'] ) . '</textarea><button class="button">ثبت پاسخ</button></form></td></tr>';
		}
		echo '</tbody></table>';
	}

	private static function tab_security( array $s ): void {
		self::form_open( 'security' );
		echo '<table class="form-table"><tbody>';
		echo '<tr><th>دامنه‌های مجاز</th><td><textarea name="mb[allowed_domains]" rows="3" class="large-text" placeholder="example.com">' . esc_textarea( (string) $s['allowed_domains'] ) . '</textarea><p class="description">هر دامنه در یک خط. خالی بگذارید تا محدودیتی اعمال نشود.</p></td></tr>';
		echo '</tbody></table>';
		submit_button( 'ذخیره' );
		echo '</form>';

		echo '<h2>کلید قطع اضطراری</h2>';
		self::form_open( 'security' );
		echo '<input type="hidden" name="mb_action" value="clear_safe">';
		echo '<p>حالت ایمن فعلی: <strong>' . ( MB_License::safe_mode() ? 'روشن (Pro خاموش)' : 'خاموش' ) . '</strong></p>';
		submit_button( 'پاک کردن حالت ایمن و بازسازی manifest', 'secondary', 'submit', false );
		echo '</form>';

		echo '<h2>دعوت‌نامه‌ها</h2>';
		global $wpdb;
		$invites = (array) $wpdb->get_results( 'SELECT * FROM ' . MB_DB::t( 'invites' ) . ' ORDER BY id DESC LIMIT 30', ARRAY_A );
		echo '<table class="widefat striped"><thead><tr><th>#</th><th>بانو</th><th>کانال</th><th>انقضا</th><th>پذیرفته</th><th>لغو شده</th></tr></thead><tbody>';
		foreach ( $invites as $inv ) {
			$wife = get_userdata( (int) $inv['wife_id'] );
			echo '<tr><td>' . (int) $inv['id'] . '</td><td>' . esc_html( $wife ? $wife->user_email : '#' . (int) $inv['wife_id'] ) . '</td><td>' . esc_html( (string) $inv['channel'] ) . '</td><td>' . esc_html( gmdate( 'Y-m-d H:i', strtotime( (string) $inv['expires_at'] ) ) ) . '</td><td>' . ( $inv['accepted_by'] ? 'بله' : 'خیر' ) . '</td><td>' . ( (int) $inv['revoked'] ? 'بله' : 'خیر' ) . '</td></tr>';
		}
		echo '</tbody></table>';

		echo '<h2>لاگ امنیت</h2>';
		echo '<table class="widefat striped"><thead><tr><th>زمان</th><th>رویداد</th><th>جزئیات</th></tr></thead><tbody>';
		foreach ( MB_DB::get_security_log( 80 ) as $row ) {
			echo '<tr><td>' . esc_html( (string) $row['created_at'] ) . '</td><td><code>' . esc_html( (string) $row['event'] ) . '</code></td><td style="font-family:monospace;font-size:11px">' . esc_html( (string) $row['detail'] ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/**
	 * تب انجمن و کوییز: moderation رکوردهای انجمن.
	 * پردازش POST داخل همین متد است (nonce + بررسی دسترسی) تا مستقل بماند.
	 */
	private static function tab_community(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'دسترسی مجاز نیست.' );
		}

		if ( isset( $_POST['mb_comm_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_comm_nonce'] ) ), 'mb_community' ) ) {
			$id     = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
			$action = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';

			if ( $id > 0 ) {
				if ( 'approve' === $action ) {
					if ( isset( $_POST['answer'] ) ) {
						MB_DB::set_community_answer( $id, sanitize_textarea_field( wp_unslash( $_POST['answer'] ) ) );
					}
					MB_DB::set_community_status( $id, 'approved' );
					echo '<div class="notice notice-success"><p>رکورد تأیید و در اپ منتشر شد.</p></div>';
				} elseif ( 'reject' === $action ) {
					MB_DB::set_community_status( $id, 'rejected' );
					echo '<div class="notice notice-warning"><p>رکورد رد شد و در اپ دیده نمی‌شود.</p></div>';
				} elseif ( 'save' === $action && isset( $_POST['answer'] ) ) {
					MB_DB::set_community_answer( $id, sanitize_textarea_field( wp_unslash( $_POST['answer'] ) ) );
					echo '<div class="notice notice-success"><p>پاسخ ذخیره شد.</p></div>';
				} elseif ( 'delete' === $action ) {
					MB_DB::delete_community( $id );
					echo '<div class="notice notice-success"><p>رکورد حذف شد.</p></div>';
				}
			}
		}

		$rows = MB_DB::get_community_admin( '', 100 );
		$cats = MB_Plugin::categories();

		echo '<h2>انجمن ناشناس</h2>';
		echo '<p>پرسش‌هایی که کاربر اجازهٔ انتشار ناشناس داده است. هیچ شناسهٔ کاربری در خروجی اپ نمی‌رود؛ فقط متن و برچسب ناشناس.</p>';
		echo '<p><strong>در انتظار بررسی:</strong> ' . esc_html( MB_Jalali::fa_num( (string) MB_DB::count_community( 'pending' ) ) )
			. ' · <strong>منتشرشده:</strong> ' . esc_html( MB_Jalali::fa_num( (string) MB_DB::count_community( 'approved' ) ) ) . '</p>';

		if ( empty( $rows ) ) {
			echo '<p><em>رکوردی وجود ندارد.</em></p>';
		} else {
			$labels = array( 'pending' => 'در انتظار', 'approved' => 'منتشرشده', 'rejected' => 'رد شده' );
			foreach ( $rows as $row ) {
				$id = (int) $row['id'];
				echo '<div class="card" style="max-width:820px;margin:12px 0;padding:14px">';
				echo '<p><strong>' . esc_html( (string) ( $labels[ $row['status'] ] ?? $row['status'] ) ) . '</strong> · '
					. esc_html( (string) ( $cats[ $row['cat'] ] ?? $row['cat'] ) ) . ' · '
					. esc_html( (string) $row['anon_label'] ) . '</p>';
				echo '<blockquote style="margin:8px 0;padding:8px 12px;border-right:3px solid #ccc;background:#fafafa">'
					. esc_html( (string) $row['question'] ) . '</blockquote>';
				echo '<form method="post">';
				wp_nonce_field( 'mb_community', 'mb_comm_nonce' );
				echo '<input type="hidden" name="id" value="' . (int) $id . '">';
				echo '<textarea name="answer" rows="4" class="large-text" placeholder="پاسخ متخصص…">' . esc_textarea( (string) ( $row['answer'] ?? '' ) ) . '</textarea>';
				echo '<p>';
				echo '<button class="button button-primary" name="do" value="approve">تأیید و انتشار</button> ';
				echo '<button class="button" name="do" value="save">ذخیرهٔ پاسخ</button> ';
				echo '<button class="button" name="do" value="reject">رد</button> ';
				echo '<button class="button button-link-delete" name="do" value="delete" onclick="return confirm(\'این رکورد حذف شود؟\')">حذف</button>';
				echo '</p></form></div>';
			}
		}

		echo '<hr><h2>کوییزها</h2>';
		$quizzes = MB_DB::get_quizzes( false );
		echo '<table class="widefat striped" style="max-width:820px"><thead><tr><th>عنوان</th><th>شناسه</th><th>پرسش‌ها</th><th>وضعیت</th></tr></thead><tbody>';
		foreach ( $quizzes as $quiz ) {
			echo '<tr><td>' . esc_html( (string) $quiz['title'] ) . '</td><td><code>' . esc_html( (string) $quiz['slug'] ) . '</code></td><td>'
				. esc_html( MB_Jalali::fa_num( (string) count( (array) $quiz['questions'] ) ) ) . '</td><td>'
				. ( (int) $quiz['active'] === 1 ? 'فعال' : 'غیرفعال' ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	/** §۸ — نمایش نتیجهٔ تست‌های پذیرش. */
	public static function render_selftest(): void {
		$result = MB_Selftest::run_all();

		echo '<h2>تست‌های پذیرش نسخهٔ ۳٫۰</h2>';
		printf(
			'<p style="font-size:15px"><strong style="color:%1$s">%2$s</strong> از %3$s تست سبز است.</p>',
			esc_attr( $result['green'] ? '#1a7f37' : '#b32d2e' ),
			esc_html( MB_Jalali::fa_num( (string) $result['pass'] ) ),
			esc_html( MB_Jalali::fa_num( (string) $result['total'] ) )
		);

		foreach ( $result['groups'] as $group => $cases ) {
			echo '<h3 style="margin-bottom:4px">' . esc_html( (string) $group ) . '</h3>';
			echo '<table class="widefat striped" style="max-width:820px;margin-bottom:14px"><tbody>';
			foreach ( $cases as $case ) {
				$ok = ! empty( $case['ok'] );
				echo '<tr><td style="width:26px">' . ( $ok ? '<span style="color:#1a7f37">●</span>' : '<span style="color:#b32d2e">●</span>' ) . '</td>';
				echo '<td>' . esc_html( (string) $case['name'] ) . '</td>';
				echo '<td style="color:#666">' . esc_html( (string) $case['detail'] ) . '</td></tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h3>چک‌لیست بستن پرونده</h3><ul style="list-style:disc;margin-inline-start:22px">';
		echo '<li>' . ( $result['green'] ? '✅' : '⬜' ) . ' همهٔ تست‌های بخش ۸ سبز</li>';
		echo '<li>' . ( MB_License::safe_mode() ? '⬜ حالت ایمن فعال است (فایل حساس تغییر کرده)' : '✅ یکپارچگی فایل‌ها تأیید شد' ) . '</li>';
		echo '<li>⬜ Lighthouse موبایل ≥ ۸۵ (روی سرور واقعی اندازه بگیرید)</li>';
		echo '<li>⬜ تأیید دستی حریم خصوصی: ورود با حساب همسر و بازبینی صفحه‌های همراه</li>';
		echo '</ul>';
	}

	private static function tab_status(): void {
		$license = MB_License::status();
		$test    = MB_Jalali::self_test();
		$vector  = self::cycle_vector_test();

		echo '<h2>اطلاعات سیستم</h2><table class="widefat striped"><tbody>';
		$rows = array(
			'نسخه پلاگین'      => MB_VERSION,
			'نسخه وردپرس'      => get_bloginfo( 'version' ),
			'نسخه PHP'         => PHP_VERSION,
			'منطقه زمانی'      => wp_timezone_string(),
			'کرون روزانه ۰۶'   => wp_next_scheduled( 'mb_daily_06' ) ? gmdate( 'Y-m-d H:i', (int) wp_next_scheduled( 'mb_daily_06' ) ) . ' UTC' : 'زمان‌بندی نشده',
			'کرون شبانه'       => wp_next_scheduled( 'mb_nightly' ) ? gmdate( 'Y-m-d H:i', (int) wp_next_scheduled( 'mb_nightly' ) ) . ' UTC' : 'زمان‌بندی نشده',
			'حالت ایمن'        => $license['safe_mode'] ? 'روشن' : 'خاموش',
			'ساخت manifest'    => $license['manifest_built'],
			'فایل‌های تغییریافته' => empty( $license['changed'] ) ? 'ندارد' : implode( ', ', $license['changed'] ),
			'هش سایت'          => $license['site_hash'],
			'توکن نصب'         => $license['install_token'],
			'تست جلالی'        => sprintf( '%d خطا از %d نمونه؛ مبدأ: %s؛ وکتور: %s', (int) $test['roundtrip_failures'], (int) $test['samples'], $test['epoch_ok'] ? 'درست' : 'نادرست', $test['vector_ok'] ? 'درست' : 'نادرست' ),
			'تست وکتور چرخه'   => $vector,
			'فونت Vazirmatn'   => self::font_status(),
		);
		foreach ( $rows as $label => $value ) {
			echo '<tr><th style="width:220px">' . esc_html( $label ) . '</th><td>' . esc_html( (string) $value ) . '</td></tr>';
		}
		echo '</tbody></table>';

		echo '<h2>هش فایل‌ها</h2><table class="widefat striped"><tbody>';
		foreach ( MB_License::file_hashes() as $file => $hash ) {
			echo '<tr><td><code>' . esc_html( $file ) . '</code></td><td style="font-family:monospace;font-size:11px">' . esc_html( substr( (string) $hash, 0, 32 ) ) . '…</td></tr>';
		}
		echo '</tbody></table>';

		self::form_open( 'status' );
		echo '<input type="hidden" name="mb_action" value="rebuild_manifest">';
		submit_button( 'بازسازی manifest', 'secondary', 'submit', false );
		echo '</form>';
	
		echo '<hr>';
		self::render_selftest();
	}

	/** بررسی حضور فایل‌های فونت لوکال (در نبودشان اپ روی فونت سیستمی می‌افتد). */
	public static function font_status(): string {
		$missing = array();
		foreach ( array( 400, 500, 600, 700, 800 ) as $weight ) {
			if ( ! file_exists( MB_DIR . 'assets/fonts/Vazirmatn-' . $weight . '.woff2' ) ) {
				$missing[] = (string) $weight;
			}
		}
		if ( empty( $missing ) ) {
			return 'هر ۵ وزن موجود است.';
		}
		return 'وزن‌های ناموجود: ' . implode( ', ', $missing ) . ' — اپ روی فونت سیستمی فارسی کار می‌کند؛ برای ظاهر دقیق، فایل‌های woff2 را در assets/fonts بگذارید.';
	}

	/** اجرای وکتور مرجع بخش ۴٫۴ روی موتور (بدون دست‌زدن به داده کاربران). */
	public static function cycle_vector_test(): string {
		$start = MB_Jalali::j2g( 1405, 6, 7 );
		$today = MB_Jalali::j2g( 1405, 6, 27 );
		$len   = 28;
		$idx   = MB_Jalali::diff_days( $start, $today ) + 1;
		$ovul  = $len - 14;
		$next  = MB_Jalali::add_days( $start, $len );
		$pmsA  = MB_Jalali::add_days( $start, $len - 7 );
		$pmsB  = MB_Jalali::add_days( $start, $len - 1 );
		$fertA = MB_Jalali::add_days( $start, $ovul - 6 );
		$fertB = MB_Jalali::add_days( $start, $ovul );

		$checks = array(
			21 === $idx,
			'1405/07/04' === MB_Jalali::en_num( MB_Jalali::format_fa( $next, 'num' ) ),
			8 === MB_Jalali::diff_days( $today, $next ),
			'1405/06/15' === MB_Jalali::en_num( MB_Jalali::format_fa( $fertA, 'num' ) ),
			'1405/06/21' === MB_Jalali::en_num( MB_Jalali::format_fa( $fertB, 'num' ) ),
			'1405/06/20' === MB_Jalali::en_num( MB_Jalali::format_fa( MB_Jalali::add_days( $start, $ovul - 1 ), 'num' ) ),
			'1405/06/28' === MB_Jalali::en_num( MB_Jalali::format_fa( $pmsA, 'num' ) ),
			'1405/07/03' === MB_Jalali::en_num( MB_Jalali::format_fa( $pmsB, 'num' ) ),
		);
		$pass = count( array_filter( $checks ) );
		return sprintf( '%d از %d بررسی پاس شد (روز چرخه: %d)', $pass, count( $checks ), $idx );
	}

	/* --------------------------------------------------------------------- */
	/* متاباکس راهنما                                                        */
	/* --------------------------------------------------------------------- */

	public static function meta_boxes(): void {
		add_meta_box( 'mb_article_meta', 'تنظیمات راهنما', array( __CLASS__, 'render_meta_box' ), 'mb_article', 'side' );
	}

	public static function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( 'mb_article_meta', 'mb_article_nonce' );
		$minutes  = (int) get_post_meta( $post->ID, 'read_minutes', true );
		$featured = (int) get_post_meta( $post->ID, 'featured_week', true );
		echo '<p><label>زمان مطالعه (دقیقه)<br><input type="number" min="1" max="120" name="mb_read_minutes" value="' . esc_attr( (string) ( $minutes ?: 5 ) ) . '" style="width:100%"></label></p>';
		echo '<p><label>ویژهٔ هفته (عدد بزرگ‌تر = اولویت بالاتر)<br><input type="number" min="0" name="mb_featured_week" value="' . esc_attr( (string) $featured ) . '" style="width:100%"></label></p>';
	}

	public static function save_article_meta( int $post_id ): void {
		if ( ! isset( $_POST['mb_article_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mb_article_nonce'] ) ), 'mb_article_meta' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
			return;
		}
		update_post_meta( $post_id, 'read_minutes', absint( $_POST['mb_read_minutes'] ?? 5 ) );
		update_post_meta( $post_id, 'featured_week', absint( $_POST['mb_featured_week'] ?? 0 ) );
	}
}
