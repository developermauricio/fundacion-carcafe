const { __ } = wp.i18n;
const { registerPlugin } = wp.plugins;
const { Fragment } = wp.element;
const { PanelBody, PanelRow, SelectControl, TextControl, CheckboxControl } = wp.components;
const { PluginSidebar } = wp.editPost;
const { withSelect, withDispatch, select } = wp.data;
const { compose }  = wp.compose;
const domReady  = wp.domReady;

const posts = [];

//@link https://rudrastyh.com/gutenberg/plugin-sidebars.html
//@link https://rsvpmaker.com/blog/2019/12/26/gutenberg-sidebar-howto/

const pluginSidebar = () => {
	if ( select('core/editor').getCurrentPostType() === 'wp_popup' ) {
		return null;
	}


	return (
		<Fragment>
			<PluginSidebar
				name="plugin-sidebar-test"
				title={ __('Add a WP Popup','wp-popup') }
				icon='slides'
			>
				<PanelBody
					title={ __('Settings','wp-popup') }
				>
					<PanelRow>
						<PopupSelect
							label={ __('Select a Popup to display on this page.', 'wp-popup') }
							metaKey="wp_popup_display_lightbox"
						/>
					</PanelRow>
					<PanelRow>
						<StaticDropDown
							label={ __( 'Once Seen', 'wp-popup' ) }
							metaKey="wp_popup_suppress"
							selectOptions={
								[
									{ value: 'always', label: __( 'Never show it to that user again', 'wp-popup' ) },
									{ value: 'session', label: __( 'Don\'t show again during the user\'s current browser session', 'wp-popup' ) },
									{ value: 'wait-7', label: __( 'Wait a week before showing it again', 'wp-popup' ) },
									{ value: 'wait-30', label: __( 'Wait 30 days before showing it again', 'wp-popup' ) },
									{ value: 'wait-90', label: __( 'Wait 90 days before showing it again', 'wp-popup' ) },
									{ value: 'never', label: __( 'Keep showing it', 'wp-popup' ) }
								]
							}
							help={ __( 'What should happen after a user sees this popup? Note: This setting may be overridden when a user clears their cookies.', 'wp-popup' ) }
							/>
					</PanelRow>
					<PanelRow>
						<StaticDropDown
							label={ __( 'Trigger', 'wp-popup' ) }
							metaKey="wp_popup_trigger"
							selectOptions={
								[
									{ value: 'immediate', label: __( 'Immediately on page load', 'wp-popup' )},
									{ value: 'delay', label: __( 'N seconds after load (specify)', 'wp-popup' )},
									{ value: 'scroll', label: __( 'After page is scrolled N pixels (specify)', 'wp-popup' )},
									{ value: 'scroll-half', label: __( 'After page is scrolled halfway', 'wp-popup' )},
									{ value: 'scroll-full', label: __( 'After page is scrolled to bottom', 'wp-popup' )},
									{ value: 'minutes', label: __( 'After N minutes spent on site this visit (specify)', 'wp-popup' )},
									{ value: 'pages', label: __( 'Once N pages have been visited in last 90 days (specify)', 'wp-popup' )}
								]
							}
							help={ __( 'When does the popup appear?', 'wp-popup' ) }
						/>
					</PanelRow>
					<PanelRow>
						<TriggerAmount
							label={ __( 'Trigger Amount', 'wp-popup' ) }
							metaKey="wp_popup_trigger_amount"
							help= { __( 'Specify the precise quantity/time/amount/number ("N") for the trigger.', 'wp-popup' ) }
							/>
					</PanelRow>
					<PanelRow>
						<DisableOnMobile
							label={ __( 'Disable on Mobile', 'wp-popup' ) }
							metaKey= "wp_popup_disable_on_mobile"
							help={ __( 'Check this box to suppress this popup on mobile devices. (Recommended)', 'wp-popup' )}
						/>
					</PanelRow>
				</PanelBody>
			</PluginSidebar>
		</Fragment>
	);
};

const DisableOnMobile = compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ] : metaValue } }
				);
			}
		}
	}),
	withSelect( function(select, props) {
		return {
			metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ]
		}
	} ) )( function( props ) {
		return <CheckboxControl
			label={ props.label }
			value={ props.metaValue }
			checked={ props.metaValue }
			onChange={ content => props.setMetaValue(content)  }
			help={ props.help }
		/>
	}
);


const TriggerAmount = compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ] : metaValue } }
				);
			}
		}
	}),
	withSelect( function(select, props) {
		return {
			metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ]
		}
	} ) )( function( props ) {

		return <TextControl
			label={ props.label }
			value={ props.metaValue }
			onChange={ content => props.setMetaValue(content) }
			help={ props.help }
		/>
	}
);

const StaticDropDown = compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ] : metaValue } }
				);
			}
		}
	}),
	withSelect( function(select, props) {
			return {
				metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ]
			}
	} ) )( function( props ) {

		return <SelectControl
				label={ props.label }
				value={ props.metaValue }
				options={ props.selectOptions }
				onChange={ content => props.setMetaValue( content ) }
				help={ props.help }
			/>
	}
);

const PopupSelect = compose(
	withDispatch( function( dispatch, props ) {
		return {
			setMetaValue: function( metaValue ) {
				dispatch( 'core/editor' ).editPost(
					{ meta: { [ props.metaKey ]: metaValue } }
				);
			}
		}
	} ),
	withSelect( function( select, props ) {
		{ /*  withSelect( returns all the WP Popup posts and meta for the current post )( uses the WP Popup posts to display a select and save that select as post meta  ) */ }
		return {
			posts: select( 'core' ).getEntityRecords( 'postType', 'wp_popup', {
				per_page: -1,
			} ),
			metaValue: select( 'core/editor' ).getEditedPostAttribute( 'meta' )[ props.metaKey ],
		}
	} ) )( function( props ) {
		let options = [];

		if( props.posts ) {
			options.push({ value: 0, label: __( 'Select a WP Popup', 'wp-popup' ) });
			props.posts.forEach( post => {
				options.push({ value:post.id, label:post.title.rendered });
			});
		} else {
			options.push({ value: 0, label: __( 'Loading...', 'wp-popup' ) });
		}

		return <SelectControl
				label={ props.label }
				value={ props.metaValue }
				options={options}
				onChange={ content =>  props.setMetaValue( content )  }
			/>
	}
);

domReady(() => {
	registerPlugin( 'wp-popup-plugin-sidebar', { render: pluginSidebar } );
});

