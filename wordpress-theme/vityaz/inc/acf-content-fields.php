<?php
/**
 * ACF Pro fields for public content types and their archives.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Populate all location-reference fields from the global ACF location dictionary.
 */
function vityaz_acf_load_location_choices( array $field ): array {
	$field['choices'] = vityaz_map_location_choices();

	return $field;
}

add_filter(
	'acf/load_field/key=' . vityaz_acf_key( 'event_post', 'event_location_id' ),
	'vityaz_acf_load_location_choices'
);
add_filter(
	'acf/load_field/key=' . vityaz_acf_key( 'trainer_post', 'trainer_hall_ids' ),
	'vityaz_acf_load_location_choices'
);

/**
 * Register ACF fields for the new site sections.
 */
function vityaz_register_acf_content_fields(): void {
	if ( ! vityaz_has_acf_pro() || ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_vityaz_home_relations',
			'title'    => 'Материалы на главной странице',
			'fields'   => array(
				vityaz_acf_field( 'home_relation', 'show_places', 'Показывать выбор залов', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_map', 'Показывать карту', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_tizers', 'Показывать преимущества', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_groups', 'Показывать набор в группы', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_why', 'Показывать причины выбора', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_schedule', 'Показывать расписание', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_price', 'Показывать цены', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_offers', 'Показывать встроенные формы', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_students', 'Показывать воспитанников', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_trainers', 'Показывать тренеров', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_news', 'Показывать новости', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_gallery', 'Показывать фотогалерею', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_events', 'Показывать мероприятия', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_reviews', 'Показывать отзывы', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field( 'home_relation', 'show_faq', 'Показывать FAQ', 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
				vityaz_acf_field(
					'home_relation',
					'home_news',
					'Новости на главной',
					'relationship',
					array(
						'instructions'  => 'Если ничего не выбрано, выводятся последние опубликованные новости.',
						'post_type'     => array( 'vityaz_news' ),
						'filters'       => array( 'search' ),
						'max'           => 5,
						'return_format' => 'object',
					)
				),
				vityaz_acf_field(
					'home_relation',
					'home_events',
					'Мероприятия на главной',
					'relationship',
					array(
						'instructions'  => 'Если ничего не выбрано, выводятся последние опубликованные мероприятия.',
						'post_type'     => array( 'vityaz_event' ),
						'filters'       => array( 'search' ),
						'max'           => 4,
						'return_format' => 'object',
					)
				),
				vityaz_acf_field(
					'home_relation',
					'home_students',
					'Воспитанники на главной',
					'relationship',
					array(
						'instructions'  => 'Порядок выбранных записей сохраняется. Если поле пустое, используется порядок записей в админке.',
						'post_type'     => array( 'vityaz_student' ),
						'filters'       => array( 'search', 'taxonomy' ),
						'max'           => 8,
						'return_format' => 'object',
					)
				),
				vityaz_acf_field(
					'home_relation',
					'home_trainers',
					'Тренеры на главной',
					'relationship',
					array(
						'instructions'  => 'Порядок выбранных записей сохраняется. Если поле пустое, используется порядок записей в админке.',
						'post_type'     => array( 'vityaz_trainer' ),
						'filters'       => array( 'search', 'taxonomy' ),
						'max'           => 4,
						'return_format' => 'object',
					)
				),
			),
			'location' => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'menu_order' => 1,
			'position'   => 'acf_after_title',
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_vityaz_archive_settings',
			'title'    => 'Описания разделов сайта',
			'fields'   => array(
				vityaz_acf_field( 'archive', 'news_archive_intro', 'Описание раздела «Новости»', 'textarea', array( 'rows' => 2 ) ),
				vityaz_acf_field( 'archive', 'events_archive_intro', 'Описание раздела «Мероприятия»', 'textarea', array( 'rows' => 2 ) ),
				vityaz_acf_field( 'archive', 'students_archive_intro', 'Описание раздела «Лучшие воспитанники»', 'textarea', array( 'rows' => 2 ) ),
				vityaz_acf_field( 'archive', 'trainers_archive_intro', 'Описание раздела «Тренеры»', 'textarea', array( 'rows' => 2 ) ),
			),
			'location' => array(
				array(
					array(
						'param'    => 'options_page',
						'operator' => '==',
						'value'    => 'vityaz-settings',
					),
				),
			),
			'menu_order' => 2,
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_vityaz_news_fields',
			'title'    => 'Данные новости',
			'fields'   => array(
				vityaz_acf_field( 'news_post', 'news_lead', 'Лид', 'textarea', array( 'instructions' => 'Короткий вводный текст для карточки и начала статьи.', 'rows' => 3 ) ),
				vityaz_acf_field( 'news_post', 'news_gallery', 'Фотогалерея', 'gallery', array( 'return_format' => 'array', 'preview_size' => 'medium', 'insert' => 'append' ) ),
				vityaz_acf_field( 'news_post', 'news_video_url', 'Ссылка на видео', 'url' ),
				vityaz_acf_field( 'news_post', 'news_source_url', 'Ссылка на источник', 'url' ),
			),
			'location' => vityaz_acf_post_type_location( 'vityaz_news' ),
			'position' => 'acf_after_title',
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_vityaz_event_fields',
			'title'    => 'Данные мероприятия',
			'fields'   => array(
				vityaz_acf_field( 'event_post', 'event_lead', 'Краткое описание', 'textarea', array( 'rows' => 3 ) ),
				vityaz_acf_field(
					'event_post',
					'event_start',
					'Дата и время начала',
					'date_time_picker',
					array(
						'display_format' => 'd.m.Y H:i',
						'return_format'  => 'Y-m-d H:i:s',
						'first_day'      => 1,
						'required'       => 1,
						'wrapper'        => array( 'width' => 50 ),
					)
				),
				vityaz_acf_field(
					'event_post',
					'event_end',
					'Дата и время окончания',
					'date_time_picker',
					array(
						'display_format' => 'd.m.Y H:i',
						'return_format'  => 'Y-m-d H:i:s',
						'first_day'      => 1,
						'wrapper'        => array( 'width' => 50 ),
					)
				),
				vityaz_acf_field(
					'event_post',
					'event_location_id',
					'Площадка из справочника',
					'select',
					array(
						'allow_null'    => 1,
						'choices'       => array(),
						'instructions'  => 'Название и адрес будут взяты из раздела «Настройки „Витязь“ → Адреса карты».',
						'placeholder'   => 'Выберите площадку',
						'return_format' => 'value',
						'ui'            => 1,
					)
				),
				vityaz_acf_field(
					'event_post',
					'event_location_name',
					'Название площадки (резерв)',
					'text',
					array(
						'instructions' => 'Используется, только если площадка из справочника не выбрана или была удалена.',
						'wrapper'      => array( 'width' => 50 ),
					)
				),
				vityaz_acf_field(
					'event_post',
					'event_address',
					'Адрес (резерв)',
					'text',
					array(
						'instructions' => 'Используется, только если площадка из справочника не выбрана или была удалена.',
						'wrapper'      => array( 'width' => 50 ),
					)
				),
				vityaz_acf_field( 'event_post', 'event_registration_url', 'Ссылка на регистрацию', 'url' ),
				vityaz_acf_field( 'event_post', 'event_gallery', 'Фотогалерея', 'gallery', array( 'return_format' => 'array', 'preview_size' => 'medium', 'insert' => 'append' ) ),
			),
			'location' => vityaz_acf_post_type_location( 'vityaz_event' ),
			'position' => 'acf_after_title',
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_vityaz_student_fields',
			'title'    => 'Данные воспитанника',
			'fields'   => array(
				vityaz_acf_field( 'student_post', 'student_subtitle', 'Краткая подпись', 'textarea', array( 'rows' => 2 ) ),
				vityaz_acf_field( 'student_post', 'student_qualification', 'Квалификация', 'textarea', array( 'rows' => 3 ) ),
				vityaz_acf_repeater(
					'student_post',
					'student_achievements',
					'Достижения',
					array(
						vityaz_acf_field( 'student_achievement', 'achievement', 'Достижение', 'text', array( 'required' => 1, 'wrapper' => array( 'width' => 60 ) ) ),
						vityaz_acf_field( 'student_achievement', 'year', 'Год', 'text', array( 'wrapper' => array( 'width' => 15 ) ) ),
						vityaz_acf_field( 'student_achievement', 'result', 'Результат/место', 'text', array( 'wrapper' => array( 'width' => 25 ) ) ),
					),
					array( 'layout' => 'table' )
				),
				vityaz_acf_field(
					'student_post',
					'student_trainers',
					'Тренеры',
					'relationship',
					array(
						'post_type'     => array( 'vityaz_trainer' ),
						'filters'       => array( 'search' ),
						'return_format' => 'object',
					)
				),
				vityaz_acf_field( 'student_post', 'student_gallery', 'Фотогалерея', 'gallery', array( 'return_format' => 'array', 'preview_size' => 'medium', 'insert' => 'append' ) ),
			),
			'location' => vityaz_acf_post_type_location( 'vityaz_student' ),
			'position' => 'acf_after_title',
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_vityaz_trainer_fields',
			'title'    => 'Данные тренера',
			'fields'   => array(
				vityaz_acf_field( 'trainer_post', 'trainer_position', 'Должность', 'textarea', array( 'rows' => 2 ) ),
				vityaz_acf_field( 'trainer_post', 'trainer_experience', 'Стаж', 'text', array( 'wrapper' => array( 'width' => 30 ) ) ),
				vityaz_acf_field( 'trainer_post', 'trainer_qualification', 'Квалификация', 'textarea', array( 'rows' => 3 ) ),
				vityaz_acf_field(
					'trainer_post',
					'trainer_hall_ids',
					'Залы из справочника',
					'select',
					array(
						'allow_null'    => 1,
						'choices'       => array(),
						'instructions'  => 'Можно выбрать несколько залов. Названия и адреса обновятся из единого справочника.',
						'multiple'      => 1,
						'return_format' => 'value',
						'ui'            => 1,
					)
				),
				vityaz_acf_repeater(
					'trainer_post',
					'trainer_halls',
					'Залы (резерв)',
					array( vityaz_acf_field( 'trainer_hall', 'hall', 'Название и адрес зала', 'text', array( 'required' => 1 ) ) ),
					array(
						'instructions' => 'Используется, только если залы из справочника не выбраны или были удалены.',
						'layout'       => 'table',
					)
				),
				vityaz_acf_repeater(
					'trainer_post',
					'trainer_achievements',
					'Достижения и звания',
					array(
						vityaz_acf_field( 'trainer_achievement', 'achievement', 'Достижение', 'text', array( 'required' => 1, 'wrapper' => array( 'width' => 75 ) ) ),
						vityaz_acf_field( 'trainer_achievement', 'year', 'Год', 'text', array( 'wrapper' => array( 'width' => 25 ) ) ),
					),
					array( 'layout' => 'table' )
				),
				vityaz_acf_field( 'trainer_post', 'trainer_gallery', 'Фотогалерея', 'gallery', array( 'return_format' => 'array', 'preview_size' => 'medium', 'insert' => 'append' ) ),
			),
			'location' => vityaz_acf_post_type_location( 'vityaz_trainer' ),
			'position' => 'acf_after_title',
		)
	);
}
add_action( 'acf/init', 'vityaz_register_acf_content_fields', 11 );

/**
 * Build a standard ACF post-type location rule.
 */
function vityaz_acf_post_type_location( string $post_type ): array {
	return array(
		array(
			array(
				'param'    => 'post_type',
				'operator' => '==',
				'value'    => $post_type,
			),
		),
	);
}
