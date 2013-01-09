<?php
// Version: 1.0: PMAutoResponder.php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function monthly_stats_profile_areas(&$profile_areas)
{
	global $context, $user_info, $txt, $settings;

	if (!(isset($_REQUEST['area']) && $_REQUEST['area'] == 'statistics'))
		return;

	// Activity by month.
	$result = wesql::query('
		SELECT
			DAY(date) AS stats_day,
			MONTH(date) AS stats_month,
			YEAR(date) AS stats_year
		FROM {db_prefix}log_activity',
		array(
			'current_offset' => ($user_info['time_offset'] + $settings['time_offset']) * 3600,
		)
	);

	$context['monthly_stats'] = $context['collapsed_years'] = array();
	$context['current_month'] = $context['today'] = 0;
	while ($row = wesql::fetch_assoc($result))
	{
		$context['monthly_stats']['years'][$row['stats_year']]['posts'] = 0;
		$context['monthly_stats']['years'][$row['stats_year']]['months'][$row['stats_month']]['posts'] = 0;
		$context['monthly_stats']['years'][$row['stats_year']]['months'][$row['stats_month']]['days'][$row['stats_day']]['posts'] = 0;
		$context['monthly_stats']['years'][$row['stats_year']]['topics'] = 0;
		$context['monthly_stats']['years'][$row['stats_year']]['months'][$row['stats_month']]['topics'] = 0;
		$context['monthly_stats']['years'][$row['stats_year']]['months'][$row['stats_month']]['days'][$row['stats_day']]['topics'] = 0;

		// Keep a list of collapsed years.
		if ($row['stats_year'] != date('Y'))
			$context['collapsed_years'][$row['stats_year']] = $row['stats_year'];

		// Keep a list of collapsed years.
		if ($row['stats_month'] == date('n') && $row['stats_year'] == date('Y'))
			$context['current_month'] = $row['stats_month'];

		// Keep a list of collapsed years.
		if ($row['stats_day'] == date('j') && $row['stats_month'] == date('n') && $row['stats_year'] == date('Y'))
			$context['today'] = $row['stats_day'];
	}
	wesql::free_result($result);
	$context['collapsed_years'] = array_reverse($context['collapsed_years']);

	$result = wesql::query('
		SELECT
			DAY(FROM_UNIXTIME(poster_time + {int:current_offset})) AS stats_day,
			MONTH(FROM_UNIXTIME(poster_time + {int:current_offset})) AS stats_month,
			YEAR(FROM_UNIXTIME(poster_time + {int:current_offset})) AS stats_year
		FROM {db_prefix}messages
		WHERE id_member = {int:current_member}',
		array(
			'current_member' => $context['id_member'],
			'current_offset' => ($user_info['time_offset'] + $settings['time_offset']) * 3600,
		)
	);

	while ($row = wesql::fetch_assoc($result))
	{
		$context['monthly_stats']['years'][$row['stats_year']]['posts']++;
		$context['monthly_stats']['years'][$row['stats_year']]['months'][$row['stats_month']]['posts']++;
		$context['monthly_stats']['years'][$row['stats_year']]['months'][$row['stats_month']]['days'][$row['stats_day']]['posts']++;
	}
	wesql::free_result($result);

	// Activity by month.
	$result = wesql::query('
		SELECT
			DAY(FROM_UNIXTIME(poster_time + {int:current_offset})) AS stats_day,
			MONTH(FROM_UNIXTIME(poster_time + {int:current_offset})) AS stats_month,
			YEAR(FROM_UNIXTIME(poster_time + {int:current_offset})) AS stats_year
		FROM {db_prefix}topics As t
			INNER JOIN {db_prefix}messages AS m ON (m.id_msg = t.id_first_msg)
		WHERE t.id_member_started = {int:current_member}',
		array(
			'current_member' => $context['id_member'],
			'current_offset' => ($user_info['time_offset'] + $settings['time_offset']) * 3600,
		)
	);

	while ($row = wesql::fetch_assoc($result))
	{
		$context['monthly_stats']['years'][$row['stats_year']]['topics']++;
		$context['monthly_stats']['years'][$row['stats_year']]['months'][$row['stats_month']]['topics']++;
		$context['monthly_stats']['years'][$row['stats_year']]['months'][$row['stats_month']]['days'][$row['stats_day']]['topics']++;
	}
	wesql::free_result($result);

	wetem::after('default', 'monthly_stats');
	loadPluginLanguage('live627:monthly_stats', 'PMAutoResponder');
	krsort($context['monthly_stats']['years']);
	foreach ($context['monthly_stats']['years'] as &$year)
		krsort($year['months']);
}

function template_monthly_stats()
{
	global $context, $txt;

	echo '
			<table class="table_grid w100 cs0 cp4" id="stats_history">
				<thead>
					<tr class="titlebg">
						<th class="first_th w25"></th>
						<th>', $txt['monthly_stats_topics'], '</th>
						<th>', $txt['monthly_stats_posts'], '</th
					</tr>
				</thead>
				<tbody>';

		foreach ($context['monthly_stats']['years'] as $yid => $year)
		{
			echo '
					<tr class="windowbg2" id="year_', $yid, '">
						<th class="year">
							<span class="foldable', !isset($context['collapsed_years'][$yid]) ? ' fold' : '', '" id="year_img_', $yid, '"></span>
							<a href="#year_', $yid, '" id="year_link_', $yid, '">', $yid, '</a>
						</th>
						<th>', $year['topics'], '</th>
						<th>', $year['posts'], '</th>
					</tr>';

			foreach ($year['months'] as $mid => $month)
			{
				echo '
					<tr class="windowbg2" id="tr_month_', $yid, '', $mid, '">
						<th class="month">
							<span class="foldable', $mid == $context['current_month'] ? ' fold' : '', '" id="img_', $yid, '', $mid, '"></span>
							<a id="m', $yid, '', $mid, '" href="#">', $txt['months'][$mid], ' ', $yid, '</a>
						</th>
						<th>', $month['topics'], '</th>
						<th>', $month['posts'], '</th>
					</tr>';

				if ($mid == $context['current_month'])
					foreach ($month['days'] as $did => $day)
						echo '
					<tr class="windowbg2', $did == $context['today'] ? ' highlight' : '', '" id="tr_day_', $yid, '-', $mid, '-', $did, '">
						<td class="day">', $yid, '-', $mid, '-', $did, '</td>
						<td>', $day['topics'], '</td>
						<td>', $day['posts'], '</td>
					</tr>';
			}
		}

		echo '
				</tbody>
			</table>';

		add_plugin_js_file('live627:monthly_stats', 'stats.js');

		add_js('
	var oStatsCenter = new weStatsCenter({
		reYearPattern: /year_(\d+)/,
		sYearImageIdPrefix: \'year_img_\',
		sYearLinkIdPrefix: \'year_link_\',

		reMonthPattern: /tr_month_(\d+)/,
		sMonthImageIdPrefix: \'img_\',
		sMonthLinkIdPrefix: \'m\',

		reDayPattern: /tr_day_(\d+-\d+-\d+)/,
		sDayRowClassname: \'windowbg2\',
		sDayRowIdPrefix: \'tr_day_\',

		aCollapsedYears: [');

		foreach ($context['collapsed_years'] as $id => $year)
			add_js('
			\'' . $year . '\'' . ($id != count($context['collapsed_years']) - 1 ? ',' : ''));

		add_js('
		]
	});');
}

?>