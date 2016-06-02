<?php
	// ------------------------------------------------------------------------- //
	//                      myAlbum-P - XOOPS photo album                        //
	//                        <http://www.peak.ne.jp/>                           //
	// ------------------------------------------------------------------------- //
	include( "admin_header.php" ) ;
	include_once( XOOPS_ROOT_PATH . '/modules/system/constants.php' ) ;
	
	// To imagemanager
	if( ! empty( $_POST['imagemanager_export'] ) && ! empty( $_POST['imgcat_id'] ) && ! empty( $_POST['cid'] ) ) {
	
		// authority check
		$sysperm_handler =& $GLOBALS['xoops']->getHandler('groupperm');
		if( ! $sysperm_handler->checkRight('system_admin', XOOPS_SYSTEM_IMAGE, $xoopsUser->getGroups() ) ) exit ;
	
		// anti-CSRF 
		if( ! xoops_refcheck() ) die( "XOOPS_URL is not included in your REFERER" ) ;
	
		// get dst information
		$dst_cid = intval( $_POST['imgcat_id'] ) ;
		$dst_table_photos = $GLOBALS['xoops']->db->prefix( "image" ) ;
		$dst_table_cat = $GLOBALS['xoops']->db->prefix( "imagecategory" ) ;
	
		// get src information
		$src_cid = intval( $_POST['cid'] ) ;
		$src_table_photos = $GLOBALS['xoops']->db->prefix($table_photos) ;
		$src_table_cat = $GLOBALS['xoops']->db->prefix($table_cat);
	
		// get storetype of the imgcat
		$crs = $GLOBALS['xoops']->db->query( "SELECT imgcat_storetype,imgcat_maxsize FROM $dst_table_cat WHERE imgcat_id='$dst_cid'" ) or die( 'Invalid imgcat_id.' ) ;
		list( $imgcat_storetype,$imgcat_maxsize ) = $GLOBALS['xoops']->db->fetchRow( $crs ) ;
	
		// mime type look up
		$mime_types = array( 'gif' => 'image/gif' , 'png' => 'image/png' , 'jpg' => 'image/jpeg' , 'jpg' => 'image/jpeg' ) ;
	
		// INSERT loop
		$srs = $GLOBALS['xoops']->db->query( "SELECT lid,ext,title,date,status FROM $src_table_photos WHERE cid='$src_cid'" ) ;
		$export_count = 0 ;
		while( list( $lid,$ext,$title,$date,$status ) = $GLOBALS['xoops']->db->fetchRow( $srs ) ) {
	
			$dst_node = uniqid( 'img' ) ;
			$dst_file = XOOPS_UPLOAD_PATH . "/{$dst_node}.{$ext}" ;
			$src_file = empty( $_POST['use_thumb'] ) ? "$photos_dir/{$lid}.{$ext}" : "$thumbs_dir/{$lid}.{$ext}" ;
	
			if( $imgcat_storetype == 'db' ) {
				$fp = fopen( $src_file , "rb" ) ;
				if( $fp == false ) continue ;
				$body = addslashes( fread( $fp , filesize( $src_file ) ) ) ;
				fclose( $fp ) ;
			} else {
				if( ! copy( $src_file , $dst_file ) ) continue ;
				$body = '' ;
			}
	
			// insert into image table
			$image_display = $status ? 1 : 0 ;
			$GLOBALS['xoops']->db->query( "INSERT INTO $dst_table_photos SET image_name='{$dst_node}.{$ext}',image_nicename='".addslashes($title)."',image_created='$date',image_mimetype='{$mime_types[$ext]}',image_display='$image_display',imgcat_id='$dst_cid'" ) or die( "DB error: INSERT image table" ) ;
			if( $body ) {
				$image_id = $GLOBALS['xoops']->db->getInsertId() ;
				$GLOBALS['xoops']->db->query( "INSERT INTO ".$GLOBALS['xoops']->db->prefix("imagebody")." SET image_id='$image_id',image_body='$body'" ) ;
			}
	
			$export_count ++ ;
		}
	
		$GLOBALS['xoops']->redirect( 'export.php' , 2 , sprintf( _AM_FMT_EXPORTSUCCESS , $export_count ) ) ;
		exit ;
	}

	//
	// Form Part
	//
	
	
	$sysperm_handler =& $GLOBALS['xoops']->getHandler('groupperm');
	if( $sysperm_handler->checkRight('system_admin', XOOPS_SYSTEM_IMAGE, $xoopsUser->getGroups() ) ) {
		$GLOBALS['xoops']->header();
		$indexAdmin = new XoopsModuleAdmin();
		$indexAdmin->renderNavigation(basename(__FILE__));
		
		$GLOBALS['xoops']->tpl->assign('admin_title', sprintf(_AM_H3_FMT_EXPORTTO,$GLOBALS['myalbumModule']->name()));
		$GLOBALS['xoops']->tpl->assign('mydirname', $GLOBALS['mydirname']);
		$GLOBALS['xoops']->tpl->assign('photos_url', $GLOBALS['photos_url']);
		$GLOBALS['xoops']->tpl->assign('thumbs_url', $GLOBALS['thumbs_url']);
		$GLOBALS['xoops']->tpl->assign('form', myalbum_admin_form_export());
		
		$GLOBALS['xoops']->tpl->display('admin:'.$GLOBALS['mydirname'].'|'.$GLOBALS['mydirname'].'_cpanel_export.html');
		
		// check $GLOBALS['myalbumModule']
		$GLOBALS['xoops']->footer();
	} else {
		$GLOBALS['xoops']->redirect('dashboard.php', 5, _NOPERM);
	}

?>