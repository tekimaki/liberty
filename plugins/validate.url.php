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
define( 'PLUGIN_GUID_VALID_URL', 'valid_url' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate Urls',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'Url Validation',
	// brief description of the plugin
        'description'              => 'Validate urls in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'		   => 'url',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_urls',
	'preview_function'        => 'preview_urls'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_URL, $pluginParams );

function preview_urls(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints) {
		$pStore[$var] = isset($pParamHash[$var]) ? 
			$pParamHash[$var] : NULL;
	}
}

function validate_urls($pVars, &$pParamHash, &$pErrors, &$store) {
	foreach( $pVars as $var => $constraints) {
		if( empty ( $pParamHash[$var] ) ) {
			// Somebody deleted the value, we need to null it out
			$store[$var] = NULL;
		}
		else {
			// TODO: Options page for the various parts to configure what is allowed as a URL on your site?
			
			// SCHEME
			$urlregex = "^(https?|ftp)\:\/\/";
			
			// USER AND PASS
			$urlregex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?";
			
			// HOSTNAME OR IP
			// $urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)*"; // http://x = allowed (ex. http://localhost, http://routerlogin)
			$urlregex .= "[a-z0-9+\$_-]+(\.[a-z0-9+\$_-]+)+"; // http://x.x = minimum
			//$urlregex .= "([a-z0-9+\$_-]+\.)*[a-z0-9+\$_-]{2,3}"; // http://x.xx(x) = minimum
			
			
			// PORT
			$urlregex .= "(\:[0-9]{2,5})?";
			// PATH
			// allow # and ! which are discouraged and should be encoded but are valid - see facebook links for headaches
			$urlregex .= "(\/([a-z0-9+\$\#!_-]\.?)+)*\/?";		
			// GET Query
			$urlregex .= "(\?[a-z+&\$_.-][a-z0-9;:@/&%=+\$_.-]*)?";
			// ANCHOR
			$urlregex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?\$";
			
			// check
			if (eregi($urlregex, $pParamHash[$var])) {
				$store[$var] = $pParamHash[$var];
			} else {
				$pErrors[$var] = 'The URL entered is invalid.';
			}
		}
	}
	
	return (count($pErrors) == 0);
}

