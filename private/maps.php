<?php
//
// Description
// -----------
// The module flags
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_atdo_maps($ciniki) {
    $maps = array();
    $maps['atdo'] = array(
        'priority'=>array(
            '0'=>'Unknown',
            '10'=>'Low',
            '30'=>'Medium',
            '50'=>'High',
        ),
    );

    return array('stat'=>'ok', 'maps'=>$maps);
}
?>
