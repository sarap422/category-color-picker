=== Category Color Picker ===
Contributors: sarap422
Tags: category, color, picker, noindex, styling, css
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPressのカテゴリーにカラーピッカーを追加し、投稿一覧などに色を反映させるプラグインです。Noindex 設定機能付き。

== Description ==

Category Color Picker は、WordPress のカテゴリーに色を設定して、フロントエンドの投稿一覧やカテゴリーリンクに自動的に色を反映させるプラグインです。

= 主な機能 =

* カテゴリー編集画面にカラーピッカーを追加
* 設定した色をフロントエンドに自動反映
* 相対輝度に基づく自動テキスト色調整
* カスタマイズ可能な CSS セレクタ
* カテゴリー一覧への「色」「ID」「Noindex」列の追加
* カテゴリー一覧を ID 列でソート可能
* カテゴリー単位で Noindex を設定（アーカイブページ・投稿ページ・タグ/日付アーカイブに対応）
* カテゴリー（色・Noindex 設定含む）の JSON エクスポート / インポート

= 対応プラグイン・テーマ =

* VK All in One Expansion Unit
* Content Views
* 一般的な WordPress テーマ
* カスタムセレクタで任意の要素に対応

= 使い方 =

1. プラグインを有効化
2. 「投稿」→「カテゴリー」でカテゴリーを編集
3. カラーピッカーで色を選択
4. 必要に応じて「Noindex」チェックボックスをオン
5. 「設定」→「カテゴリーカラー」でセレクタをカスタマイズ（オプション）

== Installation ==

1. プラグインファイルを `/wp-content/plugins/category-color-picker` ディレクトリにアップロード
2. WordPress 管理画面の「プラグイン」メニューからプラグインを有効化
3. 「投稿」→「カテゴリー」でカテゴリーの色・Noindex を設定

== Frequently Asked Questions ==

= どのテーマでも動作しますか？ =

はい、WordPress 標準のカテゴリー表示を使用しているテーマであれば動作します。カスタムセレクタの設定により、特定のテーマやプラグインにも対応できます。

= VK All in One Expansion Unit の代替になりますか？ =

はい、VK All in One Expansion Unit のカテゴリーカラー機能の代替として使用できます。より柔軟なセレクタ設定が可能です。

= セレクタをカスタマイズできますか？ =

はい、「設定」→「カテゴリーカラー」から自由に CSS セレクタを設定できます。

= Noindex はどのページに適用されますか？ =

カテゴリーアーカイブページ、そのカテゴリーに属する投稿ページ、タグ・年月日アーカイブページ（対象カテゴリーの投稿を含む場合）に `<meta name="robots" content="noindex" />` を出力します。

= Noindex の設定はどこで確認できますか？ =

カテゴリー一覧の「Noindex」列で確認できます。設定済みのカテゴリーは赤字で「noindex」と表示されます。

== Screenshots ==

1. カテゴリー編集画面のカラーピッカーと Noindex 設定
2. カテゴリー一覧での色・ID・Noindex 列表示
3. セレクタ設定画面
4. フロントエンドでの色反映例

== Changelog ==

= 1.2.0 =
* カテゴリーの JSON エクスポート / インポート機能を追加（カテゴリー一覧画面の右上にボタンを設置）
* エクスポートは全カテゴリーを対象（名前・スラッグ・親・説明・色・Noindex を出力）
* インポートはスラッグ基準で再解決。既存カテゴリーは色・Noindex のみ上書き、未存在は新規作成
* includes/ にロジックを分離（純粋ロジックはユニットテスト対応）

= 1.1.0 =
* カテゴリー一覧にカラム「ID」「Noindex」を追加
* カラム順を「名前・スラッグ・色・説明・カウント・ID・Noindex」に変更
* ID 列のソートに対応
* Noindex 機能を追加（カテゴリー単位で meta robots noindex を出力）

= 1.0.6 =
* タグを対象から排除

= 1.0.5 =
* wp_enqueue_style() を使用した CSS 出力方法に変更（プラグインチェック対応）
* テキスト色の輝度閾値を 0.6 に調整（より読みやすく）
* プラグインの説明とメッセージを日本語化
* コードの最適化と WordPress 標準への準拠

= 1.0.4 =
* バグ修正と安定性の向上
* CSS セレクター処理の強化
* エラーハンドリングの改善

= 1.0.3 =
* 初回リリース
* カラーピッカー統合
* 自動テキスト色調整
* カスタマイズ可能な CSS セレクター