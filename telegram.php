<?php
function sendTelegram($chat_id, $message) {
    $bot_token = '7725125362:AAE3T5ZvBO9y8JpalgfJgq_dCvQKta1YgGA';
    $url = "https://api.telegram.org/bot$bot_token/sendMessage";
    
    $data = [
        'chat_id' => $chat_id,
        'text' => $message, 
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}
echo sendTelegram('123456789', '🚨 Tes notifikasi dari bot!');
?>