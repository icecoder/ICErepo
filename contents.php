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
<script type="text/javascript" src="lib/difflib.js"></script>
<link rel="stylesheet" type="text/css" href="ice-repo.css">
</head>

<body>
	
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
$repoPath = explode("@",strClean($_POST['repo']));
$repo = $repoPath[0];
$path = $repoPath[1];
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
	
<div id="compareList" class="mainContainer"></div>
	
<div id="commitPane" class="commitPane">
<b style='font-size: 18px'>COMMIT CHANGES:</b><br><br>
<form name="fcForm" action="file-control.php" target="fileControl" method="POST">
<input type="text" name="title" value="Title..." style="width: 260px; border: 0; background: #f8f8f8; margin-bottom: 10px" onFocus="titleDefault='Title...'; if(this.value==titleDefault) {this.value=''}" onBlur="if(this.value=='') {this.value=titleDefault}"><br>
<textarea name="message" style="width: 260px; height: 180px; border: 0; background: #f8f8f8; margin-bottom: 5px" onFocus="messageDefault='Message...'; if(this.value==messageDefault) {this.value=''}" onBlur="if(this.value=='') {this.value=messageDefault}">Message...</textarea>
<input type="hidden" name="token" value="<?php echo strClean($_POST['token']);?>">
<input type="hidden" name="username" value="<?php echo strClean($_POST['username']);?>">
<input type="hidden" name="password" value="<?php echo strClean($_POST['password']);?>">
<input type="hidden" name="path" value="<?php echo $path; ?>">	
<input type="hidden" name="rowID" value="">
<input type="hidden" name="gitRepo" value="">
<input type="hidden" name="repo" value="">
<input type="hidden" name="dir" value="">
<input type="hidden" name="action" value="">
<input type="submit" name="commit" value="Commit changes" onClick="return commitChanges()" style="border: 0; background: #555; color: #fff; cursor: pointer">
</form>
</div>
	
<script>
var github = new Github(<?php
if ($_POST['token']!="") {
	echo '{token: "'.strClean($_POST['token']).'", auth: "oauth"}';
} else{
	echo '{username: "'.strClean($_POST['username']).'", password: "'.strClean($_POST['password']).'", auth: "basic"}';
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
		var compareList = "";
		rowID = 0;
 		repo.getTree('master?recursive=true', function(err, tree) {
			for (i=0;i<tree.length;i++) {
				repoListArray.push(tree[i].path);
				repoSHAArray.push(tree[i].sha);
			}
			compareList += "<b style='font-size: 18px'>CHANGED FILES:</b><br><br>";
			newFilesList = "";
			for (i=0;i<dirListArray.length;i++) {
				repoArrayPos = repoListArray.indexOf(dirListArray[i]);
				if (dirTypeArray[i]=="dir") {
					fileExt = "folder";
				} else {
					fileExt = dirListArray[i].substr(dirListArray[i].lastIndexOf('.')+1);
				}
				if (repoArrayPos == "-1") {
					rowID++;
					styleExtra = fileExt == 'folder' ? ' style="cursor: default"' : '';
					clickExtra = fileExt != 'folder' ? ' onClick="getContent('+rowID+',\''+dirListArray[i]+'\')"' : '';
					newFilesList += "<div class='row' id='row"+rowID+"'"+clickExtra+styleExtra+"><input type='checkbox' id='checkbox"+rowID+"' style='border: 0; background: #888"+styleExtra+"' onMouseOver='overOption=true' onMouseOut='overOption=false' onClick='updateSelection(this,"+rowID+",\"<?php echo $path;?>/"+dirListArray[i]+"\",\"new\")'> <div class='icon ext-"+fileExt+"'></div>"+dirListArray[i]+"<br></div>";
					newFilesList += "<span class='rowContent' id='row"+rowID+"Content'></span>";
				} else if (dirTypeArray[i] == "file" && dirSHAArray[i] != repoSHAArray[repoArrayPos]) {
					rowID++;
					styleExtra = fileExt == 'folder' ? ' style="cursor: default"' : '';
					clickExtra = fileExt != 'folder' ? ' onClick="getContent('+rowID+',\''+dirListArray[i]+'\')"' : '';
					compareList += "<div class='row' id='row"+rowID+"'"+clickExtra+styleExtra+"><input type='checkbox' id='checkbox"+rowID+"' style='border: 0; background: #888' onMouseOver='overOption=true' onMouseOut='overOption=false' onClick='updateSelection(this,"+rowID+",\"<?php echo $path;?>/"+dirListArray[i]+"@<?php echo $repo;?>/"+dirListArray[i]+"\",\"changed\")'> <div class='icon ext-"+fileExt+"'></div>"+dirListArray[i]+"<div class='pullGithub' onMouseOver='overOption=true' onMouseOut='overOption=false'>Pull from Github</div><br></div>";
					compareList += "<span class='rowContent' id='row"+rowID+"Content'></span>";
				}
			}

			compareList += "<br><br><b style='font-size: 18px'>NEW FILES:</b><br><br>"+newFilesList;
			
			delFilesList = "";
			for (i=0;i<repoListArray.length;i++) {
				dirArrayPos = dirListArray.indexOf(repoListArray[i]);
				if (repoListArray[i].lastIndexOf('/') > repoListArray[i].lastIndexOf('.')) {
					fileExt = "folder";
				} else {
					fileExt = repoListArray[i].substr(repoListArray[i].lastIndexOf('.')+1);
				}
				if (dirArrayPos == "-1") {
					rowID++;
					styleExtra = fileExt == 'folder' ? ' style="cursor: default"' : '';
					clickExtra = fileExt != 'folder' ? ' onClick="getContent('+rowID+',\''+repoListArray[i]+'\')"' : '';
					delFilesList += "<div class='row' id='row"+rowID+"'"+clickExtra+styleExtra+"><input type='checkbox' id='checkbox"+rowID+"' style='border: 0; background: #888' onMouseOver='overOption=true' onMouseOut='overOption=false' onClick='updateSelection(this,"+rowID+",\"<?php echo $repo;?>/"+repoListArray[i]+"\",\"deleted\")'> <div class='icon ext-"+fileExt+"'></div>"+repoListArray[i]+"<div class='pullGithub' onMouseOver='overOption=true' onMouseOut='overOption=false'>Pull from Github</div><br></div>";
					delFilesList += "<span class='rowContent' id='row"+rowID+"Content'></span>";
				}
			}
			
			compareList += "<br><br><b style='font-size: 18px'>DELETED FILES:</b><br><br>"+delFilesList;
			document.getElementById('compareList').innerHTML = compareList;
			top.document.getElementById('blackMask').style.display='none';
			}
		)
	}
}
	
getContent = function(thisRow,path) {
	if("undefined" == typeof overOption || !overOption) {
		if ("undefined" == typeof lastRow || lastRow!=thisRow || document.getElementById('row'+thisRow+'Content').innerHTML=="") {
			for (i=1;i<=rowID;i++) {
				document.getElementById('row'+i+'Content').innerHTML = "";
				document.getElementById('row'+i+'Content').style.display = "none";
			}
			repo = "<?php echo $repo;?>" + "/" + path;
			dir = "<?php echo $path;?>" + "/" + path;
			document.fcForm.rowID.value = thisRow;
			document.fcForm.repo.value = repo;
			document.fcForm.dir.value = dir;
			document.fcForm.action.value = "view";
			document.fcForm.submit();
		} else {
			document.getElementById('row'+thisRow+'Content').innerHTML = "";
			document.getElementById('row'+thisRow+'Content').style.display = "none";
		}
		lastRow = thisRow;
	}
}

selRowArray = [];
selRepoDirArray = [];
selActionArray = [];
updateSelection = function(elem,row,repoDir,action) {
	if (elem.checked) {
		selRowArray.push(row);
		selRepoDirArray.push(repoDir);
		selActionArray.push(action);
	} else {
		arrayIndex = selRowArray.indexOf(row);
		selRowArray.splice(arrayIndex,1);
		selRepoDirArray.splice(arrayIndex,1);
		selActionArray.splice(arrayIndex,1);
	};
}

commitChanges = function() {
	if(selRowArray.length>0) {
		if (document.fcForm.title.value!="Title..." && document.fcForm.message.value!="Message...") {
			top.document.getElementById('blackMask').style.display = "block";
			selRowValue = "";
			selDirValue = "";
			selRepoValue = "";
			selActionValue = "";
			for (i=0;i<selRowArray.length;i++) {
				selRowValue += selRowArray[i];
				if (selActionArray[i]=="changed") {
					selDirValue += selRepoDirArray[i].split('@')[0];
					selRepoValue += selRepoDirArray[i].split('@')[1];
				}
				if (selActionArray[i]=="new") {
					selDirValue += selRepoDirArray[i];
					selRepoValue += "";
				}
				if (selActionArray[i]=="deleted") {
					selDirValue += "";
					selRepoValue += selRepoDirArray[i];
				}
				selActionValue += selActionArray[i];
				if (i<selRowArray.length-1) {
					selRowValue += ",";
					selDirValue += ",";
					selRepoValue += ",";
					selActionValue += ",";
				}
			}
			document.fcForm.rowID.value = selRowValue;
			document.fcForm.gitRepo.value = "<?php echo $repo;?>";
			document.fcForm.dir.value = selDirValue;
			document.fcForm.repo.value = selRepoValue;
			document.fcForm.action.value = selActionValue;
			document.fcForm.submit();
		} else {
			alert('Please enter a title & message for the commit');		
		}
	} else {
		alert('Please select some files/folders to commit');
	}
	return false;
}
	
gitCommand('repo.show','<?php echo strClean($_POST['repo']);?>');
</script>
	
<iframe name="fileControl" style="display: none"></iframe>
	
</body>
	
</html>