# 📊 WordPressカスタム投稿タイプ パフォーマンス最適化ガイド

## 🎯 概要

このガイドでは、WordPressのカスタム投稿タイプ（特に「助成金・補助金」）のパフォーマンス問題を診断・改善する方法を説明します。

**重くなる主な原因:**
1. ✅ **hierarchical（階層構造）** が true になっている
2. ✅ **カスタムフィールド（postmeta）** の酷使
3. ✅ **リビジョン** の蓄積
4. ✅ **管理画面の一覧カラム** が多すぎる
5. ✅ **meta_query** を使った複雑な検索

---

## 🚀 クイックスタート

### ステップ 1: 診断ツールのインストール

1. `functions.php` に以下を追加:

```php
// パフォーマンス診断ツール
require_once get_template_directory() . '/inc/performance-diagnostic.php';
```

2. WordPress管理画面で **「ツール」→「パフォーマンス診断」** にアクセス

3. **「🚀 診断を実行」** ボタンをクリック

### ステップ 2: 最適化パッチの適用

診断結果を確認後、以下を `functions.php` に追加:

```php
// パフォーマンス最適化パッチ
require_once get_template_directory() . '/inc/performance-optimization-patch.php';
```

### ステップ 3: カスタム投稿タイプ設定の修正

`inc/theme-foundation.php` の `register_post_type('grant', ...)` を確認し、以下を修正:

```php
register_post_type('grant', array(
    // ⚠️ 最重要: 階層構造を無効化
    'hierarchical' => false, // ← true の場合は false に変更！
    
    // REST API を有効化（Gutenberg エディタに必要）
    'show_in_rest' => true,
    'rest_base' => 'grants',
    'rest_controller_class' => 'WP_REST_Posts_Controller',
    
    // その他の設定...
));
```

---

## 📋 診断結果の読み方

診断ツールを実行すると、以下のセクションで問題を報告します:

### 1. カスタム投稿タイプ設定

**チェック項目:**
- ✅ `hierarchical` が `false` であること
- ✅ `show_in_rest` が `true` であること
- ✅ リビジョン機能の設定

**深刻度:**
- 🔴 **CRITICAL**: `hierarchical => true` の場合（最優先で修正）
- 🟡 **WARNING**: REST API が無効の場合

### 2. データベース統計

**チェック項目:**
- 総投稿数
- カスタムフィールド総数
- 1投稿あたりの平均カスタムフィールド数
- リビジョン数
- 自動下書き数

**基準値:**
- ✅ **良好**: 平均カスタムフィールド < 20個、リビジョン < 500件
- 🟡 **注意**: 平均カスタムフィールド 20-30個、リビジョン 500-1000件
- 🔴 **要対応**: 平均カスタムフィールド > 30個、リビジョン > 1000件

### 3. クエリパフォーマンス

**測定内容:**
- 基本クエリ実行時間
- meta_query 実行時間

**基準値:**
- ✅ **高速**: < 200ms
- 🟡 **改善の余地**: 200-500ms
- 🔴 **遅い**: > 500ms

### 4. カスタムフィールド分析

よく使われるメタキー TOP 10 を表示し、ACF（Advanced Custom Fields）の使用状況を分析します。

### 5. リビジョン分析

リビジョンが多い投稿 TOP 5 を表示します。

---

## 🔧 具体的な対策

### 対策 1: hierarchical を無効化（最優先）

**問題:**
`hierarchical => true` の場合、投稿数が増えると URL 生成や管理画面表示で全投稿の親子関係を解析するため、劇的に遅くなります。

**解決方法:**

`inc/theme-foundation.php` の 300行目付近を修正:

```php
register_post_type('grant', array(
    // ... その他の設定 ...
    'hierarchical' => false, // ← ここを false に変更
    // ... その他の設定 ...
));
```

**変更後:**
```bash
# 管理画面で「設定」→「パーマリンク設定」を開いて「変更を保存」をクリック
# （リライトルールをフラッシュ）
```

---

### 対策 2: リビジョンの削除と制限

#### 2-1. リビジョン数を制限

`functions.php` の冒頭に追加:

```php
// リビジョンを最新3件のみ保存
if (!defined('WP_POST_REVISIONS')) {
    define('WP_POST_REVISIONS', 3);
}

// または完全に無効化（非推奨）
// define('WP_POST_REVISIONS', false);
```

#### 2-2. 既存リビジョンの削除

**方法A: プラグインを使用（推奨）**

1. **WP-Optimize** プラグインをインストール
2. 「WP-Optimize」→「データベース」タブ
3. 「投稿リビジョンをクリーン」にチェック
4. 「最適化を実行」をクリック

**方法B: URL パラメータで削除（テスト環境のみ）**

```
https://your-site.com/wp-admin/?gi_delete_revisions=1
```

注意: 本番環境では使用しないでください。

**方法C: SQL で直接削除（上級者向け）**

phpMyAdmin で以下のSQLを実行:

```sql
-- リビジョンを削除
DELETE FROM wp_posts 
WHERE post_type = 'revision' 
AND post_parent IN (
    SELECT ID FROM (
        SELECT ID FROM wp_posts WHERE post_type = 'grant'
    ) AS tmp
);

-- 孤児メタデータを削除
DELETE pm FROM wp_postmeta pm
LEFT JOIN wp_posts p ON pm.post_id = p.ID
WHERE p.ID IS NULL;
```

---

### 対策 3: 自動下書きの削除

#### URL パラメータで削除（テスト環境のみ）

```
https://your-site.com/wp-admin/?gi_delete_autodrafts=1
```

#### または WP-Optimize で削除

1. 「WP-Optimize」→「データベース」タブ
2. 「自動下書き投稿を削除」にチェック
3. 「最適化を実行」をクリック

---

### 対策 4: データベースインデックスの追加

postmeta テーブルに適切なインデックスを追加することで、カスタムフィールド検索が高速化されます。

**実行方法:**

phpMyAdmin または MySQL クライアントで以下のSQLを実行:

```sql
-- meta_key にインデックスを追加
ALTER TABLE wp_postmeta ADD INDEX meta_key_index (meta_key(191));

-- meta_value にインデックスを追加（検索で使用する場合）
ALTER TABLE wp_postmeta ADD INDEX meta_value_index (meta_value(191));
```

**注意:** 既にインデックスが存在する場合はエラーになります。その場合は無視してください。

**インデックス確認方法:**

```sql
SHOW INDEX FROM wp_postmeta;
```

---

### 対策 5: 管理画面の最適化

#### 5-1. 一覧画面の表示項目を削減

1. 管理画面で「助成金・補助金」の一覧ページを開く
2. 画面右上の「表示オプション」をクリック
3. 不要なカラムのチェックを外す
4. 「1ページに表示する項目数」を 20 以下に設定

#### 5-2. Admin Columns プラグインの設定見直し

Admin Columns を使用している場合:

1. 「設定」→「Admin Columns」
2. Grant 投稿タイプの設定を開く
3. 不要なカラムを削除または無効化
4. カスタムフィールドのカラムを最小限に

---

### 対策 6: カスタムフィールドの使用を削減

#### 問題:
カスタムフィールド（postmeta）が多すぎると、検索や一覧表示で大量のデータベースクエリが発生します。

#### 解決方法:

**タクソノミー（カテゴリー・タグ）で置き換える**

カスタムフィールドで管理している項目のうち、以下はタクソノミーで管理することを推奨:

- ✅ **選択式の項目**: 都道府県、市区町村、カテゴリー
- ✅ **検索・絞り込みに使用する項目**: 補助金額範囲、対象者、業種
- ✅ **複数選択可能な項目**: タグ、目的、対象業種

**例: 補助金額範囲をタクソノミーで管理**

```php
// タクソノミー登録（inc/theme-foundation.php に追加）
register_taxonomy('grant_amount_range', 'grant', array(
    'labels' => array(
        'name' => '補助金額範囲',
        'singular_name' => '補助金額範囲',
    ),
    'hierarchical' => false, // タグ形式
    'public' => true,
    'show_in_rest' => true,
));
```

**メリット:**
- 🚀 検索が高速（インデックスが効く）
- 📊 一覧表示が軽量
- 🎯 管理画面での絞り込みが容易

---

### 対策 7: meta_query の使用を避ける

#### 問題:
`meta_query` は非常に重い処理です。

**❌ 遅い例:**

```php
$query = new WP_Query([
    'post_type' => 'grant',
    'meta_query' => [
        [
            'key' => 'grant_amount',
            'value' => 1000000,
            'compare' => '>='
        ]
    ]
]);
```

**✅ 速い例（タクソノミーを使用）:**

```php
$query = new WP_Query([
    'post_type' => 'grant',
    'tax_query' => [
        [
            'taxonomy' => 'grant_amount_range',
            'field' => 'slug',
            'terms' => ['high'] // 100万円以上
        ]
    ]
]);
```

---

### 対策 8: クエリのキャッシュ

頻繁に使用するクエリの結果をキャッシュすることで、データベース負荷を軽減できます。

**例: Transient API を使用**

```php
function gi_get_recent_grants() {
    // キャッシュキー
    $cache_key = 'gi_recent_grants';
    
    // キャッシュから取得を試みる
    $grants = get_transient($cache_key);
    
    if ($grants === false) {
        // キャッシュがない場合はクエリ実行
        $query = new WP_Query([
            'post_type' => 'grant',
            'posts_per_page' => 10,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        
        $grants = $query->posts;
        
        // 5分間キャッシュ
        set_transient($cache_key, $grants, 300);
    }
    
    return $grants;
}
```

**キャッシュをクリアする:**

```php
// 投稿が更新されたらキャッシュをクリア
add_action('save_post_grant', function($post_id) {
    delete_transient('gi_recent_grants');
});
```

---

## 📈 パフォーマンス改善の効果

### 期待できる改善例:

| 対策 | 改善前 | 改善後 | 効果 |
|------|--------|--------|------|
| hierarchical を false に | 5秒 | 0.5秒 | **90%削減** |
| リビジョン削除（10,000件） | 3秒 | 1秒 | **66%削減** |
| meta_query → tax_query | 2秒 | 0.3秒 | **85%削減** |
| 管理画面カラム削減 | 8秒 | 2秒 | **75%削減** |
| データベースインデックス追加 | 1.5秒 | 0.4秒 | **73%削減** |

---

## 🔍 診断ツールの使い方

### 診断の実行

1. WordPress管理画面にログイン
2. 「ツール」→「パフォーマンス診断」をクリック
3. 「🚀 診断を実行」ボタンをクリック
4. 診断結果を確認

### 診断結果の見方

#### 🔴 CRITICAL（緊急対応が必要）
- `hierarchical` が `true` になっている
- リビジョンが1,000件以上
- 平均カスタムフィールドが30個以上
- クエリ実行時間が500ms以上

#### 🟡 WARNING（改善推奨）
- リビジョンが500件以上
- 平均カスタムフィールドが20個以上
- クエリ実行時間が200ms以上

#### ✅ OK（問題なし）
- 上記に該当しない

---

## 🛠️ トラブルシューティング

### Q1: 診断ツールでメモリエラーが出る

**原因:** PHP のメモリ制限が低い

**解決方法:**

`wp-config.php` に以下を追加:

```php
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

### Q2: hierarchical を false にしたら URL が変わった

**原因:** リライトルールがフラッシュされていない

**解決方法:**

1. 管理画面で「設定」→「パーマリンク設定」を開く
2. 何も変更せずに「変更を保存」をクリック
3. ブラウザのキャッシュをクリア

### Q3: リビジョンを削除したらデータが消えた？

**回答:** リビジョンは過去の保存履歴なので、最新の投稿内容は消えません。安心してください。

### Q4: 最適化後も重い

**確認項目:**

1. ✅ キャッシュプラグインを使用していますか？（WP Super Cache, W3 Total Cache など）
2. ✅ 画像最適化をしていますか？（Smush, EWWW Image Optimizer など）
3. ✅ サーバーのスペックは十分ですか？（メモリ、CPU）
4. ✅ 他のプラグインが重い処理をしていませんか？（Query Monitor で確認）

---

## 📚 推奨プラグイン

### パフォーマンス診断・最適化

- **Query Monitor**: データベースクエリの詳細分析
- **WP-Optimize**: リビジョン・自動下書き削除、データベース最適化
- **P3 (Plugin Performance Profiler)**: プラグインのパフォーマンス測定

### キャッシュ

- **WP Super Cache**: 静的HTMLキャッシュ
- **W3 Total Cache**: 総合的なキャッシュソリューション
- **Redis Object Cache**: オブジェクトキャッシュ（Redisサーバーが必要）

### 画像最適化

- **Smush**: 画像圧縮・WebP変換
- **EWWW Image Optimizer**: 自動画像最適化

---

## 📊 定期メンテナンス

### 月次

- ✅ リビジョン数を確認
- ✅ 自動下書きを削除
- ✅ データベース最適化（WP-Optimize）

### 四半期

- ✅ パフォーマンス診断を実行
- ✅ 不要なプラグインを無効化
- ✅ カスタムフィールドの使用状況を見直し

### 年次

- ✅ サーバースペックの見直し
- ✅ PHPバージョンの更新
- ✅ MySQLバージョンの更新

---

## 🎓 さらに学ぶ

### WordPress公式ドキュメント

- [投稿タイプの最適化](https://developer.wordpress.org/reference/functions/register_post_type/)
- [データベース最適化](https://codex.wordpress.org/Database_Optimization)

### パフォーマンス関連記事

- [WordPress高速化の完全ガイド](https://kinsta.com/jp/learn/speed-up-wordpress/)
- [カスタムフィールドの正しい使い方](https://www.wpbeginner.com/plugins/best-practices-for-custom-fields-in-wordpress/)

---

## 📝 まとめ

### 最優先でやるべきこと（3つ）

1. ✅ **hierarchical を false に変更**（最大の効果）
2. ✅ **リビジョンを削除**（即効性あり）
3. ✅ **診断ツールを実行**（現状把握）

### 中期的に取り組むこと

4. ✅ カスタムフィールドをタクソノミーに移行
5. ✅ データベースインデックスを追加
6. ✅ キャッシュプラグインを導入

### 長期的に取り組むこと

7. ✅ meta_query の使用を削減
8. ✅ 定期的なパフォーマンス診断
9. ✅ サーバースペックの見直し

---

## 🤝 サポート

質問や問題がある場合は、以下をご確認ください:

1. このガイドの「トラブルシューティング」セクション
2. 診断ツールの結果と推奨対策
3. WordPress公式フォーラム

---

**バージョン:** 1.0.0  
**最終更新:** 2025-11-28  
**対象テーマ:** Grant Insight Perfect  
**対象投稿タイプ:** grant (助成金・補助金)
