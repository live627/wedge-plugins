<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topic_notes_messageindex_buttons()
{
	global $context, $txt, $board, $board_info, $settings;

	loadPluginLanguage('live627:topic_notees', 'TopicNotes-MessageIndex');

	// Check if the current board is in the list of boards practising Topic Notes, leave if not.
	$board_list = !empty($settings['topicnotes_boards']) ? unserialize($settings['topicnotes_boards']) : array();
	if (!in_array($board_info['id'], $board_list))
		return;

	if (empty($context['topics']))
		return;

	$topic_ids = array_keys($context['topics']);
	$request = wesql::query('
		SELECT id_topic
		FROM {db_prefix}topicnotes
		WHERE id_topic IN ({array_int:topics})',
		array(
			'topics' => $topic_ids,
		)
	);
	while (list ($id) = wesql::fetch_row($request))
	{
		$context['topics'][$id]['style'] .= ' notes';
		$context['topics'][$id]['first_post']['icon_url'] = $context['plugins_url']['live627:topic_notees'] . '/img/tick.png';
	}

	if (wesql::num_rows($request) > 0 && !empty($settings['topicnotes_bg1']) && !empty($settings['topicnotes_bg2']) && !empty($settings['topicnotes_fg']))
		add_css('
	.notes { color: ' . $settings['topicnotes_fg'] . ' } .windowbg.notes { background-color: ' . $settings['topicnotes_bg1'] . ' } .windowbg2.notes { background-color: ' . $settings['topicnotes_bg2'] . ' }');
}

// Since the usual case for this function is message index, save something by putting this here.
function topicNotesQuickModeration(&$quickmod)
{
	global $context, $txt, $board, $board_info, $settings;

	loadPluginLanguage('live627:topic_notees', 'TopicNotes-MessageIndex');

	$board_list = !empty($settings['topicnotes_boards']) ? unserialize($settings['topicnotes_boards']) : array();
	if (empty($board_list))
		return;

	// Do permission test for 'any' in this board (or for multiple boards if it is search)
	if (!empty($board))
	{
		if ((!allowedTo('topicnotes_any') && !allowedTo('topicnotes_own')) || !in_array($board_info['id'], $board_list))
			return;
		$can = true;
	}
	else
	{
		$boards_can = boardsAllowedTo(array('topicnotes_any', 'topicnotes_own'));
		if (!in_array(0, $boards_can['topicnotes_any']))
		{
			$can = false;
			foreach ($boards_can as $perm => $boards)
			{
				$boards_can[$perm] = array_intersect($boards_can[$perm], $board_list);
				if (!empty($boards_can[$perm]))
					$can = true;
			}
		}
		else
			$can = true;
	}

	if ($can)
		$quickmod['marknotes'] = $txt['quick_mod_marknotes'];
}

?>