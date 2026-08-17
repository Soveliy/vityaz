<?php
/**
 * ACF Pro options pages and local field groups.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

function vityaz_acf_key( string $context, string $name ): string {
	return 'field_vityaz_' . sanitize_key( $context . '_' . $name );
}

function vityaz_acf_field(
	string $context,
	string $name,
	string $label,
	string $type = 'text',
	array $args = array()
): array {
	return array_merge(
		array(
			'key'   => vityaz_acf_key( $context, $name ),
			'label' => $label,
			'name'  => $name,
			'type'  => $type,
		),
		$args
	);
}

function vityaz_acf_tab( string $context, string $name, string $label ): array {
	return vityaz_acf_field(
		$context,
		$name,
		$label,
		'tab',
		array(
			'placement' => 'top',
		)
	);
}

function vityaz_acf_image_field( string $context, string $name, string $label ): array {
	return vityaz_acf_field(
		$context,
		$name,
		$label,
		'image',
		array(
			'preview_size' => 'medium',
			'return_format' => 'array',
			'library'       => 'all',
		)
	);
}

function vityaz_acf_repeater(
	string $context,
	string $name,
	string $label,
	array $sub_fields,
	array $args = array()
): array {
	return vityaz_acf_field(
		$context,
		$name,
		$label,
		'repeater',
		array_merge(
			array(
				'button_label' => __( 'Добавить', 'vityaz' ),
				'layout'       => 'block',
				'sub_fields'   => $sub_fields,
			),
			$args
		)
	);
}

function vityaz_register_acf_options_page(): void {
	if ( ! vityaz_has_acf_pro() ) {
		return;
	}

	acf_add_options_page(
		array(
			'page_title' => __( 'Настройки сайта «Витязь»', 'vityaz' ),
			'menu_title' => __( 'Настройки «Витязь»', 'vityaz' ),
			'menu_slug'  => 'vityaz-settings',
			'capability' => 'manage_options',
			'icon_url'   => 'dashicons-location-alt',
			'redirect'   => false,
			'autoload'   => true,
		)
	);
}
add_action( 'acf/init', 'vityaz_register_acf_options_page', 5 );

function vityaz_register_acf_fields(): void {
	if ( ! vityaz_has_acf_pro() || ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group(
		array(
			'key'      => 'group_vityaz_options',
			'title'    => __( 'Глобальные настройки сайта', 'vityaz' ),
			'fields'   => array(
				vityaz_acf_tab( 'options', 'contacts_tab', 'Контакты' ),
				vityaz_acf_field( 'options', 'phone', 'Телефон', 'text', array( 'default_value' => '+7 (920) 265-73-65' ) ),
				vityaz_acf_field( 'options', 'email', 'Email', 'email', array( 'default_value' => 'vityazi-kursk@yandex.ru' ) ),
				vityaz_acf_field( 'options', 'address', 'Адрес', 'text', array( 'default_value' => 'г. Курск, ул. Краснознамённая, 20А' ) ),
				vityaz_acf_field( 'options', 'vk_url', 'Ссылка ВКонтакте', 'url', array( 'default_value' => 'https://vk.com/vityazikursk' ) ),
				vityaz_acf_field( 'options', 'request_email', 'Email для заявок', 'email' ),
				vityaz_acf_field( 'options', 'legal_privacy_url', 'Политика обработки данных', 'url' ),
				vityaz_acf_field( 'options', 'legal_terms_url', 'Пользовательское соглашение', 'url' ),
				vityaz_acf_field( 'options', 'review_widget_url', 'URL iframe отзывов Яндекс', 'url', array( 'default_value' => 'https://yandex.ru/maps-reviews-widget/235430791173?comments' ) ),
				vityaz_acf_field( 'options', 'global_offer_title', 'Общий заголовок формы', 'textarea', array( 'rows' => 2, 'default_value' => 'Запишитесь на бесплатную пробную тренировку' ) ),
				vityaz_acf_image_field( 'options', 'global_offer_image', 'Общее изображение формы' ),

				vityaz_acf_tab( 'options', 'cookie_tab', 'Cookie' ),
				vityaz_acf_field(
					'options',
					'cookie_notice_enabled',
					'Показывать уведомление о cookie',
					'true_false',
					array(
						'default_value' => 1,
						'ui'            => 1,
					)
				),
				vityaz_acf_field( 'options', 'cookie_notice_title', 'Заголовок уведомления', 'text', array( 'default_value' => 'Мы используем файлы cookie' ) ),
				vityaz_acf_field(
					'options',
					'cookie_notice_text',
					'Текст уведомления',
					'textarea',
					array(
						'rows'          => 4,
						'default_value' => 'Это нужно, чтобы сайт работал корректно и помогал нам улучшать качество сервиса. Продолжая пользоваться сайтом, вы соглашаетесь с использованием файлов cookie.',
					)
				),
				vityaz_acf_field( 'options', 'cookie_notice_button', 'Подпись кнопки закрытия', 'text', array( 'default_value' => 'Понятно' ) ),

				vityaz_acf_tab( 'options', 'branding_tab', 'Логотипы и API' ),
				vityaz_acf_image_field( 'options', 'header_logo', 'Логотип в шапке' ),
				vityaz_acf_image_field( 'options', 'header_logo_mobile', 'Логотип в мобильной шапке' ),
				vityaz_acf_image_field( 'options', 'footer_logo', 'Логотип в подвале' ),
				vityaz_acf_field(
					'options',
					'yandex_maps_api_key',
					'Ключ JavaScript API Яндекс Карт',
					'password',
					array(
						'instructions' => 'Для production предпочтительно задать константу VITYAZ_YANDEX_MAPS_API_KEY в wp-config.php.',
					)
				),

				vityaz_acf_tab( 'options', 'locations_tab', 'Адреса карты' ),
				vityaz_acf_repeater(
					'options',
					'map_locations',
					'Площадки',
					array(
						vityaz_acf_field(
							'location',
							'location_id',
							'ID',
							'text',
							array(
								'instructions' => 'Постоянный ID латиницей, например school-5-kursk. Не меняйте его после привязки материалов.',
								'required'     => 1,
								'wrapper'      => array( 'width' => 20 ),
							)
						),
						vityaz_acf_field( 'location', 'name', 'Название', 'text', array( 'wrapper' => array( 'width' => 30 ) ) ),
						vityaz_acf_field( 'location', 'district', 'Район/подпись', 'text', array( 'wrapper' => array( 'width' => 25 ) ) ),
						vityaz_acf_field(
							'location',
							'location_group',
							'Группа залов',
							'select',
							array(
								'choices'       => vityaz_location_group_choices(),
								'instructions'  => 'Стабильная группа для карты и модального окна «Ближайший зал».',
								'placeholder'   => 'Выберите группу',
								'required'      => 1,
								'return_format' => 'value',
								'ui'            => 1,
								'wrapper'       => array( 'width' => 25 ),
							)
						),
						vityaz_acf_field( 'location', 'address', 'Адрес', 'text', array( 'required' => 1 ) ),
						vityaz_acf_field( 'location', 'longitude', 'Долгота', 'number', array( 'step' => 'any', 'required' => 1, 'wrapper' => array( 'width' => 25 ) ) ),
						vityaz_acf_field( 'location', 'latitude', 'Широта', 'number', array( 'step' => 'any', 'required' => 1, 'wrapper' => array( 'width' => 25 ) ) ),
						vityaz_acf_field(
							'location',
							'disciplines',
							'Направления',
							'checkbox',
							array(
								'choices'       => array( 'Каратэ' => 'Каратэ', 'Кудо' => 'Кудо' ),
								'default_value' => array( 'Каратэ' ),
								'layout'        => 'horizontal',
								'wrapper'       => array( 'width' => 25 ),
							)
						),
						vityaz_acf_field(
							'location',
							'scope',
							'Область показа',
							'select',
							array(
								'choices'       => array( 'city' => 'Курск', 'region' => 'Курская область' ),
								'default_value' => 'city',
								'wrapper'       => array( 'width' => 25 ),
							)
						),
					),
					array( 'collapsed' => vityaz_acf_key( 'location', 'address' ) )
				),
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
			'menu_order' => 0,
			'position'   => 'normal',
			'show_in_rest' => 0,
		)
	);

	$home_fields = array(
		vityaz_acf_tab( 'home', 'hero_tab', 'Первый экран' ),
		vityaz_acf_field( 'home', 'hero_title', 'Заголовок', 'textarea', array( 'rows' => 2, 'default_value' => 'Каратэ для детей в Курске и области' ) ),
		vityaz_acf_field( 'home', 'hero_text', 'Описание', 'textarea', array( 'rows' => 3 ) ),
		vityaz_acf_repeater(
			'home',
			'hero_facts',
			'Короткие преимущества',
			array( vityaz_acf_field( 'hero_fact', 'text', 'Текст' ) ),
			array( 'layout' => 'table', 'max' => 5 )
		),
		vityaz_acf_image_field( 'home', 'hero_image_first', 'Фото слева' ),
		vityaz_acf_image_field( 'home', 'hero_image_second', 'Фото справа' ),
		vityaz_acf_image_field( 'home', 'hero_image_fighters', 'Спортсмены PNG' ),
		vityaz_acf_image_field( 'home', 'hero_image_mobile', 'Фото на мобильном' ),

		vityaz_acf_tab( 'home', 'places_tab', 'Залы и карта' ),
		vityaz_acf_field( 'home', 'places_title', 'Заголовок выбора залов', 'text', array( 'default_value' => 'Выберите ближайший зал' ) ),
		vityaz_acf_repeater(
			'home',
			'place_groups',
			'Группы залов',
			array(
				vityaz_acf_field( 'place_group', 'title', 'Название' ),
				vityaz_acf_field( 'place_group', 'subtitle', 'Краткий список адресов' ),
				vityaz_acf_field(
					'place_group',
					'group_key',
					'Ключ фильтра',
					'select',
					array(
						'choices' => array(
							'center'    => 'Центр',
							'northwest' => 'Северо-Западный район',
							'north'     => 'СХА, Победа, Дериглазова',
							'railway'   => 'Железнодорожный округ',
							'seym'      => 'Сеймский округ',
							'volokno'   => 'Волокно',
							'region'    => 'Курская область',
						),
					)
				),
			),
			array( 'layout' => 'table' )
		),
		vityaz_acf_field( 'home', 'map_title', 'Заголовок карты', 'text', array( 'default_value' => 'Наши адреса' ) ),

		vityaz_acf_tab( 'home', 'benefits_tab', 'Преимущества' ),
		vityaz_acf_field( 'home', 'tizers_title', 'Заголовок карточек', 'text', array( 'default_value' => 'Что даёт ребёнку занятие каратэ и кудо' ) ),
		vityaz_acf_repeater(
			'home',
			'tizers',
			'Карточки преимуществ',
			array(
				vityaz_acf_image_field( 'tizer', 'image', 'Изображение' ),
				vityaz_acf_field( 'tizer', 'title', 'Заголовок' ),
				vityaz_acf_field( 'tizer', 'text', 'Описание', 'textarea', array( 'rows' => 3 ) ),
			)
		),
		vityaz_acf_field( 'home', 'groups_title', 'Заголовок групп', 'text', array( 'default_value' => 'Набор в группы по каратэ' ) ),
		vityaz_acf_repeater(
			'home',
			'groups',
			'Возрастные группы',
			array(
				vityaz_acf_image_field( 'group', 'icon', 'Иконка' ),
				vityaz_acf_field( 'group', 'title', 'Название' ),
				vityaz_acf_field( 'group', 'description', 'Описание' ),
			)
		),
		vityaz_acf_field( 'home', 'why_title', 'Заголовок «Почему выбирают»', 'text', array( 'default_value' => 'Почему родители выбирают каратэ' ) ),
		vityaz_acf_repeater(
			'home',
			'why_items',
			'Причины',
			array(
				vityaz_acf_field( 'why_item', 'title', 'Заголовок' ),
				vityaz_acf_field( 'why_item', 'description', 'Описание', 'textarea', array( 'rows' => 3 ) ),
			),
			array( 'min' => 1, 'max' => 4 )
		),
		vityaz_acf_image_field( 'home', 'why_image', 'Изображение секции' ),

		vityaz_acf_tab( 'home', 'commercial_tab', 'Расписание и цены' ),
		vityaz_acf_field( 'home', 'schedule_title', 'Заголовок расписания', 'text', array( 'default_value' => 'Расписание' ) ),
		vityaz_acf_image_field( 'home', 'schedule_image', 'Изображение расписания на компьютере' ),
		vityaz_acf_image_field( 'home', 'schedule_image_mobile', 'Изображение расписания на телефоне' ),
		vityaz_acf_field( 'home', 'schedule_button', 'Текст кнопки', 'text', array( 'default_value' => 'Получить подробное расписание' ) ),
		vityaz_acf_field( 'home', 'price_title', 'Заголовок цен', 'text', array( 'default_value' => 'Сколько стоит обучение каратэ' ) ),
		vityaz_acf_repeater(
			'home',
			'prices',
			'Тарифы',
			array(
				vityaz_acf_field( 'price', 'title', 'Название' ),
				vityaz_acf_field( 'price', 'value', 'Цена' ),
				vityaz_acf_field( 'price', 'description', 'Описание', 'textarea', array( 'rows' => 2 ) ),
				vityaz_acf_field( 'price', 'features', 'Преимущества (по одному в строке)', 'textarea', array( 'rows' => 4 ) ),
				vityaz_acf_field( 'price', 'button', 'Текст кнопки', 'text', array( 'default_value' => 'Записаться' ) ),
			)
		),
		vityaz_acf_field( 'home', 'offer_title', 'Заголовок формы', 'textarea', array( 'rows' => 2, 'default_value' => 'Запишитесь на бесплатную пробную тренировку' ) ),
		vityaz_acf_image_field( 'home', 'offer_image', 'Изображение формы' ),

		vityaz_acf_tab( 'home', 'people_tab', 'Воспитанники и тренеры' ),
		vityaz_acf_field( 'home', 'students_title', 'Заголовок воспитанников', 'text', array( 'default_value' => 'Лучшие воспитанники' ) ),
		vityaz_acf_repeater(
			'home',
			'students',
			'Воспитанники',
			array(
				vityaz_acf_image_field( 'student', 'image', 'Фото' ),
				vityaz_acf_field( 'student', 'name', 'Имя' ),
				vityaz_acf_field( 'student', 'subtitle', 'Подпись', 'textarea', array( 'rows' => 2 ) ),
				vityaz_acf_field( 'student', 'qualification', 'Квалификация', 'textarea', array( 'rows' => 2 ) ),
				vityaz_acf_field( 'student', 'achievements', 'Достижения (по одному в строке)', 'textarea', array( 'rows' => 5 ) ),
				vityaz_acf_field( 'student', 'trainer', 'Тренер' ),
			)
		),
		vityaz_acf_field( 'home', 'trainers_title', 'Заголовок тренеров', 'text', array( 'default_value' => 'Тренеры' ) ),
		vityaz_acf_repeater(
			'home',
			'trainers',
			'Тренеры',
			array(
				vityaz_acf_image_field( 'trainer', 'image', 'Фото' ),
				vityaz_acf_field( 'trainer', 'name', 'Имя' ),
				vityaz_acf_field( 'trainer', 'subtitle', 'Должность', 'textarea', array( 'rows' => 2 ) ),
				vityaz_acf_field( 'trainer', 'experience', 'Стаж' ),
				vityaz_acf_field( 'trainer', 'qualification', 'Квалификация', 'textarea', array( 'rows' => 3 ) ),
				vityaz_acf_field( 'trainer', 'halls', 'Залы (по одному в строке)', 'textarea', array( 'rows' => 4 ) ),
			)
		),

		vityaz_acf_tab( 'home', 'content_tab', 'Новости и галерея' ),
		vityaz_acf_field( 'home', 'news_title', 'Заголовок новостей', 'text', array( 'default_value' => 'Новости' ) ),
		vityaz_acf_repeater( 'home', 'news', 'Новости', vityaz_acf_content_card_fields( 'news' ) ),
		vityaz_acf_field( 'home', 'gallery_title', 'Заголовок галереи', 'text', array( 'default_value' => 'Фотогалерея' ) ),
		vityaz_acf_field(
			'home',
			'gallery',
			'Фотогалерея',
			'gallery',
			array( 'return_format' => 'array', 'preview_size' => 'medium', 'insert' => 'append', 'max' => 12 )
		),
		vityaz_acf_field( 'home', 'events_title', 'Заголовок мероприятий', 'text', array( 'default_value' => 'Мероприятия' ) ),
		vityaz_acf_repeater( 'home', 'events', 'Мероприятия', vityaz_acf_content_card_fields( 'event' ) ),

		vityaz_acf_tab( 'home', 'trust_tab', 'Отзывы и FAQ' ),
		vityaz_acf_field( 'home', 'reviews_title', 'Заголовок отзывов', 'text', array( 'default_value' => 'Отзывы' ) ),
		vityaz_acf_repeater(
			'home',
			'reviews',
			'Отзывы',
			array(
				vityaz_acf_field( 'review', 'author', 'Автор' ),
				vityaz_acf_field( 'review', 'text', 'Текст', 'textarea', array( 'rows' => 5 ) ),
			),
			array( 'max' => 4 )
		),
		vityaz_acf_field( 'home', 'faq_title', 'Заголовок FAQ', 'text', array( 'default_value' => 'Частые вопросы' ) ),
		vityaz_acf_repeater(
			'home',
			'faq',
			'Вопросы и ответы',
			array(
				vityaz_acf_field( 'faq', 'question', 'Вопрос' ),
				vityaz_acf_field( 'faq', 'answer', 'Ответ', 'textarea', array( 'rows' => 4 ) ),
			),
			array( 'collapsed' => vityaz_acf_key( 'faq', 'question' ) )
		),
	);

	acf_add_local_field_group(
		array(
			'key'        => 'group_vityaz_home',
			'title'      => __( 'Главная страница', 'vityaz' ),
			'fields'     => $home_fields,
			'location'   => array(
				array(
					array(
						'param'    => 'page_type',
						'operator' => '==',
						'value'    => 'front_page',
					),
				),
			),
			'menu_order' => 0,
			'position'   => 'acf_after_title',
			'style'      => 'default',
			'show_in_rest' => 0,
		)
	);
}
add_action( 'acf/init', 'vityaz_register_acf_fields', 10 );

/**
 * Seed the editable location repeater once and enrich legacy rows without
 * overwriting administrator-managed values.
 */
function vityaz_seed_map_locations(): void {
	if ( ! is_admin() || ! current_user_can( 'manage_options' ) || ! vityaz_has_acf_pro() || ! function_exists( 'update_field' ) ) {
		return;
	}

	$defaults = vityaz_default_map_locations();

	if ( ! $defaults ) {
		return;
	}

	$current = get_field( 'map_locations', 'option' );
	$current = is_array( $current ) ? $current : array();
	$seeded  = (string) get_option( 'vityaz_map_locations_seed_version' );

	if ( ! $current && ! $seeded ) {
		$rows = array_map(
			static function ( array $location ): array {
				$coordinates = (array) ( $location['coordinates'] ?? array() );

				return array(
					'location_id'    => sanitize_title( (string) ( $location['id'] ?? '' ) ),
					'name'           => (string) ( $location['name'] ?? '' ),
					'district'       => (string) ( $location['district'] ?? '' ),
					'location_group' => vityaz_location_group( $location ),
					'address'        => (string) ( $location['address'] ?? '' ),
					'longitude'      => (float) ( $coordinates[0] ?? 0 ),
					'latitude'       => (float) ( $coordinates[1] ?? 0 ),
					'disciplines'    => array_values( (array) ( $location['disciplines'] ?? array( 'Каратэ' ) ) ),
					'scope'          => (string) ( $location['scope'] ?? 'city' ),
				);
			},
			$defaults
		);

		update_field( vityaz_acf_key( 'options', 'map_locations' ), $rows, 'option' );
		update_option( 'vityaz_map_locations_seed_version', VITYAZ_THEME_VERSION, false );
		return;
	}

	if ( ! $current ) {
		update_option( 'vityaz_map_locations_seed_version', VITYAZ_THEME_VERSION, false );
		return;
	}

	if ( VITYAZ_THEME_VERSION === get_option( 'vityaz_map_locations_seed_version' ) ) {
		return;
	}

	$changed = false;

	foreach ( $current as $index => &$row ) {
		if ( ! empty( $row['location_id'] ) && ! empty( $row['location_group'] ) ) {
			continue;
		}

		$row_address = (string) ( $row['address'] ?? '' );

		foreach ( $defaults as $default ) {
			if ( vityaz_location_addresses_match( $row_address, (string) ( $default['address'] ?? '' ) ) ) {
				if ( empty( $row['location_id'] ) ) {
					$row['location_id'] = sanitize_title( (string) ( $default['id'] ?? 'location-' . $index ) );
				}

				if ( empty( $row['location_group'] ) ) {
					$row['location_group'] = vityaz_location_group( $default );
				}

				$changed               = true;
				break;
			}
		}

		if ( empty( $row['location_id'] ) ) {
			$row['location_id'] = 'location-' . ( $index + 1 );
			$changed            = true;
		}

		if ( empty( $row['location_group'] ) ) {
			$row['location_group'] = vityaz_location_group( $row );
			$changed               = true;
		}
	}
	unset( $row );

	if ( $changed ) {
		update_field( vityaz_acf_key( 'options', 'map_locations' ), $current, 'option' );
	}

	update_option( 'vityaz_map_locations_seed_version', VITYAZ_THEME_VERSION, false );
}
add_action( 'admin_init', 'vityaz_seed_map_locations', 40 );

function vityaz_acf_content_card_fields( string $context ): array {
	return array(
		vityaz_acf_image_field( $context, 'image', 'Изображение' ),
		vityaz_acf_field( $context, 'date', 'Дата', 'date_picker', array( 'display_format' => 'd.m.Y', 'return_format' => 'd.m.Y' ) ),
		vityaz_acf_field( $context, 'title', 'Заголовок' ),
		vityaz_acf_field( $context, 'excerpt', 'Описание', 'textarea', array( 'rows' => 3 ) ),
		vityaz_acf_field( $context, 'url', 'Ссылка', 'url' ),
	);
}
