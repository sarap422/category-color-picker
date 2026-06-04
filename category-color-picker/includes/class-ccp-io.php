<?php

/**
 * Category Color Picker — pure import/export logic (no WordPress dependency).
 *
 * Everything here is deterministic and unit-testable without WordPress loaded:
 * envelope validation, parent-before-child ordering, hex validation, row
 * normalization. The WordPress glue (AJAX, term operations) lives in
 * class-ccp-tools.php and delegates here.
 *
 * @package category-color-picker
 */

// セキュリティチェック（テストでは bootstrap が ABSPATH を定義する）
if (!defined('ABSPATH')) {
  exit;
}

class Category_Color_IO {

  const FORMAT  = 'category-color-picker-categories';
  const VERSION = 1;

  /**
   * インポート JSON のエンベロープを検証する。
   *
   * @param mixed $data json_decode 済みデータ（連想配列を想定）
   * @return array{ok:bool,error:string}
   */
  public static function validate_import($data) {
    if (!is_array($data)) {
      return array('ok' => false, 'error' => 'データの形式が不正です。');
    }
    if (!isset($data['format']) || self::FORMAT !== $data['format']) {
      return array('ok' => false, 'error' => 'ファイル形式が不正です（format が一致しません）。');
    }
    if (!isset($data['version']) || (int) $data['version'] !== self::VERSION) {
      return array('ok' => false, 'error' => '対応していないバージョンのファイルです。');
    }
    if (empty($data['categories']) || !is_array($data['categories'])) {
      return array('ok' => false, 'error' => 'ファイル形式が不正です（categories がありません）。');
    }
    return array('ok' => true, 'error' => '');
  }

  /**
   * カテゴリー行を「親が子より先」に並べ替える（親再マップのため）。
   *
   * - 親なし（parent_slug が空）はそのまま配置
   * - 親が同セット内に無い（移行先で再解決される orphan）はトップレベル扱いで配置
   * - 循環参照は無限ループせず、残りをそのまま末尾に付与して全件を保持
   *
   * @param array $cats 正規化済みの行配列（slug / parent_slug を持つ）
   * @return array 並べ替え後の行配列（要素は入力のまま、順序のみ変更）
   */
  public static function sort_by_hierarchy(array $cats) {
    $present = array();
    foreach ($cats as $row) {
      if (isset($row['slug']) && '' !== $row['slug']) {
        $present[$row['slug']] = true;
      }
    }

    $sorted    = array();
    $placed    = array();
    $remaining = $cats;

    do {
      $progress       = false;
      $next_remaining = array();
      foreach ($remaining as $row) {
        $slug   = isset($row['slug']) ? $row['slug'] : '';
        $parent = isset($row['parent_slug']) ? $row['parent_slug'] : '';

        // 配置可能：親なし / 親が同セット外 / 親が既に配置済み
        if ('' === $parent || empty($present[$parent]) || !empty($placed[$parent])) {
          $sorted[] = $row;
          if ('' !== $slug) {
            $placed[$slug] = true;
          }
          $progress = true;
        } else {
          $next_remaining[] = $row;
        }
      }
      $remaining = $next_remaining;
    } while ($progress && !empty($remaining));

    // 循環で残った分は欠落させず末尾へ
    foreach ($remaining as $row) {
      $sorted[] = $row;
    }

    return $sorted;
  }

  /**
   * HEX カラー文字列（#RGB または #RRGGBB）として妥当か。
   *
   * @param mixed $color
   * @return bool
   */
  public static function is_valid_hex($color) {
    if (!is_string($color)) {
      return false;
    }
    return (bool) preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $color);
  }

  /**
   * 1 行を既知キーへ正規化し、欠損は既定値で補完する。
   *
   * @param mixed $row
   * @return array{name:string,slug:string,parent_slug:string,description:string,color:string,noindex:bool}
   */
  public static function normalize_row($row) {
    if (!is_array($row)) {
      $row = array();
    }
    return array(
      'name'        => isset($row['name']) ? (string) $row['name'] : '',
      'slug'        => isset($row['slug']) ? (string) $row['slug'] : '',
      'parent_slug' => isset($row['parent_slug']) ? (string) $row['parent_slug'] : '',
      'description' => isset($row['description']) ? (string) $row['description'] : '',
      'color'       => isset($row['color']) ? (string) $row['color'] : '',
      'noindex'     => self::to_bool(isset($row['noindex']) ? $row['noindex'] : false),
    );
  }

  /**
   * 緩い真偽変換。文字列 '0' と '' は false に倒す。
   *
   * @param mixed $v
   * @return bool
   */
  private static function to_bool($v) {
    if (is_string($v)) {
      return '' !== $v && '0' !== $v;
    }
    return (bool) $v;
  }
}
