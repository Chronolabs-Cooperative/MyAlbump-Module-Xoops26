<?php

if( ! defined( 'MYALBUM_BLOCK_TOPNEWS_INCLUDED' ) ) {

define('MYALBUM_BLOCK_TOPNEWS_INCLUDED' , 1 ) ;

function b_myalbum_topnews_show( $options )
{
	$GLOBALS['xoops'] = Xoops::getInstance();
	$GLOBALS['myts'] = MyTextSanitizer::getInstance();
	xoops_load('xoopslocal');

	// For myAlbum-P < 2.70
	if( strncmp( $options[0] , 'myalbum' , 7 ) != 0 ) {
		$title_max_length = intval( $options[1] ) ;
		$photos_num = intval( $options[0] ) ;
		$mydirname = 'myalbum' ;
	} else {
		$title_max_length = intval( $options[2] ) ;
		$photos_num = intval( $options[1] ) ;
		$mydirname = $options[0] ;
	}
	$cat_limitation = empty( $options[3] ) ? 0 : intval( $options[3] ) ;
	$cat_limit_recursive = empty( $options[4] ) ? 0 : 1 ;
	$cols = empty( $options[6] ) ? 1 : intval( $options[6] ) ;

	include( XOOPS_ROOT_PATH."/modules/$mydirname/include/read_configs.php" ) ;

	// Category limitation
	if( $cat_limitation ) {
		if( $cat_limit_recursive ) {
			include_once( XOOPS_ROOT_PATH."/class/xoopstree.php" ) ;
			$cattree = new XoopsTree( $GLOBALS['xoops']->db->prefix( $table_cat ) , "cid" , "pid" ) ;
			$children = $cattree->getAllChildId( $cat_limitation ) ;
			$whr_cat = "cid IN (" ;
			foreach( $children as $child ) {
				$whr_cat .= "$child," ;
			}
			$whr_cat .= "$cat_limitation)" ;
		} else {
			$whr_cat = "cid='$cat_limitation'" ;
		}
	} else {
		$whr_cat = '1' ;
	}

	$block = array() ;
	$GLOBALS['myts'] =& MyTextSanitizer::getInstance() ;
	$result = $GLOBALS['xoops']->db->query( "SELECT lid , cid , title , ext , res_x , res_y , submitter , status , date AS unixtime , hits , rating , votes , comments FROM ".$GLOBALS['xoops']->db->prefix( $table_photos )." WHERE status>0 AND $whr_cat ORDER BY unixtime DESC" , $photos_num , 0 ) ;
	$count = 1 ;
	while( $photo = $GLOBALS['xoops']->db->fetchArray( $result ) ) {
		$photo['title'] = $GLOBALS['myts']->displayTarea( $photo['title'] ) ;
		if( strlen( $photo['title'] ) >= $title_max_length ) {
			if( ! XOOPS_USE_MULTIBYTES ) {
				$photo['title'] = substr( $photo['title'] , 0 , $title_max_length - 1 ) . "..." ;
			} else if( function_exists( 'mb_strcut' ) ) {
				$photo['title'] = mb_strcut( $photo['title'] , 0 , $title_max_length - 1 ) . "..." ;
			}
		}
		$photo['suffix'] = $photo['hits'] > 1 ? 'hits' : 'hit' ;
		$photo['date'] = XoopsLocalAbstract::formatTimestamp( $photo['unixtime'] , 's' ) ;
		$photo['thumbs_url'] = $thumbs_url ;

		if( in_array( strtolower( $photo['ext'] ) , $myalbum_normal_exts ) ) {
			$width_spec = "width='$myalbum_thumbsize'" ;
			if( $myalbum_makethumb ) {
				list( $width , $height , $type ) = getimagesize( "$thumbs_dir/{$photo['lid']}.{$photo['ext']}" ) ;
				if( $width <= $myalbum_thumbsize ) 
				// if thumb images was made, 'width' and 'height' will not set.
				$width_spec = '' ;
			}
			$photo['width_spec'] = $width_spec ;
		} else {
			$photo['ext'] = 'gif' ;
			$photo['width_spec'] = '' ;
		}

		$block['photo'][$count++] = $photo ;
	}
	$block['mod_url'] = $mod_url ;
	$block['cols'] = $cols ;

	return $block ;
}


function b_myalbum_topnews_edit( $options )
{
	$GLOBALS['xoops'] = Xoops::getInstance();
	$GLOBALS['myts'] = MyTextSanitizer::getInstance();
	$GLOBALS['xoops']->load('xoopslocal');

	// For myAlbum-P < 2.70
	if( strncmp( $options[0] , 'myalbum' , 7 ) != 0 ) {
		$title_max_length = intval( $options[1] ) ;
		$photos_num = intval( $options[0] ) ;
		$mydirname = 'myalbum' ;
	} else {
		$title_max_length = intval( $options[2] ) ;
		$photos_num = intval( $options[1] ) ;
		$mydirname = $options[0] ;
	}
	$cat_limitation = empty( $options[3] ) ? 0 : intval( $options[3] ) ;
	$cat_limit_recursive = empty( $options[4] ) ? 0 : 1 ;
	$cols = empty( $options[6] ) ? 1 : intval( $options[6] ) ;

	include_once( XOOPS_ROOT_PATH."/class/xoopstree.php" ) ;
	$cattree = new XoopsTree( $GLOBALS['xoops']->db->prefix( "{$mydirname}_cat" ) , "cid" , "pid" ) ;

	ob_start() ;
	$cattree->makeMySelBox( "title" , "title" , $cat_limitation , 1 , 'options[3]' ) ;
	$catselbox = ob_get_contents() ;
	ob_end_clean() ;

	return "
		"._ALBM_TEXT_DISP." &nbsp;
		<input type='hidden' name='options[0]' value='{$mydirname}' />
		<input type='text' size='4' name='options[1]' value='$photos_num' style='text-align:right;' />
		<br />
		"._ALBM_TEXT_STRLENGTH." &nbsp;
		<input type='text' size='6' name='options[2]' value='$title_max_length' style='text-align:right;' />
		<br />
		"._ALBM_TEXT_CATLIMITATION." &nbsp; $catselbox
		"._ALBM_TEXT_CATLIMITRECURSIVE."
		<input type='radio' name='options[4]' value='1' ".($cat_limit_recursive?"checked='checked'":"")."/>"._YES."
		<input type='radio' name='options[4]' value='0' ".($cat_limit_recursive?"":"checked='checked'")."/>"._NO."
		<br />
		<input type='hidden' name='options[5]' value='' />
		"._ALBM_TEXT_COLS."&nbsp;
		<input type='text' size='2' name='options[6]' value='$cols' style='text-align:right;' />
		<br />
		\n" ;
}

}

?>