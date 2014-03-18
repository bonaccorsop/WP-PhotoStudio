<?php


function var_jump( $data ) {
	echo '<pre>';
	print_r( $data );
	echo '</pre>';
}



class PhotoItem {


	private $image = NULL;


	public function __construct( $post_id, $format = 'full' ) {

		$this->image = $this->get_image( $post_id, $format );

	}


	public function get_exif() {

		$exif = @exif_read_data( $this->get_path() );



		if( !$exif ) {
			return FALSE;
		}
		$out = array(
			'filename' => $exif[ 'FileName' ],
			'capture_date' => date( 'd/m/Y H:i', $exif[ 'FileDateTime' ] ),
			'orientation' => $exif[ 'Orientation' ] == 1 ? 'Portrait' : 'Landscape',
			'camera' => $exif[ 'Model' ],
			'size' => $exif[ 'COMPUTED' ][ 'Width' ] . 'x' . $exif[ 'COMPUTED' ][ 'Height' ],
			'filesize' => $this->human_filesize( $exif[ 'FileSize' ] ),
			'flash' => $exif[ 'Flash' ] == 1 ? 'Yes' : 'No',
			'gps' => $this->get_gps( $exif["GPSLatitude"], $exif['GPSLatitudeRef'] ) . ',' . $this->get_gps( $exif["GPSLongitude"], $exif['GPSLongitudeRef'] ),
			'zoom' => '0%'
		);

		/*var_jump( $out );
		var_jump( $exif );

		die();*/


		return $out;

	}


	private function get_gps( $exifCoord, $hemi ) {

	    $degrees = count($exifCoord) > 0 ? $this->gps2Num($exifCoord[0]) : 0;
	    $minutes = count($exifCoord) > 1 ? $this->gps2Num($exifCoord[1]) : 0;
	    $seconds = count($exifCoord) > 2 ? $this->gps2Num($exifCoord[2]) : 0;

	    $flip = ($hemi == 'W' or $hemi == 'S') ? -1 : 1;

	    return $flip * ($degrees + $minutes / 60 + $seconds / 3600);

	}

	private function gps2Num($coordPart) {

	    $parts = explode('/', $coordPart);

	    if (count($parts) <= 0)
	        return 0;

	    if (count($parts) == 1)
	        return $parts[0];

	    return floatval($parts[0]) / floatval($parts[1]);
	}

	private function human_filesize( $bytes, $decimals = 2 ) {
		$sz = 'BKMGTP';
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}


	private function get_path(  ) {

		$file = parse_url( $this->image[ 'src' ] );
		return str_replace( '/wp-admin', '', getcwd() ) . $file[ 'path' ];
	}


	private function get_image( $post_id, $format = 'full' )
	{

		if( has_post_thumbnail( $post_id ) ) {

			$postThumbId = get_post_thumbnail_id( $post_id );
			$img = wp_get_attachment_image_src( $postThumbId, $format, true );

			$alt = get_post_meta( $postThumbId, "_wp_attachment_image_alt", true );
			$title = get_post_meta( $postThumbId, "_wp_attachment_image_title", true );

			return array(
        		"src" => $img[ 0 ],
        		"width" => $img[ 1 ],
        		"height" => $img[ 2 ],
        		"alt" => $alt != "" ? $alt : get_the_title( $post_id ),
        		"title" => $title != "" ? $title : get_the_title( $post_id )
           	 );

		}
		else {
			return FALSE;
		}


	}



}