<?php
//
// Description
// -----------
// Search messages by subject and date
//
// Arguments
// ---------
// user_id: 		The user making the request
// search_str:		The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_atdo_messagesSearchQuick($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No search specified'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No limit specified'), 
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
    $rc = ciniki_atdo_checkAccess($ciniki, $args['business_id'], 'ciniki.atdo.messagesSearchQuick', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the number of messages in each status for the business, 
	// if no rows found, then return empty array
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	$strsql = "SELECT ciniki_atdos.id, ciniki_atdos.subject, a1.subject AS parent_subject, "
//		. "IF((ciniki_atdos.flags&0x02)=2, 'yes', 'no') AS private, "
		. "IF((u1.perms&0x04)=4, 'yes', 'no') AS assigned, "
		. "IF((u1.perms&0x08)=8, 'yes', 'no') AS viewed, "
		. "IFNULL(u3.display_name, '') AS assigned_users "
		. "FROM ciniki_atdos "
		. "LEFT JOIN ciniki_atdo_users AS u1 ON (ciniki_atdos.id = u1.atdo_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
		. "LEFT JOIN ciniki_atdo_users AS u2 ON (ciniki_atdos.id = u2.atdo_id && (u2.perms&0x04) = 4) "
		. "LEFT JOIN ciniki_users AS u3 ON (u2.user_id = u3.id) "
		. "LEFT JOIN ciniki_atdo_followups ON (ciniki_atdos.id = ciniki_atdo_followups.atdo_id) "
		. "LEFT JOIN ciniki_atdos AS a1 ON (ciniki_atdos.parent_id = a1.id AND a1.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
		. "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_atdos.type = 6 "		// Messages
		. "AND (u1.perms&0x10) = 0 " 		// Check for message which haven't been deleted by user
		. "AND ciniki_atdos.status = 1 "
		. "AND (ciniki_atdos.subject LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_atdos.subject LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_atdo_followups.content LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_atdo_followups.content LIKE ' %" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. ") "
		. "";
	// Check for public/private atdos, and if private make sure user created or is assigned
	$strsql .= "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to business
			// created by the user requesting the list
			. "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
			// Assigned to the user requesting the list, and the user hasn't deleted the message
			. "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04 AND (u1.perms&0x10) <> 0x10 ) "
			. ") "
		. "GROUP BY ciniki_atdos.id, u3.id "
		. "ORDER BY assigned DESC, ciniki_atdos.id, u3.display_name "
		. "";
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} else {
		$strsql .= "LIMIT 25 ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.atdo', array(
		array('container'=>'messages', 'fname'=>'id', 'name'=>'message',
			'fields'=>array('id', 'subject', 'viewed', 'parent_subject', 'assigned', 'assigned_users'), 'lists'=>array('assigned_users')),
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
