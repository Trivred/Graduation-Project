(function(){
    'use strict';
    const frame = document.querySelector('.map-frame');
    if(frame){
        frame.addEventListener('load', ()=>frame.classList.add('loaded'));
    }
})();
function toggleMenu(){
    document.getElementById("mainNav").classList.toggle("show");
}