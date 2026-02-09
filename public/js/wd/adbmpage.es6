document.addEventListener('alpine:init', () => {

  Alpine.data('adbmPage', ()  => ({

    sidebarExpanded: false,
    sidebarOpen: false,
    darkMode: false,
    editMode : 0  ,
    editAllow: 0  ,

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


    toggleEdit() {

      let n = this.editMode;
      /*
      if ((this.editAllow & 3) === 3) {
        n = (n + 1) % 3;
      } else if (this.editorAllow & 2) {
        n = (n ^ 2) & 2;
      } else if (this.editorAllow & 1) {
        n = (n ^ 1) & 1;
      } else {
        n = 0;
      }

       */
      this.editMode = (n+1)%3 ;

    },

    init: function () {

      this.darkMode = localStorage
        .getItem('dark-mode') === 'dark';
      this.editMode = localStorage.getItem('edit-mode') & (this.editAllow || 0);
      this.editAllow = 3 ; // localStorage.getItem('edit-allow');
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

