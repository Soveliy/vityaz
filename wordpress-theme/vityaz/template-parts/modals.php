<?php
/**
 * Site modals.
 *
 * @package Vityaz
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="modal" data-modal="request" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="request-modal-title" hidden>
	<div class="modal__dialog" data-modal-dialog>
		<button class="modal__close" type="button" data-modal-close aria-label="Закрыть окно"></button>
		<h2 class="modal__title" id="request-modal-title" data-request-modal-title>Оставьте заявку — мы вам перезвоним</h2>
		<?php
		get_template_part(
			'template-parts/request-form',
			null,
			array(
				'id_prefix'   => 'modal-request',
				'class'       => 'modal__form form',
				'show_email'  => true,
				'request_type' => 'Заявка из модального окна',
			)
		);
		?>
	</div>
</div>

<div class="modal modal--locations" data-modal="locations" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="locations-modal-title" hidden>
	<div class="modal__dialog modal__dialog--locations" data-modal-dialog>
		<button class="modal__close" type="button" data-modal-close aria-label="Закрыть окно"></button>
		<h2 class="modal__title" id="locations-modal-title" data-location-modal-title></h2>
		<ul class="modal__locations-list" data-location-modal-list></ul>
		<button class="btn modal__locations-btn" type="button" data-location-signup>Записаться</button>
	</div>
</div>

<div class="notice" data-success-notice role="status" aria-live="polite" aria-atomic="true" hidden>
	<strong class="notice__title">Успешно</strong>
	<span class="notice__text">Ваша заявка принята в работу</span>
</div>
