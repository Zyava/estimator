var estimatorWSServer;

function connectSocket(sessionId, sprintId)
{
    estimatorWSServer = new FancyWebSocket('ws://'+window.location.hostname+':9300');

    //Let the user know we're connected
    estimatorWSServer.bind('open', function() {
        estimatorWSServer.send('message',
            JSON.stringify({'action' : 'login', 'token' : sessionId, 'sprint_id' : sprintId}));

        estimatorWSServer.send('message',
            JSON.stringify({'class' : 'TicketController', 'method' : 'getTicketList', 'data': null}));
    });

    //OH NOES! Disconnection occurred.
    estimatorWSServer.bind('close', function() {});

    //Log any messages sent from server
    estimatorWSServer.bind('message', function(json) {
        var jsonObject = jQuery.parseJSON(json);
        var data;

        if (jsonObject.hasOwnProperty('class') && jsonObject.hasOwnProperty('method')) {
            var className = jsonObject.class;
            var methodName = jsonObject.method;
            if (jsonObject.hasOwnProperty('data')) {
                data = jsonObject.data;
            } else {
                data = {};
            }
            eval(className + '.' + methodName + '(data)');
        }
    });

    estimatorWSServer.connect();
}

function sendToSocket(params)
{
    estimatorWSServer.send('message', JSON.stringify(params));
}
