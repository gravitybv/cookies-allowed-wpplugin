<?php
/*
Plugin Name: Cookies Allowed
Description: Add front-end cookie notification bar, front-end cookie settings pannel, back-end Cookie management, back-end scripts manager page
Version: 1.1.1
Author: Pepijn Nichting
Author URI: https://gravity.nl
*/




/*
*   Description: return the url of a file if exists in child, if not return parent file url
*   Args: $path relative to theme file path
*   Expected return: http://pepijn.local/autovakmeester.nl/wp-content/themes/gravitycore/includes/cookies-allowed/cookies-allowed.js
*   Usage: gravity_get_available_file_uri('includes/cookies-allowed/cookies-allowed.js')
*/

function gravity_get_available_file_uri($path) {
    if ( file_exists( get_stylesheet_directory() . '/' . $path )) {
        $available_path = get_stylesheet_directory_uri() . '/' . $path ;
    } else {
        $available_path = get_template_directory_uri() . '/' . $path ;
    }
    return $available_path;
}


/*
*   Description: enqueue cookies_allowed scripts
*   Args: none
*   Expected return:
*   Usage:
*/
if (!is_admin()) add_action("wp_enqueue_scripts", "enqueue_cookies_allowed_scripts", 11);
function enqueue_cookies_allowed_scripts() {
  //JS
  wp_register_script( 'cookies-allowed-js' , gravity_get_available_file_uri('includes/cookies-allowed/cookies-allowed.js'), '', '1.1.0', true);
  wp_enqueue_script(  'cookies-allowed-js');

  // CSS
  wp_register_style( 'cookies-allowed-default-css' , gravity_get_available_file_uri('includes/cookies-allowed/cookies-allowed-default.css'), '', null , 'all');
  if( get_field( 'cookies_allowed_default_css', 'options' )){
    wp_enqueue_style( 'cookies-allowed-default-css');
  }

  // If wpml = active add the language to the ajax url
  if( in_array('sitepress-multilingual-cms/sitepress.php', get_option('active_plugins')) ){
  	$ajaxurl = admin_url( 'admin-ajax.php?lang=' . ICL_LANGUAGE_CODE );
  } else{
  	$ajaxurl = admin_url( 'admin-ajax.php');
  }
  /* Telling the JS file where ajaxUrl is */
  wp_localize_script( 'cookies-allowed-js', 'ajaxUrl', array(
      'url' => $ajaxurl,
  ));

}



/*
*   Description: add the cookie bar to the wp_footer()
*   Args: none
*   Expected return:
*   Usage:
*/
add_action( 'wp_ajax_cookies_allowed_html', 'cookies_allowed_html' );
add_action( 'wp_ajax_nopriv_cookies_allowed_html', 'cookies_allowed_html' );
add_action( 'wp_footer', 'cookies_allowed_html' );
function cookies_allowed_html() {
  //include(locate_template('parts/cookies-allowed.php'));
  echo(get_cookies_allowed_html());
  if ( wp_doing_ajax() ){
    die();
  }
}

// add a wrapper for the cookie scripts to be loaded into
add_action( 'wp_footer', 'set_footer_script_wrapper' );
function set_footer_script_wrapper(){
  echo('<div id="cookies-allowed-footer-scripts"></div>');
}

/*
*   Check if cookies_allowed_level is set at a specific level
*   args: 0,1,2,3
*   expected return: TRUE, FALSE
*   usage: is_cookies_allowed_level(3)
*/
function is_cookies_allowed_level($cookie_allowed_level = 1){
  if(isset($_COOKIE['cookie_allowed_level']) && $_COOKIE['cookie_allowed_level'] == $cookie_allowed_level) :
    return true;
  endif;
  return false;
}


// Load the cookie scripts by AJAX
add_action( 'wp_ajax_get_cookies_allowed_scripts', 'get_cookies_allowed_scripts' );
add_action( 'wp_ajax_nopriv_get_cookies_allowed_scripts', 'get_cookies_allowed_scripts' );
function get_cookies_allowed_scripts(){
  //$my_current_lang = apply_filters( 'wpml_current_language', NULL );
  //wp_die($my_current_lang); // Returns en, which is correct and what it should be
  //do_action('wpml_switch_language', $_GET['wpml_lang']); // Here I'm trying to switch the language, but it does not do anything


  $scripts = array();

  // Script to be loaded before any cookie check
  $scripts["header"][] = get_field( 'cookies_allowed_header_scripts_before_all', 'options' );
  $scripts["footer"][] = get_field( 'cookies_allowed_footer_scripts_before_all', 'options' );

  if(get_cookies_allowed_level() >= 0 ){
    //$scripts["header"][] = locate_template('parts/cookies_allowed_header_scripts.php');
    $scripts["header"][] = get_field( 'cookies_allowed_header_scripts_1', 'options' );

    //$scripts["footer"][] = locate_template('parts/cookies_allowed_footer_scripts.php');
    $scripts["footer"][] = get_field( 'cookies_allowed_footer_scripts_1', 'options' );
  }
  if(get_cookies_allowed_level() >= 2){
    //$scripts["header"][] = locate_template('parts/cookies_allowed_header_scripts.php');
    $scripts["header"][] = get_field( 'cookies_allowed_header_scripts_2', 'options' );

    //$scripts["footer"][] = locate_template('parts/cookies_allowed_footer_scripts.php');
    $scripts["footer"][] = get_field( 'cookies_allowed_footer_scripts_2', 'options' );
  }
  if(get_cookies_allowed_level() >= 3){
    //$scripts["header"][] = locate_template('parts/cookies_allowed_header_scripts.php');
    $scripts["header"][] = get_field( 'cookies_allowed_header_scripts_3', 'options' );

    //$scripts["footer"][] = locate_template('parts/cookies_allowed_footer_scripts.php');
    $scripts["footer"][] = get_field( 'cookies_allowed_footer_scripts_3', 'options' );
  }

  // Script to be loaded after any cookie check
  $scripts["header"][] = get_field( 'cookies_allowed_header_scripts_after_all', 'options' );
  $scripts["footer"][] = get_field( 'cookies_allowed_footer_scripts_after_all', 'options' );

  //         print_r($scripts);
  //wp_send_json($scripts);
  echo json_encode($scripts);
  die();
}


add_action( 'admin_notices', 'install_and_activate_plugins' );
function install_and_activate_plugins(){
  global $wp;
  if ( ! class_exists( 'acf_code_field' ) && current_user_can( 'manage_options') /* && $installing == true  */) {
    $current_request = add_query_arg($_GET,$wp->request);
    $plugin_install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=acf-code-field'), 'install-plugin_acf-code-field');
    $installing = (isset($current_request) && strpos($plugin_install_url, $current_request) == true ) ? true : false; // needs work......
    //print_r($plugin_install_url);
    ?>
    <div class="notice notice-warning">
      <h3><?php esc_html_e( 'Cookies Allowed plugin', 'gravity_theme' ); ?></h3>
      <p><?php _e( '<a href="'.$plugin_install_url.'">Instaleer acf-code-field</a>, dit is nodig om de cookies allowed backend te laten werken', 'gravity_theme' ); ?></p>
    </div>
    <?php
  }
}



// Add ACF json files for the back-end cookie settings
add_filter('acf/settings/load_json', 'my_acf_json_cookies_allowed');
function my_acf_json_cookies_allowed( $paths ) {

    // append path to child theme
    $paths[] = get_stylesheet_directory() . '/includes/cookies-allowed/acf-json';
      //to parent
    $paths[] = get_template_directory() . '/includes/cookies-allowed/acf-json';

    // return
    return $paths;
}





/*
*   Get the cookies_allowed_level
*   args: no args
*   expected return:  0, 1, 2, 3
*   usage: get_cookies_allowed_level()
*/
function get_cookies_allowed_level(){
  if(isset($_COOKIE['cookie_allowed_level']) ):
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
if(function_exists('acf_add_options_page')){
  acf_add_options_page(array(
    'page_title'    => 'Cookie Opties',
    'menu_title'    => 'Cookie Opties',
    'menu_slug'     => 'cookie-options',
    'position'      => false,
    'icon_url'      => 'dashicons-art',
    'redirect'      => false,
  ));
}



/*
*   Build the html for the cookie notice and modal
*   args: no args
*   expected return: cookie html
*   usage:
*/
function get_cookies_allowed_html(){
  $html = '';
  ob_start();

  $highest_cookie_allowed_level   = (get_field('highest_cookie_allowed_level', 'options')) ? get_field('highest_cookie_allowed_level', 'options') : 3;
  if(class_exists('NumberFormatter')){
    $numbertoword               = new NumberFormatter("nl", NumberFormatter::SPELLOUT);
    $highest_cookie_word        = $numbertoword->format($highest_cookie_allowed_level);
  }
  $policy_page_url                = '#';
  $previous_cookie_level          = get_cookies_allowed_level();
  $acf_cookie_modal_text          = get_field('cookie_modal_text', 'options');
  $default_cookie_modal_text      = sprintf( __( '<h4>What are cookies?</h4>\n
                                    <p>Cookies are small files that are placed by us on your computer, tablet or smartphone in order to use a website properly. Some cookies are necessary for optimal use of the website. Some cookies are extra.</p>\n
                                    <h4>Manage your cookie settings</h4>\n
                                    <p>Functional cookies are needed to use the website, which is why they are always on. For an optimal online experience, we recommend to enable additional cookies</p>\n
                                    <p>More information about the different types of cookies and their effect can be found in our <a href="%s">Cookie Policy</a> page.</p>', 'gravity_theme' ), $policy_page_url );

  $acf_cookie_notice_text         = get_field('cookie_notice_text', 'options');
  $default_cookie_notice_text     = sprintf( __( '<p> %s uses cookies to optimize your experience on this website. By using this website you automatically agree to the use of functional cookies and anonymous Analytic cookies.</p>', 'gravity_theme'), $_SERVER["SERVER_NAME"] );


  $acf_highest_cookie_notice_text         = get_field('highest_cookie_notice_text', 'options');
  $default_highest_cookie_notice_text     = __( '<p>We also use user specific analytic and marketing cookies, by clicking on \'Allow cookies\' you also agree to the use of these cookies. Go to <a href="#" class="js-cookie-modal"> Settings </a> to manage your cookies on this website.</p>', 'gravity_theme' );

?>
    <div id="cookies-allowed" data-page-reload="<?php echo get_field( 'cookies_allowed_reload_page', 'options' ) ? 'true' : 'false'; ?>">
        <div id="cookie-notice" class="cookie-notice" data-highest-cookie-allowed-level="<?php echo $highest_cookie_allowed_level ?>">
            <div class="cookie-notice__container">
                <div class="cookie-notice__wrapper">
                    <div class="cookie-notice__content">
                        <?php // echo get_cookies_allowed_level(); ?>
                        <?php if(get_cookies_allowed_level() < 1): ?>
                            <?php echo empty($acf_cookie_notice_text) ? $default_cookie_notice_text : $acf_cookie_notice_text; ?>
                        <?php else: ?>
                            <?php echo empty($acf_highest_cookie_notice_text) ? $default_highest_cookie_notice_text : $acf_highest_cookie_notice_text; ?>
                        <?php endif; ?>
                    </div>
                    <div class="cookie-notice__buttons">
                        <button class="cookie__button cookie__button--opacity" onclick="allowCookies(<?php echo $highest_cookie_allowed_level ?>);"><?php esc_html_e( 'Allow cookies', 'gravity_theme' ); ?></button>
                    <?php if(get_cookies_allowed_level() < 1): ?>
                        <button class="cookie__button" onclick="toggleCookieModal();"><?php esc_html_e( 'Settings', 'gravity_theme' ); ?></button>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>


        <div class="cookie-modal">
            <div class="cookie-modal__backdrop js-cookie-modal"></div>
            <div class="cookie-modal__wrapper">
                <div class="cookie-modal__content">
                    <h3 class="cookie-modal__title"><?php esc_html_e( 'Manage your settings', 'gravity_theme' ); ?></h3>
                    <div class="cookie-modal__entry">
                        <?php echo empty($acf_cookie_modal_text) ? $default_cookie_modal_text : $acf_cookie_modal_text; ?>
                    </div>
                    <div class="cookie-modal__entry">
                        <h4><?php esc_html_e( 'Cookie types:', 'gravity_theme' ); ?></h4>
                        <div class="cookie-modal__checkbox__wrapper">
                            <input class="cookie-modal__checkbox" id="allow-cookies-check1" type="checkbox" checked="checked" disabled onclick="allowCookies(1);">
                            <label class="cookie-modal__label" for="allow-cookies-check1"><?php esc_html_e( 'Functional & Analytic cookies (anonymous)', 'gravity_theme' ); ?></label>
                        </div>
                    <?php if($highest_cookie_allowed_level >= 2): ?>
                        <div class="cookie-modal__checkbox__wrapper">
                            <input class="cookie-modal__checkbox" id="allow-cookies-check2" type="checkbox" <?php if( is_cookies_allowed_level(2) || is_cookies_allowed_level(3) ) echo('checked') ?> onclick="if(this.checked){allowCookies(2)}else{allowCookies(1)};">
                            <label class="cookie-modal__label" for="allow-cookies-check2"><?php esc_html_e( 'Analytic cookies (user specific)', 'gravity_theme' ); ?></label>
                        </div>
                    <?php endif; ?>
                    <?php if($highest_cookie_allowed_level == 3): ?>
                        <div class="cookie-modal__checkbox__wrapper">
                            <input class="cookie-modal__checkbox" id="allow-cookies-check3" type="checkbox" <?php if( is_cookies_allowed_level(3) ) echo('checked') ?> onclick="if(this.checked){allowCookies(3)}else{allowCookies(2)};">
                            <label class="cookie-modal__label" for="allow-cookies-check3"><?php esc_html_e( 'Marketing & Advertising cookies', 'gravity_theme' ); ?></label>
                        </div>
                    <?php endif; ?>
                    </div>
                    <div class="cookie-modal__entry cookie-modal__buttons">
                        <button class="cookie__button cookie__button--large cookie__button--success" onclick="toggleCookieModal();"><?php esc_html_e( 'Save', 'gravity_theme' ); ?></button>
                        <button class="cookie__button cookie__button--large cookie__button--ghost js-cookie-modal" onclick="allowCookies(<?php echo $previous_cookie_level ?>);"><?php esc_html_e( 'Cancel', 'gravity_theme' ); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>


<?php
  $html .= ob_get_clean();

  return $html;
}