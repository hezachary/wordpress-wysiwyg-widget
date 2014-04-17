<?php
class Widget_WP_Editor extends WP_Widget {
    
    /**
     * Construct
     * Setup id and names for widget
     * @param array optional $aryExtraSettings
     * @return void
     */
    function __construct() {
        
        $strName = __( 'Wordpress Editor' );
        $aryWidgetOptions = array('classname' => 'widget_wysiwyg', 'description' => __('Arbitrary text or HTML'));
        $aryControlOptions = array();
        
        $strBaseId = str_replace( 'Widget_', '', get_class() );
        $strName = $strName ? $strName : implode(' ', preg_split('/(?=[A-Z])/', $strBaseId));
        $strBaseId = preg_replace('/\W+/', '_', $strBaseId);
        
        parent::__construct(
             $strBaseId, // Base ID
            $strName, // Name
            $aryWidgetOptions, // Args
            $aryControlOptions
        );
        
        add_action( 'admin_footer', array( $this, 'enqueue_scripts') );
    }
    
    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        $strContent = apply_filters( 'the_content', $instance['html'] );
        
        echo trim($strContent) ? $args['before_widget'] : str_replace(' class="', ' class="empty ', $args['before_widget']);
        echo $strContent;
        echo $args['after_widget'];
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        if( !$_POST ) wp_print_styles('editor-buttons');
        
        if ( class_exists( 'Metabox' ) && is_callable( array( 'Metabox', 'app' ), true ) ) add_filter('teeny_mce_before_init', array( Metabox::app(), 'customTinyMCE' ) );
        
        $field_id = $this->get_field_id( 'html' );
        $field_name = $this->get_field_name( 'html' );
        $html = isset( $instance[ 'html' ] ) ? apply_filters( 'the_content', $instance['html'] ) : '';
        ?>
        
    <div class="widget-mce">
        <br />
        <?php wp_editor( $html, $field_id, array('textarea_name' => $field_name, 'editor_css' => '<style>table.mceToolbar > tbody > tr{transform: scale(0.85,0.85);-ms-transform: scale(0.85,0.85);-webkit-transform: scale(0.85,0.85); transform-origin: 0 0; -moz-transform-origin: 0 0; -webkit-transform-origin: 0 0;}td.mceToolbar span[role=application]{display: block;width: 386px;overflow: hidden;}</style>' ) );?>
        <br />
    </div>
    <script type="text/javascript">
    //<![CDATA[
        (function(jq){
            var id = <?php echo json_encode( $field_id );?>;
            var isAjaxMode = <?php echo json_encode( defined( 'DOING_AJAX' ) && DOING_AJAX == true );?>;
            /**
             * For ajax only, that is the reason why we check $_POST
             **/
            if( id.toString().length && isAjaxMode && typeof tinymce != 'undefined' ){
                tinymce.execCommand( 'mceRemoveEditor', true, id );
                var init;
                if( typeof tinyMCEPreInit.mceInit[ id ] == 'undefined' ){
                    init = tinyMCEPreInit.mceInit[ id ] = tinymce.extend( {}, tinyMCEPreInit.mceInit[ getTemplateWidgetId( id ) ] );
                }else{
                    init = tinyMCEPreInit.mceInit[ id ];
                }
                
                try { tinymce.init( init ); } catch(e){ console.log( e ); }
                
                if ( typeof(QTags) == 'function' ) {
                    var objQtSettings;
                    
                    if( typeof tinyMCEPreInit.qtInit[ id ] == 'undefined' ){
                        objQtSettings = tinyMCEPreInit.qtInit[ id ] = jq.extend( {}, tinyMCEPreInit.qtInit[ getTemplateWidgetId( id ) ] );
                        objQtSettings['id'] = id;
                    }else{
                        objQtSettings = tinyMCEPreInit.qtInit[ id ];
                    }
                    
                    jq( '[id="wp-' + id + '-wrap"]' ).unbind( 'onmousedown' );
                    jq( '[id="wp-' + id + '-wrap"]' ).bind( 'onmousedown', function(){
                        wpActiveEditor = id;
                    });
                    
                    QTags( objQtSettings );
                    QTags._buttonsInit();
                    
                    switchEditors.switchto( jq( 'textarea[id="' + id + '"]' ).closest( '.widget-mce' ).find( '.wp-switch-editor.switch-' + ( getUserSetting( 'editor' ) == 'html' ? 'html' : 'tmce' ) )[0] );
                }
                
            }
        })(jQuery);
    //]]>
    </script>
        <?php 
    }
    

    /**
     * enqueue_scripts function
     *
     * @access public
     * @print string
     */
    public function enqueue_scripts(){
        ?>
        <script type="text/javascript">
        //<![CDATA[
        /**
         * Get widget template id, for retrieving init settings from js
         **/
        function getTemplateWidgetId( id ){
            var form = jQuery( 'textarea[id="' + id + '"]' ).closest( 'form' );
            var id_base = form.find( 'input[name="id_base"]' ).val();
            var widget_id = form.find( 'input[name="widget-id"]' ).val();
            return id.replace( widget_id, id_base + '-__i__' );
        }
        (function(jq){
            jq( document ).ready(function(){
                /**
                 * Turn off mce mode can trigger mce store data to the textarea
                 **/
                function fixTinyMceSubmit( mce ){
                    mce.closest('form').find('input[type="submit"]').click(function(){
                        if( getUserSetting( 'editor' ) == 'tmce' ){
                            var id = mce.find( 'textarea' ).attr( 'id' );
                            tinymce.execCommand('mceRemoveEditor', false, id);
                            tinymce.execCommand( 'mceAddEditor', false, id );
                        }
                        return true;
                    });
                }
                /**
                 * Reactive mce everytime node structure changes
                 * Which includes add widget, or re-position widget
                 **/
                function fixTinyMceSort(mce, mce_html){
                    if( !mce.children().length ){
                        //Add widget
                        mce.html( mce_html );
                        mce.removeAttr( 'data-mce');
                        var id = mce.find( 'textarea' ).attr( 'id' );
                        setTimeout(function(){
                            tinymce.execCommand('mceRemoveEditor', true, id);
                            var init = tinyMCEPreInit.mceInit[ id ] = tinymce.extend( {}, tinyMCEPreInit.mceInit[ getTemplateWidgetId( id ) ] );
                            for( i in init )
                                if( typeof init[i] == 'string' )
                                    init[i] = init[i].replace( getTemplateWidgetId( id ), id );
                                    
                            try { tinymce.init( init ); } catch(e){ console.log( e ); }
                            
                            /**
                             * Fix Quick tag
                             **/
                            if ( typeof(QTags) == 'function' ) {
                                mce.find( '.quicktags-toolbar' ).remove();
                                
                                var objQtSettings = jq.extend({}, tinyMCEPreInit.qtInit[ getTemplateWidgetId( id ) ] );
                                objQtSettings['id'] = id;
                                
                                jq( '[id="wp-' + id + '-wrap"]' ).unbind( 'onmousedown' );
                                jq( '[id="wp-' + id + '-wrap"]' ).bind( 'onmousedown', function(){
                                    wpActiveEditor = id;
                                });
                                
                                //Add settings with current widget id into QTags 
                                QTags(objQtSettings);
                                //Re-init the QTags
                                QTags._buttonsInit();
                                
                                switchEditors.switchto( mce.find( '.wp-switch-editor.switch-' + ( getUserSetting( 'editor' ) == 'html' ? 'html' : 'tmce' ) )[ 0 ] );
                            }
                            
                            fixTinyMceSubmit( mce );
                        }, 50 );
                    }else{
                        //Re-posistion widget
                        var id = mce.find( 'textarea' ).attr( 'id' );
                        tinymce.execCommand('mceRemoveEditor', false, id);
                        tinymce.execCommand( 'mceAddEditor', false, id );
                    }
                }
                
                jq( '.widget-mce' ).each(function(){
                    var mce = jq(this);
                    
                    if( mce.closest('#widgets-left ').length ){
                        /**
                         * Remove all tinymce behavior from default widget template
                         * Avoid any js trouble
                         * Store html as settings as html attribute [data-mce]
                         * restore to widget when add widget
                         **/
                        var id = mce.find( 'textarea' ).attr( 'id' );
                        tinymce.execCommand('mceRemoveEditor', true, id);
                        mce.attr( 'data-mce', mce.html() );
                        mce.empty();
                    }else{
                        fixTinyMceSubmit( mce );
                    }
                } );
                
                jq( 'div.widgets-sortables' ).bind( 'sortstop', function(event,ui){
                    setTimeout(function(){
                        var mce = jq(ui.item).find( '.widget-mce' );
                        if( mce.length ){
                            fixTinyMceSort(mce, mce.attr( 'data-mce' ));
                        }
                    }, 50 );
                });
            });
        })(jQuery);
        //]]>
        </script>
        <?php
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update($new_instance , $old_instance ) {
        $instance = array();
        $instance['html'] = ( !empty( $new_instance['html'] ) ) ? $new_instance['html'] : '';

        return $new_instance;
    }
}
