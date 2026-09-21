<?php
/**
 * درگاه پرداخت زیبال.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Gateway_Zibal extends MB_Gateway {

	public function id(): string {
		return 'zibal';
	}

	public function title(): string {
		return 'زیبال';
	}

	private function merchant(): string {
		return trim( (string) MB_Plugin::setting_secret( 'zibal_merchant', '' ) );
	}

	private function base(): string {
		return 'https://gateway.zibal.ir/';
	}

	public function create_request( int $amount, string $description, string $callback, array $meta = array() ) {
		$merchant = $this->merchant();
		if ( '' === $merchant ) {
			return new WP_Error( 'mb_gateway_cfg', 'کد مرچنت زیبال در تنظیمات ماه‌بانو وارد نشده است.' );
		}

		$body = array(
			'merchant'    => $merchant,
			'amount'      => $this->to_gateway_amount( $amount ),
			'callbackUrl' => $callback,
			'description' => $description,
		);

		if ( ! empty( $meta['ref'] ) ) {
			$body['orderId'] = (string) $meta['ref'];
		}

		$user = isset( $meta['user_id'] ) ? get_userdata( (int) $meta['user_id'] ) : null;
		if ( $user ) {
			$mobile = preg_replace( '/[^0-9]/', '', MB_Jalali::en_num( (string) get_user_meta( $user->ID, 'mb_mobile', true ) ) );
			if ( 10 <= strlen( $mobile ) ) {
				$body['mobile'] = $mobile;
			}
		}

		$data = $this->post_json( $this->base() . 'v1/request', $body );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$code     = isset( $data['result'] ) ? (int) $data['result'] : 0;
		$track_id = isset( $data['trackId'] ) ? (string) $data['trackId'] : '';
		$message  = isset( $data['message'] ) ? (string) $data['message'] : 'کد خطای ' . $code;

		if ( 100 !== $code || '' === $track_id ) {
			return new WP_Error( 'mb_gateway_req', 'زیبال درخواست را نپذیرفت: ' . $message );
		}

		return array(
			'authority' => $track_id,
			'redirect'  => $this->base() . 'start/' . rawurlencode( $track_id ),
		);
	}

	public function verify( string $authority, int $amount ) {
		if ( '' === $authority || '' === $this->merchant() ) {
			return new WP_Error( 'mb_gateway_verify', 'شناسه پرداخت یا کد مرچنت زیبال یافت نشد.' );
		}

		$data = $this->post_json(
			$this->base() . 'v1/verify',
			array(
				'merchant' => $this->merchant(),
				'trackId'  => is_numeric( $authority ) ? (int) $authority : $authority,
			)
		);
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$code = isset( $data['result'] ) ? (int) $data['result'] : 0;
		// نبودِ amount در پاسخ، پیش از این «مبلغ درست» فرض می‌شد و بررسی را بی‌اثر می‌کرد.
		$paid = isset( $data['amount'] ) ? (int) $data['amount'] : -1;
		$ref  = isset( $data['refNumber'] ) ? (string) $data['refNumber'] : $authority;
		$msg  = isset( $data['message'] ) ? (string) $data['message'] : 'کد ' . $code;

		// ۱۰۰ = موفق، ۲۰۱ = قبلاً تأیید شده؛ هر دو idempotent هستند.
		if ( in_array( $code, array( 100, 201 ), true ) ) {
			if ( $paid !== $this->to_gateway_amount( $amount ) ) {
				return new WP_Error( 'mb_gateway_amount', 'مبلغ پرداخت با مبلغ سفارش هم‌خوانی ندارد.' );
			}
			return array( 'ref' => '' !== $ref ? $ref : $authority, 'amount' => $amount );
		}

		return new WP_Error( 'mb_gateway_verify', 'تأیید پرداخت زیبال ناموفق بود: ' . $msg );
	}

	public function reverify( string $authority, int $amount ): ?bool {
		if ( '' === $authority || '' === $this->merchant() ) {
			return null;
		}

		$data = $this->post_json(
			$this->base() . 'v1/inquiry',
			array(
				'merchant' => $this->merchant(),
				'trackId'  => is_numeric( $authority ) ? (int) $authority : $authority,
			),
			15
		);
		if ( is_wp_error( $data ) ) {
			return null;
		}

		$code = isset( $data['result'] ) ? (int) $data['result'] : 0;
		if ( 100 !== $code ) {
			return null;
		}
		if ( isset( $data['amount'] ) && (int) $data['amount'] !== $this->to_gateway_amount( $amount ) ) {
			return false;
		}
		return true;
	}
}
