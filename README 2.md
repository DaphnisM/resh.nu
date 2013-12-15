# WordPress Ephemeris Calculator

Essentially, this brief plugin provides a JSON interface and shortcode for working with the Swiss Ephemeris C library. **The swetest file must be executable, and PHP must have access to shell_exec on this file.**

## Swiss Ephemeris Library
I'm using the Swiss Ephemeris software under the GPL.

The source can also be retrieved [here](http://www.astro.com/ftp/swisseph/?lang=e).

It's an amazing piece of software and I'm infinitely glad I've found it. They have two license options - one, the GPL, which only means that any code you write must also be GPL. And second, their license, which is paid. As far as I can tell, most natal chart calculators out there use this as their base library.

Long live the GPL! Their default executable, swetest, is perfect for now.

## In WordPress???
This is currently a working WordPress plugin. swetest will need to be chmod +x'd, and PHP needs access to shell_exec.

## Features

* A sortcode to display basic planetary/zodiacal positions for a given or current date.
* A JSON API (wp-admin/admin-ajax.php?action=wpephemeris&date=)

Uses UTF-8 characters for zodiac/planets.