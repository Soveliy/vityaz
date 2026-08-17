<?php
/** @package Vityaz */
defined( 'ABSPATH' ) || exit;
get_header();
get_template_part( 'template-parts/single-content', null, array( 'post_type' => 'vityaz_news' ) );
get_footer();

