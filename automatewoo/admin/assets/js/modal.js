// Register eslint ignored glabals - to be revisited.
// https://github.com/woocommerce/automatewoo/issues/1212
/* global AutomateWoo, AW, CustomEvent */
/**
 * AutomateWoo Modal
 */
AutomateWoo.Modal = {
	/**
	 * A set of classes to be used to interact with the modal singleton.
	 */
	triggerClasses: {
		/** Clicking on such element closes the modal. */
		close: 'js-close-automatewoo-modal',
		/**
		 * To be used on `HTMLAnchorElement`, to load ajax content fetched from `href`.
		 */
		openLink: 'js-open-automatewoo-modal',
	},
};
jQuery( function ( $ ) {
	Object.assign( AutomateWoo.Modal, {
		init() {
			const $body = $( document.body );
			$body.on( 'click', `.${ this.triggerClasses.close }`, this.close );
			$body.on( 'click', '.automatewoo-modal-overlay', this.close );
			$body.on(
				'click',
				`.${ this.triggerClasses.openLink }`,
				this.handle_link
			);

			$( document ).on( 'keydown', function ( e ) {
				if ( e.keyCode === 27 ) {
					AutomateWoo.Modal.close();
				}
			} );
		},

		handle_link( e ) {
			e.preventDefault();

			const $a = $( this );
			const size = $a.data( 'automatewoo-modal-size' );

			AutomateWoo.Modal.open( size );
			AutomateWoo.Modal.loading();

			$.post( $a.attr( 'href' ), {}, function ( response ) {
				AutomateWoo.Modal.contents( response );
			} );
		},

		open( size ) {
			let sizeClass = '';

			if ( size ) {
				sizeClass = 'automatewoo-modal--size-' + size;
			}

			document.body.classList.add( 'automatewoo-modal-open' );

			$( document.body ).append(
				`<div class="automatewoo-modal-container"><div class="automatewoo-modal-overlay"></div><div class="automatewoo-modal  ${ sizeClass }"><div class="automatewoo-modal__contents"><div class="automatewoo-modal__header"></div></div><div class="automatewoo-icon-close ${ this.triggerClasses.close }"></div></div></div>`
			);
		},

		loading() {
			document.body.classList.add( 'automatewoo-modal-loading' );
		},

		contents( contents ) {
			document.body.classList.remove( 'automatewoo-modal-loading' );
			$( '.automatewoo-modal__contents' ).html( contents );

			AW.initTooltips();
		},

		/**
		 * Closes modal, by changin classes on `document.body` and removing modal elements.
		 *
		 * @fires awmodal-close on the `document.body`.
		 */
		close() {
			document.body.classList.remove(
				'automatewoo-modal-open',
				'automatewoo-modal-loading'
			);
			$( '.automatewoo-modal-container' ).remove();

			// Fallback to Event in the browser does not support CustomEvent, like IE.
			const eventCtor =
				typeof CustomEvent === 'undefined' ? Event : CustomEvent;
			document.body.dispatchEvent( new eventCtor( 'awmodal-close' ) );
		},
	} );

	AutomateWoo.Modal.init();
} );
