<?php
// utility-functions.php
function post_exists_by_title($title) {
    global $wpdb;
    $query = $wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'post' AND post_status = 'publish'",
        $title
    );
    return $wpdb->get_var($query);
}
