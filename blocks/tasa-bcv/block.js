( function ( blocks, element, blockEditor, components, i18n, serverSideRender ) {
    var el = element.createElement;
    var __ = i18n.__;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var RangeControl = components.RangeControl;
    var SelectControl = components.SelectControl;
    var ServerSideRender = serverSideRender;

    blocks.registerBlockType( 'tasa-bcv/tasa-widget', {
        edit: function ( props ) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            return el(
                element.Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        { title: __( 'Ajustes de Tasa BCV', 'tasa-bcv-widget' ) },
                        el( SelectControl, {
                            label: __( 'Moneda a mostrar', 'tasa-bcv-widget' ),
                            value: attributes.moneda,
                            options: [
                                { label: __( 'Dólar (USD)', 'tasa-bcv-widget' ), value: 'usd' },
                                { label: __( 'Euro (EUR)', 'tasa-bcv-widget' ), value: 'eur' },
                                { label: __( 'Ambas', 'tasa-bcv-widget' ), value: 'ambas' },
                            ],
                            onChange: function ( val ) {
                                setAttributes( { moneda: val } );
                            },
                        } ),
                        attributes.moneda === 'ambas' &&
                            el( TextControl, {
                                label: __( 'Etiqueta USD', 'tasa-bcv-widget' ),
                                value: attributes.etiquetaUsd,
                                onChange: function ( val ) {
                                    setAttributes( { etiquetaUsd: val } );
                                },
                            } ),
                        attributes.moneda === 'ambas' &&
                            el( TextControl, {
                                label: __( 'Etiqueta EUR', 'tasa-bcv-widget' ),
                                value: attributes.etiquetaEur,
                                onChange: function ( val ) {
                                    setAttributes( { etiquetaEur: val } );
                                },
                            } ),
                        el( TextControl, {
                            label: __( 'Prefijo', 'tasa-bcv-widget' ),
                            value: attributes.prefijo,
                            onChange: function ( val ) {
                                setAttributes( { prefijo: val } );
                            },
                        } ),
                        el( TextControl, {
                            label: __( 'Sufijo', 'tasa-bcv-widget' ),
                            value: attributes.sufijo,
                            onChange: function ( val ) {
                                setAttributes( { sufijo: val } );
                            },
                        } ),
                        el( ToggleControl, {
                            label: __( 'Mostrar fecha de actualización', 'tasa-bcv-widget' ),
                            checked: attributes.mostrarFecha,
                            onChange: function ( val ) {
                                setAttributes( { mostrarFecha: val } );
                            },
                        } ),
                        attributes.mostrarFecha &&
                            el( TextControl, {
                                label: __( 'Etiqueta de la fecha', 'tasa-bcv-widget' ),
                                value: attributes.etiquetaFecha,
                                onChange: function ( val ) {
                                    setAttributes( { etiquetaFecha: val } );
                                },
                            } ),
                        el( SelectControl, {
                            label: __( 'Alineación', 'tasa-bcv-widget' ),
                            value: attributes.alineacion,
                            options: [
                                { label: __( 'Izquierda', 'tasa-bcv-widget' ), value: 'left' },
                                { label: __( 'Centro', 'tasa-bcv-widget' ), value: 'center' },
                                { label: __( 'Derecha', 'tasa-bcv-widget' ), value: 'right' },
                            ],
                            onChange: function ( val ) {
                                setAttributes( { alineacion: val } );
                            },
                        } ),
                        el( RangeControl, {
                            label: __( 'Tamaño de fuente (px)', 'tasa-bcv-widget' ),
                            value: attributes.tamanoFuente,
                            min: 12,
                            max: 72,
                            onChange: function ( val ) {
                                setAttributes( { tamanoFuente: val } );
                            },
                        } )
                    )
                ),
                el( ServerSideRender, {
                    block: 'tasa-bcv/tasa-widget',
                    attributes: attributes,
                } )
            );
        },
        // El render real ocurre en PHP (render_callback), así que save
        // devuelve null y WordPress usa dynamic rendering.
        save: function () {
            return null;
        },
    } );
} )(
    window.wp.blocks,
    window.wp.element,
    window.wp.blockEditor,
    window.wp.components,
    window.wp.i18n,
    window.wp.serverSideRender
);
