// ✅ ATTENDRE QUE LE DOM SOIT CHARGÉ
document.addEventListener('DOMContentLoaded', function() {

    // Menu toggle for mobile
    function toggleMenu() {
        const menu = document.getElementById('navMenu');
        menu.classList.toggle('active');
    }
    
    // Rend toggleMenu accessible globalement pour onclick
    window.toggleMenu = toggleMenu;

    // Smooth scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
                // Close mobile menu if open
                document.getElementById('navMenu').classList.remove('active');
            }
        });
    });

    // Animate on scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animation = 'fadeInUp 0.6s ease-out';
            }
        });
    });

    document.querySelectorAll('.service-card, .feature-item').forEach((el) => {
        observer.observe(el);
    });

    // FORMULAIRE DE CONTACT - ENVOI AJAX VERS PHP
    const contactForm = document.getElementById('contactForm');
    
    if (contactForm) {
        contactForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Empêche la soumission normale
            
            const form = this;
            const button = form.querySelector('button[type="submit"]');
            const messageDiv = document.getElementById('formMessage');
            const originalButtonText = button.textContent;
            
            // Animation du bouton
            button.textContent = '⏳ Envoi en cours...';
            button.disabled = true;
            messageDiv.style.display = 'none';
            
            // Prépare les données
            const formData = new FormData(form);
            
            // Envoie AJAX vers le script PHP
            fetch('send-mail.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                // Vérifie si la réponse est OK
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || 'Erreur serveur');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // ✅ SUCCÈS
                    messageDiv.innerHTML = '✅ <strong>Message enregistré avec succès !</strong><br>Nous vous recontacterons sous 24h.';
                    messageDiv.className = 'form-message success';
                    messageDiv.style.display = 'block';
                    
                    // Réinitialise le formulaire
                    form.reset();
                    
                    // Scroll vers le message
                    messageDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    
                    // Cache le message après 5 secondes
                    setTimeout(() => {
                        messageDiv.style.display = 'none';
                    }, 5000);
                    
                } else {
                    throw new Error(data.message || 'Erreur inconnue');
                }
            })
            .catch(error => {
                // ❌ ERREUR
                messageDiv.innerHTML = '❌ <strong>Erreur :</strong> ' + error.message + '<br>Veuillez réessayer ou nous contacter directement par email.';
                messageDiv.className = 'form-message error';
                messageDiv.style.display = 'block';
                console.error('Erreur formulaire:', error);
            })
            .finally(() => {
                // Réactive le bouton
                button.textContent = originalButtonText;
                button.disabled = false;
            });
        });
    } else {
        console.error('❌ Formulaire #contactForm introuvable !');
    }

}); // FIN DU DOMContentLoaded