<?php
/*
Plugin Name: Cookies Allowed
Description: Add front-end cookie notification bar, front-end cookie settings pannel, back-end Cookie management, back-end scripts manager page
Version: 1.1.1
Author: Pepijn Nichting
Author URI: https://gravity.nl
*/





/*
*   Description: enqueue cookies_allowed scripts
*   Args: none
*   Expected return:
*   Usage:
*/
if (!is_admin()) add_action("wp_enqueue_scripts", "enqueue_cookies_allowed_scripts", 11);
function enqueue_cookies_allowed_scripts() {
  //JS
  wp_register_script( 'cookies-allowed-js' , get_template_directory_uri() . '/includes/cookies-allowed/cookies-allowed.js', '', '1.1.0', true);
  wp_enqueue_script(  'cookies-allowed-js');

  // CSS
  wp_register_style( 'cookies-allowed-default-css' , get_template_directory_uri() . '/includes/cookies-allowed/cookies-allowed-default.css', '', null , 'all');
  if( get_field( 'cookies_allowed_default_css', 'options' )){
    wp_enqueue_style( 'cookies-allowed-default-css');
  }

  /* Telling the JS file where ajaxUrl is */
  wp_localize_script( 'cookies-allowed-js', 'ajaxUrl', array(
      'url' => admin_url() . 'admin-ajax.php',
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
  $scripts = array();

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
  //         print_r($scripts);
  //wp_send_json($scripts);
  echo json_encode($scripts);
  die();
}


add_action( 'admin_init', 'install_and_activate_plugins' );
function install_and_activate_plugins(){
  //include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
  if ( ! class_exists( 'acf_code_field' ) && current_user_can( 'manage_options' ) ){
    $plugin_install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=acf-code-field'), 'install-plugin_acf-code-field');
    //print_r($plugin_install_url);

    echo '<div class="error">';
      echo '<p><a href="'.$plugin_install_url.'"> Instaleer cf-code-field, dit is nodig om de cookies allowed backend te laten werken</a></p>';
    echo '</div>';


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
  $previous_cookie_level          = get_cookies_allowed_level();
  $acf_cookie_modal_text          = get_field('cookie_modal_text', 'options');
  $default_cookie_modal_text      ='
                        <h4>Wat zijn cookies?</h4>
                        <p>Cookies zijn kleine bestanden die door ons op je computer, tablet of smartphone worden geplaatst om een website goed te kunnen gebruiken. Sommige cookies zijn noodzakelijk voor een optimaal gebruik van de website. Sommige cookies zijn extra.</p>
                        <h4>Beheer zelf je cookie-instellingen</h4>
                        <p>Functionele cookies zijn nodig om de website te kunnen gebruiken, daarom staan deze altijd aan. Voor een optimale online ervaring, raden wij aan om extra cookies aan te zetten</p>

                        <p>Meer informatie over de verschillende soorten cookies en hun werking is te lezen in onze <a href="#">Cookie Policy</a>.</p>
        ';



  $acf_cookie_notice_text         = get_field('cookie_notice_text', 'options');
  $default_cookie_notice_text     ='
                            <p>
                                '.$_SERVER["SERVER_NAME"].' maakt gebruik van cookies om jouw ervaring met deze website te optimaliseren. Door het gebruik van deze website ga je automatisch akkoord met het gebruik van functionele cookies en anonieme analyse cookies.
                                </br>Wij maken ook gebruik van gebruiker specifieke analyse en marketing cookies, door op \'Sta cookies toe\' te klikken ga je ook akkoord met het gebruik van deze cookies.Ga naar <a href="#" class="js-cookie-modal">Instellingen</a> om je cookies op deze website te beheren.
                            </p>';

  $acf_highest_cookie_notice_text         = get_field('highest_cookie_notice_text', 'options');
  $default_highest_cookie_notice_text     ='
                            <p>
                                Wij maken ook gebruik van gebruiker specifieke analyse en marketing cookies, door op \'Sta cookies toe\' te klikken ga je ook akkoord met het gebruik van deze cookies.Ga naar <a href="#" class="js-cookie-modal">Instellingen</a> om je cookies op deze website te beheren.
                            </p>';
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
                        <button class="cookie__button cookie__button--opacity" onclick="allowCookies(<?php echo $highest_cookie_allowed_level ?>);">Sta cookies toe</button>
                    <?php if(get_cookies_allowed_level() < 1): ?>
                        <button class="cookie__button" onclick="toggleCookieModal();">Instellingen</button>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>


        <div class="cookie-modal">
            <div class="cookie-modal__backdrop js-cookie-modal"></div>
            <div class="cookie-modal__wrapper">
                <div class="cookie-modal__content">
                    <h3 class="cookie-modal__title">Kies je instellingen</h3>
                    <div class="cookie-modal__entry">
                        <?php echo empty($acf_cookie_modal_text) ? $default_cookie_modal_text : $acf_cookie_modal_text; ?>
                    </div>
                    <div class="cookie-modal__entry">
                        <h4>Soorten cookies:</h4>
                        <div class="cookie-modal__checkbox__wrapper">
                            <input class="cookie-modal__checkbox" id="allow-cookies-check1" type="checkbox" checked="checked" disabled onclick="allowCookies(1);">
                            <label class="cookie-modal__label" for="allow-cookies-check1">Functionele cookies & Analyse cookies (anoniem)</label>
                        </div>
                    <?php if($highest_cookie_allowed_level >= 2): ?>
                        <div class="cookie-modal__checkbox__wrapper">
                            <input class="cookie-modal__checkbox" id="allow-cookies-check2" type="checkbox" <?php if( is_cookies_allowed_level(2) || is_cookies_allowed_level(3) ) echo('checked') ?> onclick="if(this.checked){allowCookies(2)}else{allowCookies(1)};">
                            <label class="cookie-modal__label" for="allow-cookies-check2">Analyse cookies (gebruiker specifiek)</label>
                        </div>
                    <?php endif; ?>
                    <?php if($highest_cookie_allowed_level == 3): ?>
                        <div class="cookie-modal__checkbox__wrapper">
                            <input class="cookie-modal__checkbox" id="allow-cookies-check3" type="checkbox" <?php if( is_cookies_allowed_level(3) ) echo('checked') ?> onclick="if(this.checked){allowCookies(3)}else{allowCookies(2)};">
                            <label class="cookie-modal__label" for="allow-cookies-check3">Marketing & Advertentie cookies</label>
                        </div>
                    <?php endif; ?>
                    </div>
                    <div class="cookie-modal__entry cookie-modal__buttons">
                        <button class="cookie__button cookie__button--large cookie__button--success" onclick="toggleCookieModal();">Opslaan</button>
                        <button class="cookie__button cookie__button--large cookie__button--ghost js-cookie-modal" onclick="allowCookies(<?php echo $previous_cookie_level ?>);">Annuleren</button>
                    </div>
                </div>
            </div>
        </div>
    </div>


<?php
  $html .= ob_get_clean();

  return $html;
}