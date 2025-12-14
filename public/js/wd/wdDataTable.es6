class wdDataTable {


    constructor(id, opts, cfg, action = null) {

        // cre
        this.T1 = document.createElement('div');
        this.T1.id = this.domid = id;
        if (!this.T1)
            throw new Error('wdDataTable: Could not create table container') ;

        this.setup(opts, cfg, action);
        this.loadColumnDefs();

        this.savecfg = false;
        this.hpick = -1;

    }

    _debouncedFetch = _wd_debounce(() => {
        this.fetchPage();
    }, 200);

    fetchSoon = () => {
        if (this.TBL)
            this.TBL.style.opacity = '0.7';

        this._debouncedFetch();
    }



    attach(show = true) {

        if (this.T1)
            this.T1.replaceChildren();
        else
            return;

        const resizeFunction = _wd_debounce((entries) => {
            let w = entries[0].contentRect.width;
            if (w > 0 && (Math.abs(this.view.tableWidth - w) > 10)) {
                this.ref.pagePager = true;
                this.updateDOM();
            }
        }, 200);

        this.T1.style.opacity = '0.0';
        try {
            this.upd.all = true;
            this.updateDOM()
            this.resizeObserver = new ResizeObserver(resizeFunction);
            this.resizeObserver.observe(this.T1);
            this.upd.all = true;
            this.fetchSoon();
        } finally {
            this.T1.style.opacity = show ? '1' : '0';
        }
        return this.T1;
    }

    detach() {
        if (this.resizeObserver)
            this.resizeObserver.disconnect()
        this.T1.replaceChildren();
    }


    setup(opts, cfg, action) {

        this.opts = opts ?? {};
        this.cfg = cfg = (cfg || this.cfg);
        this.actionCB = (action || this.actionCB);


        this.upd = {
            all: true,
        };

        this.ref = {
            hpick: -1,
        };


        this.pager = {
            width: 0,
            page: -1,
            npages: -1,
            nrows: 0,
            pagelist: [[]],
        }

        this.view = {
            refresh: 0,
            offset: 0,
            length: 5,
            pagecalc: false,
            tableWidth: -100,
            nslots: 1,
            page: 1,
            npages: 1,
            nrows: 0,
            data: []
        }

        this.uuid = '_wd_tblkey_' + this.domid + '_' + (cfg?.version ?? '0');
        this.highlighter = (typeof TextHighlighter !== 'undefined') ? new TextHighlighter({className: 'wd-highlight'}) : null;
        this.hpick = -1; // no header currently picked
        this.view.pageLength = 5;


    }

    loadColumnDefs() {
        if (!this?.cfg?.columns)
            throw new Error('no columns defined');

        this.ncols = this.cfg.columns?.length || 0;
        // default mapping 0->0, 1->1 , 2->2 ....
        this.colOrder = [];
        this.colHead = [];
        this.coldefs = [];

        for (let i = 0; i < this.ncols; i++) {
            let c = this.cfg?.columns[i];
            this.colOrder[i] = i;
            this.colHead[i] = c?.head ?? '';
            this.coldefs[i] = {
                f: c?.field || i,
                sortable: c?.sortable || true,
                searchable: c?.searchable || true,
                sort: c?.sort || 0,
                size: c?.size ?? 1,
                type: c?.type || false,
                vis: c?.vis || true,
                exp: c?.exp || true
            }
        }
        if (this.uuid)
            this.loadTableFromMeta(this.uuid);
    }

    loadTableFromMeta(lkey) {

        let cfg = this?.cfg;
        if (!cfg)
            return;


        let jdef = localStorage.getItem(lkey);
        this.defcfg = jdef ? JSON.parse(jdef) : false;

        if (!this.defcfg || this.defcfg?.defs?.length !== this.ncols)
            return;

        let dup = [];
        let z = 0;
        if (this.defcfg?.defs) {

            this.defcfg?.defs.forEach((d, i) => {
                let j = d?.colno ?? -1;
                if (j >= 0 && j <= this.ncols && !(dup.includes(d.colno))) {
                    dup.push(j);

                    z += (j + 1);
                    this.colOrder[i] = j;
                    if (d?.name !== null) this.colHead[j] = d.name;
                    if (d?.size !== null) this.coldefs[j].size = d.size;
                    if (d?.vis !== null) this.coldefs[j].vis = d.vis;
                    if (d?.exp !== null) this.coldefs[j].exp = d.exp;
                }
            })

            let target = Math.floor(((this.ncols + 1) * this.ncols) * 0.5);
            if (target !== z)
                throw new Error('duplicate column definitions detected');
        }

    }





    fillMetaFromTable(d) {

        let defs = []
        for (let i = 0; i < this.ncols; i++) {
            let j = this.colOrder[i] ?? i;
            defs.push({
                colno: j,
                name: this.colHead[j],
                size: this.coldefs[j].size,
                vis: this.coldefs[j]?.vis ?? true,
                exp: this.coldefs[j]?.exp ?? true
            });
        }

        d.data.title = this.cfg?.title ?? '';
        d.data.defs = defs;
    }


    createOptionsModal(template_name) {

        this.makeModal((M) => {

            if (!(M instanceof HTMLDivElement))
                throw new Error('invalid modal element not a div : ' + template_name);

            const tempnode = document.getElementById(template_name);
            if (!tempnode)
                throw new Error('template not found : ' + template_name);

            const frag = tempnode.content.cloneNode(true);
            if (!frag)
                throw new Error('failed to clone template : ' + template_name);

            let refreshMeta = () => {
                let d = Alpine.$data(M);
                this.fillMetaFromTable(d);
                this.upd.all = true;
                this.updateDOM();
            }


            if (!M.actionFn) {
                M.actionFn = (act, k, v) => {

                    switch (act) {
                        case 'init' : {

                            let d = Alpine.$data(M);
                            this.fillMetaFromTable(d);
                            localStorage.setItem(this.uuid + '_undo', JSON.stringify(d.data));
                            return;
                        }
                        case 'close' :

                            if (M && M.parentElement)
                                M.parentElement.remove();
                            return;
                        case 'name' :
                            if (k >= 0 && k < this.ncols) {
                                if (typeof v === 'string')
                                    this.colHead[this.colOrder[k]] = v;
                            }
                            break;
                        case 'up' :
                            if (k > 0) {
                                let tmp = this.colOrder[k - 1];
                                this.colOrder[k - 1] = this.colOrder[k];
                                this.colOrder[k] = tmp;
                            }
                            break;
                        case 'down' :
                            if (k < this.ncols - 1) {
                                let tmp = this.colOrder[k + 1];
                                this.colOrder[k + 1] = this.colOrder[k];
                                this.colOrder[k] = tmp;
                            }
                            break;
                        case 'size' :
                            if (k >= 0 && k < this.ncols)
                                this.coldefs[this.colOrder[k]].size = v;
                            break;
                        case 'vis' :
                            if (k >= 0 && k < this.ncols)
                                this.coldefs[this.colOrder[k]].vis = (!(v))
                            break;
                        case 'exp' :
                            if (k >= 0 && k < this.ncols)
                                this.coldefs[this.colOrder[k]].exp = (!(v))
                            break;
                        case 'title' :
                            if (typeof v === 'string')
                                this.cfg.title = v;
                            break;
                        case 'undo' :
                            this.loadTableFromMeta(this.uuid + '_undo')
                            this.loadColumnDefs();
                            break;
                        case 'reset' :
                            localStorage.removeItem(this.uuid);
                            this.loadColumnDefs();
                            break;
                        case 'apply':

                            let d = Alpine.$data(M);
                            this.fillMetaFromTable(d);

                            localStorage.setItem(this.uuid, JSON.stringify(d.data));
                            this.loadColumnDefs();
                            refreshMeta(M);
                            if (M && M.parentElement)
                                M.parentElement.remove();
                            break;
                    }
                    refreshMeta(M);
                }
            }

            const xdata = "{ data: {} , init() { return this.$root.actionFn( 'init' ) } , action( t , k , v ) { this.$root.actionFn(t,k,v)} }";
            M.setAttribute('x-data', xdata);
            M.appendChild(frag);


        });

    }

    updateTitleBlock() {
        _wd_makeBlock(this.T1, 'titleblock', 'div', (E, init) => {
            if (init)
                E.className = 'wd-section wd-titleblock';

            if (init || this.ref.title) {

                _wd_makeBlock(E, 'title', 'h1', (T, isnew) => {
                    if (isnew)
                        T.className = 'wd-title';
                    T.textContent = this.cfg?.title ?? '';

                })

            }
            this.ref.title = false;
        });

    }

    updateQBarTypeBar(QBAR, q) {

        if (q?.list) {

            q.list.forEach((item, i) => {
                let B = document.createElement('button');
                B.className = 'wd-qbar-item';
                console.log('qq', q.value, i)
                if (q?.value === i)
                    B.classList.add('active');
                B.textContent = item;
                B.addEventListener('click', (e) => {
                    q.value = i;
                    this.fetchSoon();
                });
                QBAR.appendChild(B);
            })
        } else
            throw new Error('empty qbar list') ;

    }

    updateQbarBlock() {

        if (this.cfg?.qbar) {
            _wd_makeBlock(this.T1, 'qbarblock', 'div', (E, init) => {
                if (init)
                    E.className = 'wd-section flex flex-wrap wd-qbarblock';
                else
                    E.replaceChildren();

                if (init || this.ref.qbar) {
                    if (Array.isArray(this.cfg.qbar)) {
                        this.cfg.qbar.forEach((q, i) => {
                            _wd_makeBlock(E, 'qbar' + i, 'div', (QBAR, isnew) => {
                                if (isnew)
                                    QBAR.className = 'wd-qbar';
                                else
                                    QBAR.replaceChildren();

                                if (!(q?.name))
                                    q.name = 'qbar_' + i;


                                if (q?.value === undefined)
                                    q.value = q?.default ?? false;

                                // draw each qbar item block
                                switch (q.type) {
                                    case 'bar' :
                                        this.updateQBarTypeBar(QBAR, q);
                                        break;
                                    default:
                                        throw new Error('unknown qbar type : ' + q.type);
                                }

                                E.appendChild(QBAR);
                            })
                        })
                    }
                }

                this.ref.qbar = false;
            });
        }

    }


    updatePageLengthControl(E) {

        _wd_makeBlock(E, 'pagelength', 'select', (Q, init) => {
            if (init) {
                Q.className = 'wd-pagelength';
                Q.addEventListener('change', (e) => {
                    this.changePageLength(+e.target.value);
                });
            }

            Q.replaceChildren();

            let page_lengths = [1, 3, 5, 10, 25, 50, 100];

            page_lengths.forEach((p) => {
                let o = document.createElement('option');
                o.value = p;
                o.innerHTML = p;
                Q.appendChild(o);
            })

            Q.value = this.view.pageLength;

        })
    }

    changePageLength(pl) {

        const p = Math.floor(this.view.offset / pl) + 1;
        this.view.offset = (p - 1) * pl;
        this.view.pageLength = pl;
        this.pager.page = -1;
        this.fetchSoon();
    }

    changePage(p) {

        this.view.offset = (p - 1) * this.view.pageLength;
        this.ref.pagePager = true;
        this.fetchSoon();
    }

    updatePageSearchControl(E) {
        _wd_makeBlock(E, 'pagesearch', 'input', (Q, init) => {
            if (init) {
                Q.type = 'text';
                Q.className = 'wd-pagesearch';
                Q.placeholder = 'Search ...';
                Q.spellcheck = false;
                Q.addEventListener('input', (e) => {
                    // search
                    this.view.search = e.target.value;
                    this.fetchSoon();
                });
                this.ref.pagesearch = false;
            }
        })
    }


    updatePageOptionsControl(E) {

        _wd_makeBlock(E, 'optionbutton', 'button', (Q, init) => {

            if (init) {
                Q.className = 'wd-pageoptions';
                Q.type = 'button';
                Q.textContent = '⚙';
                Q.addEventListener('click', () => this.createOptionsModal(this.cfg?.modal ?? 'modal_options'));
            }


        })

        this.ref.pageoptions = false;
    }


    updateTopBlock() {

        _wd_makeBlock(this.T1, 'topblock', 'div', (E, init) => {
            if (init)
                E.className = 'wd-section wd-topblock';

            // page length control
            if (init || this.ref.pageLengthSelect)
                this.updatePageLengthControl(E);
            this.ref.pageLengthSelect = false;

            // page search input
            if (init || this.ref.pageSearchInput)
                this.updatePageSearchControl(E);
            this.ref.pageSearchInput = false;

            // page options button
            if (init || this.ref.pageOptionButton)
                this.updatePageOptionsControl(E);
            this.ref.pageOptionButton = false;


        })


    }

    updateTableColgroupControl(E) {
        _wd_makeBlock(E, 'colgroup', 'colgroup', (Q, init) => {
            if (init)
                Q.className = 'wd-colgroup';
            else
                Q.replaceChildren();

            if (this?.cfg?.multi) {
                let COLPICK = document.createElement('col');
                COLPICK.style.width = '24px';
                Q.appendChild(COLPICK);
            }

            for (let i = 0; i < this.ncols; i++) {
                let COL = document.createElement('col');
                if (!COL) {
                    COL = document.createElement('col');
                    Q.appendChild(COL);
                }
                const cdef = this.coldefs?.[this.colOrder[i] || i];
                if (cdef?.size > 0 && cdef?.vis === true) {
                    COL.style.width = cdef?.size + 'rem';
                    COL.style.visibility = '';
                } else {
                    COL.style.visibility = 'collapse';
                    COL.style.width = '0px';
                }


            }
            this.ref.colgroup = false;
        })

    }

    changeSortOrder(at, ctrl = false) {

        const j = this.colOrder[at] || 0;

        if (ctrl && ((this.opts?.allowMultiSort ?? false))) {
            let sort = this.coldefs[j].sort;
            this.coldefs[j].sort = (sort === 0) ? 1 : -sort;
        } else for (let k = 0; k < this.ncols; k++) {
            if (k !== j)
                this.coldefs[k].sort = 0;
            else {
                let sort = this.coldefs[j].sort;
                this.coldefs[j].sort = (sort === 0) ? 1 : -sort;
            }
        }

        this.fetchSoon();


    }


    updateTableTableHead(E) {
        _wd_makeBlock(E, 'thead', 'thead', (Q, init) => {
            if (init)
                Q.className = 'wd-thead';
            else
                Q.replaceChildren();


            let TR = document.createElement('tr');
            if (this?.cfg?.multi) {
                let TH = document.createElement('th');
                if (this?.cfg?.multihead) {
                    const I = document.createElement('input');
                    I.type = 'checkbox';
                    TH.appendChild(I);
                    I.addEventListener('change', (e) => {
                        this.picked_all = !this.picked_all ?? true;
                        if (!this.picked)
                            this.picked = [];


                    })
                }
                TR.appendChild(TH);
            }
            for (let i = 0; i < this.ncols; i++) {
                let j = this.colOrder[i] ?? i;
                let cdef = this.coldefs[j];

                let TH = document.createElement('th');

                if (i == this.hpick)
                    TH.classList.add('wd-th-pick');

                const S = document.createElement('span');
                const S1 = document.createElement('span');
                const S2 = document.createElement('span');
                const S3 = document.createElement('span');


                S1.textContent = '';
                S.appendChild(S1);


                S2.textContent = (this.colHead?.[j] ?? '');
                S.appendChild(S2);

                if (cdef?.sortable) {
                    let sort = cdef?.sort ?? 0;
                    S3.innerText = (sort == 0) ? '↕' : (sort < 0 ? '▼' : '▲');
                    S.appendChild(S3);
                }

                S.addEventListener('click', (e) => {
                    _wd_check_double_click_event(e,
                        // Single click handler
                        (e) => {
                            this.changeSortOrder(i, e.ctrlKey);

                        },
                        // Double click handler
                        (e) => {
                        }
                    );
                })
                TH.appendChild(S);


                TR.appendChild(TH);
            }

            Q.replaceChildren(TR);

            this.ref.thead = false;
        })


    }

    updateTableDataCell(TD, rowdata, col) {

        let j = this.colOrder[col] ?? col;
        let cdef = this.coldefs[j];
        let v = rowdata[j];

        switch (cdef?.type) {

            case 'iso-date-str':
                if (v)
                    v = new Date(v).toLocaleDateString();
                TD.classList.add('wd-right-align');
                break;
            case 'boolean' :
                TD.classList.add('wd-center-align');
                v = (v === true || v === 'true' || v === 1) ? '✓' : '✕';
                break;
            case 'button' :
                const B = document.createElement('button');
                B.className = "w-fit px-2 py-1 rounded-md bg-blue-500 text-white";
                B.textContent = 'View';
                /*
                B.addEventListener('click', (e) => {
                    this.actionCB('T', 'view', v, rowdata);
                })

                 */
                TD.classList.add('wd-right-align')
                TD.appendChild(B);
                return;

            default:
                break;

        }

        if (this.view.search !== '' && cdef?.searchable && this.highlighter)
            TD.innerHTML = this.highlighter.highlight(v, this.view.search);
        else
            TD.textContent = v


    }

    updateTableTableBody(E) {
        _wd_makeBlock(E, 'tbody', 'tbody', (Q, init) => {
            if (init)
                Q.className = 'wd-tbody';

            Q.replaceChildren();
            for (let r = 0; r < this.view.pageLength; r++) {
                let TR = document.createElement('tr');
                let rowdata = this.view.data?.[r] ?? false;
                if (rowdata) {
                    if (this?.cfg?.multi) {
                        let TD = document.createElement('td');
                        let I = document.createElement('input');
                        I.type = 'checkbox';
                        I.checked = this.view?.picked?.[r] ?? false;
                        TD.appendChild(I);
                        TR.appendChild(TD);
                    }
                    for (let c = 0; c < this.ncols; c++) {
                        let TD = document.createElement('td');
                        TR.appendChild(TD);
                        this.updateTableDataCell(TD, rowdata, c);
                        if (c == this.hpick)
                            TD.classList.add('wd-td-pick');

                    }
                    Q.appendChild(TR);
                }

            }

        })

    }

    updateTableTableFoot(E) {
        _wd_makeBlock(E, 'tfoot', 'tfoot', (Q, init) => {
            if (init)
                Q.className = 'wd-tfoot';
            this.ref.tfoot = false;
        })

    }

    calcPage() {
    }

    updatePageInfoControl(E) {
        _wd_makeBlock(E, 'pageinfo', 'div', (Q, init) => {
            if (init)
                Q.className = 'wd-pageinfo';

            if (this.ref.pageInfo) {
                _wd_makeBlock(Q, 'pageinfo-text', 'span', (T, isnew) => {
                    if (isnew)
                        T.className = 'wd-pageinfo-text';
                    T.textContent = 'Page ' + this.view.page + ' of ' + this.view.npages + ' (' + this.view.nrows + ' rows)';
                })
            }


            this.ref.pageInfo = false;
        })
    }

    // N = number of pages , P = current page, S = number of slots to show ( approximate always > 5 )
    splitPageNumbersForSlots(nPages, page, slots) {

        if (slots > 50)
            slots = 50;
        else if (slots < 5)
            slots = 5;

        let groups, newgroups;
        let start = [1];
        let left = [page - 1];
        let right = [page + 1];
        let end = [nPages];

        let iter = 2;  // Start at 2 since we already have P±1
        while (iter < slots) {
            groups = [];
            groups.push(1, ...left, page, ...right, ...end);
            newgroups = groups.filter((value, index, self) =>
                value >= 1 && value <= nPages &&
                index === self.findIndex((t) => (t === value))
            ).sort((a, b) => a - b);

            if (slots <= 5 || newgroups.length > slots - 3)
                break;

            start.push(iter + 1);

            left.push(page - iter, page - iter - 1);
            right.push(page + iter, page + iter + 1);
            end.push(nPages - iter + 1);
            iter++;
        }

        return newgroups;
    }


    updatePagePagerControl(E) {

        if (!E)
            return;

        let w = (E?.clientWidth - 160);


        if (this.pager.width !== w ||
            this.pager.npages !== this.view.npages ||
            this.pager.page !== this.view.page ||
            this.pager.nrows !== this.view.nrows) {

            this.pager.width = w;
            this.pager.npages = this.view.npages;
            this.pager.page = this.view.page;
            this.pager.nrows = this.view.nrows;

            let nslots = Math.floor(w / 80);
            this.pager.pageList = this.splitPageNumbersForSlots(this.view.npages, this.view.page, nslots);

        }

        _wd_makeBlock(E, 'pagepager', 'nav', (Q, init) => {
            if (init)
                Q.className = 'wd-pagelist';

            Q.replaceChildren();
            let UL = document.createElement('ul');

            for (let i = 0; i < this.pager.pageList.length; i++) {
                let LI = document.createElement('li');
                const p = this.pager.pageList[i];
                LI.textContent = p;
                if (p === this.view.page)
                    LI.classList.add('wd-active');
                else
                    LI.addEventListener('click', (e) => {
                        this.changePage(+e.target.textContent)
                    })

                UL.appendChild(LI);
            }

            Q.appendChild(UL);
            this.ref.pagePager = false;

        })
    }


    updateTableBlock() {
        _wd_makeBlock(this.T1, 'tableblock', 'table', (E, init) => {

            this.TBL = E;

            if (init)
                E.className = 'wd-table';

            if (init || this.ref.tblColgroup)
                this.updateTableColgroupControl(E);
            this.ref.tblColgroup = false;

            if (init || this.ref.tblHead || this.ref.tblStyle)
                this.updateTableTableHead(E);
            this.ref.tblHead = false;

            if (init || this.ref.tblBody || this.ref.tblStyle)
                this.updateTableTableBody(E);
            this.ref.tblBody = false;

            if (init || this.ref.tblFoot)
                this.updateTableTableFoot(E);
            this.ref.tblFoot = false;

            this.ref.tblStyle = false;

        })
    }

    updateBotBlock() {
        _wd_makeBlock(this.T1, 'botblock', 'div', (E, init) => {
            if (init)
                E.className = 'wd-section wd-botblock';

            if (init || this.ref.pageInfo)
                this.updatePageInfoControl(E);

            if (init || this.ref.pagePager)
                this.updatePagePagerControl(E);


            this.ref.pageInfo = false;

        })
    }


    updateDOM() {

        if (this.upd.all) {
            this.upd.titleBlock = true;
            this.upd.qblock = true;
            this.upd.topBlock = true;
            this.upd.tableBlock = true;
            this.upd.botBlock = true;

        }

        if (this.upd.titleBlock) {
            this.ref.title = true;
            this.upd.titleBlock = false;
        }

        if (this.ref.title)
            this.updateTitleBlock();


        if (this.upd.qblock) {
            this.ref.qbar = true;
            this.upd.qblock = false;
        }

        if (this.ref.qbar)
            this.updateQbarBlock();


        if (this.upd.topBlock) {
            this.ref.pageLengthSelect = true;
            this.ref.pageSearchInput = true;
            this.ref.pageOptionButton = true;
            this.upd.topBlock = false;
        }

        if (this.ref.pageLengthSelect || this.ref.pageSearchInput || this.ref.pageOptionButton)
            this.updateTopBlock();


        if (this.ref.hpick !== this.hpick) {
            this.ref.tblStyle = true;
            this.ref.hpick = this.hpick;
        }

        if (this.upd.tableBlock) {
            this.ref.tblColgroup = true;
            this.ref.tblHead = true;
            this.ref.tblBody = true;
            this.ref.tblFoot = true;
            this.upd.tableBlock = false;
        }

        if (this.ref.tblColgroup || this.ref.tblHead || this.ref.tblBody || this.ref.tblFoot || this.ref.tblStyle)
            this.updateTableBlock();


        if (this.upd.botBlock) {
            this.ref.pageInfo = true;
            this.ref.pagePager = true;
            this.upd.botBlock = false;
        }

        if (this.ref.pageInfo || this.ref.pagePager)
            this.updateBotBlock();

        if (this.ref.hpick !== this.hpick)
            this.ref.hpick = this.hpick;

        this.upd.all = false;
        if (this.TBL)
            this.TBL.style.opacity = '1.0';
    }

    setPageData(data, ntotal) {


        this.view.data = data;
        this.view.nrows = ntotal;
        this.view.npages = (this.view.pageLength > 0) ? Math.ceil(ntotal / this.view.pageLength) : 1;
        this.view.page = (this.view.pageLength > 0) ? Math.floor(this.view.offset / this.view.pageLength) + 1 : 1;
        // make sure we are not off the end of the data
        if (this.view.page > this.view.npages) {
            this.view.offset = (this.view.npages - 1) * this.view.pageLength;
            this.fetchSoon();
            return;
        }

        this.upd.all = true;
        this.updateDOM();


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
            // console.error(e);
            return Promise.reject(e);

        }

        return Promise.resolve(false);
    }


    fetchPage() {
        try {

            let payload = {
                refresh: ++this.view.refresh,
                sterm: this.view.search,
                offset: (this.view.offset < 0) ? 0 : this.view.offset,
                limit: this.view.pageLength,
                col: this.coldefs,
                qbar: this.cfg?.qbar.map((q) => ({t: q.type, n: q.name, v: q.value}))
            }


            const ajx = this.cfg?.ajax;

            if (ajx && ajx.url ) {
                ajx.token = this.cfg?.token ;

                (async (req) => {

                    try {
                        const ajx = this.cfg?.ajax;
                        if (ajx.url && ajx.token) {

                            const response = await this.fetchAjax(ajx, req)
                            if (response.ok) {
                                const data = await response.json();
                                if (data.refresh === this.view.refresh)
                                    this.setPageData(data.data, data.total, req);
                            } else
                                throw new Error('bad response:' + JSON.stringify(response));
                        }
                    } catch (e) {
                        this.T1.style.opacity = '1.0';
                        // console.error(e);
                    } finally {

                    }
                })(payload);
            }


        } catch (e) {
            // console.error(e);
        }

    }

}
