<?php
//
// Description
// -----------
// This function will return the list of available blocks to the ciniki.reporting module.
//
// Arguments
// ---------
// ciniki:
// tnid:     
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_atdo_reporting_blocks(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.atdo']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.220', 'msg'=>"I'm sorry, the block you requested does not exist."));
    }

    $blocks = array();

    //
    // Load the list of employees
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'tenantEmployees');
    $rc = ciniki_tenants_hooks_tenantEmployees($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.33', 'msg'=>'Unable to load employee list', 'err'=>$rc['err']));
    }
    $employees = isset($rc['users']) ? $rc['users'] : array();

    //
    // Return the list of blocks for the tenant
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.atdo', 0x02) ) {
        $blocks['ciniki.atdo.employeetasks'] = array(
            'name'=>'Employee Tasks',
            'module' => 'Tasks',
            'options'=>array(
                'user_id'=>array('label'=>'Employee', 'type'=>'select', 'options'=>$employees, 
                    'complex_options'=>array('name'=>'display_name', 'value'=>'user_id'),
                    ),
                'priority'=>array('label'=>'Priority', 'type'=>'toggle', 'default'=>'0', 'toggles'=>array(
                    '0' => 'All', '50' => 'High', '30' => 'Medium', '10' => 'Low',
                    )),
                ),
            );
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
