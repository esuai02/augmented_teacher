<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
  
$wboardid=$_GET["id"]; 
$ncnt=$_GET["ncnt"];
$contentsid=$_GET["contentsid"]; 
$srcid=$_GET["srcid"]; 
$nstep=$_GET["nstep"];
if($ncnt==NULL) $ncnt=1;
$nextstep=$nstep+1;
if($nextstep==7)$nextstep=1;
$instruction=$DB->get_record_sql("SELECT * FROM mdl_abessi_cognitiveassessment WHERE contentsid='$contentsid' AND ncnt='$ncnt' ORDER BY id DESC LIMIT 1 "); 

$color1='lightgrey';$color2='lightgrey';$color3='lightgrey';$color4='lightgrey';$color5='lightgrey';$color6='lightgrey';
if($nstep==1){$color6='purple';$instructiontext=$instruction->step1;}
elseif($nstep==2){$color1='purple';$instructiontext=$instruction->step2;}
elseif($nstep==3){$color2='purple';$instructiontext=$instruction->step3;}
elseif($nstep==4){$color3='purple';$instructiontext=$instruction->step4;}
elseif($nstep==5){$color4='purple';$instructiontext=$instruction->step5;}
elseif($nstep==6){$color5='purple';$instructiontext=$instruction->step6;}

echo ' 
<head>  
	<title>인지 플라이휠</title>
	<style>
		#container {
			float: right;
			width: 30%;
      height: 400px;
		}
    #rotbtn {
			margin-top: 0px;
      margin-right: 50px;
		}	
    
		iframe {
			float: left;
			width: 75%;
      height:100%
		}
	</style>
</head>
<body>
 
<div class="carousel-item">
<div class="ratio ratio-16x9">
<iframe scrolling="no" src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_flywheel.php?id='.$wboardid.'&srcid='.$srcid.'&contentsid='.$contentsid.'&nstep='.$nstep.'"></iframe>
</div>
</div>
<div id="container">
<div id="circle1" class="circle"></div>
<div id="circle2" class="circle"></div>
<div id="circle3" class="circle"></div>
<div id="circle4" class="circle"></div>
<div id="circle5" class="circle"></div>
<div id="circle6" class="circle"></div>
</div>
<div id="rotbtn">
<a href="https://mathking.kr/moodle/local/augmented_teacher/books/cgntvflywheel.php?id='.$srcid.'_user'.$USER->id.'nstep'.$nextstep.'&srcid='.$srcid.'&contentsid='.$contentsid.'&ncnt='.$ncnt.'&nstep='.$nextstep.'"><button id="rotate-button" style="font-size:20px;width:60px;">다음</button></a><hr>
</div>
<div id="typing-box">
  <div id="typing-text"></div>
  <div id="typing-cursor"></div>
</div>

</body>
 
<style>

#typing-container {
  display: flex;
  flex-direction: row;
  justify-content: center;
  align-items: center;
  padding: 10px;
}

#teacher-image {
  width: 20%;
  padding: 20px;
}

#teacher-image img {
  width: 60%;
  height: auto;
  display: block;
  border-radius: 10px;
}

#typing-box {
  width: 20%;
  padding: 10px;
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
#typing-text {
  font-size: 24px;
  line-height: 1.5;
  margin-left:10px;
  margin-top: 5px;
}

@media (max-width: 767px) {
  /* Set font size for screens smaller than 768px (smartphones) */
  #typing-text {
    font-size: 30px;
  }
}

#container {
  position: relative;
  width: 24%;
  height: 24%;
  background-color:white;
}

.circle {
  position: absolute;
  top: 35%;
  left: 40%;
  transform: translate(50%, 50%);
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: 2px solid white;
}

#circle1 {
  transform: rotate(0deg) translateX(70px) rotate(0deg);
  background-color: '.$color1.';
}

#circle2 {
  transform: rotate(60deg) translateX(70px) rotate(0deg);
  background-color: '.$color2.';
}

#circle3 {
  transform: rotate(120deg) translateX(70px) rotate(0deg);
  background-color: '.$color3.';
}

#circle4 {
  transform: rotate(180deg) translateX(70px) rotate(0deg);
  background-color: '.$color4.';
}

#circle5 {
  transform: rotate(240deg) translateX(70px) rotate(0deg);
  background-color: '.$color5.';
}

#circle6 {
  transform: rotate(300deg) translateX(70px) rotate(0deg);
  background-color: '.$color6.';
 

  </style>
 
<script>
const button = document.getElementById("rotate-button");
const circles = document.querySelectorAll(".circle");
let angle = 0;

button.addEventListener("click", function() {
  angle += 60;
  circles.forEach((circle, index) => {
    circle.style.transform = `rotate(${angle + index * 60}deg) translateX(70px) rotate(-${angle + index * 60}deg)`;
  });

});

var text = "'.$instructiontext.'";
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
  }else {
    // Add a line break after typing out the last line of text
    document.getElementById("typing-text").innerHTML += "<br>";
  }
}
typeLine(); 
</script>
 
';
?>