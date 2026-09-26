document.getElementById("form-connexion").addEventListener("submit", async (e) => {
  e.preventDefault();
  const form = e.target;
  const zoneMessage = document.getElementById("zone-message");
  zoneMessage.innerHTML = "";

  try {
    const resultat = await appelApi("/auth/connexion.php", {
      methode: "POST",
      corps: { email: form.email.value, mot_de_passe: form.mot_de_passe.value },
    });
    connecterLocalement(resultat.jeton, resultat.utilisateur);
    window.location.href = "index.html";
  } catch (err) {
    zoneMessage.innerHTML = `<div class="message-erreur">${err.message}</div>`;
  }
});
