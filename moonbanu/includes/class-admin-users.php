<?php
/**
 * تب «کاربران» پنل مدیریت: مدیریت کامل کاربران اپ و اشتراک‌هایشان.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Admin_Users {

	const PER_PAGE = 25;

	/* --------------------------------------------------------------------- */
	/* داده                                                                 */
	/* --------------------------------------------------------------------- */

	/** کاربران اپ با جست‌وجو و صفحه‌بندی. */
	public static function query( string $search = '', string $filter = '', int $paged = 1 ): array {
		$args = array(
			'number'  => self::PER_PAGE,
			'offset'  => max( 0, ( max( 1, $paged ) - 1 ) * self::PER_PAGE ),
			'orderby' => 'registered',
			'order'   => 'DESC',
			'fields'  => 'all',
		);

		if ( 'partner' === $filter ) {
			$args['role'] = MB_ROLE_PARTNER;
		} elseif ( 'woman' === $filter ) {
			$args['role'] = MB_ROLE_WOMAN;
		} elseif ( 'app' === $filter || '' === $filter ) {
			$args['role__in'] = array( MB_ROLE_WOMAN, MB_ROLE_PARTNER, 'subscriber' );
		}

		if ( '' !== $search ) {
			$args['search']         = '*' . $search . '*';
			$args['search_columns'] = array( 'user_email', 'user_login', 'display_name', 'user_nicename' );
			unset( $args['role__in'] );
		}

		$q     = new WP_User_Query( $args );
		$users = (array) $q->get_results();

		// فیلترهای مبتنی بر اشتراک روی همین صفحه اعمال می‌شوند.
		if ( in_array( $filter, array( 'pro', 'free', 'grace' ), true ) ) {
			$users = array_values(
				array_filter(
					$users,
					static function ( $user ) use ( $filter ) {
						$sub = MB_Subscription::get_subscription( (int) $user->ID );
						$is  = $sub && in_array( (string) $sub['status'], array( 'active', 'grace' ), true );
						if ( 'pro' === $filter ) {
							return $is;
						}
						if ( 'grace' === $filter ) {
							return $sub && 'grace' === (string) $sub['status'];
						}
						return ! $is;
					}
				)
			);
		}

		return array( 'users' => $users, 'total' => (int) $q->get_total() );
	}

	/** خلاصهٔ یک کاربر برای جدول. */
	public static function row_data( WP_User $user ): array {
		$uid = (int) $user->ID;
		$sub = MB_Subscription::get_subscription( $uid );
		$pro = MB_Subscription::is_pro( $uid );

		return array(
			'id'        => $uid,
			'name'      => $user->first_name ? $user->first_name : $user->display_name,
			'email'     => (string) $user->user_email,
			'mobile'    => (string) get_user_meta( $uid, 'mb_mobile', true ),
			'roles'     => (array) $user->roles,
			'joined'    => MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $user->user_registered ) ), 'num' ),
			'onboarded' => MB_DB::is_onboarded( $uid ),
			'sub'       => $sub,
			'pro'       => $pro,
			'expires'   => $sub && $sub['expires_at'] ? MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $sub['expires_at'] ) ), 'num' ) : '',
			'tickets'   => count( MB_Support::user_tickets( $uid, 50 ) ),
		);
	}

	/* --------------------------------------------------------------------- */
	/* اقدام‌ها                                                             */
	/* --------------------------------------------------------------------- */

	/**
	 * اجرای اقدام روی کاربر. برمی‌گرداند: کلید پیام.
	 */
	public static function handle_action( string $action, array $post ): string {
		$uid = isset( $post['user_id'] ) ? absint( $post['user_id'] ) : 0;

		switch ( $action ) {
			case 'user_sub_add':
				if ( ! $uid || ! get_userdata( $uid ) ) {
					return 'nouser';
				}
				$days = self::days_from_input( $post );
				MB_Subscription::grant_manual( $uid, $days );
				MB_DB::add_notification( $uid, 'sub_active', array( 'title' => 'اشتراک تو فعال شد', 'body' => 'نسخهٔ پیشرفته برای ' . MB_Jalali::fa_num( $days ) . ' روز فعال است.' ) );
				return 'granted';

			case 'user_sub_extend':
				if ( ! $uid ) {
					return 'nouser';
				}
				$days = self::days_from_input( $post );
				return self::extend( $uid, $days ) ? 'extended' : 'nosub';

			case 'user_sub_revoke':
				if ( ! $uid ) {
					return 'nouser';
				}
				return self::revoke( $uid ) ? 'revoked' : 'nosub';

			case 'user_sub_delete':
				$sub_id = isset( $post['sub_id'] ) ? absint( $post['sub_id'] ) : 0;
				return self::delete_subscription( $sub_id ) ? 'sub_deleted' : 'nosub';

			case 'user_role':
				if ( ! $uid ) {
					return 'nouser';
				}
				return self::set_role( $uid, sanitize_key( (string) ( $post['new_role'] ?? '' ) ) ) ? 'role' : 'nouser';

			case 'user_reset_onboarding':
				if ( ! $uid ) {
					return 'nouser';
				}
				MB_DB::save_profile( $uid, array( 'onboarded' => 0 ) );
				delete_transient( 'mb_stats_' . $uid );
				return 'onboarding_reset';

			case 'user_send_reset':
				$user = $uid ? get_userdata( $uid ) : null;
				if ( ! $user ) {
					return 'nouser';
				}
				$code = (string) wp_rand( 100000, 999999 );
				update_user_meta( $uid, 'mb_reset_hash', wp_hash_password( $code ) );
				update_user_meta( $uid, 'mb_reset_exp', time() + 30 * MINUTE_IN_SECONDS );
				update_user_meta( $uid, 'mb_reset_tries', 0 );
				MB_Notify::reset_code( $uid, $code );
				return 'reset_sent';

			case 'user_purge':
				if ( ! $uid ) {
					return 'nouser';
				}
				self::purge_user_data( $uid );
				return 'purged';

			case 'user_delete':
				if ( ! $uid || user_can( $uid, 'manage_options' ) ) {
					return 'nodelete';
				}
				self::purge_user_data( $uid );
				require_once ABSPATH . 'wp-admin/includes/user.php';
				wp_delete_user( $uid );
				return 'user_deleted';
		}
		return '';
	}

	/** روز از ورودی (روز مستقیم یا ماه). */
	private static function days_from_input( array $post ): int {
		$months = isset( $post['grant_months'] ) ? absint( $post['grant_months'] ) : 0;
		if ( $months > 0 ) {
			return max( 1, min( 3650, $months * 30 ) );
		}
		$days = isset( $post['grant_days'] ) ? absint( $post['grant_days'] ) : 30;
		return max( 1, min( 3650, $days ) );
	}

	/** افزودن روز به اشتراک فعلی (یا ساخت تازه). */
	public static function extend( int $user_id, int $days ): bool {
		global $wpdb;
		$sub = MB_Subscription::get_subscription( $user_id );
		if ( ! $sub ) {
			MB_Subscription::grant_manual( $user_id, $days );
			return true;
		}
		$base = ! empty( $sub['expires_at'] ) ? strtotime( (string) $sub['expires_at'] ) : time();
		if ( $base < time() ) {
			$base = time();
		}
		$wpdb->update(
			MB_DB::t( 'subscriptions' ),
			array(
				'status'     => 'active',
				'plan'       => 'pro',
				'expires_at' => gmdate( 'Y-m-d H:i:s', $base + $days * DAY_IN_SECONDS ),
			),
			array( 'id' => (int) $sub['id'] ),
			array( '%s', '%s', '%s' ),
			array( '%d' )
		);
		delete_transient( 'mb_pro_' . $user_id );
		MB_DB::add_notification( $user_id, 'sub_active', array( 'title' => 'اشتراک تو تمدید شد', 'body' => MB_Jalali::fa_num( $days ) . ' روز به اشتراکت اضافه شد.' ) );
		MB_Plugin::log_security( 'sub_extended', array( 'user' => $user_id, 'days' => $days ) );
		return true;
	}

	/** پایان فوری اشتراک. */
	public static function revoke( int $user_id ): bool {
		global $wpdb;
		$sub = MB_Subscription::get_subscription( $user_id );
		if ( ! $sub ) {
			return false;
		}
		$wpdb->update(
			MB_DB::t( 'subscriptions' ),
			array( 'status' => 'expired', 'expires_at' => current_time( 'mysql', true ) ),
			array( 'id' => (int) $sub['id'] ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		delete_transient( 'mb_pro_' . $user_id );
		MB_DB::add_notification( $user_id, 'sub_expired', array( 'title' => 'اشتراک پیشرفته خاموش شد', 'body' => 'همهٔ داده‌هایت محفوظ است.' ) );
		MB_Plugin::log_security( 'sub_revoked', array( 'user' => $user_id ) );
		return true;
	}

	/** حذف کامل یک ردیف اشتراک. */
	public static function delete_subscription( int $sub_id ): bool {
		global $wpdb;
		if ( $sub_id <= 0 ) {
			return false;
		}
		$uid = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM ' . MB_DB::t( 'subscriptions' ) . ' WHERE id = %d', $sub_id ) );
		$wpdb->delete( MB_DB::t( 'subscriptions' ), array( 'id' => $sub_id ), array( '%d' ) );
		if ( $uid ) {
			delete_transient( 'mb_pro_' . $uid );
		}
		MB_Plugin::log_security( 'sub_deleted', array( 'sub' => $sub_id, 'user' => $uid ) );
		return true;
	}

	/** تغییر نقش اپ. */
	public static function set_role( int $user_id, string $role ): bool {
		$allowed = array( MB_ROLE_WOMAN, MB_ROLE_PARTNER, 'subscriber' );
		$user    = get_userdata( $user_id );
		if ( ! $user || ! in_array( $role, $allowed, true ) || user_can( $user_id, 'manage_options' ) ) {
			return false;
		}
		$user->set_role( $role );
		delete_transient( 'mb_pro_' . $user_id );
		MB_Plugin::log_security( 'user_role_changed', array( 'user' => $user_id, 'role' => $role ) );
		return true;
	}

	/** پاک‌کردن همهٔ دادهٔ اپ یک کاربر (بدون حذف حساب وردپرس). */
	public static function purge_user_data( int $user_id ): void {
		global $wpdb;
		foreach ( array( 'profile', 'logs', 'cycles', 'notifications', 'questions' ) as $table ) {
			$wpdb->delete( MB_DB::t( $table ), array( 'user_id' => $user_id ), array( '%d' ) );
		}
		$wpdb->delete( MB_DB::t( 'invites' ), array( 'wife_id' => $user_id ), array( '%d' ) );
		$wpdb->delete( MB_DB::t( 'subscriptions' ), array( 'user_id' => $user_id ), array( '%d' ) );
		if ( class_exists( 'MB_Support' ) ) {
			MB_Support::purge_user( $user_id );
		}
		foreach ( array( 'mb_mobile', 'mb_share_defaults', 'mb_saved_articles', 'mb_partner_state', 'mb_reset_hash', 'mb_reset_exp', 'mb_reset_tries' ) as $meta ) {
			delete_user_meta( $user_id, $meta );
		}
		delete_transient( 'mb_pro_' . $user_id );
		delete_transient( 'mb_stats_' . $user_id );
		MB_Plugin::log_security( 'user_data_purged', array( 'user' => $user_id ) );
	}

	/* --------------------------------------------------------------------- */
	/* رندر                                                                 */
	/* --------------------------------------------------------------------- */

	public static function render(): void {
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$filter = isset( $_GET['who'] ) ? sanitize_key( wp_unslash( $_GET['who'] ) ) : '';
		$paged  = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$detail = isset( $_GET['user'] ) ? absint( $_GET['user'] ) : 0;

		if ( $detail > 0 && get_userdata( $detail ) ) {
			self::render_detail( $detail );
			return;
		}

		$result = self::query( $search, $filter, $paged );
		$stats  = MB_Subscription::admin_stats();

		echo '<div class="mb-cards" style="display:flex;gap:12px;flex-wrap:wrap;margin:16px 0">';
		foreach ( array(
			'کاربران اپ'     => number_format_i18n( self::count_app_users() ),
			'اشتراک فعال'    => number_format_i18n( (int) $stats['active'] ),
			'تیکت باز'       => number_format_i18n( MB_Support::admin_count( 'open' ) ),
			'کوپن فعال'      => number_format_i18n( self::count_active_coupons() ),
		) as $label => $value ) {
			echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:10px;padding:14px 18px;min-width:170px"><div style="color:#646970;font-size:12px">' . esc_html( $label ) . '</div><div style="font-size:20px;font-weight:700">' . esc_html( $value ) . '</div></div>';
		}
		echo '</div>';

		// فیلترها + جست‌وجو.
		echo '<p>';
		foreach ( array( '' => 'همه کاربران اپ', 'pro' => 'اشتراک فعال', 'grace' => 'مهلت ارفاق', 'free' => 'رایگان', 'woman' => 'بانوان', 'partner' => 'همراهان' ) as $key => $label ) {
			echo '<a class="button' . ( $filter === $key ? ' button-primary' : '' ) . '" style="margin-inline-end:6px" href="'
				. esc_url( add_query_arg( array( 'page' => 'moonbanu', 'tab' => 'users', 'who' => $key ), admin_url( 'admin.php' ) ) ) . '">' . esc_html( $label ) . '</a>';
		}
		echo '</p>';

		echo '<form method="get" action="' . esc_url( admin_url( 'admin.php' ) ) . '" style="margin:10px 0">';
		echo '<input type="hidden" name="page" value="moonbanu"><input type="hidden" name="tab" value="users">';
		echo '<input type="search" name="s" value="' . esc_attr( $search ) . '" placeholder="جست‌وجوی ایمیل، نام یا نام کاربری" style="min-width:280px"> ';
		submit_button( 'جست‌وجو', 'secondary', 'submit', false );
		echo '</form>';

		echo '<table class="widefat striped"><thead><tr><th>کاربر</th><th>تماس</th><th>نقش</th><th>اشتراک</th><th>انقضا</th><th>عضویت</th><th>اقدام سریع</th></tr></thead><tbody>';
		if ( empty( $result['users'] ) ) {
			echo '<tr><td colspan="7">کاربری با این شرایط پیدا نشد.</td></tr>';
		}
		foreach ( $result['users'] as $user ) {
			$row  = self::row_data( $user );
			$link = add_query_arg( array( 'page' => 'moonbanu', 'tab' => 'users', 'user' => $row['id'] ), admin_url( 'admin.php' ) );

			echo '<tr>';
			echo '<td><strong><a href="' . esc_url( $link ) . '">' . esc_html( '' !== $row['name'] ? $row['name'] : '#' . $row['id'] ) . '</a></strong>';
			echo $row['onboarded'] ? '' : ' <span style="color:#b26b00;font-size:11px">(تنظیم نشده)</span>';
			echo '<br><span style="color:#646970;font-size:11px">#' . (int) $row['id'] . '</span></td>';
			echo '<td style="font-size:12px">' . esc_html( $row['email'] ) . ( '' !== $row['mobile'] ? '<br>' . esc_html( $row['mobile'] ) : '' ) . '</td>';
			echo '<td style="font-size:12px">' . esc_html( self::role_label( $row['roles'] ) ) . '</td>';
			echo '<td>' . self::plan_badge( $row ) . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput
			echo '<td style="font-size:12px">' . esc_html( '' !== $row['expires'] ? $row['expires'] : '—' ) . '</td>';
			echo '<td style="font-size:12px">' . esc_html( $row['joined'] ) . '</td>';

			echo '<td><div style="display:flex;gap:4px;flex-wrap:wrap">';
			self::mini_form( $row['id'], 'user_sub_add', 'یک ماه اشتراک', array( 'grant_months' => 1 ) );
			self::mini_form( $row['id'], 'user_sub_revoke', 'پایان اشتراک', array(), true );
			echo '<a class="button button-small" href="' . esc_url( $link ) . '">مدیریت</a>';
			echo '</div></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';

		self::pagination( (int) $result['total'], $paged, $search, $filter );

		echo '<hr><h2>افزودن اشتراک با ایمیل</h2>';
		MB_Admin::open_form( 'users' );
		echo '<input type="hidden" name="mb_action" value="sub_grant">';
		echo '<input type="email" name="grant_email" placeholder="ایمیل کاربر" required style="min-width:240px"> ';
		echo '<input type="number" name="grant_days" value="30" min="1" max="3650" style="width:90px"> روز ';
		submit_button( 'فعال‌سازی', 'secondary', 'submit', false );
		echo '</form>';

		self::render_coupons();
	}

	private static function pagination( int $total, int $paged, string $search, string $filter ): void {
		$pages = (int) ceil( $total / self::PER_PAGE );
		if ( $pages < 2 ) {
			return;
		}
		echo '<div class="tablenav"><div class="tablenav-pages">';
		for ( $i = 1; $i <= min( $pages, 20 ); $i++ ) {
			$url = add_query_arg(
				array( 'page' => 'moonbanu', 'tab' => 'users', 'paged' => $i, 's' => $search, 'who' => $filter ),
				admin_url( 'admin.php' )
			);
			echo '<a class="button' . ( $i === $paged ? ' button-primary' : '' ) . '" style="margin-inline-end:4px" href="' . esc_url( $url ) . '">' . esc_html( number_format_i18n( $i ) ) . '</a>';
		}
		echo '</div></div>';
	}

	/** فرم کوچک یک‌دکمه‌ای. */
	private static function mini_form( int $user_id, string $action, string $label, array $extra = array(), bool $danger = false ): void {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
		wp_nonce_field( 'mb_admin_save' );
		echo '<input type="hidden" name="action" value="mb_save"><input type="hidden" name="mb_tab" value="users">';
		echo '<input type="hidden" name="mb_action" value="' . esc_attr( $action ) . '">';
		echo '<input type="hidden" name="user_id" value="' . (int) $user_id . '">';
		foreach ( $extra as $key => $value ) {
			echo '<input type="hidden" name="' . esc_attr( sanitize_key( (string) $key ) ) . '" value="' . esc_attr( (string) $value ) . '">';
		}
		$class = $danger ? 'button button-small button-link-delete' : 'button button-small';
		echo '<button class="' . esc_attr( $class ) . '">' . esc_html( $label ) . '</button>';
		echo '</form>';
	}

	private static function plan_badge( array $row ): string {
		$sub    = $row['sub'];
		$status = $sub ? (string) $sub['status'] : 'free';
		$map    = array(
			'active'   => array( 'فعال', '#00694a', '#d5f5e8' ),
			'grace'    => array( 'ارفاق', '#8a5a00', '#fdf0d5' ),
			'expired'  => array( 'منقضی', '#8a2020', '#fbe3e3' ),
			'canceled' => array( 'لغو', '#5a5a5a', '#ececec' ),
			'pending'  => array( 'در انتظار', '#1f4e79', '#e0edf8' ),
			'free'     => array( 'رایگان', '#5a5a5a', '#f0f0f1' ),
		);
		list( $label, $fg, $bg ) = $map[ $status ] ?? $map['free'];
		return '<span style="display:inline-block;padding:2px 9px;border-radius:999px;font-size:11px;font-weight:700;color:'
			. esc_attr( $fg ) . ';background:' . esc_attr( $bg ) . '">' . esc_html( $label ) . '</span>';
	}

	private static function role_label( array $roles ): string {
		$map = array(
			MB_ROLE_WOMAN   => 'بانو',
			MB_ROLE_PARTNER => 'همراه',
			'subscriber'    => 'مشترک',
			'administrator' => 'مدیر',
		);
		$out = array();
		foreach ( $roles as $role ) {
			$out[] = $map[ $role ] ?? $role;
		}
		return implode( '، ', $out );
	}

	public static function count_app_users(): int {
		$q = new WP_User_Query(
			array(
				'role__in' => array( MB_ROLE_WOMAN, MB_ROLE_PARTNER, 'subscriber' ),
				'number'   => 1,
				'fields'   => 'ID',
			)
		);
		return (int) $q->get_total();
	}

	public static function count_active_coupons(): int {
		global $wpdb;
		return (int) $wpdb->get_var(
			'SELECT COUNT(*) FROM ' . MB_DB::t( 'coupons' ) . ' WHERE active = 1 AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())'
		);
	}

	/* --------------------------------------------------------------------- */
	/* صفحهٔ یک کاربر                                                        */
	/* --------------------------------------------------------------------- */

	private static function render_detail( int $user_id ): void {
		global $wpdb;
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		$row  = self::row_data( $user );
		$back = add_query_arg( array( 'page' => 'moonbanu', 'tab' => 'users' ), admin_url( 'admin.php' ) );

		echo '<p><a class="button" href="' . esc_url( $back ) . '">‹ بازگشت به فهرست</a></p>';
		echo '<h2>' . esc_html( '' !== $row['name'] ? $row['name'] : '#' . $user_id ) . ' ' . self::plan_badge( $row ) . '</h2>'; // phpcs:ignore WordPress.Security.EscapeOutput

		echo '<table class="form-table"><tbody>';
		echo '<tr><th>ایمیل</th><td>' . esc_html( $row['email'] ) . '</td></tr>';
		echo '<tr><th>موبایل</th><td>' . esc_html( '' !== $row['mobile'] ? $row['mobile'] : '—' ) . '</td></tr>';
		echo '<tr><th>نقش</th><td>' . esc_html( self::role_label( $row['roles'] ) ) . '</td></tr>';
		echo '<tr><th>تاریخ عضویت</th><td>' . esc_html( $row['joined'] ) . '</td></tr>';
		echo '<tr><th>تنظیم چرخه</th><td>' . ( $row['onboarded'] ? 'انجام شده' : 'انجام نشده' ) . '</td></tr>';
		echo '<tr><th>انقضای اشتراک</th><td>' . esc_html( '' !== $row['expires'] ? $row['expires'] : '—' ) . '</td></tr>';
		echo '<tr><th>درخواست‌های پشتیبانی</th><td>' . esc_html( number_format_i18n( (int) $row['tickets'] ) ) . '</td></tr>';
		echo '</tbody></table>';

		// افزودن / تمدید.
		echo '<h3>اشتراک</h3><div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">';
		MB_Admin::open_form( 'users' );
		echo '<input type="hidden" name="mb_action" value="user_sub_add"><input type="hidden" name="user_id" value="' . (int) $user_id . '">';
		echo '<label>اشتراک تازه: <select name="grant_months"><option value="1">۱ ماه</option><option value="3">۳ ماه</option><option value="6">۶ ماه</option><option value="12">۱۲ ماه</option></select></label> ';
		submit_button( 'افزودن اشتراک', 'primary', 'submit', false );
		echo '</form>';

		MB_Admin::open_form( 'users' );
		echo '<input type="hidden" name="mb_action" value="user_sub_extend"><input type="hidden" name="user_id" value="' . (int) $user_id . '">';
		echo '<label>تمدید: <input type="number" name="grant_days" value="30" min="1" max="3650" style="width:90px"> روز</label> ';
		submit_button( 'تمدید', 'secondary', 'submit', false );
		echo '</form>';

		MB_Admin::open_form( 'users' );
		echo '<input type="hidden" name="mb_action" value="user_sub_revoke"><input type="hidden" name="user_id" value="' . (int) $user_id . '">';
		submit_button( 'پایان فوری اشتراک', 'delete', 'submit', false );
		echo '</form>';
		echo '</div>';

		// تاریخچهٔ اشتراک.
		$subs = (array) $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . MB_DB::t( 'subscriptions' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT 40', $user_id ),
			ARRAY_A
		);
		echo '<h3>تاریخچهٔ اشتراک</h3><table class="widefat striped"><thead><tr><th>#</th><th>وضعیت</th><th>مدت</th><th>مبلغ</th><th>کوپن</th><th>درگاه</th><th>انقضا</th><th>حذف</th></tr></thead><tbody>';
		if ( empty( $subs ) ) {
			echo '<tr><td colspan="8">ردیفی ثبت نشده است.</td></tr>';
		}
		foreach ( $subs as $sub ) {
			echo '<tr><td>' . (int) $sub['id'] . '</td>';
			echo '<td>' . esc_html( MB_Subscription::status_label( (string) $sub['status'] ) ) . '</td>';
			echo '<td>' . esc_html( MB_Jalali::fa_num( max( 1, (int) $sub['term_months'] ) ) . ' ماه' ) . '</td>';
			echo '<td>' . esc_html( MB_Jalali::fa_num( number_format_i18n( (int) $sub['amount'] ) ) ) . '</td>';
			echo '<td>' . esc_html( (string) ( $sub['coupon'] ?? '' ) ) . '</td>';
			echo '<td>' . esc_html( (string) $sub['gateway'] ) . '</td>';
			echo '<td>' . esc_html( $sub['expires_at'] ? MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $sub['expires_at'] ) ), 'num' ) : '—' ) . '</td>';
			echo '<td>';
			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'این ردیف اشتراک برای همیشه حذف شود؟\')">';
			wp_nonce_field( 'mb_admin_save' );
			echo '<input type="hidden" name="action" value="mb_save"><input type="hidden" name="mb_tab" value="users"><input type="hidden" name="mb_action" value="user_sub_delete">';
			echo '<input type="hidden" name="sub_id" value="' . (int) $sub['id'] . '"><input type="hidden" name="user_id" value="' . (int) $user_id . '">';
			echo '<button class="button button-small button-link-delete">حذف</button></form></td></tr>';
		}
		echo '</tbody></table>';

		// نقش و ابزارهای حساب.
		echo '<h3>حساب</h3><div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">';
		MB_Admin::open_form( 'users' );
		echo '<input type="hidden" name="mb_action" value="user_role"><input type="hidden" name="user_id" value="' . (int) $user_id . '">';
		echo '<label>نقش: <select name="new_role">';
		foreach ( array( MB_ROLE_WOMAN => 'بانو', MB_ROLE_PARTNER => 'همراه', 'subscriber' => 'مشترک' ) as $key => $label ) {
			echo '<option value="' . esc_attr( $key ) . '"' . selected( in_array( $key, $row['roles'], true ), true, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></label> ';
		submit_button( 'ذخیرهٔ نقش', 'secondary', 'submit', false );
		echo '</form>';

		MB_Admin::open_form( 'users' );
		echo '<input type="hidden" name="mb_action" value="user_send_reset"><input type="hidden" name="user_id" value="' . (int) $user_id . '">';
		submit_button( 'ارسال کد بازیابی رمز', 'secondary', 'submit', false );
		echo '</form>';

		MB_Admin::open_form( 'users' );
		echo '<input type="hidden" name="mb_action" value="user_reset_onboarding"><input type="hidden" name="user_id" value="' . (int) $user_id . '">';
		submit_button( 'بازنشانی تنظیم چرخه', 'secondary', 'submit', false );
		echo '</form>';
		echo '</div>';

		echo '<h3 style="color:#8a2020">اقدام‌های خطرناک</h3><div style="display:flex;gap:10px;flex-wrap:wrap">';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'همهٔ داده‌های اپ این کاربر پاک شود؟ حساب باقی می‌ماند.\')">';
		wp_nonce_field( 'mb_admin_save' );
		echo '<input type="hidden" name="action" value="mb_save"><input type="hidden" name="mb_tab" value="users"><input type="hidden" name="mb_action" value="user_purge"><input type="hidden" name="user_id" value="' . (int) $user_id . '">';
		echo '<button class="button button-link-delete">پاک کردن دادهٔ اپ</button></form>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'حساب کاربر و همهٔ داده‌هایش برای همیشه حذف شود؟\')">';
		wp_nonce_field( 'mb_admin_save' );
		echo '<input type="hidden" name="action" value="mb_save"><input type="hidden" name="mb_tab" value="users"><input type="hidden" name="mb_action" value="user_delete"><input type="hidden" name="user_id" value="' . (int) $user_id . '">';
		echo '<button class="button button-link-delete">حذف کامل حساب</button></form>';
		echo '</div>';
	}

	/* --------------------------------------------------------------------- */
	/* کوپن‌ها                                                              */
	/* --------------------------------------------------------------------- */

	public static function render_coupons(): void {
		global $wpdb;
		echo '<hr><h2>کدهای تخفیف</h2>';
		echo '<p class="description">هر کد می‌تواند درصدی یا مبلغ ثابت (تومان) باشد. اگر هر دو پر شود، درصد اولویت دارد.</p>';

		$coupons = (array) $wpdb->get_results( 'SELECT * FROM ' . MB_DB::t( 'coupons' ) . ' ORDER BY active DESC, code ASC', ARRAY_A );
		echo '<table class="widefat striped"><thead><tr><th>کد</th><th>تخفیف</th><th>حداقل خرید</th><th>انقضا</th><th>سقف</th><th>مصرف</th><th>وضعیت</th><th>اقدام</th></tr></thead><tbody>';
		if ( empty( $coupons ) ) {
			echo '<tr><td colspan="8">کدی ثبت نشده است.</td></tr>';
		}
		foreach ( $coupons as $c ) {
			$off = (int) $c['percent'] > 0
				? MB_Jalali::fa_num( (int) $c['percent'] ) . '٪'
				: MB_Jalali::fa_num( number_format_i18n( (int) ( $c['amount_off'] ?? 0 ) ) ) . ' تومان';
			echo '<tr>';
			echo '<td><code>' . esc_html( (string) $c['code'] ) . '</code>' . ( ! empty( $c['note'] ) ? '<br><span style="font-size:11px;color:#646970">' . esc_html( (string) $c['note'] ) . '</span>' : '' ) . '</td>';
			echo '<td>' . esc_html( $off ) . '</td>';
			echo '<td>' . esc_html( (int) ( $c['min_amount'] ?? 0 ) > 0 ? MB_Jalali::fa_num( number_format_i18n( (int) $c['min_amount'] ) ) : '—' ) . '</td>';
			echo '<td>' . esc_html( $c['expires_at'] ? MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $c['expires_at'] ) ), 'num' ) : '—' ) . '</td>';
			echo '<td>' . esc_html( (int) $c['max_uses'] > 0 ? MB_Jalali::fa_num( (int) $c['max_uses'] ) : 'بی‌نهایت' ) . '</td>';
			echo '<td>' . esc_html( MB_Jalali::fa_num( (int) $c['uses'] ) ) . '</td>';
			echo '<td>' . ( (int) ( $c['active'] ?? 1 ) ? '<span style="color:#00694a;font-weight:700">فعال</span>' : '<span style="color:#8a2020">خاموش</span>' ) . '</td>';
			echo '<td><div style="display:flex;gap:4px">';

			MB_Admin::open_form( 'users' );
			echo '<input type="hidden" name="mb_action" value="coupon_toggle"><input type="hidden" name="coupon_code" value="' . esc_attr( (string) $c['code'] ) . '">';
			echo '<button class="button button-small">' . ( (int) ( $c['active'] ?? 1 ) ? 'خاموش' : 'روشن' ) . '</button></form>';

			echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" onsubmit="return confirm(\'این کد حذف شود؟\')">';
			wp_nonce_field( 'mb_admin_save' );
			echo '<input type="hidden" name="action" value="mb_save"><input type="hidden" name="mb_tab" value="users"><input type="hidden" name="mb_action" value="coupon_del">';
			echo '<input type="hidden" name="coupon_code" value="' . esc_attr( (string) $c['code'] ) . '">';
			echo '<button class="button button-small button-link-delete">حذف</button></form>';
			echo '</div></td></tr>';
		}
		echo '</tbody></table>';

		echo '<h3>افزودن یا ویرایش کد</h3>';
		MB_Admin::open_form( 'users' );
		echo '<input type="hidden" name="mb_action" value="coupon_add">';
		echo '<table class="form-table"><tbody>';
		echo '<tr><th>کد</th><td><input type="text" name="coupon_code" placeholder="MOON20" required dir="ltr"><p class="description">حروف بزرگ و انگلیسی. کد تکراری بازنویسی می‌شود.</p></td></tr>';
		echo '<tr><th>درصد تخفیف</th><td><input type="number" name="coupon_percent" value="20" min="0" max="100" style="width:90px"> ٪</td></tr>';
		echo '<tr><th>یا مبلغ ثابت</th><td><input type="number" name="coupon_amount" value="0" min="0" step="1000" style="width:130px"> تومان</td></tr>';
		echo '<tr><th>حداقل مبلغ خرید</th><td><input type="number" name="coupon_min" value="0" min="0" step="1000" style="width:130px"> تومان</td></tr>';
		echo '<tr><th>تاریخ انقضا</th><td><input type="date" name="coupon_expires"><p class="description">خالی = بدون انقضا.</p></td></tr>';
		echo '<tr><th>سقف استفاده</th><td><input type="number" name="coupon_max" value="0" min="0" style="width:90px"><p class="description">صفر = بی‌نهایت.</p></td></tr>';
		echo '<tr><th>یادداشت</th><td><input type="text" class="regular-text" name="coupon_note" placeholder="کمپین نوروز"></td></tr>';
		echo '<tr><th>فعال باشد</th><td><label><input type="checkbox" name="coupon_active" value="1" checked> بله</label></td></tr>';
		echo '</tbody></table>';
		submit_button( 'ذخیرهٔ کد تخفیف' );
		echo '</form>';
	}
}
