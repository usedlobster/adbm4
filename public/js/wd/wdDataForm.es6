
class wdDataForm  {

    constructor( cfg , actionCB , mode )
    {
        this.cfg = cfg ;
        this.actionCB = actionCB ;
        this.viewMode = mode ;
        this.F1 = document.createElement('div');
        if (!this.F1 )
            throw new Error('wdDataForm: cannot create div element');

        this.viewMode = mode;
        this.domid = '_wdf_' + cfg?.id;
    }



    attach() {
        if ( this?.F1 ) {
            this.buildForm() ;
            return this?.F1;
        }
    }

    detach() {

    }

    walkForm(frm, walkCB) {

        const walkSection = (page, sec, p, s, r) => {

            sec.id = page.id + '_s' + s + '_r' + r;

            walkCB('section', 'start', sec, p, s, r, 0, 0, page);

            // Normalize: use existing groups, or create a temporary group wrapper for elements
            const groups = sec?.groups || (sec?.elements ? [{elements: sec.elements, fake: true}] : []);

            if (groups) {
                groups.forEach((grp, g) => {
                    grp.id = sec.id + '_g' + g;
                    walkCB('group', 'start', grp, p, s, r, g, 0, sec);
                    if (grp?.elements) {
                        grp.elements.forEach((el, e) => {
                            el.id = grp.id + '_e' + e;
                            el.iter = {p, s, r, g, e};
                            walkCB('element', 'visit', el, p, s, r, g, e, grp)
                        })
                    }
                    walkCB('group', 'end', grp, p, s, r, g, 0, sec);
                });
            }
            walkCB('section', 'end', sec, p, s, r, 0, 0, page);

        }


        if (!frm || !walkCB || typeof walkCB !== 'function')
            return;
        //
        let p = 0, s = 0, r = 0, g = 0, e = 0;
        walkCB('form', 'start', frm, p, s, r, g, e, null);
        if (frm?.pages) {
            frm.id = this.domid;
            // go through pages
            frm.pages.forEach((page, p) => {
                page.id = frm.id + '_p' + p;

                walkCB('page', 'start', page, p, s, r, g, e, frm);
                if (page?.sections) {
                    // we have sections - whic
                    page.sections.forEach((sec, s) => {
                        sec.id = page.id + '_s' + s;
                        walkCB('repeat', 'start', sec, p, s, r, g, e, page)
                        for (r = 0; r < (this?.repeat?.depth || 1); r++)
                            walkSection(page, sec, p, s, r);
                        walkCB('repeat', 'end', sec, p, s, r, g, e, page)

                    })
                }
                walkCB('page', 'end', page, p, s, r, g, e, frm);
            })
        }
        walkCB('form', 'end', frm, p, s, r, g, e, null);
    }

    buildForm() {


        let cfg = this.cfg?.form;
        if (cfg)
            this.walkForm(cfg, (type, ev, cfg, p, s, r, g, e, pcfg) => {
                console.log(type, ev, cfg, p, s, r, g, e, pcfg);
            });
    }




}