<?php 
function fpep_events_init() {
    $labels = array(
        'name' 			     => __('Events'),
        'singular_name'      => __('Event'),
        'add_new'            => __('Add New Event'),
		'add_new_item'       => __('Add New Event'),
        'edit_item'	         => __('Edit Event'),
        'new_item'           => __('New Event'),
        'all_items'          => __('All Events'),
        'view_item'          => __('View Event'),
        'search_item'        => __('Search Events'),
        'not_found'          => __('No events found'),
        'not_found_in_trash' => __('No events found in trash'),
        'menu_name'          => __('Events')
	);
	$args = array(
        'public'            => true,
        'has_archive'       => true,
        'labels'            => $labels,
		'show_in_rest'      => true,
		'show_in_nav_menus' => true,
		'supports'          => array('title', 'thumbnail', 'editor', 'author'),
		'rewrite'           => array('slug' => 'events'),
        'menu_icon'         => 'dashicons-calendar-alt',
        'menu_position'     => 4
	);

	register_post_type('fpep-event', $args);
}
add_action( 'init', 'fpep_events_init' );

function fpep_event_column_headers( $columns ) {

    $columns = array(
        'cb'         => '<input type="checkbox" />', 
        'title'      => __('Title'),
        'start_date' => __('Start Date'),
        'end_date'   => __('End Date'),
        'time'       => __('Time'),
        'recur'      => __('Recurring?'),
    );

    return $columns;
}

add_filter('manage_edit-fpep-event_columns', 'fpep_event_column_headers');

function fpep_event_column_data( $column, $post_id ) {

    $output = '';

    switch( $column ) {
        case 'start_date' :
            $s_date = strtotime(get_field( 'start_date' ));
            $start_date = date('m/d/Y', $s_date);
            $output .= $start_date;
        break;
        case 'end_date' :
            $e_date = strtotime(get_field( 'end_date' ));
            $format_date = date('m/d/Y', $e_date);
            $end_date = $format_date == '01/01/1970' ? '' : $format_date;
            $output .= $end_date;
        break;
        case 'time' :
            $start_time = get_field( 'start_time' );
            $end_time = get_field( 'end_time' );
            $output .= $start_time . ' - ' . $end_time;
        break;
        case 'recur' :
            $event_type = get_field( 'event_type' );
            $recur = $event_type == 'recur' ? 'Yes' : 'No';
            $output .= $recur;
        break;
    }

    echo $output;

}

add_filter('manage_fpep-event_posts_custom_column', 'fpep_event_column_data', 1, 2);

if( function_exists('acf_add_local_field_group') ):

    acf_add_local_field_group(array(
        'key' => 'group_60f85305983c9',
        'title' => 'Events',
        'fields' => array(
            array(
                'key' => 'field_60f8568a9225a',
                'label' => 'Event Type',
                'name' => 'event_type',
                'type' => 'button_group',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => array(
                    'single' => 'Single Day',
                    'multiple' => 'Multiple Days',
                    'recur' => 'Recurring',
                ),
                'allow_null' => 0,
                'default_value' => '',
                'layout' => 'horizontal',
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_6104101d59bc1',
                'label' => 'Recurring Day',
                'name' => 'recurring_day',
                'type' => 'button_group',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_60f8568a9225a',
                            'operator' => '==',
                            'value' => 'recur',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => array(
                    'monday' => 'Monday',
                    'tuesday' => 'Tuesday',
                    'wednesday' => 'Wednesday',
                    'thursday' => 'Thursday',
                    'friday' => 'Friday',
                    'saturday' => 'Saturday',
                    'sunday' => 'Sunday',
                ),
                'allow_null' => 0,
                'default_value' => '',
                'layout' => 'horizontal',
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_60f8548e23cef',
                'label' => 'Start Date',
                'name' => 'start_date',
                'type' => 'date_picker',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'display_format' => 'm/d/Y',
                'return_format' => 'Ymd',
                'first_day' => 0,
            ),
            array(
                'key' => 'field_60f85613ae102',
                'label' => 'End Date',
                'name' => 'end_date',
                'type' => 'date_picker',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => array(
                    array(
                        array(
                            'field' => 'field_60f8568a9225a',
                            'operator' => '==',
                            'value' => 'multiple',
                        ),
                    ),
                ),
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'display_format' => 'm/d/Y',
                'return_format' => 'Ymd',
                'first_day' => 0,
            ),
            array(
                'key' => 'field_60f8556023cf1',
                'label' => 'Start Time',
                'name' => 'start_time',
                'type' => 'time_picker',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'display_format' => 'g:i a',
                'return_format' => 'g:i a',
            ),
            array(
                'key' => 'field_60f85626ae103',
                'label' => 'End Time',
                'name' => 'end_time',
                'type' => 'time_picker',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '50',
                    'class' => '',
                    'id' => '',
                ),
                'display_format' => 'g:i a',
                'return_format' => 'g:i a',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'fpep-event',
                ),
            ),
        ),
        'menu_order' => 0,
        'position' => 'acf_after_title',
        'style' => 'default',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
    ));
    
    endif;