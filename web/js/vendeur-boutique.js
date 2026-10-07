// =============================================================================
// RÔLE : gestion des informations de la boutique du vendeur.
// =============================================================================

if (!estConnecte()) {
  window.location.href = "connexion.html";
}

function afficherMessage(type, message) {
  const zone = document.getElementById("zone-message");
  const classe = type === "succes" ? "message-succes" : "message-erreur";

  zone.innerHTML = `<div class="${classe}">${message}</div>`;
}

function remplirFormulaire(boutique) {
  document.getElementById("nom-boutique").value = boutique.nom_boutique || "";
  document.getElementById("description-boutique").value =
    boutique.description_boutique || "";
  document.getElementById("numero-mobile-money").value =
    boutique.numero_mobile_money || "";
  document.getElementById("operateur-mobile-money").value =
    boutique.operateur_mobile_money || "";
}

async function chargerBoutique() {
  try {
    const resultat = await appelApi("/vendeur/modifier_boutique.php");
    remplirFormulaire(resultat.boutique);
  } catch (err) {
    const messages = err.donnees?.erreurs
      ? Object.values(err.donnees.erreurs).join(" | ")
      : err.message;

    afficherMessage("erreur", messages);
  }
}

async function enregistrerBoutique() {
  const bouton = document.getElementById("bouton-enregistrer");

  const nomBoutique = document.getElementById("nom-boutique").value.trim();
  const description = document
    .getElementById("description-boutique")
    .value.trim();
  const numeroMobileMoney = document
    .getElementById("numero-mobile-money")
    .value.trim();
  const operateurMobileMoney = document.getElementById(
    "operateur-mobile-money"
  ).value;

  if (!nomBoutique) {
    afficherMessage("erreur", "Le nom de la boutique est requis.");
    return;
  }

  bouton.disabled = true;
  bouton.textContent = "Enregistrement...";

  try {
    const resultat = await appelApi("/vendeur/modifier_boutique.php", {
      methode: "POST",
      corps: {
        nom_boutique: nomBoutique,
        description_boutique: description,
        numero_mobile_money: numeroMobileMoney,
        operateur_mobile_money: operateurMobileMoney
      }
    });

    remplirFormulaire(resultat.boutique);
    afficherMessage(
      "succes",
      resultat.message || "Les informations de la boutique ont été mises à jour."
    );
  } catch (err) {
    const messages = err.donnees?.erreurs
      ? Object.values(err.donnees.erreurs).join(" | ")
      : err.message;

    afficherMessage("erreur", messages);
  } finally {
    bouton.disabled = false;
    bouton.textContent = "Enregistrer les modifications";
  }
}

document
  .getElementById("bouton-enregistrer")
  .addEventListener("click", enregistrerBoutique);

chargerBoutique();
