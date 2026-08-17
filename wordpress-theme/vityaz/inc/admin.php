<?php
/**
 * Administration improvements for public Vityaz content types.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return content types enhanced by this module.
 */
function vityaz_admin_content_types(): array {
	return array( 'vityaz_news', 'vityaz_event', 'vityaz_student', 'vityaz_trainer' );
}

/**
 * Insert columns immediately after the title column.
 */
function vityaz_admin_insert_columns( array $columns, array $custom_columns ): array {
	$result = array();

	foreach ( $columns as $key => $label ) {
		$result[ $key ] = $label;

		if ( 'title' === $key ) {
			$result += $custom_columns;
		}
	}

	return $result;
}

/**
 * Add useful overview columns for each custom content type.
 */
function vityaz_admin_content_columns( array $columns ): array {
	$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$post_type = $screen instanceof WP_Screen ? $screen->post_type : '';
	$thumbnail = array( 'vityaz_thumbnail' => 'Фото' );

	if ( 'vityaz_news' === $post_type ) {
		return vityaz_admin_insert_columns( $columns, $thumbnail + array( 'vityaz_summary' => 'Анонс' ) );
	}

	if ( 'vityaz_event' === $post_type ) {
		return vityaz_admin_insert_columns(
			$columns,
			$thumbnail + array(
				'vityaz_event_start'    => 'Начало',
				'vityaz_event_location' => 'Площадка',
			)
		);
	}

	if ( 'vityaz_student' === $post_type ) {
		return vityaz_admin_insert_columns(
			$columns,
			$thumbnail + array(
				'vityaz_qualification' => 'Квалификация',
				'vityaz_trainers'      => 'Тренеры',
				'vityaz_menu_order'    => 'Порядок',
			)
		);
	}

	if ( 'vityaz_trainer' === $post_type ) {
		return vityaz_admin_insert_columns(
			$columns,
			$thumbnail + array(
				'vityaz_position'   => 'Должность',
				'vityaz_halls'      => 'Залы',
				'vityaz_menu_order' => 'Порядок',
			)
		);
	}

	return $columns;
}

foreach ( vityaz_admin_content_types() as $vityaz_admin_post_type ) {
	add_filter( "manage_{$vityaz_admin_post_type}_posts_columns", 'vityaz_admin_content_columns' );
}
unset( $vityaz_admin_post_type );

/**
 * Parse an ACF date-time value without applying the site timezone twice.
 */
function vityaz_admin_parse_datetime( mixed $value ): ?DateTimeImmutable {
	$value = trim( (string) $value );

	if ( '' === $value ) {
		return null;
	}

	$formats = array( 'Y-m-d H:i:s', 'd.m.Y H:i', 'Y-m-d\TH:i', '!Ymd', '!Y-m-d', '!d.m.Y' );

	foreach ( $formats as $format ) {
		$date   = DateTimeImmutable::createFromFormat( $format, $value, wp_timezone() );
		$errors = DateTimeImmutable::getLastErrors();

		if ( $date instanceof DateTimeImmutable && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ) {
			return $date;
		}
	}

	return null;
}

/**
 * Render a dash or concise text in an admin column.
 */
function vityaz_admin_column_text( string $value, int $word_limit = 14 ): void {
	$value = trim( wp_strip_all_tags( $value ) );

	if ( '' === $value ) {
		echo '<span aria-hidden="true">—</span>';
		return;
	}

	echo esc_html( wp_trim_words( $value, $word_limit ) );
}

/**
 * Render custom admin column values.
 */
function vityaz_admin_content_column( string $column, int $post_id ): void {
	$post_type = get_post_type( $post_id );

	if ( 'vityaz_thumbnail' === $column ) {
		if ( has_post_thumbnail( $post_id ) ) {
			echo get_the_post_thumbnail( $post_id, array( 64, 64 ), array( 'class' => 'vityaz-admin-thumbnail', 'loading' => 'lazy' ) );
		} else {
			echo '<span aria-hidden="true">—</span>';
		}

		return;
	}

	if ( 'vityaz_summary' === $column ) {
		vityaz_admin_column_text( vityaz_post_excerpt( $post_id, 'news_lead' ), 16 );
		return;
	}

	if ( 'vityaz_event_start' === $column ) {
		$date = vityaz_admin_parse_datetime( vityaz_get_field( 'event_start', '', $post_id ) );

		if ( $date ) {
			echo '<time datetime="' . esc_attr( $date->format( DATE_ATOM ) ) . '">' . esc_html( $date->format( 'd.m.Y H:i' ) ) . '</time>';
		} else {
			echo '<strong class="vityaz-admin-warning">Не указано</strong>';
		}

		return;
	}

	if ( 'vityaz_event_location' === $column ) {
		$location = array_filter(
			array(
				trim( (string) vityaz_get_field( 'event_location_name', '', $post_id ) ),
				trim( (string) vityaz_get_field( 'event_address', '', $post_id ) ),
			)
		);

		vityaz_admin_column_text( implode( ', ', $location ), 12 );
		return;
	}

	if ( 'vityaz_qualification' === $column && 'vityaz_student' === $post_type ) {
		vityaz_admin_column_text( (string) vityaz_get_field( 'student_qualification', '', $post_id ), 12 );
		return;
	}

	if ( 'vityaz_trainers' === $column && 'vityaz_student' === $post_type ) {
		$trainers = vityaz_resolve_posts( vityaz_get_field( 'student_trainers', array(), $post_id ) );

		if ( ! $trainers ) {
			echo '<span aria-hidden="true">—</span>';
			return;
		}

		$links = array_map(
			static fn( WP_Post $trainer ): string => sprintf(
				'<a href="%s">%s</a>',
				esc_url( get_edit_post_link( $trainer->ID ) ?: '#' ),
				esc_html( get_the_title( $trainer ) )
			),
			$trainers
		);

		echo wp_kses_post( implode( '<br>', $links ) );
		return;
	}

	if ( 'vityaz_position' === $column && 'vityaz_trainer' === $post_type ) {
		vityaz_admin_column_text( (string) vityaz_get_field( 'trainer_position', '', $post_id ), 12 );
		return;
	}

	if ( 'vityaz_halls' === $column && 'vityaz_trainer' === $post_type ) {
		$trainer = vityaz_trainer_card_from_post( $post_id );
		$halls   = vityaz_lines( $trainer['halls'] ?? array() );
		vityaz_admin_column_text( implode( '; ', $halls ), 14 );
		return;
	}

	if ( 'vityaz_menu_order' === $column ) {
		echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
	}
}

foreach ( vityaz_admin_content_types() as $vityaz_admin_post_type ) {
	add_action( "manage_{$vityaz_admin_post_type}_posts_custom_column", 'vityaz_admin_content_column', 10, 2 );
}
unset( $vityaz_admin_post_type );

/**
 * Declare sortable date and ordering columns.
 */
function vityaz_admin_sortable_columns( array $columns ): array {
	$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$post_type = $screen instanceof WP_Screen ? $screen->post_type : '';

	if ( 'vityaz_event' === $post_type ) {
		$columns['vityaz_event_start'] = 'vityaz_event_start';
	}

	if ( in_array( $post_type, array( 'vityaz_student', 'vityaz_trainer' ), true ) ) {
		$columns['vityaz_menu_order'] = 'vityaz_menu_order';
	}

	return $columns;
}

foreach ( vityaz_admin_content_types() as $vityaz_admin_post_type ) {
	add_filter( "manage_edit-{$vityaz_admin_post_type}_sortable_columns", 'vityaz_admin_sortable_columns' );
}
unset( $vityaz_admin_post_type );

/**
 * Add a direction filter to each CPT list table.
 */
function vityaz_admin_direction_filter( string $post_type, string $which ): void {
	unset( $which );

	if ( ! in_array( $post_type, vityaz_admin_content_types(), true ) || ! taxonomy_exists( 'vityaz_direction' ) ) {
		return;
	}

	$selected = isset( $_GET['vityaz_direction_filter'] ) ? sanitize_title( wp_unslash( $_GET['vityaz_direction_filter'] ) ) : '';

	wp_dropdown_categories(
		array(
			'taxonomy'        => 'vityaz_direction',
			'name'            => 'vityaz_direction_filter',
			'show_option_all' => 'Все направления',
			'hide_empty'      => false,
			'hierarchical'    => true,
			'value_field'     => 'slug',
			'selected'        => $selected,
		)
	);
}
add_action( 'restrict_manage_posts', 'vityaz_admin_direction_filter', 10, 2 );

/**
 * Apply custom list-table sorting and direction filtering.
 */
function vityaz_admin_configure_content_query( WP_Query $query ): void {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$post_type = $query->get( 'post_type' );

	if ( ! is_string( $post_type ) || ! in_array( $post_type, vityaz_admin_content_types(), true ) ) {
		return;
	}

	$orderby = (string) $query->get( 'orderby' );

	if ( 'vityaz_event_start' === $orderby ) {
		$query->set( 'meta_key', 'event_start' );
		$query->set( 'meta_type', 'DATETIME' );
		$query->set( 'orderby', 'meta_value' );
	} elseif ( 'vityaz_menu_order' === $orderby ) {
		$order = 'DESC' === strtoupper( (string) $query->get( 'order' ) ) ? 'DESC' : 'ASC';
		$query->set(
			'orderby',
			array(
				'menu_order' => $order,
				'title'      => 'ASC',
			)
		);
	}

	$direction = isset( $_GET['vityaz_direction_filter'] ) ? sanitize_title( wp_unslash( $_GET['vityaz_direction_filter'] ) ) : '';

	if ( $direction ) {
		$query->set(
			'tax_query',
			array(
				array(
					'taxonomy' => 'vityaz_direction',
					'field'    => 'slug',
					'terms'    => $direction,
				),
			)
		);
	}
}
add_action( 'pre_get_posts', 'vityaz_admin_configure_content_query', 20 );

/**
 * Ensure an event cannot end before it begins.
 */
function vityaz_validate_event_end( mixed $valid, mixed $value, array $field, string $input ): mixed {
	unset( $field, $input );

	if ( true !== $valid || ! $value ) {
		return $valid;
	}

	$acf_data  = isset( $_POST['acf'] ) && is_array( $_POST['acf'] ) ? wp_unslash( $_POST['acf'] ) : array();
	$start_key = function_exists( 'vityaz_acf_key' ) ? vityaz_acf_key( 'event_post', 'event_start' ) : '';
	$start     = $start_key && isset( $acf_data[ $start_key ] ) ? sanitize_text_field( (string) $acf_data[ $start_key ] ) : '';

	if ( ! $start && isset( $_POST['post_ID'] ) ) {
		$start = (string) vityaz_get_field( 'event_start', '', absint( $_POST['post_ID'] ) );
	}

	$start_date = vityaz_admin_parse_datetime( $start );
	$end_date   = vityaz_admin_parse_datetime( $value );

	if ( ! $end_date ) {
		return 'Укажите корректную дату и время окончания.';
	}

	if ( $start_date && $end_date < $start_date ) {
		return 'Дата окончания не может быть раньше даты начала.';
	}

	return $valid;
}
add_filter( 'acf/validate_value/name=event_end', 'vityaz_validate_event_end', 10, 4 );

/**
 * Keep custom list-table columns compact and readable.
 */
function vityaz_admin_content_columns_css(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen instanceof WP_Screen || ! in_array( $screen->post_type, vityaz_admin_content_types(), true ) ) {
		return;
	}
	?>
	<style>
		.column-vityaz_thumbnail { width: 76px; }
		.column-vityaz_menu_order { width: 82px; text-align: center; }
		.column-vityaz_event_start { width: 145px; }
		.vityaz-admin-thumbnail { width: 56px; height: 56px; border-radius: 4px; object-fit: cover; }
		.vityaz-admin-warning { color: #b32d2e; }
	</style>
	<?php
}
add_action( 'admin_head-edit.php', 'vityaz_admin_content_columns_css' );
