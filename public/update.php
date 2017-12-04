<?php
//
// Description
// -----------
// Update a task
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_atdo_update(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'atdo_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Atdo'), 
        'type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'),
        'parent_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Parent'),
        'project_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Project'),
        'subject'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Subject'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        'location'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Location'), 
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Content'), 
        'assigned'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Assigned Users'),
        'private'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Private Flag'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Status'),
        'priority'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Priority'),
        'customer_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Customer'),
        'product_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Product'),
        'followup'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Followup'), 
        'appointment_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Appointment Date'), 
        'appointment_duration'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Appointment Duration'), 
        'appointment_allday'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Appointment All Day Flag'), 
        'appointment_repeat_type'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Appointment Repeat'), 
        'appointment_repeat_interval'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Appointment Repeat Interval'), 
        'appointment_repeat_end'=>array('required'=>'no', 'type'=>'date', 'blank'=>'yes', 'name'=>'Appointment Repeat End'), 
        'due_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'name'=>'Due Date'), 
        'due_duration'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Due Date Duration'), 
        'due_allday'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Due All Day Flag'), 
        'userdelete'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'User Delete Flag'),
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
    $rc = ciniki_atdo_checkAccess($ciniki, $args['tnid'], 'ciniki.atdo.update'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.atdo');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Add the order to the database
    //
    $strsql = "UPDATE ciniki_atdos SET last_updated = UTC_TIMESTAMP()";

    //
    // Turn allday flag on or off
    //

    if( isset($args['appointment_allday']) ) {
        if( $args['appointment_allday'] == 'yes' ) {
            $strsql .= ', appointment_flags=(appointment_flags|0x01)';
        } else {
            $strsql .= ', appointment_flags=(appointment_flags&~0x01)';
        }
    }
    if( isset($args['due_allday']) ) {
        if( $args['due_allday'] == 'yes' ) {
            $strsql .= ', due_flags=(due_flags|0x01)';
        } else {
            $strsql .= ', due_flags=(due_flags&~0x01)';
        }
    }
    // Make sure the message is private, or if other type is set as private
    if( (isset($args['type']) && $args['type'] == 6) || (isset($args['private']) && $args['private'] == 'yes') ) {
        $strsql .= ', perm_flags=(perm_flags|0x01)';
    } elseif( isset($args['private']) ) {
        $strsql .= ', perm_flags=(perm_flags&~0x01)';
    }

    //
    // Add all the fields to the change log
    //
    $changelog_fields = array(
        'parent_id',
        'project_id',
        'type',
        'category',
        'subject',
        'location',
        'content',
        'status',
        'priority',
        'appointment_date',
        'appointment_duration',
        'appointment_repeat_type',
        'appointment_repeat_interval',
        'appointment_repeat_end',
        'due_date',
        'due_duration',
        );
    foreach($changelog_fields as $field) {
        if( isset($args[$field]) ) {
            $strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.atdo', 'ciniki_atdo_history', $args['tnid'], 
                2, 'ciniki_atdos', $args['atdo_id'], $field, $args[$field]);
        }
    }
    $strsql .= "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['atdo_id']) . "' ";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.atdo');
    if( $rc['stat'] != 'ok' ) { 
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.17', 'msg'=>'Unable to update task'));
    }

    //
    // Push the change to the other servers
    //
    $ciniki['syncqueue'][] = array('push'=>'ciniki.atdo.atdo', 
        'args'=>array('id'=>$args['atdo_id']));

    //
    // Check if the user has delete the message from their messages
    // which results in it being marked deleted in ciniki_atdo_users.perms
    //
    if( isset($args['userdelete']) && $args['userdelete'] == 'yes' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
        $rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.atdo', 'user', $args['tnid'], 
            'ciniki_atdo_users', 'ciniki_atdo_history', 'atdo', $args['atdo_id'], 
            $ciniki['session']['user']['id'], 0x10);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.18', 'msg'=>'Unable to remove message', 'err'=>$rc['err']));
        }
    }


    //
    // Check if the assigned users has changed
    //
    if( isset($args['assigned']) && is_array($args['assigned']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadRemoveUserPerms');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
        //
        // Get the list of currently assigned users
        //
        $strsql = "SELECT user_id "
            . "FROM ciniki_atdo_users "
            . "WHERE atdo_id = '" . ciniki_core_dbQuote($ciniki, $args['atdo_id']) . "' "
            . "AND (perms&0x04) = 4 "
            . "";
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.atdo', 'users', 'user_id');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.19', 'msg'=>'Unable to load task information', 'err'=>$rc['err']));
        }
        $task_users = $rc['users'];
        // 
        // Remove users no longer assigned
        //
        $to_be_removed = array_diff($task_users, $args['assigned']);
        if( is_array($to_be_removed) ) {
            foreach($to_be_removed as $user_id) {
                $rc = ciniki_core_threadRemoveUserPerms($ciniki, 'ciniki.atdo', 'user', 
                $args['tnid'], 'ciniki_atdo_users', 'ciniki_atdo_history', 
                'atdo', $args['atdo_id'], $user_id, 0x04);
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.20', 'msg'=>'Unable to update task information', 'err'=>$rc['err']));
                }
            }
        }
        $to_be_added = array_diff($args['assigned'], $task_users);
        if( is_array($to_be_added) ) {
            foreach($to_be_added as $user_id) {
                $rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.atdo', 'user', 
                    $args['tnid'], 'ciniki_atdo_users', 'ciniki_atdo_history', 
                    'atdo', $args['atdo_id'], $user_id, (0x04));
                if( $rc['stat'] != 'ok' ) {
                    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.21', 'msg'=>'Unable to update task information', 'err'=>$rc['err']));
                }
            }
        }
    }

    //
    // Check if there is a followup, but after we have adjusted the assigned users
    // so any new users get the unviewed flag set
    //
    if( isset($args['followup']) && $args['followup'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollowup');
        $rc = ciniki_core_threadAddFollowup($ciniki, 'ciniki.atdo', 'followup', $args['tnid'], 
            'ciniki_atdo_followups', 'ciniki_atdo_history', 'atdo', $args['atdo_id'], array(
            'user_id'=>$ciniki['session']['user']['id'],
            'atdo_id'=>$args['atdo_id'],
            'content'=>$args['followup']
            ));
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
            return $rc;
        }
    }

    //
    // Update the assigned users viewed flag, and remove any user delete flags so they see the message again
    // if followup specified, or status has changed.
    //
    if( (isset($args['followup']) && $args['followup'] != '')
        || (isset($args['status']) && $args['status'] != '' ) ) {
        //
        // Get the list of currently assigned users
        //
        $strsql = "SELECT user_id "
            . "FROM ciniki_atdo_users "
            . "WHERE atdo_id = '" . ciniki_core_dbQuote($ciniki, $args['atdo_id']) . "' "
            . "AND user_id <> '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
            . "AND (perms&0x04) = 0x04 "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQueryList');
        $rc = ciniki_core_dbQueryList($ciniki, $strsql, 'ciniki.atdo', 'users', 'user_id');
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.22', 'msg'=>'Unable to load task information', 'err'=>$rc['err']));
        }
        $task_users = $rc['users'];
        // 
        // Remove the delete flag if set, add unread flag
        //
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadRemoveUserPerms');
        foreach($task_users as $user_id) {
            $rc = ciniki_core_threadRemoveUserPerms($ciniki, 'ciniki.atdo', 'user', 
            $args['tnid'], 'ciniki_atdo_users', 'ciniki_atdo_history', 
            'atdo', $args['atdo_id'], $user_id, 0x18);
            if( $rc['stat'] != 'ok' ) {
                return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.23', 'msg'=>'Unable to update task information', 'err'=>$rc['err']));
            }
        }
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.atdo');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'atdo');

    return array('stat'=>'ok');
}
?>
