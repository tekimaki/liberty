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
define( 'PLUGIN_GUID_VALID_MIN', 'valid_min' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Min',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Min Validation',
	// brief description of the plugin
        'description'              => 'Validate minimum values in a forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'		   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_attribute'   	   => 'min',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_attribute_function'     => 'validate_min',
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_MIN, $pluginParams );

function validate_min($pVar, $pConstraints, &$pParamHash, &$pObject, &$store) {
  if (!(empty($pConstraints['min']) ||
	$pParamHash[$var] < $pConstraints['min'])) {
    $pObject->mErrors[$var] = 'The value of '
      . $pContraints['name']
      . 'is less than the minimum of '
      . $pConstraints['min'];
  }
  return (count($pObject->mErrors) == 0);
}
