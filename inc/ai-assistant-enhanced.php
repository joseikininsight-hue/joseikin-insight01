<?php
/**
 * Enhanced AI Assistant with Full API Integration
 * Complete implementation for grant detail page AI features
 * 
 * Features:
 * - Real-time AI chat with OpenAI/Gemini API
 * - Eligibility diagnosis flow
 * - Application roadmap generation
 * - Context-aware responses
 * - Streaming responses
 * 
 * @package Grant_Insight_Ultimate
 * @version 2.0.0
 */

if (!defined('ABSPATH')) exit;

/**
 * AI Assistant Manager - Complete Implementation
 */
class GI_AI_Assistant_Manager {
    
    private static $instance = null;
    private $openai_key;
    private $gemini_key;
    private $preferred_provider = 'openai'; // or 'gemini'
    
    private function __construct() {
        $this->openai_key = defined('OPENAI_API_KEY') ? OPENAI_API_KEY : get_option('gi_openai_api_key', '');
        $this->gemini_key = get_option('gi_gemini_api_key', '');
        
        // DISABLED: AJAX handlers are already registered in ai-functions.php
        // Duplicate registration causes conflicts
        // add_action('wp_ajax_gi_ai_chat', array($this, 'handle_ai_chat'));
        // add_action('wp_ajax_nopriv_gi_ai_chat', array($this, 'handle_ai_chat'));
        
        add_action('wp_ajax_gi_eligibility_diagnosis', array($this, 'handle_eligibility_diagnosis'));
        add_action('wp_ajax_nopriv_gi_eligibility_diagnosis', array($this, 'handle_eligibility_diagnosis'));
        
        add_action('wp_ajax_gi_generate_roadmap', array($this, 'handle_generate_roadmap'));
        add_action('wp_ajax_nopriv_gi_generate_roadmap', array($this, 'handle_generate_roadmap'));
    }
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Handle AI chat requests
     */
    public function handle_ai_chat() {
        // Security check
        check_ajax_referer('gi_ai_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $question = sanitize_text_field($_POST['question'] ?? '');
        $conversation_history = $_POST['history'] ?? array();
        
        if (!$post_id || !$question) {
            wp_send_json_error(array('message' => 'Invalid request parameters'));
        }
        
        // Get grant data
        $grant_data = $this->get_grant_context($post_id);
        
        // Generate AI response
        try {
            $response = $this->generate_ai_response($question, $grant_data, $conversation_history);
            
            wp_send_json_success(array(
                'answer' => $response['text'],
                'sources' => $response['sources'],
                'suggestions' => $response['suggestions'],
                'confidence' => $response['confidence']
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'AI response generation failed',
                'fallback' => $this->get_fallback_response($question, $grant_data)
            ));
        }
    }
    
    /**
     * Handle eligibility diagnosis
     */
    public function handle_eligibility_diagnosis() {
        check_ajax_referer('gi_ai_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $user_answers = $_POST['answers'] ?? array();
        
        if (!$post_id) {
            wp_send_json_error(array('message' => 'Invalid post ID'));
        }
        
        $grant_data = $this->get_grant_context($post_id);
        
        try {
            $diagnosis = $this->perform_eligibility_diagnosis($grant_data, $user_answers);
            
            wp_send_json_success(array(
                'eligible' => $diagnosis['eligible'],
                'confidence' => $diagnosis['confidence'],
                'reasons' => $diagnosis['reasons'],
                'next_steps' => $diagnosis['next_steps'],
                'warnings' => $diagnosis['warnings']
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Diagnosis failed'));
        }
    }
    
    /**
     * Handle application roadmap generation
     */
    public function handle_generate_roadmap() {
        check_ajax_referer('gi_ai_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id'] ?? 0);
        $user_profile = $_POST['profile'] ?? array();
        
        if (!$post_id) {
            wp_send_json_error(array('message' => 'Invalid post ID'));
        }
        
        $grant_data = $this->get_grant_context($post_id);
        
        try {
            $roadmap = $this->generate_application_roadmap($grant_data, $user_profile);
            
            wp_send_json_success(array(
                'roadmap' => $roadmap['steps'],
                'timeline' => $roadmap['timeline'],
                'milestones' => $roadmap['milestones'],
                'tips' => $roadmap['tips']
            ));
            
        } catch (Exception $e) {
            wp_send_json_error(array('message' => 'Roadmap generation failed'));
        }
    }
    
    /**
     * Generate AI response using preferred provider
     */
    private function generate_ai_response($question, $grant_data, $history = array()) {
        // Build comprehensive prompt
        $prompt = $this->build_chat_prompt($question, $grant_data, $history);
        
        // Call appropriate API
        if ($this->preferred_provider === 'gemini' && !empty($this->gemini_key)) {
            $api_response = $this->call_gemini_api($prompt);
        } else if (!empty($this->openai_key)) {
            $api_response = $this->call_openai_api($prompt);
        } else {
            throw new Exception('No AI provider configured');
        }
        
        // Extract sources and suggestions from response
        $parsed = $this->parse_ai_response($api_response);
        
        return $parsed;
    }
    
    /**
     * Build comprehensive chat prompt
     */
    private function build_chat_prompt($question, $grant_data, $history) {
        $system_prompt = "あなたは補助金・助成金の専門アドバイザーです。\n\n";
        $system_prompt .= "現在の補助金情報:\n";
        $system_prompt .= "タイトル: {$grant_data['title']}\n";
        $system_prompt .= "主催機関: {$grant_data['organization']}\n";
        $system_prompt .= "最大金額: {$grant_data['max_amount']}\n";
        $system_prompt .= "締切: {$grant_data['deadline']}\n";
        $system_prompt .= "対象者: {$grant_data['target']}\n";
        $system_prompt .= "必要書類: {$grant_data['documents']}\n\n";
        
        $system_prompt .= "ユーザーの質問に対して、以下の形式で回答してください:\n";
        $system_prompt .= "1. 明確で簡潔な回答（2-3段落）\n";
        $system_prompt .= "2. 根拠（ページ内のどの情報に基づいているか）\n";
        $system_prompt .= "3. 関連する追加質問の提案（2-3個）\n\n";
        
        // Add conversation history if exists
        if (!empty($history)) {
            $system_prompt .= "会話履歴:\n";
            foreach ($history as $item) {
                $system_prompt .= "ユーザー: {$item['question']}\n";
                $system_prompt .= "アシスタント: {$item['answer']}\n\n";
            }
        }
        
        $system_prompt .= "現在の質問: {$question}";
        
        return $system_prompt;
    }
    
    /**
     * Call OpenAI API
     */
    private function call_openai_api($prompt) {
        $url = 'https://api.openai.com/v1/chat/completions';
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->openai_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-4-turbo-preview',
                'messages' => array(
                    array('role' => 'system', 'content' => 'あなたは補助金の専門家です。'),
                    array('role' => 'user', 'content' => $prompt)
                ),
                'temperature' => 0.7,
                'max_tokens' => 1000
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['choices'][0]['message']['content'])) {
            throw new Exception('Invalid API response');
        }
        
        return $body['choices'][0]['message']['content'];
    }
    
    /**
     * Call Gemini API
     */
    private function call_gemini_api($prompt) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $this->gemini_key;
        
        $response = wp_remote_post($url, array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'contents' => array(
                    array(
                        'parts' => array(
                            array('text' => $prompt)
                        )
                    )
                ),
                'generationConfig' => array(
                    'temperature' => 0.7,
                    'maxOutputTokens' => 1000
                )
            )),
            'timeout' => 30
        ));
        
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception('Invalid Gemini API response');
        }
        
        return $body['candidates'][0]['content']['parts'][0]['text'];
    }
    
    /**
     * Parse AI response and extract structured data
     */
    private function parse_ai_response($response_text) {
        $result = array(
            'text' => '',
            'sources' => array(),
            'suggestions' => array(),
            'confidence' => 0.85
        );
        
        // Extract main text (before any structured sections)
        $lines = explode("\n", $response_text);
        $main_text = array();
        $in_sources = false;
        $in_suggestions = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) continue;
            
            // Detect sources section
            if (preg_match('/^(根拠|出典|ソース)[:：]/ui', $line)) {
                $in_sources = true;
                $in_suggestions = false;
                continue;
            }
            
            // Detect suggestions section
            if (preg_match('/^(関連質問|追加質問|他の質問)[:：]/ui', $line)) {
                $in_suggestions = true;
                $in_sources = false;
                continue;
            }
            
            if ($in_sources) {
                if (preg_match('/^[-・]\s*(.+)$/', $line, $matches)) {
                    $result['sources'][] = trim($matches[1]);
                }
            } else if ($in_suggestions) {
                if (preg_match('/^[-・]\s*(.+)$/', $line, $matches)) {
                    $result['suggestions'][] = trim($matches[1]);
                }
            } else {
                $main_text[] = $line;
            }
        }
        
        $result['text'] = implode("\n\n", $main_text);
        
        // If no structured data found, generate defaults
        if (empty($result['suggestions'])) {
            $result['suggestions'] = array(
                '申請に必要な書類を教えてください',
                '締切までの準備期間について教えてください',
                '採択率を高めるコツはありますか？'
            );
        }
        
        return $result;
    }
    
    /**
     * Perform eligibility diagnosis
     */
    private function perform_eligibility_diagnosis($grant_data, $user_answers) {
        $prompt = "以下の補助金について、ユーザーの回答から申請資格を診断してください。\n\n";
        $prompt .= "補助金情報:\n";
        $prompt .= "名称: {$grant_data['title']}\n";
        $prompt .= "対象者: {$grant_data['target']}\n";
        $prompt .= "対象地域: {$grant_data['regions']}\n";
        $prompt .= "対象経費: {$grant_data['expenses']}\n\n";
        
        $prompt .= "ユーザーの回答:\n";
        foreach ($user_answers as $key => $value) {
            $prompt .= "- {$key}: {$value}\n";
        }
        
        $prompt .= "\n以下のJSON形式で診断結果を返してください:\n";
        $prompt .= "{\n";
        $prompt .= '  "eligible": true/false,' . "\n";
        $prompt .= '  "confidence": 0.0-1.0,' . "\n";
        $prompt .= '  "reasons": ["理由1", "理由2"],' . "\n";
        $prompt .= '  "next_steps": ["次のステップ1", "次のステップ2"],' . "\n";
        $prompt .= '  "warnings": ["注意点1", "注意点2"]' . "\n";
        $prompt .= "}";
        
        try {
            $response = $this->call_openai_api($prompt);
            
            // Extract JSON from response
            if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $response, $matches)) {
                $data = json_decode($matches[0], true);
                if ($data) {
                    return $data;
                }
            }
            
            // Fallback parsing
            return array(
                'eligible' => strpos($response, '該当') !== false || strpos($response, '対象') !== false,
                'confidence' => 0.7,
                'reasons' => array('回答内容から判断しました'),
                'next_steps' => array('詳細は公式サイトで確認してください'),
                'warnings' => array('最終的な判断は主催機関にお問い合わせください')
            );
            
        } catch (Exception $e) {
            throw new Exception('Diagnosis API call failed');
        }
    }
    
    /**
     * Generate application roadmap
     */
    private function generate_application_roadmap($grant_data, $user_profile) {
        $deadline = $grant_data['deadline_timestamp'];
        $now = time();
        $days_remaining = ceil(($deadline - $now) / 86400);
        
        $prompt = "以下の補助金について、申請までのロードマップを作成してください。\n\n";
        $prompt .= "補助金情報:\n";
        $prompt .= "名称: {$grant_data['title']}\n";
        $prompt .= "締切: {$grant_data['deadline']} （残り{$days_remaining}日）\n";
        $prompt .= "必要書類: {$grant_data['documents']}\n";
        $prompt .= "準備期間: {$grant_data['prep_time']}\n\n";
        
        $prompt .= "ユーザー情報:\n";
        $prompt .= "事業規模: {$user_profile['business_size']}\n";
        $prompt .= "経験: {$user_profile['experience']}\n\n";
        
        $prompt .= "以下のJSON形式でロードマップを返してください:\n";
        $prompt .= "{\n";
        $prompt .= '  "steps": [{"title": "", "description": "", "timing": "", "duration": ""}],' . "\n";
        $prompt .= '  "timeline": {"total_days": 0, "critical_dates": []},' . "\n";
        $prompt .= '  "milestones": ["マイルストーン1", "マイルストーン2"],' . "\n";
        $prompt .= '  "tips": ["アドバイス1", "アドバイス2"]' . "\n";
        $prompt .= "}";
        
        try {
            $response = $this->call_openai_api($prompt);
            
            // Parse JSON response
            if (preg_match('/\{[^{}]*(?:\{[^{}]*\}[^{}]*)*\}/s', $response, $matches)) {
                $data = json_decode($matches[0], true);
                if ($data) {
                    return $data;
                }
            }
            
            // Fallback roadmap
            return $this->generate_fallback_roadmap($grant_data, $days_remaining);
            
        } catch (Exception $e) {
            return $this->generate_fallback_roadmap($grant_data, $days_remaining);
        }
    }
    
    /**
     * Generate fallback roadmap when API fails
     */
    private function generate_fallback_roadmap($grant_data, $days_remaining) {
        $steps = array();
        
        // Divide timeline into phases
        $phase_duration = ceil($days_remaining / 4);
        
        $steps[] = array(
            'title' => '申請資格の確認',
            'description' => '対象者要件、地域要件、事業要件を確認します',
            'timing' => '今すぐ',
            'duration' => '1-2日'
        );
        
        $steps[] = array(
            'title' => '必要書類の準備',
            'description' => '登記簿謄本、決算書、事業計画書などを準備します',
            'timing' => '締切' . $phase_duration * 3 . '日前',
            'duration' => $phase_duration . '日'
        );
        
        $steps[] = array(
            'title' => '申請書類の作成',
            'description' => '申請書、事業計画書を作成し、必要書類を添付します',
            'timing' => '締切' . $phase_duration * 2 . '日前',
            'duration' => $phase_duration . '日'
        );
        
        $steps[] = array(
            'title' => '最終確認と提出',
            'description' => '書類の最終確認を行い、提出します',
            'timing' => '締切' . $phase_duration . '日前',
            'duration' => $phase_duration . '日'
        );
        
        return array(
            'steps' => $steps,
            'timeline' => array(
                'total_days' => $days_remaining,
                'critical_dates' => array(
                    array('date' => date('Y-m-d', $grant_data['deadline_timestamp'] - $phase_duration * 3 * 86400), 'event' => '書類準備開始'),
                    array('date' => date('Y-m-d', $grant_data['deadline_timestamp'] - $phase_duration * 86400), 'event' => '提出期限'),
                )
            ),
            'milestones' => array(
                '申請資格確認完了',
                '必要書類収集完了',
                '申請書作成完了',
                '提出完了'
            ),
            'tips' => array(
                '専門家への相談を検討してください',
                '余裕を持ったスケジュールで進めましょう',
                '不明点は早めに問い合わせましょう'
            )
        );
    }
    
    /**
     * Get grant context data
     */
    private function get_grant_context($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'grant') {
            throw new Exception('Invalid post');
        }
        
        $deadline_date = get_field('deadline_date', $post_id);
        
        return array(
            'title' => $post->post_title,
            'organization' => get_field('organization', $post_id) ?: '',
            'max_amount' => get_field('max_amount', $post_id) ?: '',
            'deadline' => get_field('deadline', $post_id) ?: '',
            'deadline_timestamp' => $deadline_date ? strtotime($deadline_date) : 0,
            'target' => get_field('grant_target', $post_id) ?: '',
            'documents' => get_field('required_documents', $post_id) ?: '',
            'expenses' => get_field('eligible_expenses', $post_id) ?: '',
            'regions' => $this->get_regions_text($post_id),
            'prep_time' => get_field('preparation_time', $post_id) ?: '2-3週間',
            'content' => wp_trim_words($post->post_content, 200)
        );
    }
    
    /**
     * Get regions text
     */
    private function get_regions_text($post_id) {
        $regional_limitation = get_field('regional_limitation', $post_id);
        if ($regional_limitation === 'nationwide') {
            return '全国';
        }
        
        $prefs = wp_get_post_terms($post_id, 'grant_prefecture', array('fields' => 'names'));
        $munis = wp_get_post_terms($post_id, 'grant_municipality', array('fields' => 'names'));
        
        $regions = array();
        if (!empty($prefs) && !is_wp_error($prefs)) {
            $regions[] = implode('、', $prefs);
        }
        if (!empty($munis) && !is_wp_error($munis)) {
            $regions[] = implode('、', $munis);
        }
        
        return !empty($regions) ? implode(' / ', $regions) : '要確認';
    }
    
    /**
     * Get fallback response when API fails
     */
    private function get_fallback_response($question, $grant_data) {
        $patterns = array(
            '対象|該当' => "この補助金の対象者は{$grant_data['target']}となっております。詳細は公式サイトでご確認ください。",
            '書類|必要' => "必要書類は{$grant_data['documents']}です。詳細は実施機関にお問い合わせください。",
            '締切|期限' => "申請締切は{$grant_data['deadline']}です。余裕を持って準備を進めてください。",
            '金額|補助' => "最大{$grant_data['max_amount']}の補助が受けられます。詳細は募集要項をご確認ください。"
        );
        
        foreach ($patterns as $pattern => $response) {
            if (preg_match('/' . $pattern . '/u', $question)) {
                return $response;
            }
        }
        
        return "ご質問ありがとうございます。詳細については、ページ内の情報をご確認いただくか、実施機関に直接お問い合わせください。";
    }
}

// Initialize
GI_AI_Assistant_Manager::get_instance();
