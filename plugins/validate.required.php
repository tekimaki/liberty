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
define( 'PLUGIN_GUID_VALID_REQUIRED', 'valid_required' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Required',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Required Validation',
	// brief description of the plugin
        'description'              => 'Validate required fields in a forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'		   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_attribute'   	   => 'required',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_attribute_function'     => 'validate_required',
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_REQUIRED, $pluginParams );

function validate_required($pVar, $pConstraints, &$pParamHash, &$pErrors, &$store) {
  if( empty ( $pParamHash[$pVar] ) ) {
    $pErrors[$pVar] = 'A value for ' . $pConstraints['name']
      . ' is required.';
  }
  return (count($pErrors) == 0);
}

