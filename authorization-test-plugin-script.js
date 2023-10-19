/**
 *
 * File script for plugin: Authorization Test Plugin
 * Author: s2s
*/

jQuery(document).ready(function($) {
    let login = $('#login');
    let login_form = $('#loginform');

    login_form.on('submit', function(e) {
        if (login_ajax_handler.enable_ajax_login === '1') {
            e.preventDefault();

            let form_data = $(this).serialize();
            let action = 'action_login_ajax_handler';

            $.ajax({

                url: login_ajax_handler.ajax_url,
                type: 'POST',
                data: form_data + '&action=' + action,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if (response.redirect_url) {
                            window.location.href = response.redirect_url;
                        }
                    } else {
                        let message_container = login.find('.message'); 

                        if (message_container.length === 0) { 
                            message_container = $('<div class="message"></div>');
                            login_form.before(message_container);
                        }

                        message_container.html('<p>' + response.message + '</p>'); 
                        login_form.find('input[type="text"], input[type="password"]').val(''); 
                    }
                }
            });
        }
    });
});


