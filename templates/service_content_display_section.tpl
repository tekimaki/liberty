{strip}
<div class="section {if $smarty.request.section && $smarty.request.action}section-edit{/if} {if !empty($gContent)}{$gContent->getContentTypeName()|strtolower} {/if}{$smarty.request.section}">
{foreach from=$gBitSystem->getPackagePluginHandlers('tpl','content_display_section') item=plugin}
	{if !empty($gContent) && $gContent->hasService($plugin.guid)}
		{include file=$plugin.plugin_handler}
	{/if}
{/foreach}
</div>
{/strip}
