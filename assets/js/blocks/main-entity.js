( function( blocks, element ) {
    var el = element.createElement;
    blocks.registerBlockType( 'sd/main-entity', {
        title: 'Directory Listing',
        icon: 'database',
        category: 'widgets',
        edit: function() {
            return el( 'p', {}, 'Directory Listing Output' );
        },
        save: function() {
            return null;
        }
    } );
} )( window.wp.blocks, window.wp.element );
