function wdPing() {
    if (document?.visibilityState !== 'visible')
        return;
    fetch('/sys/ping', {
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-store'
    }).catch(function () {
        // ignore ping failures
    });
}

document.addEventListener('alpine:init', () => {

    Alpine.data('adbmPage', (config = {}) =>
    ({
        editAllow: Number(config.editAllow ?? 0),
        editMode: Number(config.editMode ?? 0),
        sidebarExpanded: false,
        sidebarOpen: false,
        darkMode: false,
        changed: false,

        setDark(v) {
            // persist dark mode

            localStorage.setItem('dark-mode', v ? 'dark' : 'light');
            this.darkMode = v;
            if (v) {
                document.body.classList.add('dark');
            } else {
                document.body.classList.remove('dark');
            }
        },


        async toggleEdit() {


            let n = this.editMode;
            if ((this.editAllow & 3) === 3)
                n = (n + 1) % 3;
            else if (this.editAllow & 2)
                n = (n ^ 2) & 2;
            else if (this.editAllow & 1)
                n = (n ^ 1) & 1;
            else
                n = 0;

            if (this.editMode !== n) {
                this.editMode = n;
                const url = new URL(window.location.href);
                if ( n == 0 )
                    url.searchParams.delete('e');
                else
                    url.searchParams.set('e', n);
                window.location.href = url.toString();
            }

        },

        init: function () {
            this.editMode &= this.editAllow ;
            this.setDark(this.darkMode) ;
            this.sidebarExpanded = localStorage.getItem('sidebar-expanded') === 'true';
            this.$watch('sidebarExpanded' , value => localStorage.setItem('sidebar-expanded', value));
            this.$watch('editMode'        , value => localStorage.setItem('edit-mode' , value))
        }


    }))
});


document.addEventListener('DOMContentLoaded', event => {
    let b = document.getElementsByTagName('body');
    if (b && b[0])
        b[0].style.display = 'block';
});

// setInterval(wdPing, 300_000);