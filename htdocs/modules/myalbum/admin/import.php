<?php
// ------------------------------------------------------------------------- //
//                      myAlbum-P - XOOPS photo album                        //
//                        <http://www.peak.ne.jp/>                           //
// ------------------------------------------------------------------------- //

include( "admin_header.php" ) ;

// From myalbum*
if( ! empty( $_POST['myalbum_import'] ) && ! empty( $_POST['cid'] ) ) {

	// anti-CSRF 
	if( ! xoops_refcheck() ) die( "XOOPS_URL is not included in your REFERER" ) ;

	// get src module
	$src_cid = intval( $_POST['cid'] ) ;
	$src_dirname = empty( $_POST['src_dirname'] ) ? '' : $_POST['src_dirname'] ;
	if( $mydirname == $src_dirname ) die( "source dirname is same as dest dirname: " . htmlspecialchars( $src_dirname ) )  ;
	if( ! preg_match( '/^myalbum(\d*)$/' , $src_dirname , $regs ) ) die( "invalid dirname of myalbum: " . htmlspecialchars( $src_dirname ) ) ;
	$module =& $module_handler->getByDirname( $src_dirname ) ;
	if( ! is_object( $module ) ) die( "invalid module dirname:" . htmlspecialchars( $src_dirname ) )  ;
	$src_mid = $module->getvar( 'mid' ) ;

	// authority check
	if( ! $GLOBALS['xoopsUser']->isAdmin( $src_mid ) ) exit ;

	// read configs from xoops_config directly
	$rs = $GLOBALS['xoops']->db->query( "SELECT conf_name,conf_value FROM  ".$GLOBALS['xoops']->db->prefix('config')." WHERE conf_modid='$src_mid'" ) ;
	while( list( $key , $val ) = $GLOBALS['xoops']->db->fetchRow( $rs ) ) {
		$src_configs[ $key ] = $val ;
	}
	$src_photos_dir = XOOPS_ROOT_PATH . $src_configs['myalbum_photospath'] ;
	$src_thumbs_dir = XOOPS_ROOT_PATH . $src_configs['myalbum_thumbspath'] ;
	// src table names
	$src_table_photos = $GLOBALS['xoops']->db->prefix( "{$src_dirname}_photos" ) ;
	$src_table_cat = $GLOBALS['xoops']->db->prefix( "{$src_dirname}_cat" ) ;
	$src_table_text = $GLOBALS['xoops']->db->prefix( "{$src_dirname}_text" ) ;
	$src_table_votedata = $GLOBALS['xoops']->db->prefix( "{$src_dirname}_votedata" ) ;

	if( isset( $_POST['copyormove'] ) && $_POST['copyormove'] == 'move' ) $move_mode = true ;
	else $move_mode = false ;

	// create category
	$GLOBALS['xoops']->db->query( "INSERT INTO ".$GLOBALS['xoops']->db->prefix( $table_cat )."(pid, title, imgurl) SELECT '0',title,imgurl FROM $src_table_cat WHERE cid='$src_cid'") or die( "DB error: INSERT cat table" ) ;
	$cid = $GLOBALS['xoops']->db->getInsertId() ;

	// INSERT loop
	$rs = $GLOBALS['xoops']->db->query( "SELECT lid,ext FROM $src_table_photos WHERE cid='$src_cid'" ) ;
	$import_count = 0 ;
	while( list( $src_lid , $ext ) = $GLOBALS['xoops']->db->fetchRow( $rs ) ) {

		// photos table
		$set_comments = $move_mode ? 'comments' : "'0'" ;
		$sql = "INSERT INTO ".$GLOBALS['xoops']->db->prefix( $table_photos )."(cid,title,ext,res_x,res_y,submitter,status,date,hits,rating,votes,comments) SELECT '$cid',title,ext,res_x,res_y,submitter,status,date,hits,rating,votes,$set_comments FROM $src_table_photos WHERE lid='$src_lid'" ;
		$GLOBALS['xoops']->db->query( $sql ) or die( "DB error: INSERT photo table" ) ;
		$lid = $GLOBALS['xoops']->db->getInsertId() ;

		// text table
		$sql = "INSERT INTO  ".$GLOBALS['xoops']->db->prefix( $table_text )." (lid,description) SELECT '$lid',description FROM $src_table_text WHERE lid='$src_lid'" ;
		$GLOBALS['xoops']->db->query( $sql ) ;

		// votedata table
		$sql = "INSERT INTO " . $GLOBALS['xoops']->db->prefix( $table_votedata ) . " (lid,ratinguser,rating,ratinghostname,ratingtimestamp) SELECT '$lid',ratinguser,rating,ratinghostname,ratingtimestamp FROM $src_table_votedata WHERE lid='$src_lid'" ;
		$GLOBALS['xoops']->db->query( $sql ) ;

		@copy( "$src_photos_dir/{$src_lid}.{$ext}" , "$photos_dir/{$lid}.{$ext}" ) ;
		if( in_array( strtolower( $ext ) , $myalbum_normal_exts ) ) {
			@copy( "$src_thumbs_dir/{$src_lid}.{$ext}" , "$thumbs_dir/{$lid}.{$ext}" ) ;
		} else {
			@copy( "$src_photos_dir/{$src_lid}.gif" , "$photos_dir/{$lid}.gif" ) ;
			@copy( "$src_thumbs_dir/{$src_lid}.gif" , "$thumbs_dir/{$lid}.gif" ) ;		}

		// exec only moving mode
		if( $move_mode ) {
			// moving comments
			$sql = "UPDATE  ".$GLOBALS['xoops']->db->prefix('xoopscomments')." SET com_modid='$myalbum_mid',com_itemid='$lid' WHERE com_modid='$src_mid' AND com_itemid='$src_lid'" ;
			$GLOBALS['xoops']->db->query( $sql ) ;

			// delete source photos
			list( $photos_dir , $thumbs_dir , $myalbum_mid , $table_photos , $table_text , $table_votedata ,  $saved_photos_dir , $saved_thumbs_dir , $saved_myalbum_mid , $saved_table_photos , $saved_table_text , $saved_table_votedata ) = array(  $src_photos_dir , $src_thumbs_dir , $src_mid , $src_table_photos , $src_table_text , $src_table_votedata , $photos_dir , $thumbs_dir , $myalbum_mid , $GLOBALS['xoops']->db->prefix( $table_photos ), $GLOBALS['xoops']->db->prefix( $table_text ) , $GLOBALS['xoops']->db->prefix( $table_votedata ) ) ;
			myalbum_delete_photos( "lid='$src_lid'" ) ;
			list( $photos_dir , $thumbs_dir , $myalbum_mid , $table_photos , $table_text ,$table_votedata  ) = array( $saved_photos_dir , $saved_thumbs_dir , $saved_myalbum_mid , $saved_table_photos , $saved_table_text , $saved_table_votedata ) ;		}

		$import_count ++ ;
	}

	$GLOBALS['xoops']->redirect( 'import.php' , 2 , sprintf( _AM_FMT_IMPORTSUCCESS , $import_count ) ) ;
	exit ;
}


// From imagemanager
else if( ! empty( $_POST['imagemanager_import'] ) && ! empty( $_POST['imgcat_id'] ) ) {

	// authority check
	$sysperm_handler =& $GLOBALS['xoops']->getHandler('groupperm');
	if( ! $sysperm_handler->checkRight('system_admin', XOOPS_SYSTEM_IMAGE, $GLOBALS['xoopsUser']->getGroups() ) ) exit ;

	// anti-CSRF 
	if( ! xoops_refcheck() ) die( "XOOPS_URL is not included in your REFERER" ) ;

	// get src information
	$src_cid = intval( $_POST['imgcat_id'] ) ;
	$src_table_photos = $GLOBALS['xoops']->db->prefix( "image" ) ;
	$src_table_cat = $GLOBALS['xoops']->db->prefix( "imagecategory" ) ;

	// create category
	$crs = $GLOBALS['xoops']->db->query( "SELECT imgcat_name,imgcat_storetype FROM $src_table_cat WHERE imgcat_id='$src_cid'" ) ;
	list( $imgcat_name , $imgcat_storetype ) = $GLOBALS['xoops']->db->fetchRow( $crs ) ;

	$GLOBALS['xoops']->db->query( "INSERT INTO ".$GLOBALS['xoops']->db->prefix( $table_cat )."SET pid=0,title='".addslashes($imgcat_name)."'" ) or die( "DB error: INSERT cat table" ) ;
	$cid = $GLOBALS['xoops']->db->getInsertId() ;

	// INSERT loop
	$rs = $GLOBALS['xoops']->db->query( "SELECT image_id,image_name,image_nicename,image_created,image_display FROM $src_table_photos WHERE imgcat_id='$src_cid'" ) ;
	$import_count = 0 ;
	while( list( $image_id,$image_name,$image_nicename,$image_created,$image_display ) = $GLOBALS['xoops']->db->fetchRow( $rs ) ) {

		$src_file = XOOPS_UPLOAD_PATH . "/$image_name" ;
		$ext = substr( strrchr( $image_name , '.' ) , 1 ) ;

		// photos table
		$sql = "INSERT INTO  ".$GLOBALS['xoops']->db->prefix( $table_photos )."SET cid='$cid',title='".addslashes($image_nicename)."',ext='$ext',submitter='$my_uid',status='$image_display',date='$image_created'" ;
		$GLOBALS['xoops']->db->query( $sql ) or die( "DB error: INSERT photo table" ) ;
		$lid = $GLOBALS['xoops']->db->getInsertId() ;

		// text table
		$sql = "INSERT INTO  ".$GLOBALS['xoops']->db->prefix( $table_text )." SET lid='$lid',description=''" ;
		$GLOBALS['xoops']->db->query( $sql ) ;

		$dst_file = "$photos_dir/{$lid}.{$ext}" ;
		if( $imgcat_storetype == 'db' ) {
			$fp = fopen( $dst_file , "wb" ) ;
			if( $fp == false ) continue ;
			$brs = $GLOBALS['xoops']->db->query( "SELECT image_body FROM  ".$GLOBALS['xoops']->db->prefix("imagebody")." WHERE image_id='$image_id'" ) ;
			list( $body ) = $GLOBALS['xoops']->db->fetchRow( $brs ) ;
			fwrite( $fp , $body ) ;
			fclose( $fp ) ;
			myalbum_create_thumb( $dst_file , $lid , $ext ) ;
		} else {
			@copy( $src_file , $dst_file ) ;
			myalbum_create_thumb( $src_file , $lid , $ext ) ;
		}

		list( $width , $height , $type ) = getimagesize( $dst_file ) ;
		$GLOBALS['xoops']->db->query( "UPDATE ".$GLOBALS['xoops']->db->prefix( $table_photos )."SET res_x='$width',res_y='$height' WHERE lid='$lid'" ) ;

		$import_count ++ ;
	}

	$GLOBALS['xoops']->redirect( 'import.php' , 2 , sprintf( _AM_FMT_IMPORTSUCCESS , $import_count ) ) ;
	exit ;
}

include_once('../include/myalbum.forms.php');
$GLOBALS['xoops']->header();
$indexAdmin = new XoopsModuleAdmin();
$indexAdmin->renderNavigation(basename(__FILE__));

$GLOBALS['xoops']->tpl->assign('admin_title', sprintf(_AM_H3_FMT_IMPORTTO,$xoopsModule->name()));
$GLOBALS['xoops']->tpl->assign('mydirname', $GLOBALS['mydirname']);
$GLOBALS['xoops']->tpl->assign('photos_url', $GLOBALS['photos_url']);
$GLOBALS['xoops']->tpl->assign('thumbs_url', $GLOBALS['thumbs_url']);
$GLOBALS['xoops']->tpl->assign('forma', myalbum_admin_form_import_myalbum());
$GLOBALS['xoops']->tpl->assign('formb', myalbum_admin_form_import_imagemanager());

$GLOBALS['xoops']->tpl->display('admin:'.$GLOBALS['mydirname'].'|'.$GLOBALS['mydirname'].'_cpanel_import.html');

$GLOBALS['xoops']->footer();

?>