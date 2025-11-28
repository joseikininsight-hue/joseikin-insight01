<?php
/**
 * WordPress Performance Diagnostic Tool
 * カスタム投稿タイプのパフォーマンス問題を診断・分析
 * 
 * 使用方法:
 * 1. このファイルをテーマの inc/ ディレクトリに配置
 * 2. functions.php に以下を追加:
 *    require_once get_template_directory() . '/inc/performance-diagnostic.php';
 * 3. 管理画面で「ツール」→「パフォーマンス診断」にアクセス
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GI_Performance_Diagnostic {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // 管理画面メニュー追加
        add_action('admin_menu', [$this, 'add_diagnostic_menu']);
        
        // AJAX ハンドラー
        add_action('wp_ajax_gi_run_diagnostic', [$this, 'ajax_run_diagnostic']);
    }
    
    /**
     * 管理画面にメニュー追加
     */
    public function add_diagnostic_menu() {
        add_management_page(
            'パフォーマンス診断',
            'パフォーマンス診断',
            'manage_options',
            'gi-performance-diagnostic',
            [$this, 'render_diagnostic_page']
        );
    }
    
    /**
     * 診断ページの表示
     */
    public function render_diagnostic_page() {
        if (!current_user_can('manage_options')) {
            wp_die('権限がありません');
        }
        
        ?>
        <div class="wrap">
            <h1>🔍 WordPress パフォーマンス診断</h1>
            <p>カスタム投稿タイプ「助成金・補助金」のパフォーマンスを診断します。</p>
            
            <div id="diagnostic-results" style="margin-top: 20px;">
                <button id="run-diagnostic" class="button button-primary button-hero">
                    🚀 診断を実行
                </button>
            </div>
            
            <div id="diagnostic-output" style="margin-top: 30px; display: none;">
                <h2>診断結果</h2>
                <div id="diagnostic-content"></div>
            </div>
        </div>
        
        <style>
            .diagnostic-section {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .diagnostic-section h3 {
                margin-top: 0;
                border-bottom: 2px solid #0073aa;
                padding-bottom: 10px;
            }
            .issue-critical { color: #dc3232; font-weight: bold; }
            .issue-warning { color: #f0b849; font-weight: bold; }
            .issue-ok { color: #46b450; font-weight: bold; }
            .diagnostic-item {
                padding: 10px;
                margin: 10px 0;
                border-left: 4px solid #0073aa;
                background: #f9f9f9;
            }
            .diagnostic-item.critical { border-left-color: #dc3232; }
            .diagnostic-item.warning { border-left-color: #f0b849; }
            .diagnostic-item.ok { border-left-color: #46b450; }
            .code-block {
                background: #282c34;
                color: #abb2bf;
                padding: 15px;
                border-radius: 4px;
                overflow-x: auto;
                font-family: 'Courier New', monospace;
                margin: 10px 0;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            $('#run-diagnostic').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('診断中...');
                
                $('#diagnostic-output').show();
                $('#diagnostic-content').html('<p>診断を実行しています。お待ちください...</p>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'gi_run_diagnostic',
                        nonce: '<?php echo wp_create_nonce('gi_diagnostic_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#diagnostic-content').html(response.data.html);
                        } else {
                            $('#diagnostic-content').html('<p class="issue-critical">エラー: ' + response.data.message + '</p>');
                        }
                        button.prop('disabled', false).text('🚀 診断を実行');
                    },
                    error: function() {
                        $('#diagnostic-content').html('<p class="issue-critical">診断中にエラーが発生しました。</p>');
                        button.prop('disabled', false).text('🚀 診断を実行');
                    }
                });
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX: 診断実行
     */
    public function ajax_run_diagnostic() {
        check_ajax_referer('gi_diagnostic_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => '権限がありません']);
        }
        
        $results = $this->run_full_diagnostic();
        
        wp_send_json_success([
            'html' => $this->generate_diagnostic_html($results)
        ]);
    }
    
    /**
     * 完全診断を実行
     */
    private function run_full_diagnostic() {
        $results = [
            'post_type_config' => $this->check_post_type_config(),
            'database_stats' => $this->check_database_stats(),
            'query_performance' => $this->check_query_performance(),
            'meta_fields' => $this->check_meta_fields(),
            'revisions' => $this->check_revisions(),
            'admin_performance' => $this->check_admin_performance(),
        ];
        
        return $results;
    }
    
    /**
     * 1. カスタム投稿タイプ設定の確認
     */
    private function check_post_type_config() {
        global $wp_post_types;
        
        $results = [
            'status' => 'ok',
            'issues' => [],
            'recommendations' => []
        ];
        
        if (!isset($wp_post_types['grant'])) {
            $results['status'] = 'critical';
            $results['issues'][] = 'カスタム投稿タイプ "grant" が登録されていません';
            return $results;
        }
        
        $grant_type = $wp_post_types['grant'];
        
        // 階層構造チェック（最重要）
        if (!empty($grant_type->hierarchical)) {
            $results['status'] = 'critical';
            $results['issues'][] = '⚠️ hierarchical が true に設定されています！';
            $results['issues'][] = '投稿数が多い場合、これが重さの最大の原因です';
            $results['recommendations'][] = [
                'title' => 'hierarchical を false に変更',
                'severity' => 'critical',
                'code' => "register_post_type('grant', array(\n    'hierarchical' => false, // ← false に変更\n    // ... その他の設定\n));"
            ];
        }
        
        // REST API 設定
        if (empty($grant_type->show_in_rest)) {
            $results['issues'][] = 'REST API が無効です（Gutenberg エディタで問題が起きる可能性）';
            $results['recommendations'][] = [
                'title' => 'REST API を有効化',
                'severity' => 'warning',
                'code' => "'show_in_rest' => true,\n'rest_base' => 'grants',"
            ];
        }
        
        // サポート機能チェック
        if (in_array('revisions', $grant_type->supports)) {
            $results['issues'][] = 'リビジョン機能が有効です（データベース負荷の原因）';
            $results['recommendations'][] = [
                'title' => 'リビジョンを制限または無効化',
                'severity' => 'warning',
                'code' => "// functions.php に追加\ndefine('WP_POST_REVISIONS', 3); // 3件まで保存\n// または\ndefine('WP_POST_REVISIONS', false); // 完全に無効化"
            ];
        }
        
        return $results;
    }
    
    /**
     * 2. データベース統計
     */
    private function check_database_stats() {
        global $wpdb;
        
        $results = [
            'status' => 'ok',
            'stats' => [],
            'issues' => [],
            'recommendations' => []
        ];
        
        // 投稿数
        $grant_count = wp_count_posts('grant');
        $total_grants = $grant_count->publish + $grant_count->draft + $grant_count->pending;
        $results['stats']['total_posts'] = $total_grants;
        
        if ($total_grants > 5000) {
            $results['status'] = 'warning';
            $results['issues'][] = "投稿数が {$total_grants} 件あります（5000件以上は要注意）";
        }
        
        // postmeta テーブルサイズ
        $postmeta_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'grant'
        ");
        $results['stats']['postmeta_rows'] = $postmeta_count;
        
        $avg_meta_per_post = $total_grants > 0 ? round($postmeta_count / $total_grants, 2) : 0;
        $results['stats']['avg_meta_per_post'] = $avg_meta_per_post;
        
        if ($avg_meta_per_post > 30) {
            $results['status'] = 'critical';
            $results['issues'][] = "1投稿あたり平均 {$avg_meta_per_post} 個のカスタムフィールドがあります（30個以上は非常に重い）";
            $results['recommendations'][] = [
                'title' => 'カスタムフィールドの使用を削減',
                'severity' => 'critical',
                'description' => '可能な限りタクソノミー（カテゴリー・タグ）で分類することを推奨します'
            ];
        }
        
        // リビジョン数
        $revisions_count = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'revision' 
            AND post_parent IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'grant')
        ");
        $results['stats']['revisions'] = $revisions_count;
        
        if ($revisions_count > 1000) {
            $results['status'] = 'critical';
            $results['issues'][] = "リビジョンが {$revisions_count} 件保存されています（削除を推奨）";
            $results['recommendations'][] = [
                'title' => 'リビジョンを一括削除',
                'severity' => 'critical',
                'code' => "// プラグイン「WP-Optimize」を使用するか、以下のSQLを実行:\nDELETE FROM {$wpdb->posts} WHERE post_type = 'revision';\nDELETE FROM {$wpdb->postmeta} WHERE post_id NOT IN (SELECT ID FROM {$wpdb->posts});"
            ];
        }
        
        // 自動下書き
        $auto_drafts = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'grant' AND post_status = 'auto-draft'
        ");
        $results['stats']['auto_drafts'] = $auto_drafts;
        
        if ($auto_drafts > 100) {
            $results['issues'][] = "自動下書きが {$auto_drafts} 件あります（削除推奨）";
        }
        
        // postmeta テーブルのインデックス確認
        $indexes = $wpdb->get_results("SHOW INDEX FROM {$wpdb->postmeta}");
        $has_meta_key_index = false;
        foreach ($indexes as $index) {
            if ($index->Column_name === 'meta_key') {
                $has_meta_key_index = true;
                break;
            }
        }
        
        if (!$has_meta_key_index) {
            $results['status'] = 'warning';
            $results['issues'][] = 'postmeta テーブルに meta_key のインデックスがありません';
            $results['recommendations'][] = [
                'title' => 'データベースインデックスを追加',
                'severity' => 'warning',
                'code' => "-- phpMyAdmin や SQL クライアントで実行:\nALTER TABLE {$wpdb->postmeta} ADD INDEX meta_key_index (meta_key(191));"
            ];
        }
        
        return $results;
    }
    
    /**
     * 3. クエリパフォーマンス
     */
    private function check_query_performance() {
        $results = [
            'status' => 'ok',
            'issues' => [],
            'recommendations' => []
        ];
        
        // クエリ実行時間を測定
        $start_time = microtime(true);
        
        $query = new WP_Query([
            'post_type' => 'grant',
            'posts_per_page' => 20,
            'fields' => 'ids',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        ]);
        
        $query_time = microtime(true) - $start_time;
        $results['query_time'] = round($query_time * 1000, 2); // ミリ秒
        
        if ($query_time > 0.5) {
            $results['status'] = 'critical';
            $results['issues'][] = "基本的なクエリに {$results['query_time']}ms かかっています（500ms以上は要注意）";
        } elseif ($query_time > 0.2) {
            $results['status'] = 'warning';
            $results['issues'][] = "クエリ実行時間: {$results['query_time']}ms（最適化の余地あり）";
        }
        
        // meta_query を含むクエリ
        $start_time = microtime(true);
        
        $meta_query = new WP_Query([
            'post_type' => 'grant',
            'posts_per_page' => 20,
            'meta_query' => [
                [
                    'key' => 'grant_amount',
                    'compare' => 'EXISTS'
                ]
            ]
        ]);
        
        $meta_query_time = microtime(true) - $start_time;
        $results['meta_query_time'] = round($meta_query_time * 1000, 2);
        
        if ($meta_query_time > 1.0) {
            $results['status'] = 'critical';
            $results['issues'][] = "meta_query の実行に {$results['meta_query_time']}ms かかっています（非常に遅い）";
            $results['recommendations'][] = [
                'title' => 'meta_query の使用を避ける',
                'severity' => 'critical',
                'description' => 'タクソノミー（税金系）での検索に置き換えることを強く推奨します'
            ];
        }
        
        return $results;
    }
    
    /**
     * 4. カスタムフィールド分析
     */
    private function check_meta_fields() {
        global $wpdb;
        
        $results = [
            'status' => 'ok',
            'top_keys' => [],
            'issues' => [],
            'recommendations' => []
        ];
        
        // よく使われるメタキー TOP 10
        $top_keys = $wpdb->get_results("
            SELECT pm.meta_key, COUNT(*) as count
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
            WHERE p.post_type = 'grant'
            GROUP BY pm.meta_key
            ORDER BY count DESC
            LIMIT 10
        ", ARRAY_A);
        
        $results['top_keys'] = $top_keys;
        
        // ACF フィールドの検出
        $acf_count = 0;
        foreach ($top_keys as $key) {
            if (strpos($key['meta_key'], '_') === 0 || strpos($key['meta_key'], 'field_') === 0) {
                $acf_count++;
            }
        }
        
        if ($acf_count > 5) {
            $results['issues'][] = "ACF（Advanced Custom Fields）のフィールドが多数検出されました";
            $results['recommendations'][] = [
                'title' => 'ACF の使用を最小限に',
                'severity' => 'warning',
                'description' => 'ACF は便利ですが、パフォーマンスに影響します。可能な限りネイティブフィールドやタクソノミーを使用してください。'
            ];
        }
        
        return $results;
    }
    
    /**
     * 5. リビジョン詳細分析
     */
    private function check_revisions() {
        global $wpdb;
        
        $results = [
            'status' => 'ok',
            'stats' => [],
            'issues' => [],
            'recommendations' => []
        ];
        
        // リビジョンが多い投稿 TOP 5
        $top_revisions = $wpdb->get_results("
            SELECT p.post_title, p.ID, COUNT(r.ID) as revision_count
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->posts} r ON p.ID = r.post_parent AND r.post_type = 'revision'
            WHERE p.post_type = 'grant'
            GROUP BY p.ID
            ORDER BY revision_count DESC
            LIMIT 5
        ", ARRAY_A);
        
        $results['top_revisions'] = $top_revisions;
        
        $max_revisions = 0;
        foreach ($top_revisions as $post) {
            if ($post['revision_count'] > $max_revisions) {
                $max_revisions = $post['revision_count'];
            }
        }
        
        if ($max_revisions > 50) {
            $results['status'] = 'critical';
            $results['issues'][] = "一部の投稿に {$max_revisions} 個以上のリビジョンがあります";
            $results['recommendations'][] = [
                'title' => 'リビジョンの数を制限',
                'severity' => 'critical',
                'code' => "// functions.php に追加\ndefine('WP_POST_REVISIONS', 3); // 最新3件のみ保存"
            ];
        }
        
        return $results;
    }
    
    /**
     * 6. 管理画面パフォーマンス
     */
    private function check_admin_performance() {
        $results = [
            'status' => 'ok',
            'issues' => [],
            'recommendations' => []
        ];
        
        // 管理画面カラム数チェック（この診断ツール自体が管理画面外で動く可能性があるため簡易チェック）
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'grant') {
            // 実際の管理画面でのみチェック可能
        }
        
        // プラグイン競合の可能性
        if (class_exists('ACF')) {
            $results['issues'][] = 'Advanced Custom Fields が有効です（パフォーマンスに影響する可能性）';
        }
        
        if (is_plugin_active('admin-columns/admin-columns.php') || is_plugin_active('codepress-admin-columns/codepress-admin-columns.php')) {
            $results['issues'][] = 'Admin Columns プラグインが有効です（一覧表示が重くなる可能性）';
            $results['recommendations'][] = [
                'title' => 'Admin Columns の表示項目を最小限に',
                'severity' => 'warning',
                'description' => '管理画面の「表示オプション」から不要なカラムのチェックを外してください'
            ];
        }
        
        return $results;
    }
    
    /**
     * 診断結果をHTMLに変換
     */
    private function generate_diagnostic_html($results) {
        ob_start();
        ?>
        
        <!-- 1. カスタム投稿タイプ設定 -->
        <div class="diagnostic-section <?php echo $results['post_type_config']['status']; ?>">
            <h3>📋 1. カスタム投稿タイプ設定</h3>
            <?php if (!empty($results['post_type_config']['issues'])): ?>
                <?php foreach ($results['post_type_config']['issues'] as $issue): ?>
                    <div class="diagnostic-item <?php echo $results['post_type_config']['status']; ?>">
                        <span class="issue-<?php echo $results['post_type_config']['status']; ?>">⚠️ 問題:</span> <?php echo esc_html($issue); ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="diagnostic-item ok">
                    <span class="issue-ok">✅ 問題なし</span> カスタム投稿タイプの設定は最適化されています
                </div>
            <?php endif; ?>
            
            <?php if (!empty($results['post_type_config']['recommendations'])): ?>
                <h4>💡 推奨される対策:</h4>
                <?php foreach ($results['post_type_config']['recommendations'] as $rec): ?>
                    <div style="margin: 15px 0;">
                        <strong style="color: <?php echo $rec['severity'] === 'critical' ? '#dc3232' : '#f0b849'; ?>;">
                            <?php echo esc_html($rec['title']); ?>
                        </strong>
                        <?php if (isset($rec['description'])): ?>
                            <p><?php echo esc_html($rec['description']); ?></p>
                        <?php endif; ?>
                        <?php if (isset($rec['code'])): ?>
                            <div class="code-block"><?php echo esc_html($rec['code']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- 2. データベース統計 -->
        <div class="diagnostic-section <?php echo $results['database_stats']['status']; ?>">
            <h3>💾 2. データベース統計</h3>
            
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <th>総投稿数</th>
                        <td><?php echo number_format($results['database_stats']['stats']['total_posts']); ?> 件</td>
                    </tr>
                    <tr>
                        <th>カスタムフィールド総数</th>
                        <td><?php echo number_format($results['database_stats']['stats']['postmeta_rows']); ?> 件</td>
                    </tr>
                    <tr>
                        <th>1投稿あたりの平均カスタムフィールド数</th>
                        <td>
                            <?php echo $results['database_stats']['stats']['avg_meta_per_post']; ?> 個
                            <?php if ($results['database_stats']['stats']['avg_meta_per_post'] > 30): ?>
                                <span class="issue-critical"> (⚠️ 多すぎます!)</span>
                            <?php elseif ($results['database_stats']['stats']['avg_meta_per_post'] > 20): ?>
                                <span class="issue-warning"> (注意が必要)</span>
                            <?php else: ?>
                                <span class="issue-ok"> (✅ 適切)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>リビジョン数</th>
                        <td>
                            <?php echo number_format($results['database_stats']['stats']['revisions']); ?> 件
                            <?php if ($results['database_stats']['stats']['revisions'] > 1000): ?>
                                <span class="issue-critical"> (⚠️ 削除推奨!)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>自動下書き</th>
                        <td><?php echo number_format($results['database_stats']['stats']['auto_drafts']); ?> 件</td>
                    </tr>
                </tbody>
            </table>
            
            <?php if (!empty($results['database_stats']['issues'])): ?>
                <h4 style="margin-top: 20px;">⚠️ 検出された問題:</h4>
                <?php foreach ($results['database_stats']['issues'] as $issue): ?>
                    <div class="diagnostic-item <?php echo $results['database_stats']['status']; ?>">
                        <?php echo esc_html($issue); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($results['database_stats']['recommendations'])): ?>
                <h4>💡 推奨される対策:</h4>
                <?php foreach ($results['database_stats']['recommendations'] as $rec): ?>
                    <div style="margin: 15px 0;">
                        <strong style="color: <?php echo $rec['severity'] === 'critical' ? '#dc3232' : '#f0b849'; ?>;">
                            <?php echo esc_html($rec['title']); ?>
                        </strong>
                        <?php if (isset($rec['description'])): ?>
                            <p><?php echo esc_html($rec['description']); ?></p>
                        <?php endif; ?>
                        <?php if (isset($rec['code'])): ?>
                            <div class="code-block"><?php echo esc_html($rec['code']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- 3. クエリパフォーマンス -->
        <div class="diagnostic-section <?php echo $results['query_performance']['status']; ?>">
            <h3>⚡ 3. クエリパフォーマンス</h3>
            
            <table class="widefat striped">
                <tbody>
                    <tr>
                        <th>基本クエリ実行時間</th>
                        <td>
                            <?php echo $results['query_performance']['query_time']; ?> ms
                            <?php if ($results['query_performance']['query_time'] > 500): ?>
                                <span class="issue-critical"> (⚠️ 遅すぎます!)</span>
                            <?php elseif ($results['query_performance']['query_time'] > 200): ?>
                                <span class="issue-warning"> (改善の余地あり)</span>
                            <?php else: ?>
                                <span class="issue-ok"> (✅ 高速)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>meta_query 実行時間</th>
                        <td>
                            <?php echo $results['query_performance']['meta_query_time']; ?> ms
                            <?php if ($results['query_performance']['meta_query_time'] > 1000): ?>
                                <span class="issue-critical"> (⚠️ 非常に遅い!)</span>
                            <?php elseif ($results['query_performance']['meta_query_time'] > 500): ?>
                                <span class="issue-warning"> (注意が必要)</span>
                            <?php else: ?>
                                <span class="issue-ok"> (✅ 許容範囲)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            
            <?php if (!empty($results['query_performance']['recommendations'])): ?>
                <h4>💡 推奨される対策:</h4>
                <?php foreach ($results['query_performance']['recommendations'] as $rec): ?>
                    <div style="margin: 15px 0;">
                        <strong style="color: <?php echo $rec['severity'] === 'critical' ? '#dc3232' : '#f0b849'; ?>;">
                            <?php echo esc_html($rec['title']); ?>
                        </strong>
                        <?php if (isset($rec['description'])): ?>
                            <p><?php echo esc_html($rec['description']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- 4. カスタムフィールド分析 -->
        <div class="diagnostic-section">
            <h3>🔑 4. カスタムフィールド分析</h3>
            
            <h4>よく使われるメタキー TOP 10:</h4>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>メタキー</th>
                        <th>使用回数</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['meta_fields']['top_keys'] as $key): ?>
                        <tr>
                            <td><code><?php echo esc_html($key['meta_key']); ?></code></td>
                            <td><?php echo number_format($key['count']); ?> 件</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (!empty($results['meta_fields']['recommendations'])): ?>
                <h4 style="margin-top: 20px;">💡 推奨される対策:</h4>
                <?php foreach ($results['meta_fields']['recommendations'] as $rec): ?>
                    <div style="margin: 15px 0;">
                        <strong><?php echo esc_html($rec['title']); ?></strong>
                        <?php if (isset($rec['description'])): ?>
                            <p><?php echo esc_html($rec['description']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- 5. リビジョン分析 -->
        <?php if (!empty($results['revisions']['top_revisions'])): ?>
        <div class="diagnostic-section <?php echo $results['revisions']['status']; ?>">
            <h3>📝 5. リビジョン分析</h3>
            
            <h4>リビジョンが多い投稿 TOP 5:</h4>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>投稿タイトル</th>
                        <th>投稿ID</th>
                        <th>リビジョン数</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results['revisions']['top_revisions'] as $post): ?>
                        <tr>
                            <td><?php echo esc_html($post['post_title']); ?></td>
                            <td><?php echo $post['ID']; ?></td>
                            <td>
                                <?php echo $post['revision_count']; ?> 件
                                <?php if ($post['revision_count'] > 50): ?>
                                    <span class="issue-critical"> (⚠️ 多すぎ!)</span>
                                <?php elseif ($post['revision_count'] > 20): ?>
                                    <span class="issue-warning"> (注意)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (!empty($results['revisions']['recommendations'])): ?>
                <h4 style="margin-top: 20px;">💡 推奨される対策:</h4>
                <?php foreach ($results['revisions']['recommendations'] as $rec): ?>
                    <div style="margin: 15px 0;">
                        <strong style="color: <?php echo $rec['severity'] === 'critical' ? '#dc3232' : '#f0b849'; ?>;">
                            <?php echo esc_html($rec['title']); ?>
                        </strong>
                        <?php if (isset($rec['code'])): ?>
                            <div class="code-block"><?php echo esc_html($rec['code']); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <!-- 総合評価 -->
        <div class="diagnostic-section" style="background: #f0f0f1; border: 2px solid #0073aa;">
            <h3>🎯 総合評価</h3>
            <?php
            $critical_count = 0;
            $warning_count = 0;
            foreach ($results as $section) {
                if (isset($section['status'])) {
                    if ($section['status'] === 'critical') $critical_count++;
                    if ($section['status'] === 'warning') $warning_count++;
                }
            }
            ?>
            
            <?php if ($critical_count === 0 && $warning_count === 0): ?>
                <p class="issue-ok" style="font-size: 16px;">
                    ✅ <strong>素晴らしい！</strong> 重大な問題は検出されませんでした。
                </p>
            <?php elseif ($critical_count > 0): ?>
                <p class="issue-critical" style="font-size: 16px;">
                    ⚠️ <strong>要対応！</strong> <?php echo $critical_count; ?> 件の重大な問題が検出されました。
                </p>
                <p>上記の推奨対策を実施してください。特に「hierarchical」設定とリビジョン削除は即効性があります。</p>
            <?php else: ?>
                <p class="issue-warning" style="font-size: 16px;">
                    ⚡ <?php echo $warning_count; ?> 件の改善可能な項目が見つかりました。
                </p>
                <p>推奨対策を実施することで、さらなるパフォーマンス向上が期待できます。</p>
            <?php endif; ?>
            
            <h4 style="margin-top: 20px;">📚 次のステップ:</h4>
            <ol>
                <li>上記の推奨コードを <code>functions.php</code> または <code>inc/theme-foundation.php</code> に追加</li>
                <li>リビジョン削除プラグイン（WP-Optimize など）をインストールして実行</li>
                <li>不要な自動下書きを削除</li>
                <li>管理画面の「表示オプション」で不要なカラムを非表示に</li>
                <li>変更後、この診断を再実行して改善を確認</li>
            </ol>
        </div>
        
        <?php
        return ob_get_clean();
    }
}

// 初期化
GI_Performance_Diagnostic::get_instance();
