<?php
/**
  * make-levees-files.php
  *
  * A PHP script to take an SQLite table of levee information and turn
  * it into GeoJSON, JSON+LD, HTML and iCalendar files.
  *
  * Requires eluceo — iCal -- install with:
  *
  * composer require eluceo/ical
  *
  * This code is released under the MIT license.
  *
  * Permission is hereby granted, free of charge, to any person obtaining
  * a copy of this software and associated documentation files (the "Software"),
  * to deal in the Software without restriction, including without
  * limitation the rights to use, copy, modify, merge, publish, distribute,
  * sublicense, and/or sell copies of the Software, and to permit persons
  * to whom the Software is furnished to do so, subject to the following conditions:
  *
  * The above copyright notice and this permission notice shall be
  * included in all copies or substantial portions of the Software.
  *
  * This program is distributed in the hope that it will be useful, but
  * WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
  *
  * @version 1.6, December 9, 2025
  * @link https://github.com/reinvented/levees
  * @author Peter Rukavina <peter@rukavina.net>
  * @copyright Copyright &copy; 2018, Reinvented Inc.
  * @license hhttps://opensource.org/licenses/MIT MIT license
  */

// Required for iCalendar creation; you must install "eluceo — iCal" as above.
require_once 'vendor/autoload.php';
use Eluceo\iCal\Domain\Entity\Calendar;
use Eluceo\iCal\Domain\Entity\Event;
use Eluceo\iCal\Domain\ValueObject\Location;
use Eluceo\iCal\Domain\ValueObject\TimeSpan;
use Eluceo\iCal\Domain\ValueObject\DateTime;

// Set the default time zone.
date_default_timezone_set("America/Halifax");

// Create a new iCalendar object.
$vCalendar = new Calendar();

// We're going to create four files; first we define them.
$file['json+ld']  = "levees.json";
$file['geojson']  = "levees.geojson";
$file['geojson-charlottetown']  = "levees-charlottetown.geojson";
$file['html']     = "levees.html";
$file['ics']      = "levees.ics";

list($fp, $content) = openFiles($file);

// Open the SQLite3 database that stores levee information.
$db = new SQLite3('data/levees.sqlite');

// Initialize a counter that we can use for the GeoJSON marker-symbol property.
$counter = 1;
$charlottetown_counter = 1;

// Initialize an array to hold output
$content = [];
$content['html'] = '';

// Retrieve all the levees
$results = $db->query('SELECT * FROM levees where active = 1 order by startDate, endDate, name');

while ($row = $results->fetchArray(SQLITE3_ASSOC)) {

  if (!$row['cancelled']) {
    // Create JSON+LD for this levee.
    $content['json+ld'][] = makeJSONLD($row);

    // Create GeoJSON for this levee.
    $content['geojson']['features'][] = makeGeoJSON($row, $counter);

    // Create GeoJSON for this levee - only if it's in Charlottetown.
    if ($row['charlottetownarea']) {
      $content['geojson-charlottetown']['features'][] = makeGeoJSON($row, $charlottetown_counter);
      $charlottetown_counter++;
    }

    // Create iCalendar for this levee.
   $vCalendar->addEvent(makeICalendar($row));

  }

  // Create HTML for this levee.
  $content['html'] .= makeHTML($row);

  // Increment the counter.
  $counter++;
}

// Add to the GeoJSON object to make it valid.
$content['geojson']['type'] = "FeatureCollection";

// Add to the GeoJSON object to make it valid.
$content['geojson-charlottetown']['type'] = "FeatureCollection";

// Write the JSON+LD data
fwrite($fp['json+ld'], json_encode($content['json+ld'], JSON_PRETTY_PRINT));

// Write the GeoJSON data
fwrite($fp['geojson'], json_encode($content['geojson'], JSON_PRETTY_PRINT));

// Write the GeoJSON data
fwrite($fp['geojson-charlottetown'], json_encode($content['geojson-charlottetown'], JSON_PRETTY_PRINT));

// Write the HTML data
fwrite($fp['html'], file_get_contents('html/preamble.html'));
fwrite($fp['html'], makeHTMLheader());
fwrite($fp['html'], $content['html']);
fwrite($fp['html'], "\t" . '</tbody>' . "\n" . '</table>' . "\n");
fwrite($fp['html'], file_get_contents('html/midsection.html'));
fwrite($fp['html'], file_get_contents('result/levees.json'));
fwrite($fp['html'], file_get_contents('html/end.html'));


// Wrhite the iCalendar data
$iCalendarComponent = (new \Eluceo\iCal\Presentation\Factory\CalendarFactory())->createCalendar($vCalendar);
fwrite($fp['ics'], (string) $iCalendarComponent);

closeFiles($file, $fp);

function openFiles($file) {
  // Create an array of file pointers and an array of contents.
  $fp = array();
  $content = array();
  // Open the files we defined earlier.
  foreach($file as $filetype => $filename) {
    $fp[$filetype] = fopen("result/" . $filename, 'w');
  }
  return array($fp, $content);
}

function closeFiles($file, $fp) {
  foreach($file as $type => $filename) {
    fclose($fp[$type]);
  }
}

function makeJSONLD($row) {
  $tmp = array();
  $tmp['@context'] = "http://schema.org";
  $tmp['@type'] = "Event";
  $tmp['name'] = $row['name'] . " 2025 New Years Levee";
  $tmp['startDate'] = $row['startDate'];
  $tmp['endDate'] = $row['endDate'];
  $tmp['location'] = array();
  $tmp['location']['@type'] = "Place";
  $tmp['location']['name'] = $row['location_name'];
  $tmp['location']['address'] = $row['location_address'];
  $tmp['location']['geo'] = array();
  $tmp['location']['geo']['@type'] = "GeoCoordinates";
  $tmp['location']['geo']['latitude'] = $row['latitude'];
  $tmp['location']['geo']['longitude'] = $row['longitude'];
  return $tmp;
}

function makeGeoJSON($row, $counter) {
  $tmp = array();
  $tmp['type'] = "Feature";
  $tmp['geometry'] = array();
  $tmp['geometry']['type'] = "Point";
  $tmp['geometry']['coordinates'] = array($row['longitude'], $row['latitude']);
  $tmp['properties'] = array();
  $tmp['properties']['name'] = $row['name'] . " 2025 New Years Levee";
  $tmp['properties']['location'] = $row['location_name'];
  $tmp['properties']['address'] = $row['location_address'];
  $tmp['properties']['startDate'] = $row['startDate'];
  $tmp['properties']['endDate'] = $row['endDate'];
  $tmp['properties']['marker-symbol'] = $counter;
  return $tmp;
}

function makeHTMLheader() {
  return "<table class='levees datatable'>\n\t<thead>\n\t\t<tr>\n\t\t\t<th class='levee_name'>Organization</th>\n\t\t\t<th class='levee_address'>Location</th>\n\t\t\t<th class='levee_start'>Starts</th>\n\t\t\t<th class='levee_end'>Ends</th>\n\t\t\t<th class='levee_accessible'>♿<span class='levee_accessible_title'> Accessible</span></th>\n\t\t\t<th class='levee_allages'><span class='levee_allages_title'>All Ages</span></th>\n\t\t</tr></thead><tbody>\n";
}

function makeHTML($row) {
  $start_number = strtotime($row['startDate']);
  $end_number = strtotime($row['endDate']);
  $tmp = '';

  $classes = array();
  if ($row['charlottetownarea']) {
    $classes[] = 'charlottetown';
  }
  else {
    $classes[] = 'notcharlottetown';
  }
  if ($row['allages']) {
    $classes[] = 'allages';
  }
  else {
    $classes[] = '19plus';
  }
  if ($row['cancelled']) {
    $classes[] = 'cancelled';
  }
  $allclasses = implode(' ', $classes);

  $tmp .= "\t\t" . '<tr class="' . $allclasses . '">' . "\n";
  $tmp .= "\t\t\t" . '<td class="levee_name"><a href="http://www.openstreetmap.org/search?query=' . $row['latitude'] . "," . $row['longitude'] . '#map=19/' . $row['latitude'] . '/' . $row['longitude'] . '">' . $row['name'] . '</a></td>' . "\n";
  $tmp .= "\t\t\t" . '<td class="levee_address">' . $row['location_name']  . "<br><span class='levee_street'>" . $row['location_address'] . '</span></td>' . "\n";
  $tmp .= "\t\t\t" . '<td class="levee_start">' . strftime_compat("%l:%M %p", $start_number) . '</td>'. "\n";
  $tmp .= "\t\t\t" . '<td class="levee_end">' . strftime_compat("%l:%M %p", $end_number) . '</td>'. "\n";
  if ($row['accessible']) {
    $tmp .= "\t\t\t" . '<td class="levee_accessible">Yes</td>'. "\n";
  }
  else {
    $tmp .= "\t\t\t" . '<td class="levee_accessible"><b>No</b></td>'. "\n";
  }
  if ($row['allages']) {
    $tmp .= "\t\t\t" . '<td class="levee_allages">Yes</td>'. "\n";
  }
  else {
    $tmp .= "\t\t\t" . '<td class="levee_allages"><b>No</b></td>'. "\n";
  }
  $tmp .= "\t\t" . '</tr>' . "\n";
  return $tmp;
}

function makeICalendar($row) {
  $vEvent = new Eluceo\iCal\Domain\Entity\Event();
  $vEvent->setSummary($row['name'] . " 2025 New Years Levee");
  $vEvent->setLocation(new Location($row['location_name'] . "\n" . $row['location_address'], $row['location_name'], $row['latitude'] . "," . $row['longitude']));
  $start = new DateTime(DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['startDate']), false);

  $end   = new DateTime(DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $row['endDate']) , false);
  $occurrence = new TimeSpan($start, $end);
  $vEvent->setOccurrence($occurrence);
  // $vEvent->setNoTime(false);
  // $vEvent->setUseTimezone(true);
  return $vEvent;
}

function strftime_compat($format, $timestamp = null) {
    // Use current timestamp if none is provided
    $timestamp = $timestamp ?? time();

    // Mapping of strftime format specifiers to date() equivalents
    $translations = [
        '%a' => 'D',  // Abbreviated weekday name
        '%A' => 'l',  // Full weekday name
        '%b' => 'M',  // Abbreviated month name
        '%B' => 'F',  // Full month name
        '%c' => 'r',  // Locale's date and time (12-hour format)
        '%d' => 'd',  // Day of the month (01-31)
        '%e' => 'j',  // Day of the month (1-31, no leading zero)
        '%H' => 'H',  // Hour (00-23)
        '%I' => 'h',  // Hour (01-12)
        '%l' => 'g',  // Hour (01-12)
        '%j' => 'z',  // Day of the year (001-366)
        '%m' => 'm',  // Month (01-12)
        '%M' => 'i',  // Minute (00-59)
        '%p' => 'A',  // AM/PM
        '%S' => 's',  // Second (00-59)
        '%U' => 'W',  // Week number of the year (Sunday as first day of week)
        '%w' => 'w',  // Day of the week (0-6, Sunday is 0)
        '%x' => 'm/d/y', // Locale's date
        '%X' => 'H:i:s', // Locale's time
        '%y' => 'y',  // Year without century (00-99)
        '%Y' => 'Y',  // Year with century
        '%Z' => 'T',  // Time zone abbreviation
        '%z' => 'O',  // Time zone offset
        '%%' => '%',  // Literal '%'
    ];

    // Replace strftime specifiers with date() equivalents
    $dateFormat = strtr($format, $translations);

    // Use PHP's date() function to format the timestamp
    return date($dateFormat, $timestamp);
}
