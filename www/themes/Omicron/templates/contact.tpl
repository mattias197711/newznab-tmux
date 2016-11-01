<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>{$page->meta_title}{if $page->meta_title != "" && $site->metatitle != ""} - {/if}{$site->metatitle}</title>
	<meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
</head>
<body class="skin-blue layout-boxed">
<div class="wrapper">
	<div class="header">
		<h2>Contact Us</h2>
		<div class="breadcrumb-wrapper">
			<ol class="breadcrumb">
				<li><a href="{$smarty.const.WWW_TOP}{$site->home_link}">Home</a></li>
				/ Contact
			</ol>
		</div>
	</div>
	<div class="box-body">
		<div class="box-content"
			<div class="row">
				<div class="box col-md-12">
					<div class="box-content">
						<div class="row">
							<div class="col-lg-12 col-sm-12 col-xs-12">
								<div class="panel panel-default">
									<div class="panel-body pagination2">
										<div class="box-body">
										<div class="row">
											<div class="col-sm-8">
												{$msg}
												{if $msg == ""}
												<h2>Have a question? <br> Don't hesitate to send us a message. Our team
													will be
													happy to help you.</h2>
											</div>
										</div>
										<div class="row m-b-30">
											<div class="col-sm-6">
												<form method="POST" action="{$smarty.const.WWW_TOP}contact-us">
													<div class="row">
														<div class="col-sm-6">
															<label for="username" class="h6">Name</label>
															<input id="username" type="text" name="username" value=""
																   placeholder="Name" class="form-control form-white">
														</div>
														<div class="col-sm-6">
															<label for="useremail" class="h6">E-mail</label>
															<input type="text" id="useremail" name="useremail"
																   class="form-control form-white">
														</div>
													</div>
													<label for="comment" class="h6">Message</label>
										<textarea rows="7" name="comment" id="comment"
												  class="form-control form-white"></textarea>
													{$page->smarty->fetch('captcha.tpl')}
													<button type="submit" value="submit" class="btn btn-primary m-t-20">
														Send
														message
													</button>
												</form>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						{/if}
							</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</body>
</html>
