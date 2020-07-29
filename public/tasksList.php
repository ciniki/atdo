<?php
//
// Description
// ===========
// This method returns the lists of tasks for a user from a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get the task list for.
// status:          (optional) Only lists tasks that are open or closed.
// category:        (optional) Only return tasks that are assigned to the specified category.
// assigned:        (optional) Only return tasks assigned to the session user.
// limit:           (optional) The maximum number of records to return.
// 
// Returns
// -------
// <tasks>
//      <task id="1" subject="Task subject" project_name="" assigned="yes" private="yes" due_date=""/>
// </tasks>
//
function ciniki_atdo_tasksList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'priority'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Priority'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        'user_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'User'), 
        'assigned'=>array('required'=>'no', 'blank'=>'no', 'validlist'=>array('no', 'yes'), 'name'=>'Category'), 
        'stats'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Stats'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'private', 'checkAccess');
    $rc = ciniki_atdo_checkAccess($ciniki, $args['tnid'], 'ciniki.atdo.tasksList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Load tenant owners
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'tenantOwners');
    $rc = ciniki_tenants_hooks_tenantOwners($ciniki, $args['tnid'], array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.24', 'msg'=>'', 'err'=>$rc['err']));
    }
    $owners = $rc['users'];

    //
    // Get timezone info // ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    //
    // Prepare some of the SQL snippets
    //
    $priority_sql = '';
    if( isset($args['priority']) && $args['priority'] != '' ) {
        $priority_sql = "AND ciniki_atdos.priority = '" . ciniki_core_dbQuote($ciniki, $args['priority']) . "' ";
    }
    $category_sql = '';
    if( isset($args['category']) && $args['category'] == 'Uncategorized' ) {
        $category_sql = "AND ciniki_atdos.category = '' ";
    } elseif( isset($args['category']) && $args['category'] != '' && $args['category'] != 'All' ) {
        $category_sql = "AND ciniki_atdos.category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "' ";
    }
    $status_sql = '';
    if( isset($args['status']) && $args['status'] != '' ) {
        switch($args['status']) {
            case 'Open':
            case 'open': $status_sql = "AND ciniki_atdos.status = 1 ";
                break;
            case 'Closed':
            case 'closed': $status_sql = "AND ciniki_atdos.status = 60 ";
                break;
        }
    }
    $project_name_sql = "'' AS project_name, ";
    $projects_sql = '';
    if( isset($modules['ciniki.projects']) ) {
        $project_name_sql = "IFNULL(ciniki_projects.name, '') AS project_name, ";
        $projects_sql = "LEFT JOIN ciniki_projects ON ("
            . "ciniki_atdos.project_id = ciniki_projects.id "
            . "AND ciniki_projects.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }
    $assigned_sql = '';
    $assigned_join_sql = '';
    if( isset($args['assigned']) && $args['assigned'] == 'yes' ) {
        $assigned_sql = "AND (u1.perms&0x04) = 4 ";
        $assigned_join_sql = "LEFT JOIN ciniki_atdo_users AS u1 ON ("
            . "ciniki_atdos.id = u1.atdo_id "
            . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }
    $user_sql = '';
    if( isset($owners[$ciniki['session']['user']['id']]) && isset($args['user_id']) && $args['user_id'] > 0 ) {
        $user_sql = "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
        $assigned_join_sql = "LEFT JOIN ciniki_atdo_users AS u1 ON ("
            . "ciniki_atdos.id = u1.atdo_id "
            . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
            . "AND (u1.perms&0x04) = 0x04 "
            . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }
    $limit_sql = '';
    if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
        $limit_sql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    }

    //
    // Get the list of tasks
    //
    if( isset($owners[$ciniki['session']['user']['id']]) ) {
        //
        // Owners can see all tasks, not just the ones assigned to them
        //
        $strsql = "SELECT ciniki_atdos.id, "
            . "IF(ciniki_atdos.category='', 'Uncategorized', ciniki_atdos.category) AS category, "
            . "ciniki_atdos.subject, "
            . $project_name_sql
            . "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
            . "IF((ciniki_atdos.perm_flags&0x01)=1, 'yes', 'no') AS private, "
            . "IF(ciniki_atdos.status=1, 'open', 'closed') AS status, "
            . "ciniki_atdos.priority, "
            . "IFNULL(ciniki_atdos.due_date, '') AS due_date, "
            . "IF((ciniki_atdos.due_flags&0x01)=1, '', IF(ciniki_atdos.due_date=0, '', ciniki_atdos.due_date)) AS due_time, "
            . "IFNULL(u3.display_name, '') AS assigned_users, "
            . "followups.id AS fol_las_up, "
            . "IFNULL(followups.last_updated, ciniki_atdos.last_updated) AS last_updated_date, "
            . "IFNULL(followups.last_updated, ciniki_atdos.last_updated) AS last_updated_time "
            . "FROM ciniki_atdos "
            . "LEFT JOIN ciniki_atdo_followups AS followups ON ("
                . "followups.id = ("
                    . "SELECT id "
                    . "FROM ciniki_atdo_followups "
                    . "WHERE atdo_id = ciniki_atdos.id "
                    . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . "ORDER BY date_added DESC "
                    . "LIMIT 1 "
                    . ") "
                . ") "
            . $assigned_join_sql
            . "LEFT JOIN ciniki_atdo_users AS u2 ON ("
                . "ciniki_atdos.id = u2.atdo_id "
                . "AND (u2.perms&0x04) = 4 "
                . "AND u2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_users AS u3 ON ( "
                . "u2.user_id = u3.id "
                . ") "
            . $projects_sql
            . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_atdos.type = 2 "
            . $priority_sql
            . $status_sql
            . $category_sql
            . $assigned_sql
            . $user_sql
            . "ORDER BY ciniki_atdos.priority DESC, ciniki_atdos.due_date DESC, ciniki_atdos.id, u3.display_name "
            . $limit_sql
            . "";
    } else {
        //
        // Employees only see the tasks assigned to them
        //
        $strsql = "SELECT ciniki_atdos.id, "
            . "IF(ciniki_atdos.category='', 'Uncategorized', ciniki_atdos.category) AS category, "
            . "ciniki_atdos.subject, "
            . $project_name_sql
            . "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
            . "IF((ciniki_atdos.perm_flags&0x01)=1, 'yes', 'no') AS private, "
            . "IF(ciniki_atdos.status=1, 'open', 'closed') AS status, "
            . "ciniki_atdos.priority, "
            . "IFNULL(ciniki_atdos.due_date, '') AS due_date, "
            . "IF((ciniki_atdos.due_flags&0x01)=1, '', IF(ciniki_atdos.due_date=0, '', ciniki_atdos.due_date)) AS due_time, "
            . "IFNULL(u3.display_name, '') AS assigned_users, "
            . "ciniki_atdos.last_updated AS last_updated_date, "
            . "ciniki_atdos.last_updated AS last_updated_time "
            . "FROM ciniki_atdos "
            . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                . "ciniki_atdos.id = u1.atdo_id "
                . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_atdo_users AS u2 ON ("
                . "ciniki_atdos.id = u2.atdo_id "
                . "AND (u2.perms&0x04) = 4 "
                . "AND u2.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") "
            . "LEFT JOIN ciniki_users AS u3 ON ( "
                . "u2.user_id = u3.id "
                . ") "
            . $projects_sql
            . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND ciniki_atdos.type = 2 "
            . $priority_sql
            . $status_sql
            . $category_sql
            . $assigned_sql
            . "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to tenant
                // created by the user requesting the list
                . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
                // Assigned to the user requesting the list
                . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
                . ") "
            . "ORDER BY ciniki_atdos.priority DESC, ciniki_atdos.due_date DESC, ciniki_atdos.id, u3.display_name "
            . $limit_sql
            . "";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.atdo', array(
        array('container'=>'tasks', 'fname'=>'id', 'name'=>'task',
            'fields'=>array('id', 'category', 'subject', 'project_name', 'allday', 'status', 'priority', 'private', 
                'assigned_users', 'due_date', 'due_time', 'last_updated_date', 'last_updated_time', 'fol_las_up'), 
            'utctotz'=>array(
                'due_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'due_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:i A'),
                'last_updated_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'last_updated_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:i A'),
                ),
            'lists'=>array('assigned_users'),    
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
        error_log(print_r($rc,true));
    $rsp = array('stat'=>'ok', 'tasks' => isset($rc['tasks']) ? $rc['tasks'] : array());

    //
    // Get the stats for the UI
    //
    if( isset($args['stats']) ) {
        //
        // Get the stats for the different statuses
        //
        $rsp['statuslist'] = array(
            array('label' => 'Open', 'id' => 'open', 'num_tasks' => 0),
            array('label' => 'Completed', 'id' => 'closed', 'num_tasks' => 0),
            array('label' => 'All', 'id' => '', 'num_tasks' => 0),
            );
        if( isset($owners[$ciniki['session']['user']['id']]) ) {
            //
            // Get complete list for owners
            //
            $strsql = "SELECT status, COUNT(*) AS num_tasks "
                . "FROM ciniki_atdos "
                . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_atdos.type = 2 "
                . "GROUP BY status "
                . "";
        } else {
            //
            // Filter for assigned tasks for the user
            //
            $strsql = "SELECT status, COUNT(*) AS num_tasks "
                . "FROM ciniki_atdos "
                . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                    . "ciniki_atdos.id = u1.atdo_id "
                    . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                    . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_atdos.type = 2 "
                . "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to tenant
                    // created by the user requesting the list
                    . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
                    // Assigned to the user requesting the list
                    . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
                . ") "
                . "GROUP BY status "
                . "";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.atdo', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.25', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
        }
        if( isset($rc['num']['1']) ) {
            $rsp['statuslist'][0]['num_tasks'] = $rc['num']['1'];
            $rsp['statuslist'][2]['num_tasks'] += $rc['num']['1'];
        }
        if( isset($rc['num']['60']) ) {
            $rsp['statuslist'][1]['num_tasks'] = $rc['num']['60'];
            $rsp['statuslist'][2]['num_tasks'] += $rc['num']['60'];
        }

        //
        // Get the stats for priorities
        //
        $rsp['prioritylist'] = array(
            array('label' => 'All', 'id' => '', 'num_tasks' => 0),
            array('label' => 'High', 'id' => '50', 'num_tasks' => 0),
            array('label' => 'Medium', 'id' => '30', 'num_tasks' => 0),
            array('label' => 'Low', 'id' => '10', 'num_tasks' => 0),
            );
        if( isset($owners[$ciniki['session']['user']['id']]) ) {
            //
            // Get complete list for owners
            //
            $strsql = "SELECT priority, COUNT(*) AS num_tasks "
                . "FROM ciniki_atdos "
                . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_atdos.type = 2 "
                . $status_sql
                . "GROUP BY priority "
                . "";
        } else {
            //
            // Filter for assigned tasks for the user
            //
            $strsql = "SELECT priority, COUNT(*) AS num_tasks "
                . "FROM ciniki_atdos "
                . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                    . "ciniki_atdos.id = u1.atdo_id "
                    . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                    . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_atdos.type = 2 "
                . $status_sql
                . "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to tenant
                    // created by the user requesting the list
                    . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
                    // Assigned to the user requesting the list
                    . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
                . ") "
                . "GROUP BY priority "
                . "";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
        $rc = ciniki_core_dbCount($ciniki, $strsql, 'ciniki.atdo', 'num');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.25', 'msg'=>'Unable to load get the number of items', 'err'=>$rc['err']));
        }
        if( isset($rc['num']['10']) ) {
            $rsp['prioritylist'][0]['num_tasks'] += $rc['num']['10'];
            $rsp['prioritylist'][3]['num_tasks'] = $rc['num']['10'];
        }
        if( isset($rc['num']['30']) ) {
            $rsp['prioritylist'][0]['num_tasks'] += $rc['num']['30'];
            $rsp['prioritylist'][2]['num_tasks'] = $rc['num']['30'];
        }
        if( isset($rc['num']['50']) ) {
            $rsp['prioritylist'][0]['num_tasks'] += $rc['num']['50'];
            $rsp['prioritylist'][1]['num_tasks'] = $rc['num']['50'];
        }

        //
        // Get the list of categories
        //
        if( isset($owners[$ciniki['session']['user']['id']]) ) {
            //
            // Get the complete list of categories for the owner
            //
            $strsql = "SELECT IF(category='', 'Uncategorized', category) AS category, COUNT(*) AS num_tasks "
                . "FROM ciniki_atdos "
                . $assigned_join_sql
                . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_atdos.type = 2 "
                . $status_sql
                . $priority_sql
                . $user_sql
                . "GROUP BY category "
                . "ORDER BY category "
                . "";
        } else {
            //
            // Filter for assigned tasks for the user
            //
            $strsql = "SELECT IF(category='', 'Uncategorized', category) AS category, COUNT(*) AS num_tasks "
                . "FROM ciniki_atdos "
                . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                    . "ciniki_atdos.id = u1.atdo_id "
                    . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                    . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_atdos.type = 2 "
                . $status_sql
                . $priority_sql
                . "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to tenant
                    // created by the user requesting the list
                    . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
                    // Assigned to the user requesting the list
                    . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
                . ") "
                . "GROUP BY category "
                . "ORDER BY category "
                . "";
        }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.atdo', array(
            array('container'=>'categories', 'fname'=>'category', 'fields'=>array('category', 'num_tasks')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.29', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
        }
        $rsp['categorylist'] = isset($rc['categories']) ? $rc['categories'] : array();
        $num_tasks = 0;
        foreach($rsp['categorylist'] as $cat) {
            $num_tasks += $cat['num_tasks'];
        }
        array_unshift($rsp['categorylist'], array('category'=>'All', 'num_tasks'=>$num_tasks));

        //
        // If owner, then provide all employee list and stats
        //
        if( isset($owners[$ciniki['session']['user']['id']]) ) {
            //
            // Get the list of employees
            //
            $strsql = "SELECT u2.id, u2.display_name, COUNT(*) AS num_tasks "
                . "FROM ciniki_atdos "
                . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                    . "ciniki_atdos.id = u1.atdo_id "
                    . "AND (u1.perms&0x04) = 0x04 "
                    . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_users AS u2 ON (" 
                    . "u1.user_id = u2.id "
                    . ") "
                . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_atdos.type = 2 "
                . $status_sql
                . $priority_sql
                . "GROUP BY u2.id "
                . "ORDER BY u2.display_name "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.atdo', array(
                array('container'=>'employees', 'fname'=>'id', 'fields'=>array('id', 'display_name', 'num_tasks')),
                ));
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.29', 'msg'=>'Unable to load ', 'err'=>$rc['err']));
            }
            $rsp['employeelist'] = isset($rc['employees']) ? $rc['employees'] : '';
            array_unshift($rsp['employeelist'], array('id'=>0, 'display_name'=>'All'));
        }
    }

    return $rsp;
}
?>
