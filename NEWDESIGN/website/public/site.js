(() => {
  const menu = document.querySelector('.bf-menu-button');
  const nav = document.querySelector('#bf-nav');
  if (menu && nav) {
    menu.addEventListener('click', () => {
      const open = menu.getAttribute('aria-expanded') !== 'true';
      menu.setAttribute('aria-expanded', String(open));
      nav.classList.toggle('is-open', open);
    });
    nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
      nav.classList.remove('is-open');
      menu.setAttribute('aria-expanded', 'false');
    }));
  }
  const schoolMenu = document.querySelector('.bf-nav-school');
  const schoolToggle = schoolMenu?.querySelector('.bf-nav-school-toggle');
  schoolToggle?.addEventListener('click', () => {
    const open = schoolToggle.getAttribute('aria-expanded') !== 'true';
    schoolToggle.setAttribute('aria-expanded', String(open));
    schoolMenu.classList.toggle('is-open', open);
  });
  schoolMenu?.querySelectorAll('.bf-nav-course-menu a').forEach(link => link.addEventListener('click', () => {
    schoolMenu.classList.remove('is-open');
    schoolToggle?.setAttribute('aria-expanded', 'false');
  }));

  const catalog = document.querySelector('#bf-catalog');
  if (catalog) {
    const items = [...catalog.querySelectorAll('.bf-catalog-item')];
    const filters = [...document.querySelectorAll('[data-filter]')];
    const search = document.querySelector('#bf-search');
    const sort = document.querySelector('#bf-sort');
    const count = document.querySelector('#bf-result-count');
    const empty = document.querySelector('#bf-no-results');
    const more = document.querySelector('#bf-load-more');
    const pageSize = 12;
    let active = 'all';
    let visibleLimit = pageSize;

    const update = () => {
      const term = (search?.value || '').trim().toLocaleLowerCase();
      const matching = items.filter((item) =>
        (active === 'all' || item.dataset.category === active) &&
        (!term || item.dataset.search?.includes(term))
      );
      matching.sort((a, b) => {
        if (sort?.value === 'series') return (a.dataset.series || '').localeCompare(b.dataset.series || '', 'zh-Hant');
        if (sort?.value === 'material') return (a.dataset.material || '').localeCompare(b.dataset.material || '', 'zh-Hant');
        return Number(b.dataset.date) - Number(a.dataset.date);
      });
      const matchingSet = new Set(matching);
      for (const item of items) if (!matchingSet.has(item)) item.hidden = true;
      matching.forEach((item, index) => {
        item.hidden = index >= visibleLimit;
        catalog.append(item);
      });
      const visible = Math.min(visibleLimit, matching.length);
      if (count) count.textContent = `顯示 ${visible} / ${matching.length} 件作品`;
      if (empty) empty.hidden = matching.length !== 0;
      if (more) more.hidden = visible >= matching.length;
    };
    const reset = () => { visibleLimit = pageSize; update(); };
    filters.forEach((button) => button.addEventListener('click', () => {
      active = button.dataset.filter;
      filters.forEach((filter) => filter.classList.toggle('is-active', filter === button));
      reset();
    }));
    search?.addEventListener('input', reset);
    sort?.addEventListener('change', reset);
    more?.addEventListener('click', () => { visibleLimit += pageSize; update(); });
    update();
  }

  const tabs = [...document.querySelectorAll('[data-course-tab]')];
  if (tabs.length) {
    tabs.forEach((tab) => tab.addEventListener('click', () => {
      const target = tab.dataset.courseTab;
      for (const item of tabs) {
        const selected = item === tab;
        item.classList.toggle('is-active', selected);
        item.setAttribute('aria-selected', String(selected));
      }
      document.querySelectorAll('[data-course-group]').forEach((group) => {
        group.hidden = group.dataset.courseGroup !== target;
      });
    }));
    document.querySelectorAll('[data-course-link]').forEach((link) =>
      link.addEventListener('click', () => tabs.find((tab) => tab.dataset.courseTab === link.dataset.courseLink)?.click())
    );
  }

  const dialog = document.querySelector('#bf-image-dialog');
  if (dialog) {
    const zoom = dialog.querySelector('img');
    document.querySelectorAll('.bf-gallery-button').forEach((button) => button.addEventListener('click', () => {
      const image = button.querySelector('img');
      if (image) {
        zoom.src = image.src;
        zoom.alt = image.alt;
        dialog.showModal();
      }
    }));
    dialog.querySelector('button')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
  }
})();
