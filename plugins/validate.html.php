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
	'validate_attribute'   	   => 'html',

	// url to page with options for this plugin
	//'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/filter_attachment.php',
	
	// various filter functions and when they are called
	'validate_attribute_function'     => 'validate_html',
);

$gLibertySystem->registerPlugin( PLUGIN_GUID_VALID_HTML, $pluginParams );

function validate_html($pVar, $pConstraints, &$pParamHash, &$pErrors, &$pStore, $pObject = NULL) {
	global $gLibertySystem;
	// this validator uses htmlpurifier
	if( $pConstraints['html'] == TRUE && $gLibertySystem->isPluginActive( 'filterhtmlpure' ) && @include_once( LIBERTY_PKG_PATH.'plugins/filter.htmlpurifier.php' ) ){
		global $gBitSystem, $gHtmlPurifier;
		// enable htmlp collecterrors
		$gBitSystem->setConfig('htmlpure_collecterrors', 'y');
		$filterHash = array( 'htmlp_filter_mode' => 'validate' );
		htmlpure_filter( $pParamHash[$pVar], $filterHash, $pObject );
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
				$pErrors[$pVar] = tra('Invalid or prohibited html submitted:').$errorStr; 
			}
			// disable htmlp collecterrors
			$gBitSystem->setConfig('htmlpure_collecterrors', 'n');
		}
	}
	
	return (count($pErrors) == 0);
}

