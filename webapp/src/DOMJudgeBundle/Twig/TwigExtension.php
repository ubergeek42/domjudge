<?php
namespace DOMJudgeBundle\Twig;
use DOMJudgeBundle\Service\DOMJudgeService;

class TwigExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    protected $domjudge;
    public function __construct(DOMJudgeService $domjudge) {
        $this->domjudge = $domjudge;
    }

    public function getFunctions()
    {
        return array(
            new \Twig_Function('putClock', array($this, 'putClock')),
            new \Twig_Function('checkrole', array($this, 'checkrole')),
        );
    }
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('price', array($this, 'priceFilter')),
        );
    }
    public function getGlobals()
    {
        // TODO: these values are used by the header template
        return array(
            'contest' => $this->domjudge->getCurrentContest(),
            'contests' => $this->domjudge->getCurrentContests(),
            'IS_ADMIN' => false,
            'have_printing' => false,
            'updates' => array(
              'judgehosts' => array(),
              'clarifications' => array(),
              'rejudgings' => array(),
            ),
        );
    }

    // TODO: this should return the correct value!
    public function checkrole($role) {
      return true;
    }

    // TODO: make this work properly
    function putClock() {
    	// global $cdata, $username, $userdata;

      $cdata = $this->domjudge->getCurrentContest();
      // $username = ?
      // $user = ?
      return '<div id="clock">clock goes here</div>';

      $ret = '';

    	$ret .= '<div id="clock">';
    	// timediff to end of contest
    	if ( difftime(now(), $cdata['starttime']) >= 0 &&
    	     difftime(now(), $cdata['endtime'])   <  0 ) {
    		$left = "time left: " . printtimediff(now(),$cdata['endtime']);
    	} else if ( difftime(now(), $cdata['activatetime']) >= 0 &&
    	            difftime(now(), $cdata['starttime'])    <  0 ) {
    		$left = "time to start: " . printtimediff(now(),$cdata['starttime']);
    	} else {
    		$left = "";
    	}
    	$ret .= "<span id=\"timeleft\">" . $left . "</span>";

    	global $cid, $cdatas;
    	// Show a contest selection form, if there are contests
    	if ( IS_JURY || count($cdatas) > 1 ) {
    		$ret .= "<div id=\"selectcontest\">\n";
    		$ret .= addForm('change_contest.php', 'get', 'selectcontestform');
    		$contests = array_map(function($c) { return $c['shortname']; }, $cdatas);
    		if ( IS_JURY ) {
    			$values = array(
    				// -1 because setting cookies to null/'' unsets then and that is not what we want
    				-1 => '- No contest'
    			);
    		}
    		foreach ( $contests as $contestid => $name ) {
    			$values[$contestid] = $name;
    		}
    		$ret .= 'contest: ' . addSelect('cid', $values, $cid, true);
    		$ret .= addEndForm();
    		$ret .= "<script type=\"text/javascript\">
    		      document.getElementById('cid').addEventListener('change', function() {
    		      document.getElementById('selectcontestform').submit();
    	});
    </script>
    ";
    		$ret .= "</div>\n";
    	}

    	if ( logged_in() ) {
    		// Show pretty name if possible
    		$displayname = $username;
    		if ($userdata['name']) {
    			$displayname = "<abbr title=\"$username\">" . $userdata['name'] . "</abbr>";
    		}
    		$ret .= "<div id=\"username\">logged in as " . $displayname
    			. ( have_logout() ? " <a href=\"../auth/logout.php\">×</a>" : "" )
    			. "</div>";
    	}

    	$ret .= "</div>";

    	$ret .= "<script type=\"text/javascript\">
    	var initial = " . time() . ";
    	var activatetime = " . ( isset($cdata['activatetime']) ? $cdata['activatetime'] : -1 ) . ";
    	var starttime = " . ( isset($cdata['starttime']) ? $cdata['starttime'] : -1 ) . ";
    	var endtime = " . ( isset($cdata['endtime']) ? $cdata['endtime'] : -1 ) . ";
    	var offset = 0;
    	var date = new Date(initial*1000);
    	var timeleftelt = document.getElementById(\"timeleft\");

    	setInterval(function(){updateClock();},1000);
    	updateClock();
    </script>\n";
      return $ret;
    }

    public function priceFilter($number, $decimals = 0, $decPoint = '.', $thousandsSep = ',')
    {
        $price = number_format($number, $decimals, $decPoint, $thousandsSep);
        $price = '$'.$price;

        return $price;
    }
}
