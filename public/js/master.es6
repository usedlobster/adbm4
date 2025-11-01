window.addEventListener('load', function () {

    let b = document.getElementsByTagName('body');
    if (b && b[0]) {
        b[0].style.display = 'block';
    }

    new ScrollArrows();
});



//
function _wd_debounce(func, delay) {

    let timeout = null ;
    return function (...args) {
        const context = this;
        if ( timeout )
            clearTimeout(timeout);
        timeout = setTimeout(() => {
            func.apply(context, args);
        }, delay);
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
            console.log('Request timed out');
        }
        throw e; // Rethrow the error to be handled by caller
    } finally {
        clearTimeout(timeoutId);
    }
}

let refreshInProgress = null;

async function _wd_api_token(url, payload, token , updateTokenCB = null ) {

    async function _make_request_(url, payload, token) {
        const options = {
            timeout: 12500,
            method: 'POST',
            mode: 'cors',
            cache: 'no-cache',
            credentials: 'same-origin',
            redirect: 'follow',
            referrerPolicy: 'no-referrer',
            body: JSON.stringify(payload),
        };
        if (token) {
            options.headers = {
                'Content-Type': 'application/json',
                'Authorization': 'Bearer ' + token
            };
        }
        return await _wd_fetch(url, options);
    }

    try {
        const response = await _make_request_(url, payload, token);
        if (response.status === 401) {
            // Try to refresh the token first
            if (refreshInProgress) {
                await refreshInProgress;
                return _make_request_(url, payload, token);
            }

            refreshInProgress = fetch('/api/v1/refresh-token', {
                method: 'POST',
                headers: {
                    'Authorization': 'Bearer ' + token
                },
                timeout: 5000
            });
            try {
                const refreshResponse = await refreshInProgress;
                if (refreshResponse.ok) {
                    const newToken = await refreshResponse.json();
                    if ( newToken?.token ) {

                        if (updateTokenCB)
                            updateTokenCB(newToken.token);

                        return  _make_request_(url, payload, newToken.token );

                    }
                }

                window.location.href = '/portal';
                return null ;

            } finally {
                refreshInProgress = null;
            }


        }

        return response;
    } catch (e) {
        console.error('API request failed:', e);
        throw e;
    }


}
