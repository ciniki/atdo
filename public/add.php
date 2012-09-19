<?php
//
// Description
// ===========
// This function will add a new atdo to the business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to add the atdo to.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_atdo_add($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'type'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No type specified'),
		'parent_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'errmsg'=>'No parent specified'),
		'project_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'errmsg'=>'No project specified'),
        'subject'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No subject specified'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No category specified'), 
        'location'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No location specified'), 
        'content'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No content specified'), 
		'assigned'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'errmsg'=>'No assignments specified'),
		'private'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'errmsg'=>'No private specified'),
		'status'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'errmsg'=>'No status specified'),
		'priority'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'10', 'errmsg'=>'No priority specified'),
		'customer_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'errmsg'=>'No customer specified'),
		'product_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'errmsg'=>'No product specified'),
        'followup'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No secondary_text specified'), 
        'appointment_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'datetime', 'errmsg'=>'No date specified'), 
        'appointment_duration'=>array('required'=>'no', 'default'=>'60', 'blank'=>'no', 'errmsg'=>'No duration specified'), 
        'appointment_allday'=>array('required'=>'no', 'default'=>'no', 'blank'=>'no', 'errmsg'=>'No allday specified'), 
        'appointment_repeat_type'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No repeat specified'), 
        'appointment_repeat_interval'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'errmsg'=>'No repeat interval specified'), 
        'appointment_repeat_end'=>array('required'=>'no', 'type'=>'date', 'default'=>'', 'blank'=>'yes', 'errmsg'=>'No repeat end specified'), 
        'due_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetime', 'default'=>'', 'errmsg'=>'No date specified'), 
        'due_allday'=>array('required'=>'no', 'default'=>'no', 'blank'=>'no', 'errmsg'=>'No due allday specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'private', 'checkAccess');
    $rc = ciniki_atdo_checkAccess($ciniki, $args['business_id'], 'ciniki.atdo.add'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//  
	// Turn off autocommit
	//  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.atdo');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Setup flags
	//
	$perm_flags = 0;
	// Make messages private, always
	if( $args['type'] == 6 || (isset($args['private']) && $args['private'] == 'yes') ) {
		$perm_flags += 1;
	}
	$appointment_flags = 0;
	if( isset($args['appointment_allday']) && $args['appointment_allday'] == 'yes' ) {
		$appointment_flags += 1;
	}
	$due_flags = 0;
	if( isset($args['due_allday']) && $args['due_allday'] == 'yes' ) {
		$due_allday += 1;
	}

	//
	// Add the atdo to the database
	//
	$strsql = "INSERT INTO ciniki_atdos (parent_id, project_id, uuid, business_id, type, category, status, priority, perm_flags, user_id, "
		. "subject, location, content, "
		. "appointment_date, appointment_duration, appointment_flags, "
		. "appointment_repeat_type, appointment_repeat_interval, appointment_repeat_end, "
		. "due_date, due_flags, "
		. "date_added, last_updated) VALUES ("
		. "'" . ciniki_core_dbQuote($ciniki, $args['parent_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['project_id']) . "', "
		. "UUID(), "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['category']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['status']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['priority']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $perm_flags) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['subject']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['location']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['content']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['appointment_date']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['appointment_duration']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $appointment_flags) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['appointment_repeat_type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['appointment_repeat_interval']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['appointment_repeat_end']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['due_date']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $due_flags) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.atdo');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'555', 'msg'=>'Unable to add item'));
	}
	$atdo_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//

	$changelog_fields = array(
		'category',
		'status',
		'priority',
		'perm_flags',
		'subject',
		'appointment_date',
		'appointment_duration',
		'appointment_flags',
		'appointment_repeat_type',
		'appointment_repeat_interval',
		'appointment_repeat_end',
		'due_date',
		'due_flags',
		);
	foreach($changelog_fields as $field) {
		$insert_name = $field;
		if( isset($ciniki['request']['args'][$field]) && $ciniki['request']['args'][$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.atdo', 'ciniki_atdo_history', $args['business_id'], 
				1, 'ciniki_atdos', $atdo_id, $insert_name, $ciniki['request']['args'][$field]);
		}
	}

	//
	// Add followup
	//
	if( isset($args['followup']) && $args['followup'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollowup');
		$rc = ciniki_core_threadAddFollowup($ciniki, 'ciniki.atdo', 'ciniki_atdo_followups', 'atdo', $atdo_id, array(
			'user_id'=>$ciniki['session']['user']['id'],
			'atdo_id'=>$atdo_id,
			'content'=>$args['followup']
			));
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
			return $rc;
		}
	}

	//
	// Add attachments to customers and products
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddAttachment');
	if( isset($args['customer_ids']) && is_array($args['customer_ids']) ) {
		foreach($args['customer_ids'] as $customer_id) {
			$rc = ciniki_core_threadAddAttachment($ciniki, 'ciniki.atdo', 'ciniki_atdo_attachments', 'atdo', $atdo_id,
				'ciniki', 'customers', 'customer', $customer_id);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
				return $rc;
			}
		}
	}
	if( isset($args['product_ids']) && is_array($args['product_ids']) ) {
		foreach($args['product_ids'] as $product_id) {
			$rc = ciniki_core_threadAddAttachment($ciniki, 'ciniki.atdo', 'ciniki_atdo_attachments', 'atdo', $atdo_id,
				'ciniki', 'products', 'product', $product_id);
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
				return $rc;
			}
		}
	}

	//
	// Add the user who created the atdo, as a follower 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
	$rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.atdo', 'ciniki_atdo_users', 'atdo', $atdo_id, $ciniki['session']['user']['id'], (0x01|0x04));
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
		return $rc;
	}

	//
	// Add users who were assigned.  If the creator also is assigned the atdo, then they will be 
	// both a follower (above code) and assigned (below code).
	// Add the viewed flag to be set, so it's marked as unread for new assigned users.
	//
	if( isset($args['assigned']) && is_array($args['assigned']) ) {
		foreach( $args['assigned'] as $user_id ) {
			$rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.atdo', 'ciniki_atdo_users', 'atdo', $atdo_id, $user_id, (0x04));
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
				return $rc;
			}
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
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'atdo');

	//
	// FIXME: Notify users
	//

	return array('stat'=>'ok', 'id'=>$atdo_id);
}
?>
