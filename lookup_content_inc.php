<?php
/**
 * lookup_content_inc
 *
 * @author   spider <spider@steelsun.com>
 * @version  $Revision$
 * @package  liberty
 * @subpackage functions
 */
	global $gContent;

	if( @BitBase::verifyId( $_REQUEST['content_id'] ) ) {
		$_REQUEST['content_id'] = preg_replace( '/[\D]/', '', $_REQUEST['content_id'] );
		require_once( LIBERTY_PKG_PATH.'LibertyBase.php');
		if( $gContent = LibertyBase::getLibertyObject( $_REQUEST['content_id'] ) ) {
			$gBitSmarty->assign_by_ref( 'gContent', $gContent );
			$gBitSmarty->assign_by_ref( 'pageInfo', $gContent->mInfo );
		}
	}

?>
