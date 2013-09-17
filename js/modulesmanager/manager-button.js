var ModulesManagerButton = Class.create();
ModulesManagerButton.prototype = {
    initialize: function(saveUrl){
        this.form;
        this.saveUrl = saveUrl;
        this.onSave = this.reloadManager.bindAsEventListener(this);
    },

    save: function(form){
        this.form = form;
        params = Form.serialize(this.form);
        var request = new Ajax.Request(
            this.saveUrl,
            {
                method:'post',
                parameters:params,
                onSuccess: this.onSave
            }
        );
    },

    reloadManager: function(transport){
        if (transport && transport.responseText) {
            try{
                response = eval('(' + transport.responseText + ')');
                top.location.reload();
            }
            catch (e) {
                response = {};
            }
        }
    }
}