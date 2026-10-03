// =============================================================================
// RÔLE DE CE FICHIER : logique de la page d'accueil — charge et affiche
// les livres du catalogue.
// =============================================================================

async function chargerLivresAccueil() {
  const zone = document.getElementById("grille-livres");
  try {
    const livres = await appelApi("/catalogue/livres.php");
    if (livres.length === 0) {
      zone.innerHTML = `<p style="color:var(--texte-clair);">Aucun livre disponible pour le moment.</p>`;
      return;
    }
    zone.innerHTML = livres.map(construireCarteLivreHtml).join("");
  } catch (err) {
    zone.innerHTML = `<p class="message-erreur">Erreur de chargement du catalogue.</p>`;
    console.error(err);
  }
}

function construireCarteLivreHtml(livre) {
  const prix = livre.prix_min ? `${Number(livre.prix_min).toLocaleString("fr-FR")} FCFA` : "Indisponible";
  const image = livre.image_couverture
    ? `<img src="${livre.image_couverture}" alt="${livre.titre}">`
    : `<span class="couverture-vide">Aucune couverture</span>`;

  return `
    <a href="livre.html?id=${livre.id}" class="carte carte-livre">
      <div class="couverture">${image}</div>
      <div class="infos">
        <div class="titre">${livre.titre}</div>
        <div class="auteur">${livre.auteur}</div>
        <div class="prix">${prix}</div>
      </div>
    </a>
  `;
}

document.getElementById("champ-recherche")?.addEventListener("keydown", (e) => {
  if (e.key === "Enter") {
    window.location.href = `catalogue.html?q=${encodeURIComponent(e.target.value)}`;
  }
});

chargerLivresAccueil();
