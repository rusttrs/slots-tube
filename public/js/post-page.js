(() => {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

  const likedLabel = (name, count) => {
    const root = document.querySelector("[data-liked-template]");
    if (!root || !name || count < 1) return "";
    const one = root.dataset.likedOne || "liked by :name";
    const many = root.dataset.likedMany || "liked by :name and :count others";
    if (count === 1) return one.replace(":name", name);
    return many.replace(":name", name).replace(":count", String(count - 1));
  };

  document.querySelectorAll(".js-like-post, .js-like-comment").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const url = btn.dataset.likeUrl;
      if (!url || btn.dataset.busy === "1") return;
      btn.dataset.busy = "1";
      try {
        const res = await fetch(url, {
          method: "POST",
          headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": csrf,
            "X-Requested-With": "XMLHttpRequest",
          },
          credentials: "same-origin",
        });
        if (res.status === 401 || res.status === 419) {
          document.querySelector(".js-open-auth")?.click();
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) return;
        btn.classList.toggle("is-liked", Boolean(data.liked));
        const countEl = btn.querySelector(".js-like-count");
        if (countEl && typeof data.count === "number") {
          countEl.textContent = String(data.count);
        }
        const likesLabel = btn.closest(".topic-comment")?.querySelector(".js-likes-label");
        if (likesLabel && data.label) {
          likesLabel.textContent = data.label;
          if (typeof data.count === "number") likesLabel.dataset.count = String(data.count);
        }
        if (btn.classList.contains("js-like-post") && typeof data.count === "number") {
          const row = document.querySelector("[data-liked-row]");
          const label = document.querySelector("[data-liked-label]");
          if (row && label) {
            if (data.count < 1) {
              row.hidden = true;
            } else {
              row.hidden = false;
              label.textContent = data.label || likedLabel(data.name, data.count);
            }
          }
        }
      } catch (_) {
        /* ignore */
      } finally {
        btn.dataset.busy = "0";
      }
    });
  });

  document.querySelectorAll(".js-reply").forEach((btn) => {
    btn.addEventListener("click", () => {
      const block = btn.closest(".topic-comment-block");
      const thread = block?.querySelector(".topic-thread");
      if (!thread) return;
      thread.classList.add("is-open");
      const parent = thread.querySelector("[name=parent_id]");
      const mention = thread.querySelector(".topic-composer__mention");
      if (parent) parent.value = btn.dataset.parent || "";
      if (mention) mention.textContent = btn.dataset.mention || "";
      thread.querySelector("textarea")?.focus();
    });
  });

  document.querySelectorAll("[data-scroll-composer]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const box = document.getElementById("post-composer");
      box?.scrollIntoView({ behavior: "smooth", block: "center" });
      box?.querySelector("textarea")?.focus();
    });
  });

  document.querySelectorAll(".js-attach-image").forEach((btn) => {
    btn.addEventListener("click", () => {
      const form = btn.closest("form");
      form?.querySelector('input[type="file"]')?.click();
    });
  });

  document.querySelectorAll('.topic-composer input[type="file"]').forEach((input) => {
    input.addEventListener("change", () => {
      const form = input.closest("form");
      const note = form?.querySelector(".js-attach-name");
      const file = input.files?.[0];
      if (!note) return;
      note.hidden = !file;
      note.textContent = file ? file.name : "";
    });
  });

  /* Body images → lightbox (same idea as slot rich text) */
  const lightbox = document.getElementById("image-lightbox");
  const lightboxImg = document.getElementById("image-lightbox-img");
  const lightboxPrev = document.getElementById("image-lightbox-prev");
  const lightboxNext = document.getElementById("image-lightbox-next");
  let lightboxGroup = [];
  let lightboxIndex = 0;
  let lightboxClosing = false;
  let lightboxCloseTimer = null;

  const setLightboxOpen = (open) => {
    if (!lightbox) return;
    if (open) {
      if (lightboxCloseTimer) {
        window.clearTimeout(lightboxCloseTimer);
        lightboxCloseTimer = null;
      }
      lightboxClosing = false;
      lightbox.hidden = false;
      document.body.classList.add("image-lightbox-open");
      window.requestAnimationFrame(() => {
        window.requestAnimationFrame(() => {
          lightbox.classList.add("is-open");
        });
      });
      return;
    }
    if (lightboxClosing) return;
    if (lightbox.hidden && !lightbox.classList.contains("is-open")) return;
    lightboxClosing = true;
    lightbox.classList.remove("is-open");
    lightboxCloseTimer = window.setTimeout(() => {
      lightbox.hidden = true;
      document.body.classList.remove("image-lightbox-open");
      if (lightboxImg) {
        lightboxImg.classList.remove("is-switching");
        lightboxImg.removeAttribute("src");
        lightboxImg.alt = "";
      }
      lightboxClosing = false;
      lightboxCloseTimer = null;
    }, 320);
  };

  const showLightboxShot = (index, { animate = false } = {}) => {
    if (!lightboxGroup.length || !lightboxImg) return;
    lightboxIndex = (index + lightboxGroup.length) % lightboxGroup.length;
    const item = lightboxGroup[lightboxIndex];
    const apply = () => {
      lightboxImg.src = item.src;
      lightboxImg.alt = item.alt || "";
      lightboxImg.classList.remove("is-switching");
      if (lightboxPrev) lightboxPrev.hidden = lightboxGroup.length < 2;
      if (lightboxNext) lightboxNext.hidden = lightboxGroup.length < 2;
    };
    if (animate && lightbox.classList.contains("is-open")) {
      lightboxImg.classList.add("is-switching");
      window.setTimeout(apply, 140);
      return;
    }
    apply();
  };

  const openLightboxGroup = (items, index) => {
    lightboxGroup = items;
    showLightboxShot(index);
    setLightboxOpen(true);
  };

  const enlargeLabel =
    document.querySelector("[data-enlarge-label]")?.dataset.enlargeLabel || "Enlarge image";

  document.querySelectorAll(".topic__content").forEach((rich) => {
    const imgs = Array.from(rich.querySelectorAll("img")).filter((img) => {
      const src = img.currentSrc || img.src || "";
      return src !== "" && !img.closest("a");
    });
    if (!imgs.length) return;

    const items = imgs.map((img) => ({
      src: img.currentSrc || img.src || "",
      alt: img.alt || "",
    }));

    imgs.forEach((img, index) => {
      img.classList.add("topic__zoom");
      img.setAttribute("role", "button");
      img.setAttribute("tabindex", "0");
      img.setAttribute("aria-label", enlargeLabel);
      const open = (event) => {
        event.preventDefault();
        event.stopImmediatePropagation();
        openLightboxGroup(items, index);
      };
      img.addEventListener("click", open, true);
      img.addEventListener("keydown", (event) => {
        if (event.key === "Enter" || event.key === " ") open(event);
      });
    });
  });

  lightboxPrev?.addEventListener("click", (event) => {
    event.stopPropagation();
    showLightboxShot(lightboxIndex - 1, { animate: true });
  });
  lightboxNext?.addEventListener("click", (event) => {
    event.stopPropagation();
    showLightboxShot(lightboxIndex + 1, { animate: true });
  });

  document.querySelectorAll(".js-close-lightbox").forEach((el) => {
    el.addEventListener("click", () => setLightboxOpen(false));
  });

  document.addEventListener("keydown", (event) => {
    if (!lightbox || lightbox.hidden || !lightbox.classList.contains("is-open")) return;
    if (event.key === "Escape") setLightboxOpen(false);
    if (event.key === "ArrowLeft") showLightboxShot(lightboxIndex - 1, { animate: true });
    if (event.key === "ArrowRight") showLightboxShot(lightboxIndex + 1, { animate: true });
  });

  const comments = document.getElementById("post-comments");
  if (comments && window.UgcTranslate) {
    window.UgcTranslate.translateNodes(comments.querySelectorAll(".topic-comment__text"), {
      serverUrl: comments.dataset.translateUrl,
    });
  }
})();
