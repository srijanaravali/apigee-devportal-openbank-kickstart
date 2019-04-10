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

})(jQuery, Drupal);
