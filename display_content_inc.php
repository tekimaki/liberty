<?php
/**
 * display_content_inc
 *
 * @author   spider <spider@steelsun.com>
 * @version  $Revision: 1.1.1.1.2.1 $
 * @package  Liberty
 * @subpackage functions
 */

	global $smarty, $gBitSystem, $gContent;

//	vd( $gContent->mInfo );
	$smarty->assign_by_ref( 'pageInfo', $gContent->mInfo );

	$gBitSystem->display( 'bitpackage:liberty/display_content.tpl' );

?>
