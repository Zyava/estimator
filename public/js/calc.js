if (!CalcController) {
    var CalcController  = {
        onStatusChange: function(data) {
            $.each(data, function(index, value){
                if (value['status'] == 1) {
                    calcCheckedTasks[value['id']] = true;
                    $('#calcTask' + value['id']).attr('checked', 'true');
                } else {
                    calcCheckedTasks[value['id']] = false;
                    $('#calcTask' + value['id']).removeAttr('checked');
                }
            });
            updateTotalCalculation();
        }
    };
}
