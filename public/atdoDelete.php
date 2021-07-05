<?php
//
// Description
// -----------
// This method will delete an atdo item from the tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the atdo is attached to.
// atdo_id:            The ID of the atdo to be removed.
//
// Returns
// -------
//
function ciniki_atdo_atdoDelete(&$ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'atdo_id'=>array('required'=>'yes', 'default'=>'', 'blank'=>'yes', 'name'=>'Item'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'private', 'checkAccess');
    $rc = ciniki_atdo_checkAccess($ciniki, $args['tnid'], 'ciniki.atdo.atdoDelete');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Get the list of followups
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_atdo_followups "
        . "WHERE atdo_id = '" . ciniki_core_dbQuote($ciniki, $args['atdo_id']) . "' "
        . ""; 
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.atdo', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.41', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $followups = isset($rc['rows']) ? $rc['rows'] : array();
    
    //
    // Get the list of users attached to the atdo
    //
    $strsql = "SELECT id, uuid "
        . "FROM ciniki_atdo_users "
        . "WHERE atdo_id = '" . ciniki_core_dbQuote($ciniki, $args['atdo_id']) . "' "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.atdo', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.26', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $users = isset($rc['rows']) ? $rc['rows'] : array();

    //
    // Start transaction
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.atdo');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Remove the followups
    //
    foreach($followups as $followup) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.atdo.followup', $followup['id'], $followup['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.27', 'msg'=>'Unable to remove followup', 'err'=>$rc['err']));
        }
    }

    //
    // Remove the users
    //
    foreach($users as $user) {
        $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.atdo.user', $user['id'], $user['uuid'], 0x04);
        if( $rc['stat'] != 'ok' ) {
            ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.42', 'msg'=>'Unable to remove user', 'err'=>$rc['err']));
        }
    }

    //
    // Remove the atdo
    //
    $rc = ciniki_core_objectDelete($ciniki, $args['tnid'], 'ciniki.atdo.atdo', $args['atdo_id'], null, 0x04);
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.28', 'msg'=>'Unable to remove item', 'err'=>$rc['err']));
    }

    //
    // Commit the transaction
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
