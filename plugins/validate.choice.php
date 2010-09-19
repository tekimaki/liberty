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
define( 'PLUGIN_GUID_VALID_CHOICE', 'valid_choice' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Choices',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Choice Validation',
	// brief description of the plugin
        'description'              => 'Validate choices in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'	   	   => 'choice',

	// The key used to indicate to run this kind of validation
    	//	'validate_key'		   => 'choice',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_choice',
	'preview_function'        => 'preview_choice'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_CHOICE, $pluginParams );

function preview_choice(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints) {
		$pStore[$var] = isset($pParamHash[$var]) ? $pParamHash[$var] : NULL;
	}
}

function validate_choice(&$pVars, &$pParamHash, &$pObject, &$store) {
	foreach( $pVars as $var => $constraints) {
		if (isset( $pParamHash[$var] ) ) {
			if( in_array( $pParamHash[$var], $constraints['choices'] ) ){
				$store[$var] = $pParamHash[$var];
			}
		}
		else if (isset($contraints['required']) && $constraints['required']) {
			$pObject->mErrors[$var] = 'A value for ' . $constraint['name'] . ' is required.';
		}
		else{
			$store[$var] = NULL;
		}
	}
}

