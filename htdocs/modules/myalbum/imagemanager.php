<?php
if( ! defined( 'XOOPS_ROOT_PATH' ) ) {
	require( dirname(__FILE__).'/header.php' ) ;
} else {
	// when this script is included by core's imagemanager.php
	$mydirname = basename( dirname( __FILE__ ) ) ;
	include(XOOPS_ROOT_PATH."/modules/$mydirname/include/read_configs.php");
}

include(XOOPS_ROOT_PATH."/modules/$mydirname/include/get_perms.php");

// Get variables
if( empty( $_GET['target'] ) ) exit ;
$num = empty( $_GET['num'] ) ? 10 : intval( $_GET['num'] ) ;
$cid = !isset($_GET['cid']) ? 0 : intval($_GET['cid']);

$GLOBALS['xoops']->header();
$GLOBALS['xoops']->tpl->assign('lang_imgmanager', _IMGMANAGER);
$GLOBALS['xoops']->tpl->assign('sitename', $xoopsConfig['sitename']);
$target = htmlspecialchars($_GET['target'], ENT_QUOTES);
$GLOBALS['xoops']->tpl->assign('target', $target);
$GLOBALS['xoops']->tpl->assign('mod_url', $mod_url);
$GLOBALS['xoops']->tpl->assign('cid', $cid);
$GLOBALS['xoops']->tpl->assign('can_add', ( $global_perms & GPERM_INSERTABLE ) && $cid );
$cats = $GLOBALS['cattree']->getChildTreeArray( 0 , 'title' ) ;
$GLOBALS['xoops']->tpl->assign('makethumb', $myalbum_makethumb);
$GLOBALS['xoops']->tpl->assign('lang_imagesize', _ALBM_CAPTION_IMAGEXYT);
$GLOBALS['xoops']->tpl->assign('lang_align', _ALIGN);
$GLOBALS['xoops']->tpl->assign('lang_add', _ADD);
$GLOBALS['xoops']->tpl->assign('lang_close', _CLOSE);
$GLOBALS['xoops']->tpl->assign('lang_left', _LEFT);
$GLOBALS['xoops']->tpl->assign('lang_center', _CENTER);
$GLOBALS['xoops']->tpl->assign('lang_right', _RIGHT);

if( sizeof( $cats ) > 0 ) {
	$GLOBALS['xoops']->tpl->assign('lang_refresh', _ALBM_CAPTION_REFRESH);

	// WHERE clause for ext
	// $whr_ext = "ext IN ('" . implode( "','" , $myalbum_normal_exts ) . "')" ;
	$whr_ext = '1' ;
	$select_is_normal = "ext IN ('" . implode( "','" , $myalbum_normal_exts ) . "')" ;

	// select box for category
	$cat_options = "<option value='0'>--</option>\n" ;
	$prs = $GLOBALS['xoops']->db->query( "SELECT cid,COUNT(lid) FROM $table_photos WHERE status>0 AND $whr_ext GROUP BY cid" ) ;
	$photo_counts = array() ;
	while( list( $c , $p ) = $GLOBALS['xoops']->db->fetchRow( $prs ) ) {
		$photo_counts[ $c ] = $p ;
	}
	foreach( $cats as $cat ) {
		$prefix = str_replace( '.' , '--' , substr( $cat['prefix'] , 1 ) ) ;
		$photo_count = isset( $photo_counts[ $cat['cid'] ] ) ? $photo_counts[ $cat['cid'] ] : 0 ;
		if( $cid == $cat['cid'] ) $cat_options .= "<option value='{$cat['cid']}' selected='selected'>$prefix{$cat['title']} ($photo_count)</option>\n" ;
		else $cat_options .= "<option value='{$cat['cid']}'>$prefix{$cat['title']} ($photo_count)</option>\n" ;
	}
	$GLOBALS['xoops']->tpl->assign('cat_options', $cat_options);

	if( $cid > 0 ) {

		$GLOBALS['xoops']->tpl->assign('lang_addimage', _ADDIMAGE);

		$rs = $GLOBALS['xoops']->db->query( "SELECT COUNT(*) FROM $table_photos WHERE cid='$cid' AND status>0 AND $whr_ext") ;
		list( $total ) = $GLOBALS['xoops']->db->fetchRow( $rs ) ;
		if ($total > 0) {
			$start = empty( $_GET['start'] ) ? 0 : intval( $_GET['start'] ) ;
			$prs = $GLOBALS['xoops']->db->query( "SELECT lid,cid,title,ext,submitter,res_x,res_y,$select_is_normal AS is_normal FROM $table_photos WHERE cid='$cid' AND status>0 AND $whr_ext ORDER BY date DESC LIMIT $start,$num" ) ;
			$GLOBALS['xoops']->tpl->assign('image_total', $total);
			$GLOBALS['xoops']->tpl->assign('lang_image', _IMAGE);
			$GLOBALS['xoops']->tpl->assign('lang_imagename', _IMAGENAME);

			if( $total > $num ) {
				$nav = new XoopsPageNav( $total , $num , $start , 'start' , "target=$target&amp;cid=$cid&amp;num=$num" ) ;
				$GLOBALS['xoops']->tpl->assign( 'pagenav' , $nav->renderNav() ) ;
			}

			// use [siteimg] or [img]
			if( empty( $myalbum_usesiteimg ) ) {
				// using links with XOOPS_URL
				$img_tag = 'img' ;
				$url_tag = 'url' ;
				$pdir = $photos_url ;
				$tdir = $thumbs_url ;
			} else {
				// using links without XOOPS_URL
				$img_tag = 'siteimg' ;
				$url_tag = 'siteurl' ;
				$pdir = substr( $myalbum_photospath , 1 ) ;
				$tdir = substr( $myalbum_thumbspath , 1 ) ;
			}

			$i = 1 ;
			while( list( $lid , $cid , $title , $ext , $submitter , $res_x , $res_y , $is_normal ) = $GLOBALS['xoops']->db->fetchRow( $prs ) ) {

				// Width of thumb
				if( ! $is_normal ) {
					$width_spec = '' ;
					$image_ext = 'gif' ;
				} else {
					$width_spec = "width='$myalbum_thumbsize'" ;
					$image_ext = $ext ;
					if( $myalbum_makethumb ) {
						list( $width , $height , $type ) = getimagesize( "$thumbs_dir/$lid.$ext" ) ;
						if( $width <= $myalbum_thumbsize ) $width_spec = '' ;
					}
				}

				$xcodel = "[$url_tag=$pdir/{$lid}.{$ext}][$img_tag align=left]$tdir/{$lid}.{$image_ext}[/$img_tag][/$url_tag]";
				$xcodec = "[$url_tag=$pdir/{$lid}.{$ext}][$img_tag]$tdir/{$lid}.{$image_ext}[/$img_tag][/$url_tag]";
				$xcoder = "[$url_tag=$pdir/{$lid}.{$ext}][$img_tag align=right]$tdir/{$lid}.{$image_ext}[/$img_tag][/$url_tag]";
				$xcodebl = "[$img_tag align=left]$pdir/{$lid}.{$ext}[/$img_tag]";
				$xcodebc = "[$img_tag]$pdir/{$lid}.{$ext}[/$img_tag]";
				$xcodebr = "[$img_tag align=right]$pdir/{$lid}.{$ext}[/$img_tag]";
				$GLOBALS['xoops']->tpl->append( 'photos' , array(
					'lid' => $lid ,
					'ext' => $ext ,
					'res_x' => $res_x ,
					'res_y' => $res_y ,
					'nicename' => $GLOBALS['myts']->htmlSpecialChars( $title ) ,
					'src' => "$thumbs_url/{$lid}.{$image_ext}" ,
					'can_edit' => ( ( $global_perms & GPERM_EDITABLE ) && ( $my_uid == $submitter || $isadmin ) ) ,
					'width_spec' => $width_spec ,
					'xcodel' => $xcodel ,
					'xcodec' => $xcodec ,
					'xcoder' => $xcoder ,
					'xcodebl' => $xcodebl ,
					'xcodebc' => $xcodebc ,
					'xcodebr' => $xcodebr ,
					'is_normal' => $is_normal ,
					'count' => $i ++
				) ) ;
			}

		} else {
			$GLOBALS['xoops']->tpl->assign('image_total', 0);
		}
	}
	$GLOBALS['xoops']->tpl->assign('xsize', 600);
	$GLOBALS['xoops']->tpl->assign('ysize', 400);
} else {
	$GLOBALS['xoops']->tpl->assign('xsize', 400);
	$GLOBALS['xoops']->tpl->assign('ysize', 180);
}

$GLOBALS['xoops']->tpl->display( "{$mydirname}_imagemanager.html" ) ;
$GLOBALS['xoops']->footer();

?>