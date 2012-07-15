<?php
//
// Description
// -----------
// This method allow's a user to close a task.  They must have suffcient permissions to
// close the task
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 		The business the task is attached to.
// atdo_id:				The ID of the task to be closed.
// content:				(optional) Any followup content to be added during close.
// 
// Returns
// -------
// <rsp stat='ok'/>
//
function ciniki_atdo_taskClose($ciniki) {
	//
	// Find all the required and optional arguments
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
	$rc = ciniki_core_prepareArgs($ciniki, 'no', array(
		'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'atdo_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No atdo specified'),
		'content'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No followup specified'),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	$args = $rc['args'];
	
	//
	// Make sure this module is activated, and
	// check permission to run this function for this business
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/atdo/private/checkAccess.php');
	$rc = ciniki_atdo_checkAccess($ciniki, $args['business_id'], 'ciniki.atdo.close', $args['atdo_id'], $ciniki['session']['user']['id']);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	
	//
	// Setup the other arguments required for adding a thread.  These are arguments
	// which should not come through the API, but be set within the API code.
	//
	$args['user_id'] = $ciniki['session']['user']['id'];

	// 
	// Turn of auto commit in the database
	//
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'atdo');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Check if there is a followup
	//
	if( isset($args['content']) && $args['content'] != '' ) {
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAddFollowup.php');
		$rc = ciniki_core_threadAddFollowup($ciniki, 'atdo', 'ciniki_atdo_followups', 'atdo', $args['atdo_id'], $args);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'atdo');
			return $rc;
		}

		//
		// Make sure the user is attached as a follower.  They may already be attached, but it
		// will make sure the flag is set.
		// 
		require_once($ciniki['config']['core']['modules_dir'] . '/core/private/threadAddFollower.php');
		$rc = ciniki_core_threadAddFollower($ciniki, 'atdo', 'ciniki_atdo_users', 'atdo', $args['atdo_id'], $ciniki['session']['user']['id']);
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'atdo');
			return $rc;
		}
	}

	//
	// Close the task
	//
	$strsql = "UPDATE ciniki_atdos SET status = 60, last_updated = UTC_TIMESTAMP() "
		. "WHERE id = '" . ciniki_core_dbQuote($ciniki, $args['atdo']) . "' "
		. "AND business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "";
	$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'atdo');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'atdo');
		return $rc;
	}

	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'atdo', 'ciniki_atdo_history', $args['business_id'],
		2, 'ciniki_atdos', $args['atdo_id'], 'status', 60);
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'atdo');
		return $rc;
	}

	//
	// FIXME: Notify the other users on this thread there was an update.
	//
	// ciniki_core_threadNotifyUsers($ciniki, 'atdo', 'ciniki_atdo_users', 'followup');
	//

	//
	// Commit the changes
	//
	$rc = ciniki_core_dbTransactionCommit($ciniki, 'atdo');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	return array('stat'=>'ok');
	
}
?>
