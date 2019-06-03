define(['jquery', 'core/ajax', 'core/notification'],
        function($, ajax, notification) {

    var ViewParticipation = function(selector, visioid) {
        this._region = $(selector);
        this._visioid = visioid;
        this._presence = [];
        this._checkboxes = $(selector).find('tbody').find('tr').find('[data-element="visio-checkbox"]');

        this._checkboxes.on('click', this._toggleUserPresence.bind(this));
        this._getUsersPresence();

        setInterval(this._getUsersPresence(), 5000);
    }

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
            var row = rows.find('[data-element="visio-checkbox"][value='+ element.userid +']');
            if (row && element.value == 1) {
                row.prop('indeterminate', true);
            } else if (row && element.value == 2) {
                row.prop('checked', true);
            } else {
                row.prop('checked', false);
            }
        });
    }

    ViewParticipation.prototype._toggleUserPresence = function(e) {
        var elem = e.currentTarget;
        if ($(elem).val()) {
            this._attestUserPresence($(elem).val(), $(elem).prop('checked'));
        }
    };

    ViewParticipation.prototype._attestUserPresence = function(userid, state) {
        ajax.call([{
            methodname: 'mod_visio_set_presence',
            args: {
                visioid: this._visioid,
                userid: userid,
                value: state == true ? 2 : 0
            },
            done: function() {
                return true;
            },
            fail: notification.exception
        }]);
    };

    return ViewParticipation;
});