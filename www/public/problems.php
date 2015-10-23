<?php
/**
 * View/download problem texts
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');

$title = 'Problem statements';
require(LIBWWWDIR . '/header.php');

$show_problemtext = dbconfig_get('public_dl_problemtext', 0);
echo "<h1>Problem statements</h1>\n\n";
if ($show_problemtext) {
  putProblemTextList();
} else {
  echo "<p>Problem text disabled</p>";
}


$show_sample = dbconfig_get('public_dl_samples', 0);
echo "<h1>Sample data</h1>\n\n";
if ($show_sample) {
  putSampleDataList();
} else {
  echo "<p>Sample testcases disabled</p>";
}

require(LIBWWWDIR . '/footer.php');
