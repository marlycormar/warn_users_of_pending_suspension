<?php
/**
 * @file
 * Provides ExternalModule class for SuspensionWarning.
 */

namespace SuspensionWarning\ExternalModule;

use ExternalModules\AbstractExternalModule;
use ExternalModules\ExternalModules;
use REDCap;

/**
 * ExternalModule class for SuspensionWarning.
 */
class ExternalModule extends AbstractExternalModule {

	/*
    * @inheritdoc
    */
    function redcap_every_page_top($project_id) {
    	//self::warn_users_account_suspension_cron();
    }

	function warn_users_account_suspension_cron()
	{
		global $suspend_users_inactive_type, $suspend_users_inactive_days,
			   $suspend_users_inactive_send_email, $project_contact_email;

		// If feature is not enabled, then return
		if ($suspend_users_inactive_type == '' || !is_numeric($suspend_users_inactive_days) || $suspend_users_inactive_days < 1) return;

		$days = $this->getSystemSetting('wups_notifications') ?? '1';
		$days = array_map("intval", explode(",", $days));

		$numNotificationsSent = 0;

		foreach($days as $day){
			$sql = "select * from (
					select username, user_email, user_sponsor, user_firstname, user_lastname, user_lastactivity, user_lastlogin,
					(case
					when user_lastactivity is not null and user_lastlogin is not null then greatest(user_lastlogin, user_lastactivity)
					when user_lastactivity is not null then user_lastactivity
					when user_lastlogin is not null then user_lastlogin
					when user_creation is not null then user_creation
					end) as user_last_date
					from redcap_user_information
					where user_suspended_time is null
					) as my_user_info
					where '$suspend_users_inactive_days' - DATEDIFF(NOW(), user_last_date) = '$day';";

			$q = ExternalModules::query($sql);

			while ($row = db_fetch_assoc($q))
			{
				if ($row['user_email'] != '')
				{
					$user_info = [
						'username' => $row['username'],
						'user_firstname' => $row['user_firstname'],
						'user_lastname' => $row['user_lastname'],
						'days_until_suspension' => $day,
						'suspension_date' => date('Y-m-d', strtotime(date("Y-m-d"). ' + '. $day .' days')),
						'to' => $row['user_email']
					];

					if(!self::sendEmail($user_info)){
						//print("Unable to send email to ". $row['user_firstname'].".");
					}
					else
						//print("The message to " .$row['user_firstname']. " was succesfull.");
						$numNotificationsSent++;
				}
			}
		}

		if($numNotificationsSent > 0)
			$GLOBALS['redcapCronJobReturnMsg'] = "$numNotificationsSent warnings of account suspension have been sent.";
	}

	function sendEmail($user_info) {
		$to = $user_info['to'];
		$sender = $project_contact_email ?? 'CTSI-REDCAP-SUPPORT-L@lists.ufl.edu';
		$subject = $this->getSystemSetting("wups_subject");
		$body = $this->getSystemSetting("wups_body");
		$activation_link = APP_PATH_WEBROOT_FULL . "modules/" . basename(dirname(__FILE__)) . "/activate_account.php?username=" . $user_info['username'];

		$piping_pairs = [
			'[username]' => $user_info['username'],
			'[user_firstname]' => $user_info['user_firstname'],
			'[user_lastname]' => $user_info['user_lastname'],
			'[activation_link]' => $activation_link,
			'[days_until_suspension]' => $user_info['days_until_suspension'],
			'[suspension_date]' => $user_info['suspension_date']
		];

		foreach (array_keys($piping_pairs) as $key){
			$body = str_replace($key, $piping_pairs[$key], $body);
		}

		$success = REDCap::email($to, $sender, $subject, $body);

		return $success;
	}
}