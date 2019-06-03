define([
    'core/ajax',
    'core/notification'],
    function(ajax, notification) {

    var Visio = function(url, roomUrl, accessString) {
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

    return {
        'init': function(url, roomUrl, accessString) {
            return new Visio(url, roomUrl, accessString);
        }
    };

});