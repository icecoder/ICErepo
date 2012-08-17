<?php
session_start();
if ($_SESSION['userLevel'] == 0) {
	die("Sorry, you need to be logged in to use ICErepo");
}

$docRoot = $_SERVER['DOCUMENT_ROOT'];
$version = "0.5.1";

// AUTHENTICATION
// Can either be done by oauth, or username & password.

// oauth
$token = "";

// Basic
$username = "username";
$password = "password";

// REPOS & SERVER DIRS
// Here you identify the repo location and related path on your server
// (the last param is to identify which dropdown option to select by default).
$repos = array(
		"mattpass/dirTree",$docRoot."/dirTree","",
		"mattpass/CodeMirror2",$docRoot."/CodeMirror2","selected"
		);
?>
<!DOCTYPE html>
<html>
<head>
<title>ICErepo v<?php echo $version;?></title>
<script src="lib/base64.js"></script>
<script src="lib/github.js"></script>
<link rel="stylesheet" type="text/css" href="ice-repo.css">
</head>

<body style="margin: 0; overflow: hidden" onLoad="doRepo(document.getElementById('repos').value)">

<div style="position: absolute; width: 100%; height: 60px; background: #444; z-index: 1">
	<select name="repos" id="repos" onChange="doRepo(this.value)" style="margin: 20px 0 0 20px">
	<?php
	for ($i=0;$i<count($repos);$i+=3) {
		echo '<option id="repo'.($i/3).'" value="'.$repos[$i].'@'.$repos[$i+1].'"';
		echo $repos[$i+2]=="selected" ? ' selected' : '';
		echo '>'.$repos[$i]."</option>\n";
	}
	?>
	</select>
	
	<div class="pullGithub" style="margin-top: 20px; margin-left: 5px">Pull all from Github</div>
	<div class="logo"><img src="images/ice-repo.gif" alt="ICErepo"></div>
</div>
	
<script>
doRepo = function(repo) {
	document.showRepo.repo.value = repo;
	document.showRepo.submit();
}
</script>

<form name="showRepo" action="contents.php" target="repo" method="POST">
<input type="hidden" name="token" value="<?php echo $token;?>">
<input type="hidden" name="username" value="<?php echo $username;?>">
<input type="hidden" name="password" value="<?php echo $password;?>">
<input type="hidden" name="repo" value="">
</form>

<iframe id="repo" style="position: absolute; width: 100%; height: 90%; left: 0px; margin-top: 60px" frameborder="0"></iframe>
	
</body>

</html>