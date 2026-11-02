/* Study builder for the radiology requisition. Vanilla JS, no Bootstrap data-API, no browser storage.
   Everything is built with DOM APIs (textContent / value), never innerHTML, so catalog or chart text cannot inject markup. */
(function () {
  var cfg = window.RADREQ;
  var cat = cfg.catalog;
  var root = document.getElementById('radreq-studies');
  var seq = 0;

  function el(tag, attrs, text) {
    var n = document.createElement(tag);
    Object.keys(attrs || {}).forEach(function (k) { n.setAttribute(k, attrs[k]); });
    if (text !== undefined) { n.textContent = text; }
    return n;
  }

  function select(name, options, value) {
    var s = el('select', { name: name });
    options.forEach(function (o) {
      var v = Array.isArray(o) ? o[0] : o;
      var t = Array.isArray(o) ? o[1] : o;
      var opt = el('option', { value: v }, t);
      if (v === value) { opt.selected = true; }
      s.appendChild(opt);
    });
    return s;
  }

  function input(name, type, value, max) {
    var i = el('input', { type: type || 'text', name: name });
    if (max) { i.maxLength = max; }
    i.value = value || '';
    return i;
  }

  function block(label, control, cls) {
    var d = el('div', cls ? { 'class': cls } : {});
    d.appendChild(el('label', {}, label));
    d.appendChild(control);
    return d;
  }

  function addStudy(data) {
    data = data || {};
    var i = seq++;
    var p = 'studies[' + i + ']';
    var box = el('article', { 'class': 'study' });
    var head = el('div', { 'class': 'studyhead' });
    var title = el('h3', {}, '');
    var del = el('button', { type: 'button', 'class': 'btn btn-sm btn-outline-danger noprint' }, cfg.i18n.remove);
    del.addEventListener('click', function () { box.parentNode.removeChild(box); renumber(); });
    head.appendChild(title);
    head.appendChild(del);
    box.appendChild(head);

    var grid = el('div', { 'class': 'grid' });
    var modality = select(p + '[modality]', Object.keys(cat.exams), data.modality || 'X-ray');
    var exam = select(p + '[examination]', [], '');
    var side = select(p + '[laterality]', cat.laterality, data.laterality || cat.laterality[0]);
    var contrast = select(p + '[contrast_protocol]', cat.contrast, data.contrast_protocol || '');
    grid.appendChild(block(cfg.i18n.modality, modality));
    grid.appendChild(block(cfg.i18n.examination, exam));
    grid.appendChild(block(cfg.i18n.laterality, side));
    grid.appendChild(block(cfg.i18n.contrast, contrast));
    grid.appendChild(block(cfg.i18n.instructions, input(p + '[instructions]', 'text', data.instructions, 255), 'span2'));
    grid.appendChild(block(cfg.i18n.otherExam, input(p + '[other_examination]', 'text', data.other_examination, 255), 'span2'));
    box.appendChild(grid);

    var safe = el('div', { 'class': 'safety' });
    box.appendChild(safe);

    var lab = el('div', { 'class': 'grid' });
    lab.appendChild(block(cfg.i18n.creatinine, input(p + '[creatinine]', 'text', data.creatinine, 64)));
    lab.appendChild(block(cfg.i18n.egfr, input(p + '[egfr]', 'text', data.egfr, 32)));
    lab.appendChild(block(cfg.i18n.renalDate, input(p + '[renal_date]', 'date', data.renal_date)));
    lab.appendChild(block(cfg.i18n.safetyNotes, input(p + '[safety_notes]', 'text', data.safety_notes, 255)));
    box.appendChild(lab);

    function fillExams(keep) {
      exam.textContent = '';
      cat.exams[modality.value].forEach(function (name) {
        var o = el('option', { value: name }, name);
        if (name === keep) { o.selected = true; }
        exam.appendChild(o);
      });
    }

    function fillSafety(answers) {
      safe.textContent = '';
      safe.appendChild(el('h4', {}, modality.value + ' ' + cfg.i18n.safetyTitle));
      (cat.safety[modality.value] || []).forEach(function (q, qi) {
        var row = el('div', { 'class': 'question' });
        row.appendChild(el('label', {}, q));
        var opts = Object.keys(cat.answers).map(function (k) { return [k, cat.answers[k]]; });
        row.appendChild(select(p + '[safety][' + qi + ']', opts, (answers && answers[qi]) || 'unknown'));
        safe.appendChild(row);
      });
    }

    function applyModality(initial) {
      fillExams(initial ? data.examination : '');
      fillSafety(initial ? data.safety : null);
      lab.style.display = cat.renal.indexOf(modality.value) >= 0 ? '' : 'none';
      if (!initial || !data.contrast_protocol) {
        contrast.value = cat.contrastDefault.indexOf(modality.value) >= 0 ? 'Radiologist to determine' : 'Not applicable';
      }
    }

    modality.addEventListener('change', function () { applyModality(false); });
    applyModality(true);
    root.appendChild(box);
    renumber();
  }

  function renumber() {
    var boxes = root.querySelectorAll('.study');
    for (var n = 0; n < boxes.length; n++) {
      boxes[n].querySelector('h3').textContent = cfg.i18n.study + ' ' + (n + 1);
    }
  }

  document.getElementById('radreq-add').addEventListener('click', function () {
    if (root.querySelectorAll('.study').length >= cfg.max) { window.alert(cfg.i18n.tooMany); return; }
    addStudy({});
  });

  (cfg.studies.length ? cfg.studies : [{}]).forEach(addStudy);
})();
