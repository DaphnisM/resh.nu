<?php
/*
Plugin Name: WordPress Ephemeris Calculator
Plugin URI: http://andrewsfreeman.com/wordpress-ephemeris
Description: Adds shortcode and ajax requests for short zodiacs. SEE README.
Version: 0.1
Author: andrewsfreeman
Author URI: http://andrewsfreeman.com
License: GPL2
*/

class WPephemeris {

	public $debug = true;

	# planets, in the order swiss provides them, with english name and symbol
	public $planets = array(
		0 => array(
			'name' => 'Sun',
			'symbol' => '☉',
			'html' => '&#x2609;',
			'letter' => 'A'
		),
		1 => array(
			'name' => 'Moon',
			'symbol' => '☽',
			'html' => '&#x263D;',
			'letter' => 'B'
		),
		2 => array(
			'name'=> 'Mercury',
			#'symbol' => '☿',
			'html' => '&#x263F;',
			'letter' => 'C'
		),
		3 => array(
			'name' => 'Venus',
			#'symbol' => '♀',
			'html' => '&#x2640;',
			'letter' => 'D'
		),
		4 => array(
			'name' => 'Mars',
			#'symbol' => '♂',
			'html' => '&#x2642;',
			'letter' => 'E'
		),
		5 => array(
			'name' => 'Jupiter',
			#'symbol' => '♃',
			'html' => '&#x2643;',
			'letter' => 'F'
		),
		6 => array(
			'name' => 'Saturn',
			#'symbol' => '♄',
			'html' => '&#x2644;',
			'letter' => 'G'
		),
	);

	# zodiacal signs, keys as swiss abbreviates them, with name and symbol
	public $zodiac = array(
		'ar' => array(
			'name' => 'Aries',
			#'symbol' => '♈',
			'html' => '&# x2648;',
			'letter' => 'a'
		),
		'ta' => array(
			'name' => 'Taurus',
			#'symbol' => '♉',
			'html' => '&#x2649;',
			'letter' => 'b'
		),
		'ge' => array(
			'name' => 'Gemini',
			#'symbol' => '♊',
			'html' => '&#x264a;',
			'letter' => 'c'
		),
		'cn' => array(
			'name' => 'Cancer',
			#'symbol' => '♋',
			'html' => '&#x264b;',
			'letter' => 'd'
		),
		'le' => array(
			'name' => 'Leo',
			#'symbol' => '♌',
			'html' => '&#x264c;',
			'letter' => 'e'
		),
		'vi' => array(
			'name' => 'Virgo',
			#'symbol' => '♍',
			'html' => '&#x264d;',
			'letter' => 'f'
		),
		'li' => array(
			'name' => 'Libra',
			#'symbol' => '♎',
			'html' => '&#x264e;',
			'letter' => 'g'
		),
		'sc' => array(
			'name' => 'Scorpio',
			#'symbol' => '♏',
			'html' => '&#x264f;',
			'letter' => 'h'
		),
		'sa' => array(
			'name' => 'Sagittarius',
			#'symbol' => '♐',
			'html' => '&#x2650;',
			'letter' => 'i'
		),
		'cp' => array(
			'name' => 'Capricorn',
			#'symbol' => '♑',
			'html' => '&#x2651;',
			'letter' => 'j'
		),
		'aq' => array(
			'name' => 'Aquarius',
			#'symbol' => '♒',
			'html' => '&#x2652;',
			'letter' => 'k'
		),
		'pi' => array(
			'name' => 'Pisces',
			#'symbol' => '♓',
			'html' => '&#x2653;',
			'letter' => 'l'
		),
	);

	##
	# __construct()   sets up the whole show for WordPress
	# 
	# adds the shortcode, activates shortcode use in widgets, adds ajax requests
	##
	public function __construct() {
		add_shortcode( 'wp-ephemeris', array( 'WPephemeris', 'get_zodiac' ) );
		add_shortcode( 'thelemic-date', array( 'WPephemeris', 'thelemic_date' ) );
		add_filter( 'widget_text', 'do_shortcode', 11 );
		add_action( 'wp_ajax_nopriv_wpephemeris', array( $this, 'get_zodiac' ) );
		add_action( 'wp_ajax_wpephemeris', array( $this, 'get_zodiac' ) );
		add_action( 'wp_ajax_date93', array( $this, 'thelemic_date' ) );
		add_action( 'wp_ajax_nopriv_date93', array( $this, 'thelemic_date' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
	}

	public function get_chart( $date, $time ) {
		# run swetest with date information and separate out results by newlines
		$swetest = plugin_dir_path( __FILE__ ) . 'src/swetest';
		$result = `$swetest -b$date -t$time -fTZ -roundmin -head 2>&1`;
		$chart = explode( "\n", $result );

		# trim excess information - swetest repeats the date and time
		foreach ( $chart as $index => $swe_output ) :
			$swe_output_cleaned = split( 'ET ', $swe_output );
			$chart[$index] = $swe_output_cleaned[1];
		endforeach;

		$chart = array_slice( $chart , 0, 8 ); # only get planet lines
		return $chart;
	}

	##
	# The shortcode `[wp-ephemeris]` by default prints the result for the 
	# current time/date.
	# 
	# timeutc="" and date="" attributes given as dd.mm.yyyy and hh.mmss
	##
	public function get_zodiac( $args ) {

		extract( shortcode_atts( array(
			'date' => date( 'd.m.Y' ),
			'time' => date( 'H.i' ),
			'svg' => '0'
		), $args ) );

		# if it's an AJAX request, parse the GET variables.
		if ( defined( 'DOING_AJAX' ) ) :
			$date = $_GET['date'] ? $_GET['date'] : $date;
			$timeutc = $_GET['timeutc'] ? $_GET['timeutc'] : "00.0000";
		endif;

		$wpephem = new WPephemeris();
		$output = "";
		
		$chart = $wpephem->get_chart( $date, $time );
		$output .= "<span class='wp-ephemeris'>"; # for the shortcode
		$json = array(); # for ajax request

		foreach( $wpephem->planets as $index => $planet ) :
			$deg = substr( $chart[$index], 0, 2 ); # degrees is first two chars
			$sign = substr( $chart[$index], 3, 2 ); # sign is next two chars
			$json[] = array(
				$planet,
				$deg,
				$wpephem->zodiac[$sign]
			);
			$output .= $wpephem->wingding( $planet['letter'] ) . " " . $deg . "° ";
			if ( $svg ) :
				$url = plugin_dir_url( __FILE__  ) . 'svgs/' . $wpephem->zodiac[$sign]['name'] . '.svg';
				$output .= "<img width='15'src='$url' />";
			else :
				$output .= $wpephem->wingding($wpephem->zodiac[$sign]['letter']) .'';
			endif;

		endforeach;

		$output .= "</span>";

		reset( $wpephem->planets );
		
		# ajax request?
		if ( defined( 'DOING_AJAX' ) ):
			echo json_encode( $json );
			exit();
		endif;

		# shortcode
		return $output;
	}

	function wingding( $letter ) {
		return '<span class="zodiac-sign">' . $letter . '</span>';
	}


	##
	# wp_head - enqueues wp-ephemeris.css
	##
	function wp_enqueue_scripts() {
		wp_enqueue_style( 'wp-ephemeris', plugin_dir_url( __FILE__  ) . 'css/wp-ephemeris.css' );
		wp_enqueue_script( 'wp-ephemeris-js', plugin_dir_url( __FILE__  ) . 'js/wp-ephemeris.js',
			array( 'jquery' ) , $ver = '0.01', false );
		wp_enqueue_script( 'suncalc-js', plugin_dir_url( __FILE__  ) . 'js/suncalc.js',
			array( 'jquery' ) , $ver = '0.01', false );
		wp_enqueue_script( 'jquery-ui-datepicker' );
	}

	function thelemic_date( $args ) {
  		extract( shortcode_atts( array(
			'date' => date( 'd.m.Y' ),
			'time' => date( 'H.i' ),
			'single' => false,
			'days' => 30
		), $args ) );

		# if it's an AJAX request, parse the GET variables.
		if ( defined( 'DOING_AJAX' ) ) :
			$date = $_GET['date'] ? $_GET['date'] : $date;
			$time = $_GET['timeutc'] ? $_GET['timeutc'] : "00.0000";
			echo WPephemeris::get_thelemic_date( $date, $time );
			exit();
		endif;

  		if ( $single )
	  		return WPephemeris::get_thelemic_date( $date, $time );

		for ( $i = 0; $i < $days ; $i++ ) {
			$nextdate = strtotime( $date ) + 60 * 60 * 24 * $i;
			$nextdate = date( 'd.m.Y', $nextdate );
			$dates .= "$nextdate " . WPephemeris::get_thelemic_date( $nextdate, $time ) . "<br />";
		}

		return $dates;
	}

	function get_thelemic_date( $date, $time ) {


		$wpephem = new WPephemeris();

		# Sol #deg Sign, Luna #deg Sign Anno IV:xxi dies --
		$chart = $wpephem->get_chart( $date, $time );

		foreach( $wpephem->planets as $index => $planet ) :
			if ( 2 == $index )
				break;
			$deg = substr( $chart[$index], 0, 2 ); # degrees is first two chars
			$sign = substr( $chart[$index], 3, 2 ); # sign is next two chars
			$output .= $wpephem->wingding( $planet['letter'] ) . " " . $deg . "° ";
			$output .= $wpephem->wingding($wpephem->zodiac[$sign]['letter']) .' ';
		endforeach;

		$output .= "Anno ";
		$date1 = "1904-04-08";
		$date2 = date( 'Y-m-d', strtotime( $date ) );

		$diff = abs(strtotime($date2) - strtotime($date1));

		$years = floor( $diff / ( 365*60*60*24 ) );
		$first = $years / 22;
		$second = $years % 22;
		$output .= $wpephem->romanic_number( $first ) . ":" . $wpephem->romanic_number( $second );
		$output .= ' Dies ' . $wpephem->planetary_day( date('N', strtotime( $date ) ) );
		return $output;
	}

	function romanic_number($num){ 
		$n = intval($num); 
		$res = ''; 

		/*** roman_numerals array  ***/ 
		$roman_numerals = array( 
		    'M'  => 1000, 
		    'CM' => 900, 
		    'D'  => 500, 
		    'CD' => 400, 
		    'C'  => 100, 
		    'XC' => 90, 
		    'L'  => 50, 
		    'XL' => 40, 
		    'X'  => 10, 
		    'IX' => 9, 
		    'V'  => 5, 
		    'IV' => 4, 
		    'I'  => 1); 

		foreach ($roman_numerals as $roman => $number){ 
		    /*** divide to get  matches ***/ 
		    $matches = intval($n / $number); 

		    /*** assign the roman char * $matches ***/ 
		    $res .= str_repeat($roman, $matches); 

		    /*** substract from the number ***/ 
		    $n = $n % $number; 
		} 

		/*** return the res ***/ 
		return $res; 
	}

	function planetary_day( $num ) {
		switch ( $num ) {
			case '1' :
				return $this->planets[1]['html'];
			break;
			case '2' :
				return $this->planets[4]['html'];
			break;
			case '3' :
				return $this->planets[2]['html'];
			break;
			case '4' :
				return $this->planets[5]['html'];
			break;
			case '5' :
				return $this->planets[3]['html'];
			break;
			case '6' :
				return $this->planets[6]['html'];
			break;
			case '7' :
				return $this->planets[0]['html'];
			break;
		}
	}
}

new WPephemeris();