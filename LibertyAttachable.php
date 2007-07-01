<?php
/**
 * Management of Liberty Content
 *
 * @package  liberty
 * @version  $Header: /cvsroot/bitweaver/_bit_liberty/LibertyAttachable.php,v 1.114 2007/07/01 20:40:48 spiderr Exp $
 * @author   spider <spider@steelsun.com>
 */
// +----------------------------------------------------------------------+
// | Copyright (c) 2004, bitweaver.org
// +----------------------------------------------------------------------+
// | All Rights Reserved. See copyright.txt for details and a complete list of authors.
// | Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
// |
// | For comments, please use phpdocu.sourceforge.net documentation standards!!!
// | -> see http://phpdocu.sourceforge.net/
// +----------------------------------------------------------------------+
// | Authors: spider <spider@steelsun.com>
// +----------------------------------------------------------------------+

/**
 * required setup
 */
require_once( LIBERTY_PKG_PATH.'LibertyContent.php' );
require_once( LIBERTY_PKG_PATH.'LibertySystem.php' );

// load the image processor plugin, check for loaded 'gd' since that is the default processor, and config might not be set.
if( $gBitSystem->isFeatureActive( 'image_processor' ) || extension_loaded( 'gd' ) ) {
	require_once( LIBERTY_PKG_PATH."plugins/processor.".$gBitSystem->getConfig( 'image_processor','gd' ).".php" );
}

// maximum size of the 'original' image when converted to jpg
define( 'MAX_THUMBNAIL_DIMENSION', 99999 );

/**
 * LibertyAttachable class
 *
 * @package liberty
 */
class LibertyAttachable extends LibertyContent {
	var $mContentId;
	var $mStorage;

	function LibertyAttachable() {
		LibertyContent::LibertyContent();
	}

	/**
	* getStoragePath - get path to store files for the feature site_upload_dir. It creates a calculable hierarchy of directories
	*
	* @access public
	* @author Christian Fowler<spider@steelsun.com>
	* @param $pSubDir any desired directory below the StoragePath. this will be created if it doesn't exist
	* @param $pCommon indicates not to use the 'common' branch, and not the 'users/.../<user_id>' branch
	* @param $pRootDir override BIT_ROOT_DIR with a custom absolute path - useful for areas where no we access should be allowed
	* @return string full path on local filsystem to store files.
	*/
	function getStoragePath( $pSubDir = NULL, $pUserId = NULL, $pPackage = ACTIVE_PACKAGE, $pPermissions = 0755, $pRootDir = NULL ) {
		$ret = null;
		if( $storageUrl = LibertyAttachable::getStorageBranch( $pSubDir, $pUserId, $pPackage, $pPermissions, $pRootDir ) ) {
			//$ret = BIT_ROOT_PATH.$storageUrl;
			$ret = ( !empty( $pRootDir ) ? $pRootDir : BIT_ROOT_PATH ).$storageUrl;
			//$ret = $storageUrl;
		}
		return $ret;
	}


	function getStorageUrl( $pSubDir = NULL, $pUserId = NULL, $pPackage = ACTIVE_PACKAGE, $pPermissions = 0755, $pRootDir = NULL ) {
		return BIT_ROOT_URL.LibertyAttachable::getStorageBranch( $pSubDir, $pUserId, $pPackage, $pPermissions, $pRootDir );
	}

	function getStorageSubDirName() {
		return 'images';
	}

	/**
	* getStorageBranch - get url to store files for the feature site_upload_dir. It creates a calculable hierarchy of directories
	*
	* @access public
	* @author Christian Fowler<spider@steelsun.com>
	* @param $pSubDir any desired directory below the StoragePath. this will be created if it doesn't exist
	* @param $pUserId indicates the 'users/.../<user_id>' branch or use the 'common' branch if null
	* @param $pRootDir override BIT_ROOT_DIR with a custom absolute path - useful for areas where no we access should be allowed
	* @return string full path on local filsystem to store files.
	*/
	function getStorageBranch( $pSubDir = NULL, $pUserId = NULL, $pPackage = ACTIVE_PACKAGE, $pPermissions = 0755, $pRootDir = NULL ) {
		// *PRIVATE FUNCTION. GO AWAY! DO NOT CALL DIRECTLY!!!
		global $gBitSystem;
		$pathParts = array();
		$pathParts = split( '/', trim( STORAGE_PKG_PATH, '/\\' ) );

		if( !$pUserId ) {
			$pathParts[] = 'common';
		} else {
			$pathParts[] = 'users';
			$pathParts[] = (int)($pUserId % 1000);
			$pathParts[] = $pUserId;
		}

		if( $pPackage ) {
			$pathParts[] = $pPackage;
		}
		// In case $pSubDir is multiple levels deep we'll need to mkdir each directory if they don't exist
		$pSubDirParts = split('/',$pSubDir);
		foreach ($pSubDirParts as $subDir) {
			$pathParts[] = $subDir;
		}

		$fullPath = implode( $pathParts, '/' ).'/';

		mkdir_p( $fullPath );
		$ret = substr( $fullPath, strlen( dirname( STORAGE_PKG_PATH ) ) );
		return $ret;
	}

	function verifyAttachment( &$pParamHash, $file ) {
		global $gBitSystem, $gBitUser, $gLibertySystem;
		if( !empty( $_FILES[$file] ) ) {
			// tiki files upload
			$pParamHash[$file] = $_FILES[$file];
		}

		if( !empty( $pParamHash[$file]['size'] ) && !empty( $pParamHash[$file] ) && is_array( $pParamHash[$file] ) ) {

			$save = TRUE;
			if( $save ) {
				if( empty( $pParamHash['storage_guid'] )) {
					// only file format storage available at present 
					$pParamHash['storage_guid'] = $storageGuid = PLUGIN_GUID_BIT_FILES;
				} else {
					$storageGuid = $pParamHash['storage_guid'];
				}
				
				if( !empty( $pParamHash[$file]['size'] ) ) {
					if ( !is_windows() ) {
						list( $pParamHash[$file]['name'], $pParamHash[$file]['type'] ) = $gBitSystem->verifyFileExtension( $pParamHash[$file]['tmp_name'], $pParamHash[$file]['name'] );
					} else {
//						$pParamHash[$file]['type'] = $gBitSystem->verifyMimeType( $pParamHash[$file]['tmp_name'] );
					}
					$pParamHash[$file]['dest_base_name'] = substr( $pParamHash[$file]['name'], 0, strrpos( $pParamHash[$file]['name'], '.' )  );
					$pParamHash[$file]['source_file'] = $pParamHash[$file]['tmp_name'];
					// lowercase all file extensions
					$pParamHash[$file]['name'] = $pParamHash[$file]['dest_base_name'].strtolower( substr( $pParamHash[$file]['name'], strrpos( $pParamHash[$file]['name'], '.' ) )  );
					if (!isset($pParamHash['STORAGE'][$storageGuid])) {
						$pParamHash['STORAGE'][$storageGuid] = array();
					}
					$pParamHash['STORAGE'][$storageGuid][$file] = array('upload' => &$pParamHash[$file]);
				}
			}
		}
	}

	function verifyAttachments( &$pParamHash ) {
		global $gBitSystem;
		// Support for single bitfile upload
		$this->verifyAttachment( $pParamHash, 'upload' );
		// Auto add uploads  for multiple attachment style
		if ($gBitSystem->getConfig('liberty_attachment_style') == "multiple") {
			if (empty($pParamHash['upload_arrays'])) {
				$pParamHash['upload_arrays'] = 'uploads';
			}
			else {
				$pParamHash['upload_arrays'] .= ',uploads';
			}
		}

		// Support for primary attachment
		$this->verifyAttachment( $pParamHash, 'primary_attachment' );

		// Support for multiple arrays of uploads.
		if (!empty($pParamHash['upload_arrays'])) {
			$other_uploads = split(',',$pParamHash['upload_arrays']);
			foreach ($other_uploads as $upload_array) {
				// We need a max to guard against infinite loop.
				// multifile.js sets this for us.
				$max_name = $upload_array.'_max';
				if (isset($pParamHash[$max_name])) {
					for ($i = 0; $i <= $pParamHash[$max_name]; $i++) {
						$field = $upload_array."_".$i;
						if(isset($_FILES[$field]) &&
							$_FILES[$field]['error'] == 0) {
							$this->verifyAttachment($pParamHash, $field);
						}
					}
				}
			}
		}

		return ( count( $this->mErrors ) == 0 );
	}

	function verify( &$pParamHash ) {
		global $gBitSystem, $gBitUser;
		// don't verify p_liberty_attach_attachments on bitpermuser class so registration with avatar upload works
		// only verify if there is an attachment and the user isn't supposed to do it.
		if( strtolower( get_class( $this ) ) != 'bitpermuser' && !empty($_FILES) && !$gBitUser->hasPermission('p_liberty_attach_attachments') ) {
			$this->mErrors['attachments'] = tra('You do not have permission to upload attachments.');
		}

		if( !empty( $pParamHash['attachment_id'] ) && !$this->verifyId( $pParamHash['attachment_id'] ) ) {
			$this->mErrors['file'] = tra('System Error: Non-numeric storage_id.');
		}

		if( empty( $pParamHash['user_id'] ) ) {
			// storage is always owned by the user that uploaded it!
			// er... or at least admin if somehow we have a NULL mUserId - anon uploads maybe?
			$pParamHash['user_id'] = @$this->verifyId( $gBitUser->mUserId ) ? $gBitUser->mUserId : ROOT_USER_ID;
		}
		if( empty( $pParamHash['process_storage'] ) ) {
			$pParamHash['process_storage'] = NULL;
		}

		if( empty( $pParamHash['subdir'] ) ) {
			$pParamHash['subdir'] = 'files';
		}

		$this->verifyAttachments($pParamHash);
		$this->verifyPrimaryAttachmentId($pParamHash);

		return( count( $this->mErrors ) == 0 );
	}

	function storeNewAttachments(&$pParamHash) {
		global $gLibertySystem, $gBitSystem, $gBitUser;
		foreach( array_keys( $pParamHash['STORAGE'] ) as $guid ) {
			$storeRows = &$pParamHash['STORAGE'][$guid]; // short hand variable assignment
			// If it is empty then nothing more to do. Avoid error in foreach.
			if (empty($storeRows)) {
				continue;
			}
			foreach( $storeRows as $key => $value ) {
				$storeRow = &$pParamHash['STORAGE'][$guid][$key];
				$storeRow['plugin_guid'] = $guid;
				if (empty($pParamHash['content_id'])) {
					$storeRow['content_id'] = $gBitUser->mContentId;
				} else {
					$storeRow['content_id'] = $pParamHash['content_id']; // copy in content_id
				}

				if (!empty($pParamHash['content_id'])) {
					$storeRow['content_id'] = $pParamHash['content_id']; // copy in content_id
				}

				if (!empty($pParamHash['user_id'])) {
					$storeRow['user_id'] = $pParamHash['user_id']; // copy in the user_id
				} else {
					$storeRow['user_id'] = $gBitUser->mUserId;
				}

				// do we have a verify function for this storage type, and do things verify?
				$verifyFunc = $gLibertySystem->getPluginFunction( $guid, 'verify_function' );
				if( $verifyFunc && $verifyFunc( $storeRow ) ) {
					// For backwards compatibility with a single upload.
					if( @BitBase::verifyId( $pParamHash['attachment_id'] )) {
						$storeRow['upload']['attachment_id'] = $storeRow['attachment_id'] = $pParamHash['attachment_id'];
					} else {
						if ( defined( 'LINKED_ATTACHMENTS' ) && @BitBase::verifyId( $pParamHash['content_id'] ) ) {
							$storeRow['upload']['attachment_id'] = $storeRow['attachment_id'] = $pParamHash['content_id'];
						} else {
							$storeRow['upload']['attachment_id'] = $storeRow['attachment_id'] = 
								defined( 'LINKED_ATTACHMENTS' ) ? $this->mDb->GenID( 'liberty_content_id_seq') : $this->mDb->GenID( 'liberty_attachments_id_seq' );
						}	
					}
					// if we have uploaded a file, we can take care of that generically
					if( is_array( $storeRow['upload'] ) && !empty( $storeRow['upload']['size'] ) ) {
						if( empty( $storeRow['upload']['type'] ) ) {
							$ext = substr( $storeRow['upload']['name'], strrpos( $storeRow['upload']['name'], '.' ) + 1 );
							$storeRow['upload']['type'] = $gBitSystem->lookupMimeType( $ext );
						}
						$storeRow['upload']['dest_path'] = $this->getStorageBranch( $storeRow['attachment_id'], $storeRow['user_id'], $this->getStorageSubDirName() );
						if (!empty( $pParamHash['thumbnail_sizes'] ) ) {
							$storeRow['upload']['thumbnail_sizes'] = $pParamHash['thumbnail_sizes'];
						}
						$storagePath = liberty_process_upload( $storeRow );
						// We're gonna store to local file system & liberty_files table
						if( empty( $storagePath ) ) {
							$this->mErrors['file'] = tra( "Could not store file" ).": ".$storeRow['upload']['name'].'.';
							$storeRow['attachment_id'] = NULL;
							$storeRow['upload']['attachment_id'] = NULL;
						} else {
							$storeRow['upload']['dest_file_path'] = $storagePath;
						}
					}

					if( @BitBase::verifyId( $storeRow['attachment_id'] ) && $storeFunc = $gLibertySystem->getPluginFunction( $storeRow['plugin_guid'], 'store_function' )) {
						$this->mStorage = $storeFunc( $storeRow );
					}

					if( !@BitBase::verifyId( $pParamHash['attachment_id'] )) {
						$sql = "INSERT INTO `".BIT_DB_PREFIX."liberty_attachments` ( `attachment_id`, `attachment_plugin_guid`, `foreign_id`, `user_id` ) VALUES ( ?, ?, ?, ? )";
						$rs = $this->mDb->query( $sql, array( $storeRow['attachment_id'], $storeRow['plugin_guid'], (int)$storeRow['foreign_id'], $storeRow['user_id'] ) );
						if (!empty($storeRow['content_id'])) {
							$sql = "INSERT INTO `".BIT_DB_PREFIX."liberty_attachments_map` (attachment_id, content_id) VALUES (?, ?)";
							$rs = $this->mDb->query($sql, array( $storeRow['attachment_id'], $storeRow['content_id']));
						}
					}
				}
			}

			// For backwards compatibility with a single upload.
			if (empty($pParamHash['attachment_id']) && !empty($storeRows['upload']['attachment_id'])) {
				$pParamHash['attachment_id'] = $storeRows['upload']['attachment_id'];
			}
		}
	}

	function storeExistingAttachments(&$pParamHash) {
		if( @$this->verifyId( $this->mContentId ) && !empty( $pParamHash['existing_attachment_id'] )) {
			// Allow for an array of attachment ids
		    foreach( $pParamHash['existing_attachment_id'] as $existingAttachmentId ) {
				// allow for multiple values separated by any non numeric character
				$ids = preg_split( '/\D/', $existingAttachmentId );
				foreach( $ids as $id ) {
					$id = ( int )$id;
					$bindVars = array($id, $this->mContentId);
					if( @$this->verifyId( $id ) ) {
						// Is it already attached?
						$query = "SELECT COUNT(*) from `".BIT_DB_PREFIX."liberty_attachments_map` WHERE attachment_id = ? AND content_id = ?";
						if($this->mDb->getOne( $query, $bindVars ) == 0) {
							// Does the attachment exist?
							$query = "SELECT COUNT(*) from `".BIT_DB_PREFIX."liberty_attachments` WHERE attachment_id = ?";
							if ($this->mDb->getOne($query, array($id)) == 1) {
								// Okay insert it.
								$query = "INSERT INTO `".BIT_DB_PREFIX."liberty_attachments_map` (attachment_id, content_id) VALUES (?, ?)";
								$this->mDb->query($query, $bindVars);
							} else {
								$this->mErrors[] = tra("No such attachment: ") . $id;
							}
						}
					} elseif( !empty( $id )) {
						$this->mErrors[] = tra("Invalid attachment id: ") . $id;
					}
				}
			}
		}
	}

	/**
	 * Verify that if somebody is setting the primary_attachment[attachment_id] that they are refering to an existing attachment
	 * @param pParamHash Array with primary_attachment[attachment_id] set.
	 **/
	function verifyPrimaryAttachmentId(&$pParamHash) {
		if (!empty($pParamHash['detach_primary_attachment'])) {
			$pParamHash['content_store']['primary_attachment_id'] = NULL;
		}
		if (!empty($pParamHash['primary_attachment_id'])) {
			if ($this->verifyId($pParamHash['primary_attachment_id'])) {
				$sql = "SELECT count(*) FROM `".BIT_DB_PREFIX."liberty_attachments` WHERE attachment_id = ?";

				$count = $this->mDb->getOne($sql, array($pParamHash['primary_attachment_id']));
				if (!$count) {
					$this->mErrors['primary_attachment'] = tra("Could not set the primary attachment because there is no attachment with the given id.");
				} else {
					$pParamHash['content_store']['primary_attachment_id'] = $pParamHash['primary_attachment_id'];
					$pParamHash['existing_attachment_id'][] = $pParamHash['primary_attachment_id'];
				}
			} else {
				$this->mErrors['primary_attachment'] = tra("Invalid primary attachment id.");
			}
		}
	}

	/**
	 * Store the primary attachment id if we need to. This function can be called statically
	 *
	 * @param array $pParamHash should contain the primary_attachment_id and the content_id the attachment_id should be linked to
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	function storePrimaryAttachmentId( $pParamHash ) {
		global $gBitSystem;
		$ret = FALSE;
		if( !empty( $pParamHash['primary_attachment_id'] ) && empty( $pParamHash['primary_attachment']['attachment_id'] )) {
			$pParamHash['primary_attachment']['attachment_id'] = $pParamHash['primary_attachment_id'];
		}

		if( !empty( $pParamHash['primary_attachment']['attachment_id'] ) && !empty( $pParamHash['content_id'] )) {
			$query = "UPDATE liberty_content SET primary_attachment_id = ? WHERE content_id = ?";
			if( $gBitSystem->mDb->query( $query, array( $pParamHash['primary_attachment']['attachment_id'], $pParamHash['content_id'] ))) {
				$ret = TRUE;
			}
		}
		return $ret;
	}

	// Things to be stored should be shoved in the array $pParamHash['STORAGE']
	function store ( &$pParamHash ) {
		global $gLibertySystem, $gBitSystem;
		$this->mDb->StartTrans();
		if( LibertyAttachable::verify( $pParamHash ) && LibertyContent::store( $pParamHash )) {
			if(!empty( $pParamHash['STORAGE'] ) && count( $pParamHash['STORAGE'] ) ) {
				$this->storeNewAttachments($pParamHash);
				// We have to do this after LibertyContent::store so we can get the content_id and after storeNewAttachments so we can get the attachment id.
				$this->storePrimaryAttachmentId($pParamHash);
			}
			$this->storeExistingAttachments($pParamHash);
		}
		$this->mDb->CompleteTrans();

		return( count( $this->mErrors ) == 0 );
	}

	// Store attachments only not the LibertyContent bits
	function storeAttachments( &$pParamHash, $pExisting = TRUE ) {
		$this->mDb->StartTrans();
		if ($this->verifyAttachments( $pParamHash )) {
			if( !empty( $pParamHash['STORAGE'] ) && count( $pParamHash['STORAGE'] )) {
				$this->storeNewAttachments($pParamHash);
			}
			if ($pExisting) {
				$this->storeExistingAttachments($pParamHash);
			}
		}
		$this->mDb->CompleteTrans();

		return( count( $this->mErrors ) == 0 );
	}

	/**
	 * Get a list of all available attachments
	 *
	 * @param array $pListHash
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	function getAttachmentList( &$pListHash ) {
		global $gLibertySystem, $gBitUser, $gBitSystem;

		$this->prepGetList( $pListHash );

		// initialise some variables
		$attachments = $ret = $bindVars = array();
		$whereSql = $joinSql = $selectSql = '';

		// only admin may view attachments from other users
		if( !$gBitUser->isAdmin() ) {
			$pListHash['user_id'] = $gBitUser->mUserId;
		}

		if( !empty( $pListHash['user_id'] ) ) {
			$whereSql .= empty( $whereSql ) ? ' WHERE ' : ' AND ';
			$whereSql .= " la.user_id = ? ";
			$bindVars[] = $pListHash['user_id'];
		}

		if( !empty( $pListHash['content_id'] ) ) {
			$whereSql .= empty( $whereSql ) ? ' WHERE ' : ' AND ';
			$whereSql .= " lam.content_id = ? ";
			$selectSql .= " , lam.content_id ";
			$joinSql  .= " INNER JOIN `".BIT_DB_PREFIX."liberty_attachments_map` lam ON (la.`attachment_id` = lam.`attachment_id`) ";
			$bindVars[] = $pListHash['content_id'];
		}
		$query = "SELECT la.* $selectSql FROM `".BIT_DB_PREFIX."liberty_attachments` la INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON(la.`user_id` = uu.`user_id`) $joinSql $whereSql";
		$result = $this->mDb->query( $query, $bindVars, $pListHash['max_records'], $pListHash['offset'] );
		while( $res = $result->fetchRow() ) {
			$attachments[] = $res;
		}

		foreach( $attachments as $attachment ) {
			if( $loadFunc = $gLibertySystem->getPluginFunction( $attachment['attachment_plugin_guid'], 'load_function' )) {
				$ret[$attachment['attachment_id']] = $loadFunc( $attachment );
			}
			if( !empty( $pListHash['load_attached_to'] ) ) {
				$sql = "SELECT lam.`content_id` FROM `".BIT_DB_PREFIX."liberty_attachments_map` lam WHERE lam.`attachment_id` = ?";
				$attached = $this->mDb->getCol($sql, array($attachment['attachment_id']));
				$ret[$attachment['attachment_id']]['attached_to'] = $attached;
			}
		}

		// count all entries
		$query = "SELECT COUNT(*)
			FROM `".BIT_DB_PREFIX."liberty_attachments` la
			INNER JOIN `".BIT_DB_PREFIX."users_users` uu ON(la.`user_id` = uu.`user_id`)
			$joinSql $whereSql
		";

		$pListHash['cant'] = $this->mDb->getOne( $query, $bindVars );
		$this->postGetList( $pListHash );

		return $ret;
	}

	/**
	 * Expunges the content deleting attachments if asked to do so, otherwise just detaching them
	 */
	function expunge ($pDeleteAttachments=FALSE) {
		if( !empty( $this->mStorage ) && count( $this->mStorage ) ) {
			foreach( array_keys( $this->mStorage ) as $i ) {
				if ($pDeleteAttachments) {
					$this->expungeAttachment(  $this->mStorage[$i]['attachment_id'] );
				}
				else {
					$this->detachAttachment( $this->mStorage[$i]['attachment_id'] );
				}
			}
		}
		if( $pDeleteAttachments && !empty( $this->mInfo['primary_attachment_id'] ) ) {
			$this->expungeAttachment( $this->mStorage[$i]['attachment_id'] );
		}
		return LibertyContent::expunge();
	}

	/**
	 * expunge attachment from the database (and file system via the plugin if required)
	 *
	 * @param numeric $pAttachmentId attachment id of the item that should be deleted
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	function expungeAttachment( $pAttachmentId ) {
		global $gLibertySystem, $gBitUser;
		$ret = NULL;
		if( @$this->verifyId( $pAttachmentId ) ) {
			$sql = "SELECT `attachment_plugin_guid`, `user_id` FROM `".BIT_DB_PREFIX."liberty_attachments` WHERE `attachment_id`=?";
			$row = $this->mDb->getRow( $sql, array( $pAttachmentId ) );
			$guid = $row['attachment_plugin_guid'];
			$user_id = $row['user_id'];
			if( $guid && ( $this->isOwner( $row ) || $gBitUser->isAdmin() )) {
				// check if we have the means available to remove this attachment
				if( $expungeFunc = $gLibertySystem->getPluginFunction( $guid,'expunge_function' )) {

					// Find all the content that will care.
					$sql = "
						SELECT lc.`content_type_guid`, lc.`content_id`
						FROM `".BIT_DB_PREFIX."liberty_content` lc
						LEFT JOIN `".BIT_DB_PREFIX."liberty_attachments_map` lam ON (lc.`content_id`=lam.`content_id`)
						WHERE lc.`primary_attachment_id`=? OR lam.`attachment_id`=? ORDER BY `content_type_guid`";
					$ret = $this->mDb->getArray( $sql, array( $pAttachmentId, $pAttachmentId ), TRUE );

					// Delete the entry from the attachments map first due to the contraint set on liberty_content
					// this is needed for packages such as treasury which handle their attachments and content sepatately from LibertyAttachable
					// this information is not needed in the table anymore since we are passing content_ids on to the expunge functions
					$sql = "DELETE FROM `".BIT_DB_PREFIX."liberty_attachments_map` WHERE `attachment_id`=?";
					$this->mDb->query( $sql, array( $pAttachmentId ));

					// Inform all the content that will care.
					if( !empty( $ret ) && is_array( $ret )) {
						// Collect by content_type_guid into a single array to cut down construction costs
						foreach( $ret as $match ) {
							$contentTypes[$match['content_type_guid']][] = $match['content_id'];
						}

						// this can get pretty expensive if an attachment is attached to a ton of content
						foreach( $contentTypes as $content_type_guid => $contentIds ) {
							// expungingAttachment is a class oriented method not an object oriented method in order to save loading objects that may not care.
							$cls = $this->getLibertyClass( $content_type_guid );

							// calling this function should leave all liberty related tables untouched that we can remove these below
							$cls->expungingAttachment( $pAttachmentId, $contentIds );
						}
					}

					// --- Do the final cleanup of liberty related tables ---
					if( $expungeFunc( $pAttachmentId )) {
						// Remove the primary ID from any content if it is this attachment
						$sql = "UPDATE `".BIT_DB_PREFIX."liberty_content` SET `primary_attachment_id`=NULL WHERE `primary_attachment_id` = ?";
						$this->mDb->query( $sql, array( $pAttachmentId ) );
						// Delete the attachment record.
						$sql = "DELETE FROM `".BIT_DB_PREFIX."liberty_attachments` WHERE `attachment_id`=?";
						$this->mDb->query( $sql, array( $pAttachmentId ) );

						// Remove attachment from memory
						unset( $this->mStorage[$pAttachmentId] );
					}
				} else {
					print( "Expunge function not found for this content!" );
					$ret = NULL;
				}
			}
		}

		return $ret;
	}

	/**
	 * Called during attachment deletion to notify a content type that an attachment for content of that type is going away so
     * it can take appropriate action. Note that it is VITAL that content NOT call expunge(TRUE) as part of handling this
	 * notification or else the universe will implode.
	 *
	 * @param the id of the attachment being deleted
	 * @param an array of content_ids that reference this attachment that are of this type.
     */
	function expungingAttachment($pAttachmentId, $pContentIds) {
		// The default is to ignore this notification.
	}

	/**
	 * detach attachment from content
	 *
	 * @param array $pAttachmentId Attachment id that needs to be detached from the content loaded
	 * @param array $pContentId Optional content ID that the attachment should be detached from
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	function detachAttachment( $pAttachmentId, $pContentId = NULL ) {
		$ret = TRUE;
		if( @$this->verifyId( $pAttachmentId )) {
			if (empty($pContentId)) {
				$contentId = $this->mContentId;
			}
			if (@$this->verifyId( $contentId) ) {
				$query = "DELETE FROM `".BIT_DB_PREFIX."liberty_attachments_map` WHERE attachment_id = ? AND content_id = ?";
				$bindVars = array($pAttachmentId, $contentId);
				$ret = $this->mDb->query($query, $bindVars);
				// Remove the primary ID if it is this attachment
				$query = "UPDATE `".BIT_DB_PREFIX."liberty_content` SET primary_attachment_id=NULL WHERE primary_attachment_id = ? AND content_id = ?";
				$ret = $this->mDb->query($query, $bindVars);
			} else {
				$this->mErrors[] = tra("Unable to detach due to an invalid content id: ").$contentId;
				$ret = FALSE;
			}
		}
		else {
			$this->mErrors[] = tra("Unable to detach due to an invalid attachment id: ") . $pAttachmentId;
			$ret = FALSE;
		}

		return $ret;
	}

	/**
	 * fully load content and insert any attachments in $this->mStorage
	 * allow an optional content_id to be passed in to ease legacy lib style objects (like blogs, articles, etc.)
	 *
	 * @param array $pContentId
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	function load( $pContentId=NULL ) {
		// assume a derived class has joined on the liberty_content table, and loaded it's columns already.
		global $gLibertySystem;
		$conId = ( @$this->verifyId( $pContentId ) ? $pContentId : $this->mContentId );

		if( @$this->verifyId( $conId ) ) {
			LibertyContent::load( $conId );
			$query = "
				SELECT lam.`content_id`, la.*, lc.`primary_attachment_id`
				FROM `".BIT_DB_PREFIX."liberty_attachments_map` lam
					INNER JOIN `".BIT_DB_PREFIX."liberty_attachments` la ON( lam.`attachment_id` = la.`attachment_id` )
					LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content` lc ON( lam.`attachment_id` = lc.`primary_attachment_id` AND lc.`content_id` = lam.`content_id` )
				WHERE lam.`content_id`=?";
			if( $result = $this->mDb->query( $query,array( (int)$conId ))) {
				$this->mStorage = array();
				while( $row = $result->fetchRow() ) {
					if( $func = $gLibertySystem->getPluginFunction( $row['attachment_plugin_guid'], 'load_function' )) {
						$this->mStorage[$row['attachment_id']] = $func( $row );
						$this->mStorage[$row['attachment_id']]['is_primary'] = !empty( $row['primary_attachment_id'] );
					} else {
						print "No load_function for ".$row['attachment_plugin_guid']." ".$gLibertySystem->mPlugins[$row['attachment_plugin_guid']];
					}
				}
			}
		}
		return( TRUE );
	}

	/**
	 * load details of a given attachment
	 * allow an optional content_id to be passed in to ease legacy lib style objects (like blogs, articles, etc.)
	 *
	 * @param array $pAttachmentId
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	function getAttachment( $pAttachmentId ) {
		global $gLibertySystem;
		$ret = NULL;

		if( @$this->verifyId( $pAttachmentId ) ) {
			$query = "SELECT * FROM `".BIT_DB_PREFIX."liberty_attachments` a
					  WHERE a.`attachment_id`=?";
			if( $result = $this->mDb->query($query,array((int) $pAttachmentId)) ) {
				$ret = array();
				if( $row = $result->fetchRow() ) {
					if( $func = $gLibertySystem->getPluginFunction( $row['attachment_plugin_guid'], 'load_function'  ) ) {
						$ret = $func( $row );
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * This function will scan through liberty_content.data and will search for any occurrances of {attachemt id=<id>}
	 *
	 * @param array $pAttachmentId Attachment id of interest
	 * @access public
	 * @return array of content using a given attachment
	 */
	function scanForAttchmentUse( $pAttachmentId ) {
		global $gLibertySystem, $gBitSystem;
		if( @BitBase::verifyId( $pAttachmentId )) {
			$ret = array();
			$query = "
				SELECT
					uue.`login` AS `modifier_user`, uue.`real_name` AS `modifier_real_name`, uue.`user_id` AS `modifier_user_id`,
					uuc.`login` AS `creator_user`, uuc.`real_name` AS `creator_real_name`, uuc.`user_id` AS `creator_user_id`,
					lc.`title`, lc.`data`, lc.`last_modified`, lc.`content_type_guid`, lc.`ip`, lc.`created`, lc.`content_id`
				FROM `".BIT_DB_PREFIX."liberty_content` lc
					INNER JOIN `".BIT_DB_PREFIX."users_users` uuc ON (lc.`modifier_user_id`=uuc.`user_id`)
					INNER JOIN `".BIT_DB_PREFIX."users_users` uue ON (lc.`modifier_user_id`=uue.`user_id`)
					LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_hits` lch ON( lc.`content_id` =  lch.`content_id`)
				ORDER BY ".$this->mDb->convertSortmode( 'last_modified_desc' );

			$result = $this->mDb->query( $query );
			$contentTypes = $gLibertySystem->mContentTypes;
			while( $aux = $result->fetchRow() ) {
				if( preg_match( "#\{attachment[^\}]*\bid\s*=\s*$pAttachmentId\b[^\}]*\}#", $aux['data'] ) && !empty( $contentTypes[$aux['content_type_guid']] )) {
					// quick alias for code readability
					$type                       = &$contentTypes[$aux['content_type_guid']];
					$aux['content_description'] = tra( $type['content_description'] );
					$aux['creator']             = ( isset( $aux['creator_real_name'] ) ? $aux['creator_real_name'] : $aux['creator_user'] );
					$aux['real_name']           = ( isset( $aux['creator_real_name'] ) ? $aux['creator_real_name'] : $aux['creator_user'] );
					$aux['editor']              = ( isset( $aux['modifier_real_name'] ) ? $aux['modifier_real_name'] : $aux['modifier_user'] );
					$aux['user']                = $aux['creator_user'];
					$aux['user_id']             = $aux['creator_user_id'];
					// create *one* object for each object *type* to  call virtual methods.
					if( empty( $type['content_object'] )) {
						include_once( $gBitSystem->mPackages[$type['handler_package']]['path'].$type['handler_file'] );
						$type['content_object'] = new $type['handler_class']();
					}

					if( !empty( $gBitSystem->mPackages[$type['handler_package']] )) {
						$aux['display_link'] = $type['content_object']->getDisplayLink( $aux['title'], $aux );
						$aux['title']        = $type['content_object']->getTitle( $aux );
						$aux['display_url']  = $type['content_object']->getDisplayUrl( $aux['content_id'], $aux );
					}
					$ret[] = $aux;
				}
			}
		}
		return $ret;
	}
}



/* -=-=-=-=-=-=-=-=-=-=-=- Liberty File Processing Functions -=-=-=-=-=-=-=-=-=-=-=- */


/**
 * Process uploaded files. Will automagically generate thumbnails for images
 *
 * @param array $pFileHash Data require to process the files
 * @param array $pFileHash['upload']['name'] (required) Name of the uploaded file
 * @param array $pFileHash['upload']['type'] (required) Mime type of the file uploaded
 * @param array $pFileHash['upload']['dest_path'] (required) Relative path where you want to store the file
 * @param array $pFileHash['upload']['source_file'] (required) Absolute path to file including file name
 * @param boolean $pFileHash['upload']['thumbnail'] (optional) Set to FALSE if you don't want to generate thumbnails
 * @param array $pFileHash['upload']['thumbnail_sizes'] (optional) Decide what sizes thumbnails you want to create: icon, avatar, small, medium, large
 * @param boolean $pMoveFile (optional) specify if you want to move or copy the original file
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function liberty_process_upload( &$pFileHash ) {
	// Check for evil file extensions that could be execed on the server
	if( preg_match( '/(.pl|.php|.php3|.php4|.phtml|.py|.cgi|.asp|.jsp|.sh|.shtml)$/', $pFileHash['upload']['name'] ) ) {
		$pFileHash['upload']['type'] = 'text/plain';
		$pFileHash['upload']['name'] = $pFileHash['upload']['name'].'.txt';
	}
	// Thumbs.db is a windows My Photos/ folder file, and seems to really piss off imagick
	if( (preg_match( '/^image\/*/', $pFileHash['upload']['type'] ) || preg_match( '/pdf/i', $pFileHash['upload']['type'] ) ) && $pFileHash['upload']['name'] != 'Thumbs.db' ) {
		$ret = liberty_process_image( $pFileHash['upload'] );
	} else {
		$ret = liberty_process_generic( $pFileHash['upload'] );
	}
	return $ret;
}

/**
 * liberty_process_archive
 *
 * @param array $pFileHash
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function liberty_process_archive( &$pFileHash ) {
	// sanity check: make sure tmp_name isn't empty. will scan / if it is
	if( !is_array( $pFileHash ) || empty( $pFileHash['tmp_name'] ) || empty( $pFileHash['name'] ) ) {
		return FALSE;
	}

	$cwd = getcwd();
	// if the file has been uploaded using a form, we'll process the uploaded
	// file directly. if it's been ftp uploaded or some other method used,
	// we'll copy the file. in the case of xuploaded files, the files have been
	// processed but don't have to be copied
	if( empty( $pFileHash['preprocessed'] ) && !is_uploaded_file( $pFileHash['tmp_name'] ) && is_file( $pFileHash['tmp_name'] ) ) {
		$tmpDir = get_temp_dir();
		$copyFile = tempnam( !empty( $tmpDir ) ? $tmpDir : '/tmp', $pFileHash['name'] );
		copy( $pFileHash['tmp_name'], $copyFile );
		$pFileHash['tmp_name'] = $copyFile;
	}

	$dir = dirname( $pFileHash['tmp_name'] );
	$upExt = strtolower( substr( $pFileHash['name'], ( strrpos( $pFileHash['name'], '.' ) + 1 ) ) );
	$baseDir = $dir.'/';
	if( is_file( $pFileHash['tmp_name'] ) ) {
		global $gBitUser;
		$baseDir .= $gBitUser->mUserId;
	}

	$destDir = $baseDir.'/'.basename( $pFileHash['tmp_name'] );
	// this if is very important logic back so subdirs get processed properly
	if( ( is_dir( $baseDir ) || mkdir( $baseDir ) ) && @mkdir( $destDir ) ) {
		// Some commands don't nicely support extracting to other directories
		chdir( $destDir );
		list( $mimeType, $mimeExt ) = split( '/', strtolower( $pFileHash['type'] ) );
		switch( $mimeExt ) {
			case 'x-rar-compressed':
			case 'x-rar':
				$shellResult = shell_exec( "unrar x \"{$pFileHash['tmp_name']}\" \"$destDir\"" );
				break;
			case 'x-bzip2':
			case 'bzip2':
			case 'x-gzip':
			case 'gzip':
			case 'x-tgz':
			case 'x-tar':
			case 'tar':
				switch( $upExt ) {
					case 'gz':
					case 'tgz': $compressFlag = '-z'; break;
					case 'bz2': $compressFlag = '-j'; break;
					default: $compressFlag = ''; break;
				}
				$shellResult = shell_exec( "tar -x $compressFlag -f \"{$pFileHash['tmp_name']}\"  -C \"$destDir\"" );
				break;
			case 'x-zip-compressed':
			case 'x-zip':
			case 'zip':
				$shellResult = shell_exec( "unzip \"{$pFileHash['tmp_name']}\" -d \"$destDir\"" );
				break;
			case 'x-stuffit':
			case 'stuffit':
				$shellResult = shell_exec( "unstuff -d=\"$destDir\" \"{$pFileHash['tmp_name']}\" " );
				break;
			default:
				if( $upExt == 'zip' ) {
					$shellResult = shell_exec( "unzip \"{$pFileHash['tmp_name']}\" -d \"$destDir\"" );
				} elseif( $upExt == 'rar' ) {
					$shellResult = shell_exec( "unrar x \"{$pFileHash['tmp_name']}\" \"$destDir\"" );
				} elseif( $upExt == 'sit' || $upExt == 'sitx' ) {
					print( "unstuff -d=\"$destDir\" \"{$pFileHash['tmp_name']}\" " );
					$shellResult = shell_exec( "unstuff -d=\"$destDir\" \"{$pFileHash['tmp_name']}\" " );
				} else {
					$destDir = NULL;
				}
				break;
		}
	}
	//vd($shellResult);
	chdir( $cwd );

	// if we created a copy of the original, we remove it
	if( !empty( $copyFile ) ) {
		@unlink( $copyFile );
	}

	if( preg_match( "!^/+$!", $destDir )) {
		// obviously something went horribly wrong
		return FALSE;
	} else {
		return $destDir;
	}
}

/**
 * liberty_process_generic
 *
 * @param array $pFileHash
 * @param array $pMoveFile
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function liberty_process_generic( &$pFileHash, $pMoveFile=TRUE ) {
	$ret = NULL;
	$destBase = $pFileHash['dest_path'].$pFileHash['name'];
	$actualPath = BIT_ROOT_PATH.$destBase;
	if( is_file( $pFileHash['source_file']) ) {
		if( $pFileHash['source_file'] == $actualPath ) {
			// do nothing if source and dest are the same
		} elseif( $pMoveFile ) {
			if( is_uploaded_file( $pFileHash['source_file'] ) ) {
				move_uploaded_file( $pFileHash['source_file'], $actualPath );
			} else {
				rename( $pFileHash['source_file'], $actualPath );
			}
		} else {
			copy( $pFileHash['source_file'], $actualPath );
		}
		$ret = $destBase;
	}
	$pFileHash['size'] = filesize( $actualPath );

	return $ret;
}


/**
 * liberty_process_image
 *
 * @param array $pFileHash
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function liberty_process_image( &$pFileHash ) {
	global $gBitSystem;
	$ret = NULL;

	list($type, $ext) = split( '/', strtolower( $pFileHash['type'] ) );
	mkdir_p( BIT_ROOT_PATH.$pFileHash['dest_path'] );
	if( $resizePath = liberty_process_generic( $pFileHash ) ) {
		$pFileHash['source_file'] = BIT_ROOT_PATH.$resizePath;
		//set permissions if possible - necessary for some wonky shared hosting environments
		if(chmod($pFileHash['source_file'], 0644)){
			//does nothing, but fails elegantly
		}
		$nameHold = $pFileHash['name'];
		$sizeHold = $pFileHash['size'];
		$ret = $pFileHash['source_file'];
		// do not thumbnail only if intentionally set to FALSE
		if( !isset( $pFileHash['thumbnail'] ) || $pFileHash['thumbnail']==TRUE ) {
			liberty_generate_thumbnails( $pFileHash );
		}
		$pFileHash['name'] = $nameHold;
		$pFileHash['size'] = $sizeHold;
	}
	return $ret;
}

/**
 * liberty_clear_thumbnails will clear all thummbnails found in a given directory
 *
 * @param array $pFileHash['dest_path'] should contain the path to the dir where we should remove thumbnails
 * @access public
 * @return TRUE on success, FALSE on failure
 */
function liberty_clear_thumbnails( &$pFileHash ) {
	if( !empty( $pFileHash['dest_path'] )) {
		// get thumbnails we want to remove
		if( $thumbs = liberty_fetch_thumbnails( $pFileHash['dest_path'], NULL, NULL, FALSE )) {
			foreach( $thumbs as $thumb ) {
				$thumb = BIT_ROOT_PATH.$thumb;
				if( is_writable( $thumb ) ) {
					unlink( $thumb );
				}
			}
			// just to make sure that we have all thumbnails cleared, we run through another round
			if( $thumbs = liberty_fetch_thumbnails( $pFileHash['dest_path'], NULL, NULL, FALSE )) {
				foreach( $thumbs as $thumb ) {
					$thumb = BIT_ROOT_PATH.$thumb;
					if( is_writable( $thumb ) ) {
						unlink( $thumb );
					}
				}
			}
		}
	}
	return TRUE;
}

/**
 * liberty_get_function
 *
 * @param array $pType
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function liberty_get_function( $pType ) {
	global $gBitSystem;
	return 'liberty_'.$gBitSystem->getConfig( 'image_processor', 'gd' ).'_'.$pType.'_image';
}

/**
 * liberty_generate_thumbnails
 *
 * @param array $pFileHash
 * @access public
 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
 */
function liberty_generate_thumbnails( &$pFileHash ) {
	global $gBitSystem, $gThumbSizes;
	$resizeFunc = liberty_get_function( 'resize' );

	// allow custom selecteion of thumbnail sizes
	if( empty( $pFileHash['thumbnail_sizes'] ) ) {
		$pFileHash['thumbnail_sizes'] = array( 'icon', 'avatar', 'small', 'medium', 'large' );
	}

	if(
		( !preg_match( '/image\/(gif|jpg|jpeg|png)/', strtolower( $pFileHash['type'] )) && $gBitSystem->isFeatureActive( 'liberty_jpeg_originals' ))
		|| in_array( 'original', $pFileHash['thumbnail_sizes'] )
	) {
		// jpeg version of original
		$pFileHash['dest_base_name'] = 'original';
		$pFileHash['name'] = 'original.jpg';
		$pFileHash['max_width'] = MAX_THUMBNAIL_DIMENSION;
		$pFileHash['max_height'] = MAX_THUMBNAIL_DIMENSION;
		$pFileHash['original_path'] = BIT_ROOT_PATH.$resizeFunc( $pFileHash );
	}

	// override $mimeExt if we have a custom setting for it
	if( $gBitSystem->isFeatureActive( 'liberty_thumbnail_format' )) {
		$mimeExt = $gBitSystem->getConfig( 'liberty_thumbnail_format' );
	} else {
		list( $type, $mimeExt ) = split( '/', strtolower( $pFileHash['type'] ));
	}

	if( preg_match( "!(png|gif)!", $mimeExt )) {
		$destExt = '.'.$mimeExt;
	} else {
		$destExt = '.jpg';
	}

	foreach( $pFileHash['thumbnail_sizes'] as $thumbSize ) {
		if( isset( $gThumbSizes[$thumbSize] )) {
			$pFileHash['dest_base_name'] = $thumbSize;
			$pFileHash['name'] = $thumbSize.$destExt;
			$pFileHash['max_width'] = $gThumbSizes[$thumbSize]['width'];
			$pFileHash['max_height'] = $gThumbSizes[$thumbSize]['height'];
			$pFileHash['icon_thumb_path'] = BIT_ROOT_PATH.$resizeFunc( $pFileHash, TRUE );
		}
	}
}

/**
 * fetch all available thumbnails for a given item. if no thumbnails are present, get thumbnailing image or the appropriate mime type icon
 *
 * @param string $pFilePath Relative path to file we want to get thumbnails for (needs to include file name for mime icons)
 * @param string $pAltImageUrl URL to an alternative fallback image such as a background thumbnailer image
 * @param array $pThumbSizes array of images to search for in the pFilePath
 * @access public
 * @return array of available thumbnails or mime icons
 */
function liberty_fetch_thumbnails( $pFilePath, $pAltImageUrl = NULL, $pThumbSizes = NULL, $pMimeImage = TRUE ) {
	global $gBitSystem, $gThumbSizes;
	$ret = array();

	if( empty( $pThumbSizes )) {
		$pThumbSizes = array_keys( $gThumbSizes );
	}

	// liberty file processors automatically pick the best format for us. we can force a format though.
	$exts = array( $gBitSystem->getConfig( 'liberty_thumbnail_format', 'jpg' ), 'jpg', 'png', 'gif' );
	// using array_unique on the above will give us the best order to look for the thumbnails
	$exts = array_unique( $exts );

	// $pFilePath might already be the absolute path or it might already contain BIT_ROOT_URL
	if( !( $pFilePath = preg_replace( "!^".preg_quote( BIT_ROOT_PATH, "!" )."!", "", $pFilePath ))) {
		$pFilePath = preg_replace( "!^".preg_quote( BIT_ROOT_URL, "!" )."!", "", $pFilePath );
	}

	// if the filepath ends with a traling / we know it's a dir. we just assume that the original file is a jpg
	// this has no outcome on the following code unless we don't find anythig and we need to get the mime type thumb
	if( preg_match( "!/$!", $pFilePath )) {
		$pFilePath .= 'dummy.jpg';
	}

	foreach( $pThumbSizes as $size ) {
		foreach( $exts as $ext ) {
			if( empty( $ret[$size] ) && is_readable( BIT_ROOT_PATH.dirname( $pFilePath ).'/'.$size.'.'.$ext )) {
				$ret[$size] = str_replace( "//", "/", BIT_ROOT_URL.dirname( $pFilePath ).'/'.$size.'.'.$ext );
			}
		}

		if( $pMimeImage && empty( $ret[$size] )) {
			if( $pAltImageUrl ) {
				$ret[$size] = $pAltImageUrl;
			} else {
				$ret[$size] = LibertySystem::getMimeThumbnailURL( $gBitSystem->lookupMimeType( $pFilePath ), substr( $pFilePath, strrpos( $pFilePath, '.' ) + 1 ));
			}
		}
	}

	return $ret;
}

/**
 * fetch a single available thumbnail for a given item. if no thumbnail is present, return NULL
 *
 * @param string $pFilePath Relative path to file we want to get thumbnails for (needs to include file name for mime icons)
 * @param string $pThumbSize image size to search for in the pFilePath
 * @param string $pThumbSize path to alternative image that will be shown if nothing is found
 * @access public
 * @return string url
 */
function liberty_fetch_thumbnail_url( $pFilePath, $pThumbSize = 'small', $pAltImageUrl = NULL, $pMimeImage = FALSE ) {
	if( !empty( $pFilePath )) {
		$ret = array();
		$ret = liberty_fetch_thumbnails( $pFilePath, $pAltImageUrl, array( $pThumbSize ), $pMimeImage );
		return $ret[$pThumbSize];
	} else {
		return NULL;
	}
}
?>
