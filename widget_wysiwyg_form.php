    <div class="widget-mce">
    <br />
    <?php wp_editor( $html, $field_id, array('textarea_name' => $field_name, 'editor_css' => '<style>table.mceToolbar > tbody > tr{transform: scale(0.85,0.85);-ms-transform: scale(0.85,0.85);-webkit-transform: scale(0.85,0.85); transform-origin: 0 0; -moz-transform-origin: 0 0; -webkit-transform-origin: 0 0;}td.mceToolbar span[role=application]{display: block;width: 386px;overflow: hidden;}</style>' ) );?>
    <?php 
    $class = new ReflectionClass( '_WP_Editors' );
    $property = $class->getProperty('mce_settings');
    $property->setAccessible( true );
    $mce_settings = $property->getValue();?>
    <br />
    </div>
    <script type="text/javascript">
    //<![CDATA[
        (function(jq){
            var id = <?php echo json_encode( $field_id );?>;
            var isPostMode = <?php echo json_encode( (boolean)sizeof($_POST) );?>;
            var settings = <?php echo json_encode( $mce_settings[$field_id] );?>;
            if( id.toString().length && isPostMode && typeof tinymce != 'undefined'){
                tinymce.execCommand('mceRemoveEditor', true, id);
                tinymce.execCommand('mceAddEditor', true, id);
                tinymce.get(id).getBody().setAttribute('skin', settings.skin );
                
                if ( typeof(QTags) == 'function' ) {
                    for ( qt in tinyMCEPreInit.qtInit ) {
                        var objQtSettings = jq.extend({}, tinyMCEPreInit.qtInit[qt]);
                        objQtSettings['id'] = id;
                        
                        jq( '[id="wp-' + id + '-wrap"]' ).unbind( 'onmousedown' );
                        jq( '[id="wp-' + id + '-wrap"]' ).bind( 'onmousedown', function(){
                            wpActiveEditor = id;
                        });
                        
                        QTags(objQtSettings);
                        QTags._buttonsInit();
                        break;
                    }
                    switchEditors.switchto( jq( 'textarea[id="' + id + '"]' ).closest( '.widget-mce' ).find( '.wp-switch-editor.switch-' + ( getUserSetting( 'editor' ) == 'html' ? 'html' : 'tmce' ) )[0] );
                }
            }
        })(jQuery);
    //]]>
    </script>
