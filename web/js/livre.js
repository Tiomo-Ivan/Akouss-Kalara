// =============================================================================
// RÔLE : page détail d'un livre — affiche les offres, permet l'ajout au panier.
// =============================================================================

const idLivre = new URLSearchParams(window.location.search).get("id");

async function chargerDetailLivre() {
  const zone = document.getElementById("contenu-livre");
  try {
    const livre = await appelApi(`/catalogue/livre_detail.php?id=${idLivre}`);
    const offres = livre.offres || [];

    zone.innerHTML = `
      <div style="display:flex; gap:36px; align-items:flex-start;">
        <div style="width:280px; flex-shrink:0; aspect-ratio:3/4; background:#f0f2f5; border-radius:10px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
          ${livre.image_couverture ? `<img src="${livre.image_couverture}" style="width:100%;height:100%;object-fit:cover;">` : `<span style="font-size:56px;">📖</span>`}
        </div>
        <div style="flex:1;">
          <h1 style="font-size:24px;">${livre.titre}</h1>
          <p style="color:var(--texte-clair); margin:4px 0 12px;">${livre.auteur}</p>
          <span class="badge badge-publiee">${livre.categorie_nom}</span>
          ${livre.description ? `<p style="margin-top:16px; line-height:1.6;">${livre.description}</p>` : ""}
          <div id="zone-offres" style="margin-top:24px;"></div>
        </div>
      </div>
    `;

    const zoneOffres = document.getElementById("zone-offres");
    if (offres.length === 0) {
      zoneOffres.innerHTML = `<div class="carte" style="padding:20px; color:var(--texte-clair);">Aucune offre disponible.</div>`;
      return;
    }

    zoneOffres.innerHTML = offres.map((offre, index) => `
      <div class="carte" style="padding:18px; margin-bottom:10px;">
        <div style="display:flex; justify-content:space-between; align-items:baseline;">
          <span style="font-size:22px; font-weight:800; color:var(--marine);">${Number(offre.prix).toLocaleString("fr-FR")} FCFA</span>
          <span style="font-size:12px; color:var(--texte-clair);">${offre.type === "physique" ? "Physique" : "Numérique"}</span>
        </div>
        <p style="font-size:13px; color:var(--texte-clair); margin:8px 0;">Vendu par ${offre.vendeur_nom}</p>
        <button class="btn btn-primaire" style="width:100%;" onclick="ajouterAuPanier(${offre.id})">Ajouter au panier</button>
      </div>
    `).join("");

  } catch (err) {
    zone.innerHTML = `<p class="message-erreur">Ce livre est introuvable.</p>`;
  }
}

async function ajouterAuPanier(offreId) {
  if (!estConnecte()) {
    alert("Connectez-vous pour ajouter un article au panier.");
    window.location.href = "connexion.html";
    return;
  }
  try {
    await appelApi("/panier/ajouter.php", { methode: "POST", corps: { offre_id: offreId, quantite: 1 } });
    alert("Ajouté au panier !");
  } catch (err) {
    alert("Erreur : " + err.message);
  }
}

chargerDetailLivre();
