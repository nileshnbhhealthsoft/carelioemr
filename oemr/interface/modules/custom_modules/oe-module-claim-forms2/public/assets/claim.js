/* Claim review screen. Renders from the form definition (form.json) the server sends,
 * so another insurer's form needs no change here. All text goes in with textContent. */
(function () {
  'use strict';
  var C = window.CLAIMFORMS;
  var root = document.getElementById('claim-app');
  var S = { def: null, payload: {}, icdOptions: [], referrers: [], signature: {}, claimId: C.claimId, formId: C.formId, enc: C.enc || [], status: 'draft' };
  var msgBox, totalSpans = [];

  function h(tag, attrs, kids) {
    var el = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) {
      if (k === 'class') el.className = attrs[k];
      else if (k === 'text') el.textContent = attrs[k];
      else if (k.indexOf('on') === 0) el.addEventListener(k.slice(2), attrs[k]);
      else if (attrs[k] !== false && attrs[k] != null) el.setAttribute(k, attrs[k]);
    });
    (kids || []).forEach(function (c) { if (c) el.appendChild(typeof c === 'string' ? document.createTextNode(c) : c); });
    return el;
  }
  function api(method, params, body) {
    var url = C.api + (params ? '?' + new URLSearchParams(params).toString() : '');
    var opt = { method: method, credentials: 'same-origin' };
    if (body) { body.csrf_token_form = C.csrf; opt.headers = { 'Content-Type': 'application/json' }; opt.body = JSON.stringify(body); }
    return fetch(url, opt).then(function (r) { return r.json().then(function (j) { j._status = r.status; return j; }); });
  }
  function centsToStr(c) { c = parseInt(c, 10) || 0; return c ? (c / 100).toFixed(2) : ''; }
  function strToCents(s) { s = String(s || '').replace(/[,\s$]/g, ''); var n = parseFloat(s); return isNaN(n) ? 0 : Math.round(n * 100); }
  function fmtMoney(c) { return (c / 100).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
  function total() {
    var t = 0; (S.payload.lines || []).forEach(function (l) { t += parseInt(l.amount, 10) || 0; });
    return t + (parseInt(S.payload.further_services_amount, 10) || 0) + (S.payload.has_surgery === 'yes' ? (parseInt(S.payload.surgical_amount, 10) || 0) : 0);
  }
  function refreshTotal() { totalSpans.forEach(function (s) { s.textContent = fmtMoney(total()); }); }
  function visible(f) {
    if (f.showIf) return S.payload[f.showIf[0]] === f.showIf[1];
    if (f.showIfAny) return f.showIfAny.some(function (p) { return S.payload[p[0]] === p[1]; });
    return true;
  }
  function icdSlots() { return (S.payload.icd || []).slice(0, 4); }

  // ---- controls ----
  function field(f) {
    var id = 'f_' + f.key, v = S.payload[f.key];
    var wrap = h('div', { class: 'form-group' });
    var label = h('label', { for: id, text: f.label });
    wrap.appendChild(label);
    switch (f.control) {
      case 'text': case 'date':
        wrap.appendChild(h('input', { class: 'form-control form-control-sm', id: id, type: f.control === 'date' ? 'date' : 'text', value: v || '', oninput: function (e) { S.payload[f.key] = e.target.value; } }));
        break;
      case 'textarea':
        var ta = h('textarea', { class: 'form-control form-control-sm', id: id, rows: f.rows || 2, oninput: function (e) { S.payload[f.key] = e.target.value; } });
        ta.value = v || ''; wrap.appendChild(ta); break;
      case 'radio': case 'yesno':
        var opts = f.control === 'yesno' ? [['yes', 'Yes'], ['no', 'No']] : f.options;
        var grp = h('div');
        opts.forEach(function (o) {
          grp.appendChild(h('div', { class: 'form-check form-check-inline' }, [
            h('input', { class: 'form-check-input', type: 'radio', name: id, id: id + o[0], value: o[0], checked: v === o[0] ? 'checked' : false,
              onchange: function () { S.payload[f.key] = o[0]; render(); } }),
            h('label', { class: 'form-check-label', for: id + o[0], text: o[1] })]));
        });
        wrap.appendChild(grp); break;
      case 'money':
        wrap.appendChild(h('input', { class: 'form-control form-control-sm', id: id, type: 'text', inputmode: 'decimal', style: 'max-width:140px', value: centsToStr(v),
          oninput: function (e) { S.payload[f.key] = strToCents(e.target.value); refreshTotal(); } })); break;
      case 'icd': wrap.appendChild(icdControl()); break;
      case 'cpt': wrap.appendChild(cptControl(f, id)); break;
      case 'referrer': wrap.appendChild(referrerControl(f)); break;
      case 'rows': return rowsControl(f);
      case 'checklist': return checklistControl(f);
      case 'lines': return linesControl(f);
    }
    if (f.hint) wrap.appendChild(h('div', { class: 'hint', text: f.hint }));
    return wrap;
  }

  function icdControl() {
    var box = h('div');
    var slots = icdSlots();
    for (var i = 0; i < 4; i++) (function (i) {
      var sel = h('select', { class: 'form-control form-control-sm mb-1', 'aria-label': 'ICD ' + (i + 1), onchange: function (e) {
        S.payload.icd = icdSlots(); while (S.payload.icd.length < 4) S.payload.icd.push('');
        S.payload.icd[i] = e.target.value; render(); } });
      sel.appendChild(h('option', { value: '', text: (i + 1) + '. ' + C.i18n.none }));
      var labels = S.icdOptions.map(function (o) { return o.label; });
      if (slots[i] && labels.indexOf(slots[i]) < 0) labels.push(slots[i]);
      labels.forEach(function (l) { sel.appendChild(h('option', { value: l, text: (i + 1) + '. ' + l, selected: slots[i] === l ? 'selected' : false })); });
      box.appendChild(sel);
    })(i);
    var results = h('div', { class: 'icd-search-results', style: 'display:none' });
    var timer;
    var search = h('input', { class: 'form-control form-control-sm', type: 'search', placeholder: C.i18n.search, oninput: function (e) {
      clearTimeout(timer); var q = e.target.value;
      timer = setTimeout(function () {
        q = q.trim();
        if (q.length < 2) { results.innerHTML = ''; results.style.display = 'none'; return; }
        function pick(o) {
          var arr = icdSlots(); while (arr.length < 4) arr.push('');
          var at = arr.indexOf(''); if (at < 0 || arr.indexOf(o.label) >= 0) return;
          if (!S.icdOptions.some(function (x) { return x.label === o.label; })) S.icdOptions.push({ code: o.code, label: o.label });
          arr[at] = o.label; S.payload.icd = arr; render();
        }
        api('GET', { action: 'icd', q: q }).then(function (r) {
          results.innerHTML = ''; results.style.display = 'block';
          (r.results || []).forEach(function (o) { results.appendChild(h('div', { text: o.text ? o.label + ' ' + o.text : o.label, onclick: function () { pick(o); } })); });
          if (!(r.results || []).length) results.appendChild(h('div', { class: 'text-muted', text: C.i18n.noMatch }));
          var typed = q.toUpperCase();
          results.appendChild(h('div', { class: 'font-italic', text: C.i18n.useTyped + ' ' + typed, onclick: function () { pick({ code: typed, label: typed }); } }));
        }).catch(function () {
          results.innerHTML = ''; results.style.display = 'block';
          results.appendChild(h('div', { class: 'text-danger', text: C.i18n.searchFailed }));
        });
      }, 250);
    } });
    box.appendChild(search); box.appendChild(results);
    return box;
  }

  /** Type-ahead dropdown of CPT4/HCPCS codes; anything typed is kept, so uncoded operations still work. */
  function cptControl(f, id) {
    var box = h('div', { class: 'cpt-picker' });
    var list = h('div', { class: 'icd-search-results', style: 'display:none' });
    var timer;
    function load(q) {
      api('GET', { action: 'cpt', q: q }).then(function (r) {
        list.innerHTML = '';
        (r.results || []).forEach(function (o) {
          list.appendChild(h('div', { text: o.label, onmousedown: function (e) { e.preventDefault(); S.payload[f.key] = o.label; input.value = o.label; list.style.display = 'none'; } }));
        });
        if (!(r.results || []).length) list.appendChild(h('div', { class: 'text-muted', text: C.i18n.noCpt }));
        list.style.display = 'block';
      }).catch(function () { list.style.display = 'none'; });
    }
    var input = h('input', { class: 'form-control form-control-sm', id: id, type: 'text', autocomplete: 'off', placeholder: C.i18n.cptHint, value: S.payload[f.key] || '',
      onfocus: function (e) { load(''); },
      onblur: function () { setTimeout(function () { list.style.display = 'none'; }, 150); },
      oninput: function (e) { S.payload[f.key] = e.target.value; clearTimeout(timer); var q = e.target.value; timer = setTimeout(function () { load(q); }, 250); } });
    box.appendChild(input); box.appendChild(list);
    return box;
  }

  function referrerControl(f) {
    var box = h('div');
    var sel = h('select', { class: 'form-control form-control-sm', onchange: function (e) {
      var opt = e.target.selectedOptions[0];
      S.payload[f.idKey] = e.target.value; S.payload[f.key] = e.target.value ? opt.textContent : ''; if (!e.target.value) other.focus(); } });
    sel.appendChild(h('option', { value: '', text: C.i18n.none }));
    S.referrers.forEach(function (r) {
      sel.appendChild(h('option', { value: r.id, text: r.name + (r.detail ? ' (' + r.detail + ')' : ''), selected: String(S.payload[f.idKey]) === r.id ? 'selected' : false }));
    });
    var other = h('input', { class: 'form-control form-control-sm mt-1', type: 'text', placeholder: 'Or type a name', value: S.payload[f.key] || '',
      oninput: function (e) { S.payload[f.key] = e.target.value; S.payload[f.idKey] = ''; sel.value = ''; } });
    box.appendChild(sel); box.appendChild(other); return box;
  }

  /** One or more drop-downs (each with plain or grouped choices) plus an "Other" text box, all editing one value.
   *  A choice is a string, or [value, label, description]; picking one from any drop-down clears the others, so a row
   *  holds a single value. A column with `describes: 'col'` also fills that column from the description. */
  function selectCell(r, c) {
    var box = h('div');
    var sets = c.sets || [{ placeholder: '- choose -', groups: c.groups }];
    function val(o) { return Array.isArray(o) ? o[0] : o; }
    function flat(set) {
      var out = [];
      (set.groups || [{ options: set.options }]).forEach(function (g) { g.options.forEach(function (o) { out.push(val(o)); }); });
      return out;
    }
    var cur = r[c.key] || '';
    var known = sets.some(function (st) { return flat(st).indexOf(cur) >= 0; });
    var isOther = cur !== '' && !known;
    var selects = [];
    var other = h('input', { class: 'form-control form-control-sm mt-1', type: 'text', placeholder: 'Other', value: isOther ? cur : '',
      style: isOther ? '' : 'display:none', oninput: function (e) { r[c.key] = e.target.value; } });
    function descOf(v) {
      var d = '';
      sets.forEach(function (st) { (st.groups || [{ options: st.options }]).forEach(function (g) { g.options.forEach(function (o) { if (val(o) === v && Array.isArray(o) && o[2]) d = o[2]; }); }); });
      return d;
    }
    function clearOthers(except) { selects.forEach(function (x) { if (x !== except) x.value = ''; }); }
    sets.forEach(function (st) {
      var sel = h('select', { class: 'form-control form-control-sm mb-1', onchange: function (e) {
        clearOthers(sel);
        if (e.target.value === '__other') { r[c.key] = other.value; other.style.display = ''; other.focus(); }
        else {
          r[c.key] = e.target.value; other.style.display = 'none'; other.value = '';
          var d = c.describes && descOf(e.target.value);
          if (d && !r[c.describes]) { r[c.describes] = d; render(); }
        }
      } });
      sel.appendChild(h('option', { value: '', text: st.placeholder }));
      (st.groups || [{ options: st.options }]).forEach(function (g) {
        var parent = g.label ? h('optgroup', { label: g.label }) : sel;
        g.options.forEach(function (o) {
          var v = val(o), l = Array.isArray(o) ? (o[1] || o[0]) : o;
          parent.appendChild(h('option', { value: v, text: l, selected: cur === v ? 'selected' : false }));
        });
        if (g.label) sel.appendChild(parent);
      });
      selects.push(sel); box.appendChild(sel);
    });
    if (c.other) selects[selects.length - 1].appendChild(h('option', { value: '__other', text: 'Other...', selected: isOther ? 'selected' : false }));
    box.appendChild(other);
    return box;
  }

  /** One-cell ICD-10 picker for optometry / ophthalmology rows. Type-ahead; only the code is kept. */
  function eyeIcdInput(r, key, scope) {
    var box = h('div', { style: 'position:relative' });
    var list = h('div', { class: 'icd-search-results', style: 'display:none;position:absolute;z-index:20;min-width:320px' });
    var timer;
    function load(q) {
      api('GET', scope ? { action: 'icd', scope: scope, q: q } : { action: 'icd', q: q }).then(function (res) {
        list.innerHTML = '';
        (res.results || []).forEach(function (o) {
          list.appendChild(h('div', { text: o.text ? o.code + ' ' + o.text : o.code, onmousedown: function (e) { e.preventDefault(); r[key] = o.code; input.value = o.code; list.style.display = 'none'; } }));
        });
        list.style.display = (res.results || []).length ? 'block' : 'none';
      }).catch(function () { list.style.display = 'none'; });
    }
    var input = h('input', { class: 'form-control form-control-sm', type: 'text', autocomplete: 'off', placeholder: 'ICD-10 code', value: r[key] || '',
      onfocus: function (e) { load(''); },
      onblur: function () { setTimeout(function () { list.style.display = 'none'; }, 150); },
      oninput: function (e) { r[key] = e.target.value.toUpperCase(); clearTimeout(timer); var q = e.target.value; timer = setTimeout(function () { load(q); }, 250); } });
    box.appendChild(input); box.appendChild(list);
    return box;
  }

  /** A printed price list: tick the items done and enter a fee for each. Items can be grouped. */
  function checklistControl(f) {
    var wrap = h('div', { class: 'form-group' }, [h('label', { text: f.label })]);
    var data = S.payload[f.key] = S.payload[f.key] || {};
    var tbl = h('table', { class: 'table table-sm lines' });
    tbl.appendChild(h('thead', {}, [h('tr', {}, ['', f.codeLabel || 'Code', 'Description', 'Fee'].map(function (t) { return h('th', { text: t }); }))]));
    var body = h('tbody');
    (f.groups || [{ items: f.items }]).forEach(function (g) {
      if (g.title) body.appendChild(h('tr', { class: 'table-active' }, [h('td', { colspan: '4', class: 'font-weight-bold', text: g.title })]));
      g.items.forEach(function (it) {
        var d = data[it.id] = data[it.id] || { on: '', fee: 0 };
        var chk = h('input', { type: 'checkbox', checked: d.on ? 'checked' : false, 'aria-label': it.label, onchange: function (e) { d.on = e.target.checked ? '1' : ''; } });
        var fee = h('input', { class: 'form-control form-control-sm', type: 'text', inputmode: 'decimal', style: 'max-width:100px', value: centsToStr(d.fee),
          oninput: function (e) { d.fee = strToCents(e.target.value); if (d.fee > 0 && !d.on) { d.on = '1'; chk.checked = true; } refreshTotal(); } });
        body.appendChild(h('tr', {}, [h('td', {}, [chk]), h('td', { text: it.code || '' }), h('td', { text: it.label }), h('td', {}, [fee])]));
      });
    });
    tbl.appendChild(body); wrap.appendChild(tbl);
    return wrap;
  }

  /** A small table of rows. `fixed` rows are pre-labelled and cannot be added or removed. */
  function rowsControl(f) {
    var wrap = h('div', { class: 'form-group' }, [h('label', { text: f.label })]);
    var rows = S.payload[f.key] = S.payload[f.key] || [];
    if (f.fixed) while (rows.length < f.fixed.length) rows.push({});
    var tbl = h('table', { class: 'table table-sm lines' });
    tbl.appendChild(h('thead', {}, [h('tr', {}, (f.fixed ? [''] : []).concat(f.columns.map(function (c) { return c.label; })).concat(f.fixed ? [] : ['']).map(function (t) { return h('th', { text: t }); }))]));
    var body = h('tbody');
    var count = f.fixed ? f.fixed.length : rows.length;
    for (var i = 0; i < count; i++) (function (i) {
      var r = rows[i], cells = [];
      if (f.fixed) cells.push(h('td', { text: f.fixed[i] }));
      f.columns.forEach(function (c) {
        var inp = c.type === 'icd_eye' ? eyeIcdInput(r, c.key, 'eye') : c.type === 'icd' ? eyeIcdInput(r, c.key, '') : c.type === 'select' ? selectCell(r, c) : c.type === 'money'
          ? h('input', { class: 'form-control form-control-sm', type: 'text', inputmode: 'decimal', style: 'max-width:110px', value: centsToStr(r[c.key]), oninput: function (e) { r[c.key] = strToCents(e.target.value); } })
          : h('input', { class: 'form-control form-control-sm', type: c.type === 'date' ? 'date' : 'text', value: r[c.key] || '', oninput: function (e) { r[c.key] = e.target.value; } });
        cells.push(h('td', {}, [inp]));
      });
      if (!f.fixed) cells.push(h('td', {}, [h('button', { class: 'btn btn-link btn-sm text-danger', type: 'button', text: C.i18n.remove, onclick: function () { rows.splice(i, 1); render(); } })]));
      body.appendChild(h('tr', {}, cells));
    })(i);
    tbl.appendChild(body); wrap.appendChild(tbl);
    if (!f.fixed) {
      var max = f.max || 10;
      if (rows.length < max) wrap.appendChild(h('button', { class: 'btn btn-outline-secondary btn-sm', type: 'button', text: C.i18n.add, onclick: function () { rows.push({}); render(); } }));
      else wrap.appendChild(h('div', { class: 'hint', text: 'The form has room for ' + max + ' rows.' }));
    }
    return wrap;
  }

  var SERVICE_TYPES = ['Visit', 'Consultation', 'Follow-up', 'Procedure'];
  function linesControl(f) {
    var wrap = h('div', { class: 'form-group' }, [h('label', { text: f.label })]);
    var lines = S.payload.lines = S.payload.lines || [];
    var types = h('datalist', { id: 'claimforms-service-types' });
    SERVICE_TYPES.forEach(function (t) { types.appendChild(h('option', { value: t })); });
    wrap.appendChild(types);
    var tbl = h('table', { class: 'table table-sm lines' });
    tbl.appendChild(h('thead', {}, [h('tr', {}, ['Date', 'Place', 'Procedure or service', 'Dx', 'Charge', ''].map(function (t) { return h('th', { text: t }); }))]));
    var body = h('tbody');
    lines.forEach(function (l, i) {
      var place = h('select', { class: 'form-control form-control-sm', onchange: function (e) { l.place = e.target.value; } });
      ['Office', 'Home', 'Hosp.'].forEach(function (p) { place.appendChild(h('option', { value: p, text: p, selected: l.place === p ? 'selected' : false })); });
      var dx = h('select', { class: 'form-control form-control-sm', onchange: function (e) { l.dx = e.target.value; } });
      icdSlots().forEach(function (code, n) { if (code) dx.appendChild(h('option', { value: String(n + 1), text: String(n + 1), selected: String(l.dx) === String(n + 1) ? 'selected' : false })); });
      body.appendChild(h('tr', {}, [
        h('td', {}, [h('input', { class: 'form-control form-control-sm', type: 'date', value: l.date || '', oninput: function (e) { l.date = e.target.value; } })]),
        h('td', {}, [place]),
        h('td', {}, [h('input', { class: 'form-control form-control-sm', type: 'text', list: 'claimforms-service-types', placeholder: 'Visit, Consultation, Follow-up, Procedure...', value: l.desc || '', oninput: function (e) { l.desc = e.target.value; } })]),
        h('td', { style: 'width:70px' }, [dx]),
        h('td', { style: 'width:120px' }, [h('input', { class: 'form-control form-control-sm', type: 'text', inputmode: 'decimal', value: centsToStr(l.amount), oninput: function (e) { l.amount = strToCents(e.target.value); refreshTotal(); } })]),
        h('td', {}, [h('button', { class: 'btn btn-link btn-sm text-danger', type: 'button', text: C.i18n.remove, onclick: function () { lines.splice(i, 1); render(); } })])
      ]));
    });
    tbl.appendChild(body); wrap.appendChild(tbl);
    var max = S.def.maxLineItems || 3;
    if (lines.length > max) wrap.appendChild(h('div', { class: 'errors', text: C.i18n.tooMany }));
    wrap.appendChild(h('button', { class: 'btn btn-outline-secondary btn-sm', type: 'button', text: C.i18n.add, onclick: function () {
      lines.push({ date: new Date().toISOString().slice(0, 10), place: 'Office', desc: '', dx: '1', amount: 0 }); render(); } }));
    return wrap;
  }

  // ---- page ----
  function render() {
    totalSpans = [];
    root.innerHTML = '';
    root.appendChild(h('h4', { text: S.def.title + (S.claimId ? ' #' + S.claimId : '') + (S.status !== 'draft' ? ' (' + S.status + ')' : '') }));
    S.def.sections.forEach(function (sec) {
      var body = h('div', { class: 'body' });
      if (sec.toggle) {
        var tk = sec.toggle.key;
        if (!S.payload[tk]) S.payload[tk] = sec.fields.some(function (f) { var v = S.payload[f.key]; return Array.isArray(v) ? v.some(function (r) { return Object.keys(r).some(function (k) { return r[k] && r[k] !== 0; }); }) : (v !== undefined && v !== '' && v !== 0); }) ? 'yes' : 'no';
        var on = S.payload[tk] === 'yes';
        body.appendChild(h('div', { class: 'form-check mb-2' }, [
          h('input', { class: 'form-check-input', type: 'checkbox', id: 'tg_' + tk, checked: on ? 'checked' : false,
            onchange: function (e) { S.payload[tk] = e.target.checked ? 'yes' : 'no'; render(); } }),
          h('label', { class: 'form-check-label font-weight-bold', for: 'tg_' + tk, text: sec.toggle.label })]));
      }
      if (!sec.toggle || S.payload[sec.toggle.key] === 'yes') {
        sec.fields.forEach(function (f) { if (visible(f)) body.appendChild(field(f)); });
      }
      if (sec.note) body.appendChild(h('div', { class: 'hint', text: sec.note }));
      root.appendChild(h('div', { class: 'section' }, [h('h5', { text: sec.title }), body]));
    });
    var tot = h('span', { class: 'total' }); totalSpans.push(tot);
    if (S.def.showTotal !== false) root.appendChild(h('div', { class: 'section' }, [h('div', { class: 'body' }, [h('span', { text: 'Total: ' }), tot])]));
    refreshTotal();
    msgBox = h('div', { class: 'errors' });
    var actions = h('div', { class: 'actions' }, [
      h('button', { class: 'btn btn-secondary mr-2', type: 'button', text: C.i18n.save, onclick: function () { save().then(function (ok) { if (ok) say(C.i18n.saved, false); }); } }),
      h('button', { class: 'btn btn-primary mr-2', type: 'button', text: C.i18n.generate, onclick: generate }),
      h('a', { href: 'index.php', text: '\u2190 Claims' }),
      msgBox
    ]);
    if (!S.signature.signature) msgBox.textContent = C.i18n.noSignature;
    root.appendChild(actions);
  }
  function say(text, isError, list) {
    msgBox.innerHTML = '';
    msgBox.style.color = isError ? '#a00' : '#060';
    msgBox.appendChild(document.createTextNode(text || ''));
    (list || []).forEach(function (m) { msgBox.appendChild(h('div', { text: '\u2022 ' + m })); });
  }
  function save() {
    return api('POST', null, { action: 'save', form: S.formId, claimId: S.claimId, encounters: S.enc, payload: S.payload }).then(function (r) {
      if (r.ok) { S.claimId = r.claimId; return true; }
      say(r.error || 'Could not save', true); return false;
    });
  }
  function generate() {
    say(C.i18n.generating, false);
    save().then(function (ok) {
      if (!ok) return;
      api('POST', null, { action: 'generate', claimId: S.claimId }).then(function (r) {
        if (r.ok) {
          S.status = 'generated';
          say('', false);
          msgBox.appendChild(h('a', { href: r.downloadUrl, target: '_blank', text: C.i18n.open }));
          (r.warnings || []).forEach(function (w) { msgBox.appendChild(h('div', { class: 'errors', text: '\u2022 ' + w })); });
        } else say(r.error || 'The claim needs attention:', true, r.errors);
      });
    });
  }

  var load = C.claimId ? api('GET', { action: 'claim', id: C.claimId }) : api('GET', { action: 'draft', form: C.formId, enc: (C.enc || []).join(',') });
  load.then(function (r) {
    if (r.error) { root.textContent = r.error; return; }
    S.def = r.definition; S.payload = r.payload; S.icdOptions = r.icdOptions || []; S.referrers = r.referrers || [];
    S.signature = r.signature || {}; S.claimId = r.claimId; S.formId = r.formId; S.enc = r.encounters || []; S.status = r.status;
    render();
  }).catch(function () { root.textContent = 'Could not load the claim.'; });
})();
