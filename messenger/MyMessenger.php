<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <meta name="google" content="notranslate" />
    <title>Ext JS RTL Example</title>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    	
	<script type="text/javascript" src="/generalUI/ext5/packages/ext-theme-neptune-touch/build/ext-theme-neptune-touch.js"></script>
	<script type="text/javascript" src="/generalUI/ext5/ext-all-rtl.js"></script>	
	<link rel="stylesheet" type="text/css" href="/generalUI/ext5/packages/ext-theme-neptune-touch/build/resources/ext-theme-neptune-touch-all-rtl-debug.css" />	
    
    <script>
        Ext.require([
    'Ext.container.Viewport',
    'Ext.grid.Panel',
    'Ext.grid.plugin.RowEditing',
    'Ext.layout.container.Border'
]);

Ext.define('MyPanel', {
    extend: 'Ext.panel.Panel',
    width: (Math.max(document.documentElement.clientWidth, window.innerWidth || 0) * 0.8),
    plugins: 'responsive',
    responsiveConfig: {
        'width < 800': {
            width: 500
        },
        'width >= 800': {
            width: 700
        }
    },
    title: 'Title',
    html: 'panel body content'
});

Ext.onReady(function() {
    Ext.create('MyPanel', {
        renderTo: document.body
    });
});

    </script>
</head>
<body>
</body>
</html>
