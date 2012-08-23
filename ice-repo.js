pullContent = function(thisRow,thisPath,thisRepo,thisAction) {
	get('blackMask','top').style.display = "block";
	if (thisRow=="selected") {
		top.selRowValue = "";
		top.selDirValue = "";
		top.selRepoValue = "";
		top.selActionValue = "";
		for (i=0;i<top.selRowArray.length;i++) {
			top.selRowValue += top.selRowArray[i];
			if (top.selActionArray[i]=="changed") {
				repoUser = top.selRepoDirArray[i].split('@')[1].split('/')[0];
				repoName = top.selRepoDirArray[i].split('@')[1].split('/')[1];
				top.selDirValue += top.selRepoDirArray[i].split('@')[0];
				top.selRepoValue += top.selRepoDirArray[i].split('@')[1].replace(repoUser+"/"+repoName+"/","");
			}
			if (top.selActionArray[i]=="new") {
				top.selDirValue += top.selRepoDirArray[i];
				top.selRepoValue += "";
			}
			if (top.selActionArray[i]=="deleted") {
				repoUser = top.selRepoDirArray[i].split('/')[0];
				repoName = top.selRepoDirArray[i].split('/')[1];
				top.selDirValue += "";
				top.selRepoValue += top.selRepoDirArray[i].replace(repoUser+"/"+repoName+"/","");
			}
			top.selActionValue += top.selActionArray[i];
			if (i<top.selRowArray.length-1) {
				top.selRowValue += ",";
				top.selDirValue += ",";
				top.selRepoValue += ",";
				top.selActionValue += ",";
			}
		}
	} else {
		top.selRowValue = thisRow;
		top.selDirValue = thisPath;
		top.selRepoValue = thisRepo;
		top.selActionValue = thisAction;
	}
	top.fcFormAlias.rowID.value = top.selRowValue;
	top.fcFormAlias.dir.value = top.selDirValue;
	top.fcFormAlias.repo.value = top.selRepoValue;
	top.fcFormAlias.action.value = "PULL:"+top.selActionValue;
	top.fcFormAlias.submit();
}
	
get = function(elem,context) {
	return context ? window[context].document.getElementById(elem) : document.getElementById(elem);
}
	
updateInfo = function(context) {
	get('infoPane',context).innerHTML = "<b style='font-size: 18px'>INFO:</b><br><br><b>"+top.rowCount+" files</b><br><br>"+top.changedCount+" changed<br>"+top.newCount+" new<br>"+top.deletedCount+" deleted";		
}