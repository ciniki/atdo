<?php
//
// Description
// -----------
// This method will turn the settings for a ATDO module for a tenant.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant to get the ATDO settings for.
// 
// Returns
// -------
//
function ciniki_atdo_settingsGet($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'atdo', 'private', 'checkAccess');
    $rc = ciniki_atdo_checkAccess($ciniki, $args['tnid'], 'ciniki.atdo.settingsGet'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    
    //
    // Get the current time in the users format
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
    $date_format = ciniki_users_dateFormat($ciniki);

    date_default_timezone_set('America/Toronto');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $strsql = "SELECT DATE_FORMAT(FROM_UNIXTIME('" . time() . "'), '" . ciniki_core_dbQuote($ciniki, $date_format) . "') as formatted_date ";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.core', 'date');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $formatted_date = '';
    if( isset($rc['date']['formatted_date']) ) {
        $formatted_date = $rc['date']['formatted_date'];
    }

    //
    // Grab the settings for the tenant from the database
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbDetailsQuery');
    $rc = ciniki_core_dbDetailsQuery($ciniki, 'ciniki_atdo_settings', 'tnid', $args['tnid'], 'ciniki.atdo', 'settings', '');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    $rc['date_today'] = $formatted_date;

    //
    // Return the response, including colour arrays and todays date
    //
    return $rc;
}
?>
