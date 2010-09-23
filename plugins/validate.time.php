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
define( 'PLUGIN_GUID_VALID_TIME', 'valid_time' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Times',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Time Validation',
	// brief description of the plugin
        'description'              => 'Validate times in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'		   => 'time',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_times',
	'preview_function'        => 'preview_times'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_TIME, $pluginParams );

function preview_times(&$pVars, &$pParamHash, &$pStore) {
	global $gBitSystem;
	$offset = $gBitSystem->get_display_offset();
	$bd = new BitDate($offset);
	foreach( $pVars as $var => $constraints) {
		if (isset( $pParamHash[$var] ) ) {
			$pStore[$var] =
				$bd->gmmktime(($pParamHash[$var]['Meridian'] == 'pm' ?
							   $pParamHash[$var]['Hour'] + 12 :
							   $pParamHash[$var]['Hour']),
							  $pParamHash[$var]['Minute'], 0,
							  1, 2, 1970) - $offset;
		}
	}
}

function validate_times($pVars, &$pParamHash, &$pObject, &$store) {
	global $gBitSystem;
	$offset = $gBitSystem->get_display_offset();
	$bd = new BitDate($offset);
	
	foreach ($pVars as $var => $constraints) {
		if (empty($pParamHash[$var])) {
			$store[$var] = NULL;
		} else {
			// if value is an array assumed to be a set of html_options_time values
			if( is_array( $pParamHash[$var] ) ){
				if ((!isset($pParamHash[$var]['Meridian']) ||
					 ($pParamHash[$var]['Meridian'] == 'am' ||
					  $pParamHash[$var]['Meridian'] == 'pm')) &&
					(isset($pParamHash[$var]['Hour']) &&
					 is_numeric($pParamHash[$var]['Hour'])) &&
					(!isset($pParamHash[$var]['Minute']) ||
					 is_numeric($pParamHash[$var]['Minute']) &&
					 (!isset($pParamHash[$var]['Second']) ||
					  is_numeric($pParamHash[$var]['Second'])))) {
					
					// We work from January 2nd to leave space for negative
					// timezone offsets.
					if (isset($pParamHash[$var]['Meridian'])) {
						$store[$var] = 
							$bd->gmmktime(($pParamHash[$var]['Meridian'] == 'pm' && $pParamHash[$var]['Hour'] < 12 ?
										   $pParamHash[$var]['Hour'] + 12 :
										   $pParamHash[$var]['Hour']),
										  $pParamHash[$var]['Minute'],
										  isset($pParamHash[$var]['Second']) ?
										  $pParamHash[$var]['Second'] : 0,
										  1, 2, 1970);
					}
					else {
						$store[$var] = $bd->gmmktime($pParamHash[$var]['Hour'],
													 $pParamHash[$var]['Minute'],
													 isset($pParamHash[$var]['Second']) ?
													 $pParamHash[$var]['Second'] : 0,
													 1, 2, 1970);
					}										
					$store[$var] = $bd->getUTCFromDisplayDate($store[$var]);
				}
				else {
					$pObject->mErrors[$var] = 'The value for '.
						$constraints['name']
						. ' is invalid.';
				}
				// otherwise validate it as an integer
			}else{
				// TODO: This should be called via a service hook now.
				validate_number($pVars, $pParamHash, $pObject, $store);
			}
		}
	}
	
	return (count($pObject->mErrors) == 0);
}

