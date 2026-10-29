// Les parcours restent utilisables sans JavaScript.

// Le résumé possède tabindex=-1 dans Twig : le focus annonce les erreurs
// et permet de rejoindre les champs. L’opérateur ?. tolère une page sans résumé.
document.querySelector("[data-focus-errors]")?.focus();

// Amélioration progressive des formulaires : le POST HTML reste natif.
// Les attributs data-* font le lien avec clients/_form.html.twig.
for (const form of document.querySelectorAll("[data-client-form]")) {
    const button = form.querySelector("[data-submit-label]");
    const status = form.querySelector("[data-submit-status]");

    if (!button || !status) {
        continue;
    }

    const originalLabel = button.textContent;

    // Indiquer l’attente et limiter les doubles clics sans intercepter la navigation.
    // Le contrôle CSRF et la validation restent obligatoires côté serveur.
    form.addEventListener("submit", () => {
        form.setAttribute("aria-busy", "true");

        button.disabled = true;
        button.textContent = "Vérification en cours…";

        status.textContent =
            "Vérification des informations en cours.";
    });

    // pageshow se produit aussi au retour arrière depuis le cache du navigateur :
    // réactiver le bouton et effacer le message de la précédente soumission.
    window.addEventListener("pageshow", () => {
        form.removeAttribute("aria-busy");

        button.disabled = false;
        button.textContent = originalLabel;

        status.textContent = "";
    });
}

// Recherche sur la liste uniquement : les autres pages ne possèdent pas ces éléments.
const searchForm = document.querySelector("#recherche-clients");
const searchInput = document.querySelector("#recherche");
const results = document.querySelector("#resultats-clients");
const searchStatus = document.querySelector("#statut-recherche");

if (searchForm && searchInput && results && searchStatus) {
    // timer regroupe les frappes ; activeRequest annule le fetch précédent ;
    // version empêche une réponse obsolète de gagner une course réseau.
    let timer;
    let activeRequest;
    let version = 0;

    /**
     * Demande la page GET /clients?q=... et remplace uniquement ses résultats.
     * Le serveur applique le même filtre que pour une navigation sans JavaScript.
     * @returns {Promise<void>} Se termine aussi en cas d’annulation ou d’erreur affichée.
     */
    async function searchClients() {
        activeRequest?.abort();

        // Photographier la version avant les await pour identifier une réponse périmée.
        const requestVersion = ++version;
        const controller = new AbortController();

        activeRequest = controller;
        searchStatus.classList.add("visually-hidden");
        searchStatus.classList.remove("notice", "notice-error");

        // URLSearchParams encode le texte ; ne pas concaténer la saisie à une URL brute.
        const url = new URL(searchForm.action, window.location.href);
        const query = searchInput.value.trim();

        if (query !== "") {
            url.searchParams.set("q", query);
        }

        results.setAttribute("aria-busy", "true");
        searchStatus.textContent = "Recherche en cours.";

        try {
            // Conserver la session du site ; AbortController permet l’annulation
            // lors d’une nouvelle frappe. Un statut HTTP d’échec doit être testé explicitement.
            const response = await fetch(url, {
                method: "GET",
                signal: controller.signal,
                credentials: "same-origin",
            });

            if (!response.ok) {
                throw new Error("Recherche indisponible.");
            }

            const html = await response.text();

            // Une réponse ancienne ne doit pas remplacer la plus récente.
            if (requestVersion !== version) {
                return;
            }

            // Extraire le fragment HTML produit par Twig (échappement côté serveur).
            // Ne pas construire de HTML à partir de la saisie brute. Une page de login
            // après expiration de session ne contient pas ce fragment et sera refusée.
            const documentResult = new DOMParser().parseFromString(
                html,
                "text/html"
            );

            const nextResults = documentResult.querySelector(
                "#resultats-clients"
            );

            if (!nextResults) {
                throw new Error("Résultats absents de la réponse.");
            }

            // Préserver le formulaire actif et son focus en remplaçant seulement la liste.
            results.replaceChildren(...nextResults.childNodes);

            // Actualiser l'adresse sans recharger la page.
            window.history.replaceState(null, "", url);

            // Annonce textuelle du nombre de résultats dans la zone aria-live,
            // sans déplacer le focus pendant la frappe.
            const heading = results.querySelector("#resultats");

            searchStatus.textContent = heading
                ? heading.textContent.replace(/\s+/g, " ").trim()
                : "Résultats actualisés.";
        // Une annulation attendue reste silencieuse ; un échec réel est visible
        // et conserve les anciens résultats pour que la page reste utilisable.
        } catch (error) {
            if (
                error.name === "AbortError"
                || requestVersion !== version
            ) {
                return;
            }

            searchStatus.textContent =
                "La recherche est indisponible. Les résultats précédents restent affichés. Réessayez dans un instant.";
            searchStatus.classList.remove("visually-hidden");
            searchStatus.classList.add("notice", "notice-error");
        // Seule la requête courante peut retirer l’indicateur d’attente.
        } finally {
            if (requestVersion === version) {
                results.removeAttribute("aria-busy");
            }
        }
    }

    /** Annule le délai et le fetch, invalide leurs réponses et remet l’état visuel à zéro. */
    function cancelPendingSearch() {
        window.clearTimeout(timer);

        // Annuler immédiatement la recherche précédente.
        activeRequest?.abort();
        version++;

        results.removeAttribute("aria-busy");
        searchStatus.textContent = "";
        searchStatus.classList.add("visually-hidden");
        searchStatus.classList.remove("notice", "notice-error");
    }

    /** Regroupe les frappes successives avec un délai de 300 ms (debounce). */
    function scheduleSearch() {
        cancelPendingSearch();

        // Attendre une courte pause avant d'interroger le serveur.
        timer = window.setTimeout(searchClients, 300);
    }

    // Pendant une composition IME, attendre la validation du caractère complet.
    searchInput.addEventListener("input", (event) => {
        if (!event.isComposing) {
            scheduleSearch();
        }
    });

    // Respecter les méthodes de saisie utilisant une composition.
    searchInput.addEventListener("compositionstart", cancelPendingSearch);
    searchInput.addEventListener("compositionend", scheduleSearch);

    // Entrée ou clic sur Rechercher : recherche immédiate, sans attendre les 300 ms.
    // Sans ce script, le même formulaire continue de fonctionner par navigation GET.
    searchForm.addEventListener("submit", (event) => {
        event.preventDefault();

        window.clearTimeout(timer);
        searchClients();
    });
}
