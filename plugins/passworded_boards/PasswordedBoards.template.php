<?php
// Version: 1.0; PasswordedBoards

function template_passwrd()
{
	global $context, $txt;

	if (empty($context['disable_login_hashing']))
		$context['main_js_files']['scripts/sha1.js'] = true;

	echo '
		<form action="<URL>?', $context['redirect_pass'], '" method="post" accept-charset="UTF-8" name="postmodify"  ', empty($context['disable_login_hashing']) ? ' onsubmit="hashLoginPassword(this, \'' . $context['session_id'] . '\');"' : '', '>
			<we:cat>
				', $context['page_title'], '
			</we:cat>
				<div class="roundframe">';

	// If an error occurred, explain what happened.
	if (!empty($context['post_errors']))
	{
		echo '
					<div id="profile_error">';

		foreach ($context['post_errors'] as $error)
			echo '
						', $txt[$error];

		echo '
					</div>';
	}

	echo '
					<we:title2>', $txt['password'], ':</we:title2>
					<input type="password" name="passwrd" id="passwrd" tabindex="1" class="input_password" autofocus size="30" />
					<div class="right padding">
						<input name="submit" value="', $txt['save'], '" class="submit" type="submit" />
					</div>
				<input type="hidden" name="user" value="" />
				<input type="hidden" name="hash_passwrd" value="" />
				</div>
			</form>';
}

?>