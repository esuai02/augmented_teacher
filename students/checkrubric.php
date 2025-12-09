<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;

$eventid = $_POST['eventid'];
$userid = $_POST['userid'];
$checkimsi = $_POST['checkimsi'];
$timecreated=time();

include("rubrics.php");

$rubric= $DB->get_record_sql("SELECT * FROM mdl_abessi_rubric WHERE userid='$userid'   ORDER BY id  DESC  LIMIT 1 ");  
$timediff=$timecreated-$rubric->timecreated;

if($rubric->timecreated==NULL)
{
$rubric11=0;
$rubric12=0;
$rubric13=0;
$rubric14=0;
$rubric15=0;

$rubric21=0;
$rubric22=0;
$rubric23=0;
$rubric24=0;
$rubric25=0;

$rubric31=0;
$rubric32=0;
$rubric33=0;
$rubric34=0;
$rubric35=0;

$rubric41=0;
$rubric42=0;
$rubric43=0;
$rubric44=0;
$rubric45=0;

$rubric51=0;
$rubric52=0;
$rubric53=0;
$rubric54=0;
$rubric55=0;

$rubric61=0;
$rubric62=0;
$rubric63=0;
$rubric64=0;
$rubric65=0;
}else
{
$rubric11=$rubric->rubric11;
$rubric12=$rubric->rubric12;
$rubric13=$rubric->rubric13;
$rubric14=$rubric->rubric14;
$rubric15=$rubric->rubric15;

$rubric21=$rubric->rubric21;
$rubric22=$rubric->rubric22;
$rubric23=$rubric->rubric23;
$rubric24=$rubric->rubric24;
$rubric25=$rubric->rubric25;

$rubric31=$rubric->rubric31;
$rubric32=$rubric->rubric32;
$rubric33=$rubric->rubric33;
$rubric34=$rubric->rubric34;
$rubric35=$rubric->rubric35;

$rubric41=$rubric->rubric41;
$rubric42=$rubric->rubric42;
$rubric43=$rubric->rubric43;
$rubric44=$rubric->rubric44;
$rubric45=$rubric->rubric45;

$rubric51=$rubric->rubric51;
$rubric52=$rubric->rubric52;
$rubric53=$rubric->rubric53;
$rubric54=$rubric->rubric54;
$rubric55=$rubric->rubric55;

$rubric61=$rubric->rubric61;
$rubric62=$rubric->rubric62;
$rubric63=$rubric->rubric63;
$rubric64=$rubric->rubric64;
$rubric65=$rubric->rubric65;
}

if($timediff>43200)
	{
	$DB->execute("INSERT INTO {abessi_rubric} (userid,timecreated,rubric11,rubric12,rubric13,rubric14,rubric15,rubric21,rubric22,rubric23,rubric24,rubric25,rubric31,rubric32,rubric33,rubric34,rubric35,rubric41,rubric42,rubric43,rubric44,rubric45,rubric51,rubric52,rubric53,rubric54,rubric55,rubric61,rubric62,rubric63,rubric64,rubric65) 
	VALUES('$userid','$timecreated','$rubric11','$rubric12','$rubric13','$rubric14','$rubric15','$rubric21','$rubric22','$rubric23','$rubric24','$rubric25','$rubric31','$rubric32','$rubric33','$rubric34','$rubric35','$rubric41','$rubric42','$rubric43','$rubric44','$rubric45','$rubric51','$rubric52','$rubric53','$rubric54','$rubric55','$rubric61','$rubric62','$rubric63','$rubric64','$rubric65')");
	}



if($eventid==11) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric11='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='오답노트';
	$text=$comment11;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==12) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric12='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='오답노트';
	$text=$comment12;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==13) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric13='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='오답노트';
	$text=$comment13;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==14) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric14='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='오답노트';
	$text=$comment14;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==15) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric15='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='오답노트';
	$text=$comment15;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}

if($eventid==21) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric21='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='목표관리';
	$text=$comment21;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==22) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric22='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='목표관리';
	$text=$comment22;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==23) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric23='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='목표관리';
	$text=$comment23;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==24) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric24='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='목표관리';
	$text=$comment24;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==25)  
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric25='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='목표관리';
	$text=$comment25;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}

if($eventid==31) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric31='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='질의응답';
	$text=$comment31;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==32) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric32='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='질의응답';
	$text=$comment32;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	} 
if($eventid==33) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric33='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='질의응답';
	$text=$comment33;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==34) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric34='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='질의응답';
	$text=$comment34;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==35) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric35='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='질의응답';
	$text=$comment35;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}

if($eventid==41) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric41='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='분별레벨';
	$text=$comment41;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==42) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric42='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='분별레벨';
	$text=$comment42;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==43) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric43='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='분별레벨';
	$text=$comment43;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==44) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric44='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='분별레벨';
	$text=$comment44;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==45) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric45='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='분별레벨';
	$text=$comment45;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}

if($eventid==51) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric51='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='루틴레벨';
	$text=$comment51;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==52) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric52='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='루틴레벨';
	$text=$comment52;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==53) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric53='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='루틴레벨';
	$text=$comment53;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==54) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric54='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='루틴레벨';
	$text=$comment54;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==55) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric55='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='루틴레벨';
	$text=$comment55;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}

if($eventid==61) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric61='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='종합의견';
	$text=$comment61;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==62) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric62='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='종합의견';
	$text=$comment62;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	} 
if($eventid==63) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric63='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='종합의견';
	$text=$comment63;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==64) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric64='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='종합의견';
	$text=$comment64;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
if($eventid==65) 
	{
	$DB->execute("UPDATE {abessi_rubric} SET rubric65='$checkimsi', timecreated='$timecreated' WHERE userid='$userid' ORDER BY id  DESC  LIMIT 1 "); 
	$feedbacktype='종합의견';
	$text=$comment65;
	$contentstype=0;
	$contentsid=0;
	$DB->execute("INSERT INTO {abessi_feedbacklog} (type,feedback,contentstype,contentsid,userid,teacherid,timecreated ) VALUES('$feedbacktype','$text','$contentstype','$contentsid','$userid','$USER->id','$timecreated')");
	}
?>

