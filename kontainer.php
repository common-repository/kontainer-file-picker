<?php
/**
 * Plugin Name:       Kontainer File Picker
 * Plugin URI:        https://kontainer.com/
 * Description:       Pull assets like images, videos, and product sheets straight from Kontainer into your WordPress media library.
 * Version:           1.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            Kontainer A/S
 * Author URI:        https://kontainer.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kontainer
 */

 require_once 'inc/settings.php';

//------------------------------------------------
// Register scrips
//------------------------------------------------

add_action( 'admin_enqueue_scripts', 'kontainer_load_script' );
function kontainer_load_script() {
    wp_enqueue_style( 'kontainer-admin-css',  plugin_dir_url( __FILE__ ) . '/assets/css/admin.css' );
    wp_enqueue_script( 'kontainer-admin-js', plugin_dir_url( __FILE__ ) . '/assets/js/admin.js', array( 'jquery' ), '1.0', true );

    $options = get_option( 'kontainer_url' );

    $scriptData = array(
        'kontainer_url' => $options,
    );

    wp_localize_script('kontainer-admin-js', 'kontainer_settings', $scriptData);
}



//------------------------------------------------
// Upload recieved image and create thumbnails and metadata
//------------------------------------------------

add_action('admin_post_custom_action_hook', 'kontainercallback');

function kontainercallback()
{
    require_once(ABSPATH . 'wp-admin/includes/file.php');

    $imgUrl = esc_url_raw($_POST['img_url']);
    $imgDesc = sanitize_textarea_field($_POST['img_desc']);
    //$imgType = sanitize_text_field($_POST['img_type']);
    $imgAlt = sanitize_text_field($_POST['img_alt']);
    $imgExt = sanitize_text_field($_POST['img_ext']);

    $allowedFiles = ['jpg', 'jpeg', 'gif', 'png', 'webp', 'avif'];

    $imgExt = strtolower($imgExt);

    if (!$imgExt || !in_array($imgExt, $allowedFiles, true) ) {
        header('Content-Type: application/json');
        $output = [
            'status'    => 'error',
            'msg'       => 'File needs to be an image, uploaded type was: ' . $imgExt,
        ];
        echo json_encode($output);
        die();
    }

    $tempFile = download_url($imgUrl, 300);

    if (!is_wp_error($tempFile)) {
        $file = [
            'name'     => strtok(basename($imgUrl), '?'),
            'type'     => 'image/' . $imgExt,
            'tmp_name' => $tempFile,
            'error'    => 0,
            'size'     => filesize($tempFile),
        ];

        $overrides = [
            'test_form' => false,
            'test_size' => true
        ];

        $results = wp_handle_sideload($file, $overrides);

        if (!empty($results['error'])) {
           header('Content-Type: application/json');
           $output = [
            'status'    => 'error',
            'msg'       => "Something went wrong, could not upload chosen file.",
            'data'      => $results['error'],
            ];
            echo json_encode($output);
            die();
        }


        $filename  = $results['file']; // Full path to the file
        $localUrl = $results['url'];  // URL to the file in the uploads dir
        $type      = $results['type']; // MIME type of the file

        $attach_id = wp_insert_attachment(
            [
                'guid' => $localUrl,
                'post_title' => $imgAlt,
                'post_excerpt' => $imgDesc,
                'post_content' => $imgDesc,
                'post_mime_type' => $type,
            ],
            $filename,
            0
        );

        $attachData = wp_generate_attachment_metadata($attach_id, $filename);
        wp_update_attachment_metadata($attach_id, $attachData);

        header('Content-Type: application/json');
        $output = [
            'status'    => 'success',
            'msg'       => "Success",
            'data'      => $attachData,
        ];

        echo json_encode($output);
    }
}
