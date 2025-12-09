<?php 
/////////////////////////////// code snippet ///////////////////////////////
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
 
 

$adjustment = '';
/////////////////////////// end of code snippet ///////////////////////////
echo '

 		<div class="quick-sidebar">
			<a href="#" class="close-quick-sidebar">
				<i class="flaticon-cross"></i>
			</a>
			<div class="quick-sidebar-wrapper">
				<ul class="nav nav-tabs nav-line nav-color-primary" role="tablist">
					<li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#messages" role="tab" aria-selected="false">활동 세부설정</a> </li>
					<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#tasks" role="tab" aria-selected="false">Tasks</a> </li>
				</ul>
				<div class="tab-content mt-3">
					<div class="tab-pane fade show active" id="messages" role="tabpanel">
						 
							<div class="quick-wrapper">
								<div class="quick-scroll scrollbar-outer">
									<div class="quick-content contact-content">
										<span class="category-title">  </span>
										<div class="contact-list">
											<div class="user"><a href="#"> '.$adjustment.'</a></div>
										</div>
									</div>
								</div>
							</div>
						 
						<div class="messages-wrapper">
							<div class="messages-title">
								<div class="user">
									<span class="name">학습 관리 시스템</span>
									<span class="last-active"> </span>
								</div>
								<button class="return">
									<i class="flaticon-left-arrow-3"></i>
								</button>
							</div>
							
				 
						</div>
					</div>
					<div class="tab-pane fade" id="tasks" role="tabpanel">
						<div class="tasks-wrapper">
							<div class="tasks-scroll scrollbar-outer">
								<div class="tasks-content">
									    
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
 
	</div>
 ';
?>