<?php
/*
Plugin Name: Test Plugin
Version: 1.0.0
*/


// Define the plugin version
define( 'MY_PLUGIN_VERSION', '1.0.0' );

// Check for updates
add_action( 'admin_init', 'my_plugin_check_updates' );
function my_plugin_check_updates() {
    $latest_release = wp_remote_get( 'https://api.github.com/repos/insc15/test-plugin-update-feature/releases/latest', array(
        'headers' => array( 'Accept' => 'application/vnd.github.v3+json' ),
    ) );
    if ( is_wp_error( $latest_release ) ) {
        // Handle the API error
        return;
    }
    $latest_version = $latest_release['tag_name'];
    if ( version_compare( $latest_version, MY_PLUGIN_VERSION, '>' ) ) {
        // Display a message in the WordPress admin area that an update is available
        add_action( 'admin_notices', 'my_plugin_update_notice' );
    }
}

// Display the update notice
function my_plugin_update_notice() {
    printf( '<div class="notice notice-info is-dismissible"><p>%s</p><p><a href="%s" class="button-primary">%s</a></p></div>', __( 'A new version of My Plugin is available!', 'test-plugin' ), 'https://github.com/insc15/test-plugin-update-feature/releases/latest/download/test-plugin.zip', __( 'Update now', 'test-plugin' ) );
}

// Auto-update the plugin
add_filter( 'pre_set_site_transient_update_plugins', 'my_plugin_auto_update' );
function my_plugin_auto_update( $transient ) {
    if ( empty( $transient->checked ) ) {
        return $transient;
    }
    $latest_release = wp_remote_get( 'https://api.github.com/repos/insc15/test-plugin-update-feature/releases/latest', array(
        'headers' => array( 'Accept' => 'application/vnd.github.v3+json' ),
    ) );
    if ( is_wp_error( $latest_release ) ) {
        // Handle the API error
        return $transient;
    }
    $latest_version = $latest_release['tag_name'];
    if ( version_compare( $latest_version, MY_PLUGIN_VERSION, '>' ) ) {
        $download_url = 'https://github.com/insc15/test-plugin-update-feature/releases/latest/download/test-plugin.zip';
        $tmp_file = download_url( $download_url );
        if ( is_wp_error( $tmp_file ) ) {
            // Handle the download error
            return $transient;
        }
        WP_Filesystem();
        $unzip_to = WP_CONTENT_DIR . '/plugins/';
        $unzip_result = unzip_file( $tmp_file, $unzip_to );
        if ( is_wp_error( $unzip_result ) ) {
            // Handle the unzip error
            return $transient;
        }
        deactivate_plugins( 'test-plugin/test-plugin.php' );
        $source = $unzip_to . 'test-plugin/';
        $destination = WP_PLUGIN_DIR . '/test-plugin/';
        $filesystem = WP_Filesystem();
        $filesystem->move( $source, $destination, true );
        activate_plugins( 'test-plugin/test-plugin.php' );
        $transient->response['test-plugin/test-plugin.php'] = (object) array(
            'slug' => 'test-plugin',
            'new_version' => $latest_version,
            'url' => 'https://github.com/insc15/repo',
            'package' => $download_url,
        );
    }
    return $transient;
}
