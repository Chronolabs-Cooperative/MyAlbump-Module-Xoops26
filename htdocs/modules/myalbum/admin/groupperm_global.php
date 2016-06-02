<?php
include_once( 'admin_header.php' ) ;
include_once( 'mygrouppermform.php' ) ;



if( ! empty( $_POST['submit'] ) ) {
	include( "mygroupperm.php" ) ;
	$GLOBALS['xoops']->redirect( XOOPS_URL."/modules/".$xoopsModule->dirname()."/admin/groupperm_global.php" , 1 , _AM_ALBM_GPERMUPDATED );
}

$GLOBALS['xoops']->header();
$indexAdmin = new XoopsModuleAdmin();
$indexAdmin->renderNavigation(basename(__FILE__));

$GLOBALS['xoops']->tpl->assign('admin_title', $GLOBALS['myalbumModule']->name());
$GLOBALS['xoops']->tpl->assign('mydirname', $GLOBALS['mydirname']);
$GLOBALS['xoops']->tpl->assign('photos_url', $GLOBALS['photos_url']);
$GLOBALS['xoops']->tpl->assign('thumbs_url', $GLOBALS['thumbs_url']);
$GLOBALS['xoops']->tpl->assign('form', myalbum_admin_form_groups());
if (isset($result_str))
	$GLOBALS['xoops']->tpl->assign('result_str', $result_str);

$GLOBALS['xoops']->tpl->display('admin:'.$GLOBALS['mydirname'].'|'.$GLOBALS['mydirname'].'_cpanel_permissions.html');

$GLOBALS['xoops']->footer();


?>