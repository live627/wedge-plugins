<?php

function fdp_enclose_post_field($id_field, &$enclose, &$replacements)
{
	global $txt;

	if (strpos($enclose, '{DATEINPUT}') !== false)
	{
		$replacements = array(
			'{DATEINPUT}' => '',
		);

		add_plugin_css_file('live627:future_dated_posts', 'todo', true);
		add_js('
	var
		strftimeFormat = ' . JavaScriptEscape(we::$user['time_format']) . ',
		days = ' . json_encode(array_values($txt['days'])) . ',
		daysShort = ' . json_encode(array_values($txt['days_short'])) . ',
		months = ' . json_encode(array_values($txt['months'])) . ',
		monthsShort = ' . json_encode(array_values($txt['months_short'])) . ';');
		add_plugin_js_file('live627:future_dated_posts', 'dateinput.js');
		add_js('
	(function() {
		var elem = document.createElement(\'input\');
		elem.setAttribute(\'type\', \'datetime\');

		if (!is_touch || elem.type === \'text\')
		{
			$(\'input[id=customfield_' . $id_field . ']\').dateinput();
			document.getElementById(\'customfield_' . $id_field . '\').setAttribute(\'type\', \'text\');
		}
	})();');
	}
}

?>