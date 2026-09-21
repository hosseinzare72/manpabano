<?php
/**
 * درگاه زرین‌پال (REST v4) با پشتیبانی سندباکس.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MB_Gateway_Zarinpal extends MB_Gateway {

	public function id(): string {
		return 'zarinpal';
	}

	public function title(): string {
		return 'زرین‌پال';
	}

	private function sandbox(): bool {
		return (bool) MB_Plugin::setting( 'zp_sandbox', 0 );
	}

	private function base(): string {
		return $this->sandbox() ? 'https://sandbox.zarinpal.com/pg/' : 'https://api.zarinpal.com/pg/';
	}

	private function startpay( string $authority ): string {
		$host = $this->sandbox() ? 'https://sandbox.zarinpal.com/pg/StartPay/' : 'https://www.zarinpal.com/pg/StartPay/';
		return $host . $authority;
	}

	private function merchant(): string {
		return (string) MB_Plugin::setting( 'zp_merchant', '' );
	}

	public function create_request( int $amount, string $description, string $callback, array $meta = array() ) {
		$merchant = $this->merchant();
		if ( 36 !== strlen( $merchant ) ) {
			return new WP_Error( 'mb_gateway_cfg', 'کلید درگاه (merchant_id) در تنظیمات ماه‌بانو وارد نشده است.' );
		}

		$url  = $this->base() . 'v4/payment/request.json';
		$body = array(
			'merchant_id'  => $merchant,
			'amount'       => $this->to_gateway_amount( $amount ),
			'callback_url' => $callback,
			'description'  => $description,
		);

		$user = isset( $meta['user_id'] ) ? get_userdata( (int) $meta['user_id'] ) : null;
		if ( $user && is_email( $user->user_email ) ) {
			$body['metadata'] = array( 'email' => $user->user_email );
		}

		$data = $this->post_json( $url, $body );
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$code      = isset( $data['data']['code'] ) ? (int) $data['data']['code'] : 0;
		$authority = isset( $data['data']['authority'] ) ? (string) $data['data']['authority'] : '';

		if ( 100 !== $code || '' === $authority ) {
			$msg = isset( $data['errors']['message'] ) ? (string) $data['errors']['message'] : 'کد خطای ' . $code;
			return new WP_Error( 'mb_gateway_req', 'درگاه درخواست را نپذیرفت: ' . $msg );
		}

		return array(
			'authority' => $authority,
			'redirect'  => $this->startpay( $authority ),
		);
	}

	public function verify( string $authority, int $amount ) {
		if ( '' === $authority ) {
			return new WP_Error( 'mb_gateway_verify', 'شناسه پرداخت یافت نشد.' );
		}
		$data = $this->post_json(
			$this->base() . 'v4/payment/verify.json',
			array(
				'merchant_id' => $this->merchant(),
				'amount'      => $this->to_gateway_amount( $amount ),
				'authority'   => $authority,
			)
		);
		if ( is_wp_error( $data ) ) {
			return $data;
		}

		$code = isset( $data['data']['code'] ) ? (int) $data['data']['code'] : 0;
		$ref  = isset( $data['data']['ref_id'] ) ? (string) $data['data']['ref_id'] : '';

		// ۱۰۰ = تأیید موفق، ۱۰۱ = قبلاً تأیید شده (idempotent).
		if ( in_array( $code, array( 100, 101 ), true ) ) {
			// برخلاف زیبال، مبلغ برگشتی اصلاً مقایسه نمی‌شد.
			if ( isset( $data['data']['amount'] ) && (int) $data['data']['amount'] !== $this->to_gateway_amount( $amount ) ) {
				return new WP_Error( 'mb_gateway_amount', 'مبلغ پرداخت با مبلغ سفارش هم‌خوانی ندارد.' );
			}
			return array( 'ref' => '' !== $ref ? $ref : $authority, 'amount' => $amount );
		}

		$msg = isset( $data['errors']['message'] ) ? (string) $data['errors']['message'] : 'کد ' . $code;
		return new WP_Error( 'mb_gateway_verify', 'تأیید پرداخت ناموفق بود: ' . $msg );
	}

	public function reverify( string $authority, int $amount ): ?bool {
		if ( '' === $authority || 36 !== strlen( $this->merchant() ) ) {
			return null;
		}
		$data = $this->post_json(
			$this->base() . 'v4/payment/inquiry.json',
			array(
				'merchant_id' => $this->merchant(),
				'authority'   => $authority,
			),
			15
		);
		if ( is_wp_error( $data ) ) {
			return null; // خطای شبکه هرگز باعث لغو اشتراک نمی‌شود.
		}
		$code   = isset( $data['data']['code'] ) ? (int) $data['data']['code'] : 0;
		$status = isset( $data['data']['status'] ) ? strtoupper( (string) $data['data']['status'] ) : '';
		if ( 100 !== $code ) {
			return null;
		}
		return in_array( $status, array( 'VERIFIED', 'PAID', 'OK' ), true );
	}
}
