<?php
//
// Description
// -----------
// This function will get the history of a field from the ciniki_core_change_logs table.
// This allows the user to view what has happened to a data element, and if they
// choose, revert to a previous version.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant to get the details for.
// key:                 The detail key to get the history for.
//
// Returns
// -------
//
function ciniki_atdo_getHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'atdo_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Atdo'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'private', 'checkAccess');
    $rc = ciniki_atdo_checkAccess($ciniki, $args['tnid'], 'ciniki.atdo.getHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    if( $args['field'] == 'appointment_date' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.atdo', 'ciniki_atdo_history', $args['tnid'], 'ciniki_atdos', $args['atdo_id'], $args['field'], 'utcdatetime');
    }
    if( $args['field'] == 'due_date' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryReformat');
        return ciniki_core_dbGetModuleHistoryReformat($ciniki, 'ciniki.atdo', 'ciniki_atdo_history', $args['tnid'], 'ciniki_atdos', $args['atdo_id'], $args['field'], 'date');
    }
    if( $args['field'] == 'private' ) {
        error_log('flag');
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistoryFlagBit');
        return ciniki_core_dbGetModuleHistoryFlagBit($ciniki, 'ciniki.atdo', 'ciniki_atdo_history', $args['tnid'], 'ciniki_atdos', $args['atdo_id'], 'perm_flags', 0x01, 'no', 'yes');
    }


    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.atdo', 'ciniki_atdo_history', $args['tnid'], 'ciniki_atdos', $args['atdo_id'], $args['field']);
}
?>
