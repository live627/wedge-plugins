<?php

function pf_admin_areas(&$admin_areas)
{
	global $txt;

	loadAddonLanguage('live627:post_fields', 'PostFields');
	$admin_areas['addons']['areas']['modsettings']['subsections']['postfields'] = array($txt['post_fields']);
}

function pf_modify_modifications(&$sub_actions)
{
	$sub_actions['postfields'] = 'ListPostFields';
	$sub_actions['postfieldedit'] = 'EditPostField';
}

function ListPostFields()
{
	global $txt, $scripturl, $context, $settings, $modSettings;

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
		redirectexit('action=admin;area=modsettings;sa=postfields');
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

			$searchable = !empty($_POST['searchable'][$field['id_field']]) ? 'yes' : 'no';
			if ($searchable != $field['searchable'])
				wesql::query('
					UPDATE {db_prefix}message_fields
					SET searchable = {string:searchable}
					WHERE id_field = {int:field}',
					array(
						'searchable' => $searchable,
						'field' => $field['id_field'],
					)
				);

		}
		redirectexit('action=admin;area=modsettings;sa=postfields');
	}

	// New field?
	if (isset($_POST['new']))
		redirectexit('action=admin;area=modsettings;sa=postfieldedit');

	$listOptions = array(
		'id' => 'pf_fields',
		'base_href' => $scripturl . '?action=action=admin;area=modsettings;sa=postfields',
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

						return sprintf(\'<a href="%1$s?action=admin;area=modsettings;sa=postfieldedit;fid=%2$d">%3$s</a><div class="smalltext">%4$s</div>\', $scripturl, $rowData[\'id_field\'], $rowData[\'name\'], $rowData[\'description\']);
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
			'searchable' => array(
				'header' => array(
					'value' => $txt['pf_can_search'],
				),
				'data' => array(
					'function' => create_function('$rowData', '
						global $txt;
						$isChecked = $rowData[\'searchable\'] == \'no\' ? \'\' : \' checked\';
						return sprintf(\'<span id="searchable_%1$s" class="color_%4$s">%3$s</span>&nbsp;<input type="checkbox" name="searchable[%1$s]" id="searchable_%1$s" value="%1$s"%2$s>\', $rowData[\'id_field\'], $isChecked, $txt[$rowData[\'searchable\']], $rowData[\'searchable\']);
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
			'href' => $scripturl . '?action=admin;area=modsettings;sa=postfields',
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
	loadBlock('show_list');
	$context['default_list'] = 'pf_fields';
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

function list_getPostFields($start, $items_per_page, $sort)
{
	$list = array();
	$request = wesql::query('
		SELECT id_field, name, description, type, bbc, active, searchable
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

	return $list;
}

function get_post_fields_filtered($board)
{
	global $user_info;

	$fields = total_getPostFields();
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

		$list[$field['id_field']] = $field;
	}

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
	global $txt, $scripturl, $context, $settings;

	$context['fid'] = isset($_REQUEST['fid']) ? (int) $_REQUEST['fid'] : 0;
	$context[$context['admin_menu_name']]['current_subsection'] = 'postfields';
	$context['page_title'] .= ' - ' . $txt['post_fields'] . ' - ' . ($context['fid'] ? $txt['pf_title'] : $txt['pf_add']);
	$context['page_title2'] = $txt['post_fields'] . ' - ' . ($context['fid'] ? $txt['pf_title'] : $txt['pf_add']);
	loadAddonTemplate('live627:post_fields', 'PostFields');
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
		$searchable = !empty($_POST['searchable']) ? 'yes' : 'no';

		$regex = isset($_POST['regex']) ? $_POST['regex'] : '';
		$length = isset($_POST['length']) ? (int) $_POST['length'] : 255;
		$groups = !empty($_POST['groups']) ? implode(',', array_keys($_POST['groups'])) : array();
		$boards = !empty($_POST['boards']) ? implode(',', array_keys($_POST['boards'])) : array();

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

		if ($context['fid'])
		{
			wesql::query('
				UPDATE {db_prefix}message_fields
				SET
					name = {string:name}, description = {string:description},
					type = {string:type}, size = {int:length},
					options = {string:options},
					active = {string:active}, default_value = {string:default_value},
					searchable = {string:searchable}, bbc = {string:bbc}, regex = {string:regex},
					groups = {string:groups}, boards = {string:boards}
				WHERE id_field = {int:current_field}',
				array(
					'length' => $length,
					'active' => $active,
					'searchable' => $searchable,
					'bbc' => $bbc,
					'current_field' => $context['fid'],
					'name' => $_POST['name'],
					'description' => $_POST['description'],
					'type' => $_POST['type'],
					'options' => $options,
					'default_value' => $default,
					'regex' => $regex,
					'groups' => $groups,
					'boards' => $boards,
				)
			);
		}
		else
		{
			wesql::insert('',
				'{db_prefix}message_fields',
				array(
					'name' => 'string', 'description' => 'string',
					'type' => 'string', 'size' => 'string', 'options' => 'string', 'active' => 'string', 'default_value' => 'string',
					'searchable' => 'string', 'bbc' => 'string', 'regex' => 'string', 'groups' => 'string', 'boards' => 'string',
				),
				array(
					$_POST['name'], $_POST['description'],
					$_POST['type'], $length, $options, $active, $default,
					$searchable, $bbc, $regex, $groups, $boards,
				),
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
		redirectexit('action=admin;area=modsettings;sa=postfields');
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
		redirectexit('action=admin;area=modsettings;sa=postfields');
	}
}

function pf_load_fields()
{
	global $board, $context, $options, $user_info;

	$fields = total_getPostFields();
	$context['fields'] = array();
	$value = '';
	$exists = false;

	foreach ($fields as $field)
	{
		$board_list = array_flip(explode(',', $field['boards']));
		if (!isset($board_list[$board]))
			continue;

		$group_list = explode(',', $field['groups']);
		$is_allowed = array_intersect($user_info['groups'], $group_list);
		if (empty($is_allowed))
			continue;

		// If this was submitted already then make the value the posted version.
		if (isset($_POST['customfield'], $_POST['customfield'][$field['id_field']]))
		{
			$value = westr::htmlspecialchars($_POST['customfield'][$field['id_field']]);
			$exists = !empty($value);
			if (in_array($field['type'], array('select', 'radio')))
				$value = ($options = explode(',', $field['options'])) && isset($options[$value]) ? $options[$value] : '';
		}

		// HTML for the input form.
		$output_html = $value;
		if ($field['type'] == 'check')
		{
			$true = (!$exists && $field['default_value']) || $value;
			$input_html = '<input type="checkbox" name="customfield[' . $field['id_field'] . ']"' . ($true ? ' checked' : '') . '>';
			$output_html = $true ? $txt['yes'] : $txt['no'];
		}
		elseif ($field['type'] == 'select')
		{
			$input_html = '<select name="customfield[' . $field['id_field'] . ']"><option value="-1"></option>';
			$foptions = explode(',', $field['options']);
			foreach ($foptions as $k => $v)
			{
				$true = (!$exists && $field['default_value'] == $v) || $value == $v;
				$input_html .= '<option value="' . $k . '"' . ($true ? ' selected' : '') . '>' . $v . '</option>';
				if ($true)
					$output_html = $v;
			}

			$input_html .= '</select>';
		}
		elseif ($field['type'] == 'radio')
		{
			$input_html = '<fieldset>';
			$foptions = explode(',', $field['options']);
			foreach ($foptions as $k => $v)
			{
				$true = (!$exists && $field['default_value'] == $v) || $value == $v;
				$input_html .= '<label><input type="radio" name="customfield[' . $field['id_field'] . ']" value="' . $k . '"' . ($true ? ' checked' : '') . '> ' . $v . '</label><br>';
				if ($true)
					$output_html = $v;
			}
			$input_html .= '</fieldset>';
		}
		elseif ($field['type'] == 'text')
		{
			$input_html = '<input type="text" name="customfield[' . $field['id_field'] . ']" ' . ($field['size'] != 0 ? 'maxsize="' . $field['size'] . '"' : '') . ' size="' . ($field['size'] == 0 || $field['size'] >= 50 ? 50 : ($field['size'] > 30 ? 30 : ($field['size'] > 10 ? 20 : 10))) . '" value="' . $value . '">';
		}
		else
		{
			@list ($fields, $cols) = @explode(',', $field['default_value']);
			$input_html = '<textarea name="customfield[' . $field['id_field'] . ']" ' . (!empty($fields) ? 'fields="' . $fields . '"' : '') . ' ' . (!empty($cols) ? 'cols="' . $cols . '"' : '') . '>' . $value . '</textarea>';
		}

		// Parse BBCode
		if ($field['bbc'])
			$output_html = parse_bbc($output_html);
		// Allow for newlines at least
		elseif ($field['type'] == 'textarea')
			$output_html = strtr($output_html, array("\n" => '<br>'));

		// Enclosing the user input within some other text?
		if (!empty($field['enclose']) && !empty($output_html))
			$output_html = strtr($field['enclose'], array(
				'{SCRIPTURL}' => $scripturl,
				'{IMAGES_URL}' => $settings['images_url'],
				'{DEFAULT_IMAGES_URL}' => $settings['default_images_url'],
				'{INPUT}' => $output_html,
			));

		$context['fields'][] = array(
			'name' => $field['name'],
			'description' => $field['description'],
			'type' => $field['type'],
			'input_html' => $input_html,
			'output_html' => $output_html,
			'id_field' => $field['id_field'],
			'value' => $value,
		);
	}
}

function pf_post()
{
	global $board, $context, $options, $user_info;

	pf_load_fields();
	if (!empty($context['fields']))
	{
		loadAddonLanguage('live627:post_fields', 'PostFields');
		loadAddonTemplate('live627:post_fields', 'PostFields');
		//loadBlock('input_post_fields', '', 'after');
		$context['is_topic_fields_collapsed'] = $user_info['is_guest'] ? !empty($_COOKIE['topicFields']) : !empty($options['topicFields']);
	}
}

function pf_after(&$msgOptions)
{
	global $board, $context, $user_info, $modSettings;

	if (isset($_POST['customfield']))
		$_POST['customfield'] = htmlspecialchars__recursive($_POST['customfield']);

	$fields = total_getPostFields();
	$changes = array();
	$log_changes = array();
	foreach ($fields as $field)
	{
		$board_list = array_flip(explode(',', $field['boards']));
		if (!isset($board_list[$board]))
			continue;

		$group_list = explode(',', $field['groups']);
		$is_allowed = array_intersect($user_info['groups'], $group_list);
		if (empty($is_allowed))
			continue;

		// Validate the user data.
		if ($field['type'] == 'check')
			$value = isset($_POST['customfield'][$field['id_field']]) ? 1 : 0;
		elseif ($field['type'] == 'select' || $field['type'] == 'radio')
		{
			$value = $field['default_value'];
			foreach (explode(',', $field['options']) as $k => $v)
				if (isset($_POST['customfield'][$field['id_field']]) && $_POST['customfield'][$field['id_field']] == $k)
					$value = $v;
		}
		// Otherwise some form of text!
		else
		{
			$value = isset($_POST['customfield'][$field['id_field']]) ? $_POST['customfield'][$field['id_field']] : '';
			if ($field['length'])
				$value = westr::substr($value, 0, $field['length']);

			// Any masks?
			if ($field['type'] == 'text' && !empty($field['mask']) && $field['mask'] != 'none')
			{
				//!!! We never error on this - just ignore it at the moment...
				if ($field['mask'] == 'email' && (!is_valid_email($value) || strlen($value) > 255))
					$value = '';
				elseif ($field['mask'] == 'number')
				{
					$value = (int) $value;
				}
				elseif (substr($field['mask'], 0, 5) == 'regex' && preg_match(substr($field['mask'], 5), $value) === 0)
					$value = '';
			}
		}
		$changes[] = array($field['id_field'], $value, $msgOptions['id']);
	}

	if (!empty($changes))
		wesql::insert('replace',
			'{db_prefix}message_field_data',
			array('id_field' => 'string-255', 'value' => 'string', 'id_msg' => 'int'),
			$changes,
			array('id_msg_field', 'id_field', 'id_msg')
		);
}

function pf_pre_load_posts(&$messages)
{
	global $board, $context, $options, $user_info;

	$field_list = get_post_fields_filtered($board);
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
		// Shortcut.
		$exists = isset($row['value']);
		$value = $exists ? $row['value'] : '';

		// HTML for the input form.
		$output_html = $value;
		if ($field_list[$row['id_field']]['type'] == 'check')
		{
			$true = (!$exists && $field_list[$row['id_field']]['default_value']) || $value;
			$input_html = '<input type="checkbox" name="customfield[' . $row['id_field'] . ']"' . ($true ? ' checked' : '') . '>';
			$output_html = $true ? $txt['yes'] : $txt['no'];
		}
		elseif ($field_list[$row['id_field']]['type'] == 'select')
		{
			$input_html = '<select name="customfield[' . $row['id_field'] . ']"><option value="-1"></option>';
			$options = explode(',', $field_list[$row['id_field']]['options']);
			foreach ($options as $k => $v)
			{
				$true = (!$exists && $field_list[$row['id_field']]['default_value'] == $v) || $value == $v;
				$input_html .= '<option value="' . $k . '"' . ($true ? ' selected' : '') . '>' . $v . '</option>';
				if ($true)
					$output_html = $v;
			}

			$input_html .= '</select>';
		}
		elseif ($field_list[$row['id_field']]['type'] == 'radio')
		{
			$input_html = '<fieldset>';
			$options = explode(',', $field_list[$row['id_field']]['options']);
			foreach ($options as $k => $v)
			{
				$true = (!$exists && $field_list[$row['id_field']]['default_value'] == $v) || $value == $v;
				$input_html .= '<label><input type="radio" name="customfield[' . $row['id_field'] . ']" value="' . $k . '"' . ($true ? ' checked' : '') . '> ' . $v . '</label><br>';
				if ($true)
					$output_html = $v;
			}
			$input_html .= '</fieldset>';
		}
		elseif ($field_list[$row['id_field']]['type'] == 'text')
		{
			$input_html = '<input type="text" name="customfield[' . $row['id_field'] . ']" ' . ($field_list[$row['id_field']]['size'] != 0 ? 'maxsize="' . $field_list[$row['id_field']]['size'] . '"' : '') . ' size="' . ($field_list[$row['id_field']]['size'] == 0 || $field_list[$row['id_field']]['size'] >= 50 ? 50 : ($field_list[$row['id_field']]['size'] > 30 ? 30 : ($field_list[$row['id_field']]['size'] > 10 ? 20 : 10))) . '" value="' . $value . '">';
		}
		else
		{
			@list ($rows, $cols) = @explode(',', $field_list[$row['id_field']]['default_value']);
			$input_html = '<textarea name="customfield[' . $row['id_field'] . ']" ' . (!empty($rows) ? 'rows="' . $rows . '"' : '') . ' ' . (!empty($cols) ? 'cols="' . $cols . '"' : '') . '>' . $value . '</textarea>';
		}

		// Parse BBCode
		if ($field_list[$row['id_field']]['bbc'])
			$output_html = parse_bbc($output_html);
		// Allow for newlines at least
		elseif ($field_list[$row['id_field']]['type'] == 'textarea')
			$output_html = strtr($output_html, array("\n" => '<br>'));

		// Enclosing the user input within some other text?
		if (!empty($field_list[$row['id_field']]['enclose']) && !empty($output_html))
			$output_html = strtr($field_list[$row['id_field']]['enclose'], array(
				'{SCRIPTURL}' => $scripturl,
				'{IMAGES_URL}' => $settings['images_url'],
				'{DEFAULT_IMAGES_URL}' => $settings['default_images_url'],
				'{INPUT}' => $output_html,
			));

		$context['fields'][$row['id_msg']][] = array(
			'name' => $field_list[$row['id_field']]['name'],
			'description' => $field_list[$row['id_field']]['description'],
			'type' => $field_list[$row['id_field']]['type'],
			'input_html' => $input_html,
			'output_html' => $output_html,
			'id_field' => $row['id_field'],
			'value' => $value,
		);
	}
	wesql::free_result($request);
	if (!empty($context['fields']))
	{
		loadAddonLanguage('live627:post_fields', 'PostFields');
		loadAddonTemplate('live627:post_fields', 'PostFields');
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
								' . $field['value'] . '
							</dd>';

		$output['body'] .= '
						</dl>';
	}
}

?>