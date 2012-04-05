<?php

function template_input_invitation_only_topics()
{
	global $context, $txt;

	echo '
					<dt>
						<span', isset($context['post_error']['cannot_invite']) ? ' class="error"' : '', ' id="caption_subject">', $txt['invite_to_topics'], ':</span>
					</dt>
					<dd>
						<input type="text" id="invitee" name="invitee" tabindex="', $context['tabindex']++, '" maxlength="80" class="w75">
						<dfn>', $txt['invite_to_topics_desc'], '</dfn>
					</dd>';
}

function template_display_invitation_only_topics()
{
	global $context, $txt;

	echo '
	<section>
		<we:title>
			', $txt['invited_participants'], '
		</we:title>
		<p id="invited_participants">', implode(', ', $context['invited_users']), '</p>
	</section>';
}

?>