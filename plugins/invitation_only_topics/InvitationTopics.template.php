<?php

function template_input_invitation_only_topics()
{
	global $context, $txt;

	echo '
					<hr style="height: 0; margin: 4px">
					<strong><span', isset($context['post_error']['cannot_invite']) ? ' class="error"' : '', ' id="caption_iinvitee">', $txt['invite_to_topics'], ':</span></strong>
					<hr style="height: 0; margin: 4px">
					<input type="text" id="invitee" name="invitee" tabindex="', $context['tabindex']++, '" maxlength="80" class="w75">
					<dfn>', $txt['invite_to_topics_desc'], '</dfn>';
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