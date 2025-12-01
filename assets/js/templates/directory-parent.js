(function(){
    const form = document.querySelector('.sd-directory-search__form');
    const resultsContainer = document.querySelector('.sd-directory-results');
    const paginationContainer = document.querySelector('.sd-directory-pagination');
    const status = document.querySelector('.sd-directory-status');
    const resetButton = document.querySelector('.sd-directory-search__reset');
    const submitButton = document.querySelector('.sd-directory-search__submit');

    if (!form || !resultsContainer || !paginationContainer || !status || typeof sdDirectoryParent === 'undefined') {
        return;
    }

    const strings = sdDirectoryParent.strings || {};
    const perPage = parseInt(sdDirectoryParent.perPage, 10) || 12;
    let currentPage = sdDirectoryParent.initial && sdDirectoryParent.initial.page ? parseInt(sdDirectoryParent.initial.page, 10) : 1;
    let totalPages = sdDirectoryParent.initial && sdDirectoryParent.initial.total_pages ? parseInt(sdDirectoryParent.initial.total_pages, 10) : 0;

    const setLoading = (isLoading) => {
        if (isLoading) {
            form.classList.add('is-loading');
            submitButton.disabled = true;
            resultsContainer.classList.add('is-loading');
        } else {
            form.classList.remove('is-loading');
            submitButton.disabled = false;
            resultsContainer.classList.remove('is-loading');
        }
    };

    const renderStatus = (message) => {
        status.textContent = message || '';
    };

    const renderCards = (items) => {
        resultsContainer.innerHTML = '';

        if (!items || !items.length) {
            renderStatus(strings.noResults || '');
            return;
        }

        renderStatus('');

        items.forEach((item) => {
            const elementTag = item.permalink ? 'a' : 'article';
            const card = document.createElement(elementTag);
            card.className = 'sd-directory-card';
            if (item.permalink) {
                card.href = item.permalink;
            }

            if (item.homepage_screenshot) {
                card.classList.add('has-screenshot');
                card.style.setProperty('--sd-card-screenshot', `url("${item.homepage_screenshot}")`);
            }

            const logo = document.createElement('div');
            logo.className = 'sd-directory-card__logo';

            if (item.logo) {
                const img = document.createElement('img');
                img.src = item.logo;
                img.alt = item.name ? item.name + ' logo' : '';
                logo.appendChild(img);
            }

            const title = document.createElement('h3');
            title.className = 'sd-directory-card__title';
            title.textContent = item.name || '';

            const meta = document.createElement('p');
            meta.className = 'sd-directory-card__meta';
            meta.textContent = [item.category_label, item.industry_label].filter(Boolean).join(' • ');

            const link = document.createElement('span');
            link.className = 'sd-directory-card__cta';
            link.textContent = strings.viewResource || '';

            card.appendChild(logo);
            card.appendChild(title);
            card.appendChild(meta);
            card.appendChild(link);

            resultsContainer.appendChild(card);
        });
    };

    const renderPagination = (page, pages) => {
        paginationContainer.innerHTML = '';

        if (!pages || pages < 2) {
            return;
        }

        const prev = document.createElement('button');
        prev.type = 'button';
        prev.textContent = strings.prev || '';
        prev.disabled = page <= 1;
        prev.addEventListener('click', () => fetchResults(page - 1));

        const next = document.createElement('button');
        next.type = 'button';
        next.textContent = strings.next || '';
        next.disabled = page >= pages;
        next.addEventListener('click', () => fetchResults(page + 1));

        const summary = document.createElement('span');
        summary.textContent = strings.pageOf ? strings.pageOf.replace('%1$s', page).replace('%2$s', pages) : '';
        summary.className = 'sd-directory-pagination__summary';

        paginationContainer.appendChild(prev);
        paginationContainer.appendChild(summary);
        paginationContainer.appendChild(next);
    };

    const serializeForm = () => {
        const data = new FormData(form);
        data.append('action', 'sd_search_directory');
        data.append('nonce', sdDirectoryParent.nonce);
        data.append('per_page', perPage);
        data.append('page', currentPage);

        return data;
    };

    const fetchResults = (page = 1) => {
        currentPage = page;
        setLoading(true);

        const data = serializeForm();
        data.set('page', page);

        fetch(sdDirectoryParent.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            body: data,
        })
            .then((response) => response.json())
            .then((json) => {
                if (!json || !json.success || !json.data) {
                    throw new Error('Request failed');
                }

                const payload = json.data;
                totalPages = payload.total_pages || 0;
                renderCards(payload.items || []);
                renderPagination(payload.page || 1, payload.total_pages || 0);
            })
            .catch(() => {
                renderStatus(strings.error || '');
                renderCards([]);
                renderPagination(1, 0);
            })
            .finally(() => setLoading(false));
    };

    const scrollToResults = () => {
        const targetY = Math.max(resultsContainer.getBoundingClientRect().top + window.scrollY - 220, 0);
        const startY = window.scrollY;
        const distance = targetY - startY;

        if (!distance) {
            return;
        }

        const duration = Math.min(900, Math.max(450, Math.abs(distance)));
        let startTime;

        const step = (timestamp) => {
            if (!startTime) {
                startTime = timestamp;
            }

            const elapsed = timestamp - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = 1 - Math.pow(1 - progress, 3);

            window.scrollTo({ top: startY + distance * eased, behavior: 'auto' });

            if (progress < 1) {
                window.requestAnimationFrame(step);
            }
        };

        window.requestAnimationFrame(step);
    };

    const resetForm = () => {
        form.reset();
        currentPage = 1;
        fetchResults(currentPage);
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        scrollToResults();
        fetchResults(1);
    });

    if (resetButton) {
        resetButton.addEventListener('click', (event) => {
            event.preventDefault();
            resetForm();
        });
    }

    if (sdDirectoryParent.initial) {
        renderCards(sdDirectoryParent.initial.items || []);
        renderPagination(sdDirectoryParent.initial.page || 1, sdDirectoryParent.initial.total_pages || 0);
        renderStatus(sdDirectoryParent.initial.items && sdDirectoryParent.initial.items.length ? '' : strings.noResults || '');
    } else {
        fetchResults(currentPage);
    }
})();
