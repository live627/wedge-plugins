<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function todo_menu_items(&$menu_buttons)
{
	global $context, $txt;
	loadPluginLanguage('live627:todo', 'todo');

	$item = array(
		'todo' => array(
			'title' => $txt['todo'],
			'href' => '<URL>?action=todo',
			'show' => allowedTo('view_todo_page'),
		),
	);
	$menu_buttons = array_insert($menu_buttons, 'mlist', $item, 'after');

	add_css('
	.m_todo { float: left; width: 16px; height: 16px; padding: 0; background: url("' . $context['plugins_url']['live627:todo'] . '/todo_small.png") no-repeat 0 0; margin:4px 4px 0 2px; }');
}

function TodoMain()
{
	global $txt, $context;

	loadPluginTemplate('live627:todo', 'todo');
	loadPluginLanguage('live627:todo', 'todo');
	loadSource('Subs-Menu');

	// Define all the menu structure - see Subs-Menu.php for details!
	$todo_areas = array(
		'forum' => array(
			'title' => $txt['todo'],
			'areas' => array(
				'my' => array(
					'label' => $txt['my_items'],
					'function' => 'Todo',
					'permission' => 'view_todo_page',
					'subsections' => array(
						'index' => array($txt['todo_menu_index']),
						'edit' => array($txt['todo_menu_edit']),
					),
				),
			),
		),
	);

	// Actually create the menu!
	$todo_include_data = createMenu($todo_areas);
	unset($todo_areas);

	// Nothing valid?
	if ($todo_include_data == false)
		fatal_lang_error('no_access', false);

	// Build the link tree.
	add_linktree($scripturl . '?action=todo', $txt['todo']);

	if (isset($todo_include_data['current_area']) && $todo_include_data['current_area'] != 'index')
		add_linktree($scripturl . '?action=todo;area=' . $todo_include_data['current_area'], $todo_include_data['label']);

	if (!empty($todo_include_data['current_subsection']) && $todo_include_data['subsections'][$todo_include_data['current_subsection']][0] != $todo_include_data['label'])
		add_linktree($scripturl . '?action=todo;area=' . $todo_include_data['current_area'] . ';sa=' . $todo_include_data['current_subsection'], $todo_include_data['subsections'][$todo_include_data['current_subsection']][0]);

	// Make a note of the Unique ID for this menu.
	$context['todo_menu_id'] = $context['max_menu_id'];
	$context['todo_menu_subject'] = 'menu_data_' . $context['todo_menu_id'];

	// Let's help our tabs along now, shall we?
	$context['todo_area'] = $todo_include_data['current_area'];

	// Come, play, O ye templates.
	$context['sub_template'] = $todo_include_data['current_area'];
	$context['page_title'] = $txt[$context['todo_area']];

	// Now - finally - call the right place!
	if (isset($todo_include_data['file']))
		loadPluginSource('live627:todo', $todo_include_data['file']);

	$todo_include_data['function']();
}

function Todo()
{
	global $context, $txt;

	// Load up all the tabs...
	$context[$context['todo_menu_subject']]['tab_data'] = array(
		'title' => $txt['todo'],
		'description' => $txt['todo_desc'],
	);

	// Format: 'sub-action' => array('function', 'permission')
	$sub_actions = array(
		'index' => 'ListTodo',
		'edit' => 'EditTodo',
	);

	// Default to sub action 'index'
	if (!isset($_GET['sa']) || !isset($sub_actions[$_GET['sa']]))
		$_GET['sa'] = 'index';

	// This area isn't for everyone - do this here since the menu code does not.
	isAllowedTo('view_todo_page');

	// Calls a function based on the sub-action
	$sub_actions[$_GET['sa']]();
}

function ListTodo()
{
	global $txt, $context, $theme, $theme;

	// Deleting?
	if (isset($_POST['delete'], $_POST['remove']))
	{
		checkSession();

		wesql::query('
			DELETE FROM {db_prefix}todo
			WHERE id_todo IN ({array_int:todos})',
			array(
				'todos' => $_POST['remove'],
			)
		);
		call_hook('delete_todos', array($_POST['remove']));
		redirectexit('action=todo;area=my');
	}

	// Changing the status?
	if (isset($_POST['save']))
	{
		checkSession();
		foreach (total_getTodo() as $todo)
		{
			$priority = !empty($_POST['priority'][$todo['id_todo']]) ? 'yes' : 'no';
			if ($priority != $todo['priority'])
				wesql::query('
					UPDATE {db_prefix}todo
					SET priority = {string:priority}
					WHERE id_todo = {int:todo}',
					array(
						'priority' => $priority,
						'todo' => $todo['id_todo'],
					)
				);

			$is_did = !empty($_POST['is_did'][$todo['id_todo']]) ? 'yes' : 'no';
			if ($is_did != $todo['is_did'])
				wesql::query('
					UPDATE {db_prefix}todo
					SET is_did = {string:is_did}
					WHERE id_todo = {int:todo}',
					array(
						'is_did' => $is_did,
						'todo' => $todo['id_todo'],
					)
				);

			$can_search = !empty($_POST['can_search'][$todo['id_todo']]) ? 'yes' : 'no';
			if ($can_search != $todo['can_search'])
				wesql::query('
					UPDATE {db_prefix}todo
					SET can_search = {string:can_search}
					WHERE id_todo = {int:todo}',
					array(
						'can_search' => $can_search,
						'todo' => $todo['id_todo'],
					)
				);
			call_hook('update_todo', array($todo));
		}
		redirectexit('action=todo;area=my');
	}

	// New todo?
	if (isset($_POST['new']))
		redirectexit('action=todo;area=my;sa=edit');

	$listOptions = array(
		'id' => 'todo_todos',
		'base_href' => '<URL>?action=action=todo;area=my',
		'default_sort_col' => 'subject',
		'no_items_label' => $txt['todo_none'],
		'items_per_page' => 25,
		'get_items' => array(
			'function' => 'list_getTodo',
		),
		'get_count' => array(
			'function' => 'list_getTodoize',
		),
		'columns' => array(
			'subject' => array(
				'header' => array(
					'value' => $txt['todo_todosubject'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'db_htmlsafe' => 'subject',
					'style' => 'width: 40%;',
				),
				'sort' => array(
					'default' => 'subject',
					'reverse' => 'subject DESC',
				),
			),
			'priority' => array(
				'header' => array(
					'value' => $txt['todo_priority'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						return \'<select name="priority">
								<option value="high" class="high"\' . ($rowData[\'priority\'] == \'high\' ? \' selected\' : \'\') . \'>\' . $txt[\'todo_priority_high\'] . \'</option>
								<option value="normal" class="normal"\' . ($rowData[\'priority\'] == \'normal\' ? \' selected\' : \'\') . \'>\' . $txt[\'todo_priority_normal\'] . \'</option>
								<option value="low" class="low"\' . ($rowData[\'priority\'] == \'low\' ? \' selected\' : \'\') . \'>\' . $txt[\'todo_priority_low\'] . \'</option>
							</select>\';
					'),
					'style' => 'width: 10%; text-align: cedit;',
				),
				'sort' => array(
					'default' => 'priority DESC',
					'reverse' => 'priority',
				),
			),
			'is_did' => array(
				'header' => array(
					'value' => $txt['todo_is_did'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'is_did\'] == \'no\' ? \'\' : \' checked\';
						return sprintf(\'<span id="is_did_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" subject="is_did[%1$s]" id="is_did_%1$s" value="%1$s"%2$s>\', $rowData[\'id_todo\'], $isChecked, $txt[$rowData[\'is_did\']], $rowData[\'is_did\']);
					'),
					'style' => 'width: 10%; text-align: cedit;',
				),
				'sort' => array(
					'default' => 'is_did DESC',
					'reverse' => 'is_did',
				),
			),
			'can_search' => array(
				'header' => array(
					'value' => $txt['todo_can_search'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'can_search\'] == \'no\' ? \'\' : \' checked\';
						return sprintf(\'<span id="can_search_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" subject="can_search[%1$s]" id="can_search_%1$s" value="%1$s"%2$s>\', $rowData[\'id_todo\'], $isChecked, $txt[$rowData[\'can_search\']], $rowData[\'can_search\']);
					'),
					'style' => 'width: 10%; text-align: cedit;',
				),
				'sort' => array(
					'default' => 'can_search DESC',
					'reverse' => 'can_search',
				),
			),
			'modify' => array(
				'header' => array(
					'value' => $txt['modify'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="<URL>?action=todo;area=my;sa=edit;fid=%1$s">' . $txt['modify'] . '</a>',
						'params' => array(
							'id_todo' => false,
						),
					),
					'style' => 'width: 10%; text-align: cedit;',
				),
			),
			'remove' => array(
				'header' => array(
					'value' => $txt['remove'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						return sprintf(\'<span id="remove_%1$s" class="color_no">%2$s</span>&nbsp;<input type="checkbox" subject="remove[%1$s]" id="remove_%1$s" value="%1$s">\', $rowData[\'id_todo\'], $txt[\'no\']);
					'),
					'style' => 'width: 10%; text-align: cedit;',
				),
				'sort' => array(
					'default' => 'remove DESC',
					'reverse' => 'remove',
				),
			),
		),
		'form' => array(
			'href' => '<URL>?action=todo;area=my',
			'subject' => 'customProfiletodos',
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '<input type="submit" subject="save" value="' . $txt['save'] . '" class="submit">&nbsp;&nbsp;<input type="submit" subject="delete" value="' . $txt['delete'] . '" onclick="return confirm(' . JavaScriptEscape($txt['todo_delete_sure']) . ');" class="delete">&nbsp;&nbsp;<input type="submit" subject="new" value="' . $txt['todo_make_new'] . '" class="new">',
				'style' => 'text-align: right;',
			),
		),
	);
	loadSource('Subs-List');
	createList($listOptions);
	wetem::load('show_list');
	$context['default_list'] = 'todo_todos';
	call_hook('list_todos', array(&$listOptions));
	add_plugin_css_file('live627:todo', 'todo', true);
}

function list_getTodo($start, $items_per_page, $sort, $where, $where_params = array())
{
	$list = array();
	$request = wesql::query('
		SELECT *
		FROM {db_prefix}todo
		WHERE ' . $where . '
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:items_per_page}',
		array_merge($where_params, array(
			'sort' => $sort,
			'start' => $start,
			'items_per_page' => $items_per_page,
		))
	);
	while ($row = wesql::fetch_assoc($request))
		$list[] = $row;
	wesql::free_result($request);

	return $list;
}

function total_getTodo($where, $where_params = array())
{
	$list = array();
	$request = wesql::query('
		SELECT *
		FROM {db_prefix}todo
		WHERE ' . $where,
		array_merge($where_params, array(
		))
	);
	while ($row = wesql::fetch_assoc($request))
		$list[] = $row;
	wesql::free_result($request);
	call_hook('get_todos', array(&$list));
	return $list;
}

function list_getNumTodo()
{
	$request = wesql::query('
		SELECT COUNT(*)
		FROM {db_prefix}todo');

	list ($numProfiletodos) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $numProfiletodos;
}

function EditTodo()
{
	global $txt, $context, $user_info;

	$context['fid'] = isset($_REQUEST['fid']) ? (int) $_REQUEST['fid'] : 0;
	$context['page_title'] = $txt['todo'] . ' - ' . ($context['fid'] ? $txt['todo_title'] : $txt['todo_add']);
	loadPluginTemplate('live627:todo', 'todo');
	loadLanguage('ManagePermissions');
	loadLanguage('ManageBoards');
	wetem::load('edit_todo');
	add_plugin_css_file('live627:todo', 'todo', true);
	add_js('
	var
		strftimeFormat = ' . JavaScriptEscape($user_info['time_format']) . ',
		days = ' . json_encode(array_values($txt['days'])) . ',
		daysShort = ' . json_encode(array_values($txt['days_short'])) . ',
		months = ' . json_encode(array_values($txt['months'])) . ',
		monthsShort = ' . json_encode(array_values($txt['months_short'])) . ';');
	add_plugin_js_file('live627:todo', 'dateinput.js');
	add_js('
	(function() {
		var elem = document.createElement(\'input\');
		elem.setAttribute(\'type\', \'datetime\');

		if (!is_touch || elem.type === \'text\')
		{
			$(\'input[name=due]\').dateinput();
			document.getElementById(\'due\').setAttribute(\'type\', \'text\');
		}
	})();');

	$request = wesql::query('
		SELECT id_permission_set, permission_set_name
		FROM {db_prefix}todo_permission_sets');
	$context['permission_sets'] = array();
	while ($row = wesql::fetch_assoc($request))
	{
		// Format the label nicely.
		if (isset($txt['permissions_permission_set_' . $row['permission_set_name']]))
			$name = $txt['permissions_permission_set_' . $row['permission_set_name']];
		else
			$name = $row['permission_set_name'];

		$context['permission_sets'][$row['id_permission_set']] = array(
			'id' => $row['id_permission_set'],
			'name' => $name,
			'can_modify' => $row['id_permission_set'] == 1 || $row['id_permission_set'] > 4,
			'unformatted_name' => $row['permission_set_name'],
		);
	}
	wesql::free_result($request);

	// We might need this to hide links to certain areas.
	$context['can_manage_permissions'] = allowedTo('manage_permissions');

	// Default membergroups.
	$context['groups'] = array(
		-1 => array(
			'id' => '-1',
			'name' => $txt['parent_guests_only'],
			'is_post_group' => false,
		),
		0 => array(
			'id' => '0',
			'name' => $txt['parent_members_only'],
			'is_post_group' => false,
		)
	);

	// Load membergroups.
	$request = wesql::query('
		SELECT group_name, id_group, min_posts
		FROM {db_prefix}membergroups
		WHERE id_group > {int:moderator_group} OR id_group = {int:global_moderator}
		ORDER BY min_posts, id_group != {int:global_moderator}, group_name',
		array(
			'moderator_group' => 3,
			'global_moderator' => 2,
		)
	);
	while ($row = wesql::fetch_assoc($request))
	{
		$context['groups'][(int) $row['id_group']] = array(
			'id' => $row['id_group'],
			'name' => trim($row['group_name']),
			'is_post_group' => $row['min_posts'] != -1,
		);
	}
	wesql::free_result($request);

	if (empty($settings['allow_guestAccess']))
		unset($context['groups'][-1]);

	// For new boards, we need to simply set up defaults for each of the groups
	$context['view_edit_same'] = true;
	$context['need_deny_perm'] = false;
	if (!$context['fid'])
	{
		foreach ($context['groups'] as $id_group => $details)
			$context['groups'][$id_group] += array(
				'view_perm' => !$details['is_post_group'] ? 'allow' : 'disallow',
				'edit_perm' => !$details['is_post_group'] ? 'allow' : 'disallow',
			);
	}
	else
	{
		$query = wesql::query('
			SELECT id_group, view_perm, edit_perm
			FROM {db_prefix}todo_groups
			WHERE id_board = {int:board}',
			array(
				'board' => $_REQUEST['boardid'],
			)
		);
		while ($row = wesql::fetch_assoc($query))
		{
			$context['groups'][(int) $row['id_group']]['view_perm'] = $row['view_perm'];
			$context['groups'][(int) $row['id_group']]['edit_perm'] = $row['edit_perm'];
			if ($row['view_perm'] != $row['edit_perm'])
				$context['view_edit_same'] = false;
			if ($row['view_perm'] == 'deny' || $row['edit_perm'] == 'deny')
				$context['need_deny_perm'] = true;
		}
		wesql::free_result($query);

		// Go through and fix up any missing items
		foreach ($context['groups'] as $id_group => $group)
		{
			if (!isset($group['view_perm']))
				$context['groups'][$id_group]['view_perm'] = 'disallow';
			if (!isset($group['edit_perm']))
				$context['groups'][$id_group]['edit_perm'] = 'disallow';
		}
	}

	loadLanguage('Profile');

	if ($context['fid'])
	{
		$request = wesql::query('
			SELECT *
			FROM {db_prefix}todo
			WHERE id_todo = {int:current_todo}',
			array(
				'current_todo' => $context['fid'],
			)
		);
		$context['todo'] = array();
		while ($row = wesql::fetch_assoc($request))
			$context['todo'] = array(
				'subject' => $row['subject'],
				'permission_set' => $row['id_permission_set'],
				'priority' => $row['priority'] == 'yes',
				'is_did' => $row['is_did'] == 'yes',
				'can_search' => $row['can_search'] == 'yes',
				'members' => !empty($row['members']) ? explode(',', $row['members']) : array(),
				'groups' => !empty($row['groups']) ? explode(',', $row['groups']) : array(),
			);
		wesql::free_result($request);
	}

	// Setup the default values as needed.
	if (empty($context['todo']))
		$context['todo'] = array(
			'subject' => '',
			'permission_set' => 1,
			'priority' => false,
			'is_did' => true,
			'can_search' => false,
			'members' => array(),
			'groups' => array(),
		);

	// Are we saving?
	if (isset($_POST['save']))
	{
		checkSession();

		$required_fields = array('subject', 'due');

		foreach ($required_fields as $required_field)
			if (empty($_POST[$required_field]))
				$context['post_errors'][] = $txt['league_' . $required_field . '_empty'];

		/*wesql::query('
			SELECT id_member
			FROM {db_prefix}members
			WHERE real_subject = {string:current_commish}',
			array(
				'current_commish' => $_POST['commish'],
			)
		);
		list ($id_member_commish) = $smcFunc['db_fetch_row']($request);
		if (empty($id_member_commish))
			$context['post_errors'][] = sprintf($txt['league_commish_not_found'], $_POST['cocommish']);*/

		// If we have no errors, we can update or create the leagues. Otherwise, return to the form with the errors printed in a nice red box.
		if (empty($context['post_errors']))
		{
			$_POST = htmltrim__recursive($_POST);
			$_POST = htmlspecialchars__recursive($_POST);
			$_POST['sport_levels'] = implode(',', array_intersect($_POST['sport_levels'], array_flip($context['sport_levels'])));
			$_POST['members'] = array(1);

			$priority = !empty($_POST['priority']) ? 'yes' : 'no';
			$is_did = !empty($_POST['is_did']) ? 'yes' : 'no';
			$can_search = !empty($_POST['can_search']) ? 'yes' : 'no';
			$length = isset($_POST['length']) ? (int) $_POST['length'] : 255;
			$groups = !empty($_POST['groups']) ? implode(',', array_keys($_POST['groups'])) : '';

			$up_col = array(
				'subject = {string:subject}', 'id_permission_set = {int:permission_set}', 'is_did = {string:is_did}', 'priority = {string:priority}', 'can_search = {string:can_search}', 'groups = {string:groups}', ' members = {array_int:members}',
			);
			$up_data = array(
				'is_did' => $is_did,
				'priority' => $priority,
				'can_search' => $can_search,
				'current_todo' => $context['fid'],
				'subject' => $_POST['subject'],
				'permission_set' => $_POST['permission_set'],
				'groups' => $groups,
				'members' => $_POST['members'],
			);
			$in_col = array(
				'subject' => 'string', 'id_permission_set' => 'int', 'is_did' => 'string', 'priority' => 'string', 'can_search' => 'string', 'groups' => 'string', 'members' => 'array_int',
			);
			$in_data = array(
				$_POST['subject'], $_POST['permission_set'], $is_did, $priority, $can_search, $groups, $_POST['members'],
			);
			call_hook('save_todo', array(&$up_col, &$up_data, &$in_col, &$in_data));

			if ($context['fid'])
			{
				wesql::query('
					UPDATE {db_prefix}todo
					SET
						' . implode(',
						', $up_col) . '
					WHERE id_todo = {int:current_todo}',
					$up_data
				);
			}
			else
			{
				wesql::insert('',
					'{db_prefix}todo',
					$in_col,
					$in_data,
					array('id_todo')
				);
			}

			// We're going to need the list of groups for this.
			$access_groups = array(
				-1 => array('id_group' => -1, 'view_perm' => 'disallow', 'edit_perm' => 'disallow'),
				0 => array('id_group' => 0, 'view_perm' => 'disallow', 'edit_perm' => 'disallow'),
			);
			$request = wesql::query('
				SELECT id_group
				FROM {db_prefix}membergroups
				WHERE id_group > {int:admin_group} AND id_group != {int:moderator}',
				array(
					'moderator' => 3,
					'admin_group' => 1,
				)
			);

			while ($row = wesql::fetch_assoc($request))
				$access_groups[$row['id_group']] = array('id_group' => $row['id_group'], 'view_perm' => 'disallow', 'edit_perm' => 'disallow');
			wesql::free_result($request);

			if (!empty($_POST['viewgroup']))
			{
				foreach ($_POST['viewgroup'] as $id_group => $access)
				{
					if (!isset($access_groups[$id_group]))
						continue;
					if (empty($_POST['need_deny_perm']) && $access == 'deny')
						$access = 'disallow';

					$access_groups[$id_group]['view_perm'] = $access;
				}
			}

			// If the edit rules are the same as the view rules, we do not care what $_POST has.
			if (!empty($_POST['view_edit_same']))
			{
				foreach ($access_groups as $id_group => $access)
					$access_groups[$id_group]['edit_perm'] = $access['view_perm'];
			}
			elseif (!empty($_POST['editgroup']))
			{
				foreach ($_POST['editgroup'] as $id_group => $access)
				{
					if (!isset($access_groups[$id_group]))
						continue;

					if (empty($_POST['need_deny_perm']) && $access == 'deny')
						$access = 'disallow';

					$access_groups[$id_group]['edit_perm'] = $access;
				}
			}

			if (empty($settings['allow_guestAccess']))
				unset($access_groups[-1]);

			// Who's allowed to access this board.
			if (isset($access_groups))
			{
				// Remove all the old ones.
				wesql::query('
					DELETE FROM {db_prefix}todo_groups
					WHERE id_board = {int:board}',
					array(
						'board' => $board_id,
					)
				);
				$rows = array();
				foreach ($access_groups as $id_group => $row)
				{
					// Skip empty rows
					if ($row['view_perm'] == 'disallow' && $row['edit_perm'] == 'disallow')
						continue;
					$rows[] = array($board_id, $id_group, $row['view_perm'], $row['edit_perm']);
				}

				wesql::insert('insert',
					'{db_prefix}todo_groups',
					array('id_board' => 'int', 'id_group' => 'int', 'view_perm' => 'string', 'edit_perm' => 'string'),
					$rows,
					array('id_board', 'id_group')
				);
			}

			redirectexit('action=todo;area=my');
		}
	}
	elseif (isset($_POST['delete']) && $context['todo']['colsubject'])
	{
		checkSession();

		wesql::query('
			DELETE FROM {db_prefix}todo
			WHERE id_todo = {int:current_todo}',
			array(
				'current_todo' => $context['fid'],
			)
		);
		call_hook('delete_todo', array($context['fid']));
		redirectexit('action=todo;area=my');
	}
}

?>