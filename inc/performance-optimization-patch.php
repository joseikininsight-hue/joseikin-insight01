<?php
/**
 * WordPress Performance Optimization Patch
 * カスタム投稿タイプのパフォーマンス問題を修正する最適化パッチ
 * 
 * 使用方法:
 * 1. このファイルをテーマの inc/ ディレクトリに配置
 * 2. functions.php に以下を追加:
 *    require_once get_template_directory() . '/inc/performance-optimization-patch.php';
 * 3. 診断ツールで改善を確認
 * 
 * @package Grant_Insight_Perfect
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class GI_Performance_Optimization_Patch {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // 1. リビジョン制限
        $this->limit_revisions();
        
        // 2. 自動保存間隔の延長
        $this->extend_autosave_interval();
        
        // 3. クエリ最適化
        add_action('pre_get_posts', [$this, 'optimize_grant_queries'], 5);
        
        // 4. 管理画面最適化
        if (is_admin()) {
            add_action('admin_init', [$this, 'optimize_admin_screen']);
            add_filter('manage_grant_posts_columns', [$this, 'optimize_admin_columns'], 999);
        }
        
        // 5. データベースクエリの最適化
        add_filter('posts_clauses', [$this, 'optimize_posts_clauses'], 10, 2);
        
        // 6. メタクエリのキャッシュ
        add_action('init', [$this, 'setup_meta_query_cache']);
        
        // 7. 不要なクエリを削減
        add_action('wp', [$this, 'disable_unnecessary_queries']);
        
        // 8. 管理画面のメモリ最適化
        if (is_admin()) {
            add_action('admin_init', [$this, 'optimize_admin_memory']);
        }
    }
    
    /**
     * 1. リビジョン制限
     */
    private function limit_revisions() {
        // 既に定義されていなければリビジョンを制限
        if (!defined('WP_POST_REVISIONS')) {
            define('WP_POST_REVISIONS', 3); // 最新3件のみ保存
        }
    }
    
    /**
     * 2. 自動保存間隔の延長
     */
    private function extend_autosave_interval() {
        if (!defined('AUTOSAVE_INTERVAL')) {
            define('AUTOSAVE_INTERVAL', 300); // 5分（デフォルトは60秒）
        }
    }
    
    /**
     * 3. Grant クエリの最適化
     * 
     * 注意: メインクエリのみを最適化し、管理画面は除外
     */
    public function optimize_grant_queries($query) {
        // 管理画面では最適化しない（投稿一覧が表示されなくなる問題を防ぐ）
        if (is_admin()) {
            return;
        }
        
        // メインクエリでない場合はスキップ
        if (!$query->is_main_query()) {
            return;
        }
        
        // Grant アーカイブの場合のみ最適化
        if (!is_post_type_archive('grant') && !is_tax(['grant_category', 'grant_prefecture', 'grant_municipality', 'grant_purpose', 'grant_tag'])) {
            return;
        }
        
        // 不要なキャッシュを無効化してパフォーマンス向上
        $query->set('no_found_rows', false); // ページネーションに必要なので true にはしない
        $query->set('update_post_meta_cache', true); // メタデータは必要
        $query->set('update_post_term_cache', true); // タクソノミーは必要
        
        // ORDER BY の最適化（インデックスを活用）
        // 既存の orderby がない場合のみ設定
        if (!$query->get('orderby')) {
            $query->set('orderby', 'date');
            $query->set('order', 'DESC');
        }
    }
    
    /**
     * 4. 管理画面の最適化
     */
    public function optimize_admin_screen() {
        $screen = get_current_screen();
        
        if (!$screen || $screen->post_type !== 'grant') {
            return;
        }
        
        // 一覧画面の場合
        if ($screen->base === 'edit') {
            // 1ページあたりの表示件数を制限（デフォルト20件）
            add_filter('edit_posts_per_page', function($per_page, $post_type) {
                if ($post_type === 'grant') {
                    return 20; // 多すぎると重くなる
                }
                return $per_page;
            }, 10, 2);
            
            // 月別フィルターを無効化（投稿数が多い場合に重い）
            add_filter('months_dropdown_results', '__return_empty_array');
            
            // ビュー（公開済み、下書きなど）のカウントをキャッシュ
            add_filter('wp_count_posts', [$this, 'cache_post_counts'], 10, 3);
        }
    }
    
    /**
     * 管理画面カラムの最適化
     */
    public function optimize_admin_columns($columns) {
        // 基本的なカラムのみに制限
        $optimized_columns = [
            'cb' => $columns['cb'], // チェックボックス
            'title' => $columns['title'], // タイトル
            'taxonomy-grant_category' => 'カテゴリー',
            'taxonomy-grant_prefecture' => '都道府県',
            'date' => $columns['date'], // 日付
        ];
        
        return $optimized_columns;
    }
    
    /**
     * 投稿数カウントのキャッシュ
     */
    public function cache_post_counts($counts, $type, $perm) {
        if ($type !== 'grant') {
            return $counts;
        }
        
        $cache_key = "grant_post_counts_{$perm}";
        $cached_counts = wp_cache_get($cache_key, 'counts');
        
        if ($cached_counts !== false) {
            return $cached_counts;
        }
        
        // 5分間キャッシュ
        wp_cache_set($cache_key, $counts, 'counts', 300);
        
        return $counts;
    }
    
    /**
     * 5. posts_clauses の最適化
     */
    public function optimize_posts_clauses($clauses, $query) {
        global $wpdb;
        
        // 管理画面では最適化をスキップ（投稿一覧が表示されなくなる問題を防ぐ）
        if (is_admin()) {
            return $clauses;
        }
        
        // Grant クエリのみ対象
        if ($query->get('post_type') !== 'grant' && !in_array('grant', (array)$query->get('post_type'))) {
            return $clauses;
        }
        
        // メインクエリでない場合はスキップ
        if (!$query->is_main_query()) {
            return $clauses;
        }
        
        // DISTINCT を削除（不要な場合が多い）
        if (isset($clauses['distinct']) && strpos($clauses['distinct'], 'DISTINCT') !== false && !$query->get('meta_query')) {
            $clauses['distinct'] = '';
        }
        
        // ORDER BY の最適化（既存の orderby がある場合は変更しない）
        if (empty($query->get('meta_key')) && empty($query->get('meta_query')) && empty($query->get('orderby'))) {
            // メタデータでソートしない場合はインデックスを活用
            $clauses['orderby'] = "ORDER BY {$wpdb->posts}.post_date DESC";
        }
        
        return $clauses;
    }
    
    /**
     * 6. メタクエリのキャッシュ設定
     */
    public function setup_meta_query_cache() {
        // よく使われるメタクエリの結果をキャッシュ
        add_filter('get_post_metadata', [$this, 'cache_frequently_used_meta'], 10, 4);
    }
    
    /**
     * 頻繁に使用されるメタデータのキャッシュ
     */
    public function cache_frequently_used_meta($value, $object_id, $meta_key, $single) {
        // 特定のメタキーのみキャッシュ（カスタマイズ可能）
        $cacheable_keys = [
            'grant_amount',
            'grant_deadline',
            'grant_status',
            'grant_organization',
        ];
        
        if (!in_array($meta_key, $cacheable_keys)) {
            return $value;
        }
        
        $cache_key = "post_meta_{$object_id}_{$meta_key}";
        $cached_value = wp_cache_get($cache_key, 'post_meta');
        
        if ($cached_value !== false) {
            return $single ? [$cached_value] : $cached_value;
        }
        
        // デフォルトの処理に任せる（その後キャッシュされる）
        return $value;
    }
    
    /**
     * 7. 不要なクエリを削減
     */
    public function disable_unnecessary_queries() {
        // フロントエンドで絵文字スクリプトを無効化
        remove_action('wp_head', 'print_emoji_detection_script', 7);
        remove_action('wp_print_styles', 'print_emoji_styles');
        remove_action('admin_print_scripts', 'print_emoji_detection_script');
        remove_action('admin_print_styles', 'print_emoji_styles');
        
        // Feed リンクを削除（使用していない場合）
        remove_action('wp_head', 'feed_links', 2);
        remove_action('wp_head', 'feed_links_extra', 3);
        
        // REST API へのリンクを削除（フロントエンドで不要な場合）
        // 注意: REST API を使用している場合はコメントアウト
        // remove_action('wp_head', 'rest_output_link_wp_head');
        // remove_action('wp_head', 'wp_oembed_add_discovery_links');
    }
    
    /**
     * 8. 管理画面のメモリ最適化
     */
    public function optimize_admin_memory() {
        // 管理画面でのメモリ使用量を監視
        $current_memory = memory_get_usage(true);
        $memory_limit = $this->return_bytes(ini_get('memory_limit'));
        
        // メモリ使用率が80%を超えたら警告
        if ($current_memory / $memory_limit > 0.8) {
            add_action('admin_notices', function() use ($current_memory, $memory_limit) {
                $usage_percent = round(($current_memory / $memory_limit) * 100);
                ?>
                <div class="notice notice-warning">
                    <p>
                        <strong>⚠️ メモリ使用率が高くなっています:</strong> 
                        <?php echo $usage_percent; ?>% (<?php echo $this->format_bytes($current_memory); ?> / <?php echo $this->format_bytes($memory_limit); ?>)
                        <br>
                        パフォーマンス診断ツールを実行して最適化を検討してください。
                    </p>
                </div>
                <?php
            });
        }
    }
    
    /**
     * バイト数変換ヘルパー
     */
    private function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int)$val;
        
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }
    
    /**
     * バイト数フォーマットヘルパー
     */
    private function format_bytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

// 初期化
GI_Performance_Optimization_Patch::get_instance();

/**
 * =============================================================================
 * 追加の最適化関数（必要に応じて使用）
 * =============================================================================
 */

/**
 * リビジョンを一括削除する関数（管理画面から手動実行）
 * 
 * 使用方法:
 * 1. functions.php でこの関数を有効化
 * 2. WordPress管理画面で任意のページにアクセス
 * 3. URL に ?gi_delete_revisions=1 を追加してアクセス
 * 4. リビジョンが削除されます
 */
function gi_delete_all_revisions() {
    if (!current_user_can('manage_options')) {
        wp_die('権限がありません');
    }
    
    global $wpdb;
    
    // Grant のリビジョンを削除
    $deleted = $wpdb->query("
        DELETE FROM {$wpdb->posts} 
        WHERE post_type = 'revision' 
        AND post_parent IN (
            SELECT ID FROM (
                SELECT ID FROM {$wpdb->posts} WHERE post_type = 'grant'
            ) AS tmp
        )
    ");
    
    // 孤児メタデータを削除
    $wpdb->query("
        DELETE pm FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE p.ID IS NULL
    ");
    
    wp_die("削除完了: {$deleted} 件のリビジョンを削除しました。<br><a href='" . admin_url() . "'>管理画面に戻る</a>");
}

// URL パラメータで実行（本番環境では削除推奨）
if (isset($_GET['gi_delete_revisions']) && $_GET['gi_delete_revisions'] == '1') {
    add_action('admin_init', 'gi_delete_all_revisions');
}

/**
 * 自動下書きを一括削除する関数
 * 
 * 使用方法: URL に ?gi_delete_autodrafts=1 を追加
 */
function gi_delete_auto_drafts() {
    if (!current_user_can('manage_options')) {
        wp_die('権限がありません');
    }
    
    global $wpdb;
    
    $deleted = $wpdb->query("
        DELETE FROM {$wpdb->posts} 
        WHERE post_type = 'grant' 
        AND post_status = 'auto-draft'
        AND DATE(post_date) < DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    
    wp_die("削除完了: {$deleted} 件の自動下書きを削除しました。<br><a href='" . admin_url() . "'>管理画面に戻る</a>");
}

// URL パラメータで実行（本番環境では削除推奨）
if (isset($_GET['gi_delete_autodrafts']) && $_GET['gi_delete_autodrafts'] == '1') {
    add_action('admin_init', 'gi_delete_auto_drafts');
}

/**
 * postmeta テーブルにインデックスを追加するSQL
 * 
 * 実行方法:
 * phpMyAdmin または MySQL クライアントで以下のSQLを実行してください
 * 
 * ALTER TABLE wp_postmeta ADD INDEX meta_key_index (meta_key(191));
 * ALTER TABLE wp_postmeta ADD INDEX meta_value_index (meta_value(191));
 * 
 * 注意: 既にインデックスが存在する場合はエラーになります
 */

/**
 * カスタム投稿タイプの hierarchical を修正する関数
 * 
 * この関数は theme-foundation.php の register_post_type に統合するか、
 * 以下のように上書きフィルターとして使用できます
 */
add_filter('register_post_type_args', 'gi_fix_grant_hierarchical', 10, 2);
function gi_fix_grant_hierarchical($args, $post_type) {
    if ($post_type === 'grant') {
        // 階層構造を無効化（パフォーマンス改善）
        $args['hierarchical'] = false;
        
        // REST API を確実に有効化
        $args['show_in_rest'] = true;
        $args['rest_base'] = 'grants';
        
        // インポート/エクスポート機能を有効化（重要！）
        $args['can_export'] = true;
        
        // クエリパフォーマンス向上のため、不要なサポート機能を削除
        if (isset($args['supports'])) {
            // リビジョンを削除する場合（推奨しない場合はコメントアウト）
            // $args['supports'] = array_diff($args['supports'], ['revisions']);
        }
    }
    
    return $args;
}

/**
 * WP_Query のデフォルト引数を最適化
 */
add_action('pre_get_posts', 'gi_optimize_default_query_args', 1);
function gi_optimize_default_query_args($query) {
    // 管理画面のメインクエリは除外
    if (is_admin()) {
        return;
    }
    
    // Grant 関連クエリのみ
    if ($query->get('post_type') === 'grant' || 
        (is_array($query->get('post_type')) && in_array('grant', $query->get('post_type')))) {
        
        // 検索クエリの場合は LIKE を最適化
        if ($query->is_search()) {
            add_filter('posts_search', 'gi_optimize_search_query', 10, 2);
        }
    }
}

/**
 * 検索クエリの最適化
 */
function gi_optimize_search_query($search, $query) {
    global $wpdb;
    
    if (empty($search)) {
        return $search;
    }
    
    // LIKE クエリを部分一致から前方一致に変更（インデックスを活用）
    // 注意: これにより検索精度は下がりますが、パフォーマンスは大幅に向上します
    // $search = str_replace("LIKE '%", "LIKE '", $search);
    
    return $search;
}
