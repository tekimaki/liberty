<?php
/**
 * @version  $Header$
 * @package  liberty
 * @subpackage plugins_filter
 */

/**
 * definitions ( guid character limit is 16 chars )
 */
define( 'PLUGIN_GUID_FILTERHTMLPURIFIER', 'filterhtmlpure' );

global $gLibertySystem;

$pluginParams = array (
	// plugin title
	'title'                    => 'HTMLPurifier',
	// help page on bitweaver org that explains this plugin
	'help_page'                => 'HTMLPurifier',
	// brief description of the plugin
	'description'              => 'Uses <a href="http://htmlpurifier.org">HTMLPurifier</a> to cleanup the HTML submitted to your site and ensure that it is standards compliant and does not contain anything malicious. It is also used to ensure that the various places that input is split for previews does not cause bad markup to break the page. This filter is <strong>highly</strong> recommended if you are allowing HTML but is still good for sites that are not using thse formats for the ability to cleanup markup which has been split for preview properly though this may disable certain plugins that insert non standards compliant code.',
	// should this plugin be active or not when loaded for the first time
	'auto_activate'            => FALSE,
	// type of plugin
	'plugin_type'              => FILTER_PLUGIN,
	// url to page with options for this plugin
	'plugin_settings_url'      => LIBERTY_PKG_URL.'admin/plugins/filter_htmlpurifier.php',

	// various filter functions and when they are called
	// called before the data is parsed
	//	'pre_function'       => 'htmlpure_filter',
	// called after the data has been parsed
	'preparse_function'  => 'htmlpure_filter',
	// called before the data is parsed if there is a split
	//	'presplit_function'  => 'htmlpure_filter',
	// called after the data has been parsed if there is a split
	'postsplit_function' => 'htmlpure_filter',
);
$gLibertySystem->registerPlugin( PLUGIN_GUID_FILTERHTMLPURIFIER, $pluginParams );

function htmlpure_filter( &$pString, &$pFilterHash, $pObject ) {
	global $gHtmlPurifier, $gHtmlpConfig, $gBitSystem, $gLastHtmlpConfig;

	// set default filter mode
	if( empty( $pFilterHash['htmlp_filter_mode'] ) ){
		$pFilterHash['htmlp_filter_mode'] = 'render';
	}

	if (!isset($gHtmlPurifier)) { 
		$pear_version = false;
		if (@include_once("PEAR.php")) {		
			if(@include_once("HTMLPurifier.php")) {
				@include_once("HTMLPurifier.auto.php");
				$auto_config = true;

				$config = htmlpure_getDefaultConfig( $pObject, $pFilterHash );

				$gHtmlPurifier = new HTMLPurifier($config);
			}
		}
	}

	// Did we manage to create one?
	if (isset($gHtmlPurifier)) { 
		/* Clean up the paragraphs a bit */
		//		$start = $pData;
		$pString = htmlpure_cleanupPeeTags($pString);
		//		$pee = $pString;
		//		$pString = html_entity_decode( $pString );
		
		// update the configuration
		// updating the configuration can be memory intensive
		// ten updates can easily knock out php
		// so we only update the configuration when really necessary
		// @TODO cache configurations
		if( is_object( $pObject ) && 
			is_a( $pObject, 'LibertyContent' ) && 
			empty( $gHtmlpConfig['last_content_id'] ) || ( $pObject->mContentId != $gHtmlpConfig['last_content_id'] ) && 
			empty( $gHtmlpConfig['last_filter_mode'] ) || ( $pFilterHash['htmlp_filter_mode'] != $gHtmlpConfig['last_filter_mode'] )
		){
			$gLastHtmlpConfig = htmlpure_getDefaultConfig( $pObject, $pFilterHash );
			if( !empty( $pFilterHash['htmlp_config'] ) ){
				/* if we've received custom configurations for the particular parse then we deal with them
				   for now were expecting config data that htmlpurfier doesn't really handle in a nice way
				   so we stuff it into the 'info' hash under a 'bitweaver' name space.

				   @TODO ideally this might also look for native htmlpurifier config values in the keys and
				   then adjust as necessary which is why $config is passed in here. -wjames5
				  */
				foreach( $pFilterHash['htmlp_config'] as $key => $val ){
					$config->def->info['bitweaver'][$key] = $val;
				}
			}

			// set last content id used 
			$gHtmlpConfig['last_content_id'] = $pObject->mContentId;
		}

		if( !empty( $gLastHtmlpConfig ) ){
			$pString = $gHtmlPurifier->purify( $pString, $gLastHtmlpConfig );
		}else{
			$pString = $gHtmlPurifier->purify( $pString );
		}

		// set last filter mode used
		$gHtmlpConfig['last_filter_mode'] = $pFilterHash['htmlp_filter_mode'];

		// If we have another parse step they may be escaping
		// entities so change quotes back.
		if (empty($pFilterHash['format_guid']) || 
		    $pFilterHash['format_guid'] != 'bithtml') {
			$pString = preg_replace('|&quot;|', '"', $pString);
			$pString = preg_replace('|&#039;|', "'", $pString);
		}

		/*
		echo "<br/><hr/><br/>".$start;
		include_once( 'Text/Diff.php' );
		include_once( 'Text/Diff/Renderer/inline.php' );
		$diff = &new Text_Diff(explode("\n", $start), explode("\n",$pee));
		$renderer = &new Text_Diff_Renderer_inline();
		echo "<br/><hr/><br/>". $renderer->render($diff);

		echo "<br/><hr/><br/>".$pString;
		include_once( 'Text/Diff.php' );
		include_once( 'Text/Diff/Renderer/inline.php' );
		$diff = &new Text_Diff(explode("\n", $pee), explode("\n",$pString));
		$renderer = &new Text_Diff_Renderer_inline();
		echo "<br/><hr/><br/>". $renderer->render($diff);
		 */
	} else {
		bit_log_error("HTMLPurifier not installed. Install with: pear channel-discover htmlpurifier.org; pear install hp/HTMLPurifier;");
	}

	return $pString;
}

function htmlpure_getDefaultConfig( $pObject=NULL, $pFilterHash = array() ){
	global $gBitSystem, $gBitUser;
	$blacklistedTags = '';
	$userPerms = array();

	$config = HTMLPurifier_Config::createDefault();
	// Necessary setup for custom configuration I think. http://htmlpurifier.org/docs/enduser-customize.html
	//$config->set( 'HTML.DefinitionID', BIT_ROOT_PATH );
	//$config->set('HTML.DefinitionRev', 1);
	//$config->set('Cache.DefinitionImpl', null); // remove this later!


	// Set the cache path
	$config->set('Cache.SerializerPath', STORAGE_PKG_PATH );

	if ($gBitSystem->getConfig('htmlpure_escape_bad', 'y') == 'y') {
		$config->set('Core.EscapeInvalidTags', true);
		$config->set('Core.EscapeInvalidChildren', true);
	}
	if ($gBitSystem->getConfig('htmlpure_use_redirect') == 'y') {
		$config->set('URI.Munge', LIBERTY_PKG_URL.'redirect.php?q=%s');
	}
	if ($gBitSystem->getConfig('htmlpure_strict_html', 'y') == 'y') {
		$config->set('HTML.Strict', true);
	}
	if ($gBitSystem->getConfig('htmlpure_xhtml', 'n') == 'n') {
		$config->set('HTML.XHTML', true);
	}
	if ($gBitSystem->getConfig('htmlpure_collecterrors', 'n') == 'y') {
		$config->set('Core.CollectErrors', true);
	}

	// Get user permissions 
	if( is_object( $pObject ) && is_a( $pObject, 'LibertyContent' ) ){
		switch( $pFilterHash['htmlp_filter_mode'] ){
		case 'validate':
			$userId = $gBitUser->mUserId;
			break;
		case 'render':
		default:
			$userId = $pObject->getField( 'modifier_user_id' );
			break;
		}
		// @TODO respect revoked permissions
		$userPerms = array_keys( $pObject->getUserPermissions( $userId, TRUE, FALSE ) );
	}

	if( !in_array( $pObject->mAdminContentPerm, $userPerms ) ) {
		if ($gBitSystem->getConfig('htmlpure_disable_extern') == 'y') {
			$config->set('URI.DisableExternal', true);
		}
		if ($gBitSystem->getConfig('htmlpure_disable_extern_res', 'y') == 'y') {
			$config->set('URI.DisableExternalResources', true);
		}
		if ($gBitSystem->getConfig('htmlpure_disable_res') == 'y') {
			$config->set('URI.DisableResources', true);
		}
		if ($gBitSystem->getConfig('htmlpure_disable_uri') == 'y') {
			$config->set('URI.Disable', true);
		}
		$blacklistedTags = $gBitSystem->getConfig('blacklisted_html_tags', '');
	}

	// Set that we are using a div to wrap things.
	$config->set('HTML.BlockWrapper', 'div');

	// set plugins
	// TODO: devise a way to parse plugins dir
	// and check for the right property here
	// so new plugins are just drop in place.
	$custom_filters = array();

	// Disable included YouTube filter, we have our own
	$config->set('Filter.YouTube', false);

	if ($gBitSystem->isFeatureActive('htmlpure_allow_youtube')) {
		require_once(UTIL_PKG_PATH.'htmlpure/Filter/YouTube.php');
		$custom_filters[] = new HTMLPurifier_Filter_YouTube();
	}
	if ($gBitSystem->isFeatureActive('htmlpure_allow_cnbc')) {
		require_once(UTIL_PKG_PATH.'htmlpure/Filter/CNBC.php');
		$custom_filters[] = new HTMLPurifier_Filter_CNBC();
	}

	// risky elements - user must have trusted editor permission
	if( in_array( 'p_liberty_trusted_editor', $userPerms ) ) {
		$config->set('URI.DisableExternalResources', false);
		$config->set('HTML.Trusted', true);
		$config->set('HTML.SafeEmbed', true);
	//	$config->set('HTML.SafeObject', true); // this seems to conflict wiht Trusted - see http://htmlpurifier.org/phorum/read.php?2,3886,4362#msg-4362 
		$config->set('Output.FlashCompat', true);
		require_once( UTIL_PKG_PATH.'htmlpure/Filter/SafeIframe.php' );
		$custom_filters[] = new HTMLPurifier_Filter_SafeIframe();
	}

	if( !empty( $custom_filters ) ){
		$config->set('Filter.Custom', $custom_filters );
	}

	// stuff for getHTMLDefinition
	$config->set('HTML.DefinitionID', 'bitweaver');
	$config->set('HTML.DefinitionRev', 1);

	if( in_array( $pObject->mAdminContentPerm, $userPerms ) ) {
		// Last person to edit this file has admin permission for this entire class of content, let freedom ring
		$config->set( 'CSS.AllowTricky', true );
	}

	if( in_array( $pObject->mAdminContentPerm, $userPerms ) ) {
		// This stuff messes with config so needs to come last
		$css =& $config->getCSSDefinition();
        $css->info['position'] = new HTMLPurifier_AttrDef_CSS_Composite(array( new HTMLPurifier_AttrDef_Enum(array('absolute', 'fixed', 'relative', 'static', 'inherit')) ) );
        $css->info['top'] = new HTMLPurifier_AttrDef_CSS_Composite(array( new HTMLPurifier_AttrDef_CSS_Length()));
        $css->info['left'] = new HTMLPurifier_AttrDef_CSS_Composite(array( new HTMLPurifier_AttrDef_CSS_Length()));
        $css->info['bottom'] = new HTMLPurifier_AttrDef_CSS_Composite(array( new HTMLPurifier_AttrDef_CSS_Length()));
        $css->info['right'] = new HTMLPurifier_AttrDef_CSS_Composite(array( new HTMLPurifier_AttrDef_CSS_Length()));
		// $def =& $config->getHTMLDefinition();
		// $def->addAttribute('a', 'target', 'Enum#_blank,_self,_target,_top');
	}

	$def = $config->getHTMLDefinition(true);
	// force on target
	$def->addAttribute('a', 'target', new HTMLPurifier_AttrDef_Enum(
	  array('_blank','_self','_target','_top')
	));

	// get it again by ref for our blacklist and rel transformation
	$def =& $config->getHTMLDefinition();

	// HTMLPurifier doesn't have a blacklist feature. Duh guys!
	// Note that this has to come last since the other configs
	// may tweak the def.
	foreach (explode(',',$blacklistedTags) as $tag) {
		unset($def->info[$tag]);
	}

	if( in_array( $pObject->mAdminContentPerm, $userPerms ) ) {
		if ($gBitSystem->getConfig('htmlpure_force_nofollow', 'y') == 'y') {
			if( !class_exists("HTMLPurifier_AttrTransform_ForceValue") ){
				class HTMLPurifier_AttrTransform_ForceValue extends HTMLPurifier_AttrTransform
				{
					var $name, $value;
					function HTMLPurifier_AttrTransform_ForceValue($name, $value) {
						$this->name  = $name;
						$this->value = $value;
					}
					function transform($attr, $config, $context) {
						$attr[$this->name] = $this->value;
						return $attr;
					}
				}
			}
			$def->info['a']->attr_transform_post['rel'] = new HTMLPurifier_AttrTransform_ForceValue('rel', 'nofollow');
		}
	}

	return $config;
}

function htmlpure_cleanupPeeTags( $pee ) {

	// Convert us some form feeds for better cross platform support
	$pee = str_replace(array("\r\n", "\r"), "\n", $pee);

	// Strip out lots of duplicate newlines now
	$pee = preg_replace("#\n\n+#", "\n\n", $pee);

	// Pee in block quotes - Removed as we now have purifier insert a div instead. See above.
	//	$pee = preg_replace('#<blockquote(.*?(?:[^>]*))>(.*?)</blockquote>#s', '<blockquote$1><p>$2</p></blockquote>', $pee);

	// Strip empty pee
	$pee = preg_replace('#<p>\s*</p>#', '', $pee);

	// Unpee pre blocks
	$pee = preg_replace('#(<pre.*?(?:[^>]*)>)(.*?)</pre>#sie',
		" '$1' . preg_replace('#<br[\s/]*(?:[^>]*)/>#', '"."\n"."',
		preg_replace('#<p[\s]*(?:[^>]*)>#', '"."\n"."',
		preg_replace('#</p[\s]*(?:[^>]*)>#', '', '$2'))). '</pre>'", $pee);

	// Fixup align divs so we can keep them.
	$pee = preg_replace('#<div(.*?)align="(.*?)"(.*?)>#', '<div$1style="text-align:$2;"$3>', $pee);

	return $pee;
}

?>
