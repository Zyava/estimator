/**
 *
 * Card class
 * Author: Andrew Zavadsky
 *
 */
if (!CalculationItem) {

    var CalculationItem = function(params) {
        this.id = params.id;
        this.title = params.title === undefined ? '' : params.title;
        this.container = params.container === undefined ? null : $('#' + params.container);
        this.draggable = params.draggable === undefined ? false : params.draggable;
        this.position = params.position === undefined ? null : params.position;

        this.taskContainer = null;
        this.titleContainer = null;
        this.descriptionContainer = null;
        this.createTask();
        this.showTask();

        if (this.draggable) {
            this.setDraggable();
        }
    }

    CalculationItem.instances = {};

    CalculationItem.zIndexStartValue = 10;

    CalculationItem.getAll = function() {
        return CalculationItem.instances;
    }

    CalculationItem.getState = function() {
        var state = [];
        for (id in CalculationItem.instances) {
            if (CalculationItem.instances.hasOwnProperty(id)) {
                var object = CalculationItem.get(id)
                state.push({
                    id: object.id,
                    title: object.title,
                    description: object.description,
                    container: object.getContainer().attr('id'),
                    draggable: object.draggable,
                    position: {
                        top: object.getTaskContainer().css('top'),
                        left: object.getTaskContainer().css('left')
                    }
                });
            }
        }
        return state;
    }

    CalculationItem.create = function(params){
        try {
            var object = Task.get(params.id);
            object.update(params);
        } catch(e) {
            params.id = params.id ? params.id : 'task' + Math.round(100000 * Math.random());
            Task.instances[params.id] = new Task(params);
            return Task.instances[params.id];
        }
    }

    CalculationItem.get = function(id){
        if (!Task.instances[id]){
            throw new Error('There is no Task with id = "' + id + '"');
        }
        return Task.instances[id];
    }

    CalculationItem.prototype.update = function(params) {
        this.title = params.title === undefined ? '' : params.title;
        this.description = params.description === undefined ? '' : params.description;
        this.container = params.container === undefined ? null : $('#' + params.container);
        this.draggable = params.draggable === undefined ? false : params.draggable;
        this.position = params.position === undefined ? null : params.position;

        this.taskContainer.append(this.titleContainer);
        this.taskContainer.append(this.descriptionContainer);
        this.showTask();
        this.setPosition();
    }

    CalculationItem.prototype.getId = function() {
        return this.id;
    }

    CalculationItem.prototype.getData = function() {
        var object = {
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

        return object;
    }

    CalculationItem.prototype.createTask = function() {
        var editLinks = '<div class="task-edit">'
            + '<img class="delete" src="/images/btn_icon_delete.gif" width="16" height="16" border="0" />'
            + '<img class="edit" src="/images/btn_icon_edit.gif" width="16" height="16" border="0" />'
            + '</div>';
        this.titleContainer = $(editLinks + '<h2>' + this.title + '</h2>');
        this.descriptionContainer = $('<div class="dragbox-content" >' + this.description + '</div>');
        this.taskContainer = $('<div class="dragbox" id="' + this.id + '"></div>');
        this.taskContainer.css('z-index', CalculationItem.zIndexStartValue++);
        this.taskContainer.append(this.titleContainer);
        this.taskContainer.append(this.descriptionContainer);

        return this;
    }

    CalculationItem.prototype.showTask = function() {
        if (this.container == null) {
            throw new Error('There is no container which should keep task');
        } else {
            this.container.append(this.taskContainer);
            this.taskContainer.show();

            $('#' + this.id).hover(function(){
                $('#' + this.id + ' div.task-edit').show();
            }, function(){
                $('#' + this.id + ' div.task-edit').hide();
            });

            $('#' + this.id + ' div.task-edit img.delete').click(function(){
            });
            $('#' + this.id + ' div.task-edit img.edit').click(function(){
            });
        }
        return this;
    }

    CalculationItem.prototype.getTaskContainer = function() {
        return this.taskContainer;
    }

    CalculationItem.prototype.setContainer = function(container) {
        this.container = $('#' + container);
    }

    CalculationItem.prototype.getContainer = function() {
        return this.container;
    }

    CalculationItem.prototype.setPosition = function() {
        if (this.position) {
            if (this.position.top !== undefined) {
                this.taskContainer.css('top', this.position.top + 'px');
            }
            if (this.position.left !== undefined) {
                this.taskContainer.css('left', this.position.left + 'px');
            }
        }
    }

    CalculationItem.prototype.setDraggable = function() {
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
    }
}
