<?php

//------------------------------------------------
// Register Settings page and fields
//------------------------------------------------

function kontainer_register_options_page()
{
    add_options_page(
        'Kontainer',
        'Kontainer',
        'manage_options',
        'kontainer',
        'kontainer_options_page_html'
    );
}
add_action('admin_menu', 'kontainer_register_options_page');

function kontainer_options_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }
?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

        <?php settings_errors(); ?>
        <form action="options.php" method="post">
          <?php settings_fields('kontainer-settings-fields'); ?>
          <?php do_settings_sections('Kontainer'); ?>
          <?php submit_button('Save Settings'); ?>
        </form>
    </div>
<?php
} 


// Register settings fields
function kontainer_settings_init() {
    
    register_setting('kontainer-settings-fields', 'kontainer_url');
    add_settings_section('kontainer-required', 'Required options', '', 'Kontainer');
    add_settings_field('required-url', 'Kontainer URL', 'kontainer_cb_plainfields', 'Kontainer', 'kontainer-required');
}
add_action('admin_init', 'kontainer_settings_init');
 

function kontainer_cb_plainfields() {
    $url = esc_attr(get_option('kontainer_url'));
    echo '<input type="text" name="kontainer_url" placeholder="https://example.kontainer.com" value="' . $url . '">';
}