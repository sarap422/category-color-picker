jQuery(document).ready(function ($) {
  var T = window.ccpTools || {};
  var i18n = T.i18n || {};

  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺====================
  // ボタン注入：.search-form の隣（#col-container の手前）
  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺====================
  function injectButtons() {
    if ($('#ccp-tools').length) { return; }
    var $anchor = $('.search-form').first();
    if (!$anchor.length) { return; }

    var $wrap = $('<span id="ccp-tools" class="ccp-tools"></span>');
    var $imp = $('<button type="button" class="button" id="ccp-import"></button>').text(i18n.importBtn || 'Import');
    var $exp = $('<button type="button" class="button" id="ccp-export"></button>').text(i18n.exportBtn || 'Export');
    var $file = $('<input type="file" id="ccp-import-file" accept=".json,application/json" style="display:none;">');

    $wrap.append($imp).append(document.createTextNode(' ')).append($exp).append($file);
    $anchor.after($wrap);
  }

  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺====================
  // エクスポート：AJAX → Blob ダウンロード
  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺====================
  $(document).on('click', '#ccp-export', function (e) {
    e.preventDefault();
    $.post(T.ajaxurl, { action: 'ccp_export_categories', nonce: T.nonce })
      .done(function (res) {
        if (!res || !res.success) {
          window.alert((i18n.genericError || '') + (res && res.data ? res.data : ''));
          return;
        }
        var data = res.data;
        var filename = data.filename || 'category-colors.json';
        var payload = $.extend({}, data);
        delete payload.filename;

        var blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
      })
      .fail(function () { window.alert(i18n.genericError || 'Error'); });
  });

  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺====================
  // インポート：ボタン → 隠し file input をクリック
  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺====================
  $(document).on('click', '#ccp-import', function (e) {
    e.preventDefault();
    $('#ccp-import-file').val('').trigger('click');
  });

  $(document).on('change', '#ccp-import-file', function () {
    var file = this.files && this.files[0];
    if (!file) { return; }
    if (!/\.json$/i.test(file.name)) { window.alert(i18n.invalidFile); return; }

    var reader = new FileReader();
    reader.onload = function (ev) {
      var parsed;
      try {
        parsed = JSON.parse(ev.target.result);
      } catch (err) {
        window.alert(i18n.parseError);
        return;
      }
      if (!parsed || !parsed.categories || !parsed.categories.length) {
        window.alert(i18n.missingItems);
        return;
      }
      if (!window.confirm(i18n.importConfirm)) { return; }

      $.post(T.ajaxurl, {
        action: 'ccp_import_categories',
        nonce: T.nonce,
        data: JSON.stringify(parsed)
      })
        .done(function (res) {
          if (res && res.success) {
            var d = res.data;
            var msg = (d.created || 0) + ' ' + (i18n.createdFmt || 'created') + ', ' +
                      (d.updated || 0) + ' ' + (i18n.updatedFmt || 'updated');
            if (d.errors && d.errors.length) {
              msg += ', ' + d.errors.length + ' ' + (i18n.errorsFmt || 'errors');
            }
            window.alert(msg);
            window.location.reload();
          } else {
            window.alert((i18n.genericError || '') + (res && res.data ? res.data : ''));
          }
        })
        .fail(function () { window.alert(i18n.genericError || 'Error'); });
    };
    reader.onerror = function () { window.alert(i18n.genericError || 'Error'); };
    reader.readAsText(file);
  });

  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺====================
  // 初期化
  // ༻༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶❀༶⊰⟡⊱༶⊰⟡⊱༶⊰⟡⊱༶༺====================
  injectButtons();
});
