<?php
//
// Description
// ===========
// This method will return all the details for an ATDO.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:     The ID of the business to get the ATDO for.
// atdo_id:         The ID of the ATDO to get.
// children:        (optional) The children flag to specify returning all child ATDO's if specified as yes.
// 
// Returns
// -------
//
function ciniki_atdo_get(&$ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'atdo_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Atdo'), 
        'children'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'no', 'name'=>'Children'),
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
    $rc = ciniki_atdo_checkAccess($ciniki, $args['business_id'], 'ciniki.atdo.get'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //
    // Get timezone info
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'intlSettings');
    $rc = ciniki_businesses_intlSettings($ciniki, $args['business_id']);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);
    $php_datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');
    $php_date_format = ciniki_users_dateFormat($ciniki, 'php');
    $mysql_date_format = ciniki_users_dateFormat($ciniki, 'mysql');

    //
    // Update the viewed flag to specify the user has requested this atdo.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'threadAddUserPerms');
    $rc = ciniki_core_threadAddUserPerms($ciniki, 'ciniki.atdo', 'user', $args['business_id'], 'ciniki_atdo_users', 'ciniki_atdo_history', 'atdo', $args['atdo_id'], $ciniki['session']['user']['id'], 0x08);
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.7', 'msg'=>'Unable to update task information', 'err'=>$rc['err']));
    }

    //
    // Get the atdo information
    //
    $strsql = "SELECT ciniki_atdos.id, ciniki_atdos.parent_id, ";
    if( isset($modules['ciniki.projects']) ) {
        $strsql .= "ciniki_atdos.project_id, ciniki_projects.name AS project_name, ";
    } else {
        $strsql .= "0 AS project_id, '' as project_name, ";
    }
    $strsql .= "ciniki_atdos.type, ciniki_atdos.subject, ciniki_atdos.location, ciniki_atdos.content, ciniki_atdos.user_id, "
        . "IF((ciniki_atdos.perm_flags&0x01)=1, 'yes', 'no') AS private, "
        . "ciniki_atdos.status, ciniki_atdos.category, ciniki_atdos.priority, "
        . "ciniki_atdos.appointment_date, "
        . "ciniki_atdos.appointment_date AS appointment_date_date, "
        . "ciniki_atdos.appointment_date AS appointment_time, "
        . "ciniki_atdos.appointment_date AS appointment_12hour, "
//      . "DATE_FORMAT(ciniki_atdos.appointment_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS appointment_date, "
//      . "DATE_FORMAT(ciniki_atdos.appointment_date, '%Y-%m-%d') AS appointment_date_date, "
//      . "DATE_FORMAT(ciniki_atdos.appointment_date, '%H:%i') AS appointment_time, "
//      . "DATE_FORMAT(ciniki_atdos.appointment_date, '%l:%i') AS appointment_12hour, "
        . "ciniki_atdos.appointment_duration, "
        . "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS appointment_duration_allday, "
//      . "DATE_FORMAT(ciniki_atdos.due_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS due_date, "
//      . "DATE_FORMAT(ciniki_atdos.due_date, '%Y-%m-%d') AS due_date_date, "
//      . "DATE_FORMAT(ciniki_atdos.due_date, '%H:%i') AS due_time, "
//      . "DATE_FORMAT(ciniki_atdos.due_date, '%l:%i') AS due_12hour, "
        . "ciniki_atdos.due_date AS due_date, "
        . "ciniki_atdos.due_date AS due_date_date, "
        . "ciniki_atdos.due_date AS due_time, "
        . "ciniki_atdos.due_date AS due_12hour, "
        . "ciniki_atdos.due_duration, "
        . "IF((ciniki_atdos.due_flags&0x01)=1, 'yes', 'no') AS due_duration_allday, "
        . "ciniki_atdos.appointment_repeat_type, ciniki_atdos.appointment_repeat_interval, "
        . "DATE_FORMAT(ciniki_atdos.appointment_date, '%D') AS appointment_repeat_dayofmonth, "
        . "DAY(ciniki_atdos.appointment_date) AS appointment_repeat_day, "
        . "DATE_FORMAT(ciniki_atdos.appointment_date, '%W') AS appointment_repeat_weekday, "
        . "DATE_FORMAT(ciniki_atdos.appointment_repeat_end, '" . ciniki_core_dbQuote($ciniki, $mysql_date_format) . "') AS appointment_repeat_end "
//      . "DATE_FORMAT(CONVERT_TZ(ciniki_atdos.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added, "
//      . "DATE_FORMAT(CONVERT_TZ(ciniki_atdos.last_updated, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS last_updated "
        . "FROM ciniki_atdos ";
    if( isset($modules['ciniki.projects']) ) {
        $strsql .= "LEFT JOIN ciniki_projects ON (ciniki_atdos.project_id = ciniki_projects.id AND ciniki_projects.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') ";
    }
    $strsql .= "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
        . "AND ciniki_atdos.id = '" . ciniki_core_dbQuote($ciniki, $args['atdo_id']) . "' "
        . "";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.atdo', array(
        array('container'=>'atdos', 'fname'=>'id', 'name'=>'atdo',
            'fields'=>array('id', 'parent_id', 'project_id', 'project_name', 'type', 'subject', 'location', 'content', 'user_id',
                'private', 'status', 'category', 'priority', 
                'appointment_date', 'appointment_date_date', 'appointment_time', 'appointment_12hour', 'appointment_duration', 'appointment_duration_allday',
                'due_date', 'due_date_date', 'due_time', 'due_12hour', 'due_duration', 'due_duration_allday',
                'appointment_repeat_type', 'appointment_repeat_interval', 'appointment_repeat_dayofmonth', 'appointment_repeat_day', 'appointment_repeat_weekday', 'appointment_repeat_end', 
                ),
            'utctotz'=>array('appointment_date'=>array('timezone'=>$intl_timezone, 'format'=>$php_datetime_format),
                'appointment_date_date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d'),
                'apointment_time'=>array('timezone'=>$intl_timezone, 'format'=>'H:i'),
                'apointment_12hour'=>array('timezone'=>$intl_timezone, 'format'=>'g:i A'),
                'due_date'=>array('timezone'=>$intl_timezone, 'format'=>$php_datetime_format),
                'due_date_date'=>array('timezone'=>$intl_timezone, 'format'=>'Y-m-d'),
                'due_time'=>array('timezone'=>$intl_timezone, 'format'=>'H:i'),
                'due_12hour'=>array('timezone'=>$intl_timezone, 'format'=>'g:i A'),
            )),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['atdos'][0]['atdo']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.atdo.8', 'msg'=>'Unable to find item'));
    }
    $atdo = $rc['atdos'][0]['atdo'];

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

    $user_ids = array($atdo['user_id']);

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
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbRspQueryPlusUserIDs');
    $rc = ciniki_core_dbRspQueryPlusUserIDs($ciniki, $strsql, 'ciniki.atdo', 'followups', 'followup', array('stat'=>'ok', 'followups'=>array(), 'user_ids'=>array()));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.9', 'msg'=>'Unable to load item information', 'err'=>$rc['err']));
    }
    $atdo['followups'] = $rc['followups'];
    $user_ids = array_merge($user_ids, $rc['user_ids']);

    //
    // Get the list of users attached to the atdo
    //
    $strsql = "SELECT atdo_id, user_id, perms "
        . "FROM ciniki_atdo_users "
        . "WHERE atdo_id = '" . ciniki_core_dbQuote($ciniki, $args['atdo_id']) . "' ";
    $rc = ciniki_core_dbRspQueryPlusUserIDs($ciniki, $strsql, 'ciniki.atdo', 'users', 'user', array('stat'=>'ok', 'users'=>array(), 'user_ids'=>array()));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.10', 'msg'=>'Unable to load item information', 'err'=>$rc['err']));
    }
    $atdo_users = $rc['users'];
    $user_ids = array_merge($user_ids, $rc['user_ids']);

    //
    // Get the users which are linked to these accounts
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'userListByID');
    $rc = ciniki_users_userListByID($ciniki, 'users', $user_ids, 'display_name');
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.11', 'msg'=>'Unable to load item information', 'err'=>$rc['err']));
    }
    if( !isset($rc['users']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.12', 'msg'=>'Unable to load item information', 'err'=>$rc['err']));
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
    // Check if the children should be loaded for a project
    //
    if( isset($args['children']) && $args['children'] == 'yes' ) {
        $atdo['appointments'] = array();
        $atdo['tasks'] = array();
        $atdo['documents'] = array();
        $atdo['notes'] = array();
        $atdo['messages'] = array();
        $strsql = "SELECT ciniki_atdos.id, ciniki_atdos.type, ciniki_atdos.subject, "
            . "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
            . "IF((ciniki_atdos.perm_flags&0x01)=1, 'yes', 'no') AS private, "
            . "IF(ciniki_atdos.status=1, 'open', 'closed') AS status, "
            . "priority, "
            . "IF((u1.perms&0x04)=4, 'yes', 'no') AS assigned, "
            . "UNIX_TIMESTAMP(ciniki_atdos.appointment_date) AS start_ts, "
            . "DATE_FORMAT(ciniki_atdos.appointment_date, '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS start_date, "
            . "IFNULL(DATE_FORMAT(ciniki_atdos.due_date, '" . ciniki_core_dbQuote($ciniki, $date_format) . "'), '') AS due_date, "
            . "IF((ciniki_atdos.due_flags&0x01)=1, '', IF(ciniki_atdos.due_date=0, '', DATE_FORMAT(ciniki_atdos.due_date, '%l:%i %p'))) AS due_time, "
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
            . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
            . "AND ciniki_atdos.parent_id = '" . ciniki_core_dbQuote($ciniki, $atdo['id']) . "' "
            . "AND (ciniki_atdos.type = 1 OR ciniki_atdos.type = 2 OR ciniki_atdos.type = 3 OR ciniki_atdos.type = 5 OR (ciniki_atdos.type = 6 AND (u1.perms&0x10) = 0)) "
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
        $strsql .= "AND ((perm_flags&0x01) = 0 "  // Public to business
                // created by the user requesting the list
                . "OR ((perm_flags&0x01) = 1 AND ciniki_atdos.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "') "
                // Assigned to the user requesting the list
                . "OR ((perm_flags&0x01) = 1 AND (u1.perms&0x04) = 0x04) "
                . ") "
            . "ORDER BY ciniki_atdos.appointment_date, ciniki_atdos.priority DESC, ciniki_atdos.id, u3.display_name "
            . "";
//      if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
//          $strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
//      }
        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
        $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.atdo', array(
            array('container'=>'childtypes', 'fname'=>'type', 'name'=>'tchild',
                'fields'=>array('type')),
            array('container'=>'children', 'fname'=>'id', 'name'=>'child',
                'fields'=>array('id', 'subject', 'allday', 'status', 'priority', 'private', 'assigned', 'assigned_user_ids', 'assigned_users', 'due_date', 'due_time',
                    'start_ts', 'start_date', 
                    'last_followup_age'=>'age_followup', 'last_followup_user'=>'followup_user'), 
                'idlists'=>array('assigned_user_ids'), 'lists'=>array('assigned_users')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.atdo.13', 'msg'=>'Unable to get child items', 'err'=>$rc['err']));
        }
        if( isset($rc['childtypes']) ) {
            foreach($rc['childtypes'] as $tcid => $tchild) {
                foreach($tchild['tchild']['children'] as $cid => $child) {
                    if( $tchild['tchild']['type'] == 1 ) {
                        array_push($atdo['appointments'], array('appointment'=>$child['child']));
                    } elseif( $tchild['tchild']['type'] == 2 ) {
                        array_push($atdo['tasks'], array('task'=>$child['child']));
                    } elseif( $tchild['tchild']['type'] == 3 ) {
                        array_push($atdo['documents'], array('document'=>$child['child']));
                    } elseif( $tchild['tchild']['type'] == 5 ) {
                        array_push($atdo['notes'], array('note'=>$child['child']));
                    } elseif( $tchild['tchild']['type'] == 6 ) {
                        array_push($atdo['messages'], array('message'=>$child['child']));
                    }
                }
            }
        }
    }

    return array('stat'=>'ok', 'atdo'=>$atdo);
}
?>
