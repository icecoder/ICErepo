<?php
session_start();
if ($_SESSION['userLevel'] == 0) {
	die("Sorry, you need to be logged in to use ICErepo");
}

function strClean($var) {
	// returns converted entities where there are HTML entity equivalents
	return htmlentities($var, ENT_QUOTES, "UTF-8");
}

function numClean($var) {
	// returns a number, whole or decimal or null
	return is_numeric($var) ? floatval($var) : false;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>ICErepo v<?php echo $version;?></title>
<script src="lib/base64.js"></script>
<script src="lib/github.js"></script>
</head>

<body style="margin: 20px">

<div id="repoList" style="position: relative: left: 0px; float: left; padding-right: 100px"></div>
	
<div id="dirList" styele="position: relative; left: 1000px; float: left">SERVER DIR LIST:<br><br>
<?php
// Function to sort given values alphabetically
function alphasort($a, $b) {
	return strcmp($a->getPathname(), $b->getPathname());
}

// Class to put forward the values for sorting
class SortingIterator implements IteratorAggregate {
	private $iterator = null;
	public function __construct(Traversable $iterator, $callback) {
		$array = iterator_to_array($iterator);
		usort($array, $callback);
		$this->iterator = new ArrayIterator($array);
	}
	public function getIterator() {
	return $this->iterator;
	}
}

// Get a full list of dirs & files and begin sorting using above class & function
$repo = explode("@",strClean($_POST['repo']));
$path = $repo[1];
$objectList = new SortingIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST), 'alphasort');

// Finally, we have our ordered list, so display
$i=0;
foreach ($objectList as $objectRef) {
	$fileFolderName = rtrim(substr($objectRef->getPathname(), strlen($path)),"..");
	if ($objectRef->getFilename()!="." && $fileFolderName[strlen($fileFolderName)-1]!="/") {
			echo $i." : ".ltrim($fileFolderName,"/")."<br>";
			$i++;
	}
}
?>
</div>
	
<script>
var github = new Github(<?php
if ($token!="") {
	echo '{token: "'.strClean($_POST['token']).'", auth: "oauth"}';
} else{
	echo '{username: "'.strClean($_POST['$username']).'", password: "'.strClean($_POST['$password']).'", auth: "basic"}';
}?>);

gitCommand = function(comm,value) {
	if (comm=="repo.show") {
		repoDir = value.split("@");
		user = repoDir[0].split("/")[0];
		repo = repoDir[0].split("/")[1];
		dir = repoDir[1];		
		var repo = github.getRepo(user,repo);
		var user = github.getUser();
		var repoList = "REPO LIST (Github):<br><br>";
		document.getElementById('repoList').innerHTML = "";
 		repo.getTree('master?recursive=true', function(err, tree) {
			for (i=0;i<tree.length;i++) {
				repoList += i + " : " + tree[i].path + "<br>";
			}
			document.getElementById('repoList').innerHTML = repoList;
			console.log(tree)}
		)
	}
}
gitCommand('repo.show','<?php echo strClean($_POST['repo']);?>');
</script>
	
</body>
	
</html>