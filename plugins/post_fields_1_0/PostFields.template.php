<?php

function template_edit_post_field()
{
	global $context, $txt, $settings, $scripturl;

	// All the javascript for this page - quite a bit!
	add_js('
	function updateInputBoxes()
	{
		var curType = $("#field_type").val(), privStatus = $("#private").val();
		$("#max_length_dt, #max_length_dd, #bbc_dt, #bbc_dd, #can_search_dt, #can_search_dd").toggle(curType == "text" || curType == "textarea");
		$("#dimension_dt, #dimension_dd").toggle(curType == "textarea");
		$("#options_dt, #options_dd").toggle(curType == "select" || curType == "radio");
		$("#default_dt, #default_dd").toggle(curType == "check");
		$("#regex_dt, #regex_dd").toggle(curType == "text");
		$("#regex_div").toggle(curType == "text" && $("#regex").val() == "regex");
		$("#display").attr("disabled", false);

		// Cannot show this on the topic
		if (curType == "textarea" || privStatus >= 2)
			$("#display").attr("checked", false).attr("disabled", true);

		// Able to show to guests?
		$("#guest_access_dt, #guest_access_dd").toggle(privStatus < 2);
	}
	updateInputBoxes();');

	add_js('
	var startOptID = ', count($context['field']['options']), ';
	function addOption()
	{
		$("#addopt").append(\'<br><input type="radio" name="default_select" value="\' + startOptID + \'" id="\' + startOptID + \'"><input type="text" name="select_option[\' + startOptID + \']" value="">\');
		startOptID++;
	}');

	echo '
	<div id="admincenter">
		<form action="', $scripturl, '?action=admin;area=modsettings;sa=postfieldedit" method="post" accept-charset="UTF-8">
			<we:cat>
				', $context['page_title2'], '
			</we:cat>
			<div class="windowbg2 wrc">';

	echo '
				<fieldset>
					<legend>', $txt['pf_general'], '</legend>

					<dl class="settings">
						<dt>
							<strong>', $txt['pf_name'], ':</strong>
						</dt>
						<dd>
							<input type="text" name="name" value="', $context['field']['name'], '" size="20" maxlength="40">
						</dd>
						<dt>
							<strong>', $txt['pf_description'], ':</strong>
						</dt>
						<dd>
							<textarea name="description" rows="3" cols="40">', $context['field']['description'], '</textarea>
						</dd>
						<dt>
							<strong>', $txt['pf_boards'], ':</strong>
						</dt>
						<dd>
							<div class="information">';

	foreach ($context['boards'] as $id_board => $board_link)
		echo '
								<input type="checkbox" name="boards[', $id_board, ']"', in_array($id_board, $context['field']['boards']) ? ' checked' : '', '>
								', $board_link, '<br />';

	echo '
							</div>
						</dd>
						<dt>
							<strong>', $txt['pf_groups'], ':</strong>
						</dt>
						<dd>
							<div class="information">';

	foreach ($context['groups'] as $id_group => $group_link)
		echo '
								<input type="checkbox" name="groups[', $id_group, ']"', in_array($id_group, $context['field']['groups']) ? ' checked' : '', '>
								', $group_link, '<br />';

	echo '
							</div>
						</dd>
					</dl>
				</fieldset>
				<fieldset>
					<legend>', $txt['pf_input'], '</legend>
					<dl class="settings">
						<dt>
							<strong>', $txt['pf_picktype'], ':</strong>
						</dt>
						<dd>
							<select name="type" id="field_type" onchange="updateInputBoxes();">
								<option value="text"', $context['field']['type'] == 'text' ? ' selected' : '', '>', $txt['custom_profile_type_text'], '</option>
								<option value="textarea"', $context['field']['type'] == 'textarea' ? ' selected' : '', '>', $txt['custom_profile_type_textarea'], '</option>
								<option value="select"', $context['field']['type'] == 'select' ? ' selected' : '', '>', $txt['custom_profile_type_select'], '</option>
								<option value="radio"', $context['field']['type'] == 'radio' ? ' selected' : '', '>', $txt['custom_profile_type_radio'], '</option>
								<option value="check"', $context['field']['type'] == 'check' ? ' selected' : '', '>', $txt['custom_profile_type_check'], '</option>
							</select>
						</dd>
						<dt id="max_length_dt">
							<strong>', $txt['pf_max_length'], ':</strong>
							<dfn>', $txt['pf_max_length_desc'], '</dfn>
						</dt>
						<dd id="max_length_dd">
							<input type="text" name="length" value="', $context['field']['length'], '" size="7" maxlength="6">
						</dd>
						<dt id="dimension_dt">
							<strong>', $txt['pf_dimension'], ':</strong>
						</dt>
						<dd id="dimension_dd">
							<strong>', $txt['pf_dimension_row'], ':</strong> <input type="text" name="rows" value="', $context['field']['rows'], '" size="5" maxlength="3">
							<strong>', $txt['pf_dimension_col'], ':</strong> <input type="text" name="cols" value="', $context['field']['cols'], '" size="5" maxlength="3">
						</dd>
						<dt id="bbc_dt">
							<strong>', $txt['pf_bbc'], '</strong>
						</dt>
						<dd id="bbc_dd">
							<input type="checkbox" name="bbc"', $context['field']['bbc'] ? ' checked' : '', '>
						</dd>
						<dt id="options_dt">
							<a href="', $scripturl, '?action=help;in=customoptions" onclick="return reqWin(this);" class="help" title="', $txt['help'], '"></a>
							<strong>', $txt['pf_options'], ':</strong>
							<dfn>', $txt['pf_options_desc'], '</dfn>
						</dt>
						<dd id="options_dd">
							<div>';

	foreach ($context['field']['options'] as $k => $option)
		echo '
								', $k == 0 ? '' : '<br>', '<input type="radio" name="default_select" value="', $k, '"', $context['field']['default_select'] == $option ? ' checked' : '', '><input type="text" name="select_option[', $k, ']" value="', $option, '">';

	echo '
								<span id="addopt"></span>
								[<a href="" onclick="addOption(); return false;">', $txt['pf_options_more'], '</a>]
							</div>
						</dd>
						<dt id="default_dt">
							<strong>', $txt['pf_default'], ':</strong>
						</dt>
						<dd id="default_dd">
							<input type="checkbox" name="default_check"', $context['field']['default_check'] ? ' checked' : '', '>
						</dd>
					</dl>
				</fieldset>
				<fieldset>
					<legend>', $txt['pf_advanced'], '</legend>
					<dl class="settings">
						<dt id="regex_dt">
							<a id="custom_regex" href="', $scripturl, '?action=help;in=custom_regex" onclick="return reqWin(this);" class="help" title="', $txt['help'], '"></a>
							<strong>', $txt['pf_regex'], ':</strong>
							<dfn>', $txt['pf_regex_desc'], '</dfn>
						</dt>
						<dd id="regex_dd">
							<input type="text" name="regex" value="', $context['field']['regex'], '" size="30">
						</dd>
						<dt id="can_search_dt">
							<strong>', $txt['pf_can_search'], ':</strong>
							<dfn>', $txt['pf_can_search_desc'], '</dfn>
						</dt>
						<dd id="can_search_dd">
							<input type="checkbox" name="searchable"', $context['field']['searchable'] ? ' checked' : '', '>
						</dd>
						<dt>
							<strong>', $txt['pf_active'], ':</strong>
							<dfn>', $txt['pf_active_desc'], '</dfn>
						</dt>
						<dd>
							<input type="checkbox" name="active"', $context['field']['active'] ? ' checked' : '', '>
						</dd>
					</dl>
				</fieldset>
				<div class="righttext">
					<input type="submit" name="save" value="', $txt['save'], '" class="submit">';

	if ($context['fid'])
		echo '
					<input type="submit" name="delete" value="', $txt['delete'], '" onclick="return confirm(', JavaScriptEscape($txt['pf_delete_sure']), ');" class="delete">';

	echo '
				</div>
			</div>
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">';

	if ($context['fid'])
		echo '
			<input type="hidden" name="fid" value="', $context['fid'], '">';

	echo '
		</form>
	</div>
	<br class="clear">';
}

function template_input_post_fields()
{
	global $context, $scripturl, $settings, $txt;

	if (!empty($context['fields']))
	{
		echo '
					<we:cat>', $txt['post_fields'], '</we:cat>
					<div class="smalltext roundframe">
						<dl class="settings">';

		foreach ($context['fields'] as $field)
			echo '
							<dt>
								<strong>', $field['name'], ': </strong><br />
								<span class="smalltext">', $field['description'], '</span>
							</dt>
							<dd>
								', $field['input_html'], '
							</dd>';

		echo '
						</dl>
					</div>';
	}
}

?>