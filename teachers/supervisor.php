<?php 
include_once("/home/moodle/public_html/moodle/config.php"); 
global $DB, $USER;
include("navbar.php");
 echo ' 
 
		<div class="main-panel">
			<div class="content">
				<div class="container-fluid">
					<div class="page-header">
						<h4 class="page-title">Dashboard</h4>
						<div class="btn-group btn-group-page-header ml-auto">
							<button type="button" class="btn btn-light btn-round btn-page-header-dropdown dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
								<i class="la la-ellipsis-h"></i>
							</button>
							<div class="dropdown-menu">
								<div class="arrow"></div>
								<a class="dropdown-item" href="#">Action</a>
								<a class="dropdown-item" href="#">Another action</a>
								<a class="dropdown-item" href="#">Something else here</a>
								<div class="dropdown-divider"></div>
								<a class="dropdown-item" href="#">Separated link</a>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-primary card-round">
								<div class="card-body ">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
												<i class="flaticon-users"></i>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<p class="card-category">Visitors</p>
												<h4 class="card-title">1,294</h4>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-info card-round">
								<div class="card-body">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
												<i class="flaticon-interface-6"></i>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<p class="card-category">Subscribers</p>
												<h4 class="card-title">1303</h4>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-success card-round">
								<div class="card-body ">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
												<i class="flaticon-graph"></i>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<p class="card-category">Sales</p>
												<h4 class="card-title">$ 1,345</h4>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-sm-6 col-md-3">
							<div class="card card-stats card-secondary card-round">
								<div class="card-body ">
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
												<i class="flaticon-success"></i>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<p class="card-category">Order</p>
												<h4 class="card-title">576</h4>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-8">
							<div class="card">
								<div class="card-header">
									<div class="card-head-row">
										<div class="card-title">Users Statistics</div>
										<div class="card-tools">
											<a href="#" class="btn btn-info btn-border btn-round btn-sm mr-2">
												<span class="btn-label">
													<i class="la la-pencil"></i>
												</span>
												Export
											</a>
											<a href="#" class="btn btn-info btn-border btn-round btn-sm">
												<span class="btn-label">
													<i class="la la-print"></i>
												</span>
												Print
											</a>
										</div>
									</div>
								</div>
								<div class="card-body">
									<div class="chart-container">
										<canvas id="statisticsChart"></canvas>
									</div>
									<div id="myChartLegend"></div>
								</div>
							</div>
						</div>
						<div class="col-md-4">
							<div class="card">
								<div class="card-header">
									<h4 class="card-title">Users Percentage</h4>
									<p class="card-category">
									Users percentage this month</p>
								</div>
								<div class="card-body">
									<div class="chart-container">
										<canvas id="usersChart"></canvas>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row row-card-no-pd">
						<div class="col-md-12">
							<div class="card">
								<div class="card-header">
									<div class="card-head-row">
										<h4 class="card-title">Users Geolocation</h4>
										<div class="card-tools">
											<a href="#" class="btn btn-primary btn-icon-only"><span class="icon flaticon-down-arrow"></span></a>
											<a href="#" class="btn btn-primary btn-icon-only"><span class="icon flaticon-repeat"></span></a>
											<a href="#" class="btn btn-primary btn-icon-only"><span class="icon flaticon-cross"></span></a>
										</div>
									</div>
									<p class="card-category">
									Map of the distribution of users around the world</p>
								</div>
								<div class="card-body">
									<div class="row">
										<div class="col-md-6">
											<div class="table-responsive table-hover table-sales">
												<table class="table">
													<tbody>
														<tr>
															<td>
																<div class="flag">
																	<img src="../assets/img/flags/id.png" alt="indonesia">
																</div>
															</td>
															<td>Indonesia</td>
															<td class="text-right">
																2.320
															</td>
															<td class="text-right">
																42.18%
															</td>
														</tr>
														<tr>
															<td>
																<div class="flag">
																	<img src="../assets/img/flags/us.png" alt="united states">
																</div>
															</td>
															<td>USA</td>
															<td class="text-right">
																240
															</td>
															<td class="text-right">
																4.36%
															</td>
														</tr>
														<tr>
															<td>
																<div class="flag">
																	<img src="../assets/img/flags/au.png" alt="australia">
																</div>
															</td>
															<td>Australia</td>
															<td class="text-right">
																119
															</td>
															<td class="text-right">
																2.16%
															</td>
														</tr>
														<tr>
															<td>
																<div class="flag">
																	<img src="../assets/img/flags/ru.png" alt="russia">
																</div>
															</td>
															<td>Russia</td>
															<td class="text-right">
																1.081
															</td>
															<td class="text-right">
																19.65%
															</td>
														</tr>
														<tr>
															<td>
																<div class="flag">
																	<img src="../assets/img/flags/cn.png" alt="china">
																</div>
															</td>
															<td>China</td>
															<td class="text-right">
																1.100
															</td>
															<td class="text-right">
																20%
															</td>
														</tr>
														<tr>
															<td>
																<div class="flag">
																	<img src="../assets/img/flags/br.png" alt="brazil">
																</div>
															</td>
															<td>Brasil</td>
															<td class="text-right">
																640
															</td>
															<td class="text-right">
																11.63%
															</td>
														</tr>
													</tbody>
												</table>
											</div>
										</div>
										<div class="col-md-6">
											<div class="mapcontainer">
												<div id="map-example" class="vmap"></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row row-card-no-pd">
						<div class="col-md-4 mt-3 mb-3">
							<div class="card">
								<div class="card-body">
									<p class="fw-mediumbold mt-1">My Balance</p>
									<h4 class="text-primary"><b>$ 3,018</b></h4>
									<a href="#" class="btn btn-primary btn-full text-left mt-3 mb-3"><i class="la la-plus"></i> Add Balance</a>
								</div>
								<div class="card-footer mt-auto">
									<ul class="nav">
										<li class="nav-item"><a class="btn btn-default btn-link" href="#"><i class="la la-history"></i> History</a></li>
										<li class="nav-item ml-auto"><a class="btn btn-default btn-link" href="#"><i class="la la-refresh"></i> Refresh</a></li>
									</ul>
								</div>
							</div>
						</div>
						<div class="col-md-5 mt-3 mb-3">
							<div class="card">
								<div class="card-body">
									<div class="progress-card">
										<div class="d-flex justify-content-between mb-1">
											<span class="text-muted">Profit</span>
											<span class="text-muted fw-bold"> $3K</span>
										</div>
										<div class="progress mb-2" style="height: 7px;">
											<div class="progress-bar bg-success" role="progressbar" style="width: 78%" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="78%"></div>
										</div>
									</div>
									<div class="progress-card">
										<div class="d-flex justify-content-between mb-1">
											<span class="text-muted">Orders</span>
											<span class="text-muted fw-bold"> 576</span>
										</div>
										<div class="progress mb-2" style="height: 7px;">
											<div class="progress-bar bg-info" role="progressbar" style="width: 65%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="65%"></div>
										</div>
									</div>
									<div class="progress-card">
										<div class="d-flex justify-content-between mb-1">
											<span class="text-muted">Tasks Complete</span>
											<span class="text-muted fw-bold"> 70%</span>
										</div>
										<div class="progress mb-2" style="height: 7px;">
											<div class="progress-bar bg-primary" role="progressbar" style="width: 70%" aria-valuenow="70" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="70%"></div>
										</div>
									</div>
									<div class="progress-card">
										<div class="d-flex justify-content-between mb-1">
											<span class="text-muted">Open Rate</span>
											<span class="text-muted fw-bold"> 60%</span>
										</div>
										<div class="progress mb-2" style="height: 7px;">
											<div class="progress-bar bg-warning" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="60%"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="col-md-3 mt-3 mb-3">
							<div class="card card-stats">
								<div class="card-body">
									<p class="fw-mediumbold mt-1">Statistic</p>
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center icon-warning">
												<i class="la la-pie-chart text-warning"></i>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<p class="card-category">Number</p>
												<h4 class="card-title">150GB</h4>
											</div>
										</div>
									</div>
									<hr/>
									<div class="row">
										<div class="col-5">
											<div class="icon-big text-center">
												<i class="la la-heart-o text-primary"></i>
											</div>
										</div>
										<div class="col-7 col-stats">
											<div class="numbers">
												<p class="card-category">Followers</p>
												<h4 class="card-title">+45K</h4>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<div class="col-md-5">
							<div class="card card-tasks">
								<div class="card-header ">
									<div class="card-head-row">
										<h4 class="card-title">Tasks</h4>
										<div class="card-tools">
											<ul class="nav nav-pills nav-secondary nav-pills-no-bd nav-sm" id="pills-tab" role="tablist">
												<li class="nav-item">
													<a class="nav-link active" id="pills-home" data-toggle="pill" href="#pills-home" role="tab" aria-selected="true">Today</a>
												</li>
												<li class="nav-item">
													<a class="nav-link" id="pills-profile" data-toggle="pill" href="#pills-profile" role="tab" aria-selected="false">Week</a>
												</li>
												<li class="nav-item">
													<a class="nav-link" id="pills-contact" data-toggle="pill" href="#pills-contact" role="tab" aria-selected="false">Month</a>
												</li>
											</ul>
										</div>
									</div>
								</div>
								<div class="card-body ">
									<div class="table-full-width">
										<table class="table">
											<thead>
												<tr>
													<th>
														<div class="form-check">
															<label class="form-check-label">
																<input class="form-check-input  select-all-checkbox" type="checkbox" data-select="checkbox" data-target=".task-select">
																<span class="form-check-sign"></span>
															</label>
														</div>
													</th>
													<th>Task</th>
													<th class="text-center">Action</th>
												</tr>
											</thead>
											<tbody>
												<tr>
													<td>
														<div class="form-check">
															<label class="form-check-label">
																<input class="form-check-input task-select" type="checkbox">
																<span class="form-check-sign"></span>
															</label>
														</div>
													</td>
													<td>Planning new project structure</td>
													<td class="td-actions text-center">
														<div class="form-button-action">
															<button type="button" data-toggle="tooltip" title="Edit Task" class="btn btn-link btn-primary">
																<i class="la la-edit"></i>
															</button>
															<button type="button" data-toggle="tooltip" title="Remove" class="btn btn-link btn-danger">
																<i class="la la-times"></i>
															</button>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check">
															<label class="form-check-label">
																<input class="form-check-input task-select" type="checkbox">
																<span class="form-check-sign"></span>
															</label>
														</div>
													</td>
													<td>Update Fonts</td>
													<td class="td-actions text-center">
														<div class="form-button-action">
															<button type="button" data-toggle="tooltip" title="Edit Task" class="btn btn-link btn-primary">
																<i class="la la-edit"></i>
															</button>
															<button type="button" data-toggle="tooltip" title="Remove" class="btn btn-link btn-danger">
																<i class="la la-times"></i>
															</button>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check">
															<label class="form-check-label">
																<input class="form-check-input task-select" type="checkbox">
																<span class="form-check-sign"></span>
															</label>
														</div>
													</td>
													<td>Add new Post
													</td>
													<td class="td-actions text-center">
														<div class="form-button-action">
															<button type="button" data-toggle="tooltip" title="Edit Task" class="btn btn-link btn-primary">
																<i class="la la-edit"></i>
															</button>
															<button type="button" data-toggle="tooltip" title="Remove" class="btn btn-link btn-danger">
																<i class="la la-times"></i>
															</button>
														</div>
													</td>
												</tr>
												<tr>
													<td>
														<div class="form-check">
															<label class="form-check-label">
																<input class="form-check-input task-select" type="checkbox">
																<span class="form-check-sign"></span>
															</label>
														</div>
													</td>
													<td>Finalise the design proposal</td>
													<td class="td-actions text-center">
														<div class="form-button-action">
															<button type="button" data-toggle="tooltip" title="Edit Task" class="btn btn-link btn-primary">
																<i class="la la-edit"></i>
															</button>
															<button type="button" data-toggle="tooltip" title="Remove" class="btn btn-link btn-danger">
																<i class="la la-times"></i>
															</button>
														</div>
													</td>
												</tr>
											</tbody>
										</table>
									</div>
								</div>
								<div class="card-footer ">
									<div class="stats">
										<i class="now-ui-icons loader_refresh spin"></i> Updated 3 minutes ago
									</div>
								</div>
							</div>
						</div>

						<div class="col-md-3">
							<div class="card">
								<div class="card-header">
									<h4 class="card-title">Task Progress</h4>
								</div>
								<div class="card-body">
									<div id="task-complete" class="chart-circle mt-4 mb-3"></div>
								</div>
								<div class="card-footer">
									<div class="legend"><i class="la la-circle text-primary"></i> Completed</div>
								</div>
							</div>
						</div>

						<div class="col-md-4">
							<div class="card card-round">
								<div class="card-body">
									<div class="card-title">Suggested People</div>
									<div class="card-list">
										<div class="item-list">
											<img src="../assets/img/jm_denis.jpg" alt="denis" class="small-pic">
											<div class="info-user ml-3">
												<div class="username">Jimmy Denis</div>
												<div class="status">Graphic Designer</div>
											</div>
											<a href="#" class="btn btn-add">
												<i class="la la-plus"></i>
											</a>
										</div>
										<div class="item-list">
											<img src="../assets/img/chadengle.jpg" alt="chad" class="small-pic">
											<div class="info-user ml-3">
												<div class="username">Chad</div>
												<div class="status">CEO Zeleaf</div>
											</div>
											<a href="#" class="btn btn-add">
												<i class="la la-plus"></i>
											</a>
										</div>
										<div class="item-list">
											<img src="../assets/img/mlane.jpg" alt="john doe" class="small-pic">
											<div class="info-user ml-3">
												<div class="username">Jhon doe</div>
												<div class="status">Content Writer</div>
											</div>
											<a href="#" class="btn btn-add">
												<i class="la la-plus"></i>
											</a>
										</div>
										<div class="item-list">
											<img src="../assets/img/talha.jpg" alt="talha" class="small-pic">
											<div class="info-user ml-3">
												<div class="username">Talha</div>
												<div class="status">Front End Designer</div>
											</div>
											<a href="#" class="btn btn-add">
												<i class="la la-plus"></i>
											</a>
										</div>
										<div class="item-list">
											<img src="../assets/img/sauro.jpg" alt="sauro" class="small-pic">
											<div class="info-user ml-3">
												<div class="username">Sauro</div>
												<div class="status">Back End Developer</div>
											</div>
											<a href="#" class="btn btn-add">
												<i class="la la-plus"></i>
											</a>
										</div>
										<div class="item-list">
											<img src="../assets/img/arashmil.jpg" alt="arash mil" class="small-pic">
											<div class="info-user ml-3">
												<div class="username">Arash Mil</div>
												<div class="status">Content Writer</div>
											</div>
											<a href="#" class="btn btn-add">
												<i class="la la-plus"></i>
											</a>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
		</div>
		<div class="quick-sidebar">
			<a href="#" class="close-quick-sidebar">
				<i class="flaticon-cross"></i>
			</a>
			<div class="quick-sidebar-wrapper">
				<ul class="nav nav-tabs nav-line nav-color-primary" role="tablist">
					<li class="nav-item"> <a class="nav-link active show" data-toggle="tab" href="#messages" role="tab" aria-selected="true">Messages</a> </li>
					<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#tasks" role="tab" aria-selected="false">Tasks</a> </li>
					<li class="nav-item"> <a class="nav-link" data-toggle="tab" href="#settings" role="tab" aria-selected="false">Settings</a> </li>
				</ul>
				<div class="tab-content mt-3">
					<div class="tab-chat tab-pane fade show active" id="messages" role="tabpanel">
						<div class="messages-contact">
							<div class="quick-wrapper">
								<div class="quick-scroll scrollbar-outer">
									<div class="quick-content contact-content">
										<span class="category-title mt-0">Recent</span>
										<div class="contact-list contact-list-recent">
											<div class="user">
												<a href="#">
													<div class="user-image">
														<img src="../assets/img/jm_denis.jpg" alt="denis">
														<span class="status online"></span>
													</div>
													<div class="user-data">
														<span class="name">Jimmy Denis</span>
														<span class="message">How are you ?</span>
													</div>
												</a>
											</div>
											<div class="user">
												<a href="#">
													<div class="user-image">
														<img src="../assets/img/chadengle.jpg" alt="chad">
														<span class="status away"></span>
													</div>
													<div class="user-data">
														<span class="name">Chad</span>
														<span class="message">Ok, Thanks !</span>
													</div>
												</a>
											</div>
											<div class="user">
												<a href="#">
													<div class="user-image">
														<img src="../assets/img/mlane.jpg" alt="john doe">
														<span class="status offline"></span>
													</div>
													<div class="user-data">
														<span class="name">John Doe</span>
														<span class="message">Ready for the meeting today with...</span>
													</div>
												</a>
											</div>
										</div>
										<span class="category-title">Contacts</span>
										<div class="contact-list">
											<div class="user">
												<a href="#">
													<div class="user-image">
														<img src="../assets/img/jm_denis.jpg" alt="denis">
														<span class="status"></span>
													</div>
													<div class="user-data2">
														<span class="name">Jimmy Denis</span>
													</div>
												</a>
											</div>
											<div class="user">
												<a href="#">
													<div class="user-image">
														<img src="../assets/img/chadengle.jpg" alt="chad">
														<span class="status away"></span>
													</div>
													<div class="user-data2">
														<span class="name">Chad</span>
													</div>
												</a>
											</div>
											<div class="user">
												<a href="#">
													<div class="user-image">
														<img src="../assets/img/talha.jpg" alt="talha">
														<span class="status offline"></span>
													</div>
													<div class="user-data2">
														<span class="name">Talha</span>
													</div>
												</a>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="messages-wrapper">
							<div class="messages-title">
								<div class="user">
									<img src="../assets/img/chadengle.jpg" alt="chad">
									<span class="name">Chad</span>
									<span class="last-active">Active 2h ago</span>
								</div>
								<button class="return">
									<i class="flaticon-left-arrow-3"></i>
								</button>
							</div>
							<div class="messages-body messages-scroll scrollbar-outer">
								<div class="message-content-wrapper">
									<div class="message message-in">
										<div class="message-pic">
											<img src="../assets/img/chadengle.jpg" alt="chad">
										</div>
										<div class="message-body">
											<div class="message-content">
												<div class="name">Chad</div>
												<div class="content">Hello, Rian</div>
											</div>
										</div>
									</div>
								</div>
								<div class="message-content-wrapper">
									<div class="message message-out">
										<div class="message-body">
											<div class="message-content">
												<div class="content">
													Hello, Chad
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="message-content-wrapper">
									<div class="message message-in">
										<div class="message-pic">
											<img src="../assets/img/chadengle.jpg" alt="chad">
										</div>
										<div class="message-body">
											<div class="message-content">
												<div class="name">Chad</div>
												<div class="content">
													When is the deadline of the project we are working on ?
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="message-content-wrapper">
									<div class="message message-out">
										<div class="message-body">
											<div class="message-content">
												<div class="content">
													The deadline is about 2 months away
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="message-content-wrapper">
									<div class="message message-in">
										<div class="message-pic">
											<img src="../assets/img/chadengle.jpg" alt="chad">
										</div>
										<div class="message-body">
											<div class="message-content">
												<div class="name">Chad</div>
												<div class="content">
													Ok, Thanks !
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
							<div class="messages-form">
								<div class="messages-form-control">
									<input type="text" placeholder="Type here" class="form-control input-pill input-solid message-input">
								</div>
								<div class="messages-form-tool">
									<a href="#" class="attachment">
										<i class="flaticon-file"></i>
									</a>
								</div>
							</div>
						</div>
					</div>
					<div class="tab-pane fade" id="tasks" role="tabpanel">
						<div class="tasks-wrapper">
							<div class="tasks-scroll scrollbar-outer">
								<div class="tasks-content">
									<span class="category-title mt-0">Today</span>
									<ul class="tasks-list">
										<li>
											<label class="custom-checkbox custom-control checkbox-secondary">
												<input type="checkbox" checked="" class="custom-control-input"><span class="custom-control-label">Planning new project structure</span>
												<span class="task-action">
													<a href="#" class="link text-danger">
														<i class="flaticon-interface-5"></i>
													</a>
												</span>
											</label>
										</li>
										<li>
											<label class="custom-checkbox custom-control checkbox-secondary">
												<input type="checkbox" class="custom-control-input"><span class="custom-control-label">Create the main structure							</span>
												<span class="task-action">
													<a href="#" class="link text-danger">
														<i class="flaticon-interface-5"></i>
													</a>
												</span>
											</label>
										</li>
										<li>
											<label class="custom-checkbox custom-control checkbox-secondary">
												<input type="checkbox" class="custom-control-input"><span class="custom-control-label">Add new Post</span>
												<span class="task-action">
													<a href="#" class="link text-danger">
														<i class="flaticon-interface-5"></i>
													</a>
												</span>
											</label>
										</li>
										<li>
											<label class="custom-checkbox custom-control checkbox-secondary">
												<input type="checkbox" class="custom-control-input"><span class="custom-control-label">Finalise the design proposal</span>
												<span class="task-action">
													<a href="#" class="link text-danger">
														<i class="flaticon-interface-5"></i>
													</a>
												</span>
											</label>
										</li>
									</ul>

									<span class="category-title">Tomorrow</span>
									<ul class="tasks-list">
										<li>
											<label class="custom-checkbox custom-control checkbox-secondary">
												<input type="checkbox" class="custom-control-input"><span class="custom-control-label">Initialize the project							</span>
												<span class="task-action">
													<a href="#" class="link text-danger">
														<i class="flaticon-interface-5"></i>
													</a>
												</span>
											</label>
										</li>
										<li>
											<label class="custom-checkbox custom-control checkbox-secondary">
												<input type="checkbox" class="custom-control-input"><span class="custom-control-label">Create the main structure							</span>
												<span class="task-action">
													<a href="#" class="link text-danger">
														<i class="flaticon-interface-5"></i>
													</a>
												</span>
											</label>
										</li>
										<li>
											<label class="custom-checkbox custom-control checkbox-secondary">
												<input type="checkbox" class="custom-control-input"><span class="custom-control-label">Updates changes to GitHub							</span>
												<span class="task-action">
													<a href="#" class="link text-danger">
														<i class="flaticon-interface-5"></i>
													</a>
												</span>
											</label>
										</li>
										<li>
											<label class="custom-checkbox custom-control checkbox-secondary">
												<input type="checkbox" class="custom-control-input"><span title="This task is too long to be displayed in a normal space!" class="custom-control-label">This task is too long to be displayed in a normal space!				</span>
												<span class="task-action">
													<a href="#" class="link text-danger">
														<i class="flaticon-interface-5"></i>
													</a>
												</span>
											</label>
										</li>
									</ul>

									<div class="mt-3">
										<div class="btn btn-primary btn-rounded btn-sm">
											<span class="btn-label">
												<i class="la la-plus"></i>
											</span>
											Add Task
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="tab-pane fade" id="settings" role="tabpanel">
						<div class="quick-wrapper settings-wrapper">
							<div class="quick-scroll scrollbar-outer">
								<div class="quick-content settings-content">

									<span class="category-title mt-0">General Settings</span>
									<ul class="settings-list">
										<li>
											<span class="item-label">Enable Notifications</span>
											<div class="item-control">
												<input type="checkbox" checked data-toggle="toggle" data-onstyle="primary" data-style="btn-round">
											</div>
										</li>
										<li>
											<span class="item-label">Signin with social media</span>
											<div class="item-control">
												<input type="checkbox" data-toggle="toggle" data-onstyle="primary" data-style="btn-round">
											</div>
										</li>
										<li>
											<span class="item-label">Backup storage</span>
											<div class="item-control">
												<input type="checkbox" data-toggle="toggle" data-onstyle="primary" data-style="btn-round">
											</div>
										</li>
										<li>
											<span class="item-label">SMS Alert</span>
											<div class="item-control">
												<input type="checkbox" checked data-toggle="toggle" data-onstyle="primary" data-style="btn-round">
											</div>
										</li>
									</ul>

									<span class="category-title mt-0">Notifications</span>
									<ul class="settings-list">
										<li>
											<span class="item-label">Email Notifications</span>
											<div class="item-control">
												<input type="checkbox" checked data-toggle="toggle" data-onstyle="primary" data-style="btn-round">
											</div>
										</li>
										<li>
											<span class="item-label">New Comments</span>
											<div class="item-control">
												<input type="checkbox" checked data-toggle="toggle" data-onstyle="primary" data-style="btn-round">
											</div>
										</li>
										<li>
											<span class="item-label">Chat Messages</span>
											<div class="item-control">
												<input type="checkbox" checked data-toggle="toggle" data-onstyle="primary" data-style="btn-round">
											</div>
										</li>
										<li>
											<span class="item-label">Project Updates</span>
											<div class="item-control">
												<input type="checkbox" data-toggle="toggle" data-onstyle="primary" data-style="btn-round">
											</div>
										</li>
										<li>
											<span class="item-label">New Tasks</span>
											<div class="item-control">
												<input type="checkbox" checked data-toggle="toggle" data-onstyle="primary" data-style="btn-round">
											</div>
										</li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- Custom template | don"t include it in your project! -->
		<div class="custom-template">
			<div class="title">Settings</div>
			<div class="custom-content">
				<div class="switcher">
					<div class="switch-block">
						<h4>Topbar</h4>
						<div class="btnSwitch">
							<button type="button" class="changeTopBarColor" data-color="default"></button>
							<button type="button" class="changeTopBarColor" data-color="black"></button>
							<button type="button" class="changeTopBarColor" data-color="dark"></button>
							<button type="button" class="selected changeTopBarColor" data-color="blue"></button>
							<button type="button" class="changeTopBarColor" data-color="purple"></button>
							<button type="button" class="changeTopBarColor" data-color="light-blue"></button>
							<button type="button" class="changeTopBarColor" data-color="green"></button>
							<button type="button" class="changeTopBarColor" data-color="orange"></button>
							<button type="button" class="changeTopBarColor" data-color="red"></button>
						</div>
					</div>
					<div class="switch-block">
						<h4>Logo Header</h4>
						<div class="btnSwitch">
							<button type="button" class="selected changeLogoHeaderColor" data-color="default"></button>
							<button type="button" class="changeLogoHeaderColor" data-color="black"></button>
							<button type="button" class="changeLogoHeaderColor" data-color="dark"></button>
							<button type="button" class="changeLogoHeaderColor" data-color="blue"></button>
							<button type="button" class="changeLogoHeaderColor" data-color="purple"></button>
							<button type="button" class="changeLogoHeaderColor" data-color="light-blue"></button>
							<button type="button" class="changeLogoHeaderColor" data-color="green"></button>
							<button type="button" class="changeLogoHeaderColor" data-color="orange"></button>
							<button type="button" class="changeLogoHeaderColor" data-color="red"></button>
						</div>
					</div>
					<div class="switch-block">
						<h4>Sidebar</h4>
						<div class="btnSwitch">
							<button type="button" class="selected changeSidebarColor" data-color="default"></button>
							<button type="button" class="changeSidebarColor" data-color="black"></button>
							<button type="button" class="changeSidebarColor" data-color="dark"></button>
							<button type="button" class="changeSidebarColor" data-color="blue"></button>
							<button type="button" class="changeSidebarColor" data-color="purple"></button>
							<button type="button" class="changeSidebarColor" data-color="light-blue"></button>
							<button type="button" class="changeSidebarColor" data-color="green"></button>
							<button type="button" class="changeSidebarColor" data-color="orange"></button>
							<button type="button" class="changeSidebarColor" data-color="red"></button>
						</div>
					</div>
					<div class="switch-block">
						<h4>Background</h4>
						<div class="btnSwitch">
							<button type="button" class="changeBackgroundColor" data-color="bg2"></button>
							<button type="button" class="changeBackgroundColor selected" data-color="bg1"></button>
							<button type="button" class="changeBackgroundColor" data-color="bg3"></button>
						</div>
					</div>
					<div class="switch-block">
						<div class="form-group d-flex p-0 align-items-center">
							<h4>Image Background</h4>
							<div class="ml-auto">
								<input id="custom-image-background" type="checkbox" data-toggle="toggle" data-onstyle="primary" data-style="btn-round">
							</div>
						</div>
					</div>
					<div class="switch-block mt-3">
						<h4>Pick Image:</h4>
						<div class="row images-pick m-0 mt-3 mb-5">
							<div class="col-4 col-md-3 img-pick active">
								<img src="../assets/img/sidebar-images/1.jpg" alt="sidebar background">
							</div>
							<div class="col-4 col-md-3 img-pick">
								<img src="../assets/img/sidebar-images/2.jpg" alt="sidebar background">
							</div>
							<div class="col-4 col-md-3 img-pick">
								<img src="../assets/img/sidebar-images/3.jpg" alt="sidebar background">
							</div>
							<div class="col-4 col-md-3 img-pick">
								<img src="../assets/img/sidebar-images/4.jpg" alt="sidebar background">
							</div>
							<div class="col-4 col-md-3 img-pick">
								<img src="../assets/img/sidebar-images/5.jpg" alt="sidebar background">
							</div>
							<div class="col-4 col-md-3 img-pick">
								<img src="../assets/img/sidebar-images/6.jpg" alt="sidebar background">
							</div>
							<div class="col-4 col-md-3 img-pick">
								<img src="../assets/img/sidebar-images/7.jpg" alt="sidebar background">
							</div>
						</div>
					</div>
				</div>
			</div>
		 
		</div>
	 
	<!--   Core JS Files   -->
	<script src="../assets/js/core/jquery.3.2.1.min.js"></script>
	<script src="../assets/js/core/popper.min.js"></script>
	<script src="../assets/js/core/bootstrap.min.js"></script>

	<!-- jQuery UI -->
	<script src="../assets/js/plugin/jquery-ui-1.12.1.custom/jquery-ui.min.js"></script>
	<script src="../assets/js/plugin/jquery-ui-touch-punch/jquery.ui.touch-punch.min.js"></script>

	<!-- jQuery Scrollbar -->
	<script src="../assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

	<!-- Moment JS -->
	<script src="../assets/js/plugin/moment/moment.min.js"></script>

	<!-- Chart JS -->
	<script src="../assets/js/plugin/chart.js/chart.min.js"></script>

	<!-- Chart Circle -->
	<script src="../assets/js/plugin/chart-circle/circles.min.js"></script>

	<!-- Datatables -->
	<script src="../assets/js/plugin/datatables/datatables.min.js"></script>

	<!-- Bootstrap Notify -->
	<script src="../assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

	<!-- Bootstrap Toggle -->
	<script src="../assets/js/plugin/bootstrap-toggle/bootstrap-toggle.min.js"></script>

	<!-- jQuery Vector Maps -->
	<script src="../assets/js/plugin/jqvmap/jquery.vmap.min.js"></script>
	<script src="../assets/js/plugin/jqvmap/maps/jquery.vmap.world.js"></script>

	<!-- Google Maps Plugin -->
	<script src="../assets/js/plugin/gmaps/gmaps.js"></script>

	<!-- Dropzone -->
	<script src="../assets/js/plugin/dropzone/dropzone.min.js"></script>

	<!-- Fullcalendar -->
	<script src="../assets/js/plugin/fullcalendar/fullcalendar.min.js"></script>

	<!-- DateTimePicker -->
	<script src="../assets/js/plugin/datepicker/bootstrap-datetimepicker.min.js"></script>

	<!-- Bootstrap Tagsinput -->
	<script src="../assets/js/plugin/bootstrap-tagsinput/bootstrap-tagsinput.min.js"></script>

	<!-- Bootstrap Wizard -->
	<script src="../assets/js/plugin/bootstrap-wizard/bootstrapwizard.js"></script>

	<!-- jQuery Validation -->
	<script src="../assets/js/plugin/jquery.validate/jquery.validate.min.js"></script>

	<!-- Summernote -->
	<script src="../assets/js/plugin/summernote/summernote-bs4.min.js"></script>

	<!-- Select2 -->
	<script src="../assets/js/plugin/select2/select2.full.min.js"></script>

	<!-- Sweet Alert -->
	<script src="../assets/js/plugin/sweetalert/sweetalert.min.js"></script>

	<!-- Ready Pro JS -->
	<script src="../assets/js/ready.min.js"></script>

	<!-- Ready Pro DEMO methods, don"t include it in your project! -->
	<script src="../assets/js/setting-demo.js"></script>
	<script src="../assets/js/demo.js"></script>
 ';

?>