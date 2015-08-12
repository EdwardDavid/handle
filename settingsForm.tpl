{**
 * settingsForm.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * PID plugin Settings Form
 *
 * $Id: settingsForm.tpl,v 1.5 2008/06/11 18:55:12 asmecher Exp $
 *}


{assign var="pageTitle" value="plugins.generic.pid"}

{include file="common/header.tpl"}
{include file="common/formErrors.tpl"}

<p>{translate key="plugins.generic.pid.description"}</p>
<h4>{translate key="plugins.generic.pidHeading"}</h4>
<form method="post" action="{plugin_url path="settings"}">
<table class="data" width="100%">
	<tr valign="top">
		<td width="17%" class="label">{fieldLabel name="pidAssignorPath" key="plugins.generic.form.pidAssignorPath"}</td>
		<td class="value"><input type="text" name="pidAssignorPath" value="{$pidAssignorPath|escape}" size="60"></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="pidResolverPath" key="plugins.generic.form.pidResolverPath"}</td>
		<td class="value"><input type="text" name="pidResolverPath" value="{$pidResolverPath|escape}" size="60"></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="pidUsername" key="plugins.generic.form.pidUsername"}</td>
		<td class="value"><input type="text" name="pidUsername" value="{$pidUsername|escape}" size="60"></td>
	</tr>
	<tr valign="top">
		<td class="label">{fieldLabel name="pidPassword" key="plugins.generic.form.pidPassword"}</td>
		<td class="value"><input type="text" name="pidPassword" value="{$pidPassword|escape}" size="60"></td>
	</tr>
	<!-- tr valign="top">
		<td class="label">{translate key="plugins.generic.form.pidOrganizationId"}</td>
		<td class="value"><input type="text" name="pidOrganizationId" value="{$pidOrganizationId|escape}" size="5"></td>
	</tr -->
</table>
<p>
<input type="submit" name="save" value="{translate key="common.save"}" class="button defaultButton" /> 
<input type="button" value="{translate key="common.cancel"}" class="button" onclick="document.location.href='{url page="admin" escape=false}'" />
</p>
<p>
<br/><br/>
<h5>{translate key="plugins.generic.pid.manager.settings.setPidRetroactivelyDesc"}</h5>
<table class="data" width="100%">
	<tr>
		<td width="60%">
				{translate key="plugins.generic.pid.manager.info.thereare"} {$articlesNoPid}
				{translate key="plugins.generic.pid.manager.info.pulishedArticlesNoPid"} {$publishedArticles} 
				{translate key="plugins.generic.pid.manager.info.numberPulishedArticles"}<br/>
			   <i>{translate key="plugins.generic.pid.manager.info.fewMinutes"}</i><br/>
		</td>
		<td>
			<input type="submit" name="setPidsRetroactively" value="{translate key="plugins.generic.pid.manager.settings.setPidRetroactively"}" class="button defaultButton" />
		</td>
	</tr>
</table>
</p>
</form>
{include file="common/footer.tpl"}