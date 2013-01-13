<?php

function pf_admin_areas()
{
	global $txt, $admin_areas;

	loadPluginLanguage('live627:post_fields', 'PostFields');
	$admin_areas['plugins']['areas']['postfields'] = array_merge($admin_areas['plugins']['areas']['postfields'], array(
		'label' => $txt['post_fields'],
		'function' => 'PostFields',
		'subsections' => array(
			'index' => array($txt['pf_menu_index']),
			'edit' => array($txt['pf_menu_edit']),
		),
	));
}

function PostFields()
{
	global $context, $txt;

	// Load up all the tabs...
	$context[$context['admin_menu_name']]['tab_data'] = array(
		'title' => $txt['post_fields'],
		'description' => $txt['post_fields_desc'],
	);

	// Format: 'sub-action' => array('function', 'permission')
	$sub_actions = array(
		'index' => 'ListPostFields',
		'edit' => 'EditPostField',
	);

	// Default to sub action 'index'
	if (!isset($_GET['sa']) || !isset($sub_actions[$_GET['sa']]))
		$_GET['sa'] = 'index';

	// This area is reserved for admins only - do this here since the menu code does not.
	isAllowedTo('asmin_forum');

	// Calls a function based on the sub-action
	$sub_actions[$_GET['sa']]();
}

function ListPostFields()
{
	global $txt, $context, $theme, $theme;

	// Deleting?
	if (isset($_POST['delete'], $_POST['remove']))
	{
		checkSession();

		// Delete the user data first.
		wesql::query('
			DELETE FROM {db_prefix}message_data
			WHERE id_field IN ({array_int:fields})',
			array(
				'fields' => $_POST['remove'],
			)
		);
		// Finally - the fields themselves are gone!
		wesql::query('
			DELETE FROM {db_prefix}message_fields
			WHERE id_field IN ({array_int:fields})',
			array(
				'fields' => $_POST['remove'],
			)
		);
		call_hook('delete_post_fields', array($_POST['remove']));
		redirectexit('action=admin;area=postfields');
	}

	// Changing the status?
	if (isset($_POST['save']))
	{
		checkSession();
		foreach (total_getPostFields() as $field)
		{
			$bbc = !empty($_POST['bbc'][$field['id_field']]) ? 'yes' : 'no';
			if ($bbc != $field['bbc'])
				wesql::query('
					UPDATE {db_prefix}message_fields
					SET bbc = {string:bbc}
					WHERE id_field = {int:field}',
					array(
						'bbc' => $bbc,
						'field' => $field['id_field'],
					)
				);

			$active = !empty($_POST['active'][$field['id_field']]) ? 'yes' : 'no';
			if ($active != $field['active'])
				wesql::query('
					UPDATE {db_prefix}message_fields
					SET active = {string:active}
					WHERE id_field = {int:field}',
					array(
						'active' => $active,
						'field' => $field['id_field'],
					)
				);

			$can_search = !empty($_POST['can_search'][$field['id_field']]) ? 'yes' : 'no';
			if ($can_search != $field['can_search'])
				wesql::query('
					UPDATE {db_prefix}message_fields
					SET can_search = {string:can_search}
					WHERE id_field = {int:field}',
					array(
						'can_search' => $can_search,
						'field' => $field['id_field'],
					)
				);
			call_hook('update_post_field', array($field));
		}
		redirectexit('action=admin;area=postfields');
	}

	// New field?
	if (isset($_POST['new']))
		redirectexit('action=admin;area=postfields;sa=edit');

	$listOptions = array(
		'id' => 'pf_fields',
		'base_href' => '<URL>?action=action=admin;area=postfields',
		'default_sort_col' => 'name',
		'no_items_label' => $txt['pf_none'],
		'items_per_page' => 25,
		'get_items' => array(
			'function' => 'list_getPostFields',
		),
		'get_count' => array(
			'function' => 'list_getPostFieldSize',
		),
		'columns' => array(
			'name' => array(
				'header' => array(
					'value' => $txt['pf_fieldname'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $scripturl;

						return sprintf(\'<a href="%1$s?action=admin;area=postfields;sa=edit;fid=%2$d">%3$s</a><div class="smalltext">%4$s</div>\', $scripturl, $rowData[\'id_field\'], $rowData[\'name\'], $rowData[\'description\']);
					'),
					'style' => 'width: 40%;',
				),
				'sort' => array(
					'default' => 'name',
					'reverse' => 'name DESC',
				),
			),
			'type' => array(
				'header' => array(
					'value' => $txt['pf_fieldtype'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;

						$textKey = sprintf(\'pf_type_%1$s\', $rowData[\'type\']);
						return isset($txt[$textKey]) ? $txt[$textKey] : $textKey;
					'),
					'style' => 'width: 10%; text-align: center;',
				),
				'sort' => array(
					'default' => 'type',
					'reverse' => 'type DESC',
				),
			),
			'bbc' => array(
				'header' => array(
					'value' => $txt['pf_bbc'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'bbc\'] == \'no\' ? \'\' : \' checked\';
						return sprintf(\'<span id="bbc_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="bbc[%1$s]" id="bbc_%1$s" value="%1$s"%2$s>\', $rowData[\'id_field\'], $isChecked, $txt[$rowData[\'bbc\']], $rowData[\'bbc\']);
					'),
					'style' => 'width: 10%; text-align: center;',
				),
				'sort' => array(
					'default' => 'bbc DESC',
					'reverse' => 'bbc',
				),
			),
			'active' => array(
				'header' => array(
					'value' => $txt['pf_active'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'active\'] == \'no\' ? \'\' : \' checked\';
						return sprintf(\'<span id="active_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="active[%1$s]" id="active_%1$s" value="%1$s"%2$s>\', $rowData[\'id_field\'], $isChecked, $txt[$rowData[\'active\']], $rowData[\'active\']);
					'),
					'style' => 'width: 10%; text-align: center;',
				),
				'sort' => array(
					'default' => 'active DESC',
					'reverse' => 'active',
				),
			),
			'can_search' => array(
				'header' => array(
					'value' => $txt['pf_can_search'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'can_search\'] == \'no\' ? \'\' : \' checked\';
						return sprintf(\'<span id="can_search_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="can_search[%1$s]" id="can_search_%1$s" value="%1$s"%2$s>\', $rowData[\'id_field\'], $isChecked, $txt[$rowData[\'can_search\']], $rowData[\'can_search\']);
					'),
					'style' => 'width: 10%; text-align: center;',
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
						'format' => '<a href="<URL>?action=admin;area=postfields;sa=edit;fid=%1$s">' . $txt['modify'] . '</a>',
						'params' => array(
							'id_field' => false,
						),
					),
					'style' => 'width: 10%; text-align: center;',
				),
			),
			'remove' => array(
				'header' => array(
					'value' => $txt['remove'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						return sprintf(\'<span id="remove_%1$s" class="color_no">%2$s</span>&nbsp;<input type="checkbox" name="remove[%1$s]" id="remove_%1$s" value="%1$s">\', $rowData[\'id_field\'], $txt[\'no\']);
					'),
					'style' => 'width: 10%; text-align: center;',
				),
				'sort' => array(
					'default' => 'remove DESC',
					'reverse' => 'remove',
				),
			),
		),
		'form' => array(
			'href' => '<URL>?action=admin;area=postfields',
			'name' => 'customProfileFields',
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '<input type="submit" name="save" value="' . $txt['save'] . '" class="submit">&nbsp;&nbsp;<input type="submit" name="delete" value="' . $txt['delete'] . '" onclick="return confirm(' . JavaScriptEscape($txt['pf_delete_sure']) . ');" class="delete">&nbsp;&nbsp;<input type="submit" name="new" value="' . $txt['pf_make_new'] . '" class="new">',
				'style' => 'text-align: right;',
			),
		),
	);
	loadSource('Subs-List');
	createList($listOptions);
	wetem::load('show_list');
	$context['default_list'] = 'pf_fields';
	call_hook('list_post_fields', array(&$listOptions));
	$context['css_main_files'][] = 'postfieldsadmin';
	$context['skin_folders'][] = array($context['plugins_dir']['live627:post_fields'] . '/', 'live627:post_fields_');
	$theme['live627:post_fields_url'] = $context['plugins_dir']['live627:post_fields'];
}

function list_getPostFields($start, $items_per_page, $sort)
{
	$list = array();
	$request = wesql::query('
		SELECT id_field, name, description, type, bbc, active, can_search
		FROM {db_prefix}message_fields
		ORDER BY {raw:sort}
		LIMIT {int:start}, {int:items_per_page}',
		array(
			'sort' => $sort,
			'start' => $start,
			'items_per_page' => $items_per_page,
		)
	);
	while ($row = wesql::fetch_assoc($request))
		$list[] = $row;
	wesql::free_result($request);

	return $list;
}

function total_getPostFields()
{
	$list = array();
	$request = wesql::query('
		SELECT *
		FROM {db_prefix}message_fields');
	while ($row = wesql::fetch_assoc($request))
		$list[] = $row;
	wesql::free_result($request);
	call_hook('get_post_fields', array(&$list));
	return $list;
}

function get_post_fields_filtered($board)
{
	$fields = total_getPostFields();
	$list = array();
	foreach ($fields as $field)
	{
		$board_list = array_flip(explode(',', $field['boards']));
		if (!isset($board_list[$board]))
			continue;

		$group_list = explode(',', $field['groups']);
		$is_allowed = array_intersect(we::$user['groups'], $group_list);
		if (empty($is_allowed))
			continue;

		$list[$field['id_field']] = $field;
	}
	call_hook('get_post_fields_filtered', array(&$list, $board));
	return $list;
}

function list_getPostFieldSize()
{
	$request = wesql::query('
		SELECT COUNT(*)
		FROM {db_prefix}message_fields');

	list ($numProfileFields) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $numProfileFields;
}

function EditPostField()
{
	global $txt, $scripturl, $context, $theme;

	$context['fid'] = isset($_REQUEST['fid']) ? (int) $_REQUEST['fid'] : 0;
	$context['page_title'] = $txt['post_fields'] . ' - ' . ($context['fid'] ? $txt['pf_title'] : $txt['pf_add']);
	$context['page_title2'] = $txt['post_fields'] . ' - ' . ($context['fid'] ? $txt['pf_title'] : $txt['pf_add']);
	loadPluginTemplate('live627:post_fields', 'PostFields');
	wetem::load('edit_post_field');
	add_plugin_js_file('live627:post_fields', 'postfieldsadmin.js');
	$context['css_main_files'][] = 'postfieldsadmin';
	$context['skin_folders'][] = array($context['plugins_dir']['live627:post_fields'] . '/', 'live627:post_fields_');
	$theme['live627:post_fields_url'] = $context['plugins_dir']['live627:post_fields'];

	$request = wesql::query('
		SELECT b.id_board, b.name AS board_name, c.name AS cat_name
		FROM {db_prefix}boards AS b
			LEFT JOIN {db_prefix}categories AS c ON (c.id_cat = b.id_cat)
		WHERE redirect = {string:empty_string}',
		array(
			'empty_string' => '',
		)
	);
	$context['boards'] = array();
	while ($row = wesql::fetch_assoc($request))
		$context['boards'][$row['id_board']] = $row['cat_name'] . ' - ' . $row['board_name'];
	wesql::free_result($request);

	$request = wesql::query('
		SELECT id_group, group_name, online_color
		FROM {db_prefix}membergroups
		WHERE min_posts = {int:min_posts}
			AND id_group != {int:mod_group}
		ORDER BY group_name',
		array(
			'min_posts' => -1,
			'mod_group' => 3,
		)
	);
	$context['groups'] = array();
	while ($row = wesql::fetch_assoc($request))
		$context['groups'][$row['id_group']] = '<span' . ($row['online_color'] ? ' style="color: ' . $row['online_color'] . '"' : '') . '>' . $row['group_name'] . '</span>';
	wesql::free_result($request);

	loadLanguage('Profile');

	if ($context['fid'])
	{
		$request = wesql::query('
			SELECT *
			FROM {db_prefix}message_fields
			WHERE id_field = {int:current_field}',
			array(
				'current_field' => $context['fid'],
			)
		);
		$context['field'] = array();
		while ($row = wesql::fetch_assoc($request))
		{
			if ($row['type'] == 'textarea')
				@list ($rows, $cols) = @explode(',', $row['default_value']);
			else
			{
				$rows = 3;
				$cols = 30;
			}

			$context['field'] = array(
				'name' => $row['name'],
				'description' => $row['description'],
				'enclose' => $row['enclose'],
				'type' => $row['type'],
				'length' => $row['size'],
				'rows' => $rows,
				'cols' => $cols,
				'bbc' => $row['bbc'] == 'yes',
				'default_check' => $row['type'] == 'check' && $row['default_value'] ? true : false,
				'default_select' => $row['type'] == 'select' || $row['type'] == 'radio' ? $row['default_value'] : '',
				'options' => strlen($row['options']) > 1 ? explode(',', $row['options']) : array('', '', ''),
				'active' => $row['active'] == 'yes',
				'can_search' => $row['can_search'] == 'yes',
				'mask' => $row['mask'],
				'regex' => $row['regex'],
				'boards' => !empty($row['boards']) ? explode(',', $row['boards']) : array(),
				'groups' => !empty($row['groups']) ? explode(',', $row['groups']) : array(),
			);
		}
		wesql::free_result($request);
	}

	// Setup the default values as needed.
	if (empty($context['field']))
		$context['field'] = array(
			'name' => '',
			'description' => '',
			'enclose' => '',
			'type' => 'text',
			'length' => 255,
			'rows' => 4,
			'cols' => 30,
			'bbc' => false,
			'default_check' => false,
			'default_select' => '',
			'options' => array('', '', ''),
			'active' => true,
			'can_search' => false,
			'mask' => '',
			'regex' => '',
			'boards' => array(),
			'groups' => array(),
		);

	// Are we saving?
	if (isset($_POST['save']))
	{
		checkSession();

		if (trim($_POST['name']) == '')
			fatal_lang_error('custom_option_need_name');
		$_POST['name'] = westr::htmlspecialchars($_POST['name']);
		$_POST['description'] = westr::htmlspecialchars($_POST['description']);

		$bbc = !empty($_POST['bbc']) ? 'yes' : 'no';
		$active = !empty($_POST['active']) ? 'yes' : 'no';
		$can_search = !empty($_POST['can_search']) ? 'yes' : 'no';

		$mask = isset($_POST['mask']) ? $_POST['mask'] : '';
		$regex = isset($_POST['regex']) ? $_POST['regex'] : '';
		$length = isset($_POST['length']) ? (int) $_POST['length'] : 255;
		$groups = !empty($_POST['groups']) ? implode(',', array_keys($_POST['groups'])) : '';
		$boards = !empty($_POST['boards']) ? implode(',', array_keys($_POST['boards'])) : '';

		$options = '';
		$newOptions = array();
		$default = isset($_POST['default_check']) && $_POST['type'] == 'check' ? 1 : '';
		if (!empty($_POST['select_option']) && ($_POST['type'] == 'select' || $_POST['type'] == 'radio'))
		{
			foreach ($_POST['select_option'] as $k => $v)
			{
				$v = westr::htmlspecialchars($v);
				$v = strtr($v, array(',' => ''));

				if (trim($v) == '')
					continue;

				$newOptions[$k] = $v;

				if (isset($_POST['default_select']) && $_POST['default_select'] == $k)
					$default = $v;
			}
			$options = implode(',', $newOptions);
		}

		if ($_POST['type'] == 'textarea')
			$default = (int) $_POST['rows'] . ',' . (int) $_POST['cols'];

		$up_col = array(
			'name = {string:name}', ' description = {string:description}', ' enclose = {string:enclose}',
			'`type` = {string:type}', ' size = {int:length}',
			'options = {string:options}',
			'active = {string:active}', ' default_value = {string:default_value}',
			'can_search = {string:can_search}', ' bbc = {string:bbc}', ' mask = {string:mask}', ' regex = {string:regex}',
			'groups = {string:groups}', ' boards = {string:boards}',
		);
		$up_data = array(
			'length' => $length,
			'active' => $active,
			'can_search' => $can_search,
			'bbc' => $bbc,
			'current_field' => $context['fid'],
			'name' => $_POST['name'],
			'description' => $_POST['description'],
			'enclose' => $_POST['enclose'],
			'type' => $_POST['type'],
			'options' => $options,
			'default_value' => $default,
			'mask' => $mask,
			'regex' => $regex,
			'groups' => $groups,
			'boards' => $boards,
		);
		$in_col = array(
			'name' => 'string', 'description' => 'string', 'enclose' => 'string',
			'type' => 'string', 'size' => 'string', 'options' => 'string', 'active' => 'string', 'default_value' => 'string',
			'can_search' => 'string', 'bbc' => 'string', 'mask' => 'string', 'regex' => 'string', 'groups' => 'string', 'boards' => 'string',
		);
		$in_data = array(
			$_POST['name'], $_POST['description'], $_POST['enclose'],
			$_POST['type'], $length, $options, $active, $default,
			$can_search, $bbc, $mask, $regex, $groups, $boards,
		);
		call_hook('save_post_field', array(&$up_col, &$up_data, &$in_col, &$in_data));

		if ($context['fid'])
		{
			wesql::query('
				UPDATE {db_prefix}message_fields
				SET
					' . implode(',
					', $up_col) . '
				WHERE id_field = {int:current_field}',
				$up_data
			);
		}
		else
		{
			wesql::insert('',
				'{db_prefix}message_fields',
				$in_col,
				$in_data,
				array('id_field')
			);
		}

		// As there's currently no option to priorize certain fields over others, let's order them alphabetically.
		wesql::query('
			ALTER TABLE {db_prefix}message_fields
			ORDER BY name',
			array(
				'db_error_skip' => true,
			)
		);
		redirectexit('action=admin;area=postfields');
	}
	elseif (isset($_POST['delete']) && $context['field']['colname'])
	{
		checkSession();

		// Delete the user data first.
		wesql::query('
			DELETE FROM {db_prefix}message_data
			WHERE id_field = {int:current_field}',
			array(
				'current_field' => $context['fid'],
			)
		);
		// Finally - the field itself is gone!
		wesql::query('
			DELETE FROM {db_prefix}message_fields
			WHERE id_field = {int:current_field}',
			array(
				'current_field' => $context['fid'],
			)
		);
		call_hook('delete_post_field', array($context['fid']));
		redirectexit('action=admin;area=postfields');
	}
}

function pf_load_fields($fields)
{
	global $board, $context, $options;

	$context['fields'] = array();
	$value = '';
	$exists = false;

	if (isset($_REQUEST['msg']))
	{
		$request = wesql::query('
			SELECT *
				FROM {db_prefix}message_field_data
				WHERE id_msg = {int:msg}
					AND id_field IN ({array_int:field_list})',
				array(
					'msg' => (int) $_REQUEST['msg'],
					'field_list' => array_keys($fields),
			)
		);
		$values = array();
		while ($row = wesql::fetch_assoc($request))
			$values[$row['id_field']] = isset($row['value']) ? $row['value'] : '';
		wesql::free_result($request);
	}
	foreach ($fields as $field)
	{
		// If this was submitted already then make the value the posted version.
		if (isset($_POST['customfield'], $_POST['customfield'][$field['id_field']]))
		{
			$value = westr::htmlspecialchars($_POST['customfield'][$field['id_field']]);
			if (in_array($field['type'], array('select', 'radio')))
				$value = ($options = explode(',', $field['options'])) && isset($options[$value]) ? $options[$value] : '';
		}
		if (isset($values[$field['id_field']]))
			$value = $values[$field['id_field']];
		$exists = !empty($value);
		$context['fields'][] = rennder_field($field, $value, $exists);
	}
}

function rennder_field($field, $value, $exists)
{
	global $scripturl, $theme;

	loadPluginSource('live627:post_fields', 'Class-PostFields');
	$class_name = 'postFields_' . $field['type'];
	if (!class_exists($class_name))
		fatal_error('Param "' . $field['type'] . '" not found', false);

	$param = new $class_name($field, $value, $exists);
	$param->setHtml();
	// Parse BBCode
	if ($field['bbc'] == 'yes')
		$param->output_html = parse_bbc_inline($param->output_html);
	// Allow for newlines at least
	elseif ($field['type'] == 'textarea')
		$param->output_html = strtr($param->output_html, array("\n" => '<br>'));

	// Enclosing the user input within some other text?
	if (!empty($field['enclose']) && !empty($output_html))
	{
		$replacements = array(
			'{SCRIPTURL}' => $scripturl,
			'{IMAGES_URL}' => $theme['images_url'],
			'{DEFAULT_IMAGES_URL}' => $theme['default_images_url'],
			'{INPUT}' => $param->output_html,
		);
		call_hook('enclose_post_field', array($field['id_field'], &$field['enclose'], &$replacements));
		$param->output_html = strtr($field['enclose'], $replacements);
	}

	return array(
		'name' => $field['name'],
		'description' => $field['description'],
		'type' => $field['type'],
		'input_html' => $param->input_html,
		'output_html' => $param->getOutputHtml(),
		'id_field' => $field['id_field'],
		'value' => $value,
	);
}

function pf_post_form()
{
	global $board, $context, $options, $theme;

	pf_load_fields(get_post_fields_filtered($board));
	if (!empty($context['fields']))
	{
		loadPluginLanguage('live627:post_fields', 'PostFields');
		loadPluginTemplate('live627:post_fields', 'PostFields');
		$context['main_css_files']['postfields'] = false;
		$context['skin_folders'][] = array($context['plugins_dir']['live627:post_fields'] . '/', 'live627:post_fields_');
		$theme['live627:post_fields_url'] = $context['plugins_dir']['live627:post_fields'];
		wetem::after('post_additional_options', 'input_post_fields');
		add_plugin_js_file('live627:post_fields', 'postfields.js');
		$context['is_post_fields_collapsed'] = we::$is_guest ? !empty($_COOKIE['postFields']) : !empty($options['postFields']);
	}
}

function pf_after(&$msgOptions)
{
	global $board, $context, $theme;

	$field_list = get_post_fields_filtered($board);
	$changes = array();
	$log_changes = array();
	foreach ($field_list as $field)
	{
		$value = isset($_POST['customfield'][$field['id_field']]) ? $_POST['customfield'][$field['id_field']] : '';
		$class_name = 'postFields_' . $field['type'];
		if (!class_exists($class_name))
			fatal_error('Param "' . $field['type'] . '" not found', false);

		$type = new $class_name($field, $value, !empty($value));
		$changes[] = array($field['id_field'], $type->getValue(), $msgOptions['id']);
	}

	if (!empty($changes))
		wesql::insert('replace',
			'{db_prefix}message_field_data',
			array('id_field' => 'string-255', 'value' => 'string', 'id_msg' => 'int'),
			$changes,
			array('id_msg_field', 'id_field', 'id_msg')
		);
}

function pf_post_post_validate(&$post_errors, &$posterIsGuest)
{
	global $board, $context, $theme;

	if (isset($_POST['customfield']))
		$_POST['customfield'] = htmlspecialchars__recursive($_POST['customfield']);

	$field_list = get_post_fields_filtered($board);
	loadPluginSource('live627:post_fields', 'Class-PostFields');
	loadPluginLanguage('live627:post_fields', 'PostFields');
			var_dump($err);
	foreach ($field_list as $field)
	{
		$value = isset($_POST['customfield'][$field['id_field']]) ? $_POST['customfield'][$field['id_field']] : '';
		$class_name = 'postFields_' . $field['type'];
		if (!class_exists($class_name))
			fatal_error('Param "' . $field['type'] . '" not found', false);

		$type = new $class_name($field, $value, !empty($value));
		$type->validate();
		if (false !== ($err = $type->getError()))
			$post_errors[] = $err;
	}
}

function pf_display_message_list(&$messages, &$times, &$all_posters)
{
	global $board, $context, $options;

	$field_list = get_post_fields_filtered($board);

	if (empty($field))
		return;

	$request = wesql::query('
		SELECT *
			FROM {db_prefix}message_field_data
			WHERE id_msg IN ({array_int:message_list})
				AND id_field IN ({array_int:field_list})',
			array(
				'message_list' => $messages,
				'field_list' => array_keys($field_list),
		)
	);
	$context['fields'] = array();
	while ($row = wesql::fetch_assoc($request))
	{
		$exists = isset($row['value']);
		$value = $exists ? $row['value'] : '';

		$context['fields'][$row['id_msg']][] = rennder_field($field_list[$row['id_field']], $value, $exists);
	}
	wesql::free_result($request);
	if (!empty($context['fields']))
	{
		loadPluginLanguage('live627:post_fields', 'PostFields');
		loadPluginTemplate('live627:post_fields', 'PostFields');
	}
}

function pf_display_post_done(&$counter, &$output)
{
	global $context;

	if (!empty($context['fields'][$output['id']]))
	{
		$output['body'] .= '
						<br />
						<br />
						<hr />
						<dl class="settings">';

		foreach ($context['fields'][$output['id']] as $field)
			$output['body'] .= '
							<dt>
								<strong>' . $field['name'] . ': </strong><br />
							</dt>
							<dd>
								' . $field['output_html'] . '
							</dd>';

		$output['body'] .= '
						</dl>';
	}
}

?>