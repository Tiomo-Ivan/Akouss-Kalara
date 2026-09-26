// =============================================================================
// RÔLE : parcours de publication d'offre — recherche livre existant,
// création si besoin, puis formulaire d'offre (physique/numérique).
// =============================================================================

if (!estConnecte()) window.location.href = "connexion.html";

let livreChoisi = null;
let categories = [];
let minuteur = null;

async function chargerCategories() {
  categories = await appelApi("/catalogue/categories.php");
}

function afficherEtapeRecherche() {
  document.getElementById("zone-etape").innerHTML = `
    <div class="carte" style="padding:24px;">
      <label class="champ-label">Rechercher le livre que vous voulez vendre</label>
      <input class="champ" id="champ-recherche-livre" placeholder="Titre ou auteur..." autofocus>
      <div id="resultats-recherche" style="margin-top:12px;"></div>
      <button class="btn btn-secondaire espace-haut" id="bouton-creer-livre" style="display:none; width:100%;">+ Ce livre n'existe pas, le créer</button>
    </div>
  `;

  const champ = document.getElementById("champ-recherche-livre");
  champ.addEventListener("input", (e) => {
    clearTimeout(minuteur);
    const valeur = e.target.value;
    if (valeur.length < 2) {
      document.getElementById("resultats-recherche").innerHTML = "";
      document.getElementById("bouton-creer-livre").style.display = "none";
      return;
    }
    minuteur = setTimeout(async () => {
      const resultats = await appelApi(`/vendeur/livres_existants.php?q=${encodeURIComponent(valeur)}`);
      const zone = document.getElementById("resultats-recherche");
      zone.innerHTML = resultats.map(l => `
        <div class="carte" style="padding:10px 12px; margin-bottom:6px; cursor:pointer;" onclick='choisirLivreExistant(${JSON.stringify(l)})'>
          <strong>${l.titre}</strong><br><span style="font-size:12px; color:var(--texte-clair);">${l.auteur}</span>
        </div>
      `).join("");
      document.getElementById("bouton-creer-livre").style.display = "block";
      document.getElementById("bouton-creer-livre").dataset.titre = valeur;
    }, 350);
  });

  document.getElementById("bouton-creer-livre").addEventListener("click", () => {
    afficherEtapeNouveauLivre(document.getElementById("champ-recherche-livre").value);
  });
}

function choisirLivreExistant(livre) {
  livreChoisi = { id: livre.id, titre: livre.titre, auteur: livre.auteur };
  afficherEtapeOffre();
}

function afficherEtapeNouveauLivre(titrePrerempli) {
  document.getElementById("zone-etape").innerHTML = `
    <div class="carte" style="padding:24px;">
      <h3 style="margin-bottom:14px;">Créer la fiche du livre</h3>
      <div id="zone-message-livre"></div>
      <div class="champ-groupe"><label class="champ-label">Titre</label><input class="champ" id="nouveau-titre" value="${titrePrerempli || ''}"></div>
      <div class="champ-groupe"><label class="champ-label">Auteur</label><input class="champ" id="nouveau-auteur"></div>
      <div class="champ-groupe">
        <label class="champ-label">Catégorie</label>
        <select class="champ" id="nouveau-categorie">
          <option value="">Choisir...</option>
          ${categories.map(c => `<option value="${c.id}">${c.nom}</option>`).join("")}
        </select>
      </div>
      <div class="champ-groupe"><label class="champ-label">Description (optionnel)</label><textarea class="champ" id="nouveau-description" rows="3"></textarea></div>
      <button class="btn btn-primaire" style="width:100%;" onclick="validerNouveauLivre()">Continuer</button>
    </div>
  `;
}

function validerNouveauLivre() {
  const titre = document.getElementById("nouveau-titre").value.trim();
  const auteur = document.getElementById("nouveau-auteur").value.trim();
  const categorieId = document.getElementById("nouveau-categorie").value;
  const description = document.getElementById("nouveau-description").value.trim();

  if (!titre || !auteur || !categorieId) {
    document.getElementById("zone-message-livre").innerHTML = `<div class="message-erreur">Titre, auteur et catégorie sont requis.</div>`;
    return;
  }

  livreChoisi = { nouveau: true, titre, auteur, categorie_id: categorieId, description };
  afficherEtapeOffre();
}

function afficherEtapeOffre() {
  document.getElementById("zone-etape").innerHTML = `
    <div class="carte" style="padding:24px;">
      <div style="background:var(--fond); padding:12px 14px; border-radius:8px; margin-bottom:18px;">
        <strong>${livreChoisi.titre}</strong><br><span style="font-size:12px; color:var(--texte-clair);">${livreChoisi.auteur}</span>
      </div>
      <div id="zone-message-offre"></div>

      <div class="champ-groupe">
        <label class="champ-label">Type de livre</label>
        <div style="display:flex; gap:10px;">
          <button type="button" class="btn btn-primaire" id="btn-type-physique" style="flex:1;" onclick="choisirType('physique')">Physique</button>
          <button type="button" class="btn btn-secondaire" id="btn-type-numerique" style="flex:1;" onclick="choisirType('numerique')">Numérique</button>
        </div>
      </div>

      <div class="champ-groupe"><label class="champ-label">Prix (FCFA)</label><input class="champ" type="number" id="offre-prix"></div>

      <div id="zone-champs-physique">
        <div class="champ-groupe"><label class="champ-label">Quantité en stock</label><input class="champ" type="number" id="offre-stock"></div>
        <div class="champ-groupe">
          <label class="champ-label">État</label>
          <select class="champ" id="offre-etat"><option value="neuf">Neuf</option><option value="occasion">Occasion</option></select>
        </div>
      </div>

      <div id="zone-champs-numerique" style="display:none;">
        <div class="champ-groupe">
          <label class="champ-label">Statut des droits d'auteur</label>
          <select class="champ" id="offre-droits" onchange="document.getElementById('zone-justificatif').style.display = this.value === 'droits_detenus' ? 'block' : 'none';">
            <option value="">Choisir...</option>
            <option value="domaine_public">Domaine public</option>
            <option value="droits_detenus">Je détiens les droits</option>
          </select>
        </div>
        <div class="champ-groupe" id="zone-justificatif" style="display:none;">
          <label class="champ-label">Justificatif de droits (upload à finaliser — indiquez "fourni" pour tester)</label>
          <input class="champ" id="offre-justificatif" placeholder="ex: licence.pdf">
        </div>
      </div>

      <button class="btn btn-primaire" style="width:100%; margin-top:10px;" onclick="soumettreOffre()">Soumettre l'offre</button>
    </div>
  `;
  choisirType('physique');
}

let typeSelectionne = 'physique';
function choisirType(type) {
  typeSelectionne = type;
  document.getElementById("zone-champs-physique").style.display = type === 'physique' ? 'block' : 'none';
  document.getElementById("zone-champs-numerique").style.display = type === 'numerique' ? 'block' : 'none';
  document.getElementById("btn-type-physique").className = type === 'physique' ? 'btn btn-primaire' : 'btn btn-secondaire';
  document.getElementById("btn-type-numerique").className = type === 'numerique' ? 'btn btn-primaire' : 'btn btn-secondaire';
}

async function soumettreOffre() {
  const zoneMessage = document.getElementById("zone-message-offre");
  const prix = document.getElementById("offre-prix").value;

  const payload = { type: typeSelectionne, prix };
  if (livreChoisi.nouveau) {
    payload.livre_nouveau = { titre: livreChoisi.titre, auteur: livreChoisi.auteur, categorie_id: livreChoisi.categorie_id, description: livreChoisi.description };
  } else {
    payload.livre_id = livreChoisi.id;
  }

  if (typeSelectionne === 'physique') {
    payload.quantite_stock = document.getElementById("offre-stock").value;
    payload.etat_article = document.getElementById("offre-etat").value;
  } else {
    payload.statut_droits = document.getElementById("offre-droits").value;
    payload.justificatif_droits = document.getElementById("offre-justificatif")?.value || null;
  }

  try {
    await appelApi("/vendeur/mes_offres.php", { methode: "POST", corps: payload });
    document.getElementById("zone-etape").innerHTML = `
      <div class="carte centre" style="padding:32px;">
        <h2>Offre soumise !</h2>
        <p style="color:var(--texte-clair); font-size:13.5px;">En attente de modération par un administrateur.</p>
        <a href="vendeur-offres.html" class="btn btn-primaire espace-haut" style="display:inline-flex;">Voir mes offres</a>
      </div>`;
  } catch (err) {
    const messages = err.donnees?.erreurs ? Object.values(err.donnees.erreurs).join(" | ") : err.message;
    zoneMessage.innerHTML = `<div class="message-erreur">${messages}</div>`;
  }
}

chargerCategories().then(afficherEtapeRecherche);
