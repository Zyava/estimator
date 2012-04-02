if (!LogController) {
    var LogController  = {
        addMessage: function(data) {
            var $log = $('#log');
            for (var i in data) {
                if (data.hasOwnProperty(i)) {
                    $log.append(($log.text() ? "<br/>" : '') + data[i]);
                }
            }

            // autoscroll
            $log[0].scrollTop = $log[0].scrollHeight - $log[0].clientHeight;
        }
    };
}
