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
  * @version 0.1, June 20, 2012
  * @link https://github.com/reinvented/levees
  * @author Peter Rukavina <peter@rukavina.net>
  * @copyright Copyright &copy; 2015, Reinvented Inc.
  * @license hhttps://opensource.org/licenses/MIT MIT license
  */

// Required for iCalendar creation; you must install "eluceo — iCal" as above.
require_once 'vendor/autoload.php';

// Set the default time zone.
date_default_timezone_set("America/Halifax");

// Create a new iCalendar object.
$vCalendar = new \Eluceo\iCal\Component\Calendar('ruk.ca/levee-2016');

// We're going to create four files; first we define them.
$file['json+ld']  = "levees.json";
$file['geojson']  = "levees.geojson";
$file['html']     = "levees.html";
$file['ics']      = "levees.ics";

list($fp, $content) = openFiles($file);

// Open the SQLite3 database that stores levee information.
$db = new SQLite3('data/levees.sqlite');

// Initialize a counter that we can use for the GeoJSON marker-symbol property.
$counter = 1;

// Retrieve all the levees
$results = $db->query('SELECT * FROM levees order by startDate, endDate, name');

while ($row = $results->fetchArray(SQLITE3_ASSOC)) {

  // Create JSON+LD for this levee.
  $content['json+ld'][] = makeJSONLD($row);

  // Create GeoJSON for this levee.
  $content['geojson']['features'][] = makeGeoJSON($row, $counter);

  // Create HTML for this levee.
  $content['html'] .= makeHTML($row);

  // Create iCalendar for this levee.
  $vCalendar->addComponent(makeICalendar($row));

  // Increment the counter.
  $counter++;
}

// Add to the GeoJSON object to make it valid.
$content['geojson']['type'] = "FeatureCollection";

// Write the JSON+LD data
fwrite($fp['json+ld'], json_encode($content['json+ld'], JSON_PRETTY_PRINT));

// Write the GeoJSON data
fwrite($fp['geojson'], json_encode($content['geojson'], JSON_PRETTY_PRINT));

// Write the HTML data
fwrite($fp['html'], makeHTMLheader());
fwrite($fp['html'], $content['html']);
fwrite($fp['html'], "\t" . '</tbody>' . "\n" . '</table>' . "\n");

// Wrhite the iCalendar data
fwrite($fp['ics'], $vCalendar->render());

closeFiles($file, $fp);

function openFiles($file) {
  // Create an array of file pointers and an array of contents.
  $fp = array();
  // Open the files we defined earlier.
  foreach($file as $filetype => $filename) {
    $fp[$filetype] = fopen("result/" . $filename, 'w');
    $content[$filetype] = '';
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
  $tmp['name'] = $row['name'] . " 2016 New Years Levee";
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
  $tmp['properties']['name'] = $row['name'] . " 2016 New Years Levee";
  $tmp['properties']['address'] = $row['location_address'];
  $tmp['properties']['startDate'] = $row['startDate'];
  $tmp['properties']['endDate'] = $row['endDate'];
  $tmp['properties']['marker-symbol'] = $counter;
  return $tmp;
}

function makeHTMLheader() {
  return "<table class='levees datatable'>\n\t<tbody>\n\t\t<tr>\n\t\t\t<th class='levee_name'>Organization</th>\n\t\t\t<th class='levee_address'>Location</th>\n\t\t\t<th class='levee_start'>Starts</th>\n\t\t\t<th class='levee_end'>Ends</th>\n\t\t\t<th class='levee_accessible'>♿<span class='levee_accessible_title'> Accessible</span></th>\n\t\t</tr>\n";
}

function makeHTML($row) {
  $start_number = strtotime($row['startDate']);
  $end_number = strtotime($row['endDate']);
  $tmp = '';
  $tmp .= "\t\t" . '<tr>' . "\n";
  $tmp .= "\t\t\t" . '<td class="levee_name"><a href="http://www.openstreetmap.org/search?query=' . $row['latitude'] . "," . $row['longitude'] . '#map=19/' . $row['latitude'] . '/' . $row['longitude'] . '">' . $row['name'] . '</a></td>' . "\n";
  $tmp .= "\t\t\t" . '<td class="levee_address">' . $row['location_address'] . '</td>' . "\n";
  $tmp .= "\t\t\t" . '<td class="levee_start">' . strftime("%l:%M %p", $start_number) . '</td>'. "\n";
  $tmp .= "\t\t\t" . '<td class="levee_end">' . strftime("%l:%M %p", $end_number) . '</td>'. "\n";
  if ($row['accessible']) {
    $tmp .= "\t\t\t" . '<td class="levee_accessible">Yes</td>'. "\n";
  }
  else {
    $tmp .= "\t\t\t" . '<td class="levee_accessible">No</td>'. "\n";
  }
  $tmp .= "\t\t" . '</tr>' . "\n";
  return $tmp;
}

function makeICalendar($row) {
  $vEvent = new \Eluceo\iCal\Component\Event();
  $vEvent->setDtStart(new \DateTime($row['startDate']));
  $vEvent->setDtEnd(new \DateTime($row['endDate']));
  $vEvent->setNoTime(false);
  $vEvent->setSummary($row['name'] . " 2016 New Years Levee");
  $vEvent->setLocation($row['location_name'] . "\n" . $row['location_address'], $row['location_name'], $row['latitude'] . "," . $row['longitude']);
  $vEvent->setUseTimezone(true);
  return $vEvent;
}
