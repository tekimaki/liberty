{strip}
{* don't replicate the surrounding div when inserting ajax content *}
{if !$gBitThemes->isAjaxRequest()}
	<div id="edit_storage_list{if !$uploadTab}_tab{/if}">
{/if}
{if $gContent->mStorage}
	<div class="row">
		<table class="data" summary="List of attached files">
			<caption>{tr}Items {if $libertyUploader && empty($gContent->mContentId)}That Will Be{/if} Attached Directly to this Content{/tr}</caption>
			<tr>
				<th scope="col" style="width:30%;" title="{tr}Thumbnail{/tr}">{tr}Thumbnail{/tr}</th>
				<th scope="col" style="width:40%;" title="{tr}Inclusion Code{/tr}">{tr}Inclusion Code{/tr}</th>
				<th scope="col" style="width:30%;" title="{tr}Actions{/tr}">{tr}Actions{/tr}</th>
			</tr>

			<tr>
				<td></td><td></td>
				{if $uploadTab}
					<td class="actionicon">
						<label>
							{tr}No {$primary_label|default:"Primary"}{/tr}:&nbsp;
							<input type="radio" name="liberty_attachments[primary]" value="none" {if empty($gContent->mInfo[primary])}checked="checked"{/if} />
						</label>
					</td>
					{assign var=area value=upload_tab}
				{/if}
			</tr>

			{foreach from=$gContent->mStorage item=storage key=attachmentId name=atts}
				<tr class="{cycle values="odd,even"}">
					<td style="text-align:center;">
						{if $storage.is_mime}
							{include file=$gContent->getMimeTemplate('inline',$storage.attachment_plugin_guid) area=$area thumbsize=small preferences=$gContent->mStoragePrefs.$attachmentId attachment=$storage}
						{else}
							{jspopup href=$storage.source_url title=$storage.title|default:$storage.filename notra=1 img=$storage.thumbnail_url.avatar}
							<br />{$storage.filename} <span class="date">{$storage.file_size|display_bytes}</span>
							{if $smarty.foreach.atts.first}
								{formhelp note="click to see large preview"}
							{/if}
						{/if}
					</td>
					<td style="text-align:center;">
						{$storage.wiki_plugin_link}
						{if $smarty.foreach.atts.first}
							{formhelp note="copy this code into your edit window to embed the file into your text"}
						{/if}
					</td>
					<td class="actionicon">
						{if $uploadTab}
							{* these radio buttons can not be displayed twice in the same form due to interference in $_REQUEST *}
							<label>{tr}{$primary_label|default:"Primary"}{/tr}:&nbsp;<input type="radio" name="liberty_attachments[primary]" value="{$attachmentId}"{if $storage.is_primary eq 'y'} checked="checked"{/if} /></label>
							<br />
						{/if}
						{if $gBitUser->isAdmin() || ($storage.user_id == $gBitUser->mUserId && $gBitUser->hasPermission('p_liberty_delete_attachments') ) }
							{capture name=urlArgs}{$attachmentBaseArgs}content_id={$gContent->mContentId}{if empty($gContent->mContentId)}{foreach from=$gContent->mStorage key=key item=val}&amp;STORAGE[existing][{$val.attachment_id}]={$val.attachment_id}{/foreach}{/if}{/capture}
							{if $libertyUploader || $gBitSystem->getConfig('liberty_attachment_style') == 'ajax'}
								<a href="javascript:void(0);" onclick="
									BitAjax.updater('edit_storage_list_tab', '{$smarty.const.LIBERTY_PKG_URL}ajax_edit_storage.php', '{$smarty.capture.urlArgs}&amp;deleteAttachment={$attachmentId}');
									BitAjax.updater('edit_storage_list', '{$smarty.const.LIBERTY_PKG_URL}ajax_edit_storage.php', '{$smarty.capture.urlArgs}');">
										{biticon ipackage="icons" iname="edit-delete" iexplain="delete"}
								</a>
							{else}
								{if $storage.is_mime}
									<a href="{$smarty.const.LIBERTY_PKG_URL}view.php?attachment_id={$attachmentId}">{biticon ipackage="icons" iname="document-open" iexplain="View"}</a>
								{/if}
								<a href="{$smarty.server.PHP_SELF}?{$smarty.capture.urlArgs}&amp;deleteAttachment={$attachmentId}">{biticon ipackage="icons" iname="edit-delete" iexplain="Delete"}</a>
							{/if}
						{/if}
					</td>
				</tr>
			{/foreach}
		</table>
	</div>
{/if}
{if !$gBitThemes->isAjaxRequest()}
	</div>
{/if}
{/strip}
