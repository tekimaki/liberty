{if !$translateFrom}
	{assign var=service_guid value=$serviceFile|regex_replace:'/_tpl$/':''}
	{foreach from=$gBitSystem->getPackagePluginHandlers('tpl',$service_guid) item=plugin}
		{if (empty($gContent) || $gContent->hasService( $plugin.guid ))}
			{if strpos($service_guid,'mini')}<div class="service">{/if}
			{include file=$plugin.plugin_handler edit_content_status_tpl=$edit_content_status_tpl}
			{if strpos($service_guid,'mini')}</div>{/if}
		{/if}
	{/foreach}
{/if}
