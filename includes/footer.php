
<footer class="mrri-footer">
    <!-- Bandeau aux couleurs du Gabon -->
    <div class="mrri-footer-band"></div>
    <div class="container">
        <div class="row g-5 py-5">
            <!-- =========================================
                 IDENTITÉ DU MINISTÈRE
                 ========================================= -->
            <div class="col-lg-5">
                <div class="mrri-footer-brand">
                    <img
                        src="../../publique/image/sceau.png"
                        alt="Armoiries de la République Gabonaise"
                        class="mrri-footer-logo"
                    >
                    <div>
                        <div class="mrri-footer-title">
                            Ministère de la Réforme et des
                            <br>
                            Relations avec les Institutions
                        </div>
                        <div class="mrri-footer-subtitle">
                            République Gabonaise
                        </div>
                    </div>
                </div>
                <p class="mrri-footer-description">
                    Plateforme de consultation des structures
                    sous tutelle du Ministère de la Réforme et
                    des Relations avec les Institutions.
                </p>
            </div>
                 
            <div class="col-6 col-lg-3">

                <h6 class="mrri-footer-heading">
                    Navigation
                </h6>

                <ul class="mrri-footer-links">

                    <li>
                        <a href="../public/accueil.php">
                            Accueil
                        </a>
                    </li>

                    <li>
                        <a href="../public/catalogue.php">
                            Structures sous tutelle
                        </a>
                    </li>

                    <li>
                        <a href="../public/actualite.php">
                            Actualités
                        </a>
                    </li>

                </ul>

            </div>


            <!-- =========================================
                 LIENS INSTITUTIONNELS
                 ========================================= -->

            <div class="col-6 col-lg-4">

                <h6 class="mrri-footer-heading">
                    Liens institutionnels
                </h6>

                <ul class="mrri-footer-links">

                    <li>
                        <a href="#">
                            Présidence de la République
                        </a>
                    </li>

                    <li>
                        <a href="#">
                            Gouvernement de la République
                        </a>
                    </li>

                    <li>
                        <a href="#">
                            Portail officiel du Gabon
                        </a>
                    </li>

                </ul>

            </div>

        </div>


        <!-- =========================================
             SÉPARATION
             ========================================= -->

        <div class="mrri-footer-separator"></div>


        <!-- =========================================
             BAS DU FOOTER
             ========================================= -->

        <div class="mrri-footer-bottom">

            <div class="mrri-footer-copyright">

                © <?= date("Y") ?>
                Ministère de la Réforme et des Relations
                avec les Institutions.
                Tous droits réservés.

            </div>


            <div class="mrri-footer-bottom-links">

                <a href="#">
                    Mentions légales
                </a>

                <span class="mrri-footer-dot">•</span>

                <!-- Accès professionnel volontairement discret -->

                <a
                    href="../public/connexion.php"
                    class="mrri-professional-link"
                >
                    <span class="mrri-lock-icon">🔒</span>
                    Espace professionnel
                </a>

            </div>

        </div>

    </div>

</footer>


<style>

/* =========================================================
   FOOTER MRRI
   ========================================================= */

.mrri-footer {

    position: relative;

    background: var(--mrri-bleu-nuit, #0B1D4D);

    color: #C9D3E8;

    margin-top: 0;

}


/* =========================================================
   BANDEAU COULEURS
   ========================================================= */

.mrri-footer-band {

    height: 5px;

    background:
        linear-gradient(
            90deg,
            var(--mrri-vert, #0F8A4B) 0 33.33%,
            var(--mrri-or, #C9972C) 33.33% 66.66%,
            var(--mrri-bleu, #1B3FAE) 66.66% 100%
        );

}


/* =========================================================
   IDENTITÉ
   ========================================================= */

.mrri-footer-brand {

    display: flex;

    align-items: center;

    gap: 16px;
}


.mrri-footer-logo {

    width: 58px;

    height: 58px;

    object-fit: contain;

    flex-shrink: 0;
}


.mrri-footer-title {

    color: #FFFFFF;

    font-family:
        var(--font-title,
        "Fraunces",
        Georgia,
        serif);

    font-size: 1rem;

    font-weight: 700;

    line-height: 1.35;
}


.mrri-footer-subtitle {

    margin-top: 5px;

    color: #AEBBD5;

    font-size: .72rem;

    letter-spacing: .08em;

    text-transform: uppercase;
}


.mrri-footer-description {

    max-width: 470px;

    margin: 22px 0 0;

    color: #AEBBD5;

    font-size: .83rem;

    line-height: 1.7;
}


/* =========================================================
   TITRES
   ========================================================= */

.mrri-footer-heading {

    position: relative;

    display: inline-block;

    margin: 0 0 20px;

    color: #FFFFFF;

    font-family:
        var(--font-body,
        "Public Sans",
        sans-serif);

    font-size: .78rem;

    font-weight: 700;

    letter-spacing: .08em;

    text-transform: uppercase;
}


.mrri-footer-heading::after {

    content: "";

    display: block;

    width: 28px;

    height: 2px;

    margin-top: 9px;

    background: var(--mrri-or, #C9972C);
}


/* =========================================================
   LIENS
   ========================================================= */

.mrri-footer-links {

    list-style: none;

    padding: 0;

    margin: 0;
}


.mrri-footer-links li {

    margin-bottom: 11px;
}


.mrri-footer-links a {

    color: #AEBBD5;

    font-size: .84rem;

    text-decoration: none;

    transition:
        color .2s ease,
        padding-left .2s ease;
}


.mrri-footer-links a:hover {

    color: #FFFFFF;

    padding-left: 4px;
}


/* =========================================================
   SÉPARATEUR
   ========================================================= */

.mrri-footer-separator {

    height: 1px;

    background: rgba(255,255,255,.12);
}


/* =========================================================
   BOTTOM
   ========================================================= */

.mrri-footer-bottom {

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;

    padding: 20px 0;

}


.mrri-footer-copyright {

    color: #8999B8;

    font-size: .72rem;

    line-height: 1.5;
}


.mrri-footer-bottom-links {

    display: flex;

    align-items: center;

    flex-wrap: wrap;

    gap: 10px;
}


.mrri-footer-bottom-links a {

    color: #9DABCA;

    font-size: .72rem;

    text-decoration: none;

    transition: color .2s ease;
}


.mrri-footer-bottom-links a:hover {

    color: #FFFFFF;
}


.mrri-footer-dot {

    color: #526485;

    font-size: .65rem;
}


/* =========================================================
   ESPACE PROFESSIONNEL
   ========================================================= */

.mrri-professional-link {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 5px 9px;

    border: 1px solid rgba(255,255,255,.10);

    border-radius: 5px;

    transition:
        border-color .2s ease,
        background .2s ease;
}


.mrri-professional-link:hover {

    background: rgba(255,255,255,.06);

    border-color: rgba(255,255,255,.22);

    padding-left: 9px !important;
}


.mrri-lock-icon {

    font-size: .65rem;

    opacity: .8;
}


/* =========================================================
   RESPONSIVE
   ========================================================= */

@media (max-width: 767px) {

    .mrri-footer-bottom {

        align-items: flex-start;

        flex-direction: column;

    }

    .mrri-footer-bottom-links {

        gap: 8px;

    }

}

</style>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
```
