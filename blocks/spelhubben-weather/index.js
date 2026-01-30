( function ( wp ) {
  const { __ } = wp.i18n;
  const { createElement: el, Fragment } = wp.element;
  const { registerBlockType } = wp.blocks;
  const { InspectorControls, useBlockProps } = wp.blockEditor || wp.editor;
  const { PanelBody, TextControl, ToggleControl, SelectControl, RangeControl } = wp.components;
  const ServerSideRender = wp.serverSideRender || ( wp.serverSideRender && wp.serverSideRender.default ) || null;

  const LAYOUTS = [
    { label: __( 'Inline', 'spelhubben-weather' ), value: 'inline' },
    { label: __( 'Compact', 'spelhubben-weather' ), value: 'compact' },
    { label: __( 'Card', 'spelhubben-weather' ), value: 'card' },
    { label: __( 'Detailed', 'spelhubben-weather' ), value: 'detailed' },
  ];

  const UNITS = [
    { label: __( 'Metric (°C, m/s, mm)', 'spelhubben-weather' ), value: 'metric' },
    { label: __( 'Metric (°C, km/h, mm)', 'spelhubben-weather' ), value: 'metric_kmh' },
    { label: __( 'Metric (°C, knt, mm)', 'spelhubben-weather' ), value: 'metric_knt' },
    { label: __( 'Imperial (°F, mph, in)', 'spelhubben-weather' ), value: 'imperial' },
  ];

  const WIND_UNITS = [
    { label: __( 'm/s', 'spelhubben-weather' ), value: 'ms' },
    { label: __( 'km/h', 'spelhubben-weather' ), value: 'kmh' },
    { label: __( 'mph', 'spelhubben-weather' ), value: 'mph' },
    { label: __( 'knt (knots)', 'spelhubben-weather' ), value: 'knt' },
  ];

  registerBlockType( 'spelhubben-weather/spelhubben-weather', {
    edit: ( props ) => {
      const { attributes, setAttributes } = props;
      const bp = useBlockProps();

      const syncPlace = ( v ) => setAttributes( { place: v, ort: v } );

      return el(
        Fragment,
        null,
        el(
          InspectorControls,
          null,
          el(
            PanelBody,
            { title: __( 'Location', 'spelhubben-weather' ), initialOpen: true },
            el( TextControl, {
              label: __( 'Place (name)', 'spelhubben-weather' ),
              value: attributes.place || attributes.ort || '',
              onChange: syncPlace,
              placeholder: __( 'e.g. Stockholm', 'spelhubben-weather' ),
            } ),
            el( TextControl, {
              label: 'Lat',
              value: attributes.lat || '',
              onChange: ( v ) => setAttributes( { lat: v } ),
            } ),
            el( TextControl, {
              label: 'Lon',
              value: attributes.lon || '',
              onChange: ( v ) => setAttributes( { lon: v } ),
              help: __( 'Coordinates override place when set.', 'spelhubben-weather' ),
            } )
          ),
          el(
            PanelBody,
            { title: __( 'Display', 'spelhubben-weather' ), initialOpen: false },
            el( SelectControl, {
              label: __( 'Layout', 'spelhubben-weather' ),
              value: attributes.layout,
              options: LAYOUTS,
              onChange: ( v ) => setAttributes( { layout: v } ),
            } ),
            el( 'div', { style: { marginBottom: '16px' } },
              el( 'label', { style: { display: 'block', marginBottom: '8px', fontWeight: '600' } }, __( 'Show fields', 'spelhubben-weather' ) ),
              [
                { key: 'temp', label: __( 'Temperature', 'spelhubben-weather' ) },
                { key: 'wind', label: __( 'Wind speed', 'spelhubben-weather' ) },
                { key: 'wind_dir', label: __( 'Wind direction', 'spelhubben-weather' ) },
                { key: 'icon', label: __( 'Weather icon', 'spelhubben-weather' ) },
              ].map( ( item ) => {
                const currentShow = ( attributes.show || '' ).split( ',' ).map( s => s.trim() );
                const isChecked = currentShow.includes( item.key );
                return el( ToggleControl, {
                  label: item.label,
                  checked: isChecked,
                  onChange: ( checked ) => {
                    let nextShow;
                    if ( checked ) {
                      nextShow = [ ...currentShow, item.key ];
                    } else {
                      nextShow = currentShow.filter( s => s !== item.key );
                    }
                    setAttributes( { show: nextShow.filter( Boolean ).join( ',' ) } );
                  },
                } );
              } )
            ),
            el( ToggleControl, {
              label: __( 'Show map', 'spelhubben-weather' ),
              checked: !! attributes.map,
              onChange: ( v ) => setAttributes( { map: !! v } ),
            } ),
            el( RangeControl, {
              label: __( 'Map height (px)', 'spelhubben-weather' ),
              min: 120,
              max: 800,
              step: 10,
              value: attributes.mapHeight,
              onChange: ( v ) => setAttributes( { mapHeight: v } ),
            } ),
            el( ToggleControl, {
              label: __( 'Animations', 'spelhubben-weather' ),
              checked: !! attributes.animate,
              onChange: ( v ) => setAttributes( { animate: !! v } ),
            } ),
            el( ToggleControl, {
              label: __( 'Show weather alerts', 'spelhubben-weather' ),
              checked: !! attributes.showAlerts,
              onChange: ( v ) => setAttributes( { showAlerts: !! v } ),
            } )
            ,
            el( ToggleControl, {
              label: __( 'Show tides (tidvatten)', 'spelhubben-weather' ),
              checked: !! attributes.tides,
              onChange: ( v ) => setAttributes( { tides: !! v } ),
              help: __( 'Requires tide support enabled in plugin settings. Provider and API key are configured under Settings → Spelhubben Weather.', 'spelhubben-weather' ),
            } )
          ),
          el(
            PanelBody,
            { title: __( 'Units & format', 'spelhubben-weather' ), initialOpen: false },
            el( SelectControl, {
              label: __( 'Preset', 'spelhubben-weather' ),
              value: attributes.units,
              options: UNITS,
              onChange: ( v ) => setAttributes( { units: v } ),
            } ),
            el( SelectControl, {
              label: __( 'Wind unit override', 'spelhubben-weather' ),
              value: attributes.wind_unit || '',
              options: [
                { label: __( '(use preset)', 'spelhubben-weather' ), value: '' },
                ...WIND_UNITS
              ],
              onChange: ( v ) => setAttributes( { wind_unit: v } ),
            } ),
            el( TextControl, {
              label: __( 'Date format (PHP date)', 'spelhubben-weather' ),
              help: __( 'Used for forecast labels (default: D j/n)', 'spelhubben-weather' ),
              value: attributes.date_format || '',
              onChange: ( v ) => setAttributes( { date_format: v } ),
              placeholder: 'D j/n',
            } )
          ),
          el(
            PanelBody,
            { title: __( 'Forecast', 'spelhubben-weather' ), initialOpen: false },
            el( SelectControl, {
              label: __( 'Type', 'spelhubben-weather' ),
              value: attributes.forecast,
              options: [
                { label: __( 'None', 'spelhubben-weather' ), value: 'none' },
                { label: __( 'Daily', 'spelhubben-weather' ), value: 'daily' },
              ],
              onChange: ( v ) => setAttributes( { forecast: v } ),
            } ),
            el( RangeControl, {
              label: __( 'Days', 'spelhubben-weather' ),
              min: 3,
              max: 10,
              value: attributes.days,
              onChange: ( v ) => setAttributes( { days: v } ),
            } )
          )
        ),
        el(
          'div',
          bp,
          ServerSideRender
            ? el( ServerSideRender, { block: 'spelhubben-weather/spelhubben-weather', attributes } )
            : el( 'p', null, __( 'Spelhubben Weather preview (ServerSideRender unavailable). Save/update to view.', 'spelhubben-weather' ) )
        )
      );
    },
    save: () => null,
  } );
} )( window.wp );
