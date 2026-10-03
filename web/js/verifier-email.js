async function verifier() {
  const jeton = new URLSearchParams(window.location.search).get("jeton");
  const zone = document.getElementById("contenu-verification");

  if (!jeton) {
    zone.innerHTML = `<p class="message-erreur">Lien invalide.</p>`;
    return;
  }

  try {
    const resultat = await appelApi(`/auth/verifier_email.php?jeton=${jeton}`);
    zone.innerHTML = `
      <div class="message-icone succes">Succès</div>
      <h2 style="margin:12px 0 8px;">${resultat.message}</h2>
      <a href="connexion.html" class="btn btn-primaire espace-haut" style="display:inline-flex;">Se connecter</a>
    `;
  } catch (err) {
    zone.innerHTML = `<div class="message-icone erreur">Erreur</div><h2 style="margin:12px 0 8px;">Lien invalide</h2><p style="color:var(--texte-clair);">${err.message}</p>`;
  }
}
verifier();
