

<?php  
 
$gptlogs=$DB->get_records_sql("SELECT * FROM mdl_abessi_gptultratalk where contextid='$contextid' AND status NOT LIKE 'hide' ORDER BY id DESC ");  
$result = json_decode(json_encode($gptlogs), True);

unset($value);
foreach($result as $value)
	{
	if($value['gpttalk']==NULL)$qstnlist.='<tr><td>'.$value['question'].'</td><td>    </td></tr>';
	else $qnalist.='<tr><td>'.$value['question'].'</td><td>'.$value['gpttalk'].'</td></tr>';
	}  

?>

