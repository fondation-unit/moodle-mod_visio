define(['jquery', 'core/ajax', 'core/notification', 'core/str'],
function($, ajax, notification, str) {

    var ViewParticipation = function(selector, visioid, presentStr, missingStr) {
        this._region = $(selector);
        this._visioid = visioid;
        this._presentStr = presentStr;
        this._missingStr = missingStr;
        this._presence = [];
        this._checkboxes = $(selector).find('tbody').find('tr').find('[data-element="visio-checkbox"]');

        this._checkboxes.on('click', this._toggleUserPresence.bind(this));
        this._setup();
        this._getUsersPresence();
    };

    ViewParticipation.prototype._setup = function() {
        if ($('#user-notifications').length) {
            $('#user-notifications').appendTo('#notifications-area');
        }
    };

    ViewParticipation.prototype._getUsersPresence = function() {
        ajax.call([{
            methodname: 'mod_visio_get_presence',
            args: { visioid: this._visioid },
            done: function(data) {
                this._presence = data;
                this._setPresenceInTable();
            }.bind(this),
            fail: notification.exception
        }]);
    };

    ViewParticipation.prototype._setPresenceInTable = function() {
        var rows = this._region.find('tbody').find('tr');
        this._presence.forEach(function(element) {
            var row = rows.find('[data-element="visio-checkbox"][value=' + element.userid + ']');
            if (row && element.value == 1) {
                row.prop('indeterminate', true);
            } else if (row && element.value == 2) {
                row.prop('checked', true);
            } else {
                row.prop('checked', false);
            }
        });
    };

    ViewParticipation.prototype._toggleUserPresence = function(e) {
        var elem = e.currentTarget;
        var value = $(elem).val();
        if (value) {
            var fname = this._region.find('[data-element="visio-firstname"][value=' + value + ']').text();
            var lname = this._region.find('[data-element="visio-lastname"][value=' + value + ']').text();
            var name = fname + ' ' + lname;
            this._attestUserPresence(value, $(elem).prop('checked'), name);
        }
    };

    ViewParticipation.prototype._attestUserPresence = function(userid, state, username) {
        ajax.call([{
            methodname: 'mod_visio_set_presence',
            args: {
                visioid: this._visioid,
                userid: userid,
                value: state == true ? 2 : 0
            },
            done: function(data) {
                var string = {
                    name: username,
                    status: state == true ? this._presentStr : this._missingStr,
                    time: data
                };

                str.get_string('presence_updated', 'visio', string)
                    .done(function(s) {
                    notification.addNotification({
                        message: s,
                        type: 'success'
                    });
                });
            }.bind(this),
            fail: notification.exception
        }]);
    };

    return ViewParticipation;
});