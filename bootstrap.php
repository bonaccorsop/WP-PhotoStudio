<?php
/**
 * @package Photo Studio
 * @version 0.1
 */
/*
Plugin Name: Photo Studio
Plugin URI:
Description: This is a plugin for manage Photos inside our Wp blog
Author: Pietro Bonaccorso
Version: 0.1
*/




require_once 'classes/PhotoItem.php';


class PhotoStudio {

	//
	public function __construct() {

		//dichiaro Custom Post Type photo
		add_action( 'init', array( $this, 'register_photo_type' ) );

		//dichiaro Custom Field Exif
		add_action( 'add_meta_boxes', array( $this, 'register_photo_fields' ) );

		//salva il campo Descrizione della Foto al database
		add_action( 'save_post', array( $this, 'save_description_meta' ) );

		//dichairo interfaccia Ajax per prendere exif della foto
		add_action( 'wp_ajax_exif_ajax_interface', array( $this, 'exif_ajax_interface' ) );

		//aggiungo colonna photo preview
		add_filter( 'manage_photo_posts_columns', array( $this, 'column_preview_title' ) );
		add_filter( 'manage_posts_custom_columns', array( $this, 'column_preview_body' ) );


		//Options Page Hook
		add_action( 'admin_menu', array( $this, 'create_menu_settings' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

	}


	//Options Page
	public function create_menu_settings() {
		add_menu_page( 'PhotoStudio Plugin Settings', 'Photo Studio Settings', 'administrator', __FILE__, array( $this, 'settings_page' ), plugins_url( '/assets/images/setts.png', __FILE__ ) );
	}

	public function register_settings() {
		register_setting( 'ps-settings-group', 'showed_exifs' );
	}

	public function settings_page() {

		?><div class="wrap">

			<h2>Photo Studio Settings</h2>

			<form method="post" action="options.php">
			    <?php settings_fields( 'ps-settings-group' ); ?>
			    <?php do_settings_sections( 'ps-settings-group' ); ?>
			    <table class="form-table">
			        <tr valign="top">
				        <th scope="row">Showed Exifs</th>
				        <td><input type="text" name="showed_exif" value="<?= get_option( 'showed_exif' ); ?>" /></td>
			        </tr>
			    </table>

			    <?php submit_button(); ?>

			</form>
		</div><?php
	}





	public function column_preview_title( $default_columns ) {

		$defaults_columns[ 'col_post_preview' ] = __( 'Preview' );

		return $default_columns;
	}

	public function column_preview_body( $column_name, $post_id ) {

		die();

		if( $column_name == 'col_post_preview' ) {
			echo $post_id;
		}

	}


	public function exif_ajax_interface() {

		$id = ! empty( $_GET[ 'photo_id' ] ) ? $_GET[ 'photo_id' ] : NULL;



		$photo = new PhotoItem( $id );
		$output = $photo->get_exif();

		//se la foto esiste e contiene un exif servilo
		if( ! empty( $output ) && $output != FALSE ) {

			header( 'Content-type: application/json' );
			header("HTTP/1.1 200 OK");
			echo json_encode( $output );
			exit();
		}

		//ritorna una 404
		else {
			header("HTTP/1.1 404 Not Found");
			echo 'No Photo Exif found';
			exit();
		}



	}


	public function save_description_meta( $post_id ) {

		//controllo se è stato passato il campo descrizione
		if( ! empty( $_POST[ 'photo-description' ] ) ) {

			//prevengo eventuali injection
			$value = sanitize_text_field( $_POST[ 'photo-description' ] );
			update_post_meta( $post_id, 'photodesc', $value );
		}

	}


	public function register_photo_fields() {

		add_meta_box(
            'desc',
            __( 'Description', 'photostudio-domain' ),
            'photo_description_view', //calback di creazione box dei campi (funzione sotto)
            'photo' 				  //custom post type dove registrare il box
        );

		add_meta_box(
            'exif',
            __( 'Photo Infos', 'photostudio-domain' ),
            'photo_exif_view', //calback di creazione box dei campi (funzione sotto)
            'photo' 			 //custom post type dove registrare il box
        );


        function photo_description_view( $post ) {

		  //Prendi il valore già memorizzato per popolare il campo
		  $description = get_post_meta( $post->ID, 'photodesc', true );
		  ?><textarea id="description-field" name="photo-description" style="width:100%; min-height: 100px" ><?= esc_attr( $description ) ?></textarea><?php

		}


        function photo_exif_view( $showed_post ) {

        	?><div id="photoinfos-wrapper"></div><?

        	//carico script di renderizzazione dinamica dati exif
        	wp_enqueue_script( "my-javascript-utils", plugin_dir_url( __FILE__ ) . 'assets/js/my-javascript-utils.js' );
        	wp_enqueue_script( "gmaps", 'http://maps.google.com/maps/api/js?sensor=true' );
        	wp_enqueue_script( "jquery-gmaps", plugin_dir_url( __FILE__ ) . 'assets/js/jquery.gmaps.js',  array( 'jquery', 'gmaps' ) );
        	wp_enqueue_script( "photo-infos-rendering", plugin_dir_url( __FILE__ ) . 'assets/js/photo-infos.js', array( 'jquery', 'underscore', 'my-javascript-utils', 'jquery-gmaps' ) );

        	wp_enqueue_style( "font-awesome", plugin_dir_url( __FILE__ ) . 'assets/css/font-awesome/css/font-awesome.min.css' );
        	wp_enqueue_style( "photo-infos-styles", plugin_dir_url( __FILE__ ) . 'assets/css/photo-infos.css' );
        }

	}


	//Callback di registrazione formato Photo
	public function register_photo_type() {

		register_post_type( 'photo', array(

			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'photo' ),
			'capability_type'    => 'post',
			'has_archive'        => true,
			'menu_position'      => 4,
			'menu_icon' 		 => 'dashicons-camera',
			'supports'           => array( 'title', 'thumbnail'/*, 'comments'*/ ),

			//Dichiaro le stringhe che Wp utilizzerà nel pannello di Amministrazione per questo CustomPostType
			'labels'             => array(
				'name'               => _x( 'Photos', 'post type general name', 'photostudio-domain' ),
				'singular_name'      => _x( 'Photo', 'post type singular name', 'photostudio-domain' ),
				'menu_name'          => _x( 'Photos', 'admin menu', 'photostudio-domain' ),
				'name_admin_bar'     => _x( 'Photo', 'add new on admin bar', 'photostudio-domain' ),
				'add_new'            => _x( 'Add New', 'book', 'photostudio-domain' ),
				'add_new_item'       => __( 'Add New Photo', 'photostudio-domain' ),
				'new_item'           => __( 'New Photo', 'photostudio-domain' ),
				'edit_item'          => __( 'Edit Photo', 'photostudio-domain' ),
				'view_item'          => __( 'View Photo', 'photostudio-domain' ),
				'all_items'          => __( 'All Photos', 'photostudio-domain' ),
				'search_items'       => __( 'Search Photos', 'photostudio-domain' ),
				'parent_item_colon'  => __( 'Parent Photos:', 'photostudio-domain' ),
				'not_found'          => __( 'No photos found.', 'photostudio-domain' ),
				'not_found_in_trash' => __( 'No photos found in Trash.', 'photostudio-domain' )
			)
		) );


	}




}






//istanziazione del Plugin (Avvio effettivo)
$boom = new PhotoStudio();
















?>







