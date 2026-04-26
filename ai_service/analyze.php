<?php
// Config dosyanın yolunu kendi sistemine göre ayarla
require_once '../config/config.php'; 

if (!isset($_FILES['photo'])) {
    die(json_encode(['error' => 'Fotoğraf bulunamadı.']));
}

$imagePath = $_FILES['photo']['tmp_name'];
$imageData = base64_encode(file_get_contents($imagePath));
$mimeType = mime_content_type($imagePath);

// Google'ın güncel ve en hızlı sürümü olan Gemini 2.5 Flash modeline geçiyoruz!
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . GEMINI_API_KEY;

$data = [
    "contents" => [
        [
            "parts" => [
                ["text" => "Bu fotoğraftaki yemeğin adını ve tahmini 1 porsiyon kalorisini söyle. SADECE şu formatta JSON döndür: {\"yemek\": \"Yemek Adı\", \"kalori\": 300}"],
                ["inline_data" => ["mime_type" => $mimeType, "data" => $imageData]]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

// InfinityFree'nin SSL (Güvenlik) engellerini bypass eden kodlar
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
curl_close($ch);

// Google'dan gelen cevabı alıp kutulara uygun JSON formatına çeviriyoruz
$jsonObj = json_decode($response, true);

// Hata kontrolü: Google'dan model veya limit hatası gelirse sistemi patlatmasın
if (isset($jsonObj['error'])) {
    $textInfo = '{"yemek": "Google API Hatası", "kalori": 0}';
} else {
    $textInfo = $jsonObj['candidates'][0]['content']['parts'][0]['text'] ?? '{"yemek": "Tanımlanamadı", "kalori": 0}';
}

// Gemini bazen yanıtın başına sonuna markdown tagları ekler, onları temizliyoruz
$textInfo = str_replace(['```json', '```'], '', $textInfo);

header('Content-Type: application/json');
echo trim($textInfo);
?>