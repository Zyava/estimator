/**
 *
 * Card class
 * Author: Andrew Zavadsky
 *
 */
if (!Task) {

    var Task = function(params) {
        this.id = params.id;
        this.title = params.title === undefined ? '' : params.title;
        this.description = params.description === undefined ? '' : params.description;
        this.container = params.container === undefined ? null : $('#' + params.container);
        this.draggable = params.draggable === undefined ? false : params.draggable;
        this.position = params.position === undefined ? null : params.position;
        this.status = params.status === undefined ? 1 : params.status;

        this.taskContainer = null;
        this.titleContainer = null;
        this.descriptionContainer = null;
        this.createTask();
        this.showTask(true);

        if (this.draggable) {
            this.setDraggable();
        }
    };

    Task.instances = {};

    Task.zIndexStartValue = 10;

    Task.getAll = function() {
        return Task.instances;
    };

    Task.getState = function() {
        var state = [];
        for (var id in Task.instances) {
            if (Task.instances.hasOwnProperty(id)) {
                var object = Task.get(id);
                state.push({
                    'id': object.id,
                    'title': object.title,
                    'description': object.description,
                    'container': object.getContainer().attr('id'),
                    'draggable': object.draggable,
                    'status': object.status,
                    'position': {
                        'top': object.getTaskContainer().css('top'),
                        'left': object.getTaskContainer().css('left')
                    }
                });
            }
        }
        return state;
    };

    Task.create = function(params){
        try {
            var object = Task.get(params.id);
            object.update(params);
        } catch(e) {
            params.id = params.id ? params.id : 'task' + Math.round(100000 * Math.random());
            Task.instances[params.id] = new Task(params);
            return Task.instances[params.id];
        }
    };

    Task.get = function(id){
        if (!Task.instances[id]){
            throw new Error('There is no Task with id = "' + id + '"');
        }
        return Task.instances[id];
    };

    Task.prototype.update = function(params) {
        var data = this.getData();
        this.title = params.title === undefined ? this.title : params.title;
        this.description = params.description === undefined ? this.description : params.description;
        this.container = params.container === undefined ? this.container : $('#' + params.container);
        this.draggable = params.draggable === undefined ? this.draggable : params.draggable;
        this.position = params.position === undefined ? this.position : params.position;
        this.status = params.status === undefined ? data.status : params.status;

        this.taskContainer.append(this.titleContainer);
        this.taskContainer.append(this.descriptionContainer);
        this.showTask(true);
        this.setPosition();
    };

    Task.prototype.getId = function() {
        return this.id;
    };

    Task.prototype.getData = function() {
        return {
            'id': this.id,
            'title': this.title,
            'description': this.description,
            'container': this.container.attr('id'),
            'draggable': this.draggable,
            'position': {
                'top': parseInt(this.taskContainer.css('top')),
                'left': parseInt(this.taskContainer.css('left'))
            }
        };
    };

    Task.prototype.createTask = function() {
        var editLinks = '<div class="task-edit">'
            + '<img class="delete" src="/images/btn_icon_delete.gif" width="16" height="16" border="0" />'
            + '<img class="edit" src="/images/btn_icon_edit.gif" width="16" height="16" border="0" />'
            + '</div>';
        this.titleContainer = $(editLinks + '<h2></h2>');
        this.descriptionContainer = $('<div class="dragbox-content" ></div>');
        this.taskContainer = $('<div class="dragbox" id="' + this.id + '"></div>');
        this.taskContainer.css('z-index', Task.zIndexStartValue++);
        this.taskContainer.append(this.titleContainer);
        this.taskContainer.append(this.descriptionContainer);

        var object = this;
        $(this.taskContainer).hover(function(){
            $(object.taskContainer).find('div.task-edit').show();
        }, function(){
            $(object.taskContainer).find('div.task-edit').hide();
        });

        $('div.task-edit img.delete', this.taskContainer).click(function(){
            if (confirm('Are you sure?')) {
                sendToSocket({
                    'class' : 'TicketController',
                    'method' : 'onDelete',
                    'data': {
                        'id': object.getId(),
                        'title': object.title
                    }
                });
            }
        });
        $('div.task-edit img.edit', this.taskContainer).click(function(){
            Estimator.showEditForm(object);
        });

        return this;
    };

    Task.prototype.showTask = function(append) {
        if (this.container == null) {
            throw new Error('There is no container which should keep task');
        } else {
            this.titleContainer.get(1).innerHTML = this.title;
            this.descriptionContainer.html(this.description);
            if (append) {
                this.container.append(this.taskContainer);
            }
            this.taskContainer.show();
        }
        return this;
    };

    Task.prototype.getTaskContainer = function() {
        return this.taskContainer;
    };

    Task.prototype.setContainer = function(container) {
        this.container = $('#' + container);
    };

    Task.prototype.getContainer = function() {
        return this.container;
    };

    Task.prototype.setPosition = function() {
        if (this.position) {
            if (this.position.top !== undefined) {
                this.taskContainer.css('top', this.position.top + 'px');
            }
            if (this.position.left !== undefined) {
                this.taskContainer.css('left', this.position.left + 'px');
            }
        }
    };

    Task.prototype.setDraggable = function() {
        this.setPosition();
        this.taskContainer.css('position', 'absolute');
        this.taskContainer.draggable({
            handle: 'h2',
            start: function(even, ui) {
                ui.helper.css('z-index', Task.zIndexStartValue++);
            },
            drag: function(event, ui) {
                var object = Task.get(ui.helper.attr('id'));

                sendToSocket({
                    'class' : 'TicketController',
                    'method' : 'onChangePosition',
                    'data' : object.getData()
                });
            },
            stop: function(event, ui) {
                var object = Task.get(ui.helper.attr('id'));

                sendToSocket({
                    'class' : 'TicketController',
                    'method' : 'onSavePosition',
                    'data' : object.getData()
                });
            }
        });
    };

    Task.prototype.delete = function() {
        var object = this
        this.taskContainer.fadeOut('slow', function() {
            object.taskContainer.remove();
            Task.instances[object.getId()] = false;
        });
    }
}
