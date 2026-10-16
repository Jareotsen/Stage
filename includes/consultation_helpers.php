

<?php
// Fonctions partagées par consultations.php et consultation_detail.php
 
if (!function_exists('consultationEstOuverte')) {
    /**
     * Une consultation est ouverte si son statut est 'ouverte'
     * ET que sa date de clôture (si elle existe) n'est pas dépassée.
     */
    function consultationEstOuverte(array $c): bool
    {
        if (($c['statut'] ?? '') !== 'ouverte') {
            return false;
        }
        if (!empty($c['date_fermeture']) && strtotime($c['date_fermeture']) < time()) {
            return false;
        }
        return true;
    }
}
 
if (!function_exists('echapper')) {
    function echapper(?string $valeur): string
    {
        return htmlspecialchars($valeur ?? '', ENT_QUOTES, 'UTF-8');
    }
}
 
