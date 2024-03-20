jQuery(document).ready(function($) {
    jQuery('#user-exports').click(function(e) {
        $(this).prop('disabled', true);
        var data = {
            'action': 'export_users'
        };
        jQuery.post(wp_bulk_manage.ajaxurl, data, function(response) {
            jQuery('#email-queue-count').text(response + ' user(s) exported');
        });
    });
});
