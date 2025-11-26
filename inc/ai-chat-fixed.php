<?php
/**
 * 修正版 AI Chat Handler
 * single-grant.php の JavaScript から呼び出される AJAX ハンドラー
 */

// 既存の重複登録を削除し、1つだけにする
remove_action('wp_ajax_gi_ai_chat', 'gi_handle_ai_chat_request');
remove_action('wp_ajax_nopriv_gi_ai_chat', 'gi_handle_ai_chat_request');

add_action('wp_ajax_gi_ai_chat', 'gi_handle_ai_chat_request_fixed');
add_action('wp_ajax_nopriv_gi_ai_chat', 'gi_handle_ai_chat_request_fixed');

function gi_handle_ai_chat_request_fixed() {
    // デバッグログ
    error_log('=== AI Chat Request Started (FIXED VERSION) ===');
    error_log('POST data: ' . print_r($_POST, true));
    
    // Nonce検証
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    
    if (empty($nonce)) {
        error_log('❌ Nonce is empty');
        wp_send_json_error(array(
            'message' => 'セキュリティトークンがありません。ページを再読み込みしてください。',
            'code' => 'NONCE_MISSING'
        ));
        return;
    }
    
    $nonce_valid = wp_verify_nonce($nonce, 'gi_ai_nonce');
    error_log('Nonce verification result: ' . var_export($nonce_valid, true));
    
    if (!$nonce_valid) {
        error_log('❌ Nonce verification failed');
        wp_send_json_error(array(
            'message' => 'セキュリティチェックに失敗しました。ページを再読み込みしてください。',
            'code' => 'NONCE_INVALID'
        ));
        return;
    }
    
    error_log('✅ Nonce valid!');
    
    // パラメータ取得
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $question = isset($_POST['question']) ? sanitize_text_field($_POST['question']) : '';
    
    error_log("Post ID: {$post_id}, Question: {$question}");
    
    if (!$post_id || empty($question)) {
        wp_send_json_error(array(
            'message' => '質問または補助金IDが指定されていません。'
        ));
        return;
    }
    
    // 投稿データ取得
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'grant') {
        wp_send_json_error(array(
            'message' => '補助金情報が見つかりませんでした。'
        ));
        return;
    }
    
    // コンテキスト構築
    $context = gi_build_grant_context_for_chat($post_id);
    error_log('Context built: ' . print_r($context, true));
    
    // AI応答生成を試行
    $answer = gi_generate_chat_answer($question, $context);
    
    error_log('Generated answer: ' . substr($answer, 0, 200) . '...');
    
    wp_send_json_success(array(
        'answer' => $answer,
        'source' => 'ai_or_fallback',
        'version' => 'fixed_v1'
    ));
}

/**
 * チャット用のコンテキスト構築
 */
function gi_build_grant_context_for_chat($post_id) {
    $context = array(
        'title' => get_the_title($post_id),
        'organization' => '',
        'max_amount' => '',
        'deadline' => '',
        'grant_target' => '',
        'required_documents' => '',
        'eligible_expenses' => '',
        'application_status' => 'open',
        'subsidy_rate' => '',
        'application_method' => ''
    );
    
    // ACFフィールドから取得（関数存在チェック付き）
    if (function_exists('get_field')) {
        $context['organization'] = get_field('organization', $post_id) ?: '';
        $context['max_amount'] = get_field('max_amount', $post_id) ?: '';
        $context['deadline'] = get_field('deadline', $post_id) ?: '';
        $context['grant_target'] = get_field('grant_target', $post_id) ?: '';
        $context['required_documents'] = get_field('required_documents', $post_id) ?: '';
        $context['eligible_expenses'] = get_field('eligible_expenses', $post_id) ?: '';
        $context['application_status'] = get_field('application_status', $post_id) ?: 'open';
        $context['subsidy_rate'] = get_field('subsidy_rate', $post_id) ?: '';
        $context['application_method'] = get_field('application_method', $post_id) ?: '';
    } else {
        // ACFがない場合はpost_metaから取得
        $context['organization'] = get_post_meta($post_id, 'organization', true) ?: '';
        $context['max_amount'] = get_post_meta($post_id, 'max_amount', true) ?: '';
        $context['deadline'] = get_post_meta($post_id, 'deadline', true) ?: '';
        $context['grant_target'] = get_post_meta($post_id, 'grant_target', true) ?: '';
        $context['required_documents'] = get_post_meta($post_id, 'required_documents', true) ?: '';
        $context['eligible_expenses'] = get_post_meta($post_id, 'eligible_expenses', true) ?: '';
    }
    
    return $context;
}

/**
 * チャット応答生成（OpenAI使用可能時はAI、それ以外はフォールバック）
 */
function gi_generate_chat_answer($question, $context) {
    // OpenAI APIキーの確認
    $api_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : get_option('gi_openai_api_key', '');
    
    error_log('API Key exists: ' . (!empty($api_key) ? 'YES' : 'NO'));
    
    if (!empty($api_key)) {
        // OpenAI APIを使用
        $ai_answer = gi_call_openai_for_chat($question, $context, $api_key);
        if ($ai_answer) {
            return $ai_answer;
        }
    }
    
    // フォールバック応答
    return gi_generate_fallback_chat_answer($question, $context);
}

/**
 * OpenAI API呼び出し
 */
function gi_call_openai_for_chat($question, $context, $api_key) {
    $system_prompt = "あなたは補助金・助成金の専門アドバイザーです。以下の補助金について、ユーザーの質問に明確で簡潔に答えてください。

補助金情報:
名称: {$context['title']}
実施機関: {$context['organization']}
最大金額: {$context['max_amount']}
締切: {$context['deadline']}
対象者: {$context['grant_target']}
必要書類: {$context['required_documents']}
対象経費: {$context['eligible_expenses']}
補助率: {$context['subsidy_rate']}

回答は2-3段落以内で、分かりやすく説明してください。情報がない場合は「詳細は公式サイトでご確認ください」と案内してください。";

    $request_body = array(
        'model' => 'gpt-3.5-turbo',
        'messages' => array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $question)
        ),
        'max_tokens' => 500,
        'temperature' => 0.7
    );
    
    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_body),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        error_log('OpenAI API Error: ' . $response->get_error_message());
        return null;
    }
    
    $http_code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    
    error_log('OpenAI Response Code: ' . $http_code);
    
    if ($http_code !== 200) {
        error_log('OpenAI API Error Response: ' . print_r($body, true));
        return null;
    }
    
    if (isset($body['choices'][0]['message']['content'])) {
        return $body['choices'][0]['message']['content'];
    }
    
    return null;
}

/**
 * フォールバック応答生成
 */
function gi_generate_fallback_chat_answer($question, $context) {
    $question_lower = mb_strtolower($question, 'UTF-8');
    
    // 対象者に関する質問
    if (mb_strpos($question_lower, '対象') !== false || mb_strpos($question_lower, '誰') !== false || mb_strpos($question_lower, '資格') !== false) {
        if (!empty($context['grant_target'])) {
            return "この補助金「{$context['title']}」の対象者は以下の通りです:\n\n" . strip_tags($context['grant_target']) . "\n\n詳細な条件は公式サイトでご確認ください。";
        }
        return "対象者の詳細については、ページ内の「対象要件」セクションをご確認いただくか、実施機関（{$context['organization']}）にお問い合わせください。";
    }
    
    // 金額に関する質問
    if (mb_strpos($question_lower, '金額') !== false || mb_strpos($question_lower, 'いくら') !== false || mb_strpos($question_lower, '上限') !== false) {
        if (!empty($context['max_amount'])) {
            $response = "この補助金の最大金額は{$context['max_amount']}です。";
            if (!empty($context['subsidy_rate'])) {
                $response .= "\n補助率は{$context['subsidy_rate']}となっています。";
            }
            return $response . "\n\n実際の支給額は申請内容や対象経費により異なります。";
        }
        return "補助金額については、ページ内の「金額・補助率」セクションをご確認ください。";
    }
    
    // 締切に関する質問
    if (mb_strpos($question_lower, '締切') !== false || mb_strpos($question_lower, '期限') !== false || mb_strpos($question_lower, 'いつまで') !== false) {
        if (!empty($context['deadline'])) {
            return "申請締切は{$context['deadline']}です。\n\n書類準備に時間がかかる場合がありますので、余裕を持った準備をお勧めします。最新情報は公式サイトでご確認ください。";
        }
        return "締切については、ページ内の「スケジュール」セクションまたは公式サイトをご確認ください。";
    }
    
    // 書類に関する質問
    if (mb_strpos($question_lower, '書類') !== false || mb_strpos($question_lower, '必要') !== false || mb_strpos($question_lower, '準備') !== false) {
        if (!empty($context['required_documents'])) {
            return "申請に必要な書類は以下の通りです:\n\n" . strip_tags($context['required_documents']) . "\n\n詳細は実施機関の募集要項をご確認ください。";
        }
        return "必要書類については、ページ内の「申請要件」セクションをご確認ください。一般的には事業計画書、決算書、登記簿謄本などが必要です。";
    }
    
    // 申請方法に関する質問
    if (mb_strpos($question_lower, '申請') !== false || mb_strpos($question_lower, '方法') !== false || mb_strpos($question_lower, 'どうやって') !== false) {
        $response = "「{$context['title']}」の申請方法について:\n\n";
        if (!empty($context['application_method'])) {
            $response .= strip_tags($context['application_method']) . "\n\n";
        }
        $response .= "一般的な流れ:\n";
        $response .= "1. 申請要件の確認\n";
        $response .= "2. 必要書類の準備\n";
        $response .= "3. 申請書の作成・提出\n";
        $response .= "4. 審査結果の通知\n\n";
        $response .= "詳細は公式サイトまたは実施機関にお問い合わせください。";
        return $response;
    }
    
    // 対象経費に関する質問
    if (mb_strpos($question_lower, '経費') !== false || mb_strpos($question_lower, '使える') !== false) {
        if (!empty($context['eligible_expenses'])) {
            return "この補助金の対象経費は以下の通りです:\n\n" . strip_tags($context['eligible_expenses']) . "\n\n対象外の経費もありますので、詳細は募集要項をご確認ください。";
        }
        return "対象経費については、ページ内の「対象経費」セクションをご確認ください。";
    }
    
    // デフォルト応答
    return "ご質問ありがとうございます。\n\nこの補助金「{$context['title']}」は{$context['organization']}が実施しています。\n\n" .
           (!empty($context['max_amount']) ? "最大金額: {$context['max_amount']}\n" : "") .
           (!empty($context['deadline']) ? "締切: {$context['deadline']}\n" : "") .
           "\n詳細については、ページ内の各セクションをご確認いただくか、実施機関に直接お問い合わせください。\n\n他にご質問があればお気軽にどうぞ。";
}
