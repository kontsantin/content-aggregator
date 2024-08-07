<?php
/*
Plugin Name: Content Aggregator
Description: Плагин для экспорта статей в CSV и Excel.
Version: 1.0
Author: Ваше Имя
*/

// Включаем буферизацию вывода в начале файла
ob_start();

// Подключаем автозагрузчик Composer
require_once 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function content_aggregator_add_export_page()
{
    add_submenu_page(
        'content-aggregator',
        'Выгрузка статей',
        'Выгрузка статей',
        'manage_options',
        'export-articles',
        'content_aggregator_export_page'
    );
}
add_action('admin_menu', 'content_aggregator_add_export_page');

function content_aggregator_export_page()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['export_articles'])) {
        $start_datetime = sanitize_text_field($_POST['start_datetime']);
        $end_datetime = sanitize_text_field($_POST['end_datetime']);
        $file_type = sanitize_text_field($_POST['file_type']);

        // Если время не выбрано, берется целый день
        if (strlen($start_datetime) === 10) {
            $start_datetime .= ' 00:00:00';
        }
        if (strlen($end_datetime) === 10) {
            $end_datetime .= ' 23:59:59';
        }

        // Вызываем экспорт данных
        content_aggregator_export_articles($start_datetime, $end_datetime, $file_type);

        // Завершаем выполнение скрипта
        exit;
    }

    $today = date('Y-m-d');
    ?>
    <div class="wrap">
        <h1>Выгрузка опубликованных статей</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="start_datetime">Начальная дата и время</label></th>
                    <td><input type="datetime-local" name="start_datetime" id="start_datetime" value="<?php echo $today; ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="end_datetime">Конечная дата и время</label></th>
                    <td><input type="datetime-local" name="end_datetime" id="end_datetime" value="<?php echo $today; ?>" required /></td>
                </tr>
                <tr>
                    <th><label for="file_type">Формат файла</label></th>
                    <td>
                        <select name="file_type" id="file_type">
                            <option value="csv">CSV</option>
                            <option value="xlsx">Excel (XLSX)</option>
                        </select>
                    </td>
                </tr>
            </table>
            <input type="submit" name="export_articles" class="button button-primary" value="Экспортировать">
        </form>
    </div>
    <?php
}

function content_aggregator_export_articles($start_datetime, $end_datetime, $file_type)
{
    $args = [
        'post_type'   => 'news',
        'post_status' => 'publish',
        'date_query'  => [
            'after'     => $start_datetime,
            'before'    => $end_datetime,
            'inclusive' => true,
        ],
        'fields'      => 'ids',
    ];

    $posts = get_posts($args);

    $data = [];
    foreach ($posts as $post_id) {
        $post = get_post($post_id);
        $data[] = [
            'title'          => $post->post_title,
            'content'        => $post->post_content,
            'status'         => get_post_meta($post_id, 'parsed_status', true) ?: 'Не определено',
            'date_published' => $post->post_date,
        ];
    }

    if ($file_type === 'csv') {
        export_to_csv($data);
    } elseif ($file_type === 'xlsx') {
        export_to_xlsx($data);
    }
}

function export_to_xlsx($data)
{
    // Очищаем буфер вывода
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Создаем новый объект Spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Устанавливаем заголовки
    $sheet->setCellValue('A1', 'Заголовок');
    $sheet->setCellValue('B1', 'Описание');
    $sheet->setCellValue('C1', 'Статус');
    $sheet->setCellValue('D1', 'Дата публикации');

    // Заполняем данными
    $rowNum = 2;
    foreach ($data as $row) {
        $sheet->setCellValue('A' . $rowNum, $row['title']);
        $sheet->setCellValue('B' . $rowNum, $row['content']);
        $sheet->setCellValue('C' . $rowNum, $row['status']);
        $sheet->setCellValue('D' . $rowNum, $row['date_published']);
        $rowNum++;
    }

    // Создаем объект Writer
    $writer = new Xlsx($spreadsheet);

    // Отправляем заголовки
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="exported_articles.xlsx"');
    header('Cache-Control: max-age=0');

    // Сохраняем файл в буфер и отправляем
    $writer->save('php://output');

    // Завершаем выполнение скрипта
    exit;
}

function export_to_csv($data)
{
    // Очищаем буфер вывода
    if (ob_get_level()) {
        ob_end_clean();
    }

    // Устанавливаем заголовки
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment;filename="exported_articles.csv"');

    // Создаем указатель на выходной поток
    $output = fopen('php://output', 'w');

    // Записываем заголовки
    fputcsv($output, ['Заголовок', 'Описание', 'Статус', 'Дата публикации']);

    // Записываем данные
    foreach ($data as $row) {
        fputcsv($output, [
            $row['title'],
            $row['content'],
            $row['status'],
            $row['date_published']
        ]);
    }

    // Закрываем указатель
    fclose($output);

    // Завершаем выполнение скрипта
    exit;
}
?>
