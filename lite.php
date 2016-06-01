<?php
error_reporting(E_ALL);
if(version_compare(PHP_VERSION, '5.3.0', '<')) die('Require PHP 5.3 or higher');
if(!extension_loaded('sqlite3')) die('Install php_sqlite3 extension!');
session_start();
$bg='';
$step=15;
$pg_lr=8;
$dir= getcwd()."/";
$ext=".db3";
$del=" onclick=\"return confirm('are you sure?')\"";
$version="2.0";
$deny= array('sqlite_sequence');
$pi= (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO'));
$sg= preg_split('!/!', $pi,-1,PREG_SPLIT_NO_EMPTY);
$scheme= 'http'.(empty($_SERVER['HTTPS']) === true || $_SERVER['HTTPS'] === 'off' ? '' : 's').'://';
$r_uri= isset($_SERVER['PATH_INFO']) === true ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
$script= $_SERVER['SCRIPT_NAME'];
$path= $scheme.$_SERVER['HTTP_HOST'].(strpos($r_uri, $script) === 0 ? $script : rtrim(dirname($script),'/.\\')).'/';
$bbs= array('False','True');
$v=SQLite3::version();
$head= '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd"><html><head>
<title>EdLiteAdmin</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
<style type="text/css">
* {margin:0;padding:0;font-size: 12px;color:#333;font-family:Arial}
a {color:#842;text-decoration:none}
a:hover {text-decoration:underline}
a,a:active,a:hover {outline: 0}
textarea, .he {min-height:90px}
table {border-collapse: collapse}
.mrg {margin-top:3px}
.box {padding:3px}
.a, .a1 {border:1px solid #555}
.a1 {margin:3px auto}
.c2 {background:#fff}
td, th {padding:4px;vertical-align:top}
.th {border-top:1px solid #555;font-weight:bold}
.scroll {overflow-x:auto}
td.pro,th.pro {border: 1px dotted #842}
.l1,.l2,.l3,.wi {width:100%}
input[type=text],input[type=password],input[type=file],textarea,button,select {width:100%;padding:2px 0;border:1px solid #bbb;outline:none;
-webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;-khtml-border-radius:4px;
-webkit-box-sizing: border-box;-moz-box-sizing: border-box;box-sizing: border-box}
input[type=text],input[type=password],select,textarea {min-width:99px}
select {padding:1px 0}
.div button, .div input {width:auto}
input[type=checkbox],input[type=radio]{position: relative;vertical-align: middle;bottom: 1px}
.l1, th, caption, button {background:#9be}
.l2,.c1 {background:#cdf}
.l3, tr:hover.r, button:hover {background:#fe3 !important}
.msg {position:absolute;top:0;right:0;padding:8px;font-weight:bold;font-size:13px;z-index:1}
.ok {background:#EFE;color:#080;border-bottom:2px solid #080}
.err {background:#FEE;color:#f00;border-bottom:2px solid #f00}
.left *, input[type=password] {width:196px;position: relative;z-index:1}
input[type=text],select {min-width:98px !important}
optgroup option {padding-left:8px}
</style>
<script src="http://emon/jq/jquery.js" type="text/javascript"></script>
<script type="text/javascript">
$(document).ready(function(){
'.((empty($_SESSION['ok']) && empty($_SESSION['err'])) ? '':'$("body").fadeIn("slow").prepend("'.
(!empty($_SESSION['ok']) ? '<div class=\"msg ok\">'.$_SESSION['ok'].'<\/div>':'').
(!empty($_SESSION['err']) ? '<div class=\"msg err\">'.$_SESSION['err'].'<\/div>':'').'");
setTimeout(function(){$(".msg").fadeOut("slow",function(){$(this).remove();});}, 7000);').'
$(".msg").dblclick(function(){$(this).hide()});
});
function selectall(lb, cb) {
var cb=document.getElementById(cb);
if(cb.checked) {
var multi=document.getElementById(lb);
for(var i=0;i<multi.options.length;i++) {
multi.options[i].selected=true;
}
}else{
var multi=document.getElementById(lb);
multi.selectedIndex=-1;
}
}
function toggle(cb, el){
var cbox=document.getElementsByName(el);
for(var i=0;i<cbox.length;i++){
cbox[i].checked = cb.checked;
}}
</script>
</head><body><div class="l1">&nbsp;<b><a href="https://github.com/edmondsql/edliteadmin">EdLiteAdmin '.$version.'</a></b> <i>SQLite '.$v['versionString'].'</i></div>';
function clean($el, $cod='') {
if(get_magic_quotes_gpc()) {
$el= stripslashes($el);
}
if($cod==1) {
return trim(str_replace(array(">","<","\\","\r\n","\r"), array("&gt;","&lt;","\\\\","\n","\n"), $el));//between quota
} else {
return trim(str_replace(array(">","<","\\","'",'"',"\r\n","\r"), array("&gt;","&lt;","\\\\","&#039;","&quot;","\n","\n"), $el));
}
}
function post($idxk='', $op='', $clean='') {
if($idxk === '' && !empty($_POST)) {
return ($_SERVER['REQUEST_METHOD'] === 'POST' ? TRUE : FALSE);
}
if(!isset($_POST[$idxk])) return FALSE;
if(is_array($_POST[$idxk])) {
if(isset($op) && is_numeric($op)) {
return clean($_POST[$idxk][$op],$clean);
} else {
$aout= array();
foreach($_POST[$idxk] as $key=>$val) {
if($val !='') $aout[$key]= clean($val,$clean);
}
}
} else {
$aout= clean($_POST[$idxk],$clean);
}
if($op=='i') return isset($aout);
if($op=='e') return empty($aout);
if($op=='!i') return !isset($aout);
if($op=='!e') return !empty($aout);
return $aout;
}
function form($furl, $enc='') {
global $path;
return "<form action='".$path.$furl."' method='post'".($enc==1 ? " enctype='multipart/form-data'":"").">";
}
function menu($db, $tb='', $left='', $sp=array()) {
global $path;
$f=1;$nrf_op='';
while($f<100) {
$nrf_op.= "<option value='$f'>$f</option>";
++$f;
}
$str = "<div class='l2'><a href='{$path}'>List DBs</a> | <a href='{$path}31/$db'>Export</a> | <a href='{$path}5/$db'>List Tables</a>".
($tb==""?"</div>":" || <a href='{$path}10/$db/$tb'>Structure</a> | <a href='{$path}21/$db/$tb'>Browse</a> | <a href='{$path}26/$db/$tb'>Empty</a> | <a href='{$path}27/$db/$tb'>Drop</a> | <a href='{$path}28/$db/$tb'>Vacuum</a></div>").
"<div class='l3'>DB: <b>$db</b>".($tb==""?"":" || Table: <b>$tb</b>").(count($sp) >1 ?" || ".$sp[0].": <b>".$sp[1]."</b>":"")."</div><div class='scroll'>";
if($left==1) $str .= "<table><tr><td class='c1 left'><table><tr><td class='th'>Query</td></tr>
<tr><td>".form("30/$db")."<textarea name='qtxt'></textarea><br/><button type='submit'>DO</button></form></td></tr>
<tr><td class='th'>Import SQL, CSV</td></tr>
<tr><td>".form("30/$db",1)."<input type='file' name='importfile' />
<input type='hidden' name='send' value='ja' /><br/><button type='submit'>DO</button></form></td></tr>
<tr><td class='th'>Create Table</td></tr><tr><td>".form("7/$db")."Table Name<br/><input type='text' name='ctab' /><br/>Number of fields<br/><select name='nrf'>".$nrf_op."</select><br/><button type='submit'>CREATE</button></form></td></tr>
<tr><td class='th'>Rename DB</td></tr><tr><td>".form("3/$db")."<input type='text' name='rdb' /><br/><button type='submit'>RENAME</button></form></td></tr>
<tr><td class='th'>Create</td></tr><tr><td><a href='{$path}40/$db'>View</a> | <a href='{$path}41/$db'>Trigger</a></td></tr>
</table></td><td>";
return $str;
}
$stru= "<table class='a1'><tr><th class='pro'>FIELD</th><th class='pro'>TYPE</th><th class='pro'>VALUE</th><th class='pro'>NULL</th><th class='pro'>DEFAULT</th></tr>";
function fieldtype($slct='') {
$fieldtype= array('Numbers'=>array("INTEGER","INT","BIGINT","DECIMAL"),'Strings'=>array("VARCHAR","TEXT"),'DateTime'=>array("DATE","DATETIME","TIME","TIMESTAMP"),'Binary'=>array("BOOLEAN","BLOB"));
$ft='';
foreach($fieldtype as $fdk=>$fdtype) {
if(is_array($fdtype)) {
$ft .= "<optgroup label='$fdk'>";
foreach($fdtype as $fdty) $ft .= "<option value='$fdty'".(($slct!='' && $fdty==$slct)?" selected":"").">$fdty</option>";
$ft .= "</optgroup>";
}
}
return $ft;
}
function redir($way='', $msg=array()) {
global $path;
if(count($msg) > 0) {
foreach($msg as $ks=>$ms) $_SESSION[$ks]= $ms;
}
header('Location: '.$path.$way);exit;
}
function sanitize($el) {
return preg_replace(array('/[^A-Za-z0-9]/'),'_',trim($el));
}
function check($level=array(), $param=array()) {
global $sg, $con, $ext, $dir;
if(in_array(1,$level)) {//exist db
$db = $sg[1];
if(!is_file($dir.$db.$ext)) redir('',array('err'=>"DB not exist"));
}
if(!empty($param['db'])) {//connect db
$con = new SQLite3($dir.$param['db'].$ext);
}
if(in_array(2,$level)) {//check table
$tb= $sg[2];
$ist= $con->querySingle("SELECT type FROM sqlite_master WHERE name='$tb'");
if(!$ist) redir("5/".$db,array('err'=>"Table not exist"));
}
if(in_array(3,$level)) {//check field
	$q_fld = $con->querySingle("SELECT ".$sg[3]." FROM ".$sg[2]);
	if($q_fld===FALSE) redir($param['redir']."/$db/".$tb,array('err'=>"Field not exist"));
}
if(in_array(4,$level)) {//check pagination
	if(!is_numeric($param['pg']) || $param['pg'] > $param['total'] || $param['pg'] < 1) redir($param['redir'],array('err'=>"Invalid page number"));
}
if(in_array(5,$level)) {//check view, trigger
	$q_sp = $con->querySingle("SELECT type FROM sqlite_master WHERE name='".$sg[2]."'");
	if($q_sp != $sg[3]) redir("5/".$db,array('err'=>"Not available view or trigger"));
}
}
function pg_number($pg, $totalpg) {
global $path, $sg, $pg_lr;
if($totalpg > 1) {
$kl= ($pg > $pg_lr ? $pg-$pg_lr:1);//left pg
$kr= (($pg > $totalpg-$pg_lr) ? $totalpg:$pg+$pg_lr);//right pg
if($sg[0]==21) $link= $path."21/".$sg[1]."/".$sg[2];
elseif($sg[0]==5) $link= $path."5/".$sg[1];
$pgs='';
while($kl <= $kr) {
	$pgs .= (($kl == $pg) ? " <b>".$kl."</b> | " : " <a href='$link/$kl'>$kl</a> | ");
	++$kl;
}
$lft= ($pg>1?"<a href='$link/1'>First</a> | <a href='$link/".($pg-1)."'>Prev</a> |":"");
$rgt= ($pg < $totalpg?"<a href='$link/".($pg+1)."'>Next</a> | <a href='$link/$totalpg'>Last</a>":"");
return $lft.$pgs.$rgt;
}
}

if(!isset($sg[0])) $sg[0]=0;
switch($sg[0]) {
default:
case ""://show DBs
	echo $head."<table><tr><td class='c1'>Create Database".
	form(2)."<input type='text' class='a1' name='dbc' /><br/>
	<button type='submit'>Create</button></form></td><td>
	<table class='a'><tr><th>DATABASE</th><th>Tables</th><th>Actions</th></tr>";
	$dbs = array();
	$dh = @opendir($dir);
	while(($dbe = readdir($dh)) != false) {
		if(is_file($dir.$dbe) && strrchr($dbe,'.') == $ext) {
			$dbs[] = $dbe;
		}
	}
	closedir($dh);
	sort($dbs);
	foreach($dbs as $db_) {
		$bg=($bg==1)?2:1;
		list($db) = explode('.db3', $db_);
		$dbx = new SQLite3($dir.$db_);
		$qs_nr = $dbx->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' or type='view'");
		echo "<tr class='r c$bg'><td>".$db."
		</td><td>".$qs_nr."</td><td><a href='{$path}31/$db'>Exp</a> | <a".$del." href='{$path}4/$db'>Drop</a> | <a href='{$path}5/$db'>Browse</a>
		</td></tr>";
	}
	echo "</table></td></tr></table>";
	break;

case "2"://create db
	if(post('dbc','!e')) {
	$db = sanitize(post('dbc'));
	if(is_file($dir.$db.$ext)) redir("",array('err'=>"DB already exist"));
	$con = new SQLite3($dir.$db.$ext);
	if(is_file($dir.$db.$ext)) redir("",array('ok'=>"Created DB"));
	else redir("",array('err'=>"Create DB failed"));
	}
	redir("",array('err'=>"DB name must not be empty"));
break;

case "3"://rename db
	check(array(1));
	$db = $sg[1];
	if(post('rdb','!e') && sanitize(post('rdb')) != $db) {
		$ndb = sanitize(post('rdb'));
		rename($dir.$db.$ext, $dir.$ndb.$ext);
		redir("",array('ok'=>"Successfully renamed"));
	} else redir("5/".$db,array('err'=>"DB name must not be empty"));
break;

case "4"://delete db
	check(array(1));
	$db = $sg[1];
	if(is_file($dir.$db.$ext)) {
	@unlink($dir.$db.$ext);
	redir("",array('ok'=>"Successfully deleted"));
	} else redir("",array('err'=>"Non-existent DB"));
break;

case "5"://show tables
	check(array(1),array('db'=>$sg[1]));
	$db = $sg[1];
	
	$all= $con->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' OR type='view'");
	$totalpg= ceil($all/$step);
	if(empty($sg[2])) {
		$pg= 1;
	} else {
		$pg= $sg[2];
		check(array(4),array('pg'=>$pg,'total'=>$totalpg,'redir'=>"5/$db"));
	}
	$offset= ($pg - 1) * $step;
	
	echo $head.menu($db,'',1);
	echo "<table class='a'><tr><th>TABLE/VIEW</th><th>RECORDS</th><th>ACTIONS</th></tr>";
	$q_tabs = $con->query("SELECT name,type FROM sqlite_master WHERE type='table' or type='view' ORDER BY type,name LIMIT $offset, $step");
	while($r_tabs = $q_tabs->fetchArray(SQLITE3_NUM)) {
		if(!in_array($r_tabs[0],$deny)) {
		$q_num = $con->querySingle("SELECT COUNT(*) FROM ".$r_tabs[0]);
		$bg=($bg==1)?2:1;
		$vl = "/$db/".$r_tabs[0];
		if($r_tabs[1] == "view") {
		$lnk = "40{$vl}/view";
		} else {
		$lnk = "10{$vl}";
		}
		echo "<tr class='r c$bg'><td>".$r_tabs[0]."</td><td>".($r_tabs[1] == "view" ? $r_tabs[1] : $q_num)."</td><td><a href='{$path}{$lnk}'>Structure</a> | <a href='{$path}27/$db/".$r_tabs[0]."'>Drop</a> | <a href='{$path}21/$db/".$r_tabs[0]."'>Browse</a></td></tr>";
		}
	}
	echo "</table>";
	$q_tri= $con->query("SELECT name, tbl_name FROM sqlite_master WHERE type='trigger' ORDER BY name");
	$t=0;
	$trg_tab= "<table class='a mrg'><tr><th>TRIGGER</th><th>TABLE</th><th>ACTIONS</th></tr>";
	while($r_tri = $q_tri->fetchArray(SQLITE3_NUM)) {
	$bg=($bg==1)?2:1;
	$trg_tab .= "<tr class='r c$bg'><td>".$r_tri[0]."</td><td>".$r_tri[1]."</td><td><a href='{$path}41/$db/".$r_tri[0]."/trigger'>Edit</a> | <a href='{$path}49/$db/".$r_tri[0]."/trigger'>Drop</a></td></tr>";
	++$t;
	}
	echo ($t>0 ? $trg_tab."</table>":"");
	echo pg_number($pg, $totalpg)."</td></tr></table></div>";
	$con = null;
break;

case "7"://create table
	check(array(1),array('db'=>$sg[1]));
	$db= $sg[1];
	echo $head.menu($db);
	if(post('ctab','!e') && post('nrf','!e') && post('nrf')>0 && is_numeric(post('nrf')) ) {
		if(post('crtb','i')) {
		$q1= "CREATE TABLE ".sanitize(post('ctab'))."(";
		for ($n=0;$n<post('nrf');$n++) {
			$v1=post('fi'.$n); $v2=post('ty'.$n); $v3=post('vl'.$n); $v4=post('nl'.$n); $v5=post('df'.$n);
			$q1.=$v1." ".$v2.($v3!='' ? "(".$v3.")":"").($v4!=0 ? " NOT NULL":"").($v5!="" ? " ".$v5:"").",";
		}
		$q2= substr($q1,0,-1).");";
		echo "<p class='box'>".($con->query($q2) ? "<b>OK!</b> $q2<br/>" : "<b>FAILED!</b> $q2")."</p>";
		} else {
		echo form("7/$db")."<input type='hidden' name='ctab' value='".post('ctab')."'/>
		<input type='hidden' name='nrf' value='".post('nrf')."'/>".$stru;
		$nr= post('nrf');
		for($i=0;$i<$nr;$i++){
		echo "<tr><td><input type=text name='fi".$i."' /></td><td><select name='ty".$i."'>".fieldtype()."</select></td><td><input type=text name='vl".$i."' /></td><td><select name='nl".$i."'><option value=0>Yes</option><option value=1>No</option></select></td><td><input type=text name='df".$i."' /></td></tr>";
		}
		echo "<tr><td class='c1' colspan=5><button type='submit' name='crtb'>Create table</button></td></tr></table></form>";
		}
	} else {
		echo "<p class='box'>Create table FAILED!</p>";
	}
	echo "</div>";
break;

case "9":
	check(array(1,2),array('db'=>$sg[1]));
	$db= $sg[1];
	$tb= $sg[2];
	if(post('copytab','!e')) {//copy table
		$cpy= post('copytab');
		$ncpy= basename($cpy,$ext);
		$q_tc = $con->querySingle("SELECT sql FROM sqlite_master WHERE name='$tb'");
		$r_sql = preg_split("/\([^()]*\)(*SKIP)(*F)|[()]/", $q_tc, -1, PREG_SPLIT_NO_EMPTY);
		$con->exec("BEGIN TRANSACTION");
		$con->exec("ATTACH DATABASE '".$dir.$cpy."' AS ".$ncpy);
		$con->exec("CREATE TABLE IF NOT EXISTS {$ncpy}.{$tb} (".$r_sql[1].");");
		$con->exec("INSERT INTO {$ncpy}.{$tb} SELECT * FROM ".$tb);
		$con->exec("DETACH DATABASE ".$ncpy);
		$con->exec("COMMIT");
	}
	if(post('rtab','!e')) {//rename table
		$new= sanitize(post('rtab'));
		$con->exec("ALTER TABLE $tb RENAME TO ".$new);
		$con->exec("BEGIN TRANSACTION");
		$con->exec("PRAGMA writable_schema=1");
		$q_rvtab= $con->query("SELECT name,sql FROM sqlite_master WHERE type='view'");//rename tb name in views
		if($q_rvtab) {
		while($r_rvtab = $q_rvtab->fetchArray(SQLITE3_NUM)) {
		$repl= str_replace($tb,$new,$r_rvtab[1]);
		$con->exec("UPDATE sqlite_master SET sql='".$repl."' WHERE name='".$r_rvtab[0]."'");
		}
		}
		$q_rvtig= $con->query("SELECT name,sql FROM sqlite_master WHERE type='trigger'");//rename tb name in triggers
		if($q_rvtig) {
		while($r_rvtig = $q_rvtig->fetchArray(SQLITE3_NUM)) {
		$repl= str_replace(" ".$tb," ".$new,$r_rvtig[1]);
		$con->exec("UPDATE sqlite_master SET sql='".$repl."' WHERE name='".$r_rvtig[0]."'");
		}
		}
		$con->exec("PRAGMA writable_schema=0");
		$con->exec("COMMIT");
	}
	if(post('idx','!e') && is_array(post('idx'))) {//create index
		$idx = implode(',',post('idx'));
		$idxn = implode('_',post('idx'));
		$con->exec("BEGIN TRANSACTION");
		if(post('primary','i')) {
			$q_pr = $con->querySingle("SELECT sql FROM sqlite_master WHERE name='$tb'");
			preg_match('/(?<=\()(.+)(?=\))/', $q_pr, $r_prsql);
			$spos= strpos($r_prsql[1],"PRIMARY KEY");
			if($spos===false) {
			$r_sql= $r_prsql[1];
			} else {
			$r_sql= preg_split("/,\s*PRIMARY KEY\s*\(.*\)|\s+PRIMARY\s+KEY\s*AUTOINCREMENT|\s+PRIMARY\s+KEY/i", $r_prsql[1], -1, PREG_SPLIT_NO_EMPTY);
			$r_sql= implode("",$r_sql);
			}
			$con->exec("BEGIN TRANSACTION");
			$con->exec("CREATE INDEX pk_{$tb} ON $tb($idx)");
			$con->exec("pragma writable_schema=1");
			$con->exec("UPDATE sqlite_master SET name='sqlite_autoindex_{$tb}_1',sql=null WHERE name='pk_{$tb}'");
			$con->exec("UPDATE sqlite_master SET sql='CREATE TABLE $tb(".$r_sql.", PRIMARY KEY($idx))' WHERE name='$tb'");
			$con->exec("COMMIT");
			$con->exec("PRAGMA writable_schema=0");
		} elseif(post('unique','i')) {
			$con->exec("CREATE UNIQUE INDEX UNI__{$idxn} ON $tb($idx)");
		} elseif(post('index','i')) {
			$con->exec("CREATE INDEX IDX__{$idxn} ON $tb($idx)");
		}
		$con->exec("VACUUM");
		$con->exec("COMMIT");
		redir("10/{$db}/".$tb,array('ok'=>"Successfully created"));
	}
	if(!empty($sg[3])) {//drop index
		$idx= base64_decode($sg[3]);
		$con->exec("DROP INDEX ".$idx);
		redir("10/$db/".$tb,array('ok'=>"Successfully dropped"));
	}
	$con = null;
	redir("5/".$db,array('ok'=>"Successfully"));
break;

case "10"://table structure
	check(array(1,2),array('db'=>$sg[1]));
	$db = $sg[1];
	$tb = $sg[2];
	echo $head.menu($db,$tb,1);
	echo form("9/$db/$tb")."<table class='a'><tr><th colspan=7>TABLE STRUCTURE</th></tr><tr><th><input type='checkbox' onclick='toggle(this,\"idx[]\")' /></th><th class='pro'>FIELD</th><th class='pro'>TYPE</th><th class='pro'>NULL</th><th class='pro'>DEFAULT</th><th class='pro'>PK</th><th class='pro'>ACTIONS</th></tr>";
	$r = $con->query("PRAGMA table_info($tb)");
	while($rec = $r->fetchArray(SQLITE3_NUM)) {
		$bg=($bg==1)?2:1;
		echo "<tr class='r c$bg'><td><input type='checkbox' name='idx[]' value='".$rec[1]."' /></td><td class='pro'>".$rec[1]."</td><td class='pro'>".$rec[2]."</td><td class='pro'>".($rec[3]==0 ? 'Yes':'No')."</td><td class='pro'>".$rec[4]."</td><td class='pro'>".($rec[5]==1 ? 'PK':'')."</td><td class='pro'><a href='{$path}11/$db/$tb/".$rec[1]."'>change</a> | <a href='{$path}13/$db/$tb/".$rec[1]."'>drop</a> | <a href='{$path}12/$db/$tb/'>add</a></td></tr>";
	}
	echo "<tr><td class='div' colspan=7><button type='submit' name='primary'>Primary</button> <button type='submit' name='index'>Index</button> <button type='submit' name='unique'>Unique</button></td></tr></table></form>";
	$q_idx = $con->query("PRAGMA index_list($tb)");
	$es= $q_idx->numColumns();
	if($es) {
	echo "<table class='a c1 mrg'><tr><th colspan=4>INDEXES</th></tr><tr><th class='pro'>NAME</th><th class='pro'>COLUMN</th><th class='pro'>Unique</th><th class='pro'>Action</th></tr>";
	while($rc = $q_idx->fetchArray(SQLITE3_NUM)) {
		$bg=($bg==1)?2:1;
		echo "<tr class='r c$bg'><td class='pro'>".$rc[1]."</td><td class='pro'>";
		$q= $con->query("PRAGMA index_info('".$rc[1]."')");
		while($rd = $q->fetchArray(SQLITE3_NUM)) {
			echo $rd[2]." ";
		}
		echo "</td><td class='pro'>".($rc[2]==1 ? 'YES':'NO')."</td><td class='pro'><a href='{$path}9/$db/$tb/".base64_encode($rc[1])."'>Drop</a></td></tr>";
	}
	echo "</table>";
	}
	$con = null;
	echo "<table class='a c1 mrg'><tr><td>Rename Table<br/>".form("9/$db/$tb")."<input type='text' name='rtab' /><br/><button type='submit'>Rename</button></form><br/>Copy Table<br/>".form("9/$db/$tb")."<select name='copytab'>";
	$dh = @opendir($dir);
	while(($dbe = readdir($dh)) != false) {
		if(is_file($dir.$dbe) && strrchr($dbe,'.') == $ext) {
			echo "<option value='{$dbe}'>".basename($dbe, $ext)."</option>";
		}
	}
	closedir($dh);
	echo "</select><br/><button type='submit'>Copy</button></form></td></tr></table>
	</td></tr></table></div>";
break;

case "11"://change field structure
	check(array(1,2,3),array('db'=>$sg[1],'redir'=>10));
	$db= $sg[1];
	$tb= $sg[2];
	$fn= $sg[3];
	$f= $con->query("PRAGMA table_info($tb)");
	if(post('change','i')) {
		$qr='';$pk='';
		while($e= $f->fetchArray(SQLITE3_NUM)) {
		if($e[1]==$fn){
			$na1 = sanitize(post("cf1"));
			$qr.= $na1." ".post('cf2').(post('cf3','!e')?"(".post('cf3').")":"").(post('cf5')!=0 ? " NOT NULL":"").(post("cf5")!=''?" ".post("cf5"):"").",";
			$pk.= ($e[5]==1 ? $na1.",":"");
		} else {
			$qr.= $e[1]." ".$e[2].($e[3]!=0 ? " NOT NULL":"").($e[4]!='' ? " ".$e[4]:"").",";
			$pk.= ($e[5]==1 ? $e[1].",":"");
		}
		}
		$qr.=($pk!='' ? " PRIMARY KEY(".substr($pk,0,-1)."),":"");
		$qrs="CREATE TABLE $tb(".substr($qr,0,-1).")";
		$con->exec("BEGIN TRANSACTION");
		$con->exec("PRAGMA writable_schema=1");
		//rename field in table
		$con->exec("UPDATE sqlite_master SET sql='$qrs' WHERE name='$tb'");
		//rename field in views
		$q_rvtab= $con->query("SELECT name,sql FROM sqlite_master WHERE type='view'");
		if($q_rvtab) {
			while($r_rvtab = $q_rvtab->fetchArray(SQLITE3_NUM)) {
			if(strpos($r_rvtab[1]," ".$tb)!= false) {
			$replv= str_replace($fn,$na1,$r_rvtab[1]);
			$con->query("UPDATE sqlite_master SET sql='".$replv."' WHERE name='".$r_rvtab[0]."'");
			}
			}
		}
		//rename field in triggers
		$q_rvtig= $con->query("SELECT name,sql FROM sqlite_master WHERE type='trigger' AND tbl_name='$tb'");
		while($r_rvtig = $q_rvtig->fetchArray(SQLITE3_NUM)) {
			$replt= str_replace($fn,$na1,$r_rvtig[1]);
			$con->query("UPDATE sqlite_master SET sql='".$replt."' WHERE name='".$r_rvtig[0]."'");
		}
		$con->exec("PRAGMA writable_schema=0");
		$con->exec("COMMIT");
		$con = null;
		redir("10/$db/$tb",array('ok'=>"Successfully changed"));
	} else {
		echo $head.menu($db,$tb);
		echo form("11/$db/$tb/$fn").$stru;
		while($d= $f->fetchArray(SQLITE3_NUM)) {
			if($d[1]==$fn){
				$d_val= preg_split("/[()]+/", $d[2], -1, PREG_SPLIT_NO_EMPTY);
				echo "<tr><td><input type='text' name='cf1' value='".$d[1]."' /></td><td><select name='cf2'>".fieldtype(strtoupper($d_val[0])).
				"</select></td><td><input type='text' name='cf3' value='".(isset($d_val[1])?$d_val[1]:"")."' /></td>
				<td><select name='cf4'><option value='0'>Yes</option><option value='1'".($d[3]!=0 ? " selected":"").">No</option></select></td>
				<td><input type='text' name='cf5' value='".$d[4]."' /></td></tr>";
			}
		}
		echo "<tr><td class='c1' colspan=5><button type='submit' name='change'>Change field</button></td></tr></table></form></div>";
	}
break;

case "12"://add new field
	check(array(1,2),array('db'=>$sg[1],'redir'=>10));
	$db= $sg[1];
	$tb= $sg[2];
	if(post('add','i')) {
		$con->query("ALTER TABLE ".$tb." ADD COLUMN ".sanitize(post('f1'))." ".post('f2').(post('f3','!e')?"(".post('f3').")":"").(post('f4')!=0 ? " NOT NULL":"").(post('f5')!='' ? " ".post('f5'):""));
		$con = null;
		redir("10/$db/$tb",array('ok'=>"Successfully added"));
	} else {
		echo $head.menu($db,$tb);
		echo form("12/$db/$tb").$stru;
		echo "<tr><td><input type='text' name='f1' /></td>
		<td><select name='f2'>".fieldtype()."</select></td>
		<td><input type='text' name='f3' /></td>
		<td><select name='f4'><option value=0>Yes</option><option value=1>No</option></select></td>
		<td><input type='text' name='f5' /></td></tr>
		<tr><td class='c1' colspan=5><button type='submit' name='add'>Add field</button></td></tr></table></form></div>";
	}
break;

case "13"://drop column
	check(array(1,2,3),array('db'=>$sg[1],'redir'=>10));
	$db= $sg[1];
	$tb= $sg[2];
	$fn= $sg[3];
	$q_nof= $con->query("SELECT * FROM ".$tb);
	$nof= $q_nof->numColumns();
	if($nof>1){
		$f= $con->query("PRAGMA table_info($tb)");
		$qr='';
		while($e= $f->fetchArray(SQLITE3_NUM)) {
		if($e[1]!=$fn){
			$qr.= $e[1]." ".$e[2].($e[3]!=0 ? " NOT NULL":"").($e[4]!='' ? " ".$e[4]:"").($e[5]==1 ? " PRIMARY KEY":"").",";
		}
		}
		$qrs="CREATE TABLE $tb(".substr($qr,0,-1).")";
		$con->exec("BEGIN TRANSACTION");
		$con->exec("PRAGMA writable_schema=1");
		$q_rtb= $con->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$tb'", true);//drop field
		if($q_rtb > 0) {
			$con->query("UPDATE sqlite_master SET sql='".$qrs."' WHERE name='$tb'");
		}
		//drop view if have field
		$q_tbw= $con->query("SELECT * FROM sqlite_master WHERE type='view'");
		while($r_tbw= $q_tbw->fetchArray(SQLITE3_ASSOC)) {
		if(strpos($r_tbw['sql'],$tb)!==false && strpos($r_tbw['sql'],$fn)!==false) $con->exec("DROP VIEW ".$r_tbw['name']);
		}
		$con->exec("PRAGMA writable_schema=0");
		$con->exec("COMMIT");
		$con = null;
		redir("10/$db/$tb",array('ok'=>"Successfully deleted"));
	} else {
		$con->query("DROP TABLE ".$tb);
		redir("10/$db/$tb",array('ok'=>"Successfully dropped"));
	}
break;

case "21"://table browse
	check(array(1,2),array('db'=>$sg[1]));
	$db= $sg[1];
	$tb= $sg[2];
	$all = $con->querySingle("SELECT COUNT(*) FROM ".$tb);
	$totalpg = ceil($all/$step);
	if(empty($sg[3])) {
		$pg = 1;
	} else {
		$pg = $sg[3];
		check(array(4),array('pg'=>$pg,'total'=>$totalpg,'redir'=>"21/$db/$tb"));
	}
	$offset = ($pg - 1) * $step;
	$res = $con->query("SELECT * FROM $tb LIMIT $offset, $step");
	$cols = $res->numColumns();

	echo $head;
	$q_vws = $con->querySingle("SELECT type FROM sqlite_master WHERE name='$tb'");
	echo menu($db,($q_vws != 'view' ? $tb:""),1);
	echo "<table class='a'><tr>";
	if($q_vws != 'view') echo "<th colspan=2><a href='{$path}22/$db/$tb'>INSERT</a></th>";
	$i=0;
	while($i < $cols) {
		echo "<th>".$res->columnName($i)."</th>";
		++$i;
	}
	echo "</tr>";

	$rinf = array();
	$q_ti = $con->query("PRAGMA table_info($tb)");
	while($r_ti = $q_ti->fetchArray(SQLITE3_NUM)) {
		$rinf[$r_ti[0]]= $r_ti[2];
	}

	while($row= $res->fetchArray(SQLITE3_NUM)) {
		$bg=($bg==1)?2:1;
		$id=base64_encode($row[0]);
		echo "<tr class='r c$bg'>";
		if($q_vws != 'view') echo "<td><a href='{$path}23/$db/$tb/".$res->columnName(0)."/".$id."'>Edit</a></td><td><a href='{$path}24/$db/$tb/".$res->columnName(0)."/".$id."'>Delete</a></td>";
		for($j=0;$j<$cols;$j++) {
			echo "<td class='pro'>".(stristr($rinf[$j],"blob") == true ? "[binary] ".number_format((strlen($row[$j])/1024),2)." KB":html_entity_decode($row[$j],ENT_QUOTES))."</td>";
		}
		echo "</tr>";
	}
	$con = null;
	echo "</table>".pg_number($pg, $totalpg)."</td></tr></table></div>";
break;

case "22"://insert row
	check(array(1,2),array('db'=>$sg[1]));
	$db= $sg[1];
	$tb= $sg[2];
	$q_pra= $con->query("PRAGMA table_info($tb)");
	if(post('insert','i')) {
		$qr2="INSERT INTO $tb (";
		$qr4="VALUES(";
		$i=0;
		while($r_re = $q_pra->fetchArray(SQLITE3_ASSOC)) {
			if(post('r'.$i,'e') && $r_re['notnull'] != 0) redir("21/$db/$tb",array('err'=>"Field structure is NotNull"));
			if(strtolower($r_re['type'])=="boolean") {
			$qr2.=$r_re['name'].",";
			$qr4.= "'".(post('r'.$i,0) ? 1:'')."',";
			} elseif(strtolower($r_re['type'])=="blob") {
			if(!empty($_FILES['r'.$i]['tmp_name'])) {
			$qr2.=$r_re['name'].",";
			$qr4 .= "'".base64_encode(file_get_contents($_FILES['r'.$i]['tmp_name']))."',";
			}
			} else {
			$qr2.=$r_re['name'].",";
			$qr4.="'".post('r'.$i,'',1)."',";
			}
			++$i;
		}
		$qr2=substr($qr2,0,-1).") ";
		$qr4=substr($qr4,0,-1).")";
		$con->exec($qr2.$qr4);
		redir("21/$db/$tb",array('ok'=>"Successfully inserted"));
	} else {
		echo $head.menu($db,$tb,1);
		echo form("22/$db/$tb", 1)."<table class='a'><caption>Insert Row</caption>";
		while($r_pra = $q_pra->fetchArray(SQLITE3_ASSOC)) {
			echo "<tr><td>".$r_pra['name']."</td><td>";
			if(strtolower($r_pra['type'])=="boolean") {
			foreach($bbs as $kj=>$bb) {
			echo "<input type='radio' name='r".$r_pra['cid']."[]' value='$kj' /> $bb ";
			}
			} elseif(strtolower($r_pra['type'])=="blob") {
			echo "<input type='file' name='r".$r_pra['cid']."' />";
			} elseif(strtolower($r_pra['type'])=="text") {
			echo "<textarea name='r".$r_pra['cid']."'></textarea>";
			} else {
			echo "<input type='text' name='r".$r_pra['cid']."' />";
			}
			echo "</td></tr>";
		}
		echo "<tr><td class='c1' colspan=2><button type='submit' name='insert'>Save</button></td></tr>";
		echo "</table></form></td></tr></table></div>";
	}
break;

case "23"://edit row
	check(array(1,2,3),array('db'=>$sg[1],'redir'=>21));
	$db= $sg[1];
	$tb= $sg[2];
	$nu= $sg[3];
	$id= base64_decode($sg[4]);
	$q_rd= $con->query("PRAGMA table_info($tb)");
	if(post('edit','i')) {
		$qr="";
		$p=0;
		while($r_rd = $q_rd->fetchArray(SQLITE3_ASSOC)) {
			if(post('d'.$p,'e') && $r_rd['notnull'] != 0) redir("21/$db/$tb",array('err'=>"Field structure is NotNull"));
			$stype= strtolower($r_rd['type']);
			if($stype=="blob") {
				if(!empty($_FILES['d'.$p]['tmp_name'])) {
				$qr .= $r_rd['name']."='".base64_encode(file_get_contents($_FILES['d'.$p]['tmp_name']))."',";
				}
			} elseif($stype=="boolean") {
				$qr .= $r_rd['name']."='".(post('d'.$p,0) ? 1:'')."',";
			} else {
				$qr.= $r_rd['name']."='".post('d'.$p,'',1)."',";
			}
			++$p;
		}
		$qq=substr($qr,0,-1);
		$con->exec("UPDATE $tb SET ".$qq." WHERE ".$nu."='".$id."'");
		redir("21/$db/$tb",array('ok'=>"Successfully updated"));
	} else {
		echo $head.menu($db,$tb,1);
		echo form("23/$db/$tb/$nu/".$sg[4], 1)."<table class='a'><caption>Edit Row</caption>";
		$qry= $con->query("SELECT * FROM ".$tb." WHERE ".$nu."='".$id."'");
		$arr= $qry->fetchArray(SQLITE3_NUM);
		while($r_ed = $q_rd->fetchArray(SQLITE3_ASSOC)) {
			$nr=$r_ed['cid'];
			$typ= strtolower($r_ed['type']);
			echo "<tr><td>".$r_ed['name']."</td><td>";
			if($typ=="boolean") {
			foreach($bbs as $kk=>$bb) {
			echo "<input type='radio' name='d".$nr."[]' value='$kk'".($arr[$nr]==$kk ? " checked":"")." /> $bb ";
			}
			} elseif($typ=="blob") {
			echo "[binary] ".number_format((strlen($arr[$nr])/1024),2)." KB<br/><input type='file' name='d".$nr."' />";
			} elseif($typ=="text") {
			echo "<textarea name='d".$nr."'>".html_entity_decode($arr[$nr],ENT_QUOTES)."</textarea>";
			} else {
			echo "<input type='text' name='d".$nr."' value='".stripslashes($arr[$nr])."' />";
			}
			echo "</td></tr>";
		}
		echo "<tr><td class='c1' colspan=2><button type='submit' name='edit'>Update</button></td></tr></table></form>";
		echo "</td></tr></table></div>";
	}
break;

case "24"://delete row
	check(array(1,2,3),array('db'=>$sg[1],'redir'=>21));
	$db= $sg[1];
	$tb= $sg[2];
	$exec_dr= $con->exec("DELETE FROM ".$tb." WHERE ".$sg[3]."='".base64_decode($sg[4])."'");
	if($exec_dr) redir("21/$db/$tb",array('ok'=>"Deleted row"));
	else redir("21/$db/$tb",array('err'=>"Delete row failed"));
break;

case "26"://table empty
	check(array(1,2),array('db'=>$sg[1]));
	$db= $sg[1];
	$tb= $sg[2];
	$exec_te= $con->exec("DELETE FROM ".$tb);
	$con = null;
	if($exec_te) redir("5/$db",array('ok'=>"Table is empty"));
	else redir("5/$db",array('err'=>"Can't empty the table"));
break;

case "27"://drop table, view
	check(array(1,2),array('db'=>$sg[1]));
	$db= $sg[1];
	$tb= $sg[2];
	$con->exec("BEGIN TRANSACTION");
	$rs= $con->querySingle("SELECT type FROM sqlite_master WHERE name='$tb'");
	$q_dv= $con->query("SELECT name,sql FROM sqlite_master WHERE type='view'");//drop view assoc with table
	if($q_dv) {
	while($r_dv= $q_dv->fetchArray(SQLITE3_NUM)) {
	if(strpos($r_dv[1],$tb)) {
	$con->exec("DROP VIEW ".$r_dv[0]);
	}
	}
	}
	$con->exec("DROP ".$rs." ".$tb);
	$con->exec("COMMIT");
	$con->exec("VACUUM");
	$con = null;
	redir("5/$db",array('ok'=>"Successfully dropped"));
break;

case "28"://vacuum
	check(array(1,2),array('db'=>$sg[1]));
	$con->exec("VACUUM");
	$con = null;
	redir("10/".$sg[1]."/".$sg[2],array('ok'=>"Successfully vacuumed"));
break;

case "30"://import
	check(array(1),array('db'=>$sg[1]));
	$db= $sg[1];
	$out='';
	$rgex = "~^\xEF\xBB\xBF|(\#|--).*|(?m)\(([^)]*\)*(\".*\")*('.*'))(*SKIP)(*F)|(?ims)(BEGIN.*?END)(*SKIP)(*F)|(?<=;)(?![ ]*$)~";
	if(post('qtxt','!e')) {//in textarea
		$e= preg_split($rgex, post('qtxt','',1), -1, PREG_SPLIT_NO_EMPTY);
	} elseif(post('send','i') && post('send') == "ja") {//from file
		if(empty($_FILES['importfile']['tmp_name'])) {
		$e='';
		redir("5/$db",array('err'=>"No file to upload"));
		} else {
			$tmp = $_FILES['importfile']['tmp_name'];
			$finame = explode('.',$_FILES['importfile']['name']);
			$fext = strtolower(end($finame));
			if($fext == 'sql') {//sql file
				$fi = clean(file_get_contents($tmp),1);
				$e= preg_split($rgex, $fi, -1, PREG_SPLIT_NO_EMPTY);
			} elseif($fext == 'csv') {//csv file
				$handle = fopen("$tmp","r");
				$data = fgetcsv($handle, 10000, ",");
				if(empty($data)) redir('5/'.$db);
				$fd = '';
				for($h=0;$h<count($data);$h++) {
					$fd .= clean($data[$h]).',';
				}
				$fdx = "(".substr($fd,0,-1).")";
				$e = array();
				while(($data = fgetcsv($handle, 1000000, ",")) !== FALSE) {
					$num = count($data);
					if($num < 1) redir('5/'.$db);
					$import="INSERT INTO ".$finame[0].$fdx." VALUES(";
					for ($c=0; $c < $num; ++$c) {
						$import.="'".clean($data[$c])."',";
					}
					$e[] = substr($import,0,-1).");";
				}
				if(empty($e)) redir("5/$db",array('err'=>"Query failed"));
				fclose($handle);
			} else {
				redir("5/$db",array('err'=>"Disallowed extension"));
			}
		}
	} else {
		redir("5/$db",array('err'=>"Query failed"));
	}
	if(is_array($e)) {
		set_error_handler(function() {});
		$con->exec("BEGIN TRANSACTION");
		foreach($e as $qry) {
			if(trim($qry)!='') $out .= "<p class='box'>".($con->exec(trim($qry)) ? "<b>OK!</b> - $qry" : "<b>FAILED!</b> $qry")."</p>";
		}
		$con->exec("COMMIT");
	}
	$con = null;
	echo $head.menu($db).$out."</div>";
break;

case "31"://export form
	check(array(1),array('db'=>$sg[1]));
	$db= $sg[1];
	$q_extbs= $con->query("SELECT name FROM sqlite_master WHERE type='table' OR type='view'");
	$deny= array('sqlite_sequence');
	$ex=0;$r_tts=array();
	while($r_extbs= $q_extbs->fetchArray(SQLITE3_NUM)) {
	if(!in_array($r_extbs[0],$deny)) {
	$r_tts[]= $r_extbs[0];
	++$ex;
	}
	}
	if($ex > 0) {
	echo $head.menu($db);
	echo "<table><tr><td>".form("32/$db")."<table class='a'><tr><th>Export</th></tr><tr><td>
	<table class='a1 wi'><tr><th>Select table(s)</th></tr><tr><td>
	<p><input type='checkbox' onclick='selectall(\"tables\",\"sel\")' id='sel' /> Select/Deselect</p>
	<select class='he' id='tables' name='tables[]' multiple='multiple'>";
	foreach($r_tts as $tts) {
	echo "<option value='".$tts."'>".$tts."</option>";
	}
	echo "</select></td></tr></table>
	<table class='a1 wi'><tr><th style='text-align:left'><input type='checkbox' onclick='toggle(this,\"fopt[]\")' /></th><th>Options</th></tr><tr><td colspan=2>";
	$opts = array('structure'=>'Structure','data'=>'Data','drop'=>'Drop','trigger'=>'Trigger');
	foreach($opts as $k => $opt) {
	echo "<p><input type='checkbox' name='fopt[]' value='{$k}'".($k=='structure' ? ' onclick="opt()" checked':'')." /> ".$opt."</p>";
	}
	echo "</td></tr></table>
	<table class='a1 wi'><tr><th>File format</th></tr><tr><td>";
	$ffo = array('sql'=>'SQL','csv'=>'CSV');
	foreach($ffo as $k => $ff) {
	echo "<p><input type='radio' name='ffmt[]' value='{$k}'".($k=='sql' ? ' checked':'')." /> {$ff}</p>";
	}
	echo "</td></tr></table>
	<table class='a1 wi'><tr><th>File type</th></tr><tr><td>";
	$fty = array('plain'=>'Plain','gzip'=>'GZ','zip'=>'Zip');
	foreach($fty as $k => $ft) {
	echo "<p><input type='radio' name='ftype[]' value='{$k}'".($k=='plain' ? ' checked':'')." /> {$ft}</p>";
	}
	echo "</td></tr></table>
	</td></tr><tr><td class='c1'><button type='submit' name='exp'>Export</button></td></tr></table></form></td></tr></table></div>";
	} else {
	redir("5/".$db,array("err"=>"No export empty DB"));
	}
break;

case "32"://export
	check(array(1),array('db'=>$sg[1]));
	$db= $sg[1];
	$tbs= array();
	$vws= array();
	if(post('tables','e')) {//push selected tables
		redir("31/".$db,array('err'=>"You didn't select any table"));
	} else {
		$tabs= post('tables');
		foreach($tabs as $tab) {
			$q_strc= $con->querySingle("SELECT name,type FROM sqlite_master WHERE name='$tab'", true);
			if($q_strc['name']==$tab && $q_strc['type']=='view') {
			array_push($vws, $tab);
			} elseif($q_strc['name']==$tab && $q_strc['type']=='table') {
			array_push($tbs, $tab);
			}
		}
	}
	if(post('fopt','e')) {//check export options
		redir("31/".$db,array('err'=>"You didn't select any option"));
	} else {
		$fopt=post('fopt');
	}
	$sql='';
	$ffmt= post('ffmt');
	if(in_array('sql',$ffmt)) {//data for sql format
		$sql.="-- EdLiteAdmin SQL Dump\n-- version ".$version."\n\n";
		foreach($tbs as $ttd) {
		$val="";
		if(in_array('structure',$fopt)) {//begin structure
			if(in_array('drop',$fopt)) {//check option drop
				$sql .= "DROP TABLE IF EXISTS $ttd;\n";
			}
			$sql .= $con->querySingle("SELECT sql FROM sqlite_master WHERE name='$ttd'").";";//structure
			$q_tidx= $con->query("SELECT sql FROM sqlite_master WHERE tbl_name='$ttd' AND type='index'");
			$cidx= $q_tidx->numColumns();
			if($cidx > 0) {
				while($r_ix= $q_tidx->fetchArray(SQLITE3_NUM)) {
				$val.= "\n".$r_ix[0].";";
				}
				$val.="\n";
			}
		}
		if(in_array('data',$fopt)) {//check option data
			$res2 = $con->query("SELECT * FROM ".$ttd);
			while($row= $res2->fetchArray(SQLITE3_NUM)) {
				$ro= "\nINSERT INTO $ttd VALUES(";
				$nr= $res2->numColumns();
				for($i = 0; $i < $nr; $i++) {
					if(is_numeric($row[$i])) $ro.= $row[$i].",";
					else $ro.= "'".$row[$i]."',";
				}
				$val.= substr($ro,0,-1).");";
			}
			$val.= "\n";
		}
		if(in_array('trigger',$fopt)) {//check option data
			$q_ttgr= $con->querySingle("SELECT name,sql FROM sqlite_master WHERE tbl_name='$ttd' AND type='trigger'", true);
			if(isset($q_ttgr['name'])) {
			if(in_array('drop',$fopt)) {//check option drop
			$val .= "\nDROP TRIGGER IF EXISTS ".$q_ttgr['name'].";";
			}
			$val .= "\n".$q_ttgr['sql'].";\n";
			}
		}
		$sql.= $val."\n";
		}
		if($vws != '' && in_array('structure',$fopt)) {//export views
		foreach($vws as $vw) {
			$q_rw= $con->querySingle("SELECT sql FROM sqlite_master WHERE type='view'");
			if($q_rw) {
			if(in_array('drop',$fopt)) {//check option drop
			$sql .= "DROP VIEW IF EXISTS $vw;\n";
			}
			$sql .= $q_rw.";\n\r";
			}
		}
		}
	} elseif(in_array('csv',$ffmt)) {//csv format
		foreach($tbs as $tb) {
		$q_csv= $con->query("SELECT * FROM ".$tb);//columnName
		$ncol= $q_csv->numColumns();
		for($i=0;$i < $ncol;++$i) $sql.='"'.$q_csv->columnName($i).'",';
		$sql=substr($sql,0,-1)."\n";
		while($r_rs=$q_csv->fetchArray(SQLITE3_NUM)) {
			for($t=0;$t<$ncol;$t++) $sql.="\"".str_replace('"','""',$r_rs[$t])."\",";
			$sql=substr($sql,0,-1)."\n";
		}
		$sql.="\n";
		}
	}
	if(in_array("csv", $ffmt)) {//type, ext
		$ffty= "text/csv";
		$ffext= ".csv";
	} elseif(in_array("sql", $ffmt)) {
		$ffty= "text/plain";
		$ffext= ".sql";
	}
	$ftype= post('ftype');
	if(in_array("gzip", $ftype)) {//pack
		$zty= "application/x-gzip";
		$zext= ".gz";
	} elseif(in_array("zip", $ftype)) {
		$zty= "application/x-zip";
		$zext= ".zip";
	}
	$fname= $db.(count($tbs) == 1 ? ".".$tbs[0] : "").$ffext;
	if(in_array("gzip", $ftype)) {//gzip
		ini_set('zlib.output_compression','Off');
		$sql = gzencode($sql, 9);
		header('Content-Encoding: gzip');
		header("Content-Length: ".strlen($sql));
	} elseif(in_array("zip", $ftype)) {//zip
		$info = array();
		$ctrl_dir = array();
		$eof = "\x50\x4b\x05\x06\x00\x00\x00\x00";
		$old_offset = 0;
		$ti = getdate();
		if($ti['year'] < 1980) {
		$ti['year'] = 1980;$ti['mon'] = 1;$ti['mday'] = 1;$ti['hours'] = 0;$ti['minutes'] = 0;$ti['seconds'] = 0;
		}
		$time = (($ti['year'] - 1980) << 25) | ($ti['mon'] << 21) | ($ti['mday'] << 16) | ($ti['hours'] << 11) | ($ti['minutes'] << 5) | ($ti['seconds'] >> 1);
		$dtime = substr("00000000" . dechex($time), -8);
		$hexdtime = '\x'.$dtime[6].$dtime[7].'\x'.$dtime[4].$dtime[5].'\x'.$dtime[2].$dtime[3].'\x'.$dtime[0].$dtime[1];
		eval('$hexdtime = "'.$hexdtime.'";');
		$fr = "\x50\x4b\x03\x04\x14\x00\x00\x00\x08\x00".$hexdtime;
		$unc_len = strlen($sql);
		$crc = crc32($sql);
		$zdata = gzcompress($sql);
		$zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2);
		$c_len = strlen($zdata);
		$fr .= pack('V', $crc).pack('V', $c_len).pack('V', $unc_len).pack('v', strlen($fname)).pack('v', 0).$fname.$zdata;
		$info[] = $fr;
		$cdrec = "\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00\x08\x00".$hexdtime.
		pack('V', $crc).pack('V', $c_len).pack('V', $unc_len).pack('v', strlen($fname)).
		pack('v', 0).pack('v', 0).pack('v', 0).pack('v', 0).pack('V', 32).pack('V', $old_offset);
		$old_offset += strlen($fr);
		$cdrec .= $fname;
		$ctrl_dir[] = $cdrec;
		$ctrldir = implode('', $ctrl_dir);
		$end = $ctrldir.$eof.pack('v', sizeof($ctrl_dir)).pack('v', sizeof($ctrl_dir)).pack('V', strlen($ctrldir)).pack('V', $old_offset)."\x00\x00";
		$datax = implode('', $info);
		$sql = $datax.$end;
	}
	header("Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0");
	header("Content-Type: ".(in_array("plain", $ftype) ? $ffty."; charset=utf-8" : $zty));
	header("Content-Disposition: attachment; filename=".$fname.(in_array("plain", $ftype) ? "":$zext));
	die($sql);
break;

case "40"://view
	if(!isset($sg[2]) && !isset($sg[3])) {//create
		check(array(1),array('db'=>$sg[1]));
		$db= $sg[1];
		$r_uv= array(1=>'',2=>'');
		if(post('uv1','!e') && post('uv2','!e')) {
			$vstat= post('uv2','',1);
			$stat= $con->exec($vstat);
			if(!$stat) redir("5/".$db,array('err'=>"Wrong statement"));
			$con->exec("CREATE VIEW ".sanitize(post('uv1'))." AS ".$vstat);
			redir("5/".$db,array('ok'=>"Successfully updated"));
		}
		echo $head.menu($db);
		echo form("40/$db");
		$b_lbl="Create";
	} else {//edit
		check(array(1,5),array('db'=>$sg[1]));
		$db= $sg[1];$sp= $sg[2];$ty= $sg[3];
		if(post('uv1','!e') && post('uv2','!e')) {
			$vstat= post('uv2','',1);
			$stat= $con->exec($vstat);
			if(!$stat) redir("5/".$db,array('err'=>"Wrong statement"));
			$con->exec("DROP $ty ".$sp);
			$con->exec("CREATE VIEW ".sanitize(post('uv1'))." AS ".$vstat);
			redir("5/".$db,array('ok'=>"Successfully created"));
		}
		echo $head.menu($db,'','',array($ty,$sp));
		$q_uv = $con->querySingle("SELECT sql FROM sqlite_master WHERE type='$ty' AND name='$sp'");
		preg_match('/CREATE\sVIEW\s(.*)\s+AS\s+(.*)$/i', $q_uv, $r_uv);
		echo form("40/$db/$sp/$ty");
		$b_lbl="Edit";
	}
	echo "<table class='a1'><tr><th colspan=2>$b_lbl View</th></tr>
	<tr><td>Name</td><td><input type='text' name='uv1' value='".$r_uv[1]."'/></td></tr>
	<tr><td>Statement</td><td><textarea name='uv2'>".$r_uv[2]."</textarea></td></tr>
	<tr><td class='c1' colspan=2><button type='submit'>Save</button></td></tr></table></form></div>";
break;

case "41"://trigger
	if(!isset($sg[2]) && !isset($sg[3])) {//create
		check(array(1),array('db'=>$sg[1]));
		$db= $sg[1];
		$r_tge= array(1=>'');
		if(post('utg1','!e') && post('utg5','!e')) {
		$q_tgcrt= $con->exec("CREATE TRIGGER ".sanitize(post('utg1'))." ".post('utg2')." ".post('utg3')." ON ".post('utg4')." BEGIN ".post('utg5','',1)."; END");
		if($q_tgcrt) redir("5/".$db,array('ok'=>"Successfully created"));
		else redir("5/".$db,array('err'=>"Create trigger failed"));
		}
		echo $head.menu($db);
		echo form("41/$db");
		$t_lbl="Create";
	} else {//edit
		check(array(1,5),array('db'=>$sg[1]));
		$db= $sg[1];$sp= $sg[2];$ty= $sg[3];
		if(post('utg1','!e') && post('utg5','!e')) {
			$con->exec("DROP {$ty} ".$sp);
			$q_tgcrt= $con->exec("CREATE TRIGGER ".sanitize(post('utg1'))." ".post('utg2')." ".post('utg3')." ON ".post('utg4')." BEGIN ".post('utg5','',1)."; END");
			if($q_tgcrt) redir("5/".$db,array('ok'=>"Successfully updated"));
			else redir("5/".$db,array('err'=>"Update trigger failed"));
		}
		$q_tge = $con->querySingle("SELECT sql FROM sqlite_master WHERE type='$ty' AND name='$sp'");
		preg_match('/CREATE\sTRIGGER\s(.*)\s+(.*)\s+(.*)\s+ON\s+(.*)\s+BEGIN\s+(.*);\s+END$/i', $q_tge, $r_tge);
		echo $head.menu($db,'','',array($ty,$sp));
		echo form("41/$db/$sp/$ty");
		$b_lbl="Edit";
	}
	$tgtb= array();
	$q_ttbs = $con->query("SELECT name FROM sqlite_master WHERE type='table'");
	while($r_ttbs = $q_ttbs->fetchArray(SQLITE3_NUM)) {
		if(!in_array($r_ttbs[0],$deny)) $tgtb[] = $r_ttbs[0];
	}
	echo "<table class='a1'><tr><th colspan=2>Edit Trigger</th></tr>
	<tr><td>Trigger Name</td><td><input type='text' name='utg1' value='".$r_tge[1]."'/></td></tr>
	<tr><td>Table</td><td><select name='utg4'>";
	foreach($tgtb as $tgt) echo "<option value='".$tgt."'".($r_tge[4]==$tgt? " selected":"").">".$tgt."</option>";
	echo "</select></td></tr><tr><td>Time</td><td><select name='utg2'>";
	$tm= array('BEFORE','AFTER');
	foreach($tm as $tn) echo "<option value='$tn'".($r_tge[2]==$tn?" selected":"").">$tn</option>";
	echo "</select></td></tr>
	<tr><td>Event</td><td><select name='utg3'>";
	$evm= array('INSERT','UPDATE','DELETE');
	foreach($evm as $evn) echo "<option value='$evn'".($r_tge[3]==$evn?" selected":"").">$evn</option>";
	echo "</select></td></tr>
	<tr><td>Definition</td><td><textarea name='utg5'>".$r_tge[5]."</textarea></td></tr>
	<tr><td class='c1' colspan=2><button type='submit'>Update</button></td></tr></table></form></div>";
break;

case "49"://drop trigger
	check(array(1,5),array('db'=>$sg[1]));
	$con->exec("DROP ".$sg[3]." ".$sg[2]);
	redir('5/'.$sg[1],array('ok'=>"Successfully dropped"));
break;
}
unset($_POST);
session_destroy();
echo '<div class="l1" style="text-align:center"><a href="http://edmondsql.github.io">edmondsql</a></div></body></html>';