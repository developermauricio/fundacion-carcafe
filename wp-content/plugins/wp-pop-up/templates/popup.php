<?php
/**
 * Template for displaying the popup markup
 */

if ( ! defined( 'WPINC' ) ) {
	die( 'Direct access not allowed' );
}
?>

<?php
// Empty variable that may hold the aria-labelleby attribute
$labelledby = '';

// If the content has an H1, set the above variable to have the aria-labelleby attribute
if ( false !== strpos( $content, '<h1' ) ) {
	$labelledby = 'aria-labelledby="wp-popup-inner-title"';
}
?>

<div id="wp-popup-content" style="display: none !important;">
	<div id="wp-popup-inner" role="dialog" aria-modal="true" aria-describedby="wp-popup-inner" <?php echo $labelledby; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php echo str_replace( '<h1', '<h1 id="wp-popup-inner-title"', $content ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
</div>
