jQuery(document).ready(function($) {	
	//Variable sortnonce is declared globally	
	var postList = $('#post-list');
	var max_levels = 6;
	if ( reorder_posts.hierarchical == 'false' ) {
		max_levels = 1;
	}
	var callback = false;
	var sort_start = {};
	var sort_end = {};
	postList.nestedSortable( {
		forcePlaceholderSize: true,
		handle: 'div',
		helper:	'clone',
		items: 'li',
		maxLevels: max_levels,
		opacity: .6,
		placeholder: 'ui-sortable-placeholder',
		revert: 250,
		tabSize: 25,
		tolerance: 'pointer',
		toleranceElement: '> div',
		listType: 'ul',
		update: function( event, ui ) {
			reorder_row_nesting();
			$loading_animation = jQuery( '#loading-animation' );
			var reorder_ajax_callback = function( response ) {
				response = jQuery.parseJSON( response );
				if ( true == response.more_posts ) {
					$.post( ajaxurl, response, reorder_ajax_callback );
				} else {
					if ( false != callback ) {
						var callback_ajax_args = callback;
						callback = false;
						$.post( ajaxurl, callback_ajax_args, reorder_ajax_callback );
					} else {
    					
						$('#loading-animation').hide();
					}
				}
			};			
			ui.item.find( 'div.row-content:first' ).append( $loading_animation );
			
			$loading_animation.show();
			
			//Get the end items where the post was placed
			sort_end.item = ui.item;
			sort_end.prev = ui.item.prev( ':not(".placeholder")' );
			sort_end.next = ui.item.next( ':not(".placeholder")' );
			
			//Get starting post parent
			var start_post_parent = parseInt( sort_start.item.attr( 'data-parent' ) );
			
			//Get ending post parent
			var end_post_parent = 0;
			if( sort_end.prev.length > 0 || sort_end.next.length > 0 ) {
				if ( sort_end.prev.length > 0 ) {
					end_post_parent = parseInt( sort_end.prev.attr( 'data-parent' ) );
				} else if ( sort_end.next.length > 0 ) {
					end_post_parent = parseInt( sort_end.next.attr( 'data-parent' ) );
				} 	
			} else if ( sort_end.prev.length == 0 && sort_end.next.length == 0 ) {
				//We're the only child :(
				end_post_parent = ui.item.parents( 'li:first' ).attr( 'data-id' );	
			}
			
			//Update post parent in DOM
			sort_end.item.attr( 'data-parent', end_post_parent );
			
			
			
			//Find the menu order and update dom accordingly
			var list_offset = 0;
			if( end_post_parent == 0 ) {
				var offset = $( '#reorder-offset' ).val();
				
				//Get index in list order and update dom
				list_offset = parseInt(offset) + parseInt(sort_end.item.index());
			} else {
				list_offset = parseInt(sort_end.item.index());
			}
			sort_end.item.attr( 'data-menu-order', list_offset );
			
			//Get attributes
			var attributes = {};
			$.each(  sort_end.item[0].attributes, function() {
				attributes [ this.name ] = this.value;
			} );
			
			//Perform Ajax Call
			var parent_ajax_args = {
				action: reorder_posts.action,
				post_parent: end_post_parent,
				start: 0,
				nonce: reorder_posts.sortnonce,
				post_id: sort_end.item.attr( 'data-id' ),
				menu_order: sort_end.item.attr( 'data-menu-order' ),
				excluded: {},
				post_type: sort_start.item.attr( 'data-post-type' ),
				attributes: attributes
			};
			//Determine if we need to sort child nodes - if post_parent ids don't match and there are any remaining child nodes, we need to reorder those
			if ( start_post_parent != end_post_parent ) {
				//Determine if there are any remaining child nodes
				callback = {
					action: reorder_posts.action,
					post_parent: start_post_parent,
					start: 0,
					nonce: reorder_posts.sortnonce,
					post_id: 0,
					menu_order: 0,
					excluded: {},
					post_type: sort_start.item.attr( 'data-post-type' ),
					attributes: attributes
				};
			}
			
			$.post( ajaxurl, parent_ajax_args, reorder_ajax_callback );			
		},
		start: function( event, ui ) {
			sort_start.item = ui.item;
			sort_start.prev = ui.item.prev( ':not(".placeholder")' );
			sort_start.next = ui.item.next( ':not(".placeholder")' );
		}
	});
	
	function reorder_row_nesting() {
		// Add Nesting capabilities
		$( '.row-content' ).each( function() {
			var parents_count = $( this ).parents( 'ul' ).length;
			var padding = 30;
			if ( parents_count > 1 ) {
				var new_padding = parents_count * 20 + padding;
				$( this ).css('padding-left', new_padding + 'px');
			} else {
				$( this ).css('padding-left', padding + 'px');
			}
		} );
	}
	
	reorder_row_nesting()
	
	$( "#post-list .expand span" ).toggle( function() {
		$( this ).removeClass( 'dashicons-arrow-right' ).addClass( 'dashicons-arrow-down' );
		$( this ).parent().parent().next( '.children' ).fadeIn( "fast" );
		return false;
	}, function() {
		$( this ).removeClass( 'dashicons-arrow-down' ).addClass( 'dashicons-arrow-right' );
		$( this ).parent().parent().next( '.children' ).fadeOut( "fast" );
		return false;
	} );
});