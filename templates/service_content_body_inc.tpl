{if $gBitSystem->isFeatureActive( 'liberty_display_status' ) && ($gBitUser->hasPermission('p_liberty_edit_content_status') || $gBitUser->hasPermission('p_liberty_edit_all_status')) && $gContent->mInfo.content_status_id != 50}
	<p class="liberty_status">{biticon iname=dialog-warning iexplain="Warning"} {tr}The status of this content is <strong>{$gContent->getContentStatusName()}</strong>{/tr}.</p>
{/if}{$gContent->mInfo.content_status_name}
