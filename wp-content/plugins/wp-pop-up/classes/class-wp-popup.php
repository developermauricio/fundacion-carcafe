<?php
/**
 * Create the custom post type, load dependencies and add hooks
 * PHP Version 5
 *
 * @since 1.0
 * @package WP_Popup
 * @author Cornershop Creative <devs@cshp.co>
 */

if ( ! defined( 'WPINC' ) ) {
    die( 'Direct access not allowed' );
}

/**
 * Performs 95% of the plugin functionality.
 *
 * Class WP_Popup
 */
class WP_Popup {

    /**
     * Configuration object for the current popup
     *
     * @var StdClass
     */
    private $config;

    /**
     * Styles for the popup window and background
     *
     * @var string
     */
    private $modal_styles_output;

    /**
     * Styles for the modal background mask
     *
     * @var string
     */
    private $modal_outer_style_properties;

    /**
     * All of the available CSS styles
     *
     * @var array
     */
    private $modal_inner_style_properties;

    /**
     * All of the CSS Styles the user set for the inner modal
     *
     * @var bool
     */
    private $modal_inner_has_set_style_properties;

    /**
     * All of the CSS Styles the user set for the outer modal
     *
     * @var bool
     */
    private $modal_outer_has_set_style_properties;

    /**
     * The current post being viewed
     *
     * @var int
     */
    private $current_post = 0;

    /**
     * Assign a stdClass to the config property, query all of the smart popups, load dependencies and set all of the
     * possible CSS properties
     */
    public function __construct() {
        $this->modal_inner_style_properties = array(
            /**
             * The Background Image is not set here, it's setup as inline JS
             *
             * 'units' => 'px' means we're "hard-coding" pixels as the units
             * 'units' => true means the units are stored in the CMB2 field
             * 'units' => false means it doesn't require units like a hex
             */
            array(
                'id'       => 'max_width',
                'property' => 'max-width',
                'units'    => 'px',
            ),
            array(
                'id'       => 'max_height',
                'property' => 'max-height',
                'units'    => true,
            ),
            array(
                'id'       => 'min_height',
                'property' => 'min-height',
                'units'    => true,
            ),
            array(
                'id'       => 'padding',
                'property' => 'padding',
                'units'    => 'px',
            ),
            array(
                'id'       => 'border_width',
                'property' => 'border-width',
                'units'    => 'px',
            ),
            array(
                'id'       => 'border_radius',
                'property' => 'border-radius',
                'units'    => 'px',
            ),
            array(
                'id'       => 'border_color',
                'property' => 'border-color',
                'units'    => false,
            ),
            array(
                'id'       => 'opacity',
                'property' => 'opacity',
                'units'    => false,
            ),
            array(
                'id'       => 'background_color',
                'property' => 'background-color',
                'units'    => false,
            ),
        );

        $this->modal_outer_style_properties = array(
            array(
                'id'       => 'background_color_mask',
                'property' => 'background-color',
                'units'    => false,
            ),
            array(
                'id'       => 'z_index',
                'property' => 'z-index',
                'units'    => false
            ),
        );

        $this->config                               = new stdClass();
        $this->config->disable_on_mobile            = '';
        $this->config->display_filter               = false;
        $this->modal_inner_has_set_style_properties = false;
        $this->modal_outer_has_set_style_properties = false;
        $this->post_query();
        $this->load_dependencies();
    }

    /**
     * Do all the hooks
     */
    public function init() {
        add_action( 'init', array( $this, 'post_type' ), 10 );

        add_action( 'manage_wp_popup_posts_custom_column', array( $this, 'admin_custom_columns' ), 10, 2 );
        add_filter( 'manage_wp_popup_posts_columns', array( $this, 'admin_add_columns' ) );

        add_action( 'wp', array( $this, 'post_loop' ), 15 );
        add_action( 'wp', array( $this, 'get_inner_set_styles' ), 50 );
        add_action( 'wp', array( $this, 'get_outer_set_styles' ), 50 );
        add_action( 'wp', array( $this, 'set_js_options' ), 30 );

        add_action( 'wp_footer', array( $this, 'footer' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'admin_notices', array( $this, 'multiple_instances_admin_notice' ) );
        add_action( 'post_updated_messages', array( $this, 'cache_admin_notice' ), 10, 1 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
        add_filter( 'fl_builder_post_types', array( $this, 'beaver_builder_enable' ) );
        add_action( 'pre_get_posts', array( $this, 'disable_single_post' ), 80 );
        add_filter( 'the_content', array( $this, 'preview_message' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'load_editor_assets' ), 10 );

        // Add a subset of the standard the_content filters to our own filter hook, which will be used
        // instead of `the_content` when the 'Disable "the_content" hooks' checkbox is checked.
        global $wp_embed;
        add_filter( 'wp_popup_content', array( $wp_embed, 'run_shortcode' ), 8 );
        add_filter( 'wp_popup_content', array( $wp_embed, 'autoembed' ), 8 );
        add_filter( 'wp_popup_content', 'do_blocks', 9 );
        add_filter( 'wp_popup_content', 'wptexturize' );
        add_filter( 'wp_popup_content', 'wpautop' );
        add_filter( 'wp_popup_content', 'shortcode_unautop' );
        add_filter( 'wp_popup_content', 'prepend_attachment' );
        add_filter( 'wp_popup_content', 'wp_filter_content_tags' );
        // formerly `wp_make_content_images_responsive()`
        add_filter( 'wp_popup_content', 'capital_P_dangit', 11 );
        add_filter( 'wp_popup_content', 'do_shortcode', 11 );
        add_filter( 'wp_popup_content', 'convert_smilies', 20 );
    }

    /**
     * Add a preview message
     *
     * @param string $content The post content.
     *
     * @return string
     */
    public function preview_message( $content ) {
        if ( is_singular( 'wp_popup' ) ) {
            $content = __( 'You are currently previewing the popup', 'wp-popup' );
        }

        return $content;
    }


    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Load CMB2 Library
        if ( file_exists( dirname( __DIR__ ) . '/includes/cmb2/init.php' ) ) {
            include_once dirname( __DIR__ ) . '/includes/cmb2/init.php';
        } elseif ( file_exists( dirname( __DIR__ ) . '/includes/CMB2/init.php' ) ) {
            include_once dirname( __DIR__ ) . '/includes/CMB2/init.php';
        }

        if ( file_exists( dirname( __DIR__ ) . '/includes/cmb2-field-post-search-ajax/cmb-field-post-search-ajax.php' ) ) {
            /**
             * Our other CSHP plugin, WP Congress uses this file below also
             * Prevent this class from being instantiated twice 'causing doubled-up ajax search fields.
             */
            if ( ! class_exists( 'MAG_CMB2_Field_Post_Search_Ajax' ) ) {
                include_once dirname( __DIR__ ) . '/includes/cmb2-field-post-search-ajax/cmb-field-post-search-ajax.php';
            }
        }

        // Include CMB2 Custom Fields
        include_once dirname( __FILE__ ) . '/class-wp-popup-custom-fields.php';

        // Include CMB2 configuration
        include_once dirname( __FILE__ ) . '/class-wp-popup-admin-fields.php';

        // Initialize CMB2 Custom Fields
        $fields = new WP_Popup_Custom_Fields();
        $fields->init();

        // Initialize CMB2 configuration
        $fields = new WP_Popup_Admin_Fields();
        $fields->init();
    }



    /**
     * Register Custom Post Type for Overlays
     */
    public function post_type() {
        $labels = array(
            'name'                  => _x( 'Popups', 'Post Type General Name', 'wp-popup' ),
            'singular_name'         => _x( 'Popup', 'Post Type Singular Name', 'wp-popup' ),
            'menu_name'             => __( 'WP Popup', 'wp-popup' ),
            'name_admin_bar'        => __( 'WP Popup', 'wp-popup' ),
            'archives'              => __( 'Popup Archives', 'wp-popup' ),
            'parent_item_colon'     => __( 'Parent Item:', 'wp-popup' ),
            'all_items'             => __( 'All Popups', 'wp-popup' ),
            'add_new_item'          => __( 'Add New Popup', 'wp-popup' ),
            'add_new'               => __( 'Create New Popup', 'wp-popup' ),
            'new_item'              => __( 'New Popup', 'wp-popup' ),
            'edit_item'             => __( 'Edit Popup', 'wp-popup' ),
            'update_item'           => __( 'Update Popup', 'wp-popup' ),
            'view_item'             => __( 'View Popup', 'wp-popup' ),
            'search_items'          => __( 'Search Popups', 'wp-popup' ),
            'not_found'             => __( 'Not found', 'wp-popup' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'wp-popup' ),
            'featured_image'        => __( 'Featured Image', 'wp-popup' ),
            'insert_into_item'      => __( 'Insert into popup', 'wp-popup' ),
            'uploaded_to_this_item' => __( 'Uploaded to this popup', 'wp-popup' ),
            'items_list'            => __( 'Popup list', 'wp-popup' ),
            'items_list_navigation' => __( 'Popup list navigation', 'wp-popup' ),
            'filter_items_list'     => __( 'Filter popups', 'wp-popup' ),
        );
        $args   = array(
            'label'                 => __( 'WP Popup', 'wp-popup' ),
            'description'           => __( 'Lightboxes to potentially display on website', 'wp-popup' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 30,
            'menu_icon'             => 'dashicons-slides',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => true,
            'capability_type'       => 'page',
            'show_in_rest'          => true,
            'rest_base'             => 'wp_popup',
            'rest_controller_class' => 'WP_REST_Posts_Controller',
        );
        register_post_type( 'wp_popup', $args ); // phpcs:ignore WordPress.NamingConventions.ValidPostTypeSlug.ReservedPrefix

        // This meta is used by the Gutenberg Plugin Sidebar Popup Select
        register_meta(
            'post',
            'wp_popup_display_lightbox',
            array(
                'type'         => 'integer',
                'single'       => true,
                'show_in_rest' => true,
            )
        );

        register_meta(
            'post',
            'wp_popup_suppress',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
            )
        );

        register_meta(
            'post',
            'wp_popup_trigger',
            array(
                'type'         => 'string',
                'single'       => true,
                'show_in_rest' => true,
            )
        );

        register_meta(
            'post',
            'wp_popup_trigger_amount',
            array(
                'type'         => 'integer',
                'single'       => true,
                'show_in_rest' => true,
            )
        );

        register_meta(
            'post',
            'wp_popup_disable_on_mobile',
            array(
                'type'         => 'boolean',
                'single'       => true,
                'show_in_rest' => true,
            )
        );
    }

    /**
     * Get the wp popup post meta setting
     * the function prioritizes none WP_Popup posts meta
     * in order to override the WP_Popup settings.
     *
     * @param int    $wp_popup_id The pop up post id.
     * @param string $key         The meta key.
     * @param bool   $single      Return single or multiple values.
     *
     * @return mix
     */
    public function get_wp_popup_setting( $wp_popup_id = 0, $key = '', $single = true ) {
        // bail if no key or popup id is passed.
        if ( empty( $key ) || empty( $wp_popup_id ) ) {
            return;
        }

        // if preview send preview overrides.
        if ( is_preview() ) {
            switch ( $key ) {
                case $this->config->prefix . 'start_date':
                    $meta = '';
                    break;
                case $this->config->prefix . 'end_date':
                    $meta = '';
                    break;
                case $this->config->prefix . 'suppress':
                    $meta = 'never';
                    break;
                case $this->config->prefix . 'trigger':
                    $meta = 'immediate';
                    break;
                case $this->config->prefix . 'disable_on_mobile':
                    $meta = 'off';
                    break;

            }

            // If $meta is set, return it â€” we are overriding. Otherwise, continue as normal.
            if ( isset( $meta ) ) {
                return $meta;
            }
        }//end if

        // If the post has the equivalent meta/setting value return it over the wp popup value.
        if ( ! empty( get_post_meta( $this->current_post, $key, $single ) ) ) {
            return get_post_meta( $this->current_post, $key, $single );
        }

        // return the wp popup value.
        return get_post_meta( $wp_popup_id, $key, $single );
    }

    /**
     * Implement content displayed in custom columns for the post list admin page.
     */
    public function admin_custom_columns( $column, $post_id ) {

        switch ( $column ) {

            case 'displayed_on':
                $field         = $this->config->prefix . 'display_lightbox_on';
                $display_value = get_post_meta( $post_id, $field, true );
                if ( 'specific_posts' === $display_value ) {
                    // If this lightbox is set to display on specific posts, list them.
                    $display_on_ids   = $this->get_popup_display_ids( $post_id );
                    $display_on_posts = array_filter( array_map( 'get_post', $display_on_ids ) );
                    if ( empty( $display_on_posts ) ) {
                        esc_html_e( 'Nowhere (disabled)', 'wp-popup' );
                    } else {
                        $post_titles = array();
                        foreach ( $display_on_posts as $display_on_post ) {
                            $post_titles[] = '<a href="' . esc_url( get_permalink( $display_on_post->ID ) ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $display_on_post->post_title ) . '</a>';
                        }
                        echo implode( ', ', $post_titles ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    }
                } else {
                    // For all other display options, show some static text.
                    $display_options = array(
                        'home'             => __( 'Homepage', 'wp-popup' ),
                        'all'              => __( 'All Pages', 'wp-popup' ),
                        'all_but_homepage' => __( 'All But Homepage', 'wp-popup' ),
                        'none'             => __( 'Nowhere (disabled)', 'wp-popup' ),
                    );
                    // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                    esc_html_e( $display_options[ $display_value ], 'wp-popup' );
                }//end if
                break;

            case 'trigger':
                $field           = $this->config->prefix . 'trigger';
                $amount          = get_post_meta( $post_id, 'wp_popup_trigger_amount', true );
                $display_options = array(
                    'immediate'   => __( 'Immediately on page load', 'wp-popup' ),
                    // translators: %s number of seconds
                    'delay'       => __( sprintf( '%s seconds after load', $amount ), 'wp-popup' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
                    // translators: %s number of pixels
                    'scroll'      => __( sprintf( 'After page is scrolled %s pixels', $amount ), 'wp-popup' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
                    'scroll-half' => __( 'After page is scrolled halfway', 'wp-popup' ),
                    'scroll-full' => __( 'At bottom of page', 'wp-popup' ),
                    // translators: %s number of minutes
                    'minutes'     => __( sprintf( 'After %s minutes spent on site this visit', $amount ), 'wp-popup' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
                    // translators: %s user selected pages
                    'pages'       => __( sprintf( 'Once %s pages have been visited in last 90 days', $amount ), 'wp-popup' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
                    'exit'        => __( 'On page exit', 'wp-popup' ),
                );
                // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText
                esc_html_e( $display_options[ get_post_meta( $post_id, $field, true ) ], 'wp-popup' );
                break;

        }//end switch

    }

    /**
     * Check if on third party editor view.
     */
    private function is_third_party_editor_view() {
        if ( ( class_exists( '\Elementor\Plugin' ) && isset( $_GET['elementor-preview'] ) ) || ( isset( $_GET['ct_builder'] ) && $_GET['ct_builder'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return true;
        }

        return false;
    }

    /**
     * Declare columns for the post list admin page.
     *
     * @param array $columns admin columns.
     * @return array
     */
    public function admin_add_columns( $columns ) {
        unset( $columns['date'] );
        $columns['displayed_on'] = __( 'Displayed On', 'wp-popup' );
        $columns['trigger']      = __( 'Trigger', 'wp-popup' );
        $columns['date']         = __( 'Date', 'wp-popup' );
        return $columns;
    }

    /**
     * Load up our JS
     * We don't inline our JS because we need jQuery dependency
     */
    public function assets() {

        if ( ! is_admin() ) {
            wp_enqueue_script(
                'wp-popup-js',
                plugins_url( '/assets/wp-popup.js', dirname( __FILE__ ) ),
                array( 'jquery' ),
                WP_POPUP_VERSION,
                true
            );

            // Check if we should add our JS
            if ( $this->config->display_filter ) {
                wp_add_inline_script( 'wp-popup-js', 'window.wp_popup_opts = ' . wp_json_encode( $this->config->js_config ) . ';' );
            }

            wp_enqueue_style(
                'wp-popup-css',
                plugins_url( '/assets/wp-popup.css', dirname( __FILE__ ) ),
                '',
                WP_POPUP_VERSION
            );

            // Check if any styles were set, if so print them on the page.
            if ( false !== $this->modal_inner_has_set_style_properties || false !== $this->modal_outer_has_set_style_properties ) {
                wp_add_inline_style( 'wp-popup-css', $this->modal_styles_output );
            }
        }//end if
    }

    /**
     * Load assets for the admin
     */
    public function admin_assets() {
        wp_register_script(
            'admin_js',
            plugins_url( '/assets/wp-popup-admin.js', dirname( __FILE__ ) ),
            array( 'jquery' ),
            WP_POPUP_VERSION,
            true
        );

        wp_enqueue_script( 'admin_js' );
    }

    /**
     * Load assets for the block editor
     */
    public function load_editor_assets() {
        wp_enqueue_script(
            'wp-popup-gutenberg-editor',
            plugins_url( '/assets/build/editor.js', dirname( __FILE__ ) ),
            array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components', 'wp-editor', 'wp-plugins', 'wp-edit-post', 'wp-data', 'wp-compose', 'wp-dom-ready' ),
            WP_POPUP_VERSION,
            false
        );
    }

    /**
     * Perform the query for WP Popups. Also sets some config properties
     */
    public function post_query() {
        $this->config->popups_array = array();
        $this->config->prefix       = apply_filters( 'wp_popup_prefix', 'wp_popup_' );

        $query_args = array(
            'post_type'      => 'wp_popup',
            'posts_per_page' => -1,
            'order'          => 'DESC',
            'orderby'        => 'modified',
            'meta_query'     => array(
                array(
                    'key'     => $this->config->prefix . 'display_lightbox_on',
                    'value'   => 'none',
                    'compare' => '!=',
                ),
            ),
            'fields'         => 'ids',
        );

        $this->config->overlays = new WP_Query( $query_args );
    }



    /**
     * Loop through WP Popups to find one to display
     */
    public function post_loop() {
        // if we are previewing overwrite everything to display the current pop up.
        if ( is_preview() && is_singular( 'wp_popup' ) ) {
            $this->config->display_filter = true;
            $this->config->popups_array[] = get_the_ID();
            return;
        }

        // if we are on a single post view lets save the id otherwise set it to 0
        // get the id functions are known to randomly return post ids causing undesired effects.
        $this->current_post = is_singular() ? get_the_ID() : 0;

        // Obviously we can only do this if there are some overlay posts defined...
        if ( $this->config->overlays->have_posts() ) :
            while ( $this->config->overlays->have_posts() ) :

                $this->config->overlays->the_post();
                $id = get_the_ID();

                // If we found an overlay to display, keep the overlay's ID, check its mobile display and break the loop
                if ( $this->maybe_display( $id ) ) {
                    $this->maybe_mobile_display( $id );

                    $this->config->popups_array[] = array(
                        'popup_id' => $id,
                        'display'  => 0,
                    );
                }

            endwhile;
        endif;

        wp_reset_postdata();

        $this->which_popup_to_display();
    }

    /**
     * Determine which popup to display
     * @since 1.2.3
     */
    public function which_popup_to_display() {
        // Final Popup ID to display
        $this->config->display_this_popup = '';

        if ( is_admin() ) {
            return;
        }

        // Loop through each popup and apply the display filter
        foreach ( $this->config->popups_array as $key => $popup ) {

            /**
             * Filters which popup to display in the event of multiple popups on the same post.
             *
             * Pass null as the first argument since we only want to filter popups that either return a value of true or false
             * if the return value is null, then use the Popup's normal display settings to determine if the popup should show.
             * useful if no filters are being applied to the popup
             *
             * @since   1.2.3
             *
             * @param   null|bool $default_display The default value for displaying this popup. Usually is null
             * @param   bool $display If the popup should display
             * @param   int $popup_id Current ID of the popup to display
             */
            $display = apply_filters( 'wp_popup_display', null, $popup['display'], $popup['popup_id'] );
            $display = apply_filters( sprintf( 'wp_popup_display_%s', $popup['popup_id'] ), $display, $popup['display'], $popup['popup_id'] ); //phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

            // Check if the popup is being filtered
            if ( true === boolval( $display ) && ! is_null( $display ) ) {
                // Someone wants this popup to display
                $this->config->display_this_popup = $popup['popup_id'];
            } elseif ( false === boolval( $display ) && ! is_null( $display ) ) {
                unset( $this->config->popups_array[ $key ] );

                // if this popup was set to display and we returned back false, then don't show the popup
                if ( $this->config->display_this_popup === $popup['popup_id'] ) {
                    $this->config->display_this_popup = 0;
                }
            } else {
                $this->config->popups_array[ $key ]['display'] = true;
            }
        }//end foreach

        // Set a default popup to display
        if ( '' === $this->config->display_this_popup && ! empty( $this->config->popups_array ) ) {

            $default_popup_key = array_key_first( $this->config->popups_array );

            $this->config->display_this_popup = $this->config->popups_array[ $default_popup_key ]['popup_id'];
        } elseif ( empty( $this->config->popups_array ) ) {
            // if there are no popups set to display and left in the list of popups to display on this page, then don't show any popups
            $this->config->display_filter = false;
        }

    }

    /**
     * Checks a WP Popup's Meta to determine if it shows on the current page.
     *
     * @param int $wp_popup_id ID for a WP Popup Post.
     *
     * @return bool
     */
    public function maybe_display( $wp_popup_id ) {
        // if is a third party editor no need to do anything else. dont display the pop up.
        if ( $this->is_third_party_editor_view() ) {
            return false;
        }

        $hour  = '23:59';
        $today = strtotime( "today $hour" );

        // get the start and end date post meta
        // if the post meta is meta get the time string from the current time
        $start_date = ! empty( get_post_meta( $wp_popup_id, "{$this->config->prefix}start_date", true ) ) ? strtotime( get_post_meta( $wp_popup_id, "{$this->config->prefix}start_date", true ) ) : $today;
        // make sure the end date time matches the today hour format.
        $end_date = ! empty( get_post_meta( $wp_popup_id, "{$this->config->prefix}end_date", true ) ) ? strtotime( get_post_meta( $wp_popup_id, "{$this->config->prefix}end_date", true ) . " $hour" ) : $today;

        // if the popup date restrictions are not met do not show it
        // any other check is not necessary
        if ( ! ( $start_date <= $today && $end_date >= $today ) ) {
            return false;
        }

        // Does this meta value match the current page?
        if ( $this->check_display( $wp_popup_id ) ) {
            $this->config->display_filter = true;

            return true;
        }

        return false;
    }

    /**
     * Set the mobile display option for an overlay. This method only runs when the correct overlay is found
     *
     * @param int $wp_popup_id ID for a WP Popup Post.
     */
    public function maybe_mobile_display( $wp_popup_id ) {
        $meta                            = $this->get_wp_popup_setting( $wp_popup_id, $this->config->prefix . 'disable_on_mobile', true );
        $this->config->disable_on_mobile = $meta;
        unset( $meta );
    }

    /**
     * Helper function that compares the display_lightbox_on meta value
     *
     * @param int $wp_popup_id The ID of the popup to check.
     * @return bool
     */
    public function check_display( $wp_popup_id ) {

        // Get the meta to know which page to display this popup on.
        $meta = get_post_meta( $wp_popup_id, $this->config->prefix . 'display_lightbox_on', true );

        // if the current post has a popup attached to it display it
        // else run the wp popup specific checks
        if ( absint( get_post_meta( $this->current_post, $this->config->prefix . 'display_lightbox', true ) ) === $wp_popup_id ) {
            return true;

        } elseif ( 'all' === $meta || is_front_page() && 'home' === $meta || ! is_front_page() && 'all_but_homepage' === $meta ) {
            return true;

        } elseif ( 'specific_posts' === $meta && is_singular() ) {
            return in_array(
                absint( get_queried_object_id() ),
                $this->get_popup_display_ids( $wp_popup_id ),
                true
            );
        }
        return false;
    }

    /**
     * Helper function to get the list of post IDs on which a popup should be displayed.
     *
     * @param int $wp_popup_id The post ID of the popup.
     * @return array Array of post IDs, or an empty array if the popup is not set to display on specific posts.
     */
    private function get_popup_display_ids( $wp_popup_id ) {

        // Return an empty array if this popup is set to display on the homepage / everywhere else / etc.
        if ( 'specific_posts' !== get_post_meta( $wp_popup_id, $this->config->prefix . 'display_lightbox_on', true ) ) {
            return array();
        }

        // Return an empty array if the 'display_lightbox_on_posts' field is missing or empty.
        $display_on_ids = get_post_meta( $wp_popup_id, $this->config->prefix . 'display_lightbox_on_posts', true );
        if ( empty( $display_on_ids ) ) {
            return array();
        }

        // If only one post is specified, its ID will be stored as a string. Multiple posts' IDs are
        // stored as an array. Standardize to an array, and remove any empty values.
        $display_on_ids = array_filter( array_map( 'absint', (array) $display_on_ids ) );

        return $display_on_ids;
    }

    /**
     * Set up the JS Config object
     */
    public function set_js_options() {
        // Hold all the meta Keys for the JS Object
        $metas = array( 'bg_image', 'suppress', 'trigger', 'trigger_amount', 'max_width', 'overlay_identifier', 'close_icon', 'close_theme_color', 'tracking_enable', 'tracking_label', 'tracking_category' );

        // Prepare
        $this->config->js_config = array(
            'context'  => $this->config->display_filter,
            'onMobile' => ! $this->config->disable_on_mobile,
        );

        $identifier = get_post_meta( $this->config->display_this_popup, $this->config->prefix . 'identifier', true );

        // Check if they set a unique cookie buster string
        if ( ! empty( $identifier ) ) {
            $this->config->js_config['wp_popup_identifier'] = $this->config->prefix . $identifier;
        }

        foreach ( $metas as $meta_key ) {
            // Helper function to get a given meta key's value
            $meta = $this->get_wp_popup_setting( $this->config->display_this_popup, $this->config->prefix . $meta_key, true );

            // Setup default values for this meta
            if ( 'tracking_label' === $meta_key && empty( $meta ) ) {
                $meta = get_the_title( $this->config->display_this_popup );
            }

            // Setup default values for this meta
            if ( 'tracking_category' === $meta_key && empty( $meta ) ) {
                $meta = __( 'WP Popup', 'wp-popup' );
            }

            if ( ! empty( $meta ) ) {

                // Grrr the bg_image meta is named `background` in the JS object
                if ( 'bg_image' === $meta_key ) {
                    $meta_key = 'background';
                }

                $this->config->js_config[ $meta_key ] = $meta;
            }
        }//end foreach
    }

    /**
     * Output the actual overlays contents chosen for this page into the footer.
     */
    public function footer() {
        // Don't do anything if there's no overlay on this page.
        if ( ! $this->config->display_this_popup ) {
            return;
        }

        $post_id = $this->config->display_this_popup;

        if ( is_preview() ) {
            $revision = wp_get_post_revisions( $this->config->display_this_popup );
            $post_id  = ! empty( $revision ) ? array_keys( $revision )[0] : $post_id;
        }

        // If Beaver Builder is enabled for this popup post, use Beaver Builder to show the content for the popup
        if ( class_exists( 'FLBuilderModel' ) && method_exists( 'FLBuilderModel', 'is_builder_enabled' ) && FLBuilderModel::is_builder_enabled() ) {
            $wp_popup_beaver_classes = function( $classes ) {
                $classes .= ' wp-popup-beaver';

                return $classes;
            };
            add_filter( 'fl_builder_content_classes', $wp_popup_beaver_classes );
            FLBuilder::enqueue_layout_styles_scripts_by_id( $post_id );
            ob_start();
            FLBuilder::render_content_by_id( $post_id );
            $content = ob_get_contents();
            ob_end_flush();
            remove_filter( 'fl_builder_content_classes', $wp_popup_beaver_classes );
        } else {
            // Variables for the modal template
            $content = $this->apply_content_filters( get_post_field( 'post_content', $post_id ) );
        }
        // Load the modal markup
        include_once dirname( __DIR__ ) . '/templates/popup.php';
    }

    /**
     * Apply either `the_content` or `wp_popup_content` filters, depending on settings.
     */
    private function apply_content_filters( $content ) {
        $use_the_content_filters = ( 'on' !== get_post_meta( $this->config->display_this_popup, $this->config->prefix . 'disable_the_content_hooks', true ) );

        $filter = ( $use_the_content_filters ? 'the_content' : 'wp_popup_content' );

        if ( $use_the_content_filters ) {
            remove_filter( 'the_content', array( $this, 'preview_message' ) );
        }

        $content = apply_filters( $filter, $content );

        if ( $use_the_content_filters ) {
            add_filter( 'the_content', array( $this, 'preview_message' ) );
        }

        return $content;
    }

    /**
     * Loop through the possible CSS Rules to check if there's post_meta for it
     */
    public function get_inner_set_styles() {
        // Call the helper function to get_post_meta(), and pass it all the possible inner styles
        $styles = $this->get_style_metas( $this->modal_inner_style_properties );

        // We have CSS. Assemble the styles.
        if ( $styles ) {
            $this->modal_inner_has_set_style_properties = true;
            $this->assemble_styles( $styles, '.wp-popup .wp-popup-content' );
            return;
        }
    }

    /**
     * Loop through the preset CSS Rules to check if there's post_meta for it
     */
    public function get_outer_set_styles() {
        // Call the helper function to get_post_meta(), and pass it all the possible inner styles
        $styles = $this->get_style_metas( $this->modal_outer_style_properties );
        // We have CSS. Assemble the styles.
        if ( $styles ) {
            $this->modal_outer_has_set_style_properties = true;
            $this->assemble_styles( $styles );
            return;
        }
    }

    /**
     * Helper function for getting CSS out of post meta
     *
     * @param array $properties an array of possible CSS properties that could be set.
     *
     * @return bool|array $style returns false if no CSS properties are set in the post meta
     */
    public function get_style_metas( $properties ) {
        $styles = false;

        foreach ( $properties as $style_property ) {
            // Finally Get the CMB2 Meta
            $property_meta = get_post_meta( $this->config->display_this_popup, $this->config->prefix . $style_property['id'], true );
            // Define Empty Variables just in case

            $value = '';
            $units = '';

            // If there is meta, i.e., the user set a css property, add it to an array
            if ( ! empty( $property_meta ) ) {
                // Skip any property that are empty arrays
                if ( is_array( $property_meta ) && '' === $property_meta['dimension_value'] ) {
                    continue;
                }

                // Check if the property requires units or not (like a hexcolor)
                switch ( $style_property['units'] ) {
                    case 1:
                    case 'px':
                        // Check if the CMB2 field is one that came with a value and units or just a value
                        if ( is_array( $property_meta ) ) {
                            $units = $property_meta['dimension_units'] . ';';
                            $value = $property_meta['dimension_value'];
                        } else {
                            // It's a CMB2 field that came with just a value (probably a legacy field that was for pixels only)
                            // Or it's a field that the user can never decide its unit (like border-radius)
                            $units = 'px;';
                            $value = $property_meta;
                        }
                        break;
                    case null:
                        // A field like opacity or a hex value
                        $units = ';';
                        $value = $property_meta;
                        break;
                }

                // Set the CSS property name & value for the current property
                if ( 'z_index' === $style_property['id'] ) {
                    // Set a fixed z-index
                    $styles[ $style_property['property'] ] = '2147483647';
                } else {
                    // something like $styles['max-width'] = 400px; //phpcs:ignore Squiz.PHP.CommentedOutCode.Found
                    $styles[ $style_property['property'] ] = $value . $units;
                }

            }//end if
        }//end foreach

        return $styles;
    }

    /**
     * Assemble a string of CSS rules for use inside of a style tag
     *
     * @param array  $properties array of CSS properties.
     * @param string $selector the CSS selector to apply these styles to.
     */
    public function assemble_styles( $properties, $selector = '.wp-popup' ) {
        $this->modal_styles_output .= "\t" . $selector . '{' . PHP_EOL;

        foreach ( $properties as $style_property => $style_value ) {
            // Assemble one style property like max-height
            $this->modal_styles_output .= "\t\t" . $style_property . ': ' . $style_value . PHP_EOL;
        }

        // If they defined a border, set a solid style for it
        if ( array_key_exists( 'border-width', $properties ) ) {
            $this->modal_styles_output .= "\t\tborder-style:solid;" . PHP_EOL;
        }

        $this->modal_styles_output .= "\t}" . PHP_EOL;
    }

    /**
     * Admin notice to explain collisions if there's more than one overlay.
     */
    public function multiple_instances_admin_notice() {

        $overlay_count  = wp_count_posts( 'wp_popup' );
        $current_screen = get_current_screen();

        if ( 'edit-wp_popup' === $current_screen->id && $overlay_count->publish > 1 ) :
            ?>
            <div class="notice notice-warning notice-alt">
                <p><?php esc_html_e( 'Note: If more than one popup is eligible to appear on a given page, only the most recent will be shown to visitors.', 'wp-popup' ); ?></p>
            </div>
        <?php
        endif;
    }


    /**
     * Displays an admin notice when a smart overlay post is updated.
     *
     * @param array $messages holds all of the WP admin Messages.
     *
     * @return mixed
     */
    public function cache_admin_notice( $messages ) {
        $post      = get_post();
        $post_type = get_post_type( $post );

        // If we are editing another type of post, return the default messages
        if ( 'wp_popup' !== $post_type ) {
            return $messages;
        }

        // Use the existing `update` message for posts.
        // Otherwise we have to define all 10 messages for the smart_overlay post type.
        // i.e., $message['wp-popup'][1], $message['wp-popup'][2] ...
        $messages['post'][1] = __( 'Post updated. Please clear your cache to see your changes.', 'wp-popup' );

        return $messages;
    }

    /**
     * Check if a post is being edited to load some styles
     *
     * @param string $hook the current page of the admin.
     */
    public function admin_scripts( $hook ) {
        if ( 'post.php' !== $hook ) {
            return;
        }
        wp_enqueue_style(
            'admin-styles',
            plugins_url( '/assets/wp-popup-admin.css', dirname( __FILE__ ) ),
            '',
            WP_POPUP_VERSION
        );
    }

    /**
     * Disable Gutenberg Editor for this post type
     *
     * @param bool   $is_enabled Is Block editor enabled on the post.
     * @param string $post_type Current post post type.
     *
     * @return bool False since this plugin is not Block editor compatible
     */
    public function disable_gutenberg( $is_enabled, $post_type ) {
        if ( 'wp_popup' === $post_type ) {
            $is_enabled = false;
        }
        return $is_enabled;
    }

    /**
     * Enable the WP Popup with Beaver Builder pagebuilder plugin
     *
     * @param array $post_types Current post types that Beaver Builder is enabled for.
     *
     * @return array
     */
    public function beaver_builder_enable( $post_types ) {
        $post_types[] = 'wp_popup';
        return $post_types;
    }

    /**
     * Prevent the popup from being viewed from the frontend.
     *
     * Needed after enabling Beaver Builder compatibility. We want users to be able to edit popups in Beaver Builder
     * but want to prevent these posts from showing up to non-admin users.
     *
     * @param WP_Query $query Current WordPress Query object.
     */
    public function disable_single_post( $query ) {
        if ( 'wp_popup' === get_post_type( $query->query_vars['page_id'] ) && ! current_user_can( 'edit_post', get_the_ID() ) && ! is_admin() ) {
            $query->set_404();
            wp_safe_redirect( home_url( '/' ), 302 );
            exit;
        } elseif ( ! is_user_logged_in() ) {

            // prevent the popups from any sort of query results
            $excluded_posts = $query->get( 'post__not_in', array() );
            $excluded_posts = array_merge( $excluded_posts, $this->config->overlays->posts );
            $query->set( 'post__not_in', $excluded_posts );
        }

        return $query;
    }
}
