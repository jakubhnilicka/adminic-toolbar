(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.adminicToolbar = {
    attach: function (context) {
      $('.toolbar__primary a').on('click', function (e) {
        e.preventDefault();
        var $tab = $(this);
        var tabId = $tab.attr('id');
        var tabKey = tabId.substring(4);
        var sectinWrapperId = 'toolbar-' + tabKey;
        if ($('#' + sectinWrapperId).length > 0){
          $('.toolbar_secondary_wrapper.active').removeClass('active');
          $('#' + sectinWrapperId).addClass('active');
        }
      });
    }
  };

}(jQuery, Drupal));
