<?php
//
// Description
// ===========
// This function will return a list of messages assigned to the user and/or the tenant.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
// <messages>
//      <message id="1" subject="Task subject" assigned="yes" private="yes" due_date=""/>
// </messages>
//
function ciniki_atdo_messagesList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'), 
        'user_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'User'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        'stats'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Stats'), 
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
    $rc = ciniki_atdo_checkAccess($ciniki, $args['tnid'], 'ciniki.atdo.messagesList'); 
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

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    //
    // Prepare the SQL snippets
    //
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
            . "AND (u1.perms&0x10) = 0 "
            . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }
    $user_sql = '';
    if( isset($owners[$ciniki['session']['user']['id']]) ) {
        if( isset($args['user_id']) && $args['user_id'] > 0 ) {
            $user_sql = "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' ";
            $assigned_join_sql = "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                . "ciniki_atdos.id = u1.atdo_id "
                . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
                . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        } else {
            $assigned_join_sql = "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                . "ciniki_atdos.id = u1.atdo_id "
                . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . ") ";
        }
    } elseif( $assigned_join_sql == '' ) {
        $assigned_sql = "AND (u1.perms&0x04) = 4 ";
        $assigned_join_sql = "LEFT JOIN ciniki_atdo_users AS u1 ON ("
            . "ciniki_atdos.id = u1.atdo_id "
            . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND (u1.perms&0x10) = 0 "
            . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }
    $limit_sql = '';
    if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
        $limit_sql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
    }
    $strsql = "SELECT ciniki_atdos.id, "
        . "ciniki_atdos.subject, "
        . $project_name_sql
        . "IF((ciniki_atdos.perm_flags&0x01)=1, 'yes', 'no') AS private, "
        . "IF(ciniki_atdos.status=1, 'open', 'closed') AS status, "
        . "IF((u1.perms&0x04)=4, 'yes', 'no') AS assigned, "
        . "IF((u1.perms&0x08)=8, 'yes', 'no') AS viewed, "
        . "u2.user_id AS assigned_user_ids, "
        . "IFNULL(u3.display_name, '') AS assigned_users, "
        . "CAST((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(IFNULL(ciniki_atdo_followups.date_added, ciniki_atdos.last_updated))) as DECIMAL(12,0)) AS age_followup, "
        . "IFNULL(u4.display_name, u5.display_name) AS followup_user "
        . "FROM ciniki_atdos "
        . $assigned_join_sql
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
        . "LEFT JOIN ciniki_users AS u4 ON ("
            . "ciniki_atdo_followups.user_id = u4.id "
            . ") "
        . "LEFT JOIN ciniki_users AS u5 ON ("
            . "ciniki_atdos.user_id = u5.id "
            . ") "
        . $projects_sql
        . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_atdos.type = 6 "
//        . "AND (u1.perms&0x10) = 0 "
        . $status_sql
        . $assigned_sql
        . $user_sql
        . "ORDER BY ciniki_atdos.last_updated, ciniki_atdos.priority DESC, ciniki_atdos.due_date DESC, ciniki_atdos.id, u3.display_name, ciniki_atdo_followups.date_added DESC "
        . $limit_sql
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.atdo', array(
        array('container'=>'messages', 'fname'=>'id', 'name'=>'message',
            'fields'=>array('id', 'subject', 'project_name', 'viewed', 'status', 'assigned_user_ids', 'assigned_users', 'last_followup_age'=>'age_followup', 'last_followup_user'=>'followup_user'), 
            'idlists'=>array('assigned_user_ids'), 'lists'=>array('assigned_users')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $rsp = array('stat'=>'ok', 'messages'=>isset($rc['messages']) ? $rc['messages'] : array());

    //
    // Get the stats for the different statuses
    //
    if( isset($args['stats']) ) {
        //
        // Get the stats for the different statuses
        //
        $rsp['statuslist'] = array(
            array('label' => 'Open', 'id' => 'open', 'num_messages' => 0),
            array('label' => 'Closed', 'id' => 'closed', 'num_messages' => 0),
            array('label' => 'All', 'id' => '', 'num_messages' => 0),
            );
        if( isset($owners[$ciniki['session']['user']['id']]) ) {
            //
            // Get complete list for owners
            //
            $strsql = "SELECT status, COUNT(*) AS num_messages "
                . "FROM ciniki_atdos "
                . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_atdos.type = 6 "
                . "GROUP BY status "
                . "";
        } else {
            //
            // Filter for assigned tasks for the user
            //
            $strsql = "SELECT status, COUNT(*) AS num_messages "
                . "FROM ciniki_atdos "
                . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                    . "ciniki_atdos.id = u1.atdo_id "
                    . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                    . "AND (u1.perms&0x10) = 0 "
                    . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_atdos.type = 6 "
                . "AND ("
                    // created by the user requesting the list
                    . "ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
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
            $rsp['statuslist'][0]['num_messages'] = $rc['num']['1'];
            $rsp['statuslist'][2]['num_messages'] += $rc['num']['1'];
        }
        if( isset($rc['num']['60']) ) {
            $rsp['statuslist'][1]['num_messages'] = $rc['num']['60'];
            $rsp['statuslist'][2]['num_messages'] += $rc['num']['60'];
        }

        //
        // If owner, then provide all employee list and stats
        //
        if( isset($owners[$ciniki['session']['user']['id']]) ) {
            //
            // Get the list of employees
            //
            $strsql = "SELECT u2.id, u2.display_name, COUNT(*) AS num_messages "
                . "FROM ciniki_atdos "
                . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                    . "ciniki_atdos.id = u1.atdo_id "
                    . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                    . ") "
                . "LEFT JOIN ciniki_users AS u2 ON (" 
                    . "u1.user_id = u2.id "
                    . ") "
                . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
                . "AND ciniki_atdos.type = 6 "
                . $status_sql
                . "GROUP BY u2.id "
                . "ORDER BY u2.display_name "
                . "";
            $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.atdo', array(
                array('container'=>'employees', 'fname'=>'id', 'fields'=>array('id', 'display_name', 'num_messages')),
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
