document.getElementById("case-vendeur").addEventListener("change", (e) => {
  document.getElementById("zone-boutique").style.display = e.target.checked ? "block" : "none";
});

document.getElementById("form-inscription").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const zoneMessage = document.getElementById("zone-message");
  zoneMessage.innerHTML = "";

  const donnees = {
    nom_complet: form.nom_complet.value,
    email: form.email.value,
    telephone: form.telephone.value,
    mot_de_passe: form.mot_de_passe.value,
    devenir_vendeur: document.getElementById("case-vendeur").checked,
    nom_boutique: form.nom_boutique.value,
  };

  try {
    const resultat = await appelApi("/auth/inscription.php", { methode: "POST", corps: donnees });

    document.getElementById("carte-inscription").innerHTML = `
      <div class="centre">
        <div style="font-size:40px;">✅</div>
        <h2 style="margin:12px 0 8px;">Vérifiez votre boîte mail</h2>
        <p style="color:var(--texte-clair); font-size:13.5px;">Un lien de confirmation a été envoyé à ${donnees.email}.</p>
        ${resultat.lien_activation_dev ? `<p style="font-size:11px; margin-top:10px;">Mode développement — <a href="${resultat.lien_activation_dev}" style="color:var(--accent);">cliquer ici pour activer directement</a></p>` : ""}
        <a href="connexion.html" class="btn btn-primaire espace-haut" style="display:inline-flex;">Aller à la connexion</a>
      </div>
    `;
  } catch (err) {
    const messages = err.donnees?.erreurs
      ? Object.values(err.donnees.erreurs).join(" | ")
      : err.message;
    zoneMessage.innerHTML = `<div class="message-erreur">${messages}</div>`;
  }
});
