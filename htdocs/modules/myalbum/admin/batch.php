<?php
// ------------------------------------------------------------------------- //
//                      myAlbum-P - XOOPS photo album                        //
//                        <http://www.peak.ne.jp/>                           //
// ------------------------------------------------------------------------- //

include( "admin_header.php" ) ;

// GPCS vars
$GLOBALS['submitter'] = empty( $_POST['submitter'] ) ? $my_uid : intval( $_POST['submitter']  ) ;
if( isset( $_POST['cid'] ) ) $cid = intval( $_POST['cid'] ) ;
else if( isset( $_GET['cid'] ) ) $cid = intval( $_GET['cid'] ) ;
else $cid = 0 ;
$GLOBALS['dir4edit'] = isset( $_POST['dir'] ) ? $GLOBALS['myts']->htmlSpecialChars( $_POST['dir'] ) : '' ;
$GLOBALS['title4edit'] = isset( $_POST['title'] ) ? $GLOBALS['myts']->htmlSpecialChars( $_POST['title'] ) : '' ;
$GLOBALS['desc4edit'] = isset( $_POST['desc'] ) ? $GLOBALS['myts']->htmlSpecialChars( $_POST['desc'] ) : '' ;


// reject Not Admin
if( ! $isadmin ) {
	$GLOBALS['xoops']->redirect( $mod_url , 2 , _ALBM_MUSTREGFIRST ) ;
	exit ;
}


$catHandler = $GLOBALS['xoops']->getModuleHandler('cat', $mydirname);
// check Categories exist
$count = $catHandler->getCount();
if( $count < 1 ) {
	$GLOBALS['xoops']->redirect(XOOPS_URL."/modules/$mydirname/",2,_ALBM_MUSTADDCATFIRST);
	exit();
}

$photosHandler = $GLOBALS['xoops']->getModuleHandler('photos', $mydirname);
$textHandler = $GLOBALS['xoops']->getModuleHandler('text', $mydirname);

if( isset( $_POST['submit'] ) && $_POST['submit'] != "" ) {
	ob_start() ;

	// Check Directory
	$dir = $GLOBALS['myts']->stripSlashesGPC( $_POST['dir'] ) ;
	if( empty( $dir ) || ! is_dir( $dir ) ) {
		if( ord( $dir ) != 0x2f ) $dir = "/$dir" ;
		$prefix = XOOPS_ROOT_PATH ;
		while( strlen( $prefix ) > 0 ) {
			if( is_dir( "$prefix$dir" ) ) {
				$dir = "$prefix$dir" ;
				break ;
			}
			$prefix = substr( $prefix , 0 , strrpos( $prefix , '/' ) ) ;
		}
		if( ! is_dir( $dir ) ) {
			$GLOBALS['xoops']->redirect( 'batch.php' , 3 , _ALBM_MES_INVALIDDIRECTORY . "<br />$dir4edit" ) ;
			exit ;
		}
	}
	if( substr( $dir , -1 ) == '/' ) $dir = substr( $dir , 0 , -1 ) ;

	$title4save = $GLOBALS['myts']->htmlSpecialChars( $_POST['title'] ) ;
	$desc4save = $GLOBALS['myts']->makeTareaData4Save( $_POST['desc'] ) ;

	$date = strtotime( $_POST['post_date'] ) ;
	if( $date == -1 ) $date = time() ;

	$dir_h = opendir( $dir ) ;
	if( $dir_h === false ) {
		$GLOBALS['xoops']->redirect( 'batch.php' , 3 , _ALBM_MES_INVALIDDIRECTORY . "<br />$dir4edit" ) ;
		exit ;
	}
	$filecount = 1 ;
	while( $file_name = readdir( $dir_h ) ) {

		// Skip '.' , '..' and hidden file
		if( substr( $file_name , 0 , 1 ) == '.' ) continue ;

		$ext = substr( strrchr( $file_name , '.' ) , 1 ) ;
		$node = substr( $file_name , 0 , - strlen( $ext ) - 1 ) ;
		$file_path = "$dir/$node.$ext" ;

		$title = empty( $_POST['title'] ) ? addslashes( $node ) : "$title4save $filecount" ;

		if( is_readable( $file_path ) && in_array( strtolower( $ext ) , $array_allowed_exts ) ) {
			if( ! in_array( strtolower( $ext ) , $myalbum_normal_exts ) ) {
				list( $w , $h ) = getimagesize( $file_path ) ;
			} else {
				list( $w , $h ) = array( 0 , 0 ) ;
			}
			$photo = $photosHandler->create();
			$photo->setVar('cid', $cid);
			$photo->setVar('title', $title);
			$photo->setVar('ext', $ext);
			$photo->setVar('res_x', $w);
			$photo->setVar('res_y', $h);
			$photo->setVar('submitter', $submitter);
			$photo->setVar('date', $date);
			$photo->setVar('status', 1);
			if( $lid = $photosHandler->insert($photo) ) {
				print " &nbsp; <a href='../photo.php?lid=$lid' target='_blank'>$file_path</a>\n" ;
				copy( $file_path , $GLOBALS['photos_dir'].DS."$lid.$ext" ) ;
				myalbum_create_thumb( $GLOBALS['photos_dir'].DS."$lid.$ext" , $lid , $ext ) ;
				$text = $textHandler->create();
				$text->setVar('lid', $lid);
				$text->setVar('description', $desc4save);
				$textHandler->insert($text);
				echo _AM_MB_FINISHED."<br />\n" ;
			}

			$filecount ++ ;
		}
	}
	closedir( $dir_h ) ;

	if( $filecount <= 1 ) {
		echo "<p>$dir4edit : "._ALBM_MES_BATCHNONE."</p>" ;
	} else {
		printf( "<p>"._ALBM_MES_BATCHDONE."</p>" , $filecount - 1 ) ;
	}

	$result_str = ob_get_contents() ;
	ob_end_clean() ;
}

$GLOBALS['xoops']->header();
$moduleAdmin = new XoopsModuleAdmin();
$moduleAdmin->renderNavigation(basename(__FILE__));

$GLOBALS['xoops']->tpl->assign('admin_title', sprintf(_AM_H3_FMT_BATCHREGISTER,$GLOBALS['myalbumModule']->name()));
$GLOBALS['xoops']->tpl->assign('mydirname', $GLOBALS['mydirname']);
$GLOBALS['xoops']->tpl->assign('photos_url', $GLOBALS['photos_url']);
$GLOBALS['xoops']->tpl->assign('thumbs_url', $GLOBALS['thumbs_url']);
$GLOBALS['xoops']->tpl->assign('form', myalbum_admin_form_admission());
if (isset($result_str))
	$GLOBALS['xoops']->tpl->assign('result_str', $result_str);

$GLOBALS['xoops']->tpl->display('admin:'.$GLOBALS['mydirname'].'|'.$GLOBALS['mydirname'].'_cpanel_batch.html');

// check $GLOBALS['myalbumModule']
$GLOBALS['xoops']->footer()

?>