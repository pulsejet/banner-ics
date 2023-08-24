# Roundcube Banner ICS

Display information about events from iCalendar attachments at the top of
the email in Roundcube Webmail

## Installation
To install, get the plugin with composer in your roundcube directory
```
composer require radialapps/banner-ics
```

### Debian package

Alternatively when using the Debian package of roundcube...


- `git clone https://github.com/pulsejet/banner-ics.git /usr/share/roundcube-banner-ics/`

- `ln -s /usr/share/roundcube-banner-ics/ /var/lib/roundcube/plugins/banner_ics`

- `cd /usr/share/roundcube-banner-ics/; composer install`

Add 'banner_ics' to the `$config['plugins']` array in /etc/roundcube/config.inc.php

* Note the underscore in the directoryname contrary to the github repo url. This needs to match with the filename banner_ics.php


## Screenshot
The plugin adds a dynamic calendar icon along with the information of the event
<br/>
<img src="screenshot.png" alt="Screenshot" width="500"/>

## License
Permissively licensed under the MIT license

