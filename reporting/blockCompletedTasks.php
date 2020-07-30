<?php
//
// Description
// -----------
// Return the report of new atdo
//
// Arguments
// ---------
// ciniki:
// tnid:         The ID of the tenant to get the birthdays for.
// args:                The options for the query.
//
// Additional Arguments
// --------------------
// days:                The number of days past to look for new atdo.
// 
// Returns
// -------
//
function ciniki_atdo_reporting_blockCompletedTasks(&$ciniki, $tnid, $args) {
    //
    // Get the tenant settings
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'intlSettings');
    $rc = ciniki_tenants_intlSettings($ciniki, $tnid);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $intl_timezone = $rc['settings']['intl-default-timezone'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuoteIDs');

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki, 'php');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki, 'php');

    //
    // Load maps
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'private', 'maps');
    $rc = ciniki_atdo_maps($ciniki);
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $maps = $rc['maps'];

    //
    // Get the list of tasks for the employee
    //
    $status_sql = "AND ciniki_atdos.status = 60 ";
    $priority_sql = '';
    if( isset($args['priority']) && $args['priority'] != '' && $args['priority'] != '0' && $args['priority'] > 0 ) {
        $priority_sql = "AND ciniki_atdos.priority = '" . ciniki_core_dbQuote($ciniki, $args['priority']) . "' ";
    }
    $project_name_sql = "'' AS project_name, ";
    $projects_sql = '';
    if( isset($modules['ciniki.projects']) ) {
        $project_name_sql = "IFNULL(ciniki_projects.name, '') AS project_name, ";
        $projects_sql = "LEFT JOIN ciniki_projects ON ("
            . "ciniki_atdos.project_id = ciniki_projects.id "
            . "AND ciniki_projects.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . ") ";
    }

    $start_dt = new DateTime('now', new DateTimezone($intl_timezone));
    $start_dt->setTime(23,59,59);
    $end_dt = clone $start_dt;
    if( isset($args['days']) && $args['days'] > 0 ) {
        $start_dt->sub(new DateInterval('P' . $args['days'] . 'D'));
    } else {
        $start_dt->sub(new DateInterval('P1D'));
    }
    $start_dt->setTimezone(new DateTimezone('UTC'));
    $end_dt->setTimezone(new DateTimezone('UTC'));

    //
    // Get the list of tasks
    //
    $strsql = "SELECT ciniki_atdos.id, "
        . "IF(ciniki_atdos.category='', 'Uncategorized', ciniki_atdos.category) AS category, "
        . "ciniki_atdos.subject, "
        . $project_name_sql
        . "IF((ciniki_atdos.appointment_flags&0x01)=1, 'yes', 'no') AS allday, "
        . "IF((ciniki_atdos.perm_flags&0x01)=1, 'yes', 'no') AS private, "
        . "IF(ciniki_atdos.status=1, 'open', 'closed') AS status, "
        . "ciniki_atdos.priority, "
        . "ciniki_atdos.priority AS priority_text, "
        . "IFNULL(ciniki_atdos.due_date, '') AS due_date, "
        . "IF((ciniki_atdos.due_flags&0x01)=1, '', IF(ciniki_atdos.due_date=0, '', ciniki_atdos.due_date)) AS due_time, "
        . "ciniki_atdos.last_updated AS last_updated_date, "
        . "ciniki_atdos.last_updated AS last_updated_time, "
        . "ciniki_atdos.date_closed "
        . "FROM ciniki_atdos "
//        . "INNER JOIN ciniki_atdo_users AS u1 ON ("
//            . "ciniki_atdos.id = u1.atdo_id "
//            . "AND u1.user_id = '" . ciniki_core_dbQuote($ciniki, $args['user_id']) . "' "
//            . "AND (u1.perms&0x04) = 0x04 "  // assigned to user
//            . "AND u1.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
//            . ") "
        . $projects_sql
        . "WHERE ciniki_atdos.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_atdos.type = 2 "
        . "AND ciniki_atdos.date_closed <= '" . ciniki_core_dbQuote($ciniki, $end_dt->format('Y-m-d H:i:s')) . "' "
        . "AND ciniki_atdos.date_closed > '" . ciniki_core_dbQuote($ciniki, $start_dt->format('Y-m-d H:i:s')) . "' "
        . $priority_sql
        . $status_sql
        . "ORDER BY ciniki_atdos.priority DESC, ciniki_atdos.due_date DESC, ciniki_atdos.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.atdo', array(
        array('container'=>'tasks', 'fname'=>'id', 'name'=>'task',
            'fields'=>array('id', 'category', 'subject', 'project_name', 'allday', 'status', 'priority', 'priority_text', 'private', 
                'due_date', 'due_time', 'last_updated_date', 'last_updated_time','date_closed',
                ), 
            'maps'=>array('priority_text'=>$maps['atdo']['priority']),
            'utctotz'=>array(
                'due_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'due_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:i A'),
                'last_updated_date'=>array('timezone'=>$intl_timezone, 'format'=>$date_format),
                'last_updated_time'=>array('timezone'=>$intl_timezone, 'format'=>'g:i A'),
                'date_closed'=>array('timezone'=>$intl_timezone, 'format'=>$datetime_format),
                ),
            'lists'=>array('assigned_users'),    
            ),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tasks = isset($rc['tasks']) ? $rc['tasks'] : array();

    if( count($tasks) > 0 ) {
        //
        // Create the report blocks
        //
        $chunk = array(
            'type'=>'table',
            'columns'=>array(
                array('label'=>'Priority', 'pdfwidth'=>'10%', 'field'=>'priority_text'),
                array('label'=>'Category', 'pdfwidth'=>'25%', 'field'=>'category'),
                array('label'=>'Tasks', 'pdfwidth'=>'50%', 'field'=>'subject'),
                array('label'=>'Date Closed', 'pdfwidth'=>'15%', 'field'=>'date_closed'),
                ),
            'data'=>array(),
            'editApp'=>array('app'=>'ciniki.atdo.main', 'args'=>array('atdo_id'=>'d.id')),
            'textlist'=>'',
            );
        foreach($tasks as $tid => $task) {
            //
            // Add emails to customer
            //
            $chunk['textlist'] .= $task['priority_text'] . " - ";
            $chunk['textlist'] .= $task['subject'] . "\n";
            $chunk['textlist'] .= $task['category'] . "\n";
            $chunk['textlist'] .= $task['last_updated_date'] . "\n";

            $chunk['textlist'] .= "\n";
            $chunk['data'][] = $task;
        }
        $chunks[] = $chunk;
    }
    //
    else {
        $chunks[] = array('type'=>'message', 'content'=>'No tasks were closed today');
    }
    
    return array('stat'=>'ok', 'chunks'=>$chunks);
}
?>
