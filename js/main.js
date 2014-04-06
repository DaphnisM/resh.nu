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

	function format_ephem( date, time ) {
		if ( date == undefined || date == null ) { // if we have no date
			date = new Date();
			dow = date.getDay();
			date = date.format('UTC:dd.mm.yyyy');
		}
		if ( time == undefined || time == null ) { // if we have no time
			time = new Date();
			time = time.format('UTC:H.MM');
		}
		if ( date instanceof Date ) { // if date is a Date
			dow = date.getDay();
			time = date.format('UTC:H.MM');
			date = date.format('UTC:dd.mm.yyyy');
		}
		return { date: date, time: time };
	}

	function get_thelemic_date( date, time ) {
		format = format_ephem( date, time );
		date = format.date;
		time = format.time;
		tdate = $.ajax({
			url : 'http://he.rmphe.us/time.php?action=thelemic_date',
			data : '&date=' + date + "&time=" + time,
			async : false,
			success : function( data ) { return data; }
		});
		thelemic_date = tdate.responseText + " dies " + planets[dow]['letter'];
		return thelemic_date;
	}

	function get_planets( date, time ) {
		format = format_ephem( date, time );
		date = format.date;
		time = format.time;
		curr_planets = $.ajax({
			url : 'http://he.rmphe.us/time.php?action=zodiac_chart&format=json',
			data : 'date=' + date + "&time=" + time,
			async : false,
			success : function( data ) { return data; }
		});
		curr_planets = JSON.parse(curr_planets.responseText);
		console.log(curr_planets);
		return curr_planets;
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

	function set_alarms( times ) {

	}

	function get_date( day ) {
		var date = [];
		date.resh = [];
		date.resh.rise = [];
		date.resh.noon = [];
		date.resh.set = [];
		date.resh.nadir = [];
		var times = SunCalc.getTimes( day, '45.5379','-122.714' );
		times.nadir = SunCalc.getTimes(new Date( day.getTime() + 24 * 60 * 60 * 1000), '45.5379','-122.714').nadir;
		var hours = get_planetary_hours( day, times );
		date.tdate = get_thelemic_date( day );
		date.resh.rise.hd = times.sunrise.format( 'shortTime' );
		date.resh.noon.hd = times.solarNoon.format( 'shortTime' );
		date.resh.set.hd = times.sunset.format( 'shortTime' );
		date.resh.nadir.hd = times.nadir.format( 'shortTime' );
		date.resh.rise.td = get_thelemic_date( times.sunrise );
		date.resh.noon.td = get_thelemic_date( times.solarNoon );
		date.resh.set.td = get_thelemic_date( times.sunset );
		date.resh.nadir.td = get_thelemic_date( times.nadir );
		date.day = hours.day;
		date.night = hours.night;
		date.curr_planets = get_planets( day );
		return date;
	}

	Handlebars.registerHelper('removeAnno', function(str){
		str = str.substring( 0, str.indexOf("Anno"));
		return str;
	});

	var source   = $("#entry-template").html();
	var template = Handlebars.compile(source);
	var today = get_date( new Date() );
	var tomorrow = get_date( new Date(new Date().getTime() + 24 * 60 * 60 * 1000) );
	var html = template( today );
	jQuery('body').append( html );

	jQuery('.datepicker input').datepicker({
		dateFormat : "@",
		onSelect : function (date) {
			var date = new Date(date*1000);
			var astrodate = get_date( date );
			var html = template( astrodate );
			jQuery('body').append( html );
		}
	});

});