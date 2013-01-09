<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function topic_notes_illegal_guest_perms()
{
	global $context;

	$context['non_guest_permissions'][] = 'topicnotes_own';
	$context['non_guest_permissions'][] = 'topicnotes_any';
}

function topic_notes_moderation_rules(&$known_variables, $admin)
{
	global $context;

	loadPluginLanguage('live627:topic_notes', 'TopicNotes-Admin');

	if (isset($context['modfilter_action_list']))
	{
		$context['modfilter_action_list'][] = '';
		$context['modfilter_action_list'][] = 'topic_note';
	}
}

?>