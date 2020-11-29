<?php
use Rtcl\Helpers\Functions;

/**
 * @author  RadiusTheme
 * @since   1.0
 * @version 1.5.4
 */

if ( !isset( $content_width ) ) {
	$content_width = 1240;
}

class Classima_Main {

	public $theme   = 'classima';
	public $action  = 'classima_theme_init';

	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'load_textdomain' ) );
		add_action( 'admin_notices',     array( $this, 'plugin_update_notices' ) );
		$this->includes();
	}

	public function load_textdomain(){
		load_theme_textdomain( $this->theme, get_template_directory() . '/languages' );
	}

	public function includes(){
		require_once get_template_directory() . '/inc/constants.php';
		require_once get_template_directory() . '/inc/helper.php';
		require_once get_template_directory() . '/inc/includes.php';

		do_action( $this->action );
	}

	public function plugin_update_notices() {
		$plugins = array();

		if ( defined( 'CLASSIMA_CORE' ) ) {
			if ( version_compare( CLASSIMA_CORE, '1.5', '<' ) ) {
				$plugins[] = 'Classima Core';
			}
		}

		if ( defined( 'RTCL_VERSION' ) ) {
			if ( version_compare( RTCL_VERSION, '1.5.55', '<' ) ) {
				$plugins[] = 'Classified Listing Pro';
			}
		}

		if ( defined( 'RTCL_STORE_VERSION' ) ) {
			if ( version_compare( RTCL_STORE_VERSION, '1.3.20', '<' ) ) {
				$plugins[] = 'Classified Listing Store';
			}
		}

		foreach ( $plugins as $plugin ) {
			$notice = '<div class="error"><p>' . sprintf( __( "Please update plugin <b><i>%s</b></i> to the latest version otherwise some functionalities will not work properly. You can update it from <a href='%s'>here</a>", 'classima' ), $plugin, menu_page_url( 'classima-install-plugins', false ) ) . '</p></div>';
			echo wp_kses_post( $notice );
		}
	}
	
	
}

function create_custom_abdoads_registration_form(){
	if ( is_user_logged_in() ) {
		$templet = do_shortcode('[rtcl_my_account]');

    } else {
		$templet = '
			
			<section id="cards"  style=" display: flex;>
				<div class="container ">
					<div class="row d-flex justify-content-center">
						
					<!--Profile Card 3-->
					<div class="col-md-4">
						<div class="card profile-card-3">
						<div class="background-block" style="
						background-image: url(http://localhost/abdoads/wp-content/uploads/2020/10/home-office-336377_640.jpg);"></div>
							<div class="profile-thumb-block">
								<img src="http://localhost/abdoads/wp-content/uploads/2020/04/Untitled-1.png" alt="profile-image" class="profile"/>
							</div>
							<div class="card-content">
								<h2>Personal subscription </h3>
								<div class="icon-block">
									<button type="button" class="btn btn-outline-secondary" onclick="personalFunction()">Join </button>
								</div>
							</div>
						</div>
					</div>




					<div class="col-md-4">
						<div class="card profile-card-3">
							<div class="background-block">
								<img src="http://localhost/abdoads/wp-content/uploads/2020/10/k-s1-rob-10005355a_1.jpg" alt="profile-sample1" class="background"/>
							</div>
							<div class="profile-thumb-block">
								<img src="http://localhost/abdoads/wp-content/uploads/2018/07/service3.png" alt="profile-image" class="profile"/>
							</div>
							<div class="card-content">
								<h2>Business subscription </h3>
								<div class="icon-block">
									<button type="button" class="btn btn-outline-secondary" onclick="businessFunction()">Join </button>
								</div>
							</div>
						</div>
					</div>
					
					
						
					</div>
				</div>

			</section>


		';
		$templet .= '
			<div id="demo" style=" display: none;" class=" justify-content-center">
				'.do_shortcode('[rtcl_my_account]').'
			</div>

		';
		$templet .= "
			<style>
				
			/*Profile Card 3*/
			.profile-card-3 {
			font-family: 'Open Sans', Arial, sans-serif;
			position: relative;
			float: left;
			overflow: hidden;
			width: 100%;
			text-align: center;
			height:368px;
			border:none;
			}
			.profile-card-3 .background-block {
				float: left;
				width: 100%;
				height: 200px;
				overflow: hidden;
			}
			.profile-card-3 .background-block .background {
			width:100%;
			vertical-align: top;
			opacity: 0.9;
			-webkit-filter: blur(0.5px);
			filter: blur(0.5px);
			-webkit-transform: scale(1.8);
			transform: scale(2.8);
			}
			.profile-card-3 .card-content {
			width: 100%;
			padding: 15px 25px;
			color:#232323;
			float:left;
			background:#efefef;
			height:50%;
			border-radius:0 0 5px 5px;
			position: relative;
			z-index: 9999;
			}
			.profile-card-3 .card-content::before {
				content: '';
				background: #efefef;
				width: 120%;
				height: 100%;
				left: 11px;
				bottom: 51px;
				position: absolute;
				z-index: -1;
				transform: rotate(-13deg);
			}
			.profile-card-3 .profile {
			border-radius: 50%;
			position: absolute;
			bottom: 50%;
			left: 50%;
			max-width: 100px;
			opacity: 1;
			box-shadow: 3px 3px 20px rgba(0, 0, 0, 0.5);
			border: 2px solid rgba(255, 255, 255, 1);
			-webkit-transform: translate(-50%, 0%);
			transform: translate(-50%, 0%);
			z-index:99999;
			}
			.profile-card-3 h2 {
			margin: 0 0 5px;
			font-weight: 600;
			font-size:25px;
			}
			.profile-card-3 h2 small {
			display: block;
			font-size: 15px;
			margin-top:10px;
			}
			.profile-card-3 i {
			display: inline-block;
				font-size: 16px;
				color: #232323;
				text-align: center;
				border: 1px solid #232323;
				width: 30px;
				height: 30px;
				line-height: 30px;
				border-radius: 50%;
				margin:0 5px;
			}
			.profile-card-3 .icon-block{
				float:left;
				width:100%;
				margin-top:15px;
			}
			.profile-card-3 .icon-block a{
				text-decoration:none;
			}
			.profile-card-3 i:hover {
			background-color:#232323;
			color:#fff;
			text-decoration:none;
			}
			</style>
		";


		$templet .= '
		<script>
			function personalFunction() {
				document.getElementById("demo").style. display = "flex";
				document.getElementById("cards").style. display = "none";

			}

			function businessFunction() {
				document.getElementById("demo").style. display = "flex";
				document.getElementById("cards").style. display = "none";

			}
		</script>
		';
		/*           
		function abdoadsFunction() {
			var value = document.getElementById("abdoads-register").value ; 
			var url = '<?= get_site_url(); ?>';
			var personal_string = '/my-account';
			var business_string = '/checkout/membership';
			
			
			
			if ( value == "/abdoads/checkout/membership") {

				window.location.href =url.concat(business_string);
			} else {
				window.location.href =url.concat(personal_string);
			}

		}
        */      
                
	}
	
	return $templet;
}

add_shortcode('abdoads_registration_form', 'create_custom_abdoads_registration_form'); 

function abdoadz_icon_font_awesome() {
	wp_enqueue_style('abdoadz',rtcl()->get_assets_uri("css/abdoadz.css"));
}
add_action( 'admin_enqueue_scripts', 'abdoadz_icon_font_awesome' );

new Classima_Main;
