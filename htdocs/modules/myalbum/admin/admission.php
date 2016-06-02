<?php
// ------------------------------------------------------------------------- //
//                      myAlbum-P - XOOPS photo album                        //
//                        <http://www.peak.ne.jp/>                           //
// ------------------------------------------------------------------------- //

include( "admin_header.php" ) ;

// GET vars
$pos = empty( $_GET[ 'pos' ] ) ? 0 : intval( $_GET[ 'pos' ] ) ;
$num = empty( $_GET[ 'num' ] ) ? 20 : intval( $_GET[ 'num' ] ) ;
$txt = empty( $_GET[ 'txt' ] ) ? '' : $GLOBALS['myts']->stripSlashesGPC( trim( $_GET[ 'txt' ] ) ) ;


if( ! empty( $_POST['action'] ) && $_POST['action'] == 'admit' && isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ) {

	$photosHandler = $GLOBALS['xoops']->getModuleHandler('photos', $GLOBALS['mydirname']);
	@$photosHandler->setStatus($_POST[ 'ids' ], 1);
	$GLOBALS['xoops']->redirect( 'admission.php' , 2 , _ALBM_AM_ADMITTING ) ;
	exit ;

} else if( ! empty( $_POST['action'] ) && $_POST['action'] == 'delete' && isset( $_POST['ids'] ) && is_array( $_POST['ids'] ) ) {

	// remove records

	// Double check for anti-CSRF
	if( ! xoops_refcheck() ) die( "XOOPS_URL is not included in your REFERER" ) ;

	$photosHandler = $GLOBALS['xoops']->getModuleHandler('photos', $GLOBALS['mydirname']);
	@$photosHandler->deletePhotos($_POST[ 'ids' ]);
	
	$GLOBALS['xoops']->redirect( "admission.php" , 2 , _ALBM_DELETINGPHOTO ) ;
	exit ;
}

$photosHandler = $GLOBALS['xoops']->getModuleHandler('photos');

// extracting by free word
$criteria = new CriteriaCompo(new Criteria('`status`', '0', '<='));
if( $txt != "" ) {
	$keywords = explode( " " , $txt ) ;
	foreach( $keywords as $keyword ) {
		$criteria->add(new Criteria('CONCAT( l.title , l.ext )', '%'.$keyword.'%', 'LIKE'), "AND");
	}
}
$GLOBALS['xoops']->header();
$moduleAdmin = new XoopsModuleAdmin();
$moduleAdmin->renderNavigation(basename(__FILE__));
$GLOBALS['xoops']->tpl->assign('admin_title', sprintf(_AM_H3_FMT_ADMISSION,$xoopsModule->name()));
$GLOBALS['xoops']->tpl->assign('mydirname', $GLOBALS['mydirname']);
$GLOBALS['xoops']->tpl->assign('photos_url', $GLOBALS['photos_url']);
$GLOBALS['xoops']->tpl->assign('thumbs_url', $GLOBALS['thumbs_url']);
$GLOBALS['xoops']->tpl->assign('txt', $txt);
$GLOBALS['xoops']->tpl->assign('num', $num);
$GLOBALS['xoops']->tpl->assign('pos', $pos);

// query for listing count
$numrows = $photosHandler->getCount($criteria);
$nav = new XoopsPageNav( $numrows , $num , $pos , 'pos' , "num=$num&txt=" . urlencode($txt) ) ;
$GLOBALS['xoops']->tpl->assign('nav_html', $nav->renderNav( 10 ));

foreach( $photosHandler->getObjects($criteria, true) as $lid => $photo) {
	$GLOBALS['xoops']->tpl->append('photos', $photo->toArray());
}


$GLOBALS['xoops']->tpl->display('admin:'.$GLOBALS['mydirname'].'|'.$GLOBALS['mydirname'].'_cpanel_admission.html');

// check $xoopsModule
$GLOBALS['xoops']->footer();
?>
