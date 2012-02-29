<?php
//
// Description
// -----------
// Search tasks by subject and date
//
// Arguments
// ---------
// user_id: 		The user making the request
// search_str:		The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_atdo_tasksSearchQuick($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
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
    require_once($ciniki['config']['core']['modules_dir'] . '/atdo/private/checkAccess.php');
    $rc = ciniki_atdo_checkAccess($ciniki, $args['business_id'], 'ciniki.atdo.searchQuick', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the number of tasks in each status for the business, 
	// if no rows found, then return empty array
	//
	$strsql = "SELECT ciniki_atdos.id, subject, priority, "
//		. "IF((ciniki_atdos.flags&0x02)=2, 'yes', 'no') AS private, "
		. "IF((u1.perms&0x04)=4, 'yes', 'no') AS assigned, "
		. "IFNULL(DATE_FORMAT(ciniki_atdos.due_date, '%b %e, %Y'), '') AS due_date, "
		. "IF((ciniki_atdos.due_flags&0x01)=1, '', IF(ciniki_atdos.due_date=0, '', DATE_FORMAT(ciniki_atdos.due_date, '%l:%i %p'))) AS due_time, "
		. "IFNULL(u3.display_name, '') AS assigned_users "
		. "FROM ciniki_atdos "
		. "LEFT JOIN ciniki_atdo_users AS u1 ON (ciniki_atdos.id = u1.atdo_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
		. "LEFT JOIN ciniki_atdo_users AS u2 ON (ciniki_atdos.id = u2.atdo_id && (u2.perms&0x04) = 4) "
		. "LEFT JOIN ciniki_users AS u3 ON (u2.user_id = u3.id) "
		. "LEFT JOIN ciniki_atdo_followups ON (ciniki_atdos.id = ciniki_atdo_followups.atdo_id ) "
		. "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_atdos.type = 2 "		// Tasks
		. "AND ciniki_atdos.status = 1 "
		. "AND (subject LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR subject LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_atdo_followups.content LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR ciniki_atdo_followups.content LIKE ' %" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. ") "
		. "";
	// Check for public/private atdos, and if private make sure user created or is assigned
	$strsql .= "AND ((perm_flags&0x01) = 0 "  // Public to business
			// created by the user requesting the list
			. "OR ((perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
			// Assigned to the user requesting the list
			. "OR ((perm_flags&0x01) = 1 AND u1.perms = 0x04) "
			. ") "
		. "GROUP BY ciniki_atdos.id, u3.id "
		. "ORDER BY assigned DESC, priority DESC, due_date, ciniki_atdos.id, u3.display_name "
		. "";
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} else {
		$strsql .= "LIMIT 25 ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'atdo', array(
		array('container'=>'tasks', 'fname'=>'id', 'name'=>'task',
			'fields'=>array('id', 'subject', 'priority', 'assigned', 'assigned_users', 'due_date', 'due_time'), 'lists'=>array('assigned_users')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['tasks']) ) {
		return array('stat'=>'ok', 'tasks'=>array());
	}
	return array('stat'=>'ok', 'tasks'=>$rc['tasks']);

//	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQuery.php');
//	return ciniki_core_dbRspQuery($ciniki, $strsql, 'tasks', 'tasks', 'task', array('stat'=>'ok', 'tasks'=>array()));
}
?>
