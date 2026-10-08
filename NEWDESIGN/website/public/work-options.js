(() => {
  const detail = document.querySelector('[data-bf-work-choice-detail]');
  const inquiryLink = document.querySelector('[data-bf-work-inquiry]');

  if (detail && inquiryLink) {
    const groups = [...detail.querySelectorAll('[data-bf-work-choice-group]')];
    const syncInquiryUrl = () => {
      const url = new URL(inquiryLink.href, window.location.href);
      url.searchParams.set('work', detail.dataset.workId || inquiryLink.dataset.bfWorkId || '');
      for (const group of groups) {
        const key = group.dataset.bfWorkChoiceGroup === 'material' ? 'material_choice' : 'size_choice';
        const selected = group.querySelector('input[type="radio"]:checked');
        if (selected) url.searchParams.set(key, selected.value);
        else url.searchParams.delete(key);
      }
      url.hash = 'inquiry';
      inquiryLink.href = url.toString();
    };

    const setGroupState = (group, selected) => {
      const feedback = group.querySelector('[data-bf-work-choice-feedback]');
      group.classList.toggle('is-selected', Boolean(selected));
      group.classList.remove('is-error');
      group.querySelectorAll('input[type="radio"]').forEach((input) => input.removeAttribute('aria-invalid'));
      if (feedback) feedback.textContent = selected ? `已選擇：${selected.value}` : '選擇後會一併帶入作品詢價。';
    };

    for (const group of groups) {
      group.addEventListener('change', () => {
        setGroupState(group, group.querySelector('input[type="radio"]:checked'));
        syncInquiryUrl();
      });
    }

    inquiryLink.addEventListener('click', (event) => {
      for (const group of groups) {
        if (group.querySelector('input[type="radio"]:checked')) continue;
        event.preventDefault();
        group.classList.add('is-error');
        const feedback = group.querySelector('[data-bf-work-choice-feedback]');
        if (feedback) feedback.textContent = `請先選擇${group.querySelector('legend')?.childNodes[0]?.textContent.replace(/，請選擇一項$/, '').replace(/^選擇/, '').trim() || '作品規格'}。`;
        const firstChoice = group.querySelector('input[type="radio"]');
        firstChoice?.setAttribute('aria-invalid', 'true');
        firstChoice?.focus();
        return;
      }
      syncInquiryUrl();
      inquiryLink.setAttribute('aria-busy', 'true');
    });

    syncInquiryUrl();
  }

  const form = document.querySelector('[data-bf-inquiry-form]');
  const workSelect = form?.querySelector('[data-bf-inquiry-work]');
  if (!form || !workSelect) return;

  const parseOptions = (value) => {
    try {
      const parsed = JSON.parse(value || '[]');
      return Array.isArray(parsed) ? parsed.filter((item) => typeof item === 'string' && item.trim()) : [];
    } catch {
      return [];
    }
  };

  const updateOptionField = (kind, values, keepSelected) => {
    const field = form.querySelector(`[data-bf-inquiry-option-field="${kind}"]`);
    const select = field?.querySelector('[data-bf-inquiry-option-select]');
    if (!field || !select) return;

    const selected = keepSelected ? (select.dataset.selected || select.value) : '';
    const prompt = kind === 'material' ? '請選擇木材／材質／款式' : '請選擇尺寸';
    select.replaceChildren(new Option(prompt, ''));
    for (const value of values) select.add(new Option(value, value));

    const hasChoices = values.length > 0;
    field.hidden = !hasChoices;
    select.disabled = !hasChoices;
    select.required = hasChoices;
    select.removeAttribute('aria-invalid');
    field.classList.remove('is-error', 'is-selected');
    if (hasChoices && values.includes(selected)) {
      select.value = selected;
      field.classList.add('is-selected');
    }
    select.dataset.selected = '';

    const feedback = field.querySelector('[data-bf-inquiry-option-feedback]');
    if (feedback) feedback.textContent = hasChoices
      ? (select.value ? `已帶入：${select.value}；可在送出前調整。` : '請選擇此作品已設定的選項。')
      : '此作品目前沒有設定可選規格。';
  };

  const refreshWorkOptions = (keepSelected) => {
    const option = workSelect.selectedOptions[0];
    updateOptionField('material', parseOptions(option?.dataset.materialOptions), keepSelected);
    updateOptionField('size', parseOptions(option?.dataset.sizeOptions), keepSelected);
  };

  workSelect.addEventListener('change', () => refreshWorkOptions(false));
  for (const field of form.querySelectorAll('[data-bf-inquiry-option-field]')) {
    const select = field.querySelector('[data-bf-inquiry-option-select]');
    select?.addEventListener('change', () => {
      field.classList.toggle('is-selected', Boolean(select.value));
      field.classList.remove('is-error');
      select.removeAttribute('aria-invalid');
      const feedback = field.querySelector('[data-bf-inquiry-option-feedback]');
      if (feedback) feedback.textContent = select.value ? `已選擇：${select.value}` : '請選擇此作品已設定的選項。';
    });
  }
  refreshWorkOptions(true);

  form.addEventListener('invalid', (event) => {
    const select = event.target.closest?.('[data-bf-inquiry-option-select]');
    if (!select) return;
    const field = select.closest('[data-bf-inquiry-option-field]');
    field?.classList.add('is-error');
    select.setAttribute('aria-invalid', 'true');
    const feedback = field?.querySelector('[data-bf-inquiry-option-feedback]');
    if (feedback) feedback.textContent = '請選擇此作品目前提供的規格，再送出詢問。';
  }, true);

  form.addEventListener('submit', () => {
    const submit = form.querySelector('button[type="submit"]');
    if (!submit) return;
    submit.disabled = true;
    submit.setAttribute('aria-busy', 'true');
    submit.textContent = '正在送出詢問…';
  });
})();
