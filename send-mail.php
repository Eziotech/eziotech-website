<?php
/**
 * Script de traitement du formulaire de contact
 * Enregistre en base de données et envoie un email
 */

header('Content-Type: application/json; charset=utf-8');

// Inclut la configuration
require_once 'config.php';

// Fonction de validation et nettoyage
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Fonction de vérification du rate limiting
function checkRateLimit($pdo, $ip) {
    if (!ENABLE_RATE_LIMIT) return true;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM contacts 
        WHERE ip_address = ? 
        AND date_soumission > DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$ip]);
    $result = $stmt->fetch();
    
    return $result['count'] < MAX_SUBMISSIONS_PER_HOUR;
}

try {
    // Vérifie que c'est une requête POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Méthode non autorisée", 405);
    }
    
    // Récupération et nettoyage des données
    $nom_entreprise = sanitize($_POST['nom_entreprise'] ?? '');
    $email = filter_var(sanitize($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $telephone = sanitize($_POST['telephone'] ?? '');
    $service_demande = sanitize($_POST['service_demande'] ?? '');
    $message = sanitize($_POST['message'] ?? '');
    
    // Validation des champs obligatoires
    $errors = [];
    
    if (empty($nom_entreprise)) {
        $errors[] = "Le nom/entreprise est requis";
    }
    
    if (!$email) {
        $errors[] = "Email invalide";
    }
    
    if (empty($telephone)) {
        $errors[] = "Le téléphone est requis";
    }
    
    if (empty($service_demande)) {
        $errors[] = "Le service est requis";
    }
    
    // Vérifie les erreurs de validation
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => implode(', ', $errors)
        ]);
        exit;
    }
    
    // Connexion à la base de données
    $pdo = getDbConnection();
    
    // Récupère l'IP et le User-Agent
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Vérification du rate limiting
    if (!checkRateLimit($pdo, $ip_address)) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Trop de demandes. Veuillez réessayer plus tard.'
        ]);
        exit;
    }
    
    // Honeypot anti-spam (si le champ caché est rempli, c'est un bot)
    if (!empty($_POST['_gotcha'])) {
        // Log le spam sans répondre
        error_log("Spam détecté depuis IP: " . $ip_address);
        // Répond OK pour tromper le bot
        echo json_encode(['success' => true, 'message' => 'Message envoyé']);
        exit;
    }
    
    // Préparation de la requête d'insertion
    $stmt = $pdo->prepare("
        INSERT INTO contacts 
        (nom_entreprise, email, telephone, service_demande, message, ip_address, user_agent) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    // Exécution de l'insertion
    $result = $stmt->execute([
        $nom_entreprise,
        $email,
        $telephone,
        $service_demande,
        $message,
        $ip_address,
        $user_agent
    ]);
    
    if ($result) {
        // Récupère l'ID inséré
        $contact_id = $pdo->lastInsertId();
        
        // Envoie un email de notification (optionnel)
        $emailData = [
            'nom_entreprise' => $nom_entreprise,
            'email' => $email,
            'telephone' => $telephone,
            'service_demande' => $service_demande,
            'message' => $message
        ];
        
        sendEmailNotification($emailData);
        
        // Réponse de succès
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Message enregistré avec succès',
            'contact_id' => $contact_id
        ]);
        
    } else {
        throw new Exception("Erreur lors de l'enregistrement");
    }
    
} catch (Exception $e) {
    // Gestion des erreurs
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    
    $errorMessage = ENVIRONMENT === 'dev' 
        ? $e->getMessage() 
        : "Une erreur est survenue. Veuillez réessayer.";
    
    echo json_encode([
        'success' => false,
        'message' => $errorMessage
    ]);
    
    // Log l'erreur
    error_log("Erreur formulaire contact : " . $e->getMessage());
}
?>