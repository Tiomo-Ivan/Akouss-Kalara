// =============================================================================
// RÔLE : page détail d'un livre — affiche les offres et permet l'ajout au panier.
// =============================================================================

const idLivre = new URLSearchParams(window.location.search).get("id");

async function chargerDetailLivre() {
  const zone = document.getElementById("contenu-livre");

  if (!idLivre) {
    zone.innerHTML = `<p class="message-erreur">Identifiant du livre manquant.</p>`;
    return;
  }

  try {
    const livre = await appelApi(`/catalogue/livre_detail.php?id=${idLivre}`);
    const offres = livre.offres || [];

    zone.innerHTML = `
      <div style="display:flex; gap:36px; align-items:flex-start; flex-wrap:wrap;">
        <div style="width:280px; flex-shrink:0; aspect-ratio:3/4; background:#f0f2f5; border-radius:10px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
          ${
            livre.image_couverture
              ? `<img src="${livre.image_couverture}" alt="${livre.titre}" style="width:100%;height:100%;object-fit:cover;">`
              : `<span class="couverture-vide">Aucune couverture</span>`
          }
        </div>

        <div style="flex:1; min-width:280px;">
          <h1 style="font-size:24px;">${livre.titre}</h1>

          <p style="color:var(--texte-clair); margin:4px 0 12px;">
            ${livre.auteur}
          </p>

          <span class="badge badge-publiee">
            ${livre.categorie_nom}
          </span>

          ${
            livre.description
              ? `<p style="margin-top:16px; line-height:1.6;">${livre.description}</p>`
              : ""
          }

          <div id="zone-offres" style="margin-top:24px;"></div>
        </div>
      </div>
    `;

    const zoneOffres = document.getElementById("zone-offres");

    if (offres.length === 0) {
      zoneOffres.innerHTML = `
        <div class="carte" style="padding:20px; color:var(--texte-clair);">
          Aucune offre disponible.
        </div>
      `;
      return;
    }

    zoneOffres.innerHTML = `
      <h2 style="font-size:18px; margin-bottom:12px;">
        Offres disponibles
      </h2>

      ${offres.map(construireOffreHtml).join("")}
    `;
  } catch (err) {
    zone.innerHTML = `
      <p class="message-erreur">
        Ce livre est introuvable.
      </p>
    `;
    console.error(err);
  }
}

function construireOffreHtml(offre) {
  const prix = Number(offre.prix).toLocaleString("fr-FR");
  const estPhysique = offre.type === "physique";
  const stock = Number(offre.quantite_stock || 0);
  const stockDisponible = !estPhysique || stock > 0;

  const type = estPhysique ? "Physique" : "Numérique";

  const etat = estPhysique && offre.etat_article
    ? `
      <span class="badge" style="background:#f0f2f5; color:var(--texte);">
        ${offre.etat_article === "neuf" ? "Neuf" : "Occasion"}
      </span>
    `
    : "";

  const stockHtml = estPhysique
    ? `
      <span style="font-size:13px; color:${stockDisponible ? "var(--succes)" : "var(--danger)"};">
        ${stockDisponible ? `${stock} disponible${stock > 1 ? "s" : ""}` : "Rupture de stock"}
      </span>
    `
    : `
      <span style="font-size:13px; color:var(--succes);">
        Disponible immédiatement
      </span>
    `;

  return `
    <div class="carte" style="padding:18px; margin-bottom:10px;">
      <div style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px; flex-wrap:wrap;">
        <span style="font-size:22px; font-weight:800; color:var(--marine);">
          ${prix} FCFA
        </span>

        <span class="badge badge-publiee">
          ${type}
        </span>
      </div>

      <p style="font-size:13px; color:var(--texte-clair); margin:8px 0;">
        Vendu par ${offre.vendeur_nom}
      </p>

      <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:14px;">
        ${etat}
        ${stockHtml}
      </div>

      <button
        class="btn btn-primaire"
        style="width:100%;"
        ${stockDisponible ? "" : "disabled"}
        onclick="ajouterAuPanier(${offre.id})"
      >
        ${stockDisponible ? "Ajouter au panier" : "Rupture de stock"}
      </button>
    </div>
  `;
}

async function ajouterAuPanier(offreId) {
  if (!estConnecte()) {
    alert("Connectez-vous pour ajouter un article au panier.");
    window.location.href = "connexion.html";
    return;
  }

  try {
    await appelApi("/panier/ajouter.php", {
      methode: "POST",
      corps: {
        offre_id: offreId,
        quantite: 1,
      },
    });

    alert("Ajouté au panier !");
  } catch (err) {
    alert("Erreur : " + err.message);
  }
}

chargerDetailLivre();
