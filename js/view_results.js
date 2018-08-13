
(function ($, Drupal) {
    Drupal.behaviors.advAuditViewResult = {
        attach: function attach(context, settings) {
            var renderScoreStatusElement = $('.render-score-status');

            var point = renderScoreStatusElement.data('score-point');
            if (point >= 80) {
                renderScoreStatusElement.addClass('aq-circle--success');
            }
            else if (point <80 && point >=40) {
                renderScoreStatusElement.addClass('aq-circle--warning');
            }
            else {
                renderScoreStatusElement.addClass('aq-circle--danger');
            }

            $('a.report-view-detailed').click(function (e) {
                e.preventDefault();
                var self = $(this);

                $('div.detailed-view[category-id="' + self.data('category-id') + '"]').toggle();
            })
        }
    };
})(jQuery, Drupal);