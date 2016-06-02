<?php
// ------------------------------------------------------------------------- //
//                      myAlbum-P - XOOPS photo album                        //
//                        <http://www.peak.ne.jp/>                           //
// ------------------------------------------------------------------------- //

include( "header.php" ) ;

// GET variables
$lid = empty( $_GET['lid'] ) ? 0 : intval( $_GET['lid'] ) ;
$cid = empty( $_GET['cid'] ) ? 0 : intval( $_GET['cid'] ) ;

myalbum_updaterating( $lid ) ;

$photos_handler = $GLOBALS['xoops']->getModuleHandler('photos', $GLOBALS['mydirname']);
$cat_handler = $GLOBALS['xoops']->getModuleHandler('cat', $GLOBALS['mydirname']);

if (!is_object($photo_obj = $photos_handler->get($lid))) {
	$GLOBALS['xoops']->redirect( "index.php" , 2 , _ALBM_NOMATCH ) ;
	exit ;
}

if (!strpos($photo_obj->getURL(), $_SERVER['REQUEST_URI'])) {
	header( "HTTP/1.1 301 Moved Permanently" ); 
	header('Location: '.$photo_obj->getURL());
	exit(0);
}

$cat = $cat_handler->get($photo_obj->getVar('cid'));

$GLOBALS['xoops']->header('module:'.$GLOBALS["mydirname"].'|'.$GLOBALS["mydirname"]."_photo.html");

if( $global_perms & GPERM_INSERTABLE ) $GLOBALS['xoops']->tpl->assign( 'lang_add_photo' , _ALBM_ADDPHOTO ) ;
$GLOBALS['xoops']->tpl->assign( 'lang_album_main' , _ALBM_MAIN ) ;
include( 'include/assign_globals.php' ) ;
foreach($GLOBALS['myalbum_assign_globals'] as $key => $value) {
	$GLOBALS['xoops']->tpl->assign($key, $value);
}

if( $photo_obj->getVar('status')<1 ) {
	$GLOBALS['xoops']->redirect( $mod_url , 3 , _ALBM_NOMATCH ) ;
	exit ;
}

// update hit count
$photo_obj->increaseHits(1);

$photo = myalbum_get_array_for_photo_assign( $photo_obj ) ;

// Middle size calculation
$photo['width_height'] = '' ;
list( $max_w , $max_h ) = explode( 'x' , $myalbum_middlepixel ) ;
if( ! empty( $max_w ) && ! empty( $p['res_x'] ) ) {
	if( empty( $max_h ) ) $max_h = $max_w ;
	if( $max_h / $max_w > $p['res_y'] / $p['res_x'] ) {
		if( $p['res_x'] > $max_w ) $photo['width_height'] = "width='$max_w'" ;
	} else {
		if( $p['res_y'] > $max_h ) $photo['width_height'] = "height='$max_h'" ;
	}
}

$GLOBALS['xoops']->tpl->assign_by_ref( 'photo' , $photo ) ;

// Category Information

$GLOBALS['xoops']->tpl->assign( 'category_id' , $cid ) ;
$cids = $GLOBALS['cattree']->getAllChild( $cid ) ;
if (!empty($cids)) {
	foreach($cids as $index => $child) {
		$catpath .= "<a href='".XOOPS_URL.'/modules/'.$GLOBALS['mydirname'].'/viewcat.php?num=' . intval( $GLOBALS['myalbum_perpage'] ) . '&cid='.$child->getVar('cid')."' >".$child->getVar('title').'</a> '.($index<sizeof($cids)?'>>':'');
	} 
} else {
	$cat = $cat_handler->get($photo_obj->getVar('cid'));
	$catpath .= "<a href='".XOOPS_URL.'/modules/'.$GLOBALS['mydirname'].'/viewcat.php?num=' . intval( $GLOBALS['myalbum_perpage'] ) . '&cid='.$cat->getVar('cid')."' >".$cat->getVar('title').'</a>';
} 
$catpath = str_replace( ">>" , " <span class='fg2'>&raquo;&raquo;</span> " , $catpath ) ;		
$sub_title = preg_replace( "/\'\>/" , "'><img src='$mod_url/images/folder16.gif' alt='' />" ,  $catpath ) ;
$sub_title = preg_replace( "/^(.+)folder16/" , '$1folder_open' , $sub_title ) ;
$GLOBALS['xoops']->tpl->assign( 'album_sub_title' , $sub_title ) ;

// Orders
include( XOOPS_ROOT_PATH."/modules/$mydirname/include/photo_orders.php" ) ;
if( isset( $_GET['orderby'] ) && isset( $myalbum_orders[ $_GET['orderby'] ] ) ) $orderby = $_GET['orderby'] ;
else if( isset( $myalbum_orders[ $myalbum_defaultorder ] ) ) $orderby = $myalbum_defaultorder ;
else $orderby = 'lidA' ;

$criteria = new CriteriaCompo(new Criteria('`status`', '0', '>'));
$criteria->add(new Criteria('cid', $photo_obj->getVar('cid')));
$criteria->setOrder($myalbum_orders[$orderby][0]);
// create category navigation
$ids = array() ;
foreach( $photos_handler->getObjects($criteria, true ) as $id => $pht ) {
	$ids[] = $id ;
}

$photo_nav = "" ;
$numrows = count( $ids ) ;
$pos = array_search( $lid , $ids ) ;
if( $numrows > 1 ) {
	// prev mark
	if( $ids[0] != $lid ) {
		$photo_nav .= "<a href='photo.php?lid=".$ids[0]."'><b>[&lt; </b></a>&nbsp;&nbsp;";
		$photo_nav .= "<a href='photo.php?lid=".$ids[$pos-1]."'><b>"._ALBM_PREVIOUS."</b></a>&nbsp;&nbsp;";
	    
	}
	
	$nwin = 7 ;
	if( $numrows > $nwin ) { // window
		if( $pos > $nwin / 2 ) {
			if( $pos > round( $numrows - ( $nwin / 2 ) - 1 ) ) {
				$start = $numrows - $nwin + 1 ;
			} else {
				$start = round( $pos - ( $nwin / 2 ) ) + 1 ;
			}
		} else {
			$start = 1 ;
		}
	} else {
		$start = 1 ;
	}
	
	for( $i = $start; $i < $numrows + 1 && $i < $start + $nwin ; $i++ ) {
		if( $ids[$i-1] == $lid ) {
			$photo_nav .= "$i&nbsp;&nbsp;";
		} else {
			$photo_nav .= "<a href='photo.php?lid=".$ids[$i-1]."'>$i</a>&nbsp;&nbsp;";
		}
	}
	
	// next mark
	if( $ids[$numrows-1] != $lid ) {
		$photo_nav .= "<a href='photo.php?lid=".$ids[$pos+1]."'><b>"._ALBM_NEXT."</b></a>&nbsp;&nbsp;" ;
		$photo_nav .= "<a href='photo.php?lid=".$ids[$numrows-1]."'><b> &gt;]</b></a>" ;
	}
}

$GLOBALS['xoops']->tpl->assign( 'photo_nav' , $photo_nav ) ;
$GLOBALS['xoops']->tpl->assign( 'xoops_pagetitle' , $photo_obj->getVar('title') .' : '. $cat->getVar('title') .' : '.$GLOBALS['xoopsModule']->getVar('name') ) ;

// comments

include XOOPS_ROOT_PATH.'/include/comment_view.php';

$GLOBALS['xoops']->footer();

?>