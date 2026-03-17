// noinspection JSUnusedGlobalSymbols
class wdFormDefaultRenderer {

    fn_form_build(wdform) {
        wdform.F1.innerHTML = '';
        const EH = document.createElement('div');
        EH.id = wdform.F0.id + '_error';
        EH.className = 'wd-error-box';
        wdform.F1.appendChild(EH);
    }

    fn_form_error( wdform , error ) {
        const EH = document.getElementById(wdform.F0.id + '_error');
        EH.textContent = error;
        EH.style.display = (error ? 'block' : 'none');
        if ( this.FORM )
            this.FORM.style.display = (error ? 'none' : 'block');
    }

    fn_data_control(wdform, node, data) {
        if (!this.CONTROL || !node || !node?.el)
            return;

        const K = document.getElementById(node.el.id + '_in');
        if ( !K )
            return ;

        if ('value' in K) {
            K.value = data ?? '';
            return;
        }
    }

    fn_start_form(wdform, node) {

        this.FORM = document.createElement('form');
        this.FORM.id = node.el.id + '_form';
        this.FORM.className = 'wd-form-box';
        wdform.F1.appendChild(this.FORM);

    }

    fn_start_page(wdform, node) {

        if (!this.FORM)
            return;

        this.PAGE = document.createElement('div');
        this.PAGE.id = node.el.id;
        this.PAGE.className = 'wd-form-page';
        this.FORM.appendChild(this.PAGE);
    }

    fn_start_repeat(wdform, node) {

        this.REPEAT = document.createElement('div');
        this.REPEAT.className = 'wd-form-repeat';
        this.REPEAT.id = node.el?.id + '_repeat';
        this.PAGE.appendChild(this.REPEAT);

    }

    fn_start_section(wdform, node) {
        if (!this.REPEAT)
            return;

        this.SECTION = document.createElement('div');
        this.SECTION.className = 'wd-form-section';
        this.SECTION.id = node.el?.id + '_section';

        let S1 = document.createElement('fieldset');
        let S2 = document.createElement('div');

        this.SECTION.appendChild(S1);
        this.SECTION.appendChild(S2);
        this.REPEAT.appendChild(this.SECTION);

    }

    fn_start_group(wdform, node) {
        if (!this.SECTION)
            return;

        this.GROUP = document.createElement('div');
        this.GROUP.className = 'wd-form-group';
        let minW = (node.par?.minWidth ?? node?.el?.minWidth ?? '6rem');
        this.GROUP.style.gridTemplateColumns = 'repeat(auto-fit, minmax(' + minW + ', 1fr))';

        this.SECTION.firstElementChild?.appendChild(this.GROUP);


    }

    create_label(node) {

        const L = document.createElement('label');
        L.id = node.el.id + '_label';
        L.className = 'wd-label';
        L.textContent = node.el?.label || node.el?.field || '';
        L.htmlFor = node.el.id + '_in';
        return L;
    }

    create_control(node) {
        const el = node?.el;
        if (!el || el?.hidden === true)
            return null;

        let K = null;
        switch (el?.type) {
            case 'select' :
                // simple select 1=>1
                K = document.createElement('select');
                K.className = 'wd-select';
                K.innerHTML = '';
                break;
            case 'text' :
                K = document.createElement('input');
                K.type = 'text';
                K.className = 'wd-input';
                break;
            case 'number' :
                K = document.createElement('input');
                K.type = 'number';
                K.min = el?.min ?? 0 ;
                K.max = el?.max ?? 0;
                K.className = 'wd-input';
                break ;

            default:
                console.error('unknown control type : ' + node.el?.type);
                return null;
        }

        K.id = node.el.id + '_in';


        return K;
    }

    create_error(node) {
        const E = document.createElement('div');
        E.id = node.el.id + '_error';
        E.className = 'wd-error';
        E.textContent = '';
        return E;

    }


    fn_start_control(wdform, node) {
        if (!this.GROUP)
            return;

        let K = this.create_control(node);
        if (!K)
            return;

        const L = this.create_label(node);
        const E = this.create_error(node);
        this.CONTROL = document.createElement('div');
        this.CONTROL.className = 'wd-form-control';
        const span = (node?.span ?? 3);
        this.CONTROL.style.gridColumn = 'span ' + span + ' / span ' + span;
        if (L)
            this.CONTROL.appendChild(L);
        if (K)
            this.CONTROL.appendChild(K);
        if (E)
            this.CONTROL.appendChild(E);


        this.GROUP.appendChild(this.CONTROL);

    }

    fn_list_control(wdform, node, list) {

        switch (node.el?.type) {
            case 'select' :
                const K = document.getElementById(node.el.id + '_in');
                if (K) {
                    K.innerHTML = '';
                    list.forEach((item) => {
                        const O = document.createElement('option');
                        O.value = item.id;
                        O.textContent = item.text;
                        K.appendChild(O);
                    })
                }
        }

    }

} // end wdFormDefaultRenderer


class wdForm {


    constructor(divid, jobj, tf, renderer = null) {

        this.TF = tf;
        this.renderer = renderer || (typeof wdFormDefaultRenderer !== 'undefined' ? new wdFormDefaultRenderer() : null);


        if (!divid || !jobj || 'string' !== typeof divid || 'object' !== typeof jobj)
            throw new Error('Invalid arguments')


        this.F0 = document.getElementById(divid)
        try {
            if (this.F0 && this.F0?.tagName === 'DIV') {
                this.F0.innerHTML = '';
                this.ref = {refresh: 0, all: true, data: false, build: false, datasrc: false , error : false }
                this.view = {}
                this.data = {list: [] , error : true }
                this.F1 = document.createElement('div');
                this.F1.id = this.F0.id + '_form';
                this.F1.className = 'wd-user-form';
                this.F0.appendChild(this.F1);
                this.cfg = jobj?.form;
            } else
                throw new Error('Invalid Divid')

        } catch (e) {


        }

    }

    show(id) {

        this.setupFORM();
        this.loadFormData(id);

    }


    walkForm(frm, walkCB) {

        if (!frm || !walkCB || typeof walkCB !== 'function')
            return;
        //
        const walkSection = (page, sec, p, s, r) => {
            sec.id = page.id + '_s' + s + '_r' + r;
            // start section
            walkCB('section', 'start', {el: sec, p, s, r, g: 0, e: 0, par: page});
            // create a fake group of one , if no groups specified
            const groups = sec?.groups || (sec?.elements ? [{elements: sec.elements, fake: true}] : []);
            if (groups) {
                groups.forEach((grp, g) => {
                    grp.id = sec.id + '_g' + g;
                    walkCB('group', 'start', {el: grp, p, s, r, g, e: 0, par: sec});
                    if (grp?.elements) {
                        grp.elements.forEach((el, e) => {
                            el.id = grp.id + '_e' + e;
                            el.iter = {p, s, r, g, e};
                            walkCB('control', 'start', {el, p, s, r, g, e, par: grp})
                            // logic ?
                            walkCB('control', 'end', {el, p, s, r, g, e, par: grp})
                        })
                    }
                    walkCB('group', 'end', {el: grp, p, s, r, g, e: 0, par: sec});
                });
            }
            // end section
            walkCB('section', 'end', {el: sec, p, s, r, g: 0, e: 0, par: page});
        }


        let p = 0, s = 0, r = 0, g = 0, e = 0;
        walkCB('form', 'start', {el: frm, p, s, r, g, e, par: null});
        if (frm?.pages) {
            // go through pages
            frm.pages.forEach((page, p) => {
                page.id = frm.id + '_p' + p;
                walkCB('page', 'start', {el: page, p, s, r, g, e, par: frm});
                if (page?.sections) {
                    // we have sections - whic
                    page.sections.forEach((sec, s) => {
                        sec.id = page.id + '_s' + s;
                        walkCB('repeat', 'start', {el: sec, p, s, r, g, e, par: page})
                        for (r = 0; r < (this?.repeat?.depth || 1); r++)
                            walkSection(page, sec, p, s, r);
                        walkCB('repeat', 'end', {el: sec, p, s, r, g, e, par: page})
                    })
                }
                walkCB('page', 'end', {el: page, p, s, r, g, e, par: frm});
            })
        }
        walkCB('form', 'end', {el: frm, p, s, r, g, e, par: null});

    }


    setupFORM() {

        let layout = this.cfg?.layout;
        this.view.fields = [];
        this.view.prikeys = [];
        this.view.sources = [];


        this.walkForm(layout, (type, event, node) => {
            if (event === 'start' && type === 'control') {
                let el = node?.el;
                if (el?.field) {
                    this.view.fields.push(el.field);
                    if (el?.key)
                        this.view.prikeys.push(el.field);
                }
                if (el?.datasrc && el?.datasrc?.type === 'named' && el?.datasrc?.name)
                    this.view.sources.push(el.datasrc.name);

            }
        })

        this.ref.all = true;

    }

    loadFormData(id) {


        let payload = {
            refresh: ++this.ref.refresh,
            fields: this.view.fields,
            prikeys: this.view.prikeys,
            sources: this.view.sources,
            keyvals: id,
        }

        if (this?.cfg?.ajax) {

            _wd_api_fetch(this.cfg.ajax.url, payload, (res) => {
                console.log('res', res);
                if (res && res?.refresh === this.ref.refresh) {
                    this.data.data = res ;
                    if ( this.data.error !== false )
                        this.ref.error = true ;
                    this.data.error = false ;

                } else {

                    if ( this.data.error === false )
                        this.ref.error = false ;

                    this.data.error = res?.error || 'Unable to load data' ;


                }

                this.ref.data = true  ;
                this.updateView();
            });
        }
    }


    buildForm() {

        if (!this.renderer || !this.F1)
            return ;

        const method = this.renderer['fn_form_build'];
        if ( typeof method === 'function') {
            method.call(this.renderer, this);
            this.walkForm(this.cfg?.layout, (type, event, node) => {
                const method = this.renderer[('fn_' + event + '_' + type)]
                if (typeof method === 'function') {
                    method.call(this.renderer, this, node);

                }
            })
        }

        this.ref.build = false;
    }

    // given data source , return list as { id , text } pairs
    fillDataSource(ds) {

        switch (ds?.type) {
            case 'fixed' :
                return ds?.data ?? [];
            case 'named' :
                return this?.data?.data?.lists?.[ds.name] || [] ;
                break ;


        }

        return undefined;

    }

    updateLists() {

        this.walkForm(this.cfg?.layout, (type, event, node) => {

            if (event === 'start' && node?.el && node.el?.id && node.el?.datasrc) {
                let list = this.fillDataSource(node.el.datasrc);
                if (list !== undefined) {
                    //
                    this.ref.data = true;
                    const method = this.renderer['fn_list_' + type];
                    if (typeof method === 'function')
                        method.call(this.renderer, this, node, list);
                }
            }

        })


        this.ref.datasrc = false;

    }

    getFieldValue(field) {

        if ( !field )
            return ;

        const fields = this?.view?.fields;
        if ( !fields )
            return ;

        const idx = Array.isArray(fields) ? fields.indexOf(field) : -1;
        if ( idx < 0 )
            return ;

        const row = this.data?.data?.data;
        if ( !row )
            return ;


        if (!Array.isArray(row) || idx < 0 || idx >= row.length)
            return undefined;

        return row[idx];
    }
    updateData() {


        this.walkForm(this.cfg?.layout, (type, event, node) => {
            if (event === 'start' && node?.el && node?.el?.field) {

                const method = this.renderer['fn_data_' + type];
                if (typeof method === 'function') {
                    const data = this.getFieldValue(node.el.field ) ;
                    if (data !== undefined)
                        method.call(this.renderer, this, node , data );


                }

            }
        });


        this.ref.data = false;

    }


    updateView() {

        if (this.ref.all) {
            this.ref.datasrc = true;
            this.ref.data = true;
            this.ref.build = true;
        }

        if (this.ref.build) {
            this.buildForm();
            this.ref.error = true;
        }

        if ( this.ref.error ) {
            const method = this.renderer['fn_form_error'];
            if (typeof method === 'function') {
                method.call(this.renderer, this, this.data.error);
            }
            this.ref.error = false ;
        }



        if (this.ref.datasrc)
            this.updateLists();

        if (this.ref.data)
            this.updateData();


        this.ref.all = false;
    }


}

