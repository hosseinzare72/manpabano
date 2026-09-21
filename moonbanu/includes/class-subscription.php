<?php
/**
 * اشتراک ماهیانه ماه‌بانو. تمام منطق Pro فقط در این کلاس و فقط سمت سرور است.
 * هیچ فلگ Pro به کلاینت فرستاده نمی‌شود.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Subscription {

	const GRACE_DAYS = 3;
	const CACHE_TTL  = 300; // ۵ دقیقه.

	/* --------------------------------------------------------------------- */
	/* وضعیت                                                                 */
	/* --------------------------------------------------------------------- */

	/** تنها مرجع تصمیم Pro. */
	public static function is_pro( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}
		if ( MB_License::safe_mode() ) {
			return false; // حالت ایمن: Pro سایت خاموش.
		}
		if ( user_can( $user_id, 'manage_options' ) ) {
			return true;
		}

		$cached = get_transient( 'mb_pro_' . $user_id );
		if ( false !== $cached ) {
			return '1' === (string) $cached;
		}

		$pro = null !== self::own_active_subscription( $user_id );

		// N2 — اشتراک خانوادگی: اشتراک فعال همسرِ هم‌couple هم Pro می‌دهد.
		if ( ! $pro ) {
			$spouse = MB_Couple::spouse_of( $user_id );
			if ( $spouse > 0 && $spouse !== $user_id && null !== self::own_active_subscription( $spouse ) ) {
				$pro = true;
			}
		}

		set_transient( 'mb_pro_' . $user_id, $pro ? '1' : '0', self::CACHE_TTL );
		return $pro;
	}

	/**
	 * اشتراک فعال «خود» کاربر، بی‌توجه به همسر.
	 *
	 * N2 برای جلوگیری از بازگشت بی‌پایان به این متد نیاز دارد: is_pro اینجا صدا زده نمی‌شود.
	 *
	 * @return array|null ردیف mb_subscriptions یا null.
	 */
	public static function own_active_subscription( int $user_id ): ?array {
		if ( $user_id <= 0 ) {
			return null;
		}
		$sub = self::get_subscription( $user_id );
		return ( $sub && self::sub_grants_pro( $sub ) ) ? $sub : null;
	}

	/** آیا این ردیف اشتراک همین حالا Pro می‌دهد؟ (با احتساب مهلت و اتصال نصب) */
	private static function sub_grants_pro( array $sub ): bool {
		if ( 'pro' !== (string) ( $sub['plan'] ?? '' ) || (int) ( $sub['term_months'] ?? 1 ) <= 0 ) {
			return false;
		}
		$exp   = ! empty( $sub['expires_at'] ) ? strtotime( (string) $sub['expires_at'] ) : 0;
		$grant = false;
		if ( 'active' === (string) $sub['status'] && $exp > time() ) {
			$grant = true;
		} elseif ( in_array( (string) $sub['status'], array( 'active', 'grace' ), true ) && $exp > 0 && time() <= $exp + self::GRACE_DAYS * DAY_IN_SECONDS ) {
			$grant = true;
		}

		// اتصال نصب (L5): اشتراک به همین سایت گره خورده است.
		if ( $grant && ! MB_License::binding_ok( $sub ) ) {
			MB_Plugin::log_security( 'binding_mismatch', array( 'user' => (int) $sub['user_id'], 'sub' => (int) $sub['id'] ) );
			return false;
		}
		return $grant;
	}

	/** آخرین اشتراک کاربر. */
	public static function get_subscription( int $user_id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . MB_DB::t( 'subscriptions' ) . " WHERE user_id = %d AND status <> 'pending' ORDER BY FIELD(status,'active','grace','canceled','expired'), expires_at DESC, id DESC LIMIT 1",
				$user_id
			),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	public static function status_data( int $user_id ): array {
		$pro = self::is_pro( $user_id );

		// N2: اگر کاربر خودش اشتراک فعال ندارد، ردیف پوشش‌دهندهٔ خانواده مبنا می‌شود.
		$sub = self::own_active_subscription( $user_id );
		if ( ! $sub ) {
			$sub = MB_Couple::covering_subscription( $user_id );
		}
		if ( ! $sub ) {
			$sub = self::get_subscription( $user_id );
		}

		$exp = $sub && $sub['expires_at'] ? $sub['expires_at'] : '';
		$days = $exp ? (int) floor( ( strtotime( $exp ) - time() ) / DAY_IN_SECONDS ) : null;

		$gifted = (bool) ( $sub && $pro && (int) $sub['user_id'] !== $user_id );
		$buyer  = $gifted ? MB_Couple::buyer_name( $user_id ) : '';

		return array(
			'family_label'  => MB_Couple::LABEL,
			'is_family'     => (bool) ( $pro && MB_Couple::spouse_of( $user_id ) > 0 ),
			'gifted'        => $gifted,
			'gifted_by'     => $buyer,
			'gift_note'     => $gifted ? ( '' !== $buyer ? 'اشتراک خانوادهٔ ماه‌بانو، هدیهٔ ' . $buyer : 'اشتراک خانوادهٔ ماه‌بانو، هدیهٔ همسرت' ) : '',
			'plan'          => $pro ? 'pro' : 'free',
			'status'        => $sub ? $sub['status'] : 'free',
			'expires'       => $exp,
			'expires_fa'    => $exp ? MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( $exp ) ), 'long' ) : '',
			'days_left'     => $days,
			'in_grace'      => $pro && null !== $days && $days < 0,
			'price'         => self::price(),
			'price_fa'      => self::price_fa(),
			'plans'         => self::plans(),
			'term_months'  => $sub ? max( 1, (int) ( $sub['term_months'] ?? 1 ) ) : 0,
			'checkout_url'  => MB_UI::app_url( '#/checkout' ),
		);
	}

	/** کش Pro هر دو عضو خانواده را تازه می‌کند. */
	public static function flush_couple_cache( int $user_id ): void {
		delete_transient( 'mb_pro_' . $user_id );
		$spouse = MB_Couple::spouse_of( $user_id );
		if ( $spouse > 0 ) {
			delete_transient( 'mb_pro_' . $spouse );
			delete_transient( 'mb_lifecycle_' . $spouse );
		}
	}

	/** ماتریس دسترسی. */
	public static function features(): array {
		return array(
			'log'             => 'free',
			'calendar_month'  => 'free',
			'home'            => 'free',
			'inapp_notify'    => 'free',
			'predict_3'       => 'pro',
			'analysis'        => 'pro',
			'cycle_map'       => 'pro',
			'symptom_prob'    => 'pro',
			'partner'         => 'pro',
			'sms'             => 'pro',
			'health_full'     => 'pro',
			'ask_expert'      => 'pro',
			'save_articles'   => 'pro',
			'accuracy_badge'  => 'pro',
			// نسخهٔ ۳٫۰ — رایگان
			'pregnancy'       => 'free',
			'quizzes'         => 'free',
			'community_read'  => 'free',
			'reminders'       => 'free',
			'trackers'        => 'free',
			// نسخهٔ ۳٫۰ — پیشرفته
			'ttc'             => 'pro',
			'report'          => 'pro',
			'assistant'       => 'pro',
			'offline_articles' => 'pro',
		);
	}

	public static function can( int $user_id, string $feature ): bool {
		$features = self::features();
		$level    = $features[ $feature ] ?? 'pro';
		if ( 'free' === $level ) {
			return true;
		}
		return self::is_pro( $user_id );
	}

	/* --------------------------------------------------------------------- */
	/* قیمت و کوپن                                                           */
	/* --------------------------------------------------------------------- */

	public static function plans(): array {
		return array(
			array( 'id' => 'month', 'months' => 1, 'label' => 'یک‌ماهه', 'price' => max( 1000, (int) MB_Plugin::setting( 'price_month', 250000 ) ) ),
			array( 'id' => 'quarter', 'months' => 3, 'label' => 'سه‌ماهه', 'price' => max( 1000, (int) MB_Plugin::setting( 'price_quarter', 500000 ) ) ),
			array( 'id' => 'halfyear', 'months' => 6, 'label' => 'شش‌ماهه', 'price' => max( 1000, (int) MB_Plugin::setting( 'price_halfyear', 1000000 ) ) ),
		);
	}

	public static function plan_by_months( int $months ): ?array {
		foreach ( self::plans() as $plan ) {
			if ( (int) $plan['months'] === $months ) {
				return $plan;
			}
		}
		return null;
	}

	public static function price(): int {
		$plans = self::plans();
		return (int) $plans[0]['price'];
	}

	public static function price_fa(): string {
		return MB_Jalali::fa_num( number_format_i18n( self::price() ) ) . ' تومان';
	}

	/**
	 * اعمال کد تخفیف روی مبلغ.
	 *
	 * درصدی یا مبلغ ثابت؛ با بررسی فعال‌بودن، انقضا، سقف مصرف، حداقل خرید و
	 * یک‌بار مصرف به‌ازای هر کاربر.
	 *
	 * @return array{amount:int,coupon:?string,percent:int,off:int,error:string}
	 */
	public static function apply_coupon( int $amount, string $code, int $user_id = 0 ): array {
		global $wpdb;
		$none = array( 'amount' => $amount, 'coupon' => null, 'percent' => 0, 'off' => 0, 'error' => '' );

		$code = strtoupper( trim( sanitize_text_field( MB_Jalali::en_num( $code ) ) ) );
		if ( '' === $code ) {
			return $none;
		}

		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . MB_DB::t( 'coupons' ) . ' WHERE code = %s', $code ), ARRAY_A );
		if ( ! $row ) {
			$none['error'] = 'کد تخفیف پیدا نشد.';
			return $none;
		}
		if ( isset( $row['active'] ) && ! (int) $row['active'] ) {
			$none['error'] = 'این کد تخفیف غیرفعال است.';
			return $none;
		}
		if ( ! empty( $row['expires_at'] ) && strtotime( (string) $row['expires_at'] ) < time() ) {
			$none['error'] = 'این کد تخفیف منقضی شده است.';
			return $none;
		}
		if ( (int) $row['max_uses'] > 0 && (int) $row['uses'] >= (int) $row['max_uses'] ) {
			$none['error'] = 'سقف استفاده از این کد پر شده است.';
			return $none;
		}
		$min = (int) ( $row['min_amount'] ?? 0 );
		if ( $min > 0 && $amount < $min ) {
			$none['error'] = 'این کد برای خریدهای بالای ' . MB_Jalali::fa_num( number_format_i18n( $min ) ) . ' تومان است.';
			return $none;
		}
		if ( $user_id > 0 && self::coupon_used_by( $user_id, $code ) ) {
			$none['error'] = 'این کد را قبلاً استفاده کرده‌ای.';
			return $none;
		}

		$percent = (int) max( 0, min( 100, (int) $row['percent'] ) );
		$fixed   = (int) max( 0, (int) ( $row['amount_off'] ?? 0 ) );

		if ( $percent > 0 ) {
			$off = (int) round( $amount * $percent / 100 );
		} elseif ( $fixed > 0 ) {
			$off = min( $fixed, $amount );
		} else {
			$none['error'] = 'این کد تخفیفی ندارد.';
			return $none;
		}

		// حداقل مبلغ قابل ارسال به درگاه ۱۰۰۰ تومان است.
		$final = (int) max( 1000, $amount - $off );
		$off   = $amount - $final;

		return array( 'amount' => $final, 'coupon' => $code, 'percent' => $percent, 'off' => $off, 'error' => '' );
	}

	/** آیا کاربر قبلاً این کد را مصرف کرده؟ */
	public static function coupon_used_by( int $user_id, string $code ): bool {
		$used = (array) get_user_meta( $user_id, 'mb_coupons_used', true );
		return in_array( strtoupper( $code ), array_map( 'strtoupper', array_map( 'strval', $used ) ), true );
	}

	/** ثبت مصرف کد برای کاربر. */
	private static function mark_coupon_used( int $user_id, ?string $code ): void {
		if ( ! $code || $user_id <= 0 ) {
			return;
		}
		$used   = (array) get_user_meta( $user_id, 'mb_coupons_used', true );
		$used[] = strtoupper( $code );
		update_user_meta( $user_id, 'mb_coupons_used', array_values( array_unique( $used ) ) );
	}

	/** برچسب فارسی وضعیت اشتراک. */
	public static function status_label( string $status ): string {
		$map = array(
			'active'   => 'فعال',
			'grace'    => 'مهلت ارفاق',
			'expired'  => 'منقضی',
			'canceled' => 'لغو شده',
			'pending'  => 'در انتظار پرداخت',
			'free'     => 'رایگان',
		);
		return $map[ $status ] ?? $status;
	}

	private static function bump_coupon( ?string $code ): void {
		global $wpdb;
		if ( ! $code ) {
			return;
		}
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . MB_DB::t( 'coupons' ) . ' SET uses = uses + 1 WHERE code = %s', strtoupper( $code ) ) );
	}

	/* --------------------------------------------------------------------- */
	/* پرداخت                                                                */
	/* --------------------------------------------------------------------- */

	public static function gateway(): MB_Gateway {
		return self::gateway_for( (string) MB_Plugin::setting( 'gateway_provider', 'zibal' ) );
	}

	/** ساخت درگاه از روی شناسهٔ ثبت‌شده؛ callback نباید به تنظیمات فعلی وابسته باشد. */
	public static function gateway_for( string $id ): MB_Gateway {
		return 'zibal' === sanitize_key( $id ) ? new MB_Gateway_Zibal() : new MB_Gateway_Zarinpal();
	}

	/** ساخت درخواست پرداخت. */
	public static function create_checkout( int $user_id, string $coupon = '', int $term_months = 1 ) {
		if ( ! MB_License::rate_limit( 'checkout_' . $user_id, 50, 60 ) ) {
			return new WP_Error( 'mb_rate', 'درخواست‌های بیش از حد. یک دقیقه بعد دوباره تلاش کن.' );
		}

		$plan = self::plan_by_months( $term_months );
		if ( ! $plan ) {
			return new WP_Error( 'mb_plan', 'پلن اشتراک معتبر نیست.' );
		}

		$priced = self::apply_coupon( (int) $plan['price'], $coupon, $user_id );
		if ( '' !== $priced['error'] ) {
			return new WP_Error( 'mb_coupon', $priced['error'] );
		}

		$gateway = self::gateway();

		global $wpdb;
		$ref = 'MB' . $user_id . '-' . time() . '-' . wp_generate_password( 6, false, false );
		$wpdb->insert(
			MB_DB::t( 'subscriptions' ),
			array(
				'user_id'       => $user_id,
				'couple_id'     => MB_Couple::id_for( $user_id ),
				'plan'          => 'pro',
				'term_months'   => (int) $plan['months'],
				'status'        => 'pending',
				'gateway'       => $gateway->id(),
				'ref_id'        => $ref,
				'amount'        => (int) $priced['amount'],
				'coupon'        => $priced['coupon'],
				'site_hash'     => (string) get_option( 'mb_site_hash' ),
				'install_token' => (string) get_option( 'mb_install_token' ),
				'remind'        => 1,
				'created_at'    => current_time( 'mysql' ),
			)
		);
		$sub_id = (int) $wpdb->insert_id;
		if ( ! $sub_id ) {
			return new WP_Error( 'mb_db', 'ثبت سفارش ممکن نشد.' );
		}

		$callback = add_query_arg( 'mb_verify', $ref, home_url( '/' ) );
		$result   = $gateway->create_request( (int) $priced['amount'], 'اشتراک ماهانه ماه‌بانو', $callback, array( 'ref' => $ref, 'user_id' => $user_id ) );

		if ( is_wp_error( $result ) ) {
			$wpdb->update( MB_DB::t( 'subscriptions' ), array( 'status' => 'canceled' ), array( 'id' => $sub_id ) );
			MB_Plugin::log_security( 'checkout_failed', array( 'user' => $user_id, 'msg' => $result->get_error_message() ) );
			return $result;
		}

		$wpdb->update( MB_DB::t( 'subscriptions' ), array( 'authority' => (string) $result['authority'] ), array( 'id' => $sub_id ), array( '%s' ), array( '%d' ) );

		return array(
			'ref_id'   => $ref,
			'amount'   => (int) $priced['amount'],
			'percent'  => (int) $priced['percent'],
			'redirect' => (string) $result['redirect'],
		);
	}

	/** کالبک درگاه: site/?mb_verify=REF */
	public static function handle_gateway_callback( string $ref, array $params ): void {
		global $wpdb;
		$ref = sanitize_text_field( $ref );
		$sub = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . MB_DB::t( 'subscriptions' ) . ' WHERE ref_id = %s', $ref ), ARRAY_A );

		$ok      = false;
		$message = 'پرداخت تأیید نشد.';

		if ( ! $sub ) {
			$message = 'سفارش پیدا نشد.';
		} elseif ( in_array( $sub['status'], array( 'active', 'grace' ), true ) ) {
			$ok      = true; // idempotent: کالبک تکراری دوباره فعال‌سازی نمی‌کند.
			$message = 'این پرداخت قبلاً تأیید شده است.';
		} else {
			$gateway_id = sanitize_key( (string) $sub['gateway'] );
			$gateway    = self::gateway_for( $gateway_id );
			$is_zibal   = 'zibal' === $gateway->id();
			$track_id  = isset( $params['trackId'] ) && is_scalar( $params['trackId'] ) ? sanitize_text_field( wp_unslash( (string) $params['trackId'] ) ) : '';
			$authority = isset( $params['Authority'] ) && is_scalar( $params['Authority'] ) ? sanitize_text_field( wp_unslash( (string) $params['Authority'] ) ) : '';
			$status    = isset( $params['Status'] ) && is_scalar( $params['Status'] ) ? sanitize_text_field( wp_unslash( (string) $params['Status'] ) ) : '';
			$success   = isset( $params['success'] ) && is_scalar( $params['success'] ) ? sanitize_text_field( wp_unslash( (string) $params['success'] ) ) : '';
			$authority = $is_zibal ? ( '' !== $track_id ? $track_id : (string) $sub['authority'] ) : ( '' !== $authority ? $authority : (string) $sub['authority'] );
			$status    = $is_zibal ? $success : $status;

			// Authority برگشتی باید همان Authority ثبت‌شده سفارش باشد.
			if ( '' !== (string) $sub['authority'] && ! hash_equals( (string) $sub['authority'], $authority ) ) {
				MB_Plugin::log_security( 'authority_mismatch', array( 'ref' => $ref ) );
				MB_UI::render_standalone_result( false, 'شناسه پرداخت با سفارش هم‌خوانی ندارد.', MB_UI::app_url( '#/subscription-failed' ) );
				return;
			}
			$order_id = isset( $params['orderId'] ) && is_scalar( $params['orderId'] ) ? sanitize_text_field( wp_unslash( (string) $params['orderId'] ) ) : '';
			if ( $is_zibal && '' !== $order_id && $ref !== $order_id ) {
				MB_Plugin::log_security( 'order_mismatch', array( 'ref' => $ref ) );
				MB_UI::render_standalone_result( false, 'شماره سفارش با پرداخت هم‌خوانی ندارد.', MB_UI::app_url( '#/subscription-failed' ) );
				return;
			}

			$failed_callback = $is_zibal ? ( '' !== $status && '1' !== $status ) : ( 'NOK' === strtoupper( $status ) );
			if ( $failed_callback ) {
				$wpdb->update( MB_DB::t( 'subscriptions' ), array( 'status' => 'canceled' ), array( 'id' => (int) $sub['id'] ) );
				$message = $is_zibal ? 'پرداخت زیبال تکمیل نشد.' : 'پرداخت توسط کاربر لغو شد.';
			} else {
				$verify = $gateway->verify( $authority, (int) $sub['amount'] );
				if ( is_wp_error( $verify ) ) {
					$message = $verify->get_error_message();
					MB_Plugin::log_security( 'verify_failed', array( 'ref' => $ref, 'msg' => $message ) );
				} else {
					self::activate( (int) $sub['id'], (string) $verify['ref'] );
					$ok      = true;
					$message = 'اشتراک شما فعال شد.';
				}
			}
		}

		$hash = $ok ? '#/subscription-ok' : '#/subscription-failed';
		MB_UI::render_standalone_result( $ok, $message, MB_UI::app_url( $hash ) );
	}

	/**
	 * بازیابی پرداخت‌های نیمه‌کاره.
	 *
	 * در ایران بازگشت از درگاه گاهی قطع می‌شود و سفارش روی pending می‌ماند
	 * درحالی‌که پول کسر شده است. این متد سفارش‌های در انتظار کاربر را مستقیم
	 * از درگاه استعلام می‌کند و اگر پرداخت واقعاً انجام شده باشد، فعال می‌کند.
	 * verify درگاه‌ها idempotent است، پس تکرارش خطر ندارد.
	 *
	 * @return array{found:int,activated:int,message:string}
	 */
	public static function recover_pending( int $user_id ): array {
		global $wpdb;

		$rows = (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . MB_DB::t( 'subscriptions' ) . " WHERE user_id = %d AND status = 'pending' AND authority <> '' AND created_at > %s ORDER BY id DESC LIMIT 5",
				$user_id,
				gmdate( 'Y-m-d H:i:s', (int) current_time( 'timestamp' ) - 7 * DAY_IN_SECONDS )
			),
			ARRAY_A
		);

		if ( empty( $rows ) ) {
			return array( 'found' => 0, 'activated' => 0, 'message' => 'پرداخت در انتظاری پیدا نشد.' );
		}

		$activated = 0;
		foreach ( $rows as $sub ) {
			$gateway = self::gateway_for( (string) $sub['gateway'] );
			$result  = $gateway->verify( (string) $sub['authority'], (int) $sub['amount'] );
			if ( is_wp_error( $result ) ) {
				MB_Plugin::log_security( 'recover_not_paid', array( 'sub' => (int) $sub['id'], 'msg' => $result->get_error_message() ) );
				continue;
			}
			self::activate( (int) $sub['id'], (string) $result['ref'] );
			++$activated;
			MB_Plugin::log_security( 'recover_activated', array( 'sub' => (int) $sub['id'], 'user' => $user_id ) );
		}

		if ( $activated > 0 ) {
			return array(
				'found'     => count( $rows ),
				'activated' => $activated,
				'message'   => 'پرداختت پیدا و تأیید شد؛ اشتراک فعال است.',
			);
		}
		return array(
			'found'     => count( $rows ),
			'activated' => 0,
			'message'   => 'پرداخت تأییدشده‌ای پیدا نشد. اگر مبلغ از حسابت کم شده، از بخش پشتیبانی با شمارهٔ پیگیری اطلاع بده.',
		);
	}

	/** بستن سفارش‌های در انتظار که رها شده‌اند. */
	public static function cleanup_stale_pending(): int {
		global $wpdb;
		return (int) $wpdb->query(
			$wpdb->prepare(
				// created_at با current_time() (وقت سایت) نوشته می‌شود، پس مقایسه با
				// gmdate() پنجره را به اندازهٔ اختلاف منطقهٔ زمانی کج می‌کرد.
				'UPDATE ' . MB_DB::t( 'subscriptions' ) . " SET status = 'canceled' WHERE status = 'pending' AND created_at < %s",
				gmdate( 'Y-m-d H:i:s', (int) current_time( 'timestamp' ) - 2 * HOUR_IN_SECONDS )
			)
		);
	}

	/** فعال‌سازی اشتراک (۳۰ روز از الان یا ادامه دوره فعلی). */
	public static function activate( int $sub_id, string $payment_ref = '' ): void {
		global $wpdb;
		$sub = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . MB_DB::t( 'subscriptions' ) . ' WHERE id = %d', $sub_id ), ARRAY_A );
		if ( ! $sub ) {
			return;
		}

		$months = max( 1, (int) ( $sub['term_months'] ?? 1 ) );
		$base = time();
		$prev = self::get_subscription( (int) $sub['user_id'] );
		if ( $prev && ! empty( $prev['expires_at'] ) && strtotime( $prev['expires_at'] ) > time() && in_array( $prev['status'], array( 'active', 'grace' ), true ) ) {
			$base = strtotime( $prev['expires_at'] ); // تمدید: روی باقیمانده اضافه می‌شود.
		}

		$wpdb->update(
			MB_DB::t( 'subscriptions' ),
			array(
				'status'         => 'active',
				'plan'           => 'pro',
				'started_at'     => current_time( 'mysql' ),
				// ۳۰ روز ثابت باعث می‌شد «شش‌ماهه» فقط ۱۸۰ روز باشد.
				'expires_at'     => gmdate( 'Y-m-d H:i:s', (int) strtotime( '+' . $months . ' months', $base ) ),
				'last_verify_at' => current_time( 'mysql' ),
				'site_hash'      => (string) get_option( 'mb_site_hash' ),
				'install_token'  => (string) get_option( 'mb_install_token' ),
				'couple_id'      => MB_Couple::id_for( (int) $sub['user_id'] ),
			),
			array( 'id' => $sub_id )
		);

		// اشتراک‌های قبلی به expired می‌روند تا فقط یک ردیف فعال بماند.
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . MB_DB::t( 'subscriptions' ) . " SET status = 'expired' WHERE user_id = %d AND id <> %d AND status IN ('active','grace')", (int) $sub['user_id'], $sub_id ) );

		self::bump_coupon( $sub['coupon'] );
		self::mark_coupon_used( (int) $sub['user_id'], $sub['coupon'] ? (string) $sub['coupon'] : null );
		delete_transient( 'mb_pro_' . (int) $sub['user_id'] );
		self::flush_couple_cache( (int) $sub['user_id'] );

		// N2: اگر خریدار «همراه» است نقشش تغییر نمی‌کند؛ اشتراک خانواده هر دو را پوشش می‌دهد.
		$user = get_userdata( (int) $sub['user_id'] );
		if ( $user && ! in_array( MB_ROLE_WOMAN, (array) $user->roles, true ) && ! user_can( $user->ID, 'manage_options' ) && ! MB_Privacy::is_partner( (int) $user->ID ) ) {
			$user->add_role( MB_ROLE_WOMAN );
		}

		// M11: اگر این کاربر با کد معرف آمده بود، کوپن پاداش معرف ساخته می‌شود.
		MB_Referral::on_pro_activated( (int) $sub['user_id'], $sub_id );

		MB_DB::add_notification( (int) $sub['user_id'], 'sub_active', array( 'ref' => $payment_ref ) );

		// N2: عضو دیگر خانواده هم باخبر می‌شود (بدون هیچ جزئیات پرداخت).
		$spouse = MB_Couple::spouse_of( (int) $sub['user_id'] );
		if ( $spouse > 0 ) {
			MB_DB::add_notification( $spouse, 'sub_active', array( 'family' => 1 ) );
		}
		MB_Notify::send_invoice( $sub_id, $payment_ref );
		MB_Plugin::log_security( 'sub_activated', array( 'user' => (int) $sub['user_id'], 'ref' => $payment_ref ) );
	}

	public static function set_status( int $sub_id, string $status ): void {
		global $wpdb;
		if ( ! in_array( $status, array( 'pending', 'active', 'grace', 'expired', 'canceled' ), true ) ) {
			return;
		}
		$uid = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM ' . MB_DB::t( 'subscriptions' ) . ' WHERE id = %d', $sub_id ) );
		$wpdb->update( MB_DB::t( 'subscriptions' ), array( 'status' => $status ), array( 'id' => $sub_id ), array( '%s' ), array( '%d' ) );
		if ( $uid ) {
			delete_transient( 'mb_pro_' . $uid );
			self::flush_couple_cache( $uid );
		}
		MB_Plugin::log_security( 'sub_status_manual', array( 'sub' => $sub_id, 'status' => $status ) );
	}

	/** افزودن دستی اشتراک از پنل ادمین. */
	public static function grant_manual( int $user_id, int $days = 30 ): void {
		global $wpdb;
		$ref = 'MANUAL-' . $user_id . '-' . time();
		$wpdb->insert(
			MB_DB::t( 'subscriptions' ),
			array(
				'user_id'       => $user_id,
				'couple_id'     => MB_Couple::id_for( $user_id ),
				'plan'          => 'pro',
				'status'        => 'active',
				'gateway'       => 'manual',
				'ref_id'        => $ref,
				'amount'        => 0,
				'site_hash'     => (string) get_option( 'mb_site_hash' ),
				'install_token' => (string) get_option( 'mb_install_token' ),
				'started_at'    => current_time( 'mysql' ),
				'expires_at'    => gmdate( 'Y-m-d H:i:s', time() + max( 1, $days ) * DAY_IN_SECONDS ),
				'remind'        => 1,
				'created_at'    => current_time( 'mysql' ),
			)
		);
		delete_transient( 'mb_pro_' . $user_id );
		self::flush_couple_cache( $user_id );
		MB_Plugin::log_security( 'sub_manual_grant', array( 'user' => $user_id, 'days' => $days ) );
	}

	/* --------------------------------------------------------------------- */
	/* چرخه عمر                                                              */
	/* --------------------------------------------------------------------- */

	/** ثبت هوک‌ها؛ در هر بار بارگذاری پلاگین صدا زده می‌شود. */
	public static function hooks(): void {
		add_action( 'mb_nightly', array( __CLASS__, 'run_nightly' ) );
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( 'mb_nightly' ) ) {
			wp_schedule_event( time() + 600, 'daily', 'mb_nightly' );
		}
	}

	public static function unschedule(): void {
		$ts = wp_next_scheduled( 'mb_nightly' );
		if ( $ts ) {
			wp_unschedule_event( $ts, 'mb_nightly' );
		}
	}

	/** انتقال وضعیت کاربر جاری در init (با کش transient). */
	public static function maybe_expire_current_user(): void {
		if ( ! is_user_logged_in() ) {
			return;
		}
		$uid = get_current_user_id();
		if ( get_transient( 'mb_lifecycle_' . $uid ) ) {
			return;
		}
		set_transient( 'mb_lifecycle_' . $uid, 1, self::CACHE_TTL );
		self::transition( $uid );
	}

	public static function transition( int $user_id ): void {
		global $wpdb;
		$sub = self::get_subscription( $user_id );
		if ( ! $sub || empty( $sub['expires_at'] ) ) {
			return;
		}
		$exp = strtotime( $sub['expires_at'] );
		if ( 'active' === $sub['status'] && $exp < time() ) {
			$wpdb->update( MB_DB::t( 'subscriptions' ), array( 'status' => 'grace' ), array( 'id' => (int) $sub['id'] ), array( '%s' ), array( '%d' ) );
			delete_transient( 'mb_pro_' . $user_id );
			MB_DB::add_notification( $user_id, 'sub_grace', array() );
		} elseif ( 'grace' === $sub['status'] && $exp + self::GRACE_DAYS * DAY_IN_SECONDS < time() ) {
			$wpdb->update( MB_DB::t( 'subscriptions' ), array( 'status' => 'expired' ), array( 'id' => (int) $sub['id'] ), array( '%s' ), array( '%d' ) );
			delete_transient( 'mb_pro_' . $user_id );
			MB_DB::add_notification( $user_id, 'sub_expired', array() );
		}
	}

	/** کرون شبانه: انتقال وضعیت‌ها، یادآور تمدید، بازراستیابی رسید (L3). */
	public static function run_nightly(): void {
		global $wpdb;

		$rows = $wpdb->get_results( 'SELECT * FROM ' . MB_DB::t( 'subscriptions' ) . " WHERE status IN ('active','grace')", ARRAY_A );
		foreach ( (array) $rows as $sub ) {
			self::transition( (int) $sub['user_id'] );

			if ( (int) $sub['remind'] === 1 && ! empty( $sub['expires_at'] ) ) {
				$days = (int) floor( ( strtotime( $sub['expires_at'] ) - time() ) / DAY_IN_SECONDS );
				if ( in_array( $days, array( 3, 1, 0 ), true ) ) {
					MB_Notify::renewal_reminder( (int) $sub['user_id'], $days );
				}
			}
		}

		// L3: بازبینی تصادفی رسیدها.
		$sample = $wpdb->get_results( 'SELECT * FROM ' . MB_DB::t( 'subscriptions' ) . " WHERE status = 'active' AND gateway IN ('zarinpal','zibal') AND authority <> '' ORDER BY RAND() LIMIT 5", ARRAY_A );
		foreach ( (array) $sample as $sub ) {
			$check = self::gateway_for( (string) $sub['gateway'] )->reverify( (string) $sub['authority'], (int) $sub['amount'] );
			$wpdb->update( MB_DB::t( 'subscriptions' ), array( 'last_verify_at' => current_time( 'mysql' ) ), array( 'id' => (int) $sub['id'] ), array( '%s' ), array( '%d' ) );
			if ( false === $check ) {
				$wpdb->update( MB_DB::t( 'subscriptions' ), array( 'status' => 'canceled' ), array( 'id' => (int) $sub['id'] ), array( '%s' ), array( '%d' ) );
				delete_transient( 'mb_pro_' . (int) $sub['user_id'] );
				MB_Plugin::log_security( 'receipt_mismatch', array( 'sub' => (int) $sub['id'], 'user' => (int) $sub['user_id'] ) );
				MB_Notify::admin_alert( 'ناهمخوانی رسید پرداخت', sprintf( 'اشتراک #%d کاربر %d با درگاه هم‌خوانی نداشت و لغو شد.', (int) $sub['id'], (int) $sub['user_id'] ) );
			}
		}

		// سفارش‌های رهاشده بسته می‌شوند تا جدول و آمار تمیز بماند.
		self::cleanup_stale_pending();

		// انقضای دعوت‌نامه‌ها: هشدار ۶ ساعت مانده.
		$invites = $wpdb->get_results( 'SELECT * FROM ' . MB_DB::t( 'invites' ) . ' WHERE revoked = 0 AND accepted_by IS NULL', ARRAY_A );
		foreach ( (array) $invites as $inv ) {
			$left = strtotime( (string) $inv['expires_at'] ) - time();
			if ( $left > 0 && $left <= 6 * HOUR_IN_SECONDS ) {
				MB_Notify::invite_expiring( (int) $inv['wife_id'] );
			}
		}
	}

	/** آمار ادمین. */
	public static function admin_stats(): array {
		global $wpdb;
		// شروع ماه شمسی جاری (نه ماه میلادی) چون گزارش درآمد فارسی است.
		list( $mb_jy, $mb_jm ) = MB_Jalali::g2j( MB_Jalali::today() );
		$month_start = MB_Jalali::j2g( (int) $mb_jy, (int) $mb_jm, 1 ) . ' 00:00:00';
		$count       = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . MB_DB::t( 'subscriptions' ) . " WHERE status IN ('active','grace') AND started_at >= %s", $month_start ) );
		$sum         = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount),0) FROM ' . MB_DB::t( 'subscriptions' ) . " WHERE status IN ('active','grace') AND started_at >= %s", $month_start ) );
		$active      = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . MB_DB::t( 'subscriptions' ) . " WHERE status = 'active'" );
		return array(
			'month_count' => $count,
			'month_sum'   => $sum,
			'active'      => $active,
			'mrr'         => $active * self::price(),
		);
	}

	public static function list_subscriptions( string $status = '', int $limit = 50 ): array {
		global $wpdb;
		if ( '' !== $status && in_array( $status, array( 'pending', 'active', 'grace', 'expired', 'canceled' ), true ) ) {
			return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . MB_DB::t( 'subscriptions' ) . ' WHERE status = %s ORDER BY id DESC LIMIT %d', $status, $limit ), ARRAY_A );
		}
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . MB_DB::t( 'subscriptions' ) . ' ORDER BY id DESC LIMIT %d', $limit ), ARRAY_A );
	}

	public static function get_by_id( int $id ): ?array {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . MB_DB::t( 'subscriptions' ) . ' WHERE id = %d', $id ), ARRAY_A );
		return $row ? $row : null;
	}
}
