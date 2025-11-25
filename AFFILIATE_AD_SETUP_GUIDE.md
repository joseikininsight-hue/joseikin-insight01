# アフィリエイト広告設定ガイド

## 📋 概要
single-grant.phpに3つの広告位置を追加しました。このガイドでは、WordPress管理画面から広告を設定する方法を説明します。

## 🎯 追加された広告位置

### 1. **single_grant_sidebar_top**（最上部）
- **表示位置**: サイドバーの最上部（AIアシスタントの上）
- **推奨サイズ**: 300x250px または 336x280px
- **表示タイミング**: ページ表示時すぐに見える位置（ファーストビュー）
- **特徴**: ユーザーが最初に目にする広告枠

### 2. **single_grant_sidebar_middle**（中部）
- **表示位置**: おすすめ補助金セクションと目次の間
- **推奨サイズ**: 300x250px
- **表示タイミング**: スクロール後に表示

### 3. **single_grant_sidebar_bottom**（下部）
- **表示位置**: 関連コラムセクションの後
- **推奨サイズ**: 300x250px または 300x600px
- **表示タイミング**: ページ下部まで閲覧した際

## 🔧 WordPress管理画面での設定手順

### ステップ1: 広告管理画面にアクセス
1. WordPress管理画面にログイン
2. 左側メニューから「**アフィリエイト広告**」をクリック
3. 「**広告一覧**」ページが表示されます

### ステップ2: 新規広告を作成
1. 「**新規広告を作成**」ボタンをクリック
2. 以下の項目を入力：

#### 基本情報
- **タイトル**: わかりやすい広告名（例：「補助金詳細ページ上部_A社広告」）
- **広告タイプ**: 
  - `html`: HTMLコードを直接貼り付け
  - `image`: 画像URLとリンクURLを設定
  - `script`: JavaScriptタグを貼り付け

#### 広告内容
- **コンテンツ**: 広告のHTMLコードまたは画像タグ
- **リンクURL**: クリック時の遷移先URL（image タイプの場合）

#### 配信設定
- **表示位置**: 複数選択可能
  - ☑ `single_grant_sidebar_top` （上部）
  - ☑ `single_grant_sidebar_middle` （中部）
  - ☑ `single_grant_sidebar_bottom` （下部）

- **対象ページ**: 
  - ☑ `single-grant` （補助金詳細ページ）

- **対象カテゴリー**: オプション
  - 特定カテゴリーのみに表示したい場合に選択
  - 例：「経営革新」「IT導入」「環境対策」など

- **デバイス**: 
  - `all`: すべてのデバイス（デフォルト）
  - `desktop`: PCのみ
  - `mobile`: スマホのみ

#### スケジュール設定（オプション）
- **開始日**: 広告配信開始日時
- **終了日**: 広告配信終了日時
- **優先度**: 数値が大きいほど優先的に表示（デフォルト: 0）

#### ステータス
- **status**: 
  - `active`: 配信中
  - `inactive`: 停止中
  - `upcoming`: 配信予定

### ステップ3: 広告の保存
1. すべての項目を入力したら「**保存**」をクリック
2. 広告一覧ページで広告が作成されたことを確認

### ステップ4: 表示確認
1. フロントエンドで補助金詳細ページを開く
2. サイドバーの指定位置に広告が表示されることを確認
3. 表示されない場合は以下を確認：
   - ステータスが `active` になっているか
   - 対象ページに `single-grant` が含まれているか
   - 表示位置が正しく選択されているか
   - 開始日・終了日が適切に設定されているか

## 📊 統計情報の確認

### アクセス方法
1. WordPress管理画面
2. 「アフィリエイト広告」→「**統計情報**」

### 確認できる指標
- **インプレッション数**: 広告が表示された回数
- **クリック数**: 広告がクリックされた回数
- **CTR（クリック率）**: (クリック数 / インプレッション数) × 100
- **日別統計**: 日付ごとの推移グラフ
- **詳細統計**: ページURL、カテゴリー、デバイス別の内訳

### 期間フィルター
- 過去7日間
- 過去30日間
- 過去90日間
- 過去365日間

## 🎨 広告デザインのベストプラクティス

### サイズ推奨
- **上部・中部**: 300x250px（レクタングル中）
- **下部**: 300x600px（ハーフページ）または 300x250px

### デザインガイドライン
1. **サイトデザインとの調和**: 白黒ベース + 黄金色アクセント
2. **視認性**: 背景と広告の境界を明確に
3. **CTAボタン**: 黄金色（#FFD700）を使用すると効果的
4. **テキスト**: 読みやすいフォントサイズ（12px以上）

### HTMLサンプル

#### シンプルなバナー広告
```html
<div style="background: #FFFFFF; border: 2px solid #E5E5E5; padding: 20px; text-align: center;">
    <a href="https://example.com" target="_blank" rel="noopener noreferrer" style="text-decoration: none;">
        <img src="https://example.com/banner.jpg" alt="広告" style="max-width: 100%; height: auto;">
        <div style="margin-top: 10px; padding: 10px 20px; background: #FFD700; color: #000; font-weight: bold; display: inline-block;">
            詳しく見る →
        </div>
    </a>
</div>
```

#### テキスト広告
```html
<div style="background: linear-gradient(135deg, #1a1a1a 0%, #000000 100%); border: 2px solid #FFD700; padding: 20px; color: #FFFFFF;">
    <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #FFD700;">補助金申請サポート</h3>
    <p style="margin: 0 0 15px 0; font-size: 14px; line-height: 1.6;">
        専門家が補助金申請を完全サポート。採択率95%の実績。
    </p>
    <a href="https://example.com" target="_blank" rel="noopener noreferrer" 
       style="display: inline-block; background: #FFD700; color: #000; padding: 10px 20px; 
              text-decoration: none; font-weight: bold; border-radius: 0;">
        無料相談はこちら
    </a>
</div>
```

## 🔍 トラブルシューティング

### 広告が表示されない場合

#### 1. ステータス確認
- 広告一覧で該当広告のステータスが `active` か確認

#### 2. 配信設定確認
- **表示位置**: 正しい位置が選択されているか
- **対象ページ**: `single-grant` が含まれているか
- **デバイス**: 現在のデバイスが対象か

#### 3. スケジュール確認
- 開始日が未来の日付になっていないか
- 終了日が過去の日付になっていないか

#### 4. ブラウザキャッシュ
- ブラウザのキャッシュをクリア
- シークレットモードで確認

#### 5. デバッグログ確認
```php
// functions.php に追加してデバッグ
add_action('wp_footer', function() {
    if (is_singular('grant')) {
        error_log('Current post ID: ' . get_the_ID());
        $categories = wp_get_post_terms(get_the_ID(), 'grant_category');
        error_log('Categories: ' . print_r($categories, true));
    }
});
```

### 広告が重複表示される場合
- 同じ位置に複数の広告が `active` になっていないか確認
- 優先度を調整して表示順序を制御

### クリック統計が記録されない場合
- ブラウザのJavaScriptが有効か確認
- 広告リンクに `ji-ad-link` クラスが付与されているか確認

## 📈 A/Bテスト実施方法

### 同一位置に複数広告を設定
1. 同じ表示位置に複数の広告を作成
2. すべて `active` に設定
3. 優先度を同じ値に設定（ランダム表示）
4. 統計情報で効果を比較

### 推奨テスト期間
- 最低2週間（十分なデータ収集のため）
- インプレッション数が100以上になるまで

### 評価指標
- **CTR（クリック率）**: 最も重要
- **インプレッション数**: 表示回数
- **カテゴリー別効果**: どのカテゴリーで効果が高いか

## 🎯 カテゴリー別広告配信の活用例

### 例1: IT導入補助金専用の広告
```
タイトル: IT導入補助金_クラウドサービス広告
表示位置: single_grant_sidebar_top
対象カテゴリー: grant_category_5（IT導入補助金）
優先度: 10
```

### 例2: 環境対策補助金専用の広告
```
タイトル: 環境対策補助金_太陽光発電広告
表示位置: single_grant_sidebar_middle
対象カテゴリー: grant_category_8（環境対策）
優先度: 10
```

### 例3: 全カテゴリー共通の広告
```
タイトル: 汎用_補助金申請サポート広告
表示位置: single_grant_sidebar_bottom
対象カテゴリー: （未選択 = 全カテゴリー）
優先度: 5
```

## 📞 サポート

広告設定に関する質問や問題がある場合は、以下を確認してください：

1. **コード実装**: `/inc/affiliate-ad-manager.php`
2. **フロントエンド**: `single-grant.php` 行3241-3376
3. **データベーステーブル**: 
   - `wp_ji_affiliate_ads`: 広告データ
   - `wp_ji_affiliate_stats`: 集計統計
   - `wp_ji_affiliate_stats_detail`: 詳細統計

## 🔄 更新履歴

- **2024-11-16 v2**: 広告位置の最適化
  - **single_grant_sidebar_top** をサイドバーの最上部に移動
  - AIアシスタントより上に配置（ファーストビュー最適化）
  - 広告視認性の向上

- **2024-11-16 v1**: 初版作成
  - 3つの広告位置を追加
  - カテゴリー別配信機能を実装
  - 詳細統計機能を実装
