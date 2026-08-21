jQuery(document).ready(function ($) {
  var DEFAULT_COLOR = '#002A7B';

  // ───────────────────────────────
  // 通常の編集画面（追加フォーム・編集フォーム）
  // ───────────────────────────────
  $('.category-color-picker').wpColorPicker({
    defaultColor: DEFAULT_COLOR
  });

  // ───────────────────────────────
  // クイック編集
  // ───────────────────────────────
  // WordPress はテンプレート行（#inline-edit）を複製して表示するため、
  // 開かれた直後に現在値を流し込み、カラーピッカーを初期化する。
  //
  // inlineEditTax.edit をラップする方式は WordPress の内部実装に依存して
  // 壊れやすいため、クリックを拾って行を探す方式にしている。

  /**
   * クイック編集の行を探す
   * ID 指定を優先し、見つからなければ表示中の .inline-editor を使う。
   */
  function findEditRow(termId) {
    var $editRow = $('#edit-' + termId);
    if ($editRow.length) {
      return $editRow;
    }
    return $('tr.inline-editor').filter(':visible').first();
  }

  /**
   * 元の行から現在の色を取得する
   * data 属性を優先し、無ければ色バッジのテキストから拾う。
   */
  function getCurrentColor($row) {
    var color = $row.find('.ccp-inline-color').data('color');

    if (typeof color === 'undefined' || color === '') {
      color = $.trim($row.find('.category-color-display').text());
    }

    return color ? String(color) : '';
  }

  /**
   * 元の行から現在の noindex 設定を取得する
   */
  function getCurrentNoindex($row) {
    return String($row.find('.ccp-inline-noindex').data('noindex')) === '1';
  }

  /**
   * カラーピッカーを（必要なら作り直して）初期化する
   */
  function initColorPicker($input, value) {
    if (!$input.length) {
      return;
    }

    // 既にピッカー化されている場合は素の input に戻してから再初期化する
    var $container = $input.closest('.wp-picker-container');
    if ($container.length) {
      $input.wpColorPicker('close');
      $input.detach();
      $container.replaceWith($input);
      $input.removeClass('wp-color-picker');
    }

    $input.val(value);

    $input.wpColorPicker({
      defaultColor: DEFAULT_COLOR
    });

    // wpColorPicker はラベル用のボタンにも色を反映するため、値を再設定しておく
    if (value) {
      $input.wpColorPicker('color', value);
    }
  }

  // クイック編集リンクのクリックを拾う。
  // WordPress 側のハンドラが行を組み立てた後に処理したいので setTimeout で後ろに回す。
  $(document).on('click', '.editinline', function () {
    var $row = $(this).closest('tr');
    var rowId = $row.attr('id') || '';
    var termId = rowId.replace(/^tag-/, '');

    if (!termId) {
      return;
    }

    setTimeout(function () {
      var $editRow = findEditRow(termId);
      if (!$editRow.length) {
        return;
      }

      initColorPicker($editRow.find('.ccp-quick-edit-color'), getCurrentColor($row));

      $editRow
        .find('.ccp-quick-edit-noindex')
        .prop('checked', getCurrentNoindex($row));
    }, 0);
  });
});