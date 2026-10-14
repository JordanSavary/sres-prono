/* Jeu « Quiz » — module navigateur. Voir avent/README.md pour le contrat. */
(function () {
    'use strict';
    window.AventJeux = window.AventJeux || {};

    var reponses = [];

    window.AventJeux.quiz = {
        demarrer: function (conteneur, config, api) {
            var questions = config.questions || [];
            var index = 0;
            reponses = [];

            var enTete = document.createElement('p');
            enTete.className = 'quiz-progression';
            var enonce = document.createElement('h3');
            enonce.className = 'quiz-enonce';
            var liste = document.createElement('div');
            liste.className = 'quiz-options';
            conteneur.appendChild(enTete);
            conteneur.appendChild(enonce);
            conteneur.appendChild(liste);

            function afficher() {
                var q = questions[index];
                enTete.textContent = 'Question ' + (index + 1) + ' / ' + questions.length;
                enonce.textContent = q.question;
                liste.innerHTML = '';
                q.options.forEach(function (texte, i) {
                    var bouton = document.createElement('button');
                    bouton.type = 'button';
                    bouton.className = 'quiz-option';
                    bouton.textContent = texte;
                    bouton.addEventListener('click', function () { repondre(i + 1, bouton); });
                    liste.appendChild(bouton);
                });
            }

            function repondre(numero, bouton) {
                if (liste.classList.contains('verrou')) { return; }
                liste.classList.add('verrou');
                bouton.classList.add('choisie');
                reponses[index] = numero;
                setTimeout(function () {
                    liste.classList.remove('verrou');
                    index++;
                    if (index >= questions.length) {
                        api.terminer({ reponses: reponses });
                    } else {
                        afficher();
                    }
                }, 220);
            }

            if (questions.length === 0) {
                api.terminer({ reponses: [] });
                return;
            }
            afficher();
        },

        arreter: function () {
            return { reponses: reponses };
        }
    };
}());
