<?php
//
// Description
// -----------
// This function will clean up the history for atdo.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_atdo_dbIntegrityCheck($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'fix'=>array('required'=>'no', 'default'=>'no', 'name'=>'Fix Problems'),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to business_id as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'private', 'checkAccess');
    $rc = ciniki_atdo_checkAccess($ciniki, $args['business_id'], 'ciniki.atdo.dbIntegrityCheck', 0);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDelete');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbFixTableHistory');

    if( $args['fix'] == 'yes' ) {
        //
        // Update the history for ciniki_atdos
        //
        $rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.atdo', $args['business_id'],
            'ciniki_atdos', 'ciniki_atdo_history', 
            array('uuid', 'parent_id', 'project_id', 'type', 'category', 'status',
                'priority', 'perm_flags', 'user_id', 'subject', 'location',
                'content', 'appointment_date', 'appointment_duration', 'appointment_flags',
                'appointment_repeat_type', 'appointment_repeat_interval', 
                'appointment_repeat_end', 'due_date', 'due_duration', 'due_flags'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Update the history for ciniki_atdo_followups
        //
        $rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.atdo', $args['business_id'],
            'ciniki_atdo_followups', 'ciniki_atdo_history', 
            array('uuid', 'parent_id', 'atdo_id', 'user_id', 'content'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Update the history for ciniki_atdo_users
        //
        $rc = ciniki_core_dbFixTableHistory($ciniki, 'ciniki.atdo', $args['business_id'],
            'ciniki_atdo_users', 'ciniki_atdo_history', 
            array('uuid', 'atdo_id', 'user_id', 'perms'));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Check for items missing a UUID
        //
        $strsql = "UPDATE ciniki_atdo_history SET uuid = UUID() WHERE uuid = ''";
        $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.atdo');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        //
        // Remote any entries with blank table_key, they are useless we don't know what they were attached to
        //
        $strsql = "DELETE FROM ciniki_atdo_history WHERE table_key = ''";
        $rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.atdo');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
    }

    return array('stat'=>'ok');
}
?>
