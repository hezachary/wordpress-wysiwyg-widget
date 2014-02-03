wordpress-wysiwyg-widget
========================

To make wp_editor can work in dynamic html (which means without reload page, js changes the page structure), there are two major issues need to take care:

1. tinymce
2. qucik-tags

For [tinymce]:

a. need reset UI properly

 - solution is [remove mce instance] -> [get proper mce settings] -> [re-init a new mce instance]

 - in js code (id means textarea id): 
```javascript
        tinymce.execCommand('mceRemoveEditor', true, id);
        var init = tinymce.extend( {}, tinyMCEPreInit.mceInit[ id ] );
        try { tinymce.init( init ); } catch(e){}
```

b. need data write back to textarea before submit

 - solution is [bind click to button] -> on submt :: [turn off mce] -> [turn on submit]
 - in js code:
```javascript
      jq('textarea[id="' + id + '"]').closest('form').find('input[type="submit"]').click(function(){
        if( getUserSetting( 'editor' ) == 'tmce' ){
          var id = mce.find( 'textarea' ).attr( 'id' );
				  tinymce.execCommand( 'mceRemoveEditor', false, id );
				  tinymce.execCommand( 'mceAddEditor', false, id );
        }
			  return true;
      });
```

For [Quick Tags]:

a. Re-init tags

 - [Get settings] -> [setup mouse event] -> [re-init QTags]

b. Switch to proper tab (mce tab or quick tag tab)

 - [switch to current tab mode]

 - both above in js code:
```javascript
    	if ( typeof(QTags) == 'function' ) {
        jq( '[id="wp-' + id + '-wrap"]' ).unbind( 'onmousedown' );
        jq( '[id="wp-' + id + '-wrap"]' ).bind( 'onmousedown', function(){
          wpActiveEditor = id;
        });
        QTags( tinyMCEPreInit.qtInit[ id ] );
        QTags._buttonsInit();
        switchEditors.switchto( jq( 'textarea[id="' + id + '"]' ).closest( '.widget-mce' ).find( '.wp-switch-editor.switch-' + ( getUserSetting( 'editor' ) == 'html' ? 'html' : 'tmce' ) )[0] );
      }
```

Also, please remember if you use ajax, every time post back mce UI, you need re-do [reset mce UI] and [Qtags] in you js.
A easy solution is using js code in you post back html, and detect in php of:

`$isAjax = defined( 'DOING_AJAX' ) && DOING_AJAX == true );`

About default settings in js value:

1. mce : `tinyMCEPreInit.mceInit`

2. qtags : `tinyMCEPreInit.qtInit`

If you try to use default setting for widget mode, you need locate default settings.

To get widget template id, in js code:

```javascript
    function getTemplateWidgetId( id ){
      var form = jQuery( 'textarea[id="' + id + '"]' ).closest( 'form' );
      var id_base = form.find( 'input[name="id_base"]' ).val();
      var widget_id = form.find( 'input[name="widget-id"]' ).val();
      return id.replace( widget_id, id_base + '-__i__' );
    }
```
So you can get settings by:

1. for mce:
```javascript
    var init;
    if( typeof tinyMCEPreInit.mceInit[ id ] == 'undefined' ){
      init = tinyMCEPreInit.mceInit[ id ] = tinymce.extend( {}, tinyMCEPreInit.mceInit[ getTemplateWidgetId( id ) ] );
    }else{
      init = tinyMCEPreInit.mceInit[ id ];
    }
```

2. For Qtags:
```javascript
		var qInit;
		if( typeof tinyMCEPreInit.qtInit[ id ] == 'undefined' ){
			qInit = tinyMCEPreInit.qtInit[ id ] = jq.extend( {}, tinyMCEPreInit.qtInit[ getTemplateWidgetId( id ) ] );
			qInit['id'] = id;
		}else{
			qInit = tinyMCEPreInit.qtInit[ id ];
		}
```
