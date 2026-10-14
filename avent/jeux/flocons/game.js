/* Jeu « Chasse aux flocons » — module navigateur. Voir avent/README.md pour le contrat. */
(function () {
    'use strict';
    window.AventJeux = window.AventJeux || {};

    var attrapes = 0;
    var generateur = null;

    window.AventJeux.flocons = {
        demarrer: function (conteneur, config, api) {
            var cible = config.cible || 20;
            var rythme = config.rythme_ms || 650;
            attrapes = 0;

            var score = document.createElement('p');
            score.className = 'flocons-score';
            var terrain = document.createElement('div');
            terrain.className = 'flocons-terrain';
            conteneur.appendChild(score);
            conteneur.appendChild(terrain);

            function maj() { score.textContent = '❄ ' + attrapes + ' / ' + cible; }
            maj();

            function apparition() {
                var flocon = document.createElement('button');
                flocon.type = 'button';
                flocon.className = 'flocons-flocon';
                flocon.textContent = '❄';
                flocon.setAttribute('aria-label', 'Flocon');
                flocon.style.left = (Math.random() * 82 + 4) + '%';
                flocon.style.animationDuration = (2.6 + Math.random() * 1.4).toFixed(2) + 's';
                flocon.addEventListener('pointerdown', function (evenement) {
                    evenement.preventDefault();
                    if (flocon.classList.contains('attrape')) { return; }
                    flocon.classList.add('attrape');
                    attrapes++;
                    maj();
                    setTimeout(function () { flocon.remove(); }, 160);
                    if (attrapes >= cible) {
                        clearInterval(generateur);
                        api.terminer({ attrapes: attrapes });
                    }
                });
                flocon.addEventListener('animationend', function () { flocon.remove(); });
                terrain.appendChild(flocon);
            }

            apparition();
            generateur = setInterval(apparition, rythme);
        },

        arreter: function () {
            clearInterval(generateur);
            return { attrapes: attrapes };
        }
    };
}());
