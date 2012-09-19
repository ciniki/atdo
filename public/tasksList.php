<?php
//
// Description
// ===========
// This method returns the lists of tasks for a user from a business.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:		The ID of the business to get the task list for.
// status:			(optional) Only lists tasks that are open or closed.
// limit:			(optional) The maximum number of records to return.
// 
// Returns
// -------
// <tasks>
// 		<task id="1" subject="Task subject" project_name="" assigned="yes" private="yes" due_date=""/>
// </tasks>
//
function ciniki_atdo_tasksList($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'private', 'checkAccess');
    $rc = ciniki_atdo_checkAccess($ciniki, $args['business_id'], 'ciniki.atdo.tasksList'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	$strsql = "SELECT ciniki_atdos.id, ciniki_atdos.subject, ciniki_projects.name AS project_name, "
		. "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
		. "IF((ciniki_atdos.perm_flags&0x01)=1, 'yes', 'no') AS private, "
		. "IF(ciniki_atdos.status=1, 'open', 'closed') AS status, "
		. "ciniki_atdos.priority, "
		. "IF((u1.perms&0x04)=4, 'yes', 'no') AS assigned, "
	//	. "DATE_FORMAT(start_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS start_date, "
	//	. "duration, "
		. "IFNULL(DATE_FORMAT(ciniki_atdos.due_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS due_date, "
		. "IF((ciniki_atdos.due_flags&0x01)=1, '', IF(ciniki_atdos.due_date=0, '', DATE_FORMAT(ciniki_atdos.due_date, '%l:%i %p'))) AS due_time, "
		. "u2.user_id AS assigned_user_ids, "
		. "IFNULL(u3.display_name, '') AS assigned_users "
		. "FROM ciniki_atdos "
		. "LEFT JOIN ciniki_atdo_users AS u1 ON (ciniki_atdos.id = u1.atdo_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
		. "LEFT JOIN ciniki_atdo_users AS u2 ON (ciniki_atdos.id = u2.atdo_id && (u2.perms&0x04) = 4) "
		. "LEFT JOIN ciniki_users AS u3 ON (u2.user_id = u3.id) "
		. "LEFT JOIN ciniki_projects ON (ciniki_atdos.project_id = ciniki_projects.id AND ciniki_projects.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') "
		. "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_atdos.type = 2 "
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
	// Check for public/private tasks, and if private make sure user created or is assigned
	$strsql .= "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to business
			// created by the user requesting the list
			. "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
			// Assigned to the user requesting the list
			. "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
			. ") "
		. "ORDER BY assigned DESC, ciniki_atdos.priority DESC, ciniki_atdos.due_date DESC, ciniki_atdos.id, u3.display_name "
		. "";
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.atdo', array(
		array('container'=>'tasks', 'fname'=>'id', 'name'=>'task',
			'fields'=>array('id', 'subject', 'project_name', 'allday', 'status', 'priority', 'private', 'assigned', 'assigned_user_ids', 'assigned_users', 'due_date', 'due_time'), 'idlists'=>array('assigned_user_ids'), 'lists'=>array('assigned_users')),
		));
	// error_log($strsql);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['tasks']) ) {
		return array('stat'=>'ok', 'tasks'=>array());
	}
	return array('stat'=>'ok', 'tasks'=>$rc['tasks']);
}
?>
