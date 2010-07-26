{* $Header$ *}
{strip}
{* perhaps we can remove this as well at some point and load it using
$gBitThemes->loadJavascript(); *}
{if $comments_ajax}
	<script src="{$smarty.const.LIBERTY_PKG_URL}scripts/LibertyComment.js" type="text/javascript"></script>
	<script type="text/javascript">
		LibertyComment.ROOT_ID = {if $gContent->mContentId}{$gContent->mContentId}{elseif $commentsParentId}{$commentsParentId}{else}null{/if}; {* this is the content id - would be better as part of something in kernel but here it is until that day *}
		LibertyComment.ROOT_GUID = "{if $gContent->mContentTypeGuid}{$gContent->mContentTypeGuid}{/if}";
		LibertyComment.SORT_MODE = "{$comments_sort_mode}";
		LibertyComment.BROWSER = "{$gBrowserInfo.browser}";
	</script>
{/if}
{if $gContent->mContentTypeGuid}
	<script type="text/javascript">
		LibertyContent = {ldelim}{rdelim};
		LibertyContent.CONTENT_TYPE_GUID = "{$gContent->mContentTypeGuid}";
	</script>
{/if}
{/strip}
