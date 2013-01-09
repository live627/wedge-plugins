<?php

// Manage rules.
function template_monthly_stats_rules()
{
	global $context, $settings, $options, $txt, $scripturl;

		echo '
			<table class="table_grid w100 cs0 cp4" id="stats_history">
				<thead>
					<tr class="titlebg">
						<th class="first_th w25">', $txt['yearly_summary'], '</th>
						<th>', $txt['stats_new_topics'], '</th>
						<th>', $txt['stats_new_posts'], '</th>
					</tr>
				</thead>
				<tbody>';

		foreach ($context['yearly'] as $id => $year)
		{
			echo '
					<tr class="windowbg2" id="year_', $id, '">
						<th class="year">
							<span class="foldable fold" id="year_img_', $id, '"></span>
							<a href="#year_', $id, '" id="year_link_', $id, '">', $year['year'], '</a>
						</th>
						<th>', $year['new_topics'], '</th>
						<th>', $year['new_posts'], '</th>
					</tr>';

			foreach ($year['months'] as $month)
			{
				echo '
					<tr class="windowbg2" id="tr_month_', $month['id'], '">
						<th class="month">
							<span class="foldable', $month['expanded'] ? ' fold' : '', '" id="img_', $month['id'], '"></span>
							<a id="m', $month['id'], '" href="', $month['href'], '">', $month['month'], ' ', $month['year'], '</a>
						</th>
						<th>', $month['new_topics'], '</th>
						<th>', $month['new_posts'], '</th>
					</tr>';

				if ($month['expanded'])
				{
					foreach ($month['days'] as $day)
					{
						echo '
					<tr class="windowbg2" id="tr_day_', $day['year'], '-', $day['month'], '-', $day['day'], '">
						<td class="day">', $day['year'], '-', $day['month'], '-', $day['day'], '</td>
						<td>', $day['new_topics'], '</td>
						<td>', $day['new_posts'], '</td>
					</tr>';
					}
				}
			}
		}

		echo '
				</tbody>
			</table>
		</div>';

		add_js_file('scripts/stats.js');

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
		],

		aDataCells: [
			\'date\',
			\'new_topics\',
			\'new_posts\'
		]
	});');
}

?>