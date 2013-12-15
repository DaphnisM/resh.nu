jQuery(document).ready(function($){
	var $today = $('#today');
	var times = SunCalc.getTimes( new Date(), '45.5379','-122.714' );
	$today.find('.resh-times').html(
		"Sunrise : " + times.sunrise.format( 'shortTime' ) + "<br />" +
		"Solar Noon : "  + times.solarNoon.format( 'shortTime' ) + "<br />" +
		"Sunset : " + times.sunset.format( 'shortTime' ) + "<br />" +
		"Solar Midnight : " + times.nadir.format( 'shortTime' ) + "<br />"
	);
});