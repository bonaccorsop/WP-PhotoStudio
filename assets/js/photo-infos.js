

jQuery( function( $ ) {

	function PhotoInfos( wrap ) {

		var self = this;
		var wrapper = wrap;

		var compiled_infos_tpl = _.template(
			'<div class="map"></div>\
    			<div class="exif-wrapper">\
	    			<div class="exif-data">\
	    				<span class="lbl"><i class="fa fa-picture-o"></i> Name</span>\
	    				<span class="val"><%= filename %></span>\
	    			</div>\
	    			<div class="exif-data">\
	    				<span class="lbl"><i class="fa fa-calendar"></i> Date</span>\
	    				<span class="val"><%= capture_date %></span>\
	    			</div>\
	    			<div class="exif-data">\
	    				<span class="lbl"><i class="fa fa-undo"></i> Orientation</span>\
	    				<span class="val"><%= orientation %></span>\
	    			</div>\
	    			<div class="exif-data">\
	    				<span class="lbl"><i class="fa fa-camera"></i> Camera</span>\
	    				<span class="val"><%= camera %></span>\
	    			</div>\
	    			<div class="exif-data">\
	    				<span class="lbl"><i class="fa fa-arrows-alt"></i> Dimension</span>\
	    				<span class="val"><%= size %></span>\
	    			</div>\
	    			<div class="exif-data">\
	    				<span class="lbl"><i class="fa fa-floppy-o"></i> File Size</span>\
	    				<span class="val"><%= filesize %></span>\
	    			</div>\
	    			<div class="exif-data">\
	    				<span class="lbl"><i class="fa fa-bolt"></i> Flash</span>\
	    				<span class="val"><%= flash %></span>\
	    			</div>\
	    			<div class="exif-data">\
	    				<span class="lbl"><i class="fa fa-search"></i> Zoom</span>\
	    				<span class="val"><%= zoom %></span>\
	    			</div>\
    			</div>\
        	</div>'
		);

		var compiled_404_tpl = _.template(
			'<div class="infos-404">\
				No Infos Found\
			</div>'
		);

		var xhr = null;


		this.lock = function() {

		};

		this.unlock = function() {

		};

		this.destroy = function() {
			wrapper.empty();
		}

		this.bind_events = function( item ) {



			//console.log( $canvas );




			return item;
		}


		this.load = function( photo_id ) {

			self.destroy();
			self.lock();

			if( xhr != null ) {
				xhr.abort();
			}

			xhr = $.get( wp_ajax_url(), {
				action : 'exif_ajax_interface',
				photo_id : photo_id

			} );


			xhr.done( function( data ) {
				//self.render( data );
				var item = self.bind_events( $( compiled_infos_tpl( data ) ) );
				wrapper.html( item );

				var $canvas = wrapper.find( '.map' );

				$canvas.gmap().bind('init', function(ev, map) {
					$canvas.gmap('addMarker', {'position': data.gps, 'bounds': true}).click(function() {
						$canvas.gmap('openInfoWindow', { 'content': 'Photo captured Here!!' }, this);
					} );
				});

			} );

			xhr.fail( function( resp ) {
				if( resp.statusText != 'abort' ) {
					//alert( 'error!' );
				}
			} );

			xhr.always( function( resp ) {
				self.unlock();
			} );

		};



		//PUB
		return {
			load : self.load
		}


	}


	$( document ).ready( function() {

		var pi = new PhotoInfos( $( '#photoinfos-wrapper' ) ).load( $( '#post_ID' ).val() );

		$( '#publish' ).click( function( e ) {
			if( $( '#set-post-thumbnail img' ).size() == 0 ) {
				alert( "Add your photo first!!!" );
				return false;
			}
		} );


	} );










} );