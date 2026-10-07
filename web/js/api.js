// =============================================================================
// RÔLE DE CE FICHIER : centralise tous les appels vers l'API PHP, ainsi que
// la gestion du jeton de session (stocké dans localStorage). Chaque page
// HTML inclut ce fichier avant son propre script spécifique.
// =============================================================================

const URL_BASE_API = "http://localhost/akouss-kalara-php/api";

function obtenirJeton() {
  return localStorage.getItem("jeton");
}

function estConnecte() {
  return !!obtenirJeton();
}

function obtenirUtilisateurCourant() {
  const brut = localStorage.getItem("utilisateur");
  return brut ? JSON.parse(brut) : null;
}

function connecterLocalement(jeton, utilisateur) {
  localStorage.setItem("jeton", jeton);
  localStorage.setItem("utilisateur", JSON.stringify(utilisateur));
}

function deconnecterLocalement() {
  localStorage.removeItem("jeton");
  localStorage.removeItem("utilisateur");
  window.location.href = "index.html";
}

/**
 * Fonction générique d'appel API.
 *
 * Par défaut, les données sont envoyées en JSON.
 * Si le corps est un FormData, il est envoyé directement afin de permettre
 * les uploads de fichiers. Dans ce cas, on ne définit surtout pas
 * manuellement Content-Type : le navigateur ajoute automatiquement la
 * boundary multipart/form-data.
 */
async function appelApi(chemin, options = {}) {
  const corpsEstFormData = options.corps instanceof FormData;

  const entetes = corpsEstFormData
    ? { ...(options.entetes || {}) }
    : { "Content-Type": "application/json", ...(options.entetes || {}) };

  const jeton = obtenirJeton();
  if (jeton) entetes["Authorization"] = `Bearer ${jeton}`;

  const reponse = await fetch(`${URL_BASE_API}${chemin}`, {
    method: options.methode || "GET",
    headers: entetes,
    body: options.corps
      ? (corpsEstFormData ? options.corps : JSON.stringify(options.corps))
      : undefined,
  });

  const donnees = await reponse.json().catch(() => ({}));

  if (!reponse.ok) {
    const erreur = new Error(donnees.erreur || "Une erreur est survenue.");
    erreur.donnees = donnees;
    erreur.status = reponse.status;
    throw erreur;
  }

  return donnees;
}

/** Met à jour dynamiquement l'en-tête (connexion/déconnexion, liens vendeur/admin). */
function initialiserEntete() {
  const zoneActions = document.getElementById("entete-actions");
  if (!zoneActions) return;

  const utilisateur = obtenirUtilisateurCourant();

  if (!utilisateur) {
    zoneActions.innerHTML = `
      <a href="connexion.html">Se connecter</a>
      <a href="inscription.html" class="btn btn-primaire" style="padding:8px 16px;">Créer un compte</a>
    `;
    return;
  }

  let liens = `<a href="panier.html">Panier</a><a href="mes-commandes.html">Mes commandes</a>`;

  if (utilisateur.est_vendeur_actif) {
    liens += `<a href="vendeur-offres.html">Mes offres</a>`;
  }

  if (utilisateur.est_admin) {
    liens += `<a href="admin.html">Administration</a>`;
  }

  liens += `<span>${utilisateur.nom_complet.split(" ")[0]}</span>`;
  liens += `<button onclick="deconnecterLocalement()">Déconnexion</button>`;

  zoneActions.innerHTML = liens;
}

document.addEventListener("DOMContentLoaded", initialiserEntete);
