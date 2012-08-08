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

<body style="margin: 20px; font-family: arial, helvetica, swiss, verdana; font-size: 12px">
	
<div id="dirList" style="position: relative; float: left; padding-right: 100px"><b style="font-size: 18px">SERVER DIR LIST:</b><br><br>
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
$dirListArray = array();
$dirSHAArray = array();
$dirTypeArray = array();
foreach ($objectList as $objectRef) {
	$fileFolderName = rtrim(substr($objectRef->getPathname(), strlen($path)),"..");
	if ($objectRef->getFilename()!="." && $fileFolderName[strlen($fileFolderName)-1]!="/") {
			$contents = file_get_contents($path.$fileFolderName);
			$store = "blob ".strlen($contents)."\0".$contents;
			echo $i." : ".ltrim($fileFolderName,"/")."<br>".sha1($store)."<br><br>";
			$i++;
			array_push($dirListArray,ltrim($fileFolderName,"/"));
			array_push($dirSHAArray,sha1($store));
			$type = is_dir($path.$fileFolderName) ? "dir" : "file";
			array_push($dirTypeArray,$type);
	}
}

echo PHP_EOL.PHP_EOL.'<script>'.PHP_EOL;
echo 'dirListArray = [';
for ($i=0;$i<count($dirListArray);$i++) {
	echo "'".$dirListArray[$i]."'";
	if ($i<count($dirListArray)-1) {echo ",";};
}
echo '];'.PHP_EOL;
echo 'dirSHAArray = [';
for ($i=0;$i<count($dirSHAArray);$i++) {
	echo "'".$dirSHAArray[$i]."'";
	if ($i<count($dirSHAArray)-1) {echo ",";};
}
echo '];'.PHP_EOL;
echo 'dirTypeArray = [';
for ($i=0;$i<count($dirTypeArray);$i++) {
	echo "'".$dirTypeArray[$i]."'";
	if ($i<count($dirTypeArray)-1) {echo ",";};
}
echo '];'.PHP_EOL;
echo '</script>';
?>
</div>

<div id="repoList" style="position: relative: left: 0px; float: left; padding-right: 100px"></div>
	
<div id="compareList" style="position: relative; float: left"></div>
	
<script>
var github = new Github(<?php
if ($token!="") {
	echo '{token: "'.strClean($_POST['token']).'", auth: "oauth"}';
} else{
	echo '{username: "'.strClean($_POST['$username']).'", password: "'.strClean($_POST['$password']).'", auth: "basic"}';
}?>);

repoListArray = [];
repoSHAArray = [];
gitCommand = function(comm,value) {
	if (comm=="repo.show") {
		repoDir = value.split("@");
		user = repoDir[0].split("/")[0];
		repo = repoDir[0].split("/")[1];
		dir = repoDir[1];		
		var repo = github.getRepo(user,repo);
		var user = github.getUser();
		var repoList = "<b style='font-size: 18px'>REPO LIST (Github):</b><br><br>";
		var compareList = "COMPARE LIST:<br><br>";
		document.getElementById('repoList').innerHTML = "";
		document.getElementById('compareList').innerHTML = "";
 		repo.getTree('master?recursive=true', function(err, tree) {
			for (i=0;i<tree.length;i++) {
				repoList += i + " : " + tree[i].path + "<br>" + tree[i].sha + "<br><br>";
				repoListArray.push(tree[i].path);
				repoSHAArray.push(tree[i].sha);
			}
			document.getElementById('repoList').innerHTML = repoList;
			console.log(tree);
			compareList += "<b style='font-size: 18px'>CHANGED FILES:</b><br><br>"
			newFilesList = "";
			for (i=0;i<dirListArray.length;i++) {
				repoArrayPos = repoListArray.indexOf(dirListArray[i]);
				if (repoArrayPos == "-1") {
					newFilesList += i+" : "+dirListArray[i]+"<br>";
				} else if (dirTypeArray[i] == "file" && dirSHAArray[i] != repoSHAArray[repoArrayPos]) {
					compareList += i+" = "+repoArrayPos+" : "+dirListArray[i]+"<br>";
				}
			}
			
			compareList += "<br><b style='font-size: 18px'>NEW FILES:</b><br><br>"+newFilesList;
			
			delFilesList = "";
			for (i=0;i<repoListArray.length;i++) {
				dirArrayPos = dirListArray.indexOf(repoListArray[i]);
				if (dirArrayPos == "-1") {
					delFilesList += i+" : "+repoListArray[i]+"<br>";
				}
			}
			
			compareList += "<br><b style='font-size: 18px'>DELETED FILES:</b><br><br>"+delFilesList;
			
			document.getElementById('compareList').innerHTML = compareList;
			}
		)
	}
}
gitCommand('repo.show','<?php echo strClean($_POST['repo']);?>');
</script>
	
</body>
	
</html>