<?php

include( "header.php" ) ;

// GET variables
$cid = empty( $_GET['cid'] ) ? 0 : intval( $_GET['cid'] ) ;
$uid = empty( $_GET['uid'] ) ? 0 : intval( $_GET['uid'] ) ;
$num = empty( $_GET['num'] ) ? intval( $myalbum_perpage ) : intval( $_GET['num'] ) ;
if( $num < 1 ) $num = 10 ;
$pos = empty( $_GET['pos'] ) ? 0 : intval( $_GET['pos'] ) ;
$view = empty( $_GET['view'] ) ? $myalbum_viewcattype : $_GET['view'] ;

$photos_handler = $GLOBALS['xoops']->getModuleHandler('photos', $GLOBALS['mydirname']);
$text_handler = $GLOBALS['xoops']->getModuleHandler('text', $GLOBALS['mydirname']);
$cat_handler = $GLOBALS['xoops']->getModuleHandler('cat', $GLOBALS['mydirname']);
if ($GLOBALS['myalbumModuleConfig']['htaccess']) {
	if ($cid==0) {
		$url = XOOPS_URL.'/'.$GLOBALS['myalbumModuleConfig']['baseurl'].'/rss,'.$cid.','.$uid.','.$num.','.$pos.','.$view.$GLOBALS['myalbumModuleConfig']['endofrss'];
	} else {
		$cat = $cat_handler->get($cid);
		$url = $cat->getRSSURL($uid, $num, $pos, $view);
	}

	if (!strpos($url, $_SERVER['REQUEST_URI'])) {
		header( "HTTP/1.1 301 Moved Permanently" ); 
		header('Location: '.$url);
	}
}

$GLOBALS['xoopsLogger']->activated = false;

if (function_exists('mb_http_output')) {
    mb_http_output('pass');
}
header('Content-Type:text/xml; charset=utf-8');

$GLOBALS['xoops']->tpl->caching = 2;
$GLOBALS['xoops']->tpl->cache_lifetime = 3600;
if (!$GLOBALS['xoops']->tpl->is_cached($GLOBALS['mydirname'].'_rss.html')) {
    xoops_load('XoopsLocal');
    $GLOBALS['xoops']->tpl->assign('channel_title', XoopsLocal::convert_encoding(htmlspecialchars($xoopsConfig['sitename'].(is_object($cat)?' : '.$cat->getVar('title'). ' : '.$GLOBALS['myalbumModule']->getVar('name'):' : '.$GLOBALS['myalbumModule']->getVar('name')), ENT_QUOTES)));
    $GLOBALS['xoops']->tpl->assign('channel_link', XOOPS_URL . '/');
    $GLOBALS['xoops']->tpl->assign('channel_desc', XoopsLocal::convert_encoding(htmlspecialchars($xoopsConfig['slogan'], ENT_QUOTES)));
    $GLOBALS['xoops']->tpl->assign('channel_lastbuild', XoopsLocal::formatTimestamp(time(), 'rss'));
    $GLOBALS['xoops']->tpl->assign('channel_webmaster', checkEmail($xoopsConfig['adminmail'], true));
    $GLOBALS['xoops']->tpl->assign('channel_editor', checkEmail($xoopsConfig['adminmail'], true));
    $GLOBALS['xoops']->tpl->assign('channel_category', $GLOBALS['myalbumModule']->getVar('name'));
    $GLOBALS['xoops']->tpl->assign('channel_generator', strtoupper($GLOBALS['myalbumModule']->getVar('dirname')));
    $GLOBALS['xoops']->tpl->assign('channel_language', _LANGCODE);
    $GLOBALS['xoops']->tpl->assign('image_url', XOOPS_URL . '/images/logo.png');
    $dimension = getimagesize(XOOPS_ROOT_PATH . '/images/logo.png');
    if (empty($dimension[0])) {
        $width = 88;
    } else {
        $width = ($dimension[0] > 144) ? 144 : $dimension[0];
    }
    if (empty($dimension[1])) {
        $height = 31;
    } else {
        $height = ($dimension[1] > 400) ? 400 : $dimension[1];
    }
    $GLOBALS['xoops']->tpl->assign('image_width', $width);
    $GLOBALS['xoops']->tpl->assign('image_height', $height);
    include( XOOPS_ROOT_PATH."/modules/$mydirname/include/photo_orders.php" ) ;
	if( isset( $_GET['orderby'] ) && isset( $myalbum_orders[ $_GET['orderby'] ] ) ) $orderby = $_GET['orderby'] ;
	else if( isset( $myalbum_orders[ $myalbum_defaultorder ] ) ) $orderby = $myalbum_defaultorder ;
	else $orderby = 'titleA' ;
	
	if( $cid > 0 ) {
		
		$cat = $cat_handler->get($cid);
		foreach($GLOBALS['cattree']->getAllChild( $cid ) as $index => $child) {
			$cids[$child->getVar('cid')] = $child->getVar('cid');
		}
		array_push( $cids , $cid ) ;
		$criteria = new CriteriaCompo(new Criteria('`status`', '0', '>'));
		$photo_total_sum = myalbum_get_photo_total_sum_from_cats( $cids , $criteria ) ;
		$criteria->add(new Criteria('`cid`', $cid));
		
	} else if( $uid != 0 ) {
	
		// This means 'my photo'
		if( $uid < 0 ) {
			$criteria = new CriteriaCompo(new Criteria('`status`', '0', '>'));
		} else {
			$criteria = new CriteriaCompo(new Criteria('`status`', '0', '>'));
			$criteria->add(new Criteria('`submitter`', $uid));
		}
	
	} else {
		$criteria = new CriteriaCompo(new Criteria('`status`', '0', '>'));
	}
	
	$criteria->setOrder($myalbum_orders[$orderby][0]);
	$criteria->setStart($pos);
	$criteria->setLimit($num);

	// Display photos
	foreach( $photos_handler->getObjects($criteria, true) as $lid => $photo ) {
		$text = $text_handler->get($lid);
		$cat = $cat_handler->get($photo->getVar('cid'));
    	$GLOBALS['xoops']->tpl->append('items', array(
        	'title' => XoopsLocal::convert_encoding(htmlspecialchars($photo->getVar('title'), ENT_QUOTES)) ,
        	'category' => XoopsLocal::convert_encoding(htmlspecialchars($cat->getVar('title'), ENT_QUOTES)) ,
            'link' => XoopsLocal::convert_encoding(htmlspecialchars($photo->getURL())) ,
            'guid' => XoopsLocal::convert_encoding(htmlspecialchars($photo->getURL())) ,
            'pubdate' => formatTimestamp($photo->getVar('date'), 'rss') ,
            'description' => XoopsLocal::convert_encoding(htmlspecialchars(sprintf(_ALBM_RSS_DESC, $photo->getThumbsURL(), $GLOBALS['myts']->displayTarea($text->getVar('description'), 1, 1, 1, 1, 1, 1)), ENT_QUOTES))));
    }

}
$GLOBALS['xoops']->tpl->display($GLOBALS['xoops']->path('/modules/'.$GLOBALS["mydirname"].'/templates/'.$GLOBALS["mydirname"].'_rss.html'));
?>