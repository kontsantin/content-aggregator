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


// Создаем страницу настроек и выводим спарсенный контент
function content_aggregator_add_admin_menu() {
    add_options_page('Content Aggregator Settings', 'Content Aggregator', 'manage_options', 'content-aggregator', 'content_aggregator_options_page');
}
add_action('admin_menu', 'content_aggregator_add_admin_menu');

function content_aggregator_options_page() {
    ?>
  <h1>Настройки Content Aggregator</h1>
        <form method="post" action="">
            <label for="content_aggregator_url">URL для парсинга:</label><br>
            <input type="text" id="content_aggregator_url" name="content_aggregator_url" value=""><br><br>
            
            <label for="content_aggregator_title_tag">Тег для заголовка:</label><br>
            <input type="text" id="content_aggregator_title_tag" name="content_aggregator_title_tag" value="h1"><br><br>
            
            <label for="content_aggregator_content_tag">Теги для контента:</label><br>
            <input type="text" id="content_aggregator_content_tag" name="content_aggregator_content_tag" value="p"><br><br>
            
            <label for="content_aggregator_region">Регион:</label><br>
            <select id="content_aggregator_region" name="content_aggregator_region">
                <?php
                // Получаем список всех регионов
                $regions = get_terms(array(
                    'taxonomy' => 'region',
                    'hide_empty' => false,
                ));

                // Выводим опции для выбора региона
                foreach ($regions as $region) {
                    echo '<option value="' . esc_attr($region->slug) . '">' . esc_html($region->name) . '</option>';
                }
                ?>
            </select><br><br>
            
            <input type="submit" name="content_aggregator_parse" value="Начать парсинг">
        </form>
        <hr>
        <h2>Результат парсинга</h2>
        <?php
        if (isset($_POST['content_aggregator_parse'])) {
            $url = esc_url($_POST['content_aggregator_url']);
            $title_tag = sanitize_text_field($_POST['content_aggregator_title_tag']);
            $content_tag = sanitize_text_field($_POST['content_aggregator_content_tag']);
            $region = sanitize_text_field($_POST['content_aggregator_region']); // Получаем выбранный регион

            $parsed_content = parse_content($url, $title_tag, $content_tag);

            if ($parsed_content) {
                echo '<h3>' . esc_html($parsed_content['title']) . '</h3>';
                echo '<div>' . wp_kses_post($parsed_content['content']) . '</div>';
                ?>
                <form method="post" action="">
                    <input type="hidden" name="content_aggregator_post" value="1">
                    <input type="hidden" name="content_aggregator_title" value="<?php echo esc_attr($parsed_content['title']); ?>">
                    <input type="hidden" name="content_aggregator_content" value="<?php echo esc_textarea($parsed_content['content']); ?>">
                    <input type="hidden" name="content_aggregator_region" value="<?php echo esc_attr($region); ?>"> <!-- Передаем регион для создания поста -->
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
            $region = sanitize_text_field($_POST['content_aggregator_region']); // Получаем регион для создания поста

            // Создаем пост с указанием региона
            $post_url = create_post($title, $content, $region);

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