<?php
/**
 * AJAX request form handler.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

function vityaz_submit_request(): void {
	check_ajax_referer( 'vityaz_request', 'nonce' );

	if ( ! empty( $_POST['website'] ) ) {
		wp_send_json_success();
	}

	$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
	$phone   = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
	$email   = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$type    = sanitize_text_field( wp_unslash( $_POST['request_type'] ?? 'Заявка с сайта' ) );
	$consent = ! empty( $_POST['consent'] );

	if ( '' === $name || '' === $phone || ! $consent ) {
		wp_send_json_error(
			array( 'message' => __( 'Заполните имя, телефон и подтвердите согласие.', 'vityaz' ) ),
			422
		);
	}

	$recipient = sanitize_email( (string) vityaz_get_option( 'request_email', get_option( 'admin_email' ) ) );
	$subject   = sprintf( 'Новая заявка: %s', $type );
	$message   = implode(
		"\n",
		array_filter(
			array(
				'Тип: ' . $type,
				'Имя: ' . $name,
				'Телефон: ' . $phone,
				$email ? 'Email: ' . $email : '',
			)
		)
	);
	$headers   = $email ? array( 'Reply-To: ' . $name . ' <' . $email . '>' ) : array();

	if ( ! $recipient || ! wp_mail( $recipient, $subject, $message, $headers ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Письмо не отправлено. Проверьте настройки почты WordPress.', 'vityaz' ) ),
			500
		);
	}

	wp_send_json_success( array( 'message' => __( 'Заявка отправлена.', 'vityaz' ) ) );
}
add_action( 'wp_ajax_vityaz_submit_request', 'vityaz_submit_request' );
add_action( 'wp_ajax_nopriv_vityaz_submit_request', 'vityaz_submit_request' );
