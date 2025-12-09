<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;

$wboardid=$_GET["id"]; 
echo ' 
<head>
  <title>Bootstrap Example</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
  <style>
  .carousel-control-prev, .carousel-control-next {
    background-color: lightgrey;
	z-index: 10;
	visibility: visible;
	width: 2%;
  overflow: hidden;
  }
.carousel-control-prev span,
.carousel-control-next span {
  height: 50%;
  line-height: 1;
}
  </style>
</head>
<body>

<!-- Carousel -->
<div id="demo" class="carousel slide" data-bs-ride="carousel" data-bs-interval="50000">

  <!-- Indicators/dots -->
  <div class="carousel-indicators">
    <button type="button" data-bs-target="#demo" data-bs-slide-to="0" class="active"></button>
    <button type="button" data-bs-target="#demo" data-bs-slide-to="1"></button>
    <button type="button" data-bs-target="#demo" data-bs-slide-to="2"></button>
    <button type="button" data-bs-target="#demo" data-bs-slide-to="3"></button>
    <button type="button" data-bs-target="#demo" data-bs-slide-to="4"></button>
    <button type="button" data-bs-target="#demo" data-bs-slide-to="5"></button>
  </div>
  
  <!-- The slideshow/carousel -->
  <div class="carousel-inner">
    <div class="carousel-item active">
      <div class="ratio ratio-16x9">
  <iframe scrolling="no"  src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$wboardid.'" title="YouTube video"></iframe>
      </div>
    </div>
    <div class="carousel-item">
	<div class="ratio ratio-16x9">
  <iframe scrolling="no" src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$wboardid.'" title="YouTube video"></iframe>
  </div>
    </div>
  <div class="carousel-item">
	<div class="ratio ratio-16x9">
  <iframe scrolling="no" src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$wboardid.'" title="YouTube video"></iframe>
  </div>
    </div>
    <div class="carousel-item">
	<div class="ratio ratio-16x9">
  <iframe scrolling="no" src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$wboardid.'" title="YouTube video"></iframe>
  </div>
    </div>
    <div class="carousel-item">
	<div class="ratio ratio-16x9">
  <iframe scrolling="no" src="https://mathking.kr/moodle/local/augmented_teacher/whiteboard/board_topic.php?id='.$wboardid.'" title="YouTube video"></iframe>
  </div>
    </div> 
  </div>
  
  <!-- Left and right controls/icons -->
  <button class="carousel-control-prev" type="button" data-bs-target="#demo" data-bs-slide="prev">
    <span class="carousel-control-prev-icon"></span>
  </button>
  <button class="carousel-control-next" type="button" data-bs-target="#demo" data-bs-slide="next">
    <span class="carousel-control-next-icon"></span>
  </button>
</div> 
 
</html>
';
?>