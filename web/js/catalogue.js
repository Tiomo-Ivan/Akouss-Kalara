// =============================================================================
// RÔLE : page catalogue — recherche, filtre par catégorie.
// =============================================================================

const parametresUrl = new URLSearchParams(window.location.search);
let categorieActive = parametresUrl.get("categorie") || "";
let rechercheActive = parametresUrl.get("q") || "";

document.getElementById("champ-recherche").value = rechercheActive;

async function chargerCategories() {
  const categories = await appelApi("/catalogue/categories.php");
  const zone = document.getElementById("liste-categories");
  zone.innerHTML = `<a href="#" data-slug="" style="font-weight:${categorieActive === "" ? "700" : "400"};">Toutes</a>` +
    categories.map(c => `<a href="#" data-slug="${c.slug}" style="font-weight:${categorieActive === c.slug ? "700" : "400"};">${c.nom}</a>`).join("");

  zone.querySelectorAll("a").forEach(lien => {
    lien.addEventListener("click", (e) => {
      e.preventDefault();
      categorieActive = lien.dataset.slug;
      chargerLivres();
      chargerCategories();
    });
  });
}

async function chargerLivres() {
  const zone = document.getElementById("grille-livres");
  try {
    const params = new URLSearchParams();
    if (rechercheActive) params.set("q", rechercheActive);
    if (categorieActive) params.set("categorie", categorieActive);

    const livres = await appelApi(`/catalogue/livres.php?${params}`);
    zone.innerHTML = livres.length
      ? livres.map(construireCarteLivreHtml).join("")
      : `<p style="color:var(--texte-clair);">Aucun résultat.</p>`;
  } catch (err) {
    zone.innerHTML = `<p class="message-erreur">Erreur de chargement.</p>`;
  }
}

function construireCarteLivreHtml(livre) {
  const prix = livre.prix_min ? `${Number(livre.prix_min).toLocaleString("fr-FR")} FCFA` : "Indisponible";
  const image = livre.image_couverture ? `<img src="${livre.image_couverture}" alt="${livre.titre}">` : `<span style="font-size:32px;">📖</span>`;
  return `
    <a href="livre.html?id=${livre.id}" class="carte carte-livre">
      <div class="couverture">${image}</div>
      <div class="infos">
        <div class="titre">${livre.titre}</div>
        <div class="auteur">${livre.auteur}</div>
        <div class="prix">${prix}</div>
      </div>
    </a>`;
}

document.getElementById("champ-recherche").addEventListener("keydown", (e) => {
  if (e.key === "Enter") {
    rechercheActive = e.target.value;
    chargerLivres();
  }
});

chargerCategories();
chargerLivres();
