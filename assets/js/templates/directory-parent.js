(function(){
    const form = document.querySelector('.sd-directory-search__form');
    const resultsContainer = document.querySelector('.sd-directory-results');
    const status = document.querySelector('.sd-directory-status');
    const resetButton = document.querySelector('.sd-directory-search__reset');
    const submitButton = document.querySelector('.sd-directory-search__submit');
    let loadSentinel = document.querySelector('.sd-directory-load-sentinel');
    let loadIndicator = loadSentinel ? loadSentinel.querySelector('.sd-directory-load-indicator') : null;

    if (!form || !resultsContainer || !status || typeof sdDirectoryParent === 'undefined') {
        return;
    }

    const strings = sdDirectoryParent.strings || {};
    const perPage = parseInt(sdDirectoryParent.perPage, 10) || 9;
    let currentPage = sdDirectoryParent.initial && sdDirectoryParent.initial.page ? parseInt(sdDirectoryParent.initial.page, 10) : 1;
    let totalPages = sdDirectoryParent.initial && sdDirectoryParent.initial.total_pages ? parseInt(sdDirectoryParent.initial.total_pages, 10) : 0;
    let isFetching = false;
    let observer;

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

    const setLoadingMore = (isLoadingMore) => {
        resultsContainer.classList.toggle('is-loading-more', isLoadingMore);

        if (loadIndicator) {
            loadIndicator.style.opacity = isLoadingMore ? '1' : '0';
        }
    };

    const ensureLoadSentinel = () => {
        if (!loadSentinel) {
            loadSentinel = document.createElement('div');
            loadSentinel.className = 'sd-directory-load-sentinel';

            loadIndicator = document.createElement('div');
            loadIndicator.className = 'sd-directory-load-indicator';

            loadSentinel.appendChild(loadIndicator);
        }

        if (!loadIndicator) {
            loadIndicator = loadSentinel.querySelector('.sd-directory-load-indicator');
        }

        if (!resultsContainer.contains(loadSentinel)) {
            resultsContainer.appendChild(loadSentinel);
        }

        return loadSentinel;
    };

    const renderStatus = (message) => {
        status.textContent = message || '';
    };

    const renderCards = (items, { append = false } = {}) => {
        const sentinel = ensureLoadSentinel();

        if (!append) {
            resultsContainer.innerHTML = '';
            resultsContainer.appendChild(sentinel);
        }

        if (!append && (!items || !items.length)) {
            renderStatus(strings.noResults || '');
            return;
        }

        if (!append) {
            renderStatus('');
        }

        (items || []).forEach((item) => {
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

            resultsContainer.insertBefore(card, sentinel);
        });
    };

    const updateInfiniteScroll = () => {
        const sentinel = ensureLoadSentinel();

        if (!sentinel) {
            return;
        }

        if (!observer) {
            observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting && !isFetching && currentPage < totalPages) {
                        fetchResults(currentPage + 1, { append: true });
                    }
                });
            }, { rootMargin: '320px 0px' });
        }

        observer.disconnect();

        if (totalPages > 1 && currentPage < totalPages) {
            sentinel.style.display = 'flex';
            observer.observe(sentinel);
        } else {
            sentinel.style.display = 'none';
        }
    };

    const serializeForm = () => {
        const data = new FormData(form);
        data.append('action', 'sd_search_directory');
        data.append('nonce', sdDirectoryParent.nonce);
        data.append('per_page', perPage);
        data.append('page', currentPage);

        return data;
    };

    const fetchResults = (page = 1, { append = false } = {}) => {
        if (isFetching) {
            return;
        }

        isFetching = true;

        if (!append) {
            totalPages = 0;
            updateInfiniteScroll();
        }

        if (append) {
            setLoadingMore(true);
        } else {
            setLoading(true);
        }

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
                currentPage = payload.page || page;
                renderCards(payload.items || [], { append });
                if (!append) {
                    renderStatus(payload.items && payload.items.length ? '' : strings.noResults || '');
                }
                updateInfiniteScroll();
            })
            .catch(() => {
                renderStatus(strings.error || '');
                if (!append) {
                    renderCards([]);
                    totalPages = 0;
                    currentPage = 1;
                    updateInfiniteScroll();
                }
            })
            .finally(() => {
                isFetching = false;
                setLoading(false);
                setLoadingMore(false);
            });
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
        totalPages = 0;
        updateInfiniteScroll();
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
        renderStatus(sdDirectoryParent.initial.items && sdDirectoryParent.initial.items.length ? '' : strings.noResults || '');
        updateInfiniteScroll();
    } else {
        fetchResults(currentPage);
    }
})();
