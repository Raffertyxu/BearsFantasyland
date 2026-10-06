(() => {
  // Break Chinese headings between phrases instead of mid-phrase: each run of
  // text ending in ，、：；。！？ (and any trailing remainder) becomes an
  // inline-block .bf-phrase. A phrase wider than the heading still wraps inside
  // its own box. Only direct text nodes are touched; child elements stay as-is.
  const phrasePattern = /[^，、：；。！？]*[，、：；。！？]+|[^，、：；。！？]+/g;
  document.querySelectorAll('#bf-main :is(h1, h2, h3)').forEach((heading) => {
    if (heading.dataset.bfPhrased || heading.hasAttribute('data-no-phrase')) return;
    heading.dataset.bfPhrased = '1';
    Array.from(heading.childNodes).forEach((node) => {
      if (node.nodeType !== Node.TEXT_NODE) return;
      const parts = node.nodeValue.match(phrasePattern) || [];
      if (parts.filter((part) => part.trim()).length < 2) return;
      const fragment = document.createDocumentFragment();
      parts.forEach((part) => {
        if (!part.trim()) { fragment.append(part); return; }
        const span = document.createElement('span');
        span.className = 'bf-phrase';
        span.textContent = part;
        fragment.append(span);
      });
      node.replaceWith(fragment);
    });
  });

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

  // Required on the live site: saved 網站版面 text overrides on the
  // collaboration page are shifted by one row, so the server renders the
  // form note in place of the landline. Remove the misplaced note row and
  // restore the landline until those overrides are re-saved.
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
    const series = document.querySelector('#bf-series');
    const search = document.querySelector('#bf-search');
    const sort = document.querySelector('#bf-sort');
    const count = document.querySelector('#bf-result-count');
    const empty = document.querySelector('#bf-no-results');
    const more = document.querySelector('#bf-load-more');
    const pageSize = 12;
    let activeCategory = 'all';
    let visibleLimit = pageSize;
    const syncPressedState = (buttons) => buttons.forEach((button) => {
      button.setAttribute('aria-pressed', String(button.classList.contains('is-active')));
    });
    syncPressedState(filters);
    if (count) {
      count.setAttribute('aria-live', 'polite');
      count.setAttribute('aria-atomic', 'true');
    }

    const update = () => {
      const term = (search?.value || '').trim().toLocaleLowerCase();
      const matching = items.filter((item) =>
        (activeCategory === 'all' || item.dataset.category === activeCategory) &&
        (!series || series.value === 'all' || item.dataset.seriesFilter === series.value) &&
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
    series?.addEventListener('change', reset);
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

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.querySelectorAll('[data-bfnd-carousel]').forEach((carousel) => {
    const slides = [...carousel.querySelectorAll('[data-bfnd-banner-slide]')];
    const controls = carousel.querySelector('[data-bfnd-banner-controls]');
    if (slides.length < 2 || !controls) return;
    const counter = controls.querySelector('[data-bfnd-banner-current]');
    const previous = controls.querySelector('[data-bfnd-banner-prev]');
    const next = controls.querySelector('[data-bfnd-banner-next]');
    const pause = controls.querySelector('[data-bfnd-banner-pause]');
    let current = 0;
    let timer;
    let pointerInside = false;
    let focusInside = false;
    let manuallyPaused = reducedMotion;

    const stop = () => { if (timer) window.clearInterval(timer); timer = undefined; };
    const schedule = () => {
      stop();
      if (manuallyPaused || pointerInside || focusInside || document.hidden) return;
      const delay = Math.max(4000, Number(carousel.dataset.bfndInterval) || 6000);
      timer = window.setInterval(() => show(current + 1), delay);
    };
    const show = (index) => {
      current = (index + slides.length) % slides.length;
      slides.forEach((slide, slideIndex) => {
        const selected = slideIndex === current;
        slide.classList.toggle('is-active', selected);
        slide.setAttribute('aria-hidden', String(!selected));
      });
      if (counter) counter.textContent = String(current + 1).padStart(2, '0');
      schedule();
    };
    const updatePauseControl = () => {
      if (!pause) return;
      pause.textContent = manuallyPaused ? '▶' : 'Ⅱ';
      pause.setAttribute('aria-label', manuallyPaused ? '繼續輪播' : '暫停輪播');
      pause.setAttribute('aria-pressed', String(manuallyPaused));
    };

    previous?.addEventListener('click', () => show(current - 1));
    next?.addEventListener('click', () => show(current + 1));
    pause?.addEventListener('click', () => {
      manuallyPaused = !manuallyPaused;
      updatePauseControl();
      schedule();
    });
    carousel.addEventListener('pointerenter', () => { pointerInside = true; stop(); });
    carousel.addEventListener('pointerleave', () => { pointerInside = false; schedule(); });
    carousel.addEventListener('focusin', () => { focusInside = true; stop(); });
    carousel.addEventListener('focusout', () => window.setTimeout(() => {
      focusInside = carousel.contains(document.activeElement);
      schedule();
    }, 0));
    document.addEventListener('visibilitychange', schedule);
    controls.hidden = false;
    if (reducedMotion && pause) pause.hidden = true;
    updatePauseControl();
    schedule();
  });
})();
(() => {
  // Product inquiry links ("聯絡我們", "商品諮詢") carry the chosen WooCommerce
  // variation, so the inquiry form can prefill e.g. 木種: 胡桃木.
  const links = document.querySelectorAll('[data-bf-product-inquiry]');
  const $ = window.jQuery;
  if (!links.length || !$) return;
  const setVariation = (id) => links.forEach((link) => {
    const url = new URL(link.href, window.location.href);
    if (id) url.searchParams.set('bf_variation', id); else url.searchParams.delete('bf_variation');
    link.href = url.toString();
  });
  $('form.variations_form')
    .on('found_variation', (event, variation) => setVariation(variation && variation.variation_id))
    .on('reset_data', () => setVariation(''));
})();
