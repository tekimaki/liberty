{strip}
{if $package}
{foreach from=$gBitSystem->getPackagePluginHandlers('tpl','package_admin') item=service}
    {if $service.package_guid eq $package}
        {include file=$service.plugin_handler}
    {/if}
{/foreach}
{/if}
{/strip}
