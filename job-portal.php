<?php
/**
 * Plugin Name: Job Portal
 * Plugin URI: https://example.com/plugins/job-portal/
 * Description: A plugin for managing job postings and applications.
 * Version: 1.0.0
 * Author: Ali Ahmed
 * Author URI: https://example.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Add a menu item for the Job Portal in the admin menu
add_action('admin_menu', 'job_portal_menu');

// Register activation hook
register_activation_hook(__FILE__, 'my_plugin_create_table');
register_deactivation_hook(__FILE__, 'my_plugin_delete_table');

function my_plugin_delete_table()
{
    global $wpdb;

    // Table name
    $table_name = $wpdb->prefix . 'job_portal';

    // Delete table
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

function my_plugin_create_table()
{
    global $wpdb;

    // Table name
    $table_name = $wpdb->prefix . 'job_portal';
    $charset_collate = $wpdb->get_charset_collate();
    // Table creation SQL
    $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(200)   NULL,
            agency varchar(200)  NULL,
            department varchar(200)  NULL,
            location varchar(200)  NULL,
            jobUrl varchar(200)  UNIQUE,
            subagency varchar(200)  NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

    // Include upgrade.php for dbDelta function
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Create table
    dbDelta($sql);
}


function job_portal_menu()
{
    add_menu_page(
        'Job Portal', // Page title
        'Job Portal', // Menu title
        'manage_options', // Capability required to access the page
        'job-portal', // Menu slug
        'job_portal_page', // Callback function to render the page
        'dashicons-businessman', // Icon URL
        30 // Position in the menu
    );
}

// Render the Job Portal page in the admin area
function job_portal_page()
{
    // Check if the user has the required capability
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }

    // If the form is submitted, process the uploaded file
    if (isset($_POST['submit'])) {
        // Check if a file was uploaded
        if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['file']['tmp_name'];
            $file_name = $_FILES['file']['name'];
            $json_data = file_get_contents($file);
            $data = json_decode($json_data, true);
            if ($data) {
                global $wpdb;

                $table_name = $wpdb->prefix . 'job_portal';
                echo '<div class="notice notice-success"><p>File uploaded and processed successfully!</p></div>';
                // Echo the data line by line
                foreach ($data as $item) {
                    $data = array(
                        'title' => $item['jobTitle'],
                        'agency' => $item['agency'],
                        'department' => $item['department'],
                        'location' => $item['location'],
                        'jobUrl' => 'https://www.usajobs.gov' . $item['jobUrl'],
                        'subagency' => $item['subagency'],
                    );
                    try {
                        $wpdb->insert($table_name, $data);
                    } catch (Exception $e) {
                        error_log('Error inserting data: ' . $e->getMessage());
                    }

                }
            } else {
                echo '<div class="notice notice-error"><p>File upload failed.</p></div>';
            }
            // Process the file here
            echo '<div class="notice notice-success"><p>File uploaded successfully!</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>File upload failed.</p></div>';
        }
    }

    // Display the Job Portal admin screen with a file upload form
    echo '<div class="wrap">';
    echo '<h1>Job Portal</h1>';
    echo '<form class="border" method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="file">';
    echo '<input type="submit" name="submit" value="Upload">';
    echo '</form>';
    echo '</div>';


    global $wpdb;

    $table_name = $wpdb->prefix . 'job_portal';

    $rows = $wpdb->get_results("SELECT * FROM $table_name");

    if (!empty($rows)) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Title</th><th>Agency</th><th>Department</th><th>Location</th><th>Url</th><th>Sub Agency</th></tr>';
        foreach ($rows as $row) {
            echo '<tr>';
            echo '<td>' . $row->id . '</td>';
            echo '<td>' . $row->title . '</td>';
            echo '<td>' . $row->agency . '</td>';
            echo '<td>' . $row->department . '</td>';
            echo '<td>' . $row->location . '</td>';
            echo '<td>' . $row->jobUrl . '</td>';
            echo '<td>' . $row->subagency . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo 'No data found';
    }

}

function my_plugin_shortcode()
{
    global $wpdb;

    $table_name = $wpdb->prefix . 'job_portal';

    $results = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY RAND() LIMIT 20");

    if ($results) {
        $output = '<div>';
        foreach ($results as $row) {
            $output .= '<div style="border: 1px solid #626262;margin: 10px; padding:10px">';
            $output .= '<h2>' . $row->title . '</h2>';
            $output .= '<strong>' . $row->agency . '</strong>';
            $output .= $row->deparment . '<br>';
            $output .= $row->location . '<br>';
            $output .= $row->subagency . '<br>';
            $output .= '<a target="_blank" rel="nofollow" href="' . $row->jobUrl . '"><button>View Job</button></a><br>';
            $output .= '</div>';
        }
        $output .= '</div>';
        return $output;
    } else {
        return 'No data found';
    }
}

add_shortcode('my_shortcode', 'my_plugin_shortcode');
