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
	this.repeatOptions = {'10':'Daily', '20':'Weekly', '30':'Monthly by Date', '31':'Monthly by Weekday','40':'Yearly'};
	this.repeatIntervals = {'1':'1', '2':'2', '3':'3', '4':'4', '5':'5', '6':'6', '7':'7', '8':'8'};
	this.statuses = {'1':'Open', '60':'Completed'};
	this.symbolpriorities = {'10':'Q', '30':'W', '50':'E'};	// also stored in core_menu.js

	this.init = function() {

		//
		// The default panel will show the tasks in a list based on assignment
		//
		this.tasks = new M.panel('Tasks',
			'ciniki_atdo_main', 'tasks',
			'mc', 'medium', 'sectioned', 'ciniki.atdo.main.tasks');
		this.tasks.data = null;
		this.tasks.sections = {
			'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':3, 'hint':'search', 
				'noData':'No tasks found',
				'headerValues':['', 'Task', 'Due'],
				'cellClasses':['multiline aligncenter', 'multiline', 'multiline'],
				},
			'assigned':{'label':'Your Tasks', 'num_cols':3, 'type':'simplegrid', 
				'headerValues':['', 'Task', 'Due'],
				'cellClasses':['multiline aligncenter', 'multiline', 'multiline'],
				'noData':'No tasks assigned to you',
				},
			'business':{'label':'General Tasks', 'num_cols':3, 'type':'simplegrid', 
				'headerValues':['', 'Task', 'Due'],
				'cellClasses':['multiline aligncenter', 'multiline', 'multiline'],
				'noData':'No business tasks',
				},
			'other':{'label':'Other Tasks', 'type':'simplelist', 'list':{
				'closed':{'label':'Recently Completed', 'fn':'M.ciniki_atdo_main.showCompleted(\'M.ciniki_atdo_main.showTasks();\')'},
				'ctb':{'label':'Call to Book', 'visible':'no', 'count':0, 'fn':'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_atdo_main.showTasks();\',\'mc\',{\'ctb\':\'yes\'});'},
				}},
//			'closed':{'label':'Closed Recently', 'num_cols':3, 'type':'simplegrid', 
//				'headerValues':['Priority', 'Task', 'Due'],
//				'cellClasses':['multiline', 'multiline', 'multiline'],
//				'noData':'No closed tasks',
//				},
		};
		this.tasks.data = {};
		this.tasks.noData = function() { return 'No tasks found'; }
		// Live Search functions
		this.tasks.liveSearchCb = function(s, i, v) {
			M.api.getJSONBgCb('ciniki.atdo.tasksSearchQuick', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'15'},
				function(rsp) {
					M.ciniki_atdo_main.tasks.liveSearchShow(s, null, M.gE(M.ciniki_atdo_main.tasks.panelUID + '_' + s), rsp.tasks);
				});
			return true;
		};
		this.tasks.liveSearchResultClass = function(s, f, i, j, d) {
			return this.sections[s].cellClasses[j];
		};
		this.tasks.liveSearchResultValue = function(s, f, i, j, d) {
			if( j == 1 ) {
				var pname = '';
				if( d.task.project_name != null && d.task.project_name != '' ) {
					pname = ' <span class="subdue">[' + d.task.project_name + ']</span>';
				}
				return '<span class="maintext">' + d.task.subject + pname + '</span><span class="subtext">' + d.task.assigned_users + '&nbsp;</span>';
			}
			switch(j) {
				case 0: return '<span class="icon">' + M.ciniki_atdo_main.symbolpriorities[d.task.priority] + '</span>';
				case 2: return '<span class="maintext">' + d.task.due_date + '</span><span class="subtext">' + d.task.due_time + '</span>';
			}
			return '';
		};
		this.tasks.liveSearchResultRowFn = function(s, f, i, j, d) {
			return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_atdo_main.showTasks(null, null);\', \'' + d.task.id + '\');'; 
		};
		this.tasks.liveSearchResultRowStyle = function(s, f, i, d) {
			if( d.task.status != 'closed' ) { return 'background: ' + M.curBusiness.atdo.settings['tasks.priority.' + d.task.priority]; }
			else { return 'background: ' + M.curBusiness.atdo.settings['tasks.status.60']; }
			return '';
		};
		this.tasks.liveSearchSubmitFn = function(s, search_str) {
			M.ciniki_atdo_main.searchTasks('M.ciniki_atdo_main.showTasks();', search_str);
		};
//		this.tasks.liveSearchResultValue = function(s, f, i, j, d) {
//			return this.cellValue(s, i, j, d);
//		}
		this.tasks.cellValue = function(s, i, j, d) {
			if( j == 0 ) { return '<span class="icon">' + M.ciniki_atdo_main.symbolpriorities[d.task.priority] + '</span>'; }
			if( j == 1 ) {
				var pname = '';
				if( d.task.project_name != null && d.task.project_name != '' ) {
					pname = ' <span class="subdue">[' + d.task.project_name + ']</span>';
				}
				return '<span class="maintext">' + d.task.subject + pname + '</span><span class="subtext">' + d.task.assigned_users + '&nbsp;</span>';
			}
			if( j == 2 ) { return '<span class="maintext">' + d.task.due_date + '</span><span class="subtext">' + d.task.due_time + '</span>'; }
		};
		this.tasks.rowStyle = function(s, i, d) {
			if( d != null && d.task != null ) {
				if( d.task.status != 'closed' ) { return 'background: ' + M.curBusiness.atdo.settings['tasks.priority.' + d.task.priority]; }
				else { return 'background: ' + M.curBusiness.atdo.settings['tasks.status.60']; }
			}
			return '';
		};
		this.tasks.rowFn = function(s, i, d) {
			return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_atdo_main.showTasks(null, null);\', \'' + d.task.id + '\');'; 
		};
		this.tasks.sectionData = function(s) { 
			if( s == 'other' ) {
				return this.sections.other.list;
			}
			return this.data[s];
		};
		this.tasks.listValue = function(s, i, d) { 
			return d['label'];
		};

		this.tasks.addButton('add', 'Add', 'M.ciniki_atdo_main.showAdd(\'M.ciniki_atdo_main.showTasks();\',\'task\');');
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
			'business':{'label':'General Notes', 'num_cols':1, 'type':'simplegrid', 
				'headerValues':null,
				'cellClasses':[''],
				'noData':'No business notes',
				},
		};
		this.faqs.data = {};
		this.faqs.noData = function() { return 'No notes found'; }
		// Live Search functions
		this.faqs.liveSearchCb = function(s, i, v) {
			M.api.getJSONBgCb('ciniki.atdo.faqsSearchQuick', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'15'},
				function(rsp) {
					M.ciniki_atdo_main.faqs.liveSearchShow(s, null, M.gE(M.ciniki_atdo_main.faqs.panelUID + '_' + s), rsp.faqs);
				});
			return true;
		};
		this.faqs.liveSearchResultClass = function(s, f, i, j, d) {
			return this.sections[s].cellClasses[j];
		};
		this.faqs.liveSearchResultValue = function(s, f, i, j, d) {
			switch(j) {
				case 0: return d.faq.subject;
			}
			return '';
		};
		this.faqs.liveSearchResultRowFn = function(s, f, i, j, d) {
			return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_atdo_main.showFAQs(null, null);\', \'' + d.faq.id + '\');'; 
		};
//		this.faqs.liveSearchSubmitFn = function(s, search_str) {
//			M.ciniki_atdo_main.searchNotes('M.ciniki_atdo_main.showNotes();', search_str);
//		};
		this.faqs.cellValue = function(s, i, j, d) {
			if( j == 0 ) { return d.faq.subject; }
		};
		this.faqs.rowFn = function(s, i, d) {
			return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_atdo_main.showFAQs(null, null);\', \'' + d.faq.id + '\');'; 
		};
		this.faqs.sectionData = function(s) { 
			return this.data[s];
		};
		this.faqs.listValue = function(s, i, d) { 
			if( d['count'] != null ) {
				return d['label'] + ' <span class="count">' + d['count'] + '</span>'; 
			}
			return d['label'];
		};

		this.faqs.addButton('add', 'Add', 'M.ciniki_atdo_main.showAdd(\'M.ciniki_atdo_main.showFAQs();\',\'faq\');');
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
			'business':{'label':'General Notes', 'num_cols':1, 'type':'simplegrid', 
				'headerValues':null,
				'cellClasses':[''],
				'noData':'No business notes',
				},
		};
		this.notes.data = {};
		this.notes.noData = function() { return 'No notes found'; }
		// Live Search functions
		this.notes.liveSearchCb = function(s, i, v) {
			M.api.getJSONBgCb('ciniki.atdo.notesSearchQuick', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'15'},
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
				if( d.note.viewed == 'no' ) {
					return '<b>' + d.note.subject + '</b>';
				}
				return d.note.subject;
			}
			return '';
		};
		this.notes.liveSearchResultRowFn = function(s, f, i, j, d) {
			return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_atdo_main.showNotes(null, null);\', \'' + d.note.id + '\');'; 
		};
//		this.notes.liveSearchSubmitFn = function(s, search_str) {
//			M.ciniki_atdo_main.searchNotes('M.ciniki_atdo_main.showNotes();', search_str);
//		};
		this.notes.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				if( d.note.viewed == 'no' ) {
					return '<b>' + d.note.subject + '</b>';
				}
				return d.note.subject;
			}
		};
		this.notes.rowFn = function(s, i, d) {
			return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_atdo_main.showNotes(null, null);\', \'' + d.note.id + '\');'; 
		};
		this.notes.sectionData = function(s) { 
			return this.data[s];
		};
		this.notes.listValue = function(s, i, d) { 
			if( d['count'] != null ) {
				return d['label'] + ' <span class="count">' + d['count'] + '</span>'; 
			}
			return d['label'];
		};

		this.notes.addButton('add', 'Add', 'M.ciniki_atdo_main.showAdd(\'M.ciniki_atdo_main.showNotes();\',\'note\');');
		this.notes.addClose('Back');

		//
		// The default panel will show the messages in a list
		//
		this.messages = new M.panel('Messages',
			'ciniki_atdo_main', 'messages',
			'mc', 'medium', 'sectioned', 'ciniki.atdo.main.messages');
		this.messages.data = null;
		this.messages.sections = {
			'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1, 'hint':'search', 
				'noData':'No messages found',
				'headerValues':null,
				'cellClasses':['multiline'],
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
			M.api.getJSONBgCb('ciniki.atdo.messagesSearchQuick', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'15'},
				function(rsp) {
					M.ciniki_atdo_main.messages.liveSearchShow(s, null, M.gE(M.ciniki_atdo_main.messages.panelUID + '_' + s), rsp.messages);
				});
			return true;
		};
		this.messages.liveSearchResultClass = function(s, f, i, j, d) {
			return this.sections[s].cellClasses[j];
		};
		this.messages.liveSearchResultValue = function(s, f, i, j, d) {
			if( j == 0 ) {
				var pname = '';
				if( d.message.project_name != null && d.message.project_name != '' ) {
					pname = ' <span class="subdue">[' + d.message.project_name + ']</span>';
				}
				if( d.message.viewed == 'no' ) {
					return '<b>' + d.message.subject + '</b>' + pname;
				}
				return d.message.subject + pname;
			}
			return '';
		};
		this.messages.liveSearchResultRowFn = function(s, f, i, j, d) {
			return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_atdo_main.showMessages(null, null);\', \'' + d.message.id + '\');'; 
		};
//		this.messages.liveSearchSubmitFn = function(s, search_str) {
//			M.ciniki_atdo_main.searchNotes('M.ciniki_atdo_main.showMessages();', search_str);
//		};
		this.messages.noData = function(s) { return 'No messages'; }
		this.messages.cellValue = function(s, i, j, d) {
			if( j == 0 ) { 
				var pname = '';
				if( d.message.project_name != null && d.message.project_name != '' ) {
					pname = ' <span class="subdue">[' + d.message.project_name + ']</span>';
				}
				var last = '<span class="subtext">' + d.message.last_followup_user + ' - ' + d.message.last_followup_age + ' ago</span>';
				if( d.message.viewed == 'no' ) {
					return '<span class="maintext"><b>' + d.message.subject + '</b>' + pname + '</span>' + last;
				}
				return '<span class="maintext">' + d.message.subject + pname + '</span>' + last; 
			}
		};
		this.messages.rowFn = function(s, i, d) {
			return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_atdo_main.showMessages(null, null);\', \'' + d.message.id + '\');'; 
		};
		this.messages.sectionData = function(s) { 
			return this.data;
		};
		this.messages.listValue = function(s, i, d) { 
			return d['label'];
		};
		this.messages.addButton('add', 'Add', 'M.ciniki_atdo_main.showAdd(\'M.ciniki_atdo_main.showMessages();\',\'message\');');
		this.messages.addClose('Back');

		//
		// The default panel will show the projects in a list based on assignment
		//
		this.projects = new M.panel('Projects',
			'ciniki_atdo_main', 'projects',
			'mc', 'medium', 'sectioned', 'ciniki.atdo.main.projects');
		this.projects.sections = {};
		this.projects.data = {};
//		this.projects.noData = function() { return 'No projects found'; }
		// Live Search functions
		this.projects.liveSearchCb = function(s, i, v) {
			M.api.getJSONBgCb('ciniki.atdo.projectsSearchQuick', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'15'},
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
				if( d.project.viewed == 'no' ) {
					return '<b>' + d.project.name + '</b>';
				}
				return d.project.name;
			}
			return '';
		};
		this.projects.liveSearchResultRowFn = function(s, f, i, j, d) {
			return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_atdo_main.showProjects(null, null);\', \'' + d.project.id + '\');'; 
		};
//		this.projects.liveSearchSubmitFn = function(s, search_str) {
//			M.ciniki_atdo_main.searchProjects('M.ciniki_atdo_main.showProjects();', search_str);
//		};
		this.projects.cellValue = function(s, i, j, d) {
			if( j == 0 ) {
				if( d.project.viewed == 'no' ) {
					return '<b>' + d.project.name + '</b>';
				}
				return d.project.name;
			}
		};
		this.projects.rowFn = function(s, i, d) {
			return 'M.ciniki_atdo_main.showProject(\'M.ciniki_atdo_main.showProjects(null, null);\', \'' + d.project.id + '\');'; 
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
		this.add = new M.panel('Add Task',
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
				'appointment':{'label':'Appointment', 'field_id':1},
				'task':{'label':'Task', 'field_id':2},
				'faq':{'label':'FAQ', 'field_id':4},
				'note':{'label':'Note', 'field_id':5},
				'message':{'label':'Message', 'field_id':6},
			}};
		this.add.forms.appointment = {
			'info':{'label':'', 'aside':'yes', 'type':'simpleform', 'fields':{
				'appointment_date':{'label':'Start', 'type':'appointment', 'caloffset':0,
					'start':'8:00',
					'end':'20:00',
					'notimelabel':'All day',
					'duration':'appointment_duration',		// Specify the duration field to update when selecting allday
					},
				'appointment_duration':{'label':'Duration', 'type':'timeduration', 'min':15, 'allday':'yes', 'date':'appointment_date', 
					'buttons':this.durationButtons},
				'subject':{'label':'Subject', 'type':'text'},
				'location':{'label':'Location', 'type':'text'},
				'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
			}},
			'_repeat':{'label':'Repeat', 'aside':'yes', 'type':'simpleform', 'fields':{
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
			'info':{'label':'', 'aside':'yes', 'type':'simpleform', 'fields':{
				'subject':{'label':'Task', 'type':'text'},
				'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
				'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curBusiness.employees},
				'private':{'label':'Options', 'type':'multitoggle', 'none':'yes', 'toggles':{'no':'Public', 'yes':'Private'}},
				'priority':{'label':'Priority', 'type':'multitoggle', 'toggles':M.curBusiness.atdo.priorityText},
		//		'status':{'label':'Status', 'type':'multitoggle', 'toggles':this.statuses},
				'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
			}},
			'_notes':{'label':'Details', 'aside':'yes', 'type':'simpleform', 'fields':{
				'followup':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
//			'links':{'label':'Attach', 'type':'simpleform', 'fields':{
//				// FIXME: Eventually this should allow for multiple customers
//				'customer_ids':{'label':'Customer', 'type':'fkid', 'livesearch':'yes'},
//				'product_ids':{'label':'Product', 'type':'fkid', 'livesearch':'yes'},
//			}},
			'_appointment':{'label':'Scheduling', 'collapsable':'yes', 'collapse':'all', 'type':'simpleform', 'fields':{
				'appointment_date':{'label':'Start', 'type':'appointment', 'caloffset':0,
					'start':'8:00',
					'end':'20:00',
					'notimelabel':'All day',
					'duration':'appointment_duration',		// Specify the duration field to update when selecting allday
					},
				'appointment_duration':{'label':'Duration', 'type':'timeduration', 'min':15, 'allday':'yes', 'date':'appointment_date', 'buttons':this.durationButtons},
				'appointment_repeat_type':{'label':'Repeat', 'type':'multitoggle', 'none':'yes', 'toggles':this.repeatOptions, 'fn':'M.ciniki_atdo_main.add.updateInterval'},
				'appointment_repeat_interval':{'label':'Every', 'type':'multitoggle', 'toggles':this.repeatIntervals, 'hint':' '},
				'appointment_repeat_end':{'label':'End Date', 'type':'date', 'hint':'never'},
				'due_date':{'label':'Due', 'type':'appointment', 'caloffset':0,
					'start':'8:00',
					'end':'20:00',
					'notimelabel':'All day',
					'duration':'due_duration',		// Specify the duration field to update when selecting allday
					},
				'due_duration':{'label':'Duration', 'type':'timeduration', 'min':15, 'allday':'yes', 'date':'due_date', 'buttons':this.durationButtons},
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
				'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curBusiness.employees},
				'private':{'label':'Options', 'type':'multitoggle', 'none':'yes', 'toggles':{'no':'Public', 'yes':'Private'}},
//				'priority':{'label':'Priority', 'type':'multitoggle', 'toggles':M.curBusiness.atdo.priorities},
		//		'status':{'label':'Status', 'type':'multitoggle', 'toggles':this.statuses},
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
				'assigned':{'label':'To', 'type':'multiselect', 'none':'yes', 'options':M.curBusiness.employees},
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
				var rsp = M.api.getJSONBgCb('ciniki.atdo.searchCategory', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':35},
					function(rsp) {
						M.ciniki_atdo_main.add.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.add.panelUID + '_' + i), rsp.categories);
					});
			}
			if( i == 'project_id' ) {
				var rsp = M.api.getJSONBgCb('ciniki.projects.searchNames', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':25},
					function(rsp) {
						M.ciniki_atdo_main.add.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.add.panelUID + '_' + i), rsp['projects']);
					});
			}
	// Going to be added in the future...
	//		if( i == 'customer_ids' ) {
	//			var rsp = M.api.getJSONBgCb('ciniki.customers.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':25},
	//				function(rsp) { 
	//					M.ciniki_atdo_main.add.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.add.panelUID + '_' + i), rsp.customers); 
	//				});
	//		} else if( i == 'product_ids' ) {
	//			var rsp = M.api.getJSONBgCb('ciniki.products.searchQuick', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':25},
	//				function(rsp) { 
	//					M.ciniki_atdo_main.add.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.add.panelUID + '_' + i), rsp.products); 
	//				});
	//		}
		};
		this.add.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'category' && d.category != null ) { return d.category.name; }
			if( f == 'project_id' && d.project != null ) { return d.project.name; }
	//		if( f == 'product_ids') {  return d.product.name; }
	//		if( f == 'customer_ids') {  return d.customer.name; }
			return '';
		};
		this.add.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( f == 'category' && d.category != null ) {
				return 'M.ciniki_atdo_main.add.updateCategory(\'' + s + '\',\'' + escape(d.category.name) + '\');';
			}
			if( f == 'project_id' ) {
				return 'M.ciniki_atdo_main.add.updateProject(\'' + s + '\',\'' + escape(d.project.name) + '\',\'' + d.project.id + '\');';
			}
	//		} else if( f == 'product_ids' ) {
	//			return 'M.ciniki_atdo_main.add.updateProduct(\'' + s + '\',\'' + escape(d.product.name) + '\',\'' + d.product.id + '\');';
	//		}
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
//		this.add.updateCustomer = function(s, customer_name, customer_id) {
//			M.gE(this.panelUID + '_customer_ids').value = customer_id;
//			M.gE(this.panelUID + '_customer_ids_fkidstr').value = unescape(customer_name);
//			this.removeLiveSearch(s, 'customer_ids');
//		};
//		this.add.updateProduct = function(s, product_name, product_id) {
//			M.gE(this.panelUID + '_product_ids').value = product_id;
//			M.gE(this.panelUID + '_product_ids_fkidstr').value = unescape(product_name);
//			this.removeLiveSearch(s, 'product_ids');
//		};

		this.add.listValue = function(s, i, d) { return d['label']; };
		this.add.listFn = function(s, i, d) { return d['fn']; };

		this.add.liveAppointmentDayEvents = function(i, day, cb) {
			// Search for events on the specified day
			if( i == 'appointment_date' ) {
				if( day == '--' ) { day = 'today'; }
				M.api.getJSONCb('ciniki.calendars.appointments', {'business_id':M.curBusinessID, 'date':day}, cb);
//				if( rsp.stat == 'ok' ) {
//					return rsp['appointments'];
//				}
			}
//			return {};
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
			'mc', 'medium', 'sectioned', 'ciniki.atdo.main.edit');
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
					'duration':'appointment_duration',		// Specify the duration field to update when selecting allday
					},
				'appointment_duration':{'label':'Duration', 'type':'timeduration', 'min':15, 'allday':'yes', 
					'date':'appointment_date', 'buttons':this.durationButtons},
				'subject':{'label':'Subject', 'type':'text'},
				'location':{'label':'Location', 'type':'text'},
				'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
			}},
			'_repeat':{'label':'Repeat', 'fields':{
				'appointment_repeat_type':{'label':'', 'type':'multitoggle', 'none':'yes', 'toggles':this.repeatOptions},
				'appointment_repeat_interval':{'label':'Every', 'type':'multitoggle', 'toggles':this.repeatIntervals},
				'appointment_repeat_end':{'label':'End Date', 'type':'date', 'hint':'never'},
				}},
			'_notes':{'label':'Notes', 'fields':{
				'content':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
				}},
			'_save':{'label':'', 'buttons':{
				'save':{'label':'Save appointment', 'fn':'M.ciniki_atdo_main.saveAtdo();'},
				}},
			'_delete':{'label':'', 'buttons':{
				'delete':{'label':'Delete appointment', 'fn':'M.ciniki_atdo_main.deleteAppointment();'},
				}},
			};
		this.atdo.forms.task = {
			'thread':{'label':'', 'type':'simplethread'},
			'_followup':{'label':'Add your response', 'fields':{
				'followup':{'label':'Details', 'hidelabel':'yes', 'type':'textarea', 'history':'no'},
				}},
			'_addfollowup':{'label':'', 'type':'simplebuttons', 'buttons':{
				'add':{'label':'Save', 'fn':'M.ciniki_atdo_main.saveAtdo();'},
				}},
			'info':{'label':'', 'type':'simpleform', 'fields':{
				'subject':{'label':'Title', 'type':'text'},
				'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
				'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curBusiness.employees, 'history':'no', 'viewed':'viewed', 'deleted':'deleted'},
				'private':{'label':'Options', 'type':'multitoggle', 'none':'yes', 'toggles':{'no':'Public', 'yes':'Private'}, 'history':'no'},
				'priority':{'label':'Priority', 'type':'multitoggle', 'toggles':M.curBusiness.atdo.priorityText, 'history':'no'},
				'status':{'label':'Status', 'type':'multitoggle', 'toggles':this.statuses, 'history':'no'},
				'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
			}},
//			'links':{'label':'Attach', 'type':'simpleform', 'fields':{
//				// FIXME: Eventually this should allow for multiple customers
//				// FIXME: The sync will not work for attachments
//				'customer_ids':{'label':'Customer', 'type':'fkid', 'livesearch':'yes'},
//				'product_ids':{'label':'Product', 'type':'fkid', 'livesearch':'yes'},
//			}},
			'_appointment':{'label':'Scheduling', 'collapsable':'yes', 'collapse':'compact', 'type':'simpleform', 'fields':{
				'appointment_date':{'label':'Start', 'type':'appointment', 'caloffset':0, 'history':'no',
					'start':'8:00',
					'end':'20:00',
					'notimelabel':'All day',
					'duration':'appointment_duration',		// Specify the duration field to update when selecting allday
					},
				'appointment_duration':{'label':'Duration', 'type':'timeduration', 'history':'no', 'min':15, 'allday':'yes', 
					'date':'appointment_date', 'buttons':this.durationButtons},
				'appointment_repeat_type':{'label':'Repeat', 'type':'multitoggle', 'none':'yes', 'history':'no', 'toggles':this.repeatOptions, 'fn':'M.ciniki_atdo_main.atdo.updateInterval'},
				'appointment_repeat_interval':{'label':'Every', 'type':'multitoggle', 'toggles':this.repeatIntervals, 'history':'no', 'hint':' '},
				'appointment_repeat_end':{'label':'End Date', 'type':'date', 'hint':'never', 'history':'no'},
				'due_date':{'label':'Due', 'type':'appointment', 'caloffset':0, 'history':'no',
					'start':'8:00',
					'end':'20:00',
					'notimelabel':'All day',
					'duration':'due_duration',		// Specify the duration field to update when selecting allday
					},
				'due_duration':{'label':'Duration', 'type':'timeduration', 'min':15, 'history':'no', 'allday':'yes', 'date':'due_date', 'buttons':this.durationButtons},
				}},
//			'links':{'label':'Additional Information', 'type':'simplelist', 'list':{
//				'customer_ids':{'label':'Customer', 'value':''},
//				'product_ids':{'label':'Product', 'value':''},
//				'customer_ids':{'label':'Customer', 'type':'fkid', 'livesearch':'yes'},
//				'product_ids':{'label':'Product', 'type':'fkid', 'livesearch':'yes'},
//				}},
			'_update':{'label':'', 'type':'simplebuttons', 'buttons':{
				'update':{'label':'Save', 'fn':'M.ciniki_atdo_main.saveAtdo();'},
				}},
			};
		this.atdo.forms.faq = {
			'thread':{'label':'', 'type':'simplethread'},
			'_followup':{'label':'Add your response', 'fields':{
				'followup':{'label':'Details', 'hidelabel':'yes', 'type':'textarea', 'history':'no'},
				}},
			'_addfollowup':{'label':'', 'type':'simplebuttons', 'buttons':{
				'add':{'label':'Save', 'fn':'M.ciniki_atdo_main.saveAtdo();'},
				}},
			'info':{'label':'', 'type':'simpleform', 'fields':{
				'subject':{'label':'Question', 'type':'text'},
				'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
				'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
			}},
//			'_update':{'label':'', 'type':'simplebuttons', 'buttons':{
//				'update':{'label':'Save', 'fn':'M.ciniki_atdo_main.saveAtdo();'},
//				}},
			};
		this.atdo.forms.note = {
			'thread':{'label':'', 'type':'simplethread'},
			'_followup':{'label':'Add your response', 'fields':{
				'followup':{'label':'Details', 'hidelabel':'yes', 'type':'textarea', 'history':'no'},
				}},
			'info':{'label':'', 'type':'simpleform', 'fields':{
				'subject':{'label':'Title', 'type':'text'},
				'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
				'assigned':{'label':'Assigned', 'type':'multiselect', 'none':'yes', 'options':M.curBusiness.employees, 'history':'no', 'viewed':'viewed', 'deleted':'deleted'},
				'private':{'label':'Options', 'type':'multitoggle', 'none':'yes', 'toggles':{'no':'Public', 'yes':'Private'}, 'history':'no'},
				'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
			}},
			'_addfollowup':{'label':'', 'type':'simplebuttons', 'buttons':{
				'add':{'label':'Save', 'fn':'M.ciniki_atdo_main.saveAtdo();'},
				}},
//			'_update':{'label':'', 'type':'simplebuttons', 'buttons':{
//				'update':{'label':'Save note', 'fn':'M.ciniki_atdo_main.saveAtdo();'},
//				}},
			};
		this.atdo.forms.message = {
			'info':{'label':'', 'type':'simpleform', 'fields':{
				'assigned':{'label':'To', 'type':'multiselect', 'none':'yes', 'options':M.curBusiness.employees, 'history':'no', 'viewed':'viewed', 'deleted':'deleted'},
				'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
			}},
			'thread':{'label':'', 'type':'simplethread'},
			'_followup':{'label':'Add your response', 'fields':{
				'followup':{'label':'Details', 'hidelabel':'yes', 'type':'textarea', 'history':'no'},
				}},
			'_addfollowup':{'label':'', 'type':'simplebuttons', 'buttons':{
				'add':{'label':'Send', 'fn':'M.ciniki_atdo_main.saveAtdo();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_atdo_main.saveAtdo(\'user\');'},
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
//			if( i == 'appointment_duration_allday' ) { return this.data['allday']; }
			if( i == 'appointment_date_date' ) { return this.data['appointment_date_date']; }
//			if( i == 'due_duration_allday' ) { return this.data['due_allday']; }
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
				var rsp = M.api.getJSONBgCb('ciniki.atdo.searchCategory', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':35},
					function(rsp) {
						M.ciniki_atdo_main.atdo.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.atdo.panelUID + '_' + i), rsp.categories);
					});
			}
			if( i == 'project_id' ) {
				var rsp = M.api.getJSONBgCb('ciniki.projects.searchNames', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':25},
					function(rsp) {
						M.ciniki_atdo_main.atdo.liveSearchShow(s, i, M.gE(M.ciniki_atdo_main.atdo.panelUID + '_' + i), rsp['projects']);
					});
			}
		};
		this.atdo.liveSearchResultValue = function(s, f, i, j, d) {
			if( f == 'category' && d.category != null ) { return d.category.name; }
			if( f == 'project_id' && d.project != null ) { return d.project.name; }
			return '';
		};
		this.atdo.liveSearchResultRowFn = function(s, f, i, j, d) { 
			if( f == 'category' && d.category != null ) {
				return 'M.ciniki_atdo_main.atdo.updateCategory(\'' + s + '\',\'' + escape(d.category.name) + '\');';
			}
			if( f == 'project_id' && d.project != null ) {
				return 'M.ciniki_atdo_main.atdo.updateParent(\'' + s + '\',\'' + escape(d.project.name) + '\',\'' + d.project.id + '\');';
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
		this.atdo.threadFollowupUser = function(s, i, d) { return d['followup']['user_display_name']; }
		this.atdo.threadFollowupAge = function(s, i, d) { return d['followup']['age']; }
		this.atdo.threadFollowupDateTime = function(s, i, d) { return d.followup.date_added; }
		this.atdo.threadFollowupContent = function(s, i, d) { return d['followup']['content']; }
		this.atdo.liveAppointmentDayEvents = this.add.liveAppointmentDayEvents;
		this.atdo.appointmentEventText = this.add.appointmentEventText;
		this.atdo.appointmentColour = this.add.appointmentColour;
		this.atdo.updateInterval = this.add.updateInterval;
		this.atdo.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.atdo.getHistory', 'args':{'business_id':M.curBusinessID, 
				'atdo_id':M.ciniki_atdo_main.atdo.atdo_id, 'field':i}};
		}
//		this.atdo.addButton('save', 'Save', 'M.ciniki_atdo_main.saveAppointment();');
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
				'cellClasses':['multiline aligncenter', 'multiline', 'multiline', 'multiline'],
				},
		};
		this.search.data = {};
		this.search.noData = function() { return 'No tasks found'; }
		this.search.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return '<span class="icon">' + M.ciniki_atdo_main.symbolpriorities[d.task.priority] + '</span>';
				case 1: return '<span class="maintext">' + d.task.subject + '</span><span class="subtext">' + d.task.assigned_users + '&nbsp;</span>';
				case 2: return d.task.status;
				case 3: return '<span class="maintext">' + d.task.due_date + '</span><span class="subtext">' + d.task.due_time + '</span>';
			}
			return '';
		};
		this.search.rowStyle = function(s, i, d) {
			if( d.task.status != 'Completed' ) { return 'background: ' + M.curBusiness.atdo.settings['tasks.priority.' + d.task.priority]; }
			else { return 'background: ' + M.curBusiness.atdo.settings['tasks.status.60']; }
		};
		this.search.rowFn = function(s, i, d) {
			if( this.last_search_completed == 'yes' ) {
				return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_atdo_main.showCompleted(null);\', \'' + d.task.id + '\');'; 
			}
			return 'M.ciniki_atdo_main.showAtdo(\'M.ciniki_atdo_main.searchTasks(null, null);\', \'' + d.task.id + '\');'; 
		};
		this.search.sectionData = function(s) { 
			if( s == 'results' ) { return this.data; }
			return null;
		};
		this.search.addClose('Back');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		//
		// Reset all employee lists, must be done when switching businesses
		//
		this.add.forms.task.info.fields.assigned.options = M.curBusiness.employees;
		this.add.forms.note.info.fields.assigned.options = M.curBusiness.employees;
		this.add.forms.message.info.fields.assigned.options = M.curBusiness.employees;
		this.atdo.forms.task.info.fields.assigned.options = M.curBusiness.employees;
		this.atdo.forms.note.info.fields.assigned.options = M.curBusiness.employees;
		this.atdo.forms.message.info.fields.assigned.options = M.curBusiness.employees;

		if( M.curBusiness.modules['ciniki.projects'] != null ) {
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

		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_atdo_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.cb = cb;
		// this.files.show(cb);
		if( args.atdo_id != null && args.atdo_id != '' ) {
			this.showAtdo(cb, args.atdo_id);
//		} else if( args['date'] != null ) {
//			this.showTasks(cb, args['date']);
		} else if( args.tasksearch != null && args.tasksearch != '' ) {
			this.searchTasks(cb, args.tasksearch);
		} else if( args.add != null && (args.add == 'task' || args.add == 'appointment' || args.add == 'faq' || args.add == 'note' || args.add == 'message' ) ) {
			this.showAdd(cb, args.add, args.date, args.time, args.allday);
		} else if( args.addtoproject != null && (args.addtoproject == 'task' || args.addtoproject == 'appointment' 
				|| args.addtoproject == 'note' || args.addtoproject == 'message') ) {
			this.showAddToProject(cb, args.addtoproject, args.project_id, args.project_name);
		} else if( args.tasks != null && args.tasks == 'yes' ) {
			this.showTasks(cb, null);
		} else if( args.messages != null && args.messages == 'yes' ) {
			this.showMessages(cb, null);
		} else if( args.faq != null && args.faq == 'yes' ) {
			this.showFAQs(cb, null);
		} else if( args.notes != null && args.notes == 'yes' ) {
			this.showNotes(cb, null);
		} else {
			this.showTasks(cb, null);
		}
	}

	this.showTasks = function(cb, scheduleDate) {
		// Get the open tasks for the user and business
		this.tasks.data = {};
		M.startLoad();
		var rsp = M.api.getJSONCb('ciniki.atdo.tasksList', 
			{'business_id':M.curBusinessID, 'status':'open'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.stopLoad();
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_atdo_main.tasks;
				p.sections = {
					'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':3, 'hint':'search', 
						'noData':'No tasks found',
						'headerValues':['', 'Task', 'Due'],
						'cellClasses':['multiline aligncenter', 'multiline', 'multiline'],
						},
				};
				
				for(i in rsp.categories) {
					p.data[rsp.categories[i].category.name] = rsp.categories[i].category.tasks;
					p.sections[rsp.categories[i].category.name] = {'label':rsp.categories[i].category.name,
						'num_cols':3, 'type':'simplegrid', 
						'headerValues':['', 'Task', 'Due'],
						'cellClasses':['multiline aligncenter', 'multiline', 'multiline'],
						'noData':'No tasks',
						};
				}
				p.sections['other'] = {'label':'Other Tasks', 'type':'simplelist', 'list':{
					'closed':{'label':'Recently Completed', 'fn':'M.ciniki_atdo_main.showCompleted(\'M.ciniki_atdo_main.showTasks();\')'},
					'ctb':{'label':'Call to Book', 'visible':'no', 'count':0, 'fn':'M.startApp(\'ciniki.wineproduction.main\',null,\'M.ciniki_atdo_main.showTasks();\',\'mc\',{\'ctb\':\'yes\'});'},
					}};

				//
				// Grab the stats for Call to Book, if module is turned on
				//
				if( M.curBusiness['modules']['ciniki.wineproduction'] != null ) {
					p.sections.other.list.ctb.visible = 'yes';
					var rsp = M.api.getJSON('ciniki.wineproduction.statsCTB', {'business_id':M.curBusinessID});
					if( rsp.stat != 'ok' ) {
						M.stopLoad();
						M.api.err(rsp);
						return false;
					}
					p.sections.other.list.ctb.count = rsp.ctb;
				} else {
					p.sections.other.list.ctb.visible = 'no';
				}

				// Show the panel
				M.stopLoad();
				p.refresh();
				p.show(cb);
			});
	};

	this.showFAQs = function(cb) {
		// Get the faqs for the user and business
		this.faqs.data = {};
		var rsp = M.api.getJSONCb('ciniki.atdo.faqsList', 
			{'business_id':M.curBusinessID, 'status':'open'}, function(rsp) {
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
					p.data[rsp.categories[i].category.name] = rsp.categories[i].category.faqs;
					p.sections[rsp.categories[i].category.name] = {'label':rsp.categories[i].category.name,
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

	this.showNotes = function(cb) {
		// Get the notes for the user and business
		this.notes.data = {'assigned':[], 'business':[]};
		var rsp = M.api.getJSONCb('ciniki.atdo.notesList', 
			{'business_id':M.curBusinessID, 'status':'open'}, function(rsp) {
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
					p.data[rsp.categories[i].category.name] = rsp.categories[i].category.notes;
					p.sections[rsp.categories[i].category.name] = {'label':rsp.categories[i].category.name,
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

	this.showMessages = function(cb) {
		// Get the notes for the user and business
		this.messages.data = {};
		var rsp = M.api.getJSONCb('ciniki.atdo.messagesList', 
			{'business_id':M.curBusinessID, 'status':'open'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_atdo_main.messages;
				p.data = rsp.messages;
				p.refresh();
				p.show(cb);
			});
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
		this.add.refresh();
		this.add.show(cb);
	};

	this.setupAdd = function(type, d, t, ad) {
		this.add.reset();
		this.add.data = this.add.default_data;
		this.add.data.project_id = 0;
		this.add.data.project_name = '';
		if( d != null ) {
			if( ad == 1 ) {
				this.add.data.appointment_date = M.dateFormat(d);
				this.add.data.appointment_allday = 'yes';
			} else {
				this.add.data.appointment_date = M.dateFormat(d) + ' ' + t;
				this.add.data.appointment_allday = 'no';
			}
		}
		this.add.formtab = type;
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
		var rsp = M.api.postJSONCb('ciniki.atdo.add', 
			{'business_id':M.curBusinessID}, c, function(rsp) {
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
			alert('No subject specified');
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
		if( type == 2 ) {
			var dad = 'no';
			if( M.gE(p.panelUID + '_due_duration_buttons_allday').childNodes[0].className == 'toggle_on' ) {
				dad = 'yes';
			}
			if( odad == null || dad != odad ) {
				c += '&due_allday=' + dad;
			}
		}
		return c;	
	};

	this.showAtdo = function(cb, aid) {
		this.atdo.reset();
		this.atdo.formtab = null;
		this.atdo.formtab_field_id = null;
		this.atdo.subject = '';
		if( aid != null ) {
			this.atdo.atdo_id = aid;
		}

		var rsp = M.api.getJSONCb('ciniki.atdo.get', 
			{'business_id':M.curBusinessID, 'atdo_id':this.atdo.atdo_id}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_atdo_main.atdo;
				p.data = rsp.atdo;
				p.org_data = rsp.atdo;	// Store original data, to use in comparison when saving to know what changed
				// Need to set the followup to blank, incase they add one it will get sent to the update
				p.data.followup = '';
				p.subject = rsp.atdo.subject;
				p.refresh();
				p.show(cb);
//				if( rsp.atdo.type == 1 || rsp.atdo.type == 2 ) {
					p.updateInterval('appointment_repeat_type', M.ciniki_atdo_main.repeatOptions[rsp.atdo.appointment_repeat_type], 'toggle_on');
//				}
			});
	};

	this.saveAtdo = function(del) {
		// Reset data to the original loaded data, so we know what changed and only send changes to server
		this.atdo.data = this.atdo.org_data;
		var c = this.getContent(this.atdo, 'no', this.atdo.data['appointment_duration_allday'], this.atdo.data['due_duration_allday']);

		// Check if the message (or other) should be removed from the users view
		if( del == 'user' ) {
			c += '&userdelete=yes';
		}

		if( c != '' ) {
			var rsp = M.api.postJSONCb('ciniki.atdo.update', 
				{'business_id':M.curBusinessID, 'atdo_id':this.atdo.atdo_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_atdo_main.atdo.close();
				});
		} else {
			M.ciniki_atdo_main.atdo.close();
		}
	};

	this.deleteAppointment = function() {
		if( confirm("Are you sure you want to delete this appointment?") ) {        
			var rsp = M.api.postJSONCb('ciniki.atdo.update', 
				{'business_id':M.curBusinessID, 'atdo_id':this.atdo.atdo_id, 'status':'60'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_atdo_main.atdo.close();
				});
		}
	}
	
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
		var rsp = M.api.getJSONBg('ciniki.atdo.tasksSearchFull', {'business_id':M.curBusinessID, 'start_needle':search_str, 'limit':100, 'full':'yes'});
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
		var rsp = M.api.getJSONBg('ciniki.atdo.tasksSearchFull', {'business_id':M.curBusinessID, 'start_needle':'', 
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
