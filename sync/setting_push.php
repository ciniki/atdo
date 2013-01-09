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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'syncBusinessModule');
	return ciniki_core_syncBusinessModule($ciniki, $sync, $business_id, 'ciniki.atdo', 'partial', 'setting');
}
?>
