<?php

function template_edit_post_field()
{
	global $context, $txt, $settings, $scripturl;

	add_js('
	var startOptID = ', count($context['field']['options']), ';
	updateInputBoxes(true);
	updateInputBoxes2(true);');

	echo '
	<div id="admincenter">
		<form action="<URL>?action=admin;area=postfields;sa=edit" method="post" accept-charset="UTF-8">
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
							<a id="field_show_enclosed" href="', $scripturl, '?action=help;in=field_show_enclosed" onclick="return reqWin(this);" class="help" title="', $txt['help'], '"></a>
							<strong>', $txt['pf_enclose'], ':</strong>
							<dfn>', $txt['pf_enclose_desc'], '</dfn>
						</dt>
						<dd>
							<textarea name="enclose" rows="10" cols="50">', $context['field']['enclose'], '</textarea>
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
								<option value="text"', $context['field']['type'] == 'text' ? ' selected' : '', '>', $txt['pf_type_text'], '</option>
								<option value="textarea"', $context['field']['type'] == 'textarea' ? ' selected' : '', '>', $txt['pf_type_textarea'], '</option>
								<option value="select"', $context['field']['type'] == 'select' ? ' selected' : '', '>', $txt['pf_type_select'], '</option>
								<option value="radio"', $context['field']['type'] == 'radio' ? ' selected' : '', '>', $txt['pf_type_radio'], '</option>
								<option value="check"', $context['field']['type'] == 'check' ? ' selected' : '', '>', $txt['pf_type_check'], '</option>
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
						<dt id="size_dt">
							<strong>', $txt['pf_size'], ':</strong>
							<dfn>', $txt['pf_size_desc'], '</dfn>
						</dt>
						<dd id="size_dd">
							<strong>', $txt['pf_size_row'], ':</strong> <input type="text" name="rows" value="', $context['field']['rows'], '" size="5" maxlength="3">
							<strong>', $txt['pf_size_col'], ':</strong> <input type="text" name="cols" value="', $context['field']['cols'], '" size="5" maxlength="3">
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
								[<a href="" onclick="addOption(); return false;">', $txt['more'], '</a>]
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
						<dt id="mask_dt">
							<a id="custom_mask" href="', $scripturl, '?action=help;in=custom_mask" onclick="return reqWin(this);" class="help" title="', $txt['help'], '"></a>
							<strong>', $txt['pf_mask'], ':</strong>
							<dfn>', $txt['pf_mask_desc'], '</dfn>
						</dt>
						<dd id="mask_dd">
							<select name="mask" id="field_mask" onchange="updateInputBoxes2();">
								<option value="number"', $context['field']['type'] == 'number' ? ' selected' : '', '>', $txt['pf_mask_number'], '</option>
								<option value="float"', $context['field']['type'] == 'float' ? ' selected' : '', '>', $txt['pf_mask_float'], '</option>
								<option value="email"', $context['field']['type'] == 'email' ? ' selected' : '', '>', $txt['pf_mask_email'], '</option>
								<option value="regex"', $context['field']['type'] == 'regex' ? ' selected' : '', '>', $txt['pf_mask_regex'], '</option>
							</select>
						</dd>
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
							<input type="checkbox" name="can_search"', $context['field']['can_search'] ? ' checked' : '', '>
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
				<div class="right">
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
	</div>';
}

function template_input_post_fields()
{
	global $context, $scripturl, $settings, $txt;

	if (!empty($context['fields']))
	{
		$fold = !empty($context['is_post_fields_collapsed']);
		echo '
					<div id="postFieldsHeader">
						<div id="postFieldsExpand" title="', $fold ? '+' : '-', '"></div> <strong><a href="#" id="postFieldsExpandLink">', $txt['post_fields'], '</a></strong>
					</div>
					<div id="postFields" class="smalltext">
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

		// Code for showing and hiding additional options.
		if (!empty($settings['additional_options_collapsable']))
		{
			// If we're collapsed, hide everything now and don't trigger the animation.
			if ($fold)
				add_js('
	$("#postFields").hide();');
			else
				add_js('
	$("#postFieldsExpand").addClass("fold");');

			add_js('
	var oSwapAdditionalOptions = new weToggle({
		bCurrentlyCollapsed: ', $fold ? 'true' : 'false', ',
		aSwappableContainers: [
			"postFields"
		],
		aSwapImages: [
			{
				sId: "postFieldsExpand",
				altExpanded: "-",
				altCollapsed: "+"
			}
		],
		aSwapLinks: [
			{
				sId: "postFieldsExpandLink",
				msgExpanded: ' . JavaScriptEscape($txt['post_fields']) . '
			}
		],
		oThemeOptions: {
			bUseThemeSettings: ' . (we::$is_guest ? 'false' : 'true') . ',
			sOptionName: \'postFields\'
		},
		oCookieOptions: {
			bUseCookie: ' . (we::$is_guest ? 'true' : 'false') . ',
			sCookieName: \'postFields\'
		}
	});');
		}
	}
}

?>