<?php
/**
 * Public content types used by the site.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register news, events, students and trainers.
 */
function vityaz_register_content_types(): void {
	$content_types = array(
		'vityaz_news'    => array(
			'plural'    => 'Новости',
			'singular'  => 'Новость',
			'add_new'   => 'Добавить новость',
			'archive'   => 'Все новости',
			'slug'      => 'news',
			'menu_icon' => 'dashicons-megaphone',
		),
		'vityaz_event'   => array(
			'plural'    => 'Мероприятия',
			'singular'  => 'Мероприятие',
			'add_new'   => 'Добавить мероприятие',
			'archive'   => 'Все мероприятия',
			'slug'      => 'events',
			'menu_icon' => 'dashicons-calendar-alt',
		),
		'vityaz_student' => array(
			'plural'    => 'Лучшие воспитанники',
			'singular'  => 'Воспитанник',
			'add_new'   => 'Добавить воспитанника',
			'archive'   => 'Все воспитанники',
			'slug'      => 'students',
			'menu_icon' => 'dashicons-awards',
		),
		'vityaz_trainer' => array(
			'plural'    => 'Тренеры',
			'singular'  => 'Тренер',
			'add_new'   => 'Добавить тренера',
			'archive'   => 'Все тренеры',
			'slug'      => 'trainers',
			'menu_icon' => 'dashicons-businessperson',
		),
	);

	foreach ( $content_types as $post_type => $settings ) {
		$supports = array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' );

		if ( 'vityaz_news' === $post_type ) {
			$supports[] = 'author';
		}

		if ( in_array( $post_type, array( 'vityaz_student', 'vityaz_trainer' ), true ) ) {
			$supports[] = 'page-attributes';
		}

		register_post_type(
			$post_type,
			array(
				'labels'              => array(
					'name'                  => $settings['plural'],
					'singular_name'         => $settings['singular'],
					'menu_name'             => $settings['plural'],
					'name_admin_bar'        => $settings['singular'],
					'add_new'               => 'Добавить',
					'add_new_item'          => $settings['add_new'],
					'edit_item'             => 'Редактировать: ' . $settings['singular'],
					'new_item'              => 'Новый материал',
					'view_item'             => 'Посмотреть',
					'view_items'            => 'Посмотреть все',
					'search_items'          => 'Поиск',
					'not_found'             => 'Материалы не найдены',
					'not_found_in_trash'    => 'В корзине ничего не найдено',
					'all_items'             => $settings['archive'],
					'archives'              => $settings['archive'],
					'featured_image'        => 'Основное изображение',
					'set_featured_image'    => 'Установить основное изображение',
					'remove_featured_image' => 'Удалить основное изображение',
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => true,
				'has_archive'         => $settings['slug'],
				'hierarchical'        => false,
				'menu_icon'           => $settings['menu_icon'],
				'query_var'           => true,
				'rewrite'             => array(
					'slug'       => $settings['slug'],
					'with_front' => false,
				),
				'supports'            => $supports,
				'delete_with_user'    => false,
				'exclude_from_search' => false,
			)
		);
	}

	register_taxonomy(
		'vityaz_direction',
		array_keys( $content_types ),
		array(
			'labels'            => array(
				'name'          => 'Направления',
				'singular_name' => 'Направление',
				'search_items'  => 'Найти направление',
				'all_items'     => 'Все направления',
				'edit_item'     => 'Редактировать направление',
				'update_item'   => 'Обновить направление',
				'add_new_item'  => 'Добавить направление',
				'new_item_name' => 'Название направления',
				'menu_name'     => 'Направления',
			),
			'public'            => false,
			'publicly_queryable' => false,
			'show_ui'           => true,
			'show_in_menu'      => true,
			'hierarchical'      => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'rewrite'           => false,
		)
	);
}
add_action( 'init', 'vityaz_register_content_types', 5 );

/**
 * Keep public archives ordered appropriately.
 */
function vityaz_configure_content_archives( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( $query->is_post_type_archive( 'vityaz_news' ) ) {
		$query->set( 'posts_per_page', 12 );
		$query->set( 'orderby', 'date' );
		$query->set( 'order', 'DESC' );
	}

	if ( $query->is_post_type_archive( 'vityaz_event' ) ) {
		$period = isset( $_GET['period'] ) ? sanitize_key( wp_unslash( $_GET['period'] ) ) : 'all';
		$period = in_array( $period, array( 'all', 'upcoming', 'past' ), true ) ? $period : 'all';

		$query->set( 'posts_per_page', 12 );
		$query->set( 'meta_key', 'event_start' );
		$query->set( 'orderby', 'meta_value' );
		$query->set( 'order', 'upcoming' === $period ? 'ASC' : 'DESC' );

		if ( in_array( $period, array( 'upcoming', 'past' ), true ) ) {
			$query->set(
				'meta_query',
				array(
					array(
						'key'     => 'event_start',
						'value'   => current_time( 'mysql' ),
						'compare' => 'upcoming' === $period ? '>=' : '<',
						'type'    => 'DATETIME',
					),
				)
			);
		}
	}

	if ( $query->is_post_type_archive( array( 'vityaz_student', 'vityaz_trainer' ) ) ) {
		$query->set( 'posts_per_page', 12 );
		$query->set(
			'orderby',
			array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			)
		);
	}
}
add_action( 'pre_get_posts', 'vityaz_configure_content_archives' );

/**
 * Flush routes once after a theme update, including updates by replacing files.
 */
function vityaz_maybe_flush_rewrite_rules(): void {
	if ( VITYAZ_THEME_VERSION === get_option( 'vityaz_rewrite_version' ) ) {
		return;
	}

	flush_rewrite_rules( false );

	foreach ( array( 'Каратэ', 'Кудо' ) as $direction ) {
		if ( ! term_exists( $direction, 'vityaz_direction' ) ) {
			wp_insert_term( $direction, 'vityaz_direction' );
		}
	}

	update_option( 'vityaz_rewrite_version', VITYAZ_THEME_VERSION, false );
}
add_action( 'init', 'vityaz_maybe_flush_rewrite_rules', 99 );
