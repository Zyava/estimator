if (!SprintController) {
    var SprintController  = {
        onChangeSprint: function(data) {
            if (data.hasOwnProperty('refresh_page') && data.refresh_page == 1) {
                $('#sprint-list-form').submit();
            }
        }
    };
}
