<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topic_notes_action()
{
	global $context, $txt, $board, $topic, $settings, $user_info;

	if (empty($topic) || empty($board))
		redirectexit();

	$board_list = !empty($settings['topicnotes_boards']) ? unserialize($settings['topicnotes_boards']) : array();
	if (!in_array($board, $board_list))
		redirectexit();

	// So, we need to know whether it is notes. Load.php will already have identified whether we can see the topic.
	$request = wesql::query('
		SELECT t.id_member_started, ts.notes
		FROM {db_prefix}topics AS t
			LEFT JOIN {db_prefix}topicnotes AS ts ON (t.id_topic = ts.id_topic)
		WHERE t.id_topic = {int:topic}',
		array(
			'topic' => $topic,
		)
	);
	list ($topic_starter, $notes) = wesql::fetch_row($request);
	wesql::free_result($request);

	// Can we mark this notes?
	// !!! Nicer error
	if (!allowedTo('marknotes_any') && (!allowedTo('marknotes_own') || $topic_starter != $user_info['id']))
		fatal_lang_error('no_access');

	if (empty($notes))
	{
		wesql::insert('replace',
			'{db_prefix}topicnotes',
			array(
				'id_topic' => 'int', 'notes' => 'int', 'id_member' => 'int',
			),
			array(
				$topic, time(), $user_info['id'],
			),
			array('id_topic')
		);
		logAction('solve', array('topic' => $topic), 'moderate');
		redirectexit('topic=' . $topic . '.0');
	}
	else
	{
		wesql::query('
			DELETE FROM {db_prefix}topicnotes
			WHERE id_topic = {int:topic}',
			array(
				'topic' => $topic,
			)
		);
		logAction('unsolve', array('topic' => $topic), 'moderate');
		redirectexit('topic=' . $topic . '.0');
	}
}

?>