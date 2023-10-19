<?php
/**
 * Plugin Name: Authorization Test Plugin (atp)
 * Author: s2s
 */


/**
 * Creating a basic settings page
 */
function atp_settings_page() {

    add_options_page(
        'Authorization Test Plugin',
        'Authorization Test Plugin',
        'manage_options',
        'atp-settings',
        'atp_settings_page_content'
    );
}
add_action('admin_menu', 'atp_settings_page');


/**
 * Settings page contents
 */
function atp_settings_page_content() {
    ?>

    <div class="wrap">
        <h1>Custom Login Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('atp-settings');
            do_settings_sections('atp-settings');
            submit_button();
            ?>
        </form>
    </div>

    <?php
}


/**
 * Create a text input for redirect after successful authorization
 */
add_action('admin_init', 'atp_settings_init');
function atp_settings_init() {

    add_settings_section(
        'atp-settings-section',
        'Login Redirect Settings',
        'atp_settings_section_callback',
        'atp-settings'
    );

    add_settings_field(
        'atp_login_redirect_url',
        'Redirect URL',
        'atp_redirect_url_callback',
        'atp-settings',
        'atp-settings-section'
    );

    register_setting('atp-settings', 'atp_login_redirect_url');
}


/**
 * CCallback functions for displaying fields on the settings page
 */
function atp_redirect_url_callback() {

    $atp_login_redirect_url = get_option('atp_login_redirect_url');

    echo 'Enter the URL where users will be redirected after successful login<br>';
    echo "/wp-admin/<input type='text' name='atp_login_redirect_url' value='$atp_login_redirect_url' />";
}


/**
 * Function to count failed admin login attempts
 */
add_action('wp_login_failed', 'atp_increment_failed_login_attempts');
function atp_increment_failed_login_attempts() {

    $failed_attempts = (int)get_option('atp_total_failed_login_attempts', 0);
    $failed_attempts++;
    update_option('atp_total_failed_login_attempts', $failed_attempts);
}



/**
 * Displaying the failed attempts counter on the settings page
 */
function atp_settings_section_callback() {

    $failed_attempts = (int)get_option('atp_total_failed_login_attempts', 0);
    echo "<div class='attention'>Total Failed Attempts: $failed_attempts</div>";
}


/**
 * Adding a checkbox for AJAX mode
 */
add_action('admin_init', 'atp_settings_init_ajax');
function atp_settings_init_ajax() {

    add_settings_field(
        'atp_login_ajax_mode',
        'Enable AJAX mode for login form',
        'atp_ajax_mode_callback',
        'atp-settings',
        'atp-settings-section'
    );

    register_setting('atp-settings', 'atp_login_ajax_mode');
}



/**
 * Callback functions to display the 'Enable AJAX' checkbox on the settings page
 */
function atp_ajax_mode_callback() {

    $atp_login_ajax_mode = get_option('atp_login_ajax_mode', false);
    echo "<input type='checkbox' name='atp_login_ajax_mode' value='1' " . checked(1, $atp_login_ajax_mode, false) . ' />';
}


/**
 * Handling successful authorization and redirect
 */
add_filter('login_redirect', 'atp_redirect', 10, 3);
function atp_redirect($redirect, $request, $user) {

    if (isset($user->ID)) {
        $redirect_to = atp_name_page_redirect();
    }

    return !empty($redirect_to) ? $redirect_to : $redirect;
}



/**
 * Handling successful authorization and redirect
 */
function atp_name_page_redirect() {

    $login_redirect_url = get_option('atp_login_redirect_url', '');

    // We check the value for empty and for the absence of invalid characters
    // Validation is performed only for values consisting of ASCII characters
    if (!empty($login_redirect_url) && preg_match('/[a-zA-Z0-9.%_\-\/\?=&]+$/', $login_redirect_url)) {

        $redirect_to = admin_url($login_redirect_url);
    }

    return $redirect_to ?? '';
}


/**
 * Connecting the admin panel styles file
 */
add_action('login_enqueue_scripts', 'atp_styles');
function atp_styles() {

    wp_enqueue_style('atp-custom-login-styles', plugin_dir_url(__FILE__) . 'authorization-test-plugin-styles.css', array(), '1.0');
}



/**
 * We include the script file on the login page
 */
add_action('login_enqueue_scripts', 'atp_scripts');
function atp_scripts() {

    wp_register_script('atp-custom-login-scripts', plugin_dir_url(__FILE__) . 'authorization-test-plugin-script.js', array('jquery'), '1.0', true);

    $ajax_settings = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'enable_ajax_login' => get_option('atp_login_ajax_mode', 0),
        'atp_login_redirect_url' => atp_name_page_redirect(),
    );

    wp_localize_script('atp-custom-login-scripts', 'login_ajax_handler', $ajax_settings);
    wp_enqueue_script('atp-custom-login-scripts');
}



/**
 * Ajax authorization check function
 */
add_action('wp_ajax_nopriv_action_login_ajax_handler', 'atp_login_ajax_handler');
function atp_login_ajax_handler() {

    if (isset($_POST['action']) && $_POST['action'] === 'action_login_ajax_handler') {  // Checking the action of the request
        $user_login = isset($_POST['log']) ? $_POST['log'] : '';
        $user_pass = isset($_POST['pwd']) ? $_POST['pwd'] : '';
        $remember = isset($_POST['rememberme']) ? $_POST['rememberme'] : '';
        $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : '';

        $user_data = [
            'user_login'    => $user_login,
            'user_password' => $user_pass,
            'remember'      => $remember
        ];

        $user = wp_signon($user_data, false);
        if (is_wp_error($user)) {
            $response = [
                'success' => false,
                'message' => $user->get_error_message()
            ];
        } else {
            $redirect_url = atp_name_page_redirect();
            $response = [
                'success' => true,
                'redirect_url' => !empty($redirect_url) ? $redirect_url : $redirect_to
            ];
        }

        wp_send_json($response);
    }
}
