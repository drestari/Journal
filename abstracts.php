<?php
session_start();
include("ajsmr@admin99/config/config.inc.php");

/**** Pagination ********/
include_once("ajsmr@admin99/config/pagination.php");

/**** Pagination ********/
//include_once("config/pagination.php");
$q_limit =32;
	if( isset($_GET['start']) )
	{

		$start = $_GET['start'];

	}

    
	else

	{

		$start = 0;

	}

	$filePath = "abstracts.php";

	$otherParams='&cid='.($_GET['cat_id'] ?? '');

/******** Pagination end *******/?>
<!DOCTYPE html>
<html>

<!-- Mirrored from html.tonatheme.com/2018/Synergy/Synergy/about.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 08 May 2018 13:14:04 GMT -->
<head>
<meta charset="utf-8">
<title>Welcome to ajsmrjournal.com :: Abstracts</title>
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
			<img class="img-fluid" src="images/ajsmrbanner.jpg" alt="First slide"></div>
		
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
									<li><a href="index.php">Home</a></li>
									<li><a href="editorialboard.php">Editorial Board</a></li>
									<li><a href="authorguidelines.php"> Author Guidelines</a></li>
									<li><a href="submitmanuscript.php">Submit Manuscript </a></li>
									<li class="current"><a href="currentissue.php"> Current Issue</a></li>
									<li><a href="archives.php">Archives</a></li>
									<li class="dropdown"><a href="#" class="dropdown-toggle" data-toggle="dropdown">Policies  <b class="caret"></b></a>
                        			<ul class="dropdown-menu">
                             		<li class="kopie"><a href="publicationethics.html">Publication Ethics</a></li>
                            			
										<li><a href="peerreviewpolicy.html">Peer Review Policy</a></li>
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
								<li><a href="index.php">Home</a></li>
									<li><a href="editorialboard.php">Editorial Board</a></li>
									<li><a href="authorguidelines.php"> Author Guidelines</a></li>
									<li><a href="submitmanuscript.php">Submit Manuscript </a></li>
									<li class="current"><a href="currentissue.php"> Current Issue</a></li>
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


    <!--Page Title-->
   <section class="main-slider">
<div id="carouselExampleControls" class="carousel slide" data-ride="carousel">
  <div class="carousel-inner">
    <div class="carousel-item active">
      <img class="d-block w-100" src="banners/currentissue.jpg" alt="First slide">
    </div>
   
  </div>
</div>
    </section>
    <!--End Page Title-->

    <!--Page Info-->
    <section class="page-info">
        <div class="container">
            <div class="flex-box-five">
                <ul class="bread-crumb">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#">Abstracts</a></li>
					 <!--<li><a href="index.php">Home</a></li>-->
                </ul>
                
            </div>
        </div>
    </section>    
    <!-- End Page Info -->
    
    <!-- about us -->
    <section class="about-us-two sp-two">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="about-column mb-30">
						<?php
						$qProductsList = "SELECT * FROM ajsmr_abstracts  WHERE  status='1'  ORDER BY contentid DESC";
						$countrs = mysql_query($qProductsList);
						$no_rows = mysql_num_rows($countrs);
						$qProductsList = "SELECT * FROM ajsmr_abstracts   WHERE  status='1'  ORDER BY contentid DESC limit 0,7";
						//echo $qProductsList;
						//exit;
						$rsProductsList = mysql_query($qProductsList);
						//$i = 0;
						while($resProductsList = mysql_fetch_array ($rsProductsList)){
						?>
						
						<div class="row writer">
							<div class="col-md-10">
							<b>Title</b>:<b><font color="#0000FF">&nbsp;<a href="abstracts_details.php?id=<?php echo $resProductsList['contentid'];?>"><?php echo $resProductsList['conttitle'];?></a></b></font><br><br>
							<strong><!--<span class="title"><b>Authors</b>:&nbsp;<font color="#CC0000"><?php echo $resProductsList['authors'];?></font></span><br></strong>
							<b>Abstract</b>:&nbsp;< ?php echo strip_tags(substr($resProductsList['abstracttxt'],0,111))."...";?><br>
							<b>Cite This Article</b>:<i>&nbsp;< ?php echo strip_tags(substr($resProductsList['citethisarticle'],0,111))."...";?><br></i>
							<b>DOI</b>:&nbsp; < ?php echo $resProductsList['doi'];?><br>							<b>Published:</b>&nbsp;< ?php echo $resProductsList['published'];?>;&nbsp;<br><b>Download URL:</b>&nbsp;< ?php echo $resProductsList['downloadurl'];?>;<br>-->
							<b>Issue Details:</b> &nbsp;<?php echo strip_tags(substr($resProductsList['issuedetails'],0,311))."...";?><br>
							<!--<b>Published:</b> < ?php echo $resProductsList['published'];?><br>
							<b>Published:</b> < ?php echo $resProductsList['published'];?><br>
							<b>DOI</b>	     : < ?php echo $resProductsList['doi'];?><br>-->
							</div>
						</div>
						<?php } ?>
						
						<!--<div align="right"><font size="+1"><a href="archives.php"><b>Continue...</b></a></font></div>-->
						
                </div>
               </div> 
            </div>
        </div>
    </section>
</div>
<!--End pagewrapper-->
<!-- main-footer -->
<?php include("footer.php");?>
    
<!-- Scroll Top Button -->
<button class="scroll-top scroll-to-target" data-target="html">
    <span class="fa fa-angle-up"></span>
</button>
    

<!-- jequery plugin -->
<script src="js/jquery.js"></script>
<script src="js/tether.min.js"></script>
<script src="js/bootstrap.min.js"></script>

<script src="js/plugins.js"></script>
<script src="js/validate.js"></script>
<script src="js/jquery.fancybox.pack.js"></script>
<script src="js/jquery.fancybox-media.js"></script>

<script src="js/script.js"></script>

<!-- map script -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCRvBPo3-t31YFk588DpMYS6EqKf-oGBSI"></script> 
<script src="js/gmaps.js"></script>
<script id="map-script" src="js/map-script.js"></script>



</body>

<!-- Mirrored from html.tonatheme.com/2018/Synergy/Synergy/about.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 08 May 2018 13:14:30 GMT -->
</html>
