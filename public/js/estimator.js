/**
 *
 * Card class
 * Author: Andrew Zavadsky
 *
 */
if (!Estimator) {
    var Estimator = {
        dragContainer: 'drag_container',
        estimateContainer: 'column0',
        positionIndex: 100,

        init: function(cards, tasks) {
            this.cards = cards;
            this.tasks = tasks;

            Estimator.createTaskForm();
        },

        enable: function() {
            var cards = [];
            for (var i in this.cards) {
                if (this.cards.hasOwnProperty(i)) {
                    cards.push(this.cards[i].getContainer().get(0));
                }
            }
            $(cards).sortable({
                connectWith: cards,
                handle: 'h2',
                cursor: 'move',
                placeholder: 'placeholder',
                forcePlaceholderSize: true,
                opacity: 0.4,
                receive: function(event, ui) {
                    var task = Task.get(ui.item.get(0).id);
                    task.setContainer(event.target.id);

                    sendToSocket({
                        'class' : 'TicketController',
                        'method' : 'onEstimate',
                        'data': {
                            id: task.getId(),
                            title: task.title,
                            container: event.target.id
                        }
                    });
                }
            }).disableSelection();

        },

        getState: function() {
            return Task.getState();
        },

        setState: function(data) {
            $(data).each(function(index, elem) {
                Task.create(elem);
            });
            updateCalculationTable();
        },

        deleteTask: function(id) {
            var task = Task.get('task' + id);
            var card = Task.get('card' + id);
            task.delete();
            card.delete();
        },

        /*
            Methods for edit dialog --- BEGIN
         */
        createTaskForm: function() {
            var title = $("#title");
            var description = $("#description");
            var allFields = $([]).add(title).add(description);
            var tips = $("#validate-tips");

            var updateTips = function(t) {
                tips
                    .text(t)
                    .addClass("ui-state-highlight");
                setTimeout(function() {
                    tips.removeClass("ui-state-highlight", 1500);
                }, 500);
            };

            var checkLength = function(o, n, min, max) {
                if (o.val().length > max || o.val().length < min) {
                    o.addClass("ui-state-error");
                    updateTips("Length of " + n + " must be between " +
                        min + " and " + max + "." );
                    return false;
                } else {
                    return true;
                }
            };

            $( "#edit-task" ).dialog({
                autoOpen: false,
                height: 270,
                width: 350,
                modal: true,
                buttons: {
                    Save: function() {
                        var bValid = true;
                        allFields.removeClass("ui-state-error");

                        bValid = bValid && checkLength(title, "title", 3, 16);
                        bValid = bValid && checkLength(description, "description", 6, 80);

                        if ( bValid ) {
                            if ($(this).attr('isEdit') == true) {
                                var card = {
                                    id: $(this).attr('cardId'),
                                    title: title.val(),
                                    description: description.val()
                                };

                                sendToSocket({
                                    'class' : 'TicketController',
                                    'method' : 'onUpdate',
                                    'data': card
                                });
                            } else {
                                var card1 = {
                                    title: title.val(),
                                    description: description.val(),
                                    container: Estimator.estimateContainer
                                };

                                var card2 = {
                                    title: title.val(),
                                    description: description.val(),
                                    container: Estimator.dragContainer,
                                    draggable: true,
                                    position: {
                                        top: Estimator.positionIndex,
                                        left: Estimator.positionIndex
                                    }
                                };

                                card1.position = {
                                    top: Estimator.positionIndex,
                                    left: Estimator.positionIndex
                                };

                                Estimator.positionIndex += 20;

                                sendToSocket({
                                    'class' : 'TicketController',
                                    'method' : 'onCreate',
                                    'data': [card1, card2]
                                });
                            }

                            $(this).dialog("close");
                        }
                    },
                    Cancel: function() {
                        $(this).dialog("close");
                    }
                },
                close: function() {
                    allFields.val("").removeClass("ui-state-error");
                }
            });
        },

        showTaskForm: function() {
            $('#edit-task').dialog({'title': 'Create task'});
            $('#edit-task').attr('isEdit', 0);
            $('#edit-task').dialog('open');
        },

        showEditForm: function(elem) {
            $('#title').val(elem.title);
            $('#description').val(elem.description);
            $('#edit-task').dialog({'title': 'Edit task'});
            $('#edit-task').attr('isEdit', 1);
            $('#edit-task').attr('cardId', elem.getId());
            $('#edit-task').dialog('open');
        }
    }
}
