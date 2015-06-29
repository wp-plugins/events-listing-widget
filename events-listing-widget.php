<?php
/*
 Plugin Name: Events Listing Widget
 Plugin URI: http://ylefebvre.ca/wordpress-plugins/events-listing-widget
 Description: Creates a new post type to manage events and a widget to display them chronologically
 Version: 1.2.7
 Author: Yannick Lefebvre	
 Author URI: http://ylefebvre.ca
 Text Domain: events-listing-widget
 Domain Path: /languages
 License: GPL2
*/

// Launch the plugin.
add_action( 'plugins_loaded', 'events_listing_widget_plugin_init' );

// Load the required files needed for the plugin to run in the proper order and add needed functions to the required hooks.
function events_listing_widget_plugin_init() {
	// Load the translation of the plugin.
	add_action( 'widgets_init', 'events_listing_widget_load_widgets' );
}

// Loads the widgets packaged with the plugin.
function events_listing_widget_load_widgets() {
	register_widget( 'events_listing_widget' );
}


// First create the widget for the admin panel
class events_listing_widget extends WP_Widget {
	function events_listing_widget() {
		$widget_ops = array( 'description' => __( 'Displays upcoming events listing in a widget', 'events-listing-widget' ) );
		$this->WP_Widget( 'events_listing_widget', __( 'Events Listing', 'events-listing-widget' ), $widget_ops );
	}

	function form( $instance ) {
		$widget_title         = ( isset( $instance['widget_title'] ) && ! empty( $instance['widget_title'] ) ? esc_html( $instance['widget_title'] ) : 'Events Listing' );
		$widget_lookahead     = ( isset( $instance['widget_lookahead'] ) && ! empty( $instance['widget_lookahead'] ) ? $instance['widget_lookahead'] : 3 );
		$widget_display_count = ( isset( $instance['widget_display_count'] ) && ! empty( $instance['widget_display_count'] ) ? $instance['widget_display_count'] : 3 );
		$widget_more_label    = ( isset( $instance['widget_more_label'] ) && ! empty( $instance['widget_more_label'] ) ? $instance['widget_more_label'] : 'more' );
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'widget_title' ); ?>"> <?php _e( 'Widget Title', 'events-listing-widget' ); echo ':'; ?>
				<input type="text" id="<?php echo $this->get_field_id( 'widget_title' ); ?>" name="<?php echo $this->get_field_name( 'widget_title' ); ?>" value="<?php echo $widget_title; ?>" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'widget_lookahead' ); ?>"> <?php _e( 'Number of months to display', 'events-listing-widget' ); echo ':'; ?>
				<input type="text" id="<?php echo $this->get_field_id( 'widget_lookahead' ); ?>" name="<?php echo $this->get_field_name( 'widget_lookahead' ); ?>" value="<?php echo $widget_lookahead; ?>" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'widget_display_count' ); ?>"> <?php _e( 'Number of items to display', 'events-listing-widget' ); echo ':'; ?>
				<input type="text" id="<?php echo $this->get_field_id( 'widget_display_count' ); ?>" name="<?php echo $this->get_field_name( 'widget_display_count' ); ?>" value="<?php echo $widget_display_count; ?>" />
			</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'widget_more_label' ); ?>"> <?php _e( 'More text label', 'events-listing-widget' ); echo ':'; ?>
				<input type="text" id="<?php echo $this->get_field_id( 'widget_more_label' ); ?>" name="<?php echo $this->get_field_name( 'widget_more_label' ); ?>" value="<?php echo $widget_more_label; ?>" />
			</label>
		</p>

		<?php wp_reset_query(); ?>

	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance                         = $old_instance;
		$instance['widget_title']         = strip_tags( $new_instance['widget_title'] );
		$instance['widget_lookahead']     = intval( strip_tags( $new_instance['widget_lookahead'] ) );
		$instance['widget_display_count'] = intval( strip_tags( $new_instance['widget_display_count'] ) );
		$instance['widget_more_label']    = strip_tags( $new_instance['widget_more_label'] );

		return $instance;
	}

	function prepare_the_content( $content, $ID, $more_link_text = null, $stripteaser = false ) {

		global $more;

		$content = apply_filters( 'the_content', $content );

		if ( null === $more_link_text ) {
			$more_link_text = __( '(more...)' );
		}

		$output = '';

		if ( preg_match( '/<!--more(.*?)?-->/', $content, $matches ) ) {
			$content = explode( $matches[0], $content, 2 );
			$content = do_shortcode( $content );
			if ( ! empty( $matches[1] ) && ! empty( $more_link_text ) ) {
				$more_link_text = strip_tags( wp_kses_no_null( trim( $matches[1] ) ) );
			}

			$hasTeaser = true;
		} else {
			$content = array( do_shortcode( $content ) );
		}

		foreach ( $content as $contentelement ) {
			if ( ( false !== strpos( $contentelement, '<!--noteaser-->' ) ) ) {
				$stripteaser = true;
				break;
			}
		}

		$teaser = $content[0];
		if ( $more && $stripteaser && $hasTeaser ) {
			$teaser = '';
		}

		$output .= $teaser;

		if ( count( $content ) > 1 ) {
			if ( ! empty( $more_link_text ) ) {
				$output .= apply_filters( 'the_content_more_link', ' <br /><a href="' . get_permalink( $ID ) . "#more-{$ID}\" class=\"more-link\">$more_link_text</a>", $more_link_text );
			}
			$output = force_balance_tags( $output );
		}

		return $output;
	}

	function widget( $args, $instance ) {
		extract( $args );
		$widget_title         = esc_html( $instance['widget_title'] );
		$widget_lookahead     = ( $instance['widget_lookahead'] != '' ? $instance['widget_lookahead'] : 3 );
		$widget_display_count = ( ! empty( $instance['widget_display_count'] ) ? $instance['widget_display_count'] : 3 );
		$widget_more_label    = ( $instance['widget_more_label'] != '' ? $instance['widget_more_label'] : 'more' );

		// Variables from the widget settings.

		echo $before_widget;

		echo $before_title . $widget_title . $after_title; // This is the line that displays the title (only if show title is set)

		// Execution of post query

		global $wpdb;

		$options = get_option( 'events_listing_Options' );

		switch ( $options['date_format'] ) {
			case 'YYYY-MM-DD':
				$phpformatstring = 'Y-m-d';
				break;
			case 'DD/MM/YYYY':
				$phpformatstring = 'd/m/Y';
				break;
			case 'MM-DD-YYYY':
				$phpformatstring = 'm-d-Y';
				break;
			case 'DD.MM.YYYY':
				$phpformatstring = 'd.m.Y';
				break;
		}

		$args = array(
			'post_type' => 'events_listing',
			'order' => 'ASC',
			'orderby' => 'meta_value',
			'meta_key' => 'events_listing_date',
			'meta_type' => 'NUMERIC',
			'posts_per_page' => intval( $widget_display_count ),
			'meta_query' => array(
				'relation' => 'OR',
				array(
					'relation' => 'AND',
					array(
						'key' => 'events_listing_date',
						'value' => current_time( 'timestamp' ),
						'compare' => '>='
					),
					array(
						'key' => 'events_listing_date',
						'value' => strtotime('+' . $widget_lookahead . ' month', current_time( 'timestamp' ) ),
						'compare' => '<='
					)
				),
				array(
					'relation' => 'AND',
					array(
						'key' => 'events_listing_date',
						'value' => current_time( 'timestamp' ),
						'compare' => '<='
					),
					array(
						'key' => 'events_listing_end_date',
						'compare' => 'EXISTS'
					),
					array(
						'key' => 'events_listing_end_date',
						'value' => current_time( 'timestamp' ),
						'compare' => '>='
					)
				),
			)

		);
		$event_query = new WP_Query( $args );

		// The Loop
		if ( $event_query->have_posts() ) {
			$counter = 0;
			while ( $event_query->have_posts() ) {
				$event_query->the_post();
				echo '<div class="events-listing">';
				echo '<div class="events-listing-title">';
				if ( $options['event_title_hyperlinks'] ) {
					echo '<a href="';
					$event_listing_url = get_post_meta( get_the_ID(), 'events_listing_url', true );
					if ( ! empty( $event_listing_url ) ) {
						echo $event_listing_url;
					} else {
						echo get_the_permalink( get_the_ID() );
					}
					echo '" target="_blank" >';
				}
				echo get_the_title( get_the_ID() );
				if ( $options['event_title_hyperlinks'] ) {
					echo '</a>';
				}
				echo '</div>';
				echo '<div class="events-listing-date">' . $options['before_date'] . date( $phpformatstring, get_post_meta( get_the_ID(), 'events_listing_date', true ) ) . $options['after_date'] . '</div>';
				echo '<div class="events-listing-content">' . $this->prepare_the_content( get_the_content(), get_the_ID(), $widget_more_label ) . '</div>';
				echo '</div>';
			}
			echo '</ul>';
		}

		/* Restore original Post Data */
		wp_reset_postdata();

		echo $after_widget;
	}

}

// Create the Content Block custom post type
add_action( 'init', 'my_events_listing_post_type_init' );

function my_events_listing_post_type_init() {
	if ( is_admin() ) {
		// Load text domain for translation of admin pages and text strings
		load_plugin_textdomain( 'events-listing-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	$labels  = array(
		'name'               => __( 'Events Listing', 'events-listing-widget' ),
		'singular_name'      => __( 'Event', 'events-listing-widget' ),
		'plural_name'        => __( 'Events', 'events-listing-widget' ),
		'add_new'            => __( 'Add New Event', 'events-listing-widget' ),
		'add_new_item'       => __( 'Add New Event', 'events-listing-widget' ),
		'edit_item'          => __( 'Edit Event', 'events-listing-widget' ),
		'new_item'           => __( 'New Event', 'events-listing-widget' ),
		'view_item'          => __( 'View Event', 'events-listing-widget' ),
		'search_items'       => __( 'Search Events', 'events-listing-widget' ),
		'not_found'          => __( 'No Event Found', 'events-listing-widget' ),
		'not_found_in_trash' => __( 'No Events found in Trash', 'events-listing-widget' ),
		'parent_item_colon'  => ''
	);
	$options = array(
		'labels'              => $labels,
		'public'              => true,
		'publicly_queryable'  => true,
		'exclude_from_search' => true,
		'has_archive'         => true,
		'show_ui'             => true,
		'query_var'           => true,
		'hierarchical'        => false,
		'menu_position'       => null,
		'menu_icon'           => plugins_url( '/images/icon16x16.png', __FILE__ ),
		'supports'            => array( 'title', 'editor', 'author' )
	);
	register_post_type( 'events_listing', $options );

	add_shortcode( 'events-listing-date', 'events_listing_event_date_shortcode' );
	add_shortcode( 'events-listing-end-date', 'events_listing_event_date_shortcode' );
	add_shortcode( 'events-listing-name', 'events_listing_event_name_shortcode' );
	add_shortcode( 'events-listing-url', 'events_listing_event_url_shortcode' );
}

function events_listing_event_date_shortcode( $atts, $content, $code ) {

	$options = get_option( 'events_listing_Options' );

	switch ( $options['date_format'] ) {
		case 'YYYY-MM-DD':
			$phpformatstring = 'Y-m-d';
			break;
		case 'DD/MM/YYYY':
			$phpformatstring = 'd/m/Y';
			break;
		case 'MM-DD-YYYY':
			$phpformatstring = 'm-d-Y';
			break;
		case 'DD.MM.YYYY':
			$phpformatstring = 'd.m.Y';
			break;
	}

	if ( $code == 'events-listing-date' ) {
		$meta_name = 'events_listing_date';
	} elseif ( $code == 'events-listing-end-date' ) {
		$meta_name = 'events_listing_end_date';
	}

	$event_id = get_the_ID();
	$raw_date = intval( get_post_meta( $event_id, $meta_name, true ) );
	return date( $phpformatstring,  $raw_date );
}

function events_listing_event_name_shortcode() {
	return get_the_title( get_the_ID() );
}

function events_listing_event_url_shortcode() {
	return get_post_meta( get_the_ID(), 'events_listing_url', true );
}


// Register function to be called when admin interface is visited
add_action( 'admin_init', 'events_listing_admin_init' );

// Function to register new meta box for book review post editor
function events_listing_admin_init() {
	add_meta_box( 'events_listing_details_meta_box', __( 'Event Details', 'events-listing-widget' ), 'events_listing_display_meta_box', 'events_listing', 'normal', 'high' );
	add_action( 'admin_post_save_events_listing_options', 'process_events_listing_options' );
}

function events_listing_display_meta_box( $event_listing ) {
	$options = get_option( 'events_listing_Options' );

	switch ( $options['date_format'] ) {
		case 'YYYY-MM-DD':
			$phpformatstring        = 'Y-m-d';
			$datepickerformatstring = 'yy-mm-dd';
			break;
		case 'DD/MM/YYYY':
			$phpformatstring        = 'd/m/Y';
			$datepickerformatstring = 'dd/mm/yy';
			break;
		case 'MM-DD-YYYY':
			$phpformatstring        = 'm-d-Y';
			$datepickerformatstring = 'mm-dd-yy';
			break;
		case 'DD.MM.YYYY':
			$phpformatstring        = 'd.m.Y';
			$datepickerformatstring = 'dd.mm.yy';
			break;
	}

	// Retrieve current author and rating based on book review ID
	$events_listing_date = intval( get_post_meta( $event_listing->ID, 'events_listing_date', true ) );
	$eventdate = date( $phpformatstring, ( !empty( $events_listing_date ) ? intval( get_post_meta( $event_listing->ID, 'events_listing_date', true ) ) : time() ) );
	$events_listing_end_date = intval( get_post_meta( $event_listing->ID, 'events_listing_end_date', true ) );
	$eventenddate = date( $phpformatstring, ( !empty( $events_listing_end_date ) ? intval( get_post_meta( $event_listing->ID, 'events_listing_end_date', true ) ) : time() ) );

	$eventurl = esc_html( get_post_meta( $event_listing->ID, 'events_listing_url', true ) );
	?>
	<table>
		<tr>
			<td style="width: 100px"><?php _e( 'Event Start Date', 'events-listing-widget' ); ?></td>
			<td>
				<input type='text' size='20' id='events_listing_date' name='events_listing_date' value='<?php echo $eventdate; ?>' />
			</td>
		</tr>
		<tr>
			<td style="width: 100px"><?php _e( 'Event End Date', 'events-listing-widget' ); ?></td>
			<td>
				<input type='text' size='20' id='events_listing_end_date' name='events_listing_end_date' value='<?php echo $eventenddate; ?>' />
			</td>
		</tr>
		<tr>
			<td style="width: 100px"><?php _e( 'Event URL', 'events-listing-widget' ); ?></td>
			<td>
				<input type='text' size='60' id='events_listing_url' name='events_listing_url' value='<?php echo $eventurl; ?>' />
			</td>
		</tr>
	</table>

	<script type='text/javascript'>
		jQuery(document).ready(function () {
			jQuery('#events_listing_date').datepicker({
				dateFormat    : '<?php echo $datepickerformatstring; ?>',
				showOn        : 'both',
				constrainInput: true,
				buttonImage   : '<?php echo plugins_url("/images/calendar.png", __FILE__); ?>'
			});
			jQuery('#events_listing_end_date').datepicker({
				dateFormat    : '<?php echo $datepickerformatstring; ?>',
				showOn        : 'both',
				constrainInput: true,
				buttonImage   : '<?php echo plugins_url("/images/calendar.png", __FILE__); ?>'
			});
		});
	</script>
<?php
}

add_action( 'admin_enqueue_scripts', 'events_listing_enqueue_admin_scripts' );

function events_listing_enqueue_admin_scripts() {
	wp_enqueue_script( 'datepickerjs', plugins_url( '/js/ui.datepicker.js', __FILE__ ) );
	wp_enqueue_style( 'datepickercss', plugins_url( '/css/ui-lightness/jquery-ui-1.8.4.custom.css', __FILE__ ) );
}

// Register function to be called when posts are saved
// The function will receive 2 arguments
add_action( 'save_post', 'save_events_listing_fields', 10, 2 );

function save_events_listing_fields( $ID = false, $event_listing = false ) {
	if ( isset( $_POST['post_title'] ) && $event_listing->post_type == 'events_listing' ) {
		$options = get_option( 'events_listing_Options' );

		switch ( $options['date_format'] ) {
			case 'YYYY-MM-DD':
				$divider = '-';
				$year_pos = 0;
				$month_pos = 1;
				$day_pos = 2;
				break;
			case 'DD/MM/YYYY':
				$divider = '/';
				$year_pos = 2;
				$month_pos = 1;
				$day_pos = 0;
				break;
			case 'MM-DD-YYYY':
				$divider = '-';
				$year_pos = 2;
				$month_pos = 0;
				$day_pos = 1;
				break;
			case 'DD.MM.YYYY':
				$divider = '.';
				$year_pos = 2;
				$month_pos = 1;
				$day_pos = 0;
				break;
		}

		if ( !empty( $_POST['events_listing_date'] ) ) {
			$datearray = explode( $divider, $_POST['events_listing_date'] );
			$year      = $datearray[$year_pos];
			$month     = $datearray[$month_pos];
			$day       = $datearray[$day_pos];
		} else {
			$year = date( 'Y', current_time( 'timestamp' ) );
			$month = date( 'n', current_time( 'timestamp' ) );
			$day = date( 'j', current_time( 'timestamp' ) );
		}

		if ( !empty( $_POST['events_listing_end_date'] ) ) {
			$enddatearray = explode( $divider, $_POST['events_listing_end_date'] );
			$endyear      = $enddatearray[$year_pos];
			$endmonth     = $enddatearray[$month_pos];
			$endday       = $enddatearray[$day_pos];
		} else {
			$endyear = date( 'Y', current_time( 'timestamp' ) );
			$endmonth = date( 'n', current_time( 'timestamp' ) );
			$endday = date( 'j', current_time( 'timestamp' ) );
		}

		$timetostore = gmmktime( 0, 0, 0, $month, $day, $year );
		$endtimetostore = gmmktime( 0, 0, 0, $endmonth, $endday, $endyear );

		// Check post type for book reviews
		// Store data in post meta table if present in post data
		if ( isset( $_POST['events_listing_date'] ) && $_POST['events_listing_date'] != '' && ! empty( $timetostore ) ) {
			$swapslashes = str_replace( '/', '-', $_POST['events_listing_date'] );
			update_post_meta( $ID, 'events_listing_date', $timetostore );
		} else {
			update_post_meta( $ID, 'events_listing_date', strtotime( 'now' ) );
		}

		if ( isset( $_POST['events_listing_end_date'] ) && $_POST['events_listing_end_date'] != '' && ! empty( $endtimetostore ) ) {
			$swapslashes = str_replace( '/', '-', $_POST['events_listing_end_date'] );
			update_post_meta( $ID, 'events_listing_end_date', $endtimetostore );
		} else {
			update_post_meta( $ID, 'events_listing_end_date', strtotime( 'now' ) );
		}

		if ( ! empty( $_POST['events_listing_url'] ) ) {
			update_post_meta( $ID, 'events_listing_url', $_POST['events_listing_url'] );
		}
	}
}

// Register function to be called when posts are deleted
add_action( 'delete_post', 'events_listing_delete_fields' );

// Function to delete post custom fields when post is deleted
function events_listing_delete_fields( $book_review_id ) {
	delete_post_meta( $book_review_id, 'book_author' );
	delete_post_meta( $book_review_id, 'book_rating' );
}

// Register function to be called when column list is being prepared
add_filter( 'manage_edit-events_listing_columns', 'events_listing_add_columns' );

// Function to add columns for author and type in book review listing
// and remove comments columns
function events_listing_add_columns( $columns ) {
	$columns['events_listing_date'] = __( 'Event Date', 'events-listing-widget' );
	unset( $columns['date'] );
	unset( $columns['author'] );

	return $columns;
}

// Register function to be called when custom post columns are rendered
add_action( 'manage_posts_custom_column', 'events_listing_populate_columns' );

// Function to send data for custom columns when displaying items
function events_listing_populate_columns( $column ) {
	global $post;

	$options = get_option( 'events_listing_Options' );

	switch ( $options['date_format'] ) {
		case 'YYYY-MM-DD':
			$phpformatstring = 'Y-m-d';
			break;
		case 'DD/MM/YYYY':
			$phpformatstring = 'd/m/Y';
			break;
		case 'MM-DD-YYYY':
			$phpformatstring = 'm-d-Y';
			break;
		case 'DD.MM.YYYY':
			$phpformatstring = 'd.m.Y';
			break;
	}

	// Check column name and send back appropriate data
	$events_listing_date = get_post_meta( get_the_ID(), 'events_listing_date', true );
	if ( 'events_listing_date' == $column && !empty( $events_listing_date ) ) {
		$events_listing_date = esc_html( get_post_meta( get_the_ID(), 'events_listing_date', true ) );
		echo date( $phpformatstring, $events_listing_date );
	}
}

add_filter( 'manage_edit-events_listing_sortable_columns', 'events_listing_author_column_sortable' );

// Register the author and rating columns are sortable columns
function events_listing_author_column_sortable( $columns ) {
	$columns['events_listing_date'] = 'events_listing_date';

	return $columns;
}

// Register function to be called when queries are being prepared to
// display post listing
add_filter( 'request', 'events_listing_column_ordering' );

// Function to add elements to query variable based on incoming arguments
function events_listing_column_ordering( $vars ) {
	if ( isset( $vars['orderby'] ) && 'events_listing_date' == $vars['orderby'] ) {
		$vars = array_merge( $vars, array(
			'meta_key' => 'events_listing_date',
			'orderby'  => 'meta_value'
		) );
	}

	return $vars;
}

global $optionspage;

register_activation_hook( __FILE__, 'events_listing_activation' );

function events_listing_activation() {
	if ( get_option( 'events_listing_Options' ) === false ) {
		$NewOptions['date_format']            = 'YYYY-MM-DD';
		$NewOptions['before_date']            = '';
		$NewOptions['after_date']             = '';
		$NewOptions['event_title_hyperlinks'] = true;
		add_option( 'events_listing_Options', $NewOptions );

		global $wpdb;

		$olddates = $wpdb->get_results( "select * from " .
		                                $wpdb->get_blog_prefix() .
		                                "postmeta where meta_key = 'events_listing_date'", ARRAY_A );

		if ( $olddates ) {
			foreach ( $olddates as $olddate ) {
				$query = "update " . $wpdb->get_blog_prefix() .
				         "postmeta set meta_value = " .
				         strtotime( $olddate['meta_value'] ) . " where meta_id = " .
				         $olddate['meta_id'] . " and post_id = " .
				         $olddate['post_id'] . " and meta_key = 'events_listing_date'";

				$wpdb->query( $query );
			}
		}
	}
}

add_action( 'admin_menu', 'events_listing_settings_menu' );

function events_listing_settings_menu() {
	global $optionspage;

	$optionspage = add_options_page( __( 'Events Listing Widget Configuration', 'events-listing-widget' ), __( 'Events Listing Widget', 'events-listing-widget' ), 'manage_options', 'events-listing-config', 'events_listing_config_page' );

	if ( $optionspage ) {
		add_action( 'load-' . $optionspage, 'events_listing_create_meta_boxes' );
	}
}

function events_listing_create_meta_boxes() {
	global $optionspage;
	wp_enqueue_script( 'common' );
	wp_enqueue_script( 'wp-lists' );
	wp_enqueue_script( 'postbox' );

	add_meta_box( 'events_listing_general_meta_box', __ ( 'General Settings', 'events-listing-widget' ), 'events_listing_plugin_meta_box', $optionspage, 'normal', 'core' );
}

function events_listing_config_page() {
	// Retrieve plugin configuration options from database
	$options = get_option( 'events_listing_Options' );
	global $optionspage;
	?>
	<div id="events-listing-general" class="wrap">
		<h2><?php _e( 'Events Listing Widget Configuration', 'events-listing-widget' ); ?></h2>

		<form action="admin-post.php" method="post">
			<input type="hidden" name="action" value="save_events_listing_options" />

			<!-- Adding security through hidden referrer field -->
			<?php wp_nonce_field( 'events_listing' ); ?>

			<!-- Security fields for meta box save processing -->
			<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
			<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>

			<div id="poststuff" class="metabox-holder">
				<div id="post-body">
					<div id="post-body-content">
						<?php do_meta_boxes( $optionspage, 'normal', $options ); ?>
						<input type="submit" value="<?php _e( 'Submit', 'events-listing-widget' ); ?>" class="button-primary" />
					</div>
				</div>
				<br class="clear" />
			</div>
		</form>
	</div>
	<script type="text/javascript">
		//<![CDATA[
		jQuery(document).ready(function ($) {

			// close postboxes that should be closed
			$('.if-js-closed').removeClass('if-js-closed').addClass('closed');

			// postboxes setup
			postboxes.add_postbox_toggles('<?php echo $optionspage; ?>');
		});
		//]]>
	</script>

<?php
}

function events_listing_plugin_meta_box( $options ) {
	?>
	<table>
		<tr>
			<td style="width: 100px"><?php _e( 'Date Format', 'events-listing-widget' ); ?></td>
			<td>
				<?php $dateoptions = array( 'YYYY-MM-DD', 'DD/MM/YYYY', 'MM-DD-YYYY', 'DD.MM.YYYY' ); ?>
				<select id="date_format" name="date_format">
					<?php foreach ( $dateoptions as $dateoption ) { ?>
						<option id="<?php echo $dateoption; ?>" <?php selected( $options['date_format'], $dateoption ); ?>><?php echo $dateoption; ?></option>
					<?php } ?>
				</select>
				</label></td>
		</tr>
		<tr>
			<td><?php _e( 'Before Date', 'events-listing-widget' ); ?></td>
			<td>
				<input type="text" size="30" id="before_date" name="before_date" value="<?php echo $options['before_date']; ?>" />
			</td>
		</tr>
		<tr>
			<td><?php _e( 'After Date', 'events-listing-widget' ); ?></td>
			<td>
				<input type="text" size="30" id="after_date" name="after_date" value="<?php echo $options['after_date']; ?>" />
			</td>
		</tr>
		<tr>
			<td><?php _e( 'Make event titles clickable', 'events-listing-widget' ); ?></td>
			<td>
				<input type="checkbox" id="event_title_hyperlinks" name="event_title_hyperlinks" <?php checked( $options['event_title_hyperlinks'], true ); ?> />
			</td>
		</tr>
	</table>

<?php
}

function process_events_listing_options() {
	// Check that user has proper security level
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'Not allowed', 'events-listing-widget' ) );
	}

	// Check that nonce field created in configuration form
	// is present
	check_admin_referer( 'events_listing' );

	// Retrieve original plugin options array
	$options = get_option( 'events_listing_Options' );

	// Cycle through all text form fields and store their values
	// in the options array
	foreach ( array( 'date_format', 'before_date', 'after_date' ) as $option_name ) {
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = $_POST[ $option_name ];
		}
	}

	foreach ( array( 'event_title_hyperlinks' ) as $option_name ) {
		if ( isset( $_POST[ $option_name ] ) ) {
			$options[ $option_name ] = true;
		} else {
			$options[ $option_name ] = false;
		}
	}

	// Store updated options array to database
	update_option( 'events_listing_Options', $options );

	// Redirect the page to the configuration form that was
	// processed
	wp_redirect( add_query_arg( array(
				'message' => '1',
				'page'    => 'events-listing-config'
			), admin_url( 'options-general.php' ) ) );
}

?>