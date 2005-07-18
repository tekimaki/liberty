<?php
/**
 * list_content
 *
 * @author   spider <spider@steelsun.com>
 * @version  $Revision: 1.1.1.1.2.4 $
 * @package  Liberty
 * @subpackage functions
 */

/**
 * required setup
 */
require_once("../bit_setup_inc.php");

// some content specific offsets and pagination settings
if( !empty( $_REQUEST['sort_mode'] ) ) {
	$content_sort_mode = $_REQUEST['sort_mode'];
	$smarty->assign( 'sort_mode', $content_sort_mode );
}

$max_content = $gBitSystem->mPrefs['maxRecords'];
$offset_content = !empty( $_REQUEST['offset'] ) ? $_REQUEST['offset'] : 0;
$smarty->assign( 'user_id', !empty( $_REQUEST['user_id'] ) ? $_REQUEST['user_id'] : NULL );
$smarty->assign( 'curPage', $page = !empty( $_REQUEST['page'] ) ? $_REQUEST['page'] : 1 );
$offset_content = ( $page - 1 ) * $gBitSystem->mPrefs['maxRecords'];

// now that we have all the offsets, we can get the content list
include_once( LIBERTY_PKG_PATH.'get_content_list_inc.php' );
 
// calculate page number
$numPages = ceil( $contentList['cant'] / $gBitSystem->mPrefs['maxRecords'] );
$smarty->assign( 'numPages', $numPages );

//$smarty->assign_by_ref('offset', $offset);
$smarty->assign( 'contentSelect', $contentSelect );
$smarty->assign( 'contentTypes', $contentTypes );
$smarty->assign( 'contentList', $contentList['data'] );
$smarty->assign( 'contentCount', $contentList['cant'] );

$gBitSystem->setBrowserTitle( 'List Content' );
$gBitSystem->display( 'bitpackage:liberty/list_content.tpl' );
?>
