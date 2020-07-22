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
        'name' => 'Atdo',
        'sync' => 'yes',
        'o_name' => 'atdo',
        'o_container' => 'atdos',
        'table' => 'ciniki_atdos',
        'fields' => array(
            'parent_id' => array('name'=>'Parent', 'ref'=>'ciniki.atdo.atdo'),
            'project_id' => array('name'=>'Project', 'ref'=>'ciniki.projects.project'),
            'type' => array('name'=>'Type'),
            'category' => array('name'=>'Category', 'default'=>''),
            'status' => array('name'=>'Status', 'default'=>'1',),
            'priority' => array('name'=>'Priority', 'default'=>'10'),
            'perm_flags' => array('name'=>'Permissions', 'default'=>0x01),
            'user_id' => array('name'=>'User', 'ref'=>'ciniki.users.user'),
            'subject' => array('name'=>'Subject'),
            'location' => array('name'=>'Location', 'default'=>''),
            'content' => array('name'=>'Content', 'default'=>''),
            'appointment_date' => array('name'=>'Appointment Date'),
            'appointment_duration' => array('name'=>'Appointment Duration'),
            'appointment_flags' => array('name'=>'Appointment Options'),
            'appointment_repeat_type' => array('name'=>'Appointment Repeat Type'),
            'appointment_repeat_interval' => array('name'=>'Appointment Repeat Interval'),
            'appointment_repeat_end' => array('name'=>'Appointment Repeat End Date'),
            'due_date' => array('name'=>'Due Date'),
            'due_duration' => array('name'=>'Due Date Duration'),
            'due_flags' => array('name'=>'Due Date Flags'),
            ),
        'history_table' => 'ciniki_atdo_history',
        );
    $objects['followup'] = array(
        'name' => 'Atdo Followup',
        'sync' => 'yes',
        'o_name' => 'followup',
        'o_container' => 'followups',
        'table' => 'ciniki_atdo_followups',
        'fields' => array(
            'parent_id' => array('name'=>'Parent', 'ref'=>'ciniki.atdo.followup'),
            'atdo_id' => array('name'=>'ATDO', 'ref'=>'ciniki.atdo.atdo'),
            'user_id' => array('name'=>'User', 'ref'=>'ciniki.users.user'),
            'content' => array('name'=>'Content'),
            ),
        'history_table' => 'ciniki_atdo_history',
        );
    $objects['user'] = array(
        'name' => 'Atdo User',
        'sync' => 'yes',
        'o_name' => 'user',
        'o_container' => 'users',
        'table' => 'ciniki_atdo_users',
        'fields' => array(
            'atdo_id' => array('name'=>'Atdo', 'ref'=>'ciniki.atdo.atdo'),
            'user_id' => array('name'=>'User', 'ref'=>'ciniki.users.user'),
            'perms' => array('name'=>'Permissions', 'default'=>0x03),
            ),
        'history_table' => 'ciniki_atdo_history',
        );
    $objects['setting'] = array(
        'type' => 'settings',
        'name' => 'Atdo Settings',
        'table' => 'ciniki_atdo_settings',
        'history_table' => 'ciniki_atdo_history',
        );

    return array('stat' => 'ok', 'objects'=>$objects);
}
?>
