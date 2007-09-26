<?php
/**
 * @version $Header: /cvsroot/bitweaver/_bit_liberty/ajax_attachment_browser.php,v 1.8 2007/09/26 06:10:23 squareing Exp $
 * @package liberty
 * @subpackage functions
 */

/**
 * required setup
 */
require_once( '../bit_setup_inc.php' );
global $gContent, $gBitSmarty;

$gContent = new LibertyAttachable();
if( isset( $_REQUEST['content_id'] )) {
	$gContent->mContentId = $_REQUEST['content_id'];
}
$gBitSmarty->assign( 'attachmentBrowser', TRUE );
include_once( LIBERTY_PKG_PATH.'attachment_browser.php' );

if( isset( $_REQUEST['json'] )){
	header( 'Content-type:application/json' );
	$gBitSmarty->display( 'bitpackage:liberty/attachment_browser_json.tpl' );
} else {
	echo $gBitSmarty->fetch( 'bitpackage:liberty/attachment_browser.tpl' );
}
?>
