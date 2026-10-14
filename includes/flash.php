<?php
function definir_flash($texte, $type = 'succes') {
    $_SESSION['flash_message'] = $texte;
    $_SESSION['flash_type'] = $type;
}

function afficher_flash() {
    if (!empty($_SESSION['flash_message'])) {
        $classes = [
            'erreur'  => 'alert alert-danger',
            'warning' => 'alert alert-warning',
        ];
        $classe = $classes[$_SESSION['flash_type'] ?? ''] ?? 'alert alert-success';
        echo '<div class="' . $classe . '">' . htmlspecialchars($_SESSION['flash_message']) . '</div>';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    }
}