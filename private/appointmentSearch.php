<?php
//
// Description
// -----------
// This function will search atdo and return them in the calendar/appointment format.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to get the details for.
// date:				The date to get the schedule for.
//
// Returns
// -------
//	<appointments>
//		<appointment calendar="Tasks" customer_name="" invoice_number="" wine_name="" />
//	</appointments>
//
function ciniki_atdo__appointmentSearch($ciniki, $business_id, $args) {

	if( !isset($args['start_needle']) || $args['start_needle'] == '' ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'553', 'msg'=>'No search specified'));
	}

	//
	// Get the module settings
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDetailsQuery.php');
	$rc =  ciniki_core_dbDetailsQuery($ciniki, 'ciniki_atdo_settings', 'business_id', $args['business_id'], 'atdo', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//
	// Load datetime formats
	//
    require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	$strsql = "SELECT ciniki_atdos.id, type, subject, location, priority,  "
		. "UNIX_TIMESTAMP(appointment_date) AS start_ts, "
		. "DATE_FORMAT(appointment_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS start_date, "
		. "DATE_FORMAT(appointment_date, '%Y-%m-%d') AS date, "
		. "DATE_FORMAT(appointment_date, '%H:%i') AS time, "
		. "DATE_FORMAT(appointment_date, '%l:%i') AS 12hour, "
		. "appointment_duration as duration, '#ffdddd' AS colour, 'ciniki.atdo' AS 'module' "
		. "FROM ciniki_atdos "
		. "LEFT JOIN ciniki_atdo_users AS u1 ON (ciniki_atdos.id = u1.atdo_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
//		. "LEFT JOIN ciniki_atdo_followups ON (ciniki_atdos.id = ciniki_atdo_followups.atdo_id "
//			. "AND (ciniki_atdo_followups.content LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
//			. "OR ciniki_atdo_followups.content LIKE ' %" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' ))"
		. "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		// Search items with an appointment date or due date
		. "AND (ciniki_atdos.appointment_date != 0 OR ciniki_atdos.due_date != 0) "
		. "";
// Search for all tasks, even when closed
	if( isset($args['full']) && $args['full'] == 'yes' ) {
		$strsql .= "AND status <= 60 ";
	} else {
		$strsql .= "AND status = 1 ";
	}
	$strsql .= "AND (subject LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. " OR DATE_FORMAT(appointment_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') LIKE '%" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. ") "
		. "";
	// Check for public/private atdos, and if private make sure user created or is assigned
	$strsql .= "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to business
			// created by the user requesting the list
			. "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
			// Assigned to the user requesting the list
			. "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND u1.perms = 0x04) "
			. ") "
		. "";
	if( isset($args['date']) && $args['date'] != '' ) {
		$strsql .= "ORDER BY ABS(DATEDIFF(DATE(ciniki_atdos.appointment_date), DATE('" . ciniki_core_dbQuote($ciniki, $args['date']) . "'))), subject ";
	} else {
		$strsql .= "ORDER BY ABS(DATEDIFF(DATE(ciniki_atdos.appointment_date), DATE(NOW()))), subject ";
	}
	if( isset($args['limit']) && is_numeric($args['limit']) && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";	// is_numeric verified
	}
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQueryTree.php');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'wineproduction', array(
		array('container'=>'appointments', 'fname'=>'id', 'name'=>'appointment', 
			'fields'=>array('id', 'module', 'start_ts', 'start_date', 'date', 'time', '12hour', 'duration', 'colour', 'type', 
				'subject', 'priority')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Apply colours if they have been configured
	//
	if( isset($rc['appointments']) && isset($settings['tasks.status.60']) ) {
		foreach($rc['appointments'] as $appointment_num => $appointment) {
			if( $appointment['appointment']['type'] == 1 ) {
				$rc['appointments'][$appointment_num]['appointment']['colour'] = $settings['appointments.status.1'];
			}
			elseif( $appointment['appointment']['type'] == 2 ) {
				if( $appointment['appointment']['status'] == 60 ) {
					$rc['appointments'][$appointment_num]['appointment']['colour'] = $settings['tasks.status.60'];
				} else {
					$rc['appointments'][$appointment_num]['appointment']['colour'] = $settings['tasks.priority.' . $appointment['appointment']['priority']];
				}
				
			}

		}
	}
	return $rc;
}
?>
