<?php
session_start();
include("biolifej@admin/config/config.inc.php");
/**** Pagination ********/
include_once("biolifej@admin/config/pagination.php");
$query_ProductsDetails  = "SELECT * FROM biolifej_issuecontent  WHERE contentid =".$_GET['id']; 
$result_ProductsDetails  = mysql_query($query_ProductsDetails ) or die(mysql_error());
$row_ProductsDetails  = mysql_fetch_object($result_ProductsDetails );
?>
<!DOCTYPE html>
<html>

<!-- Mirrored from html.tonatheme.com/2018/Synergy/Synergy/about.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 08 May 2018 13:14:04 GMT -->
<head>
<meta charset="utf-8">
<title>biolifej.com :: <?php echo $row_ProductsDetails ->conttitle;?></title>
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
<?php include("header.php");?>


    <!--Page Title-->
    <section class="page-title" style="background-image:url(images/background/3.jpg);">
        <div class="container">
            <div class="box">
                <h1><?php echo $row_ProductsDetails ->conttitle;?></h1>
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
                    <li><a href="issues.php">Archives</a></li>
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
						
						<div class="row writer">
							<div class="col-md-2"><img src="images/resource/news-1.jpg"></div>
							
							<div class="col-md-10">
							<span class="title">Article Type: Survey Report</span><br>
							Article Title: “A Survey on Bit keys in Cryptography”<br>
							Article Authors: “A Survey on Bit keys in Cryptography”<br>
							ArticleID/Manuscript: “A Survey on Bit keys in Cryptography”<br>
							ArticleID/Manuscript: “A Survey on Bit keys in Cryptography”<br>
							date: “A Survey on Bit keys in Cryptography”<br>
							doi: “A Survey on Bit keys in Cryptography”<br>
								<span class="title">Effect of growing media and storage of stone on the success and survival of soft wood grafting in Mango<br> (Mangifera indica L.)</span>
								
								<span class="writer-name">Gholap Supriya S. and N. D. Polara</span>
								
								<span class="writer-name">
								The Ame J Sci & Med Res, 2015,1(1); 
									<a href="#">Pages 1-6 [PDF] </a></span>
								
								<span>doi:10.17812/ajsmr2015111a; Published online 12 February,</span> 2015
							</div>
						</div>
						
						
						<div class="row writer">
							<div class="col-md-2"><img src="images/resource/news-1.jpg"></div>
							
							<div class="col-md-10">
							<span class="title">Article Type: Survey Report</span>
								<span class="title">Effect of growing media and storage of stone on the success and survival of soft wood grafting in Mango<br> (Mangifera indica L.)</span>
								
								<span class="writer-name">Gholap Supriya S. and N. D. Polara</span>
								
								<span class="writer-name">
								The Ame J Sci & Med Res, 2015,1(1); 
									<a href="#">Pages 1-6 [PDF] </a></span>
								
								<span>doi:10.17812/ajsmr2015111a; Published online 12 February,</span> 2015
							</div>
						</div>
						
						
						<div class="row writer">
							<div class="col-md-2"><img src="images/resource/news-1.jpg"></div>
							
							<div class="col-md-10">
								<span class="title">Effect of growing media and storage of stone on the success and survival of soft wood grafting in Mango<br> (Mangifera indica L.)</span>
								
								<span class="writer-name">Gholap Supriya S. and N. D. Polara</span>
								
								<span class="writer-name">
								The Ame J Sci & Med Res, 2015,1(1); 
									<a href="#">Pages 1-6 [PDF] </a></span>
								
								<span>doi:10.17812/ajsmr2015111a; Published online 12 February,</span> 2015
							</div>
						</div>
												
						<div class="row writer">
							<div class="col-md-2"><img src="images/resource/news-1.jpg"></div>
							
							<div class="col-md-10">
								<span class="title">Effect of growing media and storage of stone on the success and survival of soft wood grafting in Mango<br> (Mangifera indica L.)</span>
								
								<span class="writer-name">Gholap Supriya S. and N. D. Polara</span>
								
								<span class="writer-name">
								The Ame J Sci & Med Res, 2015,1(1); 
									<a href="#">Pages 1-6 [PDF] </a></span>
								
								<span>doi:10.17812/ajsmr2015111a; Published online 12 February,</span> 2015
							</div>
						</div>
						
						<div class="row writer">
							<div class="col-md-2"><img src="images/resource/news-1.jpg"></div>
							
							<div class="col-md-10">
								<span class="title">Effect of growing media and storage of stone on the success and survival of soft wood grafting in Mango<br> (Mangifera indica L.)</span>
								
								<span class="writer-name">Gholap Supriya S. and N. D. Polara</span>
								
								<span class="writer-name">
								The Ame J Sci & Med Res, 2015,1(1); 
									<a href="#">Pages 1-6 [PDF] </a></span>
								
								<span>doi:10.17812/ajsmr2015111a; Published online 12 February,</span> 2015
							</div>
						</div>
						
						<div class="row writer">
							<div class="col-md-2"><img src="images/resource/news-1.jpg"></div>
							
							<div class="col-md-10">
								<span class="title">Effect of growing media and storage of stone on the success and survival of soft wood grafting in Mango<br> (Mangifera indica L.)</span>
								
								<span class="writer-name">Gholap Supriya S. and N. D. Polara</span>
								
								<span class="writer-name">
								The Ame J Sci & Med Res, 2015,1(1); 
									<a href="#">Pages 1-6 [PDF] </a></span>
								
								<span>doi:10.17812/ajsmr2015111a; Published online 12 February,</span> 2015
							</div>
						</div>

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