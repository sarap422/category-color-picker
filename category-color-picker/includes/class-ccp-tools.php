<?php

/**
 * Category Color Picker — import/export tools (WordPress glue).
 *
 * UI ボタンはカテゴリー一覧画面に JS で注入。重い処理はサーバ側 AJAX。
 * 純粋ロジックは Category_Color_IO に委譲する。
 *
 * @package category-color-picker
 */

// セキュリティチェック
if (!defined('ABSPATH')) {
  exit;
}

class Category_Color_Tools {

  const NONCE_ACTION     = 'ccp_category_tools';
  const MAX_IMPORT_BYTES = 2097152; // 2MB

  public function __construct() {
    add_action('admin_enqueue_scripts', array($this, 'enqueue'));
    add_action('wp_ajax_ccp_export_categories', array($this, 'ajax_export_categories'));
    add_action('wp_ajax_ccp_import_categories', array($this, 'ajax_import_categories'));
  }

  /**
   * カテゴリー一覧画面（edit-tags.php?taxonomy=category）のみでスクリプトを読み込む。
   */
  public function enqueue($hook) {
    if ('edit-tags.php' !== $hook) {
      return;
    }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || 'edit-category' !== $screen->id) {
      return;
    }

    wp_enqueue_script(
      'category-color-tools',
      plugin_dir_url(dirname(__FILE__)) . 'category-color-tools.js',
      array('jquery'),
      CCP_VERSION,
      true
    );

    wp_localize_script('category-color-tools', 'ccpTools', array(
      'ajaxurl' => admin_url('admin-ajax.php'),
      'nonce'   => wp_create_nonce(self::NONCE_ACTION),
      'i18n'    => array(
        'exportBtn'     => __('Export', 'category-color-picker'),
        'importBtn'     => __('Import', 'category-color-picker'),
        'importConfirm' => __('This will overwrite the color and noindex settings of existing categories with matching slugs. Continue?', 'category-color-picker'),
        'invalidFile'   => __('Please select a JSON file.', 'category-color-picker'),
        'parseError'    => __('Failed to parse JSON.', 'category-color-picker'),
        'missingItems'  => __('Invalid file format (no categories).', 'category-color-picker'),
        'createdFmt'    => __('created', 'category-color-picker'),
        'updatedFmt'    => __('updated', 'category-color-picker'),
        'errorsFmt'     => __('errors', 'category-color-picker'),
        'genericError'  => __('An error occurred: ', 'category-color-picker'),
      ),
    ));
  }

  // ============================================================
  // エクスポート
  // ============================================================
  public function ajax_export_categories() {
    check_ajax_referer(self::NONCE_ACTION, 'nonce');
    if (!current_user_can('manage_categories')) {
      wp_send_json_error(__('You do not have permission.', 'category-color-picker'));
    }

    $terms = get_terms(array('taxonomy' => 'category', 'hide_empty' => false));
    if (is_wp_error($terms)) {
      wp_send_json_error($terms->get_error_message());
    }

    // 親 term_id → slug の解決用マップ
    $slug_by_id = array();
    foreach ($terms as $t) {
      $slug_by_id[(int) $t->term_id] = $t->slug;
    }

    $categories = array();
    foreach ($terms as $t) {
      $parent_slug = '';
      if ((int) $t->parent && isset($slug_by_id[(int) $t->parent])) {
        $parent_slug = $slug_by_id[(int) $t->parent];
      }
      $color = get_term_meta($t->term_id, 'category_color', true);

      $categories[] = array(
        'id'          => (int) $t->term_id,
        'name'        => $t->name,
        'slug'        => $t->slug,
        'parent_slug' => $parent_slug,
        'description' => $t->description,
        'color'       => $color ? $color : '',
        'noindex'     => (get_term_meta($t->term_id, 'category_noindex', true) === '1'),
      );
    }

    wp_send_json_success(array(
      'format'      => Category_Color_IO::FORMAT,
      'version'     => Category_Color_IO::VERSION,
      'exported_at' => current_time('c'),
      'source_site' => home_url(),
      'categories'  => $categories,
      'filename'    => 'category-colors-' . current_time('YmdHis') . '.json',
    ));
  }

  // ============================================================
  // インポート
  // ============================================================
  public function ajax_import_categories() {
    check_ajax_referer(self::NONCE_ACTION, 'nonce');
    if (!current_user_can('manage_categories')) {
      wp_send_json_error(__('You do not have permission.', 'category-color-picker'));
    }

    // 生 JSON を温存（sanitize すると壊れるため）。直後にサイズ・json_decode で検証し、
    // 各フィールドは復号後に sanitize する。
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
    $raw = isset($_POST['data']) ? wp_unslash($_POST['data']) : '';
    if (!is_string($raw) || strlen($raw) > self::MAX_IMPORT_BYTES) {
      wp_send_json_error(__('The data is invalid.', 'category-color-picker'));
    }

    $data = json_decode($raw, true);
    if (JSON_ERROR_NONE !== json_last_error()) {
      wp_send_json_error(__('Failed to parse JSON.', 'category-color-picker'));
    }

    $check = Category_Color_IO::validate_import($data);
    if (!$check['ok']) {
      wp_send_json_error($check['error']);
    }

    // 正規化 → 親が子より先に来るよう並べ替え
    $rows = array();
    foreach ($data['categories'] as $row) {
      $rows[] = Category_Color_IO::normalize_row($row);
    }
    $rows = Category_Color_IO::sort_by_hierarchy($rows);

    $created    = 0;
    $updated    = 0;
    $errors     = array();
    $id_by_slug = array(); // 同セットで作成/解決済み slug → term_id（親解決用）

    foreach ($rows as $row) {
      $name        = sanitize_text_field($row['name']);
      $slug        = sanitize_title($row['slug']);
      $description = sanitize_text_field($row['description']);
      $color       = Category_Color_IO::is_valid_hex($row['color']) ? sanitize_hex_color($row['color']) : '';
      $noindex     = $row['noindex'];

      if ('' === $slug) {
        $errors[] = ('' !== $name) ? $name : '(no slug)';
        continue;
      }

      // 親 term_id を解決（slug は sanitize_title で正規化し、マップのキーと一致させる）
      $parent_slug = sanitize_title($row['parent_slug']);
      $parent_id   = 0;
      if ('' !== $parent_slug) {
        if (isset($id_by_slug[$parent_slug])) {
          $parent_id = $id_by_slug[$parent_slug];
        } else {
          $pt = get_term_by('slug', $parent_slug, 'category');
          if ($pt && !is_wp_error($pt)) {
            $parent_id = (int) $pt->term_id;
          }
        }
      }

      $existing = get_term_by('slug', $slug, 'category');
      if ($existing && !is_wp_error($existing)) {
        // 既存：色/noindex のみ上書き（名前・説明・親は触らない）
        $term_id = (int) $existing->term_id;
        $updated++;
      } else {
        // 新規作成
        $inserted = wp_insert_term(('' !== $name) ? $name : $slug, 'category', array(
          'slug'        => $slug,
          'description' => $description,
          'parent'      => $parent_id,
        ));
        if (is_wp_error($inserted)) {
          $errors[] = (('' !== $name) ? $name : $slug) . ': ' . $inserted->get_error_message();
          continue;
        }
        $term_id = (int) $inserted['term_id'];
        $created++;
      }

      // 色：ファイルの値で上書き（空なら削除）
      if ('' !== $color) {
        update_term_meta($term_id, 'category_color', $color);
      } else {
        delete_term_meta($term_id, 'category_color');
      }
      // noindex：ファイルの値で上書き（false なら削除）
      if ($noindex) {
        update_term_meta($term_id, 'category_noindex', '1');
      } else {
        delete_term_meta($term_id, 'category_noindex');
      }

      $id_by_slug[$slug] = $term_id;
    }

    wp_send_json_success(array(
      'created' => $created,
      'updated' => $updated,
      'errors'  => $errors,
    ));
  }
}
