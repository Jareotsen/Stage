
  /* ================= DONNÉES ================= */
  const categories = [
    { id:"icnp", label:"Institutions Constitutionnelles non Parlementaire" },
    { id:"icp",  label:"Institutions Constitutionnelles Parlementaires" },
    { id:"icj",  label:"Institutions à Caractère Juridictionnelles" },
    { id:"aai",  label:"Autorité Administrative Indépendante" },
    { id:"mrri", label:"Ministère des Réformes et des Relations avec les Institutions" },
  ];

  const structures = [
    { nom:"Assemblée de la transition", sigle:"AT", cat:"icnp",
      desc:"Organe consultatif chargé d'accompagner la période de transition institutionnelle.",
      adresse:"Libreville, Centre-ville", tel:"+241 01 00 00 00", email:"contact@assemblee-transition.ga",
      docs:["Règlement intérieur.pdf","Rapport d'activité 2025.pdf"] },
    { nom:"Cabinet du président", sigle:"CP", cat:"icnp",
      desc:"Structure d'appui direct à la présidence dans l'exercice de ses attributions institutionnelles.",
      adresse:"Libreville, Palais présidentiel", tel:"+241 01 00 00 01", email:"contact@presidence.ga",
      docs:["Organigramme.pdf"] },
    { nom:"Haute Autorité de la Communication", sigle:"HAC", cat:"icnp",
      desc:"Autorité chargée de réguler et de garantir le pluralisme dans le secteur des médias.",
      adresse:"Libreville, Batterie IV", tel:"+241 01 00 00 02", email:"contact@hac.ga",
      docs:["Texte fondateur.pdf","Rapport annuel 2025.pdf"] },
    { nom:"Assemblée Nationale", sigle:"AN", cat:"icp",
      desc:"Chambre basse du Parlement, chargée notamment du vote des lois.",
      adresse:"Libreville, Quartier administratif", tel:"+241 01 00 00 04", email:"contact@assemblee-nationale.ga",
      docs:["Règlement intérieur.pdf","Calendrier des sessions.pdf"] },
    { nom:"Sénat de la transition", sigle:"ST", cat:"icp",
      desc:"Chambre haute du Parlement pendant la période de transition.",
      adresse:"Libreville, Quartier administratif", tel:"+241 01 00 00 03", email:"contact@senat.ga",
      docs:["Règlement intérieur.pdf"] },
    { nom:"Cours des comptes", sigle:"CDC", cat:"icj",
      desc:"Juridiction chargée du contrôle des comptes publics et de la gestion des finances de l'État.",
      adresse:"Libreville, Quartier administratif", tel:"+241 01 00 00 06", email:"contact@cours-comptes.ga",
      docs:["Texte fondateur.pdf"] },
    { nom:"Conseil d'État", sigle:"CE", cat:"icj",
      desc:"Juridiction administrative suprême, conseille également le gouvernement.",
      adresse:"Libreville, Quartier administratif", tel:"+241 01 00 00 07", email:"contact@conseil-etat.ga",
      docs:["Texte fondateur.pdf"] },
    { nom:"Cour de Cassation", sigle:"CCASS", cat:"icj",
      desc:"Plus haute juridiction de l'ordre judiciaire, garante de la bonne application de la loi.",
      adresse:"Libreville, Quartier administratif", tel:"+241 01 00 00 08", email:"contact@cour-cassation.ga",
      docs:["Texte fondateur.pdf"] },
    { nom:"Cour Constitutionnelle", sigle:"CC", cat:"icj",
      desc:"Juridiction chargée de veiller au respect de la Constitution.",
      adresse:"Libreville, Quartier administratif", tel:"+241 01 00 00 09", email:"contact@cour-constitutionnelle.ga",
      docs:["Texte fondateur.pdf","Décisions publiées.pdf"] },
    { nom:"Ministère des Réformes et des Relations avec les Institutions", sigle:"MRRI", cat:"mrri",
      desc:"Ministère porteur des réformes institutionnelles et de la coordination avec les structures sous tutelle.",
      adresse:"Libreville, Batterie IV", tel:"+241 01 00 00 10", email:"contact@mrri.ga",
      docs:["Organigramme.pdf","Rapport annuel 2025.pdf"] },
  ];

  const dropdownMeta = {
    icnp:"Institutions Constitutionnelles Non Parlementaires",
    icp:"Institutions Constitutionnelles Parlementaires",
    icj:"Institutions à Caractère Juridictionnelles",
  };

  const iconBuilding = (c) => `<svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="${c}" stroke-width="2"><rect x="4" y="3" width="16" height="18" rx="1"/><line x1="9" y1="7" x2="9" y2="7.01"/><line x1="15" y1="7" x2="15" y2="7.01"/><line x1="9" y1="11" x2="9" y2="11.01"/><line x1="15" y1="11" x2="15" y2="11.01"/><line x1="9" y1="15" x2="9" y2="15.01"/><line x1="15" y1="15" x2="15" y2="15.01"/><line x1="10" y1="21" x2="10" y2="17"/><line x1="14" y1="21" x2="14" y2="17"/></svg>`;

  /* ================= NAVIGATION ENTRE VUES ================= */
  const vues = {
    landing: document.getElementById("vue-landing"),
    accueil: document.getElementById("vue-accueil"),
    catalogue: document.getElementById("vue-catalogue"),
    fiche: document.getElementById("vue-fiche"),
  };
  function afficherVue(nom){
    Object.values(vues).forEach(v=>v.style.display="none");
    vues[nom].style.display="block";
    window.scrollTo(0,0);
  }

  document.getElementById("btn-entrer").addEventListener("click", ()=>afficherVue("accueil"));
  document.getElementById("btn-mrri-accueil").addEventListener("click", ()=>ouvrirFiche("Ministère des Réformes et des Relations avec les Institutions"));
  document.getElementById("btn-loupe-accueil").addEventListener("click", ()=>{afficherVue("catalogue"); document.getElementById("champ-recherche").focus();});
  document.getElementById("cta-catalogue").addEventListener("click", ()=>afficherVue("catalogue"));
  document.getElementById("btn-accueil-catalogue").addEventListener("click", ()=>afficherVue("accueil"));
  document.getElementById("btn-mrri-catalogue").addEventListener("click", ()=>ouvrirFiche("Ministère des Réformes et des Relations avec les Institutions"));
  document.getElementById("btn-retour").addEventListener("click", ()=>afficherVue("catalogue"));

  document.querySelectorAll('.actu-item').forEach(el=>{
    el.addEventListener("click", ()=>afficherToast("Aperçu de l'article non disponible dans ce prototype"));
  });

  /* ---------- carrousel actualités ---------- */
  const slidesCarousel = [
    { legende:"DEMGAB — Appui aux réformes institutionnelles", image:"images/Caroussel1.png" },
    { legende:"Cérémonie officielle au Ministère", image:"images/conference.png" },
    { legende:"Signature d'un partenariat institutionnel", image:"images/Caroussel1.png" },
  ];
  function rendreCarousel(i){
    const s = slidesCarousel[i];
    document.getElementById("carousel-image").src = s.image;
    document.getElementById("carousel-legende").textContent = s.legende;
    document.querySelectorAll(".dot").forEach(d=>d.classList.toggle("actif", Number(d.dataset.i)===i));
  }
  document.querySelectorAll(".dot").forEach(d=>{
    d.addEventListener("click", ()=>rendreCarousel(Number(d.dataset.i)));
  });
  rendreCarousel(0);

  /* ---------- menus déroulants du catalogue ---------- */
  const menus = document.querySelectorAll(".menu");
  function fermerMenus(){menus.forEach(m=>m.classList.remove("ouvert"));}
  function rendreMenus(){
    menus.forEach(m=>{
      const catId = m.dataset.menu;
      const items = structures.filter(s=>s.cat===catId);
      m.innerHTML = items.map(s=>`<div class="menu-item" data-nom="${s.nom}">${s.nom}</div>`).join("");
      m.querySelectorAll(".menu-item").forEach(mi=>{
        mi.addEventListener("click", (e)=>{
          e.stopPropagation();
          fermerMenus();
          ouvrirFiche(mi.dataset.nom);
        });
      });
    });
  }
  rendreMenus();
  document.querySelectorAll('[data-nav]').forEach(btn=>{
    btn.addEventListener("click", (e)=>{
      e.stopPropagation();
      const catId = btn.dataset.nav;
      const menu = document.querySelector(`.menu[data-menu="${catId}"]`);
      const dejaOuvert = menu.classList.contains("ouvert");
      fermerMenus();
      if(!dejaOuvert) menu.classList.add("ouvert");
    });
  });
  document.addEventListener("click", fermerMenus);

  /* ---------- onglets secteurs + sélecteur ---------- */
  let filtreSecteur = "Tous";
  const tabsEl = document.getElementById("tabs");
  const selectEl = document.getElementById("select-secteur");

  function rendreOnglets(){
    const principaux = categories.filter(c=>["icnp","icp","icj"].includes(c.id));
    tabsEl.innerHTML = `<div class="tab ${filtreSecteur==='Tous'?'actif':''}" data-cat="Tous">Tous</div>` +
      principaux.map(c=>`<div class="tab ${filtreSecteur===c.id?'actif':''}" data-cat="${c.id}">${c.label}</div>`).join("");
    tabsEl.querySelectorAll(".tab").forEach(t=>{
      t.addEventListener("click", ()=>{
        filtreSecteur = t.dataset.cat;
        selectEl.value = "Tous";
        rendreOnglets();
        rendreListe();
      });
    });

    selectEl.innerHTML = `<option value="Tous">Tous les secteurs</option>` +
      categories.map(c=>`<option value="${c.id}">${c.label}</option>`).join("");
    selectEl.value = filtreSecteur;
  }
  selectEl.addEventListener("change", ()=>{
    filtreSecteur = selectEl.value;
    rendreOnglets();
    rendreListe();
  });

  /* ---------- liste des structures ---------- */
  const listeEl = document.getElementById("liste");
  const champRecherche = document.getElementById("champ-recherche");
  const nbStructuresEl = document.getElementById("nb-structures");

  function normaliserRecherche(texte){
    return texte.normalize("NFD").replace(/[\u0300-\u036f]/g,"").toLowerCase().trim();
  }

  function rendreListe(){
    const terme = normaliserRecherche(champRecherche.value);
    const resultats = structures.filter(s=>{
      const okSecteur = filtreSecteur==="Tous" || s.cat===filtreSecteur;
      const okTerme = !terme ||
        normaliserRecherche(s.nom).includes(terme) ||
        normaliserRecherche(s.sigle).includes(terme);
      return okSecteur && okTerme;
    });
    nbStructuresEl.textContent = resultats.length;

    if(resultats.length===0){
      listeEl.innerHTML = `<div class="vide">Aucune structure ne correspond à votre recherche.</div>`;
      return;
    }

    listeEl.innerHTML = resultats.map(s => `
      <div class="carte" data-nom="${s.nom}">
        <div class="carte-g">
          <div class="carte-icone">${iconBuilding("#3a75c4")}</div>
          <div class="carte-nom">${s.nom}</div>
        </div>
        <div class="carte-lien">Voir la fiche ↗</div>
      </div>
    `).join("");

    listeEl.querySelectorAll(".carte").forEach(c=>{
      c.addEventListener("click", ()=>ouvrirFiche(c.dataset.nom));
    });
  }
  champRecherche.addEventListener("input", rendreListe);

  /* ---------- fiche détaillée ---------- */
  function ouvrirFiche(nom){
    const s = structures.find(x=>x.nom===nom);
    if(!s) return;

    document.getElementById("fiche-icone-svg").innerHTML = iconBuilding("#7fb3ff").replace('width="17" height="17"','width="22" height="22"');
    document.getElementById("fiche-nom").textContent = s.nom;
    document.getElementById("fiche-type").textContent = dropdownMeta[s.cat] || categories.find(c=>c.id===s.cat).label;
    document.getElementById("fiche-desc").textContent = s.desc;
    document.getElementById("f-secteur").textContent = categories.find(c=>c.id===s.cat).label;
    document.getElementById("f-adresse").textContent = s.adresse;
    document.getElementById("f-tel").textContent = s.tel;
    document.getElementById("f-email").textContent = s.email;

    const docsEl = document.getElementById("docs-liste");
    docsEl.innerHTML = s.docs.map(d => `
      <div class="doc">
        <div class="doc-g">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#9a9ea6" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/></svg>
          <span>${d}</span>
        </div>
        <button data-doc="${d}" aria-label="Télécharger ${d}">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        </button>
      </div>
    `).join("");
    docsEl.querySelectorAll("button").forEach(b=>{
      b.addEventListener("click", ()=>telechargerDocument(b.dataset.doc));
    });

    afficherVue("fiche");
  }

  /* ---------- téléchargement simulé ---------- */
  const toastEl = document.getElementById("toast");
  let toastTimer;
  function afficherToast(texte){
    toastEl.textContent = texte;
    toastEl.classList.add("show");
    clearTimeout(toastTimer);
    toastTimer = setTimeout(()=>toastEl.classList.remove("show"), 2200);
  }
  function telechargerDocument(nomFichier){
    const contenu = "Document d'exemple généré par le prototype.\nFichier : " + nomFichier;
    const blob = new Blob([contenu], {type:"text/plain"});
    const url = URL.createObjectURL(blob);
    const a = document.createElement("a");
    a.href = url;
    a.download = nomFichier.replace(/\.pdf$/,".txt");
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
    afficherToast("Téléchargement : " + nomFichier);
  }

  /* ---------- initialisation ---------- */
  rendreOnglets();
  rendreListe();
  afficherVue("landing");
