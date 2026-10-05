<?php
session_start();
include("ajsmr@admin99/config/config.inc.php");
?>
<!DOCTYPE html>
<html>

<!-- Mirrored from html.tonatheme.com/2018/Synergy/Synergy/about.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 08 May 2018 13:14:04 GMT -->
<head>
<meta charset="utf-8">
<title>ajsmrjournal.com :: Issues</title>


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
    <section class="page-title" style="background-image:url(banners/archives.jpg);">
        <div class="container">
            <div class="box">
                <h1>Issues</h1>
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
                    <li>Archives</li>
                </ul>
                
            </div>
        </div>
    </section>    
    <!-- End Page Info -->
    
    <!-- about us -->
    <section class="about-us-two sp-two">
        <div class="container">
            <div class="row">
                <div class="col-lg-9">
                    <div class="about-column mb-30">
<div class="row previous-issues">
		<?php
		$qur_eng_cat="SELECT DISTINCT c.catid FROM biolifej_issueyears c, biolifej_issuecontent p WHERE p.status =1 AND p.status =1 AND c.catid = p.catid ORDER BY c.catid ASC";
		//echo $qur_eng_cat;
		//exit;
		$qur_eng_res=mysql_query($qur_eng_cat);
		while($qur_eng_row=mysql_fetch_array($qur_eng_res))
		{
		// $qur_eng_cat1="SELECT * FROM careerbooks_categories where cat_id=".$qur_eng_row['cat_id'];
		$qur_eng_cat1="SELECT * FROM biolifej_issueyears where catid='".$qur_eng_row['catid']."'";
		//echo $qur_eng_cat1;
		//exit;
		$qur_eng_res1=mysql_query($qur_eng_cat1);
		$qur_eng_row1=mysql_fetch_array($qur_eng_res1);
		?>
		<div class="col-md-1 date"><a href="issueslist.php?cat_id=<?php echo $qur_eng_row1['catid']?>"><? echo $qur_eng_row1['catename'];?></a></div>
		<?php } ?>
 
</div>
                </div>
               </div> 
            </div>
        </div>
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
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCRvBPo3-t31YFk588DpMYS6EqKf-oGBSI"></script> 
<script src="js/gmaps.js"></script>
<script id="map-script" src="js/map-script.js"></script>



</body>

<!-- Mirrored from html.tonatheme.com/2018/Synergy/Synergy/about.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 08 May 2018 13:14:30 GMT -->
</html>