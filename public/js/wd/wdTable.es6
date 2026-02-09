class wdTable {

    constructor(divid, jobj) {

        if (typeof divid !== 'string' || typeof jobj !== 'object' || !jobj) {
            throw new Error('wdTable: invalid parameters');
        }

        let cfg = this.cfg = jobj?.table;
        if (!cfg) {
            throw new Error('wdTable: no table configuration');
        }

        this.T0 = document.getElementById(divid);
        if (!this.T0) {
            throw new Error(`wdTable: divid ${divid} not found`);
        }

        // create empty modal

        this.MODAL           = document.createElement('div');
        this.MODAL.id        = divid + '_modal';
        this.MODAL.className = 'wd-modal overflow-hidden';
        this.MODAL.style.display = 'none';
        this.T0.appendChild(this.MODAL);

        //

        this.T1 = document.createElement('div');
        this.T1.id = divid + '_table';
        this.T1.className = 'wd-table-box';

        // create hidden modal

        this.T0.appendChild(this.T1);
        this.col = [];
        this.colorder = [];

        this.loadUserConfig();

        if (cfg?.columns) {

            let found_sort = null;
            let index = 0;

            Object.keys(cfg.columns).forEach((k) => {

                let cdef = cfg?.columns?.[k];

                this.col[k] = {
                    index: index,
                    title: cdef?.title ?? ('_' + k),
                    moveable: cdef?.moveable ?? true,
                    width: cdef?.width ?? 1,
                    sortable: cdef?.sortable ?? true, // by default sortable
                    searchable: cdef?.searchable ?? true, // sortable by default
                    visible: cdef?.visible ?? true,
                    sort: cdef?.sort ?? 0,
                    field: cdef?.field ?? k,
                };


                this.colorder[index] = cdef?.order || this?.usercfg?.colorder?.[index] || index;

                index++;

                // note possible sort
                if (found_sort === null && (cdef?.sortable ?? true)) {
                    found_sort = this.col[k];
                }

            });

            if (found_sort && found_sort.sort === 0)
                found_sort.sort = 1;


        }

        this.ncols = this.col.length;

        function fixOrder(colorder, ncols) {
            const seen = new Set();
            const repaired = [];
            for (let i = 0; i < colorder.length; i++) {
                const idx = colorder[i];
                if (Number.isInteger(idx) && idx >= 0 && idx < ncols && !seen.has(idx)) {
                    repaired.push(idx);
                    seen.add(idx);
                }
            }

            for (let i = 0; i < ncols; i++) {
                if (!seen.has(i)) {
                    repaired.push(i);
                }
            }


            return repaired;


        }

        if (!this.colorder || !Array.isArray(this.colorder))
            this.colorder = [];

        this.colorder = fixOrder(this.colorder, this.ncols);


        this.ref = {
            all: false,
            title: false,
            error: false,
            plen: false,
            sbar: false,
            obtn: false,
            colgroup: false,
            thead: false,
            tbody: false,
            pagenumbers: false,
        };

        this.view = {
            page: 0,
            pageLength: this.usercfg?.pageLength ?? this.cfg?.pageLength ?? 5,
            sbarPlaceHolder: this.cfg?.sbarPlaceHolder ?? 'Search...',
        };

        this.data = {
            data: [],
            offset: 0,
            end: 0,
        };

        // test
        this.refreshTable();
        this.ref.all = true;
        this.refreshTable();
        this.ref.all = true;
        this.refreshTable();
        this.ref.all = true;
        this.refreshTable();
        this.ref.all = true;
        this.refreshTable();
        this.ref.all = true;
        this.refreshTable();
        this.ref.all = true;
        this.refreshTable();

        this.fetchPage();

    }

    loadUserConfig() {
        let cfg = localStorage.getItem('wdTableState');
        this.usercfg = cfg ? JSON.parse(cfg) : {};
        return this.usercfg;
    }

    saveUserConfig() {
        localStorage.setItem('wdTableState', JSON.stringify(this.usercfg));
    }

    makeBlock(base, name, tag = 'div', updateCB = null) {

        let n = (base?.id ?? 'block') + '-' + name;
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

    updateTitleBlock() {

        this.makeBlock(this.T1, 'title', 'div', (E, einit) => {

            if (einit) {
                E.className = 'wd-table-section wd-topblock';

                if (einit || this.ref.title) {
                    this.makeBlock(E, 'title', 'h1', (Q, qinit) => {
                        if (qinit) {
                            Q.className = 'wd-title';
                        }
                        Q.textContent = this.cfg?.title ?? '';

                    });
                    this.ref.title = false;
                }

                if (einit || this.ref.error) {
                    this.makeBlock(E, 'error', 'h2', (Q, qinit) => {
                        if (qinit) {
                            Q.className = 'wd-error';
                        }

                        Q.style.display = (this.cfg?.error ? 'block' : 'none');
                        Q.textContent = this.cfg?.error ?? '';

                    });

                    this.ref.error = false;
                }

            }

            this.ref.topblock = false;

        });
    }

    updateQueryBlock() {
        this.ref.qblock = false;
    }

    changePageLength(plen) {

    }

    changeShownPage(p) {
    }

    updateInfoBlock() {

        this.makeBlock(this.T1, 'infobar', 'div', (E, einit) => {
            if (einit) {
                E.className = 'wd-table-section wd-iblock';
            }

            // page length selector
            if (einit || this.ref.plen) {

                this.makeBlock(E, 'plen', 'select', (Q, qinit) => {
                    if (qinit) {
                        Q.className = 'wd-plen';
                        let page_lengths = this.cfg?.pagelengths || [1, 3, 5, 10, 25, 50, 100, 250, 500];
                        page_lengths.forEach((p) => {
                            let o = document.createElement('option');
                            o.value = p;
                            o.innerHTML = p;
                            Q.appendChild(o);
                        });
                        Q.onchange = () => _wd_debounce(this.changePageLength(Q.value), 50, 500);
                    }

                    Q.value = this.view.pageLength;
                    this.ref.plen = false;
                });
            }

            // quick search text filter
            if (einit || this.ref.sbar) {
                this.makeBlock(E, 'sbar', 'input', (Q, qinit) => {
                    if (qinit) {
                        Q.className = 'wd-sbar';
                        Q.placeholder = this.view.sbarPlaceHolder ?? '';
                    }
                });
                this.ref.sbar = false;
            }

            // modal options button
            if (einit || this.ref.obtn) {
                this.makeBlock(E, 'obtn', 'button', (Q, qinit) => {
                    if (qinit) {
                        Q.className = 'wd-obtn';
                        Q.textContent = '⚙';
                        Q.addEventListener('click', (e) => {
                            this.openModal();

                        })
                    }
                });
                this.ref.obtn = false;
            }

        });

        this.ref.iblock = false;
    }

    updateTableBlock() {

        this.makeBlock(this.T1, 'table', 'table', (E, einit) => {
            if (einit) {
                E.className = 'wd-table-section wd-tblock ';
            }

            // column group
            if (einit || this.ref.colgroup) {

                this.makeBlock(E, 'colgroup', 'colgroup', (Q, qinit) => {

                    if (!qinit || Q.childElementCount !== this.ncols) {
                        Q.replaceChildren();
                        for (let coln = 0; coln < this.ncols; coln++)
                            Q.appendChild(document.createElement('COL'));
                    }
                    // update
                    for (let i = 0; i < this.ncols; i++) {
                        const cdef = this.col[this.colorder[i]];
                        let COL = Q.children[i];
                        if (COL) {
                            if (cdef?.visible ?? true)
                                COL.style.width = (cdef?.width ?? 5) * 10 + 'rem';
                            else {
                                COL.style.width = '0px';
                            }
                        }
                    }
                });

                this.ref.colgroup = false;
            }

            // table header
            if (einit || this.ref.thead) {

                this.makeBlock(E, 'thead', 'thead', (Q, qinit) => {

                    if (qinit) {
                        Q.className = 'wd-thead';
                    }

                    // check if need to build
                    if (qinit || Q.childElementCount !== 1 || Q.children[0].childElementCount !== this.ncols) {
                        Q.replaceChildren();

                        const TR = document.createElement('tr');
                        TR.className = 'wd-thead-tr';
                        for (let coln = 0; coln < this.ncols; coln++) {
                            const TH = document.createElement('th');
                            const D = document.createElement('div');
                            D.className = 'wd-th';
                            const S1 = document.createElement('span');
                            const S2 = document.createElement('span');
                            const S3 = document.createElement('span');
                            S3.className = 'wd-th-sort';
                            D.appendChild(S1);
                            D.appendChild(S2);
                            D.appendChild(S3);
                            TH.appendChild(D);

                            const cdef = this.col[this.colorder[coln]];
                            if (cdef?.sortable ?? true) {
                                D.addEventListener('click', (e) => {
                                    _wd_wrap_click_event(e, (e) => {
                                        for (let ii = 0; ii < this.ncols; ii++) {
                                            let hdef = this.col[this.colorder[ii]];
                                            if (ii === coln) {
                                                hdef.sort = (hdef.sort <= 0) ? 1 : -1;
                                            } else {
                                                hdef.sort = 0;
                                            }
                                        }

                                        this.ref.thead = true;
                                        this.fetchPage();
                                    }, (e) => {
                                    });
                                });
                                TR.appendChild(TH);
                            }
                        }
                        Q.appendChild(TR);
                    }

                    // populate thead row
                    const TR = Q.children[0];
                    if (TR && TR.tagName === 'TR') {
                        for (let coln = 0; coln < this.ncols; coln++) {

                            const cdef = this.col[this.colorder[coln]];
                            const TH = TR.children[coln];
                            if (cdef?.visible ?? true) {
                                TH.style.display = 'table-cell';
                            } else {
                                TH.style.display = 'none';
                                TH.width = '0px';
                            }


                            if (TH) {
                                const D = TH.children[0];

                                if (D) {
                                    if (!cdef?.visible) {
                                        D.style.display = 'none';
                                    }

                                    const S1 = D.children[0];
                                    if (S1) {
                                        S1.textContent = ''; // '▸' ;
                                    }

                                    const S2 = D.children[1];
                                    if (S2) {
                                        S2.textContent = cdef?.title ?? ('_' + coln);
                                    }

                                    const S3 = D.children[2];
                                    if (S3) {
                                        if (cdef?.sortable) {
                                            D.children[2].innerText = (cdef?.sort == 0) ? '↕' : (cdef?.sort < 0 ? '▼' : '▲');
                                        } else {
                                            D.children[2].innerText = '';
                                        }
                                    }
                                }
                            }
                        }

                    }

                });

                this.ref.thead = false;
            }

            if (einit || this.ref.tbody) {

                this.makeBlock(E, 'tbody', 'tbody', (Q, qinit) => {

                    if (qinit) {
                        Q.className = 'wd-tbody';
                    }

                    // how many rows do we need to display
                    let avail = this.data.end - this.data.offset;
                    let nrows = (avail > this.view.pageLength) ? this.view.pageLength : avail;

                    if (nrows > 0) {

                        // build table structure if changed
                        if (qinit || Q.childElementCount !== nrows || Q.children[0].childElementCount !== this.ncols) {

                            Q.replaceChildren();
                            this.prevdata = [];

                            for (let rown = 0; rown < nrows; rown++) {


                                if (!this.prevdata[rown])
                                    this.prevdata[rown] = [];

                                const TR = document.createElement('tr');
                                for (let coln = 0; coln < this.ncols; coln++) {
                                    const TD = document.createElement('td');
                                    TD.className = 'wd-td';
                                    TR.appendChild(TD);
                                    this.prevdata[rown][coln] = undefined;
                                }
                                Q.appendChild(TR);
                            }
                        }

                        for (let rown = 0; rown < nrows; rown++) {
                            let row = this.data.data[rown];

                            const TR = Q.children[rown];


                            for (let coln = 0; coln < this.ncols; coln++) {
                                const TD = TR.children[coln];
                                if (TD) {

                                    const v = row[this.colorder[coln]];
                                    TD.innerHTML = v ?? '';

                                }

                            }
                        }


                    } else {
                        Q.replaceChildren();
                        Q.innerHTML = '<tr><td colspan="' + this.ncols + '">No data</td></tr>';
                    }


                });

                this.ref.tbody = false;
            }


        });

        this.ref.tblock = false;

    }

    updatePaginationBlock() {
        this.makeBlock(this.T1, 'pager', 'div', (E, einit) => {
            if (einit) {
                E.className = 'wd-table-section wd-pblock ';
            }

            if (einit || this.ref.pagenumbers) {

                this.makeBlock(E, 'pn', 'div', (Q, qinit) => {
                    if (qinit) {
                        const S1 = document.createElement('span');
                        S1.textContent = 'Page: ';
                        Q.appendChild(S1);
                        const P = document.createElement('input');
                        if (P) {
                            P.className = 'wd-pgn';
                            P.type = 'number';

                            Q.appendChild(P);
                        }

                        const S2 = document.createElement('span');

                        Q.appendChild(S2);
                    }

                    const P = Q.children[1];
                    if (P) {
                        P.min = 1;
                        P.max = this.view.npages;
                        P.value = this.view.page + 1;
                        const S = Q.children[2];
                        if (S)
                            S.textContent = ' / ' + this.view.npages;
                    }


                });

                this.ref.pagenumbers = false;
            }
        });
        this.ref.pblock = false;
    }

    refreshTable() {

        if (this.ref.all) {
            this.ref.topblock = true; // title block
            this.ref.qblock = true; // query block
            this.ref.iblock = true; // info block
            this.ref.tblock = true; // table block
            this.ref.tbody = true;
            this.ref.thead = true;
            this.ref.pblock = true; // pagination block
            this.ref.pagenumbers = true;
        }

        if (this.ref.topblock || this.ref.title || this.ref.error) {
            this.updateTitleBlock();
        }

        if (this.ref.qblock) {
            this.updateQueryBlock();
        }

        if (this.ref.iblock || this.ref.plen || this.ref.sbar || this.ref.obtn) {
            this.updateInfoBlock();
        } // iblock contains page size / search bar and options

        if (this.ref.tblock || this.ref.colgroup || this.ref.thead || this.ref.tbody) {
            this.updateTableBlock();
        }

        if (this.ref.pblock || this.ref.pagenumbers) {
            this.updatePaginationBlock();
        }

        this.ref.all = false;
    }


    fetchPage() {

        const payload = {
            defs: this.col,
            offset: this.view.page * this.view.pageLength,
            limit: this.view.pageLength,
            search: this.view.search,
        };

        this.T1.style.opacity = 0.2;
        if (this.cfg?.ajax) {

            _wd_api_fetch(this.cfg.ajax?.url, payload, (res) => {

                console.log('<data>', res);
                this.T1.style.opacity = 1;
                if (!res || res?.error) {
                    if (res?.error)
                        alert(res?.error);
                    console.error(res?.error);
                    this.data = {data: [], offset: [0], end: 0};

                } else
                    this.data = {data: res?.data || [], offset: res?.offset || payload?.offset, end: res?.end || 0};


                this.view.npages = Math.max(1, Math.ceil(this.data.end / this.view.pageLength));

                this.ref.all = true;
                this.refreshTable();

            });
        }

    }


    openModal() {

        if ( !this.MODAL )
            return ;

        const tempnode = document.getElementById('table_modal');
        if (!tempnode)
            throw new Error('template not found' );

        const frag = tempnode.content.cloneNode(true);
        if (!frag)
            throw new Error('failed to find modal template id  : ');

        this.MODAL.replaceChildren() ;

        const M = document.createElement( 'div' ) ;

        M.actionFn = (act, k, v) => {
            console.log( 'actionFn ' , act , k , v ) ;
            switch( act ) {
                case 'init' : {
                    let d = Alpine.$data(M);
                    d.data = {} ;
                }
            }
        }

        M.className = 'wd-modal-content' ;
        M.style.display = 'block';

        const xdata = "{ data: {} , init() { return this.$root.actionFn( 'init' ) } , action( t , k , v ) { this.$root.actionFn(t,k,v)} }";
        M.setAttribute('x-data', xdata);
        M.appendChild( frag ) ;

        this.MODAL.appendChild( M ) ;
        this.MODAL.style.display = 'block';

        const removeModal = () => {
            const M = this.MODAL.firstChild ;
            if ( M )
                M.remove() ;
            this.MODAL.replaceChildren() ;
            this.MODAL.style.display = 'none';
        }

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                removeModal() ;
            }
        })

        document.addEventListener('click', (e) => {
            if (e.target === this.MODAL) {
                removeModal() ;
            }
        })







        // load template



    }

}

