<?php
error_reporting(E_ALL);
if(version_compare(PHP_VERSION,'5.4.0','<')) die('Require PHP 5.4 or higher');
if(!extension_loaded('sqlite3') && !extension_loaded('pdo_sqlite')) die('Install sqlite3 or pdo_sqlite extension!');
session_name('Lite');
session_start();
$bg=2;
$step=20;
$version="3.14.2";
$bbs=['False','True'];
$deny=['sqlite_sequence'];
$js=(file_exists('jquery.js')?"/jquery.js":"https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js");
class DBT {
	public static $litetype=['sqlite3','pdo_sqlite'];
	private static $instance=NULL;
	private $_cnx,$_query,$_fetch=[],$_num_col,$ltype;
	public static function factory($db) {
		if(!isset(self::$instance)) self::$instance=new DBT($db);
		return self::$instance;
	}
	public function __construct($db) {
		$ty=self::$litetype[0];
		if(extension_loaded($ty)) $this->ltype=$ty;
		else $this->ltype=self::$litetype[1];
		if($this->ltype==$ty) $this->_cnx=new SQLite3($db);
		else $this->_cnx=new PDO("sqlite:".$db);
	}
	private function __clone() {}
	public function exec($sql) {
		return $this->_cnx->exec($sql);
	}
	public function query($sql,$single=false) {
		try{
		if($this->ltype==self::$litetype[0]) {
		if($single==false) $this->_query=$this->_cnx->query($sql);
		else $this->_query=$this->_cnx->querySingle($sql);
		} else {
		$this->_query=$this->_cnx->query($sql);
		}
		return $this;
		} catch(Exception $e) {
		return false;
		}
	}
	public function last() {
		if($this->ltype==self::$litetype[0]) return $this->_cnx->changes();
		else return $this->_query->rowCount();
	}
	public function err() {
		if($this->ltype==self::$litetype[0]) return $this->_cnx->lastErrorCode();
		else return $this->_cnx->errorInfo();
	}
	public function fetch($mode=0) {
		if($this->ltype==self::$litetype[0]) {
		if($mode==1 || $mode==2) {
			switch($mode){
			case 1: $ty=SQLITE3_NUM; break;
			case 2: $ty=SQLITE3_ASSOC; break;
			}
			$res=[];
			while($row=$this->_query->fetchArray($ty)) {
			$res[]=$row;
			}
			return $res;
		} else {
			return $this->_query;
		}
		} else {
		if($mode==1 || $mode==2) {
			switch($mode){
			case 1: $this->_query->setFetchMode(PDO::FETCH_NUM); break;
			case 2: $this->_query->setFetchMode(PDO::FETCH_ASSOC); break;
			}
			return $this->_query->fetchAll();
		} else {
			return $this->_query->fetchColumn();
		}
		}
	}
	public function num_col() {
		if($this->ltype==self::$litetype[0]) return $this->_query->numColumns();
		else return $this->_query->columnCount();
	}
}
class ED {
	public $con,$dir,$ext=".sqlite",$sg,$path;
	protected $passwd='';
	public function __construct() {
		$this->dir=getcwd()."/";
		if(!file_exists($this->dir)) @mkdir($this->dir,0744);
		$pi=(isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO'));
		$this->sg=preg_split('!/!',$pi,-1,PREG_SPLIT_NO_EMPTY);
		$scheme='http'.(empty($_SERVER['HTTPS'])===true || $_SERVER['HTTPS']==='off' ? '' : 's').'://';
		$r_uri=isset($_SERVER['PATH_INFO'])===true ? $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
		$script=$_SERVER['SCRIPT_NAME'];
		$this->path=$scheme.$_SERVER['HTTP_HOST'].(strpos($r_uri,$script)===0 ? $script : rtrim(dirname($script),'/.\\')).'/';
	}
	public function sanitize($el) {
		return preg_replace(['/[^A-Za-z0-9]/'],'_',trim($el));
	}
	public function utf($fi) {
		if(function_exists("iconv") && preg_match("~^\xFE\xFF|^\xFF\xFE~",$fi)) {
		$fi=iconv("utf-16","utf-8",$fi);
		}
		return $fi;
	}
	public function form($furl,$enc='') {
		return "<form action='".$this->path.$furl."' method='post'".($enc==1 ? " enctype='multipart/form-data'":"").">";
	}
	public function fieldtype($slct='') {
		$fieldtype=['Numbers'=>["INTEGER","INT","DECIMAL"],'Strings'=>["VARCHAR","TEXT"],'DateTime'=>["DATE","DATETIME","TIME","TIMESTAMP"],'Binary'=>["BOOLEAN","BLOB"]];
		$ft='';
		foreach($fieldtype as $fdk=>$fdtype) {
		if(is_array($fdtype)) {
		$ft.="<optgroup label='$fdk'>";
		foreach($fdtype as $fdty) $ft.="<option value='$fdty'".(($slct!='' && $fdty==$slct)?" selected":"").">$fdty</option>";
		$ft.="</optgroup>";
		}
		}
		return $ft;
	}
	public function post($key='',$op='') {
		if($key==='' && !empty($_POST)) {
		return ($_SERVER['REQUEST_METHOD']==='POST' ? TRUE : FALSE);
		}
		if(!isset($_POST[$key])) return FALSE;
		if(is_array($_POST[$key])) {
		if(isset($op) && is_numeric($op)) {
		return $_POST[$key][$op];
		} else {
		$aout=[];
		foreach($_POST[$key] as $k=>$val) {
		if($val !='') $aout[$k]=$val;
		}
		}
		} else {
		$aout=$_POST[$key];
		}
		if($op=='i') return isset($aout);
		if($op=='e') return empty($aout);
		if($op=='!i') return !isset($aout);
		if($op=='!e') return !empty($aout);
		return $aout;
	}
	public function redir($way='',$msg=[]) {
		if(count($msg) > 0) {
		foreach($msg as $ks=>$ms) $_SESSION[$ks]=$ms;
		}
		header('Location: '.$this->path.$way);exit;
	}
	public function listdb() {
		$dbs=[];
		$dh=@opendir($this->dir);
		while(($dbe=readdir($dh)) !=false) {
		$dbext=pathinfo($dbe);
		if(@is_file($this->dir.$dbe) && !empty($dbext['extension']) && ".".$dbext['extension']==$this->ext) $dbs[]=$dbext['filename'];
		}
		closedir($dh);
		sort($dbs);
		return $dbs;
	}
	public function check($level=[],$param=[]) {
		if(!empty($_SESSION['ltoken'])) {
		if($_SESSION['ltoken'] !=base64_encode(md5($_SERVER['HTTP_USER_AGENT'].$this->passwd))) $this->redir("50",['err'=>"Wrong password"]);
		if(empty($_SERVER['HTTP_X_REQUESTED_WITH'])) session_regenerate_id(true);
		} else {
		$this->redir("50");
		}
		if(in_array(1,$level)) {//exist db
		$op=$this->sg[0];
		$db=$this->sg[1];
		$dbx=$this->dir.$db.$this->ext;
		if(!is_file($dbx)) $this->redir('',['err'=>"DB not exist"]);
		if(is_writable($this->dir) && $op!=3 && $op!=4) {
		$this->con=DBT::factory($dbx);
		$this->con->exec("PRAGMA default_synchronous=OFF");
		}
		}
		if(in_array(2,$level)) {//check table
		$tb=$this->sg[2];
		$obj=(($op==20) ? "(type='table' OR type='view')":"type='table'");
		$ist=$this->con->query("SELECT 1 FROM sqlite_master WHERE name='$tb' AND ".$obj,true)->fetch();
		if(!$ist) $this->redir("5/$db",['err'=>"Table not exist"]);
		}
		if(in_array(3,$level)) {//check field
		$q_fld=$this->con->query("PRAGMA table_info($tb)")->fetch(2);
		$meta=[];
		foreach($q_fld as $row) $meta[]=$row['name'];
		$err=['err'=>"Field not exist"];
		if(!in_array($this->sg[3],$meta)) $this->redir($param['redir']."/$db/$tb",$err);
		if(isset($this->sg[5])) {
		if(!in_array($this->sg[5],$meta)) $this->redir($param['redir']."/$db/$tb",$err);
		}
		}
		if(in_array(4,$level)) {//check pagination
		if(!is_numeric($param['pg']) || $param['pg'] > $param['total'] || $param['pg'] <1) $this->redir($param['redir'],['err'=>"Invalid page number"]);
		}
		if(in_array(5,$level)) {//check view,trigger
		$sp=['view','trigger'];
		$sg3=$this->sg[3];
		if($op!=49 && ($op==40 && $sg3!=$sp[0]) || ($op==41 && $sg3!=$sp[1])) $this->redir("5/$db");
		$q_sp=$this->con->query("SELECT 1 FROM sqlite_master WHERE name='".$this->sg[2]."' AND type='$sg3'",true)->fetch();
		if(!$q_sp) $this->redir("5/$db",['err'=>"Not available object"]);
		}
	}
	public function menu($db='',$tb='',$left='',$sp=[]) {
		$str="";
		if($db==1 || $db!='') $str.="<div class='l2'><ul><li><a href='{$this->path}'>Databases</a></li>";
		if($db!='' && $db!=1) $str.="<li><a href='{$this->path}31/$db'>Export</a></li><li><a href='{$this->path}5/$db'>Tables</a></li>";
		$dv="<li class='divider'>---</li>";
		if($tb!="") $str.=$dv."<li><a href='{$this->path}10/$db/$tb'>Structure</a></li><li><a href='{$this->path}20/$db/$tb'>Browse</a></li><li><a href='{$this->path}21/$db/$tb'>Insert</a></li><li><a href='{$this->path}24/$db/$tb'>Search</a></li><li><a class='del' href='{$this->path}25/$db/$tb'>Empty</a></li><li><a class='del' href='{$this->path}26/$db/$tb'>Drop</a></li>";//table
		if(!empty($sp[1]) && $sp[0]=='view') $str.=$dv."<li><a href='{$this->path}40/$db/".$sp[1]."/view'>Structure</a></li><li><a href='{$this->path}20/$db/".$sp[1]."'>Browse</a></li><li><a class='del' href='{$this->path}49/$db/".$sp[1]."/view'>Drop</a></li>";//view
		if($db!='') $str.="</ul></div>";

		if($db!="" && $db!=1) {
		$str.="<div class='l3 auto'><select onchange='location=this.value;'><optgroup label='databases'>";
		foreach($this->listdb() as $udb) $str.="<option value='{$this->path}5/$udb'".($udb==$db?" selected":"").">$udb</option>";
		$str.="</optgroup></select>";
		$q_ts=[]; $c_sp=!empty($sp) ? count($sp):"";
		if($tb!="" || $c_sp >1) {
		$q_ts=$this->con->query("SELECT name,type FROM sqlite_master WHERE type IN ('table','view','trigger') ORDER BY type,name")->fetch(1);
		$sl2="<select onchange='location=this.value;'>";
		$qtype='';
		foreach($q_ts as $r_ts) {
		if($qtype !=$r_ts[1]) {
		if($qtype !='') $sl2.='</optgroup>';
		$sl2.='<optgroup label="'.$r_ts[1].'s">';
		}
		$sl2.="<option value='".$this->path.($r_ts[1]=='trigger'?"41/$db/".$r_ts[0]."/".$r_ts[1]:"20/$db/".$r_ts[0])."'".($r_ts[0]==$tb || ($c_sp >1 && $r_ts[0]==$sp[1])?" selected":"").">".$r_ts[0]."</option>";
		$qtype=$r_ts[1];
		}
		if($qtype !='') $sl2.='</optgroup>';
		$str.=$sl2."</select>".((!empty($_SESSION["_litesearch_{$db}_{$tb}"]) && $this->sg[0]==20) ? " [<a href='{$this->path}24/$db/$tb/reset'>reset search</a>]":"");
		}
		$str.="</div>";
		}

		$str.="<div class='container'>";
		if($left==2) $str.="<div class='col3'>";
		$f=1;$nrf_op='';
		while($f<50) {
		$nrf_op.="<option value='$f'>$f</option>";
		++$f;
		}
		if($left==1) $str.="<div class='col1'><h3>Run sql</h3>
		".$this->form("30/$db")."<textarea name='qtxt'></textarea><br/><button type='submit'>Run</button></form>
		<h3>Import</h3><small>sql, csv, json, xml, ".substr($this->ext,1).", gz, zip</small>".$this->form("30/$db",1)."<input type='file' name='importfile' />
		<input type='hidden' name='send' value='ff' /><br/><button type='submit'>Upload (&lt;".ini_get("upload_max_filesize")."B)</button></form>
		<h3>Create Table</h3>".$this->form("6/$db")."<input type='text' name='ctab' /><br/>Number of fields<br/><select name='nrf'>$nrf_op</select><br/><button type='submit'>Create</button></form>
		<h3>Rename DB</h3>".$this->form("3/$db")."<input type='text' name='rdb' /><br/><button type='submit'>Rename</button></form>
		<h3>Create</h3><a href='{$this->path}40/$db'>View</a><a href='{$this->path}41/$db'>Trigger</a>
		</div><div class='col2'>";
		return $str;
	}
	public function pg_number($pg,$totalpg) {
		if($totalpg > 1) {
		if($this->sg[0]==20) $link=$this->path."20/".$this->sg[1]."/".$this->sg[2];
		elseif($this->sg[0]==5) $link=$this->path."5/".$this->sg[1];
		$pgs='';$k=1;
		while($k <=$totalpg) {
		$pgs.="<option ".(($k==$pg) ? "selected='selected'>":"value='$link/$k'>")."$k</option>";
		++$k;
		}
		$lft=($pg>1?"<a href='$link/1'>First</a><a href='$link/".($pg-1)."'>Prev</a>":"");
		$rgt=($pg < $totalpg?"<a href='$link/".($pg+1)."'>Next</a><a href='$link/$totalpg'>Last</a>":"");
		return "<div class='pg'>".$lft."<select onchange='location=this.value;'>$pgs</select>".$rgt."</div>";
		}
	}
	public function imp_csv($fname,$fbody) {
		$exist=$this->con->query("SELECT 1 FROM sqlite_master WHERE name='$fname' AND type='table'",true)->fetch();
		if(!$exist) $this->redir("5/".$this->sg[1],['err'=>"Table not exist"]);
		$fname=$this->sanitize($fname);
		$e=[];
		if(@is_file($fbody)) $fbody=file_get_contents($fbody);
		$fbody=$this->utf($fbody);
		$fbody=preg_replace('/^\xEF\xBB\xBF|^\xFE\xFF|^\xFF\xFE/','',$fbody);
		//delimiter
		$delims=[';'=> 0,','=> 0];
		foreach($delims as $dl=> &$cnt) $cnt=count(str_getcsv($fbody,$dl));
		$mark=array_search(max($delims),$delims);
		//data
		$data=explode("\n",str_replace(["\r\n","\n\r","\r"],"\n",$fbody));
		$row=null;
		foreach($data as $item) {
			$row.=$item;
			if(trim($row)==='') {
			$row=null;
			continue;
			} elseif (substr_count($row,'"') % 2 !==0) {
			$row.=PHP_EOL;
			continue;
			}
			$rows[]=str_getcsv($row,$mark,'"','"');
			$row=null;
		}
		foreach($rows as $k=>$rw) {
		if($k>0) {
		$e1="INSERT INTO $fname (".implode(',',$rows[0]).") VALUES(";
		foreach($rw as $r) $e1.=(is_numeric($r)?$r:"'".str_replace("'","''",$r)."'").',';
		$e[]=substr($e1,0,-1).");";
		}
		}
		if(empty($e)) $this->redir("5/".$this->sg[1],['err'=>"Query failed"]);
		return $e;
	}
	public function imp_json($fname,$fbody) {
		$exist=$this->con->query("SELECT 1 FROM sqlite_master WHERE name='$fname' AND type='table'",true)->fetch();
		if(!$exist) $this->redir("5/".$this->sg[1],['err'=>"Table not exist"]);
		$e=[];
		if(@is_file($fbody)) $fbody=file_get_contents($fbody);
		$fbody=$this->utf($fbody);
		$rgxj="~^\xEF\xBB\xBF|^\xFE\xFF|^\xFF\xFE|(\/\/).*\n*|(\/\*)*.*(\*\/)\n*|((\"*.*\")*('*.*'))(*SKIP)(*F)~";
		$ex=preg_split($rgxj,$fbody,-1,PREG_SPLIT_NO_EMPTY);
		$lines=json_decode($ex[0],true);
		$jr='';
		foreach($lines[0] as $k=>$li) $jr.=$k.",";
		foreach($lines as $line) {
		$jv='';
		foreach($line as $ky=>$el) $jv.=(is_numeric($el)?$el:"'".$el."'").",";
		$e[]="INSERT INTO $fname (".substr($jr,0,-1).") VALUES (".substr($jv,0,-1).")";
		}
		return $e;
	}
	public function imp_xml($fname,$fbody) {
		$e=[];
		if(@is_file($fbody)) $fbody=file_get_contents($fbody);
		$fbody=$this->utf($fbody);
		if(!function_exists('libxml_disable_entity_loader')) return;
		libxml_disable_entity_loader();
		$xml=simplexml_load_string($fbody,"SimpleXMLElement",LIBXML_COMPACT);
		$nspace=$xml->getNameSpaces(true);
		$ns=key($nspace);
		//structure
		$sq=[];
		if(isset($nspace[$ns]) && isset($xml->children($nspace[$ns])->{'structure_schemas'}->{'database'}->{'table'})) {
			$stru=$xml->children($nspace[$ns])->{'structure_schemas'}->{'database'}->{'table'};
			foreach($stru as $st) $sq[]=explode(";",str_replace("\t\t\t","",(string)$st));
		}
		$sq=(empty($sq) ? $sq : call_user_func_array('array_merge',$sq));
		//data
		$data=$xml->xpath('//database/table');
		foreach($data as $dt) {
			$tt=$dt->attributes();
			$co='';$va='';
			foreach($dt as $dt2) {
			$tv=$dt2->attributes();
			$co.=(string)$tv['name'].",";
			$va.="'".$dt2."',";
			}
			if($co!='' && $va!='') $e[]="INSERT INTO '".(string)$tt['name']."'(".substr($co,0,-1).") VALUES(".substr($va,0,-1).");";
		}
		return array_merge($sq,$e);
	}
	public function imp_sqlite($fname,$fbody) {
		if($fbody!='') {
		if(substr(file_get_contents($fbody),0,15) !="SQLite format 3" && substr($fbody,0,15) !="SQLite format 3") $this->redir('',['err'=>"No SQLite file"]);
		$file=pathinfo($fname);
		$new=$this->dir.$this->sanitize($file['filename']).$this->ext;
		if(is_uploaded_file($fbody)) {
			if(move_uploaded_file($fbody,$new)) {
			$this->redir('',['ok'=>"SQLite file uploaded"]);
			}
		} else {
			$sfile=fopen($new,"wb");
			if(!$sfile) $this->redir('',['err'=>"Unable to create sqlite file"]);
			fwrite($sfile,$fbody);
			fclose($sfile);
			$this->redir('',['ok'=>"SQLite file uploaded"]);
		}
		}
		$this->redir('',['err'=>"No upload"]);
	}
	public function tb_structure($tb,$fopt,$tab='') {
		$val="\n";
		if(in_array('drop',$fopt)) {//check option drop
		$val.=$tab."DROP TABLE IF EXISTS $tb;\n";
		}
		$q_tbst=$this->con->query("SELECT sql FROM sqlite_master WHERE name='$tb'",true)->fetch().";";
		if(in_array('ifnot',$fopt)) {//check option if not
		$q_tbst=preg_replace('~(CREATE\sTABLE\s)(.*)~i','${1}IF NOT EXISTS ${2}',$q_tbst);
		}
		$val.=$tab.str_replace("\n","\n".$tab,$q_tbst);
		$q_tidx=$this->con->query("SELECT sql FROM sqlite_master WHERE tbl_name='$tb' AND type='index'");
		$cidx=$q_tidx->num_col();
		if($cidx > 0) {
		foreach($q_tidx->fetch(1) as $r_ix) {
		if($r_ix[0]) $val.="\n".$tab.$r_ix[0].";";
		}
		$val.="\n";
		}
		return $val;
	}
}
$ed=new ED;
$head='<!DOCTYPE html><html lang="en"><head>
<title>EdLiteAdmin</title><meta charset="utf-8">
<style>
*{margin:0;padding:0;font-size:12px;color:#333;font-family:Arial}
html{-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%;background:#fff}
html,textarea{overflow:auto}
.container{overflow:auto;overflow-y:hidden;-ms-overflow-y:hidden;white-space:nowrap}
[hidden],.mn ul{display:none}
.m1{position:absolute;right:0;top:0}
.mn li:hover ul{display:block;position:absolute}
.ce{text-align:center}
.link{float:right;padding:3px 0}
.pg *{padding:0 2px;width:auto}
caption{font-weight:bold;text-decoration:underline}
.l1 ul,.l2 ul{list-style:none}
.left{float:left}
.left button{margin:0 1px}
h3{margin:2px 0 1px;padding:2px 0}
a{color:#842;text-decoration:none}
a:hover{text-decoration:underline}
a,a:active,a:hover{outline:0}
table a,.l1 a,.l2 a,.col1 a{padding:0 3px}
table{border-collapse:collapse;border-spacing:0;border-bottom:1px solid #555}
td,th{padding:4px;vertical-align:top}
input[type=checkbox],input[type=radio]{position:relative;vertical-align:middle;bottom:1px}
input[type=text],input[type=password],input[type=file],textarea,button,select{width:100%;padding:2px;border:1px solid #9be;outline:none;-webkit-border-radius:3px;-moz-border-radius:3px;border-radius:3px;-webkit-box-sizing:border-box;-moz-box-sizing:border-box;box-sizing:border-box}
input[type=text],select{min-width:98px !important}
select{padding:1px 0}
optgroup option{padding-left:8px}
textarea,.he{min-height:90px}
textarea{white-space:pre-wrap}
.msg{position:absolute;top:0;right:0;z-index:9}
.ok,.err{padding:8px;font-weight:bold;font-size:13px}
.ok{background:#efe;color:#080;border-bottom:2px solid #080}
.err{background:#fee;color:#f00;border-bottom:2px solid #f00}
.l1,th,caption,button{background:#9be}
.l2,.c1,.col1,h3{background:#cdf}
.c2,.mn ul{background:#fff}
.l3,tr:hover.r,button:hover{background:#fe3 !important}
.ok,.err,.l2 li,.mn>li{display:inline-block;zoom:1}
.col1,.col2{display:table-cell}
.col1{vertical-align:top;padding:0 3px;min-width:180px}
.col1,.dw{width:180px}
.col2 table{margin:3px}
.col3 table,.dw{margin:3px auto}
.auto button,.auto input,.auto select{width:auto}
.l3.auto select{border:0;padding:0;background:#fe3}
.sort tbody tr{cursor:default;position:relative}
.handle{font:18px/12px Arial;vertical-align:middle}
.handle:hover{cursor:move}
.opacity{opacity:0.7}
.drag{opacity:1;top:3px;left:0}
.l1,.l2,.l3{width:100%}
.msg,.a{cursor:pointer}
</style>
</head><body><noscript><h1 class="msg err">Please enable the javascript in your browser</h1></noscript>'.(empty($_SESSION['ok'])?'':'<div class="msg ok">'.$_SESSION['ok'].'</div>').(empty($_SESSION['err'])?'':'<div class="msg err">'.$_SESSION['err'].'</div>').'<div class="l1"><b><a href="https://github.com/edmondsql/edliteadmin">EdLiteAdmin '.$version.'</a></b>'.(isset($ed->sg[0]) && $ed->sg[0]==50 ? "":'<ul class="mn m1"><li>More <small>&#9660;</small><ul><li><a href="'.$ed->path.'60">Info</a></li></ul></li><li><a href="'.$ed->path.'51">Logout</a></li></ul>').'</div>';
$stru="<table><caption>TABLE STRUCTURE</caption><tr><th>FIELD</th><th>TYPE</th><th>VALUE</th><th>NULL</th><th>DEFAULT</th></tr>";

if(!isset($ed->sg[0])) $ed->sg[0]=0;
switch($ed->sg[0]) {
default:
case ""://show DBs
	$ed->check();
	echo $head.$ed->menu()."<div class='col1'>Create Database".$ed->form(2)."<input type='text' name='dbc' /><br/><button type='submit'>Create</button></form></div><div class='col2'><table><tr><th>DATABASE</th><th>Tables</th><th>Actions</th></tr>";
	foreach($ed->listdb() as $db) {
		$bg=($bg==1)?2:1;
		$dbx=new DBT($ed->dir.$db.$ed->ext);
		$qs_nr=$dbx->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' or type='view'",true)->fetch();
		echo "<tr class='r c$bg'><td>$db</td><td>$qs_nr</td><td><a href='{$ed->path}31/$db'>Exp</a><a class='del' href='{$ed->path}4/$db'>Drop</a><a href='{$ed->path}5/$db'>Browse</a></td></tr>";
		$dbx=NULL;
	}
	echo "</table>";
break;

case "2"://create db
	$ed->check();
	if($ed->post('dbc','!e')) {
	$db=$ed->sanitize($ed->post('dbc'));
	if(@is_file($ed->dir.$db.$ed->ext)) $ed->redir("",['err'=>"DB already exist"]);
	$ed->con=DBT::factory($ed->dir.$db.$ed->ext);
	if(@is_file($ed->dir.$db.$ed->ext)) $ed->redir("",['ok'=>"Created DB"]);
	else $ed->redir("",['err'=>"Create DB failed"]);
	}
	$ed->redir("",['err'=>"DB name must not be empty"]);
break;

case "3"://rename db
	$ed->check([1]);
	$db=$ed->sg[1];
	if($ed->post('rdb','!e') && $ed->sanitize($ed->post('rdb')) !=$db) {
	$ndb=$ed->sanitize($ed->post('rdb'));
	rename($ed->dir.$db.$ed->ext,$ed->dir.$ndb.$ed->ext);
	$ed->redir("",['ok'=>"Successfully renamed"]);
	} else $ed->redir("5/$db",['err'=>"DB name must not be empty"]);
break;

case "4"://delete db
	$ed->check([1]);
	$db=$ed->sg[1];
	if(@is_file($ed->dir.$db.$ed->ext)) {
	$fl=$ed->dir.$db.$ed->ext;
	chmod($fl,0664);
	@unlink($fl);
	$ed->redir("",['ok'=>"Successfully deleted"]);
	} else $ed->redir("",['err'=>"Missing DB"]);
break;

case "5"://show tables
	$ed->check([1]);
	$db=$ed->sg[1];
	$all=$ed->con->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' OR type='view'",true)->fetch();
	$totalpg=ceil($all/$step);
	if(empty($ed->sg[2])) {
		$pg=1;
	} else {
		$pg=$ed->sg[2];
		$ed->check([4],['pg'=>$pg,'total'=>$totalpg,'redir'=>"5/$db"]);
	}
	$offset=($pg - 1) * $step;
	echo $head.$ed->menu($db,'',1);
	echo "<table><tr><th>TABLE/VIEW</th><th>ROWS</th><th>ACTIONS</th></tr>";
	$q_tabs=$ed->con->query("SELECT name,type FROM sqlite_master WHERE type IN ('table','view') ORDER BY type,name LIMIT $offset,$step")->fetch(1);
	foreach($q_tabs as $r_tabs) {
		if(!in_array($r_tabs[0],$deny)) {
		$q_num=($r_tabs[1] !="view" ? $ed->con->query("SELECT COUNT(*) FROM ".$r_tabs[0],true)->fetch() : $r_tabs[1]);
		$bg=($bg==1)?2:1;
		$vl="/$db/".$r_tabs[0];
		if($r_tabs[1]=="view") {
		$lnk="40{$vl}/view"; $vdel="49{$vl}/view";
		} else {
		$lnk="10{$vl}"; $vdel="26{$vl}";
		}
		echo "<tr class='r c$bg'><td>".$r_tabs[0]."</td><td>$q_num</td><td><a href='{$ed->path}{$lnk}'>Structure</a><a class='del' href='{$ed->path}{$vdel}'>Drop</a><a href='{$ed->path}20/$db/".$r_tabs[0]."'>Browse</a></td></tr>";
		}
	}
	echo "</table>";
	$q_tri=$ed->con->query("SELECT name,tbl_name FROM sqlite_master WHERE type='trigger' ORDER BY name")->fetch(1);
	$t=0;
	$trg_tab="<table><tr><th>TRIGGER</th><th>TABLE</th><th>ACTIONS</th></tr>";
	foreach($q_tri as $r_tri) {
	$bg=($bg==1)?2:1;
	$trg_tab.="<tr class='r c$bg'><td>".$r_tri[0]."</td><td>".$r_tri[1]."</td><td><a href='{$ed->path}41/$db/".$r_tri[0]."/trigger'>Edit</a><a class='del' href='{$ed->path}49/$db/".$r_tri[0]."/trigger'>Drop</a></td></tr>";
	++$t;
	}
	echo ($t>0 ? $trg_tab."</table>":"").$ed->pg_number($pg,$totalpg);
break;

case "6"://create table
	$ed->check([1]);
	$db=$ed->sg[1];
	if($ed->post('ctab','!e') && !is_numeric(substr($ed->post('ctab'),0,1)) && $ed->post('nrf','!e') && $ed->post('nrf')>0 && is_numeric($ed->post('nrf'))) {
	$q_ch=$ed->con->query("SELECT 1 FROM sqlite_master WHERE name='".$ed->sanitize($ed->post('ctab'))."'",true)->fetch();
	if($q_ch) $ed->redir("5/$db",['err'=>"Name already exists"]);
		echo $head.$ed->menu($db,'',2);
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
		$q2="CREATE TABLE ".$ed->sanitize($ed->post('ctab'))."(".substr($q1,0,-1).");";
		echo "<p>".(strlen($q1) > 5 && $ed->con->query($q2) ? "<b>OK!</b> $q2<br/>":"<b>FAILED!</b> $q2")."</p>";
		} else {
		echo $ed->form("6/$db")."<input type='hidden' name='ctab' value='".$ed->post('ctab')."'/>
		<input type='hidden' name='nrf' value='".$ed->post('nrf')."'/>$stru";
		$nr=$ed->post('nrf');
		$i=0;
		while($i<$nr) {
		echo "<tr><td><input type=text name='fi$i' /></td><td><select name='ty$i'>".$ed->fieldtype()."</select></td><td><input type=text name='vl$i' /></td><td><select name='nl$i'><option value='0'>Yes</option><option value='1'>No</option></select></td><td><input type=text name='df$i' /></td></tr>";
		++$i;
		}
		echo "<tr><td colspan='5'><button type='submit' name='crtb'>Create table</button></td></tr></table></form>";
		}
	} else {
		$ed->redir("5/$db",['err'=>"Table name must not be empty"]);
	}
break;

case "9":
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	if($ed->post('copytab','!e')) {//copy table
		$cpy=$ed->post('copytab');
		$ncpy=basename($cpy,$ed->ext);
		$q_tc=$ed->con->query("SELECT sql FROM sqlite_master WHERE name='$tb'",true)->fetch();
		$r_sql=preg_split("/\([^()]*\)(*SKIP)(*F)|[()]/",$q_tc,-1,PREG_SPLIT_NO_EMPTY);
		$ed->con->exec("ATTACH DATABASE '".$ed->dir.$cpy.$ed->ext."' AS $ncpy");
		$q_cc=$ed->con->exec("SELECT 1 FROM {$ncpy}.{$tb}");
		if($q_cc) $ed->redir("10/$db/$tb",['err'=>"Table already exists"]);
		$ed->con->exec("CREATE TABLE {$ncpy}.{$tb} (".$r_sql[1].");");
		$ed->con->exec("INSERT INTO {$ncpy}.{$tb} SELECT * FROM $tb");
		$ed->con->exec("DETACH DATABASE $ncpy");
		$ed->redir("10/$db/$tb",['ok'=>"Successfully copied"]);
	}
	if($ed->post('rtab','!e')) {//rename table
		$new=$ed->sanitize($ed->post('rtab'));
		$ren_tb=$ed->con->exec("ALTER TABLE $tb RENAME TO $new");
		if($ren_tb===false) $ed->redir("10/$db/$tb",array('err'=>"Can't rename"));
		$ed->redir("5/$db",array('ok'=>"Successfully renamed"));
	}
	if($ed->post('reord','!e')) {//reorder
		$q_fd=$ed->con->query("PRAGMA table_info($tb)")->fetch(1);
		$q_fk=$ed->con->query("PRAGMA foreign_key_list($tb)")->fetch(2);
		$post=$ed->post('reord');
		$els=explode(",",$post);
		$s_fd=[];$n_fd=[];$s_pk=[];$n_pk=[];$fk='';
		foreach($q_fd as $r_fd) {
		$s_fd[$r_fd[1]]=$r_fd[1].' '.$r_fd[2].($r_fd[3]==1 ? ' NOT NULL':'').(empty($r_fd[4]) ? '':' DEFAULT '.$r_fd[4]);
		if($r_fd[5]>0) $s_pk[]=$r_fd[1];
		}
		foreach($q_fk as $r_fk) {
		$fk.="FOREIGN KEY ({$r_fk['from']}) REFERENCES {$r_fk['table']} ({$r_fk['to']})".(empty($r_fk['on_delete'])?"":" ON DELETE ".$r_fk['on_delete']).(empty($r_fk['on_update'])?"":" ON UPDATE ".$r_fk['on_update']).",";
		}
		foreach($els as $k=>$el) {
		$n_fd[$k]=$s_fd[$el];
		if(in_array($el,$s_pk)) $n_pk[]=$el;
		}
		$q_it=$ed->con->query("SELECT sql FROM sqlite_master WHERE tbl_name='$tb' AND type='index' OR type='trigger'")->fetch(1);
		$n_fd=implode(',',$n_fd);
		$qr=(empty($s_pk) ? "":", PRIMARY KEY (".implode(',',$n_pk)."),").$fk;
		$r_qs=["BEGIN TRANSACTION"];
		$r_qs[]="ALTER TABLE $tb RENAME TO temp_$tb";
		$r_qs[]="CREATE TABLE $tb (".$n_fd.substr($qr,0,-1).")";
		$r_qs[]="INSERT INTO $tb ($post) SELECT $post FROM temp_$tb";
		$r_qs[]="DROP TABLE temp_$tb";
		foreach($q_it as $r_it) {
		if($r_it[0]) $r_qs[]=$r_it[0];
		}
		$r_qs[]="COMMIT";
		foreach($r_qs as $r_q) $ed->con->exec($r_q);
		exit;
	}
	if($ed->post('idx','!e') && is_array($ed->post('idx'))) {//create index
		$idx=implode(',',$ed->post('idx'));
		$idn=implode('_',$ed->post('idx'));
		if($ed->post('primary','i')) {
			$q_pr=$ed->con->query("PRAGMA table_info($tb)")->fetch(1);
			$r_sql='';$f='';
			foreach($q_pr as $r_pr) {
			$r_sql.=$r_pr[1]." ".$r_pr[2].($r_pr[3]>0 ? " NOT NULL":"").($r_pr[4]!='' ? " DEFAULT ".$r_pr[4]:"").",";
			$f.=$r_pr[1].",";
			}
			$f=substr($f,0,-1);
			$sqs=$ed->con->query("SELECT sql FROM sqlite_master WHERE tbl_name='$tb' AND type IN ('index','trigger')")->fetch(1);
			$ed->con->exec("BEGIN TRANSACTION");
			$ed->con->exec("ALTER TABLE $tb RENAME TO temp_$tb");
			$ed->con->exec("CREATE TABLE $tb ($r_sql PRIMARY KEY($idx))");
			$ed->con->exec("INSERT INTO $tb SELECT $f FROM temp_$tb");
			$ed->con->exec("DROP TABLE temp_$tb");
			foreach($sqs as $sq) $ed->con->exec($sq[0]);
			$ed->con->exec("COMMIT");
		} elseif($ed->post('unique','i')) {
			$ed->con->exec("CREATE UNIQUE INDEX UNI__$idn ON $tb($idx)");
		} elseif($ed->post('index','i')) {
			$ed->con->exec("CREATE INDEX IDX__$idn ON $tb($idx)");
		}
		$ed->con->exec("VACUUM");
		$ed->redir("10/$db/$tb",['ok'=>"Successfully created"]);
	}
	if(!empty($ed->sg[3])) {//drop index
		$s_idx=base64_decode($ed->sg[3]);
		$ed->con->exec("PRAGMA foreign_keys=off");
		$q_ii=$ed->con->exec("DROP INDEX $s_idx");
		if($q_ii===false) $ed->redir("10/$db/$tb",['err'=>"Can't drop index"]);
		else $ed->redir("10/$db/$tb",['ok'=>"Successfully dropped"]);
	}
	$ed->redir("10/$db/$tb",['err'=>"Wrong action"]);
break;

case "10"://table structure
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	echo $head.$ed->menu($db,$tb,1).$ed->form("9/$db/$tb")."<table><caption>TABLE STRUCTURE</caption><thead><tr><th><input type='checkbox' onclick='toggle(this,\"idx[]\")' /></th><th>FIELD</th><th>TYPE</th><th>NULL</th><th>DEFAULT</th><th>PK</th><th>ACTIONS</th></tr></thead><tbody class='sort'>";
	$q_rec=$ed->con->query("PRAGMA table_info($tb)")->fetch(1);
	foreach($q_rec as $rec) {
		$bg=($bg==1)?2:1;
		echo "<tr class='r c$bg' id='".$rec[1]."'><td><input type='checkbox' name='idx[]' value='".$rec[1]."' /></td><td>".$rec[1]."</td><td>".$rec[2]."</td><td>".($rec[3]==0 ? 'Yes':'No')."</td><td>".$rec[4]."</td><td>".($rec[5]>0 ? 'PK':'')."</td><td><a href='{$ed->path}12/$db/$tb/".$rec[1]."'>change</a><a class='del' href='{$ed->path}13/$db/$tb/".$rec[1]."'>drop</a><a href='{$ed->path}11/$db/$tb/'>add</a><span class='handle' title='move'>&#10070;</span></td></tr>";
	}
	echo "</tbody><tfoot><tr><td class='auto' colspan='7'><div class='left'><button type='submit' name='primary'>Primary</button><button type='submit' name='index'>Index</button><button type='submit' name='unique'>Unique</button></div><div class='link'><a href='{$ed->path}27/$db/$tb/analyze'>Analyze</a></div></td></tr></tfoot></table></form>";
	$q_idx=$ed->con->query("PRAGMA index_list($tb)")->fetch(1);
	echo "<table><caption>INDEX</caption><tr><th>NAME</th><th>FIELD</th><th>UNIQUE</th><th>ACTIONS</th></tr>";
	foreach($q_idx as $rc) {
		$bg=($bg==1)?2:1;
		echo "<tr class='r c$bg'><td>".$rc[1]."</td><td>";
		$q=$ed->con->query("PRAGMA index_info('".$rc[1]."')")->fetch(1);
		foreach($q as $rd) echo $rd[2]."<br/>";
		echo "</td><td>".($rc[2]==1 ? 'YES':'NO')."</td><td><a class='del' href='{$ed->path}9/$db/$tb/".base64_encode($rc[1])."'>drop</a></td></tr>";
	}
	$q_fkl=$ed->con->query("PRAGMA foreign_key_list($tb)")->fetch(2);
	echo "</table><table><caption>FOREIGN KEYS</caption><tr><th>FIELD</th><th>TARGET</th><th>ON DELETE</th><th>ON UPDATE</th><th>ACTIONS <a href='{$ed->path}14/$db/$tb'>add</a></th></tr>";
	foreach($q_fkl as $r_fkl) {
		$bg=($bg==1)?2:1;
		echo "<tr class='r c$bg'><td>".$r_fkl['from']."</td><td>".$r_fkl['table'].".".$r_fkl['to']."</td><td>".$r_fkl['on_delete']."</td><td>".$r_fkl['on_update']."</td><td><a href='{$ed->path}14/$db/$tb/{$r_fkl['id']}'>change</a><a class='del' href='{$ed->path}14/$db/$tb/{$r_fkl['id']}/fk'>drop</a></td></tr>";
	}
	echo "</table><table class='c1'><tr><td>Rename Table<br/>".$ed->form("9/$db/$tb")."<input type='text' name='rtab' /><br/><button type='submit'>Rename</button></form><br/>Copy Table<br/>".$ed->form("9/$db/$tb")."<select name='copytab'>";
	foreach($ed->listdb() as $dbl) echo "<option value='$dbl'>$dbl</option>";
	echo "</select><br/><button type='submit'>Copy</button></form></td></tr></table>";
break;

case "11"://add field
	$ed->check([1,2],['redir'=>10]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	if($ed->post('add','i')) {
		$f1=$ed->sanitize($ed->post('f1'));
		if(!empty($f1)) {
		$e=$ed->con->query("ALTER TABLE $tb ADD COLUMN $f1 ".$ed->post('f2').($ed->post('f3','!e')?"(".$ed->post('f3').")":"").($ed->post('f4')==1 ? " NOT NULL":"").($ed->post('f5')!='' ? " DEFAULT '".$ed->post('f5')."'":""));
		} else $ed->redir("11/$db/$tb",['err'=>"Empty field name"]);
		if($e) $ed->redir("10/$db/$tb",['ok'=>"Successfully added"]);
		else $ed->redir("10/$db/$tb",['err'=>"Can't add this field"]);
	} else {
		echo $head.$ed->menu($db,$tb,2).$ed->form("11/$db/$tb").$stru."<tr><td><input type='text' name='f1' /></td><td><select name='f2'>".$ed->fieldtype()."</select></td><td><input type='text' name='f3' /></td><td><select name='f4'><option value='0'>Yes</option><option value='1'>No</option></select></td><td><input type='text' name='f5' /></td></tr><tr><td colspan='5'><button type='submit' name='add'>Add field</button></td></tr></table></form>";
	}
break;

case "12"://change field
	$ed->check([1,2,3],['redir'=>10]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$fn1=$ed->sg[3];
	$q_t=$ed->con->query("PRAGMA table_info($tb)")->fetch(1);
	if($ed->post('change','i')) {
		$qr='';$pk='';$fk='';$re='';
		$fn2=$ed->sanitize($ed->post("cf1"));
		$q_fk=$ed->con->query("PRAGMA foreign_key_list($tb)")->fetch(2);
		foreach($q_fk as $r_fk) {
		$fkr=$r_fk['from']==$fn1 ? $fn2:$r_fk['from'];
		$fk.="FOREIGN KEY ($fkr) REFERENCES ".$r_fk['table']." (".$r_fk['to'].")".(empty($r_fk['on_delete'])?"":" ON DELETE ".$r_fk['on_delete']).(empty($r_fk['on_update'])?"":" ON UPDATE ".$r_fk['on_update']).",";
		}
		foreach($q_t as $e) {
		if($e[1]==$fn1){
			if(empty($fn2) || is_numeric(substr($fn2,0,1))) $ed->redir("10/$db/$tb",['err'=>"Not a valid field name"]);
			$qr.=$fn2." ".$ed->post('cf2').($ed->post('cf3','!e')?"(".$ed->post('cf3').")":"").($ed->post('cf4')==1 ? " NOT NULL":"").($ed->post("cf5","e")?"":" DEFAULT '".$ed->post("cf5")."'").",";
		} else {
			$re.=$e[1].",";
			$qr.=$e[1]." ".$e[2].($e[3]!=0 ? " NOT NULL":"").($e[4]!='' ? " DEFAULT ".$e[4]:"").",";
		}
		if($e[5]>0) {
		$pk.=($e[1]==$fn1 ? $fn2:$e[1]).",";
		}
		}
		$qr.=(empty($pk) ? "":" PRIMARY KEY(".substr($pk,0,-1)."),").$fk;
		$ed->con->exec("BEGIN TRANSACTION");
		$q_it=$ed->con->query("SELECT type,name,sql FROM sqlite_master WHERE type IN ('index','view','trigger')")->fetch(1);
		foreach($q_it as $r_it) $ed->con->exec("DROP ".$r_it[0]." ".$r_it[1]);
		$ed->con->exec("ALTER TABLE $tb RENAME TO temp_$tb");
		$ed->con->exec("CREATE TABLE $tb(".substr($qr,0,-1).")");
		$ed->con->exec("INSERT INTO $tb(".$re.$fn2.") SELECT ".$re.$fn1." FROM temp_$tb");
		$ed->con->exec("DROP TABLE temp_$tb");
		foreach($q_it as $r_it) $ed->con->exec($r_it[2]);
		$ed->con->exec("COMMIT");
		$ed->redir("10/$db/$tb",['ok'=>"Successfully changed"]);
	} else {
		echo $head.$ed->menu($db,$tb,2).$ed->form("12/$db/$tb/$fn1").$stru;
		foreach($q_t as $d) {
		if($d[1]==$fn1){
		$d_val=preg_split("/[()]+/",$d[2],-1,PREG_SPLIT_NO_EMPTY);
		echo "<tr><td><input type='text' name='cf1' value='".$d[1]."' /></td><td><select name='cf2'>".$ed->fieldtype(strtoupper($d_val[0]))."</select></td><td><input type='text' name='cf3' value='".(isset($d_val[1])?$d_val[1]:"")."' /></td><td><select name='cf4'><option value='0'>Yes</option><option value='1'".($d[3]!=0 ? " selected":"").">No</option></select></td><td><input type='text' name='cf5' value='".($d[4]==''?'':str_replace("'","",$d[4]))."' /></td></tr>";
		}
		}
		echo "<tr><td colspan='5'><button type='submit' name='change'>Change field</button></td></tr></table></form>";
	}
break;

case "13"://drop field
	$ed->check([1,2,3],['redir'=>10]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$fn=$ed->sg[3];
	$obj=[];
	$ed->con->exec("BEGIN TRANSACTION");
	$q_it=$ed->con->query("SELECT type,name,sql FROM sqlite_master WHERE type IN ('index','view','trigger')")->fetch(1);
	foreach($q_it as $r_it) $ed->con->exec("DROP ".$r_it[0]." ".$r_it[1]);
	$q_f=$ed->con->query("PRAGMA table_info($tb)")->fetch(1);
	$qr='';$pk='';$fk='';$re='';
	foreach($q_f as $r_f) {
	if($r_f[1]!=$fn){
		$qr.=$r_f[1]." ".$r_f[2].($r_f[3]!=0 ? " NOT NULL":"").($r_f[4]!='' ? " DEFAULT ".$r_f[4]:"").",";
		$re.=$r_f[1].",";
		if($r_f[5]) $pk.=$r_f[1].",";
	}
	}
	$q_fk=$ed->con->query("PRAGMA foreign_key_list($tb)")->fetch(2);
	foreach($q_fk as $r_fk) {
	if($r_fk['from']!=$fn) $fk.="FOREIGN KEY (".$r_fk['from'].") REFERENCES ".$r_fk['table']." (".$r_fk['to'].")".(empty($r_fk['on_delete'])?"":" ON DELETE ".$r_fk['on_delete']).(empty($r_fk['on_update'])?"":" ON UPDATE ".$r_fk['on_update']).",";
	}
	$qr.=(empty($pk) ? "":" PRIMARY KEY(".substr($pk,0,-1)."),").$fk;
	$ed->con->exec("ALTER TABLE $tb RENAME TO temp_$tb");
	$ed->con->exec("CREATE TABLE $tb (".substr($qr,0,-1).")");
	$ed->con->exec("INSERT INTO $tb SELECT ".substr($re,0,-1)." FROM temp_$tb");
	$ed->con->exec("DROP TABLE temp_$tb");
	foreach($q_it as $r_it) $ed->con->exec($r_it[2]);
	$ed->con->exec("COMMIT");
	$ed->redir("5/$db",['ok'=>"Successfully deleted"]);
break;

case "14"://fk
	$ed->check([1,2],['redir'=>10]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$fkty=['RESTRICT','NO ACTION','CASCADE','SET NULL','SET DEFAULT'];
	$q_t=$ed->con->query("PRAGMA table_info($tb)")->fetch(1);
	$q_fk=$ed->con->query("PRAGMA foreign_key_list($tb)")->fetch(2);
	if(($ed->post('table','!e') && $ed->post('to','!e')) || isset($ed->sg[4])) {
		$qr='';$pk='';$fk='';$re='';
		foreach($q_fk as $r_fk) {
		if(isset($ed->sg[3]) && $r_fk['id']==$ed->sg[3] && !isset($ed->sg[4])) {
		$fk.="FOREIGN KEY (".$ed->post('from').") REFERENCES ".$ed->post('table')." (".$ed->post('to').")".($ed->post('drule','!e')?" ON DELETE ".$ed->post('drule'):'').($ed->post('urule','!e')?" ON UPDATE ".$ed->post('urule'):'').",";
		} elseif((isset($ed->sg[3]) && $r_fk['id']!=$ed->sg[3]) || !isset($ed->sg[4])) {
		$fk.="FOREIGN KEY (".$r_fk['from'].") REFERENCES ".$r_fk['table']." (".$r_fk['to'].")".(empty($r_fk['on_delete'])?"":" ON DELETE ".$r_fk['on_delete']).(empty($r_fk['on_update'])?"":" ON UPDATE ".$r_fk['on_update']).",";
		}
		}
		if(!isset($ed->sg[3])) {
		$fk.="FOREIGN KEY (".$ed->post('from').") REFERENCES ".$ed->post('table')." (".$ed->post('to').")".($ed->post('drule','!e')?" ON DELETE ".$ed->post('drule'):'').($ed->post('urule','!e')?" ON UPDATE ".$ed->post('urule'):'').",";
		}
		foreach($q_t as $r_t) {
		$re.=$r_t[1].",";
		$qr.=$r_t[1]." ".$r_t[2].($r_t[3]!=0 ? " NOT NULL":"").($r_t[4]!='' ? " DEFAULT ".$r_t[4]:"").",";
		if($r_t[5]>0) $pk.=$r_t[1].",";
		}
		$qr.=(empty($pk) ? "":" PRIMARY KEY(".substr($pk,0,-1)."),").$fk;
		$ed->con->exec("PRAGMA foreign_keys=OFF");
		$ed->con->exec("BEGIN TRANSACTION");
		$q_it=$ed->con->query("SELECT sql FROM sqlite_master WHERE tbl_name='$tb' AND type IN ('index','trigger')")->fetch(1);
		$ed->con->exec("ALTER TABLE $tb RENAME TO temp_$tb");
		$ed->con->exec("CREATE TABLE $tb(".substr($qr,0,-1).")");
		$ed->con->exec("INSERT INTO $tb SELECT ".substr($re,0,-1)." FROM temp_$tb");
		$ed->con->exec("DROP TABLE temp_$tb");
		foreach($q_it as $r_it) $ed->con->exec($r_it[0]);
		$ed->con->exec("COMMIT");
		$ed->con->exec("PRAGMA foreign_keys=ON");
		$ed->redir("10/$db/$tb",['ok'=>"Successfully ".(isset($ed->sg[3])?"changed":"add")]);
	}
	echo $head.$ed->menu($db,$tb,2).$ed->form("14/$db/$tb".((isset($ed->sg[3]) && $ed->sg[3]>=0)?"/".$ed->sg[3]:''));
	echo "<table><caption>TABLE FOREIGN KEY</caption><tr><th>FIELD</th><th>TARGET TABLE</th><th>TARGET FIELD</th><th>ON DELETE</th><th>ON UPDATE</th></tr>";
	if(isset($ed->sg[3]) && $ed->sg[3]>=0) {
	foreach($q_fk as $r_fk) {
	if($r_fk['id']==$ed->sg[3]) {
	echo "<tr><td><input type='text' name='from' value='{$r_fk['from']}' /></td><td><input type='text' name='table' value='{$r_fk['table']}' /></td><td><input type='text' name='to' value='{$r_fk['to']}' /></td><td><select name='drule'>";
	foreach($fkty as $fkt) echo "<option value='$fkt'".($r_fk['on_delete']==$fkt?" selected":"").">$fkt</option>";
	echo "</select></td><td><select name='urule'>";
	foreach($fkty as $fkt) echo "<option value='$fkt'".($r_fk['on_update']==$fkt?" selected":"").">$fkt</option>";
	echo "</select></td></tr>";
	}
	}
	} else {
	echo "<tr><td><input type='text' name='from' /></td><td><input type='text' name='table' /></td><td><input type='text' name='to' /></td><td><select name='drule'>";
	foreach($fkty as $fkt) echo "<option value='$fkt'>$fkt</option>";
	echo "</select></td><td><select name='urule'>";
	foreach($fkty as $fkt) echo "<option value='$fkt'>$fkt</option>";
	echo "</select></td></tr>";
	}
	echo "<tr><td colspan='5'><button type='submit'>Change</button></td></tr></table></form>";
break;

case "20"://table browse
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$where=(!empty($_SESSION["_litesearch_{$db}_{$tb}"])?" ".$_SESSION["_litesearch_{$db}_{$tb}"] : "");
	$all=$ed->con->query("SELECT COUNT(*) FROM ".$tb.$where,true)->fetch();
	$totalpg=ceil($all/$step);
	if(empty($ed->sg[3])) {
	$pg=1;
	} else {
	$pg=$ed->sg[3];
	$ed->check([4],['pg'=>$pg,'total'=>$totalpg,'redir'=>"20/$db/$tb"]);
	}
	$offset=($pg - 1) * $step;
	$q_rex=$ed->con->query("SELECT * FROM {$tb}{$where} LIMIT $offset,$step");

	$cols=$q_rex->num_col();
	$r_col=$q_rex->fetch(2);
	$q_vws=$ed->con->query("SELECT type FROM sqlite_master WHERE name='$tb'",true)->fetch();
	echo $head.$ed->menu($db,($q_vws=='view'?'':$tb),1,($q_vws=='view'?['view',$tb]:''))."<table><tr>";
	if($q_vws !='view') echo "<th>ACTIONS</th>";
	$q_ti=$ed->con->query("PRAGMA table_info($tb)")->fetch(1);
	$rinf=[];
	foreach($q_ti as $r_ti) {
		if($r_col && array_key_exists($r_ti[1],$r_col[0])) {
		$rinf[$r_ti[1]]=$r_ti[2];
		echo "<th>".$r_ti[1]."</th>";
		} elseif(empty($r_col)) echo "<th>".$r_ti[1]."</th>";
	}
	echo "</tr>";
	if($r_col) {
	$key=array_keys($r_col[0]);
	foreach($r_col as $row) {
		$bg=($bg==1)?2:1;
		echo "<tr class='r c$bg'>";
		if($q_vws !='view') {
		$nu=$key[0]."/".($row[$key[0]]=="" && !is_numeric($row[$key[0]])?"isnull":base64_encode($row[$key[0]])).(!empty($key[1]) && !empty($rinf[$key[1]]) && (stristr($rinf[$key[1]],"int") || stristr($rinf[$key[1]],"varchar")) && stristr($rinf[$key[1]],"blob")==false && !empty($row[$key[1]]) ? "/".$key[1]."/".base64_encode($row[$key[1]]):"");
		echo "<td><a href='{$ed->path}22/$db/$tb/$nu'>Edit</a><a class='del' href='{$ed->path}23/$db/$tb/$nu'>Delete</a></td>";
		}
		$j=0;
		while($j<$cols) {
			$val=($row[$key[$j]]==''?'':htmlentities($row[$key[$j]]));
			echo "<td>";
			if(stristr($rinf[$key[$j]],"blob")==true ) {
			$le=strlen($row[$key[$j]]);
			echo "[blob] ";
			if($le > 4) {
			echo "<a href='".$ed->path."33/$db/$tb/$nu/".$key[$j]."'>".number_format(($le/1024),2)." KB</a>";
			} else {
			echo number_format(($le/1024),2)." KB";
			}
			} elseif(strlen($val) > 70) {
			echo substr($val,0,70)."[...]";
			} else echo $val;
			echo "</td>";
			++$j;
		}
		echo "</tr>";
	}
	}
	echo "</table>";
	echo $ed->pg_number($pg,$totalpg);
break;

case "21"://insert row
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$q_pra=$ed->con->query("PRAGMA table_info($tb)")->fetch(2);
	if($ed->post('save','i') || $ed->post('save2','i')) {
		$qr2="INSERT INTO $tb (";
		$qr4="VALUES(";
		$i=0;
		foreach($q_pra as $r_ra) {
			if(strtolower($r_ra['type'])=="boolean") {
			$qr2.=$r_ra['name'].",";
			$qr4.="'".($ed->post('r'.$i,0) ? 1:'')."',";
			} elseif(strtolower($r_ra['type'])=="blob") {
			if(!empty($_FILES['r'.$i]['tmp_name'])) {
			$qr2.=$r_ra['name'].",";
			$qr4.="'".base64_encode(file_get_contents($_FILES['r'.$i]['tmp_name']))."',";
			}
			} else {
			$qr2.=$r_ra['name'].",";
			if($r_ra['pk']==1 && stripos($r_ra['type'],'INTEGER') && $ed->post('r'.$i,'e')) {
			$max=$ed->con->query("SELECT max(".$q_pra[0]['name'].") FROM ".$tb,true)->fetch();
			$qr4.=($max+1).",";
			} else {
			$qr4.=(($ed->post('r'.$i,'e') && $r_ra['notnull']==0)? "NULL":"'".str_replace("'","''",$ed->post('r'.$i))."'").",";
			}
			}
			++$i;
		}
		$qr2=substr($qr2,0,-1).") ";
		$qr4=substr($qr4,0,-1).")";
		$ed->con->exec("PRAGMA foreign_keys=ON");
		$q_inn=$ed->con->exec($qr2.$qr4);
		$ed->con->exec("PRAGMA foreign_keys=OFF");
		if($ed->post('save2','i')) $rr=21;
		else $rr=20;
		if($q_inn===false) $ed->redir("$rr/$db/$tb",['err'=>"Can't insert"]);
		else $ed->redir("$rr/$db/$tb",['ok'=>"Successfully inserted"]);
	} else {
		echo $head.$ed->menu($db,$tb,1).$ed->form("21/$db/$tb",1)."<table><caption>Insert Row</caption>";
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
		echo "<tr><td><button type='submit' name='save'>Save</button></td><td><button type='submit' name='save2'>Save &amp; Insert Next</button></td></tr></table></form>";
	}
break;

case "22"://edit row
	$ed->check([1,2,3],['redir'=>20]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$nu=$ed->sg[3];
	if(empty($nu)) $ed->redir("20/$db/$tb",['err'=>"Can't edit empty field"]);
	$id=($ed->sg[4]=="isnull"?"":base64_decode($ed->sg[4]));
	$nu1=(empty($ed->sg[5])?"":$ed->sg[5]); $id1=(empty($ed->sg[6])?"":base64_decode($ed->sg[6]));
	$q_rd=$ed->con->query("PRAGMA table_info($tb)")->fetch(2);
	$nul=("(".$nu." IS NULL OR ".$nu."='')");
	if($ed->post('edit','i')) {
		$qr="";
		foreach($q_rd as $r_rd) {
			$stype=strtolower($r_rd['type']);
			$f_rd=$r_rd['name'];
			if($stype=="blob") {
				if(!empty($_FILES['d'.$f_rd]['tmp_name'])) {
				$qr.=$f_rd."='".base64_encode(file_get_contents($_FILES['d'.$f_rd]['tmp_name']))."',";
				}
			} elseif($stype=="boolean") {
				$qr.=$f_rd."='".($ed->post('d'.$f_rd,0) ? 1:'')."',";
			} else {
				$qr.=$f_rd."=".(($ed->post('d'.$f_rd,'e') && !is_numeric($ed->post('d'.$f_rd)) && $r_rd['notnull']==0)? "NULL":"'".$qry=str_replace("'","''",$ed->post('d'.$f_rd))."'").",";
			}
		}
		$qq=substr($qr,0,-1);
		$ed->con->exec("PRAGMA foreign_keys=ON");
		$q_edd=$ed->con->exec("UPDATE $tb SET $qq WHERE ".($id==""?$nul:$nu."='$id'").(!empty($nu1) && !empty($id1)?" AND $nu1='$id1'":""));
		$ed->con->exec("PRAGMA foreign_keys=OFF");
		if($q_edd===false) $ed->redir("20/$db/$tb",['err'=>"Can't update"]);
		else $ed->redir("20/$db/$tb",['ok'=>"Successfully updated"]);
	} else {
		$arr=$ed->con->query("SELECT * FROM $tb WHERE ".($id==""?$nul:$nu."='$id'").(!empty($nu1) && !empty($id1)?" AND $nu1='$id1'":"")." LIMIT 1")->fetch(2);
		echo $head.$ed->menu($db,$tb,1).$ed->form("22/$db/$tb/$nu/".($id==""?"isnull":base64_encode($id)).(!empty($nu1) && !empty($id1)?"/$nu1/".base64_encode($id1):""),1)."<table><caption>Edit Row</caption>";
		foreach($q_rd as $r_ed) {
			$nr=$r_ed['name'];
			$typ=strtolower($r_ed['type']);
			echo "<tr><td>".$r_ed['name']."</td><td>";
			if($typ=="boolean") {
			foreach($bbs as $kk=>$bb) {
			echo "<input type='radio' name='d".$nr."[]' value='$kk'".($arr[0][$nr]==$kk ? " checked":"")." /> $bb ";
			}
			} elseif($typ=="blob") {
			echo "[blob] ".number_format((strlen($arr[0][$nr])/1024),2)." KB<br/><input type='file' name='d".$nr."' />";
			} elseif($typ=="text") {
			echo "<textarea name='d".$nr."'>".($arr[0][$nr]==''?'':htmlentities($arr[0][$nr]))."</textarea>";
			} else {
			echo "<input type='text' name='d".$nr."' value='".($arr[0][$nr]==''?'':htmlentities($arr[0][$nr]))."' />";
			}
			echo "</td></tr>";
		}
		echo "<tr><td><a class='del link' href='".$ed->path."23/$db/$tb/$nu/".($id==""?"isnull":base64_encode($id)).(!empty($nu1) && !empty($id1)?"/$nu1/".base64_encode($id1):"")."'>Delete</a></td><td><button type='submit' name='edit'>Update</button></td></tr></table></form>";
	}
break;

case "23"://delete row
	$ed->check([1,2,3],['redir'=>20]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$nul=("(".$ed->sg[3]." IS NULL OR ".$ed->sg[3]."='')");
	$ed->con->exec("PRAGMA foreign_keys=ON");
	$exec_dr=$ed->con->query("DELETE FROM ".$tb." WHERE ".($ed->sg[4]=="isnull"?$nul:$ed->sg[3]."='".base64_decode($ed->sg[4])."'").(!empty($ed->sg[5]) && !empty($ed->sg[6])?" AND ".$ed->sg[5]."='".base64_decode($ed->sg[6])."'":""));
	$ed->con->exec("PRAGMA foreign_keys=OFF");
	if($exec_dr->last()) $ed->redir("20/$db/$tb",['ok'=>"Deleted row"]);
	else $ed->redir("20/$db/$tb",['err'=>"Delete row failed"]);
break;

case "24"://search
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	unset($_SESSION['_litesearch_'.$db.'_'.$tb]);
	if(!empty($ed->sg[3]) && $ed->sg[3]=='reset') {
	$ed->redir("20/$db/$tb",['ok'=>"Reset search"]);
	}
	$q_se=$ed->con->query("PRAGMA table_info($tb)")->fetch(2);
	$cond1=['=','&lt;','&gt;','&lt;=','&gt;=','!=','LIKE','NOT LIKE','REGEXP','NOT REGEXP'];
	$cond2=['BETWEEN','NOT BETWEEN'];
	$cond3=['IN','NOT IN'];
	$cond4=['IS NULL','IS NOT NULL'];
	$cond=array_merge($cond1,$cond2,$cond3,$cond4);
	if($ed->post('search','i')) {//post
	$search_cond=[];
	foreach($q_se as $r_se) {
		if($ed->post($r_se['name'],'!e') || in_array($ed->post('cond__'.$r_se['name']),$cond4)) {
		$fd=$r_se['name'];
		$cd=$ed->post('cond__'.$fd);
		$po=$ed->post($fd);
		if(in_array($cd,$cond2)) {
		$sl=preg_split("/[,]+/",$po);
		$sl2=(!empty($sl[1])?$sl[1]:$sl[0]);
		$search_cond[]=$fd." ".$cd." '".$sl[0]."' AND '".$sl2."'";
		}
		elseif(in_array($cd,$cond3)) $search_cond[]=$fd." ".$cd." ('".$po."')";
		elseif(in_array($cd,$cond4)) $search_cond[]=$fd." ".$cd;
		else $search_cond[]=$fd." ".html_entity_decode($ed->post('cond__'.$fd))." '".$po."'";
		}
	}
	$se_str=($search_cond?"WHERE ":"").implode(" AND ",$search_cond).($ed->post('order_field','!e')?" ORDER BY ".$ed->post('order_field')." ".$ed->post('order_ord')." ":"");
	$_SESSION['_litesearch_'.$db.'_'.$tb]=$se_str;
	$ed->redir("20/$db/$tb");
	}
	echo $head.$ed->menu($db,$tb,1).$ed->form("24/$db/$tb")."<table><caption>Search</caption>";
	$conds="";
	foreach($cond as $cnd) $conds.="<option value='".$cnd."'>".$cnd."</option>";
	$fields="<option value=''>&nbsp;</option>";
	foreach($q_se as $r_se) {
	$fl=$r_se['name'];
	$fields.="<option value='$fl'>$fl</option>";
	echo "<tr><td>$fl</td><td><select name='cond__".$fl."'>$conds</select></td><td><input type='text' name='$fl'/></td></tr>";
	}
	echo "<tr class='c1'><td>ORDER</td><td><select name='order_field'>$fields</select></td><td><select name='order_ord'><option value='ASC'>ASC</option><option value='DESC'>DESC</option></select></td></tr><tr><td colspan='3'><button type='submit' name='search'>Search</button></td></tr></table></form>";
break;

case "25"://table empty
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$ed->con->exec("DELETE FROM ".$tb);
	$ed->redir("5/$db",['ok'=>"Table is empty"]);
break;

case "26"://drop table
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$ed->con->exec("BEGIN TRANSACTION");
	$q_dv=$ed->con->query("SELECT name,sql FROM sqlite_master WHERE type='view'")->fetch(1);//drop view
	if($q_dv) {
	foreach($q_dv as $r_dv) {
	preg_match("/\b(".$tb.")\b/i",$r_dv[1],$match);
	if($match) $ed->con->exec("DROP VIEW ".$r_dv[0]);
	}
	}
	$ed->con->exec("DROP TABLE $tb");
	$ed->con->exec("COMMIT");
	$ed->con->exec("VACUUM");
	$ed->redir("5/$db",['ok'=>"Successfully dropped"]);
break;

case "27"://analyze
	$ed->check([1,2]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$op=$ed->sg[3];
	$ops=['analyze'];
	if(!empty($op) && in_array($op,$ops)) {
	$ed->con->exec("$op $tb");
	$ed->redir("10/$db/$tb",['ok'=>"Successfully $op runed"]);
	} else $ed->redir("10/$db/$tb",['err'=>"Action $op failed"]);
break;

case "30"://import
	$ed->check([1]);
	$db=$ed->sg[1];
	$out="";
	@set_time_limit(7200);
	$e='';
	$rgex="~^\xEF\xBB\xBF|^\xFE\xFF|^\xFF\xFE|(\#|--).*|(\/\*).*(\*\/;*)|\(([^)]*\)*(\"*.*\")*('*.*'))(*SKIP)(*F)|(?is)(BEGIN.*?END)(*SKIP)(*F)|(?<=;)(?![ ]*$)~";
	if($ed->post('qtxt','!e')) {//in textarea
		$qtxt=$ed->post('qtxt');
		if(preg_match('/^\b(select)\b/is',$qtxt)) {//run select
			$q_sel=$ed->con->query($qtxt);
			if($q_sel) {
			$q_sel=$q_sel->fetch(2);
			echo $head.$ed->menu($db,'',1)."<table><tr>";
			foreach($q_sel[0] as $k=>$r_sel) echo "<th>$k</th>";
			echo "</tr>";
			foreach($q_sel as $r_sel) {
			$bg=($bg==1)?2:1;
			echo "<tr class='r c$bg'>";
			foreach($r_sel as $r_se) echo "<td>$r_se</td>";
			echo "</tr>";
			}
			echo "</table>";
			} else $ed->redir("5/$db",['err'=>"Wrong query"]);
		} else {
			$e=preg_split($rgex,$qtxt,-1,PREG_SPLIT_NO_EMPTY);//import sql
		}
	} elseif($ed->post('send','i') && $ed->post('send')=="ff") {//from file
		if(empty($_FILES['importfile']['tmp_name'])) {
		$ed->redir("5/$db",['err'=>"No file to upload"]);
		} else {
			$tmp=$_FILES['importfile']['tmp_name'];
			$file=pathinfo($_FILES['importfile']['name']);
			$fext=strtolower($file['extension']);
			if($fext=='sql') {//sql file
				$fi=$ed->utf(file_get_contents($tmp));
				$e=preg_split($rgex,$fi,-1,PREG_SPLIT_NO_EMPTY);
			} elseif($fext=='csv') {//csv file
				$e=$ed->imp_csv($file['filename'],$tmp);
			} elseif($fext=='json') {//json file
				$e=$ed->imp_json($file['filename'],$tmp);
			} elseif($fext=='xml') {//xml file
				$e=$ed->imp_xml($file['filename'],$tmp);
			} elseif(in_array($fext,['db',substr($ed->ext,1)])) {//sqlite file
				$ed->imp_sqlite($file['filename'],$tmp);
			} elseif($fext=='gz') {//gz file
				if(($fgz=fopen($tmp,'r')) !==FALSE) {
				if(@fread($fgz,3) !="\x1F\x8B\x08") $ed->redir("5/$db",['err'=>"Not a valid GZ file"]);
				fclose($fgz);
				}
				if(@function_exists('gzopen')) {
					$gzfile=@gzopen($tmp,'rb');
					if(!$gzfile) $ed->redir("5/$db",['err'=>"Can't open GZ file"]);
					while(!gzeof($gzfile)) {
					$e.=gzgetc($gzfile);
					}
					gzclose($gzfile);
					$entr=pathinfo($file['filename']);
					$e_ext=$entr['extension'];
					if($e_ext=='sql') $e=preg_split($rgex,$ed->utf($e),-1,PREG_SPLIT_NO_EMPTY);
					elseif($e_ext=='csv') $e=$ed->imp_csv($entr['filename'],$e);
					elseif($e_ext=='json') $e=$ed->imp_json($entr['filename'],$e);
					elseif($e_ext=='xml') $e=$ed->imp_xml($entr['filename'],$e);
					elseif(in_array($e_ext,['db',substr($ed->ext,1)])) $ed->imp_sqlite($file['filename'],$e);
					else $ed->redir("5/$db",['err'=>"Disallowed extension"]);
				} else {
					$ed->redir("5/$db",['err'=>"Can't open GZ file"]);
				}
			} elseif($fext=='zip') {//zip file
				if(($fzip=fopen($tmp,'r')) !==FALSE) {
					if(@fread($fzip,4) !="\x50\x4B\x03\x04") $ed->redir("5/$db",['err'=>"Not a valid ZIP file"]);
					fclose($fzip);
				}
				$zip=zip_open($tmp);
				if(is_resource($zip)) {
					$zip_entry=zip_read($zip);
					if(zip_entry_open($zip,$zip_entry,"rb")) {
					$zentry=zip_entry_name($zip_entry);
					if($file['filename']==$zentry) {
					$buf=zip_entry_read($zip_entry,zip_entry_filesize($zip_entry));
					preg_match("/^(.*)\.(sql|csv|json|xml)$/i",$zentry,$zn);
					if(!empty($zn[2]) && $zn[2]=='sql') $e=preg_split($rgex,$ed->utf($buf),-1,PREG_SPLIT_NO_EMPTY);
					elseif(!empty($zn[2]) && $zn[2]=='csv') $e=$ed->imp_csv($zn[1],$buf);
					elseif(!empty($zn[2]) && $zn[2]=='json') $e=$ed->imp_json($zn[1],$buf);
					elseif(!empty($zn[2]) && $zn[2]=='xml') $e=$ed->imp_xml($zn[1],$buf);
					else $ed->redir("5/$db",['err'=>"Disallowed extension"]);
					zip_entry_close($zip_entry);
					}
					}
					zip_close($zip);
				}
			} else {
				$ed->redir("5/$db",['err'=>"Disallowed extension"]);
			}
		}
	} else {
		$ed->redir("5/$db",['err'=>"Query failed"]);
	}
	$q=0;
	if(is_array($e)) {
		set_error_handler(function() {});
		$ed->con->exec("BEGIN TRANSACTION");
		foreach($e as $qry) {
			$qry=trim($qry);
			if(!empty($qry)) {
				$qry=str_replace(["\'","\\n"],["''","\n"],$qry);
				$exc=$ed->con->query($qry);
				$op=['insert','update','delete'];
				$p_qry=strtolower(substr($qry,0,6));
				if(in_array($p_qry,$op)) $exc=$exc->last();
				$e_exc=$ed->con->err();
				$out.="<p>";
				$fa="<b>FAILED!</b> ";
				if(extension_loaded(DBT::$litetype[0])) {
					if($exc && !$e_exc) ++$q;
					else $out.=$fa.$qry;
				} else {
					if($exc && ($e_exc[0]==='00000' || $e_exc[0]==='01000')) ++$q;
					else $out.=$fa.$qry;
				}
				$out.="</p>";
			}
		}
		$ed->con->exec("COMMIT");
		echo $head.$ed->menu($db)."<div class='col2'><p>Successfully executed: <b>$q quer".($q>1?'ies':'y')."</b></p>$out";
	}
break;

case "31"://export form
	$ed->check([1]);
	$db=$ed->sg[1];
	$q_extbs=$ed->con->query("SELECT name FROM sqlite_master WHERE type IN ('table','view')")->fetch(1);
	$deny=['sqlite_sequence'];
	$ex=0;$r_tts=[];
	foreach($q_extbs as $r_extbs) {
	if(!in_array($r_extbs[0],$deny)) {
	$r_tts[]=$r_extbs[0];
	++$ex;
	}
	}
	if($ex > 0) {
	echo $head.$ed->menu($db,'',2).$ed->form("32/$db")."<div class='dw'><h3 class='l1'>Export</h3><h3>Select table(s)</h3><p><input type='checkbox' onclick='selectall(this,\"tables\")' /> All/None</p><select class='he' id='tables' name='tables[]' multiple='multiple'>";
	foreach($r_tts as $tts) {
	echo "<option value='$tts'>$tts</option>";
	}
	echo "</select><h3><input type='checkbox' onclick='toggle(this,\"fopt[]\")' /> Options</h3>";
	$opts=['structure'=>'Structure','data'=>'Data','drop'=>'Drop if exist','ifnot'=>'If not exist','trigger'=>'Triggers'];
	foreach($opts as $k=> $opt) {
	echo "<p><input type='checkbox' name='fopt[]' value='$k'".($k=='structure' ? ' checked':'')." /> $opt</p>";
	}
	echo "<h3>File format</h3>";
	$ffo=['sql'=>'SQL','csv1'=>'CSV,','csv2'=>'CSV;','json'=>'JSON','xls'=>'Excel Spreadsheet','doc'=>'Word Web','xml'=>'XML','sqlite'=>'SQLite'];
	foreach($ffo as $k=> $ff) {
	echo "<p><input type='radio' name='ffmt[]' onclick='opt()' value='$k'".($k=='sql' ? ' checked':'')." /> $ff</p>";
	}
	echo "<h3>File compression</h3><p><select name='ftype'>";
	$fty=['plain'=>'None','gzip'=>'GZ','zip'=>'Zip'];
	foreach($fty as $k=> $ft) {
	echo "<option value='$k'>$ft</option>";
	}
	echo "</select></p><button type='submit' name='exp'>Export</button></div></form>";
	} else {
	$ed->redir("5/$db",["err"=>"No export empty DB"]);
	}
break;

case "32"://export
	$ed->check([1]);
	$db=$ed->sg[1];
	$tbs=[];
	$vws=[];
	$ftype=$ed->post('ftype');
	$ffmt=$ed->post('ffmt');
	if($ffmt[0]!='sqlite') {
	if($ed->post('tables')=='' && $ffmt[0]!='sql') {
		$ed->redir("31/$db",['err'=>"You didn't select any table"]);
	} elseif($ed->post('tables','!e')) {//selected tables
		$tabs=$ed->post('tables');
		foreach($tabs as $tab) {
			$q_strc=$ed->con->query("SELECT name,type FROM sqlite_master WHERE name='$tab'")->fetch(2);
			if($q_strc[0]['name']==$tab && $q_strc[0]['type']=='view') {
			array_push($vws,$tab);
			} elseif($q_strc[0]['name']==$tab && $q_strc[0]['type']=='table') {
			array_push($tbs,$tab);
			}
		}
	}
	if($ed->post('fopt')=='') {//export options
		$ed->redir("31/$db",['err'=>"You didn't select any option"]);
	} else {
		$fopt=$ed->post('fopt');
	}
	}
	if($ffmt[0]=='sql') {//data sql
		$ffty="text/plain"; $ffext=".sql"; $fname=$db.$ffext;
		$sql="-- EdLiteAdmin $version SQL Dump\n";
		if(!empty($fopt)) {
			foreach($tbs as $tb) {
				if(in_array('structure',$fopt)) $sql.=$ed->tb_structure($tb,$fopt);//begin structure
				$val='';
				if(in_array('data',$fopt)) {//option data
					$res2=$ed->con->query("SELECT * FROM ".$tb);
					$nr=$res2->num_col();
					foreach($res2->fetch(1) as $row) {
					$ro="\nINSERT INTO $tb VALUES(";
					$i=0;
					while($i < $nr) {
					if(is_numeric($row[$i])) $ro.=$row[$i].",";
					else $ro.="'".preg_replace(["/\r\n|\r|\n/","/'/"],["\\n","''"],$row[$i])."',";
					++$i;
					}
					$val.=substr($ro,0,-1).");";
					}
					$sql.=$val."\n";
				}
			}
			if($vws !='' && in_array('structure',$fopt)) {//export views
			$sql.="\n";
			foreach($vws as $vw) {
				$q_rw=$ed->con->query("SELECT sql FROM sqlite_master WHERE name='$vw' AND type='view'",true)->fetch();
				if($q_rw) {
				if(in_array('drop',$fopt)) {//option drop
				$sql.="DROP VIEW IF EXISTS $vw;\n";
				}
				if(in_array('ifnot',$fopt)) {//option if not
				$sql.=preg_replace('~(CREATE\sVIEW\s)(.*)~i','${1}IF NOT EXISTS ${2}',$q_rw);
				} else {
				$sql.=$q_rw;
				}
				$sql.=";\n";
				}
			}
			}
			if(in_array('trigger',$fopt)) {//option data
				$q_ttgr=$ed->con->query("SELECT name,sql FROM sqlite_master WHERE type='trigger'")->fetch(1);
				foreach($q_ttgr as $r_ttgr) {
				if(in_array('drop',$fopt)) {//option drop
				$sql.="\nDROP TRIGGER IF EXISTS ".$r_ttgr[0].";";
				}
				$sql.="\n";
				if(in_array('ifnot',$fopt)) {//option if not
				$sql.=preg_replace('~(CREATE\sTRIGGER\s)(.*)~i','${1}IF NOT EXISTS ${2}',$r_ttgr[1]);
				} else {
				$sql.=$r_ttgr[1];
				}
				$sql.=";\n";
				}
			}
		}
	} elseif($ffmt[0]=='csv1' || $ffmt[0]=='csv2') {//csv
		$tbs=array_merge($tbs,$vws);
		$ffty="text/csv"; $ffext=".csv"; $fname=$db.$ffext;
		$sql=[];
		if(count($tbs)==1 || $ftype=="plain") {
			$tbs=[$tbs[0]];
			$fname=$tbs[0].$ffext;
		}
		$sign=($ffmt[0]=='csv1'?',':';');
		if(empty($tbs[0])) $ed->redir("31/$db",['err'=>"Select a table/view"]);
		foreach($tbs as $tb) {
			$sq='';
			$q_csvs=$ed->con->query("SELECT * FROM $tb");
			$q_csv=$q_csvs->fetch(1);
			$ncol=$q_csvs->num_col();
			$cols=$ed->con->query("PRAGMA table_info($tb)")->fetch(2);
			$i=0;
			while($i < $ncol) {
			$sq.=$cols[$i]['name'].$sign;
			++$i;
			}
			$sq=substr($sq,0,-1)."\n";
			foreach($q_csv as $r_rs) {
				$t=0;
				while($t<$ncol) {
				$sq.=(is_numeric($r_rs[$t]) ? $r_rs[$t]:"\"".preg_replace(["/\r\n|\r|\n/","/'/","/\"/"],["\\n","\'","\"\""],$r_rs[$t])."\"").$sign;
				++$t;
				}
				$sq=substr($sq,0,-1)."\n";
			}
			$sql[$tb.$ffext]=$sq;
		}
		if(count($tbs)==1 || $ftype=="plain") $sql=$sql[$fname];
	} elseif($ffmt[0]=='json') {//json
		$tbs=array_merge($tbs,$vws);
		$ffty="text/json"; $ffext=".json"; $fname=$db.$ffext;
		$sql=[];
		if(count($tbs)==1 || $ftype=="plain") {
			$tbs=[$tbs[0]];
			$fname=$tbs[0].$ffext;
		}
		foreach($tbs as $tb) {
			$sq="// EdLiteAdmin $version JSON Dump\n\n";
			$q_jso=$ed->con->query("SELECT * FROM $tb")->fetch(2);
			if(count($q_jso) > 0) {
			$sq.='[';
			foreach($q_jso as $k_jso=>$r_jso) {
			$jh='{';
			foreach($r_jso as $k_jo=>$r_jo) $jh.='"'.$k_jo.'":'.(is_numeric($r_jo)?$r_jo:'"'.preg_replace(["/\r\n|\r|\n/","/\t/","/'/","/\"/"],["\\n","\\t","''","&quot;"],$r_jo).'"').',';
			$sq.=substr($jh,0,-1).'},';
			}
			$sq=substr($sq,0,-1).']';
			}
			$sql[$tb.$ffext]=$sq;
		}
		if(count($tbs)==1 || $ftype=="plain") $sql=$sql[$fname];
	} elseif($ffmt[0]=='doc') {//doc
		$tbs=array_merge($tbs,$vws);
		$ffty="application/msword"; $ffext=".doc"; $fname=$db.$ffext;
		$sql='<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:word" 	xmlns="http://www.w3.org/TR/REC-html40"><!DOCTYPE html><html><head><meta http-equiv="Content-type" content="text/html;charset=utf-8"></head><body>';
		foreach($tbs as $tb) {
		$q_doc=$ed->con->query("PRAGMA table_info($tb)")->fetch(2);
		if(in_array('structure',$fopt)) {
			$wh='<table border=1 cellpadding=0 cellspacing=0 style="border-collapse: collapse"><tr bgcolor="#eeeeee">';
			foreach($q_doc[0] as $r_k=>$r_doc) $wh.='<th>'.$r_k.'</th>';
			$wh.='</tr>';
			foreach($q_doc as $r_doc) {
				$wh.='<tr>';
				foreach($r_doc as $r_d1) $wh.='<td>'.$r_d1.'</td>';
				$wh.='</tr>';
			}
			$wh.='</table><br>';
		}
		if(in_array('data',$fopt)) {
			$wb='<table border=1 cellpadding=0 cellspacing=0 style="border-collapse: collapse"><tr>';
			foreach($q_doc as $r_dc) $wb.='<th>'.$r_dc['name'].'</th>';
			$wb.="</tr>";
			$q_dc2=$ed->con->query("SELECT * FROM $tb")->fetch(1);
			foreach($q_dc2 as $r_dc2) {
			$wb.="<tr>";
			foreach($r_dc2 as $r_d2) $wb.='<td>'.$r_d2.'</td>';
			$wb.="</tr>";
			}
			$wb.='</table><br>';
		}
		$sql.=$wh.$wb;
		}
		$sql.='</body></html>';
	} elseif($ffmt[0]=='xls') {//xls
		$tbs=array_merge($tbs,$vws);
		$ffty="application/excel"; $ffext=".xls"; $fname=$db.$ffext;
		$sql='<?xml version="1.0"?><Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" xmlns:html="http://www.w3.org/TR/REC-html40">';
		foreach($tbs as $tb) {
			$xh='<Worksheet ss:Name="'.$tb.'"><Table><Row>';
			$q_xl1=$ed->con->query("PRAGMA table_info($tb)")->fetch(2);
			foreach($q_xl1 as $r_xl1) {
			$xh.='<Cell><Data ss:Type="String">'.$r_xl1['name'].'</Data></Cell>';
			}
			$xh.='</Row>';
			$q_xl2=$ed->con->query("SELECT * FROM $tb")->fetch(1);
			foreach($q_xl2 as $r_xl2) {
			$xh.='<Row>';
			foreach($r_xl2 as $r_x2) $xh.='<Cell><Data ss:Type="'.(is_numeric($r_x2)?'Number':'String').'">'.htmlentities($r_x2).'</Data></Cell>';
			$xh.='</Row>';
			}
			$sql.=$xh.'</Table></Worksheet>';
		}
		$sql.='</Workbook>';
	} elseif($ffmt[0]=='xml') {//xml
		$tbvws=array_merge($tbs,$vws);
		$ffty="application/xml"; $ffext=".xml"; $fname=$db.$ffext;
		$sql='<?xml version="1.0" encoding="utf-8"?>';
		$sql.="\n<!-- EdLiteAdmin $version XML Dump -->\n";
		$sql.="<export version=\"1.0\" xmlns:ed=\"https://github.com/edmondsql\">";
		if(in_array('structure',$fopt)) {
		$sql.="\n\t<ed:structure_schemas>";
		$sql.="\n\t\t<ed:database name=\"$db\">";
		foreach($tbvws as $tb) {
		$sql.="\n\t\t\t<ed:table name=\"$tb\">\n";
		$sql.=	$ed->tb_structure($tb,$fopt,"\t\t\t");
		$sql.="\t\t\t</ed:table>";
		}
		$sql.="\n\t\t</ed:database>\n\t</ed:structure_schemas>";
		}
		if(in_array('data',$fopt)) {
		$sq="\n\t<database name=\"$db\">";
		foreach($tbs as $tb) {
		$q_xm1=$ed->con->query("PRAGMA table_info($tb)")->fetch(1);
		$q_xm2=$ed->con->query("SELECT * FROM $tb")->fetch(1);
		foreach($q_xm2 as $r_=>$r_xm2) {
			$sq.="\n\t\t<table name=\"$tb\">";
			$x=0;
			foreach($r_xm2 as $r_x2) {
			$sq.="\n\t\t\t<column name=\"".$q_xm1[$x][1]."\">".addslashes(htmlspecialchars($r_x2))."</column>";
			++$x;
			}
			$sq.="\n\t\t</table>";
		}
		}
		$sq.="\n\t</database>";
		}
		$sql.=(empty($tbs)?'':$sq)."\n</export>";
	} elseif($ffmt[0]=='sqlite') {//sqlite
		$ffty="application/octet-stream"; $ffext=$ed->ext; $fname=$db.$ffext;
		$sql=file_get_contents($ed->dir.$db.$ed->ext);
	}

	if($ftype=="gzip") {//gzip
		$zty="application/x-gzip"; $zext=".gz";
		ini_set('zlib.output_compression','Off');
		if(is_array($sql) && count($sql)>1) {
		$sq='';
		foreach($sql as $qname=>$sqa) {
			$tmpf=tmpfile();
			$len=strlen($sqa);
			$ctxt=pack("a100a8a8a8a12a12",$qname,644,0,0,decoct($len),decoct(time()));
			$checksum=8*32;
			for($i=0; $i < strlen($ctxt); $i++) $checksum +=ord($ctxt[$i]);
			$ctxt.=sprintf("%06o",$checksum)."\0 ";
			$ctxt.=str_repeat("\0",512 - strlen($ctxt));
			$ctxt.=$sqa;
			$ctxt.=str_repeat("\0",511 - ($len + 511) % 512);
			fwrite($tmpf,$ctxt);
			fseek($tmpf,0);
			$fs=fstat($tmpf);
			$sq.=fread($tmpf,$fs['size']);
			fclose($tmpf);
		}
		$fname=$db.".tar";
		$sql=$sq.pack('a1024','');
		}
		$sql=gzencode($sql,9);
		header('Content-Encoding: gzip');
	} elseif($ftype=="zip") {//zip
		$zty="application/x-zip";
		$zext=".zip";
		$info=[];
		$ctrl_dir=[];
		$eof="\x50\x4b\x05\x06\x00\x00\x00\x00";
		$old_offset=0;
		if(is_array($sql)) $sqlx=$sql;
		else $sqlx[$fname]=$sql;
		foreach($sqlx as $qname=>$sqa) {
		$ti=getdate();
		if($ti['year'] < 1980) {
		$ti['year']=1980;$ti['mon']=1;$ti['mday']=1;$ti['hours']=0;$ti['minutes']=0;$ti['seconds']=0;
		}
		$time=(($ti['year'] - 1980) << 25) | ($ti['mon'] << 21) | ($ti['mday'] << 16) | ($ti['hours'] << 11) | ($ti['minutes'] << 5) | ($ti['seconds'] >> 1);
		$dtime=substr("00000000".dechex($time),-8);
		$hexdtime='\x'.$dtime[6].$dtime[7].'\x'.$dtime[4].$dtime[5].'\x'.$dtime[2].$dtime[3].'\x'.$dtime[0].$dtime[1];
		eval('$hexdtime="'.$hexdtime.'";');
		$fr="\x50\x4b\x03\x04\x14\x00\x00\x00\x08\x00".$hexdtime;
		$unc_len=strlen($sqa);
		$crc=crc32($sqa);
		$zdata=gzcompress($sqa);
		$zdata=substr(substr($zdata,0,strlen($zdata) - 4),2);
		$c_len=strlen($zdata);
		$fr.=pack('V',$crc).pack('V',$c_len).pack('V',$unc_len).pack('v',strlen($qname)).pack('v',0).$qname.$zdata;
		$info[]=$fr;
		$cdrec="\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00\x08\x00".$hexdtime.
		pack('V',$crc).pack('V',$c_len).pack('V',$unc_len).pack('v',strlen($qname)).
		pack('v',0).pack('v',0).pack('v',0).pack('v',0).pack('V',32).pack('V',$old_offset);
		$old_offset +=strlen($fr);
		$cdrec.=$qname;
		$ctrl_dir[]=$cdrec;
		}
		$ctrldir=implode('',$ctrl_dir);
		$end=$ctrldir.$eof.pack('v',sizeof($ctrl_dir)).pack('v',sizeof($ctrl_dir)).pack('V',strlen($ctrldir)).pack('V',$old_offset)."\x00\x00";
		$datax=implode('',$info);
		$sql=$datax.$end;
	}
	header("Cache-Control: no-store,no-cache,must-revalidate,pre-check=0,post-check=0,max-age=0");
	header("Content-Type: ".($ftype=="plain" ? $ffty."; charset=utf-8":$zty));
	header("Content-Length: ".strlen($sql));
	header("Content-Disposition: attachment; filename=".$fname.($ftype=="plain" ? "":$zext));
	die($sql);
break;

case "33": //blob download
	$ed->check([1,2,3],['redir'=>20]);
	$db=$ed->sg[1];
	$tb=$ed->sg[2];
	$nu=$ed->sg[3];
	$id=base64_decode($ed->sg[4]);
	if(empty($ed->sg[7])){
	$ph=$ed->sg[5];$nu1="";
	} else {
	$ph=$ed->sg[7];$nu1=" AND ".$ed->sg[5]."='".base64_decode($ed->sg[6])."'";
	}
	$q_ph=$ed->con->query("SELECT $ph FROM $tb WHERE $nu='$id'$nu1",true)->fetch();
	$r_ph=base64_decode($q_ph);
	$len=strlen($r_ph);
	if($len >=2 && $r_ph[0]==chr(0xff) && $r_ph[1]==chr(0xd8)) {$tp='image/jpeg';$xt='.jpg';}
	elseif($len >=3 && substr($r_ph,0,3)=='GIF') {$tp='image/gif';$xt='.gif';}
	elseif($len >=4 && substr($r_ph,0,4)=="\x89PNG") {$tp='image/png';$xt='.png';}
	else {$tp='application/octet-stream';$xt='.bin';$r_ph=$q_ph;}
	header("Content-type: $tp");
	header("Content-Length: $len");
	header("Content-Disposition: attachment; filename={$tb}-blob{$xt}");
	die($r_ph);
break;

case "40"://view
	if(!isset($ed->sg[2]) && !isset($ed->sg[3])) {//add
		$ed->check([1]);
		$db=$ed->sg[1];
		$r_uv=[1=>'',2=>''];
		if($ed->post('uv1','!e') && $ed->post('uv2','!e')) {
			$tb=$ed->sanitize($ed->post('uv1'));
			if(is_numeric(substr($tb,0,1))) $ed->redir("40/$db",['err'=>"Not a valid name"]);
			$exi=$ed->con->query("SELECT 1 FROM sqlite_master WHERE name='$tb'",true)->fetch();
			if($exi) $ed->redir("40/$db",['err'=>"This name exist"]);
			$vstat=$ed->post('uv2');
			$stat=$ed->con->exec($vstat);
			if($stat===false) $ed->redir("40/$db",['err'=>"Wrong statement"]);
			$v_cre=$ed->con->exec("CREATE VIEW $tb AS $vstat");
			if($v_cre===false) $ed->redir("40/$db",['err'=>"Can't create view"]);
			else $ed->redir("5/$db",['ok'=>"Successfully created"]);
		}
		echo $head.$ed->menu($db,'',2).$ed->form("40/$db");
		$b_lbl="Create";
	} else {//edit
		$ed->check([1,5]);
		$db=$ed->sg[1];$sp=$ed->sg[2];$ty=$ed->sg[3];
		$q_uv=$ed->con->query("SELECT sql FROM sqlite_master WHERE type='$ty' AND name='$sp'",true)->fetch();
		preg_match('/CREATE\sVIEW\s(.*)\s+AS\s+(.*)$/i',$q_uv,$r_uv);
		if($ed->post('uv1','!e') && $ed->post('uv2','!e')) {
			$tb=$ed->sanitize($ed->post('uv1'));
			if(is_numeric(substr($tb,0,1))) $ed->redir("5/$db",['err'=>"Not a valid name"]);
			$exi=$ed->con->query("SELECT 1 FROM sqlite_master WHERE name='$tb'",true)->fetch();
			if($exi && $tb!=$r_uv[1]) $ed->redir("5/$db",['err'=>"This name exist"]);
			$vstat=$ed->post('uv2');
			$stat=$ed->con->exec($vstat);
			if($stat===false) $ed->redir("5/$db",['err'=>"Wrong statement"]);
			$ed->con->exec("DROP $ty $sp");
			$ed->con->exec("CREATE VIEW $tb AS $vstat");
			$ed->redir("5/$db",['ok'=>"Successfully updated"]);
		}
		echo $head.$ed->menu($db,'',2,[$ty,$sp]).$ed->form("40/$db/$sp/$ty");
		$b_lbl="Edit";
	}
	echo "<table><tr><th colspan='2'>$b_lbl View</th></tr><tr><td>Name</td><td><input type='text' name='uv1' value='".$r_uv[1]."'/></td></tr><tr><td>Statement</td><td><textarea name='uv2'>".$r_uv[2]."</textarea></td></tr><tr><td colspan='2'><button type='submit'>Save</button></td></tr></table></form>";
break;

case "41"://trigger
	if(!isset($ed->sg[2]) && !isset($ed->sg[3])) {//add
		$ed->check([1]);
		$db=$ed->sg[1];
		$r_tge=[1=>'',2=>'',3=>'',4=>'',5=>''];
		if($ed->post('utg1','!e') && $ed->post('utg5','!e')) {
		$t_nm=$ed->sanitize($ed->post('utg1'));
		if(is_numeric(substr($t_nm,0,1))) $ed->redir("41/$db",['err'=>"Not a valid name"]);
		$q_tgcrt=$ed->con->exec("CREATE TRIGGER $t_nm ".$ed->post('utg2')." ".$ed->post('utg3')." ON ".$ed->post('utg4')." BEGIN ".$ed->post('utg5')."; END");
		if($q_tgcrt===false) $ed->redir("5/$db",['err'=>"Create trigger failed"]);
		else $ed->redir("5/$db",['ok'=>"Successfully created"]);
		}
		echo $head.$ed->menu($db,'',2).$ed->form("41/$db");
		$t_lbl="Create";
	} else {//edit
		$ed->check([1,5]);
		$db=$ed->sg[1];$sp=$ed->sg[2];$ty=$ed->sg[3];
		if($ed->post('utg1','!e') && $ed->post('utg5','!e')) {
			$utg1=$ed->sanitize($ed->post('utg1'));
			$utg2=$ed->post('utg2');$utg3=$ed->post('utg3');$utg4=$ed->post('utg4');$utg5=$ed->post('utg5');
			if(is_numeric(substr($utg1,0,1))) $ed->redir("5/$db",['err'=>"Not a valid name"]);
			$q_tgc=$ed->con->exec("CREATE TEMP TRIGGER temp_$utg1 $utg2 $utg3 ON $utg4 BEGIN $utg5; END");
			if($q_tgc===false) {
			$ed->redir("5/$db",['err'=>"Update trigger failed"]);
			} else {
			$ed->con->exec("DROP $ty $sp");
			$ed->con->exec("CREATE TRIGGER $utg1 $utg2 $utg3 ON $utg4 BEGIN $utg5; END");
			$ed->redir("5/$db",['ok'=>"Successfully updated"]);
			}
		}
		$q_tge=$ed->con->query("SELECT sql FROM sqlite_master WHERE type='$ty' AND name='$sp'",true)->fetch();
		preg_match('/CREATE\sTRIGGER\s(.*)\s+(.*)\s+(.*)\s+ON\s+(.*)\s+BEGIN\s+(.*);\s+END$/i',$q_tge,$r_tge);
		echo $head.$ed->menu($db,'',2,[$ty,$sp]).$ed->form("41/$db/$sp/$ty");
		$t_lbl="Edit";
	}

	echo "<table><tr><th colspan='2'>$t_lbl Trigger</th></tr><tr><td>Trigger Name</td><td><input type='text' name='utg1' value='".$r_tge[1]."'/></td></tr><tr><td>Table</td><td><select name='utg4'>";
	$q_tbs=$ed->con->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name")->fetch(1);
	foreach($q_tbs as $tgt) {
	$tgt=$tgt[0];
	if(!in_array($tgt,$deny)) echo "<option value='".$tgt."'".($r_tge[4]==$tgt ? " selected":"").">$tgt</option>";
	}
	echo "</select></td></tr><tr><td>Time</td><td><select name='utg2'>";
	$tm=['BEFORE','AFTER'];
	foreach($tm as $tn) echo "<option value='$tn'".($r_tge[2]==$tn?" selected":"").">$tn</option>";
	echo "</select></td></tr><tr><td>Event</td><td><select name='utg3'>";
	$evm=['INSERT','UPDATE','DELETE'];
	foreach($evm as $evn) echo "<option value='$evn'".($r_tge[3]==$evn?" selected":"").">$evn</option>";
	echo "</select></td></tr><tr><td>Definition</td><td><textarea name='utg5'>".$r_tge[5]."</textarea></td></tr><tr><td colspan='2'><button type='submit'>Save</button></td></tr></table></form>";
break;

case "49"://drop view,trigger
	$ed->check([1,5]);
	$ed->con->exec("DROP ".$ed->sg[3]." ".$ed->sg[2]);
	$ed->redir('5/'.$ed->sg[1],['ok'=>"Successfully dropped"]);
break;

case "50"://login
	if($ed->post('password','i')) {
	$_SESSION['ltoken']=base64_encode(md5($_SERVER['HTTP_USER_AGENT'].$ed->post('password')));
	$ed->redir();
	}
	session_unset();
	session_destroy();
	echo $head.$ed->menu('','',2).$ed->form("50")."<div class='dw'><h3>LOGIN</h3><div>Password<br/><input type='password' id='passwd' name='password' /></div><div><button type='submit'>Login</button></div></div></form>";
break;

case "51"://logout
	$ed->check();
	session_unset();
	session_destroy();
	$ed->redir();
break;

case "60"://info
	$ed->check();
	echo $head.$ed->menu(1,'',2)."<table><tr><th colspan='2'>INFO</th></tr>";
	$lty=DBT::$litetype[0];
	if(extension_loaded($lty)) {
	$v=SQLite3::version();
	$vv=$v['versionString'];
	} else {
	$dbv=new PDO('sqlite::memory:');
	$vv=$dbv->getAttribute(PDO::ATTR_SERVER_VERSION);
	unset($dbv);
	$lty=DBT::$litetype[1];
	}
	$q_var=['Extension'=>$lty,'SQLite'=>$vv,'PHP'=>PHP_VERSION,'Software'=>$_SERVER['SERVER_SOFTWARE']];
	foreach($q_var as $r_k=>$r_var) {
	$bg=($bg==1)?2:1;
	echo "<tr class='r c$bg'><td>$r_k</td><td>$r_var</td></tr>";
	}
	echo "</table>";
break;
}
$ed->con=null;
unset($_POST);
unset($_SESSION["ok"]);
unset($_SESSION["err"]);
?></div></div><div class="l1 ce"><a href="http://edmondsql.github.io">edmondsql</a></div>
<script src="<?=$js?>"></script>
<script>
$(function(){
$("#passwd").focus();
$("noscript").remove();
if($(".msg").text()!="") setTimeout(function(){$(".msg").fadeOut(900,function(){$(this).remove();});},7000);
$(".del").on("click",function(e){
e.preventDefault();
$(".msg").remove();
var but=$(this),hrf=but.prop("href");
$("body").append('<div class="msg"><div class="ok">Yes<\/div><div class="err">No<\/div><\/div>');
$(".msg .ok").on("click",function(){window.location=hrf;});
$(".msg .err").on("click",function(){$(".msg").remove();});
$(document).on("keyup",function(e){
if(e.which==89 || e.which==32) window.location=hrf;
if(e.which==27 || e.which==78) $(".msg").remove();
});
});
$(".msg").on("dblclick",function(){$(this).remove()});
$(".sort").sort();
});
$.fn.sort=function(){
var base=$(this),els=base.find("tr"),its=base.find(".handle"),drag=false,item;
its.on('mousedown',function(e){
base.css({"-webkit-touch-callout":"none","-webkit-user-select":"none","-moz-user-select":"none","-ms-user-select":"none","user-select":"none"});
if(e.which===1){item=$(this).closest("tr");els.addClass("opacity");item.addClass("drag");drag=true;}
});
its.on('mousemove',function(e){
var hoverItem=$(this).closest("tr"),overTop=false,overBottom=false,hoverItemHeight=hoverItem.offsetHeight,yPos=e.offsetY;
yPos<(hoverItemHeight/2)?overTop=true:overBottom=true;
if(item && hoverItem.parent().get(0)===item.parent().get(0)){
if(drag && overTop) hoverItem.before(item);
if(drag && overBottom) hoverItem.after(item);
}
$(document).on('mouseup',function(){
base.css({"-webkit-touch-callout":"auto","-webkit-user-select":"auto","-moz-user-select":"auto","-ms-user-select":"auto","user-select":"auto"});
els.removeClass("opacity");
item.removeClass("drag");
var reord=[];
base.find("tr").each(function(i,d){reord[i]=$(d).prop("id");});
drag=false;
if(els.map(function(){return this.id;}).get() !=reord)
$.ajax({type:"POST",url:"<?=$ed->path.'9/'.(empty($ed->sg[1])?"":$ed->sg[1].'/').(empty($ed->sg[2])?"":$ed->sg[2])?>",data:"reord="+reord,success:function(){$(this).load(location.reload())}});
});
});
}
function selectall(cb,lb){
var multi=document.getElementById(lb);
if(cb.checked) for(var i=0;i<multi.options.length;i++) multi.options[i].selected=true;
else multi.selectedIndex=-1;
}
function toggle(cb,el){
var cbox=document.getElementsByName(el);
for(var i=0;i<cbox.length;i++) cbox[i].checked=cb.checked;
if(el="fopt[]") opt();
}
function opt(){
var opt=document.getElementsByName("fopt[]"),ft=document.getElementsByName("ffmt[]"),from=2,to=opt.length,ch="";
for(var j=0;ft[j];++j){if(ft[j].checked) ch=ft[j].value;}
if(ch=="sql"){
for(var k=0;k<to;k++) opt[k].parentElement.style.display="block";
}else if(ch=="doc" || ch=="xml"){
for(var k=0;k<from;k++) opt[k].parentElement.style.display="block";
for(var k=2;k<to;k++) {opt[k].parentElement.style.display="none";opt[k].checked=false;}
}else{
for(var i=0;i<to;i++) opt[i].parentElement.style.display="none";
}
}
</script>
</body></html>