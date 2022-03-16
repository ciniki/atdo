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
// tnid:         The ID of the tenant to get the appointments for.
// args:                The arguments passed to the calling public method ciniki.calendars.appointments.
//
// Returns
// -------
//
function ciniki_atdo_hooks_appointments($ciniki, $tnid, $args) {

    //
    // FIXME: Add timezone information
    //
//  date_default_timezone_set('America/Toronto');
    if( isset($args['date']) && ($args['date'] == '' || $args['date'] == 'today') ) {
        $args['date'] = strftime("%Y-%m-%d");
    }

    //
    // Get the module settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    $rc =  ciniki_core_dbDetailsQuery($ciniki, 'ciniki_atdo_settings', 'tnid', $args['tnid'], 'ciniki.atdo', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $settings = $rc['settings'];

    //
    // Load timezone info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
//  $intl_currency_fmt = numfmt_create($rc['settings']['intl-default-locale'], NumberFormatter::CURRENCY);
//  $intl_currency = $rc['settings']['intl-default-currency'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timeFormat');
    $time_format = ciniki_users_timeFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'mysql');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    
    //
    // Load date formats
    //
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
//  $datetime_format = ciniki_users_datetimeFormat($ciniki);
//  ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
//  $date_format = ciniki_users_dateFormat($ciniki);

    //
    // The appointments
    //
    $appointments = array();

    //
    // The request is for a range of dates
    //
    if( isset($args['start_date']) && $args['start_date'] != '' 
        && isset($args['end_date']) && $args['end_date'] != ''
        ) {
        $start_date_quoted = ciniki_core_dbQuote($ciniki, $args['start_date']);
        $end_date_quoted = ciniki_core_dbQuote($ciniki, $args['end_date']);

        //
        // Get all repeatable appointments
        //
        $strsql = "SELECT ciniki_atdos.id, type, ciniki_atdos.status, subject, location, priority, "
            . "appointment_date AS start_ts, "
            . "appointment_date AS date, "
            . "appointment_date AS start_date, "
            . "appointment_date AS time, "
            . "appointment_date AS 12hour, "
            . "appointment_date AS weekday, "
            . "appointment_date AS year, "
            . "appointment_date AS month, "
            . "appointment_date AS day, "
            . "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
            . "appointment_duration AS duration, "
            . "appointment_repeat_type AS repeat_type, "
            . "appointment_repeat_interval AS repeat_interval, "
            . "DATE_FORMAT(appointment_repeat_end, '" . ciniki_core_dbQuote($ciniki, $date_format) . "') AS repeat_end, "
            . "content AS secondary_text "
            . "FROM ciniki_atdos "
            . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                . "ciniki_atdos.id = u1.atdo_id "
                . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                . ") "
            . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_atdos.appointment_repeat_type > 0 "
            . "AND ciniki_atdos.status = 1 "                                // Active status
            . "AND (ciniki_atdos.type = 1 OR ciniki_atdos.type = 2) "       // Appointment or task
            . "AND ciniki_atdos.appointment_date <= '" . ciniki_core_dbQuote($ciniki, $end_date_quoted) . "' "      // Start of repeat is before end date
            . "AND (ciniki_atdos.appointment_repeat_end = '0000-00-00' OR DATEDIFF('$start_date_quoted', ciniki_atdos.appointment_repeat_end) <= 0 ) " // end of repeat is after start date
            // Check for public/private atdos, and if private make sure user created or is assigned
            . "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to tenant
                // created by the user requesting the list
                . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
                // Assigned to the user requesting the list
                . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
                . ") "
            . "ORDER BY appointment_date "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.atdo', array(
            array('container'=>'appointments', 'fname'=>'id', 'name'=>'appointment', 
                'fields'=>array('id', 'start_ts', 'start_date', 'date', 'time', '12hour', 'allday', 'duration', 
                    'repeat_type', 'repeat_interval', 'repeat_end', 'weekday', 'year', 'month', 'day', 'type', 'status', 
                    'subject', 'location', 'secondary_text', 'priority'),
                'utctotz'=>array(
                    'start_ts'=>array('timezone'=>$intl_timezone, 'format'=>'U'),
                    'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
//                  'end_date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d'),
                    'date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d'),
                    'time'=>array('timezone'=>$intl_timezone, 'format'=>'H:i'),
                    '12hour'=>array('timezone'=>$intl_timezone, 'format'=>'g:i'),
                    'weekday'=>array('timezone'=>$intl_timezone, 'format'=>'w'),
                    'year'=>array('timezone'=>$intl_timezone, 'format'=>'Y'),
                    'month'=>array('timezone'=>$intl_timezone, 'format'=>'n'),
                    'day'=>array('timezone'=>$intl_timezone, 'format'=>'j'),
                )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }

        if( isset($rc['appointments']) ) {
            $repeats = $rc['appointments'];
            foreach($repeats as $aid => $appointment) {
                $dt = new DateTime($appointment['date'] . ' 00:00:00', new DateTimeZone($intl_timezone));
                $repeats[$aid]['start_ts'] = $dt->format('U');
                if( $appointment['repeat_end'] != '' && $appointment['repeat_end'] != '0000-00-00' ) {
                    $dt = new DateTime($appointment['repeat_end'] . ' 00:00:00', new DateTimeZone($intl_timezone));
                    $dt->add(new DateInterval('P1D'));
                    $repeats[$aid]['end_ts'] = $dt->format('U');
                } else {
                    $repeats[$aid]['end_ts'] = '';
                }
            }

            //
            // Setup the UTC start and end dates, then convert to tenant timezone. 
            // cdt is used as the current date
            //
            $sdt = new DateTime($args['start_date'], new DateTimeZone('UTC')); 
            $sdt->setTimezone(new DateTimeZone($intl_timezone));
            $cdt = clone $sdt;
            $edt = new DateTime($args['end_date'], new DateTimeZone('UTC'));
            $edt->setTimezone(new DateTimeZone($intl_timezone));

            //
            // Go through all the dates requested and check for any repeating appointments
            //
            $increment = new DateInterval('P1D');
            while($cdt <= $edt) {
                $cts = $cdt->format('U');
                $cts_days = floor($cts/86400);
                foreach($repeats as $aid => $appointment) {
                    //
                    // Check if repeat is not active or is finished
                    //
                    if( $cts < $appointment['start_ts'] || ($appointment['end_ts'] != '' && $cts >= $appointment['end_ts']) ) {
                        continue;
                    }

                    //
                    // Check to see if the current date has any repeat appointments
                    //
                    if(
                        //
                        // Daily appointments
                        //
                            (
                            $appointment['repeat_type'] == '10' 
                            // occurs on current date
                            && ($cts_days-floor($appointment['start_ts']/86400))%$appointment['repeat_interval'] == 0
                            )
                        //
                        // Weekly appointments
                        //
                        || (
                            $appointment['repeat_type'] == '20'             // Weekly
                            && $cdt->format('w') == $appointment['weekday']     // Same day of the week
                            // occurs on current date
                            && (floor($cts/604800)-floor($appointment['start_ts']/604800))%$appointment['repeat_interval'] == 0
                            )
                        //
                        // Monthly by day of month
                        //
                        || (
                            $appointment['repeat_type'] == '30'                 // Monthly
                            && $cdt->format('j') == $appointment['day']     // Same day of the month
                            // Number of months between now and start_date
                            && ((($cdt->format('Y')-$appointment['year'])*12)+($cdt->format('n')+$appointment['month']))%$appointment['repeat_interval'] == 0
                            )
                        //
                        // Monthly by day of week
                        //
                        || (
                            $appointment['repeat_type'] == '31'                 // Monthly
                            && $cdt->format('w') == $appointment['weekday']         // Same day of the week
                            // Check for Xth (day of week) of the month (first monday of the month)
                            && floor(($cdt->format('j')-1)/7) == floor(($appointment['day']-1)/7)
                            // Check if right repeat (Number of months between now and start_date)
                            && ((($cdt->format('Y')-$appointment['year'])*12)+($cdt->format('n')+$appointment['month']))%$appointment['repeat_interval'] == 0
                            ) 
                        //
                        // Yearly
                        //
                        || (
                            $appointment['repeat_type'] == '40'
                            && $cdt->format('j') == $appointment['day']
                            && $cdt->format('n') == $appointment['month']
                            && ($cdt->format('Y') - $appointment['year'])%$appointment['repeat_interval'] == 0
                            )
                        ) {
                        $appointment['date'] = $cdt->format('Y-m-d');
                        unset($appointment['weekday']);
                        unset($appointment['year']);
                        unset($appointment['month']);
                        unset($appointment['day']);
                        $appointments[] = $appointment;
                    } 
                }

                $cdt->add($increment);
            }
        }


        //
        // Get all single date appointments
        //
        $strsql = "SELECT ciniki_atdos.id, "
            . "ciniki_atdos.type, "
            . "ciniki_atdos.status, "
            . "ciniki_atdos.subject, "
            . "ciniki_atdos.location, "
            . "ciniki_atdos.priority, "
            . "ciniki_atdos.appointment_date AS start_date, "
            . "ciniki_atdos.appointment_date AS date, "
            . "ciniki_atdos.appointment_date AS time, "
            . "ciniki_atdos.appointment_date AS 12hour, "
            . "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
            . "ciniki_atdos.appointment_duration AS duration, "
            . "content AS secondary_text "
            . "FROM ciniki_atdos "
            . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                . "ciniki_atdos.id = u1.atdo_id "
                . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                . ") "
            . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND ciniki_atdos.appointment_repeat_type = 0 "
            . "AND ciniki_atdos.appointment_date >= '" . ciniki_core_dbQuote($ciniki, $start_date_quoted) . "' "
            . "AND ciniki_atdos.appointment_date <= '" . ciniki_core_dbQuote($ciniki, $end_date_quoted) . "' "
            . "AND ciniki_atdos.status = 1 "                                // Active status
            . "AND (ciniki_atdos.type = 1 OR ciniki_atdos.type = 2) "       // Appointment or task
            // Check for public/private atdos, and if private make sure user created or is assigned
            . "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to tenant
                // created by the user requesting the list
                . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
                // Assigned to the user requesting the list
                . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
                . ") "
            . "ORDER BY appointment_date, time ASC, subject "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.atdo', array(
            array('container'=>'appointments', 'fname'=>'id', 'name'=>'appointment', 
                'fields'=>array('id', 'start_date', 'date', 'time', '12hour', 'allday', 'duration', 
                    'type', 'status', 'subject', 'location', 'secondary_text', 'priority'),
                'utctotz'=>array('start_date'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                    'date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d'),
                    'time'=>array('timezone'=>$intl_timezone, 'format'=>'H:i'),
                    '12hour'=>array('timezone'=>$intl_timezone, 'format'=>'g:i'),
                )),
            ));
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( isset($rc['appointments']) ) {
            $appointments = array_merge($appointments, $rc['appointments']);
        }
    }

    //
    // The request is for a single date, or atdo_id
    //
    else {
        $strsql = "SELECT ciniki_atdos.id, "
            . "ciniki_atdos.type, "
            . "ciniki_atdos.status, "
            . "ciniki_atdos.subject, "
            . "ciniki_atdos.location, "
            . "ciniki_atdos.priority, "
    //      . "UNIX_TIMESTAMP(appointment_date) AS start_ts, "
    //      . "DATE_FORMAT(appointment_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS start_date, "
    //      . "DATE_FORMAT(appointment_date, '%Y-%m-%d') AS date, "
    //      . "DATE_FORMAT(appointment_date, '%H:%i') AS time, "
    //      . "DATE_FORMAT(appointment_date, '%l:%i') AS 12hour, "
    //      . "appointment_date AS start_ts, "
            . "ciniki_atdos.appointment_date AS start_date, "
            . "ciniki_atdos.appointment_date AS date, "
            . "ciniki_atdos.appointment_date AS time, "
            . "ciniki_atdos.appointment_date AS 12hour, "
            . "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
            . "ciniki_atdos.appointment_duration AS duration, "
            . "appointment_repeat_type AS repeat_type, "
            . "appointment_repeat_interval AS repeat_interval, "
            . "DATE_FORMAT(appointment_repeat_end, '" . ciniki_core_dbQuote($ciniki, '%Y-%m-%d') . "') AS repeat_end, "
            // FIXME: grab the secondary_text from followups, keep this as a placeholder for now
            . "content AS secondary_text "
            . "FROM ciniki_atdos "
            . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                . "ciniki_atdos.id = u1.atdo_id "
                . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                . ") "
            . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
            . "AND (ciniki_atdos.type = 1 OR ciniki_atdos.type = 2) "
            . "";
        if( isset($args['atdo_id']) && $args['atdo_id'] > 0 ) {
            $strsql .= "AND ciniki_atdos.id = '" . ciniki_core_dbQuote($ciniki, $args['appointment_id']) . "' ";
        } elseif( isset($args['date']) && $args['date'] != '' ) {
            $quoted_date = ciniki_core_dbQuote($ciniki, $args['date']);
            $strsql = "SELECT ciniki_atdos.id, type, ciniki_atdos.status, subject, location, priority, "
    //          . "FROM_UNIXTIME(UNIX_TIMESTAMP(appointment_date)+(UNIX_TIMESTAMP(DATE('$quoted_date'))-UNIX_TIMESTAMP(DATE(appointment_date)))) AS start_ts, "
                . "appointment_date AS start_date, "
                . "FROM_UNIXTIME(UNIX_TIMESTAMP('$quoted_date') + (UNIX_TIMESTAMP(appointment_date)-UNIX_TIMESTAMP(DATE(appointment_date)))) AS date, "
                . "appointment_date AS time, "
                . "appointment_date AS 12hour, "
    //          . "(UNIX_TIMESTAMP(appointment_date)+(UNIX_TIMESTAMP(DATE('$quoted_date'))-UNIX_TIMESTAMP(DATE(appointment_date)))) AS date, "
    //          . "DATE_FORMAT(FROM_UNIXTIME(UNIX_TIMESTAMP(appointment_date)+(UNIX_TIMESTAMP(DATE('$quoted_date'))-UNIX_TIMESTAMP(DATE(appointment_date)))), '%Y-%m-%d') AS date, "
    //          . "DATE_FORMAT(UNIX_TIMESTAMP(appointment_date)+(UNIX_TIMESTAMP(DATE('$quoted_date'))-UNIX_TIMESTAMP(DATE(appointment_date))), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS start_date, "
    //          . "DATE_FORMAT(appointment_date, '%H:%i') AS time, "
    //          . "DATE_FORMAT(appointment_date, '%l:%i') AS 12hour, "
                . "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
                . "appointment_duration as duration, appointment_repeat_type as repeat_type, appointment_repeat_interval as repeat_interval, "
                . "DATE_FORMAT(appointment_repeat_end, '" . ciniki_core_dbQuote($ciniki, '%Y-%m-%d') . "') AS repeat_end, "
                . "content AS secondary_text "
                . "FROM ciniki_atdos "
                . "LEFT JOIN ciniki_atdo_users AS u1 ON ("
                    . "ciniki_atdos.id = u1.atdo_id "
                    . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
                    . ") "
                . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
                . "AND ciniki_atdos.status = 1 "
                . "AND (ciniki_atdos.type = 1 OR ciniki_atdos.type = 2) "
                . "";

            $strsql .= "AND (DATE(ciniki_atdos.appointment_date) = '$quoted_date' "
                . "OR ("
                    // Check for repeatable atdos
                    . "DATEDIFF(DATE(ciniki_atdos.appointment_date), DATE('$quoted_date')) < 0 "
                        . "AND (DATE(appointment_repeat_end) = '0000-00-00' OR DATEDIFF(DATE('$quoted_date'), DATE(appointment_repeat_end)) <= 0 ) "
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
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.2', 'msg'=>'No constraints provided'));
        }
        // Check for public/private atdos, and if private make sure user created or is assigned
        $strsql .= "AND ((ciniki_atdos.perm_flags&0x01) = 0 "  // Public to tenant
                // created by the user requesting the list
                . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
                // Assigned to the user requesting the list
                . "OR ((ciniki_atdos.perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
                . ") "
            . "";
        $strsql .= ""
            . "ORDER BY time ASC, subject "
            . "";
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.atdo', array(
            array('container'=>'appointments', 'fname'=>'id', 'name'=>'appointment', 
                'fields'=>array('id', 'start_date', 'date', 'time', '12hour', 'allday', 'duration', 
                    'repeat_type', 'repeat_interval', 'repeat_end', 'type', 'status', 
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
        if( isset($rc['appointments']) ) {
            $appointments = array_merge($appointments, $rc['appointments']);
        }
    }

    //
    // Apply colours if they have been configured
    //
    foreach($appointments as $aid => $appointment) {    
        // Set the default colour and module for each appointment
        $appointments[$aid]['colour'] = '#ffcccc';
        $appointments[$aid]['module'] = 'ciniki.atdo';
        // Set the start_ts for each appointment, adjusted for the tenant timezone
        $dt = new DateTime($appointment['date'] . ' ' . $appointment['time'], new DateTimeZone($intl_timezone));
        $appointments[$aid]['start_ts'] = $dt->format('U');
        // Check for specified colours of appointments in settings
        if( $appointment['type'] == 1 ) {
            $appointments[$aid]['colour'] = (isset($settings['appointments.status.1'])?$settings['appointments.status.1']:'#ffcccc');
        }
        elseif( $appointment['type'] == 2 ) {
            if( isset($appointment['status']) && $appointment['status'] == 60 ) {
                $appointments[$aid]['colour'] = (isset($settings['tasks.status.60'])?$settings['tasks.status.60']:'#ffcccc');
            } else {
                $appointments[$aid]['colour'] = (isset($settings['tasks.priority.' . $appointment['priority']])?$settings['tasks.priority.' . $appointment['priority']]:'#ffcccc');
            }
        }
    }

    return array('stat'=>'ok', 'appointments'=>$appointments);
}
?>
