<?php
//
// Description
// -----------
// This function will return any appointments or tasks that should be displayed in the calendar.
// This function is used by ciniki.calendars.appointments.
//
// Arguments
// ---------
// ciniki:
// business_id:			The ID of the business to get the appointments for.
// args:				The arguments passed to the calling public method ciniki.calendars.appointments.
//
// Returns
// -------
//
function ciniki_atdo_hooks_appointments($ciniki, $business_id, $args) {

	//
	// FIXME: Add timezone information
	//
	date_default_timezone_set('America/Toronto');
	if( isset($args['date']) && ($args['date'] == '' || $args['date'] == 'today') ) {
		$args['date'] = strftime("%Y-%m-%d");
	}

	//
	// Get the module settings
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
	$rc =  ciniki_core_dbDetailsQuery($ciniki, 'ciniki_atdo_settings', 'business_id', $args['business_id'], 'ciniki.atdo', 'settings', '');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$settings = $rc['settings'];

	//
	// Load timezone info
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
	$rc = ciniki_businesses_intlSettings($ciniki, $business_id);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$intl_timezone = $rc['settings']['intl-default-timezone'];
//	$intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//	$intl_currency = $rc['settings']['intl-default-currency'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
	$time_format = ciniki_users_timeFormat($ciniki, 'php');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki, 'php');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
	
	//
	// Load date formats
	//
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
//	$datetime_format = ciniki_users_datetimeFormat($ciniki);
//	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//	$date_format = ciniki_users_dateFormat($ciniki);

	$strsql = "SELECT ciniki_atdos.id, type, ciniki_atdos.status, subject, location, priority, "
//		. "UNIX_TIMESTAMP(appointment_date) AS start_ts, "
//		. "DATE_FORMAT(appointment_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS start_date, "
//		. "DATE_FORMAT(appointment_date, '%Y-%m-%d') AS date, "
//		. "DATE_FORMAT(appointment_date, '%H:%i') AS time, "
//		. "DATE_FORMAT(appointment_date, '%l:%i') AS 12hour, "
		. "appointment_date AS start_ts, "
		. "appointment_date AS date, "
		. "appointment_date AS start_date, "
		. "appointment_date AS time, "
		. "appointment_date AS 12hour, "
		. "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
		. "appointment_duration as duration, appointment_repeat_type as repeat_type, appointment_repeat_interval as repeat_interval, "
		. "DATE_FORMAT(appointment_repeat_end, '" . ciniki_core_dbQuote($ciniki, '%Y-%m-%d') . "') AS repeat_end, "
		// FIXME: grab the secondary_text from followups, keep this as a placeholder for now
		. "'#ffcccc' AS colour, 'ciniki.atdo' AS module, content AS secondary_text "
		. "FROM ciniki_atdos "
		. "LEFT JOIN ciniki_atdo_users AS u1 ON (ciniki_atdos.id = u1.atdo_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
		. "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (type = 1 OR type = 2) "
		. "";
	if( isset($args['atdo_id']) && $args['atdo_id'] > 0 ) {
		$strsql .= "AND ciniki_atdos.id = '" . ciniki_core_dbQuote($ciniki, $args['appointment_id']) . "' ";
	} elseif( isset($args['date']) && $args['date'] != '' ) {
		$quoted_date = ciniki_core_dbQuote($ciniki, $args['date']);
		$strsql = "SELECT ciniki_atdos.id, type, ciniki_atdos.status, subject, location, priority, "
//			. "FROM_UNIXTIME(UNIX_TIMESTAMP(appointment_date)+(UNIX_TIMESTAMP(DATE('$quoted_date'))-UNIX_TIMESTAMP(DATE(appointment_date)))) AS start_ts, "
			. "FROM_UNIXTIME(UNIX_TIMESTAMP('$quoted_date') + (UNIX_TIMESTAMP(appointment_date)-UNIX_TIMESTAMP(DATE(appointment_date)))) AS date, "
			. "appointment_date AS start_date, "
			. "appointment_date AS time, "
			. "appointment_date AS 12hour, "
//			. "(UNIX_TIMESTAMP(appointment_date)+(UNIX_TIMESTAMP(DATE('$quoted_date'))-UNIX_TIMESTAMP(DATE(appointment_date)))) AS date, "
//			. "DATE_FORMAT(FROM_UNIXTIME(UNIX_TIMESTAMP(appointment_date)+(UNIX_TIMESTAMP(DATE('$quoted_date'))-UNIX_TIMESTAMP(DATE(appointment_date)))), '%Y-%m-%d') AS date, "
//			. "DATE_FORMAT(UNIX_TIMESTAMP(appointment_date)+(UNIX_TIMESTAMP(DATE('$quoted_date'))-UNIX_TIMESTAMP(DATE(appointment_date))), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS start_date, "
//			. "DATE_FORMAT(appointment_date, '%H:%i') AS time, "
//			. "DATE_FORMAT(appointment_date, '%l:%i') AS 12hour, "
			. "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
			. "appointment_duration as duration, appointment_repeat_type as repeat_type, appointment_repeat_interval as repeat_interval, "
			. "DATE_FORMAT(appointment_repeat_end, '" . ciniki_core_dbQuote($ciniki, '%Y-%m-%d') . "') AS repeat_end, "
			. "'#ffcccc' AS colour, 'ciniki.atdo' AS module, content AS secondary_text "
			. "FROM ciniki_atdos "
			. "LEFT JOIN ciniki_atdo_users AS u1 ON (ciniki_atdos.id = u1.atdo_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
			. "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND status = 1 "
			. "AND (type = 1 OR type = 2) "
		. "";


		$strsql .= "AND (DATE(ciniki_atdos.appointment_date) = '$quoted_date' "
			. "OR ("
				// Check for repeatable atdos
				. "DATEDIFF(DATE(ciniki_atdos.appointment_date), DATE('$quoted_date')) < 0 AND (DATE(appointment_repeat_end) = '0000-00-00' OR DATEDIFF(DATE('$quoted_date'), DATE(appointment_repeat_end)) <= 0 ) "
				. "AND ("
					// Daily
					. "(appointment_repeat_type = 10 AND MOD(TO_DAYS(DATE(ciniki_atdos.appointment_date))-TO_DAYS(DATE('$quoted_date')), appointment_repeat_interval) = 0 "
						. ") "
					// Weekly
					. "OR (appointment_repeat_type = 20 AND DAYOFWEEK(ciniki_atdos.appointment_date) = DAYOFWEEK('$quoted_date') "
						. "AND MOD(FLOOR(UNIX_TIMESTAMP(DATE(ciniki_atdos.appointment_date))/604800)-FLOOR(UNIX_TIMESTAMP(DATE('$quoted_date'))/604800), appointment_repeat_interval) = 0 "
						. ") "
					// Monthy by day of month
					. "OR (appointment_repeat_type = 30 AND DAY(ciniki_atdos.appointment_date) = DAY('$quoted_date') "
						. "AND MOD(((YEAR('$quoted_date') - YEAR(ciniki_atdos.appointment_date))*12)+(MONTH('$quoted_date')-MONTH(ciniki_atdos.appointment_date)), appointment_repeat_interval) = 0 "
						. ") "
					// Monthy by day of week
					. "OR (appointment_repeat_type = 31 AND DAYOFWEEK(ciniki_atdos.appointment_date) = DAYOFWEEK('$quoted_date') "
						// Check for Xth (day of week) of the month (first monday of the month)
						. "AND FLOOR((DAY(ciniki_atdos.appointment_date)-1)/7) = FLOOR((DAY('$quoted_date')-1)/7) "
						. "AND MOD(((YEAR('$quoted_date') - YEAR(ciniki_atdos.appointment_date))*12)+(MONTH('$quoted_date')-MONTH(ciniki_atdos.appointment_date)), appointment_repeat_interval) = 0 "
						. ") "
					// Yearly
					. "OR (appointment_repeat_type = 40 AND DAY(ciniki_atdos.appointment_date) = DAY('$quoted_date') "
						. "AND MONTH(ciniki_atdos.appointment_date) = MONTH('$quoted_date') "
						. "AND MOD(YEAR('$quoted_date') - YEAR(ciniki_atdos.appointment_date), appointment_repeat_interval) = 0 "
						. ") "
				. ") "
			. ")) ";
	} elseif( isset($args['start_date']) && $args['start_date'] != '' 
		&& isset($args['end_date']) && $args['end_date'] != '' 
		) {
		$quoted_date = ciniki_core_dbQuote($ciniki, $args['start_date']);
		$strsql = "SELECT ciniki_atdos.id, type, ciniki_atdos.status, subject, location, priority, "
//			. "FROM_UNIXTIME(UNIX_TIMESTAMP(appointment_date)+(UNIX_TIMESTAMP(DATE('$quoted_date'))-UNIX_TIMESTAMP(DATE(appointment_date)))) AS start_ts, "
			. "appointment_date AS date, "
			. "appointment_date AS start_date, "
			. "appointment_date AS time, "
			. "appointment_date AS 12hour, "
			. "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
			. "appointment_duration as duration, appointment_repeat_type as repeat_type, appointment_repeat_interval as repeat_interval, "
			. "DATE_FORMAT(appointment_repeat_end, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS repeat_end, "
			. "'#ffcccc' AS colour, 'ciniki.atdo' AS module, content AS secondary_text "
			. "FROM ciniki_atdos "
			. "LEFT JOIN ciniki_atdo_users AS u1 ON (ciniki_atdos.id = u1.atdo_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
			. "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
			. "AND status = 1 "
			. "AND (type = 1 OR type = 2) "
		. "";

		$strsql .= "AND (DATE(ciniki_atdos.appointment_date) = '$quoted_date' "
			. "OR ("
				// Check for repeatable atdos
				. "DATEDIFF(DATE(ciniki_atdos.appointment_date), DATE('$quoted_date')) < 0 AND (DATE(appointment_repeat_end) = '0000-00-00' OR DATEDIFF(DATE('$quoted_date'), DATE(appointment_repeat_end)) <= 0 ) "
				. "AND ("
					// Daily
					. "(appointment_repeat_type = 10 AND MOD(TO_DAYS(DATE(ciniki_atdos.appointment_date))-TO_DAYS(DATE('$quoted_date')), appointment_repeat_interval) = 0 "
						. ") "
					// Weekly
					. "OR (appointment_repeat_type = 20 AND DAYOFWEEK(ciniki_atdos.appointment_date) = DAYOFWEEK('$quoted_date') "
						. "AND MOD(FLOOR(UNIX_TIMESTAMP(DATE(ciniki_atdos.appointment_date))/604800)-FLOOR(UNIX_TIMESTAMP(DATE('$quoted_date'))/604800), appointment_repeat_interval) = 0 "
						. ") "
					// Monthy by day of month
					. "OR (appointment_repeat_type = 30 AND DAY(ciniki_atdos.appointment_date) = DAY('$quoted_date') "
						. "AND MOD(((YEAR('$quoted_date') - YEAR(ciniki_atdos.appointment_date))*12)+(MONTH('$quoted_date')-MONTH(ciniki_atdos.appointment_date)), appointment_repeat_interval) = 0 "
						. ") "
					// Monthy by day of week
					. "OR (appointment_repeat_type = 31 AND DAYOFWEEK(ciniki_atdos.appointment_date) = DAYOFWEEK('$quoted_date') "
						// Check for Xth (day of week) of the month (first monday of the month)
						. "AND FLOOR((DAY(ciniki_atdos.appointment_date)-1)/7) = FLOOR((DAY('$quoted_date')-1)/7) "
						. "AND MOD(((YEAR('$quoted_date') - YEAR(ciniki_atdos.appointment_date))*12)+(MONTH('$quoted_date')-MONTH(ciniki_atdos.appointment_date)), appointment_repeat_interval) = 0 "
						. ") "
					// Yearly
					. "OR (appointment_repeat_type = 40 AND DAY(ciniki_atdos.appointment_date) = DAY('$quoted_date') "
						. "AND MONTH(ciniki_atdos.appointment_date) = MONTH('$quoted_date') "
						. "AND MOD(YEAR('$quoted_date') - YEAR(ciniki_atdos.appointment_date), appointment_repeat_interval) = 0 "
						. ") "
				. ") "
			. ")) ";
	} else {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'554', 'msg'=>'No constraints provided'));
	}
	// Check for public/private atdos, and if private make sure user created or is assigned
	$strsql .= "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to business
			// created by the user requesting the list
			. "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
			// Assigned to the user requesting the list
			. "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
			. ") "
		. "";
	$strsql .= ""
		. "ORDER BY time ASC, subject "
		. "";
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.atdo', array(
		array('container'=>'appointments', 'fname'=>'id', 'name'=>'appointment', 
			'fields'=>array('id', 'module', 'start_date', 'date', 'time', '12hour', 'allday', 'duration', 
				'repeat_type', 'repeat_interval', 'repeat_end', 'colour', 'type', 'status', 
				'subject', 'location', 'secondary_text', 'priority'),
			'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
				'date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d'),
				'time'=>array('timezone'=>$intl_timezone, 'format'=>'H:i'),
				'12hour'=>array('timezone'=>$intl_timezone, 'format'=>'g:i'),
			)),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Apply colours if they have been configured
	//
	if( isset($rc['appointments']) && isset($settings['tasks.status.60']) ) {
		foreach($rc['appointments'] as $aid => $appointment) {
			$dt = new DateTime($appointment['appointment']['date'] . ' ' . $appointment['appointment']['time'], new DateTimeZone($intl_timezone));
			$rc['appointments'][$aid]['appointment']['start_ts'] = $dt->format('U');
			if( $appointment['appointment']['type'] == 1 ) {
				$rc['appointments'][$aid]['appointment']['colour'] = $settings['appointments.status.1'];
			}
			elseif( $appointment['appointment']['type'] == 2 ) {
				if( isset($appointment['appointment']['status']) && $appointment['appointment']['status'] == 60 ) {
					$rc['appointments'][$aid]['appointment']['colour'] = $settings['tasks.status.60'];
				} else {
					$rc['appointments'][$aid]['appointment']['colour'] = $settings['tasks.priority.' . $appointment['appointment']['priority']];
				}
			}
		}
	}

	return $rc;
}
?>
