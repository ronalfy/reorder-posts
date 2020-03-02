jQuery(document).ready(function($) {	
	$( '#mn-reorder-quick-sort-button' ).on( 'click', function( e ) {
		e.preventDefault();
		var selectVal = $( '#mn-reorder-quick-sort-options' ).val();
		alert( selectVal );
	} );
});