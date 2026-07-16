// ---------- Mini rich-text editor ----------
document.querySelectorAll('[data-editor]').forEach(area => {
  const hidden = document.getElementById(area.dataset.editor);
  const bar = document.createElement('div');
  bar.className = 'editor-toolbar';
  const tools = [
    ['B', () => document.execCommand('bold'), 'ตัวหนา'],
    ['I', () => document.execCommand('italic'), 'ตัวเอียง'],
    ['U', () => document.execCommand('underline'), 'ขีดเส้นใต้'],
    ['H3', () => document.execCommand('formatBlock', false, 'h3'), 'หัวข้อ'],
    ['• รายการ', () => document.execCommand('insertUnorderedList'), 'รายการ'],
    ['1. ลำดับ', () => document.execCommand('insertOrderedList'), 'ลำดับเลข'],
    ['🔗 ลิงก์', () => { const u = prompt('URL ของลิงก์:'); if (u) document.execCommand('createLink', false, u); }, 'แทรกลิงก์'],
    ['🖼 รูป', () => { const u = prompt('URL ของรูปภาพ (อัปโหลดผ่านช่องรูปด้านล่างก่อน แล้วคัดลอกลิงก์มาวาง):'); if (u) document.execCommand('insertImage', false, u); }, 'แทรกรูป'],
    ['⌫ ล้างรูปแบบ', () => document.execCommand('removeFormat'), 'ล้างรูปแบบ'],
  ];
  tools.forEach(([label, fn, title]) => {
    const b = document.createElement('button');
    b.type = 'button'; b.textContent = label; b.title = title;
    b.onclick = () => { area.focus(); fn(); sync(); };
    bar.appendChild(b);
  });
  area.parentNode.insertBefore(bar, area);
  const sync = () => { hidden.value = area.innerHTML; };
  area.addEventListener('input', sync);
  area.closest('form')?.addEventListener('submit', sync);
});

// ---------- Confirm delete ----------
document.querySelectorAll('form[data-confirm]').forEach(f => {
  f.addEventListener('submit', e => {
    if (!confirm(f.dataset.confirm || 'ยืนยันการลบ?')) e.preventDefault();
  });
});

// ---------- Drag-sort tables (บันทึกลำดับอัตโนมัติ) ----------
document.querySelectorAll('tbody[data-sortable]').forEach(tbody => {
  let dragging = null;
  tbody.querySelectorAll('tr').forEach(tr => {
    tr.draggable = true;
    tr.addEventListener('dragstart', () => { dragging = tr; tr.classList.add('dragging'); });
    tr.addEventListener('dragend', () => {
      tr.classList.remove('dragging');
      const ids = [...tbody.querySelectorAll('tr')].map(r => r.dataset.id);
      const fd = new FormData();
      fd.append('csrf', tbody.dataset.csrf);
      fd.append('order', ids.join(','));
      fetch(tbody.dataset.sortable, { method: 'POST', body: fd });
    });
    tr.addEventListener('dragover', e => {
      e.preventDefault();
      if (!dragging || dragging === tr) return;
      const rect = tr.getBoundingClientRect();
      const after = e.clientY > rect.top + rect.height / 2;
      tbody.insertBefore(dragging, after ? tr.nextSibling : tr);
    });
  });
});
