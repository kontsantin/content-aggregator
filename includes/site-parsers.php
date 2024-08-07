<?php
// site-parsers.php

return [
    'ge_globo' => [
        'name' => 'Globo Esporte',
        'url' => 'https://ge.globo.com/',
        'articleLinkSelector' => '.feed-post-link',
        'headerSelector' => '.content-head__title',
        'bodySelector' => '.mc-article-body',
        'imageSelector' => 'figure ',
        'options' => [
            'filter_elements' => ['.content-video', '.shadow-video-flow'] // Изображения и видео, которые нужно исключить
        ],
        'enabled' => false
    ],
    'depor' => [
        'name' => 'Depor',
        'url' => 'https://depor.com/',
        'articleLinkSelector' => '.title-xs',
        'headerSelector' => '.sht__title',
        'bodySelector' => '#contenedor',
        'imageSelector' => '.st-sidebar__main figure ',
        'options' => [
            'filter_elements' => ['.content-video', '.shadow-video-flow', 'section > :nth-last-child(6) ~ *'] // Исключаем видео и определённые секции
        ],
        'enabled' => true
    ],
    'elbocon' => [
        'name' => 'El Bocon',
        'url' => 'https://elbocon.pe/',
        'articleLinkSelector' => '.extraordinary-l-score__title-link, .featured-story__title-link, .stories-l-item',
        'headerSelector' => '.sht__title',
        'bodySelector' => '#contenedor',
        'imageSelector' => '.st-sidebar__main figure ',
        'options' => [
            'filter_elements' => ['.content-video', '.shadow-video-flow'] // Исключаем видео
        ],
        'enabled' => true
    ],
    'tycsports' => [
        'name' => 'TyC Sports',
        'url' => 'https://www.tycsports.com/',
        'articleLinkSelector' => 'h3 > a',
        'headerSelector' => 'h1',
        'bodySelector' => '#conid .capital',
        'imageSelector' => '#conid figure img',
        'options' => [
            'filter_elements' => ['.watchto-wrap', '.content-video', '.shadow-video-flow', '.fluidMedia', '> :nth-last-child(5) ~ *'] // Исключаем различные элементы, включая видео
        ],
        'enabled' => true
    ],
    'mediotiempo' => [
        'name' => 'Medio Tiempo',
        'url' => 'https://www.mediotiempo.com/',
        'articleLinkSelector' => 'h2 > a, .title > a',
        'headerSelector' => 'h1',
        'bodySelector' => '#content-body',
        'imageSelector' => '.nd-media-detail-base__img',
        'options' => [
            'filter_elements' => ['.content-video', '.shadow-video-flow', '.twitter-tweet', '.nd-related-news-detail-media-dual'] // Исключаем видео, твиты и связанные новости
        ],
        'enabled' => true
    ],
    'as' => [
        'name' => 'AS',
        'url' => 'https://as.com/',
        'articleLinkSelector' => 'h2.s__tl > a',
        'headerSelector' => 'h1',
        'bodySelector' => '.art__bo',
        'imageSelector' => '.art__m-mm .mm__img',
        'options' => [
            'filter_elements' => ['.content-video', '.shadow-video-flow', 'h4, h4 ~ *'] // Исключаем видео и дополнительные заголовки
        ],
        'enabled' => true
    ],
];
