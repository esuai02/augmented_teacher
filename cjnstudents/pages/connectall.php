<?php  
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB,$USER;
 
// 자동메뉴 구성
$userid=$_GET["userid"]; 
 
echo '
<div class="shortcut-menu-grid">
  <div class="shortcut-menu">Shortcut 1</div>
  <div class="shortcut-menu">Shortcut 2</div>
  <div class="shortcut-menu">Shortcut 3</div>
  <div class="shortcut-menu">Shortcut 4</div>
  <div class="shortcut-menu">Shortcut 5</div>
</div>
';

echo '<style>
.shortcut-menu-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    grid-gap: 10px;
  }
  
  .shortcut-menu {
    background-color: #eee;
    padding: 10px;
  }
</style>';
?>