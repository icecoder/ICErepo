<?php
session_start();
if ($_SESSION['userLevel'] == 0) {
	die("Sorry, you need to be logged in to use ICErepo");
}
// returns converted entities where there are HTML entity equivalents
function strClean($var) {
	return htmlentities($var, ENT_QUOTES, "UTF-8");
}

// returns a number, whole or decimal or null
function numClean($var) {
	return is_numeric($var) ? floatval($var) : false;
}

$repoPath = strClean($_POST['repoPath']);
$gitRepo = strClean($_POST['gitRepo']);
$path = strClean($_POST['path']);
$rowID = strClean($_POST['rowID']);
$repo = strClean($_POST['repo']);
$dir = strClean($_POST['dir']);
$action = str_replace("PULL:","",str_replace("SAVEPULLS:","",strClean($_POST['action'])));
?>
<!DOCTYPE html>
<html>
<head>
<title>ICErepo v<?php echo $version;?></title>
<script src="lib/underscore-min.js"></script>
<script src="lib/base64.js"></script>
<script src="lib/github.js"></script>
<script src="lib/difflib.js"></script>
<link rel="stylesheet" type="text/css" href="ice-repo.css">
</head>

<body>
	
<script>
	fullRepoPath='<?php echo $repo;?>';
	gitRepo='<?php echo $gitRepo;?>';
	var github = new Github(<?php
	if ($_POST['token']!="") {
		echo '{token: "'.strClean($_POST['token']).'", auth: "oauth"}';
	} else{
		echo '{username: "'.strClean($_POST['username']).'", password: "'.strClean($_POST['password']).'", auth: "basic"}';
	}?>);
	repoUser = gitRepo.split('/')[0];
	repoName = gitRepo.split('/')[1];
	filePath = fullRepoPath.replace(repoUser+"/"+repoName+"/","");
	var repo = github.getRepo(repoUser,repoName);
	
	removeFirstArrayItems = function() {
		rowIDArray.splice(0,1);
		repoArray.splice(0,1);
		dirArray.splice(0,1);
		actionArray.splice(0,1);	
	}
		
	hideRow = function(row) {
		parent.document.getElementById('checkbox'+row).checked=false;
		parent.updateSelection(parent.document.getElementById('checkbox'+row));
		parent.document.getElementById('row'+row).style.display = parent.document.getElementById('row'+row+'Content').style.display = "none";
	}
</script>

<?php if ($_POST['action']=="view") {
	$fileContents = file_get_contents($dir); ?>

	<form name="fcForm">
	<textarea name="fileContents"><?php echo htmlentities($fileContents); ?></textarea>
	<textarea name="repoContents"></textarea>
	</form>
	
	<script>
	rowID = <?php echo $rowID; ?>;
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
	</script>
<?php } else if (substr($_POST['action'],0,5)=="PULL:") { ?>
	<?php
	$rowIDArray = explode(",",$rowID);
	$repoArray = explode(",",$repo);
	$dirArray = explode(",",$dir);
	$actionArray = explode(",",$action);
	?>
	<form name="fcForm" action="file-control.php" method="POST">
	<?php
	echo '<input type="hidden" name="rowID" value="'.$rowID.'">';
	echo '<input type="hidden" name="repo" value="'.$repo.'">';
	echo '<input type="hidden" name="dir" value="'.$dir.'">';
	echo '<input type="hidden" name="action" value="SAVEPULLS:'.$action.'">';
	echo '<input type="hidden" name="path" value="'.$path.'">';
	for ($i=0;$i<count($rowIDArray);$i++) {
		if ($repoArray[$i]!="") {
			echo '<textarea name="repoContents'.$rowIDArray[$i].'"></textarea>';
		}
	}
	?>
	</form>
	<script>
	<?php
	$rowIDVal = $repoVal = $dirVal = $actionVal = "";
	for ($i=0;$i<count($rowIDArray);$i++) {
		$rowIDVal .= $rowIDArray[$i];
		$repoVal .= "'".$repoArray[$i]."'";
		$dirVal .= "'".$dirArray[$i]."'";
		$actionVal .= "'".$actionArray[$i]."'";
		if ($i<count($rowIDArray)-1) {
			$rowIDVal .= ",";
			$repoVal .= ",";
			$dirVal .= ",";
			$actionVal .= ",";
		}
	}
	?>
	rowIDArray = [<?php echo $rowIDVal;?>];
	repoArray = [<?php echo $repoVal;?>];
	dirArray = [<?php echo $dirVal;?>];
	actionArray = [<?php echo $actionVal;?>];
	getData = function() {
		if (actionArray[0]!="new") {
			repo.read('master', repoArray[0], function(err, data) {
				document.fcForm['repoContents'+rowIDArray[0]].innerHTML=data;
				if(!err) {
					removeFirstArrayItems();
					rowIDArray.length>0 ? getData() : document.fcForm.submit();
				} else {
					alert('Sorry, there was an error reading '+repoArray[0]);
				}
			});
		} else {
			removeFirstArrayItems();
			rowIDArray.length>0 ? getData() : document.fcForm.submit();
		}
	}
	getData();
	</script>
<?php } else if (substr($_POST['action'],0,10)=="SAVEPULLS:") { ?>
	<?php
		$rowIDArray = explode(",",$rowID);
		$repoArray = explode(",",$repo);
		$dirArray = explode(",",$dir);
		$actionArray = explode(",",$action);
		for ($i=0;$i<count($rowIDArray);$i++) {
			if ($actionArray[$i]!="new") {
				$dirs = explode("/",$repoArray[$i]);
				$relDir = "";
				for ($j=0;$j<count($dirs)-1;$j++) {
					$relDir .= "/".$dirs[$j];
					if (!is_dir($path.$relDir)) {
						mkdir($path.$relDir, 0755);
					}
				}
				$fh = fopen($path."/".$repoArray[$i], 'w') or die("<script>alert('Sorry, there was a problem pulling ".$repoArray[$i].". Either the file is unavailable on Github or server permissions aren\'t allowing it to be created/updated.');top.document.getElementById('blackMask').style.display='none';</script>");
				fwrite($fh, $_POST['repoContents'.$rowIDArray[$i]]);
				fclose($fh);
// 				echo "<script>removeFirstArrayItems()</script>";
				echo "<script>hideRow(".$rowIDArray[$i].")</script>";
			} else {
				is_dir($dir) ? $success = rmdir($dir) : $success = unlink($dir);
				if (!$success) {
					echo "<script>alert('Sorry, couldn\'t delete ".$dir."\\n\\nMaybe you need to give file permissions for it to be deleted?')</script>";
				} else {
					echo "<script>hideRow(".$rowIDArray[$i].")</script>";
				}
			}
		}
		echo "<script>top.document.getElementById('blackMask').style.display = 'none'</script>";
	?>
<?php } else { ?>
	<?php
	$rowIDArray = explode(",",$rowID);
	$repoArray = explode(",",$repo);
	$dirArray = explode(",",$dir);
	$actionArray = explode(",",$action);
	?>
	<form name="fcForm">
	<?php
	for ($i=0;$i<count($rowIDArray);$i++) {
		if ($dirArray[$i]!="") {
			$fileContents = file_get_contents($dirArray[$i]);
			echo '<textarea name="fileContents'.$rowIDArray[$i].'">'.htmlentities($fileContents).'</textarea>';
		}
	}
	?>
	</form>
	<script>
	<?php
	$rowIDVal = $repoVal = $dirVal = $actionVal = "";
	for ($i=0;$i<count($rowIDArray);$i++) {
		$rowIDVal .= $rowIDArray[$i];
		$repoVal .= "'".$repoArray[$i]."'";
		$dirVal .= "'".$dirArray[$i]."'";
		$actionVal .= "'".$actionArray[$i]."'";
		if ($i<count($rowIDArray)-1) {
			$rowIDVal .= ",";
			$repoVal .= ",";
			$dirVal .= ",";
			$actionVal .= ",";
		}
	}
	?>
	rowIDArray = [<?php echo $rowIDVal;?>];
	repoArray = [<?php echo $repoVal;?>];
	dirArray = [<?php echo $dirVal;?>];
	actionArray = [<?php echo $actionVal;?>];

	// Add or Update files...
	ffAddOrUpdate = function(row,gitRepo,action) {
		repo.write('master', gitRepo, document.fcForm['fileContents'+row].value, '<?php echo strClean($_POST['title']); ?>\n\n'+parent.document.fcForm.message.value, function(err) {
			if(!err) {
				removeFirstArrayItems();
				hideRow(row);
				rowIDArray.length>0 ? startProcess() : top.document.getElementById('blackMask').style.display = "none";
			} else {
				alert('Sorry, there was an error adding '+gitRepo);
			}
		});
	}
	// Delete files...
	ffDelete = function(row,gitRepo,action) {
		repo.remove('master', gitRepo, function(err) {
			if(!err) {
				removeFirstArrayItems();
				hideRow(row);
				rowIDArray.length>0 ? startProcess() : top.document.getElementById('blackMask').style.display = "none";
			} else {
				alert('Sorry, there was an error deleting '+gitRepo);
			}
		});
	}

	startProcess = function() {
		if(actionArray[0]=="changed"||actionArray[0]=="new") {
			if(actionArray[0]=="changed")	{repoLoc = repoArray[0].replace(repoUser+"/"+repoName+"/","")}
			if(actionArray[0]=="new")		{repoLoc = dirArray[0].replace('<?php echo $path;?>/','')}
			ffAddOrUpdate(rowIDArray[0],repoLoc,actionArray[0]);
		}
		if(actionArray[0]=="deleted") {
			repoLoc = repoArray[0].replace(repoUser+"/"+repoName+"/","");
			ffDelete(rowIDArray[0],repoLoc,actionArray[0]);
		}
	}
	startProcess();
	</script>
<?php } ?>
	
</body>
	
</html>