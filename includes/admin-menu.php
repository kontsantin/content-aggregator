<?php
// Создаем страницу настроек и выводим спарсенный контент
function content_aggregator_add_admin_menu() {
    add_options_page('Content Aggregator Settings', 'Content Aggregator', 'manage_options', 'content-aggregator', 'content_aggregator_options_page');
}
add_action('admin_menu', 'content_aggregator_add_admin_menu');

function content_aggregator_options_page() {
    ?>
    <div class="wrap">
        <h1>Настройки Content Aggregator</h1>
        <form id="content_aggregator_form" method="post" action="">
            <div id="url_fields">
                <div class="url_field">
                    <label for="content_aggregator_url">URL для парсинга:</label><br>
                    <input type="text" id="content_aggregator_url" name="content_aggregator_urls[]"><br><br>

                    <label for="content_aggregator_title_tag">Тег для заголовка:</label><br>
                    <input type="text" id="content_aggregator_title_tag" name="content_aggregator_title_tags[]" value="h1"><br><br>

                    <label for="content_aggregator_content_tag">Теги для контента (через запятую):</label><br>
                    <input type="text" id="content_aggregator_content_tag" name="content_aggregator_content_tags[]" value="p"><br><br>

                    <label for="content_aggregator_custom_tags">Дополнительные теги (через запятую):</label><br>
                    <input type="text" id="content_aggregator_custom_tags" name="content_aggregator_custom_tags[]"><br><br>

                    <label for="content_aggregator_selectors">Селекторы (через запятую):</label><br>
                    <input type="text" id="content_aggregator_selectors" name="content_aggregator_selectors[]"><br><br>

                    <button type="button" class="remove_url_button">Удалить URL</button>
                    <hr>
                </div>
            </div>

            <button type="button" id="add_url_button">Добавить URL</button><br><br>

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
            $urls = $_POST['content_aggregator_urls'];
            $title_tags = $_POST['content_aggregator_title_tags'];
            $content_tags = $_POST['content_aggregator_content_tags'];
            $custom_tags = $_POST['content_aggregator_custom_tags'];
            $selectors = $_POST['content_aggregator_selectors'];
            $region = sanitize_text_field($_POST['content_aggregator_region']);

            foreach ($urls as $key => $url) {
                $default_tags = array(
                    'title_tag' => sanitize_text_field($title_tags[$key]),
                    'content_tag' => sanitize_text_field($content_tags[$key])
                );
                $custom_tag = sanitize_text_field($custom_tags[$key]);
                $selector = sanitize_text_field($selectors[$key]);

                $parsed_content = parse_content($url, $default_tags, $custom_tag, $selector);

                if ($parsed_content) {
                    echo '<h3>' . esc_html($parsed_content['title']) . '</h3>';
                    echo '<div>' . wp_kses_post($parsed_content['content']) . '</div>';
                    ?>
                    <form method="post" action="">
                        <input type="hidden" name="content_aggregator_post" value="1">
                        <input type="hidden" name="content_aggregator_title" value="<?php echo esc_attr($parsed_content['title']); ?>">
                        <input type="hidden" name="content_aggregator_content" value="<?php echo esc_textarea($parsed_content['content']); ?>">
                        <input type="hidden" name="content_aggregator_region" value="<?php echo esc_attr($region); ?>">
                        <input type="submit" value="Опубликовать статью">
                    </form>
                    <?php
                } else {
                    echo '<p>Не удалось загрузить или спарсить контент.</p>';
                }
            }
        }

        if (isset($_POST['content_aggregator_post'])) {
            $title = sanitize_text_field($_POST['content_aggregator_title']);
            $content = wp_kses_post($_POST['content_aggregator_content']);
            $region = sanitize_text_field($_POST['content_aggregator_region']);

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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var addUrlButton = document.getElementById('add_url_button');

            addUrlButton.addEventListener('click', function() {
                var urlFields = document.getElementById('url_fields');
                var newField = document.createElement('div');
                newField.classList.add('url_field');
                newField.innerHTML = `
                    <label for="content_aggregator_url">URL для парсинга:</label><br>
                    <input type="text" name="content_aggregator_urls[]"><br><br>
                    
                    <label for="content_aggregator_title_tag">Тег для заголовка:</label><br>
                    <input type="text" name="content_aggregator_title_tags[]" value="h1"><br><br>
                    
                    <label for="content_aggregator_content_tag">Теги для контента (через запятую):</label><br>
                    <input type="text" name="content_aggregator_content_tags[]" value="p"><br><br>
                    
                    <label for="content_aggregator_custom_tags">Дополнительные теги (через запятую):</label><br>
                    <input type="text" name="content_aggregator_custom_tags[]"><br><br>
                    
                    <label for="content_aggregator_selectors">Селекторы (через запятую):</label><br>
                    <input type="text" name="content_aggregator_selectors[]"><br><br>
                    
                    <button type="button" class="remove_url_button">Удалить URL</button>
                    <hr>
                `;
                urlFields.appendChild(newField);
            });

            document.addEventListener('click', function(event) {
                if (event.target.classList.contains('remove_url_button')) {
                    var urlField = event.target.closest('.url_field');
                    urlField.remove();
                }
            });
        });
    </script>
    <?php
}
