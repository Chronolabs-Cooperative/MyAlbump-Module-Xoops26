<?php
// ------------------------------------------------------------------------- //
//                      myAlbum-P - XOOPS photo album                        //
//                        <http://www.peak.ne.jp/>                           //
// ------------------------------------------------------------------------- //

include("header.php");

$cat_handler = $GLOBALS['xoops']->getModuleHandler('cat', $GLOBALS['mydirname']);
$photos_handler = $GLOBALS['xoops']->getModuleHandler('photos', $GLOBALS['mydirname']);

$num = empty( $_GET['num'] ) ? $myalbum_newphotos : intval( $_GET['num'] ) ;
$pos = empty( $_GET['pos'] ) ? 0 : intval( $_GET['pos'] ) ;

if ($GLOBALS['myalbumModuleConfig']['htaccess']) {
	$url = XOOPS_URL.'/'.$GLOBALS['myalbumModuleConfig']['baseurl'].'/index,'.$num.','.$pos.$GLOBALS['myalbumModuleConfig']['endofurl'];
	if (!strpos($url, $_SERVER['REQUEST_URI'])) {
		header( "HTTP/1.1 301 Moved Permanently" ); 
		header('Location: '.$url);
		exit;
	}
}

$GLOBALS['xoops']->header('module:'.$GLOBALS["mydirname"].'|'.$GLOBALS["mydirname"]."_index.html");

if (!is_object($cat)) {
	$cat = $cat_handler->create();
}
$GLOBALS['xoops']->tpl->assign( 'rss' , $cat->getRSSURL(0, $num, $pos, $myalbum_viewcattype) ) ;
$GLOBALS['xoops']->tpl->assign( 'xoConfig', $GLOBALS['myalbumModuleConfig'] ) ;
$GLOBALS['xoops']->tpl->assign( 'mydirname', $GLOBALS['mydirname'] ) ;

include( 'include/assign_globals.php' ) ;
foreach($GLOBALS['myalbum_assign_globals'] as $key => $value) {
	$GLOBALS['xoops']->tpl->assign($key, $value);
}

$GLOBALS['xoops']->tpl->assign( 'subcategories' , myalbum_get_sub_categories( 0 , $GLOBALS['cattree'] ) ) ;

$GLOBALS['xoops']->tpl->assign( 'category_options' , myalbum_get_cat_options() ) ;

$criteria = new Criteria('`status`', '0', '>');
$photo_num_total = $photos_handler->getCount($criteria);

$GLOBALS['xoops']->tpl->assign( 'photo_global_sum' , sprintf( _ALBM_THEREARE , $photo_num_total ) ) ;
if( $global_perms & GPERM_INSERTABLE ) $GLOBALS['xoops']->tpl->assign( 'lang_add_photo' , _ALBM_ADDPHOTO ) ;

// Navigation

if( $num < 1 ) $num = $myalbum_newphotos ;
if( $pos >= $photo_num_total ) $pos = 0 ;
if( $photo_num_total > $num ) {
	$nav = new XoopsPageNav( $photo_num_total , $num , $pos , 'pos' , "num=$num" ) ;
	$nav_html = $nav->renderNav( 10 ) ;
	$last = $pos + $num ;
	if( $last > $photo_num_total ) $last = $photo_num_total ;
	$photonavinfo = sprintf( _ALBM_AM_PHOTONAVINFO , $pos + 1 , $last , $photo_num_total ) ;
	$GLOBALS['xoops']->tpl->assign( 'photonavdisp' , true ) ;
	$GLOBALS['xoops']->tpl->assign( 'photonav' , $nav_html ) ;
	$GLOBALS['xoops']->tpl->assign( 'photonavinfo' , $photonavinfo ) ;
} else {
	$GLOBALS['xoops']->tpl->assign( 'photonavdisp' , false ) ;
}

$criteria = new Criteria('`status`', '0', '>');
$criteria->setStart($pos);
$criteria->setLimit($num);
$criteria->setSort('`title`');
$criteria->setOrder('DESC');
// Assign Latest Photos
foreach($photos_handler->getObjects($criteria, true) as $lid => $photo)	{
	$GLOBALS['xoops']->tpl->append_by_ref( 'photos' , myalbum_get_array_for_photo_assign( $photo , true ) ) ;
}

$GLOBALS['xoops']->footer();

?>