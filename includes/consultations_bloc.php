<?php
/*
 * Bloc d'affichage partagé (formulaire de création + liste + bouton Fermer).
 * Variables attendues, définies par la page qui l'inclut :
 *   $consultations   tableau des consultations à lister
 *   $token           jeton CSRF
 *   $erreur          message d'erreur du formulaire (ou null)
 *   $saisie          valeurs saisies (titre, contenu, date_fermeture)
 *   $titreFormulaire texte du titre du formulaire
 *   $afficherPortee  true pour afficher la colonne "Portée" (agent)
 *   $formulaireActif false pour masquer le formulaire (ex. point focal sans structure)
 */

/**
 * @var array       $consultations
 * @var string      $token
 * @var string|null $erreur
 * @var array       $saisie
 * @var string      $titreFormulaire
 * @var bool        $afficherPortee
 * @var bool        $formulaireActif
 */
?>
<style>
    .cg-champ { margin-bottom: 14px; }
    .cg-champ label { display: block; font-weight: 600; font-size: .85rem; margin-bottom: 4px; }
    .cg-champ input, .cg-champ textarea { width: 100%; padding: 9px 12px; border: 1px solid #E4E7EC; border-radius: 8px; font-size: .9rem; }
    .cg-champ input:focus, .cg-champ textarea:focus { outline: none; border-color: #1B3FAE; box-shadow: 0 0 0 3px rgba(27,63,174,.14); }
    .cg-aide { font-size: .78rem; color: #667085; margin-top: 3px; }
    .cg-table { width: 100%; border-collapse: collapse; font-size: .84rem; }
    .cg-table th { text-align: left; padding: 10px 12px; background: #F5F7FA; color: #667085; font-size: .72rem; text-transform: uppercase; border-bottom: 1px solid #E4E7EC; white-space: nowrap; }
    .cg-table td { padding: 12px; border-bottom: 1px solid #E4E7EC; vertical-align: middle; }
    .cg-badge { display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: .7rem; font-weight: 700; }
    .cg-badge.ouverte { background: #E5F5EC; color: #0B6B3A; }
    .cg-badge.fermee { background: #EEF1F2; color: #667085; }
    .cg-badge.generale { background: #E8EDFB; color: #1B3FAE; }
    .cg-badge.structure { background: #E5F5EC; color: #0B6B3A; }
    .cg-btn-fermer { padding: 6px 14px; border: 1px solid #c0392b; border-radius: 6px; background: #fff; color: #c0392b; font-weight: 700; font-size: .78rem; cursor: pointer; }
    .cg-btn-fermer:hover { background: #c0392b; color: #fff; }
    .cg-lien { color: #1B3FAE; font-weight: 600; font-size: .8rem; margin-right: 10px; }
</style>

<div class="row g-4">

    <?php if ($formulaireActif): ?>
    <div class="col-lg-4">
        <div class="mrri-panel">
            <div class="mrri-panel-header">
                <h2 class="mrri-panel-title"><?= echapper($titreFormulaire) ?></h2>
            </div>
            <div class="mrri-panel-body">

                <?php if (!empty($erreur)): ?>
                    <div class="mrri-alert mrri-alert-danger"><?= echapper($erreur) ?></div>
                <?php endif; ?>

                <form action="" method="post">
                    <input type="hidden" name="csrf_token" value="<?= echapper($token) ?>">
                    <input type="hidden" name="action" value="creer">

                    <div class="cg-champ">
                        <label for="titre">Titre</label>
                        <input type="text" id="titre" name="titre" required maxlength="255"
                               value="<?= echapper($saisie['titre']) ?>">
                    </div>

                    <div class="cg-champ">
                        <label for="contenu">Contenu</label>
                        <textarea id="contenu" name="contenu" rows="6" required minlength="10"><?= echapper($saisie['contenu']) ?></textarea>
                        <div class="cg-aide">Présentez le sujet et ce que vous attendez des citoyens.</div>
                    </div>

                    <div class="cg-champ">
                        <label for="date_fermeture">Date de fermeture (facultative)</label>
                        <input type="date" id="date_fermeture" name="date_fermeture"
                               min="<?= date('Y-m-d') ?>" value="<?= echapper($saisie['date_fermeture']) ?>">
                        <div class="cg-aide">Sans date, la consultation reste ouverte jusqu'à sa clôture manuelle.</div>
                    </div>

                    <button type="submit" class="mrri-btn mrri-btn-primary">Publier la consultation</button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="<?= $formulaireActif ? 'col-lg-8' : 'col-12' ?>">
        <div class="mrri-panel">
            <div class="mrri-panel-header">
                <h2 class="mrri-panel-title">Consultations (<?= count($consultations) ?>)</h2>
            </div>
            <div class="mrri-panel-body" style="overflow-x:auto;">
                <?php if (empty($consultations)): ?>
                    <p class="mrri-page-description">Aucune consultation pour le moment.</p>
                <?php else: ?>
                    <table class="cg-table">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <?php if ($afficherPortee): ?><th>Portée</th><?php endif; ?>
                                <th>Statut</th>
                                <th>Fermeture</th>
                                <th>Contrib.</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($consultations as $c): ?>
                                <?php $ouverte = consultationEstOuverte($c); ?>
                                <tr>
                                    <td><strong><?= echapper($c['titre']) ?></strong></td>
                                    <?php if ($afficherPortee): ?>
                                        <td>
                                            <?php if (empty($c['id_structure'])): ?>
                                                <span class="cg-badge generale">Générale</span>
                                            <?php else: ?>
                                                <span class="cg-badge structure"><?= echapper($c['nom_struc']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td>
                                        <span class="cg-badge <?= $ouverte ? 'ouverte' : 'fermee' ?>">
                                            <?= $ouverte ? 'Ouverte' : 'Fermée' ?>
                                        </span>
                                    </td>
                                    <td><?= !empty($c['date_fermeture']) ? formaterDateFr($c['date_fermeture']) : '—' ?></td>
                                    <td><?= (int) $c['nb_contributions'] ?></td>
                                    <td style="white-space:nowrap;">
                                        <a class="cg-lien" href="../public/consultation_detail.php?id=<?= (int) $c['id_consultation'] ?>">Voir</a>
                                        <?php if ($ouverte): ?>
                                            <form action="" method="post" style="display:inline;"
                                                  onsubmit="return confirm('Fermer cette consultation ? Plus aucune contribution ni vote ne sera possible.');">
                                                <input type="hidden" name="csrf_token" value="<?= echapper($token) ?>">
                                                <input type="hidden" name="action" value="fermer">
                                                <input type="hidden" name="id_consultation" value="<?= (int) $c['id_consultation'] ?>">
                                                <button type="submit" class="cg-btn-fermer">Fermer</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>