<?php
/**
 * فاکتور اشتراک (برای ایمیل؛ استایل درون‌خطی).
 *
 * @package moonbanu
 * @var array $data
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_sub  = (array) $data['sub'];
$mb_user = $data['user'];
$mb_ref  = (string) ( $data['payment_ref'] ?? '' );
$mb_date = MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) ( $mb_sub['started_at'] ?: current_time( 'mysql' ) ) ) ), 'long' );
$mb_exp  = $mb_sub['expires_at'] ? MB_Jalali::format_fa( gmdate( 'Y-m-d', strtotime( (string) $mb_sub['expires_at'] ) ), 'long' ) : '—';
$mb_rows = array(
	'شمارهٔ فاکتور'   => MB_Jalali::fa_num( (string) $mb_sub['id'] ),
	'کد سفارش'        => (string) $mb_sub['ref_id'],
	'کد رهگیری بانکی' => '' !== $mb_ref ? MB_Jalali::fa_num( $mb_ref ) : '—',
	'نام'             => $mb_user ? $mb_user->display_name : '',
	'ایمیل'           => $mb_user ? $mb_user->user_email : '',
	'طرح'             => 'اشتراک ماهانهٔ پیشرفته (۳۰ روز)',
	'تاریخ پرداخت'    => $mb_date,
	'اعتبار تا'       => $mb_exp,
	'کد تخفیف'        => $mb_sub['coupon'] ? (string) $mb_sub['coupon'] : '—',
	'مبلغ پرداختی'    => MB_Jalali::fa_num( number_format_i18n( (int) $mb_sub['amount'] ) ) . ' تومان',
	'درگاه'           => 'zarinpal' === $mb_sub['gateway'] ? 'زرین‌پال' : ( 'zibal' === $mb_sub['gateway'] ? 'زیبال' : (string) $mb_sub['gateway'] ),
);
?>
<div style="direction:rtl;text-align:right;font-family:Tahoma,system-ui,sans-serif">
	<h2 style="font-size:15px;margin:0 0 10px;color:#F8F3F9">فاکتور اشتراک ماه‌بانو</h2>
	<table style="width:100%;border-collapse:collapse;font-size:12.5px;color:#ABA0BC">
		<tbody>
		<?php foreach ( $mb_rows as $mb_label => $mb_value ) : ?>
			<tr>
				<th style="text-align:right;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.08);font-weight:600;color:#F8F3F9;width:40%"><?php echo esc_html( $mb_label ); ?></th>
				<td style="padding:7px 0;border-bottom:1px solid rgba(255,255,255,.08)"><?php echo esc_html( (string) $mb_value ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<p style="font-size:11.5px;color:#7C7191;margin-top:14px">این فاکتور به‌صورت خودکار پس از تأیید پرداخت صادر شده است. برای پیگیری، کد سفارش را نگه دارید.</p>
</div>
