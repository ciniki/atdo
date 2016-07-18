#
# Copy information from appointments and tasks into atdo
#


# Copy task information
INSERT INTO ciniki_atdos (id, uuid, business_id, user_id, type, status, priority, perm_flags,
    appointment_date, appointment_duration, appointment_flags,
    appointment_repeat_type, appointment_repeat_interval, appointment_repeat_end,
    due_date, due_duration, due_flags,
    subject, location, content,
    date_added, last_updated) 
    SELECT id, uuid, business_id, user_id, 2, status, priority, flags,
        start_date, duration, flags,
        repeat_type, repeat_interval, repeat_end,
        due_date, due_duration, flags,
        subject, '', '',
        date_added, last_updated 
        FROM ciniki_tasks;
    

# Update the permission flags to set private flag
UPDATE ciniki_atdos SET perm_flags = 1 WHERE (perm_flags&0x02) = 0x02;
UPDATE ciniki_atdos SET appointment_flags = 1 WHERE (appointment_flags&0x01) = 0x01;
UPDATE ciniki_atdos SET due_flags = 1 WHERE (appointment_flags&0x04) = 0x04;

# Copy followups
INSERT INTO ciniki_atdo_followups (id, parent_id, atdo_id, user_id, content, date_added, last_updated)
    SELECT id, parent_id, task_id, user_id, content, date_added, last_updated FROM ciniki_task_followups;

# Copy users
INSERT INTO ciniki_atdo_users (atdo_id, user_id, perms, date_added, last_updated)
    SELECT task_id, user_id, perms, date_added, last_updated FROM ciniki_task_users;

# Copy settings
INSERT INTO ciniki_atdo_settings (business_id, detail_key, detail_value, date_added, last_updated)
    SELECT business_id, CONCAT('tasks.', detail_key), detail_value, date_added, last_updated FROM ciniki_task_settings;


# Copy appointment information
INSERT INTO ciniki_atdos (id, uuid, business_id, user_id, type, status, priority, perm_flags, 
    appointment_date, appointment_duration, appointment_flags,
    appointment_repeat_type, appointment_repeat_interval, appointment_repeat_end,
    subject, location, content,
    date_added, last_updated) 
    SELECT id+100, uuid, business_id, user_id, 1, status, 10, 0,
        start_date, duration, flags,
        repeat_type, repeat_interval, repeat_end,
        subject, location, notes,
        date_added, last_updated 
        FROM ciniki_appointments;
