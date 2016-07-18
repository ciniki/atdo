<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_atdo_objects($ciniki) {
    $objects = array();
    $objects['atdo'] = array(
        'name'=>'Atdo',
        'table'=>'ciniki_atdos',
        'fields'=>array(
            'parent_id'=>array(),
            'project_id'=>array('ref'=>'ciniki.projects.project'),
            'type'=>array(),
            'category'=>array(),
            'status'=>array(),
            'priority'=>array(),
            'perm_flags'=>array(),
            'user_id'=>array('ref'=>'ciniki.users.user'),
            'subject'=>array(),
            'location'=>array(),
            'content'=>array(),
            'appointment_date'=>array(),
            'appointment_duration'=>array(),
            'appointment_flags'=>array(),
            'appointment_repeat_type'=>array(),
            'appointment_repeat_interval'=>array(),
            'appointment_repeat_end'=>array(),
            'due_date'=>array(),
            'due_duration'=>array(),
            'due_flags'=>array(),
            ),
        'history_table'=>'ciniki_atdo_history',
        );
    $objects['followup'] = array(
        'name'=>'Atdo Followup',
        'table'=>'ciniki_atdo_followups',
        'fields'=>array(
            'parent_id'=>array(),
            'atdo_id'=>array('ref'=>'ciniki.atdo.atdo'),
            'user_id'=>array('ref'=>'ciniki.users.user'),
            'content'=>array(),
            ),
        'history_table'=>'ciniki_atdo_history',
        );
    $objects['user'] = array(
        'name'=>'Atdo User',
        'table'=>'ciniki_atdo_users',
        'fields'=>array(
            'atdo_id'=>array('ref'=>'ciniki.atdo.atdo'),
            'user_id'=>array('ref'=>'ciniki.users.user'),
            'perms'=>array(),
            ),
        'history_table'=>'ciniki_atdo_history',
        );
    $objects['setting'] = array(
        'type'=>'settings',
        'name'=>'Atdo Settings',
        'table'=>'ciniki_atdo_settings',
        'history_table'=>'ciniki_atdo_history',
        );

    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
