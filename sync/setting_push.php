<?php
//
// Description
// -----------
//
// Arguments
// ---------
// 
// Returns
// -------
//
function ciniki_atdo_setting_push(&$ciniki, &$sync, $business_id, $args) {
//	$args['type'] = 'partial';
//	//
//	// Sync the settings
//	//
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'private', 'syncModuleSettings');
//	$rc = ciniki_atdo_syncModuleSettings($ciniki, $sync, $business_id, $args);
//	if( $rc['stat'] != 'ok' ) {
//		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'254', 'msg'=>'Unable to sync settings'));
//	}
//
//	return array('stat'=>'ok');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusinessModule');
	return ciniki_core_syncBusinessModule($ciniki, $sync, $business_id, 'ciniki.atdo', 'partial', 'setting');
}
?>
