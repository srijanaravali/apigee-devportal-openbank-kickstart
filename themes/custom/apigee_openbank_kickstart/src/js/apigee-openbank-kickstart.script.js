import 'popper.js';
import 'bootstrap';

// Components.
// TODO: Break this into libraries.
import '../components/form/fieldset';
import '../components/card/collapsible-card';

(function($, Drupal) {
  Drupal.behaviors.side_menu = {
    attach: function(context, settings) {
      var pathName = location.pathname;
      $('.nav-link').each(function() {
        if ($(this).attr('href') == pathName) {
          $(this).parent().siblings().addClass('show');
          $(this).closest('li').addClass('expanded');
        }
      });

      $(context).find('.svg-inline--fa').once('icon-clicked').click(function() {
        var subMenu = $(this).parent().siblings();
        if (subMenu.hasClass('show')) {
          subMenu.removeClass('show');
          $(this).closest('li').removeClass('expanded');
        }
        else {
          subMenu.addClass('show');
          $(this).closest('li').addClass('expanded');
        }
      })
    }
  };

  Drupal.behaviors.block_scroll = {
    attach: function(context, settings) {
      if (location.hash) {
        var hash = location.hash.split('/');
        var openBlockElem = '#operations-default-' + hash[2];

        if ($(openBlockElem)) {
          $('html, body').animate({
            scrollTop: $(openBlockElem).offset().top - 90
          }, 'slow');
        }
      }

    }
  };

})(jQuery, Drupal);
