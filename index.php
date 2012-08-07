<?php
session_start();
if ($_SESSION['userLevel'] == 0) {
	die("Sorry, you need to be logged in to use ICErepo");
}

$docRoot = $_SERVER['DOCUMENT_ROOT'];
$version = "0.1";

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
		"mattpass/ICEcoder",$docRoot."/ICEcoder","selected"
		);
?>
<!DOCTYPE html>
<html>
<head>
<title>ICErepo v<?php echo $version;?></title>
<script src="lib/base64.js"></script>
<script src="lib/github.js"></script>
</head>

<body onLoad="gitCommand('repo.show',document.getElementById('repos').value)">

<select name="repos" id="repos" onChange="gitCommand('repo.show',this.value)">
<?php
for ($i=0;$i<count($repos);$i+=3) {
 	echo '<option id="repo'.($i/3).'" value="'.$repos[$i].'@'.$repos[$i+1].'"';
	echo $repos[$i+2]=="selected" ? ' selected' : '';
	echo '>'.$repos[$i]."</option>\n";
}
?>
</select>

<script>
var github = new Github(<?php
	if ($token!="") {
		echo '{token: "'.$token.'", auth: "oauth"}';
	} else{
		echo '{username: "'.$username.'", password: "'.$password.'", auth: "basic"}';
	}?>);
	
gitCommand = function(comm,value) {
	if (comm=="repo.show") {
		repoDir = value.split("@");
		user = repoDir[0].split("/")[0];
		repo = repoDir[0].split("/")[1];
		dir = repoDir[1];		
		console.log("user:" + user + " repo: " + repo + " server dir: " + dir);
		var repo = github.getRepo(user,repo);
		var user = github.getUser();
		document.getElementById('repo').innerHTML = "";
 		repo.getTree('master?recursive=true', function(err, tree) {
			for (i=0;i<tree.length;i++) {
				document.getElementById('repo').innerHTML += i + " : " + tree[i].path + "<br>";
			}
			console.log(tree)}
		)
	}
}	
	
</script>

<div id="repo" style="margin-top: 20px">
	
</div>
	
</body>

</html>