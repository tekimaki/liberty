<?php /* -*- Mode: php; tab-width: 4; indent-tabs-mode: t; c-basic-offset: 4; -*- */
/**
 * Utility class for validating various kinds of input.
 *
 */

class LibertyValidator {	

	function preview(&$pVars, &$pParamHash, &$store) {
		$activePlugins = LibertyValidator::getActivePlugins();

		foreach($pVars as $type => $vars) {
			switch ($type) {
				// We include null in here so that it can not be disabled
			case 'null':
				LibertyValidator::validate_null($vars, $pParamHash, $pObject, $store);
				break;
			default:
				if (!empty($activePlugins[$type])) {
					foreach ($activePlugins[$type] as $type => $data) {
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
				break;
			}
		}
	}

    function validateAttachment(t$pName, $pParams, &$pObject) {
		if (is_array($pParams['format']) && !empty($_FILES[$pName]) && !empty($_FILES[$pName]['type'])) {
			if (!in_array($_FILES[$pName]['type'], $pParams['format'])) {
				$pObject->mErrors[$pName] = "Invalid file format for " . $pParams['name'];
			}
		}			
	}

	function getActivePlugins() {
		global $gLibertySystem;

		$plugins = $gLibertySystem->getPluginsOfType( VALIDATE_PLUGIN );
		$activePlugins = array();
		foreach ($plugins as $key => &$data) {
			if ($data['is_active']) {
				if (!empty($data['validate_type'])) {
					if (is_array($data['validate_type'])) {
						foreach($data['validate_type'] as $type) {
							$activePlugins[$type][] = &$data;
						}
					} else {
						$activePlugins[$data['validate_type']][] = &$data;
					}
				}
				if (!empty($data['validate_key'])) {
					$activePlugins[$data['validate_key']][] = &$data;
				}
			}
		}
		// Stupid php scoping
		unset($data);

		return $activePlugins;
	}

	function validate(&$pVars, &$pParamHash, &$pObject, &$store) {
		$activePlugins = LibertyValidator::getActivePlugins();

		foreach($pVars as $type => $vars) {
			switch ($type) {
				// We include null in here so that it can not be disabled
			case 'null':
				LibertyValidator::validate_null($vars, $pParamHash, $pObject, $store);
				break;
			default:
				if (!empty($activePlugins[$type])) {
					foreach ($activePlugins[$type] as $type => $data) {
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
				break;
			}
		}
	}

	function preview_null(&$pVars, &$pParamHash, &$pStore) {
		 foreach( $pVars as $var ) {
		 	  $pStore[$var] = isset($pParamHash[$var]) ? 
				$pParamHash[$var] : NULL;
		}
	}

	function validate_null($pVars, &$pParamHash, &$pObject, &$store) {
		 foreach( $pVars as $var ) {
		 	  $pStore[$var] = isset($pParamHash[$var]) ? 
				$pParamHash[$var] : NULL;
		}
	}
}
