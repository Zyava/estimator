if (!TabController) {
    var TabController  = {
        onChangeCurrentTab: function(data) {
            if (data.hasOwnProperty('tab_index')) {
                if ($('#tabs li.ui-state-disabled').length > 0) {
                    $('#tabs li').removeClass("ui-state-disabled");
                    $('#tabs li').removeClass("ui-tabs-selected");
                    $('#tabs li').removeClass("ui-state-active");
                    $('#tabs').tabs("select", data.tab_index);
                    $('#tabs li').addClass("ui-state-disabled");
                    $('#tabs li:nth-child('+(1+data.tab_index)+')')
                        .removeClass("ui-state-disabled")
                        .addClass("ui-tabs-selected")
                        .addClass("ui-state-active");
                } else {
                    $('#tabs').tabs("enable", data.tab_index).tabs("select", data.tab_index);
                }
            }
        }
    };
}
