(() => {
  const cfg = window.SlotPage || {};
  const csrf =
    cfg.csrf ||
    document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") ||
    "";

  const openAuth = () => {
    const modal = document.getElementById("auth-modal");
    if (!modal) return;
    modal.hidden = false;
    modal.classList.add("is-open");
    document.body.classList.add("auth-modal-open");
    const email = modal.querySelector('input[type="email"]');
    if (email) email.focus();
  };

  // Auth open uses .js-open-auth from main.js; keep helper for programmatic use.

  // Override prototype stub submit with real API when authenticated.
  const rankAccept = document.getElementById("rank-accept");
  const rankModal = document.getElementById("rank-modal");
  const statusEl = document.getElementById("rank-prototype-status");

  if (rankAccept && rankModal) {
    rankAccept.addEventListener(
      "click",
      async (event) => {
        if (!cfg.authenticated && rankModal.dataset.authenticated !== "1") {
          event.stopImmediatePropagation();
          rankModal.classList.remove("is-open");
          rankModal.hidden = true;
          document.body.classList.remove("rank-modal-open");
          openAuth();
          return;
        }

        event.stopImmediatePropagation();
        event.preventDefault();

        const modeBtn = rankModal.querySelector("[data-rating-mode].is-active");
        const playMode = modeBtn?.dataset.ratingMode || "demo";
        const valueText = document.getElementById("rank-value")?.textContent || "4";
        const rating = Math.min(5, Math.max(1, Math.round(Number(valueText) || 4)));
        const playedMyself = Boolean(document.getElementById("rank-evidence")?.checked);
        const body = document.getElementById("rank-comment")?.value?.trim() || "";
        const url = cfg.reviewUrl || rankModal.dataset.reviewUrl;

        if (statusEl) statusEl.textContent = "Submitting…";

        try {
          const res = await fetch(url, {
            method: "POST",
            headers: {
              "Content-Type": "application/json",
              Accept: "application/json",
              "X-CSRF-TOKEN": csrf,
              "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
            body: JSON.stringify({
              play_mode: playMode,
              rating,
              played_myself: playedMyself,
              body: body || null,
            }),
          });

          if (res.status === 401 || res.status === 419) {
            if (statusEl) statusEl.textContent = "Please log in to submit a review.";
            openAuth();
            return;
          }

          const data = await res.json().catch(() => ({}));
          if (!res.ok) {
            const msg =
              data.message ||
              (data.errors ? Object.values(data.errors).flat().join(" ") : null) ||
              "Could not save your review.";
            if (statusEl) statusEl.textContent = msg;
            return;
          }

          const fields = document.getElementById("rank-modal-fields");
          const thanks = document.getElementById("rank-modal-thanks");
          if (fields) fields.hidden = true;
          if (thanks) thanks.hidden = false;
          if (statusEl) statusEl.textContent = "";

          const list = document.getElementById("slot-reviews-list");
          if (list && data.review_id) {
            document.getElementById("reviews-empty")?.remove();
            list.querySelector(`[data-review-id="${data.review_id}"]`)?.remove();
            const i18n = window.SlotPage?.i18n || {};
            const name =
              rankModal.querySelector(".rank-modal__name")?.textContent?.trim() ||
              i18n.player ||
              "Player";
            const modeLabel =
              playMode === "real"
                ? i18n.realMoney || "Real money"
                : i18n.demoPlay || "Demo play";
            const esc = (value) =>
              String(value ?? "")
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;");
            const card = document.createElement("article");
            card.className = "reader-review";
            card.dataset.ratingSource = playMode;
            card.dataset.reviewId = String(data.review_id);
            card.innerHTML = `
              <div class="reader-review__top">
                <strong>${esc(name)}</strong>
                <span>${modeLabel} play · ${rating}/5</span>
                ${playedMyself ? '<span class="reader-review__badge">Played myself</span>' : ""}
              </div>
              <p class="reader-review__copy">${esc(body)}</p>
              <footer>
                <time datetime="${new Date().toISOString()}">just now</time>
                <button type="button">${(i18n.helpful || "Helpful · :count").replace(":count", '<span class="js-like-count">0</span>')}</button>
              </footer>`;
            list.prepend(card);
          }

          const paintStars = (root, score) => {
            if (!root) return;
            const filled = Math.round(score);
            root.querySelectorAll("use").forEach((useEl, index) => {
              const sprite = (useEl.getAttribute("href") || "").split("#")[0];
              useEl.setAttribute("href", `${sprite}#${index < filled ? "star" : "star-outline-orange"}`);
            });
          };

          if (typeof data.average === "number") {
            const avgText = data.average.toFixed(1);
            document.querySelectorAll("#slot-avg-rating, #reviews-avg-display").forEach((el) => {
              el.textContent = avgText;
            });
            paintStars(document.querySelector(".rank__player-stars"), data.average);
          }
          if (typeof data.count === "number") {
            const heroCount = document.getElementById("slot-reviews-count");
            if (heroCount) {
              heroCount.textContent = "Based on player reviews";
            }
            const reviewsCount = document.getElementById("reviews-count-display");
            if (reviewsCount) {
              reviewsCount.textContent = `Based on ${data.count} player review${data.count === 1 ? "" : "s"}`;
            }
          }

          window.setTimeout(() => window.location.reload(), 1400);
        } catch (err) {
          if (statusEl) statusEl.textContent = "Network error — please try again.";
        }
      },
      true
    );
  }

  document.querySelectorAll(".js-like-review").forEach((btn) => {
    btn.addEventListener("click", async () => {
      const url = btn.dataset.likeUrl;
      if (!url) return;
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
          openAuth();
          return;
        }
        const data = await res.json().catch(() => ({}));
        if (!res.ok) return;
        const countEl = btn.querySelector(".js-like-count");
        if (countEl && typeof data.count === "number") {
          countEl.textContent = String(data.count);
        }
        btn.classList.toggle("is-liked", Boolean(data.liked));
      } catch (_) {
        /* ignore */
      }
    });
  });

  const reviewsSection = document.getElementById("player-reviews");
  const reviewsMore = document.getElementById("reviews-more");
  let activeStarFilter = null;

  const currentModeFilter = () =>
    reviewsSection?.querySelector("[data-rating-filter].is-active")?.dataset.ratingFilter || "all";

  const reviewMatchesFilters = (card) => {
    const mode = currentModeFilter();
    const source = card.dataset.ratingSource || "";
    const modeOk = mode === "all" || source === mode || source.split(/\s+/).includes(mode);
    const starOk =
      activeStarFilter === null || Number(card.dataset.reviewRating) === Number(activeStarFilter);
    return modeOk && starOk;
  };

  const visibleReviewCards = () => {
    const filtering = reviewsSection?.dataset.filtered === "1";
    return Array.from(reviewsSection?.querySelectorAll(".reader-review") || []).filter((card) => {
      if (card.hidden) return false;
      if (filtering) return true;
      return !card.classList.contains("is-paged-out");
    });
  };

  const syncReviewWideLayout = () => {
    if (!reviewsSection) return;
    const cards = visibleReviewCards();
    reviewsSection.querySelectorAll(".reader-review").forEach((card) => {
      card.classList.remove("reader-review--wide");
    });
    if (cards.length % 2 === 1) {
      cards[cards.length - 1].classList.add("reader-review--wide");
    }
  };

  const translateReviewBodies = () => {
    if (!reviewsSection || !window.UgcTranslate) return;
    const nodes = Array.from(reviewsSection.querySelectorAll(".reader-review__copy")).filter((node) => {
      const card = node.closest(".reader-review");
      if (!card) return false;
      return reviewsSection.dataset.filtered === "1"
        ? !card.hidden
        : !card.hidden && !card.classList.contains("is-paged-out");
    });
    window.UgcTranslate.translateNodes(nodes, { serverUrl: cfg.translateUrl });
  };

  const applyReviewFilters = () => {
    if (!reviewsSection) return;
    const filtering = currentModeFilter() !== "all" || activeStarFilter !== null;
    reviewsSection.dataset.filtered = filtering ? "1" : "0";

    reviewsSection.querySelectorAll(".reader-review").forEach((card) => {
      card.hidden = !reviewMatchesFilters(card);
    });

    reviewsSection.querySelectorAll("[data-star-filter]").forEach((btn) => {
      const active = activeStarFilter !== null && String(btn.dataset.starFilter) === String(activeStarFilter);
      btn.classList.toggle("is-active", active);
      btn.setAttribute("aria-pressed", active ? "true" : "false");
    });

    syncReviewWideLayout();
    translateReviewBodies();

    if (!reviewsMore) return;
    if (filtering) {
      reviewsMore.hidden = true;
      return;
    }

    const left = Array.from(reviewsSection.querySelectorAll(".reader-review.is-paged-out")).length;
    reviewsMore.hidden = left === 0;
    const label = reviewsMore.querySelector("span");
    if (label && left > 0) {
      const i18n = window.SlotPage?.i18n || {};
      label.textContent =
        left > 10
          ? i18n.showNext10 || "Show next 10"
          : (i18n.showNext || "Show next :count").replace(":count", String(left));
    }
  };

  if (reviewsMore) {
    reviewsMore.addEventListener("click", () => {
      const hidden = Array.from(
        reviewsSection.querySelectorAll(".reader-review.is-paged-out")
      ).filter((card) => reviewMatchesFilters(card));
      hidden.slice(0, 10).forEach((card) => card.classList.remove("is-paged-out"));
      applyReviewFilters();
    });
  }

  reviewsSection?.querySelectorAll("[data-rating-filter]").forEach((btn) => {
    btn.addEventListener("click", (event) => {
      event.stopImmediatePropagation();
      reviewsSection.querySelectorAll("[data-rating-filter]").forEach((el) => {
        const active = el === btn;
        el.classList.toggle("is-active", active);
        el.setAttribute("aria-pressed", active ? "true" : "false");
      });
      applyReviewFilters();
    });
  });

  reviewsSection?.querySelectorAll("[data-star-filter]").forEach((btn) => {
    btn.addEventListener("click", () => {
      const star = Number(btn.dataset.starFilter);
      activeStarFilter = activeStarFilter === star ? null : star;
      applyReviewFilters();
    });
  });

  applyReviewFilters();

  const lightbox = document.getElementById("image-lightbox");
  const lightboxImg = document.getElementById("image-lightbox-img");
  const lightboxPrev = document.getElementById("image-lightbox-prev");
  const lightboxNext = document.getElementById("image-lightbox-next");
  let lightboxGroup = [];
  let lightboxIndex = 0;
  let swipeStartX = null;
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
      if (typeof item.onShow === "function") {
        item.onShow(lightboxIndex);
      }
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

  const shotThumbs = Array.from(document.querySelectorAll("#screenshots .shots__thumb"));
  const shotsMain = document.getElementById("shots-main");
  const shotsOpen = document.getElementById("shots-open");
  let currentShot = 0;

  const activateShot = (index) => {
    if (!shotThumbs.length) return;
    currentShot = (index + shotThumbs.length) % shotThumbs.length;
    const thumb = shotThumbs[currentShot];
    const src = thumb.getAttribute("data-shot") || "";
    const alt = thumb.getAttribute("data-alt") || "";
    if (shotsMain) {
      shotsMain.src = src;
      shotsMain.alt = alt;
    }
    if (shotsOpen) {
      shotsOpen.dataset.src = src;
      shotsOpen.dataset.alt = alt;
    }
    shotThumbs.forEach((item, itemIndex) => {
      const active = itemIndex === currentShot;
      item.classList.toggle("is-active", active);
      item.setAttribute("aria-pressed", active ? "true" : "false");
    });
  };

  const screenshotItems = shotThumbs.map((thumb, index) => ({
    src: thumb.getAttribute("data-shot") || "",
    alt: thumb.getAttribute("data-alt") || "",
    onShow: activateShot,
  }));

  shotThumbs.forEach((thumb, index) => {
    thumb.addEventListener("click", () => activateShot(index));
  });
  if (shotsOpen) {
    shotsOpen.addEventListener("click", () => openLightboxGroup(screenshotItems, currentShot));
  }

  const mobileTriggers = Array.from(document.querySelectorAll("#mobile .js-open-lightbox"));
  const mobileItems = mobileTriggers.map((btn) => {
    const img = btn.querySelector("img");
    return {
      src: btn.dataset.src || img?.currentSrc || img?.src || "",
      alt: btn.dataset.alt || img?.alt || "",
    };
  });
  mobileTriggers.forEach((btn, index) => {
    btn.addEventListener(
      "click",
      (event) => {
        event.stopImmediatePropagation();
        openLightboxGroup(mobileItems, index);
      },
      true
    );
  });

  /* Screenshots inside editorial / description rich text */
  const enlargeLabel = cfg.i18n?.enlargeScreenshot || "Enlarge screenshot";
  document.querySelectorAll(".section__rich").forEach((rich) => {
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
      img.classList.add("section__rich-zoom");
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
        if (event.key === "Enter" || event.key === " ") {
          open(event);
        }
      });
    });
  });

  if (lightboxPrev) {
    lightboxPrev.addEventListener("click", (event) => {
      event.stopPropagation();
      showLightboxShot(lightboxIndex - 1, { animate: true });
    });
  }
  if (lightboxNext) {
    lightboxNext.addEventListener("click", (event) => {
      event.stopPropagation();
      showLightboxShot(lightboxIndex + 1, { animate: true });
    });
  }
  document.addEventListener("keydown", (event) => {
    if (!lightbox || (lightbox.hidden && !lightbox.classList.contains("is-open"))) return;
    if (event.key === "ArrowLeft") showLightboxShot(lightboxIndex - 1, { animate: true });
    if (event.key === "ArrowRight") showLightboxShot(lightboxIndex + 1, { animate: true });
    if (event.key === "Escape") setLightboxOpen(false);
  });
  document.querySelectorAll(".js-close-lightbox").forEach((btn) => {
    btn.addEventListener("click", (event) => {
      event.stopPropagation();
      setLightboxOpen(false);
    });
  });
  if (lightbox) {
    lightbox.addEventListener("click", (event) => {
      if (event.target === lightbox || event.target.classList.contains("image-lightbox__backdrop")) {
        setLightboxOpen(false);
      }
    });
    lightbox.addEventListener("touchstart", (event) => {
      swipeStartX = event.changedTouches[0]?.screenX ?? null;
    }, { passive: true });
    lightbox.addEventListener("touchend", (event) => {
      if (swipeStartX === null) return;
      const dx = (event.changedTouches[0]?.screenX ?? swipeStartX) - swipeStartX;
      swipeStartX = null;
      if (dx > 40) showLightboxShot(lightboxIndex - 1, { animate: true });
      if (dx < -40) showLightboxShot(lightboxIndex + 1, { animate: true });
    });
  }

  const demoModal = document.getElementById("demo-modal");
  const demoFrame = document.getElementById("demo-modal-frame");
  const gamePromo = document.getElementById("game-promo");
  let gamePromoTimer = null;

  const hideGamePromo = () => {
    if (gamePromoTimer) {
      window.clearTimeout(gamePromoTimer);
      gamePromoTimer = null;
    }
    if (gamePromo) {
      gamePromo.hidden = true;
      gamePromo.classList.remove("is-visible");
    }
  };

  const showGamePromo = () => {
    if (!gamePromo || demoModal?.hidden) return;
    gamePromo.hidden = false;
    requestAnimationFrame(() => gamePromo.classList.add("is-visible"));
  };

  const scheduleGamePromo = () => {
    hideGamePromo();
    if (!gamePromo) return;
    const delay = Number(gamePromo.dataset.delay || 30000);
    gamePromoTimer = window.setTimeout(showGamePromo, Number.isFinite(delay) ? delay : 30000);
  };

  const openDemo = () => {
    if (!demoModal || !demoFrame) return;
    if (!demoFrame.getAttribute("src")) {
      demoFrame.setAttribute("src", demoFrame.dataset.src || "");
    }
    demoModal.hidden = false;
    document.body.classList.add("demo-modal-open");
    scheduleGamePromo();
  };
  const closeDemo = () => {
    if (!demoModal || !demoFrame) return;
    hideGamePromo();
    demoModal.hidden = true;
    demoFrame.setAttribute("src", "");
    document.body.classList.remove("demo-modal-open");
  };
  document.querySelectorAll(".js-open-demo").forEach((btn) => {
    btn.addEventListener("click", openDemo);
  });
  document.querySelectorAll(".js-close-demo").forEach((btn) => {
    btn.addEventListener("click", closeDemo);
  });
  document.querySelectorAll(".js-close-game-promo").forEach((btn) => {
    btn.addEventListener("click", (event) => {
      event.stopPropagation();
      hideGamePromo();
    });
  });
  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape" || !demoModal || demoModal.hidden) return;
    if (gamePromo && !gamePromo.hidden) {
      hideGamePromo();
      return;
    }
    closeDemo();
  });

  try {
    const params = new URLSearchParams(window.location.search);
    if (params.get("rate") === "1" && cfg.authenticated) {
      document.querySelector(".js-open-rank-modal")?.click();
      params.delete("rate");
      const next = params.toString();
      window.history.replaceState({}, "", window.location.pathname + (next ? "?" + next : "") + window.location.hash);
    }
  } catch (_) {
    /* ignore */
  }
})();
