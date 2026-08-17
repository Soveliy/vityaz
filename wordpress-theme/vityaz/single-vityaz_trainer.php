<?php
/** @package Vityaz */
defined( 'ABSPATH' ) || exit;
get_header();
get_template_part( 'template-parts/single-profile', null, array( 'post_type' => 'vityaz_trainer' ) );
get_footer();

