/**
 * @author Piotr Mrowczynski <piotr@owncloud.com>
 *
 * @copyright Copyright (c) 2017, ownCloud GmbH
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

(function( $ ) {
 
    // ocDiagnosticsAddServer
    $.fn.ocDiagnosticsAddServer = function() {

        /* Go easy on jquery and define some vars
         ========================================================================== */

        var $wrapper = $(this),
            $inpEnableDiagnostics = $wrapper.find("#enableDiagnostics"),
            $inpDiagnosticLogLevel = $wrapper.find("#diagnosticLogLevel"),
            $cleanDiagnosticLog = $wrapper.find("#cleanDiagnosticLog"),
            $diagnosticLog = $wrapper.find("#diagnosticLog");

        
        /* Interaction
         ========================================================================== */

        $inpEnableDiagnostics.on("change", function() {
            var checked = $(this).is(':checked');
            $.post(
                OC.generateUrl('/apps/diagnostics/setdebug'),
                {
                    enable: checked
                }
            ).done(function (data) {
                $diagnosticLog.toggleClass('hidden', !checked);
            });
        });

        $inpDiagnosticLogLevel.on("change", function() {
            var value = $(this).val();
            $.post(
                OC.generateUrl('/apps/diagnostics/setdiaglevel'),
                {
                    logLevel: value
                }
            );
        });

        $cleanDiagnosticLog.on("click", function()
        {
            $.post(
                OC.generateUrl('/apps/diagnostics/log/clean'),
                {}
            ).done(function (data) {
                location.reload();
            });
        });
    };




 
})( jQuery );

$(document).ready(function () {

    $('#ocDiagnosticsSettings').ocDiagnosticsAddServer();
});
