<div id="div__<?php echo $strSectionId;?>__<?php echo $intCurrentIndex;?>" class="textarea_field">
    <p class="description"><?php echo $strDesc;?></p>
    <?php wp_editor( apply_filters('the_content', $meta_data[$intCurrentIndex]), sprintf( '__%s__%s__value', $strSectionId, $intCurrentIndex ), array( 'textarea_name' => sprintf( '%s[%s]', $strSectionName, $intCurrentIndex ) ) ); ?>
</div>
<script type="text/javascript">
    /*<![CDATA[*/
    (function(jq){
        var sectionIdInit = '<?php echo $strSectionId;?>';
        var intCurrentIndexInit = <?php echo $intCurrentIndex;?>;
        var sectionNameInit = '<?php echo $strSectionName;?>';
        var mainContainerInit = jq('#div__' + sectionIdInit + '__' + intCurrentIndexInit);
        var idBase = '__' + sectionIdInit + '__' + intCurrentIndexInit;
        var pattern = new RegExp( idBase + '(_|$)?', 'g' );
        var namePattern = sectionNameInit + '[' + intCurrentIndexInit + ']';
        var dataMce;
        if( typeof tinymce != 'undefined' ){
            tinymce.execCommand('mceRemoveEditor', true, mainContainerInit.find( 'textarea' ).attr( 'id' ) );
            dataMce = mainContainerInit.html();
            tinymce.init( tinyMCEPreInit.mceInit[ mainContainerInit.find( 'textarea' ).attr( 'id' ) ] );
        }else{
            dataMce = mainContainerInit.html();
        }
        
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
        function fixTinyMceSort(mce, mce_html) {
            if (!mce.children().length) {
                //Add widget
                mce.html(mce_html);
                var id = mce.find('textarea').attr('id');
                setTimeout(function() {
                    tinymce.execCommand('mceRemoveEditor', true, id);
                    var init = tinyMCEPreInit.mceInit[ id ] = tinymce.extend({}, tinyMCEPreInit.mceInit[ '__' + sectionIdInit + '__' + intCurrentIndexInit + '__value' ]);
                    for( i in init )
                        if( typeof init[i] == 'string' )
                            init[i] = init[i].replace( '__' + sectionIdInit + '__' + intCurrentIndexInit + '__value', id );
                    
                    try {
                        tinymce.init(init);
                    } catch (e) {
                        console.log(e);
                    }

                    /**
                     * Fix Quick tag
                     **/
                    if (typeof (QTags) == 'function') {
                        mce.find('.quicktags-toolbar').remove();
                        
                        var objQtSettings = jq.extend({}, tinyMCEPreInit.qtInit[ '__' + sectionIdInit + '__' + intCurrentIndexInit + '__value' ]);
                        objQtSettings['id'] = id;
                        
                        jq('[id="wp-' + id + '-wrap"]').unbind('onmousedown');
                        jq('[id="wp-' + id + '-wrap"]').bind('onmousedown', function() {
                            wpActiveEditor = id;
                        });
                        
                        //Add settings with current widget id into QTags 
                        QTags(objQtSettings);
                        //Re-init the QTags
                        QTags._buttonsInit();

                        switchEditors.switchto(mce.find('.wp-switch-editor.switch-' + (getUserSetting('editor') == 'html' ? 'html' : 'tmce'))[ 0 ]);
                    }
                    
                    fixTinyMceSubmit(mce);
                }, 50);
            } else {
                //Re-posistion widget
                var id = mce.find('textarea').attr('id');
                tinymce.execCommand('mceRemoveEditor', false, id);
                tinymce.execCommand('mceAddEditor', false, id);
            }
        }
        
        function metaTool(){
            var __mainContainer, __sectionId, __intCurrentIndex;
            this.init = function(mainContainer, sectionId, intCurrentIndex, sectionName){
                __mainContainer = mainContainer;
                __sectionId = sectionId;
                __intCurrentIndex = intCurrentIndex;
                __sectionName = sectionName;
                
                setTimeout(function(){
                    if( __intCurrentIndex == intCurrentIndexInit ) {
                        //__mainContainer.empty();
                        //fixTinyMceSort( __mainContainer, dataMce );
                    }else{
                        fixTinyMceSort( __mainContainer, dataMce.replace( pattern, '__' + __sectionId + '__' + intCurrentIndex + '$1' ).replace( namePattern, __sectionName + '[' + intCurrentIndex + ']' ) );
                    }
                }, 1);
                                
                __mainContainer.data('duplication', function(target, intNewCurrentIndex){
                    duplication(target, intNewCurrentIndex);
                });
            }
            
            function duplication(target, intNewCurrentIndex){
                var cloned = __mainContainer.clone();
                
                cloned.attr( 'id', cloned.attr( 'id').replace( pattern, '__' + __sectionId + '__' + intNewCurrentIndex + '$1' ) );
                cloned.empty();
                
                cloned.ready(function(){
                    cloned.data('objMetaTool', new metaTool());
                    cloned.data('objMetaTool').init(cloned, __sectionId, intNewCurrentIndex, __sectionName);
                });
                jq(target).append(cloned);
            }
        }
        
        jq(document).ready(function(){
            var objMetaTool = new metaTool();
            objMetaTool.init(mainContainerInit, sectionIdInit, intCurrentIndexInit, sectionNameInit);
        });
    })(jQuery);
    /*]]>*/
</script>
