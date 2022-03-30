### 3.8.0 ###

* Stop using httpswwwroot removed in Moodle 3.8
* PHPUnit tests updated to work on recent versions.

### 3.7.3 ###

* Content submitted by admins or guests is ignored during the auto-detection
  (MDLSITE-5938).
* The reason for the automatic user suspension now recorded in the suspended user
  profile description (MDLSITE-5938).

### 3.7.2 ###

* Fixed the Delete user and save content for akismet report feature (MDLSITE-5851).
* Slight improvement of how buttons are shown on the reports page.

### 3.7.1 ###

* Make it use the new forum 3.7 rendering mechanisms (credit goes to Andrew Nicols for
  help).

### 3.7 ###

* Modify the Forum report button to use correct classes

### 3.6.1 ###

* Added protection for submitted comments.
* Added display of error codes allowing us to see the reason for flagging the content.

### v0.3 ###

* User's first posts at the site are penalised neither by links to other pages
  at that site nor by embedded media.
* Even accounts older than one month can now be suspended for spamming.

### v0.2 ###

* Detect spammer signups in cron
* Add notifications for spam reports
* Add spam link to (some) comment fields (doesn't work universally)

