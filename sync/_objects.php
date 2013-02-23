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
function ciniki_atdo_sync_objects($ciniki, &$sync, $business_id, $args) {
	//
	// Note: Pass the standard set of arguments in, they may be required in the future
	//

	$objects = array();
	$objects['atdo'] = array(
		'name'=>'Atdo',
		'table'=>'ciniki_atdos',
		'fields'=>array(
			'parent_id'=>array(),
			'project_id'=>array('ref'=>'ciniki.projects.project'),
			'type'=>array(),
			'category'=>array(),
			'status'=>array(),
			'priority'=>array(),
			'perm_flags'=>array(),
			'user_id'=>array('ref'=>'ciniki.users.user'),
			'subject'=>array(),
			'location'=>array(),
			'content'=>array(),
			'appointment_date'=>array(),
			'appointment_duration'=>array(),
			'appointment_flags'=>array(),
			'appointment_repeat_type'=>array(),
			'appointment_repeat_interval'=>array(),
			'appointment_repeat_end'=>array(),
			'due_date'=>array(),
			'due_duration'=>array(),
			'due_flags'=>array(),
			),
		'history_table'=>'ciniki_atdo_history',
		);
// FIXME: Allow for attachments
//	$objects['attachment'] = array(
//		'name'=>'Atdo Attachment',
//		'table'=>'ciniki_atdo_attachments',
//		'fields'=>array(
//			'atdo_id'=>array('ref'=>'ciniki.atdos.atdo'),
//			'flags'=>array(),
//			'package'=>array(),
//			'module'=>array(),
//			'element'=>array(),
//			'element_id'=>array(),
//			),
//		'history_table'=>'ciniki_atdo_attachments',
//		);
	$objects['followup'] = array(
		'name'=>'Atdo Followup',
		'table'=>'ciniki_atdo_followups',
		'fields'=>array(
			'parent_id'=>array(),
			'atdo_id'=>array('ref'=>'ciniki.atdos.atdo'),
			'user_id'=>array('ref'=>'ciniki.users.user'),
			'content'=>array(),
			),
		'history_table'=>'ciniki_atdo_history',
		);
	$objects['user'] = array(
		'name'=>'Atdo User',
		'table'=>'ciniki_atdo_users',
		'fields'=>array(
			'atdo_id'=>array('ref'=>'ciniki.atdos.atdo'),
			'user_id'=>array('ref'=>'ciniki.users.user'),
			'perms'=>array(),
			),
		'history_table'=>'ciniki_atdo_history',
		);
	$objects['settings'] = array(
		'type'=>'settings',
		'name'=>'Atdo Settings',
		'table'=>'ciniki_atdo_settings',
		'history_table'=>'ciniki_atdo_history',
		);

	return array('stat'=>'ok', 'objects'=>$objects);
}
?>
