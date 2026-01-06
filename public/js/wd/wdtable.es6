class wdDataTable {


    constructor(id, cfg, callback = null) {

        this.T0 = document.getElementById(id);
        this.T1 = document.createElement('div');
        this.T1.id = id + '_table';
        this.T1.className = 'wd-table ';
        this.T0.appendChild(this.T1);
        this.setupTable(cfg);
        this.refreshData();
    }

    setupTable(cfg) {

        this.cfg = cfg;


        this.upd = {
            all: true
        }

        this.ref = {
            title: true
        };

        this.view = {
            pageLength: 10,
            pageSearch: '',
        }

        this.loadColumnInfo(cfg);


        // this.drawTable();
    }

    loadColumnInfo(cfg) {

        this.col = {n: 0, defs: [], head: [], order: []};

        let i = 0;
        Object.keys(cfg.columns).forEach((k) => {
            let cdef = cfg?.columns?.[k];
            if (cdef) {
                this.col.order[i] = cdef?.order ?? i;
                this.col.defs[i] = {

                }

                this.col.head[i] = {
                    'title': cdef?.title ?? i,
                    'sortable'   : false ,
                    'searchable' : false ,
                }
                i++;
            }
        })
        this.col.n = i;
    }


    refreshData = _wd_debounce(() => {
        this.fetchPage();

    }, 250);




    fetchPage = () => {
        if (this.cfg?.api) {
            // Construct the payload for the API
            let payload = {
                length: this.view.pageLength ,
                search: this.view.pageSearch ,
                cdefs : this.col.defs ,
                hdefs : this.col.head ,
                ...this.cfg?.api?.params ,
            };

            this.T1.style.opacity = '0.7' ;
            console.log( 'call ' , this.cfg.api.url , payload );
            apiFetch(this.cfg.api.url, this.cfg.api.token, payload, (res) => {

                // process result

                this.T1.style.opacity = '1.0';
                if (res.error) {
                    this.T0.style.backgroundColor = 'rgba(255,0,0,0.1)';
                    console.error('Table fetch failed:', res.error);
                    this.error = res.error ;
                    this.upd.all = true ;
                } else {
                    this.error = '';
                    this.T1.style.backgroundColor = '';
                    // If apiFetch performed a refresh, update our local config token
                    if (res.newToken)
                        this.cfg.api.token = res.newToken;
                    // Update data and redraw
                    this.data = res.data;
                }
                this.drawTable();
            });
        }
    }


    viewChangePageLength(newLength) {


    }


    makeBlock(base, name, tag = 'div', updateCB = null) {

        let n = (base?.id ?? 'block') + '-' + name;
        let E = document.getElementById(n)
        if (!E) {
            E = document.createElement(tag);
            E.id = n;
            base.appendChild(E);
            if (updateCB)
                updateCB(E, true);
            return;
        }

        if (updateCB)
            updateCB(E, false);

    }

    updateTitle() {


        this.makeBlock(this.T1, 'titleblock', 'div', (E, init) => {


            if (init)
                E.className = 'wd-section wd-titleblock';

            if (init || this.ref.title) {
                this.makeBlock(E, 'title', 'h1', (T, isnew) => {
                    if (isnew)
                        T.className = 'wd-title';
                    T.textContent = this.cfg?.title ?? '';

                })
                this.ref.title = false;
            }

            if (init || this.ref.error) {

                this.makeBlock(E, 'error', 'h2', (Q, isnew) => {
                    if (isnew) {
                        Q.className = 'wd-error';
                        Q.style.display = 'block' ;
                        Q.style.backgroundColor = 'red';
                        Q.style.color = 'white';
                    }

                    Q.textContent = this.error ?? '';
                })

            }

        });


    }


    updateTopBar() {


        this.makeBlock(this.T1, 'topbar', 'div', (E, init) => {


            if (init)
                E.className = 'wd-section wd-topbar';

            // page length control
            if (init || this.ref.topPageLen) {

                this.makeBlock(E, 'pagelength', 'select', (Q, init) => {
                    if (init) {
                        Q.className = 'wd-len';
                        Q.addEventListener('change', (e) => {
                            this.viewChangePageLength(+e.target.value);
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

                this.ref.topPageLen = false;
            }

            // search bar
            if (init || this.ref.topSearchBar) {
                this.makeBlock(E, 'searchbar', 'input', (Q, init) => {
                    if (init) {
                        Q.className = 'wd-search';
                        Q.type = 'text';
                        Q.placeholder = 'Search ...';
                        Q.spellcheck = false;
                        this.ref.topSearchBar = false;
                    }

                })
            }

            if (init || this.ref.topOptions)
                this.makeBlock(E, 'options', 'div', (Q, init) => {
                        if (init)
                            Q.className = 'wd-options';
                    }
                )

        });

    }

    updateTable() {
        this.makeBlock(this.T1, 'table', 'table', (E, init) => {
            if (init)
                E.className = 'wd-section wd-table-block';

            if (init || this.ref.colgroup) {
                this.makeBlock(E, 'colgroup', 'colgroup', (Q, init) => {

                    if (init)
                        Q.className = 'wd-colgroup';
                    else
                        Q.replaceChildren();

                    for (let i = 0; i < this.col.n; i++) {
                        let COL = document.createElement('col');
                        Q.appendChild(COL);
                    }


                })

                this.ref.colgroup = false;
            }

            if (init || this.ref.head) {
                this.makeBlock(E, 'thead', 'thead', (Q, init) => {
                    if (init)
                        Q.className = 'wd-thead';
                    else
                        Q.replaceChildren();

                    let TR = document.createElement('tr');
                    TR.id = Q.id + '-head';
                    for (let i = 0; i < this.col.n; i++) {
                        let TH = document.createElement('th');
                        const j = this.col.order[i] ?? i;
                        TH.textContent = this.col.head[j].title ?? '-'  ;
                        TH.id = TR.id + '-col-' + i;
                        TR.appendChild(TH);
                    }

                    Q.appendChild(TR);
                })
            }


            if (init || this.ref.body) {

                this.makeBlock(E, 'tbody', 'tbody', (Q, init) => {
                    if (init)
                        Q.className = 'wd-tbody';
                    else
                        Q.replaceChildren();

                    for (let row = 0; row < this.view.pageLength; row++) {
                        const TR = document.createElement('tr');
                        TR.id = Q.id + '-row-' + row;
                        for (let col = 0; col < this.col.n; col++) {
                            let TD = document.createElement('td');
                            TD.id = TR.id + '-col-' + col;
                            TD.className = 'border-red-500 border';
                            TD.textContent = '*';
                            TR.appendChild(TD);
                        }
                        Q.appendChild(TR);
                    }
                })
            }
        });
    }

    drawTable() {


        if (this.upd.all || this.error ) {
            this.T1.replaceChildren();
            this.upd.title = true;
            this.upd.head = true;
            this.upd.table = true;
            this.upd.all = false;
        }

        if (this.upd.title) {
            this.ref.title = true ;
            this.ref.error = true ;
            this.upd.title = false;
        }

        if (this.ref.title)
            this.updateTitle();

        if ( this.error )
            return ;


        if (this.upd.head) {
            this.ref.topPageLen = true;
            this.ref.topSearchBar = true;
            this.ref.topOptions = true;
            this.upd.head = false;
        }

        if (this.ref.topPageLen || this.ref.topSearchBar || this.ref.topOptions)
            this.updateTopBar();

        if (this.upd.table) {
            this.ref.colgroup = true;
            this.ref.head = true;
            this.ref.body = true;
            this.ref.foot = true;
            this.upd.table = false;
        }

        if (this.ref.colgroup || this.ref.head || this.ref.body || this.ref.foot)
            this.updateTable();


    }

}