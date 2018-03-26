(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.behaviors.adminicToolbar = {
    attach: function (context) {
      var $toolbarSecondary = $('.toolbar__secondary');
      var $body = $('body');
      var compactBreakpoint = window.matchMedia("only screen and (min-width: 60em)");
      compactBreakpoint.addListener(setBodyPadding);

      $('.nano').nanoScroller();


      var activeTab = drupalSettings.adminic_toolbar.active_tab;
      if (activeTab) {
        $('#tab--' + activeTab).addClass('active');
        $('#toolbar-' + activeTab).addClass('active');
      }
      setBodyPadding(compactBreakpoint);

      var activeLink = drupalSettings.adminic_toolbar.active_link;
      if (activeLink) {
        $('.toolbar_links a[href="' + activeLink + '"]').addClass('active');
      }


      $('.tab').on('click', function (e) {
        // If cmd + click go directly to the href.
        if (e.metaKey) {
          e.preventDefault();
          window.location.replace($(this).attr('href'));
          return true;
        }

        var $tab = $(this);
        var tabId = $tab.attr('id');
        var tabKey = tabId.substring(5);
        var $sectionWrapper = $('#toolbar-' + tabKey);

        $('.tab').removeClass('active');
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
        $('.wrapper').removeClass('active');
        $body.removeClass('adminic-toolbar-secondary');
      }

      function setBodyPadding(compactBreakpoint) {
        if (compactBreakpoint.matches) {
          var $tabActive = $('.tab.active');
          if ($tabActive.length > 0) {
            var tabId = $tabActive.attr('id');
            var tabKey = tabId.substring(5);
            var $sectionWrapper = $('#toolbar-' + tabKey);
            if ($sectionWrapper[0] !== undefined && $sectionWrapper.hasClass) {
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

}(jQuery, Drupal, drupalSettings));
