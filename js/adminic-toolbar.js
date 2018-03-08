(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.adminicToolbar = {
    attach: function (context) {
      var $toolbarSecondary = $('.toolbar__secondary');
      var $body = $('body');
      var compactBreakpoint = window.matchMedia("only screen and (min-width: 60em)");

      showSecondaryToolbar(compactBreakpoint);
      compactBreakpoint.addListener(showSecondaryToolbar);

      $('.nano').nanoScroller();

      $('.tab').on('click', function (e) {
        $('.tab').removeClass('active');

        var $tab = $(this);
        var tabId = $tab.attr('id');
        var tabKey = tabId.substring(5);
        var $sectionWrapper = $('#toolbar-' + tabKey);
        $tab.addClass('active');
        $('.wrapper.active').removeClass('active');

        if ($sectionWrapper[0] !== undefined) {
          e.preventDefault();
          $sectionWrapper.addClass('active');
          showSecondaryToolbar(compactBreakpoint);
        }
        else {
          hideSecondaryToolbar()
        }
      });

      $('.toolbar__header .close').on('click', function (e) {
        $('.wrapper.active').removeClass('active');
        hideSecondaryToolbar();
      });

      function showSecondaryToolbar(compactBreakpoint) {
        var $tabActive = $('.tab.active');

        if (compactBreakpoint.matches && $tabActive.length > 0) {
          var tabId = $tabActive.attr('id');
          var tabKey = tabId.substring(5);
          var $sectionWrapper = $('#toolbar-' + tabKey);
          if ($sectionWrapper[0] !== undefined) {
            $sectionWrapper.addClass('active');
            $body.addClass('adminic-toolbar-secondary');
            $toolbarSecondary.show();
          }
          else {
            hideSecondaryToolbar();
          }
        }
      }

      function hideSecondaryToolbar() {
        $toolbarSecondary.hide();
        $body.removeClass('adminic-toolbar-secondary');
      }

    }
  };

}(jQuery, Drupal));
