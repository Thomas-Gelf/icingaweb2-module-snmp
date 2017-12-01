
(function(Icinga) {

    var Snmp = function(module) {
        this.module = module;

        this.initialize();

        this.module.icinga.logger.debug('Snmp module loaded');
    };

    Snmp.prototype = {
        initialize: function()
        {
            this.module.on('rendered', this.rendered);
            // this.module.on('change', '.mib-drop-zone', this.droppedFiles);
            // this.module.on('drop', '.mib-drop-zone', this.droppedFiles);
        },

        rendered: function (event) {
            $container = $(event.currentTarget);
            if (this.isAdvancedUpload()) {
                this.initializeFiles($container);
                $container.find('.mib-drop-zone').on('change', this.droppedFiles);
            } else {
                $container.find('.mib-drop-zone').remove();
            }
        },

        initializeFiles: function ($container) {
            var droppedFiles = false;
            var $dropZone = $container.find('.mib-drop-zone');
            var $form = $dropZone.closest('form');

            $dropZone.on('drag dragstart dragend dragover dragenter dragleave drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
            })
                .on('dragover dragenter', function() {
                    $dropZone.addClass('is-dragover');
                })
                .on('dragleave dragend drop', function() {
                    $dropZone.removeClass('is-dragover');
                })
                .on('drop', function(e) {
                    droppedFiles = e.originalEvent.dataTransfer.files;
                    //console.log(droppedFiles);
                    var $input = $form.find('input[type="file"]');
                    $input.prop('files', droppedFiles);
                    $form.submit();
                    return;
                    var ajaxData = new FormData($form.get(0));
/*
                    if (droppedFiles) {
                        $.each( droppedFiles, function(i, file) {
                            ajaxData.append( $input.attr('name'), file );
                        });
                    }
*/
                    $.ajax({
                        url: $form.attr('action'),
                        type: $form.attr('method'),
                        data: ajaxData,
                        dataType: 'json',
                        cache: false,
                        contentType: false,
                        processData: false,
                        complete: function() {
                            $form.removeClass('is-uploading');
                        },
                        success: function(data) {
                            $dropZone.addClass( data.success === true ? 'is-success' : 'is-error' );
                            if (!data.success) $errorMsg.text(data.error);
                        },
                        error: function() {
                            // Log the error, show an alert, whatever works for you
                        }
                    });

                });

        },

        isAdvancedUpload: function () {
            var div = document.createElement('div');
            return (('draggable' in div)
                || ('ondragstart' in div && 'ondrop' in div))
                && 'FormData' in window && 'FileReader' in window;
        },

        droppedFiles: function(event){
            console.log('Triggered');
            event.preventDefault();
            event.stopPropagation();
            var files = event.target.files;
            $('#drop').css('display', 'none');
            for(var i = 0, len = files.length; i < len; i++) {
                if(files[i].type === 'text/plain' || files[i].type === ''){
                    $.ajax({
                        type: "POST",
                        url: "uploader.php?id="+i,
                        contentType: "multipart/form-data",
                        headers: {
                            "X-File-Name" : files[i].name,
                            "X-File-Size" : files[i].size,
                            "X-File-Type" : files[i].type
                        }
                    });
                }else{
                    $('#info').append('Content type must be text/plain');
                }
            }
        }
    };

    Icinga.availableModules.snmp = Snmp;

}(Icinga));
