(function ($, Drupal) {

    'use strict';

    Drupal.behaviors.drd = {
        attach: function (context) {
            $('.drdt').on('click', function (e) {
                $(this).parent().toggleClass('open');
            });

            $(document).on('click', function (e) {
                var target = e.target;
                if (!$(target).is('.drdt') && !$(target).parents().is('.drdt')) {
                    $('.drd').removeClass('open');
                }
            });

        }
    };

}(jQuery, Drupal));
