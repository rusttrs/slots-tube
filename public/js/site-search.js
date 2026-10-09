(() => {
  const root = document.getElementById("site-search");
  if (!root) return;

  const input = root.querySelector(".site-search__input");
  const form = root.querySelector(".site-search__form");
  const results = root.querySelector("[data-search-results]");
  const tags = Array.from(root.querySelectorAll("[data-search-type]"));
  const endpoint = root.getAttribute("data-endpoint");
  const MIN_QUERY = 2;

  let type = "all";
  let lastKey = null;
  let timer = null;
  let controller = null;
  let closeTimer = null;
  let returnFocus = null;

  const isOpen = () => !root.hidden && document.body.classList.contains("search-open");

  const setTriggersState = (open) => {
    document.querySelectorAll(".js-open-search").forEach((trigger) => {
      trigger.setAttribute("aria-expanded", open ? "true" : "false");
      if (trigger.classList.contains("bottom-nav__link")) {
        trigger.classList.toggle("is-active", open);
      }
    });
  };

  const load = () => {
    const query = input.value.trim();
    const effective = query.length >= MIN_QUERY ? query : "";
    const key = `${type}|${effective}`;
    if (key === lastKey) return;
    lastKey = key;

    if (controller) controller.abort();
    controller = new AbortController();

    const url = new URL(endpoint, window.location.origin);
    if (effective) url.searchParams.set("q", effective);
    if (type !== "all") url.searchParams.set("type", type);

    root.classList.add("is-loading");
    fetch(url.toString(), {
      headers: { Accept: "text/html", "X-Requested-With": "XMLHttpRequest" },
      credentials: "same-origin",
      signal: controller.signal,
    })
      .then((response) => (response.ok ? response.text() : Promise.reject(response)))
      .then((html) => {
        results.innerHTML = html;
        root.classList.remove("is-loading");
      })
      .catch((error) => {
        if (error && error.name === "AbortError") return;
        lastKey = null;
        root.classList.remove("is-loading");
      });
  };

  const scheduleLoad = () => {
    window.clearTimeout(timer);
    timer = window.setTimeout(load, 250);
  };

  const open = (trigger, initialQuery = "") => {
    window.clearTimeout(closeTimer);
    if (isOpen()) {
      input.focus();
      return;
    }

    returnFocus = trigger && trigger.tagName === "BUTTON" ? trigger : null;

    const drawerClose = document.getElementById("drawer-close");
    const drawer = document.getElementById("mobile-drawer");
    if (drawerClose && drawer && drawer.classList.contains("is-open")) drawerClose.click();

    if (initialQuery) input.value = initialQuery;
    root.hidden = false;
    document.body.classList.add("search-open");
    setTriggersState(true);
    input.focus({ preventScroll: true });
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => root.classList.add("is-open"));
    });
    load();
  };

  const close = () => {
    if (root.hidden) return;
    root.classList.remove("is-open");
    document.body.classList.remove("search-open");
    setTriggersState(false);
    window.clearTimeout(closeTimer);
    closeTimer = window.setTimeout(() => {
      root.hidden = true;
    }, 200);
    if (returnFocus) returnFocus.focus({ preventScroll: true });
    returnFocus = null;
  };

  document.querySelectorAll(".js-open-search").forEach((trigger) => {
    if (trigger.tagName === "FORM") {
      const field = trigger.querySelector("input");
      const openFromField = (event) => {
        event.preventDefault();
        const typed = field ? field.value.trim() : "";
        if (field) {
          field.value = "";
          field.blur();
        }
        open(trigger, typed);
      };
      trigger.addEventListener("submit", openFromField);
      if (field) {
        field.addEventListener("focus", openFromField);
        field.addEventListener("click", openFromField);
      }
      return;
    }

    trigger.addEventListener("click", (event) => {
      event.preventDefault();
      if (isOpen()) close();
      else open(trigger);
    });
  });

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    window.clearTimeout(timer);
    load();
  });

  input.addEventListener("input", scheduleLoad);

  tags.forEach((tag) => {
    tag.addEventListener("click", () => {
      type = tag.getAttribute("data-search-type") || "all";
      tags.forEach((other) => {
        const active = other === tag;
        other.classList.toggle("is-active", active);
        other.setAttribute("aria-pressed", active ? "true" : "false");
      });
      window.clearTimeout(timer);
      load();
      input.focus({ preventScroll: true });
    });
  });

  root.querySelectorAll("[data-search-close]").forEach((el) => {
    el.addEventListener("click", close);
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape" && !root.hidden) close();
  });
})();
