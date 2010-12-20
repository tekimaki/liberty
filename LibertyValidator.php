<?php /* -*- Mode: php; tab-width: 4; indent-tabs-mode: t; c-basic-offset: 4; -*- */
/**
 * Utility class for validating various kinds of input.
 *
 */

class LibertyValidator {	

	static $sActivePluginsByType;
	static $sActivePluginsByAttribute;

	function preview(&$pVars, &$pParamHash, &$store) {
		if( !empty( $pVars ) ){
			LibertyValidator::setupPlugins();
			// For each variable that we need to preview
			foreach($pVars as $type => $vars) {
				// If there is a handler of the right type
				if (!empty(LibertyValidator::$sActivePluginsByType[$type])) {
					// Call all plugins of the given type
					foreach (LibertyValidator::$sActivePluginsByType[$type] as $type => $data) {
						// But only call them if they have a preview function
						if (!empty($data['preview_function'])) {
							$function = $data['preview_function'];
							$function($vars, $pParamHash, $store);
						}
					}
				} else {
					// TODO: Should we just call null here and ignore they turned off all validators of the right type?
					global $gBitSystem;
					$gBitSystem->fatalError("Unsupported validation type: ".$type);
				}
			}
		}
	}

    function validateAttachment($pName, $pParams, &$pObject) {
		// If there is a particular format required and there is a file with a type
		if (is_array($pParams['format']) && !empty($_FILES[$pName]) && !empty($_FILES[$pName]['type'])) {

			global $gBitSystem;
			// Get the mime type since the php type can not be trusted
			$mimeType = $gBitSystem->verifyMimeType( $_FILES[$pName]['tmp_name'] );
			// Check this type against the allowed formats for this attachment
			if (!in_array($mimeType, $pParams['format'])) {
				$pObject->mErrors[$pName] = "Invalid file format for " . $pParams['name'];
			}
		}			
	}

	function setupPlugins() {
		global $gLibertySystem;
		// If we have not already setup the plugins by type
		if (empty(LibertyValidator::$sActivePluginsByType)) {
			// Pull in all the validate plugins
			$plugins = $gLibertySystem->getPluginsOfType( VALIDATE_PLUGIN );
			LibertyValidator::$sActivePluginsByType = array();
			// And loop over them
			foreach ($plugins as $key => &$data) {
				// Only add active plugins
				if ($data['is_active']) {
					// Does the plugin specifiy one or more types?
					if (!empty($data['validate_type'])) {
						// Does this plugin have an array of types
						if (is_array($data['validate_type'])) {
							// Then add all types to the byType array
							foreach($data['validate_type'] as $type) {
								LibertyValidator::$sActivePluginsByType[$type][] = &$data;
							}
						// Or the plugin just has one type
						} else {
							LibertyValidator::$sActivePluginsByType[$data['validate_type']][] = &$data;
						}
					}
					// Does the plugin specify one or more attributes?
					if (!empty($data['validate_attribute'])) {
						// Does the plugin support more than one attribute
						if (is_array($data['validate_attribute'])) {
							// Add all attributes to the by attribute array
							foreach($data['validate_attribute'] as $attr) {
								LibertyValidator::$sActivePluginsByAttribute[$attr][] = &$data;
							}
						// The plugin just has one attribute
						} else {
							LibertyValidator::$sActivePluginsByAttribute[$data['validate_attribute']][] = &$data;
						}
					}
				}
			}
			// Stupid php scoping
			unset($data);
		}
	}

	function validate(&$pVars, &$pParamHash, &$pObject, &$store) {
		if( !empty( $pVars ) ){
			// Make sure we have the byType array setup
			LibertyValidator::setupPlugins();
			// For each variable we need to validate
			foreach($pVars as $type => $vars) {
				// If we have one or more plugin of the right type
				if (!empty(LibertyValidator::$sActivePluginsByType[$type])) {
					// Then run each of the plugins of the right type
					foreach (LibertyValidator::$sActivePluginsByType[$type] as $plug => $data) {
						// But only if they have specified a validate function
						if (!empty($data['validate_function'])) {
							$function = $data['validate_function'];
							$function($vars, $pParamHash, $pObject, $store);
						}					
					}
				// No plugins of the right type! Ack!
				} else {
					// TODO: Should we just call null here and ignore they turned off all validators of the right type?
					global $gBitSystem;
					$gBitSystem->fatalError("Unsupported validation type: ".$type);
				}
				// Now for each variable check the attributes
				foreach($vars as $var => $constraints) {
					// So we loop over the constraints
					foreach ($constraints as $constraint => $value) {
						// And if we have an attribute which matches the constraints
						if (!empty(LibertyValidator::$sActivePluginsByAttribute[$constraint])) {
							// Then apply each attribute constraint
							foreach (LibertyValidator::$sActivePluginsByAttribute[$constraint] as $plug => $data) {
								// But only if it has specified an attribute function properly
								if (!empty($data['validate_attribute_function'])) {
									$function = $data['validate_attribute_function'];
									$function($var, $constraints, $pParamHash, $pObject, $store);
								}		
							}
						}
					}
				}
			}
		}
	}
}
