jQuery(document).ready(function($) {
    jQuery('#user-exports').click(function(e) {
        $(this).prop('disabled', true);
        var data = {
            'action': 'export_users'
        };
        jQuery.post(wp_bulk_manage.ajaxurl, data, function(response) {
            response = JSON.parse(response);
            jQuery('#user-export-count').text(response.user_count + ' user(s) exported');
            jQuery('#download-user-exports').prop('disabled', false);
            jQuery('#export-filename-id').attr('value', response.export_name);
        });
    });

    jQuery('#delete-users').submit(function(e){
        e.preventDefault();
        var form = document.getElementById('delete-users');
        var formData = new FormData(form);
        jQuery.ajax({
            url: wp_bulk_manage.ajaxurl + '?action=delete_user_upload',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                alert('Your form has been sent successfully.');
            },
            error: function (xhr, status, error) {
                alert('Your form was not sent successfully.');
                console.error(error);
            }
        });
    });

    jQuery('#export-download').submit(function(e){
        e.preventDefault();
        var inputs = $('#export-download :input');
        var data = {
            'action': 'download_user_export',
            'export_name' : inputs[0].value
        };
        jQuery.get(wp_bulk_manage.ajaxurl, data, function(blob, status, xhr) {
            var filename = "";
            var disposition = xhr.getResponseHeader("Content-Disposition");
            if(disposition && disposition.indexOf("filename") !== -1){
                splitted = disposition.split("filename=");
                filename = splitted[splitted.length-1];
                // strip out some header garbage....
                saveData(blob, filename.replace(/[";]+/g, ''));
            }
        });
    });

    const saveData = (function () {
        const a = document.createElement("a");
        document.body.appendChild(a);
        a.style = "display: none";
        return function (data, fileName) {
            const blob = new Blob([data], {type: "octet/stream"}),
                url = window.URL.createObjectURL(blob);
            a.href = url;
            a.download = fileName;
            a.click();
            window.URL.revokeObjectURL(url);
        };
    }());
});
