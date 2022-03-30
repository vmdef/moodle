M.local_plugins_moodleversion = {

    Y : null,

    cookieexpires : null,

    init : function(Y){
        this.Y = Y;
        Y.all('select.local_plugins_moodleversion').each(this.moodle_version_selected, this);

        //hide the submit buttons
        Y.all('input.local_plugins_moodleversionsubmit').setStyle('display', 'none');
        YUI().use(['datatype-date-math','cookie','node'], function(Y) {
            this.cookieexpires = Y.DataType.Date.addMonths(new Date(), 3);
            var selectnode = Y.one('select.local_plugins_moodleversion');
            if (selectnode && selectnode.get('value')) {
                Y.Cookie.set('local_plugins_moodle_version', selectnode.get('value'), {path: '/', expires: this.cookieexpires});
            }
        })
    },

    moodle_version_selected : function(selectnode) {
        selectnode.on('change', this.submit_moodle_version, this, selectnode);
    },

    submit_moodle_version : function(e, selectnode){
        YUI().use('cookie', function(Y) {
            Y.Cookie.set('local_plugins_moodle_version', selectnode.get('value'), {path: '/', expires: this.cookieexpires});
        });
        selectnode.ancestor('form').submit()
    }
};