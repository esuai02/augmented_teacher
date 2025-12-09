<?php   

if($newtalk==NULL)$newtalk="How are you today ?";

$cjnchat=$DB->get_records_sql("SELECT * FROM mdl_abessi_cjntalk where userid='$userid' ORDER BY id DESC ");  
$result = json_decode(json_encode($cjnchat), True);
unset($value);
foreach($result as $value)
	{
  $chathistory.='<tr><td width=2%></td><td width=10%>'.$value['userid'].'</td><td>'.$value['text'].'</td></tr>';
	}  


$typenew= '<script>
  var text = "'.$newtalk.'";
  var lines = text.split("\n");
  var lineIndex = 0;
  var charIndex = 0;
  var speed = 50;
  var typingTimer;

  function typeLine() {
    var line = lines[lineIndex];
    if (charIndex < line.length) {
      document.getElementById("typing-text").innerHTML += line.charAt(charIndex);
      charIndex++;
      typingTimer = setTimeout(typeLine, speed);
    } else if (lineIndex < lines.length - 1) {
      document.getElementById("typing-text").innerHTML += "<br>";
      lineIndex++;
      charIndex = 0;
      typingTimer = setTimeout(typeLine, speed);
    }
  }
typeLine();
</script>
';

echo '
<!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
#typing-text {
  font-size: 24px;
  line-height: 1.5;
  margin-bottom: 10px;
}

@media (max-width: 767px) {
  /* Set font size for screens smaller than 768px (smartphones) */
  #typing-text {
    font-size: 30px;
  }
}

#typing-container {
    display: flex;
    flex-direction: row;
    justify-content: center;
    align-items: center;
    padding: 20px;
  }
  
  #teacher-image {
    width: 20%;
    padding: 20px;
  }
  
  #teacher-image img {
    width: 30%;
    height: auto;
    display: block;
    border-radius: 10px;
  }
  
  #typing-box {
    width: 90%;
    padding: 20px;
    border-radius: 10px;
    background-color: #f5f5f5;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    align-items: flex-start;
  }
  
  #typing-text {
    font-size: 24px;
    line-height: 1.5;
    margin-bottom: 10px;
  }
  
  #typing-cursor {
    width: 5px;
    height: 24px;
    background-color: #000;
    animation: cursor-blink 1s infinite;
  }
  
  @keyframes cursor-blink {
    0% {
      opacity: 0;
    }
    50% {
      opacity: 1;
    }
    100% {
      opacity: 0;
    }
  }
  .submit-button {
    background-color: #4CAF50; /* Green */
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 16px;
    border-radius: 20px;
    box-shadow: 0px 5px 10px rgba(0, 0, 0, 0.1);
    margin-top: 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  .submit-button:hover {
    background-color: #3e8e41; /* Dark green */
  }
</style>
</head>
<body>
<table width=100%><tr><td width=50%><div id="my-div">
'.$showpage.'
</div></td><td width=50%>
<div id="typing-container"><div id="teacher-image"><table align=center valign=bottom><tr><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637068285.png" alt="Teacher Image"></td></tr></table>
</div></div><br><table width=100% style="font-size:20px;">'.$chathistory.'</table>
<div id="typing-box">
<div id="typing-text"></div>
<div id="typing-cursor"></div>
</div>'.$typenew.'<table align=center>'.$buttons.'</table></td></tr></table></body></html>';

echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function() {
    // Attach click event handler to the button
    $("#my-button").click(function() {
      // Update the content of the div
      $("#my-div").html("This is the updated content of the div.");
    });
  });
</script>';
?>