M.local_plugins_plugincategory = {

    Y : null,

    cookieexpires : null,

    init : function(Y){
        this.Y = Y;
        Y.all('select.local_plugins_plugin_category').each(this.plugin_category_selected, this);
        //hide the submit buttons
        Y.all('input.local_plugins_plugincategorysubmit').setStyle('display', 'none');
        YUI().use(['datatype-date-math','cookie','node'], function(Y) {
            this.cookieexpires = Y.DataType.Date.addMonths(new Date(), 3);
            var selectnode = Y.one('select.local_plugins_plugincategory');
            if (selectnode && selectnode.get('value')) {
                Y.Cookie.set('local_plugins_plugin_category', selectnode.get('value'), {path: '/', expires: this.cookieexpires});
            }
        })
    },

    plugin_category_selected : function(selectnode) {
        selectnode.on('change', this.submit_plugin_category, this, selectnode);
    },

    submit_plugin_category : function(e, selectnode){
        YUI().use('cookie', function(Y) {
            Y.Cookie.set('local_plugins_plugin_category', selectnode.get('value'), {path: '/', expires: this.cookieexpires});
        });
        selectnode.ancestor('form').submit()
    }
};