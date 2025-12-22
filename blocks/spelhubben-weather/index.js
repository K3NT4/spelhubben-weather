( function ( wp ) {
  const { __ } = wp.i18n;
  const { registerBlockType } = wp.blocks;
  const { InspectorControls, useBlockProps } = wp.blockEditor || wp.editor;
  const { PanelBody, TextControl, ToggleControl, SelectControl, RangeControl } = wp.components;
  const ServerSideRender = wp.serverSideRender || wp.serverSideRender?.default;

  const LAYOUTS = [
    { label: __('Inline', 'spelhubben-weather'), value: 'inline' },
    { label: __('Compact', 'spelhubben-weather'), value: 'compact' },
    { label: __('Card', 'spelhubben-weather'), value: 'card' },
    { label: __('Detailed', 'spelhubben-weather'), value: 'detailed' },
  ];

  const UNITS = [
    { label: __('Metric (°C, m/s, mm)', 'spelhubben-weather'), value: 'metric' },
    { label: __('Metric (°C, km/h, mm)', 'spelhubben-weather'), value: 'metric_kmh' },
    { label: __('Imperial (°F, mph, in)', 'spelhubben-weather'), value: 'imperial' },
  ];

  registerBlockType('spelhubben/weather', {
    edit: (props) => {
      const { attributes, setAttributes } = props;
      const bp = useBlockProps();

      const syncPlace = (v) => setAttributes({ place: v, ort: v });

      return (
        <>
          <InspectorControls>
            <PanelBody title={__('Location', 'spelhubben-weather')} initialOpen={true}>
              <TextControl
                label={__('Place (name)', 'spelhubben-weather')}
                value={attributes.place || attributes.ort || ''}
                onChange={syncPlace}
                placeholder={__('e.g. Stockholm', 'spelhubben-weather')}
              />
              <TextControl label="Lat" value={attributes.lat || ''} onChange={(v)=>setAttributes({lat:v})}/>
              <TextControl label="Lon" value={attributes.lon || ''} onChange={(v)=>setAttributes({lon:v})}
                           help={__('Coordinates override place when set.', 'spelhubben-weather')}/>
            </PanelBody>

            <PanelBody title={__('Display', 'spelhubben-weather')} initialOpen={false}>
              <SelectControl
                label={__('Layout', 'spelhubben-weather')}
                value={attributes.layout}
                options={LAYOUTS}
                onChange={(v)=>setAttributes({layout:v})}
              />
              <TextControl
                label={__('Fields (comma-separated)', 'spelhubben-weather')}
                help="temp,wind,icon"
                value={attributes.show}
                onChange={(v)=>setAttributes({show:v})}
              />
              <ToggleControl label={__('Show map', 'spelhubben-weather')}
                             checked={!!attributes.map}
                             onChange={(v)=>setAttributes({map:!!v})}/>
              <RangeControl label={__('Map height (px)', 'spelhubben-weather')}
                            min={120} max={800} step={10}
                            value={attributes.mapHeight}
                            onChange={(v)=>setAttributes({mapHeight:v})}/>
              <ToggleControl label={__('Animations', 'spelhubben-weather')}
                             checked={!!attributes.animate}
                             onChange={(v)=>setAttributes({animate:!!v})}/>
            </PanelBody>

            <PanelBody title={__('Units & format', 'spelhubben-weather')} initialOpen={false}>
              <SelectControl
                label={__('Preset', 'spelhubben-weather')}
                value={attributes.units}
                options={UNITS}
                onChange={(v)=>setAttributes({units:v})}
              />
              <TextControl
                label={__('Date format (PHP date)', 'spelhubben-weather')}
                help={__('Used for forecast labels (default: D j/n)', 'spelhubben-weather')}
                value={attributes.date_format || ''}
                onChange={(v)=>setAttributes({date_format:v})}
                placeholder="D j/n"
              />
            </PanelBody>

            <PanelBody title={__('Forecast', 'spelhubben-weather')} initialOpen={false}>
              <SelectControl
                label={__('Type', 'spelhubben-weather')}
                value={attributes.forecast}
                options={[
                  { label: __('None', 'spelhubben-weather'), value: 'none' },
                  { label: __('Daily', 'spelhubben-weather'), value: 'daily' },
                ]}
                onChange={(v)=>setAttributes({forecast:v})}
              />
              <RangeControl label={__('Days', 'spelhubben-weather')}
                            min={3} max={10}
                            value={attributes.days}
                            onChange={(v)=>setAttributes({days:v})}/>
            </PanelBody>
          </InspectorControls>

          <div {...bp}>
            {ServerSideRender ? (
              <ServerSideRender block="spelhubben/weather" attributes={attributes}/>
            ) : (
              <p>{__('Spelhubben Weather preview (ServerSideRender unavailable). Save/update to view.', 'spelhubben-weather')}</p>
            )}
          </div>
        </>
      );
    },
    save: () => null,
  });
} )( window.wp );
