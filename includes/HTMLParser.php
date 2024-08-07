<?php

require_once "vendor/autoload.php";
use PHPHtmlParser\Dom;


class HTMLParser
{
    private $data;

    public function __construct()
    {
        $siteParsersPath = plugin_dir_path(__FILE__) . 'site-parsers.php';
        $this->data = include $siteParsersPath;

        if (empty($this->data)) {
            error_log('Ошибка: Не удалось загрузить конфигурации парсеров.');
            return;
        }

        // Логируем полученные данные
        error_log('Конфигурации парсеров: ' . print_r($this->data, true));

        $activeSites = $this->getActiveSites();
        error_log('Активные сайты: ' . print_r($activeSites, true));

        foreach ($this->data as $siteKey => $parserConfig) {
            if (!in_array($siteKey, $activeSites)) {
                error_log("Ошибка парсинга для сайта: {$siteKey}. Не настроен или не включен.");
                continue;
            }
            error_log("Настройки парсера для {$siteKey}: " . print_r($parserConfig, true));
        }
    }

    private function getActiveSites()
    {
        // Здесь должна быть реализация метода для получения списка активных сайтов
        // Например, это может быть запрос к базе данных или получение настроек плагина
        return ['ge_globo', 'depor', 'elbocon', 'tycsports', 'mediotiempo', 'as']; // Пример возвращаемого значения
    }



    public function getLinks($config): array
    {
        try {
            $dom = new Dom;
            $dom->loadFromUrl($config['url']);
            $selector = $config['articleLinkSelector'] ?? 'a';
            $links = $dom->find($selector);
    
            $result = [];
            foreach ($links as $link) {
                if ($link->href) {
                    $result[] = $this->normalizeLink($config['url'], $link->href);
                }
            }
    
            return $result;
        } catch (Exception $e) {
            error_log("Ошибка при получении ссылок с URL {$config['url']}: " . $e->getMessage());
            return [];
        }
    }
    public function check_key($url) {
        $url_conf = parse_url($url)['host'];
        if ($url_conf == "ge.globo.com") {
            return "ge_globo";
        } elseif($url_conf == "depor.com") {
            return "depor";
        } elseif($url_conf == "elbocon.pe") {
            return "elbocon";
        } elseif($url_conf == "www.tycsports.com") {
            return "tycsports";
        } elseif($url_conf == "www.mediotiempo.com") {
            return "mediotiempo";
        } elseif($url_conf == "as.com") {
            return "as";
        }
    }

    public function parseArticle($url, $headerSelector, $bodySelector, $imageSelector = null, $options = null, $country=null): array
    {
        try {
            $dom = new Dom;
            $dom->loadFromUrl($url);
            $header = $dom->find($headerSelector)[0]->innerText;
            $images = $dom->find($imageSelector);
            $imageLink = $imageSelector && count($images) > 0 ? $images[0]->getAttribute('src') : null;
            $imageLink = str_replace('&amp;', '&', $imageLink);
            $node = $dom->find($bodySelector)[0];
            if (!$this->data) {
                error_log("Ошибка: Конфигурация парсеров не загружена.");
                return ['title' => 'Ошибка', 'content' => 'Не удалось получить статью', 'image' => null];
            }
            $config = $this->data[$this->check_key($url)] ?? [];
            if (empty($config)) {
                error_log("Ошибка: Конфигурация для URL {$url} не найдена.");
                return ['title' => 'Ошибка', 'content' => 'Не удалось получить статью', 'image' => null];
            }
            $elsToRemove = $node->find('meta, nav, template, amp-img, audio, video');
            for ($i = 0; $i < $elsToRemove->count(); $i++) {
                $elsToRemove[$i]->delete();
            }

            if ($options && gettype($options) === 'array' && isset($options['filter_elements'])) {
                if (gettype($options['filter_elements']) === 'array') {
                    $options['filter_elements'] = implode(', ', $options['filter_elements']);
                }
                $elsToRemove = $node->find($options['filter_elements']);
                for ($i = 0; $i < $elsToRemove->count(); $i++) {
                    $elsToRemove[$i]->delete();
                }
            }

            $children = $node->find('a');
            for ($i = 0; $i < $children->count(); $i++) {
                if ($children[$i]->href && strpos($children[$i]->href, 'whatsapp.com') !== false) {
                    $children[$i]->delete();
                }
                if (preg_match("/^\\+\\s+.*/", $children[$i]->innerText)) {
                    $children[$i]->delete();
                }
                if ($options && gettype($options) === 'array' && isset($options['filter_links_by_urls'])) {
                    if (gettype($options['filter_links_by_urls']) === 'array') {
                        $options['filter_links_by_urls'] = implode('|', $options['filter_links_by_urls']);
                    }
                    if ($children[$i]->href && preg_match("/" . $options['filter_links_by_urls'] . "/", $children[$i]->href)) {
                        $children[$i]->delete();
                    }
                }
                if ($options && gettype($options) === 'array' && isset($options['filter_links_by_text'])) {
                    if (gettype($options['filter_links_by_text']) === 'array') {
                        $options['filter_links_by_text'] = implode('|', $options['filter_links_by_text']);
                    }
                    if ($children[$i]->href && preg_match("/" . $options['filter_links_by_text'] . "/", $children[$i]->innerText)) {
                        $children[$i]->delete();
                    }
                }
                
            }

            return ['title' => $header, 'content' => $node->innerText()." ".$url, 'image' => $imageLink, "region" => $country];
        } catch (Exception $e) {
            error_log("Ошибка при парсинге статьи с URL {$url}: " . $e->getMessage());
            return ['title' => 'Ошибка', 'content' => 'Не удалось получить статью', 'image' => null];
        }
    }

    public function normalizeLink($domain, $link)
    {
        if (strpos($link, 'http') !== 0) {
            if (strpos($link, '/') === 0) {
                $parts = parse_url($domain);
                $link = $parts['scheme'] . '://' . $parts['host'] . $link;
            } else {
                $link = $domain . '/' . ltrim($link, '/');
            }
        }
        return $link;
    }
    

    public function getArticles(array $config, int $num_articles, $country): array
    {
        $links = $this->getLinks($config);
        $articles = [];
    
        for ($i = 0; $i < min(count($links), $num_articles); $i++) {
            if (!isset($config['headerSelector']) || !isset($config['bodySelector'])) {
                error_log("Конфигурация для сайта {$config['url']} неполная.");
                continue;
            }
    
            $articleData = $this->parseArticle(
                $links[$i],
                $config['headerSelector'],
                $config['bodySelector'],
                $config['imageSelector'] ?? null,
                $config['options'] ?? null,
                $country
            );
            $articles[] = $articleData;
        }
        return $articles;
    }

    public function printLinks($url)
    {
        $links = $this->getLinks($url);
        foreach ($links as $link) {
            $link = $this->normalizeLink($url, $link);
            print '<a href="' . $link . '" target="_blank">' . $link . '</a><br>';
        }
    }
  
    public function printArticle($url)
    {
        try {
            $origin = preg_replace("/^(https?:\\/\\/[^\\/]+\\/?).*/", "$1", $url);
            $config = $this->data[$origin] ?? [];
            
            if (!isset($config['headerSelector']) || !isset($config['bodySelector'])) {
                throw new Exception('Конфигурация для сайта неполная.');
            }
    
            $data = $this->parseArticle(
                $url,
                $config['headerSelector'],
                $config['bodySelector'],
                $config['imageSelector'] ?? null,
                $config['options'] ?? null
            );
    
            print '<h1>' . $data['title'] . '</h1>';
            if ($data['image']) {
                print '<img src="' . htmlspecialchars($data['image']) . '" alt="Article Image" style="max-width: 100%;">';
            }
            print '<p>' . nl2br(htmlspecialchars($data['content'])) . '</p>';
        } catch (Exception $e) {
            error_log("Ошибка при печати статьи с URL {$url}: " . $e->getMessage());
            print '<h1>Ошибка</h1><p>Не удалось вывести статью.</p>';
        }
    }
    
}

function content_aggregator_add_admin_menu()
{
    add_menu_page(
        'Настройки Content Aggregator',
        'Content Aggregator',
        'manage_options',
        'content-aggregator',
        'content_aggregator_options_page',
        'dashicons-admin-site-alt3',
        2
    );

    add_submenu_page(
        'content-aggregator',
        'Ручное выполнение крон-задач',
        'Ручное выполнение крон-задач',
        'manage_options',
        'run-cron-tasks',
        'content_aggregator_cron_tasks_page'
    );
}
add_action('admin_menu', 'content_aggregator_add_admin_menu');

function perform_parsing(string $site_key): array
{
    $site_parsers = include plugin_dir_path(__FILE__) . 'site-parsers.php';
    $selected_sites = get_option('content_aggregator_sites', []);
    $parser = new HTMLParser();
    if (isset($selected_sites[$site_key]) and !empty($selected_sites[$site_key]['enabled'])) {
        $num_articles = $selected_sites[$site_key]['num_articles'] ?? 10;
        $articles = $parser->getArticles($site_parsers[$site_key], $num_articles, $selected_sites[$site_key]['region']);

        if (empty($articles)) {
            error_log("Ошибка парсинга для сайта: $site_key. Статьи не найдены или не удалось выполнить парсинг.");
        }

        update_option('content_aggregator_parsed_articles_' . $site_key, $articles);
        content_aggregator_publish_articles($site_key);
        return $articles;
    } else {
        error_log("Ошибка парсинга для сайта: $site_key. Не настроен или не включен.");
        return [];
    }
}





function content_aggregator_publish_articles(string $site_key)
{
    $parsed_articles = get_option('content_aggregator_parsed_articles_' . $site_key, []);

    foreach ($parsed_articles as $article) {
        create_post($article['title'], $article['content'], $article['region'], $article['image']);
    }

    delete_option('content_aggregator_parsed_articles_' . $site_key);
}

require_once 'create_post.php';

function content_aggregator_add_cron_interval(array $schedules): array
{
    $schedules['every_minute'] = [
        'interval' => 60,
        'display'  => __('Каждую минуту')
    ];
    $schedules['every_5_minutes'] = [
        'interval' => 300,
        'display'  => __('Каждые 5 минут')
    ];
    $schedules['every_15_minutes'] = [
        'interval' => 900,
        'display'  => __('Каждые 15 минут')
    ];
    $schedules['every_30_minutes'] = [
        'interval' => 1800,
        'display'  => __('Каждые 30 минут')
    ];
    $schedules['every_hour'] = [
        'interval' => 3600,
        'display'  => __('Каждый час')
    ];
    $schedules['every_6_hours'] = [
        'interval' => 21600,
        'display'  => __('Каждые 6 часов')
    ];
    $schedules['daily'] = [
        'interval' => 86400,
        'display'  => __('Каждый день')
    ];
    // Добавьте другие интервалы при необходимости
    return $schedules;
}
add_filter('cron_schedules', 'content_aggregator_add_cron_interval');


// Запланировать парсинг через WP Crontrol
function content_aggregator_schedule_cron()
{
    $selected_sites = get_option('content_aggregator_sites', []);
    
    foreach ($selected_sites as $key => $site) {
        $interval = $site['interval'] ?? 'every_minute';
        $hook = 'content_aggregator_cron_event_' . $key;

        // Удаляем старую задачу, если она существует
        wp_clear_scheduled_hook($hook);

        // Планируем новую задачу с новым интервалом
        if (!wp_next_scheduled($hook)) {
            wp_schedule_event(time(), $interval, $hook);
        }
    }
}
register_deactivation_hook(__FILE__, 'content_aggregator_schedule_cron');
// Обработчики крон-задач для каждого сайта
function register_cron_event_handlers()
{
    $selected_sites = get_option('content_aggregator_sites', []);
    foreach (array_keys($selected_sites) as $key) {
        $hook = 'content_aggregator_cron_event_' . $key;
        add_action($hook, fn() => perform_parsing($key));
    }
}
add_action('init', 'register_cron_event_handlers');

// Удаление планирования крон-задач при деактивации плагина
function content_aggregator_deactivate()
{
    $selected_sites = get_option('content_aggregator_sites', []);

    foreach ($selected_sites as $key => $site) {
        $hook = 'content_aggregator_cron_event_' . $key;
        wp_clear_scheduled_hook($hook);
    }
}
register_deactivation_hook(__FILE__, 'content_aggregator_deactivate');

// Функция для ручного выполнения парсинга
function content_aggregator_manual_run()
{
    if (isset($_POST['manual_run']) && isset($_POST['site_key'])) {
        $site_key = sanitize_text_field($_POST['site_key']);
        perform_parsing($site_key);
        echo '<div class="updated"><p>Парсинг выполнен вручную для сайта: ' . esc_html($site_key) . '</p></div>';
    }
}

// Страница настроек плагина

function content_aggregator_options_page()
{
    $site_parsers = include plugin_dir_path(__FILE__) . 'site-parsers.php';
    $selected_sites = get_option('content_aggregator_sites', []);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['save_settings'])) {
            update_option('content_aggregator_sites', $_POST['content_aggregator_sites']);
            content_aggregator_schedule_cron(); // Перепланируем крон-задачи после сохранения
            echo '<div class="updated"><p>Настройки сохранены.</p></div>';
        } elseif (isset($_POST['manual_run'])) {
            $selected_sites = $_POST['content_aggregator_sites'];
            $has_errors = false;

            foreach ($selected_sites as $site_key => $site_settings) {
                if (!empty($site_settings['enabled'])) {
                    $articles = perform_parsing($site_key);

                    if (empty($articles)) {
                        $has_errors = true;
                        echo '<div class="error"><p>Ошибка парсинга для сайта: ' . esc_html($site_key) . '</p></div>';
                    }
                }
            }

            if (!$has_errors) {
                echo '<div class="updated"><p>Парсинг выполнен вручную для выбранных сайтов.</p></div>';
            }
        }
    }

    // Определение ключа сайта для отображения статей
    $site_key = isset($_POST['site_key']) ? sanitize_text_field($_POST['site_key']) : '';

    // Получение статей для выбранного сайта
    $articles = [];
    if ($site_key) {
        $articles = get_option('content_aggregator_parsed_articles_' . $site_key);
    }

    ?>
    <div class="wrap">
        <h1>Настройки Content Aggregator</h1>
        <form method="post" action="">
            <h2>Настройки времени и интервала парсинга для каждого сайта</h2>
            <?php foreach ($site_parsers as $key => $site): ?>
                <h2><?php echo esc_html($site['name']); ?></h2>
                <input type="checkbox" name="content_aggregator_sites[<?php echo esc_attr($key); ?>][enabled]" value="1" <?php checked(!empty($selected_sites[$key]['enabled'])); ?>>
                <label for="content_aggregator_sites[<?php echo esc_attr($key); ?>][enabled]">Парсить этот сайт</label><br><br>

                <label for="content_aggregator_sites[<?php echo esc_attr($key); ?>][interval]">Интервал парсинга:</label>
                <select name="content_aggregator_sites[<?php echo esc_attr($key); ?>][interval]">
                    <option value="every_minute" <?php selected($selected_sites[$key]['interval'] ?? 'every_minute', 'every_minute'); ?>>Каждую минуту</option>
                    <option value="every_5_minutes" <?php selected($selected_sites[$key]['interval'] ?? 'every_5_minutes', 'every_5_minutes'); ?>>Каждые 5 минут</option>
                    <option value="every_15_minutes" <?php selected($selected_sites[$key]['interval'] ?? 'every_15_minutes', 'every_15_minutes'); ?>>Каждые 15 минут</option>
                    <option value="every_30_minutes" <?php selected($selected_sites[$key]['interval'] ?? 'every_30_minutes', 'every_30_minutes'); ?>>Каждые 30 минут</option>
                    <option value="every_hour" <?php selected($selected_sites[$key]['interval'] ?? 'every_hour', 'every_hour'); ?>>Каждый час</option>
                    <option value="every_6_hours" <?php selected($selected_sites[$key]['interval'] ?? 'every_6_hours', 'every_6_hours'); ?>>Каждые 6 часов</option>
                    <option value="daily" <?php selected($selected_sites[$key]['interval'] ?? 'daily', 'daily'); ?>>Каждый день</option>
                </select><br><br>

                <label for="content_aggregator_sites[<?php echo esc_attr($key); ?>][num_articles]">Количество статей для парсинга:</label>
                <input type="number" name="content_aggregator_sites[<?php echo esc_attr($key); ?>][num_articles]" value="<?php echo esc_attr($selected_sites[$key]['num_articles'] ?? 10); ?>"><br><br>

                <label for="content_aggregator_sites[<?php echo esc_attr($key); ?>][region]">Регион:</label>
                <select name="content_aggregator_sites[<?php echo esc_attr($key); ?>][region]">
                    <option value="">Выберите регион</option>
                    <?php
                    $terms = get_terms([
                        'taxonomy'   => 'region',
                        'hide_empty' => false,
                    ]);

                    foreach ($terms as $term) {
                        $selected = isset($selected_sites[$key]['region']) && $selected_sites[$key]['region'] == $term->term_id ? 'selected' : '';
                        echo '<option value="' . esc_attr($term->term_id) . '" ' . $selected . '>' . esc_html($term->name) . '</option>';
                    }
                    ?>
                </select><br><br>
            <?php endforeach; ?>

            <input type="submit" name="save_settings" class="button button-primary" value="Сохранить настройки">
            <input type="submit" name="manual_run" class="button button-primary" value="Выполнить парсинг вручную">
        </form>

        <?php if ($site_key): ?>
            <h2>Спарсенные статьи для сайта: <?php echo esc_html($site_parsers[$site_key]['name'] ?? 'Неизвестный сайт'); ?></h2>
            <?php if ($articles): ?>
                <table class="widefat fixed">
                    <thead>
                        <tr>
                            <th>Название</th>
                            <th>Содержание</th>
                            <th>Изображение</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($articles as $article): ?>
                            <tr>
                                <td><?php echo esc_html($article['title']); ?></td>
                                <td><?php echo esc_html(wp_trim_words($article['content'], 100)); ?></td>
                                <td>
                                    <?php if (!empty($article['image'])): ?>
                                        <img src="<?php echo esc_url($article['image']); ?>" alt="<?php echo esc_attr($article['title']); ?>" style="max-width: 150px;">
                                    <?php else: ?>
                                        Нет изображения
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Нет доступных статей.</p>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <?php
}





// Страница для ручного выполнения крон-задач
function content_aggregator_cron_tasks_page()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_cron_task'])) {
        $site_key = sanitize_text_field($_POST['site_key']);
        perform_parsing($site_key);
        echo '<div class="updated"><p>Парсинг выполнен вручную для сайта: ' . esc_html($site_key) . '</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Ручное выполнение крон-задач</h1>

        <h2>Запланированные крон-задачи</h2>
        <?php list_cron_jobs(); ?>
    </div>
    <?php
}

// Вывод списка запланированных задач
function list_cron_jobs()
{
    $crons = _get_cron_array();

    if (empty($crons)) {
        echo '<p>Нет запланированных задач.</p>';
        return;
    }

    echo '<table class="widefat fixed">';
    echo '<thead><tr><th>Дата и время</th><th>Хук</th><th>Интервал</th><th>Действие</th></tr></thead>';
    echo '<tbody>';

    foreach ($crons as $timestamp => $hooks) {
        foreach ($hooks as $hook => $schedule) {
            foreach ($schedule as $hookData) {
                $time = date('Y-m-d H:i:s', $timestamp);
                $interval = isset($hookData['args'][0]) ? esc_html($hookData['args'][0]) : 'Не задано';

                echo '<tr>';
                echo '<td>' . esc_html($time) . '</td>';
                echo '<td>' . esc_html($hook) . '</td>';
                echo '<td>' . esc_html($interval) . '</td>';

                $site_key = str_replace('content_aggregator_cron_event_', '', $hook);

                echo '<td>';
                if ($site_key) {
                    echo '<form method="post" action="" style="display:inline;">
                            <input type="hidden" name="site_key" value="' . esc_attr($site_key) . '">
                            <input type="submit" name="run_cron_task" class="button button-primary" value="Выполнить">
                          </form>';
                }
                echo '</td>';

                echo '</tr>';
            }
        }
    }

    echo '</tbody>';
    echo '</table>';
}

?>
