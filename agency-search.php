<?php

/*
	Plugin Name: Agencies Search Plugin
	Plugin URI: http://marketinggroupplc.com/
	Description: Helps to search Agencies by Services, Language and Location
	Author: Dhananjaya Maha Malage
	Author URI: http://www.whenalive.com
	Version: 1.0
 */

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Agencies_Search extends WP_List_Table {

    var $example_data = array(
        array( 'ID' => 1,'booktitle' => 'Quarter Share', 'author' => 'Nathan Lowell',
            'isbn' => '978-0982514542' ),
        array( 'ID' => 2, 'booktitle' => '7th Son: Descent','author' => 'J. C. Hutchins',
            'isbn' => '0312384378' ),
        array( 'ID' => 3, 'booktitle' => 'Shadowmagic', 'author' => 'John Lenahan',
            'isbn' => '978-1905548927' ),
        array( 'ID' => 4, 'booktitle' => 'The Crown Conspiracy', 'author' => 'Michael J. Sullivan',
            'isbn' => '978-0979621130' ),
        array( 'ID' => 5, 'booktitle'     => 'Max Quick: The Pocket and the Pendant', 'author'    => 'Mark Jeffrey',
            'isbn' => '978-0061988929' ),
        array('ID' => 6, 'booktitle' => 'Jack Wakes Up: A Novel', 'author' => 'Seth Harwood',
            'isbn' => '978-0307454355' )
    );
    function __construct(){
        global $status, $page;

        parent::__construct( array(
            'singular'  => __( 'agency', 'agencieslist' ),     //singular name of the listed records
            'plural'    => __( 'agencies', 'agencieslist' ),   //plural name of the listed records
            'ajax'      => false        //does this table support ajax?

        ) );

        add_action( 'admin_head', array( &$this, 'admin_header' ) );

    }

    function admin_header() {
        $page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
        if( 'my_list_test' != $page )
            return;
        echo '<style type="text/css">';
        echo '.wp-list-table .column-id { width: 5%; }';
        echo '.wp-list-table .column-agencyname { width: 35%; }';
        echo '.wp-list-table .column-service { width: 20%; }';
        echo '.wp-list-table .column-language { width: 20%;}';
        echo '.wp-list-table .column-location { width: 20%;}';
        echo '</style>';
    }

    function no_items() {
        _e( 'No Agencies found' );
    }

    function column_default( $item, $column_name ) {
        switch( $column_name ) {
            case 'agencyname':
            case 'service':
            case 'language':
            case 'location':
                return $item[ $column_name ];
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'agencyname'  => array('agencyname',false),
            'service' => array('service',false),
            'language'   => array('language',false),
            'location'   => array('location',false)
        );
        return $sortable_columns;
    }

    function get_columns(){
        $columns = array(
            'cb'        => '<input type="checkbox" />',
            'agencyname' => __( 'Agency Name', 'agencieslist' ),
            'service'    => __( 'Service', 'agencieslist' ),
            'language'      => __( 'Language', 'agencieslist' ),
            'location'      => __( 'Location', 'agencieslist' )
        );
        return $columns;
    }

    function usort_reorder( $a, $b ) {
        // If no sort, default to title
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'agencyname';
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'asc';
        // Determine sort order
        $result = strcmp( $a[$orderby], $b[$orderby] );
        // Send final sort direction to usort
        return ( $order === 'asc' ) ? $result : -$result;
    }

    function column_booktitle($item){
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&book=%s">Edit</a>',$_REQUEST['page'],'edit',$item['ID']),
            'delete'    => sprintf('<a href="?page=%s&action=%s&book=%s">Delete</a>',$_REQUEST['page'],'delete',$item['ID']),
        );

        return sprintf('%1$s %2$s', $item['agencyname'], $this->row_actions($actions) );
    }

    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
        );
        return $actions;
    }

    function column_cb($item) {
        return sprintf(
            '<input type="checkbox" name="agency[]" value="%s" />', $item['ID']
        );
    }

    function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
        usort( $this->example_data, array( &$this, 'usort_reorder' ) );

        $per_page = 5;
        $current_page = $this->get_pagenum();
        $total_items = count( $this->example_data );

        // only ncessary because we have sample data
        $this->found_data = array_slice( $this->example_data,( ( $current_page-1 )* $per_page ), $per_page );

        $this->set_pagination_args( array(
            'total_items' => $total_items,                  //WE have to calculate the total number of items
            'per_page'    => $per_page                     //WE have to determine how many items to show on a page
        ) );
        $this->items = $this->found_data;
    }

} //class



function my_add_menu_items(){
    //$hook = add_menu_page( 'My Plugin List Table', 'My List Table Example', 'activate_plugins', 'my_list_test', 'my_render_list_page' );
    $hook = add_submenu_page( 'edit.php?post_type=agencies', 'Agencies Search', 'Agencies Search', 'activate_plugins', 'agencies_search_list', 'agencies_search_page' );
    add_action( "load-$hook", 'add_options' );
}

function add_options() {
    global $agenciesList;
    $option = 'per_page';
    $args = array(
        'label' => 'Agencies',
        'default' => 10,
        'option' => 'agencies_per_page'
    );
    add_screen_option( $option, $args );
    $agenciesList = new Agencies_Search();
}
add_action( 'admin_menu', 'my_add_menu_items' );



function agencies_search_page(){
    global $agenciesList;
    echo '</pre><div class="wrap"><h2>Agencies Search</h2>';
    $agenciesList->prepare_items();
    ?>
    <hr>
    <form method="post">

        <label for="service"><strong>Services:</strong></label>
        <select name="service">
            <option value="">Select a service</option>
        </select>

        <label for="language"><strong>Language:</strong></label>
        <select name="language">
            <option value="">Select a language</option>
        </select>

        <label for="location"><strong>Location:</strong></label>
        <select name="location">
            <option value="">Select a location</option>
        </select>
        <input type="hidden" name="page" value="ttest_list_table">
    <?php
    $agenciesList->search_box( 'search', 'search_id' );

    $agenciesList->display();
    echo '</form></div>';
}
