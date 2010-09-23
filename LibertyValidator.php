<?php /* -*- Mode: php; tab-width: 4; indent-tabs-mode: t; c-basic-offset: 4; -*- */
/**
 * Utility class for validating various kinds of input.
 *
 */

class LibertyValidator {	

	static $sActivePluginsByType;
	static $sActivePluginsByAttribute;

	function preview(&$pVars, &$pParamHash, &$store) {
		LibertyValidator::setupPlugins();
		foreach($pVars as $type => $vars) {
			if (!empty(LibertyValidator::$sActivePluginsByType[$type])) {
				foreach (LibertyValidator::$sActivePluginsByType[$type] as $type => $data) {
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

    function validateAttachment($pName, $pParams, &$pObject) {
		if (is_array($pParams['format']) && !empty($_FILES[$pName]) && !empty($_FILES[$pName]['type'])) {

			global $gBitSystem;
			$mimeType = $gBitSystem->verifyMimeType( $_FILES[$pName]['tmp_name'] );
			if (!in_array($mimeType, $pParams['format'])) {
				$pObject->mErrors[$pName] = "Invalid file format for " . $pParams['name'];
			}
		}			
	}

	function setupPlugins() {
		global $gLibertySystem;

		if (empty(LibertyValidator::$sActivePluginsByType)) {
			$plugins = $gLibertySystem->getPluginsOfType( VALIDATE_PLUGIN );
			LibertyValidator::$sActivePluginsByType = array();
			foreach ($plugins as $key => &$data) {
				if ($data['is_active']) {
					if (!empty($data['validate_type'])) {
						if (is_array($data['validate_type'])) {
							foreach($data['validate_type'] as $type) {
								LibertyValidator::$sActivePluginsByType[$type][] = &$data;
							}
						} else {
							LibertyValidator::$sActivePluginsByType[$data['validate_type']][] = &$data;
						}
					}
					if (!empty($data['validate_attribute'])) {
						if (is_array($data['validate_attribute'])) {
							foreach($data['validate_attribute'] as $attr) {
								LibertyValidator::$sActivePluginsByAttribute[$attr][] = &$data;
							}
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
		LibertyValidator::setupPlugins();
		foreach($pVars as $type => $vars) {
			if (!empty(LibertyValidator::$sActivePluginsByType[$type])) {
				foreach (LibertyValidator::$sActivePluginsByType[$type] as $plug => $data) {
					if (!empty($data['validate_function'])) {
						$function = $data['validate_function'];
						$function($vars, $pParamHash, $pObject, $store);
					}					
				}
			} else {
				// TODO: Should we just call null here and ignore they turned off all validators of the right type?
				global $gBitSystem;
				$gBitSystem->fatalError("Unsupported validation type: ".$type);
			}			
			foreach($vars as $var => $constraints) {
				foreach ($constraints as $constraint => $value) {
					if (!empty(LibertyValidator::$sActivePluginsByAttribute[$constraint])) {
						foreach (LibertyValidator::$sActivePluginsByAttribute[$constraint] as $plug => $data) {
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
