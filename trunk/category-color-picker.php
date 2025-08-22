<?php

/**
 * Plugin Name: Category Color Picker
 * Description: Add a color picker to categories and reflect category colors in post listings and other selectors.
 * Version: 1.0.6
 * Author: sarap422
 * Text Domain: category-color-picker
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

class CategoryColorPicker {

  public function __construct() {
    add_action('init', array($this, 'init'));
  }

  public function init() {
    // initフックで翻訳を読み込み
    load_plugin_textdomain(
      'category-color-picker',
      false,
      dirname(plugin_basename(__FILE__)) . '/languages/'
    );

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

    // 管理画面にメニューを追加
    add_action('admin_menu', array($this, 'add_admin_menu'));
    add_action('admin_init', array($this, 'register_settings'));

    // フロントエンドでCSSをエンキュー（修正版）
    add_action('wp_enqueue_scripts', array($this, 'enqueue_category_colors_css'));
  }

  /**
   * 新規カテゴリー追加画面にカラーピッカーを追加
   */
  public function add_category_color_field() {
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
  public function edit_category_color_field($term) {
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
  public function save_category_color($term_id) {
    // 権限チェック
    if (!current_user_can('manage_categories')) {
      return;
    }

    // Nonce検証（新規作成時と編集時で異なる）
    if (isset($_POST['tag-name'])) {
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

    if (isset($_POST['category_color']) && !empty($_POST['category_color'])) {
      $color = sanitize_hex_color(sanitize_text_field(wp_unslash($_POST['category_color'])));
      if ($color) {
        update_term_meta($term_id, 'category_color', $color);
      } else {
        // 無効な色の場合は削除
        delete_term_meta($term_id, 'category_color');
      }
    }
  }

  /**
   * 管理画面でカラーピッカーのスクリプトを読み込み
   */
  public function enqueue_admin_scripts($hook) {
    if ($hook === 'edit-tags.php' || $hook === 'term.php') {
      wp_enqueue_style('wp-color-picker');
      wp_enqueue_script('wp-color-picker');
      wp_enqueue_script('category-color-picker', plugin_dir_url(__FILE__) . 'category-color-picker.js', array('wp-color-picker'), '1.0.0', true);
    }
  }

  /**
   * フロントエンドでカテゴリーカラーのCSSをエンキュー
   */
  public function enqueue_category_colors_css() {
    // 空のスタイルシートをエンキュー（ダミーファイルでもOK）
    wp_register_style(
      'category-color-picker-frontend',
      false, // URLをfalseにしてインラインスタイル専用に
      array(),
      '1.0.6'
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
  private function generate_category_colors_css() {
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

    // デフォルトのカテゴリー色
    $default_tag_selectors = str_replace('{$slug}', '', $selectors_template);
    $css .= $default_tag_selectors . " {\n";
    $css .= "    background: hsla(0, 0%, 96%, 1);\n";
    $css .= "    color: var(--c-gray, hsl(223, 6%, 50%));\n";
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
   * 管理画面にメニューを追加
   */
  public function add_admin_menu() {
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
  public function register_settings() {
    register_setting(
      'category_color_settings',
      'category_color_selectors',
      array(
        'sanitize_callback' => array($this, 'sanitize_category_color_selectors')
      )
    );
  }

  /**
   * カテゴリーカラーセレクターのサニタイゼーション
   */
  public function sanitize_category_color_selectors($input) {
    return sanitize_textarea_field($input);
  }

  /**
   * 設定画面を表示
   */
  public function settings_page() {
    // 権限チェック
    if (!current_user_can('manage_options')) {
      wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'category-color-picker'));
    }

    // デフォルトのセレクタ
    $default_selectors = '.post-meta-fields [rel*="tag"][href*="category/{$slug}"],
.su-post-meta-fields [rel*="tag"][href*="category/{$slug}"],
.veu_postList ul.postList .postList_terms a[href*="category/{$slug}"],
.pt-cv-wrapper .pt-cv-view [class*="pt-cv-tax"][href*="category/{$slug}"]';

    $selectors = get_option('category_color_selectors', $default_selectors);
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
        </table>

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

        <?php submit_button(); ?>
      </form>

      <h2><?php esc_html_e('Category List', 'category-color-picker'); ?></h2>
      <p><a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=category')); ?>" class="button"><?php esc_html_e('Go to Category Management', 'category-color-picker'); ?></a></p>
    </div>
<?php
  }

  /**
   * サンプルCSS生成
   */
  private function generate_sample_css($selectors) {
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
   * カテゴリー一覧に色列を追加
   */
  public function add_category_color_column($columns) {
    // 説明列の後に色列を追加
    $new_columns = array();
    foreach ($columns as $key => $value) {
      $new_columns[$key] = $value;
      if ($key === 'description') {
        $new_columns['color'] = esc_html__('Color', 'category-color-picker');
      }
    }
    return $new_columns;
  }

  /**
   * カテゴリー一覧の色列にカラー表示
   */
  public function show_category_color_column($content, $column_name, $term_id) {
    if ($column_name === 'color') {
      $color = get_term_meta($term_id, 'category_color', true);
      if ($color) {
        $text_color = $this->get_text_color($color);
        $content = sprintf(
          '<div class="category-color-display" style="background-color: %s; color: %s; padding: 4px 8px; border-radius: 3px; display: inline-block; min-width: 60px; text-align: center; font-size: 11px;">%s</div>',
          esc_attr($color),
          esc_attr($text_color),
          esc_html($color)
        );
      } else {
        $content = '<span style="color: #999;">' . esc_html__('Not set', 'category-color-picker') . '</span>';
      }
    }
    return $content;
  }

  /**
   * 背景色に基づいて適切なテキスト色を計算
   */
  private function get_text_color($hex_color) {
    // #を除去
    $hex_color = ltrim($hex_color, '#');

    // RGB値に変換
    $r = hexdec(substr($hex_color, 0, 2));
    $g = hexdec(substr($hex_color, 2, 2));
    $b = hexdec(substr($hex_color, 4, 2));

    // 相対輝度を計算
    $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

    // 輝度が0.6より高い場合は暗いテキスト、そうでない場合は白テキスト
    return $luminance > 0.6 ? 'var(--c-text, hsl(223, 6%, 13%))' : '#FFF';
  }
}

// プラグインを初期化
new CategoryColorPicker();