// Les parcours restent utilisables sans JavaScript.

document.querySelector("[data-focus-errors]")?.focus();

for (const form of document.querySelectorAll("[data-client-form]")) {
    const button = form.querySelector("[data-submit-label]");
    const status = form.querySelector("[data-submit-status]");

    if (!button || !status) {
        continue;
    }

    const originalLabel = button.textContent;

    form.addEventListener("submit", () => {
        form.setAttribute("aria-busy", "true");

        button.disabled = true;
        button.textContent = "Vérification en cours…";

        status.textContent =
            "Vérification des informations en cours.";
    });

    window.addEventListener("pageshow", () => {
        form.removeAttribute("aria-busy");

        button.disabled = false;
        button.textContent = originalLabel;

        status.textContent = "";
    });
}

const searchForm = document.querySelector("#recherche-clients");
const searchInput = document.querySelector("#recherche");
const results = document.querySelector("#resultats-clients");
const searchStatus = document.querySelector("#statut-recherche");

if (searchForm && searchInput && results && searchStatus) {
    let timer;
    let activeRequest;
    let version = 0;

    async function searchClients() {
        activeRequest?.abort();

        const requestVersion = ++version;
        const controller = new AbortController();

        activeRequest = controller;

        const url = new URL(searchForm.action, window.location.href);
        const query = searchInput.value.trim();

        if (query !== "") {
            url.searchParams.set("q", query);
        }

        results.setAttribute("aria-busy", "true");
        searchStatus.textContent = "Recherche en cours.";

        try {
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

            results.replaceChildren(...nextResults.childNodes);

            // Actualiser l'adresse sans recharger la page.
            window.history.replaceState(null, "", url);

            const heading = results.querySelector("#resultats");

            searchStatus.textContent = heading
                ? heading.textContent.replace(/\s+/g, " ").trim()
                : "Résultats actualisés.";
        } catch (error) {
            if (
                error.name === "AbortError"
                || requestVersion !== version
            ) {
                return;
            }

            searchStatus.textContent =
                "La recherche est indisponible. Réessayez dans un instant.";
        } finally {
            if (requestVersion === version) {
                results.removeAttribute("aria-busy");
            }
        }
    }

    function scheduleSearch() {
        window.clearTimeout(timer);

        // Annuler immédiatement la recherche précédente.
        activeRequest?.abort();
        version++;

        results.removeAttribute("aria-busy");
        searchStatus.textContent = "";

        // Attendre une courte pause avant d'interroger le serveur.
        timer = window.setTimeout(searchClients, 300);
    }

    searchInput.addEventListener("input", (event) => {
        if (!event.isComposing) {
            scheduleSearch();
        }
    });

    // Respecter les méthodes de saisie utilisant une composition.
    searchInput.addEventListener("compositionend", scheduleSearch);

    searchForm.addEventListener("submit", (event) => {
        event.preventDefault();

        window.clearTimeout(timer);
        searchClients();
    });
}