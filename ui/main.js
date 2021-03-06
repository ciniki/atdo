//
function ciniki_atdo_main() {
    //
    // Panels
    //
    this.dayschedule = null;
    this.add = null;

    this.cb = null;
    this.toggleOptions = {'off':'Off', 'on':'On'};
    this.durationOptions = {'1440':'All day', '15':'15', '30':'30', '45':'45', '60':'60', '90':'1:30', '120':'2h'};
    this.durationButtons = {'-30':'-30', '-15':'-15', '+15':'+15', '+30':'+30', '+2h':'+120'};
    this.repeatOptions = {'0':'None', '10':'Daily', '20':'Weekly', '30':'Monthly by Date', '31':'Monthly by Weekday','40':'Yearly'};
    this.repeatIntervals = {'1':'1', '2':'2', '3':'3', '4':'4', '5':'5', '6':'6', '7':'7', '8':'8'};
    this.statuses = {'1':'Open', '60':'Completed'};
    this.symbolpriorities = {'10':'Q', '30':'W', '50':'E'}; // also stored in core_menu.js

    //
    // The default panel will show the tasks in a list based on assignment
    //
    this.tasks = new M.panel('Tasks',
        'ciniki_atdo_main', 'tasks',
        'mc', 'flexible', 'sectioned', 'ciniki.atdo.main.tasks');
    this.tasks.data = null;
    this.tasks.status = 'open';
    this.tasks.priority = '';
    this.tasks.category = 'All';
    this.tasks.user_id = 0;
    this.tasks.sections = {
        'statuslist':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'flexcolumn':1,
            'flexgrow':1,
            'minwidth':'10em',
            'width':'10em',
            },
        'prioritylist':{'label':'Priority', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'flexcolumn':1,
            },
        'employeelist':{'label':'Employees', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'flexcolumn':1,
            'visible':function() { return M.curTenant.permissions.owners != null ? 'yes' : 'no'; },
            },
        'categorylist':{'label':'Categories', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'flexcolumn':1,
            'editFn':function(s, i, d) {
                if( d.category != 'All' && M.curTenant.permissions.owners != null ) {
                    return 'M.ciniki_atdo_main.tasks.renameCategory(\'' + escape(d.category) + '\');';
                }
                return '';
                },
            },
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5, 'hint':'search', 
            'flexcolumn':2,
            'flexgrow':2,
            'minwidth':'25em',
            'width':'40em',
            'noData':'No tasks found',
            'headerValues':['', 'Category', 'Task', 'Due', 'Updated'],
            'cellClasses':['multiline aligncenter', '', 'multiline', '', 'multiline'],
            },
        'tasks':{'label':'Tasks', 'num_cols':5, 'type':'simplegrid', 
            'flexcolumn':2,
            'headerValues':['', 'Category', 'Task', 'Due', 'Updated'],
            'cellClasses':['multiline aligncenter', '', 'multiline', '', 'multiline'],
            'sortable':'yes',
            'sortTypes':['altnumber', 'text', 'text', 'date', 'date'],
            'noData':'No tasks',
            },
    };
    this.tasks.liveSearchCb = function(s, i, v) {
        M.api.getJSONBgCb('ciniki.atdo.tasksSearchQuick', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'15'},
            function(rsp) {
                M.ciniki_atdo_main.tasks.liveSearchShow(s, null, M.gE(M.ciniki_atdo_main.tasks.panelUID + '_' + s), rsp.tasks);
            });
        return true;
    };
    this.tasks.liveSearchResultValue = function(s, f, i, j, d) {
        return this.cellValue(s, i, j, d);
    };
    this.tasks.liveSearchResultRowFn = function(s, f, i, j, d) {
        return this.rowFn(s, i, d);
    };
    this.tasks.liveSearchResultRowClass = function(s, f, i, d) {
        if( d.status == 'closed' ) {
            return 'statusgreen';
        } else {
            switch (d.priority) {
                case '10': return 'statusyellow';
                case '30': return 'statusorange';
                case '50': return 'statusred';
            }
        }
//        if( d.status != 'closed' ) { return 'background: ' + M.curTenant.atdo.settings['tasks.priority.' + d.priority]; }
//        else { return 'background: ' + M.curTenant.atdo.settings['tasks.status.60']; }
        return '';
    };
    this.tasks.liveSearchSubmitFn = function(s, search_str) {
        M.ciniki_atdo_main.searchTasks('M.ciniki_atdo_main.tasks.open();', search_str);
    };
    this.tasks.cellSortValue = function(s, i, j, d) {
        if( s == 'tasks' && j == 0 ) {
            return d.priority;
        }
    }
    this.tasks.cellValue = function(s, i, j, d) {
        if( s == 'statuslist' ) {
            return d.label + (d.num_tasks != null && d.num_tasks > 0 ? ' <span class="count">' + d.num_tasks + '</span>': '');
        }
        if( s == 'prioritylist' ) {
            return d.label + (d.num_tasks != null && d.num_tasks > 0 ? ' <span class="count">' + d.num_tasks + '</span>': '');
        }
        if( s == 'categorylist' ) {
            return d.category + (d.num_tasks != null && d.num_tasks > 0 ? ' <span class="count">' + d.num_tasks + '</span>': '');
        }
        if( s == 'employeelist' ) {
            return d.display_name + (d.num_tasks != null && d.num_tasks > 0 ? ' <span class="count">' + d.num_tasks + '</span>': '');
        }
        if( s == 'tasks' || s == 'search' ) {
            switch(j) {
                case 0: return '<span class="icon">' + M.ciniki_atdo_main.symbolpriorities[d.priority] + '</span>';
                case 1: return d.category;
                case 2: return M.multiline(d.subject + M.subdue(' [', d.project_name , ']'), d.assigned_users);
                case 3: return d.due_date;
                case 4: return M.multiline(d.last_updated_date, d.last_updated_time);
            }
        }
    }
    this.tasks.rowClass = function(s, i, d) {
        if( s == 'statuslist' && this.status == d.id ) {
            return 'highlight';
        }
        if( s == 'prioritylist' && this.priority == d.id ) {
            return 'highlight';
        }
        if( s == 'categorylist' && this.category == d.category ) {
            return 'highlight';
        }
        if( s == 'employeelist' && this.user_id == d.id ) {
            return 'highlight';
        }
        if( s == 'tasks' ) {
            if( d.status == 'closed' ) {
                return 'statusgreen';
            } else {
                switch (d.priority) {
                    case '10': return 'statusyellow';
                    case '30': return 'statusorange';
                    case '50': return 'statusred';
                }
            }
        }
    }
/*    this.tasks.rowStyle = function(s, i, d) {
        if( d != null ) {
            if( d.status != 'closed' ) { return 'background: ' + M.curTenant.atdo.settings['tasks.priority.' + d.priority]; }
            else { return 'background: ' + M.curTenant.atdo.settings['tasks.status.60']; }
        }
        return '';
    }; */
    this.tasks.rowFn = function(s, i, d) {
        if( s == 'statuslist' ) {
            return 'M.ciniki_atdo_main.tasks.filter(\'status\',\'' + d.id + '\');';
        }
        if( s == 'prioritylist' ) {
            return 'M.ciniki_atdo_main.tasks.filter(\'priority\',\'' + d.id + '\');';
        }
        if( s == 'categorylist' ) {
            return 'M.ciniki_atdo_main.tasks.filter(\'category\',\'' + escape(d.category) + '\');';
        }
        if( s == 'employeelist' ) {
            return 'M.ciniki_atdo_main.tasks.filter(\'user_id\',\'' + d.id + '\');';
        }
        if( s == 'tasks' || s == 'search' ) {
            return 'M.ciniki_atdo_main.atdo.open(\'M.ciniki_atdo_main.tasks.open(null, null);\', \'' + d.id + '\');'; 
        }
    };
    this.tasks.filter = function(f, id) {
        this.lastY = 0;
        if( f == 'category' ) {
            this[f] = unescape(id);
        } else {
            this[f] = id;
        }
        if( f == 'status' || f == 'priority' || f == 'user_id' ) {
            this.category = 'All';
        }
        this.open();
    }
    this.tasks.renameCategory = function(c) {
        var n = prompt("New category name", unescape(c));
        if( n != c ) {
            M.api.getJSONCb('ciniki.atdo.categoryRename', {'tnid':M.curTenantID, 'type':2, 
                'status':this.status, 'priority':this.priority, 'user_id':this.user_id,
                'old':c, 'new':escape(n)}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_atdo_main.tasks.open();
                });
        }
    }
    this.tasks.open = function(cb, scheduleDate) {
        // Get the open tasks for the user and tenant
        this.data = {};
        M.api.getJSONCb('ciniki.atdo.tasksList', {'tnid':M.curTenantID, 'stats':'yes',
            'status':this.status, 'priority':this.priority, 'category':this.category, 'user_id':this.user_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.stopLoad();
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_atdo_main.tasks;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.tasks.addButton('add', 'Add', 'M.ciniki_atdo_main.showAdd(\'M.ciniki_atdo_main.tasks.open();\',\'task\');');
    this.tasks.addClose('Back');

    //
    // The default panel will show the faq in a list based on category
    //
    this.faqs = new M.panel('FAQs',
        'ciniki_atdo_main', 'faqs',
        'mc', 'medium', 'sectioned', 'ciniki.atdo.main.faqs');
    this.faqs.data = null;
    this.faqs.sections = {
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1, 'hint':'search', 
            'noData':'No notes found',
            'headerValues':null,
            'cellClasses':[''],
            },
        'assigned':{'label':'Your Notes', 'num_cols':1, 'type':'simplegrid', 
            'headerValues':null,
            'cellClasses':[''],
            'noData':'No notes assigned to you',
            },
        'tenant':{'label':'General Notes', 'num_cols':1, 'type':'simplegrid', 
            'headerValues':null,
            'cellClasses':[''],
            'noData':'No tenant notes',
            },
    };
    this.faqs.data = {};
    this.faqs.noData = function() { return 'No notes found'; }
    // Live Search functions
    this.faqs.liveSearchCb = function(s, i, v) {
        M.api.getJSONBgCb('ciniki.atdo.faqsSearchQuick', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'15'},
            function(rsp) {
                M.ciniki_atdo_main.faqs.liveSearchShow(s, null, M.gE(M.ciniki_atdo_main.faqs.panelUID + '_' + s), rsp.faqs);
            });
        return true;
    };
    this.faqs.liveSearchResultValue = function(s, f, i, j, d) {
        return this.cellValue(s, i, j, d);
    };
    this.faqs.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_atdo_main.atdo.open(\'M.ciniki_atdo_main.faqs.open(null, null);\', \'' + d.id + '\');'; 
    };
    this.faqs.cellValue = function(s, i, j, d) {
        if( j == 0 ) { 
            return d.subject; 
        }
    };
    this.faqs.rowFn = function(s, i, d) {
        return 'M.ciniki_atdo_main.atdo.open(\'M.ciniki_atdo_main.faqs.open(null, null);\', \'' + d.id + '\');'; 
    };
    this.faqs.listValue = function(s, i, d) { 
        if( d.count != null ) {
            return d.label + ' <span class="count">' + d.count + '</span>'; 
        }
        return d.label;
    };
    this.faqs.open = function(cb) {
        // Get the faqs for the user and tenant
        this.data = {};
        M.api.getJSONCb('ciniki.atdo.faqsList', {'tnid':M.curTenantID, 'status':'open'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_atdo_main.faqs;
            // Setup the data to display the sections of the 
            p.sections = {
                'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1, 'hint':'search', 
                    'noData':'No faqs found',
                    'headerValues':null,
                    'cellClasses':[''],
                    },
            };
            for(i in rsp.categories) {
                p.data[rsp.categories[i].name] = rsp.categories[i].faqs;
                p.sections[rsp.categories[i].name] = {'label':rsp.categories[i].name,
                    'num_cols':1, 'type':'simplegrid', 'headerValues':null,
                    'cellClasses':[''],
                    'noData':'No FAQs found',
                    };
            }

            // Show the panel
            p.refresh();
            p.show(cb);
        });
    }
    this.faqs.addButton('add', 'Add', 'M.ciniki_atdo_main.showAdd(\'M.ciniki_atdo_main.faqs.open();\',\'faq\');');
    this.faqs.addClose('Back');

    //
    // The default panel will show the notes in a list based on assignment
    //
    this.notes = new M.panel('Notes',
        'ciniki_atdo_main', 'notes',
        'mc', 'medium', 'sectioned', 'ciniki.atdo.main.notes');
    this.notes.data = null;
    this.notes.sections = {
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1, 'hint':'search', 
            'noData':'No notes found',
            'headerValues':null,
            'cellClasses':[''],
            },
        'assigned':{'label':'Your Notes', 'num_cols':1, 'type':'simplegrid', 
            'headerValues':null,
            'cellClasses':[''],
            'noData':'No notes assigned to you',
            },
        'tenant':{'label':'General Notes', 'num_cols':1, 'type':'simplegrid', 
            'headerValues':null,
            'cellClasses':[''],
            'noData':'No tenant notes',
            },
    };
    this.notes.data = {};
    this.notes.noData = function() { return 'No notes found'; }
    // Live Search functions
    this.notes.liveSearchCb = function(s, i, v) {
        M.api.getJSONBgCb('ciniki.atdo.notesSearchQuick', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'15'},
            function(rsp) {
                M.ciniki_atdo_main.notes.liveSearchShow(s, null, M.gE(M.ciniki_atdo_main.notes.panelUID + '_' + s), rsp.notes);
            });
        return true;
    };
    this.notes.liveSearchResultClass = function(s, f, i, j, d) {
        return this.sections[s].cellClasses[j];
    };
    this.notes.liveSearchResultValue = function(s, f, i, j, d) {
        if( j == 0 ) {
            if( d.viewed == 'no' ) {
                return '<b>' + d.subject + '</b>';
            }
            return d.subject;
        }
        return '';
    };
    this.notes.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_atdo_main.atdo.open(\'M.ciniki_atdo_main.notes.open(null, null);\', \'' + d.id + '\');'; 
    };
//      this.notes.liveSearchSubmitFn = function(s, search_str) {
//          M.ciniki_atdo_main.searchNotes('M.ciniki_atdo_main.notes.open();', search_str);
//      };
    this.notes.cellValue = function(s, i, j, d) {
        if( j == 0 ) {
            if( d.viewed == 'no' ) {
                return '<b>' + d.subject + '</b>';
            }
            return d.subject;
        }
    };
    this.notes.rowFn = function(s, i, d) {
        return 'M.ciniki_atdo_main.atdo.open(\'M.ciniki_atdo_main.notes.open(null, null);\', \'' + d.id + '\');'; 
    };
    this.notes.sectionData = function(s) { 
        return this.data[s];
    };
    this.notes.listValue = function(s, i, d) { 
        if( d.count != null ) {
            return d.label + ' <span class="count">' + d.count + '</span>'; 
        }
        return d.label;
    };
    this.notes.open = function(cb) {
        // Get the notes for the user and tenant
        this.data = {'assigned':[], 'tenant':[]};
        var rsp = M.api.getJSONCb('ciniki.atdo.notesList', 
            {'tnid':M.curTenantID, 'status':'open'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }

                var p = M.ciniki_atdo_main.notes;
                p.sections = {
                    'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1, 'hint':'search', 
                        'noData':'No notes found',
                        'headerValues':null,
                        'cellClasses':[''],
                        },
                    };
                for(i in rsp.categories) {
                    p.data[rsp.categories[i].name] = rsp.categories[i].notes;
                    p.sections[rsp.categories[i].name] = {'label':rsp.categories[i].name,
                        'num_cols':1, 'type':'simplegrid', 'headerValues':null,
                        'cellClasses':[''],
                        'noData':'No Notes found',
                        };
                }

                // Show the panel
                p.refresh();
                p.show(cb);
            });
    }
    this.notes.addButton('add', 'Add', 'M.ciniki_atdo_main.showAdd(\'M.ciniki_atdo_main.notes.open();\',\'note\');');
    this.notes.addClose('Back');

    //
    // The default panel will show the messages in a list
    //
    this.messages = new M.panel('Messages',
        'ciniki_atdo_main', 'messages',
        'mc', 'medium narrowaside', 'sectioned', 'ciniki.atdo.main.messages');
    this.messages.data = null;
    this.messages.status = 'open';
    this.messages.user_id = 0;
    this.messages.sections = {
        'statuslist':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            },
        'employeelist':{'label':'Employees', 'aside':'yes', 'type':'simplegrid', 'num_cols':1,
            'visible':function() { return M.curTenant.permissions.owners != null ? 'yes' : 'no'; },
            },
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1, 'hint':'search', 
            'noData':'No messages found',
            'headerValues':null,
            'cellClasses':[''],
            },
        'messages':{'label':'Messages', 'num_cols':1, 'type':'simplegrid', 
            'headerValues':null,
            'cellClasses':['multiline',''],
            'noData':'No messages',
            },
    };
    this.messages.data = {};
    // Live Search functions
    this.messages.liveSearchCb = function(s, i, v) {
        M.api.getJSONBgCb('ciniki.atdo.messagesSearchQuick', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'15'},
            function(rsp) {
                M.ciniki_atdo_main.messages.liveSearchShow(s, null, M.gE(M.ciniki_atdo_main.messages.panelUID + '_' + s), rsp.messages);
            });
        return true;
    };
    this.messages.liveSearchResultValue = function(s, f, i, j, d) {
        return d.subject + (d.project_name != null && d.project_name != '' ? ' <span class="subdue">[' + d.project_name + ']</span>' : '');
    };
    this.messages.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_atdo_main.atdo.open(\'M.ciniki_atdo_main.messages.open(null, null);\', \'' + d.id + '\');'; 
    };
//    this.messages.noData = function(s) { return 'No messages'; }
    this.messages.cellValue = function(s, i, j, d) {
        if( s == 'statuslist' ) {
            return d.label + (d.num_messages != null && d.num_messages > 0 ? ' <span class="count">' + d.num_messages + '</span>': '');
        }
        if( s == 'employeelist' ) {
            return d.display_name + (d.num_messages != null && d.num_messages > 0 ? ' <span class="count">' + d.num_messages + '</span>': '');
        }
        if( s == 'messages' ) {
            return M.multiline((d.viewed == 'no' ? ('<b>'+d.subject+'</b>') : d.subject) + (d.project_name != null && d.project_name != '' ? ' <span class="subdue">[' + d.project_name + ']</span>' : ''), d.last_followup_user + ' - ' + d.last_followup_age);
        }
    };
    this.messages.rowFn = function(s, i, d) {
        if( s == 'statuslist' ) {
            return 'M.ciniki_atdo_main.messages.filter(\'status\',\'' + d.id + '\');';
        }
        if( s == 'messages' ) {
            return 'M.ciniki_atdo_main.atdo.open(\'M.ciniki_atdo_main.messages.open(null, null);\', \'' + d.id + '\');'; 
        }
        if( s == 'employeelist' ) {
            return 'M.ciniki_atdo_main.messages.filter(\'user_id\',\'' + d.id + '\');';
        }
    };
    this.messages.rowClass = function(s, i, d) {
        if( s == 'statuslist' && this.status == d.id ) {
            return 'highlight';
        }
        if( s == 'employeelist' && this.user_id == d.id ) {
            return 'highlight';
        }
    }
    this.messages.filter = function(f, id) {
        this.lastY = 0;
//        if( f == 'category' ) {
//            this[f] = unescape(id);
//        } else {
            this[f] = id;
//        }
//        if( f == 'status' || f == 'priority' || f == 'user_id' ) {
//            this.category = 'All';
//        }
        this.open();
    }
    this.messages.open = function(cb) {
        // Get the notes for the user and tenant
        this.data = {};
        M.api.getJSONCb('ciniki.atdo.messagesList', {'tnid':M.curTenantID, 'status':this.status, 'user_id':this.user_id, 'stats':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_atdo_main.messages;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.messages.addButton('add', 'Add', 'M.ciniki_atdo_main.showAdd(\'M.ciniki_atdo_main.messages.open();\',\'message\');');
    this.messages.addClose('Back');

    //
    // The default panel will show the projects in a list based on assignment
    //
    this.projects = new M.panel('Projects',
        'ciniki_atdo_main', 'projects',
        'mc', 'medium', 'sectioned', 'ciniki.atdo.main.projects');
    this.projects.sections = {};
    this.projects.data = {};
//      this.projects.noData = function() { return 'No projects found'; }
    // Live Search functions
    this.projects.liveSearchCb = function(s, i, v) {
        M.api.getJSONBgCb('ciniki.atdo.projectsSearchQuick', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'15'},
            function(rsp) {
                M.ciniki_atdo_main.projects.liveSearchShow(s, null, M.gE(M.ciniki_atdo_main.projects.panelUID + '_' + s), rsp.projects);
            });
        return true;
    };
    this.projects.liveSearchResultClass = function(s, f, i, j, d) {
        return this.sections[s].cellClasses[j];
    };
    this.projects.liveSearchResultValue = function(s, f, i, j, d) {
        if( j == 0 ) {
            if( d.viewed == 'no' ) {
                return '<b>' + d.name + '</b>';
            }
            return d.name;
        }
        return '';
    };
    this.projects.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_atdo_main.atdo.open(\'M.ciniki_atdo_main.showProjects(null, null);\', \'' + d.id + '\');'; 
    };
//      this.projects.liveSearchSubmitFn = function(s, search_str) {
//          M.ciniki_atdo_main.searchProjects('M.ciniki_atdo_main.showProjects();', search_str);
//      };
    this.projects.cellValue = function(s, i, j, d) {
        if( j == 0 ) {
            if( d.viewed == 'no' ) {
                return '<b>' + d.name + '</b>';
            }
            return d.name;
        }
    };
    this.projects.rowFn = function(s, i, d) {
        return 'M.ciniki_atdo_main.showProject(\'M.ciniki_atdo_main.showProjects(null, null);\', \'' + d.id + '\');'; 
    };
    this.projects.sectionData = function(s) { 
        return this.data[s];
    };
    this.projects.listValue = function(s, i, d) { 
        if( d['count'] != null ) {
            return d['label'] + ' <span class="count">' + d['count'] + '</span>'; 
        }
        return d['label'];
    };

    this.projects.addButton('add', 'Add', 'M.ciniki_atdo_main.showAdd(\'M.ciniki_atdo_main.showProjects();\',\'project\');');
    this.projects.addClose('Back');

    //
    // The form panel to add a new production order
    //
    this.add = new M.panel('Add Appointment',
        'ciniki_atdo_main', 'add',
        'mc', 'medium', 'sectioned', 'ciniki.atdo.main.edit');
    this.add.default_data = {
        'status':'1',
        'priority':'10',
        'private':'no',
        'appointment_date':'',
        'appointment_date_date':'',
        'appointment_duration_allday':'no',
        'appointment_repeat_interval':'1',
        'appointment_duration':'60',
        'due_duration_allday':'no',
        'due_duration':'60',
        'project_id':'0',
        'project_name':'',
        };
    this.add.data = this.add.default_data;
    this.add.forms = {};
    this.add.formtab = null;
    this.add.formtabs = {'label':'', 'field':'type', 'tabs':{
            'offering':{'label':'Class', 'visible':'no', 'field_id':0, 'fn':''},
            'appointment':{'label':'Appointment', 'visible':'no', 'field_id':1},
            'task':{'label':'Task', 'visible':'no', 'field_id':2},
            'faq':{'label':'FAQ', 'visible':'no', 'field_id':4},
            'note':{'label':'Note', 'visible':'no', 'field_id':5},
            'message':{'label':'Message', 'visible':'no', 'field_id':6},
        }};
    this.add.forms.appointment = {
        'info':{'label':'Appointment', 'aside':'yes', 'type':'simpleform', 'aside':'yes', 'fields':{
            'appointment_date':{'label':'Start', 'type':'appointment', 'caloffset':0,
                'start':'8:00',
                'end':'20:00',
                'notimelabel':'All day',
                'duration':'appointment_duration',      // Specify the duration field to update when selecting allday
                },
            'appointment_duration':{'label':'Duration', 'type':'timeduration', 'min':15, 'allday':'yes', 'date':'appointment_date', 
                'buttons':this.durationButtons},
            'subject':{'label':'Subject', 'type':'text'},
            'location':{'label':'Location', 'type':'text'},
            'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
        }},
        '_repeat':{'label':'Repeat', 'aside':'yes', 'type':'simpleform', 'aside':'yes', 'fields':{
            'appointment_repeat_type':{'label':'Type', 'type':'multitoggle', 'none':'yes', 'toggles':this.repeatOptions, 
                'fn':'M.ciniki_atdo_main.add.updateInterval'},
            'appointment_repeat_interval':{'label':'Every', 'type':'multitoggle', 'toggles':this.repeatIntervals, 'hint':' '},
            'appointment_repeat_end':{'label':'End Date', 'type':'date', 'hint':'never'},
            }},
        '_notes':{'label':'Notes', 'type':'simpleform', 'fields':{
            'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save appointment', 'fn':'M.ciniki_atdo_main.addAtdo();'},
            }},
        };
    this.add.forms.task = {
        'info':{'label':'Task', 'aside':'yes', 'type':'simpleform', 'fields':{
            'subject':{'label':'Task', 'type':'text'},
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees},
            'private':{'label':'Options', 'type':'multitoggle', 'none':'yes', 'toggles':{'no':'Public', 'yes':'Private'}},
            'priority':{'label':'Priority', 'type':'multitoggle', 'toggles':M.curTenant.atdo.priorityText},
    //      'status':{'label':'Status', 'type':'multitoggle', 'toggles':this.statuses},
            'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
            'due_date':{'label':'Due', 'type':'date'},
        }},
//          'links':{'label':'Attach', 'type':'simpleform', 'fields':{
//              // FIXME: Eventually this should allow for multiple customers
//              'customer_ids':{'label':'Customer', 'type':'fkid', 'livesearch':'yes'},
//              'product_ids':{'label':'Product', 'type':'fkid', 'livesearch':'yes'},
//          }},
//        '_due':{'label':'Due Date', 'aside':'yes', 'fields':{
//            }},
        '_appointment':{'label':'Appointment', 'aside':'yes', 'collapsable':'yes', 'collapse':'all', 'type':'simpleform', 'fields':{
            'appointment_date':{'label':'Start', 'type':'appointment', 'caloffset':0,
                'start':'8:00',
                'end':'20:00',
                'notimelabel':'All day',
                'duration':'appointment_duration',      // Specify the duration field to update when selecting allday
                },
            'appointment_duration':{'label':'Duration', 'type':'timeduration', 'min':15, 'allday':'yes', 'date':'appointment_date', 'buttons':this.durationButtons},
            'appointment_repeat_type':{'label':'Repeat', 'type':'select', 'none':'yes', 'options':this.repeatOptions, 'fn':'M.ciniki_atdo_main.add.updateInterval'},
            'appointment_repeat_interval':{'label':'Every', 'type':'multitoggle', 'toggles':this.repeatIntervals, 'hint':' '},
            'appointment_repeat_end':{'label':'End Date', 'type':'date', 'hint':'never'},
            }},
        '_notes':{'label':'Details', 'type':'simpleform', 'fields':{
            'followup':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large'},
            }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save task', 'fn':'M.ciniki_atdo_main.addAtdo();'},
            }},
        };
    this.add.forms.faq = {
        'info':{'label':'', 'type':'simpleform', 'fields':{
            'subject':{'label':'Question', 'type':'text'},
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
        }},
        '_notes':{'label':'Answer', 'type':'simpleform', 'fields':{
            'followup':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save faq', 'fn':'M.ciniki_atdo_main.addAtdo();'},
            }},
        };
    this.add.forms.note = {
        'info':{'label':'', 'type':'simpleform', 'fields':{
            'subject':{'label':'Title', 'type':'text'},
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees},
            'private':{'label':'Options', 'type':'multitoggle', 'none':'yes', 'toggles':{'no':'Public', 'yes':'Private'}},
//              'priority':{'label':'Priority', 'type':'multitoggle', 'toggles':M.curTenant.atdo.priorities},
    //      'status':{'label':'Status', 'type':'multitoggle', 'toggles':this.statuses},
            'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
        }},
        '_notes':{'label':'Details', 'type':'simpleform', 'fields':{
            'followup':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Save note', 'fn':'M.ciniki_atdo_main.addAtdo();'},
            }},
        };
    this.add.forms.message = {
        'info':{'label':'', 'type':'simpleform', 'fields':{
            'assigned':{'label':'To', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees, 'history':'no'},
            'subject':{'label':'Subject', 'type':'text'},
            'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
        }},
        '_notes':{'label':'Details', 'type':'simpleform', 'fields':{
            'followup':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_save':{'label':'', 'buttons':{
            'save':{'label':'Send', 'fn':'M.ciniki_atdo_main.addAtdo();'},
            }},
        };
    // Default to appointment
    this.add.sections = this.add.forms.appointment;
    this.add.updateInterval = function(i, t, v) {
        if( i == 'appointment_repeat_type' && v == 'toggle_on' ) {
            if( t == 'Daily' ) { M.gE(this.panelUID + '_appointment_repeat_interval_hint').innerHTML = 'days'; }
            if( t == 'Weekly' ) { M.gE(this.panelUID + '_appointment_repeat_interval_hint').innerHTML = 'weeks'; }
            if( t == 'Monthly by Date' ) { M.gE(this.panelUID + '_appointment_repeat_interval_hint').innerHTML = 'months'; }
            if( t == 'Monthly by Weekday' ) { M.gE(this.panelUID + '_appointment_repeat_interval_hint').innerHTML = 'months'; }
            if( t == 'Yearly' ) { M.gE(this.panelUID + '_appointment_repeat_interval_hint').innerHTML = 'years'; }
        } else if( i == 'appointment_repeat_type' && v == 'toggle_off' ) {
            M.gE(this.panelUID + '_appointment_repeat_interval_hint').innerHTML = ''; 
        }
    };
    this.add.fieldValue = function(s, i, d) { 
        if( i == 'project_id_fkidstr' ) { return this.data.project_name; }
        return this.data[i]; 
    };
    this.add.liveSearchCb = function(s, i, value) {
        if( i == 'category' ) {
            var rsp = M.api.getJSONBgCb('ciniki.atdo.searchCategory', {'tnid':M.curTenantID, 'start_needle':value, 'limit':35},
                function(rsp) {
                    M.ciniki_atdo_main.add.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.add.panelUID + '_' + i), rsp.categories);
                });
        }
        if( i == 'project_id' ) {
            var rsp = M.api.getJSONBgCb('ciniki.projects.searchNames', {'tnid':M.curTenantID, 'start_needle':value, 'limit':25},
                function(rsp) {
                    M.ciniki_atdo_main.add.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.add.panelUID + '_' + i), rsp.projects);
                });
        }
// Going to be added in the future...
//      if( i == 'customer_ids' ) {
//          var rsp = M.api.getJSONBgCb('ciniki.customers.searchQuick', {'tnid':M.curTenantID, 'start_needle':value, 'limit':25},
//              function(rsp) { 
//                  M.ciniki_atdo_main.add.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.add.panelUID + '_' + i), rsp.customers); 
//              });
//      } else if( i == 'product_ids' ) {
//          var rsp = M.api.getJSONBgCb('ciniki.products.searchQuick', {'tnid':M.curTenantID, 'start_needle':value, 'limit':25},
//              function(rsp) { 
//                  M.ciniki_atdo_main.add.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.add.panelUID + '_' + i), rsp.products); 
//              });
//      }
    };
    this.add.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'category' && d != null ) { return d.name; }
        if( f == 'project_id' && d != null ) { return d.name; }
//      if( f == 'product_ids') {  return d.product.name; }
//      if( f == 'customer_ids') {  return d.customer.name; }
        return '';
    };
    this.add.liveSearchResultRowFn = function(s, f, i, j, d) { 
        if( f == 'category' && d != null ) {
            return 'M.ciniki_atdo_main.add.updateCategory(\'' + s + '\',\'' + escape(d.name) + '\');';
        }
        if( f == 'project_id' ) {
            return 'M.ciniki_atdo_main.add.updateProject(\'' + s + '\',\'' + escape(d.name) + '\',\'' + d.id + '\');';
        }
    };
    this.add.updateCategory = function(s, category) {
        M.gE(this.panelUID + '_category').value = unescape(category);
        this.removeLiveSearch(s, 'category');
    };
    this.add.updateProject = function(s, project_name, project_id) {
        M.gE(this.panelUID + '_project_id').value = project_id;
        M.gE(this.panelUID + '_project_id_fkidstr').value = unescape(project_name);
        this.removeLiveSearch(s, 'project_id');
    };
//      this.add.updateCustomer = function(s, customer_name, customer_id) {
//          M.gE(this.panelUID + '_customer_ids').value = customer_id;
//          M.gE(this.panelUID + '_customer_ids_fkidstr').value = unescape(customer_name);
//          this.removeLiveSearch(s, 'customer_ids');
//      };
//      this.add.updateProduct = function(s, product_name, product_id) {
//          M.gE(this.panelUID + '_product_ids').value = product_id;
//          M.gE(this.panelUID + '_product_ids_fkidstr').value = unescape(product_name);
//          this.removeLiveSearch(s, 'product_ids');
//      };

    this.add.listValue = function(s, i, d) { return d['label']; };
    this.add.listFn = function(s, i, d) { return d['fn']; };

    this.add.liveAppointmentDayEvents = function(i, day, cb) {
        // Search for events on the specified day
        if( i == 'appointment_date' ) {
            if( day == '--' ) { day = 'today'; }
            M.api.getJSONCb('ciniki.calendars.appointments', {'tnid':M.curTenantID, 'date':day}, cb);
//              if( rsp.stat == 'ok' ) {
//                  return rsp['appointments'];
//              }
        }
//          return {};
    };
    this.add.appointmentEventText = function(ev) { return ev['subject']; };
    this.add.appointmentColour = function(ev) {
        if( ev != null && ev['colour'] != null && ev['colour'] != '' ) {
            return ev['colour'];
        }
        return '#aaddff';
    };
    this.add.addButton('save', 'Save', 'M.ciniki_atdo_main.addAtdo();');
    this.add.addClose('Cancel');

    //
    // Then to display an bottling appointment
    //
    this.atdo = new M.panel('Atdo',
        'ciniki_atdo_main', 'atdo',
        'mc', 'medium mediumaside', 'sectioned', 'ciniki.atdo.main.edit');
    this.atdo.aid = 0;
    this.atdo.data = null;
    this.atdo.cb = null;
    this.atdo.subject = '';
    this.atdo.forms = {};
    this.atdo.formtab = null;
    this.atdo.formtabs = {'label':'', 'field':'type', 'tabs':{
            'appointment':{'label':'Appointment', 'field_id':1},
            'task':{'label':'Task', 'field_id':2},
            'faq':{'label':'FAQ', 'field_id':4},
            'note':{'label':'Note', 'field_id':5},
            'message':{'label':'Message', 'field_id':6},
        }};
    this.atdo.forms.appointment = {
        'info':{'label':'', 'aside':'yes', 'fields':{
            'appointment_date':{'label':'Start', 'type':'appointment', 'caloffset':0,
                'start':'8:00',
                'end':'20:00',
                'notimelabel':'All day',
                'duration':'appointment_duration',      // Specify the duration field to update when selecting allday
                },
            'appointment_duration':{'label':'Duration', 'type':'timeduration', 'min':15, 'allday':'yes', 
                'date':'appointment_date', 'buttons':this.durationButtons},
            'subject':{'label':'Subject', 'type':'text'},
            'location':{'label':'Location', 'type':'text'},
            'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
            'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees, 'history':'no'},
        }},
        '_repeat':{'label':'Repeat', 'aside':'yes', 'fields':{
            'appointment_repeat_type':{'label':'', 'type':'multitoggle', 'none':'yes', 'toggles':this.repeatOptions},
            'appointment_repeat_interval':{'label':'Every', 'type':'multitoggle', 'toggles':this.repeatIntervals, 'hint':' '},
            'appointment_repeat_end':{'label':'End Date', 'type':'date', 'hint':'never'},
            }},
        '_notes':{'label':'Notes', 'fields':{
            'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save appointment', 'fn':'M.ciniki_atdo_main.atdo.save();'},
            'delete':{'label':'Delete appointment', 'fn':'M.ciniki_atdo_main.atdo.remove();'},
            }},
//          '_delete':{'label':'', 'buttons':{
//              }},
        };
    this.atdo.forms.task = {
        'info':{'label':'', 'type':'simpleform', 'aside':'yes', 'fields':{
            'subject':{'label':'Title', 'type':'text'},
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees, 'history':'no'},
            'private':{'label':'Options', 'type':'toggle', 'none':'yes', 'toggles':{'no':'Public', 'yes':'Private'}},
            'priority':{'label':'Priority', 'type':'toggle', 'toggles':M.curTenant.atdo.priorityText,},
            'status':{'label':'Status', 'type':'toggle', 'toggles':this.statuses},
            'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
            'due_date':{'label':'Due Date', 'type':'date'},
        }},
//          'links':{'label':'Attach', 'type':'simpleform', 'fields':{
//              // FIXME: Eventually this should allow for multiple customers
//              // FIXME: The sync will not work for attachments
//              'customer_ids':{'label':'Customer', 'type':'fkid', 'livesearch':'yes'},
//              'product_ids':{'label':'Product', 'type':'fkid', 'livesearch':'yes'},
//          }},
//        '_due':{'label':'Due Date', 'aside':'yes', 'fields':{
//            }},
        '_appointment':{'label':'Appointment', 'collapsable':'yes', 'aside':'yes', 'collapse':'compact', 'type':'simpleform', 'fields':{
            'appointment_date':{'label':'Start', 'type':'appointment', 'caloffset':0,
                'start':'8:00',
                'end':'20:00',
                'notimelabel':'All day',
                'duration':'appointment_duration',      // Specify the duration field to update when selecting allday
                },
            'appointment_duration':{'label':'Duration', 'type':'timeduration', 'min':15, 'allday':'yes', 
                'date':'appointment_date', 'buttons':this.durationButtons},
            'appointment_repeat_type':{'label':'Repeat', 'type':'select', 'none':'yes', 'options':this.repeatOptions, 'fn':'M.ciniki_atdo_main.atdo.updateInterval'},
            'appointment_repeat_interval':{'label':'Every', 'type':'toggle', 'toggles':this.repeatIntervals, 'hint':' '},
            'appointment_repeat_end':{'label':'End Date', 'type':'date', 'hint':'never'},
            }},
//          'links':{'label':'Additional Information', 'type':'simplelist', 'list':{
//              'customer_ids':{'label':'Customer', 'value':''},
//              'product_ids':{'label':'Product', 'value':''},
//              'customer_ids':{'label':'Customer', 'type':'fkid', 'livesearch':'yes'},
//              'product_ids':{'label':'Product', 'type':'fkid', 'livesearch':'yes'},
//              }},
        'thread':{'label':'', 'type':'simplethread'},
        '_followup':{'label':'Add your response', 'fields':{
            'followup':{'label':'Details', 'hidelabel':'yes', 'type':'textarea', 'history':'no'},
            }},
        '_update':{'label':'', 'type':'simplebuttons', 'buttons':{
            'update':{'label':'Save', 'fn':'M.ciniki_atdo_main.atdo.save();'},
            'delete':{'label':'Delete', 
                'visible':function() { return M.curTenant.permissions.owners != null ? 'yes' : 'no'; },
                'fn':'M.ciniki_atdo_main.atdo.remove();',
                },
            }},
//        '_addfollowup':{'label':'', 'type':'simplebuttons', 'buttons':{
//            'add':{'label':'Save', 'fn':'M.ciniki_atdo_main.atdo.save();'},
//            }},
        };
    this.atdo.forms.faq = {
        'info':{'label':'', 'type':'simpleform', 'fields':{
            'subject':{'label':'Question', 'type':'text'},
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
        }},
        'thread':{'label':'', 'type':'simplethread'},
        '_followup':{'label':'Add your response', 'fields':{
            'followup':{'label':'Details', 'hidelabel':'yes', 'type':'textarea', 'history':'no'},
            }},
        '_addfollowup':{'label':'', 'type':'simplebuttons', 'buttons':{
            'add':{'label':'Save', 'fn':'M.ciniki_atdo_main.atdo.save();'},
            'delete':{'label':'Delete', 
                'visible':function() { return M.curTenant.permissions.owners != null ? 'yes' : 'no'; },
                'fn':'M.ciniki_atdo_main.atdo.remove();',
                },
            }},
        };
    this.atdo.forms.note = {
        'info':{'label':'', 'aside':'yes', 'type':'simpleform', 'fields':{
            'subject':{'label':'Title', 'type':'text'},
            'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
            'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees, 'history':'no'},
            'private':{'label':'Options', 'type':'multitoggle', 'none':'yes', 'toggles':{'no':'Public', 'yes':'Private'}, 'history':'no'},
            'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
        }},
        'thread':{'label':'Note', 'type':'simplethread'},
        '_followup':{'label':'Add your response', 'fields':{
            'followup':{'label':'Details', 'hidelabel':'yes', 'type':'textarea', 'history':'no'},
            }},
        '_addfollowup':{'label':'', 'type':'simplebuttons', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_atdo_main.atdo.save();'},
            'delete':{'label':'Delete', 
                'visible':function() { return M.curTenant.permissions.owners != null ? 'yes' : 'no'; },
                'fn':'M.ciniki_atdo_main.atdo.remove();',
                },
            }},
        };
    this.atdo.forms.message = {
        'info':{'label':'Details', 'type':'simpleform', 'aside':'yes', 'fields':{
            'assigned':{'label':'To', 'type':'multiselect', 'none':'yes', 'options':M.curTenant.employees, 'history':'no', 'viewed':'viewed', 'deleted':'deleted'},
            'status':{'label':'Status', 'type':'multitoggle', 'toggles':{'1':'Open', '60':'Closed'}, 'history':'no'},
            'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
        }},
        'thread':{'label':'Message', 'type':'simplethread'},
        '_followup':{'label':'Add your response', 'fields':{
            'followup':{'label':'Details', 'hidelabel':'yes', 'type':'textarea', 'history':'no'},
            }},
        '_addfollowup':{'label':'', 'type':'simplebuttons', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_atdo_main.atdo.save();'},
            'trash':{'label':'Delete', 'class':'delete',
                'visible':function() { return M.curTenant.permissions.owners == null ? 'yes' : 'no'; },
                'fn':'M.ciniki_atdo_main.atdo.save(\'user\');',
                },
            'delete':{'label':'Delete', 
                'visible':function() { return M.curTenant.permissions.owners != null ? 'yes' : 'no'; },
                'fn':'M.ciniki_atdo_main.atdo.remove();',
                },
            }},
        };
    this.atdo.sections = this.atdo.forms.task;
    this.atdo.sectionData = function(s) {
        if( s == 'info' ) { return this.sections[s].list; }
        if( s == 'thread' ) { return this.data['followups']; }
        return this.data['orders'];
    };
    this.atdo.listFn = function(s, i, d) { return d['fn']; };
    this.atdo.fieldValue = function(s, i, d) { 
//          if( i == 'appointment_duration_allday' ) { return this.data['allday']; }
        if( i == 'appointment_date_date' ) { return this.data['appointment_date_date']; }
//          if( i == 'due_duration_allday' ) { return this.data['due_allday']; }
        if( i == 'due_date_date' ) { return this.data['due_date_date']; }
        if( i == 'appointment_repeat_interval' && this.data[i] == 0 ) { return 1; }
        if( i == 'project_id_fkidstr' ) { return this.data.project_name; }
        if( this.data[i] == '0000-00-00' ) {
            return '';
        } else if( this.data[i] == '0000-00-00 00:00:00' ) {
            return '';
        }
        return this.data[i];
    };
    this.atdo.listLabel = function(s, i, d) { return d['label']; }
    this.atdo.listValue = function(s, i, d) { 
        if( s == 'info' ) {
            if( i == 'assigned' ) {
                var str = '';
                for(var i=0;i<this.data.assigned.length;i++) {
                    if( str == '' ) {
                        str = this.data.assigned[i].user.display_name;
                    } else {
                        str += ', ' + this.data.assigned[i].user.display_name;
                    }
                }
                return str;
            }
            return this.data[i];
        }
    };
    this.atdo.liveSearchCb = function(s, i, value) {
        if( i == 'category' ) {
            var rsp = M.api.getJSONBgCb('ciniki.atdo.searchCategory', {'tnid':M.curTenantID, 'start_needle':value, 'limit':35},
                function(rsp) {
                    M.ciniki_atdo_main.atdo.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.atdo.panelUID + '_' + i), rsp.categories);
                });
        }
        if( i == 'project_id' ) {
            var rsp = M.api.getJSONBgCb('ciniki.projects.searchNames', {'tnid':M.curTenantID, 'start_needle':value, 'limit':25},
                function(rsp) {
                    M.ciniki_atdo_main.atdo.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.atdo.panelUID + '_' + i), rsp.projects);
                });
        }
    };
    this.atdo.liveSearchResultValue = function(s, f, i, j, d) {
        if( f == 'category' && d != null ) { return d.name; }
        if( f == 'project_id' && d != null ) { return d.name; }
        return '';
    };
    this.atdo.liveSearchResultRowFn = function(s, f, i, j, d) { 
        if( f == 'category' && d != null ) {
            return 'M.ciniki_atdo_main.atdo.updateCategory(\'' + s + '\',\'' + escape(d.name) + '\');';
        }
        if( f == 'project_id' && d != null ) {
            return 'M.ciniki_atdo_main.atdo.updateParent(\'' + s + '\',\'' + escape(d.name) + '\',\'' + d.id + '\');';
        }
    };
    this.atdo.updateCategory = function(s, category) {
        M.gE(this.panelUID + '_category').value = unescape(category);
        this.removeLiveSearch(s, 'category');
    };
    this.atdo.updateParent = function(s, project_name, project_id) {
        M.gE(this.panelUID + '_project_id').value = project_id;
        M.gE(this.panelUID + '_project_id_fkidstr').value = unescape(project_name);
        this.removeLiveSearch(s, 'project_id');
    };
    this.atdo.threadSubject = function(s) { return this.subject; }
    this.atdo.threadFollowupUser = function(s, i, d) { return d.followup.user_display_name; }
    this.atdo.threadFollowupAge = function(s, i, d) { return d.followup.age; }
    this.atdo.threadFollowupDateTime = function(s, i, d) { return d.followup.date_added; }
    this.atdo.threadFollowupContent = function(s, i, d) { return d.followup.content; }
    this.atdo.liveAppointmentDayEvents = this.add.liveAppointmentDayEvents;
    this.atdo.appointmentEventText = this.add.appointmentEventText;
    this.atdo.appointmentColour = this.add.appointmentColour;
    this.atdo.updateInterval = this.add.updateInterval;
    this.atdo.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.atdo.getHistory', 'args':{'tnid':M.curTenantID, 
            'atdo_id':M.ciniki_atdo_main.atdo.atdo_id, 'field':i}};
    }
    this.atdo.open = function(cb, aid) {
        if( aid != null ) { this.atdo_id = aid; }
        this.reset();
        this.formtab = null;
        this.formtab_field_id = null;
        this.subject = '';
        M.api.getJSONCb('ciniki.atdo.get', {'tnid':M.curTenantID, 'atdo_id':this.atdo_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_atdo_main.atdo;
            console.log(rsp);
            p.data = rsp.atdo;
            p.org_data = rsp.atdo;  // Store original data, to use in comparison when saving to know what changed
            // Need to set the followup to blank, incase they add one it will get sent to the update
            p.data.followup = '';
            p.subject = rsp.atdo.subject;
            var employeeList = [];
            for(var i in M.curTenant.employees) {
                employeeList[i] = M.curTenant.employees[i];
            }
            // Add any old employees that are current
            for(var i in rsp.atdo.users) {
                if( employeeList[rsp.atdo.users[i].user.user_id] == null ) {
                    employeeList[rsp.atdo.users[i].user.user_id] = rsp.atdo.users[i].user.display_name;
                }
            }
            p.forms.appointment.info.fields.assigned.options = employeeList;
            p.forms.task.info.fields.assigned.options = employeeList;
            p.forms.note.info.fields.assigned.options = employeeList;
            p.forms.message.info.fields.assigned.options = employeeList;
            if( rsp.atdo.type == '1' ) {
                p.size = 'medium';
            } else {
                p.size = 'medium mediumaside';
            }
            p.refresh();
            p.show(cb);
            p.updateInterval('appointment_repeat_type', M.ciniki_atdo_main.repeatOptions[rsp.atdo.appointment_repeat_type], 'toggle_on');
        });
    };
    this.atdo.save = function(del) {
        // Reset data to the original loaded data, so we know what changed and only send changes to server
        this.data = this.org_data;
        var c = M.ciniki_atdo_main.getContent(this, 'no', this.data['appointment_duration_allday'], this.data['due_duration_allday']);

        // Check if the message (or other) should be removed from the users view
        if( del == 'user' ) {
            c += '&userdelete=yes';
        }

        if( c != '' ) {
            M.api.postJSONCb('ciniki.atdo.update', {'tnid':M.curTenantID, 'atdo_id':this.atdo_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_atdo_main.atdo.close();
            });
        } else {
            this.close();
        }
    };
    this.atdo.remove = function() {
        var type = 'item';
        if( this.data.type == 1 ) {
            type = 'appointment';
        } else if( this.data.type == 2 ) {
            type = 'task';
        } else if( this.data.type == 3 ) {
            type = 'document';
        } else if( this.data.type == 4 ) {
            type = 'faq';
        } else if( this.data.type == 5 ) {
            type = 'note';
        } else if( this.data.type == 6 ) {
            type = 'message';
        }
        M.confirm("Are you sure you want to delete this " + type + "?",null,function() {
            M.api.getJSONCb('ciniki.atdo.atdoDelete', {'tnid':M.curTenantID, 'atdo_id':M.ciniki_atdo_main.atdo.atdo_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_atdo_main.atdo.close();
            });
        });
    }
    this.atdo.addButton('save', 'Save', 'M.ciniki_atdo_main.atdo.save();');
    this.atdo.addClose('Cancel');

    //
    // The search panel will list all search results for a string.  This allows more advanced searching,
    // and will search the entire strings, not just start of the string like livesearch
    //
    this.search = new M.panel('Search Results',
        'ciniki_atdo_main', 'search',
        'mc', 'xlarge', 'sectioned', 'ciniki.atdo.main.search');
    this.search.sections = {
        'results':{'label':'', 'num_cols':4, 'type':'simplegrid',
            'headerValues':['', 'Task', 'Status', 'Due'],
            'cellClasses':['multiline aligncenter', 'multiline', 'multiline', ''],
            },
    };
    this.search.data = {};
    this.search.noData = function() { return 'No items found'; }
    this.search.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return '<span class="icon">' + M.ciniki_atdo_main.symbolpriorities[d.priority] + '</span>';
            case 1: return '<span class="maintext">' + d.subject + '</span><span class="subtext">' + d.assigned_users + '&nbsp;</span>';
            case 2: return d.status;
            case 3: return '<span class="maintext">' + d.due_date + '</span>';
        }
        return '';
    };
    this.search.rowStyle = function(s, i, d) {
        if( d.status != 'Completed' ) { return 'background: ' + M.curTenant.atdo.settings['tasks.priority.' + d.priority]; }
        else { return 'background: ' + M.curTenant.atdo.settings['tasks.status.60']; }
    };
    this.search.rowFn = function(s, i, d) {
        if( this.last_search_completed == 'yes' ) {
            return 'M.ciniki_atdo_main.atdo.open(\'M.ciniki_atdo_main.showCompleted(null);\', \'' + d.id + '\');'; 
        }
        return 'M.ciniki_atdo_main.atdo.open(\'M.ciniki_atdo_main.searchTasks(null, null);\', \'' + d.id + '\');'; 
    };
    this.search.sectionData = function(s) { 
        if( s == 'results' ) { return this.data; }
        return null;
    };
    this.search.addClose('Back');

    //
    // Arguments:
    // aG - The arguments to be parsed into args
    //
    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }

        //
        // Reset all employee lists, must be done when switching tenants
        //
        this.add.forms.task.info.fields.assigned.options = M.curTenant.employees;
        this.add.forms.note.info.fields.assigned.options = M.curTenant.employees;
        this.add.forms.message.info.fields.assigned.options = M.curTenant.employees;
        this.atdo.forms.task.info.fields.assigned.options = M.curTenant.employees;
        this.atdo.forms.note.info.fields.assigned.options = M.curTenant.employees;
        this.atdo.forms.message.info.fields.assigned.options = M.curTenant.employees;

        if( M.curTenant.modules['ciniki.projects'] != null ) {
            this.add.forms.appointment.info.fields.project_id.active = 'yes';
            this.add.forms.task.info.fields.project_id.active = 'yes';
            this.add.forms.note.info.fields.project_id.active = 'yes';
            this.add.forms.message.info.fields.project_id.active = 'yes';
            this.atdo.forms.appointment.info.fields.project_id.active = 'yes';
            this.atdo.forms.task.info.fields.project_id.active = 'yes';
            this.atdo.forms.note.info.fields.project_id.active = 'yes';
            this.atdo.forms.message.info.fields.project_id.active = 'yes';
        } else {
            this.add.forms.appointment.info.fields.project_id.active = 'no';
            this.add.forms.task.info.fields.project_id.active = 'no';
            this.add.forms.note.info.fields.project_id.active = 'no';
            this.add.forms.message.info.fields.project_id.active = 'no';
            this.atdo.forms.appointment.info.fields.project_id.active = 'no';
            this.atdo.forms.task.info.fields.project_id.active = 'no';
            this.atdo.forms.note.info.fields.project_id.active = 'no';
            this.atdo.forms.message.info.fields.project_id.active = 'no';
        }

        //
        // Determine which parts of atdo are visible
        //
        this.add.formtabs.tabs.appointment.visible = (M.curTenant.modules['ciniki.atdo'].flags&0x01)==0x01?'yes':'no';
        this.add.formtabs.tabs.task.visible = (M.curTenant.modules['ciniki.atdo'].flags&0x02)==0x02?'yes':'no';
        this.add.formtabs.tabs.faq.visible = (M.curTenant.modules['ciniki.atdo'].flags&0x08)==0x08?'yes':'no';
        this.add.formtabs.tabs.note.visible = (M.curTenant.modules['ciniki.atdo'].flags&0x10)==0x10?'yes':'no';
        this.add.formtabs.tabs.message.visible = (M.curTenant.modules['ciniki.atdo'].flags&0x20)==0x20?'yes':'no';
        this.atdo.formtabs.tabs.appointment.visible = (M.curTenant.modules['ciniki.atdo'].flags&0x01)==0x01?'yes':'no';
        this.atdo.formtabs.tabs.task.visible = (M.curTenant.modules['ciniki.atdo'].flags&0x02)==0x02?'yes':'no';
        this.atdo.formtabs.tabs.faq.visible = (M.curTenant.modules['ciniki.atdo'].flags&0x08)==0x08?'yes':'no';
        this.atdo.formtabs.tabs.note.visible = (M.curTenant.modules['ciniki.atdo'].flags&0x10)==0x10?'yes':'no';
        this.atdo.formtabs.tabs.message.visible = (M.curTenant.modules['ciniki.atdo'].flags&0x20)==0x20?'yes':'no';

        //
        // Create the app container if it doesn't exist, and clear it out
        // if it does exist.
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_atdo_main', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        this.cb = cb;
        // this.files.show(cb);
        if( args.atdo_id != null && args.atdo_id != '' ) {
            this.atdo.open(cb, args.atdo_id);
//      } else if( args['date'] != null ) {
//          this.tasks.open(cb, args['date']);
        } else if( args.tasksearch != null && args.tasksearch != '' ) {
            this.searchTasks(cb, args.tasksearch);
        } else if( args.add != null && (args.add == 'task' || args.add == 'appointment' || args.add == 'faq' || args.add == 'note' || args.add == 'message' ) ) {
            this.showAdd(cb, args.add, args.date, args.time, args.allday);
        } else if( args.addtoproject != null && (args.addtoproject == 'task' || args.addtoproject == 'appointment' 
                || args.addtoproject == 'note' || args.addtoproject == 'message') ) {
            this.showAddToProject(cb, args.addtoproject, args.project_id, args.project_name);
        } else if( args.tasks != null && args.tasks == 'yes' ) {
            this.tasks.open(cb, null);
        } else if( args.messages != null && args.messages == 'yes' ) {
            this.messages.open(cb, null);
        } else if( args.faq != null && args.faq == 'yes' ) {
            this.faqs.open(cb, null);
        } else if( args.notes != null && args.notes == 'yes' ) {
            this.notes.open(cb, null);
        } else {
            this.tasks.open(cb, null);
        }
    }




    //
    // cb - callback
    // type - type of form to show
    // d - date
    // t - time
    // ad - allday flag
    //
    this.showAdd = function(cb, type, d, t, ad) {
        this.setupAdd(type, d, t, ad);
        this.add.formtabs.tabs.offering.visible = 'no';
        if( M.curTenant.modules['ciniki.fatt'] != null ) {
            this.add.formtabs.tabs.offering.visible = 'yes';
            this.add.formtabs.tabs.offering.fn = 'M.startApp(\'ciniki.fatt.offerings\',null,\'' + cb + '\',\'mc\',{\'add\':\'courses\',\'date\':\'' + d + '\',\'time\':\'' + t + '\',\'allday\':\'' + ad + '\'});';
        }
        this.add.refresh();
        this.add.show(cb);
    };

    this.setupAdd = function(type, d, t, ad) {
        this.add.reset();
        this.add.data = this.add.default_data;
        this.add.data.project_id = 0;
        this.add.data.project_name = '';
        if( type == 'task' ) {  
            this.add.data['private'] = 'yes';
        }
        if( d != null ) {
            if( ad == 1 ) {
                this.add.data.appointment_date = M.dateFormat(d);
                this.add.data.appointment_allday = 'yes';
                this.add.data.appointment_duration_allday = 'yes';
            } else {
                this.add.data.appointment_date = M.dateFormat(d) + ' ' + t;
                this.add.data.appointment_allday = 'no';
                this.add.data.appointment_duration_allday = 'no';
            }
        }
        this.add.formtab = type;
        if( M.modFlagOn('ciniki.atdo', 0x02) ) {
            this.add.size = 'medium mediumaside';
        } else {
            this.add.size = 'medium';
        }
    };

    this.showAddToProject = function(cb, type, pid, pname) {
        this.setupAdd(type);
        if( pid != null ) {
            this.add.data.project_id = pid;
            this.add.data.project_name = unescape(pname);
        }
        this.add.refresh();
        this.add.show(cb);
    };

    this.addAtdo = function() {
        var c = this.getContent(this.add, 'yes', null, null);
        M.api.postJSONCb('ciniki.atdo.add', {'tnid':M.curTenantID}, c, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            } 
            M.ciniki_atdo_main.add.close();
        });
    };

    this.getContent = function(p, s, oad, odad) {
        var subject = M.gE(p.panelUID + '_subject');
        if( s == 'yes' && subject.value == '' ) {
            M.alert('No subject specified');
            return false;
        }
        var c = p.serializeForm(s);
        var aad = 'no';
        var type = p.formtabs.tabs[p.formtab].field_id;
        if( type == 1 || type == 2 ) {
            if( M.gE(p.panelUID + '_appointment_duration_buttons_allday').childNodes[0].className == 'toggle_on' ) {
                aad = 'yes';
            }
            if( oad == null || aad != oad ) {
                c += '&appointment_allday=' + aad;
            }
        }
/*        if( type == 2 ) {
            var dad = 'no';
            if( M.gE(p.panelUID + '_due_duration_buttons_allday').childNodes[0].className == 'toggle_on' ) {
                dad = 'yes';
            }
            if( odad == null || dad != odad ) {
                c += '&due_allday=' + dad;
            }
        } */
        return c;   
    };



    
    this.searchTasks = function(cb, search_str) {
        if( search_str == null || search_str == 'null' ) {
            if( this.search.last_search_str != null ) {
                search_str = this.search.last_search_str;
            } else {
                search_str = '';
            }
        } else {
            this.search.last_search_str = search_str;
        }
        this.search.last_search_completed = 'no';
        var rsp = M.api.getJSONBg('ciniki.atdo.tasksSearchFull', {'tnid':M.curTenantID, 'start_needle':search_str, 'limit':100, 'full':'yes'});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        this.search.data = rsp.tasks;
        this.search.refresh();
        this.search.show(cb);
    }

    this.showCompleted = function(cb) {
        this.search.last_search_completed = 'yes';
        var rsp = M.api.getJSONBg('ciniki.atdo.tasksSearchFull', {'tnid':M.curTenantID, 'start_needle':'', 
            'limit':100, 'full':'yes', 'completed':'yes'});
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        this.search.data = rsp.tasks;
        this.search.refresh();
        this.search.show(cb);
    }
}
