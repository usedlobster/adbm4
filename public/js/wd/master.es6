function _wd_debounce(func, delay, delay2 = null) {

    let timeout = null;
    let slow = 0;
    return function (...args) {
        const context = this;

        if (timeout) {
            clearTimeout(timeout);
            slow++;
        }

        timeout = setTimeout(() => {
            func.apply(context, args);
            slow = 0;
            timeout = null; // not strictly necessary
        }, ((delay2 != null && slow > 2) ? delay2 : delay));
    };
}


function _wd_wrap_click_event(e, singleClickFn, doubleClickFn, delay = 400) {

    let s = _wd_wrap_click_event;

    // Initialize static properties
    if (!s.lastClickTime) {
        s.lastClickTime = 0;
    }

    if (!s.clickTimeout) {
        s.clickTimeout = null;
    }

    const tDiff = e.timeStamp - s.lastClickTime;

    if (tDiff < delay && tDiff > 0) {
        // Double click detected
        if (s.clickTimeout) {
            clearTimeout(s.clickTimeout);
            s.clickTimeout = null;
        }
        s.lastClickTime = 0;  // Reset timestamp
        doubleClickFn(e);
    } else {
        // Potential single click
        if (s.clickTimeout) {
            clearTimeout(s.clickTimeout);
        }
        s.lastClickTime = e.timeStamp;
        s.clickTimeout = setTimeout(() => {

            singleClickFn(e);
            s.clickTimeout = null;
            s.lastClickTime = 0;  // Also reset timestamp after single click executes
        }, delay);
    }
}

async function _wd_fetch(url, options) {
    const fetchUrl = `/api/fetch-endpoint.php?url=${encodeURIComponent(url)}&method=${options.method || 'GET'}`;

    const controller = new AbortController();
    const timeout = options.timeout || 10000;
    const timeoutId = setTimeout(() => controller.abort(new Error('timeout')), timeout);
    try {
        const response = await fetch(url, {
            ...options,
            signal: controller.signal,
        });
        return response;
    } catch (e) {
        // Handle abort/timeout errors
        if (e.name === 'AbortError') {
            console.warn('Request timed out:', e);
        }

    } finally {
        clearTimeout(timeoutId);
    }
}


async function _wd_refresh_token_request() {

    if (window?.wdAuth?.refreshUrl) {

        const response = await _wd_fetch(window.wdAuth.refreshUrl, {method: 'POST'});
        if (response?.ok === true) {
            let ref = await response.json();
            if (ref?.ok === true && ref?.atkn) {
                window.wdAuth.token = ref.atkn;
                return true;
            }
        }

    }
    return false;
}

async function _wd_api_fetch(url, payload = {}, callBack = null, isRetry = false) {


    let result = null;
    try {

        let token = window?.wdAuth?.token || '';
        let response = await _wd_fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });


        if (response?.ok === true) {
            result = await response.json();

            if (result?.expired === true) {

                if (!isRetry) {
                    if (await _wd_refresh_token_request())
                        return _wd_api_fetch(url, payload, callBack, true);
                }
            }
        }

    } catch (e) {
        result = {error: e.message};
    } finally {
        if (callBack)
            callBack(result);
    }


}

function _wd_make_block( base , name, tag = 'div', updateCB = null) {

    let n = (base?.id ?? 'blk') + '-' + name;
    let E = document.getElementById(n);
    if (!E) {
        E = document.createElement(tag);
        E.id = n;
        base.appendChild(E);
        if (updateCB) {
            updateCB(E, true);
        }
        return;
    }

    if (updateCB) {
        updateCB(E, false);
    }

}



function _wd_open_modal_template(MODAL, tempname, modalFn = null) {
    if (!MODAL || !tempname)
        throw new Error('wdTable: cannot find modal');

    // Clean out modal
    MODAL.replaceChildren();
    const tempnode = document.getElementById(tempname);
    if (!tempnode)
        throw new Error('template not found');

    const frag = tempnode.content.cloneNode(true);
    if (!frag || !frag.children)
        throw new Error('failed to clone template');

    const M = document.createElement('div');
    if (!M)
        throw new Error('failed to create modal div');

    M.id = MODAL.id + tempname + '_clone';
    M.className = 'wd-modal-content';
    M.modalFn = modalFn;
    M.modalFn?.('modal_init', M);
    M.style.display = 'block';
    M.appendChild(frag);

    MODAL.appendChild(M);
    document.body.style.overflow = 'hidden';

    // Create named handler functions so they can be removed later
    const handleKeyDown = (e) => {
        if (e.key === 'Escape') {
            _wd_close_modal_template(MODAL, tempname);
        }
    };

    const handleClick = (e) => {
        if (e.target === MODAL) {
            _wd_close_modal_template(MODAL, tempname);
        }
    };

    // Store references on the modal element for later cleanup
    M._keyDownHandler = handleKeyDown;
    M._clickHandler = handleClick;

    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('click', handleClick);

    MODAL.style.display = 'block';
}

function _wd_close_modal_template(MODAL ) {

    if (MODAL) {
        const M = MODAL.firstChild;
        if (M ) {
            //
            M.modalFn?.('modal_close', M);
            // Remove the event listeners
            document.removeEventListener('keydown', M._keyDownHandler);
            document.removeEventListener('click', M._clickHandler);

            M.remove();
            MODAL.replaceChildren();
            MODAL.style.display = 'none';
            document.body.style.overflow = '';
        }
    }
}


