<?php
/**
 * Theme bootstrap.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

define( 'VITYAZ_THEME_VERSION', '1.4.0' );
define( 'VITYAZ_THEME_DIR', get_template_directory() );

require_once VITYAZ_THEME_DIR . '/inc/development-http.php';
define( 'VITYAZ_THEME_URI', get_template_directory_uri() );

require_once VITYAZ_THEME_DIR . '/inc/helpers.php';
require_once VITYAZ_THEME_DIR . '/inc/defaults.php';
require_once VITYAZ_THEME_DIR . '/inc/content-types.php';
require_once VITYAZ_THEME_DIR . '/inc/content.php';
require_once VITYAZ_THEME_DIR . '/inc/content-migration.php';
require_once VITYAZ_THEME_DIR . '/inc/starter-content.php';
require_once VITYAZ_THEME_DIR . '/inc/setup.php';
require_once VITYAZ_THEME_DIR . '/inc/acf-fields.php';
require_once VITYAZ_THEME_DIR . '/inc/acf-content-fields.php';
require_once VITYAZ_THEME_DIR . '/inc/contact-form-7.php';

if ( ! vityaz_cf7_is_available() || ! vityaz_cf7_form_id() ) {
	require_once VITYAZ_THEME_DIR . '/inc/form-handler.php';
}

require_once VITYAZ_THEME_DIR . '/inc/admin.php';
require_once VITYAZ_THEME_DIR . '/inc/seo.php';
