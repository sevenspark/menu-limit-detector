<?php
/*
Plugin Name: Menu Item Limit Detector
Plugin URI: http://sevenspark.com
Description: Detects potential Menu Item Limits caused by max_input_vars or suhosin and provides a warning
Author: Chris Mavricos, SevenSpark
Author URI: http://sevenspark.com
Version: 0.1
Text Domain: mldetect
*/

add_action( 'admin_notices',  'mldetect_check_post_limits' );


function mldetect_check_post_limits(){

	$screen = get_current_screen();
	if( $screen->id != 'nav-menus' ) return;

	$currentPostVars_count = mldetect_count_post_vars();
		

	$r = array(); //restrictors

	$r['suhosin_post_maxvars'] = ini_get( 'suhosin.post.max_vars' );
	$r['suhosin_request_maxvars'] = ini_get( 'suhosin.request.max_vars' );
	$r['max_input_vars'] = ini_get( 'max_input_vars' );


	//$r['max_input_vars'] = 1300; //for testing

	if( $r['suhosin_post_maxvars'] != '' ||
		$r['suhosin_request_maxvars'] != '' ||
		$r['max_input_vars'] != '' ){

		if( ( $r['suhosin_post_maxvars'] != '' && $r['suhosin_post_maxvars'] < 1000 ) || 
			( $r['suhosin_request_maxvars']!= '' && $r['suhosin_request_maxvars'] < 1000 ) ){
			$message[] = __( "Your server is running Suhosin, and your current maxvars settings may limit the number of menu items you can save." , 'mldetect' );
		}

		//150 ~ 10 left
		foreach( $r as $key => $val ){
			if( $val > 0 ){
				if( $val - $currentPostVars_count < 150 ){
					$message[] = __( "You are approaching the post variable limit imposed by your server configuration.  Exceeding this limit may automatically delete menu items when you save.  Please increase your <strong>$key</strong> directive in php.ini.  <a href='http://goo.gl/9jm7s'>More information</a>" , 'mldetect' );
				}
			}
		}

		if( !empty( $message ) ): ?>
		<div class="mldetect-warning error">
			<h4><?php _e( 'Menu Item Limit Warning' , 'mldetect' ); ?></h4>
			<ul>
			<?php foreach( $message as $m ): ?>
				<li><?php echo $m; ?></li>
			<?php endforeach; ?>
			</ul>

			<?php
			if( $r['max_input_vars'] != '' ) echo "<strong>max_input_vars</strong> :: ". 
				$r['max_input_vars']. " <br/>";
			if( $r['suhosin_post_maxvars'] != '' ) echo "<strong>suhosin.post.max_vars</strong> :: ".$r['suhosin_post_maxvars']. " <br/>";
			if( $r['suhosin_request_maxvars'] != '' ) echo "<strong>suhosin.request.max_vars</strong> :: ". $r['suhosin_request_maxvars'] ." <br/>";
			
			echo "<br/><strong>".__( 'Menu Item Post variable count on last save' )."</strong> :: ". $currentPostVars_count."<br/>";
			if( $r['max_input_vars'] != '' ){
				$estimate = ( $r['max_input_vars'] - $currentPostVars_count ) / 14;
				if( $estimate < 0 ) $estimate = 0;
				echo "<strong>".__( 'Approximate remaining menu items' , 'mldetect' )."</strong> :: " . floor( $estimate );
			};

			?>


		</div>
		<?php endif; 

	}

}

/*
 * This could be improved by checking for each menu individually.  Currently it naively assumes the
 * last saved menu is the only menu.
 */
function mldetect_count_post_vars() {

	if( isset( $_POST['save_menu'] ) ){

		$count = 0;
		foreach( $_POST as $key => $arr ){
			$count+= count( $arr );
		}

		update_option( 'mldetect-post-var-count' , $count );
	}
	else{
		$count = get_option( 'mldetect-post-var-count' , 0 );
	}

	return $count;
}



/*
 * Cheap way of adding some simple styles for the warning.
 */
function mldetect_simple_styles() {
	?>
	<style>
   	.mldetect-warning.error{
		clear:both;
		padding:15px 15px;
		border-radius:0;
		border:1px solid #DDB723;	
		background: #FFEA73;
		color: #A63C00;
	}
	.mldetect-warning.error h4{
		color:inherit;
		margin:0 0 0px 0;
		font-weight:bold;
		text-transform: uppercase;
		font-size:12px;
	}
	</style>
	<?php
}

add_action( 'admin_print_styles-nav-menus.php', 'mldetect_simple_styles' );