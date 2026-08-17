<?php
/**
 * Contact Form 7 integration.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;

const VITYAZ_CF7_FORM_OPTION = 'vityaz_cf7_form_id';
const VITYAZ_CF7_FORM_META   = '_vityaz_managed_contact_form';
const VITYAZ_CF7_FORM_SCHEMA = '2';

/**
 * Check whether Contact Form 7 is active and loaded.
 */
function vityaz_cf7_is_available(): bool {
	return class_exists( 'WPCF7_ContactForm' );
}

/**
 * Resolve the managed Contact Form 7 form ID.
 */
function vityaz_cf7_form_id(): int {
	if ( ! vityaz_cf7_is_available() ) {
		return 0;
	}

	$form_id = absint( get_option( VITYAZ_CF7_FORM_OPTION ) );

	if ( $form_id && 'wpcf7_contact_form' === get_post_type( $form_id ) && 'trash' !== get_post_status( $form_id ) ) {
		return $form_id;
	}

	$forms = get_posts(
		array(
			'post_type'      => 'wpcf7_contact_form',
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => VITYAZ_CF7_FORM_META,
			'meta_value'     => '1',
			'no_found_rows'  => true,
		)
	);

	$form_id = absint( $forms[0] ?? 0 );

	if ( $form_id ) {
		update_option( VITYAZ_CF7_FORM_OPTION, $form_id, false );
	}

	return $form_id;
}

/**
 * Return the theme-managed Contact Form 7 object.
 *
 * @return object|null
 */
function vityaz_cf7_form(): ?object {
	if ( ! vityaz_cf7_is_available() ) {
		return null;
	}

	$form_id = vityaz_cf7_form_id();

	return $form_id ? WPCF7_ContactForm::get_instance( $form_id ) : null;
}

/**
 * Contact Form 7 source template used by all request forms.
 */
function vityaz_cf7_form_template(): string {
	return <<<'HTML'
<label class="form__item">
<span class="form__label">Имя</span>
[text* your-name class:form__input autocomplete:name placeholder "Ваше имя"]
</label>
<div class="modal__contact-row">
<label class="form__item">
<span class="form__label">Телефон</span>
[tel* your-phone class:form__input autocomplete:tel placeholder "Ваш номер телефона"]
</label>
<label class="form__item" data-schedule-field hidden>
<span class="form__label">Почта</span>
[email your-email class:form__input autocomplete:email placeholder "Ваш e-mail"]
</label>
</div>
<div class="form__footer">
<div class="form__submit">[submit class:btn class:form__btn "Отправить"]</div>
<div class="form__checkbox">[acceptance consent]Я согласен(а) на %%VITYAZ_PRIVACY_LINK%%[/acceptance]</div>
</div>
[hidden request-type default:shortcode_attr class:vityaz-request-type]
HTML;
}

/**
 * Apply the form, mail and validation settings owned by the theme.
 */
function vityaz_cf7_managed_properties( array $properties ): array {
	$properties['form'] = vityaz_cf7_form_template();
	$properties['mail'] = array_merge(
		(array) ( $properties['mail'] ?? array() ),
		array(
			'subject'            => 'Новая заявка: [request-type]',
			'body'               => "Тип заявки: [request-type]\nИмя: [your-name]\nТелефон: [your-phone]\nEmail: [your-email]\nСогласие: [consent]\n\nСтраница: [_url]",
			'recipient'          => '[_site_admin_email]',
			'additional_headers' => '',
			'attachments'        => '',
			'use_html'           => 0,
			'exclude_blank'      => 1,
		)
	);
	$properties['messages'] = array_merge(
		(array) ( $properties['messages'] ?? array() ),
		array(
			'mail_sent_ok'     => 'Ваша заявка принята в работу.',
			'mail_sent_ng'     => 'Не удалось отправить заявку. Попробуйте ещё раз позднее.',
			'validation_error' => 'Проверьте правильность заполнения полей.',
			'spam'             => 'Не удалось отправить заявку. Попробуйте ещё раз позднее.',
			'accept_terms'     => 'Подтвердите согласие на обработку персональных данных.',
			'invalid_required' => 'Заполните обязательное поле.',
		)
	);
	$properties['additional_settings'] = 'acceptance_as_validation: on';

	return $properties;
}

/**
 * Create the managed CF7 form after the plugin is activated.
 */
function vityaz_cf7_maybe_create_form(): void {
	if ( ! current_user_can( 'manage_options' ) || ! vityaz_cf7_is_available() || vityaz_cf7_form_id() ) {
		return;
	}

	$form = WPCF7_ContactForm::get_template(
		array(
			'locale' => 'ru_RU',
			'title'  => 'Заявка с сайта «Витязь»',
		)
	);

	if ( ! $form ) {
		return;
	}

	$form->set_properties( vityaz_cf7_managed_properties( $form->get_properties() ) );
	$form_id = absint( $form->save() );

	if ( $form_id ) {
		update_post_meta( $form_id, VITYAZ_CF7_FORM_META, '1' );
		update_post_meta( $form_id, '_vityaz_managed_form_version', VITYAZ_CF7_FORM_SCHEMA );
		update_option( VITYAZ_CF7_FORM_OPTION, $form_id, false );
	}
}
add_action( 'admin_init', 'vityaz_cf7_maybe_create_form', 30 );

/**
 * Upgrade only the form created and managed by the theme.
 */
function vityaz_cf7_maybe_upgrade_form(): void {
	if ( ! current_user_can( 'manage_options' ) || ! vityaz_cf7_is_available() ) {
		return;
	}

	$form_id = vityaz_cf7_form_id();

	if ( ! $form_id || VITYAZ_CF7_FORM_SCHEMA === get_post_meta( $form_id, '_vityaz_managed_form_version', true ) ) {
		return;
	}

	$form = WPCF7_ContactForm::get_instance( $form_id );

	if ( ! $form ) {
		return;
	}

	$form->set_properties( vityaz_cf7_managed_properties( $form->get_properties() ) );
	$form->save();
	update_post_meta( $form_id, '_vityaz_managed_form_version', VITYAZ_CF7_FORM_SCHEMA );
}
add_action( 'admin_init', 'vityaz_cf7_maybe_upgrade_form', 31 );

/**
 * Render the managed form with the classes expected by the existing layout.
 */
function vityaz_render_cf7_form( array $args = array() ): bool {
	$form = vityaz_cf7_form();

	if ( ! $form ) {
		return false;
	}

	$options = array(
		'html_id'      => sanitize_html_class( (string) ( $args['id_prefix'] ?? wp_unique_id( 'request-' ) ) . '-form' ),
		'html_class'   => trim( (string) ( $args['class'] ?? 'form__offer form' ) . ' vityaz-cf7-form' ),
		'html_title'   => (string) ( $args['title'] ?? 'Форма заявки' ),
		'request-type' => sanitize_text_field( (string) ( $args['request_type'] ?? 'Пробная тренировка' ) ),
	);

	echo $form->form_html( $options ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped by Contact Form 7.

	return true;
}

/**
 * Disable automatic paragraphs only while rendering the managed form.
 */
function vityaz_cf7_disable_managed_autop( bool $autop, array $options = array() ): bool {
	$form = function_exists( 'wpcf7_get_current_contact_form' ) ? wpcf7_get_current_contact_form() : null;

	return $form && absint( $form->id() ) === vityaz_cf7_form_id() && 'form' === ( $options['for'] ?? 'form' ) ? false : $autop;
}
add_filter( 'wpcf7_autop_or_not', 'vityaz_cf7_disable_managed_autop', 20, 2 );

/**
 * Replace the privacy marker at render time so the URL remains editable in ACF.
 */
function vityaz_cf7_replace_privacy_link( string $html ): string {
	$form = function_exists( 'wpcf7_get_current_contact_form' ) ? wpcf7_get_current_contact_form() : null;

	if ( ! $form || absint( $form->id() ) !== vityaz_cf7_form_id() ) {
		return $html;
	}

	$privacy_url = (string) vityaz_get_option( 'legal_privacy_url', get_privacy_policy_url() );
	$label       = esc_html__( 'обработку персональных данных', 'vityaz' );
	$link        = $privacy_url
		? sprintf( '<a href="%s" target="_blank" rel="noopener">%s</a>', esc_url( $privacy_url ), $label )
		: $label;

	$html = str_replace( '%%VITYAZ_PRIVACY_LINK%%', $link, $html );

	return $html;
}
add_filter( 'wpcf7_form_elements', 'vityaz_cf7_replace_privacy_link', 20 );

/**
 * Use the recipient configured in the theme without changing mail transport.
 */
function vityaz_cf7_mail_recipient( array $components, $contact_form, $mail ): array {
	if (
		! $contact_form
		|| absint( $contact_form->id() ) !== vityaz_cf7_form_id()
		|| ! $mail
		|| 'mail' !== $mail->name()
	) {
		return $components;
	}

	$recipient = sanitize_email( (string) vityaz_get_option( 'request_email', get_option( 'admin_email' ) ) );

	if ( $recipient ) {
		$components['recipient'] = $recipient;
	}

	return $components;
}
add_filter( 'wpcf7_mail_components', 'vityaz_cf7_mail_recipient', 20, 3 );

/**
 * Add a safe Reply-To header only when the optional email is present.
 */
function vityaz_cf7_optional_reply_to( array $components, $contact_form, $mail ): array {
	if (
		! $contact_form
		|| absint( $contact_form->id() ) !== vityaz_cf7_form_id()
		|| ! $mail
		|| 'mail' !== $mail->name()
		|| ! class_exists( 'WPCF7_Submission' )
	) {
		return $components;
	}

	$submission = WPCF7_Submission::get_instance();
	$email      = $submission ? sanitize_email( $submission->get_posted_string( 'your-email' ) ) : '';

	if ( $email ) {
		$components['additional_headers'] = trim( (string) ( $components['additional_headers'] ?? '' ) . "\nReply-To: " . $email );
	}

	return $components;
}
add_filter( 'wpcf7_mail_components', 'vityaz_cf7_optional_reply_to', 25, 3 );

/**
 * Require an email only for detailed schedule requests.
 */
function vityaz_cf7_validate_schedule_email( $result, $tag ) {
	$form = function_exists( 'wpcf7_get_current_contact_form' ) ? wpcf7_get_current_contact_form() : null;

	if ( ! $form || absint( $form->id() ) !== vityaz_cf7_form_id() || 'your-email' !== $tag->name ) {
		return $result;
	}

	$request_type_value = function_exists( 'wpcf7_superglobal_post' ) ? wpcf7_superglobal_post( 'request-type' ) : '';
	$email_value        = function_exists( 'wpcf7_superglobal_post' ) ? wpcf7_superglobal_post( 'your-email' ) : '';
	$request_type       = sanitize_text_field( is_scalar( $request_type_value ) ? (string) $request_type_value : '' );
	$email              = sanitize_email( is_scalar( $email_value ) ? (string) $email_value : '' );

	if ( preg_match( '/расписан/ui', $request_type ) && ! $email ) {
		$result->invalidate( $tag, 'Укажите email, на который отправить расписание.' );
	}

	return $result;
}
add_filter( 'wpcf7_validate_email', 'vityaz_cf7_validate_schedule_email', 20, 2 );

/**
 * Validate the normalized Russian phone number on the server.
 */
function vityaz_cf7_validate_phone( $result, $tag ) {
	$form = function_exists( 'wpcf7_get_current_contact_form' ) ? wpcf7_get_current_contact_form() : null;

	if ( ! $form || absint( $form->id() ) !== vityaz_cf7_form_id() || 'your-phone' !== $tag->name ) {
		return $result;
	}

	$phone_value = function_exists( 'wpcf7_superglobal_post' ) ? wpcf7_superglobal_post( 'your-phone' ) : '';
	$phone       = sanitize_text_field( is_scalar( $phone_value ) ? (string) $phone_value : '' );
	$digits = preg_replace( '/\D+/', '', $phone );

	if ( 11 !== strlen( (string) $digits ) || ! in_array( $digits[0] ?? '', array( '7', '8' ), true ) ) {
		$result->invalidate( $tag, 'Введите телефон в формате +7 (999) 999-99-99.' );
	}

	return $result;
}
add_filter( 'wpcf7_validate_tel*', 'vityaz_cf7_validate_phone', 20, 2 );

/**
 * Keep the consent value in notification emails readable and marker-free.
 */
function vityaz_cf7_acceptance_mail_value( string $replaced, $submitted, bool $html, $mail_tag ): string {
	unset( $html );

	$form = function_exists( 'wpcf7_get_current_contact_form' ) ? wpcf7_get_current_contact_form() : null;

	$form_tag   = is_object( $mail_tag ) && method_exists( $mail_tag, 'corresponding_form_tag' ) ? $mail_tag->corresponding_form_tag() : null;
	$field_name = is_object( $form_tag ) ? (string) ( $form_tag->name ?? '' ) : '';

	if ( ! $form || absint( $form->id() ) !== vityaz_cf7_form_id() || 'consent' !== $field_name ) {
		return $replaced;
	}

	return empty( $submitted ) ? 'нет' : 'да';
}
add_filter( 'wpcf7_mail_tag_replaced_acceptance', 'vityaz_cf7_acceptance_mail_value', 20, 4 );

/**
 * Show a one-click install/activation notice when Contact Form 7 is missing.
 */
function vityaz_cf7_dependency_notice(): void {
	if ( vityaz_cf7_is_available() || ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	$plugin_file = 'contact-form-7/wp-contact-form-7.php';
	$plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
	$can_install = current_user_can( 'install_plugins' );

	if ( file_exists( $plugin_path ) ) {
		$action_url  = wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $plugin_file ) ), 'activate-plugin_' . $plugin_file );
		$action_text = 'Активировать Contact Form 7';
	} elseif ( $can_install ) {
		$action_url  = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=contact-form-7' ), 'install-plugin_contact-form-7' );
		$action_text = 'Установить Contact Form 7';
	} else {
		$action_url  = '';
		$action_text = '';
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Для отправки заявок теме Vityaz необходим плагин Contact Form 7.', 'vityaz' );

	if ( $action_url ) {
		echo ' <a class="button button-primary" href="' . esc_url( $action_url ) . '">' . esc_html( $action_text ) . '</a>';
	}

	echo '</p></div>';
}
add_action( 'admin_notices', 'vityaz_cf7_dependency_notice' );
