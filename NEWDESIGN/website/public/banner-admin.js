(() => {
  if (!window.wp?.media) return;

  document.querySelectorAll('[data-bfnd-banner-gallery]').forEach((gallery) => {
    const input = gallery.querySelector('input[type="hidden"]');
    const list = gallery.querySelector('.bfnd-banner-items');
    const pick = gallery.querySelector('[data-pick-banner]');
    if (!input || !list || !pick) return;
    let frame;

    const sync = () => {
      input.value = [...list.querySelectorAll('[data-id]')].map((item) => item.dataset.id).join(',');
      const count = gallery.querySelector('.bfnd-banner-panel-head span');
      if (count) count.textContent = `${list.querySelectorAll('[data-id]').length} 張`;
    };

    const append = (media) => {
      const existing = new Set([...list.querySelectorAll('[data-id]')].map((item) => item.dataset.id));
      media.filter((item) => !/(editorial|wooden-cup)/i.test(item.url || '') && !String(item.alt || '').includes('示意'))
        .filter((item) => !existing.has(String(item.id)))
        .slice(0, Math.max(0, 10 - existing.size))
        .forEach((item) => {
          const card = document.createElement('article');
          card.className = 'bfnd-banner-item';
          card.dataset.id = String(item.id);
          const image = document.createElement('img');
          image.src = item.sizes?.medium?.url || item.url;
          image.alt = '';
          const actions = document.createElement('div');
          actions.className = 'bfnd-banner-item-actions';
          const makeButton = (label, attributes) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = attributes.remove ? 'button-link-delete' : 'button';
            button.textContent = label;
            if (attributes.remove) button.dataset.remove = '';
            else {
              button.dataset.move = attributes.move;
              button.setAttribute('aria-label', attributes.label);
            }
            return button;
          };
          actions.append(makeButton('←', {move: '-1', label: '圖片往前'}), makeButton('→', {move: '1', label: '圖片往後'}), makeButton('移除', {remove: true}));
          card.append(image, actions);
          list.append(card);
        });
      sync();
    };

    pick.addEventListener('click', () => {
      if (!frame) {
        frame = wp.media({title: '選取 Banner 照片', button: {text: '加入所選照片'}, multiple: true, library: {type: 'image'}});
        frame.on('open', () => {
          const selection = frame.state().get('selection');
          selection.reset();
          input.value.split(',').filter(Boolean).forEach((id) => {
            const attachment = wp.media.attachment(id);
            attachment.fetch();
            selection.add(attachment);
          });
        });
        frame.on('select', () => append(frame.state().get('selection').toJSON()));
      }
      frame.open();
    });

    list.addEventListener('click', (event) => {
      const item = event.target.closest('[data-id]');
      if (!item) return;
      if (event.target.closest('[data-remove]')) {
        item.remove();
        sync();
        return;
      }
      const move = event.target.closest('[data-move]');
      if (!move) return;
      if (move.dataset.move === '-1' && item.previousElementSibling) list.insertBefore(item, item.previousElementSibling);
      if (move.dataset.move === '1' && item.nextElementSibling) list.insertBefore(item.nextElementSibling, item);
      sync();
    });

    sync();
  });
})();
