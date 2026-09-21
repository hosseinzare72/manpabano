<?php
/**
 * N2 — اشتراک خانوادگی (زوجی).
 *
 * قاعده: هر زوج یک couple_id دارد. هنگام پذیرش دعوت، couple_id بانو و همسر
 * یکی می‌شود و روی همهٔ ردیف‌های mb_subscriptions هر دو مهر می‌خورد.
 *
 * is_pro(uid) = اشتراک فعال خود کاربر  یا  اشتراک فعال همسرِ هم‌couple.
 * خرید برای هر دو نقش باز است (آقا هم می‌تواند بخرد و هدیه بدهد).
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Couple {

	const LABEL = 'اشتراک خانوادهٔ ماه‌بانو';

	const META = 'mb_couple_id';

	/* --------------------------------------------------------------------- */
	/* شناسهٔ زوج                                                            */
	/* --------------------------------------------------------------------- */

	/** couple_id کاربر؛ اگر نداشت ساخته و ذخیره می‌شود. */
	public static function id_for( int $user_id ): string {
		if ( $user_id <= 0 ) {
			return '';
		}
		$id = (string) get_user_meta( $user_id, self::META, true );
		if ( '' !== $id ) {
			return $id;
		}
		$id = 'c' . substr( hash( 'sha256', $user_id . '|' . wp_generate_password( 18, false, false ) ), 0, 23 );
		update_user_meta( $user_id, self::META, $id );
		self::stamp_subscriptions( $user_id, $id );
		return $id;
	}

	/** couple_id موجود، بدون ساختن. */
	public static function peek( int $user_id ): string {
		return $user_id > 0 ? (string) get_user_meta( $user_id, self::META, true ) : '';
	}

	/**
	 * پیوند زوج: couple_id بانو مرجع است و به همسر تحمیل می‌شود.
	 * روی MB_Privacy::accept_invite صدا زده می‌شود.
	 */
	public static function link( int $wife_id, int $partner_id ): string {
		if ( $wife_id <= 0 || $partner_id <= 0 || $wife_id === $partner_id ) {
			return '';
		}
		$couple = self::id_for( $wife_id );
		update_user_meta( $partner_id, self::META, $couple );
		self::stamp_subscriptions( $wife_id, $couple );
		self::stamp_subscriptions( $partner_id, $couple );

		// وضعیت Pro هر دو باید بلافاصله بازمحاسبه شود.
		delete_transient( 'mb_pro_' . $wife_id );
		delete_transient( 'mb_pro_' . $partner_id );

		MB_Plugin::log_security( 'couple_linked', array( 'wife' => $wife_id, 'partner' => $partner_id ) );
		return $couple;
	}

	/** قطع پیوند (لغو دسترسی همسر): couple_id همسر تازه می‌شود. */
	public static function unlink( int $partner_id ): void {
		if ( $partner_id <= 0 ) {
			return;
		}
		delete_user_meta( $partner_id, self::META );
		$fresh = self::id_for( $partner_id );
		self::stamp_subscriptions( $partner_id, $fresh );
		delete_transient( 'mb_pro_' . $partner_id );
		MB_Plugin::log_security( 'couple_unlinked', array( 'partner' => $partner_id ) );
	}

	/** مهر couple_id روی همهٔ ردیف‌های اشتراک کاربر. */
	public static function stamp_subscriptions( int $user_id, string $couple ): void {
		global $wpdb;
		if ( $user_id <= 0 || '' === $couple ) {
			return;
		}
		$wpdb->update(
			MB_DB::t( 'subscriptions' ),
			array( 'couple_id' => $couple ),
			array( 'user_id' => $user_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/* --------------------------------------------------------------------- */
	/* اعضا                                                                  */
	/* --------------------------------------------------------------------- */

	/** شناسهٔ کاربر دیگر همین زوج (۰ اگر تنهاست). */
	public static function spouse_of( int $user_id ): int {
		if ( $user_id <= 0 ) {
			return 0;
		}

		// مسیر سریع و قابل اتکا: رابطهٔ دعوت‌نامه.
		if ( MB_Privacy::is_partner( $user_id ) ) {
			$wife = MB_Privacy::wife_of( $user_id );
			if ( $wife > 0 ) {
				return $wife;
			}
		} else {
			$partner = MB_Privacy::partner_of( $user_id );
			if ( $partner > 0 ) {
				return $partner;
			}
		}

		// مسیر پشتیبان: هم‌couple بودن (اگر دعوت بعداً بازسازی شده باشد).
		$couple = self::peek( $user_id );
		if ( '' === $couple ) {
			return 0;
		}
		$ids = get_users(
			array(
				'meta_key'   => self::META,
				'meta_value' => $couple,
				'fields'     => 'ID',
				'number'     => 3,
				'exclude'    => array( $user_id ),
			)
		);
		return empty( $ids ) ? 0 : (int) $ids[0];
	}

	/** اعضای زوج برای نمای خانواده در ادمین. */
	public static function members( int $user_id ): array {
		$out = array();
		foreach ( array_unique( array_filter( array( $user_id, self::spouse_of( $user_id ) ) ) ) as $id ) {
			$user  = get_userdata( (int) $id );
			$out[] = array(
				'id'      => (int) $id,
				'name'    => $user ? $user->display_name : '—',
				'email'   => $user ? $user->user_email : '',
				'role'    => MB_Privacy::is_partner( (int) $id ) ? 'همراه' : 'بانو',
				'is_pro'  => MB_Subscription::is_pro( (int) $id ),
				'own_sub' => MB_Subscription::own_active_subscription( (int) $id ),
			);
		}
		return $out;
	}

	/**
	 * اشتراک فعالی که این زوج را پوشش می‌دهد (از هر کدام از دو نفر).
	 *
	 * @return array|null ردیف mb_subscriptions یا null.
	 */
	public static function covering_subscription( int $user_id ): ?array {
		global $wpdb;

		$own = MB_Subscription::own_active_subscription( $user_id );
		if ( $own ) {
			return $own;
		}

		$spouse = self::spouse_of( $user_id );
		if ( $spouse > 0 ) {
			$sub = MB_Subscription::own_active_subscription( $spouse );
			if ( $sub ) {
				return $sub;
			}
		}

		// پشتیبان: هر ردیف فعال با همین couple_id.
		$couple = self::peek( $user_id );
		if ( '' === $couple ) {
			return null;
		}
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . MB_DB::t( 'subscriptions' ) . " WHERE couple_id = %s AND status IN ('active','grace') ORDER BY expires_at DESC LIMIT 1",
				$couple
			),
			ARRAY_A
		);
		return $row ? $row : null;
	}

	/** آیا اشتراک پوشش‌دهنده را همسر خریده است؟ (برچسب «هدیهٔ همسر») */
	public static function is_gifted( int $user_id ): bool {
		$sub = self::covering_subscription( $user_id );
		return (bool) ( $sub && (int) $sub['user_id'] !== $user_id );
	}

	/** نام خریدار اشتراک خانواده. */
	public static function buyer_name( int $user_id ): string {
		$sub = self::covering_subscription( $user_id );
		if ( ! $sub ) {
			return '';
		}
		$user = get_userdata( (int) $sub['user_id'] );
		return $user ? ( $user->first_name ? $user->first_name : $user->display_name ) : '';
	}
}
