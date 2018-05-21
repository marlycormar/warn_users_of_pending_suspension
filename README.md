# REDCap Warn Users of Pending Suspension

A REDCap external module that will warn users of pending suspensions and provide an easy opportunity to extend the life of REDCap accounts.

## Prerequisites
- REDCap >= 8.0.3

## Installation
(Pending)

## Configuration

### Email Configuration Example

- Email Subject: 
    
        Warning of Account Suspension

- Email Body: 
    
        Dear [user_firstname] [user_lastname], <br><br>

        Your account will be suspended in [days_until_suspension] days on [suspension_date]. If you want to avoid account suspension, please go to 
        <a href="[activation_link]">REDCap account extension</a>. <br><br>

        Regards,<br>
        REDCap Support Team.
        
- Days Before Suspension:
    
        10, 12, 20
