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
define( 'PLUGIN_GUID_VALID_STRING', 'valid_string' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Strings',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'String Validation',
	// brief description of the plugin
	'description'              => 'Validate strings in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'	   	   => 'string',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_strings',
	'preview_function'        => 'preview_strings'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_STRING, $pluginParams );

function preview_strings(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints) {
		$pStore[$var] = isset($pParamHash[$var]) ? 
			$pParamHash[$var] : NULL;
	}
}

function validate_strings($pVars, &$pParamHash, &$pObject, &$store) {
	foreach( $pVars as $var => $constraints) {
		if( empty ( $pParamHash[$var] ) ) {
			// Somebody deleted the value, we need to null it out
			$store[$var] = NULL;
		}
		else {
			$store[$var] = (string)$pParamHash[$var];
		}
	}
	
	return (count($pObject->mErrors) == 0);
}

