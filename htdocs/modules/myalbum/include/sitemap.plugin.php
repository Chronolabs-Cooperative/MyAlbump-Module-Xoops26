<?php

if( ! defined( 'XOOPS_ROOT_PATH' ) ) exit ;

$mydirname = basename( dirname( dirname( __FILE__ ) ) ) ;
if( ! preg_match( '/^(\D+)(\d*)$/' , $mydirname , $regs ) ) echo ( "invalid dirname: " . htmlspecialchars( $mydirname ) ) ;
$mydirnumber = $regs[2] === '' ? '' : intval( $regs[2] ) ;

eval( '

function b_sitemap_'.$mydirname.'(){
	$GLOBALS[\'xoops\'] = Xoops::getInstance();

    $block = sitemap_get_categoires_map($GLOBALS[\'xoops\']->db->prefix("myalbum'.$mydirnumber.'_cat"), "cid", "pid", "title", "viewcat.php?cid=", "title");

	return $block;
}

' ) ;


?>