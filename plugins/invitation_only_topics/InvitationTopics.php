<?php
// Version: 1.0: InvitationTopics.php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function invitation_only_topics_load_permissions(&$permissionGroups, &$permissionList, &$leftPermissionGroups, &$hiddenPermissions, &$relabelPermissions)
{
	loadPluginLanguage('live627:invitation_only_topics', 'InvitationTopics');
	$permissionList['board'] += array(
		'invite_to_topic' => array(true, 'topic', 'make_posts'),
	);
}

function invitation_only_topics_illegal_guest_perms()
{
	global $context;

	$context['non_guest_permissions'] = array_unshift($context['non_guest_permissions'],
		'invite_to_topic'
	);
}

function invitation_only_topics_extend_db_replacement()
{
	global $db_prefix, $topic, $user_info;

	if (!($user_info['is_admin'] && !empty($topic)))
	{
		$user_info['query_see_topic'] .= '
		AND
		(
			IF
			(
				t.invited = 0, 1,
				t.id_member_started != 0
				AND (SELECT id_member_invited FROM ' . $db_prefix . 'topic_invites AS ti WHERE id_member_invited = ' . $user_info['id'] . ' AND ti.id_topic = t.id_topic)
			)
		)';

		wesql::register_replacement('query_see_topic', $user_info['query_see_topic']);
	}
}

function invitation_only_topics_display_main()
{
	global $context, $topic, $user_info;

	$request = wesql::query('
		SELECT invited
		FROM {db_prefix}topics
		WHERE id_topic = {int:topic}',
		array(
			'topic' => (int) $topic,
		)
	);
	list ($invited) = wesql::fetch_row($request);

	loadPluginLanguage('live627:invitation_only_topics', 'InvitationTopics');
	loadPluginTemplate('live627:invitation_only_topics', 'InvitationTopics');
	wetem::load('sidebar', 'display_invitation_only_topics');

	if (!empty($topic))
	{
		$request = wesql::query('
			SELECT id_member_invited, real_name
			FROM {db_prefix}topic_invites
				INNER JOIN {db_prefix}members AS mem ON (mem.id_member = id_member_invited)
			WHERE id_topic = {int:topic}',
			array(
				'topic' => $topic,
			)
		);
		$context['invited_users'] = array();
		while ($row = wesql::fetch_row($request))
			$context['invited_users'][] = '<a href="<URL>?action=profile;u=' . $row[0] . '">' . $row[1] . '</a>';
	}
}

function invitation_only_topics_lang_help()
{
	loadPluginLanguage('live627:invitation_only_topics', 'InvitationTopics');
}

function invitation_only_topics_post_form()
{
	global $topic, $txt;

	if (allowedTo('invite_to_topic'))
	{
		loadPluginLanguage('live627:invitation_only_topics', 'InvitationTopics');
		loadPluginTemplate('live627:invitation_only_topics', 'InvitationTopics');
		wetem::after('post_subject', 'input_invitation_only_topics');
		add_js_file('scripts/suggest.js');

		if (!empty($topic))
		{
			$request = wesql::query('
				SELECT id_member_invited, real_name
				FROM {db_prefix}topic_invites
					INNER JOIN {db_prefix}members AS mem ON (mem.id_member = id_member_invited)
				WHERE id_topic = {int:topic}',
				array(
					'topic' => $topic,
				)
			);
			$invited = array();
			while ($row = wesql::fetch_row($request))
				$invited += array((int) $row[0] => $row[1]);
		}
		elseif (!empty($_POST['invited_list']))
			$invited = $_POST['invited_list'];
		else
			$invited = array();

		add_js('
	var sDelSuggest = ', JavaScriptEscape($txt['autosuggest_delete_item']), ';
	new weAutoSuggest({
		bItemList: true,
		sControlId: \'invitee\',
		sPostName:  \'invited_list\',
		sTextDeleteItem: sDelSuggest');

		if (!empty($invited))
			add_js(',
		aListItems: ', json_encode($invited));

		add_js('
	});');
	}
}

function invitation_only_topics_after(&$msgOptions, &$topicOptions, &$posterOptions)
{
	global $user_info;

	$invitee = array($_POST['invitee']);
	if (isset($_POST['invited_list']) && is_array($_POST['invited_list']))
	{
		$invitee1 = array();
		foreach ($_POST['invited_list'] as $invited)
			$invitee1[(int) $invited] = (int) $invited;
		$invitee += $invitee1;
	}
	for ($k = 0, $n = count($invitee); $k < $n; $k++)
	{
		$invitee[$k] = trim($invitee[$k]);

		if (strlen($invitee[$k]) == 0)
			unset($invitee[$k]);
	}

	// Find all the id_member's for the member_name's in the list.
	if (empty($invitee))
		$invitee = array();
	if (!empty($invitee))
	{
		$request = wesql::query('
			SELECT id_member
			FROM {db_prefix}members
			WHERE id_member IN ({array_string:invited_list}) OR real_name IN ({array_string:invited_list})
			LIMIT ' . count($invitee),
			array(
				'invited_list' => $invitee,
			)
		);
		$inserts = array();
		while ($row = wesql::fetch_assoc($request))
			$inserts[] = array($topicOptions['id'], $row['id_member'], $user_info['id']);
		wesql::free_result($request);
		$inserts[] = array($topicOptions['id'], $user_info['id'], $user_info['id']);

		wesql::insert('replace',
			'{db_prefix}topic_invites',
			array('id_topic' => 'int', 'id_member_invited' => 'int', 'id_member_inviter' => 'int'),
			$inserts,
			array('id_topic')
		);

		if (!empty($inserts))
			wesql::query('
				UPDATE {db_prefix}topics
				SET invited = 1
				WHERE id_topic = {int:id_topic}',
				array(
					'id_topic' => $topicOptions['id'],
				)
			);
	}
	else
		wesql::query('
			UPDATE {db_prefix}topics
			SET invited = 0
			WHERE id_topic = {int:id_topic}
				AND invited = 1',
			array(
				'id_topic' => $topicOptions['id'],
			)
		);
}

function invitation_only_topics_pre_post_validate(&$post_errors)
{
	global $txt;

	if (!allowedTo('invite_to_topic') && (!empty($_POST['invited_list']) || !empty($_POST['invitee'])))
		$post_errors['cannot_invite'] = $txt['error_invitation_only_topics'];
}

?>