class wdDataTable {

    constructor(cfg) {

        if (!cfg || !cfg?.table)
            throw new Error('no table defined');

        this.T0 = document.getElementById(cfg?.id);
        if (!this.T0)
            throw new Error('no table/ element found');

        this.domid = cfg.id;
        this.cfg = cfg.table;
        this.hpick = -1; // no header selected

        this.makeBlock(this.T0, 'table', 'div', (E, create) => {
            if (create)
                E.className = 'wd-table-wrapper';
            E.replaceChildren(); // make sure empty
            this.T1 = E;
        })

        this.upd = {
            all: true,
        };

        this.ref = {
            pagecalc: true,
        };
        this.view = {
            refresh: 0,
            page: 0,
            pageLength: 0,
            offset: 0,
            strem: '',
        };


        this.fetchSoon = _wd_debounce(this.fetchPage.bind(this), 100);


        this.loadConfig(this.cfg);


        this.updateDOM();
        this.fetchSoon();


    }

    loadConfig(cfg) {

        if (!cfg?.columns)
            throw new Error('no columns defined');

        this.lkey = '_def_' + this.domid + '_' + (cfg?.version ?? '');
        //

        let jdef = localStorage.getItem(this.lkey);
        let defcfg = jdef ? JSON.parse(jdef) : false;
        if (defcfg)
            this.need.savecfg = false;


        this.ncols = cfg.columns?.length || 0;
        this.col = [];
        this.defs = [];

        // user may have renamed head columns ...
        if (!defcfg.colHead || defcfg.colHead.length !== this.ncols) {
            this.colHead = Array.from({length: this.ncols}, (_, i) => null);
        } else
            this.colHead = defcfg.colHead;


        if (!defcfg.colOrder || defcfg.colOrder.length !== this.ncols)
            this.colOrder = Array.from({length: this.ncols}, (_, i) => i);
        else
            this.colOrder = defcfg.colOrder;

        // this.view.search = cfg?.search ?? def?.search ?? '' ;


        for (let i = 0; i < this.ncols; i++) {
            // populate default values
            const c = cfg.columns[i];

            let q = {

                f: c?.field ?? i,
                vis: c?.vis ?? true,
                searchable: c?.searchable ?? true,
                sortable: c?.sortable ?? true,
                sort: c?.sort ?? 0,
                size: c?.size ?? 1

            }

            this.col[i] = q;
            this.colHead[i] = this.colHead[i] ?? c?.head ?? '';
            this.defs[i] = {
                f: q.f,
                vis: q.vis,
                searchable: q.searchable,
                sortable: q.sortable,
                sort: q.sort,
            }


        }

        this.setViewLength(5);
        this.setViewPage(1);

    }

    makeBlock(base, name, tag = 'div', updateCB = null) {

        let n = this.domid + '-' + name;
        let E = base.querySelector('#' + n);
        if (!E) {

            E = document.createElement(tag);
            E.id = n;

            base.appendChild(E);
            if (updateCB)
                updateCB(E, true);
            return;
        }

        if (!E)
            throw new Error('failed to create dom block ' + this.domid + '_' + name);

        if (updateCB)
            updateCB(E, false);

    }

    createPageLengthSelect() {


        const P = document.createElement('select');
        P.className = 'wd-pagelen';
        let a = this.cfg?.pagelengths ?? [1, 3, 5, 10, 15, 25, 50, 100, 250, 500];
        a.forEach((v) => {
            const O = document.createElement('option');
            O.value = v;
            O.innerText = v;
            if (v === this.view.pagelength)
                O.selected = true;
            P.appendChild(O);
        })
        P.addEventListener('change', (e) => {
            this.setViewLength(+e.target.value);
        })

        return P;

    }

    createPageSearch() {
        const S = document.createElement('input');
        S.className = 'wd-search';
        S.type = 'text';
        S.placeholder = 'Search';
        S.value = this.view.search ?? '';
        S.spellcheck = false;
        S.addEventListener('input', (e) => {
            this.view.search = e.target.value;
            this.fetchSoon();
        })
        return S;
    }

    updateTOPBAR() {

        this.makeBlock(this.T1, 'topbar', 'div', (E, create) => {

            if (create)
                E.className = 'wd-topbar';

            if (this.ref.pagelen) {

                this.makeBlock(E, 'pagelen', 'div', (Q, init) => {


                    Q.replaceChildren();
                    if (init)
                        Q.className = 'flex-shrink-0';


                    Q.appendChild(this.createPageLengthSelect());

                });

                this.ref.pagelen = false;
            }


            if (this.ref.pagesearch) {

                this.makeBlock(E, 'pagesearch', 'div', (Q, init) => {

                    Q.replaceChildren();
                    if (init)
                        Q.className = 'flex-grow ';

                    Q.appendChild(this.createPageSearch());
                })


                this.ref.pagesearch = false;
            }

            if (this.ref.pageopts) {

                this.makeBlock(E, 'pageopts', 'div', (Q, init) => {

                    Q.replaceChildren();
                    if (init)
                        Q.className = 'flex-shrink-0';

                    const B = document.createElement('button');
                    B.className = 'wd-pageopts-btn';
                    B.innerText = '⚙';
                    Q.appendChild(B);
                })


                this.ref.pageopts = false;
            }


            this.upd.topbar = false;
        })
    }


    changeSortOrder(at, ctrl = false) {

        const j = this.colOrder[at] || 0;

        if (ctrl) {
            let sort = this.defs[j].sort;
            this.defs[j].sort = (sort === 0) ? 1 : -sort;
        } else for (let k = 0; k < this.ncols; k++) {
            if (k !== j)
                this.defs[k].sort = 0;
            else
                this.changeSortOrder(at, true);
        }

        this.upd.table = true;
        console.log('?');
        this.fetchSoon();

    }


    pickHeader(i) {
        this.hpick = i;
        this.upd.table = true;
        this.updateDOM();
    }

    buildTH(TH, i) {

        if (!TH)
            throw new Error('no table header element');

        TH.replaceChildren();


        let j = this.colOrder[i];

        TH.tabIndex = -1;
        if (this.hpick === i)
            TH.classList.add('colpick');


        const cdef = this.col[j];


        //TH.className = (i == this.hpick) ? 'colpick' : ' ';
        //if (cdef?.sort)
        //    TH.className += 'sort';

        let S = document.createElement('span');
        if (!S)
            throw new Error('no span element');

        S.className = 'flex justify-between';

        let S1 = document.createElement('div');
        S1.className = 'flex-grow';
        S1.innerText = this.colHead[j] || cdef?.head || '';

        S.appendChild(S1);

        if (cdef?.sortable) {
            let S2 = document.createElement('div');
            if (!S2)
                throw new Error('no span element');
            S2.className = 'flex-shrink mr-2 min-w-6';
            let sort = this.defs[j].sort || 0;
            S2.innerText = (sort == 0) ? '↕' : (sort < 0 ? '▼' : '▲');
            S.appendChild(S2);
        }


        TH.appendChild(S);

        // add listeners
        if (cdef?.sortable) {

            let lastClickTime = 0;
            let clickTimeout = null;
            const DOUBLE_CLICK_THRESHOLD = 400; // milliseconds

            TH.addEventListener('click', (e) => {
                e.preventDefault();
                const tDiff = e.timeStamp - lastClickTime;
                lastClickTime = e.timeStamp;
                let cdef = this.col[this.colOrder[i]];
                if (tDiff < DOUBLE_CLICK_THRESHOLD) {

                    if (clickTimeout)
                        clearTimeout(clickTimeout);

                    if (this.hpick < 0)
                        this.pickHeader(i);
                    else if (i !== this.hpick)
                        this.moveColumn(this.hpick, i);
                    else
                        this.pickHeader(-1);

                    // double click
                } else {

                    clickTimeout = setTimeout(() => {
                        // single click
                        if ( this.hpick < 0 )
                            this.changeSortOrder(i,e.ctrlKey );

                    }, DOUBLE_CLICK_THRESHOLD);
                }
            })
        }


    }

    buildTD(TD, row, r, c) {

        let j = this.colOrder[c];
        let v = row?.[j] || '';
        if (v)
            TD.innerText = v;

        if (this.hpick === c)
            TD.classList.add('colpick');
        else if (this.defs[c].sort !== 0)
            TD.classList.add('sort');
    }

    moveColumn(src, dst) {
        if (src >= 0 && dst >= 0 && src < this.ncols && dst < this.ncols) {
            let j = this.colOrder[src];
            this.colOrder[src] = this.colOrder[dst];
            this.colOrder[dst] = j;
            this.upd.table = true;
            if (this.hpick === src)
                this.hpick = dst;
            this.fetchSoon();
        }
    }


    updateTABLE() {

        this.makeBlock(this.T1, 'table', 'table', (E, create) => {
            if (create)
                E.className = 'wd-table-data';

            if (this.ref.colgrp) {

                this.makeBlock(E, 'colgrp', 'colgroup', (Q, init) => {
                    Q.className = 'w-4';
                    Q.replaceChildren();
                    for (let c = 0; c < this.ncols; c++) {
                        const COL = document.createElement('col');
                        // guide width only
                        COL.style.width = (this?.col[c]?.size ?? 1) + 'rem';
                        Q.appendChild(COL);
                    }
                });

                this.ref.colgrp = false;
            }


            if (this.ref.thead) {

                this.makeBlock(E, 'thead', 'thead', (Q, init) => {

                    Q.replaceChildren();
                    const TR = document.createElement('tr');
                    TR.id = this.domid + '-tr-head';
                    for (let c = 0; c < this.ncols; c++) {

                        const TH = document.createElement('th');
                        this.buildTH(TH, c);
                        TR.appendChild(TH);
                    }
                    Q.appendChild(TR);

                });

                this.ref.thead = false;
            }

            if (this.ref.tbody) {

                this.makeBlock(E, 'tbody', 'tbody', (Q, init) => {

                    Q.replaceChildren();
                    for (let r = 0; r < this.view.pagelength; r++) {
                        let row = this.view.data?.[r] || false;
                        if ( !row )
                            continue ;


                        const TR = document.createElement('tr');
                        TR.id = this.domid + '-tr-' + r;
                        for (let c = 0; c < this.ncols; c++) {
                            const TD = document.createElement('td');
                            this.buildTD(TD, row, r, c)
                            TR.appendChild(TD);
                        }

                        Q.appendChild(TR);


                    }
                    this.TBODY = Q;

                });

                this.ref.tbody = false;
            }


        })
        this.upd.table = false;


    }

    updateBOTBAR() {

    }

    updateDOM() {

        if ( this.ref.pagecalc ) {
            this.view.npages = Math.ceil(this.view.nrows / this.view.pagelength) ;
            if ( this.view.page > this.view.npages ) {

                this.setViewPage(this.view.npages);
                return ;
            }
            else if ( this.view.page < 1 ) {
                this.setViewPage(1);
            }


            this.ref.pageno   = true ;
            this.ref.pagecalc = false ;
        }

        if (this.upd.all || this.upd.topbar) {

            this.ref.pagelen = true;
            this.ref.pageno = true;
            this.ref.pagesearch = true;
            this.ref.pageopts = true;
        }

        // top bar
        if (this.ref.pagelen || this.ref.pageno || this.ref.pagesearch || this.ref.pageopts)
            this.updateTOPBAR();

        // table section
        if (this.upd.all || this.upd.table) {
            this.ref.colgrp = true;
            this.ref.thead = true;
            this.ref.tbody = true;
        }

        if (this.ref.colgrp || this.ref.thead || this.ref.tbody)
            this.updateTABLE();


        // bottom bar
        if (this.upd.all || this.upd.botbar) {
            this.ref.pageinfo = true;
            this.ref.pager = true;
        }

        if (this.ref.pageinfo || this.ref.pager)
            this.updateBOTBAR();


        if (this.ref.savecfg) {
            this.saveSoon();
        }


        this.upd.all = false;


    }


    async fetchAjax(ajx, request) {
        try {
            const response = await _wd_api_token(
                ajx.url,
                request,
                ajx.token,
                (newToken) => {
                    this.cfg.ajax.token = newToken;
                });
            return response;
        } catch (e) {

        }
        return Promise.resolve(false);
    }

    // set view length
    setViewLength(l) {
        this.view.pagelength = l;
        let p  = ( l > 0 ) ? Math.floor(this.view.offset / l) + 1 : 1 ;
        this.ref.pagecalc = true ;
        this.setViewPage(p);
    }

    setViewPage(p) {

        this.view.page = p ;
        this.view.offset = ( p > 0 ) ? (p - 1) * this.view.pagelength : 0 ;
        this.ref.pagecalc = true ;
        this.fetchSoon() ;
    }

    setViewData(data, total, payload) {
        console.log('setViewData', payload,data , total ) ;
        this.view.nrows = total;
        this.view.data = data;
        this.ref.pagecalc = true;
        this.ref.tbody = true ;
        this.updateDOM();
    }

    fetchPage() {


        const payload = {
            refresh: ++this.view.refresh,
            offset: this.view.offset,
            limit: this.view.pagelength,
            sterm: this.view.search,
            col: this.defs,
        }


        const ajx = this.cfg?.ajax;
        if (ajx && ajx.url && ajx.token) {
            // fetch data from ajax

            (async (req) => {

                try {
                    const ajx = this.cfg?.ajax;
                    if (ajx.url && ajx.token) {
                        const response = await this.fetchAjax(ajx, req)
                        if (response.ok) {
                            const data = await response.json();
                            if (data.refresh === this.view.refresh) {

                                this.setViewData(data.data, data.total, payload);
                            }
                        } else
                            throw new Error('bad response');
                    }
                } catch (e) {
                    console.error(e);
                } finally {

                }

            })(payload);

        } else
            throw new Error('ajax not configured');

    }


}
