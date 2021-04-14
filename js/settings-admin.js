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

OC.Settings = OC.Settings || {};
OCA.DiagnosticUsers = _.extend(OC.Settings, {

    _cachedUsers: null,

    /**
     * Setup selection box for users selection.
     *
     * Values need to be separated by a pipe "|" character.
     * (mostly because a comma is more likely to be used
     * for users)
     *
     * @param $elements jQuery element (hidden input) to setup select2 on
     * @param {Array} [extraOptions] extra options hash to pass to select2
     * @param {Array} [options] extra options
     * @param {Array} [options.excludeAdmins=false] flag whether to exclude admin groups
     */
    setupUsersSelect: function($elements, extraOptions, options) {
        var self = this;
        if ($elements.length > 0) {
            // note: settings are saved through a "change" event registered
            // on all input fields
            $elements.select2(_.extend({
                placeholder: t('diagnostics', 'Users'),
                allowClear: true,
                multiple: true,
                separator: '|',
                query: _.debounce(function(query) {
                    var queryData = {};
                    if (self._cachedUsers && query.term === '') {
                        query.callback({results: self._cachedUsers});
                        return;
                    }
                    if (query.term !== '') {
                        queryData = {
                            pattern: query.term,
                            filterGroups: 1
                        };
                    }
                    $.ajax({
                        url: OC.generateUrl('/settings/users/users'),
                        data: queryData,
                        dataType: 'json',
                        success: function(data) {
                            var results = [];

                            $.each(data, function(i, userData) {
                                results.push({id:userData.name, displayname:userData.displayname});
                            });

                            if (query.term === '') {
                                // cache full list
                                self._cachedGroups = results;
                            }
                            query.callback({results: results});
                        }
                    });
                }, 100, true),
                id: function(element) {
                    return JSON.stringify(element);
                },
                initSelection: function(element, callback) {
                    var selection =
                        _.map(($(element).val() || []).split('|').sort(),
                            function(userJSON) {
                                var user = JSON.parse(userJSON);
                                return {
                                    id: user.id,
                                    displayname: user.displayname
                                };
                            });
                    callback(selection);
                },
                formatResult: function (element) {
                    return escapeHTML(element.displayname);
                },
                formatSelection: function (element) {
                    return escapeHTML(element.displayname);
                },
                escapeMarkup: function(m) {
                    // prevent double markup escape
                    return m;
                }
            }, extraOptions || {}));
        }
    }
});

(function( $ ) {

    // ocDiagnosticsAddServer
    $.fn.ocDiagnosticsAddServer = function() {

        /* Go easy on jquery and define some vars
         ========================================================================== */

        var $wrapper = $(this),
            $inpEnableDiagnostics = $wrapper.find("#enableDiagnostics"),
            $inpDiagnosticLogLevel = $wrapper.find("#diagnosticLogLevel"),
            $inpUseLoggingLocks = $wrapper.find("#useLoggingLocks"),
            $cleanDiagnosticLog = $wrapper.find("#cleanDiagnosticLog"),
            $diagnosticLog = $wrapper.find("#diagnosticLog"),
            $diagnosticUserList = $wrapper.find("#diagnosticUserList");


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
                $diagnosticLog.toggleClass('hidden', checked);
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

        $inpUseLoggingLocks.on("change", function() {
            var checked = $(this).is(':checked');
            $.post(
                OC.generateUrl('/apps/diagnostics/setlogginglocks'),
                {
                    enable: checked
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

        OCA.DiagnosticUsers.setupUsersSelect($diagnosticUserList);
        $diagnosticUserList.change(function(ev) {
            var users = ev.val || [];

            // Convert JSON user string to objects and add to JSON array
			var usersJsonArray = [];
			users.forEach( function(user) {
				usersJsonArray.push(JSON.parse(user));
			} );

			// Stringify JSON array and set diagnostic for users
            $.post(
                OC.generateUrl('/apps/diagnostics/setdiagnosticforusers'),
                {
					users: JSON.stringify(usersJsonArray)
                }
            );
        });
    };





})( jQuery );

$(document).ready(function () {

    $('#ocDiagnosticsSettings').ocDiagnosticsAddServer();
});
