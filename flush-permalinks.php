<?php
/**
 * パーマリンクフラッシュ実行スクリプト
 * 
 * 使用方法:
 * 1. このファイルをテーマディレクトリに配置
 * 2. ブラウザで以下のURLにアクセス:
 *    https://your-site.com/wp-content/themes/your-theme/flush-permalinks.php
 * 3. 「パーマリンクをフラッシュしました」と表示されたら完了
 * 4. このファイルを削除（セキュリティのため）
 */

// WordPress環境を読み込む
require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/wp-load.php';

// 管理者権限チェック
if (!current_user_can('manage_options')) {
    wp_die('権限がありません。管理者としてログインしてください。');
}

// パーマリンクをフラッシュ
flush_rewrite_rules(true);

// オプションをクリア
delete_option('gi_permalinks_flushed_v11_rest_api_fix');

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>パーマリンクフラッシュ完了</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            max-width: 600px;
            margin: 100px auto;
            padding: 20px;
            background: #f0f0f1;
        }
        .success-box {
            background: #fff;
            border-left: 4px solid #46b450;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.13);
        }
        h1 {
            margin-top: 0;
            color: #46b450;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #2271b1;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin-top: 15px;
        }
        .button:hover {
            background: #135e96;
        }
        .warning {
            background: #fcf3cf;
            border-left: 4px solid #f0b849;
            padding: 15px;
            margin-top: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="success-box">
        <h1>✅ パーマリンクフラッシュ完了</h1>
        <p>カスタム投稿タイプ「助成金・補助金」のインポート/エクスポート機能が有効になりました。</p>
        
        <h3>次のステップ:</h3>
        <ol>
            <li>WordPress管理画面にアクセス</li>
            <li>「ツール」→「エクスポート」で「助成金・補助金」が表示されることを確認</li>
            <li>「ツール」→「インポート」で助成金データをインポート可能か確認</li>
        </ol>
        
        <a href="<?php echo admin_url(); ?>" class="button">管理画面に戻る</a>
        <a href="<?php echo admin_url('export.php'); ?>" class="button">エクスポート画面を開く</a>
    </div>
    
    <div class="warning">
        <strong>⚠️ セキュリティ警告:</strong><br>
        このファイル（<code>flush-permalinks.php</code>）は削除してください。放置するとセキュリティリスクになります。
    </div>
</body>
</html>
