document.addEventListener('alpine:init', () => {

    Alpine.data('adbmPage', () => {
        return {
            sidebarOpen     : false ,
            sidebarExpanded : false ,
            darkMode : undefined ,

            setDarkMode( on ) {

                if ( on === true ) {
                    if ( this.darkMode !== true ) {
                        localStorage.setItem('dark-mode', 'dark');
                        document.body.classList.add('dark');
                        this.darkMode = true;
                    }
                }
                else {
                    if ( this.darkMode !== false ) {
                        localStorage.setItem('dark-mode', '');
                        document.body.classList.remove('dark');
                        this.darkMode = false;
                    }
                }
            } ,
            init: function () {
                this.setDarkMode( localStorage.getItem( 'dark-mode') === 'dark' )
                this.sidebarExpanded = localStorage.getItem('sidebar-expanded') == 'true';
                this.$watch('sidebarExpanded', value => localStorage.setItem('sidebar-expanded', value));
                this.sidebarOpen = localStorage.getItem('sidebar-open') == 'true';
                this.$watch('sidebarOpen', value => localStorage.setItem('sidebar-open', value));
            }

        }
    })
})

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

async function _wd_fetch(url, options) {
    const controller = new AbortController();
    const timeout = options.timeout || 10000;
    const timeoutId = setTimeout(() => controller.abort( new Error( 'timeout' )), timeout);
    try {
        const response = await fetch(url, {
            ...options,
            signal: controller.signal
        });
        return response;
    } catch (e) {
        // Handle abort/timeout errors
        if (e.name === 'AbortError') {
            console.warn('Request timed out:' , e);
        }
        throw e; // Rethrow the error to be handled by caller
    } finally {
        clearTimeout(timeoutId);
    }
}

async function apiFetch(url, token, payload = {}, callBack = null, isRetry = false) {
    try {
        let response = await _wd_fetch(url, {
            method: 'POST',
            headers: {
                'Authorization': `Bearer ${token}`,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });

        if (response.status === 401 && !isRetry) {
            // Try to refresh via the PHP backend
            const refreshRes = await _wd_fetch(window.Auth.refreshUrl || '/auth/refresh-token', { method: 'POST' });

            if (refreshRes.ok) {
                const data = await refreshRes.json();
                const newToken = data.access_token;

                // Update Global state if it exists
                if (window.Auth)
                    window.Auth.token = newToken;

                // IMPORTANT: Pass the NEW token into the retry
                return apiFetch(url, newToken, payload, callBack, true);
            }
        }

        if (!response.ok) throw new Error(`API_ERROR: ${response.status}`);

        const json = await response.json();

        // Return both the data AND the potentially updated token
        // so the calling class (like wdDataTable) can update its config
        const result = { data: json, newToken: isRetry ? token : null };

        if (callBack) return callBack(result);
        return result;

    } catch (e) {
        if (callBack) return callBack({ error: e.message });
        throw e;
    }
}








