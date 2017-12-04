//
function ciniki_atdo_settings() {
    //
    // Panels
    //
    this.main = null;
    this.add = null;

    this.cb = null;
    this.toggleOptions = {'off':'Off', 'on':'On'};

    this.init = function() {
        //
        // The main panel, which lists the options for production
        //
        this.main = new M.panel('Settings',
            'ciniki_atdo_settings', 'main',
            'mc', 'narrow', 'sectioned', 'ciniki.atdo.settings.main');
        this.main.sections = {
            '_appointments':{'label':'Appointment Colours', 'fields':{
                'appointments.status.1':{'label':'Appointment', 'type':'colour'},
            }},
            '_tasks':{'label':'Task Colours', 'fields':{
                'tasks.status.60':{'label':'Completed', 'type':'colour'},
                'tasks.priority.10':{'label':'Low', 'type':'colour'},
                'tasks.priority.30':{'label':'Medium', 'type':'colour'},
                'tasks.priority.50':{'label':'High', 'type':'colour'},
            }},
            '_mainmenu':{'label':'Main Menu', 'fields':{}},
        };

        this.main.fieldValue = function(s, i, d) { 
            return this.data[i];
        };

        //  
        // Callback for the field history
        //  
        this.main.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.atdo.settingsHistory', 'args':{'tnid':M.curTenantID, 'field':i}};
        };

        this.main.addButton('save', 'Save', 'M.ciniki_atdo_settings.saveSettings();');
        this.main.addClose('Cancel');
    }

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_atdo_settings', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        //
        // Get the task categories for this tenant
        //
        var rsp = M.api.getJSONCb('ciniki.atdo.tasksCategories', 
            {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) { 
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_atdo_settings.main;    
                var options = {'':'Not displayed'};
                for(i in rsp.categories) {
                    options[rsp.categories[i].category.name] = rsp.categories[i].category.name;
                }
                p.sections._mainmenu.fields = {};
                p.sections._mainmenu.fields['tasks.ui.mainmenu.category.'+M.userID] = 
                    {'label':'Task Category', 'type':'select', 'options':options};
                M.ciniki_atdo_settings.showMain(cb);
            });
    }

    //
    // Grab the stats for the tenant from the database and present the list of orders.
    //
    this.showMain = function(cb) {
        var rsp = M.api.getJSONCb('ciniki.atdo.settingsGet', 
            {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_atdo_settings.main;
                p.data = rsp.settings;
                p.refresh();
                p.show(cb);
            });
    }

    this.saveSettings = function() {
        var c = this.main.serializeForm('no');
        if( c != '' ) {
            var rsp = M.api.postJSONCb('ciniki.atdo.settingsUpdate', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    } 
                    M.ciniki_atdo_settings.main.close();
                });
        } else {
            M.ciniki_atdo_settings.main.close();
        }
    }
}
