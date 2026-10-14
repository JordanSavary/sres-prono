<script>
    const burger = document.getElementById('burger');
    const nav = document.getElementById('nav-links');

    burger.addEventListener('click', () => {
        nav.classList.toggle('nav-open');
    });

    // Close menu on click outside
    document.addEventListener('click', (event) => {
        if (!nav.contains(event.target) && !burger.contains(event.target)) {
            nav.classList.remove('nav-open');
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        var link = document.getElementById('toggle-passes');
        var section = document.getElementById('section-passes');

        if(link && section) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                if (section.style.display === 'none') {
                    section.style.display = 'block';
                    link.textContent = 'Afficher moins';
                } else {
                    section.style.display = 'none';
                    link.textContent = 'Afficher plus';
                }
            });
        }
    });

	// Scroll automatique vers le bouton actif dans la barre de jours
	document.addEventListener('DOMContentLoaded', () => {
		const actif = document.querySelector('.btn-jour.actif');
		if (actif) {
			actif.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
		}
	});

    document.addEventListener('DOMContentLoaded', function () {
	    const paysButeurSelect = document.getElementById('pays_buteur');
	    const meilleurButeurSelect = document.getElementById('meilleur_buteur');

	    if (!paysButeurSelect || !meilleurButeurSelect) {
	        return;
	    }

	    function filtrerJoueursParPays() {
	        const paysSelectionne = paysButeurSelect.value;
	        const joueurSelectionne = meilleurButeurSelect.options[meilleurButeurSelect.selectedIndex];
	        let joueurEncoreDisponible = !meilleurButeurSelect.value;

	        meilleurButeurSelect.disabled = !paysSelectionne;

	        Array.from(meilleurButeurSelect.options).forEach(function (option) {
	            if (option.value === '') {
	                option.hidden = false;
	                return;
	            }

	            const afficher = option.dataset.pays === paysSelectionne;
	            option.hidden = !afficher;

	            if (afficher && joueurSelectionne && option.value === joueurSelectionne.value) {
	                joueurEncoreDisponible = true;
	            }
	        });

	        if (!joueurEncoreDisponible) {
	            meilleurButeurSelect.value = '';
	        }
	    }

	    paysButeurSelect.addEventListener('change', function () {
	        meilleurButeurSelect.value = '';
	        filtrerJoueursParPays();
	        meilleurButeurSelect.dispatchEvent(new Event('change', { bubbles: true }));
	    });

	    filtrerJoueursParPays();
	});

	document.querySelectorAll('.autosave-form').forEach(form => {
		const inputs = form.querySelectorAll('input[type="number"]');
		const statut = form.querySelector('.statut-save');

		inputs.forEach(input => {
			input.addEventListener('change', () => {
				const prono_a = form.querySelector('[name="prono_a"]').value;
				const prono_b = form.querySelector('[name="prono_b"]').value;

				// Les deux champs doivent être remplis
				if (prono_a === '' || prono_b === '') return;

				statut.textContent = '⏳ Enregistrement…';
				statut.style.color = '#999';

				fetch('save_pronostic.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest'  },
					body: new URLSearchParams({
						id_match:   form.dataset.idMatch,
						csrf_token: form.dataset.csrf,
						prono_a,
						prono_b
					})
				})
				.then(r => r.json())
				.then(data => {
					if (data.success) {
						statut.textContent = '✅';
						statut.style.color = 'green';
					} else {
						statut.textContent = '❌';
						statut.style.color = 'red';
					}
				})
				.catch(() => {
					statut.textContent = '❌ Erreur réseau';
					statut.style.color = 'red';
				});
			});
		});
	});
	
	const formTournoi = document.querySelector('.autosave-tournoi');
	if (formTournoi) {
		formTournoi.querySelectorAll('select').forEach(select => {
			select.addEventListener('change', () => {
				const statut = formTournoi.querySelector('.statut-save');
				statut.textContent = '⏳';

				fetch('save_tournoi.php', {
					method: 'POST',
					headers: {
						'Content-Type': 'application/x-www-form-urlencoded',
						'X-Requested-With': 'XMLHttpRequest'
					},
					body: new URLSearchParams({
						csrf_token:      formTournoi.dataset.csrf,
						pays_gagnant:    formTournoi.querySelector('[name="pays_gagnant"]').value,
						meilleur_buteur: formTournoi.querySelector('[name="meilleur_buteur"]').value
					})
				})
				.then(r => r.json())
				.then(data => {
					statut.textContent = data.success ? '✅ Enregistré' : '❌ Erreur';
					setTimeout(() => statut.textContent = '', 2000);
				})
				.catch(() => { statut.textContent = '❌ Erreur réseau'; });
			});
		});
	}
</script>