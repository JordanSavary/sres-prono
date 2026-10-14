/* Édition d'un jour : insertion de l'exemple de contenu proposé par le module de jeu. */
(function () {
    'use strict';

    var formulaire = document.getElementById('formulaire-jour');
    if (!formulaire) { return; }

    var exemples = {};
    try { exemples = JSON.parse(formulaire.dataset.exemples || '{}'); } catch (e) { exemples = {}; }

    var jeu = document.getElementById('jeu');
    var config = document.getElementById('config');
    var bouton = document.getElementById('inserer-exemple');

    function inserer() {
        var exemple = exemples[jeu.value];
        if (!exemple) { return false; }
        config.value = exemple;
        return true;
    }

    if (bouton) {
        bouton.addEventListener('click', function () {
            if (!inserer()) { window.alert('Choisis d\'abord un jeu qui propose un exemple.'); }
        });
    }
    // Un contenu vide est prérempli quand on choisit un jeu.
    jeu.addEventListener('change', function () {
        if (config.value.trim() === '') { inserer(); }
    });
}());
