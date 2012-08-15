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
<script type="text/javascript" src="lib/difflib.js"></script>
<link rel="stylesheet" type="text/css" href="ice-repo.css">
</head>

<body>
	
<?php
$fileContents = file_get_contents($dir);
?>

<form name="fcForm">
<textarea name="fileContents"><?php echo htmlentities($fileContents); ?></textarea>
<textarea name="repoContents"></textarea>
</form>

<script>
<?php if ($_POST['action']=="view") {?>
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
		dirContent = document.fcForm.fileContents.value;
		repoContent = document.fcForm.repoContents.value;
		diffUsingJS(dirContent,repoContent);
		parent.document.getElementById("row"+rowID+"Content").style.display = "inline-block";
	});
}
	
function diffUsingJS (dirContent,repoContent) {
	var base = difflib.stringAsLines(dirContent);
	var newtxt = difflib.stringAsLines(repoContent);
	var sm = new difflib.SequenceMatcher(base, newtxt);
	var opcodes = sm.get_opcodes();
	var diffoutputdiv = parent.document.getElementById("row"+rowID+"Content");
	while (diffoutputdiv.firstChild) diffoutputdiv.removeChild(diffoutputdiv.firstChild);
	var contextSize = ""; // optional
	contextSize = contextSize ? contextSize : null;
	diffoutputdiv.appendChild(
		diffview.buildView(
			{
			baseTextLines:base,
			newTextLines:newtxt,
			opcodes:opcodes,
			baseTextName:"Server:     <?php echo str_replace($_SERVER['DOCUMENT_ROOT']."/","",$dir);?>     ",
			newTextName:"Github:     <?php echo $repo;?>",
			contextSize:contextSize,
			viewType: 1 // 0 = side by side, 1 = inline
			}
		)
	)
}
	
sendData();
<?php ;}; ?>
<?php if ($_POST['action']=="changed") {?>
	// Update files...
	console.log('UPDATE PROCESS...');
	parent.document.getElementById('row'+<?php echo $rowID;?>).style.display = "none";
	parent.document.getElementById('row'+<?php echo $rowID;?>+'Content').style.display = "none";
<?php ;}; ?>
<?php if ($_POST['action']=="new") {?>
	// Add files...
	console.log('ADD PROCESS...');
	parent.document.getElementById('row'+<?php echo $rowID;?>).style.display = "none";
	parent.document.getElementById('row'+<?php echo $rowID;?>+'Content').style.display = "none";
<?php ;}; ?>
<?php if ($_POST['action']=="deleted") {?>
	// Delete files...
	console.log('DELETE PROCESS...');
	parent.document.getElementById('row'+<?php echo $rowID;?>).style.display = "none";
	parent.document.getElementById('row'+<?php echo $rowID;?>+'Content').style.display = "none";
<?php ;}; ?>
<?php
	echo "console.log('".$_POST['action']."');";
	echo "console.log('".$_POST['title']."');";
	echo "console.log('".$_POST['message']."');";
	echo "console.log('".$rowID."');";
	echo "console.log('".$repo."');";
	echo "console.log('".$dir."');";
?>
</script>
	
</body>
	
</html>