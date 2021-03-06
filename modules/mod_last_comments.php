<?php
/**
 * @version $Header$
 * @package liberty
 * @subpackage modules
 */

/**
 * Initial Setup
 */
require_once( LIBERTY_PKG_PATH.'LibertyComment.php' );
global $gQueryUser, $gBitUser, $gLibertySystem, $moduleParams;
$params = $moduleParams['module_params'];
$moduleTitle = !empty($moduleParams['title'])? $moduleParams['title'] : NULL;

$userId = NULL;
if( !empty( $gQueryUser->mUserId ) ) {
	$userId = $gQueryUser->mUserId;
}

$listHash = array(
	'user_id' => $userId,
	'max_records' => $moduleParams['module_rows'],
);

if (!empty($params['full'])) {
	$listHash['parse'] = TRUE;
}

if (!empty($params['sort'])) {
	$listHash['sort_mode'] = $params['sort'];
}

if (!empty($params['pigeonholes'])) {
	$listHash['pigeonholes']['root_filter'] = $params['pigeonholes'];
}

if( !empty( $params['root_content_type_guid'] ) ) {
	if( empty($moduleTitle) && is_string( $params['root_content_type_guid'] ) ) {
		$moduleTitle = $gLibertySystem->getContentTypeName( $params['root_content_type_guid'] ).' '.tra( 'Comments' );
	}
	$listHash['root_content_type_guid'] = $params['root_content_type_guid'];
}
$gBitSmarty->assign( 'moduleTitle', $moduleTitle );

$lcom = new LibertyComment();
$modLastComments = $lcom->getList( $listHash );
$gBitSmarty->assign( 'modLastComments', $modLastComments );
?>
