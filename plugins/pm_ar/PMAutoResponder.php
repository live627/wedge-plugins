<?php
// Version: 1.0: PMAutoResponder.php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function pm_ar_personal_message($recipients, $from_name, $subject, $message)
{
	global $context, $user_info;

	if (isset($context['ar_pm']))
		return;

	if (!empty($recipients['to']) || !empty($recipients['bcc']))
	{
		$auto_recipients = array_merge($recipients['to'], $recipients['bcc']);
		foreach ($auto_recipients as &$auto_recipient)
			$auto_recipient = (int) $auto_recipient;

		$request = wesql::query('
			SELECT m.id_member, m.real_name, m.member_name, t.value, t.variable
			FROM {db_prefix}members AS m
				INNER JOIN {db_prefix}themes AS t ON (m.id_member = t.id_member AND t.variable LIKE {string:auto_recipients_var})
			WHERE m.id_member IN ({array_int:auto_recipients})
				AND t.value != ""',
			array(
				'auto_recipients' => $auto_recipients,
				'auto_recipients_var' => '%ar_pm_%',
			)
		);
		$members = array();
		$theme_members = array();
		while ($row = wesql::fetch_assoc($request))
		{
			$members[$row['id_member']] = array(
				'name' => $row['real_name'],
				'username' => $row['member_name'],
			);
			$theme_members[$row['id_member']][$row['variable']] = $row['value'];
		}

		foreach ($members as $id_member => $member)
		{
			if (isset($theme_members[$id_member]['ar_pm_enabled'], $theme_members[$id_member]['ar_pm_subject'], $theme_members[$id_member]['ar_pm_body']))
			{
				$context['ar_pm'] = true;
				pm_ar_apply_rules($id_member, $theme_members[$id_member]['ar_pm_subject'], $theme_members[$id_member]['ar_pm_body'], $theme_members[$id_member]['ar_pm_outbox']);
				sendpm(
					array(
						'to' => array($user_info['id']),
						'bcc' => array()
					),
					$theme_members[$id_member]['ar_pm_subject'],
					$theme_members[$id_member]['ar_pm_body'],
					!empty($theme_members[$id_member]['ar_pm_outbox']),
					array(
						'id' => $id_member,
						'name' => $member['name'],
						'username' => $member['username']
					)
				);
			}
		}
	}
}

function pm_ar_profile_areas(&$profile_areas)
{
	global $txt;

	if (!allowedTo('pm_ar'))
		return $profile_areas;

	loadPluginLanguage('live627:pm_ar', 'PMAutoResponder');
	$profile_areas['edit_profile']['areas']['pm_ar'] = array(
		'label' => $txt['ar_pm_profile_area'],
		'file' => array('live627:pm_ar', 'PMAutoResponder'),
		'function' => 'PMAutoResponderProfile',
		'enabled' => allowedTo(array('profile_extra_own', 'profile_extra_any')),
		'sc' => 'post',
		'permission' => array(
			'own' => array('profile_extra_own'),
			'any' => array('profile_extra_any'),
		),
		'subsections' => array(
			'general' => array($txt['ar_pm_general']),
			'filters' => array($txt['ar_pm_filters']),
		),
	);
}

function PMAutoResponderProfile($memID)
{
	global $context, $txt, $scripturl;

	$sub_actions = array(
		'general' => 'PMAutoResponderGeneral',
		'filters' => 'PMAutoResponderFilters',
	);

	// Default to sub action 'general'
	if (!isset($_GET['sa']) || !isset($sub_actions[$_GET['sa']]))
		$_GET['sa'] = 'general';

	// Create the tabs for the template.
	$context[$context['profile_menu_name']]['tab_data'] = array(
		'title' => $txt['ar_pm_profile_area'],
		'description' => $txt['ar_pm_general_desc'],
		'icon' => 'profile_sm.gif',
		'tabs' => array(
			'general' => array(
				'title' => $txt['ar_pm_general'],
				'description' => $txt['ar_pm_general_desc'],
			),
			'filters' => array(
				'title' => $txt['ar_pm_filters'],
				'description' => $txt['ar_pm_filters_desc'],
			),
		),
	);

	// Calls a function based on the sub-action
	$sub_actions[$_GET['sa']]($memID);
}

function PMAutoResponderGeneral($memID)
{
	global $context, $cur_profile, $txt;

	$context['profile_fields'] = array(
		'ar_pm_enabled' => array(
			'label' => $txt['ar_pm_enabled'],
			'type' => 'check',
			'input_attr' => '',
			'value' => isset($cur_profile['options']['ar_pm_enabled']) ? $cur_profile['options']['ar_pm_enabled'] : '',
		),
		'ar_pm_subject' => array(
			'label' => $txt['ar_pm_subject'],
			'subtext' => $txt['ar_pm_subject_desc'],
			'type' => 'text',
			'input_attr' => '',
			'value' => isset($cur_profile['options']['ar_pm_subject']) ? $cur_profile['options']['ar_pm_subject'] : '',
		),
		'ar_pm_body' => array(
			'type' => 'callback',
			'callback_func' => 'ar_pm_body',
		),
		'ar_pm_outbox' => array(
			'label' => $txt['ar_pm_outbox'],
			'type' => 'check',
			'input_attr' => '',
			'value' => isset($cur_profile['options']['ar_pm_outbox']) ? $cur_profile['options']['ar_pm_outbox'] : '',
		),
	);

	wetem::load('edit_options');
	$context['profile_header_text'] = $txt['ar_pm_profile_area'];
	$context['page_desc'] = $txt['ar_pm_profile_area'];
	$context['profile_execute_on_save'] = array('ar_pm_profile_save');
}

function ar_pm_profile_save()
{
	global $context;

	$_POST['default_options'] = array(
		'ar_pm_enabled',
		'ar_pm_subject',
		'ar_pm_body',
		'ar_pm_outbox',
	);

	makeThemeChanges($context['member'], 1);
}

function pm_ar_load_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	loadPluginLanguage('live627:pm_ar', 'PMAutoResponder');
	$permissionList['membergroup'] += array(
		'pm_ar' => array(false, 'pm', 'use_pm_system'),
	);
}

function pm_ar_illegal_guest_perms()
{
	global $context;

	$context['non_guest_permissions'] = array_unshift($context['non_guest_permissions'],
		'pm_ar'
	);
}

function template_profile_ar_pm_body()
{
	global $cur_profile, $txt;

	echo '
						<dt>
							<strong>', $txt['ar_pm_body'], '</strong>
							<dfn>', $txt['ar_pm_body_desc'], '</dfn>
						</dt>
						<dd>
							<textarea id="ar_pm_body" name="options[ar_pm_body]" style="width:90%; height: 300px;">', isset($cur_profile['options']['ar_pm_body']) ? $cur_profile['options']['ar_pm_body'] : '', '</textarea>
						</dd>';
}

function template_profile_ar_pm_body2()
{
	global $context, $txt;

	echo '
						<dt>
							<strong>', $txt['ar_pm_body'], '</strong>
							<dfn>', $txt['ar_pm_body_desc'], '</dfn>
						</dt>
						<dd>
							<textarea id="body" name="body" style="width:90%; height: 300px;">', isset($context['rule']['body']) ? $context['rule']['body'] : '', '</textarea>
						</dd>';
}

// List all rules, and allow adding/entering etc....
function PMAutoResponderFilters($memID)
{
	global $txt, $context, $user_info, $scripturl;

	pm_ar_load_rules(false, $memID);
	loadLanguage('PersonalMessage');
	wetem::load('rules');

	// Likely to need all the groups!
	$request = wesql::query('
		SELECT mg.id_group, mg.group_name, IFNULL(gm.id_member, 0) AS can_moderate, mg.hidden
		FROM {db_prefix}membergroups AS mg
			LEFT JOIN {db_prefix}group_moderators AS gm ON (gm.id_group = mg.id_group AND gm.id_member = {int:current_member})
		WHERE mg.min_posts = {int:min_posts}
			AND mg.id_group != {int:moderator_group}
			AND mg.hidden = {int:not_hidden}
		ORDER BY mg.group_name',
		array(
			'current_member' => $user_info['id'],
			'min_posts' => -1,
			'moderator_group' => 3,
			'not_hidden' => 0,
		)
	);
	$context['groups'] = array();
	while ($row = wesql::fetch_assoc($request))
	{
		// Hide hidden groups!
		if ($row['hidden'] && !$row['can_moderate'] && !allowedTo('manage_membergroups'))
			continue;

		$context['groups'][$row['id_group']] = $row['group_name'];
	}
	wesql::free_result($request);

	// Editing a specific one?
	if (isset($_GET['add']))
	{
		$context['in'] = isset($_GET['in']) && isset($context['rules'][$_GET['in']])? (int) $_GET['in'] : 0;
		loadBlock('edit_options');

		// Current rule information...
		if ($context['in'])
		{
			$context['rule'] = $context['rules'][$context['in']];
			$members = array();
			// Need to get member names!
			foreach ($context['rule']['criteria'] as $k => $criteria)
				if ($criteria['t'] == 'mid' && !empty($criteria['v']))
					$members[(int) $criteria['v']] = $k;

			if (!empty($members))
			{
				$request = wesql::query('
					SELECT id_member, member_name
					FROM {db_prefix}members
					WHERE id_member IN ({array_int:member_list})',
					array(
						'member_list' => array_keys($members),
					)
				);
				while ($row = wesql::fetch_assoc($request))
					$context['rule']['criteria'][$members[$row['id_member']]]['v'] = $row['member_name'];
				wesql::free_result($request);
			}
		}
		else
			$context['rule'] = array(
				'id' => '',
				'name' => '',
				'criteria' => array(),
				'subject' => '',
				'message' => '',
				'save_in_outbox' => 0,
				'logic' => 'and',
			);

		$context['profile_fields'] = array(
			'name' => array(
				'label' => $txt['pm_rule_name'],
				'subtext' => $txt['pm_rule_name_desc'],
				'type' => 'text',
				'input_attr' => '',
				'value' => empty($context['rule']['name']) ? $txt['pm_rule_name_default'] : $context['rule']['name'],
			),
			'ar_pm_add_rule' => array(
				'type' => 'callback',
				'callback_func' => 'ar_pm_add_rule',
			),
			'subject' => array(
				'label' => $txt['ar_pm_subject'],
				'subtext' => $txt['ar_pm_subject_desc'],
				'type' => 'text',
				'input_attr' => '',
				'value' => isset($context['rule']['subject']) ? $context['rule']['subject'] : '',
			),
			'body' => array(
				'type' => 'callback',
				'callback_func' => 'ar_pm_body2',
			),
			'save_in_outbox' => array(
				'label' => $txt['ar_pm_outbox'],
				'type' => 'check',
				'input_attr' => '',
				'value' => isset($context['rule']['save_in_outbox']) ? $context['rule']['save_in_outbox'] : '',
			),
		);
		$context['profile_header_text'] = $txt['ar_pm_profile_area'];
		$context['page_desc'] = $txt['ar_pm_profile_area'];
		$context['submit_button_text'] = $txt['pm_rule_save'];
		$context['profile_custom_submit_url'] = $scripturl . '?action=profile;area=' . $context['menu_item_selected'] . ';sa=filters;u=' . $context['id_member'] . ';pmarsave';
	}
	// Saving?
	elseif (isset($_GET['pmarsave']))
	{
		checkSession('post');
		$context['in'] = isset($_GET['in']) && isset($context['rules'][$_GET['in']])? (int) $_GET['in'] : 0;

		// Name is easy!
		$name = westr::safe(trim($_POST['name']));
		if (empty($name))
			fatal_lang_error('pm_rule_no_name', false);

		// Sanity check...
		if (empty($_POST['ruletype']))
			fatal_lang_error('pm_rule_no_criteria', false);

		// Let's do the criteria first - it's also hardest!
		$criteria = array();
		foreach ($_POST['ruletype'] as $ind => $type)
		{
			// Check everything is here...
			if ($type == 'gid' && (!isset($_POST['ruledefgroup'][$ind]) || !isset($context['groups'][$_POST['ruledefgroup'][$ind]])))
				continue;
			elseif ($type != 'bud' && !isset($_POST['ruledef'][$ind]))
				continue;

			// Members need to be found.
			if ($type == 'mid')
			{
				$name = trim($_POST['ruledef'][$ind]);
				$request = wesql::query('
					SELECT id_member
					FROM {db_prefix}members
					WHERE real_name = {string:member_name}
						OR member_name = {string:member_name}',
					array(
						'member_name' => $name,
					)
				);
				if ($smcFunc['db_num_rows']($request) == 0)
					continue;
				list ($memID) = $smcFunc['db_fetch_row']($request);
				wesql::free_result($request);

				$criteria[] = array('t' => 'mid', 'v' => $memID);
			}
			elseif ($type == 'bud')
				$criteria[] = array('t' => 'bud', 'v' => 1);
			elseif ($type == 'gid')
				$criteria[] = array('t' => 'gid', 'v' => (int) $_POST['ruledefgroup'][$ind]);
			elseif (in_array($type, array('sub', 'msg')) && trim($_POST['ruledef'][$ind]) != '')
				$criteria[] = array('t' => $type, 'v' => westr::safe(trim($_POST['ruledef'][$ind])));
		}
		$is_or = $_POST['rule_logic'] == 'or' ? 'yes' : 'no';
		$save_in_outbox = !empty($_POST['save_in_outbox']) ? 'yes' : 'no';

		if (empty($criteria))
			fatal_lang_error('pm_rule_no_criteria', false);

		// What are we storing?
		$criteria = serialize($criteria);
		$subject = westr::safe(trim($_POST['subject']));
		$body = westr::safe(trim($_POST['body']));

		// Create the rule?
		if (empty($context['in']))
			wesql::insert('',
				'{db_prefix}pm_ar_rules',
				array(
					'id_member' => 'int', 'name' => 'string', 'criteria' => 'string',
					'subject' => 'string', 'body' => 'string', 'save_in_outbox' => 'int', 'is_or' => 'string',
				),
				array(
					$user_info['id'], $name, $criteria, $subject, $body, $save_in_outbox, $is_or,
				),
				array('id_rule')
			);
		else
			wesql::query('
				UPDATE {db_prefix}pm_ar_rules
				SET name = {string:name}, criteria = {string:criteria}, subject = {string:subject},
					body = {string:body}, save_in_outbox = {string:save_in_outbox}, is_or = {string:is_or}
				WHERE id_rule = {int:id_rule}
					AND id_member = {int:current_member}',
				array(
					'current_member' => $user_info['id'],
					'is_or' => $is_or,
					'id_rule' => $context['in'],
					'name' => $name,
					'criteria' => $criteria,
					'subject' => $subject,
					'body' => $body,
					'save_in_outbox' => $save_in_outbox,
				)
			);

		redirectexit('action=profile;area=pm_ar;sa=filters');
	}
	// Deleting?
	elseif (isset($_POST['delselected']) && !empty($_POST['delrule']))
	{
		checkSession('post');
		$delete_list = array();
		foreach ($_POST['delrule'] as $k => $v)
			$delete_list[] = (int) $k;

		if (!empty($delete_list))
			wesql::query('
				DELETE FROM {db_prefix}pm_ar_rules
				WHERE id_rule IN ({array_int:delete_list})
					AND id_member = {int:current_member}',
				array(
					'current_member' => $user_info['id'],
					'delete_list' => $delete_list,
				)
			);

		redirectexit('action=profile;area=pm_ar;sa=filters');
	}
}

// This will apply rules to all unread messages. If all_messages is set will, clearly, do it to all!
function pm_ar_apply_rules($id_member_from, &$subject, &$body, &$save_in_outbox)
{
	global $context, $user_info;

	// Want this - duh!
	pm_ar_load_rules(false, $id_member_from);

	// No rules?
	if (empty($context['rules']))
		return;

	foreach ($context['rules'] as $rule)
	{
		$match = false;
		// Loop through all the criteria hoping to make a match.
		foreach ($rule['criteria'] as $criterium)
		{
			if (($criterium['t'] == 'mid' && $criterium['v'] == $id_member_from) || ($criterium['t'] == 'gid' && in_array($criterium['v'], $user_info['groups'])) || ($criterium['t'] == 'sub' && strpos($subject, $criterium['v']) !== false) || ($criterium['t'] == 'msg' && strpos($body, $criterium['v']) !== false))
				$match = true;
			// If we're adding and one criteria don't match then we stop!
			elseif ($rule['logic'] == 'and')
			{
				$match = false;
				break;
			}
		}

		// If we have a match the rule must be true - act!
		if ($match)
		{
			$subject = $rule['subject'];
			$body = $rule['body'];
			$save_in_outbox = $rule['save_in_outbox'];
		}
	}
}

// Load up all the rules for the current user.
function pm_ar_load_rules($reload = false, $id_member_from)
{
	global $user_info, $context;

	if (isset($context['rules']) && !$reload)
		return;

	$request = wesql::query('
		SELECT
			id_rule, name, criteria, subject, body, save_in_outbox, is_or
		FROM {db_prefix}pm_ar_rules
		WHERE id_member = {int:id_member_from}',
		array(
			'id_member_from' => $id_member_from,
		)
	);
	$context['rules'] = array();
	// Simply fill in the data!
	while ($row = wesql::fetch_assoc($request))
		$context['rules'][$row['id_rule']] = array(
			'id' => $row['id_rule'],
			'name' => $row['name'],
			'criteria' => unserialize($row['criteria']),
			'subject' => $row['subject'],
			'body' => $row['body'],
			'save_in_outbox' => $row['save_in_outbox'] == 'yes',
			'logic' => $row['is_or'] == 'yes' ? 'or' : 'and',
		);

	wesql::free_result($request);
}

// Manage rules.
// !!! TODO: Convert this to use the generic list.
function template_rules()
{
	global $context, $settings, $options, $txt, $scripturl;

	echo '
	<form action="', $scripturl, '?action=profile;area=pm_ar;sa=filters;u=' . $context['id_member'] . '" method="post" accept-charset="UTF-8" name="manRules" id="manrules">
		<div class="cat_bar">
			<h3 class="catbg">', $txt['pm_manage_rules'], '</h3>
		</div>
		<div class="description">
			', $txt['pm_manage_rules_desc'], '
		</div>
		<table width="100%" class="table_grid">
		<thead>
			<tr class="catbg">
				<th class="lefttext first_th">
					', $txt['pm_rule_title'], '
				</th>
				<th width="4%" class="centertext last_th">';

	if (!empty($context['rules']))
		echo '
					<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />';

	echo '
				</th>
			</tr>
		</thead>
		<tbody>';

	if (empty($context['rules']))
		echo '
			<tr class="windowbg2">
				<td colspan="2" align="center">
					', $txt['pm_rules_none'], '
				</td>
			</tr>';

	$alternate = false;
	foreach ($context['rules'] as $rule)
	{
		echo '
			<tr class="', $alternate ? 'windowbg' : 'windowbg2', '">
				<td>
					<a href="', $scripturl, '?action=profile;area=pm_ar;sa=filters;add;in=', $rule['id'], ';u=' . $context['id_member'] . '">', $rule['name'], '</a>
				</td>
				<td width="4%" align="center">
					<input type="checkbox" name="delrule[', $rule['id'], ']" class="input_check" />
				</td>
			</tr>';
		$alternate = !$alternate;
	}

	echo '
		</tbody>
		</table>
		<div class="righttext">
			[<a href="', $scripturl, '?action=profile;area=pm_ar;sa=filters;add;in=0;u=' . $context['id_member'] . '">', $txt['pm_add_rule'], '</a>]';

	if (!empty($context['rules']))
		echo '
			<input type="hidden" name="', $context['session_var'], '" value="', $context['session_id'], '" />
			<input type="submit" name="delselected" value="', $txt['pm_delete_selected_rule'], '" onclick="return confirm(\'', $txt['pm_js_delete_rule_confirm'], '\');" class="button_submit smalltext" />';

	echo '
		</div>
	</form>';

}

// Template for adding/editing a rule.
function template_profile_ar_pm_add_rule()
{
	global $context, $settings, $options, $txt, $scripturl;

	$context['profile_javascript'] = '
			var criteriaNum = 0;
			var actionNum = 0;
			var groups = [];';

	foreach ($context['groups'] as $id => $title)
		$context['profile_javascript'] .= '
			groups[' . $id . '] = "' . addslashes($title) . '";';

	$context['profile_javascript'] .= '
			function addCriteriaOption()
			{
				if (criteriaNum == 0)
				{
					for (var i = 0; i < document.forms.creator.elements.length; i++)
						if (document.forms.creator.elements[i].id.substr(0, 8) == "ruletype")
							criteriaNum++;
				}
				criteriaNum++

				setOuterHTML(document.getElementById("criteriaAddHere"), \'<br /><select name="ruletype[\' + criteriaNum + \']" id="ruletype\' + criteriaNum + \'" onchange="updateRuleDef(\' + criteriaNum + \'); rebuildRuleDesc();"><option value="">' . addslashes($txt['pm_rule_criteria_pick']) . ':<\' + \'/option><option value="mid">' . addslashes($txt['pm_rule_mid']) . '<\' + \'/option><option value="gid">' . addslashes($txt['pm_rule_gid']) . '<\' + \'/option><option value="sub">' . addslashes($txt['pm_rule_sub']) . '<\' + \'/option><option value="msg">' . addslashes($txt['pm_rule_msg']) . '<\' + \'/option><option value="bud">' . addslashes($txt['pm_rule_bud']) . '<\' + \'/option><\' + \'/select>&nbsp;<span id="defdiv\' + criteriaNum + \'" style="display: none;"><input type="text" name="ruledef[\' + criteriaNum + \']" id="ruledef\' + criteriaNum + \'" onkeyup="rebuildRuleDesc();" value="" class="input_text" /><\' + \'/span><span id="defseldiv\' + criteriaNum + \'" style="display: none;"><select name="ruledefgroup[\' + criteriaNum + \']" id="ruledefgroup\' + criteriaNum + \'" onchange="rebuildRuleDesc();"><option value="">' . addslashes($txt['pm_rule_sel_group']) . '<\' + \'/option>';

	foreach ($context['groups'] as $id => $group)
		$context['profile_javascript'] .= '<option value="' . $id . '">' . strtr($group, array("'" => "\'")) . '<\' + \'/option>';

	$context['profile_javascript'] .= '<\' + \'/select><\' + \'/span><span id="criteriaAddHere"><\' + \'/span>\');
			}

			function updateRuleDef(optNum)
			{
				if (document.getElementById("ruletype" + optNum).value == "gid")
				{
					document.getElementById("defdiv" + optNum).style.display = "none";
					document.getElementById("defseldiv" + optNum).style.display = "";
				}
				else if (document.getElementById("ruletype" + optNum).value == "bud" || document.getElementById("ruletype" + optNum).value == "")
				{
					document.getElementById("defdiv" + optNum).style.display = "none";
					document.getElementById("defseldiv" + optNum).style.display = "none";
				}
				else
				{
					document.getElementById("defdiv" + optNum).style.display = "";
					document.getElementById("defseldiv" + optNum).style.display = "none";
				}
			}

			// Rebuild the rule description!
			function rebuildRuleDesc()
			{
				// Start with nothing.
				var text = "";
				var joinText = "";
				var actionText = "";
				var hadBuddy = false;
				var foundCriteria = false;
				var foundAction = false;
				var curNum, curVal, curDef;

				for (var i = 0; i < document.forms.creator.elements.length; i++)
				{
					if (document.forms.creator.elements[i].id.substr(0, 8) == "ruletype")
					{
						if (foundCriteria)
							joinText = document.getElementById("logic").value == \'and\' ? ' . JavaScriptEscape(' ' . $txt['pm_readable_and'] . ' ') . ' : ' . JavaScriptEscape(' ' . $txt['pm_readable_or'] . ' ') . ';
						else
							joinText = \'\';
						foundCriteria = true;

						curNum = document.forms.creator.elements[i].id.match(/\d+/);
						curVal = document.forms.creator.elements[i].value;
						if (curVal == "gid")
							curDef = document.getElementById("ruledefgroup" + curNum).value.php_htmlspecialchars();
						else if (curVal != "bud")
							curDef = document.getElementById("ruledef" + curNum).value.php_htmlspecialchars();
						else
							curDef = "";

						// What type of test is this?
						if (curVal == "mid" && curDef)
							text += joinText + ' . JavaScriptEscape($txt['pm_readable_member']) . '.replace("{MEMBER}", curDef);
						else if (curVal == "gid" && curDef && groups[curDef])
							text += joinText + ' . JavaScriptEscape($txt['pm_readable_group']) . '.replace("{GROUP}", groups[curDef]);
						else if (curVal == "sub" && curDef)
							text += joinText + ' . JavaScriptEscape($txt['pm_readable_subject']) . '.replace("{SUBJECT}", curDef);
						else if (curVal == "msg" && curDef)
							text += joinText + ' . JavaScriptEscape($txt['pm_readable_body']) . '.replace("{BODY}", curDef);
						else if (curVal == "bud" && !hadBuddy)
						{
							text += joinText + ' . JavaScriptEscape($txt['pm_readable_buddy']) . ';
							hadBuddy = true;
						}
					}
				}

				// If still nothing make it default!
				if (text == "" || !foundCriteria)
					text = "' . $txt['pm_rule_not_defined'] . '";
				else
				{
					if (actionText != "")
						text += ' . JavaScriptEscape(' ' . $txt['pm_readable_then'] . ' ') . ' + actionText;
					text = ' . JavaScriptEscape($txt['pm_readable_start']) . ' + text + ' . JavaScriptEscape($txt['pm_readable_end']) . ';
				}

				// Set the actual HTML!
				//setInnerHTML(document.getElementById("ruletext"), text);
			}';

	echo '
				<dt><strong>', $txt['pm_rule_criteria'], '</strong></dt><dd>';

	// Add a dummy criteria to allow expansion for none js users.
	$context['rule']['criteria'][] = array('t' => '', 'v' => '');

	// For each criteria print it out.
	$isFirst = true;
	foreach ($context['rule']['criteria'] as $k => $criteria)
	{
		if (!$isFirst && $criteria['t'] == '')
			echo '<div id="removeonjs1">';
		elseif (!$isFirst)
			echo '<br />';

		echo '
					<select name="ruletype[', $k, ']" id="ruletype', $k, '" onchange="updateRuleDef(', $k, '); rebuildRuleDesc();">
						<option value="">', $txt['pm_rule_criteria_pick'], ':</option>
						<option value="mid" ', $criteria['t'] == 'mid' ? 'selected="selected"' : '', '>', $txt['pm_rule_mid'], '</option>
						<option value="gid" ', $criteria['t'] == 'gid' ? 'selected="selected"' : '', '>', $txt['pm_rule_gid'], '</option>
						<option value="sub" ', $criteria['t'] == 'sub' ? 'selected="selected"' : '', '>', $txt['pm_rule_sub'], '</option>
						<option value="msg" ', $criteria['t'] == 'msg' ? 'selected="selected"' : '', '>', $txt['pm_rule_msg'], '</option>
						<option value="bud" ', $criteria['t'] == 'bud' ? 'selected="selected"' : '', '>', $txt['pm_rule_bud'], '</option>
					</select>
					<span id="defdiv', $k, '" ', !in_array($criteria['t'], array('gid', 'bud')) ? '' : 'style="display: none;"', '>
						<input type="text" name="ruledef[', $k, ']" id="ruledef', $k, '" onkeyup="rebuildRuleDesc();" value="', in_array($criteria['t'], array('mid', 'sub', 'msg')) ? $criteria['v'] : '', '" class="input_text" />
					</span>
					<span id="defseldiv', $k, '" ', $criteria['t'] == 'gid' ? '' : 'style="display: none;"', '>
						<select name="ruledefgroup[', $k, ']" id="ruledefgroup', $k, '" onchange="rebuildRuleDesc();">
							<option value="">', $txt['pm_rule_sel_group'], '</option>';

		foreach ($context['groups'] as $id => $group)
			echo '
							<option value="', $id, '" ', $criteria['t'] == 'gid' && $criteria['v'] == $id ? 'selected="selected"' : '', '>', $group, '</option>';
		echo '
						</select>
					</span>';

		// If this is the dummy we add a means to hide for non js users.
		if ($isFirst)
			$isFirst = false;
		elseif ($criteria['t'] == '')
			echo '</div>';
	}

	echo '
					<span id="criteriaAddHere"></span><br />
					<a href="#" onclick="addCriteriaOption(); return false;" id="addonjs1" style="display: none;">(', $txt['pm_rule_criteria_add'], ')</a>
					</dd>
					<dt><strong>', $txt['pm_rule_logic'], ':</strong></dt><dd>
					<select name="rule_logic" id="logic" onchange="rebuildRuleDesc();">
						<option value="and" ', $context['rule']['logic'] == 'and' ? 'selected="selected"' : '', '>', $txt['pm_rule_logic_and'], '</option>
						<option value="or" ', $context['rule']['logic'] == 'or' ? 'selected="selected"' : '', '>', $txt['pm_rule_logic_or'], '</option>
					</select>
				</dd>';

	foreach ($context['rule']['criteria'] as $k => $c)
		$context['profile_javascript'] .= '
			updateRuleDef(' . $k . ');';

	$context['profile_javascript'] .= '
			rebuildRuleDesc();';

	// If this isn't a new rule and we have JS enabled remove the JS compatibility stuff.
	if ($context['in'])
		$context['profile_javascript'] .= '
			document.getElementById("removeonjs1").style.display = "none";';

	$context['profile_javascript'] .= '
			document.getElementById("addonjs1").style.display = "";';

	add_js_inline($context['profile_javascript']);
}

?>