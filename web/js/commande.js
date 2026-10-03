// =============================================================================
// RÔLE : page de paiement — choix MTN/Orange, saisie du numéro, initiation
// du paiement, puis attente de confirmation (polling du statut).
// =============================================================================

if (!estConnecte()) window.location.href = "connexion.html";

const commandeId = new URLSearchParams(window.location.search).get("commande_id");
let intervalleVerification = null;

function afficherFormulairePaiement() {
  document.getElementById("zone-commande").innerHTML = `
    <div class="carte" style="padding:24px;">
      <h1 style="font-size:20px; margin-bottom:16px;">Choisissez votre moyen de paiement</h1>
      <div id="zone-message"></div>

      <div class="champ-groupe">
        <label class="champ-label">
          <input type="radio" name="fournisseur" value="mtn_momo" checked> MTN Mobile Money
        </label>
      </div>
      <div class="champ-groupe">
        <label class="champ-label">
          <input type="radio" name="fournisseur" value="orange_money"> Orange Money
        </label>
      </div>
      <div class="champ-groupe">
        <label class="champ-label">Numéro de téléphone</label>
        <input class="champ" id="champ-telephone" placeholder="6XXXXXXXX" required>
      </div>

      <button class="btn btn-primaire" style="width:100%;" onclick="lancerPaiement()">Payer maintenant</button>
    </div>
  `;
}

async function lancerPaiement() {
  const fournisseur = document.querySelector('input[name="fournisseur"]:checked').value;
  const telephone = document.getElementById("champ-telephone").value.trim();
  const zoneMessage = document.getElementById("zone-message");

  if (!telephone) {
    zoneMessage.innerHTML = `<div class="message-erreur">Numéro de téléphone requis.</div>`;
    return;
  }

  zoneMessage.innerHTML = `<p style="color:var(--texte-clair);">Initiation du paiement...</p>`;

  try {
    const resultat = await appelApi("/paiement/initier.php", {
      methode: "POST",
      corps: { commande_id: commandeId, fournisseur, telephone },
    });

    if (resultat.url_paiement) {
      // Orange Money : redirection vers la page de paiement Orange
      window.location.href = resultat.url_paiement;
      return;
    }

    // MTN MoMo : le client confirme via son téléphone, on attend en interrogeant le statut
    afficherAttenteConfirmation();
    intervalleVerification = setInterval(verifierStatutPaiement, 4000);

  } catch (err) {
    zoneMessage.innerHTML = `<div class="message-erreur">${err.message}</div>`;
  }
}

function afficherAttenteConfirmation() {
  document.getElementById("zone-commande").innerHTML = `
    <div class="carte centre" style="padding:32px;">
      <div style="font-size:40px;">⏳</div>
      <h2 style="margin:12px 0 8px;">Confirmez sur votre téléphone</h2>
      <p style="color:var(--texte-clair); font-size:13.5px;">
        Une notification de paiement a été envoyée à votre téléphone. Confirmez-la pour finaliser votre commande.
      </p>
    </div>
  `;
}

async function verifierStatutPaiement() {
  try {
    const resultat = await appelApi(`/paiement/statut.php?commande_id=${commandeId}`);
    if (resultat.statut === "succes") {
      clearInterval(intervalleVerification);
      afficherResultat(true);
    } else if (resultat.statut === "echec") {
      clearInterval(intervalleVerification);
      afficherResultat(false);
    }
    // si "en_attente", on continue simplement d'attendre
  } catch (err) {
    clearInterval(intervalleVerification);
  }
}

function afficherResultat(succes) {
  document.getElementById("zone-commande").innerHTML = succes ? `
    <div class="carte centre" style="padding:32px;">
      <div class="message-icone succes">Succès</div>
      <h2 style="margin:12px 0 8px;">Paiement réussi !</h2>
      <p style="color:var(--texte-clair); font-size:13.5px;">Votre commande #${commandeId} a été confirmée.</p>
      <a href="mes-commandes.html" class="btn btn-primaire espace-haut" style="display:inline-flex;">Voir mes commandes</a>
    </div>
  ` : `
    <div class="carte centre" style="padding:32px;">
      <div class="message-icone erreur">Échec</div>
      <h2 style="margin:12px 0 8px;">Paiement échoué</h2>
      <p style="color:var(--texte-clair); font-size:13.5px;">Le paiement n'a pas abouti.</p>
      <button class="btn btn-primaire espace-haut" onclick="afficherFormulairePaiement()">Réessayer</button>
    </div>
  `;
}

// Si on revient d'une redirection Orange Money (succès/échec via callback), vérifie directement
const statutRetour = new URLSearchParams(window.location.search).get("statut");
if (statutRetour) {
  verifierStatutPaiement();
} else {
  afficherFormulairePaiement();
}
