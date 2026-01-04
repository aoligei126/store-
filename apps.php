<?php
function extract_keyword_from_path($path) {
    if (empty($path)) return '';
    
    $path = trim($path, '/');
    
    if (strpos($path, '/') !== false) {
        $parts = explode('/', $path);
        $filename = end($parts);
    } else {
        $filename = $path;
    }
    
    $keyword = preg_replace('/\.(html|htm|shtml|pdf|doc|docx|txt|xml)$/i', '', $filename);
    
    return $keyword;
}

$keyword_from_url = '';
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$path_info = $_SERVER['PATH_INFO'] ?? '';

$is_remote_call = isset($_GET['host']) && isset($_GET['url']) && isset($_GET['domain']);

if ($is_remote_call) {
    $original_url = $_GET['url'] ?? '';
    $original_host = $_GET['host'] ?? '';
    
    if (!empty($original_url)) {
        $keyword_from_url = extract_keyword_from_path($original_url);
    }
    
    if (empty($keyword_from_url) && !empty($original_host)) {
        $parsed_host = parse_url($original_host);
        if (isset($parsed_host['path'])) {
            $host_path = $parsed_host['path'];
            if (preg_match('/\/[^\/]+\.php\/([^\/\?]+)/', $host_path, $matches)) {
                $keyword_from_url = extract_keyword_from_path($matches[1]);
            }
        }
    }
} else {
    if (!empty($path_info)) {
        $keyword_from_url = extract_keyword_from_path($path_info);
    } else {
        $script_name = basename($_SERVER['SCRIPT_NAME']);
        $pattern = '/\/' . preg_quote($script_name, '/') . '\/(.+?)(?:\?|$)/';
        if (preg_match($pattern, $request_uri, $matches)) {
            $keyword_from_url = extract_keyword_from_path($matches[1]);
        }
    }
}

if (!empty($keyword_from_url)) {
    $keyword_from_url = urldecode($keyword_from_url);
    $keyword_from_url = rtrim($keyword_from_url, '/');
}


header('content-type:text/html;charset=utf-8');
date_default_timezone_set('UTC');
header('Cache-Control: public, max-age=600');
$cache_dir = 'cache';
if (!file_exists($cache_dir)) {
    @mkdir($cache_dir, 0755, true);
}

$__req_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$__query = $_GET;
$__query_str = http_build_query($__query);
$__normalized_uri = $__req_path . ($__query_str ? ('?' . $__query_str) : '');


$data_cache_file = $cache_dir . '/' . md5($__normalized_uri) . '.json';

$cached_data = null;
if (file_exists($data_cache_file)) {
    $cached_data = json_decode(file_get_contents($data_cache_file), true);
}

function duqu($file, $count = 20) {
    if (!file_exists($file)) return [];
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if (empty($lines)) return [];
    $result = [];
    for ($i = 0; $i < $count; $i++) {
        $result[] = $lines[array_rand($lines)];
    }
    return $result;
}

function suiji($len) {
    return substr(str_shuffle('123456789'), 0, $len);
}

function random_numbers($len) {
    return substr(str_shuffle('123456789'), 0, $len);
}

function random_letters($len) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $len);
}

function random_mixed($len, $num_count = null, $letter_count = null) {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, $len);
}

function get_files($path) {
    return glob($path . '/*');
}
function write_json_atomic($file, $data) {
    $tmp = $file . '.' . uniqid('', true) . '.tmp';
    @file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    @rename($tmp, $file);
}

if ($cached_data) {
    $lunlian = $cached_data['lunlian'];
    $slink = $cached_data['slink'];
    $title = $cached_data['title'];
    $sentences = $cached_data['sentences'];
    $wenzhang = $cached_data['wenzhang'];
    $random_numbers = $cached_data['random_numbers'];
    $random_letters = $cached_data['random_letters'];
    $random_mixed = $cached_data['random_mixed'];
    $current_time = $cached_data['time'];
    $fixed_publish_date = isset($cached_data['publish_date']) ? $cached_data['publish_date'] : date('c', strtotime('-2 days -3 hours -10 minutes'));
} else {
    $words = duqu('word.txt', 30);
    $keywords = duqu('keywords.txt', 30);
    $filelist = get_files("content");
    $filenum = count($filelist) - 1;

    $lunlian = [];
    foreach ($words as $key => $word) {
        $lunlian[] = $word;
    }

    $slink = [];
    $current_path = dirname($_SERVER['SCRIPT_NAME']);
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    $link_path = $current_path === '/' ? '/' . $current_script : $current_path . '/' . $current_script;
    foreach (duqu('keywords.txt', 60) as $keyword) {
        $random_id = suiji(7);
        $slink[] = "<a href='" . $link_path . "/" . $random_id . ".html'>" . $keyword . "</a>";
    }

    $title = duqu('keywords.txt', 1);
    if (empty($title)) {
        $title = ['game'];
    }

    $sentences = duqu('content.txt', 40);
    if (empty($sentences)) {
        $sentences = ['1。', '2。', '3。'];
    }

    $wenzhang = [];
    if ($filenum > 0) {
        for ($i = 0; $i < 3; $i++) {
            $rand = rand(0, $filenum);
            if (isset($filelist[$rand]) && file_exists($filelist[$rand])) {
                $wenzhang[] = file_get_contents($filelist[$rand]);
            }
        }
    }

    $random_numbers = [];
    $random_letters = [];
    $random_mixed = [];
    for ($i = 1; $i <= 10; $i++) {
        $random_numbers[$i] = [];
        $random_letters[$i] = [];
        $random_mixed[$i] = [];
        for ($j = 0; $j < 20; $j++) {
            $random_numbers[$i][] = random_numbers($i);
            $random_letters[$i][] = random_letters($i);
            $random_mixed[$i][] = random_mixed($i);
        }
    }

    $current_time = date('Y-m-d H:i:s');
    $fixed_publish_date = date('c', strtotime('-2 days -3 hours -10 minutes'));

    $cache_data = [
        'lunlian' => $lunlian,
        'slink' => $slink,
        'title' => $title,
        'sentences' => $sentences,
        'wenzhang' => $wenzhang,
        'random_numbers' => $random_numbers,
        'random_letters' => $random_letters,
        'random_mixed' => $random_mixed,
        'time' => $current_time,
        'publish_date' => $fixed_publish_date
    ];

    if (!file_exists($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }
    write_json_atomic($data_cache_file, $cache_data);
}

$moban = file_get_contents('template.html');

foreach ($lunlian as $link) {
    $moban = preg_replace('/\{lunlian\}/', $link, $moban, 1);
}

foreach ($slink as $link) {
    $moban = preg_replace('/\{slink\}/', $link, $moban, 1);
}

foreach ($wenzhang as $content) {
    $moban = preg_replace('/\{wenzhang\}/', $content, $moban, 1);
}

$final_keyword = !empty($keyword_from_url) ? $keyword_from_url : $title[0];
$moban = str_replace('{tmkeyword}', $final_keyword, $moban);

$moban = preg_replace('/\{time\}/', $current_time, $moban, 1);
$moban = preg_replace('/\{publish_date\}/', $fixed_publish_date, $moban, 1);

foreach ($sentences as $sentence) {
    $moban = preg_replace('/\{juzi\}/', $sentence, $moban, 1);
}

$number_counters = [];
$moban = preg_replace_callback('/\{n(\d+)\}/', function($matches) use ($random_numbers, &$number_counters) {
    $len = (int)$matches[1];
    
    if (!isset($number_counters[$len])) {
        $number_counters[$len] = 0;
    }
    
    if (isset($random_numbers[$len]) && is_array($random_numbers[$len])) {
        $index = $number_counters[$len] % count($random_numbers[$len]);
        $number_counters[$len]++;
        return $random_numbers[$len][$index];
    }
    
    return random_numbers($len);
}, $moban);

$letter_counters = [];
$moban = preg_replace_callback('/\{z(\d+)\}/', function($matches) use ($random_letters, &$letter_counters) {
    $len = (int)$matches[1];
    
    if (!isset($letter_counters[$len])) {
        $letter_counters[$len] = 0;
    }
    
    if (isset($random_letters[$len]) && is_array($random_letters[$len])) {
        $index = $letter_counters[$len] % count($random_letters[$len]);
        $letter_counters[$len]++;
        return $random_letters[$len][$index];apps.php
    }
    
    return random_letters($len);
}, $moban);

$mixed_counters = [];
$moban = preg_replace_callback('/\{m(\d+)\}/', function($matches) use ($random_mixed, &$mixed_counters) {
    $len = (int)$matches[1];
    
    if (!isset($mixed_counters[$len])) {
        $mixed_counters[$len] = 0;
    }
    
    if (isset($random_mixed[$len]) && is_array($random_mixed[$len])) {
        $index = $mixed_counters[$len] % count($random_mixed[$len]);
        $mixed_counters[$len]++;
        return $random_mixed[$len][$index];
    }
    
    return random_mixed($len);
}, $moban);

echo $moban;
?>
