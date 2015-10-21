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

echo "<h1>Problem statements</h1>\n\n";

putProblemTextList();

echo "<h1>Sample data</h1>\n\n";

putSampleDataList();

require(LIBWWWDIR . '/footer.php');
