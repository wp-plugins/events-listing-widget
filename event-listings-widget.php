<?php
/*
 Plugin Name: Events Listing Widget
 Plugin URI: http://yannickcorner.nayanna.biz/wordpress-plugins/events-listing-widget
 Description: Creates a new post type to manage events and a widget to display them chronologically
 Version: 1.1.1
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
                
                $options = get_option('events_listing_Options');
                
                switch ($options['date_format'])
                {
                    case 'YYYY-MM-DD':
                        $phpformatstring = "Y-m-d";
                    break;
                    case 'DD/MM/YYYY':
                        $phpformatstring = "d/m/Y";
                    break;
                }

                $query = "SELECT *, wpostmetadate.meta_value as event_date, wpostmetaurl.meta_value as event_url 
                                        FROM $wpdb->posts wposts, $wpdb->postmeta wpostmetadate, $wpdb->postmeta wpostmetaurl
                                        WHERE wposts.ID = wpostmetadate.post_id 
                                        AND wpostmetadate.meta_key = 'events_listing_date' 
                                        AND wposts.ID = wpostmetaurl.post_id
                                        AND wpostmetaurl.meta_key = 'events_listing_url' 
                                        AND wposts.post_type = 'events_listing' 
                                        AND wposts.post_status = 'publish' 
                                        AND FROM_UNIXTIME(wpostmetadate.meta_value) >= '" . date('Y-m-d') . "' AND FROM_UNIXTIME(wpostmetadate.meta_value) < '" . date('Y-m-d', strtotime($widget_lookahead . 'months')) . "' 
                                        ORDER BY event_date ASC
                                        LIMIT 0, " . $widget_display_count;
                
                $events = $wpdb->get_results($query, ARRAY_A);

                // Check if any posts were returned by query
                if ( $events )
                {
                        // Cycle through all items retrieved
                        foreach ($events as $event):
                                echo '<div class="events-listing">';
                                echo '<div class="events-listing-title"><a href="';
                                if ( !empty( $event['event_url'] ) )
                                    echo $event['event_url'];
                                else
                                    echo get_permalink($event['ID']);
                                echo '" target="_blank" >';
                                echo get_the_title($event['ID']);
                                echo '</a></div>';
                                echo '<div class="events-listing-date">' . $options['before_date'] . date($phpformatstring, $event['event_date']) . $options['after_date'] . '</div>';
                                echo '<div class="events-listing-content">' . $this->prepare_the_content($event['post_content'], $event['ID'], $widget_more_label) . '</div>';
                                echo '</div>';
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
    add_action('admin_post_save_events_listing_options', 'process_events_listing_options');
}

function events_listing_display_meta_box($event_listing)
{ 
    $options = get_option('events_listing_Options');
    
    switch ($options['date_format'])
    {
        case 'YYYY-MM-DD':
            $phpformatstring = "Y-m-d";
            $datepickerformatstring = "yy-mm-dd";
            break;
        case 'DD/MM/YYYY':
            $phpformatstring = "d/m/Y";
            $datepickerformatstring = "dd/mm/yy";
            break;
    }
    
    //echo "Post Meta" . get_post_meta($event_listing->ID, 'events_listing_date', true);
    
    // Retrieve current author and rating based on book review ID
    $eventdate = date($phpformatstring, intval(get_post_meta($event_listing->ID, 'events_listing_date', true)));
	if ( $eventdate == "" ) $eventdate = date( $phpformatstring );
    $eventurl = esc_html( get_post_meta( $event_listing->ID, 'events_listing_url', true ) );
    ?>
    <table>
        <tr>
            <td style="width: 100px">Event Date</td>
            <td><input type='text' size='20' id='events_listing_date' name='events_listing_date' value='<?php echo $eventdate; ?>' /></td>
        </tr>
        <tr>
            <td style="width: 100px">Event URL</td>
            <td><input type='text' size='60' id='events_listing_url' name='events_listing_url' value='<?php echo $eventurl; ?>' /></td>
        </tr>
	</table>
	
	<script type='text/javascript'>
		jQuery(document).ready(function() {
		jQuery('#events_listing_date').datepicker({minDate: '+0', dateFormat: '<?php echo $datepickerformatstring; ?>', showOn: 'both', constrainInput: true, buttonImage: '<?php echo plugins_url("/images/calendar.png", __FILE__); ?>'}) });
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

function save_events_listing_fields($ID = false, $event_listing = false)
{
    $options = get_option('events_listing_Options');
    
    switch ($options['date_format'])
    {
        case 'YYYY-MM-DD':
            $phpformatstring = "Y-m-d";
            break;
        case 'DD/MM/YYYY':
            $phpformatstring = "d/m/Y";
            break;
    }
    
    // Check post type for book reviews
    if ($event_listing->post_type == 'events_listing')
    {
        // Store data in post meta table if present in post data
        if (isset($_POST['events_listing_date']) && $_POST['events_listing_date'] != '')
        {
            $swapslashes = str_replace("/", "-", $_POST['events_listing_date']);
            update_post_meta($ID, "events_listing_date", strtotime($swapslashes));
        }
        else
        {
            update_post_meta($ID, "events_listing_date", strtotime("now"));
        }
        
        if ( !empty( $_POST['events_listing_url'] ) )
        {
            update_post_meta( $ID, "events_listing_url", $_POST['events_listing_url'] );
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
    
    $options = get_option('events_listing_Options');
    
    switch ($options['date_format'])
    {
        case 'YYYY-MM-DD':
            $phpformatstring = "Y-m-d";
            break;
        case 'DD/MM/YYYY':
            $phpformatstring = "d/m/Y";
            break;
    }
   
    // Check column name and send back appropriate data
    if ("events_listing_date" == $column)
    {
        $events_listing_date = esc_html(get_post_meta(get_the_ID(), 'events_listing_date', true));
        echo date($phpformatstring, $events_listing_date);
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

global $optionspage;

register_activation_hook(__FILE__, 'events_listing_activation');

function events_listing_activation() {
    if (get_option('events_listing_Options') === false) {
        $NewOptions['date_format'] = 'YYYY-MM-DD';
        $NewOptions['before_date'] = '';
        $NewOptions['after_date'] = '';
        add_option('events_listing_Options', $NewOptions);
        
        global $wpdb;
        
        $olddates = $wpdb->get_results("select * from " .
                $wpdb->get_blog_prefix() .
                "postmeta where meta_key = 'events_listing_date'", ARRAY_A);
        
        if ($olddates)
        {
            foreach ($olddates as $olddate)
            {
                $query = "update " . $wpdb->get_blog_prefix() .
                        "postmeta set meta_value = " . 
                        strtotime($olddate['meta_value']) . " where meta_id = ".
                        $olddate['meta_id'] . " and post_id = " .
                        $olddate['post_id'] . " and meta_key = 'events_listing_date'";
                
                $wpdb->query($query);
            }
        }
    }
}

add_action('admin_menu', 'Ch3DOA_settings_menu');

function Ch3DOA_settings_menu()
{
    global $optionspage;
    
    $optionspage = add_options_page('Events Listing Widget Configuration', 'Events Listing Widget', 'manage_options', 'events_listing_config', 'events_listing_config_page');
    
    if ($optionspage)
        add_action( 'load-' . $optionspage, 'Ch3DOA_create_meta_boxes' );
}

function Ch3DOA_create_meta_boxes()
{    
    global $optionspage;
    wp_enqueue_script('common');
    wp_enqueue_script('wp-lists');
    wp_enqueue_script('postbox');
    
    add_meta_box('events_listing_general_meta_box', 'General Settings', 'events_listing_plugin_meta_box', $optionspage, 'normal', 'core');    
}

function events_listing_config_page() {
    // Retrieve plugin configuration options from database
    $options = get_option('events_listing_Options');
    global $optionspage;
    ?>
	<div id="events-listing-general" class="wrap">
	<h2>Events Listing Widget Configuration</h2>
        
	<form action="admin-post.php" method="post">
        <input type="hidden" name="action" value="save_events_listing_options" />
        
        <!-- Adding security through hidden referrer field -->
        <?php wp_nonce_field('events_listing'); ?>
        
        <!-- Security fields for meta box save processing -->
        <?php wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false ); ?>
        <?php wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false ); ?>

        <div id="poststuff" class="metabox-holder">
        <div id="post-body">
            <div id="post-body-content">
                <?php do_meta_boxes($optionspage, 'normal', $options); ?>
                <input type="submit" value="Submit" class="button-primary"/>	
            </div>
        </div>
        <br class="clear"/>
        </div>	
        </form>
        </div>
<script type="text/javascript">
        //<![CDATA[
        jQuery(document).ready( function($) {
            
                // close postboxes that should be closed
                $('.if-js-closed').removeClass('if-js-closed').addClass('closed');
                
                // postboxes setup
                postboxes.add_postbox_toggles('<?php echo $optionspage; ?>');
        });
        //]]>
</script>
		
<?php }

function events_listing_plugin_meta_box($options)
{ 
    ?>
    <table>
        <tr>
            <td style="width: 100px">Date Format</td>
            <td>
        <?php $dateoptions = array('YYYY-MM-DD', 'DD/MM/YYYY' ); ?>
            <select id="date_format" name="date_format">
            <?php foreach ($dateoptions as $dateoption): 
                if ($options['date_format'] == $dateoption)
                    $selected = "selected='selected'";
                else
                    $selected = "";
                ?>
                <option id="<?php echo $dateoption; ?>" <?php echo $selected; ?>><?php echo $dateoption; ?></option>
            <?php endforeach; ?>
            </select>			
        </label></td>               
        </tr>
        <tr>
            <td>Before Date</td>
            <td><input type="text" size="30" id="before_date" name="before_date" value="<?php echo $options['before_date']; ?>" /></td>
        </tr>
        <tr>
            <td>After Date</td>
            <td><input type="text" size="30" id="after_date" name="after_date" value="<?php echo $options['after_date']; ?>" /></td>
        </tr>
        
    </table>
    
<?php }

function process_events_listing_options() {
        // Check that user has proper security level
        if ( !current_user_can('manage_options') )
                wp_die( 'Not allowed' );
        
        // Check that nonce field created in configuration form
        // is present
        check_admin_referer('events_listing');

        // Retrieve original plugin options array
        $options = get_option('events_listing_Options');

        // Cycle through all text form fields and store their values
        // in the options array
        foreach (array('date_format', 'before_date', 'after_date') as $option_name) {
            if (isset($_POST[$option_name])) {
                    $options[$option_name] = $_POST[$option_name];
            }
        }

        // Store updated options array to database
        update_option('events_listing_Options', $options);

        // Redirect the page to the configuration form that was
        // processed
        wp_redirect(events_listing_remove_querystring_var($_POST['_wp_http_referer'], 'message') . '&message=1');
}

function events_listing_remove_querystring_var($url, $key)
{ 
    $keypos = strpos($url, $key);
    if ($keypos)
    {
            $ampersandpos = strpos($url, '&', $keypos);
            $newurl = substr($url, 0, $keypos - 1);

            if ($ampersandpos)
                    $newurl .= substr($url, $ampersandpos);
    }
    else
            $newurl = $url;

    return $newurl; 
}
?>