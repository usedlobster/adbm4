
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


async function _wd_refresh() {

  if ( window?.wdAuth?.refreshUrl ) {

    const response = await _wd_fetch( window.wdAuth.refreshUrl , { method: 'POST' } ) ;
    if ( response?.ok === true ) {
      let ref = await response.json() ;
      if ( ref?.ok === true && ref?.atkn ) {
        window.wdAuth.token = ref.atkn ;
        return true ;
      }
    }

  }
  return false ;
}

async function _wd_api_fetch(url, payload = {}, callBack = null, isRetry = false) {

  let result = null ;
  try {

    let token = window?.wdAuth?.token || '' ;
    let response = await _wd_fetch(url, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if ( response?.ok === true ) {
      result = await response.json();
      if ( result?.expired === true ) {
        if ( !isRetry ) {
          if ( await _wd_refresh())
            return _wd_api_fetch(url, payload, callBack, true);
        }
      }
    }


    if ( callBack )
      callBack(result);


  }
  catch( e ) {
    result = null ;
  }


}

