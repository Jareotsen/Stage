<?php
function definir_flash($texte, $type = 'succes') {
    $_SESSION['flash_message'] = $texte;
    $_SESSION['flash_type'] = $type;
}

function afficher_flash() {
    if (!empty($_SESSION['flash_message'])) {
        $classe = $_SESSION['flash_type'] === 'erreur' ? 'alert alert-danger' : 'alert alert-success';
        echo '<div class="' . $classe . '">' . htmlspecialchars($_SESSION['flash_message']) . '</div>';
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
    }
}