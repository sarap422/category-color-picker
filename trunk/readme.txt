=== Category Color Picker ===
Contributors: sarap422
Tags: category, color, picker, styling, css
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.6
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

カテゴリーにカラーピッカーを追加し、投稿一覧などに色を反映させるプラグインです。

== Description ==

Category Color Pickerは、WordPressのカテゴリーに色を設定して、フロントエンドの投稿一覧やカテゴリーリンクに自動的に色を反映させるプラグインです。

= 主な機能 =

* カテゴリー編集画面にカラーピッカーを追加
* 設定した色をフロントエンドに自動反映
* 相対輝度に基づく自動テキスト色調整
* カスタマイズ可能なCSSセレクタ
* カテゴリー一覧での色表示

= 対応プラグイン・テーマ =

* VK All in One Expansion Unit
* Content Views
* 一般的なWordPressテーマ
* カスタムセレクタで任意の要素に対応

= 使い方 =

1. プラグインを有効化
2. 「投稿」→「カテゴリー」でカテゴリーを編集
3. カラーピッカーで色を選択
4. 「設定」→「カテゴリーカラー」でセレクタをカスタマイズ（オプション）

== Installation ==

1. プラグインファイルを `/wp-content/plugins/category-color-picker` ディレクトリにアップロード
2. WordPress管理画面の「プラグイン」メニューからプラグインを有効化
3. 「投稿」→「カテゴリー」でカテゴリーの色を設定

== Frequently Asked Questions ==

= どのテーマでも動作しますか？ =

はい、WordPress標準のカテゴリー表示を使用しているテーマであれば動作します。カスタムセレクタの設定により、特定のテーマやプラグインにも対応できます。

= VK All in One Expansion Unitの代替になりますか？ =

はい、VK All in One Expansion Unitのカテゴリーカラー機能の代替として使用できます。より柔軟なセレクタ設定が可能です。

= セレクタをカスタマイズできますか？ =

はい、「設定」→「カテゴリーカラー」から自由にCSSセレクタを設定できます。

== Screenshots ==

1. カテゴリー編集画面のカラーピッカー        ← screenshot-1.png
2. カテゴリー一覧での色表示                  ← screenshot-2.png  
3. セレクタ設定画面                          ← screenshot-3.png
4. フロントエンドでの色反映例                ← screenshot-4.png

== Changelog ==

= 1.0.6 =
* タグを対象から排除

= 1.0.5 =
* wp_enqueue_style()を使用したCSS出力方法に変更（プラグインチェック対応）
* テキスト色の輝度閾値を0.6に調整（より読みやすく）
* プラグインの説明とメッセージを日本語化
* コードの最適化とWordPress標準への準拠

= 1.0.4 =
* バグ修正と安定性の向上
* CSSセレクター処理の強化
* エラーハンドリングの改善

= 1.0.3 =
* 初回リリース
* カラーピッカー統合
* 自動テキスト色調整
* カスタマイズ可能なCSSセレクター