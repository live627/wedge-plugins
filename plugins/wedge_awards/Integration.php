<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function awards_admin_areas()
{
	global $admin_areas, $context, $txt;

	loadPluginLanguage('live627:awards', 'Awards');
	$admin_areas['layout']['areas'] += array(
		'awards' => array(
			'label' => $txt['awards'],
			'file' => array('live627:awards', 'ManageAwards'),
			'function' => 'Awards',
			'icon' => $context['plugins_url']['live627:awards'] . '/awards.gif',
			'bigicon' => $context['plugins_url']['live627:awards'] . '/bigawards.gif',
			'permission' => array('manage_awards'),
			'subsections' => array(
				'main' => array($txt['awards_main']),
				'modify' => array($txt['awards_modify']),
				'assign' => array($txt['awards_assign']),
				'categories' => array($txt['awards_categories']),
				'settings' => array($txt['awards_settings']),
			),
		),
	);
}

function awards_after(&$msgOptions)
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

function awards_pre_load_posts(&$messages)
{
	global $board, $context, $options, $user_info;

	$field_list = get_awards_filtered($board);
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
		loadPluginLanguage('live627:awards', 'PostFields');
		loadPluginTemplate('live627:awards', 'PostFields');
	}
}

function awards_display_post_done(&$counter, &$output)
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