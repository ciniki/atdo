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
function ciniki_atdo_categoryRename(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'type'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Type'),
        'status'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Old Category'),
        'priority'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Old Category'),
        'user_id'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Old Category'),
        'old'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Old Category'),
        'new'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'New Category'),
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
    $rc = ciniki_atdo_checkAccess($ciniki, $args['tnid'], 'ciniki.atdo.categoryRename'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    if( isset($args['old']) && $args['old'] == 'Uncategorized' ) {
        $args['old'] = '';
    }

    //
    // Prepare some of the SQL snippets
    //
    $priority_sql = '';
    if( isset($args['priority']) && $args['priority'] != '' ) {
        $priority_sql = "AND ciniki_atdos.priority = '" . ciniki_core_dbQuote($ciniki, $args['priority']) . "' ";
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
    $user_sql = '';
    if( isset($args['user_id']) && $args['user_id'] > 0 ) {
        $user_sql = "INNER JOIN ciniki_atdo_users AS u1 ON ("
            . "ciniki_atdos.id = u1.atdo_id "
            . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
            . "AND (u1.perms&0x04) = 0x04 "
            . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }

    //
    // Get the list of items with that category
    //
    $strsql = "SELECT ciniki_atdos.id, "
        . "ciniki_atdos.category "
        . "FROM ciniki_atdos "
        . $user_sql
        . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_atdos.category = '" . ciniki_core_dbQuote($ciniki, $args['old']) . "' "
        . $priority_sql
        . $status_sql
        . "";
    if( isset($args['type']) && $args['type'] != '' ) {
        $strsql .= "AND type = '" . ciniki_core_dbQuote($ciniki, $args['type']) . "' ";
    }
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.atdo', 'item');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.19', 'msg'=>'Unable to load item', 'err'=>$rc['err']));
    }
    $atdos = isset($rc['rows']) ? $rc['rows'] : array();
    
    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectUpdate');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.atdo');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Update the items
    //
    foreach($atdos as $atdo) {
        $rc = ciniki_core_objectUpdate($ciniki, $args['tnid'], 'ciniki.atdo.atdo', $atdo['id'], array('category'=>$args['new']), 0x04);
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.20', 'msg'=>'Unable to update', 'err'=>$rc['err']));
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
