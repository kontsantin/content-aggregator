<?php
/**
 * Plugin Name: Content Aggregator
 * Plugin URI: http://example.com/content-aggregator
 * Description: Простой плагин для парсинга и постинга контента с других сайтов.
 * Version: 1.0
 * Author: Ваше Имя
 * Author URI: http://example.com
 */

// Функция для загрузки HTML с URL
function load_html($url) {
    $response = wp_remote_get($url);

    if (is_wp_error($response)) {
        return false;
    }

    $html = wp_remote_retrieve_body($response);
    return $html;
}

// Функция для парсинга контента
function parse_content($url, $title_tag, $content_tag) {
    $html = load_html($url);

    if (!$html) {
        return false;
    }

    require_once(plugin_dir_path(__FILE__) . 'simple_html_dom.php');
    $dom = str_get_html($html);

    $title = $dom->find($title_tag, 0)->plaintext;
    $content = '';

    foreach ($dom->find($content_tag) as $element) {
        $content .= $element->innertext;
    }

    return array(
        'title' => $title,
        'content' => $content,
    );
}

// Функция для создания поста в WordPress
function create_post($title, $content) {
    $post_data = array(
        'post_title'    => $title,
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_author'   => get_current_user_id(),
        'post_type'     => 'post',
    );

    $post_id = wp_insert_post($post_data);

    if ($post_id) {
        return get_permalink($post_id);
    } else {
        return false;
    }
}

// Создаем страницу настроек и выводим спарсенный контент
function content_aggregator_add_admin_menu() {
    add_options_page('Content Aggregator Settings', 'Content Aggregator', 'manage_options', 'content-aggregator', 'content_aggregator_options_page');
}
add_action('admin_menu', 'content_aggregator_add_admin_menu');

function content_aggregator_options_page() {
    ?>
    <div class="wrap">
        <h1>Настройки Content Aggregator</h1>
        <form method="post" action="">
            <label for="content_aggregator_url">URL для парсинга:</label><br>
            <input type="text" id="content_aggregator_url" name="content_aggregator_url" value=""><br><br>
            
            <label for="content_aggregator_title_tag">Тег для заголовка:</label><br>
            <input type="text" id="content_aggregator_title_tag" name="content_aggregator_title_tag" value="h1"><br><br>
            
            <label for="content_aggregator_content_tag">Теги для контента:</label><br>
            <input type="text" id="content_aggregator_content_tag" name="content_aggregator_content_tag" value="p"><br><br>
            
            <input type="submit" name="content_aggregator_parse" value="Начать парсинг">
        </form>
        <hr>
        <h2>Результат парсинга</h2>
        <?php
        if (isset($_POST['content_aggregator_parse'])) {
            $url = esc_url($_POST['content_aggregator_url']);
            $title_tag = sanitize_text_field($_POST['content_aggregator_title_tag']);
            $content_tag = sanitize_text_field($_POST['content_aggregator_content_tag']);

            $parsed_content = parse_content($url, $title_tag, $content_tag);

            if ($parsed_content) {
                echo '<h3>' . esc_html($parsed_content['title']) . '</h3>';
                echo '<div>' . wp_kses_post($parsed_content['content']) . '</div>';
                ?>
                <form method="post" action="">
                    <input type="hidden" name="content_aggregator_post" value="1">
                    <input type="hidden" name="content_aggregator_title" value="<?php echo esc_attr($parsed_content['title']); ?>">
                    <input type="hidden" name="content_aggregator_content" value="<?php echo esc_textarea($parsed_content['content']); ?>">
                    <input type="submit" value="Опубликовать статью">
                </form>
                <?php
            } else {
                echo '<p>Не удалось загрузить или спарсить контент.</p>';
            }
        }

        if (isset($_POST['content_aggregator_post'])) {
            $title = sanitize_text_field($_POST['content_aggregator_title']);
            $content = wp_kses_post($_POST['content_aggregator_content']);

            $post_url = create_post($title, $content);

            if ($post_url) {
                echo '<p>Статья успешно опубликована: <a href="' . esc_url($post_url) . '" target="_blank">' . esc_html($title) . '</a></p>';
            } else {
                echo '<p>Ошибка при публикации статьи.</p>';
            }
        }
        ?>
    </div>
    <?php
}
