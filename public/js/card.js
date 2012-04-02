if (!Card) {
    var Card = function(params) {
        this.id = params.id;
        this.title = params.title === undefined ? '' : params.title;
        this.container = params.container === undefined ? null : $('#' + params.container);
        this.cardClass = 'column';

        this.cardContainer = null;
        this.createCard();
    };

    Card.instances = {};

    Card.getAll = function() {
        return Card.instances;
    };

    Card.create = function(params){
        params.id = params.id ? params.id : 'card' + Math.round(100000 * Math.random());
        Card.instances[params.id] = new Card(params);
        return Card.instances[params.id];
    };

    Card.get = function(id){
        if (!Card.instances[id]){
            throw new Error('There is no Card with id = "' + id + '"');
        }
        return Card.instances[id];
    };

    Card.prototype.createCard = function() {
        this.cardContainer = $('<div class="column" id="' + this.id + '"><div class="title">'
            + this.title + '</div></div>');
        if (this.container == null) {
            throw new Error('There is no container which should keep card');
        } else {
            this.container.append(this.cardContainer);
            this.cardContainer.show();
        }
    };

    Card.prototype.getContainer = function() {
        return this.cardContainer;
    }
}
