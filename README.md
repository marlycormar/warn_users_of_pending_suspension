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

To revise the set of test users `alice`, `bob`, `dan`, and `carol` to receive messages based on the above configuration, change their `user_lastlogin` and `user_lastactivity` dates as follows:

    update redcap_user_information set user_lastlogin = date_add(now(), interval -22 day), user_lastactivity = date_add(now(), interval -10 day) where username='alice';
    update redcap_user_information set user_lastlogin = date_add(now(), interval -18 day), user_lastactivity = NULL where username='bob';
    update redcap_user_information set user_lastlogin = date_add(now(), interval -10 day), user_lastactivity = date_add(now(), interval -25 day) where username='carol';
    update redcap_user_information set user_lastlogin = null, user_lastactivity = date_add(now(), interval -20 day) where username='dan';

    update redcap_user_information set user_email = 'you@example.org' where username in ("alice", "bob", "carol", "dan");

When tested, each of four users should get a message.  To trigger the cron job, uncomment this line in ExternalModule.php:

    function redcap_every_page_top($project_id) {
        //self::warn_users_account_suspension_cron();
    }
