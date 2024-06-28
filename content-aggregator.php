<?php
/**
 * Plugin Name: Content Aggregator
 * Plugin URI: http://example.com/content-aggregator
 * Description: Простой плагин для парсинга и постинга контента с других сайтов.
 * Version: 1.0
 * Author: Ваше Имя
 * Author URI: http://example.com
 */

// Подключаем Guzzle и Simple HTML DOM Parser
require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');
require_once(plugin_dir_path(__FILE__) . 'simple_html_dom.php');

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

// Функция для загрузки HTML с URL с использованием Guzzle и пользовательского агента
function load_html($url, $user_agent = 'Mozilla/5.0') {
    $client = new Client([
        'headers' => [
            'User-Agent' => $user_agent,
        ],
        'cookies' => true,
    ]);

    try {
        $response = $client->request('GET', $url);
        $html = $response->getBody()->getContents();
        return $html;
    } catch (RequestException $e) {
        return false;
    }
}

// Функция для поиска ссылок на статьи по заданному CSS селектору и парсинга их содержимого
function parse_articles($url, $link_selector, $user_agent = 'Mozilla/5.0') {
    $html = load_html($url, $user_agent);

    if (!$html) {
        return false;
    }

    $dom = str_get_html($html);

    if (!$dom) {
        return false;
    }

    $article_links = $dom->find($link_selector);

    if (empty($article_links)) {
        return false;
    }

    $articles = [];

    foreach ($article_links as $link) {
        $article_url = $link->href;
        $article_html = load_html($article_url, $user_agent);

        if ($article_html) {
            $article_dom = str_get_html($article_html);

            if ($article_dom) {
                // Находим заголовок и контент статьи
                $title = $article_dom->find('h1', 0)->plaintext;
                $content = '';

                foreach ($article_dom->find('p') as $element) {
                    $content .= $element->innertext;
                }

                // Сохраняем найденную статью
                $articles[] = array(
                    'title' => $title,
                    'content' => $content,
                );
            }
        }
    }

    return $articles;
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

            <label for="content_aggregator_link_selector">CSS селектор ссылок на статьи:</label><br>
            <input type="text" id="content_aggregator_link_selector" name="content_aggregator_link_selector" value=""><br><br>
            
            <label for="content_aggregator_user_agent">User-Agent:</label><br>
            <input type="text" id="content_aggregator_user_agent" name="content_aggregator_user_agent" value="Mozilla/5.0"><br><br>
            
            <input type="submit" name="content_aggregator_parse" value="Начать парсинг">
        </form>
        <hr>
        <h2>Результат парсинга</h2>
        <?php
        if (isset($_POST['content_aggregator_parse'])) {
            $url = esc_url($_POST['content_aggregator_url']);
            $link_selector = sanitize_text_field($_POST['content_aggregator_link_selector']);
            $user_agent = sanitize_text_field($_POST['content_aggregator_user_agent']);

            $parsed_articles = parse_articles($url, $link_selector, $user_agent);

            if ($parsed_articles) {
                echo '<p>Найдено статей: ' . count($parsed_articles) . '</p>';

                foreach ($parsed_articles as $article) {
                    echo '<h3>' . esc_html($article['title']) . '</h3>';
                    echo '<div>' . wp_kses_post($article['content']) . '</div>';
                    echo '<hr>';
                }
            } else {
                echo '<p>Не удалось загрузить или спарсить статьи.</p>';
            }
        }
        ?>
    </div>
    <?php
}
