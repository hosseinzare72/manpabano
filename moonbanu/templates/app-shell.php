<?php
/**
 * Shell مستقل برگهٔ اپ ماه‌بانو.
 *
 * هدر، فوتر و سایدبار قالب وارد این shell نمی‌شوند. wp_head/wp_footer اجرا
 * می‌شوند تا enqueue خود اپ کار کند، اما خروجی‌شان از لایهٔ جداسازی می‌گذرد:
 * manifest، theme-color، آیکن و ثبت service worker قالب حذف می‌شود تا PWA اپ
 * کاملاً مستقل بماند و با PWA سایت تداخل نکند.
 *
 * @package moonbanu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$mb_content = (string) get_post_field( 'post_content', get_queried_object_id() );
if ( '' === trim( $mb_content ) ) {
	$mb_content = '[moonbanu_app]';
}

ob_start();
wp_head();
$mb_head = MB_Isolation::clean_head( (string) ob_get_clean() );
?>
<!doctype html>
<html <?php language_attributes(); ?> dir="rtl" lang="fa">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover, user-scalable=no">
	<meta name="format-detection" content="telephone=no">
	<title><?php echo esc_html( MB_Plugin::brand( 'name' ) ); ?></title>
	<?php
	// خروجی wp_head پس از پاک‌سازی تگ‌های PWA بیگانه.
	echo $mb_head; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	?>
	<style data-mb="1">html,body{background:<?php echo esc_html( MB_Assets::pwa_color( 'pwa_bg_color' ) ); ?>!important}</style>
</head>
<body <?php body_class( 'mb-app mb-shell-page' ); ?>>
<?php wp_body_open(); ?>
<?php
echo do_shortcode( $mb_content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
<?php
ob_start();
wp_footer();
echo MB_Isolation::clean_footer( (string) ob_get_clean() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
?>
</body>
</html>
