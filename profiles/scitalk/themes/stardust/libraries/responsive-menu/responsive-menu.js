(function ($) {
  // Store our function as a property of Drupal.behaviors.
  Drupal.behaviors.starlight_mobile_menus = {
   attach: function (context, settings) {

     // 'push_left' also works here
     menu_setting = 'push_right';
     // Override this file using libraries-override to change this breakpoint
     menu_breakpoint = 960;

     if (context == document) {
       // Get all the blocks in the header region, except the branding block
       header_blocks = $('.region-header .block:not(".block-system-branding-block")');
       console.log(header_blocks);
     }

     // Build the menu if the browser with is smaller than the breakpoint
     if (($(window).width() <= menu_breakpoint) && ($('.region-branding .menu--toggle').length == 0)) {
       buildMenu();
     }

     // Make the mobile menu
     function buildMenu() {

       // Create the toggle icon
       if ($('.menu--toggle', $('.region-header')).length == 0) {
         $($('.region-header'), context).append('<button class="menu--toggle"><svg class="header-fg-fill" viewBox="0 0 5.4651985 4.6302084" height="1.6rem" width="2rem"> <path d="M 0 0 L 0 2.5 L 20.65625 2.5 L 20.65625 0 L 0 0 z M 0 7.5 L 0 10 L 20.65625 10 L 20.65625 7.5 L 0 7.5 z M 0 15 L 0 17.5 L 20.65625 17.5 L 20.65625 15 L 0 15 z " transform="scale(0.26458333)" /> </svg></button>');
       }
       $('body').addClass('responsive-menu-active menu--' + menu_setting);

       // Create an empty div for the menu
       if ($('#menu-container').length == 0) {
         $('body').prepend('<div id="menu-container" class="responsive-menu menu--' + menu_setting + '"></div>');
       }

       // create the close toggle element
       if ($('#menu-container .menu--close').length == 0) {
         $('#menu-container', context).prepend('<span class="menu--close menu--toggle menu--icon"><svg class="menu-caret" width=".8rem" height="1.6rem" viewBox="0 0 4.9516669 8.4939145" version="1.1" ><g transform="translate(-65.253826,-87.561633)"><path d="m 65.959261,87.561633 c 0.228262,0.03664 0.347541,0.266383 0.515668,0.405013 1.215628,1.215628 2.431257,2.431257 3.646885,3.646885 0.197952,0.18869 0.0027,0.413189 -0.152987,0.544557 -1.271261,1.271261 -2.542521,2.542521 -3.813781,3.813781 -0.188693,0.197949 -0.413188,0.0027 -0.544557,-0.152987 -0.13101,-0.161758 -0.380353,-0.282064 -0.351645,-0.520555 0.09613,-0.218679 0.313758,-0.354713 0.468436,-0.53307 0.985313,-0.985313 1.970627,-1.970627 2.955941,-2.955941 -1.115121,-1.115122 -2.230243,-2.230243 -3.345364,-3.345365 -0.198575,-0.189385 -0.0031,-0.412969 0.152987,-0.544556 0.149713,-0.123798 0.26057,-0.335285 0.468417,-0.357762 z" /></g></svg></span>');
       }

       // Move the blocks
       header_blocks.detach().appendTo($('#menu-container'));

     }

     // Put the menu back to the standard (desktop) layout
     function destroyMenu() {
       header_blocks.detach();
       header_blocks.appendTo($('.region-header'));

       $('.menu--toggle').remove();
       $('body').removeClass('responsive-menu-active');
     }

     // Toggle the menu open/closed
     $(document).on('click', '.menu--toggle', function() {
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
       if (($(window).width() <= menu_breakpoint) && ($('.region-branding .menu--toggle').length == 0)) {
         buildMenu();
       }
       // Otherwise, if it's greater than the menu breakpoint and ???
       else if ($(window).width() > menu_breakpoint) {
         destroyMenu($('.menu-region'));
       }
     }

     // Second level menu behaviour
     // @todo: This doesn't work after resize, only if page is reloaded
     $('li.expanded .menu-trigger', context).click(function(e) {
       e.preventDefault();
       $(this).toggleClass('expanded');
       $(this).siblings('.menu', context).slideToggle();
     });

   }
  };
 }(jQuery));
