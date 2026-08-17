<?php
/** @package Vityaz */
defined( 'ABSPATH' ) || exit;
get_header();
get_template_part( 'template-parts/archive-listing', null, array( 'post_type' => 'vityaz_student' ) );
get_footer();

