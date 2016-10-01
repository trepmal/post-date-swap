<?php
/**
 ** Plugin Name: Post Date Swap
 **
 **/

/**
 * Insert button above post table
 *
 * @return void
 */
function pds_restrict_manage_posts( $post_type ) {
	if ( 'post' !== $post_type ) {
		return;
	}
	?>
	<span id="pds-span"></span>
	<button id="pds-button" class="button" style="margin: 1px 8px 0" disabled="disabled">Swap post dates</button>
	<script>
	jQuery(document).ready(function($){

		$('input[type=checkbox][name="post\[\]"]').on('click',function(event) {
			var $checked = $('input[type=checkbox][name="post\[\]"]:checked');

			if ( 2 == $checked.length ) {
				$('#pds-button').removeAttr('disabled');
			} else {
				$('#pds-button').attr('disabled','disabled');
			}

		});

		$('#pds-button').on('click', function(event) {
			event.preventDefault();
			var $checked = $('input[type=checkbox][name="post\[\]"]:checked');
			// console.log( $checked );
			post_ids = _.pluck( $checked, 'value' )

			$.post( window.ajaxurl, {
				action:   'pds-button',
				post_ids: post_ids,
				nonce:    '<?php echo wp_create_nonce( 'pds' ); ?>'
			}, function( data ) {
				console.log( data.success );
				if ( data.success ) {
					$('tr#post-'+data.data.postA.ID+' .column-date').append('<br /><em>Swapped: ' + data.data.postA.post_date + '</em>' );
					$('tr#post-'+data.data.postB.ID+' .column-date').append('<br /><em>Swapped: ' + data.data.postB.post_date + '</em>' );
				} else {
					alert( 'error' );
				}
			}, 'json' );
		});

	});
	</script>
	<?php
}
add_action( 'restrict_manage_posts', 'pds_restrict_manage_posts' );

/**
 * Ajax callback
 *
 * @return void
 */
function pds_ajax_button() {
	if ( ! check_ajax_referer( 'pds', 'nonce' ) ) {
		wp_send_json_error( );
	}
	$post_ids = array_map( 'absint', $_POST['post_ids'] );

	$postA = get_post( $post_ids[0] );
	$postB = get_post( $post_ids[1] );

	$postA_date = $postA->post_date;
	$postB_date = $postB->post_date;

	wp_update_post( array(
		'ID'        => $postA->ID,
		'post_date' => $postB_date,
	) );
	wp_update_post( array(
		'ID'        => $postB->ID,
		'post_date' => $postA_date,
	) );

	wp_send_json_success( array(
		'postA' => array( 'ID' => $postA->ID, 'post_date' => mysql2date( 'm/d/y h:ia', $postB_date ) ),
		'postB' => array( 'ID' => $postB->ID, 'post_date' => mysql2date( 'm/d/y h:ia', $postA_date ) ),
	) );
}
add_action( 'wp_ajax_pds-button', 'pds_ajax_button' );
