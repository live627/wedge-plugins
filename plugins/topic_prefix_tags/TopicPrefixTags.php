<?php

function tpt_buffer($buffer)
{
	global $context, $scripturl;

	// A regex-ready $scripturl, useful later.
	$preg_scripturl = preg_quote($scripturl, '~');

	// Get all the topic links on the page.
	$topic_links = array();
	preg_match_all('~<a\b([^>]+href="' . $preg_scripturl . '\?(?:[^"]+)?\btopic=(\d+)[^>]*)>(.*?)</a>~', $buffer, $topic_links, PREG_SET_ORDER);
	$loaded_ids = array();
	foreach ($topic_links as $topic_link)
		$loaded_ids[] = (int) $topic_link[2];

	$loaded_ids = array_unique($loaded_ids);

	if (!empty($loaded_ids))
	{
		$request = wesql::query('
			SELECT *
			FROM {db_prefix}message_field_data AS mfd
				INNER JOIN {db_prefix}message_fields AS mf ON (mf.id_field = mfd.id_field AND mf.name LIKE \'%prefix%\')
				INNER JOIN {db_prefix}messages AS m ON (m.id_msg = mfd.id_msg)
				INNER JOIN {db_prefix}topics AS t ON (t.id_topic = m.id_topic)
			WHERE t.id_topic IN ({array_int:loaded_ids})
				AND active = {string:active}',
			 array(
				'loaded_ids' => $loaded_ids,
				'active' => 'yes',
			)
		);

		// Put all the pieces back together!
		while ($row = wesql::fetch_assoc($request))
			foreach ($topic_links as $topic_link)
				if ($topic_link[2] == $row['id_topic'] && strip_tags($topic_link[3]) == $row['subject'] && strpos($topic_link[3], 'http://') === false)
				{
					$buffer = str_replace($topic_link[0], parse_bbc($row['value']) . ' ' . $topic_link[0], $buffer);
					break;
				}

		wesql::free_result($request);
	}

	return $buffer;
}

?>