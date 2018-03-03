(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.adminicToolbar = {
    attach: function (context) {
      var $toolbarSecondary = $('.toolbar__secondary');
      var $body = $('body');

      if ($('.wrapper.active').length > 0) {
        showSecondaryToolbar();
      }
      else {
        hideSecondaryToolbar()
      }

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
          showSecondaryToolbar();
        }
        else {
          hideSecondaryToolbar()
        }
      });

      function showSecondaryToolbar() {
        $toolbarSecondary.show();
        $body.addClass('adminic-toolbar-secondary');
      }

      function hideSecondaryToolbar() {
        $toolbarSecondary.hide();
        $body.removeClass('adminic-toolbar-secondary');
      }
    }
  };

}(jQuery, Drupal));
