define(['jquery', 'core/ajax', 'core/notification'],
function($, ajax, notification) {

    var PollAction = function(selector, visioId) {
        this._region = $(selector);
        this._visioid = visioId;

        this._region.find('ul').unbind().on('click', 'li', this._setUserChoice.bind(this));
    };

    PollAction.prototype._setUserChoice = function(element) {
        var elem = $(element.target);
        var value = elem.data('pollname');

        if (value) {
            ajax.call([{
                methodname: 'mod_visio_set_pollchoice',
                args: {
                    visioid: this._visioid,
                    poll_value: value
                },
                done: function() {
                    this._region.find('li').removeClass('bg-primary');
                    elem.addClass('bg-primary');
                    return true;
                }.bind(this),
                fail: notification.exception
            }]);
        }
        return true;
    };

    return PollAction;
});