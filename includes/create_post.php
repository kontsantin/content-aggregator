<?php

// Создание поста на основе спарсенной статьи
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';


// Проверка существования поста по заголовку
function post_exists_by_title($title) {
    global $wpdb;
    $query = $wpdb->prepare(
        "SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'news' AND post_status = 'publish'",
        $title
    );
    return $wpdb->get_var($query);
}
function create_post(string $title, string $content, string $region, string $thumbnail_url = null)
{
     // Проверка существования поста с таким заголовком
    if (post_exists_by_title($title)) {
        error_log('Пост с заголовком "' . $title . '" уже существует. Пропуск создания.');
        return;
    }
    // Создание нового поста
    $postarr = [
        'post_title'   => $title,
        'post_content' => $content,
        'post_type'    => 'news',
        'post_status'  => 'publish'
    ];

    $post_id = wp_insert_post($postarr);

    if (!is_wp_error($post_id)) {
        if (!empty($region)) {
            // Проверка существования региона
            $term = get_term_by('id', $region, 'region');
            if ($term) {
                wp_set_object_terms($post_id, [$term->term_id], 'region');
            } else {
                error_log('Регион с ID ' . $region . ' не найден.');
            }
        }

        if (!empty($thumbnail_url)) {
            set_post_thumbnail_from_url($post_id, $thumbnail_url);
        }
    } else {
        error_log('Ошибка создания поста: ' . $post_id->get_error_message());
    }
}

// Установка миниатюры поста по URL
function set_post_thumbnail_from_url($post_id, $url)
{
    if (empty($url) || empty($post_id)) {
        error_log('URL или ID поста пустой.');
        return;
    }

    // Загрузка изображения в медиабиблиотеку WordPress
    $tmp = download_url($url);
    if (is_wp_error($tmp)) {
        error_log('Ошибка загрузки изображения: ' . $tmp->get_error_message());
        return;
    }

    $desc = explode("?", basename($url))[0];
    $file_array = array(
        'name' => $desc,
        'tmp_name' => $tmp
    );

    // Загрузка изображения в WordPress
    $id = media_handle_sideload($file_array, $post_id, $desc);
    // Если загрузка завершена с ошибкой, удаляем временный файл
    if (is_wp_error($id)) {
        @unlink($file_array['tmp_name']);
        error_log('Ошибка загрузки миниатюры: ' . $id->get_error_message());
        return;
    }

    // Установка миниатюры для поста
    $result = set_post_thumbnail($post_id, $id);

    if (is_wp_error($result)) {
        error_log('Ошибка установки миниатюры для поста с ID: ' . $post_id);
    } else {
        error_log('Миниатюра успешно установлена для поста с ID: ' . $post_id);
    }

    // Удаление временного файла
    @unlink($file_array['tmp_name']);
}
