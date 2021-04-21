function setCookie(cookiename, cookievalue, exdays) {
  var d = new Date();
  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
  var expires = 'expires=' + d.toUTCString();
  document.cookie = cookiename + '=' + cookievalue + '; ' + expires + '; path=/';
}

function getCookie(cookiename) {
  var name = cookiename + '=';
  var ca = document.cookie.split(';');
  for (var i = 0; i < ca.length; i++) {
    var c = ca[i];
    while (c.charAt(0) == ' ') c = c.substring(1);
    if (c.indexOf(name) != -1) return c.substring(name.length, c.length);
  }
  return '';
}

function allowCookies(cookieLevel) {
  jQuery(window).unbind('scroll', handleCookieScrollEvent);
  previous_cookie_allowed_level = getCookie('cookie_allowed_level');
  highestCookieAllowedLevel = jQuery('#cookie-notice').data('highest-cookie-allowed-level');

  if (cookieLevel == 1 && previous_cookie_allowed_level != 1) {
    jQuery('#cookie-notice').show();
    jQuery('body').toggleClass('cookie-notice--open', true);

    setCookie('cookie_allowed_level', 1, 30);
    jQuery('.cookie-modal').find('#allow-cookies-check2, #allow-cookies-check3').prop('checked', false);
    console.log('cookie_allowed_level is nu gezet op 1');
  }
  if (cookieLevel == 2 && previous_cookie_allowed_level != 2) {
    jQuery('#cookie-notice').show();
    jQuery('body').toggleClass('cookie-notice--open', true);
    //Set cookie
    setCookie('cookie_allowed_level', 2, 30);

    //hide level 1 notifcation
    jQuery('.cookie-modal').find('#allow-cookies-check2').prop('checked', true);
    jQuery('.cookie-modal').find('#allow-cookies-check3').prop('checked', false);
    jQuery('#cookie-notice').find('#cookie-notification-level-2').fadeOut(600);
    console.log('cookie_allowed_level is nu gezet op 2');
  }
  if (cookieLevel == 3 && previous_cookie_allowed_level != 3) {
    //Set cookie
    setCookie('cookie_allowed_level', 3, 30);

    //hide level 1 notifcation
    jQuery('.cookie-modal').find('#allow-cookies-check2, #allow-cookies-check3').prop('checked', true);
    console.log('cookie_allowed_level is nu gezet op 3');
  }
  if (cookieLevel >= highestCookieAllowedLevel) {
    jQuery('#cookie-notice').fadeOut(600);
    jQuery('body').toggleClass('cookie-notice--open', false);
    cookiebarOffset(600, '0');
  }
}

function cookiebarOffset(timeoutTime, pxOffset) {
  if (!timeoutTime) {
    timeoutTime = 1000;
  }
  setTimeout(function () {
    if (!pxOffset) {
      pxOffset = jQuery('.cookie-notice').outerHeight(true);
    }
    jQuery('html').animate({'padding-bottom': pxOffset}, 'slow');
  }, timeoutTime);
}

function toggleCookieModal() {
  jQuery('body').toggleClass('cookie-modal--open');
  if (!jQuery('body').hasClass('cookie-modal--open') && jQuery('#cookies-allowed').attr('data-page-reload') === 'true') {
    setTimeout(function () {
      location.reload();
    }, 600);
  }
}

var handleCookieScrollEvent = function () {
  //This is just wrong. Check if you actualy scrolled, not if you load the page with an offset
  if (!(getCookie('cookie_allowed_level') >= 2) && jQuery(window).scrollTop() > 200) {
    allowCookies(2);
  }
};

if (!(getCookie('cookie_allowed_level') >= 1) && document.referrer.indexOf(window.location.hostname) != -1) {
  var referrer = document.referrer;

  allowCookies(1);
}

$(document).ready(function () {
  jQuery.post(
    ajaxUrl.url, {
      'action': 'cookies_allowed_html'
    },
    function (response) {
      //console.log('load cookie html: ' + response);
      jQuery('#cookies-allowed').replaceWith(response);

      cookieLevel = getCookie('cookie_allowed_level');
      highestCookieAllowedLevel = jQuery('#cookie-notice').data('highest-cookie-allowed-level');

      if (cookieLevel < highestCookieAllowedLevel) {
        jQuery('#cookie-notice').show();
        cookiebarOffset();
      }
    },
  );

  jQuery.post(
    ajaxUrl.url, {
      'action': 'get_cookies_allowed_scripts'
    },
    function (response) {
      var responseJSON = response;

      var header_scripts = '';
      var footer_scripts = '';

      if (responseJSON.header) {
        for (var i = 0; i < responseJSON.header.length; i++) {
          header_scripts += responseJSON.header[i] + '\n';
        }
      }
      if (responseJSON.footer) {
        for (var i = 0; i < responseJSON.footer.length; i++) {
          if (responseJSON.footer[i]) footer_scripts += responseJSON.footer[i] + '\n';
        }
      }

      jQuery('head').append(header_scripts);
      jQuery('#cookies-allowed-footer-scripts').html(footer_scripts);
    },
  );

  jQuery(function ($) {
    cookieLevel = getCookie('cookie_allowed_level');
    highestCookieAllowedLevel = $('#cookie-notice').data('highest-cookie-allowed-level');

    //Check if level 3 cookies are used and if not yet accepted show the banner
    if (cookieLevel < highestCookieAllowedLevel) {
      $('#cookie-notice').show();
      cookiebarOffset();
      $('body').toggleClass('cookie-notice--open', true);
    }

    $('html').on('click', '.js-cookie-modal', function (e) {
      e.preventDefault();
      toggleCookieModal();
    });

  });
});
