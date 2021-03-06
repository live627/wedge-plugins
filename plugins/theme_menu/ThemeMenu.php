<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function live627_theme_listing()
{
	global $txt, $context;

	// Will need this whatever.
	loadSource('Themes');

	// Every menu gets a unique ID, these are shown in first in, first out order.
	$context['max_menu_id'] = isset($context['max_menu_id']) ? $context['max_menu_id'] + 1 : 1;

	// This will be all the data for this menu - and we'll make a shortcut to it to aid readability here.
	$context['menu_data_' . $context['max_menu_id']] = array();
	$menu_context =& $context['menu_data_' . $context['max_menu_id']];

	$temp = cache_get_data('live627_theme_listing', 180);
	if ($temp === null)
	{
		// Get all the themes...
		$request = wesql::query('
			SELECT id_theme AS id, value AS name
			FROM {db_prefix}themes
			WHERE variable = {string:name}',
			array(
				'name' => 'name',
			)
		);
		while ($row = wesql::fetch_assoc($request))
			$temp[$row['id']] = $row;
		wesql::free_result($request);

		// Get theme dir for all themes
		$request = wesql::query('
			SELECT id_theme AS id, value AS dir
			FROM {db_prefix}themes
			WHERE variable = {string:dir}',
			array(
				'dir' => 'theme_dir',
			)
		);
		while ($row = wesql::fetch_assoc($request))
			$temp[$row['id']]['skins'] = wedge_get_skin_list($row['dir'] . '/skins');
		wesql::free_result($request);

		cache_put_data('live627_theme_listing', $temp, 180);
	}
	$menu_context['sections']['live627_theme_listing'] = array(
		'id' => 'live627_theme_listing',
		'title' => 'Switch Theme',
	);
	foreach ($temp as $th_id => $th_data)
	{
		$th_data[count($th_data) - 1]['is_last'] = true;
		show_skins_recursive($th_data, $th_data['skins'], $menu_context);
	}
	loadTemplate('GenericMenu');
	wetem::add('header', 'generic_menu_dropdown');
	$menu_context['current_section'] = '';
	$menu_context['extra_parameters'] = '';
	add_css('
		#header ul#amen' . ($context['max_menu_id'] > 1 ? '_' . ($context['max_menu_id'] - 1) : '') . '
		{
			display: inline-block;
			padding: 0pt;
			margin-bottom: 0.25em;
			min-height: 1em;
		}
		#header ul#amen' . ($context['max_menu_id'] > 1 ? '_' . ($context['max_menu_id'] - 1) : '') . ' ul a
		{
			color: #330000;
		}
		#header ul#amen' . ($context['max_menu_id'] > 1 ? '_' . ($context['max_menu_id'] - 1) : '') . ' h4
		{
			margin: 0;
			color: #fff;
		}
		#header ul#amen' . ($context['max_menu_id'] > 1 ? '_' . ($context['max_menu_id'] - 1) : '') . ' > li
		{
			margin-bottom: -0.4em;
		}');
}

function show_skins_recursive($th, $skins, &$menu_context)
{
	global $context, $scripturl, $theme;

	$last = count($skins);
	$current = 1;
	foreach ($skins as $skin)
	{
		$sd = base64_encode($skin['dir']);
		$menu_context['sections']['live627_theme_listing']['areas'][$th['id'] . '_' . $sd] = array(
			'id' => $th['id'] . '_' . $sd,
			'label' => $skin['name'] . ' (' . $th['name'] . ')',
			'url' => $scripturl . '?theme=' . $th['id'] . '_' . $sd,
			'icon' => '',
		);
		$is_current_skin = $context['skin'] == $skin['dir'] && $theme['theme_id'] == $th['id'];
		if ($is_current_skin)
			$menu_context['current_area'] = $th['id'] . '_' . $sd;
		if ($current == $last && empty($skin['skins']) && !empty($th['is_last']))
			$menu_context['sections']['live627_theme_listing']['areas'][] = '';
		if (!empty($skin['skins']))
			show_skins_recursive($th, $skin['skins'], $menu_context);
		$current++;
	}
}

?>