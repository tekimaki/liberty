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
define( 'PLUGIN_GUID_VALID_MAX', 'valid_max' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Max',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Max Validation',
	// brief description of the plugin
        'description'              => 'Validate maximum values in a forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'		   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_attribute'   	   => 'max',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admax/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_attribute_function'     => 'validate_max',
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_MAX, $pluginParams );

function validate_max($pVar, $pConstraints, &$pParamHash, &$pErrors, &$pStore, $pObject = NULL) {
  if (!empty($pConstraints['max']) && !empty($pParamHash[$pVar]) && ( strlen($pParamHash[$pVar]) > $pConstraints['max'] )) {
    $pErrors[$pVar] = ''
      . $pConstraints['name']
      . ' is too long, max '
      . $pConstraints['max']
      . ' characters allowed.';
  }
  return (count($pErrors) == 0);
}
