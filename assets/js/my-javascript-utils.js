function site_url(rest){

	rest = typeof(rest) == 'undefined' ? '' : rest;

	var loc = window.location;
	var prefx = loc.protocol + "//";

	return  prefx + loc.host + "/" + loc.pathname.split('/')[1] + "/" + rest;

}

function wp_ajax_url( ){

	return site_url( 'admin-ajax.php' );

}
