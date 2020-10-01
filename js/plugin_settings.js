window.GFZohoCRMSettings = null;

(function ($) {
    GFZohoCRMSettings = function () {
        var self = this;

        this.init = function () {
            this.pageURL = gform_zohocrm_pluginsettings_strings.settings_url;

            this.bindDeauthorize();
        }

        this.bindDeauthorize = function () {
            // De-Authorize Zoho CRM.
            $('#gform_zohocrm_deauth_button').on('click', function (e) {
                e.preventDefault();

                // Get button.
                var $button = $('#gform_zohocrm_deauth_button');

                // Confirm deletion.
                if (!confirm(gform_zohocrm_pluginsettings_strings.disconnect)) {
                    return false;
                }

                // Set disabled state.
                $button.attr('disabled', 'disabled');

                // De-Authorize.
                $.ajax( {
                    async:    false,
                    url:      ajaxurl,
                    dataType: 'json',
                    data:     {
                        action: 'gfzohocrm_deauthorize',
                        nonce:  gform_zohocrm_pluginsettings_strings.nonce_deauthorize
                    },
                    success:  function ( response ) {
                        if ( response.success ) {
                            window.location.href = self.pageURL;
                        } else {
                            alert( response.data.message );
                        }

                        $button.removeAttr( 'disabled' );
                    }
                } );

            });
        }

        this.init();
    }

    $(document).ready(GFZohoCRMSettings);
})(jQuery);
