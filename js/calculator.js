if (!Calculator) {
    var Calculator = {
        init: function(tasks) {
            this.tasks = tasks;
        },

        enable: function() {
            var tasks = [];
            for (var i in this.tasks) {
                if (this.tasks.hasOwnProperty(i)) {
                    tasks.push(this.tasks[i].getContainer().get(0));
                }
            }
            $(tasks).sortable({
                connectWith: tasks,
                handle: 'h2',
                cursor: 'move',
                placeholder: 'placeholder',
                forcePlaceholderSize: true,
                opacity: 0.4
            }).disableSelection();
        }
    };
}
