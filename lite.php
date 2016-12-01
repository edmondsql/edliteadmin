<?php
error_reporting(E_ALL);
if(version_compare(PHP_VERSION, '5.3.0', '<')) die('Require PHP 5.3 or higher');
if(!extension_loaded('sqlite3') && !extension_loaded('pdo_sqlite')) die('Install sqlite3 or pdo_sqlite extension!');
session_start();
$bg='';
$step=15;
$version="3.1";
$del=" onclick=\"return confirm('are you sure?')\"";
$bbs= array('False','True');
$deny= array('sqlite_sequence');

class DBT {
	public static $contype= array('sqlite3','pdo_sqlite');
	private static $instance = NULL;
	private $_cnx, $_query, $_fetch = array(), $_num_col;
	public static function factory($db) {
		if(!isset(self::$instance))
		self::$instance = new DBT($db);
		return self::$instance;
	}
	public function __construct($db) {
		if($_SESSION['contype'] == self::$contype[0]) {
			$this->_cnx = new SQLite3($db);
		} else {
			$this->_cnx = new PDO("sqlite:".$db);
		}
	}
	private function __clone() {}
	public function exec($sql) {
		return $this->_cnx->exec($sql);
	}
	public function query($sql, $single=false) {
		try{
			if($_SESSION['contype'] == self::$contype[0]) {
				if($single == false) {
				$this->_query = $this->_cnx->query($sql);
				} else {
				$this->_query = $this->_cnx->querySingle($sql);
				}
			} else {
				$this->_query = $this->_cnx->query($sql);
			}
			return $this;
		} catch(Exception $e) {
			return false;
		}
	}
	public function last() {
		if($_SESSION['contype'] == self::$contype[0]) {
		return $this->_cnx->changes();
		} else {
		return $this->_query->rowCount();
		}
	}
	public function err() {
		if($_SESSION['contype'] == self::$contype[0]) {
		return $this->_cnx->lastErrorCode();
		} else {
		return $this->_cnx->errorInfo();
		}
	}
	public function fetch($mode=0) {
		if($_SESSION['contype'] == self::$contype[0]) {
		if($mode > 0) {
			switch($mode){
			case 1:
			$ty = SQLITE3_NUM;
			break;
			case 2:
			$ty = SQLITE3_ASSOC;
			break;
			}
			$res = array();
			while($row = $this->_query->fetchArray($ty)) {
			$res[] = $row;
			}
			return $res;
		} else {
			return $this->_query;
		}
	} else {
		if($mode > 0) {
			switch($mode){
			case 1:
			$ty = PDO::FETCH_NUM;
			break;
			case 2:
			$ty = PDO::FETCH_ASSOC;
			break;
			}
			$res = array();
			while($row = $this->_query->fetch($ty)) {
			$res[] = $row;
			}
			return $res;
		} else {
			return $this->_query->fetchColumn();
		}
	}
	}
	public function num_col() {
		if($_SESSION['contype'] == self::$contype[0]) {
		$this->_num_col = $this->_query->numColumns();
		} else {
		$this->_num_col = $this->_query->columnCount();
		}
		return $this->_num_col;
	}
}

class ED {
	public $con, $dir, $ext=".db3", $sg, $path, $pg_lr=8;
	protected $passwd='';
	public function __construct() {
		$this->dir= getcwd()."/";
		$pi= (isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO'));
		$this->sg= preg_split('!/!', $pi,-1,PREG_SPLIT_NO_EMPTY);
		
		$scheme= 'http'.(empty($_SERVER['HTTPS']) === true || $_SERVER['HTTPS'] === 'off' ? '' : 's').'://';
		$r_uri= isset($_SERVER['PATH_INFO']) === true ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
		$script= $_SERVER['SCRIPT_NAME'];
		$this->path= $scheme.$_SERVER['HTTP_HOST'].(strpos($r_uri, $script) === 0 ? $script : rtrim(dirname($script),'/.\\')).'/';
	}
	public function clean($el, $cod='') {
		if($cod==1) {
		return trim(str_replace(array(">","<","\r\n","\r"), array("&gt;","&lt;","\n","\n"), $el));//between quota
		} else {
		return trim(str_replace(array(">","<","\\","'",'"',"\r\n","\r"), array("&gt;","&lt;","\\\\","&#039;","&quot;","\n","\n"), $el));
		}
	}
	public function post($idxk='', $op='', $clean='') {
		if($idxk === '' && !empty($_POST)) {
		return ($_SERVER['REQUEST_METHOD'] === 'POST' ? TRUE : FALSE);
		}
		if(!isset($_POST[$idxk])) return FALSE;
		if(is_array($_POST[$idxk])) {
		if(isset($op) && is_numeric($op)) {
		return $this->clean($_POST[$idxk][$op],$clean);
		} else {
		$aout= array();
		foreach($_POST[$idxk] as $key=>$val) {
		if($val !='') $aout[$key]= $this->clean($val,$clean);
		}
		}
		} else {
		$aout= $this->clean($_POST[$idxk],$clean);
		}
		if($op=='i') return isset($aout);
		if($op=='e') return empty($aout);
		if($op=='!i') return !isset($aout);
		if($op=='!e') return !empty($aout);
		return $aout;
	}
	public function form($furl, $enc='') {
		return "<form action='".$this->path.$furl."' method='post'".($enc==1 ? " enctype='multipart/form-data'":"").">";
	}
	public function menu($db, $tb='', $left='', $sp=array()) {
		$f=1;$nrf_op='';
		while($f<50) {
		$nrf_op.= "<option value='$f'>$f</option>";
		++$f;
		}
		$str = "<div class='l2'><a href='{$this->path}'>List DBs</a> | <a href='{$this->path}31/$db'>Export</a> | <a href='{$this->path}5/$db'>List Tables</a>".
		($tb==""?"</div>":" || <a href='{$this->path}10/$db/$tb'>Structure</a> | <a href='{$this->path}21/$db/$tb'>Browse</a> | <a href='{$this->path}26/$db/$tb'>Empty</a> | <a href='{$this->path}27/$db/$tb'>Drop</a> | <a href='{$this->path}28/$db/$tb'>Vacuum</a></div>").
		"<div class='l3'>DB: <b>$db</b>".($tb==""?"":" || Table: <b>$tb</b>").(count($sp) >1 ?" || ".$sp[0].": <b>".$sp[1]."</b>":"")."</div><div class='scroll'>";
		if($left==1) $str .= "<table><tr><td class='c1 left'><table><tr><td class='th'>Query</td></tr>
		<tr><td>".$this->form("30/$db")."<textarea name='qtxt'></textarea><br/><button type='submit'>DO</button></form></td></tr>
		<tr><td class='th'>Import sql, csv, gz, zip, ".substr($this->ext,1)."</td></tr>
		<tr><td>".$this->form("30/$db",1)."<input type='file' name='importfile' />
		<input type='hidden' name='send' value='ja' /><br/><button type='submit'>DO</button></form></td></tr>
		<tr><td class='th'>Create Table</td></tr><tr><td>".$this->form("7/$db")."Table Name<br/><input type='text' name='ctab' /><br/>Number of fields<br/><select name='nrf'>".$nrf_op."</select><br/><button type='submit'>CREATE</button></form></td></tr>
		<tr><td class='th'>Rename DB</td></tr><tr><td>".$this->form("3/$db")."<input type='text' name='rdb' /><br/><button type='submit'>RENAME</button></form></td></tr>
		<tr><td class='th'>Create</td></tr><tr><td><a href='{$this->path}40/$db'>View</a> | <a href='{$this->path}41/$db'>Trigger</a></td></tr>
		</table></td><td>";
		return $str;
	}
	public function fieldtype($slct='') {
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
	public function redir($way='', $msg=array()) {
		if(count($msg) > 0) {
		foreach($msg as $ks=>$ms) $_SESSION[$ks]= $ms;
		}
		header('Location: '.$this->path.$way);exit;
	}
	public function sanitize($el) {
		return preg_replace(array('/[^A-Za-z0-9]/'),'_',trim($el));
	}
	public function check($level=array(), $param=array()) {
		if(!empty($_SESSION['token']) && !empty($_SESSION['contype'])) {
		if(!in_array($_SESSION['contype'], DBT::$contype)) $this->redir("50");
		if($_SESSION['token'] != base64_encode(md5($_SERVER['HTTP_USER_AGENT'].$this->passwd))) $this->redir("50",array('err'=>"Wrong password"));
		} else {
		$this->redir("50");
		}
		if(in_array(1,$level)) {//exist db
		$db = $this->sg[1];
		if(!is_file($this->dir.$db.$this->ext)) $this->redir('',array('err'=>"DB not exist"));
		}
		if(!empty($param['db']) && is_writable($this->dir)) {//connect db
		$this->con = DBT::factory($this->dir.$param['db'].$this->ext);
		}
		if(in_array(2,$level)) {//check table
		$tb= $this->sg[2];
		$ist= $this->con->query("SELECT 1 FROM sqlite_master WHERE name='$tb' AND (type='table' OR type='view')", true)->fetch();
		if(!$ist) $this->redir("5/".$db,array('err'=>"Table not exist"));
		}
		if(in_array(3,$level)) {//check field
			$q_fld = $this->con->query("SELECT ".$this->sg[3]." FROM ".$this->sg[2], true)->fetch();
			if($q_fld===FALSE) $this->redir($param['redir']."/$db/".$tb,array('err'=>"Field not exist"));
			if(isset($this->sg[5])) {
			$q_fld2 = $this->con->query("SELECT ".$this->sg[5]." FROM ".$this->sg[2], true)->fetch();
			if($q_fld2===FALSE) $this->redir($param['redir']."/$db/".$tb,array('err'=>"Field not exist"));
			}
		}
		if(in_array(4,$level)) {//check pagination
			if(!is_numeric($param['pg']) || $param['pg'] > $param['total'] || $param['pg'] < 1) $this->redir($param['redir'],array('err'=>"Invalid page number"));
		}
		if(in_array(5,$level)) {//check view, trigger
			$q_sp = $this->con->query("SELECT 1 FROM sqlite_master WHERE name='".$this->sg[2]."' AND type='".$this->sg[3]."'", true)->fetch();
			if(!$q_sp) $this->redir("5/".$db,array('err'=>"Not available object"));
		}
	}
	public function pg_number($pg, $totalpg) {
		if($totalpg > 1) {
		$kl= ($pg > $this->pg_lr ? $pg-$this->pg_lr:1);//left pg
		$kr= (($pg > $totalpg-$this->pg_lr) ? $totalpg:$pg+$this->pg_lr);//right pg
		if($this->sg[0]==21) $link= $this->path."21/".$this->sg[1]."/".$this->sg[2];
		elseif($this->sg[0]==5) $link= $this->path."5/".$this->sg[1];
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
	public function imp_sqlite($fname, $fbody) {
		if($fbody!='') {
		if(substr(file_get_contents($fbody), 0, 15) !="SQLite format 3" && substr($fbody, 0, 15) !="SQLite format 3") $this->redir('',array('err'=>"No SQLite file"));
		$file= pathinfo($fname);
		$new= $this->dir.$this->sanitize($file['filename']).$this->ext;
		if(is_uploaded_file($fbody)) {
			if(move_uploaded_file($fbody, $new)) {
			$this->redir('',array('ok'=>"SQLite file uploaded"));
			}
		} else {
			$sfile = fopen($new, "wb");
			if(!$sfile) $this->redir('',array('err'=>"Unable to create sqlite file"));
			fwrite($sfile, $fbody);
			fclose($sfile);
			$this->redir('',array('ok'=>"SQLite file uploaded"));
		}
		}
		$this->redir('',array('err'=>"No upload"));
	}
	public function imp_csv($fname, $fbody) {
		$exist= $this->con->query("SELECT 1 FROM sqlite_master WHERE name='".$fname."' AND type='table'", true)->fetch();
		if(!$exist) $this->redir("5/".$this->sg[1],array('err'=>"Table not exist"));
		$e = array();
		if(is_file($fbody)) {
			$handle = fopen("$fbody","rb");
			$data = fgetcsv($handle, 0, ",");
			if(empty($data)) $this->redir('5/'.$this->sg[1]);
			$fd = '';
			for($h=0;$h<count($data);$h++) {
				$fd .= $this->clean($data[$h]).',';
			}
			$fdx = "(".substr($fd,0,-1).")";
			while(($data = fgetcsv($handle, 0, ",")) !== FALSE) {
				$num = count($data);
				if($num < 1) $this->redir('5/'.$this->sg[1]);
				$import="INSERT INTO ".$fname.$fdx." VALUES(";
				for ($c=0; $c < $num; ++$c) {
					$import.="'".$this->clean($data[$c])."',";
				}
				$e[] = substr($import,0,-1).");";
			}
			fclose($handle);
		} else {
			$data = array();
			foreach(preg_split("/((\r?\n)|(\r\n?))/", $fbody) as $line){
			$data[] = $line;
			}
			$i=1;
			$co= count($data);
			if($co < 1) $this->redir("5/".$this->sg[1],array('err'=>"No data"));
			while($i < $co) {
			if(!empty($data[$i])) $e[] = "INSERT INTO ".$fname."(".str_replace('"','',$data[0]).") VALUES(".$data[$i].");";
			++$i;
			}
		}
		if(empty($e)) $this->redir("5/".$this->sg[1],array('err'=>"Query failed"));
		return $e;
	}
	public function ver() {
		if($_SESSION['contype'] == DBT::$contype[0]) {
			$v=SQLite3::version();
			$_SESSION['ver'] = $v['versionString'];
		} else {
			$dbv = new PDO('sqlite::memory:');
			$_SESSION['ver'] = $dbv->getAttribute(PDO::ATTR_SERVER_VERSION);
			unset($dbv);	 
		}
	}
}
$ed= new ED;
$head= '<!DOCTYPE html><html><head>
<title>EdLiteAdmin</title><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<!--[if IE]><meta http-equiv="X-UA-Compatible" content="IE=edge" /><![endif]-->
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
.lgn, .msg{position:absolute;top:0;right:0}
.msg {padding:8px;font-weight:bold;font-size:13px;z-index:1}
.ok {background:#EFE;color:#080;border-bottom:2px solid #080}
.err {background:#FEE;color:#f00;border-bottom:2px solid #f00}
.left *, input[type=password] {width:196px;position: relative;z-index:1}
input[type=text],select {min-width:98px !important}
optgroup option {padding-left:8px}
</style>
<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js" type="text/javascript"></script>
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
</head><body><div class="l1">&nbsp;<b><a href="https://github.com/edmondsql/edliteadmin">EdLiteAdmin '.$version.'</a> '.(!empty($_SESSION['contype']) ? '<i>SQLite '.$_SESSION['ver'].'</i>':'').'</b>'.(isset($ed->sg[0]) && $ed->sg[0]==50 ? "": '<div class="lgn"><a href="'.$ed->path.'51">Logout</a>&nbsp;</div>').'</div>';
$stru= "<table class='a1'><tr><th class='pro'>FIELD</th><th class='pro'>TYPE</th><th class='pro'>VALUE</th><th class='pro'>NULL</th><th class='pro'>DEFAULT</th></tr>";

if(!isset($ed->sg[0])) $ed->sg[0]=0;
switch($ed->sg[0]) {
default:
case ""://show DBs
	$ed->check();
	echo $head."<table><tr><td class='c1 left'>Create Database".
	$ed->form(2)."<input type='text' name='dbc' /><br/>
	<button type='submit'>Create</button></form></td><td>
	<table class='a'><tr><th>DATABASE</th><th>Tables</th><th>Actions</th></tr>";
	$dbs = array();
	$dh = @opendir($ed->dir);
	while(($dbe = readdir($dh)) != false) {
		if(is_file($ed->dir.$dbe) && strrchr($dbe,'.') == $ed->ext) {
			$dbs[] = $dbe;
		}
	}
	closedir($dh);
	sort($dbs);
	foreach($dbs as $db_) {
		$bg=($bg==1)?2:1;
		list($db) = explode('.db3', $db_);
		$dbx = new DBT($ed->dir.$db_);
		$qs_nr = $dbx->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' or type='view'", true)->fetch();
		echo "<tr class='r c$bg'><td>".$db."
		</td><td>".$qs_nr."</td><td><a href='{$ed->path}31/$db'>Exp</a> | <a".$del." href='{$ed->path}4/$db'>Drop</a> | <a href='{$ed->path}5/$db'>Browse</a>
		</td></tr>";
		$dbx = NULL;
	}
	echo "</table></td></tr></table>";
	break;

case "2"://create db
	$ed->check();
	if($ed->post('dbc','!e')) {
	$db = $ed->sanitize($ed->post('dbc'));
	if(is_file($ed->dir.$db.$ed->ext)) $ed->redir("",array('err'=>"DB already exist"));
	$ed->con = DBT::factory($ed->dir.$db.$ed->ext);
	if(is_file($ed->dir.$db.$ed->ext)) $ed->redir("",array('ok'=>"Created DB"));
	else $ed->redir("",array('err'=>"Create DB failed"));
	}
	$ed->redir("",array('err'=>"DB name must not be empty"));
break;

case "3"://rename db
	$ed->check(array(1));
	$db = $ed->sg[1];
	if($ed->post('rdb','!e') && $ed->sanitize($ed->post('rdb')) != $db) {
		$ndb = $ed->sanitize($ed->post('rdb'));
		rename($ed->dir.$db.$ed->ext, $ed->dir.$ndb.$ed->ext);
		$ed->redir("",array('ok'=>"Successfully renamed"));
	} else $ed->redir("5/".$db,array('err'=>"DB name must not be empty"));
break;

case "4"://delete db
	$ed->check(array(1));
	$db = $ed->sg[1];
	if(is_file($ed->dir.$db.$ed->ext)) {
	$fl= $ed->dir.$db.$ed->ext;
	chmod($fl, 0664);
	@unlink($fl);
	$ed->redir("",array('ok'=>"Successfully deleted"));
	} else $ed->redir("",array('err'=>"Non-existent DB"));
break;

case "5"://show tables
	$ed->check(array(1),array('db'=>$ed->sg[1]));
	$db = $ed->sg[1];
	
	$all= $ed->con->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' OR type='view'", true)->fetch();
	$totalpg= ceil($all/$step);
	if(empty($ed->sg[2])) {
		$pg= 1;
	} else {
		$pg= $ed->sg[2];
		$ed->check(array(4),array('pg'=>$pg,'total'=>$totalpg,'redir'=>"5/$db"));
	}
	$offset= ($pg - 1) * $step;
	
	echo $head.$ed->menu($db,'',1);
	echo "<table class='a'><tr><th>TABLE/VIEW</th><th>RECORDS</th><th>ACTIONS</th></tr>";
	$q_tabs = $ed->con->query("SELECT name,type FROM sqlite_master WHERE type='table' OR type='view' ORDER BY type,name LIMIT $offset, $step")->fetch(1);
	foreach($q_tabs as $r_tabs) {
		if(!in_array($r_tabs[0],$deny)) {
		$q_num = $ed->con->query("SELECT COUNT(*) FROM ".$r_tabs[0], true)->fetch();
		$bg=($bg==1)?2:1;
		$vl = "/$db/".$r_tabs[0];
		if($r_tabs[1] == "view") {
		$lnk = "40{$vl}/view";
		} else {
		$lnk = "10{$vl}";
		}
		echo "<tr class='r c$bg'><td>".$r_tabs[0]."</td><td>".($r_tabs[1] == "view" ? $r_tabs[1] : $q_num)."</td><td><a href='{$ed->path}{$lnk}'>Structure</a> | <a href='{$ed->path}27/$db/".$r_tabs[0]."'>Drop</a> | <a href='{$ed->path}21/$db/".$r_tabs[0]."'>Browse</a></td></tr>";
		}
	}
	echo "</table>";
	$q_tri= $ed->con->query("SELECT name, tbl_name FROM sqlite_master WHERE type='trigger' ORDER BY name")->fetch(1);
	$t=0;
	$trg_tab= "<table class='a mrg'><tr><th>TRIGGER</th><th>TABLE</th><th>ACTIONS</th></tr>";
	foreach($q_tri as $r_tri) {
	$bg=($bg==1)?2:1;
	$trg_tab .= "<tr class='r c$bg'><td>".$r_tri[0]."</td><td>".$r_tri[1]."</td><td><a href='{$ed->path}41/$db/".$r_tri[0]."/trigger'>Edit</a> | <a href='{$ed->path}49/$db/".$r_tri[0]."/trigger'>Drop</a></td></tr>";
	++$t;
	}
	echo ($t>0 ? $trg_tab."</table>":"");
	echo $ed->pg_number($pg, $totalpg)."</td></tr></table></div>";
	$ed->con = null;
break;

case "7"://create table
	$ed->check(array(1),array('db'=>$ed->sg[1]));
	$db= $ed->sg[1];
	if($ed->post('ctab','!e') && !is_numeric(substr($ed->post('ctab'),0,1)) && $ed->post('nrf','!e') && $ed->post('nrf')>0 && is_numeric($ed->post('nrf')) ) {
		echo $head.$ed->menu($db);
		if($ed->post('crtb','i')) {
		$q1='';
		$n=0;
		while($n<$ed->post('nrf')) {
			$v1=$ed->sanitize($ed->post('fi'.$n));
			if(!empty($v1) && !is_numeric(substr($v1,0,1))) {
			$v2=$ed->post('ty'.$n); $v3=$ed->post('vl'.$n); $v4=$ed->post('nl'.$n); $v5=$ed->post('df'.$n);
			$q1.=$v1." ".$v2.($v3!='' ? "(".$v3.")":"").($v4==1 ? " NOT NULL":"").($v5!="" ? " DEFAULT '".$v5."'":"").",";
			}
			++$n;
		}
		$q2= "CREATE TABLE ".$ed->sanitize($ed->post('ctab'))."(".substr($q1,0,-1).");";
		echo "<p class='box'>".(strlen($q1) > 5 && $ed->con->exec($q2) ? "<b>OK!</b> $q2<br/>" : "<b>FAILED!</b> $q2")."</p>";
		} else {
		echo $ed->form("7/$db")."<input type='hidden' name='ctab' value='".$ed->post('ctab')."'/>
		<input type='hidden' name='nrf' value='".$ed->post('nrf')."'/>".$stru;
		$nr= $ed->post('nrf');
		for($i=0;$i<$nr;$i++){
		echo "<tr><td><input type=text name='fi".$i."' /></td><td><select name='ty".$i."'>".$ed->fieldtype()."</select></td><td><input type=text name='vl".$i."' /></td><td><select name='nl".$i."'><option value=0>Yes</option><option value=1>No</option></select></td><td><input type=text name='df".$i."' /></td></tr>";
		}
		echo "<tr><td class='c1' colspan=5><button type='submit' name='crtb'>Create table</button></td></tr></table></form>";
		}
		echo "</div>";
	} else {
		$ed->redir("5/$db",array('err'=>"Table name must not be empty"));
	}
break;

case "9":
	$ed->check(array(1,2),array('db'=>$ed->sg[1]));
	$db= $ed->sg[1];
	$tb= $ed->sg[2];
	if($ed->post('copytab','!e')) {//copy table
		$cpy= $ed->post('copytab');
		$ncpy= basename($cpy,$ed->ext);
		$q_tc = $ed->con->query("SELECT sql FROM sqlite_master WHERE name='$tb'", true)->fetch();
		$r_sql = preg_split("/\([^()]*\)(*SKIP)(*F)|[()]/", $q_tc, -1, PREG_SPLIT_NO_EMPTY);
		$ed->con->exec("ATTACH DATABASE '".$ed->dir.$cpy."' AS ".$ncpy);
		$ed->con->exec("CREATE TABLE IF NOT EXISTS {$ncpy}.{$tb} (".$r_sql[1].");");
		$ed->con->exec("INSERT INTO {$ncpy}.{$tb} SELECT * FROM ".$tb);
		$ed->con->exec("DETACH DATABASE ".$ncpy);
	}
	if($ed->post('rtab','!e')) {//rename table
		$new= $ed->sanitize($ed->post('rtab'));
		$ren_tb= $ed->con->exec("ALTER TABLE $tb RENAME TO ".$new);
		if(!$ren_tb) $ed->redir("10/$db/$tb",array('err'=>"Can't rename"));
		$ed->con->exec("BEGIN TRANSACTION");
		$ed->con->exec("PRAGMA writable_schema=1");
		$q_rvtab= $ed->con->query("SELECT name,sql FROM sqlite_master WHERE type='view'")->fetch(1);//rename tb name in views
		if($q_rvtab) {
		foreach($q_rvtab as $r_rvtab) {
		$repl= preg_replace("/\b(".$tb.")\b/i",$new,$r_rvtab[1]);
		$ed->con->exec("UPDATE sqlite_master SET sql='".$repl."' WHERE name='".$r_rvtab[0]."'");
		}
		}
		$q_rvtig= $ed->con->query("SELECT name,sql FROM sqlite_master WHERE type='trigger'")->fetch(1);//rename tb name in triggers
		if($q_rvtig) {
		foreach($q_rvtig as $r_rvtig) {
		$repl= preg_replace("/\b(".$tb.")\b/i",$new,$r_rvtig[1]);
		$ed->con->exec("UPDATE sqlite_master SET sql='".$repl."' WHERE name='".$r_rvtig[0]."'");
		}
		}
		$ed->con->exec("PRAGMA writable_schema=0");
		$ed->con->exec("COMMIT");
	}
	if($ed->post('idx','!e') && is_array($ed->post('idx'))) {//create index
		$idx = implode(',',$ed->post('idx'));
		$ed->con->exec("BEGIN TRANSACTION");
		if($ed->post('primary','i')) {
			$q_pr = $ed->con->query("SELECT sql FROM sqlite_master WHERE name='$tb'", true)->fetch();
			preg_match('/(?<=\()(.+)(?=\))/ms', $q_pr, $r_prsql);
			$spos= strpos($r_prsql[1],"PRIMARY KEY");
			if($spos===false) {
			$r_sql= $r_prsql[1];
			} else {
			$r_sql= preg_split("/,\s*PRIMARY KEY\s*\(.*\)|\s+PRIMARY\s+KEY\s*AUTOINCREMENT|\s+PRIMARY\s+KEY/i", $r_prsql[1], -1, PREG_SPLIT_NO_EMPTY);
			$r_sql= implode("",$r_sql);
			}
			$ed->con->exec("BEGIN TRANSACTION");
			$ed->con->exec("CREATE INDEX pk_{$tb} ON $tb($idx)");
			$ed->con->exec("PRAGMA writable_schema=1");
			$ed->con->exec("UPDATE sqlite_master SET name='sqlite_autoindex_{$tb}_1',sql=null WHERE name='pk_{$tb}'");
			$ed->con->exec("UPDATE sqlite_master SET sql='CREATE TABLE $tb(".$r_sql.", PRIMARY KEY($idx))' WHERE name='$tb'");
			$ed->con->exec("COMMIT");
			$ed->con->exec("PRAGMA writable_schema=0");
		} elseif($ed->post('unique','i')) {
			$ed->con->exec("CREATE UNIQUE INDEX UNI__".uniqid(mt_rand())." ON $tb($idx)");
		} elseif($ed->post('index','i')) {
			$ed->con->exec("CREATE INDEX IDX__".uniqid(mt_rand())." ON $tb($idx)");
		}
		$ed->con->exec("VACUUM");
		$ed->con->exec("COMMIT");
		$ed->redir("10/{$db}/".$tb,array('ok'=>"Successfully created"));
	}
	if(!empty($ed->sg[3])) {//drop index
		$s_idx= base64_decode($ed->sg[3]);
		$ed->con->exec("DROP INDEX ".$s_idx);
		$ed->redir("10/$db/".$tb,array('ok'=>"Successfully dropped"));
	}
	$ed->con = null;
	$ed->redir("5/".$db,array('ok'=>"Successfully"));
break;

case "10"://table structure
	$ed->check(array(1,2),array('db'=>$ed->sg[1]));
	$db = $ed->sg[1];
	$tb = $ed->sg[2];
	echo $head.$ed->menu($db,$tb,1);
	echo $ed->form("9/$db/$tb")."<table class='a'><tr><th colspan=7>TABLE STRUCTURE</th></tr><tr><th><input type='checkbox' onclick='toggle(this,\"idx[]\")' /></th><th class='pro'>FIELD</th><th class='pro'>TYPE</th><th class='pro'>NULL</th><th class='pro'>DEFAULT</th><th class='pro'>PK</th><th class='pro'>ACTIONS</th></tr>";
	$r = $ed->con->query("PRAGMA table_info($tb)")->fetch(1);
	foreach($r as $rec) {
		$bg=($bg==1)?2:1;
		echo "<tr class='r c$bg'><td><input type='checkbox' name='idx[]' value='".$rec[1]."' /></td><td class='pro'>".$rec[1]."</td><td class='pro'>".$rec[2]."</td><td class='pro'>".($rec[3]==0 ? 'Yes':'No')."</td><td class='pro'>".$rec[4]."</td><td class='pro'>".($rec[5]==1 ? 'PK':'')."</td><td class='pro'><a href='{$ed->path}12/$db/$tb/".$rec[1]."'>change</a> | <a href='{$ed->path}13/$db/$tb/".$rec[1]."'>drop</a> | <a href='{$ed->path}11/$db/$tb/'>add</a></td></tr>";
	}
	echo "<tr><td class='div' colspan=7><button type='submit' name='primary'>Primary</button> <button type='submit' name='index'>Index</button> <button type='submit' name='unique'>Unique</button></td></tr></table></form>";
	$q_idx = $ed->con->query("PRAGMA index_list($tb)")->fetch(1);
	echo "<table class='a c1 mrg'><tr><th colspan=4>INDEXES</th></tr><tr><th class='pro'>NAME</th><th class='pro'>COLUMN</th><th class='pro'>Unique</th><th class='pro'>Action</th></tr>";
	foreach($q_idx as $rc) {
		$bg=($bg==1)?2:1;
		echo "<tr class='r c$bg'><td class='pro'>".$rc[1]."</td><td class='pro'>";
		$q= $ed->con->query("PRAGMA index_info('".$rc[1]."')")->fetch(1);
		foreach($q as $rd) {
			echo $rd[2]." ";
		}
		echo "</td><td class='pro'>".($rc[2]==1 ? 'YES':'NO')."</td><td class='pro'><a href='{$ed->path}9/$db/$tb/".base64_encode($rc[1])."'>Drop</a></td></tr>";
	}
	echo "</table>";
	$ed->con = null;
	echo "<table class='a c1 mrg'><tr><td>Rename Table<br/>".$ed->form("9/$db/$tb")."<input type='text' name='rtab' /><br/><button type='submit'>Rename</button></form><br/>Copy Table<br/>".$ed->form("9/$db/$tb")."<select name='copytab'>";
	$dh = @opendir($ed->dir);
	while(($dbe = readdir($dh)) != false) {
		if(is_file($ed->dir.$dbe) && strrchr($dbe,'.') == $ed->ext) {
			echo "<option value='{$dbe}'>".basename($dbe, $ed->ext)."</option>";
		}
	}
	closedir($dh);
	echo "</select><br/><button type='submit'>Copy</button></form></td></tr></table>
	</td></tr></table></div>";
break;

case "11"://add new field
	$ed->check(array(1,2),array('db'=>$ed->sg[1],'redir'=>10));
	$db= $ed->sg[1];
	$tb= $ed->sg[2];
	if($ed->post('add','i')) {
		$f1= $ed->sanitize($ed->post('f1'));
		if(!empty($f1)) {
		$e= $ed->con->exec("ALTER TABLE ".$tb." ADD COLUMN ".$f1." ".$ed->post('f2').($ed->post('f3','!e')?"(".$ed->post('f3').")":"").($ed->post('f4')==1 ? " NOT NULL":"").($ed->post('f5')!='' ? " DEFAULT '".$ed->post('f5')."'":""));
		} else $ed->redir("10/$db/$tb",array('err'=>"Empty field name"));
		$ed->con = null;
		if($e) $ed->redir("10/$db/$tb",array('ok'=>"Successfully added"));
		else $ed->redir("10/$db/$tb",array('err'=>"Can't add this field"));
	} else {
		echo $head.$ed->menu($db,$tb);
		echo $ed->form("11/$db/$tb").$stru;
		echo "<tr><td><input type='text' name='f1' /></td>
		<td><select name='f2'>".$ed->fieldtype()."</select></td>
		<td><input type='text' name='f3' /></td>
		<td><select name='f4'><option value=0>Yes</option><option value=1>No</option></select></td>
		<td><input type='text' name='f5' /></td></tr>
		<tr><td class='c1' colspan=5><button type='submit' name='add'>Add field</button></td></tr></table></form></div>";
	}
break;

case "12"://change field structure
	$ed->check(array(1,2,3),array('db'=>$ed->sg[1],'redir'=>10));
	$db= $ed->sg[1];
	$tb= $ed->sg[2];
	$fn= $ed->sg[3];
	$f= $ed->con->query("PRAGMA table_info($tb)")->fetch(1);
	if($ed->post('change','i')) {
		$qr='';$pk='';
		foreach($f as $e) {
		if($e[1]==$fn){
			$na1 = $ed->sanitize($ed->post("cf1"));
			if(empty($na1) || is_numeric(substr($na1,0,1))) $ed->redir("10/$db/$tb",array('err'=>"Not a valid field name"));
			$qr.= $na1." ".$ed->post('cf2').($ed->post('cf3','!e')?"(".$ed->post('cf3').")":"").($ed->post('cf4')==1 ? " NOT NULL":"").($ed->post("cf5")!=''?" DEFAULT ".$ed->post("cf5"):"").",";
			$pk.= ($e[5]==1 ? $na1.",":"");
		} else {
			$qr.= $e[1]." ".$e[2].($e[3]!=0 ? " NOT NULL":"").($e[4]!='' ? " ".$e[4]:"").",";
			$pk.= ($e[5]==1 ? $e[1].",":"");
		}
		}
		$qr.=($pk!='' ? " PRIMARY KEY (".substr($pk,0,-1)."),":"");
		$qrs="CREATE TABLE $tb(".substr($qr,0,-1).")";

		$ed->con->exec("BEGIN TRANSACTION");
		$ed->con->exec("PRAGMA writable_schema=1");
		//rename field in table
		$ed->con->exec("UPDATE sqlite_master SET sql='$qrs' WHERE name='$tb'");

		//rename field in index
		$q_idx = $ed->con->query("SELECT name,sql FROM sqlite_master WHERE type='index' AND tbl_name='{$tb}'")->fetch(1);
		foreach($q_idx as $r_idx) {
		if($r_idx[1]) {
		preg_match('/(.*)(?<=\()(.+)(?=\))/ms', $r_idx[1], $r_prsql);
		$repl = preg_replace('/\b('.$fn.')\b/i', $na1, $r_prsql[2]);
		$ed->con->exec("UPDATE sqlite_master SET sql='".$r_prsql[1].$repl.")' WHERE type='index' AND name='".$r_idx[0]."' AND tbl_name='{$tb}'");
		}
		}
		//rename field in views
		$q_rvtab= $ed->con->query("SELECT name,sql FROM sqlite_master WHERE type='view'")->fetch(1);
		if($q_rvtab) {
			foreach($q_rvtab as $r_rvtab) {
			if(strpos($r_rvtab[1]," ".$tb)!= false) {
			$replv= str_replace($fn,$na1,$r_rvtab[1]);
			$ed->con->exec("UPDATE sqlite_master SET sql='".$replv."' WHERE name='".$r_rvtab[0]."'");
			}
			}
		}
		//rename field in triggers
		$q_rvtig= $ed->con->query("SELECT name,sql FROM sqlite_master WHERE type='trigger' AND tbl_name='$tb'")->fetch(1);
		if($q_rvtig) {
		foreach($q_rvtig as $r_rvtig) {
			$replt= str_replace($fn,$na1,$r_rvtig[1]);
			$ed->con->exec("UPDATE sqlite_master SET sql='".$replt."' WHERE name='".$r_rvtig[0]."'");
		}
		}
		$ed->con->exec("PRAGMA writable_schema=0");
		$ed->con->exec("COMMIT");
		$ed->con = null;
		$ed->redir("10/$db/$tb",array('ok'=>"Successfully changed"));
	} else {
		echo $head.$ed->menu($db,$tb);
		echo $ed->form("12/$db/$tb/$fn").$stru;
		foreach($f as $d) {
			if($d[1]==$fn){
				$d_val= preg_split("/[()]+/", $d[2], -1, PREG_SPLIT_NO_EMPTY);
				echo "<tr><td><input type='text' name='cf1' value='".$d[1]."' /></td><td><select name='cf2'>".$ed->fieldtype(strtoupper($d_val[0])).
				"</select></td><td><input type='text' name='cf3' value='".(isset($d_val[1])?$d_val[1]:"")."' /></td>
				<td><select name='cf4'><option value='0'>Yes</option><option value='1'".($d[3]!=0 ? " selected":"").">No</option></select></td>
				<td><input type='text' name='cf5' value='".str_replace("'","",$d[4])."' /></td></tr>";
			}
		}
		echo "<tr><td class='c1' colspan=5><button type='submit' name='change'>Change field</button></td></tr></table></form></div>";
	}
break;

case "13"://drop column
	$ed->check(array(1,2,3),array('db'=>$ed->sg[1],'redir'=>10));
	$db= $ed->sg[1];
	$tb= $ed->sg[2];
	$fn= $ed->sg[3];
	$q_nof= $ed->con->query("SELECT * FROM ".$tb);
	$nof= $q_nof->num_col();
	if($nof>1){
		$f= $ed->con->query("PRAGMA table_info($tb)")->fetch(1);
		$qr='';
		foreach($f as $e) {
		if($e[1]!=$fn){
			$qr.= $e[1]." ".$e[2].($e[3]!=0 ? " NOT NULL":"").($e[4]!='' ? " ".$e[4]:"").($e[5]==1 ? " PRIMARY KEY":"").",";
		}
		}
		$qrs="CREATE TABLE $tb(".substr($qr,0,-1).")";
		$ed->con->exec("BEGIN TRANSACTION");
		$ed->con->exec("PRAGMA writable_schema=1");
		//remove field from index
		$q_idx = $ed->con->query("SELECT name,sql FROM sqlite_master WHERE type='index' AND tbl_name='$tb'")->fetch(1);
		if($q_idx) {
		foreach($q_idx as $r_idx) {
		if($r_idx[1]) {
		preg_match('/(.*)(?<=\()(.+)(?=\))/ms', $r_idx[1], $r_prsql);
		$repl= explode(',', $r_prsql[2]);
		$po= array_search($fn, $repl);
		unset($repl[$po]);
		$repl= implode(',',$repl);
		$ed->con->exec("UPDATE sqlite_master SET sql='".$r_prsql[1].$repl.")' WHERE type='index' AND name='".$r_idx[0]."' AND tbl_name='$tb'");
		}
		}
		}
		//drop field tb
		$q_rtb= $ed->con->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='$tb'", true)->fetch();
		if($q_rtb > 0) {
			$ed->con->exec("UPDATE sqlite_master SET sql='$qrs' WHERE name='$tb'");
		}
		//drop field view
		$q_tbw= $ed->con->query("SELECT name,sql FROM sqlite_master WHERE type='view'")->fetch(2);
		if($q_tbw) {
		foreach($q_tbw as $r_tbw) {
			preg_match("/\b(".$tb.")\b/i",$r_tbw['sql'],$match1);
			preg_match("/\b(".$fn.")\b/i",$r_tbw['sql'],$match2);
			if($match1 && $match2) {
				$ed->con->exec("DROP VIEW ".$r_tbw['name']);
			}
		}
		}
		$ed->con->exec("PRAGMA writable_schema=0");
		$ed->con->exec("COMMIT");
		$ed->con = null;
		$ed->redir("10/$db/$tb",array('ok'=>"Successfully deleted"));
	} else {
		$q_dv= $ed->con->query("SELECT name,sql FROM sqlite_master WHERE type='view'")->fetch(1);//drop view assoc with table
		if($q_dv) {
		foreach($q_dv as $r_dv) {
			preg_match("/\b(".$tb.")\b/i",$r_dv[1],$match);
			if($match) {
			$ed->con->exec("DROP VIEW ".$r_dv[0]);
			}
		}
		}
		$ed->con->exec("DROP TABLE ".$tb);
		$ed->redir("5/$db",array('ok'=>"Successfully dropped"));
	}
break;

case "21"://table browse
	$ed->check(array(1,2),array('db'=>$ed->sg[1]));
	$db= $ed->sg[1];
	$tb= $ed->sg[2];
	$all = $ed->con->query("SELECT COUNT(*) FROM ".$tb, true)->fetch();
	$totalpg = ceil($all/$step);
	if(empty($ed->sg[3])) {
		$pg = 1;
	} else {
		$pg = $ed->sg[3];
		$ed->check(array(4),array('pg'=>$pg,'total'=>$totalpg,'redir'=>"21/$db/$tb"));
	}
	$offset = ($pg - 1) * $step;
	$q_rex = $ed->con->query("SELECT * FROM $tb LIMIT $offset, $step");
	$cols = $q_rex->num_col();
	$res = $q_rex->fetch(1);

	$tbinfo = $ed->con->query("PRAGMA table_info($tb)");
	$cols_name= $tbinfo->fetch(2);

	echo $head;
	$q_vws = $ed->con->query("SELECT type FROM sqlite_master WHERE name='$tb'", true)->fetch();
	echo $ed->menu($db,($q_vws != 'view' ? $tb:""),1);
	echo "<table class='a'><tr>";
	if($q_vws != 'view') echo "<th colspan=2><a href='{$ed->path}22/$db/$tb'>INSERT</a></th>";
	foreach($cols_name as $c_name) {
	echo "<th>". $c_name['name']."</th>";
	}
	echo "</tr>";

	$rinf = array();
	$q_ti = $ed->con->query("PRAGMA table_info($tb)")->fetch(1);
	foreach($q_ti as $r_ti) {
		$rinf[$r_ti[0]]= $r_ti[2];
	}

	foreach($res as $row) {
		$bg=($bg==1)?2:1;
		$id=base64_encode($row[0]);
		echo "<tr class='r c$bg'>";
		if($q_vws != 'view') echo "<td><a href='{$ed->path}23/$db/$tb/".$cols_name[0]['name']."/".$id."'>Edit</a></td><td><a href='{$ed->path}24/$db/$tb/".$cols_name[0]['name']."/".$id."'>Delete</a></td>";
		for($j=0;$j<$cols;$j++) {
			echo "<td class='pro'>";
			if(stristr($rinf[$j],"blob") == true ) {
			$le= strlen(base64_decode($row[$j]));
			echo "[blob] ";
			if($le > 4) {
			echo "<a href='".$ed->path."25/$db/$tb/".$cols_name[0]['name']."/$id/".$cols_name[$j]['name']."'>".number_format(($le/1024),2)." KB</a>";
			} else {
			echo number_format(($le/1024),2)." KB";
			}
			} elseif(strlen($row[$j]) > 200) {
			echo substr(htmlentities($row[$j],ENT_QUOTES,"UTF-8"),0,200);
			} else echo htmlentities($row[$j],ENT_QUOTES,"UTF-8");
			echo "</td>";
		}
		echo "</tr>";
	}
	$ed->con = null;
	echo "</table>".$ed->pg_number($pg, $totalpg)."</td></tr></table></div>";
break;

case "22"://insert row
	$ed->check(array(1,2),array('db'=>$ed->sg[1]));
	$db= $ed->sg[1];
	$tb= $ed->sg[2];
	$q_pra= $ed->con->query("PRAGMA table_info($tb)")->fetch(2);
	if($ed->post('insert','i')) {
		$qr2="INSERT INTO $tb (";
		$qr4="VALUES(";
		$i=0;
		foreach($q_pra as $r_re) {
			if($ed->post('r'.$i,'e') && $r_re['notnull'] != 0) $ed->redir("21/$db/$tb",array('err'=>"Field structure is NotNull"));
			if(strtolower($r_re['type'])=="boolean") {
			$qr2.=$r_re['name'].",";
			$qr4.= "'".($ed->post('r'.$i,0) ? 1:'')."',";
			} elseif(strtolower($r_re['type'])=="blob") {
			if(!empty($_FILES['r'.$i]['tmp_name'])) {
			$qr2.=$r_re['name'].",";
			$qr4 .= "'".base64_encode(file_get_contents($_FILES['r'.$i]['tmp_name']))."',";
			}
			} else {
			$qr2.=$r_re['name'].",";
			$qr4.="'".$ed->post('r'.$i,'',1)."',";
			}
			++$i;
		}
		$qr2=substr($qr2,0,-1).") ";
		$qr4=substr($qr4,0,-1).")";
		$ed->con->exec($qr2.$qr4);
		$ed->redir("21/$db/$tb",array('ok'=>"Successfully inserted"));
	} else {
		echo $head.$ed->menu($db,$tb,1);
		echo $ed->form("22/$db/$tb", 1)."<table class='a'><caption>Insert Row</caption>";
		foreach($q_pra as $r_pra) {
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
	$ed->check(array(1,2,3),array('db'=>$ed->sg[1],'redir'=>21));
	$db= $ed->sg[1];
	$tb= $ed->sg[2];
	$nu= $ed->sg[3];
	$id= base64_decode($ed->sg[4]);
	$q_rd= $ed->con->query("PRAGMA table_info($tb)")->fetch(2);
	if($ed->post('edit','i')) {
		$qr="";
		foreach($q_rd as $r_rd) {
			if($ed->post('d'.$r_rd['name'],'e') && $r_rd['notnull'] != 0) $ed->redir("21/$db/$tb",array('err'=>"Field structure is NotNull"));
			$stype= strtolower($r_rd['type']);
			if($stype=="blob") {
				if(!empty($_FILES['d'.$r_rd['name']]['tmp_name'])) {
				$qr .= $r_rd['name']."='".base64_encode(file_get_contents($_FILES['d'.$r_rd['name']]['tmp_name']))."',";
				}
			} elseif($stype=="boolean") {
				$qr .= $r_rd['name']."='".($ed->post('d'.$r_rd['name'],0) ? 1:'')."',";
			} else {
				$qr.= $r_rd['name']."='".$ed->post('d'.$r_rd['name'],'',1)."',";
			}
		}
		$qq=substr($qr,0,-1);
		$ed->con->exec("UPDATE $tb SET ".$qq." WHERE ".$nu."='".$id."'");
		$ed->redir("21/$db/$tb",array('ok'=>"Successfully updated"));
	} else {
		$arr= $ed->con->query("SELECT * FROM ".$tb." WHERE ".$nu."='".$id."'")->fetch(2);
		if(!$arr) $ed->redir("21/$db/$tb",array('err'=>"Can't edit empty field"));
		echo $head.$ed->menu($db,$tb,1);
		echo $ed->form("23/$db/$tb/$nu/".$ed->sg[4], 1)."<table class='a'><caption>Edit Row</caption>";
		foreach($q_rd as $r_ed) {
			$nr=$r_ed['name'];
			$typ= strtolower($r_ed['type']);
			echo "<tr><td>".$r_ed['name']."</td><td>";
			if($typ=="boolean") {
			foreach($bbs as $kk=>$bb) {
			echo "<input type='radio' name='d".$nr."[]' value='$kk'".($arr[0][$nr]==$kk ? " checked":"")." /> $bb ";
			}
			} elseif($typ=="blob") {
			echo "[blob] ".number_format((strlen($arr[0][$nr])/1024),2)." KB<br/><input type='file' name='d".$nr."' />";
			} elseif($typ=="text") {
			echo "<textarea name='d".$nr."'>".html_entity_decode($arr[0][$nr],ENT_QUOTES)."</textarea>";
			} else {
			echo "<input type='text' name='d".$nr."' value='".stripslashes($arr[0][$nr])."' />";
			}
			echo "</td></tr>";
		}
		echo "<tr><td class='c1' colspan=2><button type='submit' name='edit'>Update</button></td></tr></table></form>";
		echo "</td></tr></table></div>";
	}
break;

case "24"://delete row
	$ed->check(array(1,2,3),array('db'=>$ed->sg[1],'redir'=>21));
	$db= $ed->sg[1];
	$tb= $ed->sg[2];
	$exec_dr= $ed->con->query("DELETE FROM ".$tb." WHERE ".$ed->sg[3]."='".base64_decode($ed->sg[4])."'");
	if($exec_dr->last()) $ed->redir("21/$db/$tb",array('ok'=>"Deleted row"));
	else $ed->redir("21/$db/$tb",array('err'=>"Delete row failed"));
break;

case "25": //blob download
	$ed->check(array(1,2,3),array('db'=>$ed->sg[1],'redir'=>21));
	$db= $ed->sg[1];
	$tb= $ed->sg[2];
	$nu= $ed->sg[3];
	$id= base64_decode($ed->sg[4]);
	$ph= $ed->sg[5];
	$q_ph = $ed->con->query("SELECT {$ph} FROM {$tb} WHERE {$nu} LIKE '".$id."'", true)->fetch();
	if(strpos($q_ph, "\0")===false) {
	$r_ph= $q_ph;
	} else {
	$r_ph= base64_decode($q_ph);
	}
	$len= strlen($r_ph);
	if($len >= 2 && $r_ph[0] == chr(0xff) && $r_ph[1] == chr(0xd8)) {$tp= 'image/jpeg';$xt='.jpg';}
	elseif($len >= 3 && substr($r_ph, 0, 3) == 'GIF') {$tp= 'image/gif';$xt='.gif';}
	elseif($len >= 4 && substr($r_ph, 0, 4) == "\x89PNG") {$tp= 'image/png';$xt='.png';}
	else {$tp= 'application/octet-stream';$xt='.bin';}
	header("Content-type: ".$tp);
	header("Content-Length: ".$len);
	header("Content-Disposition: attachment; filename=bin-".$id.$xt);
	die($r_ph);
break;

case "26"://table empty
	$ed->check(array(1,2),array('db'=>$ed->sg[1]));
	$db= $ed->sg[1];
	$tb= $ed->sg[2];
	$exec_te= $ed->con->exec("DELETE FROM ".$tb);
	$ed->con = null;
	$ed->redir("5/$db",array('ok'=>"Table is empty"));
break;

case "27"://drop table, view
	$ed->check(array(1,2),array('db'=>$ed->sg[1]));
	$db= $ed->sg[1];
	$tb= $ed->sg[2];
	$ed->con->exec("BEGIN TRANSACTION");
	$rs= $ed->con->query("SELECT type FROM sqlite_master WHERE name='$tb'", true)->fetch();
	$q_dv= $ed->con->query("SELECT name,sql FROM sqlite_master WHERE type='view'")->fetch(1);//drop view assoc with table
	if($q_dv) {
	foreach($q_dv as $r_dv) {
	preg_match("/\b(".$tb.")\b/i",$r_dv[1],$match);
	if($match) {
	$ed->con->exec("DROP VIEW ".$r_dv[0]);
	}
	}
	}
	$ed->con->exec("DROP ".$rs." ".$tb);
	$ed->con->exec("COMMIT");
	$ed->con->exec("VACUUM");
	$ed->con = null;
	$ed->redir("5/$db",array('ok'=>"Successfully dropped"));
break;

case "28"://vacuum
	$ed->check(array(1,2),array('db'=>$ed->sg[1]));
	$ed->con->exec("VACUUM");
	$ed->con = null;
	$ed->redir("10/".$ed->sg[1]."/".$ed->sg[2],array('ok'=>"Successfully vacuumed"));
break;

case "30"://import
	$ed->check(array(1),array('db'=>$ed->sg[1]));
	$db= $ed->sg[1];
	$out="<div class='box'>";
	$rgex = "~^\xEF\xBB\xBF|(\#|--).*|(?-m)\(([^)]*\)*(\".*\")*('.*'))(*SKIP)(*F)|(?is)(BEGIN.*?END)(*SKIP)(*F)|(?<=;)(?![ ]*$)~m";
	if($ed->post('qtxt','!e')) {//in textarea
		$e= preg_split($rgex, $ed->post('qtxt','',1), -1, PREG_SPLIT_NO_EMPTY);
	} elseif($ed->post('send','i') && $ed->post('send') == "ja") {//from file
		if(empty($_FILES['importfile']['tmp_name'])) {
		$e='';
		$ed->redir("5/$db",array('err'=>"No file to upload"));
		} else {
			$tmp = $_FILES['importfile']['tmp_name'];
			$file= pathinfo($_FILES['importfile']['name']);
			$fext = strtolower($file['extension']);
			if($fext == 'sql') {//sql file
				$fi = $ed->clean(file_get_contents($tmp),1);
				$e= preg_split($rgex, $fi, -1, PREG_SPLIT_NO_EMPTY);
			} elseif($fext == 'csv') {//csv file
				$e= $ed->imp_csv($file['filename'], $tmp);
			} elseif($fext == 'sqlite' || $fext == substr($ed->ext,1)) {//sqlite file
				$ed->imp_sqlite($file['filename'], $tmp);
			} elseif($fext == 'gz') {//gz file
				if(($fgz = fopen($tmp, 'r')) !== FALSE) {
					if(@fread($fgz, 3) != "\x1F\x8B\x08") {
					$ed->redir("5/$db",array('err'=>"Not a valid GZ file"));
					}
					fclose($fgz);
				}
				if(@function_exists('gzopen')) {
					$gzfile = @gzopen($tmp, 'rb');
					if (!$gzfile) {
					$ed->redir("5/$db",array('err'=>"Can't open GZ file"));
					}
					$e = '';
					while (!gzeof($gzfile)) {
					$e .= gzgetc($gzfile);
					}
					gzclose($gzfile);
					$entr= pathinfo($file['filename']);
					$e_ext= $entr['extension'];
					if($e_ext == 'sql') $e= preg_split($rgex, $e, -1, PREG_SPLIT_NO_EMPTY);
					elseif($e_ext == 'csv') $e= $ed->imp_csv($entr['filename'], $e);
					elseif($e_ext == 'sqlite' || $e_ext == substr($ed->ext,1)) $ed->imp_sqlite($file['filename'], $e);
					else $ed->redir("5/$db",array('err'=>"Disallowed extension"));
				} else {
					$ed->redir("5/$db",array('err'=>"Can't open GZ file"));
				}
			} elseif($fext == 'zip') {//zip file
				if(($fzip = fopen($tmp, 'r')) !== FALSE) {
					if(@fread($fzip, 4) != "\x50\x4B\x03\x04") {
					$ed->redir("5/$db",array('err'=>"Not a valid ZIP file"));
					}
					fclose($fzip);
				}
				$zip = zip_open($tmp);
				if(is_resource($zip)) {
					$buf = '';
					while($zip_entry = zip_read($zip)) {
					if($file['filename'] == zip_entry_name($zip_entry)) {
					if(zip_entry_open($zip, $zip_entry, "rb")) {
					$buf .= zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
					zip_entry_close($zip_entry);
					}
					}
					}
					zip_close($zip);
					$entr= pathinfo($file['filename']);
					$e_ext= $entr['extension'];
					if($e_ext == 'sql') $e= preg_split($rgex, $buf, -1, PREG_SPLIT_NO_EMPTY);
					elseif($e_ext == 'csv') $e= $ed->imp_csv($entr['filename'], $buf);
					elseif($e_ext == 'sqlite' || $e_ext == substr($ed->ext,1)) $ed->imp_sqlite($file['filename'], $buf);
					else $ed->redir("5/$db",array('err'=>"Disallowed extension"));
				}
			} else {
				$ed->redir("5/$db",array('err'=>"Disallowed extension"));
			}
		}
	} else {
		$ed->redir("5/$db",array('err'=>"Query failed"));
	}
	if(is_array($e)) {
		set_error_handler(function() {});
		$ed->con->exec("BEGIN TRANSACTION");
		foreach($e as $qry) {
			$qry= trim($qry);
			if(!empty($qry)) {
				$exc= $ed->con->query($qry);
				$op= array('insert','update','delete');
				$p_qry= strtolower(substr($qry,0,6));
				if(in_array($p_qry, $op)) $exc = $exc->last();
				$e_exc= $ed->con->err();
				$out .= "<p>";
				$ok="<b>OK!</b> ";
				$fa="<b>FAILED!</b> ";
				if($_SESSION['contype'] == DBT::$contype[0]) {
				$out .= ($exc && !$e_exc ? $ok.$qry : $fa.$qry);
				} else {
				$out .= ($exc && ($e_exc[0]==='00000' || $e_exc[0]==='01000') ? $ok.$qry : $fa.$qry);
				}
				$out .= "</p>";
			}
		}
		$ed->con->exec("COMMIT");
	}
	$ed->con = null;
	echo $head.$ed->menu($db).$out."</div>";
break;

case "31"://export form
	$ed->check(array(1),array('db'=>$ed->sg[1]));
	$db= $ed->sg[1];
	$q_extbs= $ed->con->query("SELECT name FROM sqlite_master WHERE type='table' OR type='view'")->fetch(1);
	$deny= array('sqlite_sequence');
	$ex=0;$r_tts=array();
	foreach($q_extbs as $r_extbs) {
	if(!in_array($r_extbs[0],$deny)) {
	$r_tts[]= $r_extbs[0];
	++$ex;
	}
	}
	if($ex > 0) {
	echo $head.$ed->menu($db);
	echo "<table><tr><td>".$ed->form("32/$db")."<table class='a'><tr><th>Export</th></tr><tr><td>
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
	$ffo = array('sql'=>'SQL','csv'=>'CSV','sqlite'=>'SQLite');
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
	$ed->redir("5/".$db,array("err"=>"No export empty DB"));
	}
break;

case "32"://export
	$ed->check(array(1),array('db'=>$ed->sg[1]));
	$db= $ed->sg[1];
	$tbs= array();
	$vws= array();
	$sql='';
	$ffmt= $ed->post('ffmt');
	if(!in_array('sqlite',$ffmt)) {
	if($ed->post('tables') =='') {//push selected tables
		$ed->redir("31/".$db,array('err'=>"You didn't select any table"));
	} else {
		$tabs= $ed->post('tables');
		foreach($tabs as $tab) {
			$q_strc= $ed->con->query("SELECT name,type FROM sqlite_master WHERE name='$tab'")->fetch(2);
			if($q_strc[0]['name']==$tab && $q_strc[0]['type']=='view') {
			array_push($vws, $tab);
			} elseif($q_strc[0]['name']==$tab && $q_strc[0]['type']=='table') {
			array_push($tbs, $tab);
			}
		}
	}
	if($ed->post('fopt')=='') {//check export options
		$ed->redir("31/".$db,array('err'=>"You didn't select any option"));
	} else {
		$fopt=$ed->post('fopt');
	}
	}
	if(in_array('sql',$ffmt)) {//data for sql format
		$sql.="-- EdLiteAdmin SQL Dump\n-- version ".$version."\n\n";
		if(!empty($fopt)) {
			foreach($tbs as $ttd) {
			$val="";
			if(in_array('structure',$fopt)) {//begin structure
				if(in_array('drop',$fopt)) {//check option drop
					$sql .= "DROP TABLE IF EXISTS $ttd;\n";
				}
				$sql .= $ed->con->query("SELECT sql FROM sqlite_master WHERE name='$ttd'", true)->fetch().";";//structure
				$q_tidx= $ed->con->query("SELECT sql FROM sqlite_master WHERE tbl_name='$ttd' AND type='index'");
				$cidx= $q_tidx->num_col();
				if($cidx > 0) {
					foreach($q_tidx->fetch(1) as $r_ix) {
					$val.= "\n".$r_ix[0].";";
					}
					$val.="\n";
				}
			}
			if(in_array('data',$fopt)) {//check option data
				$res2 = $ed->con->query("SELECT * FROM ".$ttd);
				$nr= $res2->num_col();
				foreach($res2->fetch(1) as $row) {
					$ro= "\nINSERT INTO $ttd VALUES(";
					for($i = 0; $i < $nr; $i++) {
						if(is_numeric($row[$i])) $ro.= $row[$i].",";
						else $ro.= "'".$row[$i]."',";
					}
					$val.= substr($ro,0,-1).");";
				}
				$val.= "\n";
			}
			if(in_array('trigger',$fopt)) {//check option data
				$q_ttgr= $ed->con->query("SELECT name,sql FROM sqlite_master WHERE tbl_name='$ttd' AND type='trigger'")->fetch(2);
				if(isset($q_ttgr[0]['name'])) {
				if(in_array('drop',$fopt)) {//check option drop
				$val .= "\nDROP TRIGGER IF EXISTS ".$q_ttgr[0]['name'].";";
				}
				$val .= "\n".$q_ttgr[0]['sql'].";\n";
				}
			}
			$sql.= $val."\n";
			}
			if($vws != '' && in_array('structure',$fopt)) {//export views
			foreach($vws as $vw) {
				$q_rw= $ed->con->query("SELECT sql FROM sqlite_master WHERE type='view'", true)->fetch();
				if($q_rw) {
				if(in_array('drop',$fopt)) {//check option drop
				$sql .= "DROP VIEW IF EXISTS $vw;\n";
				}
				$sql .= $q_rw.";\n\r";
				}
			}
			}
		}
	} elseif(in_array('csv',$ffmt)) {//csv format
		$tbs= array_merge($tbs, $vws);
		if(empty($tbs[0])) $ed->redir("31/".$db,array('err'=>"Select a table/view"));
		$q_csvs= $ed->con->query("SELECT * FROM ".$tbs[0]);
		$q_csv= $q_csvs->fetch(1);
		$ncol= $q_csvs->num_col();
		$cols= $ed->con->query("PRAGMA table_info(".$tbs[0].")")->fetch(2);
		for($i=0;$i < $ncol;++$i) {
			$sql.='"'.$cols[$i]['name'].'",';
		}		
		$sql=substr($sql,0,-1)."\n";
		foreach($q_csv as $r_rs) {
			for($t=0;$t<$ncol;$t++) $sql.="\"".str_replace('"','""',$r_rs[$t])."\",";
			$sql=substr($sql,0,-1)."\n";
		}
		$sql.="\n";
	} elseif(in_array('sqlite',$ffmt)) {//sqlite format
		$sql .= file_get_contents($ed->dir.$db.$ed->ext);
	}
	if(in_array("sql", $ffmt)) {//type, ext
		$ffty= "text/plain";
		$ffext= ".sql";
		$fname= $db.(count($tbs) == 1 ? ".".$tbs[0] : "").$ffext;
	} elseif(in_array("csv", $ffmt)) {
		$ffty= "text/csv";
		$ffext= ".csv";
		$fname= $tbs[0].$ffext;
	} elseif(in_array("sqlite", $ffmt)) {
		$ffty= "application/octet-stream";
		$ffext= $ed->ext;
		$fname= $db.$ffext;
	}
	$ftype= $ed->post('ftype');
	if(in_array("gzip", $ftype)) {//pack
		$zty= "application/x-gzip";
		$zext= ".gz";
	} elseif(in_array("zip", $ftype)) {
		$zty= "application/x-zip";
		$zext= ".zip";
	}
	if(in_array("gzip", $ftype)) {//gzip
		ini_set('zlib.output_compression','Off');
		$sql = gzencode($sql, 9);
		header('Content-Encoding: gzip');
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
	header("Content-Length: ".strlen($sql));
	header("Content-Disposition: attachment; filename=".$fname.(in_array("plain", $ftype) ? "":$zext));
	die($sql);
break;

case "40"://view
	if(!isset($ed->sg[2]) && !isset($ed->sg[3])) {//create
		$ed->check(array(1),array('db'=>$ed->sg[1]));
		$db= $ed->sg[1];
		$r_uv= array(1=>'',2=>'');
		if($ed->post('uv1','!e') && $ed->post('uv2','!e')) {
			$tb= $ed->sanitize($ed->post('uv1'));
			$exi= $ed->con->query("SELECT 1 FROM sqlite_master WHERE name='$tb'", true)->fetch();
			if($exi) $ed->redir("5/".$db,array('err'=>"This name exist"));
			$vstat= $ed->post('uv2','',1);
			$stat= $ed->con->exec($vstat);
			if(!$stat) $ed->redir("5/".$db,array('err'=>"Wrong statement"));
			$v_cre= $ed->con->exec("CREATE VIEW ".$tb." AS ".$vstat);
			if($v_cre) $ed->redir("5/".$db,array('ok'=>"Successfully created"));
			else $ed->redir("5/".$db,array('err'=>"Can't create view"));
		}
		echo $head.$ed->menu($db);
		echo $ed->form("40/$db");
		$b_lbl="Create";
	} else {//edit
		$ed->check(array(1,5),array('db'=>$ed->sg[1]));
		$db= $ed->sg[1];$sp= $ed->sg[2];$ty= $ed->sg[3];
		$q_uv = $ed->con->query("SELECT sql FROM sqlite_master WHERE type='$ty' AND name='$sp'", true)->fetch();
		preg_match('/CREATE\sVIEW\s(.*)\s+AS\s+(.*)$/i', $q_uv, $r_uv);
		if($ed->post('uv1','!e') && $ed->post('uv2','!e')) {
			$tb= $ed->sanitize($ed->post('uv1'));
			if(is_numeric(substr($tb,0,1))) $ed->redir("5/".$db,array('err'=>"Not a valid name"));
			$exi= $ed->con->query("SELECT 1 FROM sqlite_master WHERE name='$tb'", true)->fetch();
			if($exi && $tb!=$r_uv[1]) $ed->redir("5/".$db,array('err'=>"This name exist"));
			$vstat= $ed->post('uv2','',1);
			$stat= $ed->con->exec($vstat);
			if(!$stat) $ed->redir("5/".$db,array('err'=>"Wrong statement"));
			$ed->con->exec("DROP $ty ".$sp);
			$ed->con->exec("CREATE VIEW ".$tb." AS ".$vstat);
			$ed->redir("5/".$db,array('ok'=>"Successfully updated"));
		}
		echo $head.$ed->menu($db,'','',array($ty,$sp));
		echo $ed->form("40/$db/$sp/$ty");
		$b_lbl="Edit";
	}
	echo "<table class='a1'><tr><th colspan=2>$b_lbl View</th></tr>
	<tr><td>Name</td><td><input type='text' name='uv1' value='".$r_uv[1]."'/></td></tr>
	<tr><td>Statement</td><td><textarea name='uv2'>".$r_uv[2]."</textarea></td></tr>
	<tr><td class='c1' colspan=2><button type='submit'>Save</button></td></tr></table></form></div>";
break;

case "41"://trigger
	if(!isset($ed->sg[2]) && !isset($ed->sg[3])) {//create
		$ed->check(array(1),array('db'=>$ed->sg[1]));
		$db= $ed->sg[1];
		$r_tge= array(1=>'');
		if($ed->post('utg1','!e') && $ed->post('utg5','!e')) {
		$q_tgcrt= $ed->con->exec("CREATE TRIGGER ".$ed->sanitize($ed->post('utg1'))." ".$ed->post('utg2')." ".$ed->post('utg3')." ON ".$ed->post('utg4')." BEGIN ".$ed->post('utg5','',1)."; END");
		if($q_tgcrt) $ed->redir("5/".$db,array('ok'=>"Successfully created"));
		else $ed->redir("5/".$db,array('err'=>"Create trigger failed"));
		}
		echo $head.$ed->menu($db);
		echo $ed->form("41/$db");
		$t_lbl="Create";
	} else {//edit
		$ed->check(array(1,5),array('db'=>$ed->sg[1]));
		$db= $ed->sg[1];$sp= $ed->sg[2];$ty= $ed->sg[3];
		if($ed->post('utg1','!e') && $ed->post('utg5','!e')) {
			$utg= $ed->sanitize($ed->post('utg1'));
			if(is_numeric(substr($utg,0,1))) $ed->redir("5/".$db,array('err'=>"Not a valid name"));
			$ed->con->exec("DROP {$ty} ".$sp);
			$q_tgcrt= $ed->con->exec("CREATE TRIGGER ".$utg." ".$ed->post('utg2')." ".$ed->post('utg3')." ON ".$ed->post('utg4')." BEGIN ".$ed->post('utg5','',1)."; END");
			if($q_tgcrt) $ed->redir("5/".$db,array('ok'=>"Successfully updated"));
			else $ed->redir("5/".$db,array('err'=>"Update trigger failed"));
		}
		$q_tge = $ed->con->query("SELECT sql FROM sqlite_master WHERE type='$ty' AND name='$sp'", true)->fetch();
		preg_match('/CREATE\sTRIGGER\s(.*)\s+(.*)\s+(.*)\s+ON\s+(.*)\s+BEGIN\s+(.*);\s+END$/i', $q_tge, $r_tge);
		echo $head.$ed->menu($db,'','',array($ty,$sp));
		echo $ed->form("41/$db/$sp/$ty");
		$b_lbl="Edit";
	}
	$tgtb= array();
	$q_ttbs = $ed->con->query("SELECT name FROM sqlite_master WHERE type='table'")->fetch(1);
	foreach($q_ttbs as $r_ttbs) {
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
	$ed->check(array(1,5),array('db'=>$ed->sg[1]));
	$ed->con->exec("DROP ".$ed->sg[3]." ".$ed->sg[2]);
	$ed->redir('5/'.$ed->sg[1],array('ok'=>"Successfully dropped"));
break;

case "50": //login
	if($ed->post('password','i') && $ed->post('contype','!e')) {
		$_SESSION['contype']= $ed->post('contype');
		$_SESSION['token']= base64_encode(md5($_SERVER['HTTP_USER_AGENT'].$ed->post('password')));
		$ed->ver();
		$ed->redir();
	}
	session_unset();
	session_destroy();
	echo $head."<div class='scroll'>".$ed->form("50")."<table class='a1'><caption>LOGIN</caption>
	<tr><td>Connect with:<br/><select name='contype'>";
	foreach(DBT::$contype as $cotyp) {
	if(extension_loaded($cotyp)) echo "<option value='".$cotyp."'>".$cotyp."</option>";
	}
	echo "</select></td></tr>
	<tr><td>Password<br/><input type='password' name='password' /></td></tr>
	<tr><td><button type='submit'>Login</button></table></form></div>";
break;

case "51": //logout
	$ed->check();
	session_unset();
	session_destroy();
	$ed->redir();
break;
}
unset($_POST);
unset($_SESSION["ok"]);
unset($_SESSION["err"]);
echo '<div class="l1" style="text-align:center"><a href="http://edmondsql.github.io">edmondsql</a></div></body></html>';