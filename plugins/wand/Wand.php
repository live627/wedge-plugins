<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function wand_admin_areas()
{
	global $admin_areas, $context, $txt;

	loadPluginLanguage('live627:wand', 'Wand');
	$admin_areas['plugins']['areas']['wand'] = array(
		'label' => $txt['wand_title_list'],
		'icon' => $context['plugins_url']['live627:wand'] . '/wand_small.png',
		'bigicon' => $context['plugins_url']['live627:wand'] . '/wand_large.png',
		'function' => 'Wand',
	);
}

function Wand()
{
	global $context, $txt;

	loadPluginSource('live627:wand', 'Class-Wand');
	loadPluginTemplate('live627:wand', 'Wand');
	$wand = new Wand();
	$steps = array();
	$context['page_title'] = $txt['wand_title_list'];

	if (isset($_GET['do']))
	{
		$context['totals'] = array(
			'current' => 0,
			'max' => 0,
		);
		foreach ($wand->getSteps() as $key => $val)
		{
			$steps[] = comma_format($val['max'] - $val['current']) . '</span> ' . $key;
			$context['totals']['max'] += $val['max'] - $val['current'];
			$context['totals']['current'] += $val['current'];
		}

		$context['steps'] = implode('</li>
					<li>Generating <span>', $steps);
		if (isset($_GET['xml']))
		{
			$wand->start();
		}
		add_plugin_css_file('live627:wand', 'wand', true);
		wetem::load('wand2');
	}
	else
	{
		foreach ($wand->getSteps() as $key => $val)
			$steps[] = comma_format($val['max'] - $val['current']) . ' ' . $key;

		$context['steps'] = implode(',<br>
					', $steps);
		wetem::load('wand');
	}
}

?>