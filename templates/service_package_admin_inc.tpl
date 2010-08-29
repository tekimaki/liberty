{strip}
{if $package}
{foreach from=$gLibertySystem->mServices item=service key=service_guid}
    {if $service.services.package_admin_tpl && $service.package eq $package}
        {include file=$service.services.package_admin_tpl}
    {/if}
{/foreach}
{/if}
{/strip}
