/* Comportements communs : neige décorative et ouverture des fenêtres du chalet. */
(function () {
    'use strict';

    var reduit = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    // --- Neige ---------------------------------------------------------------
    var neige = document.querySelector('.neige');
    if (neige && !reduit) {
        var nombre = window.innerWidth < 600 ? 22 : 40;
        for (var i = 0; i < nombre; i++) {
            var f = document.createElement('span');
            var taille = 3 + Math.random() * 5;
            f.className = 'flocon';
            f.style.left = (Math.random() * 100) + '%';
            f.style.width = f.style.height = taille + 'px';
            f.style.opacity = (0.45 + Math.random() * 0.5).toFixed(2);
            f.style.animationDuration = (9 + Math.random() * 12).toFixed(1) + 's';
            f.style.animationDelay = (-Math.random() * 20).toFixed(1) + 's';
            neige.appendChild(f);
        }
    }

    // --- Fenêtres du calendrier ---------------------------------------------------
    var toast = document.getElementById('toast');
    var minuteur = null;

    function annoncer(texte) {
        if (!toast) { return; }
        toast.textContent = texte;
        toast.hidden = false;
        clearTimeout(minuteur);
        minuteur = setTimeout(function () { toast.hidden = true; }, 3200);
    }

    document.querySelectorAll('.fenetre').forEach(function (fenetre) {
        fenetre.addEventListener('click', function (evenement) {
            if (fenetre.dataset.bloque === '1') {
                evenement.preventDefault();
                fenetre.classList.remove('secoue');
                void fenetre.offsetWidth; // relance l'animation
                fenetre.classList.add('secoue');
                annoncer('Pas encore ! Cette fenêtre s\'ouvre le ' + fenetre.dataset.message + '.');
                return;
            }
            if (reduit || evenement.metaKey || evenement.ctrlKey || evenement.shiftKey) {
                return; // navigation immédiate
            }
            evenement.preventDefault();
            var destination = fenetre.getAttribute('href');
            fenetre.classList.add('ouvre');
            setTimeout(function () { window.location.href = destination; }, 650);
        });
    });
}());
