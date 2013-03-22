<?php

function template_edit_todo()
{
	global $context, $txt;

	echo '
		<we:cat>
			', $context['page_title'], '
		</we:cat>
		<form action="<URL>?action=todo;area=my;sa=edit" method="post" accept-charset="UTF-8" name="todo_add">
			<div class="windowbg2 wrc">';

	if (!empty($context['post_errors']))
	{
		echo '
					<div class="errorbox">
						<h4>There were some errors</h4>';

		foreach ($context['post_errors'] as $error)
			echo '
						<span>', $error, '</span>';

		echo '
					</div>';
	}

	echo '
				<fieldset>
					<legend>', $txt['todo_general'], '</legend>
					<dl class="settings">
						<dt>
							<strong>', $txt['todo_subject'], '</strong>
						</dt>
						<dd>
							<input type="text" name="subject" value="', $context['todo']['subject'], '" size="50" maxlength="255" />
						</dd>
						<dt>
							<strong>', $txt['todo_due'], '</strong>
						</dt>
						<dd>
							<input type="datetime" id="due" name="due" value="', $context['todo']['due'], '" />
						</dd>';

	if ($context['can_manage_permissions'])
	{
		echo '
						<dt>
							<strong>', $txt['todo_groups'], ':</strong>
						</dt>
						<dd>
							<div class="information">
								<label>
									<input type="checkbox" onclick="invertAll(this, this.form);">
									<span class="everyone" title="', $txt['mboards_groups_everyone_desc'], '">', $txt['mboards_groups_everyone'], '</span>
								</label><br><br>';

		foreach ($context['groups'] as $id_group => $group_link)
			echo '
								<label>
									<input type="checkbox" name="groups[', $id_group, ']"', in_array($id_group, $context['todo']['groups']) ? ' checked' : '', '>
									', $group_link, '
								</label><br>';

		echo '
							</div>
						</dd>';
	}

	echo '
					</dl>
				</fieldset>
				<fieldset>
					<legend>', $txt['todo_advanced'], '</legend>
					<dl class="settings">
						<dt id="priority_dt">
							<strong>', $txt['todo_priority'], ':</strong>
						</dt>
						<dd id="priority_dd">
							<select name="priority">
								<option value="high" class="high"', $context['todo']['priority'] == 'high' ? ' selected' : '', '>', $txt['todo_priority_high'], '</option>
								<option value="normal" class="normal"', $context['todo']['priority'] == 'normal' ? ' selected' : '', '>', $txt['todo_priority_normal'], '</option>
								<option value="low" class="low"', $context['todo']['priority'] == 'low' ? ' selected' : '', '>', $txt['todo_priority_low'], '</option>
							</select>
						</dd>
						<dt id="can_search_dt">
							<strong>', $txt['todo_can_search'], ':</strong>
							<dfn>', $txt['todo_can_search_desc'], '</dfn>
						</dt>
						<dd id="can_search_dd">
							<input type="checkbox" name="can_search"', $context['todo']['can_search'] ? ' checked' : '', '>
						</dd>
						<dt>
							<strong>', $txt['todo_is_did'], ':</strong>
							<dfn>', $txt['todo_is_did_desc'], '</dfn>
						</dt>
						<dd>
							<input type="checkbox" name="is_did"', $context['todo']['is_did'] ? ' checked' : '', '>
						</dd>
					</dl>
				</fieldset>
				<div class="right">
					<input type="submit" name="save" value="', $txt['save'], '" class="submit">';

	if ($context['fid'])
		echo '
					<input type="submit" name="delete" value="', $txt['delete'], '" onclick="return confirm(', JavaScriptEscape($txt['todo_delete_sure']), ');" class="delete">';

	echo '
				</div>
			</div>
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '">';

	if ($context['fid'])
		echo '
			<input type="hidden" name="fid" value="', $context['fid'], '">';

	echo '
		</form>';
}
?>