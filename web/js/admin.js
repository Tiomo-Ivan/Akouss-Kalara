if (!estConnecte() || !obtenirUtilisateurCourant()?.est_admin) {
  window.location.href = "index.html";
}

async function afficherOnglet(onglet) {
  document.getElementById("onglet-offres").className = onglet === "offres" ? "btn btn-primaire" : "btn btn-secondaire";
  document.getElementById("onglet-vendeurs").className = onglet === "vendeurs" ? "btn btn-primaire" : "btn btn-secondaire";

  if (onglet === "offres") await chargerOffresAModerer();
  else await chargerVendeurs();
}

async function chargerOffresAModerer() {
  const zone = document.getElementById("zone-contenu");
  const offres = await appelApi("/admin/offres_a_moderer.php?statut=en_attente");

  if (offres.length === 0) {
    zone.innerHTML = `<div class="carte" style="padding:30px; text-align:center; color:var(--texte-clair);">Aucune offre en attente.</div>`;
    return;
  }

  zone.innerHTML = offres.map(o => `
    <div class="carte" style="padding:16px; display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
      <div>
        <strong>${o.livre_titre}</strong><br>
        <span style="font-size:12px; color:var(--texte-clair);">Par ${o.vendeur_nom} · ${o.type} · ${Number(o.prix).toLocaleString("fr-FR")} FCFA</span>
        ${o.type === 'numerique' && o.statut_droits === 'droits_detenus' ? `<br><span style="font-size:11px; color:${o.justificatif_droits ? 'var(--succes)' : 'var(--danger)'};">${o.justificatif_droits ? '✓ Justificatif fourni' : '⚠ Justificatif manquant'}</span>` : ""}
      </div>
      <div style="display:flex; gap:8px;">
        <button class="btn btn-danger" onclick="moderer(${o.id}, 'refusee')">Refuser</button>
        <button class="btn btn-primaire" onclick="moderer(${o.id}, 'publiee')">Publier</button>
      </div>
    </div>
  `).join("");
}

async function moderer(id, decision) {
  try {
    await appelApi(`/admin/moderer_offre.php?id=${id}`, { methode: "POST", corps: { decision } });
    chargerOffresAModerer();
  } catch (err) {
    alert(err.message);
  }
}

async function chargerVendeurs() {
  const zone = document.getElementById("zone-contenu");
  const vendeurs = await appelApi("/admin/vendeurs.php");

  zone.innerHTML = `<table class="carte"><thead><tr><th>Boutique</th><th>Vendeur</th><th>Statut</th><th></th></tr></thead><tbody>` +
    vendeurs.map(v => `<tr>
      <td>${v.nom_boutique}</td>
      <td>${v.nom_complet} <span style="color:var(--texte-clair);">(${v.email})</span></td>
      <td><span class="badge badge-${v.statut === 'actif' ? 'publiee' : v.statut === 'suspendu' ? 'refusee' : 'attente'}">${v.statut}</span></td>
      <td>
        ${v.statut === 'actif' ? `<button onclick="suspendre(${v.id})" style="color:var(--danger); background:none; border:none; cursor:pointer; font-size:12px;">Suspendre</button>` : ""}
        ${v.statut === 'suspendu' ? `<button onclick="reactiver(${v.id})" style="color:var(--succes); background:none; border:none; cursor:pointer; font-size:12px;">Réactiver</button>` : ""}
      </td>
    </tr>`).join("") + `</tbody></table>`;
}

async function suspendre(id) {
  const motif = prompt("Motif de la suspension :");
  if (!motif) return;
  await appelApi(`/admin/suspendre_vendeur.php?id=${id}`, { methode: "POST", corps: { motif } });
  chargerVendeurs();
}

async function reactiver(id) {
  if (!confirm("Réactiver ce vendeur ?")) return;
  await appelApi(`/admin/reactiver_vendeur.php?id=${id}`, { methode: "POST" });
  chargerVendeurs();
}

afficherOnglet('offres');
