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
define( 'PLUGIN_GUID_VALID_REAL', 'valid_real' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Reals',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Real Validation',
	// brief description of the plugin
        'description'              => 'Validate real numbers in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'	   	   => array('real','float','double'),

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_reals',
	'preview_function'        => 'preview_reals'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_REAL, $pluginParams );

function preview_reals(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints) {
		$pStore[$var] = isset($pParamHash[$var]) ? $pParamHash[$var] : NULL;
	}
}

function validate_reals($pVars, &$pParamHash, &$pObject, &$store) {
	foreach( $pVars as $var => $constraints) {
		if (!empty( $pParamHash[$var] ) ) {
			if (is_numeric($pParamHash[$var])) {
				if (preg_match('/^([0-9]+\.?[0-9]*)$/',
							   $pParamHash[$var], $match)) {
					$store[$var] = $match[1];
				}
				else {
					$pObject->mErrors[$var] = 'The value of '
						. $constraints['name']
						. ' is not a real number.';
				}
			}
			else {
				$pObject->mErrors[$var] = 'The value of ' .
					$constraints['name']
					. ' is not an real number.';
			}
		}
		else {
			$store[$var] = '0';
		}
	}
	
	return (count($pObject->mErrors) == 0);
}

