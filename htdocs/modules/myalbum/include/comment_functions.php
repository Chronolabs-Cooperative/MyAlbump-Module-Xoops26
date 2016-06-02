<?php

if( ! defined( 'MYALBUM_COMMENT_FUNCTIONS_INCLUDED' ) ) {

define('MYALBUM_COMMENT_FUNCTIONS_INCLUDED' , 1 ) ;

// comment callback functions

function myalbum_comments_update( $lid , $total_num ) {
	$GLOBALS['xoops'] = Xoops::getInstance();
	$photos_handler = $GLOBALS['xoops']->getModuleHandler('photos', $GLOBALS['mydirname']);
	$photo = $photos_handler->get($lid);
	$photo->setVar('comments', $total_num);
	return $photos_handler->insert($photo, true);
}

function myalbum_comments_approve( &$comment )
{
	// notification mail here
}

}
?>