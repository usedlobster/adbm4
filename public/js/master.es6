
window.addEventListener('load', function () {

    let b = document.getElementsByTagName('body');
    if (b && b[0]) {
        b[0].style.display = 'block';
    }

});


function _wdf_set_dark_mode(    value) {

    let h = document.getElementsByTagName('html');
    if ( value  ) {
        h[0].classList.add('dark');
    } else {
        h[0].classList.remove('dark');
    }
    localStorage.setItem('dark-mode', value) ;
}
