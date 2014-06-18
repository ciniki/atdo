<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_atdo_flags($ciniki) {
	$flags = array(
		array('flag'=>array('bit'=>'1', 'name'=>'Appointments')),
		array('flag'=>array('bit'=>'2', 'name'=>'Tasks')),
		array('flag'=>array('bit'=>'4', 'name'=>'FAQ')),
		array('flag'=>array('bit'=>'5', 'name'=>'Notes')),
		array('flag'=>array('bit'=>'6', 'name'=>'Messages')),
		);

	return array('stat'=>'ok', 'flags'=>$flags);
}
?>
