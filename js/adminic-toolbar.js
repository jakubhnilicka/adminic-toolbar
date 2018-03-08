(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.adminicToolbar = {
    attach: function (context) {
      var $toolbarSecondary = $('.toolbar__secondary');
      var $body = $('body');
      var compactBreakpoint = window.matchMedia("only screen and (min-width: 60em)");
      compactBreakpoint.addListener(setBodyPadding);
      setBodyPadding(compactBreakpoint);

      $('.nano').nanoScroller();

      $('.tab').on('click', function (e) {
        $('.tab').removeClass('active');

        var $tab = $(this);
        var tabId = $tab.attr('id');
        var tabKey = tabId.substring(5);
        var $sectionWrapper = $('#toolbar-' + tabKey);
        $tab.addClass('active');
        $('.wrapper.active').css('z-index', 999).removeClass('active');

        if ($sectionWrapper[0] !== undefined) {
          e.preventDefault();
          $sectionWrapper.css('z-index', 999).addClass('active');
          showSecondaryToolbar();
        }
        else {
          hideSecondaryToolbar()
        }
      });

      $('.toolbar__header .close').on('click', function (e) {
        $('.wrapper.active').removeClass('active');
        hideSecondaryToolbar();
      });

      function showSecondaryToolbar() {
        var $tabActive = $('.tab.active');

        if ($tabActive.length > 0) {
          var tabId = $tabActive.attr('id');
          var tabKey = tabId.substring(5);
          var $sectionWrapper = $('#toolbar-' + tabKey);
          if ($sectionWrapper[0] !== undefined) {
            $sectionWrapper.addClass('active');
            $toolbarSecondary.show();
          }
          else {
            hideSecondaryToolbar();
          }
        }
        setBodyPadding(compactBreakpoint);
      }

      function hideSecondaryToolbar() {
        $toolbarSecondary.hide();
        setBodyPadding(compactBreakpoint);
      }

      function setBodyPadding(compactBreakpoint) {
        if (compactBreakpoint.matches) {
          var $tabActive = $('.tab.active');
          if ($tabActive.length > 0) {
            var tabId = $tabActive.attr('id');
            var tabKey = tabId.substring(5);
            var $sectionWrapper = $('#toolbar-' + tabKey);
            if ($sectionWrapper[0] !== undefined) {
              $sectionWrapper.addClass('active');
              $body.addClass('adminic-toolbar-secondary');
            }
          }
        }
        else {
          $body.removeClass('adminic-toolbar-secondary');
        }
      }
    }
  };

}(jQuery, Drupal));
