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
function ciniki_atdo_flags($ciniki, $modules) {
    $flags = array(
        // 0x01
        array('flag'=>array('bit'=>'1', 'name'=>'Appointments')),
        array('flag'=>array('bit'=>'2', 'name'=>'Tasks')),
//        array('flag'=>array('bit'=>'3', 'name'=>'')),
        array('flag'=>array('bit'=>'4', 'name'=>'FAQ')),
        // 0x10 
        array('flag'=>array('bit'=>'5', 'name'=>'Notes')),
        array('flag'=>array('bit'=>'6', 'name'=>'Messages')),
//        array('flag'=>array('bit'=>'7', 'name'=>'')),
//        array('flag'=>array('bit'=>'8', 'name'=>'')),
        // 0x0100 
//        array('flag'=>array('bit'=>'9', 'name'=>'')),
//        array('flag'=>array('bit'=>'10', 'name'=>'')),
//        array('flag'=>array('bit'=>'11', 'name'=>'')),
//        array('flag'=>array('bit'=>'12', 'name'=>'')),
        // 0x1000 
//        array('flag'=>array('bit'=>'13', 'name'=>'')),
//        array('flag'=>array('bit'=>'14', 'name'=>'')),
//        array('flag'=>array('bit'=>'15', 'name'=>'')),
//        array('flag'=>array('bit'=>'16', 'name'=>'')),
        );

    return array('stat'=>'ok', 'flags'=>$flags);
}
?>
