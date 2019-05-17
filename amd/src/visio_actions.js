define([
    'core/ajax',
    'core/notification'],
    function(ajax, notification) {

    var Visio = function(url, roomUrl, accessString) {
        setPresence();
        var container = document.getElementById('mod_visio_receiver');
        var button = document.createElement('button');
        button.classList = 'btn btn-primary';
        button.innerHTML = accessString;

        ajax
            .call([{
                methodname: 'mod_visio_host_launch_visio',
                args: { url: url },
                done: function(data) {
                    button.addEventListener('click', function() {
                        window.open(roomUrl + "/?session=" + data);
                    });

                    container.appendChild(button);
                },
                fail: function(ex) {
                    notification.exception(ex);
                }
            }], true);
    };

    var setPresence = function() {
        var checkboxes = document.getElementsByClassName('visio_checkbox');
        Array.prototype.slice.call(checkboxes).forEach(function(element) {
            element.addEventListener('change', function() {
                sendPresenceState(1, this.checked);
            });
        });
    }

    var sendPresenceState = function(user_id, value) {
        ajax
            .call([{
                methodname: 'mod_visio_set_presence',
                args: { 
                    user_id: user_id,
                    value: value
                },
                done: function(data) {
                    console.log(data);
                },
                fail: function(ex) {
                    notification.exception(ex);
                }
            }], true);
    }

    return {
        'init': function(url, roomUrl, accessString) {
            return new Visio(url, roomUrl, accessString);
        }
    };

});