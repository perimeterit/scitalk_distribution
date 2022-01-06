(function ($) {
  // Store our function as a property of Drupal.behaviors.
  Drupal.behaviors.scitalk_mobile_menus = {
   attach: function (context, settings) {

     // 'push_left' also works here
     menu_setting = 'push_right';
     menu_breakpoint = 959.999;

     if (context == document) {
       // Get all the elements we need to put into the mobil emenu
       nav_element = $('.left-nav-wrapper');
     }
     // Build the menu if the browser with is smaller than the breakpoint
     if (($(window).width() <= menu_breakpoint) && ($('.region-branding .menu-toggle').length == 0)) {
       buildMenu();
     }

     // Make the mobile menu
     function buildMenu() {
       // Add classes to the body tag
       $('body').addClass('responsive-menu-active menu--' + menu_setting);
       // Create an empty div for the menu
       if ($('#menu-container').length == 0) {
         $('body').prepend('<div id="menu-container" class="responsive-menu menu--' + menu_setting + ' " tabindex="-1"></div>');
       }
       // create the close toggle element
       if ($('#menu-container .menu--close').length == 0) {
         $('#menu-container', context).prepend('<button class="menu--close menu--icon" aria-expanded="true"><span class="visually-hidden">' + $('.menu-toggle span').html() +  '</span></button>');
       }

       // Move the blocks
       nav_element.detach().appendTo($('#menu-container'));
     }

     // Put the menu back to the standard (desktop) layout
     function destroyMenu() {
       nav_element.detach();
       nav_element.prependTo($('.page-wrapper'));
       $('body').removeClass('responsive-menu-active');
     }

     // Toggle the menu open/closed, from the hamburger icon or the close <
     $menu_toggle_buttons = $('.menu-toggle, .menu--close', context);
     $($menu_toggle_buttons).on('click', function() {
       if ($(this).attr('aria-expanded') == 'false') {
         $menu_toggle_buttons.attr('aria-expanded', 'true');
         // Also moves the focus to the first link item (for screen readers)
         $('#menu-container .menu--main').attr('tabindex','-1').focus();
         // Change the button text
         $toggle_text = $('.menu-toggle span').html();
         $(this).children('span').html($('.menu-toggle').attr('data-hide-text'));
       } else {
         // Close the menu
         $menu_toggle_buttons.attr('aria-expanded', 'false');
         $('.menu-toggle').focus();
         $menu_toggle_buttons.children('span').html($toggle_text);
       }
       $('body, #menu-container', context).toggleClass('menu--open');
     });

     // Fix layout when browser window is resized
     var resizeId;
     $(window).on('resize', function() {
       clearTimeout(resizeId);
       resizeId = setTimeout(doneResizing, 700);
     });

     function doneResizing() {
       // If it's smaller than the mobile menu breakpoint and the menu toggle doesn't exist, make it
       if (($(window).width() <= menu_breakpoint) && ($('.region-branding .menu-toggle').length == 0)) {
         buildMenu();
       }
       // Otherwise, if it's greater than the menu breakpoint and ???
       else if ($(window).width() > menu_breakpoint) {
         destroyMenu($('.menu-region'));
       }
     }
   }
  };

  // / Search toggle
  Drupal.behaviors.scitalk_search_toggle = {
    attach: function (context, settings) {

      $('.search-toggle', context).click(function() {
        if ($(this).attr('aria-expanded') == 'false') {
          $(this).attr('aria-expanded', 'true');
          // Also moves the focus to the first link item (for screen readers)
          $('.search-block-form').attr('tabindex','-1').toggleClass('open').focus();
          // Change the button text
          $toggle_text = $(this).children('span').html();
          $(this).children('span').html($(this).attr('data-hide-text'));
        } else {
          // Close the menu
          $(this).attr('aria-expanded', 'false').children('span').html($toggle_text).focus();
          $('.search-block-form').toggleClass('open');
        }
      });

    }
  };
}(jQuery));
