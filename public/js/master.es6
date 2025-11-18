window.addEventListener('load', function () {

    let b = document.getElementsByTagName('body');
    if (b && b[0]) {
        b[0].style.display = 'block';
    }

    if ( typeof ScrollArrows !== 'undefined')
        window.scrollArrows = new ScrollArrows();

});


//
function _wd_debounce(func, delay) {

    let timeout = null;
    return function (...args) {
        const context = this;
        if (timeout)
            clearTimeout(timeout);

        timeout = setTimeout(() => {
            func.apply(context, args);
        }, delay);
    };
}

/*
function _wd_check_for_double_click( e , func_1 , func_2 , delay = 500 ) {
    let lastCall = 0;
    return function (...args) {
        const now = Date.now();
        if (now - lastCall < delay) {
            func_2.apply(this, args);
        } else {
            func_1.apply(this, args);
        }
        lastCall = now;
    }
}
*/

function _wd_check_double_click_event(e, singleClickFn, doubleClickFn, delay = 400) {
    let s = _wd_check_double_click_event;  // aesthetic shorthand

    // Initialize static properties
    if (!s.lastClickTime) {
        s.lastClickTime = 0;
    }

    if (!s.clickTimeout) {
        s.clickTimeout = null;
    }

    const tDiff = e.timeStamp - s.lastClickTime;


    if (tDiff < delay && tDiff > 0) {  // Added positive check
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

function _wd_throttle(func, delay) {

    let lastCall = 0;
    let timeout = null;

    return function (...args) {

        const now = Date.now();
        // save context ( this ) for setTimeout to call original function
        const context = this;

        // If enough time has passed since the last call, schedule now
        if (now - lastCall >= delay) {
            if (timeout) {
                clearTimeout(timeout);
                timeout = null;
            }

            lastCall = now;
            func.apply(context, args);
        }
        // Otherwise, schedule the execution if not already scheduled
        else if (!timeout) {
            timeout = setTimeout(() => {
                lastCall = Date.now();
                func.apply(context, args);
                timeout = null;
            }, delay - (now - lastCall));
        }
    };
}

async function _wd_fetch(url, options) {
    const controller = new AbortController();
    const timeout = options.timeout || 5000;
    const timeoutId = setTimeout(() => controller.abort(), timeout);
    try {
        const response = await fetch(url, {
            ...options,
            signal: controller.signal
        });
        return response;
    } catch (e) {
        // Handle abort/timeout errors
        if (e.name === 'AbortError') {
            console.warn('Request timed out');
        }
        throw e; // Rethrow the error to be handled by caller
    } finally {
        clearTimeout(timeoutId);
    }
}



let _globalRefreshPromise = null;

_wd_api_token = async (url, payload, token, updateTokenCB = null) => {


    async function _make_request_(url, payload, token) {

        const response = await fetch(url, {
            timeout: 12500,
            method: 'POST',
            mode: 'cors',
            cache: 'no-cache',
            credentials: 'same-origin',
            redirect: 'follow',
            referrerPolicy: 'no-referrer',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            },
            body: JSON.stringify(payload)
        });
        return response;
    }

    async function _refresh_token_(token) {
        if (!_globalRefreshPromise) {
            _globalRefreshPromise = fetch('/api/v1/refresh-token', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                timeout: 12500
            })
                .then(response => response.json())
                .finally(() => {
                    _globalRefreshPromise = null;
                });
        }
        return _globalRefreshPromise;
    }

    try {
        // Make initial request
        const response = await _make_request_(url, payload, token);

        // If unauthorized, try to refresh token
        if (response.status === 401) {
            const refreshResult = await _refresh_token_(token);

            // If refresh successful, retry original request with new token
            if (refreshResult?.token) {
                if (updateTokenCB) {
                    updateTokenCB(refreshResult.token);
                }
                return _make_request_(url, payload, refreshResult.token);
            }

            // If refresh failed, redirect to login
            window.location.href = '/portal';
            return null;
        }

        return response;
    } catch (e) {
        console.error('API request failed:', e);
        throw e;
    }
}

