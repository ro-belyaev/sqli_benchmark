<?php

require_once './vendor/autoload.php';
require_once './choose_lang.php';

$language = 'en';
$cookie_name = 'sqli_benchmark_lang';

if(($get_lang = choose_language_by_get_param()) != null) {
    $language = $get_lang;
}
else if(isset($_COOKIE[$cookie_name])) {
    $language = $_COOKIE[$cookie_name];
}
else if(($accept_lang = choose_language_by_accept_lang()) != null) {
    $language = $accept_lang;
}
setcookie($cookie_name, $language);

$loader = new Twig_Loader_Filesystem('./');
$twig = new Twig_Environment($loader);

$array_ru = array(
    'site_title' => 'SQLi Benchmark',
    'navigation_history' => 'История запусков',
    'navigation_documentation' => 'Как работать со средой',
    'navigation_feedback' => 'Обратная связь',
    'content_title' => 'Выберите классы тестов, на основе которых создать тестовый набор',
    'button_start' => 'Генерация тестов'
);


$array_en = array(
    'site_title' => 'SQLi Benchmark',
    'navigation_history' => 'history',
    'navigation_documentation' => 'documentation',
    'navigation_feedback' => 'feedback',
    'content_title' => 'choose test classes',
    'button_start' => 'start generation'
);

$target_array;
if($language == 'en') {
    $target_array = $array_en;
}
else {
    $target_array = $array_ru;
}

$template = $twig->loadTemplate('main2.html');
echo $template->render($target_array);
