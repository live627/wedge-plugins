<?php

function max_img_moderation_rules(&$known_variables, $admin)
{
	global $txt, $admin_areas;

	loadPluginLanguage('live627:max_img', 'MaxImages');
	loadPluginTemplate('live627:max_img', 'MaxImages');
	$known_variables['imgs'] = array(
		'type' => 'range',
		'current' => 0,
		'func_val' => 'count_img_post',
		'function' => create_function('$criteria', '
			global $txt;
			return $txt[\'modfilter_cond_\' . $criteria[\'name\']] . \': \' . $txt[\'modfilter_range_\' . $criteria[\'term\']] . \' \' . $criteria[\'value\'];
		'),
	);
}

function displayRow_imgs($rule)
{
	return simpleRange_displayRow($rule, 'imgs');
}

function count_img_post($subject, $body)
{
	$body = strtolower(preg_replace('~\[img.*?]~', '[img]', $body));
	return substr_count($body, '[img]');
}

?>