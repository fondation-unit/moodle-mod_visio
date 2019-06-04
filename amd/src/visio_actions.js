define([
    'core/ajax',
    'core/notification',
    'core/str'],
function(ajax, notification, str) {

    var Visio = function(url, roomUrl, isPassed) {
        var container = document.getElementById('mod_visio_receiver');
        var button = document.createElement('button');
        button.classList = 'btn btn-primary';

        // If the visio is not passed yet.
        if (!isPassed) {
            str.get_string('access', 'visio').done(function(s) { button.innerHTML = s; });
        } else {
            // If the visio is passed and has no broadcast yet.
            if (roomUrl != '') {
                str.get_string('broadcastview', 'visio').done(function(s) { button.innerHTML = s; });
            } else {
                button = document.createElement('span');
                button.classList = 'alert alert-info';
                str.get_string('broadcastsoon', 'visio').done(function(s) { button.innerHTML = s; });
            }
        }

        ajax
            .call([{
                methodname: 'mod_visio_host_launch_visio',
                args: { url: url },
                done: function(data) {
                    if (roomUrl != '') {
                        button.addEventListener('click', function() {
                            window.open(roomUrl + "/?session=" + data);
                        });
                    }

                    container.appendChild(button);
                },
                fail: function(ex) {
                    notification.exception(ex);
                }
            }], true);
    };

    return {
        'init': function(url, roomUrl, isPassed) {
            return new Visio(url, roomUrl, isPassed);
        }
    };

});