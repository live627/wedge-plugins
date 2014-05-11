<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function hooks_admin_areas()
{
	global $admin_areas, $context, $txt;

	loadPluginLanguage('live627:show_hooks', 'ShowHooks');
	$admin_areas['plugins']['areas']['hooks'] = array(
		'label' => $txt['hooks_title_list'],
		'icon' => $context['plugins_url']['live627:show_hooks'] . '/hook_small.png',
		'bigicon' => $context['plugins_url']['live627:show_hooks'] . '/hook_large.png',
		'function' => 'ListHooks',
	);
}

function ListHooks()
{
	global $context, $theme, $settings, $txt;

	populate_plugin_choices();
	$plugins = get_hooks();
	$context['filter_enabled_plugins'] = array();
	$context['enabled_plugins']['show_hooks:unknown'] = 'show_hooks:unknown';
	foreach ($plugins as $plugin_id => $hooks)
	{
		$enabled_plugin_name = isset($context['enabled_plugins'][$plugin_id]) ? $context['enabled_plugins'][$plugin_id] :  $plugin_id;
		if (isset($context['plugins_dir'][$plugin_id]))
		{
			if (filetype($context['plugins_dir'][$plugin_id]) == 'dir' && file_exists($context['plugins_dir'][$plugin_id] . '/plugin-info.xml'))
				$plugin = load_plugin_details($plugin_id);
		}
		elseif ($plugin_id === 'show_hooks:unknown')
			$plugin = array(
				'name' => 'Isolated hooks',
				'description' => 'These hooks are not a part of a plugin.',
			);
		elseif ($plugin_id === 'all')
			$plugin = array(
				'name' => 'All hooks',
				'description' => 'Showing all hooks added either by plugins or custom code.',
			);
		else
			$plugin = array(
				'name' => $plugin_id,
				'description' => $plugin_id,
			);

		$context['plugin_details'][$enabled_plugin_name] = $plugin;

		if (isset($_REQUEST['select_hooks'], $settings['plugin_' . $_REQUEST['select_hooks']]) && $_REQUEST['select_hooks'] === $enabled_plugin_name || !isset($_REQUEST['select_hooks']) || isset($_REQUEST['select_hooks']) && ($_REQUEST['select_hooks'] === 'show_hooks:unknown' && $_REQUEST['select_hooks'] === $enabled_plugin_name || $_REQUEST['select_hooks'] === 'all_cat' || $_REQUEST['select_hooks'] === 'all' || $_REQUEST['select_hooks'] === 'show_hooks:hooks'))
		{
			$list_options = array(
				'id' => 'list_hooks_' . $enabled_plugin_name,
				'items_per_page' => 50,
				'base_href' => '<URL>?action=admin;area=hooks;' . $context['session_var'] . '=' . $context['session_id'],
				'default_sort_col' => 'hook_name',
				'get_items' => array(
					'function' => 'get_hooks_data',
					'params' => array($plugin_id),
				),
				'get_count' => array(
					'function' => 'get_hooks_count',
				),
				'no_items_label' => $txt['hooks_no_hooks'],
				'columns' => array(
					'hook_name' => array(
						'header' => array(
							'value' => $txt['hooks_field_hook_name'],
						),
						'data' => array(
							'db' => 'hook_name',
						),
						'sort' =>  array(
							'default' => 'hook_name',
							'reverse' => 'hook_name DESC',
						),
					),
					'function_name' => array(
						'header' => array(
							'value' => $txt['hooks_field_function_name'],
						),
						'data' => array(
							'db' => 'function_name',
						),
						'sort' =>  array(
							'default' => 'function_name',
							'reverse' => 'function_name DESC',
						),
					),
					'hook_exists' => array(
						'header' => array(
							'value' => $txt['hooks_field_hook_exists'],
						),
						'data' => array(
							'function' => create_function('$rowData', '
								global $txt;
								return sprintf(\'<span id="hook_exists_%1$s" class="color_%3$s">%2$s</span>\', $rowData[\'hook_name\'], $rowData[\'hook_exists\'] ? $txt[\'yes\'] : $txt[\'no\'], $rowData[\'hook_exists\'] ? \'yes\' : \'no\');
							'),
							'class' => 'centertext',
						),
						'sort' =>  array(
							'default' => 'hook_exists',
							'reverse' => 'hook_exists DESC',
						),
					),
					'priority' => array(
						'header' => array(
							'value' => $txt['hooks_field_priority'],
						),
						'data' => array(
							'db' => 'priority',
							'class' => 'centertext',
						),
						'sort' =>  array(
							'default' => 'priority',
							'reverse' => 'priority DESC',
						),
					),
					'check' => array(
						'header' => array(
							'value' => '<input type="checkbox" onclick="invertAll(this, this.form);" class="input_check" />',
						),
						'data' => array(
							'function' => create_function('$data', '
								return \'<input type="checkbox" name="remove[\' . $data[\'hook_name\'] . \'][]" value="\' . $data[\'function_name\'] . \'"\' . ($data[\'hook_exists\'] ? \' disabled="disabled"\' : \'\') . \'  class="input_check" />\';
							'),
							'class' => 'centertext',
						),
					),
				),
				'form' => array(
					'href' => '<URL>?action=admin;area=hooks;' . $context['session_var'] . '=' . $context['session_id'],
				),
				'additional_rows' => array(
					array(
						'position' => 'below_table_data',
						'value' => '<input type="submit" name="remove_hooks" value="' . $txt['hooks_button_remove'] . '" class="delete" />',
						'class' => 'righttext',
					),
				),
			);
			$context['filter_enabled_plugins'] += array($plugin_id => $enabled_plugin_name);
		}

		loadSource('Subs-List');
		if (isset($list_options))
		{
			if (strpos($plugin_id, ':') === false)
				$list_options['columns']['function_name']['header']['value'] = $txt['hooks_field_hook_exists'];

			if ($plugin_id !== 'show_hooks:unknown')
				unset($list_options['columns']['check'], $list_options['form'], $list_options['additional_rows']);
			else
				unset($list_options['columns']['priority']);

			createList($list_options);
		}
	}

	if (!empty($_POST['remove_hooks']) && !empty($_POST['remove']) && is_array($_POST['remove']))
	{
		checkSession();

		foreach ($_POST['remove'] as $hook => $functions)
		{
			if (!is_array($functions))
				continue;

			foreach ($functions as $function)
				remove_function($hook, $function);
		}
	}

	$context['page_title'] = $txt['hooks_title_list'];
	loadPluginTemplate('live627:show_hooks', 'ShowHooks');
	wetem::load('hooks');
	call_hook('list_show_hooks', array(&$listOptions));
	$context['main_css_files']['showhooks'] = true;
	$context['skin_folders'][] = array($context['plugins_dir']['live627:show_hooks'] . '/', 'live627:show_hooks_');
	$theme['live627:show_hooks_url'] = $context['plugins_dir']['live627:show_hooks'];
}

function get_hooks_data($start, $items_per_page, $sort, $requested_plugin_id)
{
	global $context;

	$sort_types = array(
		'hook_name' => array('hook', SORT_ASC),
		'hook_name DESC' => array('hook', SORT_DESC),
		'function_name' => array('function', SORT_ASC),
		'function_name DESC' => array('function', SORT_DESC),
		'hook_exists' => array('hook_exists', SORT_ASC),
		'hook_exists DESC' => array('hook_exists', SORT_DESC),
		'priority' => array('priority', SORT_ASC),
		'priority DESC' => array('priority', SORT_DESC),
	);

	$sort_options = $sort_types[$sort];
	$sort = array();
	$plugins = get_hooks();
	foreach ($plugins as $plugin_id => $hooks)
	{
		if ($plugin_id === $requested_plugin_id)
			foreach ($hooks as $hook => $functions)
				foreach ($functions as $function)
				{
					$fun = explode('|', trim($function));
					$call = strpos($fun[0], '::') !== false ? explode('::', $fun[0]) : $fun[0];

					// Load any required file.
					if (!empty($fun[1]))
					{
						// We might be loading plugin files, we might not. This can't be set by add_hook, but by the hook manager.
						if (!empty($fun[2]) && $fun[2] === 'plugin')
							include_once($fun[1] . '.php');
						else
							loadSource($fun[1]);
					}
					if (strpos($plugin_id, ':') === false)
					{
						if (isset($_REQUEST['select_hooks']) && $_REQUEST['select_hooks'] === 'all')
							if (!isset($fun[1]))
								$hook2 = $hook;
							else
							{
								$path = dirname($fun[1]);
								$plugins_dir = array_flip($context['plugins_dir']);
								if (!isset($plugins_dir[$path]))
									$hook2 = $hook;
								else
								{
									$plugin = load_plugin_details($plugins_dir[$path]);
									if (!empty($plugin['name']))
										$hook2 = $hook . ' (' . $plugin['name'] . ')';
									else
										$hook2 = $hook;
								}
							}
						else
						{
							$plugin = load_plugin_details($hook);
							$hook2 = $plugin['name'];
						}
					}
					else
						$hook2 = $hook;

					$sort[] = $$sort_options[0];
					$temp_data[] = array(
						'hook_name' => $hook2,
						'function_name' => $fun[0],
						'hook_exists' => is_callable($call),
						'priority' => isset($fun[3]) ? $fun[3] : 'N/A'
					);
				}
	}

	array_multisort($sort, $sort_options[1], $temp_data);

	$counter = 0;
	$start++;

	foreach ($temp_data as $data)
	{
		if (++$counter < $start)
			continue;
		elseif ($counter == $start + $items_per_page)
			break;

		$hooks_data[] = $data;
	}

	return $hooks_data;
}

function get_hooks_count()
{
	$hooks_count = 0;
	$plugins = get_hooks();
	foreach ($plugins as $plugin_id => $hooks)
		foreach ($hooks as $hook => $functions)
			$hooks_count += count($functions);

	return $hooks_count;
}

function get_hooks()
{
	global $context, $settings, $pluginsdir;
	static $plugins;
	add_hook('test_point', 'test_fn', 'TestFile', false);

	if (!isset($plugins))
	{
		$plugins = array();
		if (isset($_REQUEST['select_hooks'], $settings['plugin_' . $_REQUEST['select_hooks']]))
		{
			$plugin_details = @unserialize($settings['plugin_' . $_REQUEST['select_hooks']]);
			$this_plugindir = $pluginsdir . '/' . $_REQUEST['select_hooks'];
			$plugin_id = $plugin_details['id'];
			unset($plugin_details['id'], $plugin_details['provides'], $plugin_details['actions']);
			foreach ($plugin_details as $hook => $functions)
				foreach ($functions as $function)
					$plugins[$plugin_id][$hook][] = strtr($function, array('$plugindir' => $this_plugindir));
		}
		else
			foreach ($settings['hooks'] as $hook => $functions)
				foreach ($functions as $function)
				{
					$fun = explode('|', trim($function));
					$path = dirname($fun[1]);
					$plugins_dir = array_flip($context['plugins_dir']);
					$plugin_id = isset($plugins_dir[$path]) ? $plugins_dir[$path] : 'show_hooks:unknown';
					switch (@$_REQUEST['select_hooks'])
					{
						case 'show_hooks:hooks':
							$plugins[$hook][$plugin_id][] = $function;
							break;

						case 'all':
							$plugins['all'][$hook][] = $function;
							break;

						default:
							$plugins[$plugin_id][$hook][] = $function;
					}
				}
	}

	return $plugins;
}

function load_plugin_details($plugin_id)
{
	global $context;
	static $plugin;

	if (!isset($plugin[$plugin_id]))
	{
		$plugin = array();
		if (isset($context['plugins_dir'][$plugin_id]))
		{
			$manifest = simplexml_load_file($context['plugins_dir'][$plugin_id] . '/plugin-info.xml');
			if ($manifest === false || empty($manifest['id']) || empty($manifest->name) || empty($manifest->author) || empty($manifest->version))
				$plugin[$plugin_id] = array(
					'name' => '',
					'author' => '',
					'author_email' => '',
					'description' => '',
				);
			else
				$plugin[$plugin_id] = array(
					'name' => westr::htmlspecialchars((string) $manifest->name),
					'author' => westr::htmlspecialchars($manifest->author),
					'author_email' => (string) $manifest->author['email'],
					'description' => westr::htmlspecialchars($manifest->description),
				);
		}
		else
			$plugin[$plugin_id] = array(
				'name' => '',
				'author' => '',
				'author_email' => '',
				'description' => '',
			);
	}

	return $plugin[$plugin_id];
}

function populate_plugin_choices()
{
	global $context;

	$sb = isset($_REQUEST['select_hooks']) ? $_REQUEST['select_hooks'] : '';
	$context['plugin_choices'] = array(
		'<optgroup label="General"></optgroup>',
		'<option value="all_cat"' . ($sb == 'all_cat' ? ' selected' : '') . '>All at once | Categorized by plugin</option>',
		'<option value="all"' . ($sb == 'all' ? ' selected' : '') . '>All at once | One big list</option>',
		'<option value="show_hooks:unknown"' . ($sb == 'show_hooks:unknown' ? ' selected' : '') . '>Isolated Hooks | Show hooks that are not part of a plugin.</option>',
		'<option value="show_hooks:hooks"' . ($sb == 'show_hooks:hooks' ? ' selected' : '') . '>All Hooks | Categorized by hook.</option>',
		'<optgroup label="Filter by Plugin"></optgroup>',
	);

	foreach ($context['enabled_plugins'] as $plugin_id => $plugin_name)
	{
		$plugin = load_plugin_details($plugin_id);
		$context['plugin_choices'][] = '<option value="' . $plugin_name .  '"' . ($sb == $plugin_name ? ' selected' : '') . '>' . $plugin['name'] . '|' . $plugin['description'] . '</option>';
	}
}

?>