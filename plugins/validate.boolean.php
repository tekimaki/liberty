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
define( 'PLUGIN_GUID_VALID_BOOLEAN', 'valid_boolean' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Booleans',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Boolean Validation',
	// brief description of the plugin
    'description'              => 'Validate booleans in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'		   => 'boolean',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_booleans',
	'preview_function'        => 'preview_booleans'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_BOOLEAN, $pluginParams );

function preview_booleans($pVars, &$pParamHash, &$pStore){
	foreach ($pVars as $var => $constrants) {
		if (isset($pParamHash[$var])) {
			$pStore[$var] =
				($pParamHash[$var] == 'on' ||
				 $pParamHash[$var] == 1 ||
				 $pParamHash[$var] == 'yes' )
				? 1 : 0;
		}
		else {
			$pStore[$var] = 0;
		}
	}
}

function validate_booleans($pVars, &$pParamHash, &$pObject, &$store) {
	foreach ($pVars as $var => $constraints) {
		if (isset($pParamHash[$var])) {
			$store[$var] =
				($pParamHash[$var] == 'on' ||
				 $pParamHash[$var] == 1 ||
				 $pParamHash[$var] == 'yes')
				? 1 : 0;
		}
		else {
			$store[$var] = 0;
		}
	}
	
	return (count($pObject->mErrors) == 0);
}

