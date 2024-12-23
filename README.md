# levees

For the last 20 years (with years off for COVID in 2021 and 2022) I've been maintaining a web schedule of the New Years Day levees held annually in Charlottetown, Prince Edward Island, Canada and area on my blog, [ruk.ca](https://ruk.ca).

This year's version is online at:

[https://ruk.ca/levee-2025](https://ruk.ca/levee-2025)

In the early days, I maintained the data for the levees as a simple HTML table. As the list grew to include more levees than ever, and with a desire to emit the schedule data in a variety of forms, I began to maintain the schedule in an SQLite database, and to use a simple PHP script, included here, to generate derivatives:

* [HTML](result/levees.html) for the web page itself.
* [GeoJSON](result/levees.geojson) for mapping the levees.
* [GeoJSON](result/levees-charlottetown.geojson) for mapping the levees for Charlottetown-area only.
* [JSON+LD](result/levees.json) for embedded in the web page for Google Structured Data purposes.
* [iCalendar](result/levees.ics) to allow the schedule to be imported into desktop and mobile calendars.

## Dependencies

PHP 5+ with support installed for [SQLite3](http://ca.php.net/manual/en/book.sqlite3.php)

For making iCalendar files, [eluceo/ical](https://github.com/markuspoerschke/iCal)

## Changelog

### Notes for 2023
* Code will need to be updated for PHP 9 -- strftime() is deprecated.
### New for 2019
* Added a column to the SQLite table to record whether levees are "all ages" (accessible to all people) or not.
* Code cleanup to support PHP 7.1.
* Generate a GeoJSON file for Charlottetown-area levees only, in addition to the one that includes all levees.

## License

This code is released under the MIT license.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
