<?php

/*
Today's Date: March 3, 2025
GitHub: github.com/ownerpv
Telegram: ownerpc.t.me

Respect the author's work. Every creation takes time and effort. Give credit, support creativity, and don’t ignore their rights.
*/

$TOKEN = ""; //token bot
$API_URL = "https://api.telegram.org/bot$TOKEN/";

$update = json_decode(file_get_contents("php://input"), true);

if (isset($update["message"])) {
    $user_id  = $update["message"]["from"]["id"];
    $text     = $update["message"]["text"];
    $user_dir = "data/$user_id";

    if (!is_dir($user_dir)) {
        mkdir($user_dir, 0777, true);
    }

    $file = "$user_dir/data.json";
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];

    if ($text == "/start") {
        sendButton($user_id, "سلام! به ربات خوش آمدید.");
    } elseif (isset($data["step"]) && $data["step"] == "waiting_for_text") {
        $data = ["step" => "waiting_for_number", "text" => $text];
        file_put_contents($file, json_encode($data));
        sendMessage($user_id, "چند تا دکمه شیشه‌ای می‌خواهید داشته باشید؟ (حداکثر ۱۰)");
    } elseif (isset($data["step"]) && $data["step"] == "waiting_for_number") {
        if (is_numeric($text) && $text > 0 && $text <= 10) {
            $data["step"] = "waiting_for_order";
            $data["buttons_count"] = (int)$text;
            file_put_contents($file, json_encode($data));
            sendMessage($user_id, "ترتیب دکمه‌ها را بگویید، مجموع اعداد باید " . $text . " باشد.\n\nمثال : تعداد 7\n4\n3\nیا \n3\n3\n1\nیا\n3\n4\nیا \n3\n2\n1\n1");
        } else {
            sendMessage($user_id, "لطفاً یک عدد معتبر بین ۱ تا ۱۰ وارد کنید.");
        }
    } elseif (isset($data["step"]) && $data["step"] == "waiting_for_order") {
        $numbers = array_map('intval', explode("\n", trim($text)));
        if (array_sum($numbers) == $data["buttons_count"]) {
            $data["step"] = "done";
            $data["order"] = $numbers;
            file_put_contents($file, json_encode($data));
            sendKeyboard($user_id, $data["text"], $numbers, $data);
        } else {
            sendMessage($user_id, "مجموع اعداد باید برابر با " . $data["buttons_count"] . " باشد.");
        }
    } elseif (isset($data["step"]) && $data["step"] == "waiting_for_button_text") {
        if (isset($data["editing_button"])) {
            $button_id = $data["editing_button"];
            if (!isset($data["buttons"])) {
                $data["buttons"] = [];
            }
            $data["buttons"][$button_id]["text"] = $text;
            $data["step"] = "done";
            file_put_contents($file, json_encode($data));
            sendMessage($user_id, "متن دکمه تنظیم شد.");
        }
    } elseif (isset($data["step"]) && $data["step"] == "waiting_for_button_name") {
        if (isset($data["editing_button"])) {
            $button_id = $data["editing_button"];
            if (!isset($data["buttons"])) {
                $data["buttons"] = [];
            }
            $data["buttons"][$button_id]["name"] = $text;
            $data["step"] = "done";
            file_put_contents($file, json_encode($data));
            sendMessage($user_id, "نام دکمه تنظیم شد.");
        }
    } elseif (isset($data["step"]) && $data["step"] == "waiting_for_send_code") {
        $data["send_code"] = $text;
        $data["step"] = "waiting_for_group_ids";
        file_put_contents($file, json_encode($data));
        sendMessage($user_id, "توجه داشته باشید حتما ربات عضو گروه یا ادمین کانال باشد.\nلطفاً ایدی گروه ها یا کانال ها را بصورت زیر ارسال کنید.\nاگر چندتا گروه یا کانال بود :\n@idchannel\n@idgroup\n@idgroup");
    } elseif (isset($data["step"]) && $data["step"] == "waiting_for_group_ids") {
        $group_ids = array_filter(array_map('trim', explode("\n", $text)));
        $finished_file = "$user_dir/finished.json";
        if (!file_exists($finished_file)) {
            sendMessage($user_id, "کد یافت نشد.");
            exit;
        }
        $finished_sets = json_decode(file_get_contents($finished_file), true);
        $send_code = $data["send_code"];
        if (!isset($finished_sets[$send_code])) {
            sendMessage($user_id, "کد نامعتبر است.");
            exit;
        }
        $finished = $finished_sets[$send_code];
        $keyboard = ["inline_keyboard" => []];
        $button_count = 1;
        if (isset($finished["order"]) && isset($finished["buttons"])) {
            foreach ($finished["order"] as $num) {
                $row = [];
                for ($i = 0; $i < $num; $i++) {
                    $button_label = isset($finished["buttons"][$button_count]["name"]) ? $finished["buttons"][$button_count]["name"] : "دکمه $button_count";
                    if (isset($finished["buttons"][$button_count]["type"])) {
                        $type = $finished["buttons"][$button_count]["type"];
                        if ($type == "link") {
                            $row[] = ["text" => $button_label, "url" => $finished["buttons"][$button_count]["text"]];
                        } elseif ($type == "warning") {
                            // تنظیم callback_data به صورتی که شامل متن هشدار باشد (URL-encoded)
                            $warning_text = isset($finished["buttons"][$button_count]["text"]) ? $finished["buttons"][$button_count]["text"] : "هشدار";
                            $row[] = ["text" => $button_label, "callback_data" => "finished_warning:" . urlencode($warning_text)];
                        }
                    } else {
                        $row[] = ["text" => $button_label, "callback_data" => "dummy"];
                    }
                    $button_count++;
                }
                $keyboard["inline_keyboard"][] = $row;
            }
        }
        $message_text = (isset($finished["text"]) ? $finished["text"] : "");
        foreach ($group_ids as $gid) {
            sendMessageWithKeyboard($gid, $message_text, $keyboard);
        }
        sendMessage($user_id, "پیام به گروه/کانال ارسال شد.");
        $data["step"] = "done";
        file_put_contents($file, json_encode($data));
    }
} elseif (isset($update["callback_query"])) {
    // ابتدا بررسی می‌کنیم که آیا callback مربوط به finished_warning است
    $callback_data = $update["callback_query"]["data"];
    $callback_query_id = $update["callback_query"]["id"];
    if (strpos($callback_data, "finished_warning:") === 0) {
        $warning_text_encoded = substr($callback_data, strlen("finished_warning:"));
        $warning_text = urldecode($warning_text_encoded);
        answerCallback($callback_query_id, $warning_text, true);
        exit;
    }

    $original_chat_id = $update["callback_query"]["message"]["chat"]["id"];
    $user_id          = $update["callback_query"]["from"]["id"];
    $user_dir         = "data/$user_id";

    if (!is_dir($user_dir)) {
        mkdir($user_dir, 0777, true);
    }
    
    $file = "$user_dir/data.json";
    $data = file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    
    if ($callback_data == "button_clicked") {
        $data = ["step" => "waiting_for_text"];
        file_put_contents($file, json_encode($data));
        sendMessage($user_id, "برای ساخت دکمه شیشه‌ای، اول متن خود را ارسال کنید.");
    } elseif (strpos($callback_data, "edit_button_") === 0) {
        $button_id = substr($callback_data, strlen("edit_button_"));
        if (isset($data["buttons"][$button_id]["type"])) {
            $type = $data["buttons"][$button_id]["type"];
            if ($type == "link") {
                answerCallback($callback_query_id, "", false);
                exit;
            } elseif ($type == "warning") {
                $button_text = isset($data["buttons"][$button_id]["text"]) ? $data["buttons"][$button_id]["text"] : "دکمه $button_id";
                answerCallback($callback_query_id, $button_text, true);
                exit;
            } else {
                $button_text = isset($data["buttons"][$button_id]["text"]) ? $data["buttons"][$button_id]["text"] : "دکمه $button_id";
                answerCallback($callback_query_id, $button_text, false);
                exit;
            }
        } else {
            $data["editing_button"] = $button_id;
            file_put_contents($file, json_encode($data));
            sendEditOptions($user_id, $data);
        }
    } elseif ($callback_data == "set_text") {
        $data["step"] = "waiting_for_button_text";
        file_put_contents($file, json_encode($data));
        answerCallback($callback_query_id, "لطفاً متن جدید دکمه را ارسال کنید.", false);
    } elseif ($callback_data == "set_button_name") {
        $data["step"] = "waiting_for_button_name";
        file_put_contents($file, json_encode($data));
        answerCallback($callback_query_id, "لطفاً نام جدید دکمه را ارسال کنید.", false);
    } elseif ($callback_data == "set_warning") {
        if (isset($data["editing_button"])) {
            $button_id = $data["editing_button"];
            if (!isset($data["buttons"])) {
                $data["buttons"] = [];
            }
            $data["buttons"][$button_id]["type"] = "warning";
            file_put_contents($file, json_encode($data));
        }
        sendEditOptions($user_id, $data);
    } elseif ($callback_data == "set_link") {
        if (isset($data["editing_button"])) {
            $button_id = $data["editing_button"];
            if (!isset($data["buttons"])) {
                $data["buttons"] = [];
            }
            $button_text = isset($data["buttons"][$button_id]["text"]) ? $data["buttons"][$button_id]["text"] : "";
            if (!filter_var($button_text, FILTER_VALIDATE_URL)) {
                answerCallback($callback_query_id, "متن دکمه لینک معتبر نیست.", false);
                exit;
            } else {
                $data["buttons"][$button_id]["type"] = "link";
                file_put_contents($file, json_encode($data));
                answerCallback($callback_query_id, "", false);
                sendEditOptions($user_id, $data);
            }
        }
    } elseif ($callback_data == "update_list") {
        $message_id = $update["callback_query"]["message"]["message_id"];
        file_get_contents($API_URL . "deleteMessage?" . http_build_query([
            "chat_id"    => $user_id, // استفاده از from_id
            "message_id" => $message_id
        ]));
        if (isset($data["editing_button"])) {
            $button_id = $data["editing_button"];
            if (!isset($data["buttons"][$button_id]["type"]) || empty($data["buttons"][$button_id]["type"])) {
                answerCallback($callback_query_id, "لطفاً ابتدا نوع دکمه را مشخص کنید.", false);
                exit;
            }
        }
        if (isset($data["text"]) && isset($data["order"])) {
            sendKeyboard($user_id, $data["text"], $data["order"], $data);
        }
    } elseif ($callback_data == "end_process") {
        $message_id = $update["callback_query"]["message"]["message_id"];
        file_get_contents($API_URL . "deleteMessage?" . http_build_query([
            "chat_id"    => $user_id, // استفاده از from_id
            "message_id" => $message_id
        ]));
        if (isset($data["finished_code"])) {
            sendMessage($user_id, $data["finished_code"]);
            exit;
        }
        $finished_file = "$user_dir/finished.json";
        $finished_sets = file_exists($finished_file) ? json_decode(file_get_contents($finished_file), true) : [];
        $unique_code = generateRandomCode(8);
        $finished_sets[$unique_code] = [
            "text"    => isset($data["text"]) ? $data["text"] : "",
            "order"   => isset($data["order"]) ? $data["order"] : [],
            "buttons" => isset($data["buttons"]) ? $data["buttons"] : []
        ];
        file_put_contents($finished_file, json_encode($finished_sets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $data["finished_code"] = $unique_code;
        unset($data["text"]);
        unset($data["order"]);
        unset($data["buttons"]);
        unset($data["buttons_count"]);
        unset($data["editing_button"]);
        file_put_contents($file, json_encode($data));
        sendMessage($user_id, $unique_code);
        exit;
    } elseif ($callback_data == "send_to_group_or_channel") {
        $data["step"] = "waiting_for_send_code";
        file_put_contents($file, json_encode($data));
        sendMessage($user_id, "لطفاً اول کد خود را ارسال کنید");
    }
}

function sendButton($user_id, $message) {
    global $API_URL;
    $keyboard = [
        "inline_keyboard" => [
            [["text" => "ساخت دکمه شیشه ای", "callback_data" => "button_clicked"]],
            [["text" => "ارسال به گروه یا کانال", "callback_data" => "send_to_group_or_channel"]]
        ]
    ];
    $payload = [
        "chat_id"      => $user_id,
        "text"         => $message,
        "reply_markup" => json_encode($keyboard)
    ];
    file_get_contents($API_URL . "sendMessage?" . http_build_query($payload));
}

function sendMessage($user_id, $message) {
    global $API_URL;
    $payload = [
        "chat_id" => $user_id,
        "text"    => $message
    ];
    file_get_contents($API_URL . "sendMessage?" . http_build_query($payload));
}

function sendMessageWithKeyboard($chat_id, $message, $keyboard) {
    global $API_URL;
    $payload = [
        "chat_id"      => $chat_id,
        "text"         => $message,
        "reply_markup" => json_encode($keyboard)
    ];
    file_get_contents($API_URL . "sendMessage?" . http_build_query($payload));
}

function sendMessageHTML($user_id, $message) {
    global $API_URL;
    $payload = [
        "chat_id"    => $user_id,
        "text"       => $message,
        "parse_mode" => "HTML"
    ];
    file_get_contents($API_URL . "sendMessage?" . http_build_query($payload));
}

function sendKeyboard($user_id, $text, $numbers, $data = []) {
    global $API_URL;
    $keyboard = ["inline_keyboard" => []];
    $button_count = 1;
    foreach ($numbers as $num) {
        $row = [];
        for ($i = 0; $i < $num; $i++) {
            $button_label = isset($data["buttons"][$button_count]["name"]) ? $data["buttons"][$button_count]["name"] : "دکمه $button_count";
            if (isset($data["buttons"][$button_count]["type"])) {
                $type = $data["buttons"][$button_count]["type"];
                if ($type == "link") {
                    $row[] = ["text" => $button_label, "url" => $data["buttons"][$button_count]["text"]];
                } elseif ($type == "warning") {
                    $row[] = ["text" => $button_label, "callback_data" => "edit_button_$button_count"];
                }
            } else {
                $row[] = ["text" => $button_label, "callback_data" => "edit_button_$button_count"];
            }
            $button_count++;
        }
        $keyboard["inline_keyboard"][] = $row;
    }
    $keyboard["inline_keyboard"][] = [["text" => "پایان‌سازی", "callback_data" => "end_process"]];
    $payload = [
        "chat_id"      => $user_id,
        "text"         => "لطفاً نام و نوع دکمه رو مشخص کنید. با زدن روی دکمه می‌توانید نام و نوع را تغییر دهید.\n\nمتن: $text",
        "reply_markup" => json_encode($keyboard)
    ];
    file_get_contents($API_URL . "sendMessage?" . http_build_query($payload));
}

function sendEditOptions($user_id, $data) {
    global $API_URL;
    $editing_button = isset($data["editing_button"]) ? $data["editing_button"] : "";
    $current_type = "";
    if (isset($data["buttons"][$editing_button]["type"])) {
        $current_type = $data["buttons"][$editing_button]["type"];
    }
    $set_text_text    = "تنظیم متن";
    $set_warning_text = "نوع دکمه هشدار";
    $set_link_text    = "نوع دکمه لینک";
    $set_name_text    = "تنظیم نام دکمه";
    if ($current_type == "warning") {
        $set_warning_text .= " ✅";
    } elseif ($current_type == "link") {
        $set_link_text .= " ✅";
    }
    $keyboard = [
        "inline_keyboard" => [
            [
                ["text" => $set_text_text, "callback_data" => "set_text"],
                ["text" => $set_warning_text, "callback_data" => "set_warning"],
                ["text" => $set_link_text, "callback_data" => "set_link"]
            ],
            [
                ["text" => $set_name_text, "callback_data" => "set_button_name"],
                ["text" => "آپدیت لیست", "callback_data" => "update_list"]
            ]
        ]
    ];
    $payload = [
        "chat_id"      => $user_id,
        "text"         => "لطفاً نوع را مشخص کنید.",
        "reply_markup" => json_encode($keyboard)
    ];
    file_get_contents($API_URL . "sendMessage?" . http_build_query($payload));
}

function answerCallback($callback_query_id, $text, $show_alert = false) {
    global $API_URL;
    $payload = [
        "callback_query_id" => $callback_query_id,
        "text"              => $text,
        "show_alert"        => $show_alert ? "true" : "false"
    ];
    file_get_contents($API_URL . "answerCallbackQuery?" . http_build_query($payload));
}

function generateRandomCode($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $code;
}

function sendAlertMessage($user_id, $text) {
    global $API_URL;
    $payload = [
        "chat_id" => $user_id,
        "text"    => "⚠️ هشدار: " . $text
    ];
    file_get_contents($API_URL . "sendMessage?" . http_build_query($payload));
}

?>
