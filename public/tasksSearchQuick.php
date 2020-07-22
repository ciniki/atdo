<?php
//
// Description
// -----------
// Search tasks by subject and date
//
// Arguments
// ---------
// user_id:         The user making the request
// search_str:      The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_atdo_tasksSearchQuick($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Limit'), 
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
    $rc = ciniki_atdo_checkAccess($ciniki, $args['tnid'], 'ciniki.atdo.tasksSearchQuick', 0); 
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
    // Get timezone info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $args['tnid']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');

    $project_name_sql = "'' AS project_name, ";
    $projects_sql = '';
    if( isset($modules['ciniki.projects']) ) {
        $project_name_sql = "IFNULL(ciniki_projects.name, '') AS project_name, ";
        $projects_sql = "LEFT JOIN ciniki_projects ON ("
            . "ciniki_atdos.project_id = ciniki_projects.id "
            . "AND ciniki_projects.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }

    //
    // Get the number of tasks in each status for the tenant, 
    // if no rows found, then return empty array
    //
    $strsql = "SELECT ciniki_atdos.id, "
        . "ciniki_atdos.subject, "
        . "ciniki_atdos.category, "
        . $project_name_sql
        . "ciniki_atdos.priority, "
        . "IF((u1.perms&0x04)=4, 'yes', 'no') AS assigned, "
        . "IFNULL(ciniki_atdos.due_date, '') AS due_date, "
        . "IF((ciniki_atdos.due_flags&0x01)=1, '', IF(ciniki_atdos.due_date=0, '', ciniki_atdos.due_date)) AS due_time, "
        . "IFNULL(u3.display_name, '') AS assigned_users, "
        . "ciniki_atdos.last_updated AS last_updated_date, "
        . "ciniki_atdos.last_updated AS last_updated_time "
        . "FROM ciniki_atdos "
        . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
            . "ciniki_atdos.id = u1.atdo_id "
            . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . ") "
        . "LEFT JOIN ciniki_atdo_users AS u2 ON ("
            . "ciniki_atdos.id = u2.atdo_id "
            . "AND (u2.perms&0x04) = 4 "
            . ") "
        . "LEFT JOIN ciniki_users AS u3 ON ("
            . "u2.user_id = u3.id "
            . ") "
        . "LEFT JOIN ciniki_atdo_followups ON ("
            . "ciniki_atdos.id = ciniki_atdo_followups.atdo_id "
            . ") "
        . $projects_sql
        . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_atdos.type = 2 "      // Tasks
//        . "AND ciniki_atdos.status = 1 "
        . "AND ("
            . "ciniki_atdos.subject LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_atdos.subject LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_atdos.category LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_atdos.category LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_atdo_followups.content LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR ciniki_atdo_followups.content LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR u3.firstname LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . "OR u3.firstname LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
            . ") "
        . "";
    if( !isset($owners[$ciniki['session']['user']['id']]) ) {
        $strsql .= "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to tenant
            // created by the user requesting the list
            . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
            // Assigned to the user requesting the list
            . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
            . ") ";
    }
    $strsql .= "ORDER BY assigned DESC, ciniki_atdos.priority DESC, due_date, ciniki_atdos.id, u3.display_name "
        . "";
    if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
        $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    } else {
        $strsql .= "LIMIT 25 ";
    }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.atdo', array(
        array('container'=>'tasks', 'fname'=>'id', 'name'=>'task',
            'fields'=>array('id', 'subject', 'category', 'project_name', 'priority', 'assigned', 'assigned_users', 
                'due_date', 'due_time', 'last_updated_date', 'last_updated_time'), 
            'utctotz'=>array(
                'due_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'due_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:i A'),
                'last_updated_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'last_updated_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:i A'),
                ),
            'lists'=>array('assigned_users')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['tasks']) ) {
        return array('stat'=>'ok', 'tasks'=>array());
    }
    return array('stat'=>'ok', 'tasks'=>$rc['tasks']);
}
?>
