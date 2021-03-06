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
define( 'PLUGIN_GUID_VALID_NUMBER', 'valid_number' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Numbers',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Number Validation',
	// brief description of the plugin
        'description'              => 'Validate whole numbers in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'	   	   => array('number','int','integer','long'),

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_number',
	'preview_function'        => 'preview_number'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_NUMBER, $pluginParams );

function validate_number(&$pVars, &$pParamHash, &$pErrors, &$store, $pObject = NULL) {
	foreach( $pVars as $var => $constraints) {
		if (!empty( $pParamHash[$var] ) ) {
			if (is_numeric($pParamHash[$var])) {
				if (preg_match('/^([0-9]+)\.?0*$/', 
							   $pParamHash[$var], $match)) {
					$store[$var] = $match[1];
				}
				else {
					$pErrors[$var] = 'The value of '
						. $constraints['name'] 
						. ' is not an integer.';
				}
			}
			else {
				$pErrors[$var] = 'The value of ' . 
					$constraints['name'] 
					. ' is not an integer.';
			}
		}
		else {
			$store[$var] = '0';
		}
	}
	
	return (count($pErrors) == 0);
}


function preview_number(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints) {
		$pStore[$var] = isset($pParamHash[$var]) ? $pParamHash[$var] : NULL;
	}
}

