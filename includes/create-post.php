<?php

if (!function_exists('create_post')) {
    // Функция для создания поста в WordPress
    function create_post($title, $content, $region) {
        $post_data = array(
            'post_title'    => $title,
            'post_content'  => $content,
            'post_status'   => 'publish',
            'post_author'   => get_current_user_id(),
            'post_type'     => 'news',
        );

        $post_id = wp_insert_post($post_data);

        if ($post_id && !is_wp_error($post_id)) {
            // Добавляем регион как таксонию к посту
            wp_set_object_terms($post_id, $region, 'region', false);

            return get_permalink($post_id);
        } else {
            return false;
        }
    }
}
?>
