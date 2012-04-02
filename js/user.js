if (!UserController) {
    var UserController  = {
        showUsers: function(data) {
            var $users = $('#users');
            $users.empty();
            for (var i in data) {
                if (data.hasOwnProperty(i)) {
                    $users.append(($users.text() ? "<br/>" : '') + data[i]);
                }
            }
        }
    };
}
