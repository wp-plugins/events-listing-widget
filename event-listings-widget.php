<?php
/*
 Plugin Name: Events Listing Widget
 Plugin URI: http://yannickcorner.nayanna.biz/wordpress-plugins/events-listing-widget
 Description: Creates a new post type to manage events and a widget to display them chronologically
 Version: 1.0
 Author: Yannick Lefebvre	
 Author URI: http://ylefebvre.ca
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
		$widget_ops = array('description' => 'Displays upcoming events listing in a widget');
		$this->WP_Widget('events_listing_widget', 'Events Listing', $widget_ops);
	}

	function form($instance) {
	    $widget_title = ( $instance['widget_title'] != "" ? esc_html($instance['widget_title']) : 'Events Listing' );
		$widget_lookahead = ( $instance['widget_lookahead'] != "" ? $instance['widget_lookahead'] : 3 );
		$widget_display_count = ( $instance['widget_display_count'] != "" ? $instance['widget_display_count'] : 3 );
		$widget_more_label = ( $instance['widget_more_label'] != "" ? $instance['widget_more_label'] : 'more' );
		?>

		<p>
			<label for="<?php echo $this->get_field_id('widget_title'); ?>"> <?php echo 'Widget Title:'; ?>			
			<input type="text" id="<?php echo $this->get_field_id('widget_title'); ?>" name="<?php echo $this->get_field_name('widget_title'); ?>" value="<?php echo $widget_title; ?>" />			
			</label>
		</p>
                
                <p>
			<label for="<?php echo $this->get_field_id('widget_lookahead'); ?>"> <?php echo 'Number of months to display:'; ?>			
			<input type="text" id="<?php echo $this->get_field_id('widget_lookahead'); ?>" name="<?php echo $this->get_field_name('widget_lookahead'); ?>" value="<?php echo $widget_lookahead; ?>" />			
			</label>
		</p>
                
        <p>
			<label for="<?php echo $this->get_field_id('widget_display_count'); ?>"> <?php echo 'Number of items to display:'; ?>			
			<input type="text" id="<?php echo $this->get_field_id('widget_display_count'); ?>" name="<?php echo $this->get_field_name('widget_display_count'); ?>" value="<?php echo $widget_display_count; ?>" />			
			</label>
		</p>
		
        <p>
			<label for="<?php echo $this->get_field_id('widget_more_label'); ?>"> <?php echo 'More text label:'; ?>			
			<input type="text" id="<?php echo $this->get_field_id('widget_more_label'); ?>" name="<?php echo $this->get_field_name('widget_more_label'); ?>" value="<?php echo $widget_more_label; ?>" />			
			</label>
		</p>		
		
		<?php wp_reset_query(); ?>

 <?php 
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['widget_title'] = strip_tags($new_instance['widget_title']);
        $instance['widget_lookahead'] = intval(strip_tags($new_instance['widget_lookahead']));
        $instance['widget_display_count'] = intval(strip_tags($new_instance['widget_display_count']));
		$instance['widget_more_label'] = strip_tags($new_instance['widget_more_label']);
		return $instance;
	}

        function prepare_the_content($content, $ID, $more_link_text = null, $stripteaser = false) {

                if ( null === $more_link_text )
                        $more_link_text = __( '(more...)' );

                $output = '';

                if ( preg_match('/<!--more(.*?)?-->/', $content, $matches) ) {
                        $content = explode($matches[0], $content, 2);
                        if ( !empty($matches[1]) && !empty($more_link_text) )
                                $more_link_text = strip_tags(wp_kses_no_null(trim($matches[1])));

                        $hasTeaser = true;
                } else {
                        $content = array($content);
                }
                if ( (false !== strpos($post->post_content, '<!--noteaser-->') ) )
                        $stripteaser = true;

                $teaser = $content[0];
                if ( $more && $stripteaser && $hasTeaser )
                        $teaser = '';

                $output .= $teaser;

                if ( count($content) > 1 ) {
                    if ( ! empty($more_link_text) )
                            $output .= apply_filters( 'the_content_more_link', ' <br /><a href="' . get_permalink($ID) . "#more-{$ID}\" class=\"more-link\">$more_link_text</a>", $more_link_text );
                    $output = force_balance_tags($output);
                }

                return $output;
        }

	function widget($args, $instance) {
		extract($args);
		$widget_title = esc_html($instance['widget_title']);
        $widget_lookahead = ( $instance['widget_lookahead'] != "" ? $instance['widget_lookahead'] : 3 );
        $widget_display_count = ( $instance['widget_display_count'] != "" ? $instance['widget_display_count'] : 3 );
		$widget_more_label = ( $instance['widget_more_label'] != "" ? $instance['widget_more_label'] : "more" );
		
		// Variables from the widget settings.
		
		echo $before_widget;		
		
		echo $before_title . $widget_title . $after_title; // This is the line that displays the title (only if show title is set)
		
                // Execution of post query

                global $wpdb;

                $query = "SELECT *, str_to_date(meta_value, '%Y-%m-%d') as event_date 
                                        FROM $wpdb->posts wposts, $wpdb->postmeta wpostmeta 
                                        WHERE wposts.ID = wpostmeta.post_id 
                                        AND wpostmeta.meta_key = 'events_listing_date' 
                                        AND wposts.post_type = 'events_listing' 
                                        AND wposts.post_status = 'publish' 
                                        AND str_to_date(meta_value, '%Y-%m-%d') >= '" . date('Y-m-d') . "' AND str_to_date(meta_value, '%Y-%m-%d') < '" . date('Y-m-d', strtotime($widget_lookahead . 'months')) . "' 
                                        ORDER BY event_date ASC
                                        LIMIT 0, " . $widget_display_count;

                $events = $wpdb->get_results($query, ARRAY_A);

                // Check if any posts were returned by query
                if ( $events )
                {
                        // Cycle through all items retrieved
                        foreach ($events as $event):
                                echo "<div class='events-listing'>";
                                echo "<div class='events-listing-title'><a href='";
                                echo get_permalink($event['ID']);
                                echo "'>";
                                echo get_the_title($event['ID']);
                                echo "</a></div>";
                                echo "<div class='events-listing-date'>" . $event['event_date'] . "</div>";
                                echo "<div class='events-listing-content'>" . $this->prepare_the_content($event['post_content'], $event['ID'], $widget_more_label) . "</div>";
                                echo "</div>";
                        endforeach;

                        // Reset post data query
                        wp_reset_postdata();
                }
			
		echo $after_widget;
	}
	
}

// Create the Content Block custom post type
add_action('init', 'my_events_listing_post_type_init');

function my_events_listing_post_type_init() {
	$labels = array(
		'name' => 'Events Listing',
		'singular_name' => 'Event',
		'plural_name' => 'Events',
		'add_new' => 'Add New Event',
		'add_new_item' => 'Add New Event',
		'edit_item' => 'Edit Event', 
		'new_item' => 'New Event', 
		'view_item' => 'View Event', 
		'search_items' => 'Search Events',
		'not_found' =>  'No Event Found',
		'not_found_in_trash' => 'No Events found in Trash',
		'parent_item_colon' => ''
	);
	$options = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'exclude_from_search' => true,
                'has_archive' => true,
		'show_ui' => true,
		'query_var' => true,
		'hierarchical' => false,
		'menu_position' => null,
		'menu_icon' => plugins_url('/images/icon16x16.png', __FILE__),
		'supports' => array('title','editor','author')
	);
	register_post_type('events_listing',$options);
}

// Register function to be called when admin interface is visited
add_action('admin_init', 'events_listing_admin_init');

// Function to register new meta box for book review post editor
function events_listing_admin_init()
{
    add_meta_box('events_listing_details_meta_box', 'Event Details', 'events_listing_display_meta_box', 'events_listing', 'normal', 'high');
}

function events_listing_display_meta_box($event_listing)
{ 
    // Retrieve current author and rating based on book review ID
    $eventdate = esc_html(get_post_meta($event_listing->ID, 'events_listing_date', true));
	if ($eventdate == "") $eventdate = date('Y-m-d');
    ?>
    <table>
        <tr>
            <td style="width: 100px">Event Date</td>
            <td><input type='text' size='20' id='events_listing_date' name='events_listing_date' value='<?php echo $eventdate; ?>' /></td>
        </tr>
	</table>
	
	<script type='text/javascript'>
		jQuery(document).ready(function() {
		jQuery('#events_listing_date').datepicker({minDate: '+0', dateFormat: 'yy-mm-dd', showOn: 'both', constrainInput: true, buttonImage: '<?php echo plugins_url("/images/calendar.png", __FILE__); ?>'}) });
	</script>
<?php }

add_action( 'admin_enqueue_scripts', 'events_listing_enqueue_admin_scripts' );

function events_listing_enqueue_admin_scripts()
{
	wp_enqueue_script('datepickerjs', plugins_url('/js/ui.datepicker.js', __FILE__));
	wp_enqueue_style('datepickercss', plugins_url('/css/ui-lightness/jquery-ui-1.8.4.custom.css', __FILE__));
}

// Register function to be called when posts are saved
// The function will receive 2 arguments
add_action('save_post', 'save_events_listing_fields', 10, 2);

function save_events_listing_fields($ID = false, $book_review = false)
{
    // Check post type for book reviews
    if ($book_review->post_type == 'events_listing')
    {
        // Store data in post meta table if present in post data
        if (isset($_POST['events_listing_date']) && $_POST['events_listing_date'] != '')
        {
            update_post_meta($ID, "events_listing_date", $_POST['events_listing_date']);
        }
        else
        {
            update_post_meta($ID, "events_listing_date", date('Y-m-d'));
        }
       
    }
}

// Register function to be called when posts are deleted
add_action('delete_post', 'events_listing_delete_fields');

// Function to delete post custom fields when post is deleted
function events_listing_delete_fields($book_review_id)
{
    delete_post_meta($book_review_id, "book_author");
    delete_post_meta($book_review_id, "book_rating");
}

// Register function to be called when column list is being prepared
add_filter("manage_edit-events_listing_columns", "events_listing_add_columns");

// Function to add columns for author and type in book review listing
// and remove comments columns
function events_listing_add_columns($columns)
{
        $columns["events_listing_date"] = "Event Date";
        unset($columns['date']);
        unset($columns['author']);

        return $columns;
}

// Register function to be called when custom post columns are rendered
add_action("manage_posts_custom_column", "events_listing_populate_columns");

// Function to send data for custom columns when displaying items
function events_listing_populate_columns($column)
{
    global $post;
   
    // Check column name and send back appropriate data
    if ("events_listing_date" == $column)
    {
        $events_listing_date = esc_html(get_post_meta(get_the_ID(), 'events_listing_date', true));
        echo $events_listing_date;
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
                'orderby' => 'meta_value'
        ) );
    }

    return $vars;
}

?>