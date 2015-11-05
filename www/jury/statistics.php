<?php

require('init.php');

// Include flot javascript library
$extrahead = '';
$extrahead .= '<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="../js/flot/excanvas.js"></script><![endif]-->';
$extrahead .= '<script language="javascript" type="text/javascript" src="../js/flot/jquery.js"></script>';
$extrahead .= '<script language="javascript" type="text/javascript" src="../js/flot/jquery.flot.js"></script>';
$extrahead .= '<script language="javascript" type="text/javascript" src="../js/flot/jquery.flot.stack.js"></script>';

// one bar per 10 minutes, should be in config somewhere
$bar_size = 10;

$title = "Statistics";
$allproblems = false;
if ( !empty($_GET['probid']) ) {
  $shortname = $DB->q('VALUE SELECT shortname FROM problem p
                       INNER JOIN contestproblem cp USING (probid)
                       WHERE p.probid = %i and cp.cid = %i', $_GET['probid'], $cid);
  $title .= " - Problem " . htmlspecialchars($shortname);
} else {
  $allproblems = true;
}

require(LIBWWWDIR . '/header.php');
echo "<h1>" . htmlspecialchars($title) . "</h1>\n\n";

$submission_over_time = $DB->q('SELECT result, COUNT(result) as count,
               (c.freezetime IS NOT NULL && submittime >= c.freezetime) AS afterfreeze,
               (FLOOR(submittime - c.starttime) DIV %i) * %i AS minute,
               s.probid as probid
               FROM submission s
               JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
               LEFT OUTER JOIN contest c ON(c.cid=j.cid)
               WHERE s.cid = %i AND s.valid = 1 ' .
              ( empty($_GET['probid']) ? '%_' : 'AND s.probid = %i ' ) . '
               AND submittime < c.endtime AND submittime >= c.starttime
               GROUP BY minute, result, probid', $bar_size * 60, $bar_size, $cid, @$_GET['probid']);

// All problems
$numproblems = 0;
$problems = $DB->q('SELECT p.probid,p.name FROM problem p
                    INNER JOIN contestproblem USING (probid)
                    WHERE cid = %i ORDER by shortname', $cid);
print '<p>';
print '<a href="statistics.php">All problems</a>&nbsp;&nbsp;&nbsp;';
while($row = $problems->next()) {
  $numproblems++;
	print '<a href="statistics.php?probid=' . urlencode($row['probid']) . '">' . htmlspecialchars($row['name']) . '</a>&nbsp;&nbsp;&nbsp;';
}
print '</p>';

// Contest information
$start = $cdata['starttime'];
$end = $cdata['endtime'];
$length = ($end - $start) / 60;

// How many teams solved how many problems?
if ($allproblems) {
  // Count how many teams are in this contest
  $public_contest = $DB->q('VALUE SELECT public FROM contest WHERE cid = %i', $cid);
  if ($public_contest == 0) {
    $totalteams = $DB->q('VALUE SELECT COUNT(teamid) FROM contestteam WHERE cid = %i', $cid);
  } else {
    $totalteams = $DB->q('VALUE SELECT COUNT(teamid) FROM team');
  }
  // Get information for how many correct submissions(grouped by team)
  $teamtotals = $DB->q('SELECT t.teamid as team,
                       sum(case when result = "correct" then 1 else 0 end) count
                       FROM submission s
                       JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
                       LEFT OUTER JOIN contest c ON(c.cid=j.cid)
                       LEFT OUTER JOIN team t USING(teamid)
                       WHERE s.cid = %i AND s.valid =1 ' .
                       ( empty($_GET['probid']) ? '%_' : 'AND s.probid = %i ' ) . '
                       AND submittime < c.endtime AND submittime >= c.starttime
                       GROUP BY team ORDER BY COUNT(result)', $cid, @$_GET['probid']);
  $activeteams = 0;  // Teams that have made at least one submission
  $numteams = array_fill(0, $numproblems+1, 0); // indexes in array are how many teams solved 'index' many problems
  foreach ($teamtotals->gettable() as $item) {
    $numteams[$item['count']]++;
    $activeteams++;
  }
  // Fix the number of teams that solved none to include teams that made no submissions
  $numteams[0] = $numteams[0] +($totalteams-$activeteams);
  echo "<h2>How many teams solved problems</h2>";
  echo "<div>";
  echo '<div style="display:inline-block;width:350px;height:400px;vertical-align:top;"><ul>';
  $numteams_json = array();  // array we'll use for the flot chart
  foreach ($numteams as $key=>$value) {
    $numteams_json[] = array($key, $value);
    if ($key == 0) $key = 'zero';
    if ($value == 0) $value = 'No';
    echo "<li>$value teams solved $key problems</li>";
  }
  echo '</ul></div>
  <div style="display:inline-block; width:650px">
  <div id="numsolvedteams" style="width:100%;height:400px;"></div>
  </div>
  </div>';
}

// Language statistics
$langinfo = $DB->q('SELECT s.langid as lang,
                    sum(case when result = "correct" then 1 else 0 end) accepted,
                    sum(case when result <> "correct" then 1 else 0 end) rejected
                    FROM submission s
                    JOIN judging j ON(s.submitid=j.submitid AND j.valid=1)
                    LEFT OUTER JOIN contest c ON(c.cid=j.cid)
                    WHERE s.cid = %i AND s.valid = 1 ' .
                   ( empty($_GET['probid']) ? '%_' : 'AND s.probid = %i ' ) . '
                    AND submittime < c.endtime AND submittime >= c.starttime
                    GROUP BY lang ORDER BY lang', $cid, @$_GET['probid']);
$langinfo = $langinfo->gettable();
echo '<h2>Language Statistics</h2>
<table cellpadding="2px">
  <tr>
    <th>Language</th>
    <th>Accepted</th>
    <th>Submissions</th>
    <th>Correct</th>
    <th>Usage</th>
  </tr>';
$fulltotal = 0;
foreach($langinfo as $l){
  $fulltotal += $l['accepted'] + $l['rejected'];
}
foreach($langinfo as $l) {
  $lang = $l['lang'];
  $accepted = $l['accepted'];
  $total = $accepted + $l['rejected'];
  $percent = (int)(($accepted/$total)*100);
  $usage = (int)(($total/$fulltotal)*100);
  echo "<tr><td>$lang</td><td>$accepted</td><td>$total</td><td>$percent%</td><td>$usage%</tr>";
  $atotal += $accepted;
}
$percent = (int)(($atotal/$fulltotal)*100);
echo "<tr><td><b>Total</b></td><td><b>$atotal</b></td><td><b>$fulltotal</b></td><td><b>$percent%</b></td></tr>";
echo "</table>";
?>
    <h2>Submission results over time</h2>
    <div id="submissionstime" style="width:1000px;height:400px;"></div>

<script id="source">
  var data = <?= json_encode($submission_over_time->gettable()); ?>;
  var contestlen = <?= $length; ?>;

$(function () {
    var answers = [{label : "correct", color : "#01DF01", bars : { fill: 1 } },
           {label : "wrong-answer", color : "red", bars : { fill: 0.6} },
           {label : "timelimit", color : "orange", bars : { fill: 0.6} },
           {label : "run-error", color : "#FF3399", bars : { fill: 0.6} },
           {label : "compiler-error", color : "blue", bars : { fill: 0.6 }, },
           {label : "no-output", color : "purple", bars : { fill: 0.6 } }, ];
    var charts = [];
    for(var i = 0; i < answers.length; i++) {
      var cur = [];
      for(var j = 0; j < contestlen / <?= $bar_size ?>; j++)
        cur.push([j * <?= $bar_size ?> + 0.1 * <?= $bar_size ?>,0]);
      var answer = answers[i].label;
      for(var j = 0; j < data.length; j++) {
        if(data[j].result == answer) {
          cur[parseInt(data[j].minute) / <?= $bar_size ?>][1] = parseInt(data[j].count);
        }
      }
      var newchart = answers[i];
      newchart.data = cur;
      charts.push(newchart);
    }
    $.plot($("#submissionstime"), charts, {
      xaxis: { min : 0, max : contestlen },
      legend: { position : "nw" },
      series: {
        bars: { show: true, barWidth: <?= $bar_size * 0.8 ?>, lineWidth : 0 },
        stack: 0
      }
    });

    $.plot($("#numsolvedteams"), [<?= json_encode($numteams_json); ?>], {
      xaxis: { tickDecimals: 0},
      yaxis: { tickDecimals: 0},
      colors: ['#01DF01'],
      series: { bars: {show: true, align: 'center', barWidth: 0.75, lineWidth: 0, fill: 1.0} }
    });
});
</script>

<?php
require(LIBWWWDIR . '/footer.php');
