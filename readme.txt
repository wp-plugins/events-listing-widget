=== Events Listing Widget ===
Contributors: jackdewey
Donate link: http://ylefebvre.ca/wordpress-plugins/events-listing-widget
Tags: events, widget, upcoming, sidebar
Requires at least: 3.3
Tested up to: 4.2
Stable tag: trunk

Create a list of upcoming events and display them using an easy-to-use widget

== Description ==

Create a list of upcoming events and display them using an easy-to-use widget

* [Support Forum](http://wordpress.org/tags/events-listing-widget)

== Installation ==

1. Download the plugin
1. Upload the extracted folder to the /wp-content/plugins/ directory
1. Activate the plugin in the Wordpress Admin
1. Create new events under the Events Listing section of the administration area
1. Create an instance of the Events Listing widget to display entries, configuring how many entries should be shown and how far ahead the widget should look to display entries.

== Changelog ==

= 1.2.5 =
* Fix for some of the date formats that were not working correctly in admin interface

= 1.2.4 =
* Fixed bug with event URL not appearing when choosing for event titles to be hyperlinked. Bug introduced in version 1.2.2

= 1.2.3 =
* Fixed issue with events not always appearing in chronological order

= 1.2.2 =
* Added support for dd.mm.yyyy date format
* Re-worked widget rendering algorithm to use WP_Query instead of custom SQL query

= 1.2.1 =
* Added new field to specify end date on events
* Modified event display code to display event until end date has passed
* Added shortcode to display new end date ([events-listing-end-date])

= 1.2 =
* Adds internationalization support

= 1.1.9 =
* Fixed PHP warnings

= 1.1.8 =
* Added shortcodes to display some of the data fields from the event listing ([events-listing-date],[events-listing-name], [events-listing-url])

= 1.1.7 =
* Updated jQuery datepicker plugin to resolve javascript error in latest versions of WordPress

= 1.1.6 =
* Fixes problem with disappearing events when too many are created

= 1.1.5 =
* Fixed two notices about undefined variables

= 1.1.4 =
* Fixed gmmktime function error displayed when creating new events, new posts and new pages
* Restored cut-off period after n number of months

= 1.1.3 =
* Fixed problem with events not being displayed if they did not have hyperlinks associated with them
* Fixed further problems with event removal time

= 1.1.2 =
* Fixed problem with events being removed from listing at wrong time
* Added new date format MM-DD-YYYY
* Added option for links not to have hyperlinks

= 1.1.1 =
* Added new field to store event URL
* Widget will attach event URL to event name if present, instead of event page
* New options to specify text / HTML to be displayed before and after date

= 1.1 =
* Added an option to change the date format (send requests if you would like other formats)

= 1.0 =
* First version

== Frequently Asked Questions ==

None at this time

== Screenshots ==

None at this time