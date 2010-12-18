<?php
/**
 * @version $Header$
 * @package liberty
 * @subpackage functions
 */

/**
 * required setup
 */
require_once( '../kernel/setup_inc.php' );
global $gBitSmarty, $gContent;

$gContent = $pluginHash = $error = NULL;

if( !empty( $_REQUEST['preflight_plugin_guid'] ) && !empty( $_REQUEST['preflight_fieldset_guid'] ) ){
	switch( $_REQUEST['preflight_plugin_guid'] ){
	case 'liberty_attachment';
		// @TODO migrate attachment_upload here
		break;
	default:
		if( !empty( $_REQUEST[$_REQUEST['preflight_plugin_guid']][$_REQUEST['preflight_fieldset_guid']] ) ){
			$storeHash[$_REQUEST['preflight_plugin_guid']][$_REQUEST['preflight_fieldset_guid']] = $_REQUEST[$_REQUEST['preflight_plugin_guid']][$_REQUEST['preflight_fieldset_guid']];
		}
		else{
			$error = tra( 'No fieldset found for plugin guid '.$_REQUEST['preflight_plugin_guid'] );
		}
		break;
	}
}

if( !empty( $_REQUEST['content_id'] )) {
// if we have a content id then we just load up that
	if( !($gContent = LibertyBase::getLibertyObject( $_REQUEST['content_id'] )) ) {
		// if there is something wrong with the content id spit back an error
		$error = tra( "Invalid contentt id" );
	}
}elseif( !empty( $_REQUEST['content_type_guid'] ) ){
/* if we don't have a content id then we assume this is new content and we need to create a draft.
 * we'll pass a new content_id back to the edit form so it can make the right association later on save.
 */
	// if we are creating new content the status must be enforced, so status recognition must be enabled
	if( !$gBitSystem->isFeatureActive( "liberty_display_status" ) ){
		$error = tra( "You must save the content to save this data." );
	}elseif( !isset( $gLibertySystem->mContentTypes[$_REQUEST['content_type_guid']] ) ){
		$error = tra( "You are attempting to upload a file to an invalid content type" );
	}else{
		// load up the requested content type handler class
		$contentType = $_REQUEST['content_type_guid'];
		$contentTypeHash = $gLibertySystem->mContentTypes[$contentType];
		$class =  $contentTypeHash['handler_class'];
		$classFile =  $contentTypeHash['handler_file'];
		$package = $contentTypeHash['handler_package'];
		$pathVar = strtoupper($package).'_PKG_PATH';

		if( !defined( $pathVar ) ) {
			$error = tra( "Undefined handler package path" );
		}else{
			require_once( constant( $pathVar ).$classFile );
			$gContent = new $class();
		}
	}
}else{
// if we don't have a valid content_id or content_type_guid we can't do nothing for you
	$error = "You have not specified a content item or content type to associate the data with";
}

// setup errors - exit
if ( !is_null( $error ) ){
	if ( is_array( $error ) ){
		$error = implode("\n", $error);
	} 
	$gBitSmarty->assign('errors', $error);
	// @TODO handler generic errors
	echo 'generic error';
	die;
}

if( empty( $error ) && is_object( $gContent ) ){
	if ( !$gContent->isValid() ){
		// if we dont have a content object we create a draft.
		if( empty( $_REQUEST['content_id'] ) ){
			$storeHash['content_status_id'] = -5;
			if( $gContent->preflightStore( $storeHash ) ){	
				$_REQUEST['content_id'] = $storeHash['content_id'];
			}else{
				$gBitSmarty->assign('errors', $gContent->getErrors() );
				// vd( $gContent->getErrors() );
				// @TODO handle preflight store errors
				echo 'preflight store error';
				die;
			}
		}
		$gContent->mContentId = $_REQUEST['content_id']; // this sucks can do this better
	}

	// store service
	$storeHandler = $gBitSystem->getPluginAPIHandler( 'function', 'content_store', $_REQUEST['preflight_plugin_guid'] );
	$gContent->invokeService( $storeHandler, $storeHash );
	if( $errors = $gContent->getErrors() ){
		$gBitSmarty->assign_by_ref( 'errors', $errors );
	}
}

// substitute for display service in LibertyMime
$gContent->preflightLoad();

// load service
// @TODO change this to load when pkgmkr can do selective loading based on perhaps a fieldset key being set
// for now user display which loads everything typically
$loadHandler = $gBitSystem->getPluginAPIHandler( 'function', 'content_display', $_REQUEST['preflight_plugin_guid'] );
$gContent->invokeService( $loadHandler, $loadHash );

$gBitSmarty->assign( 'gContent', $gContent );
$gBitSmarty->assign( 'libertyUploader', TRUE );
$gBitSmarty->assign( 'uploadTab', TRUE );

// hand the updated edit form back
$displayHandler = $gBitSystem->getPluginAPIHandler( 'tpl', 'content_edit_mini', $_REQUEST['preflight_plugin_guid'] );
echo $gBitSystem->display( $displayHandler['plugin_handler'], NULL, array( 'format'=>'none', 'display_mode' => 'display' ));
