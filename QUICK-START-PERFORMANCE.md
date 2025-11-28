# 🚀 パフォーマンス診断ツール - クイックスタートガイド

## 📋 概要

このツールは、WordPressのカスタム投稿タイプ（助成金・補助金）のパフォーマンス問題を**自動診断**し、**具体的な改善策**を提示します。

---

## ⚡ 3ステップで診断開始

### ステップ 1: セットアップ完了確認 ✅

以下のファイルが既に配置されています:

- ✅ `/inc/performance-diagnostic.php` - 診断ツール本体
- ✅ `/inc/performance-optimization-patch.php` - 自動最適化パッチ
- ✅ `functions.php` - 上記2ファイルを読み込み済み

### ステップ 2: 診断実行 🔍

1. WordPress管理画面にログイン
2. 左メニューの **「ツール」** をクリック
3. **「パフォーマンス診断」** をクリック
4. **「🚀 診断を実行」** ボタンをクリック

### ステップ 3: 結果確認 📊

診断結果が以下のセクションで表示されます:

- 📋 カスタム投稿タイプ設定
- 💾 データベース統計
- ⚡ クエリパフォーマンス
- 🔑 カスタムフィールド分析
- 📝 リビジョン分析

---

## 🎯 よくある問題と即効性のある対策

### 🔴 CRITICAL: hierarchical が true の場合

**症状:** 管理画面が非常に重い、一覧表示に数秒かかる

**対策:**
```php
// inc/theme-foundation.php の 328行目を確認
'hierarchical' => false, // ← 既に false になっています ✅
```

**注意:** 既に `false` に設定されているため、この問題は発生していません。

---

### 🔴 CRITICAL: リビジョンが1000件以上

**症状:** データベースが肥大化、バックアップが遅い

**即効対策（推奨）:**

1. **WP-Optimize** プラグインをインストール
   ```
   管理画面 → プラグイン → 新規追加 → "WP-Optimize" で検索
   ```

2. リビジョンを削除
   ```
   WP-Optimize → データベース → 「投稿リビジョンをクリーン」にチェック → 実行
   ```

**上級者向け（テスト環境のみ）:**
```
URL: https://your-site.com/wp-admin/?gi_delete_revisions=1
```

---

### 🟡 WARNING: カスタムフィールドが多すぎる

**症状:** 検索が遅い、一覧表示が重い

**対策:** カスタムフィールドをタクソノミー（カテゴリー・タグ）に移行

**例:**
```php
// 補助金額範囲をタクソノミーで管理
register_taxonomy('grant_amount_range', 'grant', array(
    'hierarchical' => false,
    'show_in_rest' => true,
));
```

---

### 🟡 WARNING: 自動下書きが100件以上

**対策（簡単）:**

URL にアクセス（テスト環境のみ）:
```
https://your-site.com/wp-admin/?gi_delete_autodrafts=1
```

または WP-Optimize で削除:
```
WP-Optimize → データベース → 「自動下書き投稿を削除」
```

---

## 📈 効果測定

### 改善前・改善後の比較

診断を実行する前と後で、以下を比較してください:

| 指標 | 確認方法 |
|------|----------|
| **管理画面の一覧表示速度** | ブラウザのDevToolsで測定 |
| **リビジョン数** | 診断ツールで表示 |
| **クエリ実行時間** | 診断ツールで表示 |
| **データベースサイズ** | phpMyAdmin で確認 |

---

## 🛠️ 最適化パッチの効果

`performance-optimization-patch.php` は以下を**自動的に**実行します:

- ✅ リビジョンを最新3件に制限
- ✅ 自動保存間隔を5分に延長
- ✅ Grant クエリの最適化
- ✅ 管理画面の不要な処理を削減
- ✅ メタクエリのキャッシュ

**注意:** パッチは `functions.php` で既に読み込まれているため、追加の作業は不要です。

---

## 📚 詳細ガイド

さらに詳しい情報は以下を参照してください:

- 📖 **[PERFORMANCE-OPTIMIZATION-GUIDE.md](./PERFORMANCE-OPTIMIZATION-GUIDE.md)** - 完全ガイド（推奨）
- 🔍 診断ツールの診断結果に表示される推奨対策

---

## ❓ トラブルシューティング

### Q: 診断ツールのメニューが表示されない

**A:** ブラウザのキャッシュをクリアしてください。または、以下を確認:

```php
// functions.php の最後に以下が追加されているか確認
require_once get_template_directory() . '/inc/performance-diagnostic.php';
```

### Q: 診断実行時にメモリエラーが出る

**A:** `wp-config.php` に以下を追加:

```php
define('WP_MEMORY_LIMIT', '256M');
```

### Q: パッチを無効化したい

**A:** `functions.php` の以下の行をコメントアウト:

```php
// require_once get_template_directory() . '/inc/performance-optimization-patch.php';
```

---

## 🎓 次のステップ

1. ✅ 診断を実行
2. ✅ 重大な問題（CRITICAL）を優先的に対処
3. ✅ リビジョン削除（即効性あり）
4. ✅ 1週間後に再度診断を実行して効果を確認
5. ✅ 月次で定期診断を実施

---

## 📞 サポート

- 📖 完全ガイド: [PERFORMANCE-OPTIMIZATION-GUIDE.md](./PERFORMANCE-OPTIMIZATION-GUIDE.md)
- 🔍 診断ツール: WordPress管理画面 → ツール → パフォーマンス診断

---

**バージョン:** 1.0.0  
**作成日:** 2025-11-28  
**所要時間:** 診断実行 約30秒、対策実施 約10分〜30分
