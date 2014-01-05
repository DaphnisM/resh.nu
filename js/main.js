jQuery(document).ready(function($){
	
	var planets = [
		{
			'name' : 'Sun',
			'symbol' : '☉',
			'html' : '&#x2609;',
			'letter' : '<span class="zodiac-sign yellow">A</span>'
		},
		{
			'name' : 'Moon',
			'symbol' : '☽',
			'html' : '&#x263D;',
			'letter' : '<span class="zodiac-sign violet">B</span>'
		},
		{
			'name' : 'Mars',
			'symbol' : '♂',
			'html' : '&#x2642;',
			'letter' : '<span class="zodiac-sign red">E</span>'
		},
		{
			'name': 'Mercury',
			'symbol' : '☿',
			'html' : '&#x263F;',
			'letter' : '<span class="zodiac-sign orange">C</span>'
		},
		{
			'name' : 'Jupiter',
			'symbol' : '♃',
			'html' : '&#x2643;',
			'letter' : '<span class="zodiac-sign green">F</span>'
		},
		{
			'name' : 'Venus',
			'symbol' : '♀',
			'html' : '&#x2640;',
			'letter' : '<span class="zodiac-sign cyan">D</span>'
		},
		{
			'name' : 'Saturn',
			'symbol' : '♄',
			'html' : '&#x2644;',
			'letter' : '<span class="zodiac-sign dark blue">G</span>'
		}
	];

	var planetary_hours = [
		6,
		4,
		2,
		0,
		5,
		3,
		1
	];

	function get_thelemic_date( date, time ) {
		if ( date == undefined || date == null ) {
			date = new Date();
			dow = date.getDay();
			date = date.format('UTC:dd.mm.yyyy');
		}
		if ( time == undefined || time == null ) {
			time = new Date();
			time = time.format('UTC:H.MM');
		}
		if ( date instanceof Date ) {
			dow = date.getDay();
			time = date.format('UTC:H.MM');
			date = date.format('UTC:dd.mm.yyyy');
		}
		tdate = $.ajax({
			url : 'time.php?action=thelemic_date',
			data : '&date=' + date + "&time=" + time,
			async : false,
			success : function( data ) { return data; }
		});
		thelemic_date = tdate.responseText + " dies " + planets[dow]['letter'];
		return thelemic_date;
	}

	function get_planetary_hours( date, times ) {

		var daylength =  times.sunset.getTime() - times.sunrise.getTime();
		var dayhourlength = daylength / 12;
		var tomorrow = new Date( date );
		tomorrow = tomorrow.setDate( tomorrow.getDate() + 1 );
		tomorrow = SunCalc.getTimes( tomorrow, '45.5379','-122.714' );
		var nighthourlength = ( tomorrow.sunrise.getTime() - times.sunset.getTime() ) / 12;
		// day of the week
		var daynow = date.getDay();
		daynow = planetary_hours.indexOf(daynow);
		var hrend = new Date ( times.sunrise.getTime() );
		var night = [];
		var day = [];

		for ( var i = 1; i <= 12; i++ ) {
			if ( 7 == daynow )
				daynow = 0;
			hstart = new Date( hrend.getTime() );
			hrend = new Date( hrend.getTime() + dayhourlength );
			day.push( planets[planetary_hours[daynow]]['letter'] + " " + hstart.format('shortTime') + " - " + hrend.format('shortTime'));
			daynow++;
		}

		for ( var i = 13; i <= 24; i++ ) {
			if ( 7 == daynow )
				daynow = 0;
			hstart = new Date( hrend.getTime() );
			hrend = new Date( hrend.getTime() + nighthourlength );
			night.push( planets[planetary_hours[daynow]]['letter'] + " " + hstart.format('shortTime') + " - " + hrend.format('shortTime'));
			daynow++;
		}

		var hours = {
			'day' : day,
			'night' : night
		};

		return hours;
	}

	function get_date( day ) {
		var date = [];
		date.resh = [];
		var times = SunCalc.getTimes( day, '45.5379','-122.714' );
		var hours = get_planetary_hours( day, times );
		console.log(hours)
		date.tdate = get_thelemic_date( day );
		date.resh.rise = times.sunrise.format( 'shortTime' );
		date.resh.noon = times.solarNoon.format( 'shortTime' );
		date.resh.set = times.sunset.format( 'shortTime' );
		date.resh.nadir = times.nadir.format( 'shortTime' );
		date.day = hours.day;
		date.night = hours.night;
		return date;
	}

	var source   = $("#entry-template").html();
	var template = Handlebars.compile(source);
	var today = get_date( new Date() );
	var tomorrow = get_date( new Date(new Date().getTime() + 24 * 60 * 60 * 1000) );
	var html = template( today );
	jQuery('body').append( html );
	jQuery('body').append( template( tomorrow ) );
});