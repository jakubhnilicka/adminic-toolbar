(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.adminicToolbar = {
    attach: function (context) {
      var $toolbarSecondary = $('.toolbar__secondary');
      var $body = $('body');

      if ($('.toolbar_secondary_wrapper.active').length > 0) {
        showSecondaryToolbar();
      }
      else {
        hideSecondaryToolbar()
      }

      $('.toolbar__primary a').not('.dropdown a').on('click', function (e) {
        $('.toolbar__primary a').removeClass('active');

        var $tab = $(this);
        var tabId = $tab.attr('id');
        var tabKey = tabId.substring(4);
        var $sectionWrapper = $('#toolbar-' + tabKey);
        $tab.addClass('active');
        if ($sectionWrapper[0] !== undefined){
          e.preventDefault();

          $('.toolbar_secondary_wrapper.active').removeClass('active');
          $sectionWrapper.addClass('active');
          showSecondaryToolbar();
        }
        else {
          $('.toolbar_secondary_wrapper.active').removeClass('active');
          hideSecondaryToolbar()
        }
      });

      // Dropdown toggle
      $('.dropdown-toggle').on('click', function(e){
        $(this).next('.dropdown').toggle();
      });

      $(document).on('click', function(e) {
        var target = e.target;
        if (!$(target).is('.dropdown-toggle') && !$(target).parents().is('.dropdown-toggle')) {
          $('.dropdown').hide();
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
