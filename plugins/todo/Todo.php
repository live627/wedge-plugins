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
	#m_todo { float: left; width: 16px; height: 16px; padding: 0; background: url("' . $context['plugins_url']['live627:todo'] . '/todo_small.png") no-repeat 0 0; margin:4px 4px 0 2px; }');
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
	add_linktree('<URL>?action=todo', $txt['todo']);

	if (isset($todo_include_data['current_area']) && $todo_include_data['current_area'] != 'index')
		add_linktree('<URL>?action=todo;area=' . $todo_include_data['current_area'], $todo_include_data['label']);

	if (!empty($todo_include_data['current_subsection']) && $todo_include_data['subsections'][$todo_include_data['current_subsection']][0] != $todo_include_data['label'])
		add_linktree('<URL>?action=todo;area=' . $todo_include_data['current_area'] . ';sa=' . $todo_include_data['current_subsection'], $todo_include_data['subsections'][$todo_include_data['current_subsection']][0]);

	// Make a note of the Unique ID for this menu.
	$context['todo_menu_id'] = $context['max_menu_id'];
	$context['todo_menu_subject'] = 'menu_data_' . $context['todo_menu_id'];

	// Let's help our tabs along now, shall we?
	$context['todo_area'] = $todo_include_data['current_area'];

	// Come, play, O ye templates.
	wetem::load($todo_include_data['current_area']);
	$context['page_title'] = $txt['todo_menu_' . $todo_include_data['current_subsection']];

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
			$priority = !empty($_POST['priority'][$todo['id_todo']]) && in_array($_POST['priority'][$todo['id_todo']], array('low', 'normal', 'high')) ? $_POST['priority'][$todo['id_todo']] : 'normal';
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
			'function' => 'list_getNumTodo',
		),
		'columns' => array(
			'subject' => array(
				'header' => array(
					'value' => $txt['todo_subject'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'db' => 'subject',
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
						return sprintf(\'<select name="priority[%1$s]">\', $rowData[\'id_todo\']) . \'
								<option value="high" class="high"\' . ($rowData[\'priority\'] == \'high\' ? \' selected\' : \'\') . \'>\' . $txt[\'todo_priority_high\'] . \'</option>
								<option value="normal" class="normal"\' . ($rowData[\'priority\'] == \'normal\' ? \' selected\' : \'\') . \'>\' . $txt[\'todo_priority_normal\'] . \'</option>
								<option value="low" class="low"\' . ($rowData[\'priority\'] == \'low\' ? \' selected\' : \'\') . \'>\' . $txt[\'todo_priority_low\'] . \'</option>
							</select>\';
					'),
					'style' => 'width: 10%;',
					'class' => 'left',
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
						return sprintf(\'<span id="is_did_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="is_did[%1$s]" id="is_did_%1$s" value="%1$s"%2$s>\', $rowData[\'id_todo\'], $isChecked, $txt[$rowData[\'is_did\']], $rowData[\'is_did\']);
					'),
					'style' => 'width: 10%;',
					'class' => 'left',
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
						return sprintf(\'<span id="can_search_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="can_search[%1$s]" id="can_search_%1$s" value="%1$s"%2$s>\', $rowData[\'id_todo\'], $isChecked, $txt[$rowData[\'can_search\']], $rowData[\'can_search\']);
					'),
					'style' => 'width: 10%;',
					'class' => 'left',
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
					'style' => 'width: 10%;',
					'class' => 'left',
				),
			),
			'remove' => array(
				'header' => array(
					'value' => $txt['remove'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<input type="checkbox" name="remove[%1$s]" id="remove_%1$s" value="%1$s">',
						'params' => array(
							'id_todo' => false,
						),
					),
					'style' => 'width: 10%;',
					'class' => 'left',
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
				'value' => '<input type="submit" name="save" value="' . $txt['save'] . '" class="submit">&nbsp;&nbsp;<input type="submit" name="delete" value="' . $txt['delete'] . '" onclick="return confirm(' . JavaScriptEscape($txt['todo_delete_sure']) . ');" class="delete">&nbsp;&nbsp;<input type="submit" name="new" value="' . $txt['todo_make_new'] . '" class="new">',
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

function list_getTodo($start, $items_per_page, $sort, $where = '', $where_params = array())
{
	$list = array();
	$request = wesql::query('
		SELECT *
		FROM {db_prefix}todo' . (!empty($where) ? '
		WHERE ' . $where : '') . '
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

function total_getTodo($where = '', $where_params = array())
{
	$list = array();
	$request = wesql::query('
		SELECT *
		FROM {db_prefix}todo' . (!empty($where) ? '
		WHERE ' . $where : ''),
		$where_params
	);
	while ($row = wesql::fetch_assoc($request))
		$list[] = $row;
	wesql::free_result($request);
	call_hook('get_todos', array(&$list));
	return $list;
}

function list_getNumTodo($where = '', $where_params = array())
{
	$request = wesql::query('
		SELECT COUNT(*)
		FROM {db_prefix}todo' . (!empty($where) ? '
		WHERE ' . $where : ''),
		$where_params
	);

	list ($numItems) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $numItems;
}

function EditTodo()
{
	global $txt, $context, $settings;

	$context['fid'] = isset($_REQUEST['fid']) ? (int) $_REQUEST['fid'] : 0;
	$context['page_title'] = $txt['todo'] . ' - ' . ($context['fid'] ? $txt['todo_title'] : $txt['todo_add']);
	loadPluginTemplate('live627:todo', 'todo');
	loadLanguage('ManagePermissions');
	loadLanguage('ManageBoards');
	wetem::load('edit_todo');
	add_plugin_css_file('live627:todo', 'todo', true);
	add_js('
	var
		strftimeFormat = ' . JavaScriptEscape(we::$user['time_format']) . ',
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

	// We might need this to hide links to certain areas.
	$context['can_manage_permissions'] = allowedTo('manage_permissions');

	// We'll need this for loading up the names of each group.
	loadLanguage('ManageBoards');

	// Default membergroups.
	$context['groups'] = array(
		-1 => $txt['parent_guests_only'],
		0 => '<span class="regular_members" title="' . $txt['mboards_groups_regular_members'] . '">' . $txt['parent_members_only'] . '</span>',
	);

	// Load membergroups.
	$request = wesql::query('
		SELECT id_group, group_name, online_color, min_posts
		FROM {db_prefix}membergroups
		WHERE id_group NOT IN (1, 3)
			AND id_parent = {int:not_inherited}' . (!empty($settings['permission_enable_postgroups']) ? '
			OR min_posts != {int:min_posts}' : '
			AND min_posts = {int:min_posts}') . '
		ORDER BY min_posts, id_group != {int:global_moderator}, group_name',
		array(
			'moderator_group' => 3,
			'global_moderator' => 2,
			'not_inherited' => -2,
			'min_posts' => -1,
		)
	);
	while ($row = wesql::fetch_assoc($request))
		$context['groups'][$row['id_group']] = '<span' . ($row['min_posts'] != -1 ? ' class="post_group" title="' . $txt['mboards_groups_post_group'] . '"' : '') . ($row['online_color'] ? ' style="color: ' . $row['online_color'] . '"' : '') . '>' . $row['group_name'] . '</span>';
	wesql::free_result($request);

	if (empty($settings['allow_guestAccess']))
		unset($context['groups'][-1]);

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
				'due' => $row['due'],
				'priority' => $row['priority'] && in_array($row['priority'], array('low', 'normal', 'high')) ? $row['priority'] : 'normal',
				'is_did' => $row['is_did'] == 'yes',
				'can_search' => $row['can_search'] == 'yes',
				'groups' => !empty($row['groups']) ? explode(',', $row['groups']) : array(),
			);
		wesql::free_result($request);
	}

	// Setup the default values as needed.
	if (empty($context['todo']))
		$context['todo'] = array(
			'subject' => '',
			'due' => '',
			'priority' => 'normal',
			'is_did' => false,
			'can_search' => false,
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

		// If we have no errors, we can update or create the leagues. Otherwise, return to the form with the errors printed in a nice red box.
		if (empty($context['post_errors']))
		{
			$_POST = htmltrim__recursive($_POST);
			$_POST = htmlspecialchars__recursive($_POST);

			$priority = !empty($_POST['priority']) && in_array($_POST['priority'], array('low', 'normal', 'high')) ? $_POST['priority'] : 'normal';
			$is_did = !empty($_POST['is_did']) ? 'yes' : 'no';
			$can_search = !empty($_POST['can_search']) ? 'yes' : 'no';
			$length = isset($_POST['length']) ? (int) $_POST['length'] : 255;
			$groups = !empty($_POST['groups']) ? implode(',', array_keys($_POST['groups'])) : '';

			$up_col = array(
				'subject = {string:subject}', 'due = {string:due}', 'is_did = {string:is_did}', 'priority = {string:priority}', 'can_search = {string:can_search}', 'groups = {string:groups}',
			);
			$up_data = array(
				'is_did' => $is_did,
				'priority' => $priority,
				'can_search' => $can_search,
				'current_todo' => $context['fid'],
				'subject' => $_POST['subject'],
				'due' => $_POST['due'],
				'groups' => $groups,
			);
			$in_col = array(
				'subject' => 'string', 'due' => 'string', 'is_did' => 'string', 'priority' => 'string', 'can_search' => 'string', 'groups' => 'string',
			);
			$in_data = array(
				$_POST['subject'], $_POST['due'], $is_did, $priority, $can_search, $groups,
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