<?php
//
// Description
// -----------
// Search faqs by subject and date
//
// Arguments
// ---------
// user_id: 		The user making the request
// search_str:		The search string provided by the user.
// 
// Returns
// -------
//
function ciniki_atdo_faqsSearchFull($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'start_needle'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No search specified'), 
        'limit'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No limit specified'), 
		'completed'=>array('required'=>'no', 'errmsg'=>'No completed specified'),
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	if( (!isset($args['completed']) || $args['completed'] != 'yes') 
		&& (!isset($args['start_needle']) || $args['start_needle'] == '') ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'585', 'msg'=>'No search specified'));
	}
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/atdo/private/checkAccess.php');
    $rc = ciniki_atdo_checkAccess($ciniki, $args['business_id'], 'ciniki.atdo.faqsSearchFull', 0); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	//
	// Get the number of faqs in each status for the business, 
	// if no rows found, then return empty array
	//
	$strsql = "SELECT ciniki_atdos.id, category, subject "
//		. "IF((u1.perms&0x04)=4, 'yes', 'no') AS assigned, "
//		. "IFNULL(u3.display_name, '') AS assigned_users "
		. "FROM ciniki_atdos "
//		. "LEFT JOIN ciniki_atdo_users AS u1 ON (ciniki_atdos.id = u1.atdo_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
//		. "LEFT JOIN ciniki_atdo_users AS u2 ON (ciniki_atdos.id = u2.atdo_id && (u2.perms&0x04) = 4) "
//		. "LEFT JOIN ciniki_users AS u3 ON (u2.user_id = u3.id) "
		. "LEFT JOIN ciniki_atdo_followups ON (ciniki_atdos.id = ciniki_atdo_followups.atdo_id ) "
		. "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_atdos.type = 4 "		// FAQs
		. "";
		if( isset($args['completed']) && $args['completed'] == 'yes' ) {
			$strsql .= "AND ciniki_atdos.status >= 60 ";
		} else {
			$strsql .= "AND (subject LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
				. "OR subject LIKE '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
				. "OR ciniki_atdo_followups.content LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
				. "OR ciniki_atdo_followups.content LIKE ' %" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
				. ") "
			. "";
		}
	// Check for public/private atdos, and if private make sure user created or is assigned
//	$strsql .= "AND ((perm_flags&0x01) = 0 "  // Public to business
//			// created by the user requesting the list
//			. "OR ((perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
//			// Assigned to the user requesting the list
//			. "OR ((perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
//			. ") "
//		. "GROUP BY ciniki_atdos.id, u3.id "
//		. "";	
	$strsql .= "GROUP BY ciniki_atdos.id ";
	if( isset($args['completed']) && $args['completed'] == 'yes' ) {
		$strsql .= "ORDER BY ciniki_atdos.subject "
		. "";
	} else {
		$strsql .= "ORDER BY ciniki_atdos.subject "
		. "";
	}
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} else {
		$strsql .= "LIMIT 25 ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.atdo', array(
		array('container'=>'faqs', 'fname'=>'id', 'name'=>'faq',
			'fields'=>array('id', 'category', 'subject'), 
			),
		));
	// error_log($strsql);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['faqs']) ) {
		return array('stat'=>'ok', 'faqs'=>array());
	}
	return array('stat'=>'ok', 'faqs'=>$rc['faqs']);

}
?>
