<?php
/**
 * کلاس انتزاعی درگاه پرداخت.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

abstract class MB_Gateway {

	/** شناسه درگاه. */
	abstract public function id(): string;

	/** نام نمایشی. */
	abstract public function title(): string;

	/**
	 * ساخت درخواست پرداخت.
	 *
	 * @return array{authority:string,redirect:string}|WP_Error
	 */
	abstract public function create_request( int $amount, string $description, string $callback, array $meta = array() );

	/**
	 * تأیید پرداخت.
	 *
	 * @return array{ref:string,amount:int}|WP_Error
	 */
	abstract public function verify( string $authority, int $amount );

	/**
	 * بازراستیابی رسید (برای کرون ضدتقلب).
	 * true = معتبر، false = نامعتبر، null = نامشخص (خطای شبکه).
	 */
	abstract public function reverify( string $authority, int $amount ): ?bool;

	/** تومان → ریال اگر لازم باشد. */
	protected function to_gateway_amount( int $toman ): int {
		return 'IRR' === MB_Plugin::setting( 'gateway_currency', 'IRR' ) ? $toman * 10 : $toman;
	}

	/** درخواست POST با wp_remote_post و مدیریت خطا. */
	protected function post_json( string $url, array $body, int $timeout = 20 ) {
		$res = wp_remote_post(
			$url,
			array(
				'timeout' => $timeout,
				'headers' => array(
					'Content-Type' => 'application/json',
					'Accept'       => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'mb_gateway_net', 'ارتباط با درگاه برقرار نشد: ' . $res->get_error_message() );
		}
		$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'mb_gateway_parse', 'پاسخ درگاه قابل خواندن نبود.' );
		}
		return $data;
	}
}
