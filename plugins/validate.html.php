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
define( 'PLUGIN_GUID_VALID_HTML', 'valid_html' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'Validate HTML',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'HTML Validation',
	// brief description of the plugin
        'description'              => 'Validate html in forms.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => TRUE,
	// should the plugin be forced to the current state.
	'forced'				   => TRUE,

	// type of plugin
	'plugin_type'              => VALIDATE_PLUGIN,

	// A type which can be used to run this and other validations
	'validate_type'	   	   => 'html',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_function'     => 'validate_html',
	'preview_function'        => 'preview_html'
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_HTML, $pluginParams );

function preview_html(&$pVars, &$pParamHash, &$pStore) {
	foreach( $pVars as $var => $constraints) {
		$pStore[$var] = isset($pParamHash[$var]) ? 
			$pParamHash[$var] : NULL;
	}
}

function validate_html(&$pVars, &$pParamHash, &$pErrors, &$pStore, $pObject = NULL) {
	foreach( $pVars as $var => $constraints) {
		if( !empty( $pStore[$var] ) &&
			empty ( $pParamHash[$var] ) ) {
			// Somebody deleted the value, we need to null it out
			$pStore[$var] = NULL;
		}
		else {
			// this validator uses htmlpurifier
			// someone more ambitious might create filter options or check the system configuration for active html parser plugins
			if( @include_once( LIBERTY_PKG_PATH.'plugins/filter.htmlpurifier.php' ) ){
				global $gBitSystem, $gHtmlPurifier;
				// enable htmlp collecterrors
				$gBitSystem->setConfig('htmlpure_collecterrors', 'y');
				$filterHash = array( 'htmlp_filter_mode' => 'validate' );
				htmlpure_filter( $pParamHash[$var], $filterHash, $pObject );
				// check for errors
				$e = $gHtmlPurifier->context->get('ErrorCollector');

				if( $errors = $e->getRaw() ){
					$errorStr = '';
					foreach( $errors as $aError ){
						// htmlpurifier error handling uses numeric indexes, good grief
						// [1] error code using php constants, see http://php.net/manual/en/errorfunc.constants.php
						// [2] message
						// we only want unrecognized tags because they will be escaped
						preg_match( '/^Unrecognized/', $aError[2], $matches );
						if( !empty( $matches ) ){
							$regex = '#Unrecognized (.+?) tag removed$#';
							$replace = '\1'; 
							$tag =  preg_replace($regex, $replace, $aError[2]);
							$errorStr .= '<span class="htmlpure-error">'.htmlspecialchars($tag).'</span>';
						}
					}

					if( !empty( $errorStr ) ){
						$pErrors[$var] = tra('Invalid or prohibited html submitted:').$errorStr; 
					}
					// disable htmlp collecterrors
					$gBitSystem->setConfig('htmlpure_collecterrors', 'n');
				}
			}

			// no errors send back the data
			if( empty( $pErrors[$var]  ) ){
				$pStore[$var] = $pParamHash[$var];
			}
		}
	}
	
	return (count($pErrors) == 0);
}

