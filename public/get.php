<?php
//
// Description
// ===========
// This function will return all the details for a atdo.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <atdo id="1" subject="Task subject" assigned="yes" private="yes" due_date=""/>
//
function ciniki_atdo_get($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'atdo_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No atdo specified'), 
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
    $rc = ciniki_atdo_checkAccess($ciniki, $args['business_id'], 'ciniki.atdo.tasksGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/timezoneOffset.php');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	$strsql = "SELECT ciniki_atdos.id, type, subject, location, content, user_id, "
		. "IF((ciniki_atdos.perm_flags&0x01)=1, 'yes', 'no') AS private, "
		. "status, ciniki_atdos.category, ciniki_atdos.priority, "
		. "DATE_FORMAT(appointment_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS appointment_date, "
		. "DATE_FORMAT(appointment_date, '%Y-%m-%d') AS appointment_date_date, "
		. "DATE_FORMAT(appointment_date, '%H:%i') AS appointment_time, "
		. "DATE_FORMAT(appointment_date, '%l:%i') AS appointment_12hour, "
		. "appointment_duration, "
		. "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS appointment_duration_allday, "
		. "DATE_FORMAT(due_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS due_date, "
		. "DATE_FORMAT(due_date, '%Y-%m-%d') AS due_date_date, "
		. "DATE_FORMAT(due_date, '%H:%i') AS due_time, "
		. "DATE_FORMAT(due_date, '%l:%i') AS due_12hour, "
		. "due_duration, "
		. "IF((ciniki_atdos.due_flags&0x01)=1, 'yes', 'no') AS due_duration_allday, "
		. "appointment_repeat_type, appointment_repeat_interval, "
		. "DATE_FORMAT(appointment_date, '%D') AS appointment_repeat_dayofmonth, "
		. "DAY(appointment_date) AS appointment_repeat_day, "
		. "DATE_FORMAT(appointment_date, '%W') AS appointment_repeat_weekday, "
		. "DATE_FORMAT(appointment_repeat_end, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS appointment_repeat_end, "
		. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
		. "DATE_FORMAT(CONVERT_TZ(last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
		. "FROM ciniki_atdos "
		
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_atdos.id = '" . ciniki_core_dbQuote($ciniki, $args['atdo_id']) . "' "
		. "";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'atdo', 'atdo');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['atdo']) ) {
		return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'565', 'msg'=>'Unable to find item'));
	}
	$atdo = $rc['atdo'];

	//
	// Setup the repeat string description
	//
	$atdo['appointment_repeat'] = '';
	$nth = array('1st', '2nd', '3rd', '4th', '5th');
	if( $atdo['appointment_repeat_type'] == 10 && $atdo['appointment_repeat_interval'] = 1 ) {
		$atdo['appointment_repeat'] = 'every day';
	} elseif( $atdo['appointment_repeat_type'] == 10 && $atdo['appointment_repeat_interval'] > 1 ) {
		$atdo['appointment_repeat'] = 'every ' . $atdo['appointment_repeat_interval'] . " days";

	} elseif( $atdo['appointment_repeat_type'] == 20 && $atdo['appointment_repeat_interval'] = 1 ) {
		$atdo['appointment_repeat'] = 'every week';
	} elseif( $atdo['appointment_repeat_type'] == 20 && $atdo['appointment_repeat_interval'] > 1 ) {
		$atdo['appointment_repeat'] = 'every ' . $atdo['appointment_repeat_interval'] . " weeks";

	} elseif( $atdo['appointment_repeat_type'] == 30 && $atdo['appointment_repeat_interval'] = 1 ) {
		$atdo['appointment_repeat'] = 'every month on the ' . $atdo['appointment_repeat_dayofmonth'];
	} elseif( $atdo['appointment_repeat_type'] == 30 && $atdo['appointment_repeat_interval'] > 1 ) {
		$atdo['appointment_repeat'] = 'every ' . $atdo['appointment_repeat_interval'] . " months on the " . $atdo['appointment_repeat_dayofmonth'];
		
	} elseif( $atdo['appointment_repeat_type'] == 31 && $atdo['appointment_repeat_interval'] = 1 ) {
		$atdo['appointment_repeat'] = 'every month on the ' . $nth[floor($atdo['appointment_repeat_day']/7)] . ' ' . $atdo['appointment_repeat_weekday'];
	} elseif( $atdo['appointment_repeat_type'] == 31 && $atdo['appointment_repeat_interval'] > 1 ) {
		$atdo['appointment_repeat'] = 'every ' . $atdo['appointment_repeat_interval'] . ' months on the '. $nth[floor($atdo['appointment_repeat_day']/7)] . ' ' . $atdo['appointment_repeat_weekday'];

	} elseif( $atdo['appointment_repeat_type'] == 40 && $atdo['appointment_repeat_interval'] = 1 ) {
		$atdo['appointment_repeat'] = 'every year';
	} elseif( $atdo['appointment_repeat_type'] == 40 && $atdo['appointment_repeat_interval'] > 1 ) {
		$atdo['appointment_repeat'] = 'every ' . $atdo['appointment_repeat_interval'] . " years";
	}

	$atdo['followers'] = array();
	$atdo['assigned'] = '';
	$atdo['viewed'] = '';
	$atdo['deleted'] = '';

	$user_ids = array($rc['atdo']['user_id']);

    //  
    // Get the followups to the atdo
    //  
    $strsql = "SELECT id, atdo_id, user_id, "
		. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
        . "CAST(UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(date_added) as DECIMAL(12,0)) as age, "
		. "content "
		. "FROM ciniki_atdo_followups "
        . "WHERE ciniki_atdo_followups.atdo_id = '" . ciniki_core_dbQuote($ciniki, $args['atdo_id']) . "' "
		. "ORDER BY ciniki_atdo_followups.date_added ASC "
        . ""; 
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbRspQueryPlusUserIDs.php');
	$rc = ciniki_core_dbRspQueryPlusUserIDs($ciniki, $strsql, 'atdo', 'followups', 'followup', array('stat'=>'ok', 'followups'=>array(), 'user_ids'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'558', 'msg'=>'Unable to load item information', 'err'=>$rc['err']));
	}
	$atdo['followups'] = $rc['followups'];
	$user_ids = array_merge($user_ids, $rc['user_ids']);

	//
	// Get the list of users attached to the atdo
	//
	$strsql = "SELECT atdo_id, user_id, perms "
		. "FROM ciniki_atdo_users "
		. "WHERE atdo_id = '" . ciniki_core_dbQuote($ciniki, $args['atdo_id']) . "' ";
	$rc = ciniki_core_dbRspQueryPlusUserIDs($ciniki, $strsql, 'atdo', 'users', 'user', array('stat'=>'ok', 'users'=>array(), 'user_ids'=>array()));
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'566', 'msg'=>'Unable to load item information', 'err'=>$rc['err']));
	}
	$atdo_users = $rc['users'];
	$user_ids = array_merge($user_ids, $rc['user_ids']);

	//
	// Get the users which are linked to these accounts
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/userListByID.php');
	$rc = ciniki_users_userListByID($ciniki, 'users', $user_ids, 'display_name');
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'563', 'msg'=>'Unable to load item information', 'err'=>$rc['err']));
	}
	if( !isset($rc['users']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'564', 'msg'=>'Unable to load item information', 'err'=>$rc['err']));
	}
	$users = $rc['users'];

	//
	// Build the list of followers and users assigned to the atdo
	//
	foreach($atdo_users as $unum => $user) {
		$display_name = 'unknown';
		if( isset($users[$user['user']['user_id']]) ) {
			$display_name = $users[$user['user']['user_id']]['display_name'];
		}
		// Followers
		if( ($user['user']['perms'] & 0x01) > 0 ) {
			array_push($atdo['followers'], array('user'=>array('id'=>$user['user']['user_id'], 'display_name'=>$display_name)));
		}
		// User has viewed the atdo
		if( ($user['user']['perms'] & 0x08) > 0 ) {
			if( $atdo['viewed'] != '' ) {
				$atdo['viewed'] .= ',';
			}
			$atdo['viewed'] .= $user['user']['user_id'];
		}
		// User has deleted the atdo
		if( ($user['user']['perms'] & 0x10) > 0 ) {
			if( $atdo['deleted'] != '' ) {
				$atdo['deleted'] .= ',';
			}
			$atdo['deleted'] .= $user['user']['user_id'];
		}
		// Assigned to
		if( ($user['user']['perms'] & 0x04) > 0 ) {
			if( $atdo['assigned'] != '' ) {
				$atdo['assigned'] .= ',';
			}
			$atdo['assigned'] .= $user['user']['user_id'];
		}
	}

	//
	// Fill in the followup information with user info
	//
	foreach($atdo['followups'] as $fnum => $followup) {
		$display_name = 'unknown';
		if( isset($users[$followup['followup']['user_id']]) ) {
			$display_name = $users[$followup['followup']['user_id']]['display_name'];
		}
		$atdo['followups'][$fnum]['followup']['user_display_name'] = $display_name;
	}

	//
	// Fill in the atdo information with user info
	//
	if( isset($atdo['user_id']) && isset($users[$atdo['user_id']]) ) {
		$atdo['user_display_name'] = $users[$atdo['user_id']]['display_name'];
	}

	//
	// Update the viewed flag to specify the user has requested this atdo.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
	$rc = ciniki_core_threadAddUserPerms($ciniki, 'atdo', 'ciniki_atdo_users', 'atdo', $args['atdo_id'], $ciniki['session']['user']['id'], 0x08);
	if( $rc['stat'] != 'ok' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'587', 'msg'=>'Unable to update task information', 'err'=>$rc['err']));
	}

	return array('stat'=>'ok', 'atdo'=>$atdo);
}
?>
