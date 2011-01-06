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
define( 'PLUGIN_GUID_VALID_REFERENCE', 'valid_reference' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate References',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Reference Validation',
	// brief description of the plugin
        'description'              => 'Validate references in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'		   => 'reference',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_reference',
	'preview_function'        => 'preview_reference'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_REFERENCE, $pluginParams );

function preview_reference(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints) {
		$pStore[$var] = isset($pParamHash[$var]) ? $pParamHash[$var] : NULL;
	}
}

/**
 * for now this is a lot like integers except that unset value is returned null not 0
 * @TODO perhaps validate against the reference record
 */
function validate_reference(&$pVars, &$pParamHash, &$pErrors, &$store) {
	foreach( $pVars as $var => $constraints) {
		if (!empty( $pParamHash[$var] ) ) {
			if (is_numeric($pParamHash[$var])) {
				if (preg_match('/^([0-9]+)\.?0*$/', 
							   $pParamHash[$var], $match)) {
					$store[$var] = $pParamHash[$var];
				}
				else {
					$pErrors[$var] = 'The value of ' . 
						$constraints['name'] 
						. ' is not an integer.';
				}
			} else {
				$pErrors[$var] = 'The value of ' . 
					$constraints['name'] 
					. ' is not an integer.';
			}
		}
		else {
			$store[$var] = NULL;
		}
	}
	
	return (count($pErrors) == 0);
}

