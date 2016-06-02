<?php
	require_once (dirname(dirname(dirname(dirname(__FILE__)))).'/include/cp_header.php');
	require_once (dirname(dirname(__FILE__))).'/include/functions.php';
	require_once (dirname(dirname(__FILE__))).'/include/read_configs.php';
	
	$GLOBALS['xoops'] = Xoops::getInstance();
	
	if (!defined('_CHARSET'))
		define("_CHARSET","UTF-8");
	if (!defined('_CHARSET_ISO'))
		define("_CHARSET_ISO","ISO-8859-1");
		
	$GLOBALS['myts'] = MyTextSanitizer::getInstance();
	
	$module_handler = $GLOBALS['xoops']->getHandler('module');
	$config_handler = $GLOBALS['xoops']->getHandler('config');
	$GLOBALS['myalbumModule'] = $module_handler->getByDirname($GLOBALS['mydirname']);
	$GLOBALS['myalbumModuleConfig'] = $GLOBALS['xoops']->getModuleConfigs($GLOBALS['mydirname']); 
	$GLOBALS['myalbum_mid'] = $GLOBALS['myalbumModule']->getVar('mid');
	$GLOBALS['photos_dir'] = XOOPS_ROOT_PATH.$GLOBALS['myalbumModuleConfig']['myalbum_photospath'];
	$GLOBALS['thumbs_dir'] = XOOPS_ROOT_PATH.$GLOBALS['myalbumModuleConfig']['myalbum_thumbspath'];
	$GLOBALS['photos_url'] = XOOPS_URL.$GLOBALS['myalbumModuleConfig']['myalbum_photospath'];
	$GLOBALS['thumbs_url'] = XOOPS_URL.$GLOBALS['myalbumModuleConfig']['myalbum_thumbspath'];
	
	xoops_load('pagenav');	
	xoops_load('xoopslists');
	xoops_load('xoopsformloader');
	
	include_once $GLOBALS['xoops']->path('class'.DS.'xoopsmailer.php');
	include_once $GLOBALS['xoops']->path('class'.DS.'tree.php');

	$cat_handler = xoops_getmodulehandler('cat');
	$cats = $cat_handler->getObjects(NULL, true);
	$GLOBALS['cattree'] = new XoopsObjectTree( $cats , 'cid' , 'pid', 0 ) ;
	
	$GLOBALS['xoops']->loadLanguage('user');
	$GLOBALS['xoops']->loadLanguage('admin', $mydirname);
	$GLOBALS['xoops']->loadLanguage('main', $mydirname);
		
	if( isset( $_GET['lid'] ) ) {
		$lid = intval( $_GET['lid' ] ) ;
		$result = $GLOBALS['xoops']->db->query("SELECT submitter FROM $table_photos where lid=$lid",0);
		list( $submitter ) = $GLOBALS['xoops']->db->fetchRow( $result ) ;
	} else {
		$submitter = $GLOBALS['xoops']->user->getVar('uid') ;
	}

	if ($GLOBALS['myalbumModuleConfig']['tag']) {
		include_once $GLOBALS['xoops']->path('modules'.DS.'tag'.DS.'include'.DS.'formtag.php');
	}
	
	extract($GLOBALS['myalbumModuleConfig']);
	
?>