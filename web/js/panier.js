// =============================================================================
// RÔLE : page panier — affiche les lignes, permet modification/suppression,
// et le passage à la commande.
// =============================================================================

if (!estConnecte()) {
  window.location.href = "connexion.html";
}

async function chargerPanier() {
  const zone = document.getElementById("zone-panier");
  try {
    const donnees = await appelApi("/panier/panier.php");

    if (donnees.lignes.length === 0) {
      zone.innerHTML = `<div class="carte" style="padding:40px; text-align:center; color:var(--texte-clair);">
        Votre panier est vide. <a href="catalogue.html" style="color:var(--accent);">Parcourir le catalogue</a>
      </div>`;
      return;
    }

    const lignesHtml = donnees.lignes.map(ligne => `
      <div class="carte" style="padding:16px; display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
        <div>
          <div style="font-weight:700; font-size:14px;">${ligne.titre}</div>
          <div style="font-size:12px; color:var(--texte-clair);">${ligne.auteur} · Vendu par ${ligne.vendeur_nom}</div>
          <div style="margin-top:8px; display:flex; align-items:center; gap:8px;">
            <button onclick="modifierQuantite(${ligne.id}, ${ligne.quantite - 1})" ${ligne.quantite <= 1 ? "disabled" : ""}>−</button>
            <span>${ligne.quantite}</span>
            <button onclick="modifierQuantite(${ligne.id}, ${ligne.quantite + 1})">+</button>
            <button onclick="supprimerLigne(${ligne.id})" style="color:var(--danger); margin-left:14px; background:none; border:none; cursor:pointer; font-size:12px;">Supprimer</button>
          </div>
        </div>
        <div style="font-weight:800;">${(ligne.prix * ligne.quantite).toLocaleString("fr-FR")} FCFA</div>
      </div>
    `).join("");

    zone.innerHTML = `
      ${lignesHtml}
      <div class="carte" style="padding:16px; display:flex; justify-content:space-between; align-items:center; margin-top:16px;">
        <span style="font-weight:700;">Total</span>
        <span style="font-size:20px; font-weight:800;">${donnees.total.toLocaleString("fr-FR")} FCFA</span>
      </div>
      <button class="btn btn-primaire" style="width:100%; margin-top:16px;" onclick="passerCommande()">Passer la commande</button>
    `;
  } catch (err) {
    zone.innerHTML = `<p class="message-erreur">Erreur de chargement du panier.</p>`;
  }
}

async function modifierQuantite(ligneId, nouvelleQuantite) {
  if (nouvelleQuantite < 1) return;
  await appelApi("/panier/modifier.php", { methode: "POST", corps: { ligne_id: ligneId, quantite: nouvelleQuantite } });
  chargerPanier();
}

async function supprimerLigne(ligneId) {
  await appelApi(`/panier/supprimer.php?ligne_id=${ligneId}`, { methode: "POST" });
  chargerPanier();
}

async function passerCommande() {
  try {
    const resultat = await appelApi("/commandes/creer.php", { methode: "POST", corps: { mode_livraison: "domicile" } });
    window.location.href = `commande.html?commande_id=${resultat.commande_id}`;
  } catch (err) {
    alert("Erreur : " + err.message);
  }
}

chargerPanier();
