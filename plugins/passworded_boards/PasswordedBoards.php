<?php

if (!defined('WEDGE'))
	die('Hacking attempt...');

function passworded_boards_illegal_guest_perms()
{
	global $context;

	$context['non_guest_permissions'][] = 'passworded_boards_own';
	$context['non_guest_permissions'][] = 'passworded_boards_any';
}

function passworded_boards_admin_areas()
{
	global $admin_areas;

	$admin_areas['plugins']['areas']['passworded_boards']['function'] = 'ModifyPasswordedBoardsSettings';
}

function ModifyPasswordedBoardsSettings($return_config = false)
{
	global $cat_tree, $boards, $boardList, $txt, $context, $settings;

	loadSource(array('ManageServer', 'Subs-Boards'));
	getBoardTree();

	$request = wesql::query('
		SELECT *
		FROM {db_prefix}passworded_boards');
	while ($row = wesql::fetch_assoc($request))
		$settings['board_' . $row['id_board']] = $row['password'];

	$config_vars = array();
 	foreach ($cat_tree as $catid => $tree)
	{
		$config_vars[] = array('title', 'cat_' . $tree['node']['id'], 'text_label' => $tree['node']['name']);
		foreach ($boardList[$catid] as $boardid)
			$config_vars[] = array('text', 'board_' . $boards[$boardid]['id'], '30" placeholder="Password', 'text_label' => '<span style="margin: 0 ' . $boards[$boardid]['level'] . 'em;">' . $boards[$boardid]['name'] . '</span>');
	}

	if ($return_config)
		return $config_vars;

	// Saving?
	if (isset($_GET['save']))
	{
		checkSession();

		foreach ($config_vars as $config_var)
			if (isset($_POST[$config_var[1]]))
			{
				if (trim($_POST[$config_var[1]]) != '')
					wesql::insert('replace',
						'{db_prefix}passworded_boards',
						array('id_board', 'password'),
						array($boards[str_replace('board_', '', $config_var[1])]['id'], sha1($_POST[$config_var[1]])),
						array()
					);
				elseif (!empty($settings[$config_var[1]]))
					wesql::query('
						DELETE FROM {db_prefix}passworded_boards
						WHERE id_board = {int:board}',
						array(
							'board' => $boards[str_replace('board_', '', $config_var[1])]['id'],
						)
					);
			}

		writeLog();
		redirectexit('action=admin;area=passworded_boards');
	}

	$context['post_url'] = '<URL>?action=admin;area=passworded_boards;save';
	wetem::load('show_settings');
	prepareDBSettingContext($config_vars);
}

function passworded_boards_pre_load()
{
	global $context;

	$request = wesql::query('
		SELECT *
		FROM {db_prefix}passworded_boards');
	while ($row = wesql::fetch_assoc($request))
		$context['passworded_boards'][$row['id_board']] = $row['password'];
}

function passworded_boards_menu_items()
{
	global $context, $txt;

	if (isset($context['posts']))
	{
		foreach ($context['posts'] as $counter => $dummy)
		{
			if (!empty($context['passworded_boards'][$context['posts'][$counter]['board']['id']]) && empty($_SESSION['password_set'][$context['posts'][$counter]['board']['id']]) && empty($_POST['hash_passwrd']))
			{
				if (empty($context['disable_login_hashing']))
					$context['main_js_files']['scripts/sha1.js'] = true;

				$context['posts'][$counter]['body'] = '
		<form action="' . $_SESSION['old_url'] . '" method="post" accept-charset="UTF-8" onsubmit="hashLoginPassword(this, \'' . $context['session_id'] . '\');">
			<strong>' . $txt['password'] . ':</strong>
			<input type="password" name="passwrd" id="passwrd" tabindex="1" class="input_password" />
			<div class="right padding">
				<input name="submit" value="' . $txt['save'] . '" class="submit" type="submit" />
			</div>
			<input type="hidden" name="board" value="' . $context['posts'][$counter]['board']['id'] . '" />
			<input type="hidden" name="hash_passwrd" value="" />
			<input type="hidden" name="user" value="" />
		</form>';

				if (isset($context['posts'][$counter]['message']))
					$context['posts'][$counter]['message'] = $context['posts'][$counter]['body'];
			}
		}

		if (isset($_POST['board'], $_POST['hash_passwrd']) && !empty($context['passworded_boards'][$_POST['board']]) && empty($_SESSION['password_set'][$_POST['board']]) && $_POST['hash_passwrd'] == sha1($context['passworded_boards'][$_POST['board']] . $context['session_id']))
		{
			$_SESSION['password_set'][$_POST['board']] = true;
			redirectexit($_SESSION['old_url']);
		}
	}
}

function passworded_boards_messageindex_buttons()
{
	global $board, $board_info, $context, $txt;

	if (!empty($_POST['hash_passwrd']) && $_POST['hash_passwrd'] != sha1($context['passworded_boards'][$board_info['id']] . $context['session_id']))
		$context['post_errors']['passwrd'] = 'password_incorrect';

	if (!empty($_POST['hash_passwrd']) && $_POST['hash_passwrd'] == sha1($context['passworded_boards'][$board_info['id']] . $context['session_id']))
	{
		$_SESSION['password_set'][$board_info['id']] = true;
		redirectexit('board=' . $board_info['id']);
	}

	if (empty($_SESSION['password_set'][$board_info['id']]) && !empty($context['passworded_boards'][$board_info['id']]))
	{
		loadPluginLanguage('live627:passworded_boards', 'PasswordedBoards');
		loadPluginTemplate('live627:passworded_boards', 'PasswordedBoards');
		wetem::load('passwrd');
		$context['page_title'] = $txt['password'] . ' - ' . $board_info['name'];
		$context['redirect_pass'] = 'board=' . $board_info['id'];
	}
}

function passworded_boards_display_main()
{
	global $board, $board_info, $context, $txt, $topic;

	if (!empty($_REQUEST['attach']) && !empty($_REQUEST['topic']))
		if (!empty($context['passworded_boards'][$board_info['id']]) && empty($_SESSION['password_set'][$board_info['id']]) && empty($_POST['hash_passwrd']))
		{
			loadLanguage('Login');
			fatal_lang_error('incorrect_password', false);
		}

	if (!empty($_POST['hash_passwrd']) && $_POST['hash_passwrd'] != sha1($context['passworded_boards'][$board_info['id']] . $context['session_id']))
		$context['post_errors']['passwrd'] = 'password_incorrect';

	if (!empty($_POST['hash_passwrd']) && $_POST['hash_passwrd'] == sha1($context['passworded_boards'][$board_info['id']] . $context['session_id']))
	{
		$_SESSION['password_set'][$board_info['id']] = true;
		redirectexit('topic=' . $topic);
	}

	loadPluginLanguage('live627:passworded_boards', 'PasswordedBoards');
	loadPluginTemplate('live627:passworded_boards', 'PasswordedBoards');
	wetem::load('passwrd');
	$context['page_title'] = $txt['password'] . ' - ' . $board_info['name'];
	$context['redirect_pass'] = 'topic=' . $topic;
}

?>