{strip}
{* in some ajax cases we need to uniquely identify portions of a form so we get an id if we dont already have one *}
{if !$form_id}
	{capture name=form_id}
		{form_id}
	{/capture}
	{assign var=form_id value=$smarty.capture.form_id}
{/if}
{include file="bitpackage:liberty/edit_upload.tpl" form_id=$form_id}
{include file="bitpackage:liberty/edit_storage_list.tpl" uploadTab=TRUE form_id=$form_id}
{/strip}
