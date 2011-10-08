<?php

function pages_admin_areas(&$admin_areas)
{
	global $txt;

	loadAddonLanguage('live627:post_fields', 'Pages');
	$admin_areas['addons']['areas']['modsettings']['subsections']['pages'] = array($txt['post_fields']);
}

function pages_modify_modifications(&$sub_actions)
{
	$sub_actions['pages'] = 'ListPages';
	$sub_actions['postfieldedit'] = 'EditPostField';
}

function ListPages()
{
	global $txt, $scripturl, $context, $settings, $modSettings;

	// Deleting?
	if (isset($_POST['delete'], $_POST['remove']))
	{
		checkSession();

		// Delete the user data first.
		wesql::query('
			DELETE FROM {db_prefix}message_data
			WHERE id_page IN ({array_int:fields})',
			array(
				'fields' => $_POST['remove'],
			)
		);
		// Finally - the fields themselves are gone!
		wesql::query('
			DELETE FROM {db_prefix}pages
			WHERE id_page IN ({array_int:fields})',
			array(
				'fields' => $_POST['remove'],
			)
		);
		redirectexit('action=admin;area=modsettings;sa=pages');
	}

	// Changing the status?
	if (isset($_POST['save']))
	{
		checkSession();
		foreach (total_getPages() as $field)
		{
			$bbc = !empty($_POST['bbc'][$field['id_page']]) ? 'yes' : 'no';
			if ($bbc != $field['bbc'])
				wesql::query('
					UPDATE {db_prefix}pages
					SET bbc = {string:bbc}
					WHERE id_page = {int:field}',
					array(
						'bbc' => $bbc,
						'field' => $field['id_page'],
					)
				);

			$active = !empty($_POST['active'][$field['id_page']]) ? 'yes' : 'no';
			if ($active != $field['active'])
				wesql::query('
					UPDATE {db_prefix}pages
					SET active = {string:active}
					WHERE id_page = {int:field}',
					array(
						'active' => $active,
						'field' => $field['id_page'],
					)
				);

			$searchable = !empty($_POST['searchable'][$field['id_page']]) ? 'yes' : 'no';
			if ($searchable != $field['searchable'])
				wesql::query('
					UPDATE {db_prefix}pages
					SET searchable = {string:searchable}
					WHERE id_page = {int:field}',
					array(
						'searchable' => $searchable,
						'field' => $field['id_page'],
					)
				);

		}
		redirectexit('action=admin;area=modsettings;sa=pages');
	}

	// New field?
	if (isset($_POST['new']))
		redirectexit('action=admin;area=modsettings;sa=postfieldedit');

	$listOptions = array(
		'id' => 'pages_fields',
		'base_href' => $scripturl . '?action=action=admin;area=modsettings;sa=pages',
		'default_sort_col' => 'name',
		'no_items_label' => $txt['pages_none'],
		'items_per_page' => 25,
		'get_items' => array(
			'function' => 'list_getPages',
		),
		'get_count' => array(
			'function' => 'list_getPostFieldSize',
		),
		'columns' => array(
			'name' => array(
				'header' => array(
					'value' => $txt['pages_fieldname'],
					'style' => 'text-align: left;',
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $scripturl;

						return sprintf(\'<a href="%1$s?action=admin;area=modsettings;sa=postfieldedit;fid=%2$d">%3$s</a><div class="smalltext">%4$s</div>\', $scripturl, $rowData[\'id_page\'], $rowData[\'name\'], $rowData[\'description\']);
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
					'value' => $txt['pages_fieldtype'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;

						$textKey = sprintf(\'pages_type_%1$s\', $rowData[\'type\']);
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
					'value' => $txt['pages_bbc'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'bbc\'] == \'no\' ? \'\' : \' checked\';
						return sprintf(\'<span id="bbc_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="bbc[%1$s]" id="bbc_%1$s" value="%1$s"%2$s>\', $rowData[\'id_page\'], $isChecked, $txt[$rowData[\'bbc\']], $rowData[\'bbc\']);
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
					'value' => $txt['pages_active'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'active\'] == \'no\' ? \'\' : \' checked\';
						return sprintf(\'<span id="active_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="active[%1$s]" id="active_%1$s" value="%1$s"%2$s>\', $rowData[\'id_page\'], $isChecked, $txt[$rowData[\'active\']], $rowData[\'active\']);
					'),
					'style' => 'width: 10%; text-align: center;',
				),
				'sort' => array(
					'default' => 'active DESC',
					'reverse' => 'active',
				),
			),
			'searchable' => array(
				'header' => array(
					'value' => $txt['pages_can_search'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'searchable\'] == \'no\' ? \'\' : \' checked\';
						return sprintf(\'<span id="searchable_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="searchable[%1$s]" id="searchable_%1$s" value="%1$s"%2$s>\', $rowData[\'id_page\'], $isChecked, $txt[$rowData[\'searchable\']], $rowData[\'searchable\']);
					'),
					'style' => 'width: 10%; text-align: center;',
				),
				'sort' => array(
					'default' => 'searchable DESC',
					'reverse' => 'searchable',
				),
			),
			'modify' => array(
				'header' => array(
					'value' => $txt['modify'],
				),
				'data' => array(
					'sprintf' => array(
						'format' => '<a href="' . $scripturl . '?action=admin;area=modsettings;sa=postfieldedit;fid=%1$s">' . $txt['modify'] . '</a>',
						'params' => array(
							'id_page' => false,
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
						return sprintf(\'<span id="remove_%1$s" class="color_no">%2$s</span>&nbsp;<input type="checkbox" name="remove[%1$s]" id="remove_%1$s" value="%1$s">\', $rowData[\'id_page\'], $txt[\'no\']);
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
			'href' => $scripturl . '?action=admin;area=modsettings;sa=pages',
			'name' => 'customProfileFields',
		),
		'additional_rows' => array(
			array(
				'position' => 'below_table_data',
				'value' => '<input type="submit" name="save" value="' . $txt['save'] . '" class="submit">&nbsp;&nbsp;<input type="submit" name="delete" value="' . $txt['delete'] . '" onclick="return confirm(' . JavaScriptEscape($txt['pages_delete_sure']) . ');" class="delete">&nbsp;&nbsp;<input type="submit" name="new" value="' . $txt['pages_make_new'] . '" class="new">',
				'style' => 'text-align: right;',
			),
		),
	);
	loadSource('Subs-List');
	createList($listOptions);
	loadBlock('show_list');
	$context['default_list'] = 'pages_fields';
	$context['header'] .= '
	<style>
		.color_yes
		{
			color: green;
		}
		.color_no
		{
			color: red;
		}
	</style>';
}

function list_getPages($start, $items_per_page, $sort)
{
	$list = array();
	$request = wesql::query('
		SELECT id_page, name, description, type, bbc, active, searchable
		FROM {db_prefix}pages
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

function total_getPages()
{
	$list = array();
	$request = wesql::query('
		SELECT *
		FROM {db_prefix}pages');
	while ($row = wesql::fetch_assoc($request))
		$list[] = $row;
	wesql::free_result($request);

	return $list;
}

function get_post_fields_filtered($board)
{
	global $user_info;

	$fields = total_getPages();
	$list = array();
	foreach ($fields as $field)
	{
		$board_list = array_flip(explode(',', $field['boards']));
		if (!isset($board_list[$board]))
			continue;

		$group_list = explode(',', $field['groups']);
		$is_allowed = array_intersect($user_info['groups'], $group_list);
		if (empty($is_allowed))
			continue;

		$list[$field['id_page']] = $field;
	}

	return $list;
}

function list_getPostFieldSize()
{
	$request = wesql::query('
		SELECT COUNT(*)
		FROM {db_prefix}pages');

	list ($numProfileFields) = wesql::fetch_row($request);
	wesql::free_result($request);

	return $numProfileFields;
}

function EditPostField()
{
	global $txt, $scripturl, $context, $settings;

	$context['fid'] = isset($_REQUEST['fid']) ? (int) $_REQUEST['fid'] : 0;
	$context[$context['admin_menu_name']]['current_subsection'] = 'pages';
	$context['page_title'] .= ' - ' . $txt['post_fields'] . ' - ' . ($context['fid'] ? $txt['pages_title'] : $txt['pages_add']);
	$context['page_title2'] = $txt['post_fields'] . ' - ' . ($context['fid'] ? $txt['pages_title'] : $txt['pages_add']);
	loadAddonTemplate('live627:post_fields', 'Pages');
	loadBlock('edit_post_field');

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
			AND online_color != {string:blank_string}
		ORDER BY group_name',
		array(
			'min_posts' => -1,
			'mod_group' => 3,
			'blank_string' => '',
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
			FROM {db_prefix}pages
			WHERE id_page = {int:current_field}',
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
				'type' => $row['type'],
				'length' => $row['size'],
				'rows' => $rows,
				'cols' => $cols,
				'bbc' => $row['bbc'] == 'yes',
				'default_check' => $row['type'] == 'check' && $row['default_value'] ? true : false,
				'default_select' => $row['type'] == 'select' || $row['type'] == 'radio' ? $row['default_value'] : '',
				'options' => strlen($row['options']) > 1 ? explode(',', $row['options']) : array('', '', ''),
				'active' => $row['active'] == 'yes',
				'searchable' => $row['searchable'] == 'yes',
				'content' => $row['content'],
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
			'type' => 'text',
			'length' => 255,
			'rows' => 4,
			'cols' => 30,
			'bbc' => false,
			'default_check' => false,
			'default_select' => '',
			'options' => array('', '', ''),
			'active' => true,
			'searchable' => false,
			'content' => '',
			'boards' => array(),
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
		$searchable = !empty($_POST['searchable']) ? 'yes' : 'no';
		$groups = !empty($_POST['groups']) ? implode(',', array_keys($_POST['groups'])) : '';


		if ($context['fid'])
		{
			wesql::query('
				UPDATE {db_prefix}pages
				SET
					name = {string:name}, description = {string:description},
					type = {string:type}, active = {string:active},
					searchable = {string:searchable}, bbc = {string:bbc}, content = {string:content},
					groups = {string:groups}
				WHERE id_page = {int:current_field}',
				array(
					'active' => $active,
					'searchable' => $searchable,
					'bbc' => $bbc,
					'current_field' => $context['fid'],
					'name' => $_POST['name'],
					'content' => $_POST['content'],
					'description' => $_POST['description'],
					'type' => $_POST['type'],
					'groups' => $groups,
				)
			);
		}
		else
		{
			wesql::insert('',
				'{db_prefix}pages',
				array(
					'name' => 'string', 'description' => 'string',
					'type' => 'string', 'content' => 'string', 'active' => 'string', 'default_value' => 'string',
					'searchable' => 'string', 'bbc' => 'string', 'groups' => 'string',
				),
				array(
					$_POST['name'], $_POST['description'],
					$_POST['type'], $_POST['content'], $active, $default,
					$searchable, $bbc, $groups,
				),
				array('id_page')
			);
		}

		// As there's currently no option to priorize certain fields over others, let's order them alphabetically.
		wesql::query('
			ALTER TABLE {db_prefix}pages
			ORDER BY name',
			array(
				'db_error_skip' => true,
			)
		);
		redirectexit('action=admin;area=modsettings;sa=pages');
	}
	elseif (isset($_POST['delete']) && $context['field']['colname'])
	{
		checkSession();

		wesql::query('
			DELETE FROM {db_prefix}pages
			WHERE id_page = {int:current_field}',
			array(
				'current_field' => $context['fid'],
			)
		);
		redirectexit('action=admin;area=modsettings;sa=pages');
	}
}

function pages_default_action()
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
								' . $field['value'] . '
							</dd>';

		$output['body'] .= '
						</dl>';
	}
}

?>