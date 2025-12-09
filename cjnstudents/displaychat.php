<?php   

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
    width: 60%;
    height: auto;
    display: block;
    border-radius: 10px;
  }
  
  #typing-box {
    width: 60%;
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
</style>
</head> ';

// show menu
echo ' <div id="typing-container">
<div id="teacher-image">
  <table align=center valign=bottom><tr><td><img src="https://mathking.kr/Contents/MATH%20MATRIX/MATH%20images/IMG/bessi21_1637068285.png" alt="Teacher Image"></td></tr></table></div>

  <div id="typing-box">
      <div id="typing-text"></div>
      <div id="typing-cursor"></div>
  </div>
</div>';

include("displaychat.php"); // show history

// typing new
echo '<script>
  var text = "'.$gpttalk.'";
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
 

</html>';
?>