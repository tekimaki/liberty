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
define( 'PLUGIN_GUID_VALID_HEXCOLOR', 'valid_hexcolor' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Hexcolors',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Hexcolor Validation',
	// brief description of the plugin
	'description'              => 'Validate hexcolors in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'		   => 'hexcolor',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_hexcolor',
	'preview_function'        => 'preview_hexcolor'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_HEXCOLOR, $pluginParams );

function preview_hexcolor(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints) {
		$pStore[$var] = isset($pParamHash[$var]) ? $pParamHash[$var] : NULL;
	}
}

function validate_hexcolor($pVars, &$pParamHash, &$pObject, &$store) {
	foreach( $pVars as $var => $constraints) {
		if (!empty( $pParamHash[$var] ) ) {
			$hexcolor = $pParamHash[$var];
			// If user accidentally passed along the # sign, strip it off
			// TODO: Not sure if we should trim this is or not as storage could be inconsitent
			// $hexcolor = ltrim( $hexcolor, '#' );
			if ( ctype_xdigit($hexcolor) && (strlen($hexcolor) == 6 || strlen($hexcolor) == 3))
			$store[$var] = $hexcolor;
			else {
				$pObject->mErrors[$var] = tra('The hex color code you entered is not valid');
			}
		}
		else {
			$store[$var] = NULL;
		}
	}
	
	return (count($pObject->mErrors) == 0);
}

