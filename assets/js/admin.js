jQuery(document).ready(function($){
    function handleForm(selector, action){
        var spinnerHideTimer;
        $(selector).on('submit', function(e){
            e.preventDefault();
            var data = $(this).serialize();
            var $spinner = $('#sd-spinner');
            var $feedback = $('#sd-feedback');
            if ($feedback.length) {
                $feedback.removeClass('is-visible').text('');
            }
            if (spinnerHideTimer) {
                clearTimeout(spinnerHideTimer);
            }
            $spinner.addClass('is-active');
            $.post(sdAjax.ajaxurl, data + '&action=' + action + '&_ajax_nonce=' + sdAjax.nonce)
                .done(function(response){
                    if ($feedback.length && response && response.data) {
                        var message = response.data.message || response.data.error;
                        if (message) {
                            $feedback.text(message).addClass('is-visible');
                        }
                    }
                })
                .fail(function(){
                    if ($feedback.length && sdAdmin.error) {
                        $feedback.text(sdAdmin.error).addClass('is-visible');
                    }
                })
                .always(function(){
                    spinnerHideTimer = setTimeout(function(){
                        $spinner.removeClass('is-active');
                    }, 150);
                });
        });
    }
    handleForm('#sd-create-form','sd_save_main_entity');
    handleForm('#sd-general-settings-form','sd_save_main_entity');

    function formatString(template){
        if (typeof template !== 'string') {
            return '';
        }

        var args = Array.prototype.slice.call(arguments, 1);
        var usedIndexes = {};
        var result = template.replace(/%(\d+)\$s/g, function(match, number){
            var index = parseInt(number, 10) - 1;

            if (typeof args[index] === 'undefined') {
                usedIndexes[index] = true;
                return '';
            }

            usedIndexes[index] = true;
            return args[index];
        });

        var sequentialIndex = 0;

        return result.replace(/%s/g, function(){
            while (usedIndexes[sequentialIndex]) {
                sequentialIndex++;
            }

            var value = typeof args[sequentialIndex] !== 'undefined' ? args[sequentialIndex] : '';
            usedIndexes[sequentialIndex] = true;
            sequentialIndex++;
            return value;
        });
    }

    $(document).on('click','.sd-upload',function(e){
        e.preventDefault();
        var target=$(this).data('target');
        var frame=wp.media({title:sdAdmin.mediaTitle,button:{text:sdAdmin.mediaButton},multiple:false});
        frame.on('select',function(){
            var attachment=frame.state().get('selection').first().toJSON();
            $(target).val(attachment.id);
            $(target+'-preview').html('<img src="'+attachment.url+'" style="max-width:100px;height:auto;" />');
        });
        frame.open();
    });

    if($('#sd-entity-list').length){
        var $entityTableBody = $('#sd-entity-list');
        var perPage = parseInt($entityTableBody.data('per-page'), 10) || 20;
        var columnCount = parseInt($entityTableBody.data('column-count'), 10) || 6;
        var $pagination = $('#sd-entity-pagination');
        var $paginationContainer = $pagination.closest('.tablenav');
        var $entityFeedback = $('#sd-entity-feedback');
        var fieldMap = sdAdmin.fieldMap || {};
        var entityFields = Array.isArray(sdAdmin.entityFields) ? sdAdmin.entityFields : [];
        var tableColumns = Array.isArray(sdAdmin.tableColumns) ? sdAdmin.tableColumns : [];
        var pendingFeedbackMessage = '';
        var currentPage = 1;
        var emptyValue = '—';

        if ($entityFeedback.length){
            $entityFeedback.hide().removeClass('is-visible');
        }

        if ($paginationContainer.length){
            $paginationContainer.hide();
        }

        function clearFeedback(){
            if ($entityFeedback.length){
                $entityFeedback.text('').hide().removeClass('is-visible');
            }
        }

        function showFeedback(message){
            if (!$entityFeedback.length){
                return;
            }

            if (message){
                $entityFeedback.text(message).show().addClass('is-visible');
            } else {
                clearFeedback();
            }
        }

        function getFieldConfig(name){
            if (!name){
                return null;
            }

            if (Object.prototype.hasOwnProperty.call(fieldMap, name)){
                return fieldMap[name];
            }

            for (var i = 0; i < entityFields.length; i++){
                if (entityFields[i] && entityFields[i].name === name){
                    return entityFields[i];
                }
            }

            return null;
        }

        function getFieldLabel(name){
            var config = getFieldConfig(name);

            if (config && config.label){
                return config.label;
            }

            return '';
        }

        function formatValue(value){
            if (value === null || typeof value === 'undefined' || value === ''){
                return emptyValue;
            }

            return String(value);
        }

        function formatFieldDisplay(name, value){
            var config = getFieldConfig(name);
            var stringValue = value === null || typeof value === 'undefined' ? '' : value;

            if (!config){
                return stringValue === '' ? emptyValue : String(stringValue);
            }

            switch (config.type){
                case 'select':
                    var options = config.options || {};

                    if (Object.prototype.hasOwnProperty.call(options, stringValue)){
                        return options[stringValue] || emptyValue;
                    }

                    return stringValue === '' ? emptyValue : String(stringValue);
                case 'items':
                    var items = parseItemsValue(stringValue);
                    return items.length ? items.join(', ') : emptyValue;
                case 'editor':
                    if (!stringValue){
                        return emptyValue;
                    }

                    return $('<div/>').html(stringValue).text() || emptyValue;
                case 'textarea':
                    return stringValue === '' ? emptyValue : String(stringValue);
                default:
                    if (name === 'featured_image_id'){
                        return stringValue ? (sdAdmin.mediaButton || sdAdmin.mediaTitle || 'Select Image') : emptyValue;
                    }

                    return stringValue === '' ? emptyValue : String(stringValue);
            }
        }

        if (!tableColumns.length){
            tableColumns = [
                { key: 'name', type: 'title', label: getFieldLabel('name') || 'Listing Name' },
                { key: 'category', type: 'meta', label: getFieldLabel('category') || 'Category' },
                { key: 'industry_vertical', type: 'meta', label: getFieldLabel('industry_vertical') || 'Industry / Vertical' },
                { key: 'service_model', type: 'meta', label: getFieldLabel('service_model') || 'Service Model' },
                { key: 'website_url', type: 'meta', label: getFieldLabel('website_url') || 'Website' },
                { key: 'actions', type: 'actions', label: sdAdmin.editAction || 'Edit' }
            ];
        }

        var hasTitleColumn = tableColumns.some(function(column){
            return column && column.type === 'title';
        });

        if (!hasTitleColumn){
            tableColumns.unshift({ key: 'name', type: 'title', label: getFieldLabel('name') || 'Listing Name' });
        }

        var hasActionsColumn = tableColumns.some(function(column){
            return column && column.type === 'actions';
        });

        if (!hasActionsColumn){
            tableColumns.push({ key: 'actions', type: 'actions', label: sdAdmin.editAction || 'Edit' });
        }

        function getFieldValue(entity, key){
            if (!entity || typeof entity !== 'object'){
                return '';
            }

            if (Object.prototype.hasOwnProperty.call(entity, key) && entity[key] !== null && typeof entity[key] !== 'undefined'){
                return entity[key];
            }

            return '';
        }

        function parseItemsValue(value){
            if (Array.isArray(value)){
                return value;
            }

            if (!value || value === ''){
                return [];
            }

            if (typeof value === 'string'){
                try {
                    var parsed = JSON.parse(value);

                    if (Array.isArray(parsed)){
                        return parsed;
                    }
                } catch (err) {
                    // Ignore JSON parse errors and fall back to splitting.
                }

                return value.split(/\r?\n/).filter(function(item){
                    return item !== '';
                });
            }

            return [];
        }

        function appendFieldInput($container, field, value, entity, entityId){
            var type = field.type || 'text';
            var fieldName = field.name;
            var stringValue = value === null || typeof value === 'undefined' ? '' : value;
            var baseId = fieldName + '-' + entityId;
            var addAnotherLabel = sdAdmin.addAnotherItem || '+ Add Another Item';

            switch (type){
                case 'select':
                    var options = field.options || {};
                    var $select = $('<select/>', { name: fieldName });
                    Object.keys(options).forEach(function(optionValue){
                        var optionLabel = options[optionValue];
                        var $option = $('<option/>', { value: optionValue, text: optionLabel });

                        if (optionValue === ''){
                            $option.prop('disabled', true);

                            if (!stringValue){
                                $option.prop('selected', true);
                            }
                        } else if (String(stringValue) === String(optionValue)){
                            $option.prop('selected', true);
                        }

                        $select.append($option);
                    });
                    $container.append($select);
                    break;
                case 'state':
                    var states = Array.isArray(field.options) ? field.options : [];
                    var $stateSelect = $('<select/>', { name: fieldName });
                    var placeholderOption = $('<option/>', {
                        value: '',
                        text: sdAdmin.makeSelection || ''
                    }).prop('disabled', true);

                    if (!stringValue){
                        placeholderOption.prop('selected', true);
                    }

                    $stateSelect.append(placeholderOption);

                    states.forEach(function(stateValue){
                        var $stateOption = $('<option/>', { value: stateValue, text: stateValue });

                        if (String(stateValue) === String(stringValue)){
                            $stateOption.prop('selected', true);
                        }

                        $stateSelect.append($stateOption);
                    });

                    $container.append($stateSelect);
                    break;
                case 'radio':
                    var radioOptions = field.options || {};

                    Object.keys(radioOptions).forEach(function(optionValue){
                        var option = radioOptions[optionValue] || {};
                        var $label = $('<label/>', { 'class': 'sd-radio-option' });
                        var $input = $('<input/>', {
                            type: 'radio',
                            name: fieldName,
                            value: optionValue
                        });

                        if (String(optionValue) === String(stringValue)){
                            $input.prop('checked', true);
                        }

                        $label.append($input);
                        $label.append(' ');
                        $label.append($('<span/>', {
                            'class': 'sd-tooltip-icon dashicons dashicons-editor-help',
                            'data-tooltip': option.tooltip || ''
                        }));
                        $label.append(document.createTextNode(option.label || ''));
                        $container.append($label);
                    });
                    break;
                case 'opt_in':
                    var optInOptions = Array.isArray(field.options) ? field.options : [];
                    var $fieldset = $('<fieldset/>');

                    optInOptions.forEach(function(option){
                        var optionName = option.name || '';
                        var isChecked = entity && (entity[optionName] === '1' || entity[optionName] === 1 || entity[optionName] === true);
                        var $label = $('<label/>', { 'class': 'sd-opt-in-option' });
                        var $input = $('<input/>', {
                            type: 'checkbox',
                            name: optionName,
                            value: '1'
                        });

                        if (isChecked){
                            $input.prop('checked', true);
                        }

                        $label.append($input);
                        $label.append(' ');
                        $label.append($('<span/>', {
                            'class': 'sd-tooltip-icon dashicons dashicons-editor-help',
                            'data-tooltip': option.tooltip || ''
                        }));
                        $label.append(document.createTextNode(option.label || ''));
                        $fieldset.append($label);
                    });

                    $container.append($fieldset);
                    break;
                case 'textarea':
                    var $textarea = $('<textarea/>', {
                        name: fieldName
                    }).val(stringValue);

                    if (field.attrs){
                        field.attrs.replace(/([\w-]+)="([^"]*)"/g, function(match, attrName, attrValue){
                            $textarea.attr(attrName, attrValue);
                            return match;
                        });
                    }

                    if (!$textarea.attr('rows')){
                        $textarea.attr('rows', 4);
                    }

                    $container.append($textarea);
                    break;
                case 'items':
                    var containerId = baseId + '-container';
                    var $itemsContainer = $('<div/>', {
                        id: containerId,
                        'class': 'sd-items-container',
                        'data-field': fieldName
                    });
                    var items = parseItemsValue(stringValue);

                    if (!items.length){
                        items = [''];
                    }

                    items.forEach(function(itemValue, index){
                        var $row = $('<div/>', {
                            'class': 'sd-item-row',
                            style: 'margin-bottom:8px; display:flex; align-items:center;'
                        });
                        var placeholderText = sdAdmin.itemPlaceholder ? formatString(sdAdmin.itemPlaceholder, index + 1) : '';
                        var $input = $('<input/>', {
                            type: 'text',
                            name: fieldName + '[]',
                            'class': 'regular-text sd-item-field',
                            placeholder: placeholderText,
                            value: itemValue
                        });
                        $row.append($input);
                        var $removeButton = $('<button/>', {
                            type: 'button',
                            'class': 'sd-delete-item',
                            'aria-label': 'Remove',
                            style: 'background:none;border:none;cursor:pointer;margin-left:8px;'
                        }).append($('<span/>', { 'class': 'dashicons dashicons-no-alt' }));
                        $row.append($removeButton);
                        $itemsContainer.append($row);
                    });

                    $container.append($itemsContainer);

                    var $addButton = $('<button/>', {
                        type: 'button',
                        'class': 'button sd-add-item',
                        'data-target': '#' + containerId,
                        'data-field-name': fieldName,
                        style: 'margin-top:8px;'
                    }).text(addAnotherLabel);

                    $container.append($addButton);
                    break;
                case 'image':
                    var inputId = baseId;
                    var $hidden = $('<input/>', {
                        type: 'hidden',
                        name: fieldName,
                        id: inputId,
                        value: stringValue
                    });
                    var $button = $('<button/>', {
                        type: 'button',
                        'class': 'button sd-upload',
                        'data-target': '#' + inputId
                    }).text(sdAdmin.mediaTitle);
                    var previewId = inputId + '-preview';
                    var $preview = $('<div/>', {
                        id: previewId,
                        style: 'margin-top:10px;'
                    });
                    var urlKey = fieldName + '_url';

                    if (entity && entity[urlKey]){
                        $preview.append($('<img/>', {
                            src: entity[urlKey],
                            alt: field.label || '',
                            style: 'max-width:100px;height:auto;'
                        }));
                    }

                    $container.append($hidden, $button, $preview);
                    break;
                case 'editor':
                    var editorId = baseId;
                    var $textarea = $('<textarea/>', {
                        name: fieldName,
                        id: editorId,
                        'class': 'sd-editor-field'
                    }).val(stringValue);
                    $container.append($textarea);
                    break;
                default:
                    var $inputField = $('<input/>', {
                        type: type,
                        name: fieldName
                    }).val(stringValue);

                    if (field.attrs){
                        field.attrs.replace(/([\w-]+)="([^"]*)"/g, function(match, attrName, attrValue){
                            $inputField.attr(attrName, attrValue);
                            return match;
                        });
                    }

                    $container.append($inputField);
                    break;
            }
        }

        function buildEntityForm(entity){
            var entityId = entity && entity.id ? entity.id : 0;
            var $form = $('<form/>', {
                'class': 'sd-entity-edit-form',
                'data-entity-id': entityId
            });
            var $flex = $('<div/>', { 'class': 'sd-flex-form' });

            $form.append($('<input/>', { type: 'hidden', name: 'id', value: entityId }));

            entityFields.forEach(function(field){
                if (!field || !field.name){
                    return;
                }

                var value = getFieldValue(entity, field.name);
                var fieldClasses = 'sd-field';

                if (field.fullWidth){
                    fieldClasses += ' sd-field-full';
                }

                var $fieldWrapper = $('<div/>', { 'class': fieldClasses });
                var $label = $('<label/>');

                $label.append($('<span/>', {
                    'class': 'sd-tooltip-icon dashicons dashicons-editor-help',
                    'data-tooltip': field.tooltip || ''
                }));
                $label.append(document.createTextNode(field.label || ''));
                $fieldWrapper.append($label);
                appendFieldInput($fieldWrapper, field, value, entity, entityId);
                $flex.append($fieldWrapper);
            });

            $form.append($flex);

            var $actions = $('<p/>', { 'class': 'sd-entity__actions submit' });
            var $saveButton = $('<button/>', {
                type: 'submit',
                'class': 'button button-primary sd-entity-save'
            }).text(sdAdmin.saveChanges || 'Save Changes');
            var $deleteButton = $('<button/>', {
                type: 'button',
                'class': 'button button-secondary sd-delete',
                'data-id': entityId
            }).text(sdAdmin.delete);
            var $feedbackArea = $('<span/>', { 'class': 'sd-feedback-area sd-feedback-area--inline' });
            var $spinner = $('<span/>', { 'class': 'spinner sd-entity-spinner', 'aria-hidden': 'true' });
            var $feedback = $('<span/>', { 'class': 'sd-entity-feedback', 'role': 'status', 'aria-live': 'polite' });
            $feedbackArea.append($spinner).append($feedback);
            $actions.append($saveButton).append(' ').append($deleteButton).append($feedbackArea);
            $form.append($actions);

            return $form;
        }

        function updatePagination(total, totalPages, page){
            if (!$pagination.length){
                return;
            }

            if (!total || total <= 0){
                $pagination.empty();

                if ($paginationContainer.length){
                    $paginationContainer.hide();
                }

                return;
            }

            var totalPagesSafe = totalPages && totalPages > 0 ? totalPages : 1;
            var pageSafe = page && page > 0 ? page : 1;
            var html = '<span class="displaying-num">' + formatString(sdAdmin.totalRecords, total) + '</span>';

            if (totalPagesSafe > 1){
                html += '<span class="pagination-links">';

                if (pageSafe > 1){
                    html += '<a class="first-page button sd-entity-page" href="#" data-page="1"><span class="screen-reader-text">' + sdAdmin.firstPage + '</span><span aria-hidden="true">&laquo;</span></a>';
                    html += '<a class="prev-page button sd-entity-page" href="#" data-page="' + (pageSafe - 1) + '"><span class="screen-reader-text">' + sdAdmin.prevPage + '</span><span aria-hidden="true">&lsaquo;</span></a>';
                } else {
                    html += '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
                    html += '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
                }

                html += '<span class="tablenav-paging-text">' + formatString(sdAdmin.pageOf, pageSafe, totalPagesSafe) + '</span>';

                if (pageSafe < totalPagesSafe){
                    html += '<a class="next-page button sd-entity-page" href="#" data-page="' + (pageSafe + 1) + '"><span class="screen-reader-text">' + sdAdmin.nextPage + '</span><span aria-hidden="true">&rsaquo;</span></a>';
                    html += '<a class="last-page button sd-entity-page" href="#" data-page="' + totalPagesSafe + '"><span class="screen-reader-text">' + sdAdmin.lastPage + '</span><span aria-hidden="true">&raquo;</span></a>';
                } else {
                    html += '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
                    html += '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
                }

                html += '</span>';
            } else {
                html += '<span class="tablenav-paging-text">' + formatString(sdAdmin.pageOf, pageSafe, totalPagesSafe) + '</span>';
            }

            $pagination.html(html);

            if ($paginationContainer.length){
                $paginationContainer.show();
            }
        }

        function renderEntities(data){
            var entities = data && Array.isArray(data.entities) ? data.entities : [];
            currentPage = data && data.page ? data.page : 1;
            var total = data && typeof data.total !== 'undefined' ? data.total : 0;
            var totalPages = data && data.total_pages ? data.total_pages : 1;

            $entityTableBody.empty();

            if (!entities.length){
                var $emptyRow = $('<tr class="no-items"></tr>');
                var $emptyCell = $('<td/>').attr('colspan', columnCount).text(sdAdmin.none);
                $emptyRow.append($emptyCell);
                $entityTableBody.append($emptyRow);
                updatePagination(total, totalPages, currentPage);
                return;
            }

            entities.forEach(function(entity){
                var entityId = entity.id || 0;
                var headerId = 'sd-entity-' + entityId + '-header';
                var panelId = 'sd-entity-' + entityId + '-panel';

                var $summaryRow = $('<tr/>', {
                    id: headerId,
                    'class': 'sd-accordion__summary-row',
                    tabindex: 0,
                    role: 'button',
                    'aria-expanded': 'false',
                    'aria-controls': panelId
                });

                tableColumns.forEach(function(column){
                    if (!column){
                        return;
                    }

                    var columnKey = column.key || '';
                    var columnType = column.type || 'meta';
                    var labelText = column.label || getFieldLabel(columnKey);
                    var cellValue = getFieldValue(entity, columnKey);

                    if (columnType === 'actions'){
                        var $actionsCell = $('<td/>', {'class': 'sd-accordion__cell sd-accordion__cell--actions'});
                        var $editText = $('<span/>', {'class': 'sd-accordion__action-link', 'aria-hidden': 'true'}).text(sdAdmin.editAction);
                        var $icon = $('<span/>', {'class': 'dashicons dashicons-arrow-down-alt2 sd-accordion__icon', 'aria-hidden': 'true'});
                        var $srText = $('<span/>', {'class': 'screen-reader-text'}).text(sdAdmin.toggleDetails);
                        $actionsCell.append($editText);
                        $actionsCell.append($icon).append($srText);
                        $summaryRow.append($actionsCell);
                        return;
                    }

                    if (columnType === 'title'){
                        var $titleCell = $('<td/>', {'class': 'sd-accordion__cell sd-accordion__cell--title'});
                        var displayTitle = formatFieldDisplay(columnKey, cellValue);
                        $titleCell.append($('<span/>', {'class': 'sd-accordion__title-text'}).text(displayTitle));
                        $summaryRow.append($titleCell);
                        return;
                    }

                    var displayValue = formatFieldDisplay(columnKey, cellValue);
                    var $metaCell = $('<td/>', {'class': 'sd-accordion__cell sd-accordion__cell--meta'});
                    var $metaText = $('<span/>', {'class': 'sd-accordion__meta-text'});

                    if (labelText){
                        $metaText.append($('<span/>', {'class': 'sd-accordion__meta-label'}).text(labelText + ':'));
                        $metaText.append(' ');
                    }

                    $metaText.append($('<span/>', {'class': 'sd-accordion__meta-value'}).text(displayValue));
                    $metaCell.append($metaText);
                    $summaryRow.append($metaCell);
                });
                $entityTableBody.append($summaryRow);

                var $panelRow = $('<tr/>', {
                    id: panelId,
                    'class': 'sd-accordion__panel-row',
                    role: 'region',
                    'aria-labelledby': headerId,
                    'aria-hidden': 'true'
                }).hide();

                var $panelCell = $('<td/>').attr('colspan', columnCount);
                var $panel = $('<div/>', {'class': 'sd-accordion__panel'}).hide();
                var $form = buildEntityForm(entity);

                $panel.append($form);
                $panelCell.append($panel);
                $panelRow.append($panelCell);
                $entityTableBody.append($panelRow);
            });

            updatePagination(total, totalPages, currentPage);

            if (typeof wp !== 'undefined' && wp.editor && typeof wp.editor.initialize === 'function'){
                $entityTableBody.find('.sd-editor-field').each(function(){
                    var editorId = $(this).attr('id');

                    if (!editorId){
                        return;
                    }

                    if (typeof wp.editor.remove === 'function'){
                        try {
                            wp.editor.remove(editorId);
                        } catch (removeError) {
                            // Ignore errors when removing editors that were not initialized yet.
                        }
                    }

                    var editorSettings = $.extend(true, {}, sdAdmin.editorSettings || {});

                    if (typeof editorSettings.tinymce === 'undefined'){
                        editorSettings.tinymce = true;
                    }

                    if (typeof editorSettings.quicktags === 'undefined'){
                        editorSettings.quicktags = true;
                    }

                    wp.editor.initialize(editorId, editorSettings);
                });
            }
        }

        function fetchEntities(page){
            var targetPage = page || 1;
            clearFeedback();

            $.post(sdAjax.ajaxurl, {
                action: 'sd_read_main_entity',
                _ajax_nonce: sdAjax.nonce,
                page: targetPage,
                per_page: perPage
            })
                .done(function(response){
                    if (response && response.success && response.data){
                        renderEntities(response.data);
                        if (pendingFeedbackMessage){
                            showFeedback(pendingFeedbackMessage);
                            pendingFeedbackMessage = '';
                        }
                    } else {
                        showFeedback(sdAdmin.loadError || sdAdmin.error);
                    }
                })
                .fail(function(){
                    showFeedback(sdAdmin.loadError || sdAdmin.error);
                    pendingFeedbackMessage = '';
                });
        }

        fetchEntities(1);

        if ($pagination.length){
            $pagination.on('click', '.sd-entity-page', function(e){
                e.preventDefault();
                var targetPage = parseInt($(this).data('page'), 10);

                if (!targetPage || targetPage === currentPage){
                    return;
                }

                fetchEntities(targetPage);
            });
        }

        $entityTableBody.on('submit', '.sd-entity-edit-form', function(e){
            e.preventDefault();
            e.stopPropagation();

            var $form = $(this);
            var $spinner = $form.find('.sd-entity-spinner');
            var $feedback = $form.find('.sd-entity-feedback');

            if ($spinner.length){
                $spinner.addClass('is-active');
            }

            if ($feedback.length){
                $feedback.removeClass('is-visible').text('');
            }

            var formData = $form.serialize();
            formData += '&action=sd_save_main_entity&_ajax_nonce=' + encodeURIComponent(sdAjax.nonce);

            $.post(sdAjax.ajaxurl, formData)
                .done(function(resp){
                    if (resp && resp.success){
                        pendingFeedbackMessage = resp.data && resp.data.message ? resp.data.message : '';
                        fetchEntities(currentPage);
                    } else {
                        var message = resp && resp.data && resp.data.message ? resp.data.message : (sdAdmin.error || '');

                        if ($feedback.length && message){
                            $feedback.text(message).addClass('is-visible');
                        }
                    }
                })
                .fail(function(){
                    if ($feedback.length && sdAdmin.error){
                        $feedback.text(sdAdmin.error).addClass('is-visible');
                    }
                })
                .always(function(){
                    if ($spinner.length){
                        setTimeout(function(){
                            $spinner.removeClass('is-active');
                        }, 150);
                    }
                });
        });

        $entityTableBody.on('click', '.sd-delete', function(e){
            e.preventDefault();
            e.stopPropagation();
            var id = $(this).data('id');

            if (!id){
                return;
            }

            clearFeedback();

            $.post(sdAjax.ajaxurl, {
                action: 'sd_delete_main_entity',
                id: id,
                _ajax_nonce: sdAjax.nonce
            })
                .done(function(resp){
                    if (resp && resp.success){
                        pendingFeedbackMessage = resp.data && resp.data.message ? resp.data.message : '';
                        fetchEntities(currentPage);
                    } else {
                        showFeedback(sdAdmin.error);
                    }
                })
                .fail(function(){
                    showFeedback(sdAdmin.error);
                });
        });
    }

    $('.sd-accordion').on('click','.item-header',function(){
        $(this).next('.item-content').slideToggle();
        $(this).parent().toggleClass('open');
    });

    function initAccordionGroups(){
        $('[data-sd-accordion-group]').each(function(){
            var $group = $(this);

            if ($group.data('sdAccordionInitialized')) {
                return;
            }

            $group.data('sdAccordionInitialized', true);

            function closeRow($summary, $panelRow){
                if (!$summary.length || !$panelRow.length) {
                    return;
                }

                $summary.removeClass('is-open').attr('aria-expanded', 'false');

                var $panel = $panelRow.find('.sd-accordion__panel');

                $panel.stop(true, true).slideUp(200, function(){
                    $panelRow.hide();
                });

                $panelRow.attr('aria-hidden', 'true');
            }

            function toggleRow($summary){
                var panelId = $summary.attr('aria-controls');
                var $panelRow = $('#' + panelId);

                if (!$panelRow.length) {
                    return;
                }

                if ($summary.hasClass('is-open')) {
                    closeRow($summary, $panelRow);
                    return;
                }

                $group.find('.sd-accordion__summary-row.is-open').each(function(){
                    var $openSummary = $(this);
                    var openPanelId = $openSummary.attr('aria-controls');
                    var $openPanelRow = $('#' + openPanelId);

                    closeRow($openSummary, $openPanelRow);
                });

                $summary.addClass('is-open').attr('aria-expanded', 'true');
                $panelRow.show().attr('aria-hidden', 'false');
                $panelRow.find('.sd-accordion__panel').stop(true, true).slideDown(200);
            }

            $group.find('.sd-accordion__summary-row').each(function(){
                var $summary = $(this);
                var panelId = $summary.attr('aria-controls');
                var $panelRow = $('#' + panelId);

                if (!$panelRow.length) {
                    return;
                }

                $summary.removeClass('is-open').attr('aria-expanded', 'false');
                $panelRow.hide().attr('aria-hidden', 'true');
                $panelRow.find('.sd-accordion__panel').hide();
            });

            $group.on('click', '.sd-accordion__summary-row', function(e){
                if ($(e.target).closest('a, button, input, textarea, select, label').length) {
                    return;
                }

                toggleRow($(this));
            });

            $group.on('keydown', '.sd-accordion__summary-row', function(e){
                var key = e.key || e.keyCode;

                if (key === 'Enter' || key === ' ' || key === 13 || key === 32) {
                    e.preventDefault();
                    toggleRow($(this));
                }
            });
        });
    }

    initAccordionGroups();

    $(document).on('click', '.sd-add-item', function(e){
        e.preventDefault();
        e.stopPropagation();

        var $button = $(this);
        var targetSelector = $button.data('target');
        var $container = targetSelector ? $(targetSelector) : $button.closest('.sd-field').find('.sd-items-container').first();

        if (!$container.length){
            return;
        }

        var fieldName = $button.data('field-name') || $container.data('field') || '';

        if (!fieldName){
            fieldName = 'items_field';
        }
        var count = $container.find('.sd-item-row').length + 1;
        var placeholderText = sdAdmin.itemPlaceholder ? formatString(sdAdmin.itemPlaceholder, count) : '';
        var $row = $('<div/>', {
            'class': 'sd-item-row',
            style: 'margin-bottom:8px; display:flex; align-items:center;'
        });
        var $input = $('<input/>', {
            type: 'text',
            name: fieldName + '[]',
            'class': 'regular-text sd-item-field',
            placeholder: placeholderText
        });
        var $removeButton = $('<button/>', {
            type: 'button',
            'class': 'sd-delete-item',
            'aria-label': 'Remove',
            style: 'background:none;border:none;cursor:pointer;margin-left:8px;'
        }).append($('<span/>', { 'class': 'dashicons dashicons-no-alt' }));

        $row.append($input).append($removeButton);
        $container.append($row);
    });

    $(document).on('click', '.sd-delete-item', function(e){
        e.preventDefault();
        e.stopPropagation();

        var $row = $(this).closest('.sd-item-row');
        var $container = $row.parent('.sd-items-container');
        $row.remove();

        if ($container && $container.length && sdAdmin.itemPlaceholder){
            $container.find('.sd-item-row').each(function(index){
                var $input = $(this).find('.sd-item-field');

                if ($input.length){
                    $input.attr('placeholder', formatString(sdAdmin.itemPlaceholder, index + 1));
                }
            });
        }
    });
});
