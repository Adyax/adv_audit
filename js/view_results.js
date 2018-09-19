/**
 * @file
 */

(function ($, Drupal) {
    Drupal.behaviors.advAuditViewResult = {
        attach: function attach(context, settings) {

            $('a.report-view-detailed').click(function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                var self = $(this);

                $('div.detailed-view[category-id="' + self.data('category-id') + '"]').toggle();
            });

            $('.report-items').once('openClose').on('click', '.report-title', function (event) {
                event.preventDefault();
                $(this).closest('.audit-reason').find('.report-content').slideToggle();
                $(this).closest('.audit-reason').find('.report-title').toggleClass('active');
            });
            $('.active').closest('.audit-reason').find('.report-content').slideDown();

        }
    };
})(jQuery, Drupal);
