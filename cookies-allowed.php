<?php
/*
Plugin Name: Cookies Allowed
Description: Add front-end cookie notification bar, front-end cookie settings pannel, back-end Cookie management, back-end scripts manager page
Version: 1.3.0
Author: Pepijn Nichting
Author URI: https://gravity.nl
*/

function load_cookies_allowed_textdomain()
{

    // vars
    $domain = 'cookies-allowed';
    $locale = apply_filters('plugin_locale', get_locale(), $domain);
    $mofile = $domain . '-' . $locale . '.mo';
    $plugin_path = locate_template('includes/cookies-allowed/languages/', false, false);

    // load from the languages directory first
    load_textdomain($domain, WP_LANG_DIR . '/plugins/' . $mofile);

    // load from plugin lang folder
    load_textdomain($domain, $plugin_path . $mofile);
}

add_action('init', 'load_cookies_allowed_textdomain');

// get the default language
function get_default_language()
{
    global $sitepress;
    if (!empty($sitepress)) {
        return $sitepress->get_default_language();
    }

    return;
}

// maybe we stil need this..... for now user the load_cookies_allowed_textdomain function
function set_cookies_allowed_language_path()
{
    $plugin_rel_path = locate_template('includes/cookies-allowed/languages/', false, false); /* Relative to WP_PLUGIN_DIR */
    echo($plugin_rel_path);
    load_plugin_textdomain('cookies-allowed', false, $plugin_rel_path);
}

//add_action('init', 'set_cookies_allowed_language_path');

/*
*   Description: return the url of a file if exists in child, if not return parent file url
*   Args: $path relative to theme file path
*   Expected return: http://pepijn.local/autovakmeester.nl/wp-content/themes/gravitycore/includes/cookies-allowed/cookies-allowed.js
*   Usage: gravity_get_available_file_uri('includes/cookies-allowed/cookies-allowed.js')
*/

function gravity_get_available_file_uri($path)
{
    $file_name = array_slice(explode('/', rtrim($path, '/')), -1)[0];

    if (file_exists(get_stylesheet_directory() . '/' . $path)) { //child-theme
        $available_path = get_stylesheet_directory_uri() . '/' . $path;
    } elseif (file_exists(get_template_directory() . '/' . $path)) { //parrent-theme
        $available_path = get_template_directory_uri() . '/' . $path;
    } elseif (file_exists(plugin_dir_path(__FILE__) . $file_name)) {
        $available_path = plugin_dir_url(__FILE__) . $file_name;
    } else { //current file location
        $current_dir = dirname(__DIR__);
        $themefolder_path = substr($current_dir, strpos($current_dir, "/themes/") + 8);
        $themefolder_name = strstr($themefolder_path, '/', true);
        $available_path = get_theme_root_uri() . '/' . $themefolder_name . '/' . $path;
    }

    return $available_path;
}

/*
*   Description: enqueue cookies_allowed scripts
*   Args: none
*   Expected return:
*   Usage:
*/
if (!is_admin()) {
    add_action("wp_enqueue_scripts", "enqueue_cookies_allowed_scripts", 11);
}
function enqueue_cookies_allowed_scripts()
{
    $language_suffix = null;
    if (defined('ICL_LANGUAGE_CODE') && function_exists('pll_default_language') && ICL_LANGUAGE_CODE != pll_default_language()) {
        $language_suffix = '_' . ICL_LANGUAGE_CODE;
    }

    $post_id = 'options' . $language_suffix;

    //JS
    wp_register_script('cookies-allowed-js', gravity_get_available_file_uri('includes/cookies-allowed/cookies-allowed.js'), ['jquery'], '1.1.0', true);
    wp_enqueue_script('cookies-allowed-js');

    // CSS
    wp_register_style('cookies-allowed-default-css', gravity_get_available_file_uri('includes/cookies-allowed/cookies-allowed-default.css'), ['jquery'], null, 'all');
    if (get_field('cookies_allowed_default_css', $post_id)) {
        wp_enqueue_style('cookies-allowed-default-css');
    }

    // If wpml = active add the language to the ajax url
    if (in_array('sitepress-multilingual-cms/sitepress.php', get_option('active_plugins'))) {
        $ajaxurl = admin_url('admin-ajax.php?lang=' . ICL_LANGUAGE_CODE);
    } else {
        $ajaxurl = admin_url('admin-ajax.php');
    }
    /* Telling the JS file where ajaxUrl is */
    wp_localize_script('cookies-allowed-js',
        'ajaxUrl',
        [
            'url' => $ajaxurl,
        ]);

}

/*
*   Description: add the cookie bar to the wp_footer()
*   Args: none
*   Expected return:
*   Usage:
*/
add_action('wp_ajax_cookies_allowed_html', 'cookies_allowed_html');
add_action('wp_ajax_nopriv_cookies_allowed_html', 'cookies_allowed_html');
add_action('wp_footer', 'cookies_allowed_html');
function cookies_allowed_html()
{
    echo get_cookies_allowed_html();
    if (wp_doing_ajax()) {
        die();
    }
}

// add a wrapper for the cookie scripts to be loaded into
add_action('wp_footer', 'set_footer_script_wrapper');
function set_footer_script_wrapper()
{
    echo('<div id="cookies-allowed-footer-scripts"></div>');
}

/*
*   Check if cookies_allowed_level is set at a specific level
*   args: 0,1,2,3
*   expected return: TRUE, FALSE
*   usage: is_cookies_allowed_level(3)
*/
function is_cookies_allowed_level($cookie_allowed_level = 1)
{
    if (isset($_COOKIE['cookie_allowed_level']) && $_COOKIE['cookie_allowed_level'] == $cookie_allowed_level) :
        return true;
    endif;

    return false;
}

// if is default language or if is single language website
if (defined('ICL_LANGUAGE_CODE') && get_default_language() == ICL_LANGUAGE_CODE || !defined('ICL_LANGUAGE_CODE')) {
    add_filter('acf/prepare_field/name=cookies_allowed_default_language_scripts', 'hide_cookies_allowed_acf_field');
}

function hide_cookies_allowed_acf_field($field)
{
    $field['disabled'] = true;
    $field['readonly'] = true;
    $field['value'] = false;

    return false; // diabled get removed by conditional logic

    //return $field;
}

// Load the cookie scripts by AJAX
add_action('wp_ajax_get_cookies_allowed_scripts', 'get_cookies_allowed_scripts');
add_action('wp_ajax_nopriv_get_cookies_allowed_scripts', 'get_cookies_allowed_scripts');

function get_cookies_allowed_scripts()
{

    global $sitepress;

    $language_suffix = null;
    if (defined('ICL_LANGUAGE_CODE') && function_exists('pll_default_language') && ICL_LANGUAGE_CODE != pll_default_language()) {
        $language_suffix = '_' . ICL_LANGUAGE_CODE;
    }

    $post_id = 'options' . $language_suffix;

    //set the language to site default
    if (get_field('cookies_allowed_default_language_scripts', 'options')) {
        if (defined('ICL_LANGUAGE_CODE') && function_exists('pll_default_language') && ICL_LANGUAGE_CODE != pll_default_language()) {
            add_filter('acf/settings/current_language', pll_default_language(), 100);
        } else if ($sitepress) {
            add_filter('acf/settings/current_language', $sitepress->get_default_language(), 100);
        } else {
            add_filter('acf/settings/current_language', pll_default_language(), 100);
        }
    }

    $scripts = [];

    // Script to be loaded before any cookie check
    $scripts["header"][] = get_field('cookies_allowed_header_scripts_before_all', $post_id);
    $scripts["footer"][] = get_field('cookies_allowed_footer_scripts_before_all', $post_id);

    if (get_cookies_allowed_level() >= 0) {
        //$scripts["header"][] = locate_template('parts/cookies_allowed_header_scripts.php');
        $scripts["header"][] = get_field('cookies_allowed_header_scripts_1', $post_id);

        //$scripts["footer"][] = locate_template('parts/cookies_allowed_footer_scripts.php');
        $scripts["footer"][] = get_field('cookies_allowed_footer_scripts_1', $post_id);
    }
    if (get_cookies_allowed_level() >= 2) {
        //$scripts["header"][] = locate_template('parts/cookies_allowed_header_scripts.php');
        $scripts["header"][] = get_field('cookies_allowed_header_scripts_2', $post_id);

        //$scripts["footer"][] = locate_template('parts/cookies_allowed_footer_scripts.php');
        $scripts["footer"][] = get_field('cookies_allowed_footer_scripts_2', $post_id);
    }
    if (get_cookies_allowed_level() >= 3) {
        //$scripts["header"][] = locate_template('parts/cookies_allowed_header_scripts.php');
        $scripts["header"][] = get_field('cookies_allowed_header_scripts_3', $post_id);

        //$scripts["footer"][] = locate_template('parts/cookies_allowed_footer_scripts.php');
        $scripts["footer"][] = get_field('cookies_allowed_footer_scripts_3', $post_id);
    }

    // Script to be loaded after any cookie check
    $scripts["header"][] = get_field('cookies_allowed_header_scripts_after_all', $post_id);
    $scripts["footer"][] = get_field('cookies_allowed_footer_scripts_after_all', $post_id);

    // reset to original language
    if (get_field('cookies_allowed_default_language_scripts', $post_id)) {
        if ($sitepress) {
            remove_filter('acf/settings/current_language', $sitepress->get_default_language(), 100);
        } else {
            remove_filter('acf/settings/current_language', pll_default_language(), 100);
        }
    }

    //         print_r($scripts);
    //wp_send_json($scripts);
    echo json_encode($scripts);
    die();
}

add_action('admin_notices', 'install_and_activate_plugins');
function install_and_activate_plugins()
{
    global $wp;
    if (class_exists('acf_code_field') || class_exists('acf_code_field_v4')) {
        return;
    } elseif (current_user_can('manage_options') /* && $installing == true  */) {
        $current_request = add_query_arg($_GET, $wp->request);
        $plugin_install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=acf-code-field'), 'install-plugin_acf-code-field');
        //$installing = (isset($current_request) && strpos($plugin_install_url, $current_request) == true ) ? true : false; // needs work......
        //print_r($plugin_install_url);
        ?>
        <div class="notice notice-warning">
            <h3><?php esc_html_e('Cookies Allowed plugin', 'cookies-allowed'); ?></h3>
            <p><?php _e('<a href="' . $plugin_install_url . '">Installeer acf-code-field</a>, dit is nodig om de cookies allowed backend te laten werken', 'cookies-allowed'); ?></p>
        </div>
        <?php
    }
}

// Add ACF json files for the back-end cookie settings
add_filter('acf/settings/load_json', 'my_acf_json_cookies_allowed');
function my_acf_json_cookies_allowed($paths)
{

    //Load plugin acf dir
    $paths[] = __DIR__ . '/acf-json';

    // return
    return $paths;
}

/*
*   Get the cookies_allowed_level
*   args: no args
*   expected return:  0, 1, 2, 3
*   usage: get_cookies_allowed_level()
*/
function get_cookies_allowed_level()
{
    if (isset($_COOKIE['cookie_allowed_level'])):
        return $_COOKIE['cookie_allowed_level'];
    endif;

    return 0;
}

/*
*   Create a backend options page
*   args: no args
*   expected return:
*   usage:
*/
// Add Options Page
add_action('init', 'acf_add_cookie_options_page');
function acf_add_cookie_options_page()
{
    if (function_exists('acf_add_options_page')) {
        //wp_die( 'pffffff!' );
        $language_suffix = null;
        if (defined('ICL_LANGUAGE_CODE') && function_exists('pll_default_language') && ICL_LANGUAGE_CODE != pll_default_language()) {
            $language_suffix = '_' . ICL_LANGUAGE_CODE;
        }

        acf_add_options_page([
            'page_title' => 'Cookie Opties',
            'menu_title' => 'Cookie Opties',
            'menu_slug' => 'cookie-options',
            'icon_url' => 'dashicons-art',
            'post_id' => 'options' . $language_suffix,
            'redirect' => false,
        ]);
    }
}

/*
*   Build the html for the cookie notice and modal
*   args: no args
*   expected return: cookie html
*   usage:
*/
function get_cookies_allowed_html($language_suffix = null)
{
    $html = '';
    ob_start();

    if (defined('ICL_LANGUAGE_CODE') && function_exists('pll_default_language') && ICL_LANGUAGE_CODE != pll_default_language()) {
        $language_suffix = '_' . ICL_LANGUAGE_CODE;
    }

    $post_id = 'options' . $language_suffix;

    if (get_field('cookies_allowed_default_language_scripts', 'options')) {
        //set the language to site default
        add_filter('acf/settings/current_language', 'get_default_language', 100);
    }

    $highest_cookie_allowed_level = (get_field('highest_cookie_allowed_level', $post_id)) ? get_field('highest_cookie_allowed_level', $post_id) : 3;

    if (get_field('cookies_allowed_default_language_scripts', 'options')) {
        // reset to original language
        remove_filter('acf/settings/current_language', 'get_default_language', 100);
    }

    if (class_exists('NumberFormatter')) {
        $numbertoword = new NumberFormatter("nl", NumberFormatter::SPELLOUT);
        $highest_cookie_word = $numbertoword->format($highest_cookie_allowed_level);
    }
    $policy_page_url = '#';
    $previous_cookie_level = get_cookies_allowed_level();

    $acf_cookie_modal_text = get_field('cookie_modal_text', $post_id);
    $default_cookie_modal_text = sprintf(__('<h4>What are cookies?</h4><p>Cookies are small files that are placed by us on your computer, tablet or smartphone in order to use a website properly. Some cookies are necessary for optimal use of the website. Some cookies are extra.</p><h4>Manage your cookie settings</h4><p>Functional cookies are needed to use the website, which is why they are always on. For an optimal online experience, we recommend to enable additional cookies</p><p>More information about the different types of cookies and their effect can be found in our <a href="%s">Cookie Policy</a> page.</p>', 'cookies-allowed'), $policy_page_url);

    $acf_cookie_notice_text = get_field('cookie_notice_text', $post_id);
    $default_cookie_notice_text = sprintf(__('<p> %s uses cookies to optimize your experience on this website. By using this website you automatically agree to the use of functional cookies and anonymous Analytic cookies.</p>', 'cookies-allowed'), $_SERVER["SERVER_NAME"]);

    $acf_highest_cookie_notice_text = get_field('highest_cookie_notice_text', $post_id);
    $default_highest_cookie_notice_text = __('<p>We also use user specific analytic and marketing cookies, by clicking on \'Allow cookies\' you also agree to the use of these cookies. Go to <a href="#" class="js-cookie-modal"> Settings </a> to manage your cookies on this website.</p>', 'cookies-allowed');

    ?>
    <div id="cookies-allowed"
         data-page-reload="<?php echo get_field('cookies_allowed_reload_page', $post_id) ? 'true' : 'false'; ?>">
        <div id="cookie-notice" class="cookie-notice"
             data-highest-cookie-allowed-level="<?php echo $highest_cookie_allowed_level ?>">
            <div class="cookie-notice__container">
                <div class="cookie-notice__wrapper">
                    <div class="cookie-notice__content">
                        <?php // echo get_cookies_allowed_level();
                        ?>
                        <?php if (get_cookies_allowed_level() < 1): ?>
                            <?php echo empty($acf_cookie_notice_text) ? $default_cookie_notice_text : $acf_cookie_notice_text; ?>
                        <?php else: ?>
                            <?php echo empty($acf_highest_cookie_notice_text) ? $default_highest_cookie_notice_text : $acf_highest_cookie_notice_text; ?>
                        <?php endif; ?>
                    </div>
                    <div class="cookie-notice__buttons">
                        <button class="cookie__button cookie__button--opacity"
                                onclick="allowCookies(<?php echo $highest_cookie_allowed_level ?>);"><?php esc_html_e('Allow cookies', 'cookies-allowed'); ?></button>
                        <?php if (get_cookies_allowed_level() < 1): ?>
                            <button class="cookie__button"
                                    onclick="toggleCookieModal();"><?php esc_html_e('Settings', 'cookies-allowed'); ?></button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="cookie-modal">
            <div class="cookie-modal__backdrop js-cookie-modal"></div>
            <div class="cookie-modal__wrapper">
                <div class="cookie-modal__content">
                    <h3 class="cookie-modal__title"><?php esc_html_e('Manage your settings', 'cookies-allowed'); ?></h3>
                    <div class="cookie-modal__entry">
                        <?php echo empty($acf_cookie_modal_text) ? $default_cookie_modal_text : $acf_cookie_modal_text; ?>
                    </div>
                    <div class="cookie-modal__entry">
                        <h4><?php esc_html_e('Cookie types:', 'cookies-allowed'); ?></h4>
                        <div class="cookie-modal__checkbox__wrapper">
                            <input class="cookie-modal__checkbox" id="allow-cookies-check1" type="checkbox"
                                   checked="checked" disabled onclick="allowCookies(1);">
                            <label class="cookie-modal__label"
                                   for="allow-cookies-check1"><?php esc_html_e('Functional & Analytic cookies (anonymous)', 'cookies-allowed'); ?></label>
                        </div>
                        <?php if ($highest_cookie_allowed_level >= 2): ?>
                            <div class="cookie-modal__checkbox__wrapper">
                                <input class="cookie-modal__checkbox" id="allow-cookies-check2"
                                       type="checkbox" <?php if (is_cookies_allowed_level(2) || is_cookies_allowed_level(3))
                                    echo('checked') ?>
                                       onclick="if(this.checked){allowCookies(2)}else{allowCookies(1)};">
                                <label class="cookie-modal__label"
                                       for="allow-cookies-check2"><?php esc_html_e('Analytic cookies (user specific)', 'cookies-allowed'); ?></label>
                            </div>
                        <?php endif; ?>
                        <?php if ($highest_cookie_allowed_level == 3): ?>
                            <div class="cookie-modal__checkbox__wrapper">
                                <input class="cookie-modal__checkbox" id="allow-cookies-check3"
                                       type="checkbox" <?php if (is_cookies_allowed_level(3))
                                    echo('checked') ?>
                                       onclick="if(this.checked){allowCookies(3)}else{allowCookies(2)};">
                                <label class="cookie-modal__label"
                                       for="allow-cookies-check3"><?php esc_html_e('Marketing & Advertising cookies', 'cookies-allowed'); ?></label>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="cookie-modal__entry cookie-modal__buttons">
                        <button class="cookie__button cookie__button--large cookie__button--success"
                                onclick="toggleCookieModal();"><?php esc_html_e('Save', 'cookies-allowed'); ?></button>
                        <button class="cookie__button cookie__button--large cookie__button--ghost js-cookie-modal"
                                onclick="allowCookies(<?php echo $previous_cookie_level ?>);"><?php esc_html_e('Cancel', 'cookies-allowed'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    $html .= ob_get_clean();

    return $html;
}
