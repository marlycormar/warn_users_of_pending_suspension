# REDCap Warn Users of Pending Suspension

A REDCap external module that will warn users of pending suspensions and provide an easy opportunity to extend the life of REDCap accounts.

## Prerequisites
- REDCap >= 8.0.3

## Installation
- Clone this repo into to `<redcap-root>/modules/warn_users_of_pending_suspension_v<version_number>`.
- Go to **Control Center > External Modules** and enable Warn Users of Pending Suspension.

## Configuration

The module is configurable at the system level to allow the subject line and body of the message to be customized. The message body supports parameter substitution like REDCap's data piping to allow messages to be customized with fields like `[username]`, `[user_firstname]`, `[user_lastname]`, `[activation_link]`, `[days_until_suspension]` and `[suspension_date]`. The `[activation_link]` is used to prevent account suspension.

### Email Configuration Example

- Email Subject:

        Warning of Account Suspension

- Email Body:

        Dear [user_firstname] [user_lastname], <br><br>

        Your account will be suspended in [days_until_suspension] days on [suspension_date].
        If you want to avoid account suspension, please go to
        <a href="[activation_link]">REDCap account extension</a>. <br><br>

        Regards,<br>
        REDCap Support Team.

- Days Before Suspension:

        10, 12, 20

## Developer testing techniques

To test this feature, you need to turn on a few redcap features it interacts with.  You need to turn on "Auto-suspend users after period of inactivity" in Control Center, User Settings.  For our tests we also set "Period of inactivity" to 30 days.  A lazy developer might just want to run the SQL to make that happen:

    update redcap_config set value="all" where field_name = "suspend_users_inactive_type";
    update redcap_config set value="1" where field_name = "suspend_users_inactive_send_email";
    update redcap_config set value="30" where field_name = "suspend_users_inactive_days";

As this tool sends email, make sure the from address are configured correctly in your redcap system. This tool uses "Email Address of REDCap Administrator" in the REDCap Control Center, General Configuration tab and it's always a good idea to set a valid "universal 'FROM' email address".  Those can be set quickly via SQL if you are so inclined. Here's an example of how a lazy developer at the University of Florida might do that:

    update redcap_config set value="please-do-not-reply@ufl.edu" where field_name = "from_email";
    update redcap_config set value="please-do-not-reply@ufl.edu" where field_name = "project_contact_email";

You'll also need some test users.  To revise the set of test users `alice`, `bob`, `dan`, and `carol` to receive messages based on the above configuration, change their `user_lastlogin` and `user_lastactivity` dates as follows:

    update redcap_user_information set user_lastlogin = date_add(now(), interval -22 day), user_lastactivity = date_add(now(), interval -10 day) where username='alice';
    update redcap_user_information set user_lastlogin = date_add(now(), interval -18 day), user_lastactivity = NULL where username='bob';
    update redcap_user_information set user_lastlogin = date_add(now(), interval -10 day), user_lastactivity = date_add(now(), interval -25 day) where username='carol';
    update redcap_user_information set user_lastlogin = null, user_lastactivity = date_add(now(), interval -20 day) where username='dan';

    update redcap_user_information set user_email = 'you@example.org' where username in ("alice", "bob", "carol", "dan");

When tested, each of the aforementioned users should get a message. FYI, the above set of test users can be created via the SQL file at https://github.com/ctsit/redcap_deployment/blob/master/deploy/files/test_with_table_based_authentication.sql

The final step to facilitate testing is to turn the frequency of the cron job up.  You can change `cron_frequency` and `cron_max_run_time` in the **config.json** file so that the cron job runs more often. For example, with the configuration `"cron_frequency": "60"` and `"cron_max_run_time": "10"`, the cron job will run every minute (60s) with a maximum run time of 10s. Then disable and re-enable the `Warn Users of Pending Suspension` module to write these new values into the cron database entry. Of course there's the lazy developers SQL method as well.

    update redcap_crons set cron_frequency = 60, cron_max_run_time = 10 where cron_name = "warn_users_account_suspension_cron";

Revert the setting to the defaults with this SQL when you are done with testing:

    update redcap_crons set cron_frequency = 86400, cron_max_run_time = 1200 where cron_name = "warn_users_account_suspension_cron";
