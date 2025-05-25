<?php
// OpenWeatherMap API anahtarı
$apiKey = '684aa33ff6d00ad68070001c3fdd5477'; // Kullanıcının API anahtarı

// Erzurum şehri için hava durumu çekme
$city = 'Erzurum';
$lat = 39.9000;
$lon = 41.2700;

// Mevcut hava durumu için sorgu
$currentUrl = "https://api.openweathermap.org/data/2.5/weather?lat=$lat&lon=$lon&appid=$apiKey&lang=tr";

// 5 günlük tahmin için sorgu
$forecastUrl = "https://api.openweathermap.org/data/2.5/forecast?lat=$lat&lon=$lon&appid=$apiKey&lang=tr";

// CURL ile veri çekme işlemi
function fetchApiData($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // SSL doğrulamasını devre dışı bırak
    $response = curl_exec($ch);
    
    $error = null;
    if($response === false) {
        $error = curl_error($ch);
    }
    
    curl_close($ch);
    return ['response' => $response, 'error' => $error];
}

$currentData = null;
$forecastData = null;
$errors = [];

// Mevcut hava durumu verilerini çek
$currentResult = fetchApiData($currentUrl);
if($currentResult['error']) {
    $errors[] = "Mevcut hava durumu çekilemedi: " . $currentResult['error'];
} else {
    $currentData = json_decode($currentResult['response'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = "JSON hatası (mevcut): " . json_last_error_msg();
    }
}

// Tahmin verilerini çek
$forecastResult = fetchApiData($forecastUrl);
if($forecastResult['error']) {
    $errors[] = "Tahmin hava durumu çekilemedi: " . $forecastResult['error'];
} else {
    $forecastData = json_decode($forecastResult['response'], true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = "JSON hatası (tahmin): " . json_last_error_msg();
    }
}

// Sonuçları birleştirmek için dizi oluştur
$result = [
    'current' => null,
    'forecast' => [],
    'errors' => $errors
];

header('Content-Type: application/json; charset=utf-8');

// Mevcut hava durumu bilgileri
if ($currentData && isset($currentData['main'])) {
    $tempK = $currentData['main']['temp'];
    $tempC = round($tempK - 273.15, 1);
    $description = $currentData['weather'][0]['description'];
    $icon = $currentData['weather'][0]['icon'];
    $windSpeed = $currentData['wind']['speed'];
    $humidity = $currentData['main']['humidity'];
    $conditions = getWeatherCondition($description, $tempC, $windSpeed);
    
    $result['current'] = [
        'status' => ucfirst($description),
        'temp' => $tempC,
        'icon' => $icon,
        'wind' => $windSpeed,
        'humidity' => $humidity,
        'conditions' => $conditions
    ];
}

// Günlük tahmin bilgileri
if ($forecastData && isset($forecastData['list'])) {
    $processedDates = [];
    
    foreach ($forecastData['list'] as $forecast) {
        $date = date('Y-m-d', $forecast['dt']);
        
        // Her gün için sadece bir tahmin ekle
        if (!in_array($date, $processedDates) && count($processedDates) < 5) {
            $tempK = $forecast['main']['temp'];
            $tempC = round($tempK - 273.15, 1);
            $description = $forecast['weather'][0]['description'];
            $icon = $forecast['weather'][0]['icon'];
            $windSpeed = $forecast['wind']['speed'];
            $humidity = $forecast['main']['humidity'];
            $conditions = getWeatherCondition($description, $tempC, $windSpeed);
            
            $result['forecast'][] = [
                'date' => $date,
                'day' => getWeekday($date),
                'status' => ucfirst($description),
                'temp' => $tempC,
                'icon' => $icon,
                'wind' => $windSpeed,
                'humidity' => $humidity,
                'conditions' => $conditions
            ];
            
            $processedDates[] = $date;
        }
    }
}

echo json_encode($result);

/**
 * Hava durumunun etkinliğe etkisi için değerlendirme yapar
 */
function getWeatherCondition($description, $temp, $wind) {
    $description = mb_strtolower($description, 'UTF-8');
    
    // Kötü hava koşulları
    if (
        strpos($description, 'kar') !== false ||
        strpos($description, 'fırtına') !== false ||
        strpos($description, 'sağanak') !== false ||
        strpos($description, 'şiddetli yağmur') !== false ||
        $wind > 10.0 ||
        $temp < -10
    ) {
        return 'bad';
    }
    
    // Orta seviye hava koşulları
    if (
        strpos($description, 'yağmur') !== false ||
        strpos($description, 'bulutlu') !== false ||
        strpos($description, 'sisli') !== false ||
        $wind > 5.0 ||
        $temp < 0 ||
        $temp > 30
    ) {
        return 'moderate';
    }
    
    // İyi hava koşulları
    return 'good';
}

/**
 * Tarih formatından haftanın gününü döndürür
 */
function getWeekday($date) {
    $days = [
        'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi', 'Pazar'
    ];
    
    $timestamp = strtotime($date);
    $dayOfWeek = date('N', $timestamp) - 1;
    
    return $days[$dayOfWeek];
}

// Kullanıcının yaptığı değişiklikler buraya eklenecek
?> 