<?php
/**
 * View/download problem texts and sample data
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');

$title = 'Problem statements';
require(LIBWWWDIR . '/header.php');

$show_problemtext = dbconfig_get('download_problemtext', 1);
echo "<h1>Problem statements</h1>\n\n";
if ($show_problemtext) {
  putProblemTextList();
} else {
  echo "<p>Problem text disabled</p>";
}

$show_sample = dbconfig_get('download_samples', 1);
echo "<h1>Sample data</h1>\n\n";
if ($show_sample) {
  putSampleDataList();
} else {
  echo "<p>Sample testcases disabled</p>";
}

require(LIBWWWDIR . '/footer.php');
