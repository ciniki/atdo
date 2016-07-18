<?php
//
// Description
// ===========
// This function will return a list of ATDO's for a project.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get the ATDO's for.
// project_id:      The ID of the project to get the ATDO's for.
// status:          Get the project children of a certain status.
// 
// Returns
// -------
//
function ciniki_atdo_projectChildren($ciniki, $business_id, $project_id, $status) {
    
    //
    // Load timezone info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $business_id);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);
    $php_datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    $date_format = ciniki_users_dateFormat($ciniki);

    $project = array();
    $project['appointments'] = array();
    $project['tasks'] = array();
    $project['documents'] = array();
    $project['notes'] = array();
    $project['messages'] = array();
    $strsql = "SELECT ciniki_atdos.id, ciniki_atdos.type, ciniki_atdos.subject, "
        . "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
        . "IF((ciniki_atdos.perm_flags&0x01)=1, 'yes', 'no') AS private, "
        . "IF(ciniki_atdos.status=1, 'open', 'closed') AS status, "
        . "priority, "
        . "IF((u1.perms&0x04)=4, 'yes', 'no') AS assigned, "
    //  . "DATE_FORMAT(start_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS start_date, "
    //  . "duration, "
        . "ciniki_atdos.appointment_date AS start_ts, "
        . "ciniki_atdos.appointment_date AS start_date, "
        . "ciniki_atdos.due_date, "
        . "IF((ciniki_atdos.due_flags&0x01)=1, '', IF(ciniki_atdos.due_date=0, '', ciniki_atdos.due_date)) AS due_time, "
//      . "UNIX_TIMESTAMP(ciniki_atdos.appointment_date) AS start_ts, "
//      . "DATE_FORMAT(ciniki_atdos.appointment_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS start_date, "
//      . "IFNULL(DATE_FORMAT(ciniki_atdos.due_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS due_date, "
//      . "IF((ciniki_atdos.due_flags&0x01)=1, '', IF(ciniki_atdos.due_date=0, '', DATE_FORMAT(ciniki_atdos.due_date, '%l:%i %p'))) AS due_time, "
        . "u2.user_id AS assigned_user_ids, "
        . "IFNULL(u3.display_name, '') AS assigned_users, "
        . "CAST((UNIX_TIMESTAMP(UTC_TIMESTAMP())-UNIX_TIMESTAMP(ciniki_atdo_followups.date_added)) as DECIMAL(12,0)) AS age_followup, "
        . "IFNULL(u4.display_name, u5.display_name) AS followup_user "
        . "FROM ciniki_atdos "
        . "LEFT JOIN ciniki_atdo_users AS u1 ON (ciniki_atdos.id = u1.atdo_id AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
        . "LEFT JOIN ciniki_atdo_users AS u2 ON (ciniki_atdos.id = u2.atdo_id && (u2.perms&0x04) = 4) "
        . "LEFT JOIN ciniki_users AS u3 ON (u2.user_id = u3.id) "
        . "LEFT JOIN ciniki_atdo_followups ON (ciniki_atdos.id = ciniki_atdo_followups.atdo_id) "
        . "LEFT JOIN ciniki_users AS u4 ON (ciniki_atdo_followups.user_id = u4.id ) "
        . "LEFT JOIN ciniki_users AS u5 ON (ciniki_atdos.user_id = u5.id ) "
        . "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ciniki_atdos.project_id = '" . ciniki_core_dbQuote($ciniki, $project_id) . "' "
        . "AND (ciniki_atdos.type = 1 OR ciniki_atdos.type = 2 OR ciniki_atdos.type = 3 OR ciniki_atdos.type = 5 OR (ciniki_atdos.type = 6 AND (u1.perms&0x10) = 0)) "
        . "";
    if( isset($status) && $status != '' ) {
        switch($status) {
            case 'Open':
            case 'open': $strsql .= "AND ciniki_atdos.status = 1 ";
                break;
            case 'Closed':
            case 'closed': $strsql .= "AND ciniki_atdos.status = 60 ";
                break;
        }
    }
    // Check for public/private tasks, and if private make sure user created or is assigned
    $strsql .= "AND ((perm_flags&0x01) = 0 "  // Public to business
            // created by the user requesting the list
            . "OR ((perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
            // Assigned to the user requesting the list
            . "OR ((perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
            . ") "
        . "ORDER BY ciniki_atdos.type, ciniki_atdos.appointment_date, ciniki_atdos.priority DESC, ciniki_atdos.id, u3.display_name "
        . "";
//      if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
//          $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
//      }
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.atdo', array(
        array('container'=>'childtypes', 'fname'=>'type', 'name'=>'tchild',
            'fields'=>array('type')),
        array('container'=>'children', 'fname'=>'id', 'name'=>'child',
            'fields'=>array('id', 'subject', 'allday', 'status', 'priority', 'private', 'assigned', 'assigned_user_ids', 'assigned_users', 
                'start_ts', 'start_date', 'due_date', 'due_time', 
                'last_followup_age'=>'age_followup', 'last_followup_user'=>'followup_user'), 
            'utctotz'=>array('start_ts'=>array('timezone'=>$intl_timezone, 'format'=>'U'),
                'start_date'=>array('timezone'=>$intl_timezone, 'format'=>$php_datetime_format),
                'due_date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d'),
                'due_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:i A'),
                ),
            'idlists'=>array('assigned_user_ids'), 'lists'=>array('assigned_users')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'831', 'msg'=>'Unable to get child items', 'err'=>$rc['err']));
    }
    if( isset($rc['childtypes']) ) {
        foreach($rc['childtypes'] as $tcid => $tchild) {
            //
            // Go through all the returned children and add the proper hash structure and names
            //
            foreach($tchild['tchild']['children'] as $cid => $child) {
                if( $tchild['tchild']['type'] == 1 ) {
                    array_push($project['appointments'], array('appointment'=>$child['child']));
                } elseif( $tchild['tchild']['type'] == 2 ) {
                    array_push($project['tasks'], array('task'=>$child['child']));
                } elseif( $tchild['tchild']['type'] == 3 ) {
                    array_push($project['documents'], array('document'=>$child['child']));
                } elseif( $tchild['tchild']['type'] == 5 ) {
                    array_push($project['notes'], array('note'=>$child['child']));
                } elseif( $tchild['tchild']['type'] == 6 ) {
                    array_push($project['messages'], array('message'=>$child['child']));
                }
            }
        }
    }

    return array('stat'=>'ok', 'project'=>$project);
}
?>
