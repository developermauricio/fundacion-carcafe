// Either create a new empty object, or work with the existing one.
window.WPPopup_CMB2 = window.WPPopup_CMB2 || {};

(function( window, document, $, app, undefined ) {
	'use strict';

	app.cache = function() {
		app.$ = {};
		app.$.triggerSelect = $( document.getElementById( 'wp_popup_trigger' ) );
		app.$.displayOnSelect = $( document.getElementById( 'wp_popup_display_lightbox_on' ) );
		app.$.trackingSelect = $( document.getElementById( 'wp_popup_tracking_enable' ) );
	};

	/**
	 * Return an array of values used to trigger the show/hide
	 *
	 * @param show_target
	 * @returns {any}
	 */
	function getAttributes(show_target) {
		let targetValues = document.getElementById( show_target ).getAttribute( 'data-conditional-value' );

		return JSON.parse(targetValues);
	}

	/**
	 * Show/hide the children fields for a given element
	 *
	 * @param element
	 */
	function doShowHide( element ) {
		// Which fields to show/hide, retrieve from the element's data attribute
		const conditionalFields = JSON.parse( element.attr('data-conditional-show-id') );

		// Some fields show/hide many fields
		$.each( conditionalFields, function(index, value){
			// Get the field to show/hide
			let conditionalField = document.getElementById( value );

			// Get which value will trigger the show/hide
			let triggerValues = getAttributes( value );

			// Show/hide
			if ( triggerValues.includes( element.val() ) ) {
				$(conditionalField).closest('.cmb-row').show();
			} else {
				$(conditionalField).closest('.cmb-row').hide();
			}
		});

	}

	app.init = function() {
		app.cache();

		// Listen to the "Trigger" field
		app.$.triggerSelect.on( 'change', function( event ) {
			doShowHide( $(this) );
		} ).trigger( 'change' );

		// Listen to the "Display Popup On" field
		app.$.displayOnSelect.on( 'change', function( event ) {
			doShowHide( $(this) );
		} ).trigger( 'change' );

		// Listen to the GA tracking field
		app.$.trackingSelect.on( 'change', function( event ) {
			doShowHide( $(this) );
		} ).trigger( 'change' );
	};

	$( document ).ready( app.init );
})( window, document, jQuery, WPPopup_CMB2 );
