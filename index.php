<?php
/**
 * PHP Bookmark Syncer
 *
 * @version 0.9.14
 * @author Offerel
 * @copyright Copyright (c) 2018, Offerel
 * @license GNU General Public License, version 3
 */
if (!isset ($_SESSION['fauth'])) {
    session_start();
}

$database = __DIR__.'/database/bookmarks.db';
$logfile = "/var/log/bookmark.log";
$realm = "Bookmarks";
$loglevel = 2;
$sender = "bookmarks@yourdomain.com";

set_error_handler("e_log");

if(!file_exists($database)) initDB($database);

if(!isset($_SERVER['PHP_AUTH_USER']) || $_SERVER['PHP_AUTH_USER'] === "" || !isset($_SERVER['PHP_AUTH_PW']) || !isset($_SESSION['fauth'])) {
	doLogin($database,$realm);
}
else {
	$db = new PDO('sqlite:'.$database);
	e_log(8,"Update lastseen date for user");
	$query = "UPDATE `users` SET `userLastLogin`=".time()." WHERE `userName`='".$_SERVER['PHP_AUTH_USER']."'";
	e_log(9,$query);
	$db->exec($query);
	$db = NULL;
	session_unset();
}

if(!isset($userData)) $userData = getUserdata($database);

if(isset($_POST['bmedt'])) {
	$db = new PDO('sqlite:'.$database);
	$query = "UPDATE `bookmarks` SET `bmTitle` = '".$_POST['title']."', `bmURL` = '".$_POST['url']."', `bmAdded` = '".round(microtime(true) * 1000)."' WHERE `bmID` = '".$_POST['id']."' AND `userID` = ".$userData['userID'];
	$db->exec($query);
	$count = $db = NULL;
	if($count > 0)
		die(true);
	else
		die(false);
}

if(isset($_POST['bmmv'])) {
	$db = new PDO('sqlite:'.$database);
	$query = "SELECT MAX(bmIndex)+1 AS 'index' FROM `bookmarks` WHERE `bmParentID` = '".$_POST['folder']."'";
	$statement = $db->prepare($query);
	$statement->execute();
	$folderData = $statement->fetchAll(PDO::FETCH_ASSOC);
	$query = "UPDATE `bookmarks` SET `bmIndex` = ".$folderData[0]['index'].", `bmParentID` = '".$_POST['folder']."', `bmAdded` = '".round(microtime(true) * 1000)."' WHERE `bmID` = '".$_POST['id']."' AND `userID` = ".$userData['userID'];
	$count = $db->exec($query);
	$db = NULL;
	if($count > 0)
		die(true);
	else
		die(false);
}

if(isset($_POST['arename'])) {
	$db = new PDO('sqlite:'.$database);
	$query = "UPDATE `clients` SET `cname` = '".$_POST['nname']."' WHERE `uid` = ".$userData['userID']." AND `cid` = '".$_POST['cido']."'";
	$count = $db->exec($query);
	$db = NULL;
	
	if($count > 0)
		die(true);
	else
		die(false);
}

if(isset($_POST['adel'])) {
	$db = new PDO('sqlite:'.$database);
	$query = "DELETE FROM `clients` WHERE `uid` = ".$userData['userID']." AND `cid` = '".$_POST['cido']."'";
	$count = $db->exec($query);
	$db = NULL;
	if($count > 0)
		die(true);
	else
		die(false);
}

if(isset($_POST['muedt'])) {
	$del = false;
	$headers = "From: Bookmark Syncer <$sender>";
	$url = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];
	switch($_POST['muedt']) {
		case "Add User":
			$pwd = password_hash($_POST['npwd'],PASSWORD_DEFAULT);
			$level = $_POST['userLevel'] + 1;
			$query = "INSERT INTO `users` (`userName`,`userType`,`userHash`) VALUES ('".$_POST['nuser']."', '".$level."', '".$pwd."')";
			e_log(8,"Adding new user ".$_POST['nuser']);
			$message = "Hello,\r\n\r\na account with the following credentials is created and stored encrypted on the database:\r\nE-Mail: ".$_POST['nuser']."\r\nPassword: ".$_POST['npwd']."\r\n\r\nYou can login at $url";
			if(!mail ($_POST['nuser'], "Account created",$message,$headers)) e_log(1,"Error sending data for created user account to user");
			break;
		case "Edit User":
			$pwd = password_hash($_POST['npwd'],PASSWORD_DEFAULT);
			$level = $_POST['userLevel'] + 1;
			$query = "UPDATE `users` SET `userName`= '".$_POST['nuser']."', `userType`= '".$level."', `userHash`= '".$pwd."' WHERE `userID` = ".$_POST['userSelect'].";";
			e_log(8,"Updating user ".$_POST['nuser']);
			$message = "Hello,\r\n\r\nyour account is changed and stored encrypted on the database. Your new credentials are:\r\nE-Mail: ".$_POST['nuser']."\r\nPassword: ".$_POST['npwd']."\r\n\r\nYou can login at $url";
			if(!mail ($_POST['nuser'], "Account changed",$message,$headers)) e_log(1,"Error sending data for changed user account to user");
			break;
		case "Delete User":
			$query = "DELETE FROM `users` WHERE `userID` = ".$_POST['userSelect'];
			$del = true;
			e_log(8,"Removing user ".$_POST['nuser']);
			$message = "Hello,\r\n\r\nyour account '".$_POST['nuser']."' and all it's data is removed from $url.";
			if(!mail ($_POST['nuser'], "Account removed",$message,$headers)) e_log(1,"Error sending data for created user account to user");
			break;
		default:
			e_log(1,"Unknown action by managing users");
			die("Unknown action by managing users");
			break;
	}
	
	$db = new PDO('sqlite:'.$database);
	e_log(9,$query);
	$db->exec($query);
	if($del) {
		$query = "DELETE FROM `clients` WHERE `userID` = ".$_POST['userSelect'];
		e_log(8,"Removing clients for user ".$_POST['nuser']);
		e_log(9,$query);
		$db->exec($query);
		$query = "DELETE FROM `bookmarks` WHERE `userID` = ".$_POST['userSelect'];
		e_log(8,"Removing bookmarks for user ".$_POST['nuser']);
		e_log(9,$query);
		$db->exec($query);
	}
	$db = NULL;
}

if(isset($_POST['mlog'])) die(file_get_contents($logfile));

if(isset($_POST['mclear'])) {
	file_put_contents($logfile,"");
	die();
}

if(isset($_POST['madd'])) {
	$bmParentID = $_POST['folder'];
	$bmURL = $_POST['url'];
	$bmID = unique_code(12);
	$bmIndex = getIndex($bmParentID);
	$bmTitle = getSiteTitle($bmURL);
	$bmAdded = round(microtime(true) * 1000);
	$userID = $userData['userID'];

	if($bmTitle === "") {
		e_log(1,"Titel is missing, adding bookmark failed.");
		die("Titel is missing, adding bookmark failed.");
	}
	else {
		try {
			$db = new PDO('sqlite:'.$database);
			$db->exec("INSERT INTO `bookmarks` (`bmID`,`bmParentID`,`bmIndex`,`bmTitle`,`bmType`,`bmURL`,`bmAdded`,`userID`) VALUES ('".$bmID."', '".$bmParentID."', ".$bmIndex.", '".$bmTitle."', 'bookmark', '".$bmURL."', ".$bmAdded.", ".$userID.")");
		}
		catch(PDOException $e) {
			e_log(1,'Exception : '.$e->getMessage());
		}
		$db = NULL;
		e_log(8,"Manual added bookmark for ".$userData['userName']);
		e_log(9,"INSERT INTO `bookmarks` (`bmID`,`bmParentID`,`bmIndex`,`bmTitle`,`bmType`,`bmURL`,`bmAdded`,`userID`) VALUES ('".$bmID."', '".$bmParentID."', ".$bmIndex.", '".$bmTitle."', 'bookmark', '".$bmURL."', ".$bmAdded.", ".$userID.")");
	}

	die(bmTree($userData,$database));
}

if(isset($_POST['mdel'])) {
	$bmID = $_POST['id'];
	$userID = $userData['userID'];

	try {
		$db = new PDO('sqlite:'.$database);
		$query = "UPDATE `bookmarks` SET `bmAction`= 1, `bmAdded`= '".round(microtime(true) * 1000)."' WHERE `bmID` = '".$bmID."' AND `userID` = ".$userID;
		$db->exec($query);
	}
	catch(PDOException $e) {
		e_log(1,'Exception : '.$e->getMessage());
	}
	$db = NULL;
	e_log(8,"Manual deleted bookmark ".$bmID);
	e_log(9,$query);

	die(bmTree($userData,$database));
}

if(isset($_POST['pupdate'])) {
	e_log(8,"Userchange: Updating user password started OK");
	if($_POST['opassword'] != "" && $_POST['npassword'] !="" && $_POST['cpassword'] !="") {
		e_log(8,"Userchange: Data complete entered OK");
		if(password_verify($_POST['opassword'],$userData['userHash'])) {
			e_log(8,"Userchange: Verify original password OK");
			if($_POST['npassword'] === $_POST['cpassword']) {
				e_log(8,"Userchange: New and confirmed password OK");
				if($_POST['npassword'] != $_POST['opassword']) {
					e_log(8,"Userchange: Old and new password NOT identical");
					$password = password_hash($_POST['npassword'],PASSWORD_DEFAULT);
					try {
						$db = new PDO('sqlite:'.$database);
						$db->exec("UPDATE `users` SET `userHash`='".$password."' WHERE `userID`=".$userData['userID']);
						e_log(9,"UPDATE `users` SET `userHash`='".$password."' WHERE `userID`=".$userData['userID']);
					}
					catch(PDOException $e) {
						e_log(1,'Exception : '.$e->getMessage());
					}
					$db = NULL;
					e_log(8,"Userchange: Password changed OK");
					$_SERVER['PHP_AUTH_USER'] = "";
					$_SERVER['PHP_AUTH_PW'] = "";
				}
				else {
					e_log(2,"Userchange: Old and new password identical, user not changed");
				}
			}
			else {
				e_log(2,"Userchange: New and confirmed password NOT OK");
			}
		}
		else {
			e_log(2,"Userchange: Verify original password NOT OK");
		}
	}
	else {
		e_log(2,"Userchange: Data missing, NOT OK");
	}
	die();
}

if(isset($_POST['uupdate'])) {
	e_log(8,"Userchange: Updating user name started");
	if($_POST['opassword'] != "") {
		e_log(8,"Userchange: Data complete entered");
		if(password_verify($_POST['opassword'],$userData['userHash'])) {
			e_log(8,"Userchange: Verify original password");
			try {
				$db = new PDO('sqlite:'.$database);
				$db->exec("UPDATE `users` SET `userName`='".$_POST['username']."' WHERE `userID`=".$userData['userID']);
				e_log(9,"UPDATE `users` SET `userName`='".$_POST['username']."' WHERE `userID`=".$userData['userID']);
			}
			catch(PDOException $e) {
				e_log(1,'Exception : '.$e->getMessage());
			}
			$db = NULL;
			e_log(8,"Userchange: Username changed");
			$_SERVER['PHP_AUTH_USER'] = "";
			$_SERVER['PHP_AUTH_PW'] = "";
		}
		else {
			e_log(2,"Userchange: Failed to verify original password");
		}
	}
	else {
		e_log(2,"Userchange: Data missing");
	}
	die();
}

if(isset($_POST['logout'])) {
	e_log(8,"Logout user ".$_SERVER['PHP_AUTH_USER']);
	unset($_SERVER['PHP_AUTH_USER']);
	unset($_SERVER['PHP_AUTH_PW']);

	header('WWW-Authenticate: Basic realm="'.$realm.'", charset="UTF-8"');
	http_response_code(401);

	die("User is now logged out.");
}

if(isset($_POST['caction'])) {
	switch($_POST['caction']) {
		case "addmark":
			$bookmark = json_decode(rawurldecode($_POST['bookmark']),true);
			$client = $_POST['client'];
			$ctype = $_POST['ctype'];
			$ctime = $bookmark["added"];
			if($bookmark['type'] == 'bookmark' && isset($bookmark['url'])) {
				$cRes = updateClient($database, $client, $ctype, $userData, $ctime);
				die(addBookmark($database, $userData, $bookmark));
			}
			else if($bookmark['type'] == 'folder') {
				$cRes = updateClient($database, $client, $ctype, $userData, $ctime);
				die(addFolder($database, $userData, $bookmark));
			}
			else {
				e_log(1,"This bookmark is not added, some parameters are missing");
				die(false);
			}
			break;
		case "movemark":
			$bookmark = json_decode($_POST['bookmark'],true);
			$client = $_POST['client'];
			$ctype = $_POST['ctype'];
			$ctime = round(microtime(true) * 1000);
			$cRes = updateClient($database, $client, $ctype, $userData, $ctime);
			$bRes = moveBookmark($database, $userData, $bookmark);
			break;
		case "delmark":
			$bookmark = json_decode(rawurldecode($_POST['bookmark']),true);
			$client = $_POST['client'];
			$ctype = $_POST['ctype'];
			$ctime = round(microtime(true) * 1000);
			if($bookmark['type'] == 'bookmark' && isset($bookmark['url'])) {
				$cRes = updateClient($database, $client, $ctype, $userData, $ctime);
				die(delBookmark($database, $userData, $bookmark));
			}
			else if($bookmark['type'] == 'folder') {
				$cRes = updateClient($database, $client, $ctype, $userData, $ctime);
				die(delFolder($database, $userData, $bookmark));
			}
			else {
				e_log(1,"This bookmark could not deleted, parameters are missing");
				die(false);
			}
			break;
		case "startup":
			$client = $_POST['client'];
			$ctype = $_POST['ctype'];
			$ctime = round(microtime(true) * 1000);
			die(json_encode(getChanges($database, $client, $ctype, $userData, $ctime),JSON_UNESCAPED_SLASHES ));
			break;
		case "import":
			$jmarks = json_decode(rawurldecode($_POST['bookmark']),true);
			$client = $_POST['client'];
			$ctype = $_POST['ctype'];
			$ctime = round(microtime(true) * 1000);
			delUsermarks($userData['userID']);
			$armarks = parseJSON($jmarks);
			$response = importMarks($armarks,$userData['userID'],$database);
			die($response);
			break;
		case "export":
			$client = $_POST['client'];
			$ctype = $_POST['ctype'];
			$ctime = round(microtime(true) * 1000);
			$usermarks = json_encode(getBookmarks($userData['userID'],$database));
			die($usermarks);
			break;
		default:
			die("Unknown Action");
	}
	die();
}

echo htmlHeader($userData);
$bmTree = bmTree($userData,$database);
echo "<div id='bookmarks'>$bmTree</div>";
echo "<div id='hmarks'>$bmTree</div>";
echo htmlFooter($userData['userID']);

function delFolder($database, $ud, $bm) {
	$db = new PDO('sqlite:'.$database);
	e_log(8,"Remove folder");
	$query = "UPDATE `bookmarks` SET `bmAction`= 1, `bmAdded`= '".round(microtime(true) * 1000)."' WHERE `bmID` = '".$bm['id']."' AND `userID` = ".$ud['userID'];
	e_log(9,$query);
	$db->exec($query);
	e_log(8,"Remove bookmarks for that folder");
	$query = "UPDATE `bookmarks` SET `bmAction`= 1, `bmAdded`= '".round(microtime(true) * 1000)."' WHERE `bmParentID` = '".$bm['id']."' AND `userID` = ".$ud['userID'];
	e_log(9,$query);
	$db->exec($query);
	return true;
}

function delBookmark($database, $ud, $bm) {
	$db = new PDO('sqlite:'.$database);
	e_log(8,"Remove bookmark");
	$query = "UPDATE `bookmarks` SET `bmAction`= 1, `bmAdded`= '".round(microtime(true) * 1000)."' WHERE `bmURL` = '".$bm['url']."' AND `userID` = ".$ud['userID'];
	e_log(9,$query);
	$db->exec($query);
	return true;
}

function moveBookmark($database, $ud, $bm) {
	$db = new PDO('sqlite:'.$database);
	e_log(8,"Bookmark seems to be moved, checking current folder data");
	$query = "SELECT MAX(`bmIndex`) +1 AS `nindex`, `bmParentID` FROM `bookmarks` WHERE `bmParentID` IN (SELECT `bmID` FROM `bookmarks` WHERE `bmType` = 'folder' AND `bmTitle` = '".$bm['nfolder']."' AND `bmIndex` = ".$bm['folderIndex']." AND `userID` = ".$ud['userID'].")";
	$statement = $db->prepare($query);
	e_log(9,$query);
	$statement->execute();
	$folderData = $statement->fetchAll(PDO::FETCH_ASSOC);
	
	e_log(8,"Checking bookmark data before moving it");
	$query = "SELECT * FROM `bookmarks` WHERE `bmID` = '".$bm["id"]."'";
	$statement = $db->prepare($query);
	e_log(9,$query);
	$statement->execute();
	$oldData = $statement->fetchAll(PDO::FETCH_ASSOC);
	
	if (!empty($folderData) && !empty($oldData)) {
		if(($folderData[0]['bmParentID'] != $oldData[0]['bmParentID']) || ($oldData[0]['bmIndex'] != $bm['index'])) {
			e_log(8,"Folder or Position changed, moving bookmark");
			$query = "DELETE FROM `bookmarks` WHERE `bmID` = '".$bm["id"]."'";
			e_log(9,$query);
			$db->exec($query);
			e_log(8,"Re-Add bookmark on new position");
			$query = "INSERT INTO `bookmarks` (`bmID`,`bmParentID`,`bmIndex`,`bmTitle`,`bmType`,`bmURL`,`bmAdded`,`userID`) VALUES ('".$bm['id']."', '".$folderData[0]['bmParentID']."', ".$bm['index'].", '".$oldData[0]['bmTitle']."', '".$oldData[0]['bmType']."', '".$oldData[0]['bmURL']."', ".$oldData[0]['bmAdded'].", ".$ud["userID"].")";
			e_log(9,$query);
			$db->exec($query);
			return true;
		}
		else {
			e_log(8,"Bookmark not moved, exiting");
			return false;
		}
	}
	else {
		return false;
	}
}

function addFolder($database, $ud, $bm) {
	try {
		$db = new PDO('sqlite:'.$database);
	}
	catch (PDOException $e) {
		e_log(1,'DB connection failed: '.$e->getMessage());
	}
	
	$query = "SELECT COUNT(*) AS bmcount  FROM `bookmarks` WHERE `bmTitle` = '".$bm['title']."' AND `bmParentID` = (SELECT `bmID` FROM `bookmarks` WHERE `bmTitle` = '".$bm['nfolder']."') AND `userID` = ".$ud['userID'];
	$statement = $db->prepare($query);
	$statement->execute();
	$fdExistData = $statement->fetchAll(PDO::FETCH_ASSOC)[0]["bmcount"];
	if($fdExistData > 0) {
		e_log(8,"Folder not added, it exists already for this user");
		return false;
	}
	
	e_log(8,"Get folder data for adding folder");
	$query = "SELECT MAX(`bmIndex`) +1 AS `nindex`, `bmParentId` FROM `bookmarks` WHERE `bmParentId` IN (SELECT `bmId` FROM `bookmarks` WHERE `bmType` = 'folder' AND `bmTitle` = '".$bm['nfolder']."' AND `userId` = ".$ud['userID'].")";
	$statement = $db->prepare($query);
	e_log(9,$query);
	
	$statement->execute();
	$folderData = $statement->fetchAll();
	
	if (!empty($folderData)) {
		e_log(8,"Add folder '".$bm['title']."'");
		$query = "INSERT INTO `bookmarks` (`bmID`,`bmParentID`,`bmIndex`,`bmTitle`,`bmType`,`bmAdded`,`userID`) VALUES ('".$bm['id']."', '".$folderData[0]['bmParentID']."', ".$folderData[0]['nindex'].", '".$bm['title']."', '".$bm['type']."', ".$bm['added'].", ".$ud["userID"].")";
		e_log(9,$query);
		$db->exec($query);
		$db = NULL;
		return true;
	}
	else {
		$db = NULL;
		e_log(1,"Couldn't add folder");
		return false;
	}
}

function addBookmark($database, $ud, $bm) {
	try {
		$db = new PDO('sqlite:'.$database);
	}
	catch (PDOException $e) {
		e_log(1,'DB connection failed: '.$e->getMessage());
	}

	$query = "SELECT COUNT(*) AS bmcount FROM `bookmarks` WHERE `bmUrl` = '".$bm['url']."' and userID = ".$ud["userID"];
	$statement = $db->prepare($query);
	$statement->execute();
	$bmExistData = $statement->fetchAll(PDO::FETCH_ASSOC)[0]["bmcount"];
	if($bmExistData > 0) {
		e_log(8,"Bookmark not added, it exists already for this user");
		return false;
	}

	e_log(8,"Get folder data for adding bookmark");
	$query = "SELECT MAX(`bmIndex`) +1 AS `nindex`, `bmParentId` FROM `bookmarks` WHERE `bmParentId` IN (SELECT `bmId` FROM `bookmarks` WHERE `bmType` = 'folder' AND `bmTitle` = '".$bm['nfolder']."' AND `userId` = ".$ud['userID'].")";
	$statement = $db->prepare($query);
	e_log(9,$query);
	
	$statement->execute();
	$folderData = $statement->fetchAll();
	
	if (!empty($folderData)) {
		e_log(8,"Add bookmark '".$bm['title']."'");
		$query = "INSERT INTO `bookmarks` (`bmID`,`bmParentID`,`bmIndex`,`bmTitle`,`bmType`,`bmURL`,`bmAdded`,`userID`) VALUES ('".$bm['id']."', '".$folderData[0]['bmParentID']."', ".$folderData[0]['nindex'].", '".$bm['title']."', '".$bm['type']."', '".$bm['url']."', ".$bm['added'].", ".$ud["userID"].")";
		e_log(9,$query);
		$db->exec($query);
		$db = NULL;
		return true;
	}
	else {
		$db = NULL;
		e_log(1,"Couldn't add bookmark");
		return false;
	}
}

function getChanges($dbase, $cl, $ct, $ud, $time) {
	$db = new PDO('sqlite:'.$dbase);
	$uid = $ud["userID"];
	e_log(8,"Browser startup sync started, get client data");
	$query = "SELECT `lastseen` FROM `clients` WHERE `cid` = '".$cl."' AND `uid` = $uid AND `ctype` = '".$ct."'";
	$statement = $db->prepare($query);
	e_log(9,$query);
	$statement->execute();
	$clientData = $statement->fetch();

	if($clientData) {
		e_log(8,"Get changed bookmarks for client $cl");
		$query = "SELECT b.bmID as fdID, b.bmTitle AS fdName, b.bmIndex as fdIndex, a.bmIndex, a.bmTitle, a.bmType, a.bmURL, a.bmAdded, a.bmModified, a.bmAction FROM bookmarks a INNER JOIN bookmarks b ON b.bmID = a.bmParentID WHERE (a.bmAdded >= ".$clientData["lastseen"]." AND a.userID = $uid) OR (a.bmAction = 1 and a.userID = $uid)";
		$statement = $db->prepare($query);
		e_log(9,$query);
		$statement->execute();
		$bookmarkData = $statement->fetchAll(PDO::FETCH_ASSOC);
	}
	else {
		e_log(8,"Client not found in database, registering now");
		updateClient($dbase, $cl, $ct, $ud, $time);
		return "New client registered for user.";
	}

	if (!empty($bookmarkData)) {
		updateClient($dbase, $cl, $ct, $ud, $time);
		e_log(8,"Try to find bookmarks, which could be completely deleted");
		$query = "SELECT bmID FROM bookmarks WHERE bmAdded <= (SELECT MIN(lastseen) FROM clients WHERE uid = $uid AND lastseen > 1) AND bmAction = 1";
		$statement = $db->prepare($query);
		e_log(9,$query);
		$statement->execute();
		$removeMarks = $statement->fetchAll(PDO::FETCH_ASSOC);

		if (!empty($removeMarks)) {
			e_log(8,count($removeMarks)." are deletable from the database");
			foreach($removeMarks as $bookmark) {
				$query = "DELETE FROM bookmarks WHERE bmID = '".$bookmark["bmID"]."'";
				e_log(9,$query);
				$db->exec($query);
			}
			e_log("Try to compacting database");
			$db->exec("VACUUM");
		}
		else {
			e_log(8,"No bookmarks found to delete from the database");
		}
		return $bookmarkData;
	}
	else {
		e_log(8,"No bookmarks changed since last sync");
		return "No bookmarks added, removed or changed since the client was last seen.";
	}
}

function updateClient($dbase, $cl, $ct, $ud, $time) {
	try {
		$db = new PDO('sqlite:'.$dbase);
	}
	catch (PDOException $e) {
		e_log(1,'DB connection failed: '.$e->getMessage());
	}

	$uid = $ud["userID"];
	$query = "SELECT * FROM `clients` WHERE `cid` = '".$cl."' AND uid = ".$uid;
	$statement = $db->prepare($query);
	e_log(9,$query);
	
	try {
		 $statement->execute();
	}
	catch(PDOException $e) {
		 echo "DB query failed: " . $e->getMessage();
		 e_log(1,"DB query failed: ".$e->getMessage());
		 return false;
	}

	$clientData = $statement->fetchAll();
	if (!empty($clientData)) {
		$query = "UPDATE `clients` SET `lastseen`= '".$time."' WHERE `cid` = '".$cl."';";
		$db->exec($query);
		e_log(8,"Updating lastlogin for client $cl.");
	}
	else {
		$db->exec("INSERT INTO `clients` (`cid`,`ctype`,`uid`,`lastseen`) VALUES ('".$cl."', '".$ct."', ".$uid.", '".$time."')");
		e_log(8,"New client detected. Register client $cl for user ".$ud["userName"]);
	}
	
	return "Client updated.";
}

function bmTree($user,$database) {
	e_log(8,"Build HTML tree from bookmarks");
	$bmTree = makeHTMLTree(getBookmarks($user['userID'],$database));
	
	do {
		$start = strpos($bmTree,"%ID");
		$end = strpos($bmTree,"\n",$start);
		$len = $end - $start;
		$bmTree = substr_replace($bmTree, "", $start, $len);
	} while (strpos($bmTree,"%ID") > 0);
	$bmTree = preg_replace("/[\r\n]\s*[\r\n]/",' ',$bmTree);
	return $bmTree;
}

function getIndex($folder) {
	global $database;
	try {
		$db = new PDO('sqlite:'.$database);
	}
	catch (PDOException $e) {
		e_log(1,'DB connection failed: '.$e->getMessage());
	}
	
	$query = "SELECT MAX(`bmIndex`) FROM `bookmarks` WHERE `bmParentID` = '".$folder."'";
	$statement = $db->prepare($query);

	e_log(8,"Get new bookmark ID");
	e_log(9,$query);
	
	try {
		 $statement->execute();
	}
	catch(PDOException $e) {
		e_log(1,'DB query failed: '.$e->getMessage());
		return false;
	}
	$IndexArr = $statement->fetchAll();
	$maxIndex = $IndexArr[0][0] + 1;
	$db = NULL;
	return $maxIndex;
}

function getSiteTitle($url) {
	e_log(8,"Get titel from remote site");
	$src = file_get_contents($url);
	if(strlen($src) > 0) {
		$src = trim(preg_replace('/\s+/', ' ', $src));
		preg_match("/\<title\>(.*)\<\/title\>/i",$src,$title);
		return $title[1];
	}
}

function getUserdata($database) {
	e_log(8,"Get userdata from the database");
	try {
		$db = new PDO('sqlite:'.$database);
	}
	catch (PDOException $e) {
		e_log(1,'DB connection failed: '.$e->getMessage());
	}
	
	$query = "SELECT * FROM `users` WHERE `userName`='".$_SERVER['PHP_AUTH_USER']."'";
	$statement = $db->prepare($query);
	e_log(9,$query);
	
	try {
		 $statement->execute();
	}
	catch(PDOException $e) {
		echo "DB query failed: " . $e->getMessage();
		e_log(1,"DB query failed: ".$e->getMessage());
		$db = NULL;
		return false;
	}

	$userData = $statement->fetchAll();
	if (!empty($userData)) {
		if(password_verify($_SERVER['PHP_AUTH_PW'], $userData[0]['userHash']))
			$db = NULL;
			return $userData[0];
	}
	else {
		$_SERVER['PHP_AUTH_PW'] = '';
		$_SERVER['PHP_AUTH_USER'] = '';
	}
	$db = NULL;
}

function unique_code($limit) {
	e_log(8,"Building bookmark id");
	return substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, $limit);
}

function e_log($level,$message,$errfile="",$errline="",$output=0) {
	global $logfile,$loglevel;
	switch($level) {
		case 9:
			$mode = "debug";
			break;
		case 8:
			$mode = "notice";
			break;
		case 4:
			$mode = "parse";
			break;
		case 2:
			$mode = "warn";
			break;
		case 1:
			$mode = "error";
			break;
		default:
			$mode = "unknown";
			break;
	}
	if($errfile != "") $message = $message." in ".$errfile." on line ".$errline;
	$user = '';
	if(isset($_SERVER['PHP_AUTH_USER'])) $user = $_SERVER['PHP_AUTH_USER'];
	$line = $_SERVER['REMOTE_ADDR']." - ".$user." [".date("Y/m/d H:i:s")."] [".$mode."] ".$message."\n";

	if($level <= $loglevel) {
		file_put_contents($logfile, $line, FILE_APPEND);
	}
}

function delUsermarks($uid) {
	global $database;
	try {
		$db = new PDO('sqlite:'.$database);
	}
	catch (PDOException $e) {
		e_log(1,'DB connection failed: '.$e->getMessage());
	}
	
	$query = "DELETE FROM `bookmarks` WHERE `UserID`=".$uid;
	$statement = $db->prepare($query);
	e_log(9,$query);
	
	try {
		 $statement->execute();
	}
	catch(PDOException $e) {
		 e_log(1,'DB query failed: '.$e->getMessage());
		 return false;
	}
	$db = NULL;
}

function htmlHeader($ud) {
	global $database;
	$db = new PDO('sqlite:'.$database);
	$htmlHeader = "<!DOCTYPE html><html><head><meta name='viewport' content='width=device-width, initial-scale=1'><base href='".$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].dirname($_SERVER['SCRIPT_NAME'])."/'><script src='scripts/jquery-3.3.1.min.js'></script><link rel=\"stylesheet\" href=\"bookmarks.css\"><link rel=\"shortcut icon\" type=\"image/x-icon\" href=\"images/bookmarks.ico\"><title>Bookmarks</title></head><body>";
	
	$htmlHeader.= "<div id='menu'><a id='mprofile' title=\"Last login: ".date("d.m.y H:i",$ud['userLastLogin'])."\">My Bookmarks</a></div>";
	
	if($ud['userType'] == 2) {
		$userSelect = "<select id='userSelect' name='userSelect'>";
		$userSelect.= "<option value=''>-- Select User --</option>";
		$statement = $db->prepare("SELECT `userID`, `userName` FROM `users`");
		$statement->execute();
		$userList = $statement->fetchAll(PDO::FETCH_ASSOC);
		
		foreach ($userList as $key => $user) {
			$userSelect.= "<option value='".$user['userID']."'>".$user['userName']."</option>";
		}
		$userSelect.= "</select>";
	}
	else {
		$userSelect = "";
	}

	$profileForm = "<div id='profileform' class='bmdialog'><div class='dheader'>Profile</div><form action='".$_SERVER['PHP_SELF']."' method=\"POST\"><div><label for=\"username\">Username:</label>
			<input type=\"text\" name=\"username\" id=\"username\" autocomplete='username' value='".$ud['userName']."'>
			<label for=\"opassword\">Old password:</label><input type=\"password\" id=\"opassword\" name=\"opassword\" autocomplete='current-password' value='' />
			<label for=\"npassword\">New password:</label><input type=\"password\" id=\"npassword\" name=\"npassword\" autocomplete='new-password' value='' />
			<label for=\"cpassword\">Confirm new password:</label><input type=\"password\" id=\"cpassword\" name=\"cpassword\" autocomplete='new-password' value='' />
			<hr />";
	if($ud['userType'] == 2) $profileForm.= "<button type='button' id='mlog' name='mlog'>Logfile</button> <button type='button' id='mngusers' name='mngusers'>Manage Users</button>";
	$profileForm.= "<button type='button' id='clientedt' name='clientedt'>Manage Clients</button>
			<input type=\"submit\" name=\"pupdate\" value=\"Change Password\" /><input type=\"submit\" name=\"uupdate\" value=\"Change User\" /><input type=\"submit\" name=\"logout\" value=\"Logout\" /></div>
			</form></div>";
	$logform = "<div id=\"close\"><button id='mclear'>clear</button> <button id='mclose'>&times;</button></div><textarea id=\"logfile\"></textarea>";

	$mnguserform = "<div id='mnguform' class='bmdialog'><div class='dheader'>Manage Users</div><form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='POST'>
	<label for='userSelect'>Select existing User:</label>$userSelect
	<label for='nuser'>Username:</label><input type='text' required id='nuser' name='nuser' autocomplete='username' value='' />
	<label for='npwd'>Password:</label><input type='password' required id='npwd' name='npwd' autocomplete='password' value='' />
	<label for='userLevel'>Userlevel:</label><select id='userLevel' required name='userLevel'><option value=''>-- Select Level --</option><option value='0'>Normal</option><option value='1'>Admin</option></select>
	<input type='submit' id='muadd' name='muedt' value='Add User' /><input type='submit' id='muedt' name='muedt' value='Edit User' disabled /><input type='submit' id='mudel' name='muedt' value='Delete User' disabled formnovalidate />
	</form></div>";
	
	$query = "SELECT * FROM `clients` WHERE `uid` = ".$ud['userID']." ORDER BY `lastseen` DESC";
	$statement = $db->prepare($query);
	$statement->execute();
	$clientData = $statement->fetchAll(PDO::FETCH_ASSOC);
	
	$clientList = "<ul class='client-list'>";
	foreach($clientData as $key => $client) {
		$cname = $client['cid'];
		if(isset($client['cname'])) $cname = $client['cname'];
		$timestamp = $client['lastseen'] / 1000;
		$lastseen = (date('D, d. M. Y H:i', $timestamp));
		$clientList.= "<li data-type='".$client['ctype']."' id='".$client['cid']."' class='client'><div class='clientname'>$cname<input type='text' name='cname' value='$cname'></div><div class='lastseen'>Last sync: $lastseen</div><div class='rename'>Rename</div><div class='remove'>Delete</div></li>";
	}
	$clientList.= "</ul>";
	
	$mngclientform = "<div id='mngcform' class='bmdialog'><div class='dheader'>Manage Clients</div>$clientList</div>";
	
	if($ud['userType'] != 2) $mnguserform = "";
	$htmlHeader.= $profileForm.$logform.$mnguserform.$mngclientform;
	$db = NULL;
	return $htmlHeader;
}

function htmlFooter($uid) {
	$sFolderOptions = "";
	$sFolderArr = getUserFolders($uid);
	foreach ($sFolderArr as $key => $folder) {
		if($folder['bmID'] === "unfiled_____")
			$sFolderOptions.= "<option selected value='".$folder['bmID']."'>".$folder['bmTitle']."</option>";
		else
			$sFolderOptions.= "<option value='".$folder['bmID']."'>".$folder['bmTitle']."</option>";
	}
	$burl = (isset($_GET['burl'])) ? $_GET['burl'] : "";
	
	$editform = "<div id='bmarkedt' class='bmdialog'><div class='dheader'>Edit Bookmark</div><form id='bmedt' method='POST'>
				<label for='edtitle'>Title:</label><input type='text' id='edtitle' name='edtitle' value=''>
				<label for='edurl'>URL:</label><input type='text' id='edurl' name='edurl' value=''>
				<input type='hidden' id='edid' name='edid' value=''>
				<input type='submit' name='edsave' id='edsave' value='Save' disabled>
				</form></div>";
				
	$moveform = "<div id='bmamove' class='bmdialog'><div class='dheader'>Move Bookmark</div><form id='bmmv' method='POST'>
				<label for='mvtitle'>Title:</label><input type='text' id='mvtitle' name='mvtitle' value='' disabled>
				<label for='mvfolder'>Folder:</label><select id='mvfolder' name='mvfolder'>$sFolderOptions</select>
				<input type='hidden' id='mvid' name='mvid' value=''>
				<input type='submit' name='mvsave' id='mvsave' value='Save' disabled>
				</form></div>";

	$htmlFooter = "<div id='bmarkadd' class='bmdialog'>
					<div class='dheader'>Add Bookmark</div>
					<form id='bmadd' action='?madd' method='POST'>
					<label for='url'>URL:</label>
					<input type='text' id='url' name='url' value='$burl'>
					<label for='folder'>Folder:</label>
					<select id='folder' name='folder'>
						$sFolderOptions
					</select>
					<input type='submit' name='madd' id='save' value='Save' disabled>
					</form></div>
					<input type='search' id='bmsearch' name='bmsearch' value=''><div id='footer'>Add new Bookmark</div>
					<script src='scripts/bookmarks.js'></script>
					</body></html>";
	$menu = "<menu class='menu'><input type='hidden' id='bmid' title='bmtitle' value=''>
			<li class='menu-item'><button type='button' id='btnEdit' class='menu-btn'><span class='menu-text'>Edit</span></button></li>
			<li class='menu-item'><button type='button' id='btnMove' class='menu-btn'><span class='menu-text'>Move</span></button></li>
			<li class='menu-item'><button type='button' id='btnDelete' class='menu-btn'><span class='menu-text'>Delete</span></button></li>
			</menu>";
	return $menu.$editform.$moveform.$htmlFooter;
}

function getUserFolders($uid) {
	global $database;
	try {
		$db = new PDO('sqlite:'.$database);
	}
	catch (PDOException $e) {
		e_log(1,'DB connection failed: '.$e->getMessage());
	}
	
	$statement = $db->prepare("SELECT * FROM `bookmarks` WHERE `bmType` = 'folder' and `userID` = ".$uid);

	e_log(8,"Get folders for user ".$_SERVER['PHP_AUTH_USER']);
	e_log(9,"SELECT * FROM `bookmarks` WHERE `bmType` = 'folder' and `userID` = ".$uid);
	
	try {
		 $statement->execute();
	}
	catch(PDOException $e) {
		e_log(1,'DB query failed: '.$e->getMessage());
		return false;
	}
	$folders = $statement->fetchAll();
	$db = NULL;
	return $folders;
}

function makeHTMLTree($arr) {
	$bookmarks = "";
	
	foreach($arr as $bm) {
		if($bm['bmType'] == "bookmark") {
			$bookmark = "\n<li class=\"file\"><a id='".$bm['bmID']."' title=\"".$bm['bmTitle']."\" target=\"_blank\" href=\"".$bm['bmURL']."\">".$bm['bmTitle']."</a></li>%ID".$bm['bmParentID'];
			$bookmarks = str_replace("%ID".$bm['bmParentID'], $bookmark, $bookmarks);
		}
		
		if($bm['bmType'] == "folder") {
			$nFolder = "\n<li id='f_".$bm['bmID']."'><label for=\"".$bm['bmTitle']."\">".$bm['bmTitle']."</label><input class='ffolder' value='".$bm['bmID']."' id=\"".$bm['bmTitle']."\" type=\"checkbox\"><ol>%ID".$bm['bmID']."\n</ol></li>";
			if(strpos($bookmarks, "%ID".$bm['bmParentID']) > 0) {
				$nFolder = "\n".$nFolder."\n%ID".$bm['bmParentID'];
				$bookmarks = str_replace("%ID".$bm['bmParentID'], $nFolder, $bookmarks);
			}
			else {
				$bookmarks.= $nFolder;
			}
		}
	}
	return $bookmarks;
}

function importMarks($bookmarks,$uid,$database) {
	e_log(8,"Starting import browser bookmarks");
	$db = new PDO('sqlite:'.$database);
	$db->beginTransaction();
	
	foreach ($bookmarks as $bookmark) {
		if(strlen($bookmark['dateGroupModified'])>0) {
			$query = "INSERT INTO `bookmarks` (`bmID`,`bmParentID`,`bmIndex`,`bmTitle`,`bmType`,`bmURL`,`bmAdded`,`bmModified`,`userID`) VALUES ('".$bookmark['bmID']."', '".$bookmark['bmParentID']."', ".$bookmark['bmIndex'].", '".$bookmark['bmTitle']."', '".$bookmark['bmType']."', '".$bookmark['bmURL']."', ".$bookmark['bmAdded'].", ".$bookmark['dateGroupModified'].", ".$uid.")";
		}
		else {
			$query = "INSERT INTO `bookmarks` (`bmID`,`bmParentID`,`bmIndex`,`bmTitle`,`bmType`,`bmURL`,`bmAdded`,`userID`) VALUES ('".$bookmark['bmID']."', '".$bookmark['bmParentID']."', ".$bookmark['bmIndex'].", '".$bookmark['bmTitle']."', '".$bookmark['bmType']."', '".$bookmark['bmURL']."', ".$bookmark['bmAdded'].", ".$uid.")";
		}
		e_log(9,$query);
		$db->query($query);
	}
	
	$response = $db->commit();
	$db = NULL;
	if($response)
		e_log(8,"Browser bookmark import successfully");
	else
		e_log(1,"Error importing browser bookmarks");
	
	return $response;
}

function parseJSON($arr) {
	static $bookmarks;
	if(is_array($arr) && array_key_exists("url", $arr)) {
		$dateGroupModified = (isset($arr['dateGroupModified'])) ? $arr['dateGroupModified'] : '';
		if($arr['url'] != "data:") $bookmarks[] = array("bmID"=>$arr['id'],"bmTitle"=>$arr['title'],"bmIndex"=>$arr['index'],"bmAdded"=>$arr['dateAdded'],"dateGroupModified"=>$dateGroupModified,"bmType"=>"bookmark","bmURL"=>$arr['url'],"bmParentID"=>$arr['parentId']);
	}
	elseif(is_array($arr) && !array_key_exists("url", $arr)) {
		$dateGroupModified = (isset($arr['dateGroupModified'])) ? $arr['dateGroupModified'] : '';
		if(array_key_exists("parentId", $arr)) $bookmarks[] = array("bmID"=>$arr['id'],"bmTitle"=>$arr['title'],"bmIndex"=>$arr['index'],"bmAdded"=>$arr['dateAdded'],"dateGroupModified"=>$dateGroupModified,"bmType"=>"folder","bmURL"=>NULL,"bmParentID"=>$arr['parentId']);
	}
	
	if(is_array($arr)) {
		foreach($arr as $k => $v) {
			parseJSON($v);
		}
	}
	return $bookmarks;
}

function getBookmarks($uid,$database) {
	try {
		$db = new PDO('sqlite:'.$database);
	}
	catch (PDOException $e) {
		e_log(1,'DB connection failed: '.$e->getMessage());
	}
	
	$query = "SELECT * FROM `bookmarks` WHERE `bmAction` IS NULL AND `userID` = ".$uid;
	$statement = $db->prepare($query);
	e_log(8,"Get bookmarks for user ".$_SERVER['PHP_AUTH_USER']);
	e_log(9,$query);
	$statement->execute();
	$userMarks = $statement->fetchAll(PDO::FETCH_ASSOC);
	$db = NULL;
	return $userMarks;
}

function doLogin($database,$realm) {
	$valid = false;
	
	if (isset($_SERVER['PHP_AUTH_USER'])) {
		try {
			$db = new PDO('sqlite:'.$database);
		}
		catch (PDOException $e) {
			e_log(1,'DB connection failed: '.$e->getMessage());
		}
		
		$statement = $db->prepare("SELECT * FROM `users` WHERE `userName`='".$_SERVER['PHP_AUTH_USER']."'");
		e_log(9,"SELECT * FROM `users` WHERE `userName`='".$_SERVER['PHP_AUTH_USER']."'");		
		try {
			 $statement->execute();
		}
		catch(PDOException $e) {
			 echo "DB query failed: " . $e->getMessage();
			 e_log(1,"DB query failed: ".$e->getMessage());
			 $db = NULL;
			 return false;
		}

		$userData = $statement->fetchAll();

		if (!empty($userData)) {
			$valid = password_verify($_SERVER['PHP_AUTH_PW'], $userData[0]['userHash']);
		}
	}
	
	if (!$valid) {
		e_log(8,"No user logged in, sending 401 to client.");
		header('WWW-Authenticate: Basic realm="'.$realm.'", charset="UTF-8"');
		http_response_code(401);
		$_SESSION['fauth']=true;
		$db = NULL;
		die("You must login to use this tool.");
	}

	$db = NULL;
}

function initDB($database) {
	if(!file_exists(dirname($database))) {
		if(!mkdir(dirname($database,0777,true))) {
			e_log(1,"Directory for database couldn't created, please check privileges");
		}
		else {
			e_log(8,"Directory for database created, initialize database now");
		}
	}
	
	try {
		$db = new PDO('sqlite:'.$database);
		$query = "CREATE TABLE `bookmarks` (`bmID`	TEXT NOT NULL, `bmParentID`	TEXT NOT NULL, `bmIndex` INTEGER NOT NULL, `bmTitle` TEXT, `bmType`	TEXT NOT NULL, `bmURL` TEXT, `bmAdded` TEXT NOT NULL, `bmModified` TEXT, `userID` INTEGER NOT NULL, `bmAction` INTEGER, PRIMARY KEY(`bmID`))";
		$db->exec($query);
		e_log(9,$query);
		$query = "CREATE TABLE `users` (`userID` INTEGER NOT NULL, `userName` TEXT UNIQUE NOT NULL, `userType` INTEGER NOT NULL, `userHash`	TEXT NOT NULL, `userLastLogin` TEXT, PRIMARY KEY(`userID`));";
		$db->exec($query);
		e_log(9,$query);
		$query = "CREATE TABLE `clients` (`cid` TEXT NOT NULL UNIQUE,`cname` TEXT, `ctype` TEXT NOT NULL, `uid`	INTEGER NOT NULL, `lastseen` TEXT NOT NULL, PRIMARY KEY(`cid`));";
		$db->exec($query);
		e_log(9,$query);
		
		$bmAdded = time();
		$userPWD = password_hash("mypass",PASSWORD_DEFAULT);
		$db->exec("INSERT INTO `bookmarks` (`bmID`,`bmParentID`,`bmIndex`,`bmTitle`,`bmType`,`bmURL`,`bmAdded`,`userID`) VALUES ('0', '0', 0, 'GitHub Repository', 'bookmark', 'https://github.com/Offerel', ".$bmAdded.", 1)");
		e_log(9,"INSERT INTO `bookmarks` (`bmID`,`bmParentID`,`bmIndex`,`bmTitle`,`bmType`,`bmURL`,`bmAdded`,`userID`) VALUES ('0', '0', 0, 'GitHub Repository', 'bookmark', 'https://github.com/Offerel', ".$bmAdded.", 1)");
		$db->exec("INSERT INTO `users` (userName,userType,userHash) VALUES ('admin',2,'".$userPWD."');");
		e_log(9,"INSERT INTO `users` (userName,userType,userHash) VALUES ('admin',2,'".$userPWD."');");
	}
	catch(PDOException $e) {
		e_log(1,'Exception : '.$e->getMessage());
	}
	$db = NULL;
}
?>