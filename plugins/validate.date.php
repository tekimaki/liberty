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
define( 'PLUGIN_GUID_VALID_DATE', 'valid_date' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Dates',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Date Validation',
	// brief description of the plugin
        'description'              => 'Validate dates in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// The key used to indicate to run this kind of validation
	'validate_key'		   => 'date',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_dates',
	'preview_function'        => 'preview_dates'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_DATE, $pluginParams );

function preview_dates(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints) {
		if( isset( $pParamHash[$var]['Month'] ) &&
			isset( $pParamHash[$var]['Day'] ) &&
			isset( $pParamHash[$var]['Year'] ) )
			{
				$pStore[$var] = $pParamHash[$var]['Year'].'-'.
					$pParamHash[$var]['Month'].'-'.$pParamHash[$var]['Day'];
			}
	}
}

function validate_dates($pVars, &$pParamHash, &$pObject, &$store) {
	foreach( $pVars as $var => $constraints) {
		if( isset( $pParamHash[$var]['Month'] ) &&
			isset( $pParamHash[$var]['Day'] ) &&
			isset( $pParamHash[$var]['Year'] ) )
			{
				if (! ( is_numeric($pParamHash[$var]['Month']) &&
						is_numeric($pParamHash[$var]['Day']) &&
						is_numeric($pParamHash[$var]['Year']) ) ) {
					$pObject->mErrors[$var] = 'The value of the ' . 
						$constraints['name'] . ' is invalid.';
				}
				else {
					if( strlen( $pParamHash[$var]['Month'] ) == 1 ) {
						$pParamHash[$var]['Month'] = 
							'0'.$pParamHash[$var]['Month'];
					}
					if( strlen( $pParamHash[$var]['Day'] ) == 1 ) {
						$pParamHash[$var]['Day'] = '0'.$pParamHash[$var]['Day'];
					}
					$store[$var] =
						$pParamHash[$var]['Year'].
						$pParamHash[$var]['Month'].
						$pParamHash[$var]['Day'];
					if (strlen($store[$var]) != 8) {
						$pObject->mErrors[$var] = 'The value of ' .
							$constraint['name'] . ' is invalid.';
					}
				}
			}
		else if (isset($contraints['required']) && $constraints['required']) {
			$pObject->mErrors[$var] = 'A value for ' .
				$constraint['name'] . ' is required.';
		}
	}
	
	return (count($pObject->mErrors) == 0);
}

