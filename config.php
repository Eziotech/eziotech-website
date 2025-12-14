<?php
/**
 * Configuration de la base de données
 * ⚠️ IMPORTANT : Ne jamais commiter ce fichier sur GitHub !
 */

// Configuration base de données
define('DB_HOST', 'localhost');           // Hôte (souvent 'localhost')
define('DB_NAME', 'eziotech');         // Nom de ta base de données
define('DB_USER', 'root');                // Utilisateur (change en production !)
define('DB_PASS', '');                    // Mot de passe (vide en local, fort en production !)
define('DB_CHARSET', 'utf8mb4');

// Configuration email
define('ADMIN_EMAIL', 'eziotech31@gmail.com');  // Email où tu recevras les notifications
define('SITE_NAME', 'Eziotech');

// CONFIGURATION SMTP
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'eziotech31@gmail.com');
define('SMTP_PASS', 'rfug avle vvse nbga');
define('SMTP_ENCRYPTION', 'tls');

// Configuration sécurité
define('ENABLE_RATE_LIMIT', true);        // Limite le nombre de soumissions
define('MAX_SUBMISSIONS_PER_HOUR', 5);    // Max 5 soumissions par heure par IP

// Environnement (dev ou prod)
define('ENVIRONMENT', 'dev');             // 'dev' ou 'prod'

/**
 * Connexion à la base de données avec gestion d'erreurs
 */
function getDbConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
        
    } catch (PDOException $e) {
        // En production, log l'erreur au lieu de l'afficher
        if (ENVIRONMENT === 'dev') {
            die("Erreur de connexion à la base de données : " . $e->getMessage());
        } else {
            error_log("Erreur DB : " . $e->getMessage());
            die("Erreur de connexion à la base de données. Contactez l'administrateur.");
        }
    }
}

/**
 * Fonction pour envoyer un email (optionnel)
 */
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmailNotification($data) {
    require_once __DIR__ . '/vendor/autoload.php';

    $mail = new PHPMailer(true);

    try {
        // SMTP
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port       = SMTP_PORT;

        // UTF-8
        $mail->CharSet = 'UTF-8';

        // Expéditeur
        $mail->setFrom(SMTP_USER, SITE_NAME);
        $mail->addAddress(ADMIN_EMAIL);

        // Réponse vers l'utilisateur
        $mail->addReplyTo($data['email'], $data['nom_entreprise']);

        // Contenu
        $mail->isHTML(true);
        $mail->Subject = "🔔 Nouvelle demande de contact - " . SITE_NAME;

        $mail->Body = "
            <h2>Nouvelle demande de contact</h2>
            <p><strong>Nom :</strong> {$data['nom_entreprise']}</p>
            <p><strong>Email :</strong> {$data['email']}</p>
            <p><strong>Téléphone :</strong> {$data['telephone']}</p>
            <p><strong>Service :</strong> {$data['service_demande']}</p>
            <p><strong>Message :</strong><br>" . nl2br($data['message']) . "</p>
        ";

        $mail->AltBody = "Nouvelle demande de contact\n\n"
            . "Nom : {$data['nom_entreprise']}\n"
            . "Email : {$data['email']}\n"
            . "Téléphone : {$data['telephone']}\n"
            . "Service : {$data['service_demande']}\n"
            . "Message : {$data['message']}";

        $mail->send();
        return true;

    } catch (Exception $e) {
        error_log('PHPMailer erreur : ' . $mail->ErrorInfo);
        return false;
    }
}


?>