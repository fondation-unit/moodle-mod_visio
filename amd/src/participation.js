define(['jquery', 'core/ajax', 'core/notification'],
function($, ajax, notification) {

    var Participation = function(selector, visioid, userid) {
        this._region = $(selector);
        this._visioid = visioid;
        this._userid = userid;

        this._region.find('[data-action="launch-visio"]').on('click', this._handleLaunchVisio.bind(this));
    };

    Participation.prototype._handleLaunchVisio = function() {
        this._setUserPresence();
    };

    Participation.prototype._setUserPresence = function() {
        ajax.call([{
            methodname: 'mod_visio_set_presence',
            args: {
                visioid: this._visioid,
                userid: this._userid,
                value: 1
            },
            done: function() {
                return true;
            },
            fail: notification.exception
        }]);
    };

    return Participation;
});