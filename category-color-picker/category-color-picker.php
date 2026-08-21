<?php

/**
 * Plugin Name: Category Color Picker
 * Description: Add a color picker to categories and reflect category colors in post listings and other selectors.
 * Version: 1.3.1
 * Author: sarap422
 * Text Domain: category-color-picker
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package category-color-picker
 * @author sarap422
 * @license GPL-2.0+
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
  exit;
}

define('CCP_VERSION', '1.3.1');

// Export/Import 機能の追加ファイル。欠落しても本体（カラーピッカー）は動かし、
// 追加機能のみ無効化して管理画面に通知する（不完全なパッケージ対策）。
$category_color_picker_required_files = array(
  'includes/class-ccp-io.php',
  'includes/class-ccp-tools.php',
);
$category_color_picker_missing_files = array();
foreach ($category_color_picker_required_files as $category_color_picker_file) {
  if (!file_exists(plugin_dir_path(__FILE__) . $category_color_picker_file)) {
    $category_color_picker_missing_files[] = $category_color_picker_file;
  }
}
$category_color_picker_tools_available = empty($category_color_picker_missing_files);

if ($category_color_picker_tools_available) {
  require_once plugin_dir_path(__FILE__) . 'includes/class-ccp-io.php';
  require_once plugin_dir_path(__FILE__) . 'includes/class-ccp-tools.php';
} else {
  add_action('admin_notices', function () use ($category_color_picker_missing_files) {
    printf(
      '<div class="notice notice-error"><p><strong>Category Color Picker:</strong> %s</p></div>',
      esc_html(
        sprintf(
          /* translators: %s: comma-separated list of missing plugin files */
          __('Required plugin files are missing: %s. Please reinstall the plugin.', 'category-color-picker'),
          implode(', ', $category_color_picker_missing_files)
        )
      )
    );
  });
}

class CategoryColorPicker
{

  public function __construct()
  {
    add_action('init', array($this, 'init'));
  }

  public function init()
  {
    // 翻訳ファイルは WordPress が自動で読み込むため、手動での読み込み処理は不要
    // （WordPress 4.6 以降。WordPress.org でホストしているプラグインが対象）

    // カテゴリー編集画面にカラーピッカーを追加
    add_action('category_add_form_fields', array($this, 'add_category_color_field'));
    add_action('category_edit_form_fields', array($this, 'edit_category_color_field'));

    // カテゴリー保存時の処理
    add_action('edited_category', array($this, 'save_category_color'));
    add_action('create_category', array($this, 'save_category_color'));

    // 管理画面でカラーピッカーのスクリプトを読み込み
    add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));

    // カテゴリー一覧に色列を追加
    add_filter('manage_edit-category_columns', array($this, 'add_category_color_column'));
    add_filter('manage_category_custom_column', array($this, 'show_category_color_column'), 10, 3);

    // ID列のソート
    add_filter('manage_edit-category_sortable_columns', array($this, 'add_sortable_columns'));

    // クイック編集にカラーピッカー・Noindex を追加
    add_action('quick_edit_custom_box', array($this, 'quick_edit_fields'), 10, 3);

    // Noindex：カテゴリー編集画面にフィールド追加・保存
    add_action('category_add_form_fields', array($this, 'add_category_noindex_field'));
    add_action('category_edit_form_fields', array($this, 'edit_category_noindex_field'));
    add_action('edited_category', array($this, 'save_category_noindex'));
    add_action('create_category', array($this, 'save_category_noindex'));

    // Noindex：フロントエンドで meta robots を出力
    add_action('wp_head', array($this, 'output_noindex_meta'), 1);

    // 管理画面にメニューを追加
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));

    // フロントエンドでCSSをエンキュー（修正版）
    add_action('wp_enqueue_scripts', array($this, 'enqueue_category_colors_css'));
    add_action('wp_enqueue_scripts', array($this, 'enqueue_category_colors_js'));
  }

  /**
   * 新規カテゴリー追加画面にカラーピッカーを追加
   */
  public function add_category_color_field()
  {
?>
    <div class="form-field">
      <label for="category_color"><?php esc_html_e('Category Color', 'category-color-picker'); ?></label>
      <input type="text" name="category_color" id="category_color" value="#002A7B" class="category-color-picker" />
      <p class="description">
        <?php esc_html_e('Select the color to use for this category.', 'category-color-picker'); ?><br>
        <a href="<?php echo esc_url(admin_url('options-general.php?page=category-color-settings')); ?>"><?php esc_html_e('Configure selectors for category colors', 'category-color-picker'); ?></a>
      </p>
    </div>
  <?php
  }

  /**
   * カテゴリー編集画面にカラーピッカーを追加
   */
  public function edit_category_color_field($term)
  {
    $color = get_term_meta($term->term_id, 'category_color', true);
    if (!$color) {
      $color = '#002A7B';
    }
  ?>
    <tr class="form-field">
      <th scope="row" valign="top">
        <label for="category_color"><?php esc_html_e('Category Color', 'category-color-picker'); ?></label>
      </th>
      <td>
        <input type="text" name="category_color" id="category_color" value="<?php echo esc_attr($color); ?>" class="category-color-picker" />
        <p class="description">
          <?php esc_html_e('Select the color to use for this category.', 'category-color-picker'); ?><br>
          <a href="<?php echo esc_url(admin_url('options-general.php?page=category-color-settings')); ?>"><?php esc_html_e('Configure selectors for category colors', 'category-color-picker'); ?></a>
        </p>
      </td>
    </tr>
  <?php
  }

  /**
   * カテゴリーカラーを保存
   */
  public function save_category_color($term_id)
  {
    // 権限チェック
    if (!current_user_can('manage_categories')) {
      return;
    }

    // Nonce検証（新規作成時・編集時・クイック編集時で異なる）
    if (isset($_POST['ccp_quick_edit_nonce'])) {
      // クイック編集（AJAX: inline-save-tax）
      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ccp_quick_edit_nonce'])), 'ccp_quick_edit')) {
        return;
      }
    } elseif (isset($_POST['tag-name'])) {
      // 新規カテゴリー作成時
      if (!isset($_POST['_wpnonce_add-tag']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_add-tag'])), 'add-tag')) {
        return;
      }
    } else {
      // カテゴリー編集時
      if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'update-tag_' . $term_id)) {
        return;
      }
    }

    if (isset($_POST['category_color'])) {
      $raw = sanitize_text_field(wp_unslash($_POST['category_color']));

      if ($raw === '') {
        // 空で送信された場合は色を解除（クイック編集でのクリアに対応）
        delete_term_meta($term_id, 'category_color');
        return;
      }

      $color = sanitize_hex_color($raw);
      if ($color) {
        update_term_meta($term_id, 'category_color', $color);
      } else {
        // 無効な色の場合は削除
        delete_term_meta($term_id, 'category_color');
      }
    }
  }

  /**
   * 管理画面でカラーピッカーのスクリプトとスタイルを読み込み
   */
  public function enqueue_admin_scripts($hook)
  {
    if ($hook === 'edit-tags.php' || $hook === 'term.php') {
      wp_enqueue_style('wp-color-picker');
      wp_enqueue_script('wp-color-picker');
      wp_enqueue_script(
        'category-color-picker',
        plugin_dir_url(__FILE__) . '/js/category-color-picker.js',
        array('jquery', 'jquery-ui-sortable', 'wp-color-picker', 'inline-edit-tax'),
        CCP_VERSION,
        true
      );

      wp_enqueue_style(
        'category-color-picker',
        plugin_dir_url(__FILE__) . '/css/category-color-picker.css',
        array(),
        CCP_VERSION
      );
    }
  }

  /**
   * クイック編集にカラーピッカーと Noindex のフィールドを追加
   *
   * タクソノミー一覧のクイック編集は列ごとに呼ばれるため、
   * 'color' 列のタイミングでまとめて出力する。
   * 現在値の流し込みは JS 側（category-color-picker.js）で行う。
   *
   * @param string $column_name カラム名
   * @param string $screen      画面種別（'edit-tags'）
   * @param string $taxonomy    タクソノミー名
   */
  public function quick_edit_fields($column_name, $screen = '', $taxonomy = '')
  {
    if ($column_name !== 'color' || $taxonomy !== 'category') {
      return;
    }

    wp_nonce_field('ccp_quick_edit', 'ccp_quick_edit_nonce');
  ?>
    <fieldset>
      <div class="inline-edit-col">
        <label>
          <span class="title"><?php esc_html_e('Color', 'category-color-picker'); ?></span>
          <span class="input-text-wrap">
            <input type="text"
              name="category_color"
              class="ccp-quick-edit-color"
              value=""
              data-default-color="#002A7B" />
          </span>
        </label>
        <label style="margin-top: 6px; display: block;">
          <span class="title">&nbsp;</span>
          <span class="input-text-wrap">
            <input type="checkbox" name="category_noindex" class="ccp-quick-edit-noindex" value="1" />
            <?php esc_html_e('Set noindex for this category', 'category-color-picker'); ?>
          </span>
        </label>
      </div>
    </fieldset>
  <?php
  }

  /**
   * フロントエンドでカテゴリーカラーのCSSをエンキュー
   */
  public function enqueue_category_colors_css()
  {
    // 空のスタイルシートをエンキュー（ダミーファイルでもOK）
    wp_register_style(
      'category-color-picker-frontend',
      false, // URLをfalseにしてインラインスタイル専用に
      array(),
      CCP_VERSION
    );
    wp_enqueue_style('category-color-picker-frontend');

    // インラインCSSを追加
    $css = $this->generate_category_colors_css();
    if (!empty($css)) {
      wp_add_inline_style('category-color-picker-frontend', $css);
    }
  }

  /**
   * カテゴリーカラーのCSSを生成
   */
  private function generate_category_colors_css()
  {
    $categories = get_categories(array('hide_empty' => false));

    if (empty($categories)) {
      return '';
    }

    // 設定されたセレクタを取得
    $default_selectors = '.post-meta-fields [rel*="tag"][href*="category/{$slug}"],
.su-post-meta-fields [rel*="tag"][href*="category/{$slug}"],
.veu_postList ul.postList .postList_terms a[href*="category/{$slug}"],
.pt-cv-wrapper .pt-cv-view [class*="pt-cv-tax"][href*="category/{$slug}"]';

    $selectors_template = get_option('category_color_selectors', $default_selectors);

    $css = "/* Category Colors CSS */\n";

    // :root にカテゴリーカラーを CSS カスタムプロパティとして出力
    $css .= $this->generate_category_colors_vars($categories);

    // デフォルトのカテゴリー色
    $default_tag_selectors = str_replace('{$slug}', '', $selectors_template);
    $css .= $default_tag_selectors . " {\n";
    $css .= "    background: hsla(0, 0%, 96%, 1);\n";
    $css .= "    color: var(--c-gray, hsl(224, 6%, 50%));\n";
    $css .= "}\n\n";

    foreach ($categories as $category) {
      $color = get_term_meta($category->term_id, 'category_color', true);

      if ($color) {
        $text_color = $this->get_text_color($color);
        $slug = $category->slug;

        $category_selectors = str_replace('{$slug}', $slug, $selectors_template);

        $css .= $category_selectors . " {\n";
        $css .= "    background: {$color} !important;\n";
        $css .= "    color: {$text_color} !important;\n";
        $css .= "}\n\n";
      }
    }

    return $css;
  }

  /**
   * :root のカテゴリーカラー CSS カスタムプロパティを生成
   *
   * 出力例:
   *   :root {
   *     --ccp-color-html-css: #e44d26;
   *     --ccp-contrast-html-css: #FFF;
   *   }
   *
   * @param array $categories get_categories() の結果
   * @return string
   */
  private function generate_category_colors_vars($categories)
  {
    $lines = array();

    foreach ($categories as $category) {
      $color = get_term_meta($category->term_id, 'category_color', true);
      if (!$color) {
        continue;
      }

      // CSS カスタムプロパティ名に使える文字だけ通す（日本語スラッグ等を除外）
      $css_key = $this->slug_to_css_key($category->slug);
      if ($css_key === '') {
        continue;
      }

      $lines[] = sprintf('  --ccp-color-%s: %s;', $css_key, $color);
      $lines[] = sprintf('  --ccp-contrast-%s: %s;', $css_key, $this->get_text_color_hex($color));
    }

    if (empty($lines)) {
      return '';
    }

    return ":root {\n" . implode("\n", $lines) . "\n}\n\n";
  }

  /**
   * フロントエンドにカテゴリーカラーの JavaScript 変数を出力
   *
   * 出力例:
   *   window.CCPColors = {"html-css":{"color":"#e44d26","contrast":"#FFFFFF"}, ...};
   *   window.__ccp_color_html_css    = "#e44d26";
   *   window.__ccp_contrast_html_css = "#FFFFFF";
   *
   * スラッグのハイフンはアンダースコアに変換される（chart1-light → chart1_light）。
   * wp_head() 内で出力するため、body 内のインラインスクリプトから参照できる。
   */
  public function enqueue_category_colors_js()
  {
    $categories = get_categories(array('hide_empty' => false));
    if (empty($categories)) {
      return;
    }

    $map     = array();
    $globals = array();

    foreach ($categories as $category) {
      $color = get_term_meta($category->term_id, 'category_color', true);
      if (!$color) {
        continue;
      }

      $contrast = $this->get_text_color_hex($color);

      // オブジェクト形式は元のスラッグをそのまま使う（日本語スラッグも保持）
      $map[$category->slug] = array(
        'color'    => $color,
        'contrast' => $contrast,
      );

      // グローバル変数は JS 識別子として有効なスラッグのみ
      $js_key = $this->slug_to_js_key($category->slug);
      if ($js_key === '') {
        continue;
      }

      $globals[] = sprintf(
        'window.__ccp_color_%1$s = %2$s; window.__ccp_contrast_%1$s = %3$s;',
        $js_key,
        wp_json_encode($color),
        wp_json_encode($contrast)
      );
    }

    if (empty($map)) {
      return;
    }

    $js  = '/* Category Colors JS */' . "\n";
    $js .= 'window.CCPColors = ' . wp_json_encode($map) . ";\n";
    if (!empty($globals)) {
      $js .= implode("\n", $globals) . "\n";
    }

    // src を false にしてインラインスクリプト専用（$in_footer = false で wp_head に出力）
    wp_register_script('category-color-picker-vars', false, array(), CCP_VERSION, false);
    wp_enqueue_script('category-color-picker-vars');
    wp_add_inline_script('category-color-picker-vars', $js);
  }

  /**
   * スラッグを CSS カスタムプロパティ名に使える形に変換
   * 英数字・ハイフン・アンダースコア以外を除去する。
   *
   * @param string $slug
   * @return string 変換後のキー（使用不可な場合は空文字）
   */
  private function slug_to_css_key($slug)
  {
    $key = preg_replace('/[^A-Za-z0-9_-]/', '', $slug);
    return ($key === '-' || $key === '_') ? '' : (string) $key;
  }

  /**
   * スラッグを JavaScript 識別子に使える形に変換
   * ハイフンをアンダースコアに変換し、識別子として無効なものは空文字を返す。
   *
   * 例: chart1-light → chart1_light
   *
   * @param string $slug
   * @return string 変換後のキー（使用不可な場合は空文字）
   */
  private function slug_to_js_key($slug)
  {
    $key = str_replace('-', '_', $slug);
    // 英数字・アンダースコア以外を除去（日本語スラッグ等）
    $key = preg_replace('/[^A-Za-z0-9_]/', '', $key);

    // 空、または先頭が数字の場合は識別子として無効
    if ($key === '' || preg_match('/^[0-9]/', $key)) {
      return '';
    }

    return $key;
  }

  /**
   * 背景色に対するコントラスト色を「実際の色値」で返す
   *
   * get_text_color() は CSS 変数（var(--c-text, ...)）を返すため、
   * Canvas / Chart.js など CSS 変数を解決できない用途ではこちらを使う。
   *
   * @param string $hex_color
   * @return string #FFFFFF または #1F2023
   */
  private function get_text_color_hex($hex_color)
  {
    $hex_color = ltrim($hex_color, '#');

    $r = hexdec(substr($hex_color, 0, 2));
    $g = hexdec(substr($hex_color, 2, 2));
    $b = hexdec(substr($hex_color, 4, 2));

    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    $threshold = (float) get_option('category_color_luminance_threshold', 0.6);

    return $luminance > $threshold ? '#1F2023' : '#FFFFFF';
  }

  /**
   * 管理画面にメニューを追加
   */
  public function add_admin_menu()
  {
    add_options_page(
      esc_html__('Category Color Settings', 'category-color-picker'),
      esc_html__('Category Color', 'category-color-picker'),
      'manage_options',
      'category-color-settings',
      array($this, 'settings_page')
    );
  }

  /**
   * 設定を登録
   */
  public function register_settings()
  {
    register_setting(
      'category_color_settings',
      'category_color_selectors',
      array(
        'sanitize_callback' => array($this, 'sanitize_category_color_selectors')
      )
    );

    register_setting(
      'category_color_settings',
      'category_color_luminance_threshold',
      array(
        'sanitize_callback' => array($this, 'sanitize_luminance_threshold'),
        'default'           => 0.6,
      )
    );
  }

  /**
   * カテゴリーカラーセレクターのサニタイゼーション
   */
  public function sanitize_category_color_selectors($input)
  {
    return sanitize_textarea_field($input);
  }

  /**
   * 輝度閾値のサニタイゼーション（0.00〜1.00 の範囲にクランプ）
   */
  public function sanitize_luminance_threshold($input)
  {
    if ($input === '' || $input === null) {
      return 0.6;
    }

    $value = (float) $input;

    if ($value < 0) {
      $value = 0;
    } elseif ($value > 1) {
      $value = 1;
    }

    return $value;
  }

  /**
   * 設定画面を表示
   */
  public function settings_page()
  {
    // 権限チェック
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'category-color-picker'));
    }

    // デフォルトのセレクタ
    $default_selectors = '.post-meta-fields [rel*="tag"][href*="category/{$slug}"],
.su-post-meta-fields [rel*="tag"][href*="category/{$slug}"],
.veu_postList ul.postList .postList_terms a[href*="category/{$slug}"],
.pt-cv-wrapper .pt-cv-view [class*="pt-cv-tax"][href*="category/{$slug}"]';

    $selectors           = get_option('category_color_selectors', $default_selectors);
    $luminance_threshold = get_option('category_color_luminance_threshold', 0.6);
  ?>
    <div class="wrap">
      <h1><?php esc_html_e('Category Color Settings', 'category-color-picker'); ?></h1>
      <form method="post" action="options.php">
        <?php
        settings_fields('category_color_settings');
        do_settings_sections('category_color_settings');
        ?>
        <table class="form-table">
          <tr>
            <th scope="row">
              <label for="category_color_selectors"><?php esc_html_e('CSS Selectors for Category Colors', 'category-color-picker'); ?></label>
            </th>
            <td>
              <textarea name="category_color_selectors" id="category_color_selectors" rows="10" cols="80" class="large-text code"><?php echo esc_textarea($selectors); ?></textarea>
              <p class="description">
                <?php esc_html_e('Set CSS selectors to apply category colors.', 'category-color-picker'); ?><br>
                <?php esc_html_e('The {$slug} part will be replaced with the category slug.', 'category-color-picker'); ?><br>
                <?php esc_html_e('Separate multiple selectors with commas.', 'category-color-picker'); ?>
              </p>
            </td>
          </tr>
          <tr>
            <th scope="row">
              <label for="category_color_luminance_threshold"><?php esc_html_e('Text Color Luminance Threshold', 'category-color-picker'); ?></label>
            </th>
            <td>
              <input type="number" name="category_color_luminance_threshold" id="category_color_luminance_threshold" value="<?php echo esc_attr($luminance_threshold); ?>" min="0" max="1" step="0.01" class="small-text">
              <p class="description">
                <?php esc_html_e('Background colors with a luminance above this value use dark text; below it, white text is used. Range: 0.00–1.00 (default: 0.60).', 'category-color-picker'); ?>
              </p>
            </td>
          </tr>
        </table>

        <!-- 変更を保存 -->
        <?php submit_button(); ?>


        <h2><?php esc_html_e('Usage Example', 'category-color-picker'); ?></h2>
        <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin: 20px 0;">
          <h3><?php esc_html_e('CSS example generated with current settings:', 'category-color-picker'); ?></h3>
          <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;"><code><?php echo wp_kses_post($this->generate_sample_css($selectors)); ?></code></pre>
        </div>

        <h2><?php esc_html_e('Common Selectors', 'category-color-picker'); ?></h2>
        <div style="background: #f0f0f1; padding: 15px; margin: 20px 0;">
          <h4><?php esc_html_e('For VK All in One Expansion Unit:', 'category-color-picker'); ?></h4>
          <code>.post-meta-fields [rel*="tag"][href*="category/{$slug}"]</code><br>
          <code>.su-post-meta-fields [rel*="tag"][href*="category/{$slug}"]</code><br>
          <code>.veu_postList ul.postList .postList_terms a[href*="category/{$slug}"]</code>

          <h4><?php esc_html_e('For Post Type & Taxonomy Filter:', 'category-color-picker'); ?></h4>
          <code>.pt-cv-wrapper .pt-cv-view [class*="pt-cv-tax"][href*="category/{$slug}"]</code>

          <h4><?php esc_html_e('General category links:', 'category-color-picker'); ?></h4>
          <code>a[href*="category/{$slug}"]</code><br>
          <code>.category-{$slug} a</code>
        </div>
      </form>

      <h2><?php esc_html_e('CSS Variables and JavaScript Variables', 'category-color-picker'); ?></h2>
      <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin: 20px 0;">
        <p>
          <?php esc_html_e('In addition to the selectors above, category colors are also output as CSS custom properties and JavaScript variables. You can use them anywhere in your theme.', 'category-color-picker'); ?>
        </p>

        <h3><?php esc_html_e('CSS custom properties', 'category-color-picker'); ?></h3>
        <p class="description">
          <?php esc_html_e('Output to :root, so they are available from any stylesheet.', 'category-color-picker'); ?>
        </p>
        <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;"><code><?php echo esc_html($this->generate_sample_vars_css()); ?></code></pre>
        <p class="description">
          <?php esc_html_e('Usage example:', 'category-color-picker'); ?>
        </p>
        <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;"><code>.tool-section {
    border: 5px solid var(--ccp-color-<?php echo esc_html($this->get_sample_slug()); ?>);
    color: var(--ccp-contrast-<?php echo esc_html($this->get_sample_slug()); ?>);
}</code></pre>

        <h3><?php esc_html_e('JavaScript variables', 'category-color-picker'); ?></h3>
        <p class="description">
          <?php esc_html_e('Output inside wp_head(), so they can be referenced from inline scripts in the page body. Useful for Chart.js and Canvas, where CSS variables cannot be resolved.', 'category-color-picker'); ?><br>
          <?php esc_html_e('Hyphens in the slug are converted to underscores (chart1-light becomes chart1_light).', 'category-color-picker'); ?>
        </p>
        <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;"><code><?php echo esc_html($this->generate_sample_vars_js()); ?></code></pre>
        <p class="description">
          <?php esc_html_e('Usage example:', 'category-color-picker'); ?>
        </p>
        <pre style="background: #fff; padding: 10px; border: 1px solid #ddd; overflow-x: auto;"><code>new Chart(ctx, {
    data: { datasets: [{ backgroundColor: __ccp_color_<?php echo esc_html($this->get_sample_js_key()); ?> }] }
});</code></pre>
        <p class="description">
          <?php esc_html_e('Slugs that are not valid JavaScript identifiers (for example, non-ASCII slugs or slugs starting with a number) are available only through the CCPColors object.', 'category-color-picker'); ?>
        </p>
      </div>

      <h2><?php esc_html_e('Category List', 'category-color-picker'); ?></h2>
      <p><a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=category')); ?>" class="button"><?php esc_html_e('Go to Category Management', 'category-color-picker'); ?></a></p>
    </div>
  <?php
  }

  /**
   * 設定ページ用：サンプル表示に使うカテゴリースラッグを取得
   * 色が設定済みのカテゴリーがあればそれを、なければダミーを返す。
   *
   * @return string
   */
  private function get_sample_slug()
  {
    foreach ($this->get_colored_categories() as $category) {
      $css_key = $this->slug_to_css_key($category->slug);
      if ($css_key !== '') {
        return $css_key;
      }
    }
    return 'sample-category';
  }

  /**
   * 設定ページ用：サンプル表示に使う JS キーを取得
   *
   * @return string
   */
  private function get_sample_js_key()
  {
    foreach ($this->get_colored_categories() as $category) {
      $js_key = $this->slug_to_js_key($category->slug);
      if ($js_key !== '') {
        return $js_key;
      }
    }
    return 'sample_category';
  }

  /**
   * 色が設定されているカテゴリーの一覧を取得
   *
   * @return array
   */
  private function get_colored_categories()
  {
    $categories = get_categories(array('hide_empty' => false));
    $colored    = array();

    foreach ($categories as $category) {
      if (get_term_meta($category->term_id, 'category_color', true)) {
        $colored[] = $category;
      }
    }

    return $colored;
  }

  /**
   * 設定ページ用：CSS カスタムプロパティのサンプル出力を生成（先頭3件）
   *
   * @return string
   */
  private function generate_sample_vars_css()
  {
    $colored = $this->get_colored_categories();

    if (empty($colored)) {
      return ":root {\n  --ccp-color-sample-category: #002A7B;\n  --ccp-contrast-sample-category: #FFFFFF;\n}";
    }

    $lines = array();
    $count = 0;

    foreach ($colored as $category) {
      $css_key = $this->slug_to_css_key($category->slug);
      if ($css_key === '') {
        continue;
      }

      $color     = get_term_meta($category->term_id, 'category_color', true);
      $lines[]   = sprintf('  --ccp-color-%s: %s;', $css_key, $color);
      $lines[]   = sprintf('  --ccp-contrast-%s: %s;', $css_key, $this->get_text_color_hex($color));
      $count++;

      if ($count >= 3) {
        $lines[] = '  ...';
        break;
      }
    }

    return ":root {\n" . implode("\n", $lines) . "\n}";
  }

  /**
   * 設定ページ用：JavaScript 変数のサンプル出力を生成（先頭3件）
   *
   * @return string
   */
  private function generate_sample_vars_js()
  {
    $colored = $this->get_colored_categories();

    if (empty($colored)) {
      return 'window.CCPColors = {"sample-category":{"color":"#002A7B","contrast":"#FFFFFF"}};' . "\n"
        . 'window.__ccp_color_sample_category = "#002A7B";' . "\n"
        . 'window.__ccp_contrast_sample_category = "#FFFFFF";';
    }

    $lines   = array('window.CCPColors = { ... };');
    $count   = 0;

    foreach ($colored as $category) {
      $js_key = $this->slug_to_js_key($category->slug);
      if ($js_key === '') {
        continue;
      }

      $color   = get_term_meta($category->term_id, 'category_color', true);
      $lines[] = sprintf('window.__ccp_color_%s = "%s";', $js_key, $color);
      $lines[] = sprintf('window.__ccp_contrast_%s = "%s";', $js_key, $this->get_text_color_hex($color));
      $count++;

      if ($count >= 3) {
        $lines[] = '...';
        break;
      }
    }

    return implode("\n", $lines);
  }

  /**
   * サンプルCSS生成
   */
  private function generate_sample_css($selectors)
  {
    $sample_slug = 'sample-category';
    $sample_color = '#002A7B';

    $processed_selectors = str_replace('{$slug}', $sample_slug, $selectors);
    $lines = explode(',', $processed_selectors);
    $formatted_selectors = array();

    foreach ($lines as $line) {
      $formatted_selectors[] = trim($line);
    }

    $css = implode(",\n", $formatted_selectors) . " {\n";
    $css .= "    background: {$sample_color};\n";
    $css .= "    color: #FFF;\n";
    $css .= "}";

    return $css;
  }

  /**
   * カテゴリー一覧のカラム構成を変更
   * 順序: 名前 | スラッグ | 色 | 説明 | カウント | ID | Noindex
   */
  public function add_category_color_column($columns)
  {
    // WordPress デフォルト列: name, description, slug, posts（カウント）
    // 目標順: name, slug, color, description, posts, id, noindex
    $new_columns = array();

    // 名前
    if (isset($columns['name'])) {
      $new_columns['name'] = $columns['name'];
    }
    // スラッグ
    if (isset($columns['slug'])) {
      $new_columns['slug'] = $columns['slug'];
    }
    // 色（新規追加）
    $new_columns['color'] = esc_html__('Color', 'category-color-picker');
    // 説明
    if (isset($columns['description'])) {
      $new_columns['description'] = $columns['description'];
    }
    // カウント
    if (isset($columns['posts'])) {
      $new_columns['posts'] = $columns['posts'];
    }
    // ID（新規追加）
    $new_columns['category_id'] = esc_html__('ID', 'category-color-picker');
    // Noindex（新規追加）
    $new_columns['noindex'] = esc_html__('Noindex', 'category-color-picker');

    // 上記で拾えなかった列（cbなど）は先頭に挿入
    foreach ($columns as $key => $value) {
      if (!isset($new_columns[$key])) {
        $new_columns = array($key => $value) + $new_columns;
      }
    }

    return $new_columns;
  }

  /**
   * ID列をソート可能にする（Color・Noindex列はソート不要）
   */
  public function add_sortable_columns($sortable)
  {
    $sortable['category_id'] = 'term_id';
    return $sortable;
  }

  /**
   * カテゴリー一覧の各カスタム列にデータを表示
   */
  public function show_category_color_column($content, $column_name, $term_id)
  {
    if ($column_name === 'color') {
      $color = get_term_meta($term_id, 'category_color', true);

      // クイック編集の JS が現在値を読み取るための隠しデータ
      $hidden = sprintf(
        '<span class="ccp-inline-color" data-color="%s" style="display:none;"></span>',
        esc_attr($color ? $color : '')
      );

      if ($color) {
        $text_color = $this->get_text_color($color);
        $content = $hidden . sprintf(
          '<div class="category-color-display" style="background-color: %s; color: %s; padding: 4px 8px; border-radius: 3px; display: inline-block; min-width: 60px; text-align: center; font-size: 11px;">%s</div>',
          esc_attr($color),
          esc_attr($text_color),
          esc_html($color)
        );
      } else {
        $content = $hidden . '<span style="color: #999;">' . esc_html__('Not set', 'category-color-picker') . '</span>';
      }
    }

    if ($column_name === 'category_id') {
      $content = '<span style="color: #666;">' . intval($term_id) . '</span>';
    }

    if ($column_name === 'noindex') {
      $noindex = get_term_meta($term_id, 'category_noindex', true);

      // クイック編集の JS が現在値を読み取るための隠しデータ
      $hidden = sprintf(
        '<span class="ccp-inline-noindex" data-noindex="%s" style="display:none;"></span>',
        $noindex ? '1' : '0'
      );

      if ($noindex) {
        $content = $hidden . '<span style="color: #d63638; font-weight: 600;">noindex</span>';
      } else {
        $content = $hidden . '<span style="color: #999;">—</span>';
      }
    }

    return $content;
  }

  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺=================
  // Noindex 機能
  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺=================

  /**
   * 新規カテゴリー追加画面に Noindex チェックボックスを追加
   */
  public function add_category_noindex_field()
  {
  ?>
    <div class="form-field">
      <label for="category_noindex">
        <input type="checkbox" name="category_noindex" id="category_noindex" value="1" />
        <?php esc_html_e('Set noindex for this category', 'category-color-picker'); ?>
      </label>
      <p class="description">
        <?php esc_html_e('If checked, noindex meta tag will be output on the category archive page and posts belonging to this category.', 'category-color-picker'); ?>
      </p>
    </div>
  <?php
  }

  /**
   * カテゴリー編集画面に Noindex チェックボックスを追加
   */
  public function edit_category_noindex_field($term)
  {
    $noindex = get_term_meta($term->term_id, 'category_noindex', true);
  ?>
    <tr class="form-field">
      <th scope="row" valign="top">
        <label for="category_noindex"><?php esc_html_e('Noindex', 'category-color-picker'); ?></label>
      </th>
      <td>
        <label>
          <input type="checkbox" name="category_noindex" id="category_noindex" value="1" <?php checked($noindex, '1'); ?> />
          <?php esc_html_e('Set noindex for this category', 'category-color-picker'); ?>
        </label>
        <p class="description">
          <?php esc_html_e('If checked, noindex meta tag will be output on the category archive page and posts belonging to this category.', 'category-color-picker'); ?>
        </p>
      </td>
    </tr>
<?php
  }

  /**
   * Noindex フラグを保存
   */
  public function save_category_noindex($term_id)
  {
    if (!current_user_can('manage_categories')) {
      return;
    }

    // Nonce 検証（新規作成時・編集時・クイック編集時で異なる）
    if (isset($_POST['ccp_quick_edit_nonce'])) {
      // クイック編集（AJAX: inline-save-tax）
      if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['ccp_quick_edit_nonce'])), 'ccp_quick_edit')) {
        return;
      }
    } elseif (isset($_POST['tag-name'])) {
      if (!isset($_POST['_wpnonce_add-tag']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce_add-tag'])), 'add-tag')) {
        return;
      }
    } else {
      if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'update-tag_' . $term_id)) {
        return;
      }
    }

    if (isset($_POST['category_noindex']) && $_POST['category_noindex'] === '1') {
      update_term_meta($term_id, 'category_noindex', '1');
    } else {
      delete_term_meta($term_id, 'category_noindex');
    }
  }

  /**
   * フロントエンドで noindex meta タグを出力
   *
   * 対象:
   *   - カテゴリーアーカイブページ
   *   - 投稿ページ（そのカテゴリーに属するもの）
   *   - タグ・年月日アーカイブページ（指定カテゴリーの投稿を含む場合）
   */
  public function output_noindex_meta()
  {
    // noindex 設定済みカテゴリーを取得
    $noindex_ids = $this->get_noindex_category_ids();

    if (empty($noindex_ids)) {
      return;
    }

    $should_noindex = false;

    // カテゴリーアーカイブページ
    if (is_category($noindex_ids)) {
      $should_noindex = true;
    }
    // 投稿ページ
    elseif (is_single() && has_category($noindex_ids)) {
      $should_noindex = true;
    }
    // タグ・年月日アーカイブ（指定カテゴリーの投稿を含む）
    elseif (is_archive() && !is_category()) {
      global $wp_query;
      if (!empty($wp_query->posts)) {
        foreach ($wp_query->posts as $post) {
          if (has_category($noindex_ids, $post)) {
            $should_noindex = true;
            break;
          }
        }
      }
    }

    if ($should_noindex) {
      echo '<meta name="robots" content="noindex" />' . "\n";
    }
  }

  /**
   * noindex フラグが設定されたカテゴリー ID の配列を返す
   *
   * @return int[]
   */
  private function get_noindex_category_ids()
  {
    static $cache = null;
    if ($cache !== null) {
      return $cache;
    }

    $categories = get_categories(array('hide_empty' => false));
    $ids = array();
    foreach ($categories as $cat) {
      if (get_term_meta($cat->term_id, 'category_noindex', true) === '1') {
        $ids[] = (int) $cat->term_id;
      }
    }

    $cache = $ids;
    return $ids;
  }

  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺=================
  // テキストカラー計算
  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺=================

  /**
   * 背景色に基づいて適切なテキスト色を計算
   */
  private function get_text_color($hex_color)
  {
    // #を除去
    $hex_color = ltrim($hex_color, '#');

    // RGB値に変換
    $r = hexdec(substr($hex_color, 0, 2));
    $g = hexdec(substr($hex_color, 2, 2));
    $b = hexdec(substr($hex_color, 4, 2));

    // 相対輝度を計算
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
    $threshold = (float) get_option('category_color_luminance_threshold', 0.6);

    // 輝度が閾値より高い場合は暗いテキスト、そうでない場合は白テキスト
    return $luminance > $threshold ? 'var(--c-base-900, hsl(224, 6%, 13%))' : '#FFF';
  }
}

// プラグインを初期化
new CategoryColorPicker();

// Export/Import 機能（ファイルが揃っている管理画面のみ）
if ($category_color_picker_tools_available && is_admin()) {
  new Category_Color_Tools();
}