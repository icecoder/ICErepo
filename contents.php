<?php include("settings.php"); ?>
<!DOCTYPE html>
<html>
<head>
<title>ICErepo v <?php echo $version;?></title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script src="lib/base64.js"></script>
<script src="lib/github.js"></script>
<script src="lib/difflib.js"></script>
<script src="ice-repo.js"></script>
<link rel="stylesheet" type="text/css" href="ice-repo.css">
</head>

<body>
	
<?php
// Get a full list of dirs & files and begin sorting using above class & function
$repoPath = explode("@",strClean($_POST['repo']));
$repo = $repoPath[0];
$path = $repoPath[1];
$objectList = new SortingIterator(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path), RecursiveIteratorIterator::SELF_FIRST), 'alphasort');

// Iterator to get files
$iter = new RecursiveIteratorIterator(
	new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
	RecursiveIteratorIterator::SELF_FIRST,
	RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
);

// Check if dir has .gitignore file
function hasGitignore($dir) {
	return is_file("$dir/.gitignore");
}

// Get a list of .gitignore files into $gi array
$gi = array();
if(hasGitignore($path)) {
	$gi[] = "$path/.gitignore";
}
foreach ($iter as $scanpath) {
    if (is_dir($scanpath) && strpos($scanpath,".git") == false) {
		$thisDir = str_replace("\\","/",$scanpath);
        if(hasGitignore($thisDir)) {
			$gi[] = $thisDir."/.gitignore";
		}
    }
}

// Get $matches array containing existing files listed in .gitignore
function parseGitignore($file) { # $file = '/absolute/path/to/.gitignore'
  $dir = dirname($file);
  $matches = array();
  $lines = file($file);
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '') continue;                 # empty line
    if (substr($line, 0, 1) == '#') continue;   # a comment
    if (substr($line, 0, 1) == '!') {           # negated glob
      $line = substr($line, 1);
      $files = array_diff(glob("$dir/*"), glob("$dir/$line"));
    } else {                                    # normal glob
      $files = glob("$dir/$line");
    }
    $matches = array_merge($matches, $files);
  }
  return $matches;
}

// Cycle through all .gitignore files running above function to get a list of $excluded files
$excluded = array();
foreach ($gi as $scanpath) {
    $excludedTest = (parseGitignore($scanpath));
	if (count($excludedTest) > 0) {
		$excluded = array_merge($excluded, $excludedTest);
	}
}

// Finally, we have our ordered list, so display
$i=0;
$dirListArray = $dirSHAArray = $dirTypeArray = array();
foreach ($objectList as $objectRef) {
	$fileFolderName = @rtrim(substr(str_replace("\\","/",$objectRef->getPathname()), strlen($path)),"..");
	if (strpos($fileFolderName,".git/") == false && !in_array($path.$fileFolderName, $excluded) && !is_dir($path.$fileFolderName)) {
			$contents = file_get_contents($path.$fileFolderName);
			$finfo = "";
			// Determine what to do based on mime type
			if (function_exists('finfo_open')) {
				$finfoMIME = finfo_open(FILEINFO_MIME_TYPE);
				$finfo = finfo_file($finfoMIME, $path.$fileFolderName);
				finfo_close($finfoMIME);
			} else {
				$fileExt = explode(" ",pathinfo($path.$fileFolderName, PATHINFO_EXTENSION));
				$fileExt = $fileExt[0];
				if (array_search($fileExt,array("coffee","css","htm","html","js","less","md","php","py","rb","ruby","txt","xml"))!==false) {$finfo = "text";};
				if (array_search($fileExt,array("gif","jpg","jpeg","png"))!==false) {$finfo = "image";};
			}
			if (strpos($finfo,"text")===0 || strpos($finfo,"empty")!==false) {
				$contents = str_replace("\r","",$contents);
			};
			$store = "blob ".strlen($contents)."\000".$contents;
			$i++;
			array_push($dirListArray,ltrim($fileFolderName,"/"));
			array_push($dirSHAArray,sha1($store));
			$type = "file";
			array_push($dirTypeArray,$type);
	} else {
		// Do nothing, it's a dir
	}
}
?>

<script>
top.repo = '<?php echo $repo;?>';
top.path = '<?php echo $path;?>';
dirListArray = [<?php echo "'".implode("','", $dirListArray)."'";?>];
dirSHAArray  = [<?php echo "'".implode("','", $dirSHAArray)."'";?>];
dirTypeArray = [<?php echo "'".implode("','", $dirTypeArray)."'";?>];
</script>
	
<div id="compareList" class="mainContainer"></div>
	
<div id="commitPane" class="commitPane">
	<b style='font-size: 18px'>COMMIT CHANGES:</b><br><br>
	<form name="fcForm" action="file-control.php?username=<?php echo $username;?>&password=<?php echo $password;?>&token=<?php echo $token;?>" target="fileControl" method="POST">
		<input type="text" name="title" value="Title..." style="width: 260px; border: 0; background: #f8f8f8; margin-bottom: 10px" onFocus="titleDefault='Title...'; if(this.value==titleDefault) {this.value=''}" onBlur="if(this.value=='') {this.value=titleDefault}"><br>
		<textarea name="message" style="width: 260px; height: 180px; border: 0; background: #f8f8f8; margin-bottom: 5px" onFocus="messageDefault='Message...'; if(this.value==messageDefault) {this.value=''}" onBlur="if(this.value=='') {this.value=messageDefault}">Message...</textarea>
		<input type="hidden" name="path" value="<?php echo $path; ?>">	
		<input type="hidden" name="rowID" value="">
		<input type="hidden" name="gitRepo" value="<?php echo $repo; ?>">
		<input type="hidden" name="repo" value="">
		<input type="hidden" name="dir" value="">
		<input type="hidden" name="action" value="">
		<input type="submit" name="commit" value="Commit changes" onClick="return commitChanges()" style="border: 0; background: #555; color: #fff; cursor: pointer">
	</form>
</div>
	
<div id="infoPane" class="infoPane"></div>

<script>
top.fcFormAlias = document.fcForm;
var github = new Github(<?php
if ($token!="") {
	echo '{token: "'.$token.'", auth: "oauth"}';
} else{
	echo '{username: "'.$username.'", password: "'.$password.'", auth: "basic"}';
}?>);
repoListArray = [];
repoSHAArray = [];
window.onLoad=gitCommand('repo.show','<?php echo strClean($_POST['repo']);?>');
</script>
	
<iframe name="fileControl" style="display: none"></iframe>
	
</body>
	
</html>