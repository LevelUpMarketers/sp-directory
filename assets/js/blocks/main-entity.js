( function( blocks, element ) {
    var el = element.createElement;
    blocks.registerBlockType( 'cpb/main-entity', {
        title: 'Main Entity',
        icon: 'database',
        category: 'widgets',
        edit: function() {
            return el( 'p', {}, 'Main Entity Output' );
        },
        save: function() {
            return null;
        }
    } );
} )( window.wp.blocks, window.wp.element );
