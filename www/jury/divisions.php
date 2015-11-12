<?php
require('init.php');

if (isset($_POST['teamid'])) {

	if (isset($_POST['switchd'])) {
	  $DB->q('UPDATE contestteam SET cid=IF(cid=4,5,4) WHERE teamid = %i', $_POST['teamid']);
	}
	if (isset($_POST['switchstealth'])) {
	  $stealthid = 15;
	  $tid = intval($_POST['teamid']);
	  if     ($tid >= 100 && $tid <= 199) { $cid = 11; }
	  elseif ($tid >= 200 && $tid <= 299) { $cid = 12; }
	  elseif ($tid >= 300 && $tid <= 399) { $cid = 13; }
	  elseif ($tid >= 400 && $tid <= 499) { $cid = 14; }

	  $DB->q("UPDATE team SET categoryid=IF(categoryid=$cid,$stealthid,$cid) WHERE teamid = %i", $tid);
	}
	header("Location: divisions.php");
	exit();
}

$title = "Team Divisions Check";
require(LIBWWWDIR . '/header.php');
echo "<h1>" . htmlspecialchars($title) . "</h1>\n\n";


// Language statistics
$teaminfo = $DB->q('select t.teamid, t.name, tc.categoryid as categoryid, tc.name as category, cid, contest.name as contest, ta.name as school from contestteam ct
left join team t using(teamid)
left join team_affiliation ta on t.affilid = ta.affilid
left join team_category tc on t.categoryid = tc.categoryid
left join contest using(cid)
where cid=4 or cid=5 order by school, t.name');
$teaminfo = $teaminfo->gettable();
?>
<style>
.d4 {
background-color: #608ABC;
}
.d5{
background-color: #C85F5F;
}
.cat15 a, .cat15 {
color: #FFFFFF;
}

</style>
<table class="list" width='1200px' cellpadding="2px">
<thead>
  <tr>
    <th>Team Name</th>
    <th>School</th>
    <th>Division</th>
    <th>Category</th>
    <th>Control</th>
  </tr>
</thead>
<tbody>
<?php
foreach($teaminfo as $team) {
  echo "<tr class='d${team['cid']} cat{$team['categoryid']}'>";
  echo "<td><a syle='padding:0px;' href='team.php?id=".urlencode($team['teamid'])."'>${team['name']}</a></td>";
  echo "<td>${team['school']}</td>";
  echo "<td>${team['contest']}</td>";
  echo "<td>${team['category']}</td>";
?>
<td>
<form method="POST" action="divisions.php">
<input type="hidden" name="teamid" value="<?= $team['teamid'] ?>" />
<input type="submit" name="switchd" value="Switch Divisions">
<input type="submit" name="switchstealth" value="Stealth/Unstealth">
</form>
</td>
<?php
}
?>
</tbody>
</table>

<?php
require(LIBWWWDIR . '/footer.php');
