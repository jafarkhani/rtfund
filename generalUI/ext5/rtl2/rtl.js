Ext.require([
    'Ext.container.Viewport',
    'Ext.grid.Panel',
    'Ext.grid.plugin.RowEditing',
    'Ext.layout.container.Border'
]);

Ext.define('MyPanel', {
    extend: 'Ext.panel.Panel',
    plugins: 'responsive',
    responsiveConfig: {
        'width < 800': {
            collapsed: true
        },
        'width >= 800': {
            collapsed: false
        }
    },
    title: 'Title',
    html: 'panel body content',
    setCollapsed: function(collapsed) {
        this[collapsed ? 'collapse' : 'expand']();
    }
});

Ext.onReady(function() {
    Ext.create('MyPanel', {
        renderTo: document.body
    });
});
