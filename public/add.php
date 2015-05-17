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
function ciniki_atdo_add(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
		'type'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Type'),
		'parent_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Parent'),
		'project_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'name'=>'Project'),
        'subject'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Subject'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Category'), 
        'location'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Location'), 
        'content'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'name'=>'Content'), 
		'assigned'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Users Assigned'),
		'private'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Private Flag'),
		'status'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'1', 'name'=>'Status'),
		'priority'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'10', 'name'=>'Priority'),
		'customer_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Customer'),
		'product_ids'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'idlist', 'name'=>'Product'),
        'followup'=>array('required'=>'no', 'default'=>'', 'blank'=>'yes', 'name'=>'Followup'), 
        'appointment_date'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'type'=>'datetimetoutc', 'name'=>'Appointment Date'), 
        'appointment_duration'=>array('required'=>'no', 'default'=>'60', 'blank'=>'no', 'name'=>'Appointment Duration'), 
        'appointment_allday'=>array('required'=>'no', 'default'=>'no', 'blank'=>'no', 'name'=>'All day flag'), 
        'appointment_repeat_type'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Repeat flag'), 
        'appointment_repeat_interval'=>array('required'=>'no', 'default'=>'0', 'blank'=>'yes', 'name'=>'Repeat Interval'), 
        'appointment_repeat_end'=>array('required'=>'no', 'type'=>'date', 'default'=>'', 'blank'=>'yes', 'name'=>'Repeat End Date'), 
        'due_date'=>array('required'=>'no', 'blank'=>'yes', 'type'=>'datetimetoutc', 'default'=>'', 'name'=>'Due Date'), 
        'due_duration'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'name'=>'Due Date Duration'), 
        'due_allday'=>array('required'=>'no', 'default'=>'no', 'blank'=>'no', 'name'=>'Due Allday Flag'), 
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
	$args['perm_flags'] = 0;
	// Make messages private, always
	if( $args['type'] == 6 || (isset($args['private']) && $args['private'] == 'yes') ) {
		$args['perm_flags'] += 1;
	}
	$args['appointment_flags'] = 0;
	if( isset($args['appointment_allday']) && $args['appointment_allday'] == 'yes' ) {
		$args['appointment_flags'] += 1;
	}
	$args['due_flags'] = 0;
	if( isset($args['due_allday']) && $args['due_allday'] == 'yes' ) {
		$args['due_flags'] += 1;
	}

	$args['user_id'] = $ciniki['session']['user']['id'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'objectAdd');
	$rc = ciniki_core_objectAdd($ciniki, $args['business_id'], 'ciniki.atdo.atdo', $args, 0x04);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.atdo');
		return $rc;
	}
	$atdo_id = $rc['id'];

	//
	// Add followup
	//
	if( isset($args['followup']) && $args['followup'] != '' ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddFollowup');
		$rc = ciniki_core_threadAddFollowup($ciniki, 'ciniki.atdo', 'followup', $args['business_id'], 
			'ciniki_atdo_followups', 'ciniki_atdo_history', 'atdo', $atdo_id, array(
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
	// FIXME: Add refs if adding atdo for use with another module.
	// -- This use to be called attachments, code removed Oct 26, 2013

	//
	// Add the user who created the atdo, as a follower 
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
	$rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.atdo', 'user', $args['business_id'], 
		'ciniki_atdo_users', 'ciniki_atdo_history', 'atdo', $atdo_id, 
		$ciniki['session']['user']['id'], (0x01|0x04));
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
			$rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.atdo', 'user', $args['business_id'], 
				'ciniki_atdo_users', 'ciniki_atdo_history', 'atdo', $atdo_id, $user_id, (0x04));
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
