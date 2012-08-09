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

$rowID = numClean($_POST['rowID']);
$repo = strClean($_POST['repo']);
$dir = strClean($_POST['dir']);

echo "<script>fullRepoPath='".$repo."';</script>";
?>
<!DOCTYPE html>
<html>
<head>
<title>ICErepo v<?php echo $version;?></title>
<script src="lib/underscore-min.js"></script>
<script src="lib/base64.js"></script>
<script src="lib/github.js"></script>
<link rel="stylesheet" type="text/css" href="ice-repo.css">
</head>

<body onLoad="sendData()">
	
<?php
$fileContents = file_get_contents($dir);
?>

<form name="fcForm">
<textarea name="fileContents"><?php echo htmlentities($fileContents); ?></textarea>
<textarea name="repoContents"></textarea>
</form>
	
<script>
var github = new Github(<?php
if ($token!="") {
	echo '{token: "'.strClean($_POST['token']).'", auth: "oauth"}';
} else{
	echo '{username: "'.strClean($_POST['$username']).'", password: "'.strClean($_POST['$password']).'", auth: "basic"}';
}?>);
rowID = <?php echo $rowID; ?>;
repoUser = fullRepoPath.split('/')[0];
repoName = fullRepoPath.split('/')[1];
filePath = fullRepoPath.replace(repoUser+"/"+repoName+"/","");
var repo = github.getRepo(repoUser,repoName);
sendData = function() {
	repo.read('master', filePath, function(err, data) {
		document.fcForm.repoContents.innerHTML=data;
		dirContent = "<pre style='white-space:pre-wrap'>"+document.fcForm.fileContents.innerHTML+"</pre>";
		repoContent = "<pre style='white-space:pre-wrap'>"+document.fcForm.repoContents.innerHTML+"</pre>";
		parent.document.getElementById("row"+rowID+"Content").innerHTML = "<b>DIR CONTENT:</b><br><br>" + dirContent + "<br><br><hr><br><b>REPO CONTENT:</b><br><br>" + repoContent;
		parent.document.getElementById("row"+rowID+"Content").style.display = "inline-block";
	});
}
</script>
	
</body>
	
</html>