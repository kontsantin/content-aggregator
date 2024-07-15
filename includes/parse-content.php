<?php
// parse-content.php

function parse_content($url, $default_tags, $custom_tags, $selectors) {
    $html = load_html($url);

    if (!$html) {
        return false;
    }

    require_once(plugin_dir_path(__FILE__) . 'simple_html_dom.php');
    $dom = str_get_html($html);

    $title = $dom->find($default_tags['title_tag'], 0)->plaintext;
    $content = '';

    foreach ($dom->find($default_tags['content_tag']) as $element) {
        $content .= $element->innertext;
    }

    // Добавление дополнительных пользовательских тегов
    if (!empty($custom_tags)) {
        $custom_tags_array = explode(',', $custom_tags);
        foreach ($custom_tags_array as $tag) {
            foreach ($dom->find(trim($tag)) as $element) {
                $content .= $element->innertext;
            }
        }
    }

    // Парсинг по пользовательским селекторам
    if (!empty($selectors)) {
        $selectors_array = explode(',', $selectors);
        foreach ($selectors_array as $selector) {
            foreach ($dom->find(trim($selector)) as $element) {
                $content .= $element->innertext;
            }
        }
    }

    return array(
        'title' => $title,
        'content' => $content,
    );
}


// create-post.php

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
