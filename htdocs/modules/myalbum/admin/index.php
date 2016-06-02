<?php
// ------------------------------------------------------------------------- //
//                      myAlbum-P - XOOPS photo album                        //
//                        <http://www.peak.ne.jp/>                           //
// ------------------------------------------------------------------------- //


include("admin_header.php");

$catHandler = xoops_getmodulehandler('cat', $GLOBALS['mydirname']);
$photosHandler = xoops_getmodulehandler('photos', $GLOBALS['mydirname']);

// GPCS vars
$action = isset( $_POST[ 'action' ] ) ? $_POST[ 'action' ] : '' ;
$disp = isset( $_GET[ 'disp' ] ) ? $_GET[ 'disp' ] : '' ;
$cid = isset( $_GET[ 'cid' ] ) ? intval( $_GET[ 'cid' ] ) : 0 ;

if( $action == "insert" ) {

	// newly insert
	$sql = "INSERT INTO ".$GLOBALS['xoops']->db->prefix( $table_cat )." SET " ;
	$cols = array( "pid" => "I:N:0" ,"title" => "50:E:1" ,"imgurl" => "150:E:0" ) ;
	$sql .= mysql_get_sql_set( $cols ) ;
	$GLOBALS['xoops']->db->query( $sql ) or die( "DB Error: insert category" ) ;

	// Check if cid == pid
	$cid = $GLOBALS['xoops']->db->getInsertId() ;
	if( $cid == intval( $_POST['pid'] ) ) {
		$GLOBALS['xoops']->db->query( "UPDATE ".$GLOBALS['xoops']->db->prefix( $table_cat )." SET pid='0' WHERE cid='$cid'" ) ;
	}

	$GLOBALS['xoops']->redirect( "index.php" , 1 , _AM_CAT_INSERTED ) ;
	exit ;

} else if( $action == "update" && ! empty( $_POST['cid'] ) ) {

	$cid = intval( $_POST['cid'] ) ;
	$pid = intval( $_POST['pid'] ) ;

	// Check if new pid was a child of cid
	if( $pid != 0 ) {
		foreach($cattree->getAllChild( $cid ) as $child) 
		$children[$child->getVar('cid')] = $child->getVar('cid') ;
		foreach( $children as $child ) {
			if( $child == $pid ) die( "category looping has occurred" ) ;
		}
	}

	// update
	$sql = "UPDATE ".$GLOBALS['xoops']->db->prefix( $table_cat )." SET " ;
	$cols = array( "pid" => "I:N:0" ,"title" => "50:E:1" ,"imgurl" => "150:E:0" ) ;
	$sql .= mysql_get_sql_set( $cols ) . " WHERE cid='$cid'" ;
	$GLOBALS['xoops']->db->query( $sql ) or die( "DB Error: update category" ) ;
	$GLOBALS['xoops']->redirect( "index.php" , 1 , _AM_CAT_UPDATED ) ;
	exit ;

} else if( ! empty( $_POST['delcat'] ) ) {

	// Delete
	$cid = intval( $_POST['delcat'] ) ;

	$children[0] = 0;
	//get all categories under the specified category
	foreach($GLOBALS['cattree']->getAllChild( $cid ) as $child) 
		$children[$child->getVar('cid')] = $child->getVar('cid') ;
	$whr = "cid IN (" ;
	foreach( $children as $child ) {
		$whr .= "$child," ;
		xoops_notification_deletebyitem( $myalbum_mid , 'category' , $child ) ;
	}
	$whr .= "$cid)" ;
	xoops_notification_deletebyitem( $myalbum_mid , 'category' , $cid ) ;
	$criteria = new Criteria('`cid`', '('.implode(',', $children).')', 'IN' );
	myalbum_delete_photos( $criteria ) ;
	$GLOBALS['xoops']->db->query( "DELETE FROM ".$GLOBALS['xoops']->db->prefix( $table_cat )." WHERE $whr" ) or die( "DB error: DELETE cat table" ) ;
	$GLOBALS['xoops']->redirect( 'index.php' , 2 , _ALBM_CATDELETED ) ;
	exit ;

} else if( ! empty( $_POST['batch_update'] ) ) {

}

//
// Form Part
//
$GLOBALS['xoops']->header();
$indexAdmin = new XoopsModuleAdmin();
$indexAdmin->renderNavigation(basename(__FILE__));


// check $GLOBALS['xoops']->module
if( ! is_object( $GLOBALS['xoops']->module ) ) $GLOBALS['xoops']->redirect( "$mod_url/" , 1 , _NOPERM ) ;
echo "<h3 style='text-align:left;'>".sprintf( _AM_H3_FMT_CATEGORIES , $GLOBALS['xoops']->module->name() )."</h3>\n" ;

if( $disp == "edit" && $cid > 0 ) {

	// Editing
	$sql = "SELECT cid,pid,title,imgurl FROM ".$GLOBALS['xoops']->db->prefix( $table_cat )." WHERE cid='$cid'" ;
	$crs = $GLOBALS['xoops']->db->query( $sql ) ;
	$cat_array = $GLOBALS['xoops']->db->fetchArray( $crs ) ;
	echo myalbum_admin_form_display_edit( $cat_array , _AM_CAT_MENU_EDIT , 'update' ) ;

} else if( $disp == "new" ) {

	// New
	$cat_array = array( 'cid' => 0 , 'pid' => $cid , 'title' => '' , 'imgurl' => 'http://' ) ;
	echo myalbum_admin_form_display_edit( $cat_array , _AM_CAT_MENU_NEW , 'insert' ) ;

} else {

	// Listing
	$live_cids = array(0=>0);
	foreach($cattree->getAllChild( $cid, array() ) as $child) { 
		$cat_tree_array[$child->getVar('cid')] = $child->toArray() ;
		$live_cids[$child->getVar('cid')] = $child->getVar('cid');
	}
	$criteria = new CriteriaCompo(new Criteria('`pid`', '('.implode(',', $live_cids).')', 'NOT IN'));
	if( $catHandler->getCount($criteria) != false ) {
		$GLOBALS['xoops']->db->queryF( "UPDATE ".$GLOBALS['xoops']->db->prefix( $table_cat )." SET pid='0' " . $criteria->renderWhere() ) ;
		$GLOBALS['xoops']->redirect( 'dashboard.php' , 0 , 'A Ghost Category found.' ) ;
		exit ;
	}

	// Waiting Admission
	$criteria = new Criteria('`status`', '0');
	$waiting = $photosHandler->getCount( $criteria ) ;
	$link_admission = $waiting > 0 ? sprintf( _AM_CAT_FMT_NEEDADMISSION , $waiting ) : '' ;

	// Top links
	echo "<p><a href='?disp=new&cid=0'>"._AM_CAT_LINK_MAKETOPCAT."<img src='../images/cat_add.gif' width='18' height='15' alt='"._AM_CAT_LINK_MAKETOPCAT."' title='"._AM_CAT_LINK_MAKETOPCAT."' /></a> &nbsp;  &nbsp; <a href='admission.php' style='color:red;'>$link_admission</a></p>\n" ;

	// TH
	echo "
	<form name='MainForm' action='' method='post' style='margin:10px;'>
	<input type='hidden' name='delcat' value='' />
	<table width='75%' class='outer' cellpadding='4' cellspacing='1'>
	  <tr valign='middle'>
	    <th>"._AM_CAT_TH_TITLE."</th>
	    <th>"._AM_CAT_TH_PHOTOS."</th>
	    <th>"._AM_CAT_TH_OPERATION."</th>
	    <th nowrap='nowrap'>"._AM_CAT_TH_IMAGE."</th>
	  </tr>
	" ;

	// TD
	$oddeven = 'odd' ;
	if (isset($cat_tree_array))
		foreach( $cat_tree_array as $cid => $cat_node ) {
			$oddeven = $oddeven == 'odd' ? 'even' : 'odd' ;
			extract( $cat_node ) ;
			$prefix = '';
			$prefix = str_repeat('&nbsp;--', $catHandler->prefixDepth($cid, 0) ) ;
			$cid = intval( $cid ) ;
			$del_confirm = 'confirm("' . sprintf( _AM_CAT_FMT_CATDELCONFIRM , $title ) . '")' ;
			$criteria = new Criteria('`cid`', $cid);
			$photos_num = $photosHandler->getCount( $criteria ) ;
			if( $imgurl && $imgurl != 'http://' ) $imgsrc4show = $GLOBALS['myts']->htmlSpecialChars( $imgurl ) ;
			else $imgsrc4show = '../images/pixel_trans.gif' ;
	
			echo "
		  <tr>
		    <td class='$oddeven' width='100%'><a href='photomanager.php?cid=$cid'>$prefix&nbsp;".$GLOBALS['myts']->htmlSpecialChars($title)."</a></td>
		    <td class='$oddeven' nowrap='nowrap' align='right'>
		      <a href='photomanager.php?cid=$cid'>$photos_num</a>
		      <a href='../submit.php?cid=$cid'><img src='../images/pictadd.gif' width='18' height='15' alt='"._AM_CAT_LINK_ADDPHOTOS."' title='"._AM_CAT_LINK_ADDPHOTOS."' /></a></td>
		    <td class='$oddeven' align='center' nowrap='nowrap'>
		      &nbsp;
		      <a href='?disp=edit&amp;cid=$cid'><img src='../images/cat_edit.gif' width='18' height='15' alt='"._AM_CAT_LINK_EDIT."' title='"._AM_CAT_LINK_EDIT."' /></a>
		      &nbsp;
		      <a href='?disp=new&amp;cid=$cid'><img src='../images/cat_add.gif' width='18' height='15' alt='"._AM_CAT_LINK_MAKESUBCAT."' title='"._AM_CAT_LINK_MAKESUBCAT."' /></a>
		      &nbsp;
		      <input type='button' value='"._DELETE."' onclick='if($del_confirm){document.MainForm.delcat.value=\"$cid\"; submit();}' />
		    </td>
		    <td class='$oddeven' align='center'><img src='$imgsrc4show' height='16' /></td>
		  </tr>\n" ;
		}

	// Table footer
	echo "
	  <!-- <tr>
	    <td colspan='4' align='right' class='foot'><input type='submit' name='batch_update' value='"._AM_CAT_BTN_BATCH."' /></td>
	  </tr> -->
	</table>
	</form>
	" ;
}


$GLOBALS['xoops']->footer();
?>
