jQuery(document).ready(function($) {
  // カラーピッカーを初期化
  $('.category-color-picker').wpColorPicker({
      // カラーピッカーのオプション
      defaultColor: '#002A7B',
      change: function(event, ui) {
          // 色が変更された時の処理（必要に応じて）
          console.log('Category color changed:', ui.color.toString());
      },
      clear: function() {
          // クリアボタンが押された時の処理
          console.log('Category color cleared');
      }
  });
});