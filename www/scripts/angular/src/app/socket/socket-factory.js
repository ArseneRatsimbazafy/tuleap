angular
    .module('socket')
    .service('SocketFactory', SocketFactory);

SocketFactory.$inject = [
    'socketFactory',
    'SharedPropertiesService'
];

function SocketFactory(
    socketFactory,
    SharedPropertiesService
) {
    if (SharedPropertiesService.getNodeServerAddress()) {
        var io_socket = io.connect('https://' + SharedPropertiesService.getNodeServerAddress() + '/trafficlights',
            {
                secure: true,
                path: '/socket.io'
            });

        return socketFactory({
            ioSocket: io_socket
        });
    }
}