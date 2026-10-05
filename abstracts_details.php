<?php
include("ajsmr@admin99/config/config.inc.php");
$qArticleDetails = "SELECT * FROM ajsmr_abstracts  WHERE contentid =".$_REQUEST['id'];
$rsArticleDetails = mysql_query($qArticleDetails);
$resArticleDetails = mysql_fetch_array($rsArticleDetails);
?>
<!DOCTYPE html>
<html>

<!-- Mirrored from html.tonatheme.com/2018/globalsciencepg/globalsciencepg/index-4.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 08 May 2018 13:09:16 GMT -->
<head>
<meta charset="utf-8">
<title>Welcome to ajsmrjournal.com</title>

<!-- Stylesheets -->
<link href="css/bootstrap.css" rel="stylesheet">
<link href="plugins/revolution/css/settings.css" rel="stylesheet">
<link href="plugins/revolution/css/layers.css" rel="stylesheet">
<link href="plugins/revolution/css/navigation.css" rel="stylesheet">
<link href="css/style.css" rel="stylesheet">
<link href="css/responsive.css" rel="stylesheet">

<!--Favicon-->
<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
<link rel="icon" href="images/favicon.ico" type="image/x-icon">
<!-- Responsive -->
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
<!--[if lt IE 9]><script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.js"></script><![endif]-->
<!--[if lt IE 9]><script src="js/respond.js"></script><![endif]-->
</head>
<body>
<div class="page-wrapper">
    <!-- Preloader -->
    <div class="preloader"></div> 
    <!-- main header -->
	
    <header class="main-header">
        <!-- header top -->
        <?php include("socialicons.php");?>
		
		<div class="banner">
			<img class="img-fluid" src="images/heading_bg.jpg" alt="First slide"></div>
		
        <!--Header-Upper-->
        <div class="header-upper style-two">
            <div class="container clearfix">
                    
                
                <div class="upper-right clearfix">
                    <div class="nav-outer clearfix">
                        <!-- Main Menu -->
                        <nav class="main-menu navbar-expand-lg">
                            <div class="navbar-header">
                                <!-- Toggle Button -->      
               <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>                                </button>
                            </div>
                            
                            <div class="navbar-collapse collapse clearfix">
                                <ul class="navigation clearfix">
                                    <!--<li class="dropdown"><a href="about.html">Publication Ethics</a>
                                        <ul>
                                            <li><a href="vision.html">Our Vision </a></li>
                                        </ul>
                                    </li>-->
									<li class="current"><a href="index.php">Home</a></li>
									<li><a href="editorialboard.php">Editorial Board</a></li>
									<li><a href="authorguidelines.php"> Author Guidelines</a></li>
									<li><a href="submitmanuscript.php">Submit Manuscript </a></li>
									<li><a href="currentissue.php"> Current Issue</a></li>
									<li><a href="archives.php">Archives</a></li>
									<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Policies  <b class="caret"></b></a>
                        			<ul class="dropdown-menu">
                             		<li class="kopie"><a href="publicationethics.php">Publication ethics</a></li>
                             		    
                            			<li><a href="peerreviewpolicy.php">Peer Review Policy</a></li>
                        			</ul>
                    				</li>
									<li><a href="contactus.php">Contact Us</a></li>
                                </ul>
                            </div>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <!--End Header Upper-->
		
		
        <!--Sticky Header-->
        <div class="sticky-header">
            <div class="container clearfix">
               
                <!--Right Col-->
                <div class="right-col">
                    <!-- Main Menu -->
                                           <nav class="main-menu navbar-expand-lg">
                            <div class="navbar-header">
                                <!-- Toggle Button -->      
               <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                                <span class="icon-bar"></span>
                              </button>
                            </div>
                            
                            <div class="navbar-collapse collapse clearfix">
                                <ul class="navigation clearfix">
								<li class="current"><a href="index.php">Home</a></li>
									<li><a href="editorialboard.php">Editorial Board</a></li>
									<li><a href="authorguidelines.php"> Author Guidelines</a></li>
									<li><a href="submitmanuscript.php">Submit Manuscript </a></li>
									<li><a href="currentissue.php"> Current Issue</a></li>
									<li><a href="archives.php">Archives</a></li>
									<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Policies  <b class="caret"></b></a>
                        			<ul class="dropdown-menu">
                             		<li class="kopie"><a href="publicationethics.php">Publication ethics</a></li>
                             		    
                            			<li><a href="peerreviewpolicy.php">Peer Review Policy</a></li>
                        			</ul>
                    				</li>
									<li><a href="contactus.php">Contact Us</a></li>
                                </ul>
                            </div>
                        </nav>

                </div>
                
            </div>
        </div>
        <!--End Sticky Header-->
    </header>
	
	
    <!--Main Slider-->
    <section class="main-slider">
<div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
  <div class="carousel-inner">
    <div class="carousel-item active">
      <img class="d-block w-100" src="images/main-slider/home-1.jpg" alt="First slide">
    </div>
    <div class="carousel-item">
      <img class="d-block w-100" src="images/main-slider/home2.jpg" alt="First slide">
    </div>
  </div>
  <a class="carousel-control-prev" href="#carouselExampleControls" role="button" data-slide="prev">
    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
    <span class="sr-only">Previous</span>
  </a>
  <a class="carousel-control-next" href="#carouselExampleControls" role="button" data-slide="next">
    <span class="carousel-control-next-icon" aria-hidden="true"></span>
    <span class="sr-only">Next</span>
  </a>
</div>
    </section>
    <!--End Main Slider-->
       <!-- about us -->
    <section class="about-us-two sp-two grey-bg">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="about-column mb-30">
					<p><font color="#9999ff"><?php echo stripslashes($resArticleDetails['issuedetails']);?></font></p>
					
					<p style="font-size:25px;"><b><?php echo stripslashes($resArticleDetails['conttitle']);?></b></p>
					
					<p><?php echo stripslashes($resArticleDetails['authors']);?></p>
					
				
					
					<p><font color="#0000CC"><i><?php echo stripslashes($resArticleDetails['journal']);?></font></i></p>
				
					
					<p><b>DOI</b>:<a href="<?php echo stripslashes($resArticleDetails['doi']);?>"><?php echo stripslashes($resArticleDetails['doi']);?></a></p>
					
				
					
					<p><b>Abstract</b>:</p>
					<p align="justify"><?php echo stripslashes($resArticleDetails['abstractsdesc']);?></p>
					<p><b>Keywords</b>:</p>
					<p align="justify"><?php echo stripslashes($resArticleDetails['keywords']);?></p>
					
					<p><b>References</b>:</p>
					<p align="justify"><?php echo stripslashes($resArticleDetails['referencestxt']);?></p>
					<p><b>Article Dates</b>:</p>
					<p align="justify"><?php echo stripslashes($resArticleDetails['articledates']);?></p>
					<p><b>How To Cite</b>:</p>
					<p align="justify"><?php echo stripslashes($resArticleDetails['howtoguide']);?></p>
					
					
					
                        <!--<div class="text">
                    <p><b>References</b>:</p> 
                   <p align="justify"><?php echo stripslashes($resArticleDetails['referencesdesc']);?></p>
                           </div>-->
                    </div>
					<span class="writer-name">
							
									<a href="pdffiles/<?php echo $resArticleDetails['abstract'];?>" target="_blank" class="btn btn-danger">Download Full Paper: PDF</a></span>
                </div>
				
				
                <div class="col-lg-4">
				<div class="manubox mar-b-30">
								<!--<h5>Manuscript Template</h5><br>-->
								<a href="submitmanuscript.php" class="btn btn-danger">
									Submit Manuscript</a>
								<!--<a href="#" class="btn btn-danger">Download Word</a>-->
						</div>
						<div class="manubox mar-b-30">
								<!--<h5>Author Copyright Form</h5><br>-->
								<a href="editorialboard.php" class="btn btn-danger">
									Join as Reviewer / Editor</a>
								<!--<a href="#" class="btn btn-danger">Download Word</a>-->
						  						   </div>
											<?php include("authorsearch.php");?>
						<?php include("search.php");?>	
														
								
										<p style="text-align:center;"><img src="/images/ugc_ajsmr.jpg" alt="Logo"></p>
											<br>
					
					
                    <!--<div class="image mb-30">
                        <img src="images/resource/about-2.jpg" alt="">
                    </div>
				-->
				
				
                </div>
            </div>
        </div>
    </section>
</div>
<!-- main-footer -->
<?php include("footer.php");?>

<!-- Scroll Top Button -->
<button class="scroll-top scroll-to-target" data-target="html">
    <span class="fa fa-angle-up"></span>
</button>
    

<!-- jequery plugins -->

<script src="js/jquery.js"></script>

<!--Revolution Slider-->
<script src="plugins/revolution/js/jquery.themepunch.revolution.min.js"></script>
<script src="plugins/revolution/js/jquery.themepunch.tools.min.js"></script>
<script src="plugins/revolution/js/extensions/revolution.extension.actions.min.js"></script>
<script src="plugins/revolution/js/extensions/revolution.extension.carousel.min.js"></script>
<script src="plugins/revolution/js/extensions/revolution.extension.kenburn.min.js"></script>
<script src="plugins/revolution/js/extensions/revolution.extension.layeranimation.min.js"></script>
<script src="plugins/revolution/js/extensions/revolution.extension.migration.min.js"></script>
<script src="plugins/revolution/js/extensions/revolution.extension.navigation.min.js"></script>
<script src="plugins/revolution/js/extensions/revolution.extension.parallax.min.js"></script>
<script src="plugins/revolution/js/extensions/revolution.extension.slideanims.min.js"></script>
<script src="plugins/revolution/js/extensions/revolution.extension.video.min.js"></script>
<script src="js/main-slider-script.js"></script>
<script src="js/tether.min.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/plugins.js"></script>
<script src="js/validate.js"></script>
<script src="js/jquery.fancybox.pack.js"></script>
<script src="js/jquery.fancybox-media.js"></script>
<script src="js/script.js"></script>
<!-- map script -->
<script src="js/gmaps.js"></script>
<script id="map-script" src="js/map-script.js"></script>
</body>
</html>