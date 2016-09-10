<?php
//
// Description
// -----------
// This function will return a list of user interface settings for the module.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get events for.
//
// Returns
// -------
//
function ciniki_atdo_hooks_uiSettings($ciniki, $business_id, $args) {

    $settings = array();

    //
    // Get the settings
    //
    $rc = ciniki_core_dbDetailsQueryDash($ciniki, 'ciniki_atdo_settings', 'business_id', 
        $business_id, 'ciniki.atdo', 'settings', '');
    if( $rc['stat'] == 'ok' && isset($rc['settings']) ) {
        $settings = $rc['settings'];
    }

    $rsp = array('stat'=>'ok', 'settings'=>$settings, 'menu_items'=>array());  

    $task_count = 0;
    $message_count = 0;
    $note_count = 0;

    $strsql = "SELECT ciniki_atdos.type, COUNT(ciniki_atdos.id) AS num_items "
        . "FROM ciniki_atdos, ciniki_atdo_users "
        . "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND (ciniki_atdos.type = 2 OR ciniki_atdos.type = 7) "   // Tasks or Projects
        . "AND ciniki_atdos.id = ciniki_atdo_users.atdo_id "
        . "AND ciniki_atdo_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND ciniki_atdos.status = 1 "
        . "AND (ciniki_atdo_users.perms&0x04) = 0x04 "
        . "GROUP BY ciniki_atdos.type "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashIDQuery');
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.atdo', 'atdo', 'type');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['atdo']['2']['num_items']) ) {
        $task_count = $rc['atdo']['2']['num_items'];
    }

    //
    // Messages and Notes are different, as it shows how many new or unread items
    //
    $strsql = "SELECT type, COUNT(ciniki_atdos.id) AS num_items "
        . "FROM ciniki_atdos, ciniki_atdo_users "
        . "WHERE ciniki_atdos.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND ((ciniki_atdos.type = 5 AND ciniki_atdos.parent_id = 0) OR ciniki_atdos.type = 6 )"  // Notes or Messages
        . "AND ciniki_atdos.id = ciniki_atdo_users.atdo_id "
        . "AND ciniki_atdo_users.user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND ciniki_atdos.status = 1 "
        . "AND (ciniki_atdo_users.perms&0x04) = 0x04 "
        . "AND (ciniki_atdo_users.perms&0x08) = 0 "
        . "GROUP BY ciniki_atdos.type "
        . "";
    $rc = ciniki_core_dbHashIDQuery($ciniki, $strsql, 'ciniki.atdo', 'atdo', 'type');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['atdo']['6']['num_items']) ) {
        $message_count = $rc['atdo']['6']['num_items'];
    }
    if( isset($rc['atdo']['5']['num_items']) ) {
        $note_count = $rc['atdo']['5']['num_items'];
    }

    //
    // Tasks
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.atdo', 0x02)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>5000,
            'label'=>'Tasks', 
            'count'=>$task_count,
            'edit'=>array('app'=>'ciniki.atdo.main', 'args'=>array('tasks'=>'"\'yes\'"')),
            'add'=>array('app'=>'ciniki.atdo.main', 'args'=>array('add'=>'"\'task\'"')),
            'search'=>array(
                'method'=>'ciniki.atdo.tasksSearchQuick',
                'args'=>array(),
                'container'=>'tasks',
                'cols'=>3,
                'headerValues'=>array('', 'Task', 'Due'),
                'cellClasses'=>array('multiline aligncenter', 'multiline', 'multiline'),
                'cellValues'=>array(
                    '0'=>'M.curBusiness.atdo.priorities[d.task.priority];',
                    '2'=>'\'<span class="maintext">\' + d.task.subject + \'</span><span class="subtext">\' + d.task.assigned_users + \'&nbsp;</span>\'',
                    '3'=>'\'<span class="maintext">\' + d.task.due_date + \'</span><span class="subtext">\' + d.task.due_time + \'</span>\'',
                    ),
                'noData'=>'No tasks found',
                'edit'=>array('method'=>'ciniki.atdo.main', 'args'=>array('atdo_id'=>'d.task.id;')),
                'submit'=>array('method'=>'ciniki.atdo.main', 'args'=>array('tasksearch'=>'search_str')),
                ),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    //
    // Messages
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.atdo', 0x20)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>3302,
            'label'=>'Messages', 
            'count'=>$message_count,
            'edit'=>array('app'=>'ciniki.atdo.main', 'args'=>array('messages'=>'"\'yes\'"')),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    //
    // Notes
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.atdo', 0x10)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>3301,
            'label'=>'Notes', 
            'count'=>$note_count,
            'edit'=>array('app'=>'ciniki.atdo.main', 'args'=>array('notes'=>'"\'yes\'"')),
            );
        $rsp['menu_items'][] = $menu_item;
    } 

    //
    // FAQ
    //
    if( ciniki_core_checkModuleFlags($ciniki, 'ciniki.atdo', 0x08)
        && (isset($args['permissions']['owners'])
            || isset($args['permissions']['employees'])
            || isset($args['permissions']['resellers'])
            || ($ciniki['session']['user']['perms']&0x01) == 0x01
            )
        ) {
        $menu_item = array(
            'priority'=>3300,
            'label'=>'FAQ', 
            'edit'=>array('app'=>'ciniki.atdo.main', 'args'=>array('faq'=>'"\'yes\'"')),
            );
        $rsp['menu_items'][] = $menu_item;
    } 
     

    return $rsp;
}
?>
