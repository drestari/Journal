<?php
include("ajsmr@admin99/config/config.inc.php");
$qContentPages = "SELECT * FROM  contentpages  WHERE trim(title)='Call for Papers'";
$rsContentPages = mysql_query($qContentPages);
$rowContentPages = mysql_fetch_object($rsContentPages);
?>
<!DOCTYPE html>
<html>
<!-- Mirrored from html.tonatheme.com/2018/Synergy/Synergy/about.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 08 May 2018 13:14:04 GMT -->
<head>
<meta charset="utf-8">
<title>Welcome to ajsmrjournal.come :: <?php echo stripslashes($rowContentPages->title);?></title>
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
                <h1>Call for Papers</h1>
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
                    <li>Call for Papers</li>
                </ul>
            </div>
        </div>
    </section>    
    <!-- End Page Info -->
    <!-- about us -->
    <section class="about-us-two sp-two">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="about-column mb-30">
                        <div class="text">
                   <p><?php echo stripslashes($rowContentPages->description);?></p>
                    </div>
                </div>
               </div> 
                
                <div class="col-lg-4">
                    <div class="image mb-30">
                        <img src="contentimgs/<?php echo $rowContentPages->image;?>" alt="">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- default section -->
     <section class="project-single grey-bg">
    </section>   
</div>
<!--End pagewrapper-->
<!-- main-footer -->
<?php include("footer.php")?>
    
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
<script src="js/gmaps.js"></script>
<script id="map-script" src="js/map-script.js"></script>
</body>
</html>