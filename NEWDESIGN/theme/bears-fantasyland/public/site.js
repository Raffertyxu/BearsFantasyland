(() => {
  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-bf-youtube-play]');
    if (!trigger) return;
    const frame = trigger.closest('[data-bf-youtube-id]');
    const videoId = frame?.dataset.bfYoutubeId || '';
    if (!frame || !/^[A-Za-z0-9_-]{11}$/.test(videoId)) return;
    event.preventDefault();
    const iframe = document.createElement('iframe');
    iframe.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(videoId)}?autoplay=1&rel=0`;
    iframe.title = '飛熊入夢品牌影片';
    iframe.allow = 'autoplay; encrypted-media; picture-in-picture; web-share';
    iframe.referrerPolicy = 'strict-origin-when-cross-origin';
    iframe.allowFullscreen = true;
    frame.replaceChildren(iframe);
    frame.classList.add('is-playing');
    iframe.focus();
  });

  // Keep the published phone contact visible if an older server-rendered
  // template is still cached after a theme update.
  document.querySelectorAll('.bf-inquiry-grid').forEach((section) => {
    const contact = section.querySelector('.bf-direct-contact');
    if (!contact) return;
    const note = section.querySelector('.bf-form-note')?.textContent.trim();
    contact.querySelectorAll('.bf-direct-contact-row').forEach((row) => {
      if (note && row.textContent.trim() === note) row.remove();
    });
    if (!contact.querySelector('a[href="tel:+886424616373"]')) {
      const row = document.createElement('p');
      row.className = 'bf-direct-contact-row';
      const label = document.createElement('span');
      label.textContent = '電話';
      const link = document.createElement('a');
      link.href = 'tel:+886424616373';
      link.textContent = '04-24616373';
      row.append(label, link);
      contact.prepend(row);
    }
  });

  const schoolMenu = document.querySelector('.bf-nav-school');
  const schoolToggle = schoolMenu?.querySelector('.bf-nav-school-toggle');
  const menu = document.querySelector('.bf-menu-button');
  const nav = document.querySelector('#bf-nav');
  const setSchoolMenuOpen = (open) => {
    if (!schoolMenu || !schoolToggle) return;
    schoolMenu.classList.toggle('is-open', open);
    schoolToggle.setAttribute('aria-expanded', String(open));
  };
  const setNavOpen = (open) => {
    if (!menu || !nav) return;
    nav.classList.toggle('is-open', open);
    menu.setAttribute('aria-expanded', String(open));
    if (!open) setSchoolMenuOpen(false);
  };

  if (menu && nav) {
    menu.addEventListener('click', () => setNavOpen(menu.getAttribute('aria-expanded') !== 'true'));
    nav.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => {
      setSchoolMenuOpen(false);
      setNavOpen(false);
    }));
  }
  schoolToggle?.addEventListener('click', () => {
    setSchoolMenuOpen(schoolToggle.getAttribute('aria-expanded') !== 'true');
  });
  document.addEventListener('pointerdown', (event) => {
    if (nav?.classList.contains('is-open') && !nav.contains(event.target) && !menu?.contains(event.target)) {
      const focusWasInNav = nav.contains(document.activeElement);
      setNavOpen(false);
      if (focusWasInNav) menu?.focus({ preventScroll: true });
    }
    if (schoolMenu?.classList.contains('is-open') && !schoolMenu.contains(event.target)) {
      const focusWasInSubmenu = schoolMenu.contains(document.activeElement);
      setSchoolMenuOpen(false);
      if (focusWasInSubmenu) schoolToggle?.focus({ preventScroll: true });
    }
  });
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    if (schoolMenu?.classList.contains('is-open')) {
      setSchoolMenuOpen(false);
      schoolToggle?.focus();
    } else if (nav?.classList.contains('is-open')) {
      setNavOpen(false);
      menu?.focus();
    }
  });

  const catalog = document.querySelector('#bf-catalog');
  if (catalog) {
    const items = [...catalog.querySelectorAll('.bf-catalog-item')];
    const filters = [...document.querySelectorAll('[data-filter]')];
    const seriesFilters = [...document.querySelectorAll('[data-series-filter]')];
    const search = document.querySelector('#bf-search');
    const sort = document.querySelector('#bf-sort');
    const count = document.querySelector('#bf-result-count');
    const empty = document.querySelector('#bf-no-results');
    const more = document.querySelector('#bf-load-more');
    const pageSize = 12;
    let activeCategory = 'all';
    let activeSeries = 'all';
    let visibleLimit = pageSize;
    const syncPressedState = (buttons) => buttons.forEach((button) => {
      button.setAttribute('aria-pressed', String(button.classList.contains('is-active')));
    });
    syncPressedState(filters);
    syncPressedState(seriesFilters);
    if (count) {
      count.setAttribute('aria-live', 'polite');
      count.setAttribute('aria-atomic', 'true');
    }

    const update = () => {
      const term = (search?.value || '').trim().toLocaleLowerCase();
      const matching = items.filter((item) =>
        (activeCategory === 'all' || item.dataset.category === activeCategory) &&
        (activeSeries === 'all' || item.dataset.seriesFilter === activeSeries) &&
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
      activeCategory = button.dataset.filter;
      filters.forEach((filter) => filter.classList.toggle('is-active', filter === button));
      syncPressedState(filters);
      reset();
    }));
    seriesFilters.forEach((button) => button.addEventListener('click', () => {
      activeSeries = button.dataset.seriesFilter;
      seriesFilters.forEach((filter) => filter.classList.toggle('is-active', filter === button));
      syncPressedState(seriesFilters);
      reset();
    }));
    search?.addEventListener('input', reset);
    sort?.addEventListener('change', reset);
    more?.addEventListener('click', () => {
      visibleLimit += pageSize;
      update();
      if (more.hidden) items.filter((item) => !item.hidden).at(-1)?.querySelector('a')?.focus({ preventScroll: true });
    });
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
    let lastTrigger = null;
    document.querySelectorAll('.bf-gallery-button').forEach((button) => button.addEventListener('click', () => {
      const image = button.querySelector('img');
      if (image) {
        lastTrigger = button;
        zoom.src = image.src;
        zoom.alt = image.alt;
        dialog.showModal();
      }
    }));
    dialog.querySelector('button')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
    dialog.addEventListener('close', () => {
      lastTrigger?.focus({ preventScroll: true });
      lastTrigger = null;
    });
  }
})();
