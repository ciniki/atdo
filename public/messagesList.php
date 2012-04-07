<?php
//
// Description
// ===========
// This function will return a list of messages assigned to the user and/or the business.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <messages>
// 		<message id="1" subject="Task subject" assigned="yes" private="yes" due_date=""/>
// </messages>
//
function ciniki_atdo_messagesList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'status'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No status specified'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No limit specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/atdo/private/checkAccess.php');
    $rc = ciniki_atdo_checkAccess($ciniki, $args['business_id'], 'ciniki.atdo.messagesList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/dateFormat.php');
	$date_format = ciniki_users_dateFormat($ciniki);

	$strsql = "SELECT ciniki_atdos.id, subject, "
	//	. "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
		. "IF((ciniki_atdos.perm_flags&0x01)=1, 'yes', 'no') AS private, "
		. "IF(ciniki_atdos.status=1, 'open', 'closed') AS status, "
	//	. "priority, "
		. "IF((u1.perms&0x04)=4, 'yes', 'no') AS assigned, "
		. "IF((u1.perms&0x08)=8, 'no', 'yes') AS viewed, "
	//	. "DATE_FORMAT(start_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS start_date, "
		. "u2.user_id AS assigned_user_ids, "
		. "IFNULL(u3.display_name, '') AS assigned_users "
		. "FROM ciniki_atdos "
		. "LEFT JOIN ciniki_atdo_users AS u1 ON (ciniki_atdos.id = u1.atdo_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
		. "LEFT JOIN ciniki_atdo_users AS u2 ON (ciniki_atdos.id = u2.atdo_id && (u2.perms&0x04) = 4) "
		. "LEFT JOIN ciniki_users AS u3 ON (u2.user_id = u3.id) "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND type = 6 "
		. "";
	if( isset($args['status']) ) {
		switch($args['status']) {
			case 'Open':
			case 'open': $strsql .= "AND ciniki_atdos.status = 1 ";
				break;
			case 'Closed':
			case 'closed': $strsql .= "AND ciniki_atdos.status = 60 ";
				break;
		}
	}
	// Check for public/private messages, and if private make sure user created or is assigned
	$strsql .= "AND ((perm_flags&0x01) = 0 "  // Public to business
			// created by the user requesting the list
			. "OR ((perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
			// Assigned to the user requesting the list
			. "OR ((perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04 AND (u1.perms&0x10) = 0x10 ) "
			. ") "
		. "ORDER BY assigned DESC, priority DESC, due_date DESC, ciniki_atdos.id, u3.display_name "
		. "";
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'atdo', array(
		array('container'=>'messages', 'fname'=>'id', 'name'=>'message',
			'fields'=>array('id', 'subject', 'viewed', 'status', 'assigned_user_ids', 'assigned_users'), 
			'idlists'=>array('assigned_user_ids'), 'lists'=>array('assigned_users')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['messages']) ) {
		return array('stat'=>'ok', 'messages'=>array());
	}
	return array('stat'=>'ok', 'messages'=>$rc['messages']);
}
?>
