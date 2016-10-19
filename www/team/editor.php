<?php
/**
 * Edit source code and resubmit to the database.
 *
 * Part of the DOMjudge Programming Contest Jury System and licenced
 * under the GNU GPL. See README and COPYING for details.
 */

require('init.php');


// submit code
if ( isset($_POST['source']) ) {
	$files = array();
	$filenames = array();
	foreach($_POST['source'] as $sourcedata)
	{
		if ( !($tmpfname = tempnam(TMPDIR, "edit_source-")) ) {
			error("Could not create temporary file.");
		}
		file_put_contents($tmpfname, $sourcedata['code']);

		$files[] = $tmpfname;
		$filenames[] = $sourcedata['filename'];
	}

	$sid = submit_solution($teamid, $_POST['probid'], $cid, $_POST['langid'],
	                $files, $filenames);

    auditlog('submission', $sid, 'added', 'via teampage editor', null, $cid);
	foreach($files as $file)
	{
		unlink($file);
	}

	header('Location: index.php');
	exit;
}

$id = getRequestID();
$submission = $DB->q('MAYBETUPLE SELECT * FROM submission s
                      WHERE submitid = %i AND teamid = %i', $id, $teamid);

$sources = $DB->q('TABLE SELECT *
                   FROM submission_file
                   LEFT JOIN submission USING(submitid)
                   WHERE submitid = %i ORDER BY rank', $id);

$probs = $DB->q('KEYVALUETABLE SELECT probid, shortname FROM problem
                 INNER JOIN contestproblem USING (probid)
                 WHERE allow_submit = 1 AND cid = %i ORDER BY name', $cid);
$langs = $DB->q('TABLE SELECT langid, name, extensions FROM language
                 WHERE allow_submit = 1 ORDER BY name');
$langs_options = [];
foreach($langs as $lang) {
    $langs_options[$lang['langid']] = $lang['name'];
}

$twig->addFilter(new Twig_SimpleFilter('langidToAce', 'langidToAce'));

$title = 'Code Editor';
renderPage(array(
    'title' => $title,
    'probs' => $probs,
    'langs' => $langs,
    'langs_options' => $langs_options,
    'sources' => $sources,
    'submission' => $submission
));
