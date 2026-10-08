/* User-generated text (slot reviews, post comments) → visitor's browser language.
   Google first, /translate as fallback; originals are kept in data-original. */
(() => {
  const browserLang = (navigator.language || navigator.userLanguage || "en").toLowerCase().slice(0, 2);
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";
  let queue = Promise.resolve();

  const cacheKeyFor = (text, lang) => `slot-gt:${lang}:${text}`;

  const viaGoogle = async (text, lang) => {
    const key = cacheKeyFor(text, lang);
    try {
      const cached = sessionStorage.getItem(key);
      if (cached) return cached;
    } catch (_) {
      /* private mode */
    }

    const url =
      "https://translate.googleapis.com/translate_a/single?client=gtx&sl=auto&tl=" +
      encodeURIComponent(lang) +
      "&dt=t&q=" +
      encodeURIComponent(text);
    const res = await fetch(url);
    if (!res.ok) throw new Error("translate failed");
    const data = await res.json();
    const translated = Array.isArray(data?.[0])
      ? data[0]
          .map((part) => (Array.isArray(part) ? part[0] : ""))
          .join("")
          .trim()
      : "";
    if (!translated) return text;
    try {
      sessionStorage.setItem(key, translated);
    } catch (_) {
      /* ignore quota */
    }
    return translated;
  };

  const viaServer = async (texts, lang, serverUrl) => {
    if (!serverUrl) return null;
    const res = await fetch(serverUrl, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
        "X-CSRF-TOKEN": csrf,
        "X-Requested-With": "XMLHttpRequest",
      },
      body: JSON.stringify({ target: lang, texts }),
    });
    if (!res.ok) return null;
    const data = await res.json();
    return Array.isArray(data.translations) ? data.translations : null;
  };

  const translateNodes = (nodes, { serverUrl = "/translate" } = {}) => {
    if (!browserLang) return;
    const targets = Array.from(nodes).filter((node) => {
      const original = (node.dataset.original || node.textContent || "").trim();
      if (!original) return false;
      if (!node.dataset.original) node.dataset.original = original;
      return node.dataset.translatedLang !== browserLang;
    });

    if (!targets.length) return;

    // Texts are authored in English; restore original when browser is EN.
    if (browserLang === "en") {
      targets.forEach((node) => {
        node.textContent = node.dataset.original || node.textContent;
        node.dataset.translatedLang = "en";
      });
      return;
    }

    queue = queue.then(async () => {
      const pending = targets.filter((node) => node.dataset.translatedLang !== browserLang);
      for (const node of pending) {
        const original = node.dataset.original || node.textContent.trim();
        try {
          node.textContent = await viaGoogle(original, browserLang);
        } catch (_) {
          try {
            const batch = await viaServer([original], browserLang, serverUrl);
            if (batch && typeof batch[0] === "string" && batch[0].trim() !== "") {
              node.textContent = batch[0];
            }
          } catch (_) {
            /* keep original */
          }
        }
        node.dataset.translatedLang = browserLang;
      }
    });
  };

  window.UgcTranslate = { browserLang, translateNodes };
})();
