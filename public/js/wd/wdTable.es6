class wdTable {

    /**
     * Fixes the sequence of an input array by ensuring it contains all integers
     * from 0 to n-1, in the correct order. Removes duplicates and invalid values.
     *
     * @param {Array} arr - The input array to be fixed. If not an array, it will be treated as empty.
     * @param {number} n - The upper limit (exclusive) for the valid range of integers.
     * @return {Array} A repaired array containing integers from 0 to n-1 in order and without duplicates.
     */
    fixArraySequence(arr, n) {

        if (!Array.isArray(arr))
            arr = [];

        let seen = new Set();
        let repaired = [];

        for (let i = 0; i < arr.length; i++) {
            const idx = arr[i];
            if (Number.isInteger(idx) && idx >= 0 && idx < n && !seen.has(idx)) {
                repaired.push(idx);
                seen.add(idx);
            }
        }

        for (let i = 0; i < n; i++) {
            if (!seen.has(i)) {
                repaired.push(i);
            }
        }

        return repaired;
    }


    recalculateModalIndices(d, k, v) {
        // Recalculate for row k
        let up = -1, down = -1;
        const col_k = this.col[d.data.defs[k].pos];
        if (col_k?.moveable ?? true) {
            for (let j = k - 1; j >= 0; j--)
                if (this.col[d.data.defs[j].pos]?.moveable ?? false) {
                    up = j;
                    break;
                }
            for (let j = k + 1; j < d.data.defs.length; j++)
                if (this.col[d.data.defs[j].pos]?.moveable ?? false) {
                    down = j;
                    break;
                }
        }
        d.data.defs[k].up = up;
        d.data.defs[k].down = down;

        // Recalculate for row v
        up = -1;
        down = -1;
        const col_v = this.col[d.data.defs[v].pos];
        if (col_v?.moveable ?? true) {
            for (let j = v - 1; j >= 0; j--)
                if (this.col[d.data.defs[j].pos]?.moveable ?? false) {
                    up = j;
                    break;
                }
            for (let j = v + 1; j < d.data.defs.length; j++)
                if (this.col[d.data.defs[j].pos]?.moveable ?? false) {
                    down = j;
                    break;
                }
        }
        d.data.defs[v].up = up;
        d.data.defs[v].down = down;
    }

    setupTABLE() {

        // load user config
        this.usercfg = JSON.parse(localStorage.getItem(this.T0.id + '_cfg') ?? '{}');
        // check version matches default config
        if ((this.cfg?.version ?? 0) !== (this.usercfg?.version ?? 0))
            this.usercfg = null;

        let pos = 0;
        this.col = [];
        this.cfg.columns?.forEach(col => {
            if (col?.field) {

                this.col.push({
                    pos,   // for convenience
                    field: col?.field,
                    title: col?.title ?? col?.field ?? (pos + 1),
                    sortable: col?.sortable ?? true,
                    moveable: col?.moveable ?? true,
                    exportable: col?.exportable ?? true,
                    sort: col?.sort ?? 0,
                    width: col?.width ?? 1,
                })

                pos++;
            }
        })

        this.ncols = this.col.length;
        this.colorder = this.fixArraySequence(this.cfg?.order, this.ncols)
        // now apply user config

        if (this?.usercfg) {
            const layout = this.usercfg?.layout;
            if (layout) {

                if (Array.isArray(layout?.order))
                    this.colorder = this.fixArraySequence(layout.order, this.ncols);

                if (Array.isArray(layout?.defs)) {
                    if (layout.defs.length === this.ncols) {
                        layout.defs.forEach((def, i) => {
                            let col = this.col[def.pos];
                            col.title = def?.name;
                            col.width = def?.width;
                            col.visible = def?.vis;
                            col.exportable = def?.exp;
                            col.sort = def?.sort ?? 0;
                            this.col[def.pos] = col;
                        });
                    }
                }
            }
        }

        this.fetchPage();
    }

    setupINNER() {
        this.T1 = document.createElement('div');
        if (!this.T1)
            throw new Error('wdTable: cannot create table');

        this.T1.id = this.T0.id + '_table';
        this.T1.className = 'wd-table-box';
        const resizeFunction = _wd_debounce((entries) => {
            //
            // this.repaginate();
            // this.updateView();
        }, 100);

        this.T0.appendChild(this.T1);
        this.resizeObserver = new ResizeObserver(resizeFunction);
        this.resizeObserver.observe(this.T1);

    }


    setupMODAL() {
        this.MODAL = document.createElement('div');
        if (!this.MODAL)
            throw new Error('wdTable: cannot create modal');
        this.MODAL.id = this.T0.id + '_modal';
        this.MODAL.className = 'wd-modal overflow-hidden';
        this.MODAL.style.display = 'none';
        this.T0.appendChild(this.MODAL);
    }

    repaginate() {

        this.view.page = Math.floor(this.data.offset / this.view.pageLength);
        this.view.npages = Math.ceil(this.data.total / this.view.pageLength);
        if (this.view.page < 0)
            this.view.page = 0;

        if (this.view.page >= this.view.npages)
            this.view.page = this.view.npages - 1;

    }


    setData(data, total, offset) {

        this.data.data = data ?? [];
        this.data.offset = offset ?? 0;
        this.data.total = total ?? 0;

        this.repaginate();

        this.ref.all = true;
        this.updateView();
        this.updateView();
        this.ref.all = true;
        this.updateView();
        this.updateView();

    }


    fetchNewData() {

        if (!this?.cfg?.ajax)
            return;

        let defs = [];
        this.col?.forEach(col => {
            if (col?.field)
                defs.push({
                    field: col.field,
                    sort: (col?.sortable ? col.sort : 0),
                    vis : (col?.visible ?? true),
                    searchable: col?.searchable ?? true,
                })
        });

        const payload = {
            refresh: ++this.ref.refresh,
            defs,
            offset: this.view.page * this.view.pageLength,
            limit: this.view.pageLength,
            search: this.view.search ?? '',
        };

        // simple fetch + callback
        _wd_api_fetch(this.cfg.ajax?.url, payload, (res) => {
            if (res && res?.refresh === this.ref.refresh)
                this.setData(res?.data, res?.total, res?.offset);
        });


    }

    fetchPage = _wd_debounce(() => {
        this.fetchNewData();
    }, 200);

    makeBlock = (base, name, tag, cb) => _wd_make_block(base, name, tag, cb);


    /**
     * Constructs an instance of the class and initializes the DOM element tied to the provided `divid`.
     * Throws an error if the provided arguments are invalid or the target element is not a DIV.
     *
     * @param {string} divid - The ID of the target DIV element in the DOM.
     * @param {object} jobj - An object containing additional configuration for the construction process.
     * @return {void}
     */
    constructor(divid, jobj) {

        if (!divid || !jobj || 'string' !== typeof divid || 'object' !== typeof jobj)
            throw new Error('Invalid arguments')

        this.T0 = document.getElementById(divid)
        try {
            if (this.T0 && this.T0?.tagName === 'DIV') {
                this.T0.replaceChildren();
                this.view = {page: 0, pageLength: 10, search: ''};
                this.ref = {refresh: 0, all: true};
                this.data = {data: [], offset: 0, total: 0};
                this.cfg = jobj?.table;
                this.setupTABLE();
                this.setupMODAL();
                this.setupINNER();
                this.updateView();
            } else
                throw new Error('Invalid Divid')

        } catch (e) {
            console.log(e)
            alert(e);

        }

    }

    tableAlpineData() {
        if (!Alpine)
            throw new Error('wdTable: alpine not found');

        let d = Alpine.$data(this.MODAL.firstChild);
        if (!d)
            throw new Error('wdTable: cannot get alpine data');

        d.data = {};
        d.data.title = this.cfg?.title ?? '';
        d.data.defs = [];
        for (let k = 0; k < this.colorder.length; k++) {
            const ord = this.colorder[k];
            const col = this.col[ord];

            let up = -1, down = -1;
            if (col?.moveable ?? true) {
                for (let j = k - 1; j >= 0; j--)
                    if (this.col[this.colorder[j]]?.moveable ?? false) {
                        up = j;
                        break;
                    }

                for (let j = k + 1; j < this.colorder.length; j++)
                    if (this.col[this.colorder[j]]?.moveable ?? false) {
                        down = j;
                        break;
                    }
            }

            d.data.defs.push({
                pos: col.pos,
                up, down,
                field: col.field,
                renameable: col?.renameable ?? false,
                moveable: col?.moveable ?? true,
                // user can change these in modal
                sortable : ( col?.sortable ?? true),
                sort: (col?.sortable ?? true) ? col?.sort : undefined,

                name: col.title,
                width: col?.width ?? 1,
                vis: col?.visible ?? true,
                exp: col?.exportable ?? true,

            })


        }

        return d;
    }

    tableAction(act, k, v) {

        const moveModalRow = (k, v, dir) => {
            if (v >= 0) {
                if (k !== v) {
                    let d = Alpine.$data(this.MODAL.firstChild);
                    // Mark both rows for animation BEFORE swapping
                    d.data.defs[k]._animate = 'out-' + dir;
                    d.data.defs[v]._animate = 'in-' + dir;

                    // This way, Alpine updates while rows are hidden/fading
                    setTimeout(() => {
                        let tmp = d.data.defs[k];
                        d.data.defs[k] = d.data.defs[v];
                        d.data.defs[v] = tmp;

                        // Recalculate up/down indices for BOTH affected rows
                        this.recalculateModalIndices(d, k, v);

                        // Clear animation flags after animation completes
                        setTimeout(() => {
                            d.data.defs[k]._animate = null;
                            d.data.defs[v]._animate = null;
                        }, 50);
                        // ← Removed: this.setupTABLE()
                    }, 450);
                }
            }
        }

        const modalSortChange = (k) => {
            let d = Alpine.$data(this.MODAL.firstChild);
            if (!d || !d.data || !d.data.defs)
                return;

            /*
            for (let i = 0; i < this.ncols; i++) {
                const idx = this.colorder[i];
                if (i === colidx)
                    this.col[idx].sort = (this.col[idx].sort < 0) ? 1 : -1;
                else
                    this.col[idx].sort = 0;
            }
            */
            for ( let i = 0 ; i < this.ncols ; i++ ) {
                const usr = d.data.defs[i] ;
                if ( usr?.sortable ) {
                    if ( i === k )
                        usr.sort = ( usr.sort < 0 ) ? 1 : -1 ;
                    else if ( usr.sort !== 0 )
                        usr.sort = 0 ;

                }


            }
        }
        const saveModal = () => {
            let d = Alpine.$data(this.MODAL.firstChild);
            if (!d || !d.data || !d.data.defs)
                return;

            const newOrder = [];
            const newDefs = [];
            for (let k = 0; k < d.data.defs.length; k++) {
                const usr = d.data.defs[k];
                newOrder.push(usr.pos);
                newDefs.push({
                    pos: usr?.pos,
                    name: usr?.name,
                    width: +(usr?.width ?? 1),
                    vis: usr?.vis ?? true,
                    exp: usr?.exp ?? true,
                    sort: usr?.sort ?? undefined
                });
            }
            if (!this.usercfg)
                this.usercfg = {};
            this.usercfg.version = this.cfg?.version ?? 0;
            this.usercfg.layout = {
                order: newOrder,
                defs: newDefs,
            }
            localStorage.setItem(this.T0.id + '_cfg', JSON.stringify(this.usercfg));
            this.setupTABLE();


        }

        const resetModal = () => {
            localStorage.removeItem(this.T0.id + '_cfg');
            this.usercfg = {};
            this.setupTABLE();
            this.tableAlpineData(Alpine.$data(this.MODAL.firstChild))

        }

        switch (act) {
            case 'modal_init' :
                const xdata = "{ data: {} , " +
                    "init() { return this.$root.modalFn( 'init' ) } , " +
                    "action( t , k , v ) { this.$root.modalFn(t,k,v)} }";
                k.setAttribute('x-data', xdata);
                // this._undo = this.col;
                break;
            case 'init' :
                return this.tableAlpineData();
            case 'reset' :
                resetModal();
                break;
            case 'close' :
                _wd_close_modal_template(this.MODAL);
                break;
            case 'save' :
                saveModal();
                _wd_close_modal_template(this.MODAL);
                break;
            case 'up' :
                moveModalRow(k, v?.up, 'up');
                break;
            case 'down' :
                moveModalRow(k, v?.down, 'down');
                break;
            case 'sort' :
                modalSortChange( k  ) ;
                break ;
        }

    }

    updateCaption() {
        this.makeBlock(this.T1, 'cap', 'div', (E, einit) => {
            if (einit)
                E.className = 'wd-table-section wd-cap-block';
            E.textContent = this.cfg?.title ?? 'Table';
        })
        this.ref.capBlock = false;

    }

    updateHeaderBlock() {

        this.makeBlock(this.T1, 'head', 'div', (E, einit) => {
            if (einit)
                E.className = 'wd-table-section wd-head-block';

            // page length box
            if (this.ref.headPageLen) {
                this.makeBlock(E, 'pagelen', 'select', (Q, qinit) => {
                    if (qinit) {
                        Q.className = 'wd-page-len';
                        [1, 3, 5, 10, 15, 20, 25, 50, 100, 200, 500].forEach(v => {
                            let O = document.createElement('option');
                            O.value = +v;
                            O.textContent = v;
                            Q.appendChild(O);
                        })
                        Q.addEventListener('change', () => {
                            this.view.pageLength = Q.value;
                            this.fetchPage();
                        })
                    }
                    Q.value = this.view.pageLength;
                })
                this.ref.headPageLen = false;
            }

            // search box
            if (einit || this.ref.headSearchBar) {
                this.makeBlock(E, 'search', 'input', (Q, qinit) => {
                    if (qinit) {
                        Q.className = 'wd-search-box';
                        Q.setAttribute('type', 'text');
                        Q.setAttribute('placeholder', 'Search');
                        Q.setAttribute('spellcheck', 'off');
                        Q.plceholder = 'Search';
                        Q.addEventListener('input', () => {
                            this.view.search = Q.value;
                            this.fetchPage();
                        })

                    }
                    Q.value = this.view.search;


                })

                this.ref.headSearchBar = false;
            }

            // modal button
            if (einit || this.ref.headControl) {
                this.makeBlock(E, 'open-modal', 'button', (Q, qinit) => {
                    if (qinit) {
                        Q.className = 'wd-open-modal-btn';
                        Q.textContent = 'Options';
                        Q.addEventListener('click', () => {
                            _wd_open_modal_template(this.MODAL,
                                'table_modal',
                                (act, k, v) => this.tableAction(act, k, v));

                        })
                    }
                })
            }
        })

    }


    updateTableView() {

        const setTH = (TH, i) => {
            const cdef = this.col[this.colorder[i]];
            const D = TH?.firstChild;
            if (D) {
                D.children[0].textContent = '';
                D.children[1].textContent = cdef.title;
                D.children[2].className = 'ml-1 py-2 mr-1';
                if (cdef?.sortable ?? true)
                    D.children[2].textContent = (cdef?.sort > 0) ? '▲' : ((cdef?.sort < 0) ? '▼' : '⬩');
                else
                    D.children[2].textContent = '';
            }


        }

        const toggleSort = (colidx) => {

            for (let i = 0; i < this.ncols; i++) {
                const idx = this.colorder[i];
                if (i === colidx)
                    this.col[idx].sort = (this.col[idx].sort < 0) ? 1 : -1;
                else
                    this.col[idx].sort = 0;
            }

            this.fetchPage();

        }


        this.makeBlock(this.T1, 'table', 'table', (E, einit) => {

            if (einit) {
                E.className = 'wd-table-section wd-table-block';
                E.appendChild(document.createElement('colgroup'));
                E.appendChild(document.createElement('thead'));
                E.appendChild(document.createElement('tbody'));
                E.appendChild(document.createElement('tfoot'));
            }

            // section 0 - colgroup
            if (einit || this.ref.tableColGroup) {
                let Q = E?.children?.[0];
                if (Q.childElementCount !== this.ncols)
                    Q.innerHTML = '';
                let sum = 0;
                this.colorder.forEach(i => sum += ((this.col[i]?.visible ?? true) ? this.col[i].width : 0));
                for (let i = 0; i < this.ncols; i++) {
                    let COL = Q.children[i];
                    if (!COL) {
                        COL = document.createElement('col');
                        Q.appendChild(COL);
                    }
                    const cdef = this.col[this.colorder[i]];
                    let w = Math.floor((cdef?.visible ?? true) ? cdef.width : 0);
                    if (cdef?.visible ?? true) {
                        COL.style.width = (sum > 0) ? (Math.floor(((w * 100) / sum)) + '%') : '0';
                        COL.style.visibility = 'visible';
                    } else {
                        COL.style.width = '0px';
                        COL.style.visibility = 'collapse';

                    }
                }
                this.ref.tableColGroup = false;
            }

            // section 1 - thead
            if (einit || this.ref.tableHeader) {
                let Q = E?.children?.[1];
                let TR = Q?.firstChild;
                if (!TR) {
                    TR = document.createElement('tr');
                    TR = document.createElement('tr');
                    Q.appendChild(TR);
                }


                if (TR.childElementCount !== this.ncols)
                    TR.innerHTML = '';

                for (let i = 0; i < this.ncols; i++) {
                    let TH = TR.children[i];
                    if (!TH) {
                        TH = document.createElement('th');
                        TH.className = 'wd-th';
                        const D = document.createElement('div');
                        D.setAttribute('role', 'button');
                        D.setAttribute('tabindex', '0');
                        D.className = 'wd-th-inner';
                        D.appendChild(document.createElement('div'));
                        D.appendChild(document.createElement('div'));
                        D.appendChild(document.createElement('div'));

                        TH.appendChild(D);
                        TH.addEventListener('click', () => {
                            toggleSort(i)

                        })
                        TR.appendChild(TH);
                    }
                    // TH.textContent = this.col[this.colorder[i]].title;
                    setTH(TH, i);
                }


                this.ref.tableHeader = false;
            }

            // section 2 - tbody
            if (einit || this.ref.tableBody) {

                let Q = E?.children?.[2];
                // number of rows in this data page
                const nrows = Math.min(this.view.pageLength, this?.data?.data?.length ?? 0);
                // dont have enougth <tr>'s
                if (Q.childElementCount !== nrows) {
                    Q.replaceChildren();
                    for (let i = 0; i < nrows; i++)
                        Q.appendChild(document.createElement('tr'));
                }

                for (let i = 0; i < nrows; i++) {

                    let TR = Q.children[i];
                    TR.innerHTML = '';
                    for (let j = 0; j < this.ncols; j++)
                        TR.appendChild(document.createElement('td'));

                    let datrow = this.data.data[i];
                    for (let j = 0; j < this.ncols; j++) {
                        const TD = TR.children[j];
                        TD.textContent = datrow[this.colorder[j]] ?? '';
                    }

                }


                this.ref.tableBody = false;

            }


            // section 3 - tfoot
            if (einit || this.ref.tableFooter) {

                let Q = E?.children?.[3];
                let TR = Q?.firstChild;
                if (!TR) {
                    TR = document.createElement('tr');
                    Q.appendChild(TR);
                }

                if (TR.childElementCount !== this.ncols)
                    TR.innerHTML = '';

                for (let i = 0; i < this.ncols; i++) {
                    let TD = TR.children[i];
                    if (!TD) {
                        TD = document.createElement('th');
                        TD.className = 'wd-tf';
                        TR.appendChild(TD);
                    }
                    // TD.textContent = this.col[this.colorder[i]].title;
                    TD.textContent = '';
                }

                this.ref.tableFooter = false;

            }

        })

        this.ref.tableBlock = false;
    }

    updateFooterBlock() {

        this.makeBlock(this.T1, 'foot', 'div', (E, einit) => {
            if (einit)
                E.className = 'wd-table-section wd-foot-block';

            if ( einit || this.ref.footPageInfo ) {

            }
        });
        this.ref.footBlock = false;
    }

    updateView() {

        if (this.ref.all) {
            this.ref.capBlock = true;
            this.ref.headBlock = true;
            this.ref.tableBlock = true;
            this.ref.footBlock = true ;
        }

        if (this.ref.capBlock)
            this.updateCaption();

        if (this.ref.headBlock) {
            this.ref.headPageLen = true;
            this.ref.headSearchBar = true;
            this.ref.headControl = true;
        }

        if (this.ref.headBlock || this.ref.headPageLen || this.ref.headSearchBar || this.ref.headControl)
            this.updateHeaderBlock();

        if (this.ref.tableBlock) {
            this.ref.tableColGroup = true;
            this.ref.tableHeader = true;
            this.ref.tableBody = true;
            this.ref.tableFooter = true;
        }

        if (this.ref.tableBlock || this.ref.tableColGroup || this.ref.tableHeader || this.ref.tableBody || this.ref.tableFooter)
            this.updateTableView();

        if ( this.ref.footBlock ) {
            this.ref.footPageInfo = true ;
            this.ref.footPageList = true ;
        }

        if ( this.ref.footPageInfo || this.ref.footPageList )
            this.updateFooterBlock() ;


        this.ref.all = false;
    }

}