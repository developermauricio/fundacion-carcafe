<?php
/**
 * Configures the meta fields for the popup options
 * PHP Version 5
 *
 * @since   1.0
 * @package WP_Popup
 * @author  Cornershop Creative <devs@cshp.co>
 */

if ( ! defined( 'WPINC' ) ) {
	die( 'Direct access not allowed' );
}

/**
 * Sets all of standard CMB2 fields for the popup
 *
 * Class Admin_Fields
 */
class WP_Popup_Admin_Fields {

	/**
	 * Holds the all the non-style fields
	 *
	 * @var object
	 */
	private $config_fields;



	/**
	 * Holds all the fields for the popup mask styles
	 *
	 * @var object
	 */
	private $outer_style_fields;



	/**
	 * Holds all the basic fields for the popup styles
	 *
	 * @var object
	 */
	private $inner_style_fields;

    /**
     * Holds all the advanced fields for the popup styles
     *
     * @var object
     */
    private $advanced_fields;



	/**
	 * Holds all the fields for the analytics
	 *
	 * @var object
	 */
	private $tracking_fields;



	/**
	 * Set the post type for CMB2
	 *
	 * @var array
	 */
	private $post_type;



	/**
	 * Prefix for CMB2
	 *
	 * @var String
	 */
	private $prefix;


	/**
	 * Sets up some variables on instantiation.
	 */
	public function __construct() {
		$this->post_type = array( 'wp_popup' );
		$this->prefix    = 'wp_popup_';
	}



	/**
	 * Initialize hooks
	 */
	public function init() {
		add_action( 'cmb2_admin_init', array( $this, 'setup_CMB2_fields' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'cmb2_label_fix' ), 99 );
	}



	/**
	 * Define the custom fields for each WP Popup post.
	 */
	public function setup_CMB2_fields() { //phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		// Set up the Display Options Box
		$this->config_fields = new_cmb2_box(
			array(
				'id'           => $this->prefix . 'options',
				'title'        => __( 'WP Popup Display Options', 'wp-popup' ),
				'object_types' => $this->post_type,
				'context'      => 'side',
				'priority'     => 'low',
			)
		);

		// Set up the Analytics Options Box
		$this->tracking_fields = new_cmb2_box(
			array(
				'id'           => $this->prefix . 'analytics',
				'title'        => __( 'WP Popup Analytics Options', 'wp-popup' ),
				'object_types' => $this->post_type,
				'context'      => 'side',
				'priority'     => 'low',
			)
		);

		// Set up the Outer Styles Box
		$this->outer_style_fields = new_cmb2_box(
			array(
				'id'           => $this->prefix . '_outer_styles',
				'title'        => __( 'WP Popup Outer Styles', 'wp-popup' ),
				'object_types' => $this->post_type,
				'context'      => 'normal',
				'priority'     => 'low',
			)
		);

		// Set up the Inner Styles Basic Box
		$this->inner_style_fields = new_cmb2_box(
			array(
				'id'           => $this->prefix . '_inner_fields',
				'title'        => __( 'WP Popup Inner Styles', 'wp-popup' ),
				'object_types' => $this->post_type,
				'context'      => 'normal',
				'priority'     => 'low',
			)
		);

        // Set up the Inner Styles Advanced Box
        $this->advanced_fields = new_cmb2_box(
            array(
                'id'           => $this->prefix . '_inner_adv_fields',
                'title'        => __( 'WP Popup Advanced Styles', 'wp-popup' ),
                'object_types' => $this->post_type,
                'context'      => 'normal',
                'priority'     => 'low',
            )
        );

		// Start Date
		$this->config_fields->add_field(
			array(
				'name' => __( 'Start Date', 'wp-popup' ),
				'desc' => __( 'The date the popup should start to be visible on.', 'wp-popup' ),
				'id'   => $this->prefix . 'start_date',
				'type' => 'text_date',
			)
		);

		// Start Date
		$this->config_fields->add_field(
			array(
				'name' => __( 'End Date', 'wp-popup' ),
				'desc' => __( 'The date the popup should stop being visible on.', 'wp-popup' ),
				'id'   => $this->prefix . 'end_date',
				'type' => 'text_date',
			)
		);

		// Identifier
		$this->config_fields->add_field(
			array(
				'name'            => __( 'Popup Identifier', 'wp-popup' ),
				'desc'            => __( 'Enter a name or number to uniquely identify this popup. Change this when revising the popup content to reset users’ cookies.', 'wp-popup' ),
				'id'              => $this->prefix . 'identifier',
				'type'            => 'text_small',
				'sanitization_cb' => array( $this, 'wp_popup_dashes' ),
			)
		);

		// Page Display
		$this->config_fields->add_field(
			array(
				'name'       => __( 'Display Popup On', 'wp-popup' ),
				'desc'       => __( 'Select where to show this popup.', 'wp-popup' ),
				'id'         => $this->prefix . 'display_lightbox_on',
				'type'       => 'select',
				'options'    => array(
					'home'             => __( 'Homepage', 'wp-popup' ),
					'all'              => __( 'All Pages', 'wp-popup' ),
					'all_but_homepage' => __( 'All But Homepage', 'wp-popup' ),
					'specific_posts'   => __( 'Specific Post(s)/Page(s)', 'wp-popup' ),
					'none'             => __( 'Nowhere (disabled)', 'wp-popup' ),
				),
				'attributes' => array(
					'data-conditional-show-id' => wp_json_encode( array( $this->prefix . 'display_lightbox_on_posts' ) ),
				),
			)
		);

		// Posts to display on (if 'specific_posts' is selected above)
		$popup_post_types = get_post_types(
			array(
				'exclude_from_search' => false,
			)
		);
		$popup_post_types = array_values( $popup_post_types );
		$popup_post_types = array_diff( $popup_post_types, array( 'attachment', 'revision' ) );
		$this->config_fields->add_field(
			array(
				'name'       => __( 'Display On Posts/Pages', 'wp-popup' ),
				'desc'       => __( 'Select post(s)/page(s) on which to show this popup.', 'wp-popup' ),
				'id'         => $this->prefix . 'display_lightbox_on_posts',
				'type'       => 'post_search_ajax',
				'sortable'   => false,
				'limit'      => 100,
				'query_args' => array(
					'post_type'      => $popup_post_types,
					'post_status'    => get_post_stati(),
					'posts_per_page' => 100,
				),
				'attributes' => array(
					'required'               => false,
					'data-conditional-id'    => $this->prefix . 'display_lightbox_on',
					'data-conditional-value' => wp_json_encode( array( 'specific_posts' ) ),
				),
			)
		);

		// Display Frequency
		$this->config_fields->add_field(
			array(
				'name'    => __( 'Once Seen', 'wp-popup' ),
				'desc'    => __( 'What should happen after a user sees this popup? Note: This setting may be overridden when a user clears their cookies.', 'wp-popup' ),
				'id'      => $this->prefix . 'suppress',
				'type'    => 'select',
				'options' => array(
					'always'  => __( 'Never show it to that user again', 'wp-popup' ),
					'session' => __( 'Don\'t show again during the user\'s current browser session', 'wp-popup' ),
					'wait-7'  => __( 'Wait a week before showing it again', 'wp-popup' ),
					'wait-30' => __( 'Wait 30 days before showing it again', 'wp-popup' ),
					'wait-90' => __( 'Wait 90 days before showing it again', 'wp-popup' ),
					'never'   => __( 'Keep showing it', 'wp-popup' ),
				),
			)
		);

		// Trigger
		$this->config_fields->add_field(
			array(
				'name'       => __( 'Trigger', 'wp-popup' ),
				'desc'       => __( 'When does the popup appear?', 'wp-popup' ),
				'id'         => $this->prefix . 'trigger',
				'type'       => 'select',
				'options'    => array(
					'immediate'   => __( 'Immediately on page load', 'wp-popup' ),
					'delay'       => __( 'N seconds after load (specify)', 'wp-popup' ),
					'scroll'      => __( 'After page is scrolled N pixels (specify)', 'wp-popup' ),
					'scroll-half' => __( 'After page is scrolled halfway', 'wp-popup' ),
					'scroll-full' => __( 'After page is scrolled to bottom', 'wp-popup' ),
					'minutes'     => __( 'After N minutes spent on site this visit (specify)', 'wp-popup' ),
					'pages'       => __( 'Once N pages have been visited in last 90 days (specify)', 'wp-popup' ),
					'exit'        => __( 'When the user is about to close the page (Exit intent)', 'wp-popup' ),
				),
				'attributes' => array(
					'data-conditional-show-id' => wp_json_encode( array( $this->prefix . 'trigger_amount' ) ),
				),
			)
		);

		// Trigger Amount
		$this->config_fields->add_field(
			array(
				'name'            => __( 'Trigger Amount', 'wp-popup' ),
				'desc'            => __( 'Specify the precise quantity/time/amount/number ("N") for the trigger.', 'wp-popup' ),
				'id'              => $this->prefix . 'trigger_amount',
				'type'            => 'text_small',
				'sanitization_cb' => array( $this, 'wp_popup_abs' ),
				'escape_cb'       => array( $this, 'wp_popup_abs' ),
				'attributes'      => array(
					'required'               => false,
					'data-conditional-id'    => $this->prefix . 'trigger',
					'data-conditional-value' => wp_json_encode( array( 'delay', 'scroll', 'minutes', 'pages' ) ),
					'type'                   => 'number',
					// we're making it numeric via https://gist.github.com/jtsternberg/c09f5deb7d818d0d170b
					'pattern'                => '\d*',
					'min'                    => '0',
					'step'                   => '0.1',
				),
			)
		);

		// Mobile
		$this->config_fields->add_field(
			array(
				'name'    => 'Disable On Mobile',
				'desc'    => 'Check this box to suppress this popup on mobile devices. (Recommended)',
				'id'      => $this->prefix . 'disable_on_mobile',
				'type'    => 'checkbox',
				'default' => $this->set_checkbox_default_for_new_post( true ),
			)
		);

		// Tracking Description
		$this->tracking_fields->add_field(
			array(
				'name' => __( 'Google Analytics Events', 'wp-popup' ),
				'desc' => __( 'Links in popup\'s content will be tracked as events. Google Analytics is required to be loaded on the page. Works with Universal Analytics (analytics.js) or Global Site Tag (gtag.js)', 'wp-popup' ),
				'id'   => $this->prefix . 'tracking_title',
				'type' => 'title',
			)
		);

		// Tracking Enable
		$this->tracking_fields->add_field(
			array(
				'desc'       => __( 'Google Analytics Event Tracking', 'wp-popup' ),
				'id'         => $this->prefix . 'tracking_enable',
				'type'       => 'select',
				'options'    => array(
					'disabled' => __( 'Disabled', 'wp-popup' ),
					'enabled'  => __( 'Enabled', 'wp-popup' ),
				),
				'attributes' => array(
					'data-conditional-show-id' => wp_json_encode( array( $this->prefix . 'tracking_label', $this->prefix . 'tracking_category' ) ),
				),
			)
		);

		// Tracking Label
		$this->tracking_fields->add_field(
			array(
				'name'       => __( 'Event Label', 'wp-popup' ),
				'desc'       => __( 'Displayed as the Event Label in Google Analytics. Leaving this blank will default to the title of the popup.', 'wp-popup' ),
				'id'         => $this->prefix . 'tracking_label',
				'type'       => 'text_small',
				'attributes' => array(
					'required'               => false,
					'data-conditional-id'    => $this->prefix . 'tracking_enable',
					'data-conditional-value' => wp_json_encode( array( 'enabled' ) ),
				),
			)
		);

		// Tracking Category
		$this->tracking_fields->add_field(
			array(
				'name'       => __( 'Event Category', 'wp-popup' ),
				'desc'       => __( 'Displayed as the Event Category in Google Analytics. Leaving this blank will default to "WP Popup".', 'wp-popup' ),
				'id'         => $this->prefix . 'tracking_category',
				'type'       => 'text_small',
				'attributes' => array(
					'required'               => false,
					'data-conditional-id'    => $this->prefix . 'tracking_enable',
					'data-conditional-value' => wp_json_encode( array( 'enabled' ) ),
				),
			)
		);

		// Disable "the_content" hooks
		$this->config_fields->add_field(
			array(
				'name'    => 'Disable "the_content" hooks',
				'desc'    => 'Check this box to suppress third party hooks from runnning on this popup "the_content". Helps remove other plugins injecting unnnecessary html into the pop up content. (Recommended)',
				'id'      => $this->prefix . 'disable_the_content_hooks',
				'type'    => 'checkbox',
				'default' => $this->set_checkbox_default_for_new_post( true ),
			)
		);

		// Background Image
		$this->inner_style_fields->add_field(
			array(
				'name'       => 'Background Image',
				'desc'       => 'Upload / Choose an image to be used for popup background. Best size depends on your popup’s content, but probably at least 300x300px.',
				'id'         => $this->prefix . 'bg_image',
				'type'       => 'file',
				// Optional:
				'options'    => array(
					'url' => false,
					// Hide the text input for the url
				),
				'text'       => array(
					'add_upload_file_text' => 'Add Image',
					// Change upload button text. Default: "Add or Upload File"
				),
				// query_args are passed to wp.media's library query.
				'query_args' => array(
					'type' => array(
						'type' => 'image',
					),
					// Make library only display images.
				),
			)
		);

		// Background Color
		$this->inner_style_fields->add_field(
			array(
				'name'    => __( 'Background Color', 'wp-popup' ),
				'desc'    => __( 'Background color of the popup.', 'wp-popup' ),
				'id'      => $this->prefix . 'background_color',
				'type'    => 'colorpicker',
				'options' => array(
					'alpha' => true,
				),
			)
		);

		// Padding
		$this->inner_style_fields->add_field(
			array(
				'name'            => __( 'Padding', 'wp-popup' ),
				'desc'            => __( 'Padding (in pixels) of the popup.', 'wp-popup' ),
				'id'              => $this->prefix . 'padding',
				'type'            => 'text_small',
				'sanitization_cb' => array( $this, 'wp_popup_absint' ),
				'escape_cb'       => array( $this, 'wp_popup_absint' ),
				'attributes'      => array(
					'type'    => 'number',
					'pattern' => '\d*',
					'min'     => '0',
				),
			)
		);

		// Border Width
		$this->inner_style_fields->add_field(
			array(
				'name'            => __( 'Border Width', 'wp-popup' ),
				'desc'            => __( 'Border width (in pixels) of the popup.', 'wp-popup' ),
				'id'              => $this->prefix . 'border_width',
				'type'            => 'text_small',
				'sanitization_cb' => array( $this, 'wp_popup_absint' ),
				'escape_cb'       => array( $this, 'wp_popup_absint' ),
				'attributes'      => array(
					'type'    => 'number',
					'pattern' => '\d*',
					'min'     => '0',
				),
			)
		);

		// Border Radius
		$this->inner_style_fields->add_field(
			array(
				'name'            => __( 'Border Radius', 'wp-popup' ),
				'desc'            => __( 'Border radius (in pixels) of the popup.', 'wp-popup' ),
				'id'              => $this->prefix . 'border_radius',
				'type'            => 'text_small',
				'sanitization_cb' => array( $this, 'wp_popup_absint' ),
				'escape_cb'       => array( $this, 'wp_popup_absint' ),
				'attributes'      => array(
					'type'    => 'number',
					'pattern' => '\d*',
					'min'     => '0',
				),
			)
		);

		// Border Color
		$this->inner_style_fields->add_field(
			array(
				'name'    => __( 'Border Color', 'wp-popup' ),
				'desc'    => __( 'Border color of the popup.', 'wp-popup' ),
				'id'      => $this->prefix . 'border_color',
				'type'    => 'colorpicker',
				'options' => array(
					'alpha' => true,
				),
			)
		);

		// Background Color
		$this->outer_style_fields->add_field(
			array(
				'name'    => __( 'Background Color', 'wp-popup' ),
				'desc'    => __( 'Background color of the mask behind the popup.', 'wp-popup' ),
				'id'      => $this->prefix . 'background_color_mask',
				'type'    => 'colorpicker',
				'options' => array(
					'alpha' => true,
				),
			)
		);

		// Close Button Theme
		$this->outer_style_fields->add_field(
			array(
				'name'    => __( 'Close Button Theme', 'wp-popup' ),
				'desc'    => __( 'Choose a dark or light theme for the close button.', 'wp-popup' ),
				'id'      => $this->prefix . 'close_theme_color',
				'type'    => 'select',
				'options' => array(
					'dark'  => __( 'Dark', 'wp-popup' ),
					'light' => __( 'Light', 'wp-popup' ),
				),
			)
		);

		// Close Button Inner
		$this->outer_style_fields->add_field(
			array(
				'name'    => __( 'Close Button', 'wp-popup' ),
				'desc'    => __( 'Choose an "&#10005" or "Close"', 'wp-popup' ),
				'id'      => $this->prefix . 'close_icon',
				'type'    => 'select',
				'options' => array(
					'&#10005'                 => __( '&#10005', 'wp-popup' ),
					__( 'Close', 'wp-popup' ) => __( 'Close', 'wp-popup' ),
				),
			)
		);

        // Max Width
        $this->advanced_fields->add_field(
            array(
                'name'            => __( 'Max Width', 'wp-popup' ),
                'desc'            => __( 'Maximum width (in pixels) of the popup displayed to users. If blank or zero, popup will stretch to accommodate content.', 'wp-popup' ),
                'id'              => $this->prefix . 'max_width',
                'type'            => 'text_small',
                'sanitization_cb' => array( $this, 'wp_popup_absint' ),
                'escape_cb'       => array( $this, 'wp_popup_absint' ),
                'attributes'      => array(
                    'type'    => 'number',
                    // we're making it numeric via https://gist.github.com/jtsternberg/c09f5deb7d818d0d170b
                    'pattern' => '\d*',
                    'min'     => '0',
                ),
            )
        );

        // Max Height
        $this->advanced_fields->add_field(
            array(
                'name'       => __( 'Max Height', 'wp-popup' ),
                'desc'       => __( 'Maximum height of the popup displayed to users. If blank or zero, popup will stretch to accommodate content.', 'wp-popup' ),
                'id'         => $this->prefix . 'max_height',
                'type'       => 'single_dimension_and_unit',
                'attributes' => array(
                    'type'    => 'number',
                    // we're making it numeric via https://gist.github.com/jtsternberg/c09f5deb7d818d0d170b
                    'pattern' => '\d*',
                    'min'     => '0',
                ),
            )
        );

        // Min Height
        $this->advanced_fields->add_field(
            array(
                'name'       => __( 'Min Height', 'wp-popup' ),
                'desc'       => __( 'Minimum height of the popup displayed to users. If blank or zero, popup will only be as tall as content, plus any padding.', 'wp-popup' ),
                'id'         => $this->prefix . 'min_height',
                'type'       => 'single_dimension_and_unit',
                'attributes' => array(
                    'type'    => 'number',
                    // we're making it numeric via https://gist.github.com/jtsternberg/c09f5deb7d818d0d170b
                    'pattern' => '\d*',
                    'min'     => '0',
                ),
            )
        );

        // Opacity
        $this->advanced_fields->add_field(
            array(
                'name'            => __( 'Opacity', 'wp-popup' ),
                'desc'            => __( 'The opacity of the popup. 0 is invisible, 1 is full color.', 'wp-popup' ),
                'id'              => $this->prefix . 'opacity',
                'type'            => 'range_slider',
                'default'         => 1,
                'sanitization_cb' => array( $this, 'wp_popup_abs' ),
                'escape_cb'       => array( $this, 'wp_popup_abs' ),
                'attributes'      => array(
                    'pattern' => '\d*',
                    'min'     => '0',
                ),

            )
        );

        // Z-Index Field
        $this->advanced_fields->add_field(
            array(
                'name'    => __( 'Use Max Z-Index', 'wp-popup' ),
                'desc'    => __( 'Enable this option if the popup is not showing above other website content correctly.', 'wp-popup' ),
                'id'      => $this->prefix . 'z_index',
                'type'    => 'checkbox',
            )
        );

	}

	/**
	 * Fix for the color picker labels being missing post 5.4.2.
	 */
	public function cmb2_label_fix( $hook ) {
		global $wp_version;
		if ( version_compare( $wp_version, '5.4.2', '>=' ) ) {
			wp_localize_script(
				'wp-color-picker',
				'wpColorPickerL10n',
				array(
					'clear'            => __( 'Clear', 'wp-popup' ),
					'clearAriaLabel'   => __( 'Clear color', 'wp-popup' ),
					'defaultString'    => __( 'Default', 'wp-popup' ),
					'defaultAriaLabel' => __( 'Select default color', 'wp-popup' ),
					'pick'             => __( 'Select Color', 'wp-popup' ),
					'defaultLabel'     => __( 'Color value', 'wp-popup' ),
				)
			);
		}
	}



	/**
	 * Only return default value if we don't have a post ID (in the 'post' query variable)
	 * From https://github.com/CMB2/CMB2/wiki/Tips-&-Tricks#setting-a-default-value-for-a-checkbox
	 *
	 * @param  bool $default On/Off (true/false).
	 * @return mixed          Returns true or '', the blank default.
	 */
	public function set_checkbox_default_for_new_post( $default ) {
		return isset( $_GET['post'] ) ? '' : ( $default ? (string) $default : '' ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}


	/**
	 * Wrapper around absint() that can take 3 arguments, because of how CMB2 invokes callbacks
	 *
	 * @return null|int
	 */
	public function wp_popup_absint( $value ) {
		// If no value was submitted, return nothing to avoid a 0 being saved to it.
		if ( empty( $value ) ) {
			return null;
		}
		return absint( $value );
	}


	/**
	 * Wrapper around abs() that can take 3 arguments, because of how CMB2 invokes callbacks
	 *
	 * @return null|float
	 */
	public function wp_popup_abs( $value, $field_args, $field ) {
	    // Force a 1 value for the opacity field if no value submitted
	    if ( $this->prefix . 'opacity' === $field->args['id'] && empty( $value ) ) {
            return 1;
        }

		// If no value was submitted, return nothing to avoid a 0 being saved to it.
		if ( empty( $value ) ) {
			return null;
		}

		return abs( $value );
	}

	/**
	 * Replace whitespaces for dashes
	 *
	 * @param string $value post title.
	 *
	 * @return string
	 */
	public function wp_popup_dashes( $value ) {
		return sanitize_title_with_dashes( $value, 'save' );
	}
}
