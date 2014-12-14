jQuery(document).ready(function($) {	
	//Variable sortnonce is declared globally	
	var postList = $('#post-list');
	var max_levels = 6;
	if ( reorder_posts.hierarchical == 'false' ) {
		max_levels = 1;
	}
	postList.nestedSortable( {
		forcePlaceholderSize: true,
		handle: 'div',
		helper:	'clone',
		items: 'li',
		maxLevels: max_levels,
		opacity: .6,
		placeholder: 'placeholder',
		revert: 250,
		tabSize: 25,
		tolerance: 'pointer',
		toleranceElement: '> div',
		listType: 'ul',
		update: function( event, ui ) {
			var order = $('ul#post-list').nestedSortable( 'toHierarchy',{ listType: 'ul'});
			order = JSON.stringify( order , null, 2);
			$('#loading-animation').show();
			$.post( ajaxurl, { action: 'post_sort', nonce: reorder_posts.sortnonce, data: order }, function( response ) {
				$('#loading-animation').hide()
			}, 'json' );
			
		}
	});
	$( "#post-list a" ).toggle( function() {
		$( this ).html( reorder_posts.collapse );
		$( this ).parent().next( '.children' ).slideDown( "slow" );
		return false;
	}, function() {
		$( this ).html( reorder_posts.expand );
		$( this ).parent().next( '.children' ).slideUp( "slow" );
		return false;
	} );
});