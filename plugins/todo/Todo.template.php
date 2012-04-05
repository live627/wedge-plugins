<?php

function template_edit_todo()
{
	global $context, $txt;

	echo '
		<we:cat>
			', $context['page_title'], '
		</we:cat>
		<form action="<URL>?action=todo;area=my;sa=edit" method="post" accept-charset="', $context['character_set'], '" name="todo_add">
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
							<input type="text" name="subject" value="" size="50" maxlength="255" class="input_text" />
						</dd>
						<dt>
							<strong>', $txt['todo_due'], '</strong>
						</dt>
						<dd>
							<input type="datetime" id="due" name="due" />
						</dd>
					<dt>
						<strong>', $txt['permission_set'], ':</strong>
						<dfn>', $context['can_manage_permission_sets'] ? sprintf($txt['permission_set_desc'], $scripturl . '?action=admin;area=permissions;sa=profiles;' . $context['session_query']) : strip_tags($txt['permission_set_desc']), '</dfn>
					</dt>
					<dd>
						<select name="permission_set">';

	if (!$context['fid'])
		echo '
							<option value="-1">[', $txt['permission_sets_none'], ']</option>';

	foreach ($context['permission_sets'] as $id_permission_set => $permission_set)
		echo '
							<option value="', $id_permission_set, '"', $id_permission_set == $context['todo']['permission_set'] ? ' selected' : '', '>', $permission_set['name'], '</option>';

	echo '
						</select>
					</dd>
					<dt>
						<strong>', $txt['mboards_groups'], ':</strong>
						<dfn>', $txt['mboards_groups_desc'], '</dfn>
					</dt>
					<dd>
						<label><input type="checkbox" name="view_edit_same" id="view_edit_same"', !empty($context['view_edit_same']) ? ' checked' : '', ' onclick="$(\'#edit_perm_col\').toggle(!this.checked)"> ', $txt['mboards_groups_view_edit_same'], '</label><br>
						<label><input type="checkbox" name="need_deny_perm" id="need_deny_perm"', !empty($context['need_deny_perm']) ? ' checked' : '', ' onclick="$(\'.deny_perm\').toggle(this.checked)"> ', $txt['mboards_groups_need_deny_perm'], '</label> <a href="<URL>?action=help;in=need_deny_perm" onclick="return reqWin(this);" class="help" title="', $txt['help'], '"></a><br>
						<br>
						<div id="view_perm_col" class="two-columns">
							<fieldset>
								<legend>', $txt['mboards_view_board'], '</legend>
								<table>
									<tr>
										<th></th>
										<th>', $txt['mboards_yes'], '</th>
										<th>', $txt['mboards_no'], '</th>
										<th class="deny_perm"', empty($context['need_deny_perm']) ? ' style="display:none"' : '', '>', $txt['mboards_never'], '</th>
									</tr>';

	foreach ($context['groups'] as $group)
	{
						echo '
									<tr>
										<td class="smalltext">
											<span', $group['is_post_group'] ? ' class="post_group" title="' . $txt['mboards_groups_post_group'] . '"' : '', $group['id'] == 0 ? ' class="regular_members" title="' . $txt['mboards_groups_regular_members'] . '"' : '', '>
												', $group['name'], '
											</span>
										</td>
										<td>
											<input type="radio" name="viewgroup[', $group['id'], ']" value="allow"', $group['view_perm'] == 'allow' ? ' checked' : '', '>
										</td>
										<td>
											<input type="radio" name="viewgroup[', $group['id'], ']" value="disallow"', (empty($context['need_deny_perm']) && $group['view_perm'] == 'deny') || $group['view_perm'] == 'disallow' ? ' checked' : '', '>
										</td>
										<td class="deny_perm cedit"', empty($context['need_deny_perm']) ? ' style="display:none"' : '', '>
											<input type="radio" name="viewgroup[', $group['id'], ']" value="deny"', !empty($context['need_deny_perm']) && $group['view_perm'] == 'deny' ? ' checked' : '', '>
										</td>
									</tr>';
	}

	echo '
								</table>
							</fieldset>
						</div>
						<div id="edit_perm_col" class="two-columns"', !empty($context['view_edit_same']) ? ' style="display:none"' : '', '>
							<fieldset>
								<legend>', $txt['mboards_edit_board'], '</legend>
								<table>
									<tr>
										<th></th>
										<th>', $txt['mboards_yes'], '</th>
										<th>', $txt['mboards_no'], '</th>
										<th class="deny_perm"', empty($context['need_deny_perm']) ? ' style="display:none;"' : '', '>', $txt['mboards_never'], '</th>
									</tr>';

	foreach ($context['groups'] as $group)
	{
						echo '
									<tr>
										<td class="smalltext">
											<span', $group['is_post_group'] ? ' class="post_group" title="' . $txt['mboards_groups_post_group'] . '"' : '', $group['id'] == 0 ? ' class="regular_members" title="' . $txt['mboards_groups_regular_members'] . '"' : '', '>
												', $group['name'], '
											</span>
										</td>
										<td>
											<input type="radio" name="editgroup[', $group['id'], ']" value="allow"', $group['edit_perm'] == 'allow' ? ' checked' : '', '>
										</td>
										<td>
											<input type="radio" name="editgroup[', $group['id'], ']" value="disallow"', (empty($context['need_deny_perm']) && $group['edit_perm'] == 'deny') || $group['edit_perm'] == 'disallow' ? ' checked' : '', '>
										</td>
										<td class="deny_perm cedit"', empty($context['need_deny_perm']) ? ' style="display:none;"' : '', '>
											<input type="radio" name="editgroup[', $group['id'], ']" value="deny"', !empty($context['need_deny_perm']) && $group['edit_perm'] == 'deny' ? ' checked' : '', '>
										</td>
									</tr>';
	}

	// Options to choose moderators, specify as announcement board and choose whether to count posts here.
	echo '
								</table>
							</fieldset>
						</div>
						<br class="clear"><br>
					</dd>
					</dl>
				</fieldset>
				<fieldset>
					<legend>', $txt['todo_advanced'], '</legend>
					<dl class="settings">
						<dt id="priority_dt">
							<strong>', $txt['todo_priority'], ':</strong>
							<dfn>', $txt['todo_priority_desc'], '</dfn>
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
				<div class="righttext">
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