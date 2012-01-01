<?php
define("ABS_PATH", dirname(__FILE__));
set_include_path(get_include_path() . PATH_SEPARATOR . ABS_PATH);
require './lib/functions.php';
?>
<!DOCTYPE html>
<html> 
 
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimum-scale=1.0, maximum-scale=1.0">
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <title>Serverstats</title>
    <link href="img/homescreen.gif" rel="apple-touch-icon" />
	<link rel="stylesheet" href="http://code.jquery.com/mobile/1.0/jquery.mobile-1.0.min.css" />
	<link rel="stylesheet" href="css/app.css" type="text/css">
	<link href="css/photoswipe.css" type="text/css" rel="stylesheet" />
	<link href="css/ps_jquery-mobile.css" type="text/css" rel="stylesheet" />


		
    <script src="http://code.jquery.com/jquery-1.6.4.min.js"></script>
    <script src="http://code.jquery.com/mobile/1.0/jquery.mobile-1.0.min.js"></script>
	<!-- code for photoswipe on page two -->
	<script type="text/javascript" src="js/code.photoswipe.jquery-3.0.4.min.js"></script>	
	<script type="text/javascript" src="js/lib/klass.min.js"></script>

	<script type="text/javascript">
		
		/*
		 * IMPORTANT!!!
		 * REMEMBER TO ADD  rel="external"  to your anchor tags. 
		 * If you don't this will mess with how jQuery Mobile works
		 */
		
		(function(window, $, PhotoSwipe){
			
			$(document).ready(function(){
				
				$('div.gallery-page')
					.live('pageshow', function(e){
						
						var 
							currentPage = $(e.target),
							options = {},
							photoSwipeInstance = $("ul.gallery a", e.target).photoSwipe(options,  currentPage.attr('id'));
							
						return true;
						
					})
					
					.live('pagehide', function(e){
						
						var 
							currentPage = $(e.target),
							photoSwipeInstance = PhotoSwipe.getInstance(currentPage.attr('id'));

						if (typeof photoSwipeInstance != "undefined" && photoSwipeInstance != null) {
							PhotoSwipe.detatch(photoSwipeInstance);
						}
						
						return true;
						
					});
				
			});
		
		}(window, window.jQuery, window.Code.PhotoSwipe));
		
	</script>	
	
	
	
</head> 
 
<body> 

<!-- SIDA 1 - inloggade --> 
<div data-role="page" id="one">

    <div data-role="header" id="myheader">
        <div class="centered">
		<img src="img/mcraft_header.jpg" alt="Minecraft Serverstats" />
	</div>
	<div data-role="navbar" data-iconpos="bottom">
	<ul>
	    <li><a href="#one" data-icon="home" class="ui-btn-active ui-state-persist">Inloggade</a></li>
		<li><a href="#two" data-icon="grid">Karta</a></li>
	    <li><a href="#three" data-icon="gear">Inst&auml;llningar</a></li>
	    <li><a href="#four" data-icon="star">Topplista</a></li>
	</ul>
	</div><!-- /navbar -->
    </div> <!-- /header -->
 
    <div data-role="content">
	<div>
	    <strong>Servernamn: </strong>lambda.servegame.com
	<!-- print out a red or green sign to show server status -->
	<?php
	$serverinfo=get_serverinfo();
	if ($serverinfo['up'] == "online") {
		$status = "green";
	}
	else {
		$status = "red";
	}
	?>
	&nbsp;&nbsp;<img src="img/<?php echo $status; ?>_light.png" width="30px" alt="Statuslampa" />

	</div>
	<?php
	    $online = get_online_users();
	    $online_count = $online['count'];
	    unset($online['count']);
	?>
	<ul data-role="listview" data-inset="true" data-filter="false">
	    <li data-role="list-divider">Inloggade just nu: <?php echo $online_count; ?> anv&auml;ndare</li>
	<?php
	    foreach ($online as $user) {
		echo "    <li>" . $user . "</li>\n";
	    }	
	?>
	</ul>
	
    </div>
 
    <div data-role="footer" id="myfooter">
		<h4>&copy; 2011 Johan Klinge</h4>
    </div>
 
</div> <!--end page one-->
 
 
 <!-- SIDA 2 - karta --> 
<div data-role="page" id="two" class="gallery-page">

    <div data-role="header" id="myheader">
        <div class="centered">
		<img src="img/mcraft_header.jpg" alt="Minecraft Serverstats" />
	</div>
	<div data-role="navbar" data-iconpos="bottom">
	<ul>
	    <li><a href="#one" data-icon="home">Inloggade</a></li>
	    <li><a href="#two" data-icon="grid" class="ui-btn-active ui-state-persist">Karta</a></li>
		<li><a href="#three" data-icon="gear">Inst&auml;llningar</a></li>
	    <li><a href="#four" data-icon="star">Topplista</a></li>
	</ul>
	</div><!-- /navbar -->
    </div> <!-- /header -->
			
	<div data-role="content">                 
		<ul class="gallery">
			<li><a href="maps/normal.png" rel="external">
			<img src="maps/thumbs/thumb_normal.png.jpg" alt="Normal" />
			</a></li>
			<li><a href="maps/isometric.png" rel="external">
			<img src="maps/thumbs/thumb_isometric.png.jpg" alt="Isometric" />
			</a></li>
			<li>
			<a href="maps/oblique.png" rel="external">
			<img src="maps/thumbs/thumb_oblique.png.jpg" alt="Oblique" />
			</a></li>
			<li><a href="maps/oblique_angle.png" rel="external">
			<img src="maps/thumbs/thumb_oblique_angle.png.jpg" alt="Oblique-vinkel" />
			</a></li>
		</ul>

	</div> <!-- /content -->
	
    <div data-role="footer" id="myfooter">
        <h4>&copy; 2011 Johan Klinge</h4>
    </div>
 
</div> <!--end page one-->
 
<!-- SIDA 3 - serverinfo --> 
<div data-role="page" id="three">
 
    <div data-role="header">
        <div class="centered">
		<img src="img/mcraft_header.jpg" alt="Minecraft Serverstats" />
	</div>
	<div data-role="navbar" data-iconpos="bottom">
	    <ul>
	    <li><a href="#one" data-icon="home">Inloggade</a></li>
		<li><a href="#two" data-icon="grid">Karta</a></li>
		<li><a href="#three" data-icon="gear" class="ui-btn-active ui-state-persist">Inst&auml;llningar</a></li>
		<li><a href="#four" data-icon="star">Topplista</a></li>
	    </ul>
	</div> <!-- /navbar -->
    </div>
 
    <div data-role="content">
	<?php
	    $serverinfo = get_serverinfo();
	?>
	<table>
	    <tr>
		<td>Status:</td>
		<td><?php echo $serverinfo['up']; ?></td>
	    </tr>
	    <tr>
		<td>Uppe sedan:</td>
		<td><?php echo $serverinfo['up_since']; ?></td>
	    </tr>
	    <tr>
		<td>Uppe i:</td>
		<td><?php echo $serverinfo['up_for']; ?></td>
	    </tr>
	    <tr>
		<td>Version:</td>
		<td><?php echo $serverinfo['version']; ?></td>
	    </tr>
	    <tr>
		<td>Karta:</td>
		<td><?php echo $serverinfo['map']; ?></td>
	    </tr>
	    <tr>
		<td>Max spelare:</td>
		<td><?php echo $serverinfo['max_players']; ?></td>
	    </tr>
	    <tr>
		<td>PVP:</td>
		<td><?php echo $serverinfo['pvp']; ?></td>
	    </tr>
	</table>
    </div> <!-- end content -->

    <div data-role="footer">
        <h4>&copy;2011  Johan Klinge</h4>
    </div>

</div>
 
<!-- SIDA 4 - topplista över inloggningar -->
<div data-role="page" id="four">

    <div data-role="header">
        <div class="centered">
			<img src="img/mcraft_header.jpg" alt="Minecraft Serverstats" />
		</div>
		<div data-role="navbar" data-iconpos="bottom">
		<ul>
			<li><a href="#one" data-icon="home">Inloggade</a></li>
			<li><a href="#two" data-icon="grid">Karta</a></li>
			<li><a href="#three" data-icon="gear">Inst&auml;llningar</a></li>
			<li><a href="#four" data-icon="star" class="ui-btn-active ui-state-persist">Topplista</a></li>
		</ul>
		</div><!-- /navbar -->		
		
    </div>

    <div data-role="content">
		<?php 
			//fetch results from function
			$topusers = get_toplist();
			//fetch dates, and unset them
			$startdate = $topusers['startdate'];
			$enddate = $topusers['enddate'];
			unset($topusers['startdate']);
			unset($topusers['enddate']);
			
			echo "<p>Antal inloggningar mellan <strong>" . $startdate . "</strong> och <strong>" . $enddate . "</strong></p>\n";
			echo '<ul data-role="listview" data-inset="true" data-theme="c" data-divider-theme="b">';
			echo '   <li data-role="list-divider">Topplista p&aring; inloggade</li>';
			//loop over users and get usernames and logins
			while ($user = current($topusers)) {
				echo "   <li><span class='ui-li-count'>" . 
				     $user . "</span>" . key($topusers) . 
                                     "</li>\n";
				next($topusers);
			}
//			foreach ($topusers as $user) {
//				echo "   <li><span class='ui-li-count'>" . $user . "</span>" . key($user) . "</li>\n";
//			}
		?>
		</ul>
    </div>

    <div data-role="footer">
        <h4>&copy; 2011, Johan Klinge</h4>
    </div>
 
</div>

</body>
</html>
