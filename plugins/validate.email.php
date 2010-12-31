<?php /* -*- Mode: php; tab-width: 4; indent-tabs-mode: t; c-basic-offset: 4; -*- */
/* vim: :set fdm=marker : */


/* TODO: Use is_email from http://code.google.com/p/isemail/source/browse/trunk/is_email.php */

/**
 * @version  $Header$
 * @package  liberty
 * @subpackage plugins_validate
 */

/**
 * definitions ( guid character limit is 16 chars )
 */
define( 'PLUGIN_GUID_VALID_EMAIL', 'valid_email' );

global $gLibertySystem;

// Try to use is_email function but don't puke if it is missing.
@include_once(UTIL_PKG_PATH."is_email.php");

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Emails',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Email Validation',
	// brief description of the plugin
        'description'              => 'Validate emails in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'	   	   => 'email',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_emails',
	'preview_function'        => 'preview_emails'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_EMAIL, $pluginParams );

function preview_emails(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints) {
		$pStore[$var] = isset($pParamHash[$var]) ? 
			$pParamHash[$var] : NULL;
	}
}

function validate_emails($pVars, &$pParamHash, &$pObject, &$store) {
	foreach( $pVars as $var => $constraints) {
		if( !empty( $pStore[$var] ) &&
			empty ( $pParamHash[$var] ) ) {
			// Somebody deleted the value, we need to null it out
			$store[$var] = NULL;
		}
		else {
			// Did we manage to inclue is_email.php above?
			if (function_exists('is_email')) {
				if (!is_email($pParamHash[$var])) {
					$pObject->mErrors[$var] =
						'Invalid email address.';
				} else {
					$store[$var] = $pParamHash[$var];
				}
			} else {
				// TODO: Something better here? At least log a warning?
				$store[$var] = $pParamHash[$var];
			}
		}
	}
	
	return (count($pObject->mErrors) == 0);
}

