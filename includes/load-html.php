<?php

if (!function_exists('load_html')) {
    // Функция для загрузки HTML с URL
    function load_html($url) {
        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return false;
        }

        $html = wp_remote_retrieve_body($response);
        return $html;
    }
}
?>
