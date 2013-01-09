<?php
//
// Description
// -----------
// This method will return the history from the atdo module.
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_atdo_setting_list($ciniki, $sync, $business_id, $args) {
	//
	// Check the args
	//
	if( !isset($args['type']) ||
		($args['type'] != 'partial' && $args['type'] != 'full' && $args['type'] != 'incremental') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1040', 'msg'=>'No type specified'));
	}
	if( $args['type'] == 'incremental' 
		&& (!isset($args['since_uts']) || $args['since_uts'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1041', 'msg'=>'No timestamp specified'));
	}

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');

	//
	// Prepare the query to fetch the list
	//
	$strsql = "SELECT detail_key, UNIX_TIMESTAMP(last_updated) AS last_updated "	
		. "FROM ciniki_atdo_settings "
		. "WHERE ciniki_atdo_settings.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' ";
	if( $args['type'] == 'incremental' ) {
		$strsql .= "AND UNIX_TIMESTAMP(ciniki_atdo_settings.last_updated) >= '" . ciniki_core_dbQuote($ciniki, $args['since_uts']) . "' ";
	}
	$strsql .= "ORDER BY last_updated "
		. "";
	$rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.atdo', 'settings', 'detail_key');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'1042', 'msg'=>'Unable to get list', 'err'=>$rc['err']));
	}

	if( !isset($rc['settings']) ) {
		return array('stat'=>'ok', 'list'=>array());
	}

	return array('stat'=>'ok', 'list'=>$rc['settings']);
}
?>
