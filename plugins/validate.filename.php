<?php /* -*- Mode: php; tab-width: 4; indent-tabs-mode: t; c-basic-offset: 4; -*- */
/* vim: :set fdm=marker : */
/**
 * @version  $Header$
 * @package  liberty
 * @subpackage plugins_validate
 */

/**
 * definitions ( guid character limit is 16 chars )
 */
define( 'PLUGIN_GUID_VALID_FILENAME', 'valid_filename' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Filenames',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Filename Validation',
	// brief description of the plugin
        'description'              => 'Validate filenames in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'	   	   => 'filename',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_filenames',
	'preview_function'        => 'preview_filenames'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_FILENAME, $pluginParams );

function preview_filenames(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints) {
		$pStore[$var] = isset($pParamHash[$var]) ? 
			$pParamHash[$var] : NULL;
	}
}

function validate_filenames(&$pVars, &$pParamHash, &$pErrors, &$pStore, $pObject = NULL) {
	foreach( $pVars as $var => $constraints) {
		if (!empty( $pParamHash[$var] ) ) {
			$filename = $pParamHash[$var];
			if ( preg_match('/[&\ $\?\*\%:\/\\\]/', $filename) ){
				$pErrors[$var] = tra('The file name contains invalid characters, make sure the filename does not contain any of the follow characters: &$?%:/\\');
			} else {
				$pStore[$var] = $filename;
			}
		}
		else {
			$pStore[$var] = NULL;
		}
	}
	
	return (count($pErrors) == 0);
}
