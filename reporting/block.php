<?php
//
// Description
// -----------
// This function will return the report details for a requested report block.
//
// Arguments
// ---------
// ciniki:
// tnid:     The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_atdo_reporting_block(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.atdo']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.31', 'msg'=>"That report is not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($args['block_ref']) || !isset($args['options']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.32', 'msg'=>"No block specified."));
    }

    //
    // The array to store the report data
    //

    //
    // Return the list of reports for the tenant
    //
    if( $args['block_ref'] == 'ciniki.atdo.employeetasks' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'reporting', 'blockEmployeeTasks');
        return ciniki_atdo_reporting_blockEmployeeTasks($ciniki, $tnid, $args['options']);
    } elseif( $args['block_ref'] == 'ciniki.atdo.completedtasks' ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'reporting', 'blockCompletedTasks');
        return ciniki_atdo_reporting_blockCompletedTasks($ciniki, $tnid, $args['options']);
    }

    return array('stat'=>'ok');
}
?>
