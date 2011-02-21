{assign var=statuses value=$gContent->getAvailableContentStatuses()} 
{if $gBitSystem->isFeatureActive( 'liberty_display_status' ) && 
	$gBitSystem->isFeatureActive( 'liberty_display_status_menu' ) && 
	$statuses &&
	( $gContent->hasUserPermission( 'p_liberty_edit_content_status') || $gContent->hasUserPermission( 'p_liberty_edit_all_status') )
	}
	{if ($statuses|@count) > 1}
	<div class="row">
		{formlabel label="Status" for="content_status_id"}
		{forminput}
			{html_options name="content_status_id" options=$statuses selected=$gContent->getField('content_status_id',$smarty.const.BIT_CONTENT_DEFAULT_STATUS)}
		{/forminput}
	</div>
	{else}
	{* statuses is associative this is the easiest way to get the one status *}
	{foreach from=$statuses key=content_status_id item=status_desc}
	<div class="row" style="display:none">
		<input type="hidden" name="content_status_id" value="{$content_status_id}" />
	</div>
	{/foreach}
	{/if}
{/if}

