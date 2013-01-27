<?php
// Version: 1.0: InvitationTopics.php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function invitation_only_topics_illegal_guest_perms()
{
	global $context;

	$context['non_guest_permissions'] = array_unshift($context['non_guest_permissions'],
		'invite_to_topic'
	);
}

function invitation_only_topics_extend_db_replacement()
{
	global $db_prefix, $topic;

	if (!(we::$user['is_admin'] && !empty($topic)))
	{
		we::$user['query_see_topic'] .= '
		AND
		(
			IF
			(
				t.invited = 0, 1,
				t.id_member_started != 0
				AND (SELECT id_member_invited FROM ' . $db_prefix . 'topic_invites AS ti WHERE id_member_invited = ' . we::$id . ' AND ti.id_topic = t.id_topic)
			)
		)';

		wesql::register_replacement('query_see_topic', we::$user['query_see_topic']);
	}
}

function invitation_only_topics_display_main()
{
	global $context, $topic;

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

function invitation_only_topics_post_form()
{
	global $topic, $txt;

	if (allowedTo('invite_to_topic'))
	{
		loadPluginLanguage('live627:invitation_only_topics', 'InvitationTopics');
		loadPluginTemplate('live627:invitation_only_topics', 'InvitationTopics');
		wetem::after('post_subject', 'input_invitation_only_topics');
		add_js_file('scripts/suggest.js');

		$invited = array();
		if (!empty($_POST['invited_list']))
		{
			$request = wesql::query('
				SELECT id_member, real_name
				FROM {db_prefix}members
				WHERE id_member IN ({array_int:invited_list})',
				array(
					'invited_list' => $_POST['invited_list'],
				)
			);
			while ($row = wesql::fetch_row($request))
				$invited += array((int) $row[0] => $row[1]);
		}
		elseif (!empty($topic))
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
			while ($row = wesql::fetch_row($request))
				$invited += array((int) $row[0] => $row[1]);
		}

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
	global $txt, $scripturl, $settings;

	if (allowedTo('invite_to_topic'))
	{
		loadPluginLanguage('live627:invitation_only_topics', 'InvitationTopics');

		// Get everyone already invited to this topic.
		$request = wesql::query('
			SELECT id_member_invited, real_name
			FROM {db_prefix}topic_invites
				INNER JOIN {db_prefix}members AS mem ON (mem.id_member = id_member_invited)
			WHERE id_topic = {int:topic}',
			array(
				'topic' => $topicOptions['id'],
			)
		);
		$invited_members = array();
		while ($row = wesql::fetch_row($request))
			$invited_members += array((int) $row[0] => $row[1]);

		$request = wesql::query('
			SELECT mf.subject
			FROM {db_prefix}topics AS t
				LEFT JOIN {db_prefix}messages AS mf ON (mf.id_msg = t.id_first_msg)
			WHERE t.id_topic = {int:current_topic}
			LIMIT 1',
			array(
				'current_topic' => $topicOptions['id'],
			)
		);
		list ($first_subject) = wesql::fetch_row($request);
		wesql::free_result($request);

		// Find out who's new for the invitation list.
		$invitee = array_merge(array($_POST['invitee']), $_POST['invited_list']);

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
			$inserts = $loaded_ids = array();
			while ($row = wesql::fetch_assoc($request))
				// Don't do this twice, and skip the topic starter.
				if ($row['id_member'] != we::$id)
				{
					$loaded_ids[] = (int) $row['id_member'];
					$inserts[] = array($topicOptions['id'], $row['id_member'], we::$id);

					if (!isset($invited_members[$row['id_member']]))
					{
						logAction('invite_to_topic', array('topic' => $topicOptions['id'], 'member' => $row['id_member']), 'moderate');
					}
				}
			wesql::free_result($request);
			$inserts[] = array($topicOptions['id'], we::$id, we::$id);

			wesql::insert('ignore',
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

			$new_invited_ids = array_diff(array_merge($loaded_ids, (array) we::$id), array_keys($invited_members));
			$kicked_ids = array_diff(array_keys($invited_members), array_merge($loaded_ids, (array) we::$id));
			if (!empty($settings['invitationtopics_inv_pm']) && !empty($new_invited_ids))
			{
				$recipients = array(
					'to' => $new_invited_ids,
					'bcc' => array(),
				);
				sendpm($recipients, $txt['pm_subject_invite_to_topic'], sprintf($txt['pm_body_invite_to_topic'], '[iurl=' . $scripturl . '?topic=' . $topicOptions['id'] . '.0]' . $first_subject . '[/iurl]'));
			}
		}
		else
		{
			wesql::query('
				DELETE FROM {db_prefix}topic_invites
				WHERE id_topic = {int:id_topic}',
				array(
					'id_topic' => $topicOptions['id'],
				)
			);
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

		if (!empty($kicked_ids))
		{
			wesql::query('
				DELETE FROM {db_prefix}topic_invites
				WHERE id_topic = {int:id_topic}
					AND id_member_invited IN ({array_int:kicked_members})',
				array(
					'id_topic' => $topicOptions['id'],
					'kicked_members' => $kicked_ids,
				)
			);
			foreach ($kicked_ids as $kicked_id)
				if ($kicked_id != we::$id)
				{
					logAction('kick_from_topic', array('topic' => $topicOptions['id'], 'member' => $kicked_id), 'moderate');

					if (!empty($settings['invitationtopics_kick_pm']))
					{
						$recipients = array(
							'to' => array($kicked_id),
							'bcc' => array(),
						);

						if (!empty($_POST['invitationtopics_kick_reason'][$kicked_id]))
							sendpm($recipients, $txt['pm_subject_kick_from_topic_reason'], sprintf($txt['pm_body_kick_from_topic'], '[iurl=' . $scripturl . '?topic=' . $topicOptions['id'] . '.0]' . $first_subject . '[/iurl]', $_POST['invitationtopics_kick_reason'][$kicked_id]));
						else
							sendpm($recipients, $txt['pm_subject_kick_from_topic'], sprintf($txt['pm_body_kick_from_topic'], '[iurl=' . $scripturl . '?topic=' . $topicOptions['id'] . '.0]' . $first_subject . '[/iurl]'));
					}
				}
		}
	}
}

function invitation_only_topics_post_post_validate(&$post_errors)
{
	global $context, $topic, $settings, $txt;

	if (allowedTo('invite_to_topic'))
	{
		loadPluginLanguage('live627:invitation_only_topics', 'InvitationTopics');

		// Get everyone already invited to this topic.
		$request = wesql::query('
			SELECT id_member_invited, real_name
			FROM {db_prefix}topic_invites
				INNER JOIN {db_prefix}members AS mem ON (mem.id_member = id_member_invited)
			WHERE id_topic = {int:topic}',
			array(
				'topic' => $topic,
			)
		);
		$invited_members = array();
		while ($row = wesql::fetch_row($request))
			$invited_members += array((int) $row[0] => $row[1]);

		// Find out who's new for the invitation list.
		$invitee = array_merge(array($_POST['invitee']), $_POST['invited_list']);

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
			$loaded_ids = $kicked_names = array();
			while ($row = wesql::fetch_assoc($request))
				if ($row['id_member'] != we::$id)
					$loaded_ids[] = $row['id_member'];

			$kicked_ids = array_diff(array_keys($invited_members), array_merge($loaded_ids, (array) we::$id));

			foreach ($kicked_ids as $kicked_id)
				if ($kicked_id != we::$id)
					if (!isset($_POST['invitationtopics_kick_reason'][$kicked_id]))
						$kicked_names[] = '<a href="<URL>?action=profile;u=' . $kicked_id . '">' . $invited_members[$kicked_id] . '</a>';

			if (!empty($settings['invitationtopics_kick_req_reason']) && !empty($kicked_names) || empty($_POST['invitationtopics_kick_reason']) && !empty($kicked_ids))
			{
				if (!empty($settings['invitationtopics_kick_reason']))
					$post_errors['give_kick_reason'] = array('give_kick_reason', implode(',', $kicked_names));

				add_js('

	reqWin(weUrl(\'action=invitationtopics;t=' . implode(',', $kicked_ids) . '\'), 800);

	$(\'#helf\').ready(function (e) {
		$(\'<input type="button" class="submit" />\')
			.val(we_ok)
			.css
			({
				\'margin-left\': \'1em\',
			})
			.appendTo(\'#helf footer\')
			.click(function (e) {
				$.each($(\'#helf textarea\'), function () {
					$(\'<input type="hidden" />\')
						.val($(this).val())
						.attr(\'name\', $(this).attr(\'name\'))
						.appendTo(postmod)
				});
				postmod.submit();
				return false;
			});

		$(\'#helf footer .delete\')
			.val(we_cancel)
			.click(function (e) {
				postmod.submit();
				return false;
			});
	});

	// Clicking anywhere on the page should NOT close the popup.
	$(\'#help_pop\').ready(function (e) {
		$(\'#help_pop\').unbind(\'click\');
	});

	');
			}
		}
	}

	if (!allowedTo('invite_to_topic') && (!empty($_POST['invited_list']) || !empty($_POST['invitee'])))
		$post_errors['cannot_invite'] = 'invitation_only_topics';
}

function invitation_only_topics_post_form_pre()
{
	global $context, $memberContext, $settings, $txt;

	if (empty($_GET['t']))
		fatal_lang_error('no_access', false);

	if (allowedTo('invite_to_topic'))
	{
		loadPluginLanguage('live627:invitation_only_topics', 'InvitationTopics');

		$request = wesql::query('
			SELECT id_member, real_name
			FROM {db_prefix}members
			WHERE id_member IN ({array_int:kicked_ids})',
			array(
				'kicked_ids' => explode(',', $_GET['t']),
			)
		);
		$kicked_members = $kicked_names = array();
		while ($row = wesql::fetch_assoc($request))
			if ($row['id_member'] != we::$id)
				$kicked_members[$row['id_member']] = $row['real_name'];

		foreach ($kicked_members as $kicked_id => $kicked_name)
			if ($kicked_id != we::$id)
				$kicked_names[$kicked_id] = '<a href="<URL>?action=profile;u=' . $kicked_id . '">' . $kicked_name . '</a>';

		loadMemberData(array_keys($kicked_members));

		if (!empty($kicked_members))
		{
			if (!empty($settings['invitationtopics_kick_reason']))
				$post_errors['give_kick_reason'] = array('give_kick_reason', implode(',', $kicked_names));
		}
	}

	$_POST['t'] = sprintf($txt['error_give_kick_reason'], implode(',', $kicked_names));

	$context['help_text'] = '
	<table class="w100 cs3">';

	foreach ($kicked_names as $member => $name)
	{
		loadMemberContext($member);
		$context['help_text'] .= '
		<tr><td class="ava">' . $memberContext[$member]['avatar']['image'] . '</td><td class="link">' . $memberContext[$member]['link'] . '</td><td><textarea name="invitationtopics_kick_reason[' . $member . ']" rows="3" cols="50" class="w100"></textarea></td></tr>';
	}

	$context['help_text'] .= '
	</table>';

	loadTemplate('Help');
	loadLanguage('Help');
	wetem::hide();
	wetem::load('popup');
}

?>