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
    	self::warn_users_account_suspension_cron();
    }

	/* handle users that have expiration dates: which of the two have priority*/
	public function warn_users_account_suspension_cron()
	{
		global $project_contact_email, $lang, $auth_meth_global, $suspend_users_inactive_type, $suspend_users_inactive_days,
			   $suspend_users_inactive_send_email;

		// If feature is not enabled, then return
		if ($suspend_users_inactive_type == '' || !is_numeric($suspend_users_inactive_days) || $suspend_users_inactive_days < 1) return;

		# find a way of setting this that is more accurate, i.e., such that it is always less than suspend_users_inactive_days
		$number_of_days_before_notifications_start = min(30, $suspend_users_inactive_days);

		print("The number of days before notifications start " .$number_of_days_before_notifications_start. ".");

		// Initialize count
		$numUsersEmailed = 0;
		$today = date("Y-m-d");
		$x_days_from_now = date("Y-m-d", mktime(date("H"),date("i"),date("s"),date("m"),date("d")+$days_before_expiration,date("Y")));
		// Instantiate email object
		// Query users that wille expire *exactly* x days from today (since this will only run once per day)
		$sql = "select username, user_email, user_sponsor, user_firstname, user_lastname, user_lastactivity, user_lastlogin 
				from redcap_user_information where user_suspended_time is null 
				and (user_lastactivity is not null and DATEDIFF(NOW(), user_lastactivity) >= '$number_of_days_before_notifications_start') 
				and (user_lastlogin is not null and DATEDIFF(NOW(), user_lastlogin) >= '$number_of_days_before_notifications_start');";
		
		$q = ExternalModules::query($sql);
		$numUsersEmailed += db_num_rows($q);


		while ($row = db_fetch_assoc($q))
		{

			$most_recent_access_date = max($row['user_lastlogin'], $row['user_lastactivity']);
			$days_passed = date("Y-m-d h:i:s") - $most_recent_access_date;

			// Email the user to warn them every 5 days and 2 days before the account will be suspended
			if ($row['user_email'] != '' && ($days_passed % 5 == 0 || $days_passed < 2))
			{

				$user_info = [
					'username' => $row['username'],
					'user_firstname' => $row['user_firstname'],
					'user_lastname' => $row['user_lastname'],
					'redcap_base_url' => $row['username'],
					'days_until_suspension' => $row['username'],
					'suspension_date' => $row['username']
				];

				// Set date and time x days from now
				$mktime = strtotime($row['user_expiration']);
				$x_days_from_now_friendly = date("l, F j, Y", $mktime);
				$x_time_from_now_friendly = date("g:i A", $mktime);
				// Determine if user has a sponsor with a valid email address
				$hasSponsor = false;
				if ($row['user_sponsor'] != '') {
					// Get sponsor's email address
					$sponsorUserInfo = User::getUserInfo($row['user_sponsor']);
					if ($sponsorUserInfo !== false && $sponsorUserInfo['user_email'] != '') {
						$hasSponsor = true;
					}
				}
				// Send email to user and/or user+sponsor
				if (!$hasSponsor) {
				} else {
				}
				// Send the email
				#$email->setTo($row['user_email']);

				if(!self::sendEmail($project_contact_email, $user_info))
				{
					print("This message was not succesfull.");
				}
				else
					print("This message was succesfull.");
			}
			break;
		}
		
		// Set cron job message
		if ($numUsersEmailed > 0) {
			$GLOBALS['redcapCronJobReturnMsg'] = "$numUsersEmailed users were emailed to warn them of their upcoming account expiration";
		}
	}

	function sendEmail($project_contact_email, $user_info) {
		$to = 'marlycormar@ufl.edu';
		$cc = $this->getSystemSetting("wups_cc");
		$subject = $this->getSystemSetting("wups_subject");
		$body = $this->getSystemSetting("wups_body");

		$piping_pairs = [
			'[username]' => $user_info['username'], 
			'[user_firstname]' => $user_info['user_firstname'], 
			'[user_lastname]' => $user_info['user_lastname'], 
			'[redcap_base_url]' => $user_info['redcap_base_url'], 
			'[days_until_suspension]' => $user_info['days_until_suspension'], 
			'[suspension_date]' => $user_info['suspension_date']
		];

		foreach (array_keys($piping_pairs) as $key){
			$body = str_replace($key, $piping_pairs[$key], $body);
		}

		$sender = $project_contact_email;
		$success = REDCap::email($to, $sender, $subject, $body . 'something3');

		return $success;
	}
}