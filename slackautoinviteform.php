<?php
/*
Plugin Name: Slack AutoInvite Form Plugin
Plugin URI:  http://bio
Description: An auto-invite tool to get people on slack.
Version:     1.0
Author:      Michael Smith
Author URI:  http://biohackspace.org/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html


Slack Auto Invite Plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.
 
Slack Auto Invite Plugin is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
 
You should have received a copy of the GNU General Public License
along with Slack Auto Invite Plugin. If not, see https://www.gnu.org/licenses/gpl-2.0.html
*/


add_action('admin_menu','slackautoinviteform_admin_actions');
function slackautoinviteform_admin_actions() {
		add_options_page('SlackAutoInvitePlugin','SlackAutoInvitePlugin','manage_options',__FILE__,'slackautoinviteform_admin');
}

function slackautoinviteform_admin()
{
	if (!current_user_can('manage_options')){
		wp_die(__('You do not have sufficient permissions to access this page.'));
	}
?>
	<div class="wrap">
		<h4>Slack Auto Invite Plugin</h4>
		<form action="" method="post"> 
<?php
	if (isset($_POST['save_settings']))
	{
		if (isset($_POST['slackautoinviteform_slackapi_key'])) {
			$slackautoinviteform_slackapi_key = $_POST['slackautoinviteform_slackapi_key'];
		update_option('slackautoinviteform_slackapi_key',$slackautoinviteform_slackapi_key); // store in wp options table
		}
		if (isset($_POST['slackautoinviteform_slack_site'])) {
			$slackautoinviteform_slack_site = $_POST['slackautoinviteform_slack_site'];
		update_option('slackautoinviteform_slack_site',$slackautoinviteform_slack_site); // store in wp options table
		}
	}
	?><table class="widefat">
			<thead>
				<tr>
					<th>Slack API Token </th>
					<th><input type="text" name="slackautoinviteform_slackapi_key" value="<?php
					if(get_option('slackautoinviteform_slackapi_key')) {
						$slackapi_key = get_option('slackautoinviteform_slackapi_key');
						echo $slackapi_key;
					} ?>"></th>
				</tr>
				<tr>
					<th>Slack Website</th>
					<th><input type="text" name="slackautoinviteform_slack_site" value="<?php
					if(get_option('slackautoinviteform_slack_site')) {
						$slack_site = get_option('slackautoinviteform_slack_site');
						echo $slack_site;
					} ?>"></th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th><input type="submit" size="50" name="save_settings" value="Save Settings" class="button-primary" /></th>
				</tr>
			</tfoot>
		<tbody>
		</tbody>
		</form>
	</div>
<?php
}


function slackautoinviteform_shortcode()
{
	$form_details = "<table class='widefat'>";
	if (isset($_POST['request_invite']))
	{
		if (isset($_POST['slackautoinvite_email']))
		{
			$email = $_POST['slackautoinvite_email'];
				// Validate e-mail
			if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) 
			{
				echo "{$email} is not a valid email address";
				$form_details .= "<form action='". str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) ."'  method='post'>";

				$form_details .= "<tr><th>Email :</<th>";
				$form_details .= "<th><input type='text' name='slackautoinvite_email' value=''></th></tr>";
				$form_details .= "<tr><th><input type=submit size='50' name='request_invite' value='Request Slack Invite' class='button-primary' /></th></tr>";
				$form_details .= "</table></form>";
			} else 
			{

			    $SLACK_URL = get_option('slackautoinviteform_slack_site');
				$SLACK_URL.="/api/users.admin.invite";
				$SLACK_API_TOKEN = get_option('slackautoinviteform_slackapi_key');

				//$response = wp_remote_retrieve_body($SLACK_URL."?email=".$email."&token".$SLACK_API_TOKEN."&set_active=true");			    
				
				$response = wp_remote_post( $SLACK_URL, array(
					'method' => 'POST',
					'timeout' => 45,
					'redirection' => 5,
					'httpversion' => '1.0',
					'blocking' => true,
					'headers' => array(),
					'body' => array( 'token' => $SLACK_API_TOKEN, 'email' => $email, 'set_active' => 'true'),
					'cookies' => array()
				    )
				);

				if ( is_wp_error( $response ) ) 
				{
				   $error_message = $response->get_error_message();
				   echo "Something went wrong: $error_message";
				} 
				else 
				{
				   echo 'Response:<pre>';
				   //print_r( $response['body'] );
				   $response_body = json_decode($response['body']);
				   $return_type = $response_body->ok;
				   //echo "json now:\n";
				   //print_r($response_body);
				   if ($response_body->ok == true)
				   {
				   		echo "Invite has been sent, please check {$email} and any spam folders.";
				   }
				   else
				   {
				    		echo $response_body->error;
				   }
				   echo '</pre>';
				}
			//echo "<!-- ".$SLACK_URL."?email=".$email."&token".$SLACK_API_TOKEN."&set_active=true"."  -->\n\n";
			}
		}
		else
		{
			echo "You need to enter an email address to receive an invite.";
			$form_details .= "<form action='". str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) ."'  method='post'>";

			$form_details .= "<tr><th>Email :</<th>";
			$form_details .= "<th><input type='text' name='slackautoinvite_email' value=''></th></tr>";
			$form_details .= "<tr><th><input type=submit size='50' name='request_invite' value='Request Slack Invite' class='button-primary' /></th></tr>";
			$form_details .= "</table></form>";
		}

	}
	else
	{
		$form_details .= "<form action='". str_replace( '%7E', '~', $_SERVER['REQUEST_URI']) ."'  method='post'>";

		$form_details .= "<tr><th>Email :</<th>";
		$form_details .= "<th><input type='text' name='slackautoinvite_email' value=''></th></tr>";
		$form_details .= "<tr><th><input type=submit size='50' name='request_invite' value='Request Slack Invite' class='button-primary' /></th></tr>";
		$form_details .= "</table></form>";
	}

	return $form_details;
}

// function slackautoinviteform_intercept_form_input() 
// {
// 	if (isset($_POST['request_invite']))
// 	{
// 		echo "do_invite";
// 	}
// }

//add_action('init', 'slackautoinviteform_intercept_form_input');

add_shortcode('slackautoinviteform','slackautoinviteform_shortcode');
?>