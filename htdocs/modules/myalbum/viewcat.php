<?php
// ------------------------------------------------------------------------- //
//                      myAlbum-P - XOOPS photo album                        //
//                        <http://www.peak.ne.jp/>                           //
// ------------------------------------------------------------------------- //

include( "header.php" ) ;

// GET variables
$cid = !isset( $_GET['cid'] ) ? 0 : intval( $_GET['cid'] ) ;
$uid = !isset( $_GET['uid'] ) ? 0 : intval( $_GET['uid'] ) ;
$num = !isset( $_GET['num'] ) ? intval( $myalbum_perpage ) : intval( $_GET['num'] ) ;
if( $num < 1 ) $num = 10 ;
$pos = !isset( $_GET['pos'] ) ? 0 : intval( $_GET['pos'] ) ;
$view = !isset( $_GET['view'] ) ? $myalbum_viewcattype : $_GET['view'] ;

$photos_handler = $GLOBALS['xoops']->getModuleHandler('photos', $GLOBALS['mydirname']);
$cat_handler = $GLOBALS['xoops']->getModuleHandler('cat', $GLOBALS['mydirname']);
if ($GLOBALS['myalbumModuleConfig']['htaccess']) {
	if ($cid==0) {
		$url = XOOPS_URL.'/'.$GLOBALS['myalbumModuleConfig']['baseurl'].'/cat,'.$cid.','.$uid.','.$num.','.$pos.','.$view.'.html';
	} else {
		$cat = $cat_handler->get($cid);
		$url = $cat->getURL($uid, $num, $pos, $view);
	}

	if (!strpos($url, $_SERVER['REQUEST_URI'])) {
		header( "HTTP/1.1 301 Moved Permanently" ); 
		header('Location: '.$url);
		exit(0);
	}
}

// Orders
include( XOOPS_ROOT_PATH."/modules/$mydirname/include/photo_orders.php" ) ;
if( isset( $_GET['orderby'] ) && isset( $myalbum_orders[ $_GET['orderby'] ] ) ) $orderby = $_GET['orderby'] ;
else if( isset( $myalbum_orders[ $myalbum_defaultorder ] ) ) $orderby = $myalbum_defaultorder ;
else $orderby = 'titleA' ;


if( $view == 'table' ) {
	$template  = 'module:'.$GLOBALS["mydirname"].'|'.$GLOBALS["mydirname"]."_viewcat_table.html" ;
	$function_assigning = 'myalbum_get_array_for_photo_assign_light' ;
} else {
	$template  = 'module:'.$GLOBALS["mydirname"].'|'.$GLOBALS["mydirname"]."_viewcat_list.html" ;
	$function_assigning = 'myalbum_get_array_for_photo_assign' ;
}

$GLOBALS['xoops']->header($template);

include( 'include/assign_globals.php' ) ;
foreach($GLOBALS['myalbum_assign_globals'] as $key => $value) {
	$GLOBALS['xoops']->tpl->assign($key, $value);
}

if( $global_perms & GPERM_INSERTABLE ) $GLOBALS['xoops']->tpl->assign( 'lang_add_photo' , _ALBM_ADDPHOTO ) ;

$GLOBALS['xoops']->tpl->assign( 'lang_album_main' , _ALBM_MAIN ) ;

if( $cid > 0 ) {
	
	$cat = $cat_handler->get($cid);
	// Category Specified
	$GLOBALS['xoops']->tpl->assign( 'category_id' , $cid ) ;
	$GLOBALS['xoops']->tpl->assign( 'subcategories' , myalbum_get_sub_categories( $cid , $GLOBALS['cattree'] ) ) ;	$GLOBALS['xoops']->tpl->assign( 'category_options' , myalbum_get_cat_options() ) ;
	
	foreach($GLOBALS['cattree']->getAllChild( $cid ) as $child) {
		$cids[$child->getVar('cid')] = $child->getVar('cid');
	}
	array_push( $cids , $cid ) ;
	$criteria = new CriteriaCompo(new Criteria('`status`', '0', '>'));
	$photo_total_sum = myalbum_get_photo_total_sum_from_cats( $cids , $criteria ) ;
	if (!empty($cids)) {
		foreach($cids as $index => $child) {
			$childcat = $cat_handler->get($child);
			if (is_object($childcat))
				$catpath .= "<a href='".XOOPS_URL.'/modules/'.$GLOBALS['mydirname'].'/viewcat.php?num=' . intval( $GLOBALS['myalbum_perpage'] ) . '&cid='.$childcat->getVar('cid')."' >".$childcat->getVar('title').'</a> '.($index<sizeof($cids)?'>>':'');
		} 
	} else {
		$cat = $cat_handler->get($cid);
		$catpath .= "<a href='".XOOPS_URL.'/modules/'.$GLOBALS['mydirname'].'/viewcat.php?num=' . intval( $GLOBALS['myalbum_perpage'] ) . '&cid='.$cat->getVar('cid')."' >".$cat->getVar('title').'</a>';
	} 
	$catpath = str_replace( ">>" , " <span class='fg2'>&raquo;&raquo;</span> " , $catpath ) ;
	$sub_title = preg_replace( "/\'\>/" , "'><img src='$mod_url/images/folder16.gif' alt='' />" , $catpath ) ;
	$sub_title = preg_replace( "/^(.+)folder16/" , '$1folder_open' , $sub_title ) ;
	$GLOBALS['xoops']->tpl->assign( 'album_sub_title' , $sub_title ) ;
	$criteria->add(new Criteria('`cid`', $cid));
	
} else if( $uid != 0 ) {
	
	// This means 'my photo'
	if( $uid < 0 ) {
		$criteria = new CriteriaCompo(new Criteria('`status`', '0', '>'));
		$GLOBALS['xoops']->tpl->assign( 'uid' , -1 ) ;
		$GLOBALS['xoops']->tpl->assign( 'album_sub_title' , _ALBM_TEXT_SMNAME4 ) ;
	// uid Specified
	} else {
		$criteria = new CriteriaCompo(new Criteria('`status`', '0', '>'));
		$criteria->add(new Criteria('`submitter`', $uid));
		$GLOBALS['xoops']->tpl->assign( 'uid' , $uid ) ;
		$GLOBALS['xoops']->tpl->assign( 'album_sub_title' , "<img src='$mod_url/images/myphotos.gif' alt='' />" . myalbum_get_name_from_uid( $uid ) ) ;
	}
	
} else {
	$criteria = new CriteriaCompo(new Criteria('`status`', '0', '>'));
	$GLOBALS['xoops']->tpl->assign( 'album_sub_title' , 'error: category id not specified' ) ;
}

if (!is_object($cat)) {
	$cat = $cat_handler->create();
}
$GLOBALS['xoops']->tpl->assign( 'rss' , $cat->getRSSURL($uid, $num, $pos, $view) ) ;
$GLOBALS['xoops']->tpl->assign( 'xoConfig', $GLOBALS['myalbumModuleConfig'] ) ;
$GLOBALS['xoops']->tpl->assign( 'mydirname', $GLOBALS['mydirname'] ) ;

$photo_small_sum = $photos_handler->getCount( $criteria ) ;
$photo_total_sum = $photos_handler->getCount( new Criteria('`status`', '0', '>') );
$GLOBALS['xoops']->tpl->assign( 'photo_small_sum' , $photo_small_sum ) ;
$GLOBALS['xoops']->tpl->assign( 'photo_total_sum' , ( empty( $photo_total_sum ) ? $photo_small_sum : $photo_total_sum ) ) ;
$criteria->setOrder($myalbum_orders[$orderby][0]);
$criteria->setStart($pos);
$criteria->setLimit($num);

if( $photo_small_sum > 0 ) {
	//if 2 or more items in result, num the navigation menu
	if( $photo_small_sum > 1 ) {
		
		// Assign navigations like order and division
		$GLOBALS['xoops']->tpl->assign( 'show_nav' ,  true ) ;
		$GLOBALS['xoops']->tpl->assign( 'lang_sortby' , _ALBM_SORTBY ) ;
		$GLOBALS['xoops']->tpl->assign( 'lang_title' , _ALBM_TITLE ) ;
		$GLOBALS['xoops']->tpl->assign( 'lang_date' , _ALBM_DATE ) ;
		$GLOBALS['xoops']->tpl->assign( 'lang_rating' , _ALBM_RATING ) ;
		$GLOBALS['xoops']->tpl->assign( 'lang_popularity' , _ALBM_POPULARITY ) ;
		$GLOBALS['xoops']->tpl->assign( 'lang_cursortedby' , sprintf( _ALBM_CURSORTEDBY , $myalbum_orders[$orderby][1] ) ) ;
		
		$nav = new XoopsPageNav( $photo_small_sum , $num , $pos , 'pos' , "$get_append&num=$num&orderby=$orderby" ) ;
		$nav_html = $nav->renderNav( 10 ) ;
		
		$last = $pos + $num ;
		if( $last > $photo_small_sum ) $last = $photo_small_sum ;
		$photonavinfo = sprintf( _ALBM_AM_PHOTONAVINFO , $pos + 1 , $last , $photo_small_sum ) ;
		$GLOBALS['xoops']->tpl->assign( 'photonav' , $nav_html ) ;
		$GLOBALS['xoops']->tpl->assign( 'photonavinfo' , $photonavinfo ) ;
		
	}
	// Display photos
	$count = 1 ;
	
	foreach( $photos_handler->getObjects($criteria, true) as $lid => $photo ) {
		//echo __LINE__.' - '.$function_assigning.' - '.$lid.'<br/>';
		$photo = $function_assigning( $photo ) + array( 'count' => $count ++ , true ) ;
		$GLOBALS['xoops']->tpl->append( 'photos' , $photo ) ;
	}
}

$GLOBALS['xoops']->footer();

?>