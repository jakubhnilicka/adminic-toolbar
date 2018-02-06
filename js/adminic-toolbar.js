(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.adminicToolbar = {
    attach: function (context) {
      $('.toolbar__primary a').on('click', function (e) {
        $('.toolbar__primary a').removeClass('active');

        var $tab = $(this);
        var tabId = $tab.attr('id');
        var tabKey = tabId.substring(4);
        var sectinWrapperId = 'toolbar-' + tabKey;
        $tab.addClass('active');

        if ($('#' + sectinWrapperId).length > 0){
          e.preventDefault();
          $('.toolbar_secondary_wrapper.active').removeClass('active');
          $('#' + sectinWrapperId).addClass('active');
        }
      });
      $('.toolbar_secondary_wrapper .toolbar__header a').on('click', function(e) {
        e.preventDefault();
        $(this).parent().parent().removeClass('active');
      });
    }
  };

}(jQuery, Drupal));
