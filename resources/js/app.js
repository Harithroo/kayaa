// Storefront behaviour. No framework — small, and the pages must stay light on mobile data.

// Product page: colour + size picker driven by the variant matrix.
document.querySelectorAll('[data-variant-picker]').forEach((root) => {
    const matrix = JSON.parse(root.dataset.matrix || '{}');
    const colorBtns = root.querySelectorAll('.sw');
    const sizeBtns = root.querySelectorAll('.size');
    const input = root.querySelector('input[name="variant_id"]');
    const priceEl = root.querySelector('[data-price]');
    const wasEl = root.querySelector('[data-was]');
    const stockEl = root.querySelector('[data-stock-note]');
    const colorName = root.querySelector('[data-color-name]');
    const addBtn = root.querySelector('[data-add]');

    let color = root.querySelector('.sw.on')?.dataset.color ?? '0';
    let size = root.querySelector('.size.on')?.dataset.size ?? null;

    function render() {
        const row = matrix[color] || {};
        sizeBtns.forEach((b) => {
            const v = row[b.dataset.size];
            const out = !v || v.stock < 1;
            b.classList.toggle('out', out);
            b.disabled = out;
            b.classList.toggle('on', b.dataset.size === size && !out);
        });
        if (size && (!row[size] || row[size].stock < 1)) size = null;
        const v = size ? row[size] : null;

        if (v) {
            input.value = v.id;
            priceEl.textContent = v.priceText;
            if (wasEl) { wasEl.textContent = v.compareText || ''; wasEl.hidden = !v.compareText; }
            stockEl.textContent = v.stock <= 5 ? `Only ${v.stock} left in ${size}` : '';
            addBtn.disabled = false;
            addBtn.textContent = 'Add to cart';
        } else {
            input.value = '';
            stockEl.textContent = '';
            addBtn.disabled = true;
            addBtn.textContent = 'Choose a size';
        }
    }

    colorBtns.forEach((b) => b.addEventListener('click', () => {
        colorBtns.forEach((x) => x.classList.remove('on'));
        b.classList.add('on');
        color = b.dataset.color;
        if (colorName) colorName.textContent = b.getAttribute('aria-label');
        render();
    }));
    sizeBtns.forEach((b) => b.addEventListener('click', () => {
        if (b.disabled) return;
        size = b.dataset.size;
        render();
    }));

    render();
});

// Product gallery thumbnails.
document.querySelectorAll('[data-gallery]').forEach((g) => {
    const main = g.querySelector('[data-gallery-main]');
    g.querySelectorAll('[data-thumb]').forEach((t) => t.addEventListener('click', () => {
        main.src = t.dataset.thumb;
        main.alt = t.querySelector('img')?.alt || main.alt;
    }));
});

// Cart quantity steppers submit their form on change.
document.querySelectorAll('[data-qty-form]').forEach((form) => {
    const input = form.querySelector('input[name="qty"]');
    form.querySelectorAll('[data-step]').forEach((b) => b.addEventListener('click', () => {
        input.value = Math.max(0, Math.min(10, Number(input.value) + Number(b.dataset.step)));
        form.requestSubmit();
    }));
});

// Checkout: highlight the chosen payment option.
document.querySelectorAll('.payopt input').forEach((r) => r.addEventListener('change', () => {
    document.querySelectorAll('.payopt').forEach((p) => p.classList.toggle('on', p.contains(r)));
}));
