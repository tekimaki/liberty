{strip}
{assign var=services value=$gBitSystem->getPackagePluginHandlers($smarty.const.PKG_PLUGIN_TYPE_TPL,$serviceLocation)}
{capture assign=liberty_service_content}{strip}
	{foreach from=$services item=service}
		{include file=$service.plugin_handler serviceHash=$serviceHash}
	{/foreach}
{/strip}{/capture}
{if !empty($liberty_service_content)}
	{if $services and ( $serviceLocation == 'content_nav' or $serviceLocation == 'content_view' )}
		<div class="services-{$serviceLocation}">
	{/if}
	{$liberty_service_content}
	{if $services and ( $serviceLocation == 'content_nav' or $serviceLocation == 'content_view' )}
		</div>
	{/if}
{/if}
{/strip}
