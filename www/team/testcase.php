<?php
/**
 * View/download a specific problem text. This page could later be
 * extended to provide more details, like sample test cases.
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');

$show_sample = dbconfig_get('show_sample_output', 0);
if (!$show_sample) {
        error("Sample testcases disabled by admin");
}

$id = getRequestID();
if ( empty($id) ) error("Missing testcase id");

$FILES   = array('input','output');
if ( isset($_GET['fetch']) && in_array($_GET['fetch'], $FILES) ) {
  putSampleData($id, $_GET['fetch']);
} else {
  error("Missing or invalid value for 'fetch'");
}
