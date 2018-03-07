var wpsolr_ajax_timer;

/**
 * Remove an element from an array
 */
 
/*Array.prototype.remove = function (value) {
    if (this.indexOf(value) !== -1) {
        this.splice(this.indexOf(value), 1);
        return true;
    } else {
        return false;
    }
}*/

/**
 * Change the value of a url parameter, without reloading the page.
 */
function generateUrlParameters(url, current_parameters, is_remove_unused_parameters) {

    //alert(JSON.stringify(current_parameters));

    // jsurl library to manipulate parameters (https://github.com/Mikhus/jsurl)
    var url1 = new Url(url);
    var force_clear_url = false;

    /**
     * Set parameters not from wpsolr
     */
    jQuery.each(current_parameters, function (key, value) {
        if (key.substring(0, 'wpsolr_'.length) !== 'wpsolr_') {
            url1.query[key] = value;
        }
    });

    /**
     * Extract parameter query
     */
    var query = current_parameters[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_S];
    if (undefined !== query) {
        url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_S] = query || '';
        force_clear_url = true;
    }

    /**
     * Extract parameter query
     */
    var query = current_parameters[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_Q] || '';
    if (query !== '') {
        url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_Q] = query;
    } else if (is_remove_unused_parameters) {
        delete url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_Q];
    }

    /**
     *    Extract parameter fq (query field)
     *    We follow Wordpress convention for url parameters with multiple occurence: xxx[0..n]=
     *    (php is xxx[]=)
     */
    // First, remove all fq parameters
    for (var index = 0; ; index++) {
        if (undefined === url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ + '[' + index + ']']) {
            break;
        } else {
            delete url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ + '[' + index + ']'];
        }
    }
    if (!force_clear_url) {
        // 2nd, add parameters
        var query_fields = current_parameters[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ] || [];
        for (var index in query_fields) {
            url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ + '[' + index + ']'] = query_fields[index];
        }
    }

    /**
     * Extract parameter sort
     */
    var sort = current_parameters[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SORT] || '';
    if ((!force_clear_url) && (sort !== '')) {
        url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SORT] = sort;
    } else if (is_remove_unused_parameters) {
        delete url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SORT];
    }

    /**
     * Extract parameter page number
     */
    var paged = current_parameters[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_PAGE] || '';
    if (paged !== '') {
        url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_PAGE] = paged;
    } else if (is_remove_unused_parameters) {
        delete url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_PAGE];
    }


    // Remove old search parameter
    delete url1.query[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SEARCH];

    return '?' + url1.query.toString();
}

/**
 * History back/forward buttons
 */
window.addEventListener("popstate", function (e) {

    // Test to fix Safari trigger ob page load
    if (e.state) {
        call_ajax_search_timer(window.location.search, false, true);
    }
});

/**
 * Get the facets state (checked)
 * @returns {Array}
 */
function get_ui_facets_state() {

    // Add all selected facets to the state
    state = [];
    jQuery('.select_opt.checked').each(function () {
        // Retrieve current selection
        var facet_id = jQuery(this).attr('id');

        var facet_data = jQuery(this).data('wpsolr-facet-data');

        if ((facet_id !== 'wpsolr_remove_facets') && (undefined !== facet_data) && !facet_data.is_permalink) {
            // Do not add the remove facets facet to url parameters
            // Do not add the url parameter of a permalink (to prevent /red?wpsolr_fq[0]=color:red)

            var value = '';

            switch (facet_data.type) {
                case 'facet_type_range':
                    value = facet_data.id + ':' + facet_data.item_value;
                    break;

                default:
                    value = facet_data.id + ':' + facet_data.item_value;
                    break;

            }

            //console.log(facet_data.is_permalink + ' ' + value);
            state.push(value);
        }

    });


    jQuery('.select_opt.unchecked').each(function () {

        // Retrieve current selection. Remove the selected value.
        opts = jQuery(this).attr('id').split(':')[0] + ':';

        state.push(opts);

        //console.log('remove unchecked: ' + jQuery(this).attr('id').split(':')[0]);
    });


    return state;
}

/**
 * Return current stored values
 * @returns {{query: *, fq: *, sort: *, start: *}}
 */
function get_ui_selection() {

    var result = {};

    result[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_Q] = jQuery('#search_que').val() || '';
    result[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ] = get_ui_facets_state();
    result[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SORT] = jQuery('.select_field').val() || '';
    result[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_PAGE] = jQuery('#paginate').val() || '';

    //alert(JSON.stringify(result));

    return result;
}

function wpsolr_ajax_loading(container, action) {
    var loader_options = {
        //color: "rgba(0, 0, 0, 0.4)",
        //image: "img/custom_loading.gif",
        //maxSize: "80px",
        //minSize: "20px",
        //resizeInterval: 0,
        //size: "50%"
    };
    container.LoadingOverlay(action, loader_options);
}

function call_ajax_search_timer(selection_parameters, is_change_url, is_scroll_top_after) {

    // Mark the beginning of loading. Removed when facets are refreshed.
    jQuery('#res_facets').append('<!-- wpsolr loading -->');

    // Ajax, show loader
    if (wp_localize_script_autocomplete.data.is_ajax) {
        var current_results = jQuery(wp_localize_script_autocomplete.data.css_ajax_container_results).first();
        wpsolr_ajax_loading(current_results, 'show');
    }

    if ('' !== wp_localize_script_autocomplete.data.ajax_delay_ms) {
        // Delay
        if (undefined !== wpsolr_ajax_timer) {
            window.clearTimeout(wpsolr_ajax_timer);
        }

        wpsolr_ajax_timer = window.setTimeout(call_ajax_search, wp_localize_script_autocomplete.data.ajax_delay_ms, selection_parameters, is_change_url, is_scroll_top_after);
    } else {
        // No delay
        call_ajax_search(selection_parameters, is_change_url, is_scroll_top_after);
    }
}

function call_ajax_search(selection_parameters, is_change_url, is_scroll_top_after) {

    var url_parameters = selection_parameters;
    if ((selection_parameters instanceof Object) && (undefined === selection_parameters['url'])) {
        // Merge default parameters with active parameters
        var parameters = get_ui_selection();
        jQuery.extend(parameters, selection_parameters);

        //alert(JSON.stringify(parameters));

        // Update url with the current selection
        url_parameters = generateUrlParameters(window.location.href, parameters, true);
    }

    // Remove the pagination from the url, to start from page 1
    // xxx/2/ => xxx/
    if (!(selection_parameters instanceof Object) || (undefined === selection_parameters['url'])) {
        var url_base = window.location.href.split("?")[0];
        var url = url_base.replace(/\/page\/\d+/, '');
    } else {
        var url = selection_parameters['url'];
        url_parameters = '';
    }

    // Not an ajax, redirect to url
    if (!wp_localize_script_autocomplete.data.is_ajax) {
        // Redirect to url
        window.location.href = url + url_parameters;
        return;
    }

    // Update url with the current selection, if required, and authorized by admin option
    if (is_change_url && wp_localize_script_autocomplete.data.is_show_url_parameters && (undefined !== window.history.pushState )) {
        // Option to show parameters in url no selected: do nothing

        // Create state from url parameters
        var state = {url: url + url_parameters};

        //alert('before pushState: ' + url1.toString());

        // Create state and change url
        window.history.pushState(state, '', state.url);
    }

    // Generate Ajax data object
    var data = {action: 'return_solr_results', url_parameters: url_parameters};

    var current_page_title = jQuery(wp_localize_script_autocomplete.data.css_ajax_container_page_title);
    var current_page_sort = jQuery(wp_localize_script_autocomplete.data.css_ajax_container_page_sort);
    var current_count = jQuery(wp_localize_script_autocomplete.data.css_ajax_container_results_count);
    var current_results = jQuery(wp_localize_script_autocomplete.data.css_ajax_container_results).first();
    var current_pagination = jQuery(wp_localize_script_autocomplete.data.css_ajax_container_pagination);

    // Pass parameters to Ajax
    jQuery.ajax({
        url: url + url_parameters,
        //type: "post",
        //data: data,
        dataType: 'html',
        success: function (response) {

            var response_results = jQuery(response).find(wp_localize_script_autocomplete.data.css_ajax_container_results).first().html();

            if (undefined === response_results) {
                // Show the page with the empty message
                window.location.href = url + url_parameters;

            } else {

                /*
                 data = JSON.parse(response);

                 // Display pagination
                 jQuery('.paginate_div').html(data[1]);

                 // Display number of results
                 jQuery('.res_info').html(data[2]);

                 jQuery('.results-by-facets').html(data[0]);
                 */

                // Remove loader
                wpsolr_ajax_loading(current_results, 'hide');

                var response_page_title = jQuery(response).find(wp_localize_script_autocomplete.data.css_ajax_container_page_title).first().html();
                var response_page_sort = jQuery(response).find(wp_localize_script_autocomplete.data.css_ajax_container_page_sort).first().html();
                var response_pagination = jQuery(response).find(wp_localize_script_autocomplete.data.css_ajax_container_pagination).first().html();
                var response_count = jQuery(response).find(wp_localize_script_autocomplete.data.css_ajax_container_results_count).first().html();

                // Refresh metas information like title, description
                jQuery(document).find('title').html(jQuery(response).filter('title').html());
                jQuery('meta').each(function () {
                    var attribute = jQuery(this).attr('name') ? 'name' : (jQuery(this).attr('property') ? 'property' : '');
                    if ('' !== attribute) {
                        ///console.log(attribute + ': ' + 'meta[' + attribute + '="' + jQuery(this).attr(attribute) + '"]');
                        jQuery(this).attr('content', jQuery(response).filter('meta[' + attribute + '="' + jQuery(this).attr(attribute) + '"]').attr('content'));
                    }
                });

                // Display facets
                jQuery('#res_facets').html(jQuery(response).find('#res_facets').first().html());

                // Display page title
                current_page_title.html(response_page_title);

                // Display page sort list
                current_page_sort.html(response_page_sort);

                // Display results
                current_results.html(undefined === response_results ? '' : response_results);

                // Display number of results
                current_count.html(response_count);

                if (undefined !== response_pagination) {
                    if (current_pagination.length === 0) {
                        //response_pagination.insertAfter(current_results);
                    }

                    current_pagination.html(response_pagination).show();
                } else {
                    current_pagination.hide();
                }

                if (is_scroll_top_after) {
                    // Come back to top
                    jQuery('html,body').animate({scrollTop: 0}, "fast");
                }

                // Notify that Ajax is completed
                //console.log('triger ajax refresh');
                jQuery(document).trigger('wpsolr_on_ajax_success');

            }

        },
        error: function () {

            // Remove loader
            wpsolr_ajax_loading(current_results, 'hide');

            // Notify that Ajax has failed
            jQuery(document).trigger('wpsolr_on_ajax_error');

        },
        always: function () {
            // Not called.
        }
    });
}

/**
 * JQuery UI events
 */
jQuery(document).ready(function () {

    jQuery(wp_localize_script_autocomplete.data.wpsolr_autocomplete_selector).off(); // Deactivate other events of theme
    jQuery(wp_localize_script_autocomplete.data.wpsolr_autocomplete_selector).prop('autocomplete', 'off'); // Prevent browser autocomplete

    /**
     * Search form is focused
     */
    jQuery(document).on('focus', wp_localize_script_autocomplete.data.wpsolr_autocomplete_selector, function (event) {

        event.preventDefault();

        var wp_ajax_action = wp_localize_script_autocomplete.data.wpsolr_autocomplete_action;
        var wp_ajax_nonce = jQuery(wp_localize_script_autocomplete.data.wpsolr_autocomplete_nonce_selector).val();

        var mythis = this;

        jQuery(this).typeahead({
            ajax: {
                url: wp_localize_script_autocomplete.data.ajax_url,
                triggerLength: 1,
                method: "post",
                loadingClass: "loading-circle",
                preDispatch: function (query) {

                    jQuery(mythis).addClass('loading_sugg');

                    return {
                        action: wp_ajax_action,
                        word: query,
                        security: wp_ajax_nonce
                    }
                },
                preProcess: function (data) {
                    jQuery(mythis).removeClass('loading_sugg');
                    return data;
                }
            }
        });
    });


    if ((wp_localize_script_autocomplete.data.is_ajax) && (0 === jQuery(document).find('.search-frm').length)) {
        /**
         *
         * Search form is triggered on ajax
         */
        jQuery('form').on('submit', function (event) {

            var me = jQuery(this);

            var current_results = jQuery(wp_localize_script_autocomplete.data.css_ajax_container_results).first();

            if (current_results.length && jQuery(this).find(wp_localize_script_autocomplete.data.wpsolr_autocomplete_selector).length) {
                // The submitted form is on a search page
                event.preventDefault();

                var keywords = me.find(wp_localize_script_autocomplete.data.wpsolr_autocomplete_selector).first().attr("value");

                // Ajax call on the current selection
                var parameter = {};
                if ('' !== wp_localize_script_autocomplete.data.redirect_search_home) {
                    // Trim spaces, then replaces double spaces by on space, then replace space by "+", then replace multiple "+" by a single "+".
                    // "  red +++  blue  " => "red+blue"
                    parameter['url'] = '/' + wp_localize_script_autocomplete.data.redirect_search_home + '/' + keywords.toLowerCase().trim().replace(/ +/g, " ").replace(/ /g, '+').replace(/\++/g, "+");
                } else {
                    parameter[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_S] = keywords;
                }
                call_ajax_search_timer(parameter, true, true);
            }
        });

        /**
         *
         * Search sort is triggered on ajax
         */
        // Unbind them's sort event, before replacing it.
        jQuery(document).off('change', wp_localize_script_autocomplete.data.css_ajax_container_page_sort + ' select');
        jQuery(wp_localize_script_autocomplete.data.css_ajax_container_page_sort).closest('form').on('submit', function () {
            return false;
        });
        jQuery(document).on('change', wp_localize_script_autocomplete.data.css_ajax_container_page_sort + ' select', function (event) {

            var me = jQuery(this);

            // The submitted form is on a search page
            event.preventDefault();

            // Ajax call on the current selection
            var parameter = {};
            parameter[me.attr("name")] = me.attr("value");
            call_ajax_search_timer(parameter, true, true);
        });

        /**
         *
         * Search navigation is triggered on ajax
         */
        jQuery(document).on('click', wp_localize_script_autocomplete.data.css_ajax_container_pagination_page, function (event) {

            event.preventDefault();

            var me = jQuery(this);

            // Ajax call on the current selection
            var parameter = {};
            parameter['url'] = me.attr("href");
            call_ajax_search_timer(parameter, true, true);
        });

    }

    /**
     * Select/unselect a facet
     */
    window.wpsolr_facet_change = function ($items, event) {

        // Reset pagination
        jQuery('#paginate').val('');

        var state = [];
        var $this;

        $items.each(function (index) {

            $this = jQuery(this);
            var facet_data = $this.data('wpsolr-facet-data');

            if ($this.attr('id') === 'wpsolr_remove_facets') {

                // Unselect all facets
                jQuery('.select_opt').removeClass('checked');
                $this.addClass('checked');

            } else {

                // Select/Unselect the element
                is_already_selected = $this.hasClass('checked') && ('facet_type_min_max' !== facet_data.type);
                var facet_name = $this.attr('id').split(":")[0];

                if (is_already_selected) {
                    // Unselect current selection

                    $this.removeClass('checked');
                    $this.addClass('unchecked');
                    $this.next("ul").children().find("[id^=" + facet_name + "]").removeClass('checked');


                    if ($this.hasClass('wpsolr_facet_option')) {

                        if ($this.parent().prop("multiple")) {
                            // Unselelect children too (next with sublevel)

                            var current_level = $this.data('wpsolr-facet-data').level;
                            $this.nextAll().each(function () {

                                //alert(current_level + ' : ' + jQuery(this).data('wpsolr-facet-data').level);
                                if (current_level < jQuery(this).data('wpsolr-facet-data').level) {

                                    //alert(jQuery(this).attr('id') + ' : ' + jQuery(this).attr('class'));
                                    jQuery(this).removeClass('checked');

                                } else {

                                    return false; // Stop asap to prevent adding another sublevel
                                }
                            });
                        }
                    }

                } else {

                    // Unselect other radioboxes
                    $this.closest("ul.wpsolr_facet_radiobox").children().find("[id^=" + facet_name + "]").removeClass('checked');

                    if ($this.hasClass('wpsolr_facet_option')) {

                        if (!$this.parent().prop("multiple")) {
                            // Unselect other options first

                            $this.parent().children().removeClass('checked');

                        } else {
                            // Select parents too (previous with sublevel)

                            var current_selected_level = $this.data('wpsolr-facet-data').level;
                            $this.prevAll().each(function () {

                                if (current_selected_level > jQuery(this).data('wpsolr-facet-data').level) {

                                    jQuery(this).addClass('checked');

                                    // Recursive on parents
                                    current_selected_level = jQuery(this).data('wpsolr-facet-data').level;
                                }
                            });
                        }

                        $this.addClass('checked');

                    } else {
                        // Select current selection (ul/li)
                        $this.parents("li").children(".select_opt").addClass('checked');
                    }

                }

                // Get facets state
                state = get_ui_facets_state();
            }
        })

        //alert(JSON.stringify(state));

        // Ajax call on the current selection
        var parameter = {};
        var permalink;
        if (undefined !== $this) {
            permalink = $this.find('a.wpsolr_permalink').first().attr('href') || $this.data('wpsolr-facet-data').permalink_href;
        }

        if (undefined === permalink) {
            parameter[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_FQ] = state;
        } else {
            if ((null !== event) && (undefined !== event)) {
                event.preventDefault(); // Prevent permalink redirection
            }
            parameter['url'] = permalink;
        }

        call_ajax_search_timer(parameter, true, false);

    }

    /**
     * A simple facet is selected/unselected
     */
    jQuery(document).on('click', 'div.select_opt', function (event) {
        if ('facet_type_field' === jQuery(this).data('wpsolr-facet-data').type) {
            wpsolr_facet_change(jQuery(this), event);
        }
    });

    function wpsolr_select_value(current) {
        var selected_values = current.val();

        //console.log(selected_values);
        //console.log(current.prop('multiple'));

        if (current.prop('multiple')) {
            // It is a multi-select. Delete first to replace values.

            current.children().removeClass('checked');
        }

        wpsolr_facet_change(current.find('option:selected'), event);
    }

    /**
     * A non-multiselect select facet is selected/unselected
     */
    jQuery(document).on('change', '.wpsolr_facet_select select', function (event) {

        wpsolr_select_value(jQuery(this));
    });

    /**
     * A non-multiselect select facet is clicked
     */
    jQuery(document).on('clickx', '.wpsolr_facet_select .wpsolr-select-multiple option', function (event) {
        //console.log('test');

        wpsolr_select_value(jQuery(this).parent('select'));
    });

    /**
     * Sort is selected
     */
    jQuery(document).on('change', '.select_field', function () {

        // Reset pagination
        jQuery('#paginate').val('');

        // Retrieve current selection
        sort_value = jQuery(this).val();

        // Ajax call on the current selection
        var parameter = {};
        parameter[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_SORT] = sort_value;
        call_ajax_search_timer(parameter, true, true);
    });


    /**
     * Pagination is selected
     */
    jQuery(document).on('click', '.paginate', function () {

        // Retrieve current selection
        page_number = jQuery(this).attr('id');

        // Store the current selection
        jQuery('#paginate').val(page_number);

        // Ajax call on the current selection
        var parameter = {};
        parameter[wp_localize_script_autocomplete.data.SEARCH_PARAMETER_PAGE] = page_number;
        call_ajax_search_timer(parameter, true, false);

    });


    /**
     * Add geolocation user agreement to selectors
     */
    jQuery(wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_SEARCH_BOX_JQUERY_SELECTOR).each(function (index) {

        jQuery(this).closest('form').append(wp_localize_script_autocomplete.data.WPSOLR_FILTER_ADD_GEO_USER_AGREEMENT_CHECKBOX_TO_AJAX_SEARCH_FORM);
    });

    /**
     * Manage geolocation
     */
    jQuery('form').on('submit', function (event) {

        //event.preventDefault();

        var me = jQuery(this);

        if (jQuery(this).find(wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_SEARCH_BOX_JQUERY_SELECTOR).length) {
            // The submitted form contains an element linked to the geolocation by a jQuery selector

            var nb_user_agreement_checkboxes = jQuery(this).find(wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_USER_AGREEMENT_JQUERY_SELECTOR).length;
            var user_agreement_first_checkbox_value = jQuery(this).find(wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_USER_AGREEMENT_JQUERY_SELECTOR).filter(':checked').first().val();

            /**
             * We want to force the checkbox value to 'n' when unchecked (normally, it's value disappears from the form).
             * Else, no way to have a 3-state url value: absent/checked/unchecked. The url absent state can be then translated to checked or unchecked.
             */
            var current_checkbox = jQuery(this).find(wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_USER_AGREEMENT_JQUERY_SELECTOR).first();
            if (!current_checkbox.prop('checked')) {
                me.append(jQuery("<input />").attr("type", "hidden").attr("name", current_checkbox.prop("name")).val(wp_localize_script_autocomplete.data.PARAMETER_VALUE_NO));
            } else {
                current_checkbox.val(wp_localize_script_autocomplete.data.PARAMETER_VALUE_YES);
            }

            //console.log('wpsolr geolocation selectors: ' + wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_SEARCH_BOX_JQUERY_SELECTOR);
            //console.log('wpsolr geolocation user agreement selectors: ' + wp_localize_script_autocomplete.data.WPSOLR_FILTER_GEOLOCATION_USER_AGREEMENT_JQUERY_SELECTOR);
            //console.log('wpsolr nb of geolocation user agreement checkboxes: ' + nb_user_agreement_checkboxes);
            //console.log('wpsolr first geolocation user agreement checkbox value: ' + user_agreement_first_checkbox_value);

            if ((0 === nb_user_agreement_checkboxes) || (undefined !== user_agreement_first_checkbox_value)) {
                // The form does not contain a field requiring to not use geolocation (a checkbox unchecked)

                if (navigator.geolocation) {

                    // Stop the submit happening while the geo code executes asynchronously
                    event.preventDefault();

                    // Add a css class to the submit buttons while collecting the location
                    me.addClass("wpsolr_geo_loading");
                    // Remove the class automatically, to prevent it staying forever if the visitor denies geolocation.
                    var wpsolr_geo_loading_timeout = window.setTimeout(
                        function () {
                            me.removeClass("wpsolr_geo_loading");
                        }
                        ,
                        10000
                    );

                    navigator.geolocation.getCurrentPosition(
                        function (position) {

                            // Stop wpsolr_geo_loading_timeout
                            window.clearTimeout(wpsolr_geo_loading_timeout);
                            // Add a css class to the submit buttons while collecting the location
                            me.addClass("wpsolr_geo_loading");

                            // Add coordinates to the form
                            me.append(jQuery("<input />").attr("type", "hidden").attr("name", wp_localize_script_autocomplete.data.SEARCH_PARAMETER_LATITUDE).val(position.coords.latitude));
                            me.append(jQuery("<input />").attr("type", "hidden").attr("name", wp_localize_script_autocomplete.data.SEARCH_PARAMETER_LONGITUDE).val(position.coords.longitude));

                            // Finally, submit
                            me.unbind('submit').submit();

                        },
                        function (error) {

                            console.log('wpsolr: geolocation error: ' + error.code);

                            // Stop wpsolr_geo_loading_timeout
                            window.clearTimeout(wpsolr_geo_loading_timeout);
                            // Add a css class to the submit buttons while collecting the location
                            me.addClass("wpsolr_geo_loading");

                            // Finally, submit
                            me.unbind('submit').submit();
                        }
                    );

                } else {

                    console.log('wpsolr: geolocation not supported by browser.');
                }
            }

        }

    });

})
;
