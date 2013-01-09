<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topic_notes_display_main()
{
	global $context, $txt, $board, $topic, $topicinfo, $settings, $user_info;

	loadPluginLanguage('live627:topic_notes', 'TopicNotes-Display');

	// Check if the current board is in the list of boards practising Topic Notes, leave if not.
	$board_list = !empty($settings['topicnotes_boards']) ? unserialize($settings['topicnotes_boards']) : array();
	if (!in_array($board, $board_list))
		return;

	$request = wesql::query('
		SELECT note
		FROM {db_prefix}topic_notes
		WHERE id_topic = {int:topic}',
		array(
			'topic' => $topic,
		)
	);
	if (wesql::num_rows($request) != 0)
	{
		list ($context['topic_note']) = wesql::fetch_row($request);

		$context['topic_note'] = parse_bbc(censorText($context['topic_note']));

		if (!empty($context['topic_note']))
			wetem::before('report_success', 'topic_notes_warning');

		add_css('
		.topic_note
		{
			background-image: url(' . $context['plugins_url']['live627:topic_notes'] . '/note_medium.png);
			background-repeat: no-repeat;
			background-position: 1%;
			padding-left: 40px;
		}');
	}
}

function topic_notes_post_form()
{
	global $board, $topic, $context, $settings, $topic_info, $user_info;

		$request = wesql::query('
			SELECT locked, is_pinned, id_poll, approved, id_first_msg, id_last_msg, id_member_started, id_board
			FROM {db_prefix}topics
			WHERE id_topic = {int:current_topic}
			LIMIT 1',
			array(
				'current_topic' => $topic,
			)
		);
		$topic_info = wesql::fetch_assoc($request);
		wesql::free_result($request);

	if (allowedTo('marknotes_any') || (allowedTo('marknotes_own') && $topic_info['id_member_started'] == $user_info['id']))
	{
		$board_list = !empty($settings['topicnotes_boards']) ? unserialize($settings['topicnotes_boards']) : array();
		if (!in_array($board, $board_list))
			return;

		$request = wesql::query('
			SELECT note
			FROM {db_prefix}topic_notes
			WHERE id_topic = {int:current_topic}',
			array(
				'current_topic' => $topic,
			)
		);
		list ($context['topic_note']) = wesql::fetch_row($request);

		if (isset($_POST['note']))
			$context['topic_note'] = $_POST['note'];

		wetem::after('post_additional_options', 'input_topic_note');
	}
}

function topic_notes_after()
{
	global $topic, $context, $user_info, $topic_info;

	if (allowedTo('marknotes_any') || (allowedTo('marknotes_own') && $topic_info['id_member_started'] == $user_info['id']))
		wesql::insert('replace',
			'{db_prefix}topic_notes',
			array('note' => 'string-1024', 'id_topic' => 'int'),
			array(westr::htmlspecialchars($_POST['note']), $topic),
			array('id_topic')
		);
}

function template_topic_notes_warning()
{
	global $context, $txt;

	echo '
		<div class="description topic_note">
			', $context['topic_note'], '
		</div>';
}

function template_input_topic_note()
{
	global $context, $txt;

	echo '
		<textarea name="note" rows="3" cols="50" class="w100">', $context['topic_note'], '</textarea>';
}

?>