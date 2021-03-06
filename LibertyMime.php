<?php
/**
 * Manages liberty Uploads
 *
 * @package  liberty
 * @version  $Header$
 */

/**
 * required setup
 */
require_once( LIBERTY_PKG_PATH.'LibertyAttachable.php' );

/**
 * LibertyMime class
 *
 * @package liberty
 */
class LibertyMime extends LibertyAttachable {
	var $mStoragePrefs = NULL;

	/**
	 * Initiates class
	 *
	 * @access public
	 * @return void
	 */
	function LibertyMime() {
		LibertyAttachable::LibertyAttachable();
	}

	/**
	 * load the attachments for a given content id and then stuff them in mStorage
	 * 
	 * @param array $pContentId 
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	function load( $pContentId = NULL, $pPluginParams = NULL ) {
		global $gLibertySystem;
		// assume a derived class has joined on the liberty_content table, and loaded it's columns already.
		$contentId = ( @BitBase::verifyId( $pContentId ) ? $pContentId : $this->mContentId );

		if( @BitBase::verifyId( $contentId )) {
			// load up the content
			LibertyContent::load( $contentId );

			// don't loadAttachmentPreferences() when we are forcing the installer since it breaks the login process before 2.1.0-beta
			if( !defined( 'INSTALLER_FORCE' ) && !defined( 'LOGIN_VALIDATE' )) {
				$this->loadAttachmentPreferences();
			}

			$query = "SELECT * FROM `".BIT_DB_PREFIX."liberty_attachments` la WHERE la.`content_id`=? ORDER BY la.`pos` ASC, la.`attachment_id` ASC";
			if( $result = $this->mDb->query( $query,array( (int)$contentId ))) {
				$this->mStorage = array();
				while( $row = $result->fetchRow() ) {
					if( !empty( $row['is_primary'] ) ) {
						// used by edit tpl's among other things
						$this->mInfo['primary_attachment_id'] = $row['attachment_id'];
					} elseif( !$this->getField( 'primary_attachment_id' ) && !empty( $row['attachment_id'] ) ) {
						// primary was not set by the above, default to first row. might be reset by later iterations via if is_primary above
						$this->mInfo['primary_attachment_id'] = $row['attachment_id'];
					}
					if( $func = $gLibertySystem->getPluginFunction( $row['attachment_plugin_guid'], 'load_function', 'mime' )) {
						// we will pass the preferences by reference that the plugin can easily update them
						if( empty( $this->mStoragePrefs[$row['attachment_id']] )) {
							$this->mStoragePrefs[$row['attachment_id']] = array();
						}
						$this->mStorage[$row['attachment_id']] = $func( $row, $this->mStoragePrefs[$row['attachment_id']], $pPluginParams );
					} else {
						print "No load_function for ".$row['attachment_plugin_guid'];
					}
				}
			}
		}
		return( TRUE );
	}

	/**
	 * need a way to call load within class reference
	 * this is basically a substitute for a mime load 
	 * or display api hook until Mime can truly be converted
	 */
	function preflightLoad(){
		if( BitBase::verifyId( $this->mContentId ) ){
			LibertyMime::load();
		}
	}

	/**
	 * Store a new upload
	 *
	 * @param array $pStoreHash contains all data to store the gallery
	 * @return bool TRUE on success, FALSE if store could not occur. If FALSE, $this->mErrors will have reason why
	 * @access public
	 **/
	function store( &$pStoreHash ) {
		global $gLibertySystem;
		// make sure all the data is in order
		if( LibertyMime::verify( $pStoreHash ) && ( !empty( $pStoreHash['skip_content_store'] ) || LibertyContent::store( $pStoreHash ) ) ) {
			$this->mDb->StartTrans();
			// files have been uploaded
			if( !empty( $pStoreHash['upload_store']['files'] ) && is_array( $pStoreHash['upload_store']['files'] )) {

				foreach( $pStoreHash['upload_store']['files'] as $key => $upload ) {
					// if we don't have an upload, we'll simply update the file settings using the mime plugins
					if( empty( $upload['tmp_name'] )) {
						if( @BitBase::verifyId( $upload['attachment_id'] )) {
							// since the form might have all options unchecked, we need to call the update function regardless
							// currently i can't think of a better way to get the plugin guid back when $pStoreHash[plugin] is
							// empty. - xing - Friday Jul 11, 2008   20:21:18 CEST
							if( !empty( $this->mStorage[$upload['attachment_id']] )) {
								$attachment = $this->mStorage[$upload['attachment_id']];
								$data = array();
								if( !empty( $pStoreHash['plugin'][$upload['attachment_id']][$attachment['attachment_plugin_guid']] )) {
									$data = $pStoreHash['plugin'][$upload['attachment_id']][$attachment['attachment_plugin_guid']];
								}
								if( !$this->updateAttachmentParams( $upload['attachment_id'], $attachment['attachment_plugin_guid'], $data )) {
									$this->mErrors['attachment_update'] = "There was a problem updating the file settings.";
								}
							}
						}
						// skip rest of process
						continue;
					}

					$storeRow = $pStoreHash['upload_store'];
					unset( $storeRow['files'] );

					// copy by reference that filetype changes are made in lookupMimeHandler()
					$storeRow['upload'] = &$upload;
					if( isset( $pStoreHash['thumbnail'] ) ) {
						$storeRow['upload']['thumbnail'] = $pStoreHash['thumbnail'];
					}

					// when content is created the content_id is only available after LibertyContent::store()
					$storeRow['content_id'] = $pStoreHash['content_id'];

					// let the plugin do the rest
					$guid = $gLibertySystem->lookupMimeHandler( $upload );
					$this->pluginStore( $storeRow, $guid, @BitBase::verifyId( $upload['attachment_id'] ));

					// finally, we need to update the original hash with the new values
					$pStoreHash['upload_store']['files'][$key] = $storeRow;
				}
			}

			// some mime plugins might not have file uploads - these plugins will tell us what mime handlers they are using
			if( !empty( $pStoreHash['mimeplugin'] ) && is_array( $pStoreHash['mimeplugin'] )) {
				foreach( $pStoreHash['mimeplugin'] as $guid => $storeRow ) {
					// check to see if we have anything worth storing in the array
					$plugin_store = FALSE;
					foreach( array_values( $storeRow ) as $value ) {
						if( !empty( $value )) {
							$plugin_store = TRUE;
						}
					}

					if( !empty( $plugin_store )) {
						// when content is created the content_id is only available after LibertyContent::store()
						$storeRow['content_id'] = $pStoreHash['content_id'];
						$this->pluginStore( $storeRow, $guid, @BitBase::verifyId( $upload['attachment_id'] ));
					}
				}
			}

			// deal with the primary attachment after we've dealt with all the files
			$this->setPrimaryAttachment(
				$pStoreHash['liberty_attachments']['primary'],
				$pStoreHash['content_id'],
				empty( $pStoreHash['liberty_attachments']['auto_primary'] ) || $pStoreHash['liberty_attachments']['auto_primary'] ? TRUE : FALSE
			);

			$this->invokeServices( 'upload_store_function', $pStoreHash );

			// Roll back if something went wrong
			if( empty( $this->mErrors )) {
				$this->mDb->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
			}
		}

		return( count( $this->mErrors ) == 0 );
	}


	function storeAttachment( &$pParamHash ){
		global $gLibertySystem;
		// can only store attachments to existing content
		if( BitBase::verifyId( $this->mContentId ) && LibertyMime::verifyAttachmentHash( $pParamHash ) ){
			$this->mDb->StartTrans();	

			// let the plugin do the rest
			if( $guid = $gLibertySystem->lookupMimeHandler( $pParamHash['upload_store']['upload'] ) ){
				$this->pluginStore( $pParamHash['upload_store'], $guid, @BitBase::verifyId( $pParamHash['upload_store']['attachment_id'] ));

				$this->invokeServices( 'upload_store_function', $pParamHash );

				if( !empty( $pParamHash['upload_store']['errors'] ) ){
					$this->mErrors['attachment'][] = $pParamHash['upload_store']['errors'];
				}
			}else{
				$this->setError['plugin'] = tra('Plugin not found for attachment');
			}

			// Roll back if something went wrong
			if( empty( $this->mErrors )) {
				$this->mDb->CompleteTrans();
			} else {
				$this->mDb->RollbackTrans();
			}
			return TRUE;
		}
		else{
			return FALSE;
		}
	}


	// @TODO rename verifyAttachment to be verifyFile and rename this to verifyAttachment
	/**
	 * @param $pParamHash['file'] 				required file reference
	 * @param $pParamHash['content_id'] 		required content_id will be populated if not set
	 * @param $pParamHash['thumbnail_sizes'] 	optional custom thumbnail sizes
	 */
	function verifyAttachmentHash( &$pParamHash ){
		if( !empty( $pParamHash['file'] ) ){
			// content_id is required
			$pParamHash['content_id'] = !empty( $pParamHash['content_id'] )?$pParamHash['content_id']:(BitBase::verifyId( $this->mContentId )?$this->mContentId:NULL);
			if( !empty( $pParamHash['content_id'] ) ){
				$pParamHash['upload_store']['content_id'] = $pParamHash['content_id']; 
			}else{
				$this->setError( 'content_id', tra('Invalid content id') );
			}

			// file 
			if( !empty( $pParamHash['file'] ) ){
				$pParamHash['upload_store']['upload'] = LibertyMime::verifyAttachment( $pParamHash['file'] );
			}else{
				// @TODO store() says it doesnt require a file, it can update some other file data - what is that?
				$this->setError( 'file', tra( 'No file to store' ) );
			}

			// custom thumbnail sizes
			if( !empty( $pParamHash['thumbnail_sizes'] ) ){
				$pParamHash['upload_store']['thumbnail_sizes'] = $pParamHash['thumbnail_sizes'];
			}

			// attachment_id - where the hell does this come from when updating? and why do i care - wjames
			if( !empty( $pParamHash['attachment_id'] ) ){
				$pParamHash['upload_store']['attachment_id'] = $pParamHash['attachment_id'];
			}

			// what the hell is this stuff? - wjames
			if( !empty( $pParamHash['thumbnail'] ) ) {
				$pParamHash['upload_store']['thumbnail'] = $pParamHash['thumbnail'];
			}
		}
		return count( $this->mErrors == 0 );
	}


	/**
	 * pluginStore will use a given plugin to store uploaded file data
	 * 
	 * @param string $pGuid GUID of plugin
	 * @param array $pStoreHash Data to be prcessed and stored by the plugin
	 * @param boolean $pUpdate set to TRUE if this is just an update
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	function pluginStore( &$pStoreHash, $pGuid, $pUpdate = FALSE ) {
		global $gLibertySystem;
		if( !empty( $pStoreHash ) && $verify_function = $gLibertySystem->getPluginFunction( $pGuid, 'verify_function' )) {
			// pass along a pointer to the content object
			$pStoreHash['this'] = &$this;
			// verify the uploaded file using the plugin
			if( $verify_function( $pStoreHash )) {
				if( $process_function = $gLibertySystem->getPluginFunction( $pGuid, (( $pUpdate ) ? 'update_function' : 'store_function' ))) {
					if( !$process_function( $pStoreHash )) {
						$this->mErrors = array_merge( $this->mErrors, $pStoreHash['errors'] );
					}
				} else {
					$this->mErrors['store_function'] = tra( 'No suitable store function found.' );
				}
			} else {
				$this->mErrors = array_merge( $this->mErrors, $pStoreHash['errors'] );
			}
		} else {
			$this->mErrors['verify_function'] = tra( 'No suitable verify function found.' );
		}

		return( count( $this->mErrors ) == 0 );
	}

	/**
	 * Verify content that is about to be stored
	 * 
	 * @param array $pStoreHash hash of all data that needs to be stored in the database
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason
	 * @todo If one of the uploaded files is an update, place the attachment_id with the upload hash in $_FILES['uploads'] or in _files_override
	 */
	function verify( &$pParamHash ) {
		global $gBitUser, $gLibertySystem;

		$uploads = array();

		// check to see if we have any files to upload
		if( isset( $pParamHash['_files_override'] )) {
			// we have been passed in a manually stuffed files attachment, such as a custom uploader would have done.
			// process this, and skip over $_FILES
			$uploads = $pParamHash['_files_override'];
		// uploads is the reserved files array for liberty pkg upload forms
		} elseif( !empty( $_FILES['uploads']['name'] ) ){ 
			$count = count( $_FILES['uploads']['name'] );
			$i = 0;
			while( $i < $count ){
				$file = array();
				foreach( $_FILES['uploads'] as $key => $values ) {
					$file[$key] = $values[$i];
				}
				if( !empty( $file['name'] ) || !empty( $file['attachment_id'] )) {
					$uploads[] = $file;
				}
				$i++;
			}
		}

		// verify uploads
		if( !empty( $uploads ) ) {
			foreach( array_keys( $uploads ) as $file ) {
				$pParamHash['upload_store']['files'][$file] = LibertyMime::verifyAttachment( $uploads[$file] );
			}
		}

		// don't check for p_liberty_attach_attachments permission on bitpermuser class so registration with avatar upload works
		if( strtolower( get_class( $this )) == 'bitpermuser' ) {
			$pParamHash['upload_store']['no_perm_check'] = TRUE;
		}

		// check for the required permissions to upload a file to the liberty attachments area
		if( !empty( $uploads ) && empty( $pParamHash['no_perm_check'] )) {
			if( !$this->hasUserPermission( 'p_liberty_attach_attachments' )) {
				$this->mErrors['permission'] = tra( 'You do not have permission to upload attachments.' );
			}
		}

		// primary attachment. Allow 'none' to clear the primary.
		if( !@BitBase::verifyId( $pParamHash['liberty_attachments']['primary'] ) && ( empty( $pParamHash['liberty_attachments']['primary'] ) || $pParamHash['liberty_attachments']['primary'] != 'none' ) ) {
			$pParamHash['liberty_attachments']['primary'] = NULL;
		}

		// if we have an error we get them all by checking parent classes for additional errors
		if( count( $this->mErrors ) > 0 ){
			// check errors of LibertyContent since LibertyMime means to override the parent verify
			LibertyContent::verify( $pParamHash );
		}

		return ( count( $this->mErrors ) == 0 );
	}


	/**
	 * getThumbnailUrl will fetch the primary thumbnail for a given content. If nothing has been set, it will fetch the last thumbnail it can find.
	 * 
	 * @param string $pSize 
	 * @param array $pInfoHash 
	 * @access public
	 * @return boolean TRUE on success, FALSE on failure - $this->mErrors will contain reason for failure
	 */
	function getThumbnailUrl( $pSize='small', $pInfoHash=NULL, $pSecondary=NULL ) {
		$ret = NULL;
		if( !empty( $pInfoHash ) ) {
			// do some stuff if we are given a hash of stuff
		} elseif( BitBase::verifyId( $this->mContentId ) && !empty( $this->mStorage ) ) {
			foreach( array_keys( $this->mStorage ) as $attachmentId ) {
				if( !empty( $this->mStorage[$attachmentId]['is_primary'] ) ) {
					break;
				}
			}
			if( !empty( $this->mStorage[$attachmentId]['thumbnail_url'][$pSize] )) {
				$ret = $this->mStorage[$attachmentId]['thumbnail_url'][$pSize];
			}
		} 
		if( empty( $ret ) ) {
			$ret = parent::getThumbnailUrl( $pSize, $pInfoHash, $pSecondary );
		}
		return $ret;
	}

	/**
	 * updateAttachmentParams will update attachment parameters
	 * 
	 * @param numeric $pAttachmentId attachment_id of the item we want the prefs from (optional)
	 * @param string $pPluginGuid GUID of the plugin that should process the data
	 * @param array $pParamHash Data to be processed by the plugin
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	function updateAttachmentParams( $pAttachmentId, $pPluginGuid, $pParamHash = array() ) {
		global $gLibertySystem;
		$ret = FALSE;

		if( BitBase::verifyId( $pAttachmentId )) {
			if( !empty( $this ) && !empty( $this->mStorage[$pAttachmentId] )) {
				$file = $this->mStorage[$pAttachmentId];
			} else {
				$file = LibertyMime::getAttachment( $pAttachmentId );
			}

			if( @BitBase::verifyId( $file['attachment_id'] ) && !empty( $pPluginGuid ) && ( $update_function = $gLibertySystem->getPluginFunction( $pPluginGuid, 'update_function', 'mime' ))) {
				if( $update_function( $file, $pParamHash )) {
					$ret = TRUE;
				} else {
					if( !empty( $file['errors'] )) {
						$this->mErrors['param_update'] = $file['errors'];
					} else {
						$this->mErrors['param_update'] = tra( 'There was an unspecified error while updating the file.' );
					}
				}
			}
		}
		return $ret;
	}

	/**
	 * verifyAttachment will perform a generic check if a file is valid for processing
	 * 
	 * @param array $pFile file array
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	function verifyAttachment( $pFile ) {
		if( !empty( $pFile['tmp_name'] ) && is_file( $pFile['tmp_name'] ) && empty( $pFile['error'] ) || !empty( $pFile['attachment_id'] )) {
			return $pFile;
		}
	}

	/**
	 * Increment the item hit flag by 1
	 *
	 * @access public
	 * @param numeric $pAttachmentId Attachment ID
	 * @return adodb query result or FALSE
	 * @note we're abusing the hits column for download count.
	 */
	function addDownloadHit( $pAttachmentId = NULL ) {
		global $gBitUser, $gBitSystem;
		if( @BitBase::verifyId( $pAttachmentId ) && $attachment = LibertyMime::getAttachment( $pAttachmentId )) {
			if( !$gBitUser->isRegistered() || ( $gBitUser->isRegistered() && $gBitUser->mUserId != $attachment['user_id'] )) {
				$bindVars = array( $pAttachmentId );
				if( $gBitSystem->mDb->getOne( "SELECT `attachment_id` FROM `".BIT_DB_PREFIX."liberty_attachments` WHERE `attachment_id` = ? AND `hits` IS NULL", $bindVars )) {
					$query = "UPDATE `".BIT_DB_PREFIX."liberty_attachments` SET `hits` = 1 WHERE `attachment_id` = ?";
				} else {
					$query = "UPDATE `".BIT_DB_PREFIX."liberty_attachments` SET `hits` = `hits`+1 WHERE `attachment_id` = ?";
				}
				return $gBitSystem->mDb->query( $query, $bindVars );
			}
		}
		return FALSE;
	}

	/**
	 * === Attachment Preferences ===
	 */

	/**
	 * Returns the attachment preference value for the passed in key.
	 *
	 * @param string Hash key for the mPrefs value
	 * @param string Default value to return if the preference is empty
	 * @param int Optional content_id for arbitrary content preference
	 */
	function getAttachmentPreference( $pAttachmentId, $pPrefName, $pPrefDefault = NULL ) {
		if( is_null( $this->mStoragePrefs ) ) {
			$this->loadAttachmentPreferences();
		}

		$ret = NULL;
		if( @BitBase::verifyId( $pAttachmentId ) && !empty( $pPrefName )) {
			if( isset( $this->mStoragePrefs ) && isset( $this->mStoragePrefs[$pAttachmentId][$pPrefName] )) {
				$ret = $this->mStoragePrefs[$pAttachmentId][$pPrefName];
			} else {
				$ret = $pPrefDefault;
			}
		}

		return $ret;
	}

	/**
	 * Returns the attachment preferences for a given attachment id
	 *
	 * @param string Hash key for the mPrefs value
	 * @param string Default value to return if the preference is empty
	 * @param int Optional content_id for arbitrary content preference
	 */
	function getAttachmentPreferences( $pAttachmentId ) {
		global $gBitSystem;

		$ret = array();
		if( !empty( $this ) && is_subclass_of( $this, "LibertyMime" ) ) {
			// we're loading from within object
			if( is_null( $this->mStoragePrefs )) {
				$this->loadAttachmentPreferences();
			}

			if( @BitBase::verifyId( $pAttachmentId ) && isset( $this->mStoragePrefs[$pAttachmentId] )) {
				$ret = $this->mStoragePrefs[$pAttachmentId];
			}
		} else {
			// if the object isn't loaded, we need to get the prefs from the database
			$sql = "SELECT `pref_name`, `pref_value` FROM `".BIT_DB_PREFIX."liberty_attachment_prefs` WHERE `attachment_id` = ?";
			$ret = $gBitSystem->mDb->getAssoc( $sql, array( $pAttachmentId ));
		}

		return $ret;
	}

	/**
	 * setAttachmentPreference will set an attachment preferences without storing it in the database
	 * 
	 * @param array $pAttachmentId 
	 * @param array $pPrefName 
	 * @param array $pPrefValue 
	 * @access public
	 * @return TRUE on success, FALSE on failure - mErrors will contain reason for failure
	 */
	function setAttachmentPreference( $pAttachmentId, $pPrefName, $pPrefValue ) {
		$this->mStoragePrefs[$pAttachmentId][$pPrefName] = $pPrefValue;
	}

	/**
	 * Saves a preference to the liberty_content_prefs database table with the 
	 * given pref name and value. If the value is NULL, the existing value will 
	 * be delete and the value will not be saved. However, a zero will be 
	 * stored.
	 *
	 * @param string Hash key for the prefs value
	 * @param string Value for the prefs hash key
	 */
	function storeAttachmentPreference( $pAttachmentId, $pPrefName, $pPrefValue = NULL ) {
		global $gBitSystem;
		$ret = FALSE;
		if( @BitBase::verifyId( $pAttachmentId ) && !empty( $pPrefName )) {
			$query    = "DELETE FROM `".BIT_DB_PREFIX."liberty_attachment_prefs` WHERE `attachment_id` = ? AND `pref_name` = ?";
			$bindvars = array( $pAttachmentId, $pPrefName );
			$result   = $gBitSystem->mDb->query( $query, $bindvars );
			if( !is_null( $pPrefValue )) {
				$query      = "INSERT INTO `".BIT_DB_PREFIX."liberty_attachment_prefs` (`attachment_id`,`pref_name`,`pref_value`) VALUES(?, ?, ?)";
				$bindvars[] = substr( $pPrefValue, 0, 250 );
				$result     = $gBitSystem->mDb->query( $query, $bindvars );
			}

			// this function might be called statically
			if( !empty( $this ) && BitBase::verifyId( $this->mContentId ) ) {
				$this->mStoragePrefs[$pAttachmentId][$pPrefName] = $pPrefValue;
			}

			$ret = TRUE;
		}
		return $ret;
	}

	/**
	 * loadPreferences of the currently loaded object or pass in to get preferences of a specific content_id
	 * 
	 * @param numeric $pContentId content_id of the item we want the prefs from (optional)
	 * @param numeric $pAttachmentId attachment_id of the item we want the prefs from (optional)
	 * @access public
	 * @return array of preferences if $pContentId or $pAttachmentId is set or pass preferences on to $this->mStoragePrefs
	 */
	function loadAttachmentPreferences( $pContentId = NULL ) {
		global $gBitSystem;

		if( !@BitBase::verifyId( $pContentId ) && @BitBase::verifyId( $this->mContentId )) {
			$pContentId = $this->mContentId;
			$store_prefs = TRUE;
		}

		$ret = array();
		if( !empty( $this ) && !is_null( $this->mStoragePrefs )) {
			$ret = $this->mStoragePrefs;
		} elseif( @BitBase::verifyId( $pContentId )) {
			$sql = "
				SELECT lap.`attachment_id`, lap.`pref_name`, lap.`pref_value`
				FROM `".BIT_DB_PREFIX."liberty_attachment_prefs` lap
					INNER JOIN `".BIT_DB_PREFIX."liberty_attachments` la ON (la.`attachment_id` = lap.`attachment_id`)
				WHERE la.`content_id` = ?";
			$result = $gBitSystem->mDb->query( $sql, array( $pContentId ));
			if( !empty( $result )) {
				while( $aux = $result->fetchRow() ) {
					$ret[$aux['attachment_id']][$aux['pref_name']] = $aux['pref_value'];
				}
			}
		}

		// if neither a content id nor an attachment id are given, we will place the results in mStoragePrefs
		if( !empty( $store_prefs )) {
			$this->mStoragePrefs = $ret;
		} else {
			return $ret;
		}
	}

	/**
	 * expungeAttachmentPreferences will remove all attachment preferences of a given attachmtent
	 * 
	 * @param array $pAttachmentId attachemnt we want to remove the prefs for
	 * @access public
	 * @return TRUE on success, FALSE on failure
	 */
	function expungeAttachmentPreferences( $pAttachmentId ) {
		global $gBitSystem;
		$ret = FALSE;
		if( @BitBase::verifyId( $pAttachmentId ) ) {
			$sql = "DELETE FROM `".BIT_DB_PREFIX."liberty_attachment_prefs` WHERE `attachment_id` = ?";
			$gBitSystem->mDb->query( $sql, array( $pAttachmentId ));
			$ret = TRUE;
		}
		return $ret;
	}
}
?>
