<?php
//
// Description
// -----------
// This method allow's a user to close a task.  They must have suffcient permissions to
// close the task
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The tenant the task is attached to.
// atdo_id:             The ID of the task to be closed.
// content:             (optional) Any followup content to be added during close.
// 
// Returns
// -------
// <rsp stat='ok'/>
//
function ciniki_atdo_taskClose(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'atdo_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Atdo'),
        'content'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Followup'),
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
    $rc = ciniki_atdo_checkAccess($ciniki, $args['tnid'], 'ciniki.atdo.taskClose', $args['atdo_id'], $ciniki['session']['user']['id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    
    //
    // Setup the other arguments required for adding a thread.  These are arguments
    // which should not come through the API, but be set within the API code.
    //
    $args['user_id'] = $ciniki['session']['user']['id'];

    // 
    // Turn of auto commit in the database
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
    // Check if there is a followup
    //
    if( isset($args['content']) && $args['content'] != '' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollowup');
        $rc = ciniki_core_threadAddFollowup($ciniki, 'ciniki.atdo', 'followup', $args['tnid'], 
            'ciniki_atdo_followups', 'ciniki_atdo_history', 'atdo', $args['atdo_id'], $args);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
            return $rc;
        }

        //
        // Make sure the user is attached as a follower.  They may already be attached, but it
        // will make sure the flag is set.
        // 
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
        $rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.atdo', 'user', $args['tnid'], 
            'ciniki_atdo_users', 'ciniki_atdo_history', 
            'atdo', $args['atdo_id'], $ciniki['session']['user']['id'], (0x01));
//      ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollower');
//      $rc = ciniki_core_threadAddFollower($ciniki, 'ciniki.atdo', 'user', $args['tnid'], 
//          'ciniki_atdo_users', 'ciniki_atdo_history', 
//          'atdo', $args['atdo_id'], $ciniki['session']['user']['id']);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
            return $rc;
        }
    }

    //
    // Close the task
    //
    $strsql = "UPDATE ciniki_atdos SET status = 60, last_updated = UTC_TIMESTAMP() "
        . "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['atdo_id']) . "' "
        . "AND tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.atdo');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
        return $rc;
    }

    $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.atdo', 'ciniki_atdo_history', $args['tnid'],
        2, 'ciniki_atdos', $args['atdo_id'], 'status', 60);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
        return $rc;
    }

    //
    // Push the change to the other servers
    //
    $ciniki['syncqueue'][] = array('push'=>'ciniki.atdo.atdo', 
        'args'=>array('id'=>$args['atdo_id']));

    //
    // FIXME: Notify the other users on this thread there was an update.
    //
    // ciniki_core_threadNotifyUsers($ciniki, 'ciniki.atdo', 'ciniki_atdo_users', 'followup');
    //

    //
    // Commit the changes
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
