<?php  /* -*- Mode: php; tab-width: 4; indent-tabs-mode: t; c-basic-offset: 4; -*- */
/* vim: :set fdm=marker : */
/**
 * @version  $Header$
 * @package  liberty
 * @subpackage plugins_validate
 */

/**
 * definitions ( guid character limit is 16 chars )
 */
define( 'PLUGIN_GUID_VALID_PHONE', 'valid_phone' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Phones',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Phone Validation',
	// brief description of the plugin
        'description'              => 'Validate phones in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// The key used to indicate to run this kind of validation
	'validate_key'		   => 'phone',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_phone',
	'preview_function'        => 'preview_phone'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_PHONE, $pluginParams );

function preview_phone($pVars, &$pParamHash, &$pStore){
	foreach ($pVars as $var => $constrants) {
		$pStore[$var] = isset($pParamHash[$var]) ? $pParamHash[$var] : NULL;
	}
}

function validate_phone($pVars, &$pParamHash, &$pObject, &$store) {
	foreach( $pVars as $var => $constraints ) {
		if( isset( $pParamHash[$var] ) ) {
			// We just strip down to what we seek.
			// TODO: Verify valid characters first
			$phone = preg_replace('/[^0-9a-zA-Z]/', '',$pParamHash[$var]);
			if( strlen( $phone ) != 0 && strlen( $phone ) != 10 && strlen( $phone ) != 12 ) {
				$pObject->mErrors[$var] = 'You must enter a 10 or 12 character value for ' . $constraints['name'];
			}
			else {
				$store[$var] = $phone;
			}
		}
		else if (isset($constraints['required']) && $constraints['required']) {
			$pObject->mErrors[$var] = 'A value for ' . $constraints['name']
				. ' is required.';
		}
		else {
			$store[$var] = NULL;
		}
	}
}

