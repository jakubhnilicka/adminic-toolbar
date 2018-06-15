(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.drd = {
    attach: function (context) {
      var openClass = 'open';
      $('.drdt', context).once('drd').on('mousedown', function (e) {
        var $drd = $(this).closest('.drd');
        $drd.toggleClass(openClass);
      });

      $(document, context).once('drdremove').on('click', function (e) {
        var target = e.target;
        if (!$(target).is('.drdt') && !$(target).parents().is('.drdt')) {
          $('.drd').removeClass(openClass);
        }
      });

    }
  };

}(jQuery, Drupal));
