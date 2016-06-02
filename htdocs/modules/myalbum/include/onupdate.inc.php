<?php

if( ! defined( 'XOOPS_ROOT_PATH' ) ) exit ;

// referer check
$ref = xoops_getenv('HTTP_REFERER');
if( $ref == '' || strpos( $ref , XOOPS_URL.'/modules/system/admin.php' ) === 0 ) {
	/* module specific part */
	$GLOBALS['xoops'] = Xoops::getInstance();
	
	// 2.8 -> 2.9
	$check_result = $GLOBALS['xoops']->dbquery( "SELECT weight FROM ".$GLOBALS['xoops']->dbprefix("myalbum{$mydirnumber}_cat") ) ;
	if( ! $check_result ) {
		$GLOBALS['xoops']->dbquery( "ALTER TABLE ".$GLOBALS['xoops']->dbprefix("myalbum{$mydirnumber}_cat")." ADD weight int(5) unsigned NOT NULL default 0, ADD depth int(5) unsigned NOT NULL default 0, ADD description text, ADD allowed_ext varchar(255) NOT NULL default 'jpg|jpeg|gif|png', ADD KEY (weight), ADD KEY (depth)" ) ;
		$GLOBALS['xoops']->dbquery( "ALTER TABLE ".$GLOBALS['xoops']->dbprefix("myalbum{$mydirnumber}_photos")." ADD KEY (`date`)" ) ;
		$GLOBALS['xoops']->dbquery( "ALTER TABLE ".$GLOBALS['xoops']->dbprefix("myalbum{$mydirnumber}_text")." DROP KEY lid" ) ;
		$GLOBALS['xoops']->dbquery( "ALTER TABLE ".$GLOBALS['xoops']->dbprefix("myalbum{$mydirnumber}_text")." ADD PRIMARY KEY (lid)" ) ;
		$GLOBALS['xoops']->dbquery( "ALTER TABLE ".$GLOBALS['xoops']->dbprefix("myalbum{$mydirnumber}_votedata")." ADD KEY (lid)" ) ;
	}

	/* General part */
	// Version 3.01
	$GLOBALS['xoops']->dbquery( "ALTER TABLE ".$GLOBALS['xoops']->dbprefix("myalbum{$mydirnumber}_photos")." ADD COLUMN tags varchar(255) NOT NULL default ''" ) ;
	// Keep the values of block's options when module is updated (by nobunobu)
	include dirname( __FILE__ ) . "/updateblock.inc.php" ;

}

?>