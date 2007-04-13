<?php
/**
 * @version  $Revision: 1.22 $
 * @package  liberty
 * @subpackage plugins_storage
 */
global $gLibertySystem, $gBitSystem, $gBitSmarty;

/**
 * definitions
 */
define( 'PLUGIN_GUID_BIT_FILES', 'bitfile' );

$pluginParams = array (
	'store_function' => 'bit_files_store',
	'verify_function' => 'bit_files_verify',
	'load_function' => 'bit_files_load',
	'expunge_function' => 'bit_files_expunge',
	'description' => 'Upload File To Server',
	'plugin_type' => STORAGE_PLUGIN,
	'auto_activate' => TRUE,
	'edit_label' => 'Upload File',
	'edit_field' => '<input type="file" name="upload" size="40" />',
	'edit_help' => 'This file will be uploaded to your personal storage area.<br />After selecting the file you want to upload, please return to the edit area and click the save button.'
);

if ($gBitSystem->isFeatureActive("liberty_multiple_attachments")) {
	$pluginParams['edit_label'] = 'Upload File(s)';
	$pluginParams['edit_help'] =  'The file(s) will be uploaded to your personal storage area.<br />After selecting the file(s) you want to upload, please return to the edit area and click the save button.';
	$pluginParams['edit_field'] = '<div id="upload_div"></div><input type="file" name="upload" size="40" id="uploads" />
<!-- Multiselect javascript. -->
<script>
	var upload_files = document.getElementById( \'upload_div\' );
	var upload_element = document.getElementById( \'uploads\' );
	var multi_selector = new MultiSelector( upload_files, '.
	$gBitSystem->getConfig('liberty_max_multiple_attachments', 10).
	' );
	multi_selector.addNamedElement( upload_element , \'uploads\');
</script>';
	$gBitSmarty->assign( 'loadMultiFile', TRUE );
}

//$gLibertySystem->registerPlugin( STORAGE_TYPE_BIT_FILES, $pluginParams );
$gLibertySystem->registerPlugin( PLUGIN_GUID_BIT_FILES, $pluginParams );


function bit_files_verify( &$pStoreRow ) {
	$pStoreRow['plugin_guid'] = PLUGIN_GUID_BIT_FILES;
	$pStoreRow['foreign_id'] = NULL;
	$pStoreRow['dest_base_name'] = substr( $pStoreRow['upload']['name'], 0, strrpos( $pStoreRow['upload']['name'], '.' ) );
	if( function_exists( 'transliterate' ) ) {
//		$pStoreRow['dest_base_name'] = transliterate( $pStoreRow['dest_base_name'], array('han_transliterate', 'diacritical_remove'), 'utf-8', 'utf-8' );
	}
	$pStoreRow['source_file'] = $pStoreRow['upload']['tmp_name'];

	return( TRUE );
}

function bit_files_store( &$pStoreRow ) {
	global $gBitSystem, $gBitUser;
	$ret = NULL;
	if( !empty( $pStoreRow['foreign_id'] ) ) {
		$sql = "UPDATE `".BIT_DB_PREFIX."liberty_files SET `storage_path`=?, `mime_type`=?, `file_size`=? WHERE `file_id` = ?";
		$gBitSystem->mDb->query( $sql, array( $pStoreRow['dest_file_path'], $pStoreRow['type'], $pStoreRow['size'], $pStoreRow['foreign_id'] ) );
	} else {
		$pStoreRow['file_id'] = $gBitSystem->mDb->GenID( 'liberty_files_id_seq' );
		$sql = "INSERT INTO `".BIT_DB_PREFIX."liberty_files` ( `storage_path`, `file_id`, `mime_type`, `file_size`, `user_id` ) VALUES ( ?, ?, ?, ?, ? )";
		$userId = !empty( $pStoreRow['upload']['user_id'] ) ? $pStoreRow['upload']['user_id'] : $gBitUser->mUserId;
		$gBitSystem->mDb->query($sql, array( $pStoreRow['upload']['dest_path'].$pStoreRow['upload']['name'], $pStoreRow['file_id'],  $pStoreRow['upload']['type'],  $pStoreRow['upload']['size'], $userId ) );
	}
	$sql = "UPDATE `".BIT_DB_PREFIX."liberty_attachments` SET `foreign_id`=? WHERE `attachment_id` = ?";
	$gBitSystem->mDb->query( $sql, array( $pStoreRow['file_id'], $pStoreRow['attachment_id'] ) );
	return $ret;
}

function bit_files_load( $pRow ) {
// this fuction broken, will fix soon - spiderr
// I think its fixed now - no promises though! - drewslater
	global $gBitSystem, $gLibertySystem;
	$ret = NULL;
	if( !empty( $pRow['foreign_id'] ) && is_numeric( $pRow['foreign_id'] )) {
		$query = "SELECT *
				  FROM `".BIT_DB_PREFIX."liberty_attachments` a INNER JOIN `".BIT_DB_PREFIX."liberty_attachments_map` am ON (a.`attachment_id` = am.`attachment_id`) INNER JOIN `".BIT_DB_PREFIX."liberty_files` lf ON (lf.`file_id` = a.`foreign_id`)
				  WHERE a.`foreign_id` = ? AND attachment_plugin_guid = ?";
		if( $ret = $gBitSystem->mDb->getRow($query, array( $pRow['foreign_id'], PLUGIN_GUID_BIT_FILES ))) {
			$canThumbFunc = liberty_get_function( 'can_thumbnail' );
			if ( file_exists( BIT_ROOT_PATH.dirname( $ret['storage_path'] ).'/small.png' ) ) {
				$ret['thumbnail_url']['avatar'] = BIT_ROOT_URL.dirname( $ret['storage_path'] ).'/avatar.png';
				$ret['thumbnail_url']['small'] = BIT_ROOT_URL.dirname( $ret['storage_path'] ).'/small.png';
				$ret['thumbnail_url']['medium'] = BIT_ROOT_URL.dirname( $ret['storage_path'] ).'/medium.png';
				$ret['thumbnail_url']['large'] = BIT_ROOT_URL.dirname( $ret['storage_path'] ).'/large.png';
			} elseif ( file_exists( BIT_ROOT_PATH.dirname( $ret['storage_path'] ).'/small.jpg' ) ) {
				$ret['thumbnail_url']['avatar'] = BIT_ROOT_URL.dirname( $ret['storage_path'] ).'/avatar.jpg';
				$ret['thumbnail_url']['small'] = BIT_ROOT_URL.dirname( $ret['storage_path'] ).'/small.jpg';
				$ret['thumbnail_url']['medium'] = BIT_ROOT_URL.dirname( $ret['storage_path'] ).'/medium.jpg';
				$ret['thumbnail_url']['large'] = BIT_ROOT_URL.dirname( $ret['storage_path'] ).'/large.jpg';
			} elseif( $canThumbFunc( $ret['mime_type'] ) ) {
				$ret['thumbnail_url']['avatar'] = LIBERTY_PKG_URL.'icons/generating_thumbnails.png';
				$ret['thumbnail_url']['small'] = LIBERTY_PKG_URL.'icons/generating_thumbnails.png';
				$ret['thumbnail_url']['medium'] = LIBERTY_PKG_URL.'icons/generating_thumbnails.png';
				$ret['thumbnail_url']['large'] = LIBERTY_PKG_URL.'icons/generating_thumbnails.png';
			} else {
				$mime_thumbnail = $gLibertySystem->getMimeThumbnailURL($ret['mime_type'], substr( $ret['storage_path'], strrpos( $ret['storage_path'], '.' ) + 1 ) );
				$ret['thumbnail_url']['avatar'] = $mime_thumbnail;
				$ret['thumbnail_url']['small'] = $mime_thumbnail;
				$ret['thumbnail_url']['medium'] = $mime_thumbnail;
				$ret['thumbnail_url']['large'] = $mime_thumbnail;
			}
//			if ( file_exists( BIT_ROOT_PATH.dirname( $ret['storage_path'] ).'/original.jpg' ) ) {
//				$ret['thumbnail_url']['original'] = BIT_ROOT_URL.dirname( $ret['storage_path'] ).'/original.jpg';
//			}
			$ret['filename'] = substr( $ret['storage_path'], strrpos($ret['storage_path'], '/')+1);
			$ret['source_url'] = BIT_ROOT_URL.str_replace( '+', '%20', str_replace( '%2F', '/', urlencode( $ret['storage_path'] ) ) );
			$ret['wiki_plugin_link'] = "{attachment id=".$ret['attachment_id']."}";
		}
	}
	return( $ret );
}

function bit_files_expunge( $pStorageId ) {
	global $gBitUser, $gBitSystem;
	$ret = FALSE;

	if (is_numeric($pStorageId)) {
		$sql = "SELECT * FROM `".BIT_DB_PREFIX."liberty_attachments` WHERE `attachment_id` = ?";
		$row = $gBitSystem->mDb->getRow($sql, array($pStorageId));
		if ($row) {
			$sql = "SELECT * FROM `".BIT_DB_PREFIX."liberty_files` WHERE `file_id` = ?";
			$fileRow = $gBitSystem->mDb->getRow($sql, array($row['foreign_id']) );
			if ($fileRow) {
				$absolutePath = BIT_ROOT_PATH.'/'.$fileRow['storage_path'];

				if ($gBitUser->isAdmin() || $gBitUser->mUserId == $row['user_id']) {
					if (file_exists($absolutePath)) {
						unlink($absolutePath);
					}
					$query = "DELETE FROM `".BIT_DB_PREFIX."liberty_files` WHERE `file_id` = ?";
					$gBitSystem->mDb->query($query, array($row['foreign_id']) );
					$ret = TRUE;
				}
			}
		}
	}
	return $ret;
}

?>
