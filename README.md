# time-zone-select
Creating a time zone select in PHP can get unruly quickly, as DateTimeZone::listIdentifiers() returns over 400 Zones and Links. This is an attempt to take a page from Ruby on Rails' ActiveSupport::TimeZone time_zone_select(), largely by using their curated list of about 140 Zones, and also by allowing for 'priority_zones' to be at the top of the list.

This repo involves occasionally scraping the rails repo. Here is the ticket asking Rails to make their data file more easily accessable: https://github.com/rails/rails/issues/22088
It would be much easier if PHP had a similarly curated list inside of PHP. Here's the request to them for that: https://bugs.php.net/bug.php?id=70801
