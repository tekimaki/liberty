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
define( 'PLUGIN_GUID_VALID_ZIP', 'valid_zip' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
    'title'                    => 'Validate Zipcodes',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Zipcode Validation',
	// brief description of the plugin
       'description'              => 'Validate zipcodes in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'	   	   => array('zip','postal_code'),

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_zips',
	'preview_function'        => 'preview_zips'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_ZIP, $pluginParams );

function preview_zips(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints ) {
		if( isset( $pParamHash[$var] ) ) {
			$pStore[$var] = isset($pParamHash[$var]) ?
				$pParamHash[$var] : NULL;
		}
	}
}

function validate_zips(&$pVars, &$pParamHash, &$pObject, &$store) {
	foreach( $pVars as $var => $constraints) {
	    if (!empty( $pStore[$var] ) &&
			empty( $pParamHash[$var] )) {
			if (isset($constraints['required']) && $constraints['required']) {
				$pObject->mErrors[$var] = 'A value for ' . (empty( $constraints['label'] ) ? $constraints['name'] : $constraints['label'])
					. ' is required.';
			}
			else {
				// Somebody deleted the value, we need to null it out
				$store[$var] = NULL;
			}
		}
		else if( empty( $pParamHash[$var] ) ) {
			if (isset($constraints['required']) && $constraints['required']) {
				$pObject->mErrors[$var] = 'A value for ' . (empty( $constraints['label'] ) ? $constraints['name'] : $constraints['label'])
					. ' is required.';
			}
			else {
				// Somebody deleted the value, we need to null it out
				$store[$var] = NULL;
			}
		}
		else {
			if( !preg_match("/^([0-9]{5})(-[0-9]{4})?$/i", $pParamHash[$var])) {
				$pObject->mErrors[$var] =
					'The value for '.(empty( $constraints['label'] ) ? $constraints['name'] : $constraints['label']).' is not in a valid zipcode format.';
			}
			else {
				$store[$var] = $pParamHash[$var];
			}
		}
	}
}

