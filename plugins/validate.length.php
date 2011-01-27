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
define( 'PLUGIN_GUID_VALID_LENGTH', 'valid_length' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Length',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Length Validation',
	// brief description of the plugin
        'description'              => 'Validate length fields in a forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'		   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_attribute'   	   => 'length',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_attribute_function'     => 'validate_length',
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_LENGTH, $pluginParams );

function validate_length($pVar, $pConstraints, &$pParamHash, &$pErrors, &$pStore, $pObject = NULL) {
  if( !empty ( $pParamHash[$pVar] ) ) {
    if (strlen($pParamHash[$pVar]) > $pConstraints['length']) {
      $pErrors[$pVar] =
	'The length of the '.$pConstraints['name'].' field is too long.';
    }
  }
  return (count($pErrors) == 0);
}

