(function () {
  "use strict";

  /* ===== Profile menu ===== */
  const profileToggle = document.getElementById("profile-toggle");
  const profileMenu = document.getElementById("profile-menu");

  const closeProfileMenu = () => {
    if (!profileMenu || !profileToggle) return;
    profileMenu.hidden = true;
    profileToggle.setAttribute("aria-expanded", "false");
  };

  /* ===== Language menu (Figma DROP_dowm LANG 2466:4072) ===== */
  const LANG_LABELS = {
    en: "English",
    de: "German",
    fr: "French",
    uk: "Ukrainian",
    ru: "Russian",
    es: "Spanish",
    it: "Italian",
    pt: "Portuguese",
    no: "Norwegian",
    ja: "Japanese",
    sv: "Swedish",
  };
  const LANG_CODES = {
    en: "EN",
    de: "DE",
    fr: "FR",
    uk: "UK",
    ru: "RU",
    es: "ES",
    it: "IT",
    pt: "PT",
    no: "NO",
    ja: "JA",
    sv: "SV",
  };
  const htmlLang = (document.documentElement.lang || "en").slice(0, 2).toLowerCase();
  let currentLang = LANG_CODES[htmlLang] ? htmlLang : "en";

  const langPairs = [
    {
      toggle: document.getElementById("lang-toggle"),
      menu: document.getElementById("lang-menu"),
    },
    {
      toggle: document.getElementById("drawer-lang-toggle"),
      menu: document.getElementById("drawer-lang-menu"),
    },
  ].filter((pair) => pair.toggle && pair.menu);

  const closeLangMenus = (exceptMenu) => {
    langPairs.forEach(({ toggle, menu }) => {
      if (exceptMenu && menu === exceptMenu) return;
      menu.hidden = true;
      toggle.setAttribute("aria-expanded", "false");
    });
  };

  const syncLangUI = () => {
    const label = LANG_LABELS[currentLang] || "English";
    const code = LANG_CODES[currentLang] || "EN";

    langPairs.forEach(({ toggle, menu }) => {
      toggle.setAttribute("aria-label", `Language: ${label}`);
      menu.querySelectorAll(".header__lang-option").forEach((btn) => {
        const active = btn.getAttribute("data-lang") === currentLang;
        btn.classList.toggle("is-active", active);
        if (active) btn.setAttribute("aria-current", "true");
        else btn.removeAttribute("aria-current");
      });
    });

    document.querySelectorAll(".header__drawer-lang-code, .header__lang-code").forEach((el) => {
      el.textContent = code;
    });
  };

  langPairs.forEach(({ toggle, menu }) => {
    toggle.addEventListener("click", (event) => {
      event.stopPropagation();
      const isOpen = toggle.getAttribute("aria-expanded") === "true";
      if (isOpen) {
        closeLangMenus();
        return;
      }
      closeProfileMenu();
      closeLangMenus(menu);
      menu.hidden = false;
      toggle.setAttribute("aria-expanded", "true");
    });

    menu.querySelectorAll(".header__lang-option").forEach((btn) => {
      btn.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        const nextLang = btn.getAttribute("data-lang") || "en";
        const target = btn.href ? new URL(btn.href, window.location.href) : null;
        const here = window.location.pathname.replace(/\/+$/, "") || "/";
        const there = target ? target.pathname.replace(/\/+$/, "") || "/" : here;
        if (target && there !== here) {
          window.location.assign(target.pathname + target.search + target.hash);
          return;
        }
        currentLang = nextLang;
        syncLangUI();
        closeLangMenus();
      });
    });
  });

  document.addEventListener("click", (event) => {
    const hitLang = langPairs.some(
      ({ toggle, menu }) =>
        toggle.contains(event.target) || menu.contains(event.target)
    );
    if (!hitLang) closeLangMenus();
  });

  if (profileToggle && profileMenu) {
    profileToggle.addEventListener("click", (event) => {
      event.stopPropagation();
      const isOpen = profileToggle.getAttribute("aria-expanded") === "true";
      if (isOpen) {
        closeProfileMenu();
      } else {
        closeMobileDrawer();
        closeLangMenus();
        profileMenu.hidden = false;
        profileToggle.setAttribute("aria-expanded", "true");
      }
    });

    document.addEventListener("click", (event) => {
      if (
        !profileMenu.hidden &&
        !profileMenu.contains(event.target) &&
        !profileToggle.contains(event.target)
      ) {
        closeProfileMenu();
      }
    });
  }

  /* ===== Mobile drawer ===== */
  const menuToggle = document.getElementById("menu-toggle");
  const mobileDrawer = document.getElementById("mobile-drawer");
  const drawerClose = document.getElementById("drawer-close");
  let drawerClosing = false;

  const isDrawerOpen = () =>
    Boolean(mobileDrawer && mobileDrawer.classList.contains("is-open"));

  const closeMobileDrawer = () => {
    if (!mobileDrawer || !menuToggle || drawerClosing) return;
    if (!isDrawerOpen() && mobileDrawer.hidden) return;

    drawerClosing = true;
    mobileDrawer.classList.remove("is-open");
    menuToggle.setAttribute("aria-expanded", "false");
    menuToggle.setAttribute("aria-label", "Open menu");
    document.body.classList.remove("menu-open");

    mobileDrawer.querySelectorAll(".header__drawer-dd.is-open").forEach((item) => {
      item.classList.remove("is-open");
      const toggle = item.querySelector(".header__drawer-link--dropdown");
      const panel = item.querySelector(".header__drawer-panel");
      if (toggle) toggle.setAttribute("aria-expanded", "false");
      if (panel) panel.hidden = true;
    });

    const finishClose = () => {
      mobileDrawer.hidden = true;
      drawerClosing = false;
    };

    const onEnd = (event) => {
      if (event.target !== mobileDrawer) return;
      mobileDrawer.removeEventListener("transitionend", onEnd);
      finishClose();
    };

    mobileDrawer.addEventListener("transitionend", onEnd);
    window.setTimeout(finishClose, 320);
  };

  const openMobileDrawer = () => {
    if (!mobileDrawer || !menuToggle) return;
    closeProfileMenu();
    closeLangMenus();
    drawerClosing = false;
    mobileDrawer.hidden = false;
    menuToggle.setAttribute("aria-expanded", "true");
    menuToggle.setAttribute("aria-label", "Close menu");
    document.body.classList.add("menu-open");
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => {
        mobileDrawer.classList.add("is-open");
      });
    });
  };

  if (menuToggle && mobileDrawer) {
    menuToggle.addEventListener("click", (event) => {
      event.stopPropagation();
      const isOpen = menuToggle.getAttribute("aria-expanded") === "true";
      if (isOpen) {
        closeMobileDrawer();
      } else {
        openMobileDrawer();
      }
    });

    mobileDrawer.querySelectorAll("a.header__drawer-link, a.header__drawer-sublink").forEach((link) => {
      link.addEventListener("click", () => {
        closeMobileDrawer();
      });
    });

    mobileDrawer.querySelectorAll(".header__drawer-dd").forEach((item) => {
      const toggle = item.querySelector(".header__drawer-link--dropdown");
      const panel = item.querySelector(".header__drawer-panel");
      if (!toggle || !panel) return;

      toggle.addEventListener("click", (event) => {
        event.preventDefault();
        event.stopPropagation();
        const willOpen = !item.classList.contains("is-open");

        mobileDrawer.querySelectorAll(".header__drawer-dd.is-open").forEach((openItem) => {
          if (openItem === item) return;
          openItem.classList.remove("is-open");
          const openToggle = openItem.querySelector(".header__drawer-link--dropdown");
          const openPanel = openItem.querySelector(".header__drawer-panel");
          if (openToggle) openToggle.setAttribute("aria-expanded", "false");
          if (openPanel) openPanel.hidden = true;
        });

        item.classList.toggle("is-open", willOpen);
        toggle.setAttribute("aria-expanded", willOpen ? "true" : "false");
        panel.hidden = !willOpen;
      });
    });
  }

  if (drawerClose) {
    drawerClose.addEventListener("click", () => {
      closeMobileDrawer();
      if (menuToggle) menuToggle.focus();
    });
  }

  /* ===== Cookie modal ===== */
  const COOKIE_KEY = "st_cookies";
  const cookieModal = document.getElementById("cookie-modal");
  let cookieModalClosing = false;

  const openCookieModal = () => {
    if (!cookieModal) return;
    cookieModalClosing = false;
    cookieModal.hidden = false;
    cookieModal.classList.remove("is-expanded");
    document.body.classList.add("cookie-modal-open");
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => {
        cookieModal.classList.add("is-open");
      });
    });
  };

  const closeCookieModal = () => {
    if (!cookieModal || cookieModalClosing) return;
    if (cookieModal.hidden && !cookieModal.classList.contains("is-open")) return;

    cookieModalClosing = true;
    cookieModal.classList.remove("is-open");

    const finishClose = () => {
      if (!cookieModalClosing) return;
      cookieModal.hidden = true;
      cookieModal.classList.remove("is-expanded");
      document.body.classList.remove("cookie-modal-open");
      cookieModalClosing = false;
    };

    const dialog = cookieModal.querySelector(".cookie-modal__dialog");
    const onEnd = (event) => {
      if (event.target !== dialog) return;
      dialog.removeEventListener("transitionend", onEnd);
      finishClose();
    };

    if (dialog) dialog.addEventListener("transitionend", onEnd);
    window.setTimeout(finishClose, 340);
  };

  const storeCookiePreference = (value) => {
    try {
      localStorage.setItem(COOKIE_KEY, value);
    } catch (_) {
      /* ignore */
    }
  };

  if (cookieModal) {
    let stored = null;
    try {
      stored = localStorage.getItem(COOKIE_KEY);
    } catch (_) {
      stored = null;
    }

    if (stored !== "1") {
      openCookieModal();
    } else {
      cookieModal.hidden = true;
    }

    const readMore = document.getElementById("cookie-read-more");
    const customize = document.getElementById("cookie-customize");
    const reject = document.getElementById("cookie-reject");
    const accept = document.getElementById("cookie-accept");

    if (readMore) {
      readMore.addEventListener("click", () => {
        cookieModal.classList.add("is-expanded");
      });
    }

    if (customize) {
      customize.addEventListener("click", () => {
        cookieModal.classList.add("is-expanded");
      });
    }

    if (reject) {
      reject.addEventListener("click", () => {
        storeCookiePreference("1");
        closeCookieModal();
      });
    }

    if (accept) {
      accept.addEventListener("click", () => {
        storeCookiePreference("1");
        closeCookieModal();
      });
    }
  }

  /* ===== Auth modal ===== */
  const authModal = document.getElementById("auth-modal");
  let authModalLastFocus = null;
  let authModalClosing = false;

  const getAuthPanels = () =>
    authModal
      ? Array.from(authModal.querySelectorAll("[data-auth-panel]"))
      : [];

  const showAuthPanel = (name) => {
    getAuthPanels().forEach((panel) => {
      panel.hidden = panel.getAttribute("data-auth-panel") !== name;
    });
    const ageError = document.getElementById("auth-age-error");
    if (ageError) ageError.hidden = true;
  };

  const openAuthModal = (trigger, panel = "login") => {
    if (!authModal) return;
    authModalLastFocus = trigger || document.activeElement;
    closeProfileMenu();
    closeLangMenus();
    closeMobileDrawer();
    authModalClosing = false;
    showAuthPanel(panel);
    authModal.hidden = false;
    document.body.classList.add("auth-modal-open");
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => {
        authModal.classList.add("is-open");
      });
    });
    const closeBtn = authModal.querySelector(".auth-modal__close");
    if (closeBtn) closeBtn.focus();
  };

  const closeAuthModal = () => {
    if (!authModal || authModalClosing) return;
    if (authModal.hidden && !authModal.classList.contains("is-open")) return;

    authModalClosing = true;
    authModal.classList.remove("is-open");

    const finishClose = () => {
      if (!authModalClosing) return;
      authModal.hidden = true;
      document.body.classList.remove("auth-modal-open");
      authModalClosing = false;
      showAuthPanel("login");
      if (authModalLastFocus && typeof authModalLastFocus.focus === "function") {
        authModalLastFocus.focus();
      }
    };

    const dialog = authModal.querySelector(".auth-modal__dialog");
    const onEnd = (event) => {
      if (event.target !== dialog) return;
      dialog.removeEventListener("transitionend", onEnd);
      finishClose();
    };

    if (dialog) dialog.addEventListener("transitionend", onEnd);
    window.setTimeout(finishClose, 340);
  };

  const setAuthReturn = (returnUrl) => {
    if (returnUrl) sessionStorage.setItem("authReturn", returnUrl);
    else sessionStorage.removeItem("authReturn");
    document.querySelectorAll('a[href^="/auth/google"]').forEach((link) => {
      link.setAttribute(
        "href",
        returnUrl
          ? "/auth/google?return_to=" + encodeURIComponent(returnUrl)
          : "/auth/google"
      );
    });
  };

  document.querySelectorAll(".js-open-auth").forEach((btn) => {
    btn.addEventListener("click", (event) => {
      event.preventDefault();
      if (btn.dataset.afterLogin === "rate") {
        const next = new URL(window.location.href);
        next.searchParams.set("rate", "1");
        next.hash = "";
        setAuthReturn(next.toString());
      } else if (btn.dataset.authReturn === "here") {
        setAuthReturn(window.location.href);
      } else {
        setAuthReturn("");
      }
      openAuthModal(btn, "login");
    });
  });

  document.querySelectorAll(".js-close-auth").forEach((el) => {
    el.addEventListener("click", closeAuthModal);
  });

  document.querySelectorAll(".js-auth-panel").forEach((btn) => {
    btn.addEventListener("click", () => {
      const target = btn.getAttribute("data-auth-target") || "login";
      showAuthPanel(target);
    });
  });

  const avatarModal = document.getElementById("avatar-modal");
  let avatarModalLastFocus = null;
  let avatarModalClosing = false;
  let avatarObjectUrl = null;
  let avatarRandomIndex = 0;
  const avatarRandomSources = [
    "assets/images/profile/avatar-sample.png",
    "assets/images/profile/avatar-r1.svg",
    "assets/images/profile/avatar-r2.svg",
    "assets/images/profile/avatar-r3.svg",
  ];

  const getAvatarBox = () =>
    avatarModal ? avatarModal.querySelector(".avatar-modal__box") : null;

  const setAvatarView = (view) => {
    const box = getAvatarBox();
    if (!box) return;
    box.setAttribute("data-avatar-view", view);
  };

  const setAvatarPhoto = (src) => {
    if (!avatarModal) return;
    const preview = avatarModal.querySelector("[data-avatar-edit-preview]");
    const photo = avatarModal.querySelector("[data-avatar-photo]");
    if (!preview || !photo) return;
    if (src) {
      photo.src = src;
      preview.classList.remove("is-initials");
    } else {
      preview.classList.add("is-initials");
    }
  };

  const revokeAvatarObjectUrl = () => {
    if (!avatarObjectUrl) return;
    URL.revokeObjectURL(avatarObjectUrl);
    avatarObjectUrl = null;
  };

  const openAvatarModal = (trigger) => {
    if (!avatarModal) return;
    avatarModalLastFocus = trigger || document.activeElement;
    closeProfileMenu();
    closeLangMenus();
    closeMobileDrawer();
    closeAuthModal();
    avatarModalClosing = false;
    setAvatarView("idle");
    setAvatarPhoto(null);
    revokeAvatarObjectUrl();
    const fileInput = document.getElementById("avatar-upload-input");
    if (fileInput) fileInput.value = "";
    avatarModal.hidden = false;
    document.body.classList.add("avatar-modal-open");
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => {
        avatarModal.classList.add("is-open");
      });
    });
    const closeBtn = avatarModal.querySelector(".avatar-modal__close");
    if (closeBtn) closeBtn.focus();
  };

  const closeAvatarModal = () => {
    if (!avatarModal || avatarModalClosing) return;
    if (avatarModal.hidden && !avatarModal.classList.contains("is-open")) return;

    avatarModalClosing = true;
    avatarModal.classList.remove("is-open");

    const finishClose = () => {
      if (!avatarModalClosing) return;
      avatarModal.hidden = true;
      document.body.classList.remove("avatar-modal-open");
      avatarModalClosing = false;
      setAvatarView("idle");
      revokeAvatarObjectUrl();
      if (avatarModalLastFocus && typeof avatarModalLastFocus.focus === "function") {
        avatarModalLastFocus.focus();
      }
    };

    const dialog = avatarModal.querySelector(".avatar-modal__dialog");
    const onEnd = (event) => {
      if (event.target !== dialog) return;
      dialog.removeEventListener("transitionend", onEnd);
      finishClose();
    };

    if (dialog) dialog.addEventListener("transitionend", onEnd);
    window.setTimeout(finishClose, 340);
  };

  document.querySelectorAll(".js-open-avatar").forEach((btn) => {
    btn.addEventListener("click", (event) => {
      event.preventDefault();
      openAvatarModal(btn);
    });
  });

  document.querySelectorAll(".js-close-avatar").forEach((el) => {
    el.addEventListener("click", closeAvatarModal);
  });

  if (avatarModal) {
    const editBtn = avatarModal.querySelector(".js-avatar-edit");
    if (editBtn) {
      editBtn.addEventListener("click", () => {
        setAvatarView("edit");
        const preview = avatarModal.querySelector("[data-avatar-edit-preview]");
        if (preview && preview.classList.contains("is-initials")) {
          setAvatarPhoto(avatarRandomSources[0]);
          avatarRandomIndex = 0;
        }
      });
    }

    const randomBtn = avatarModal.querySelector(".js-avatar-random");
    if (randomBtn) {
      randomBtn.addEventListener("click", () => {
        revokeAvatarObjectUrl();
        avatarRandomIndex = (avatarRandomIndex + 1) % avatarRandomSources.length;
        setAvatarPhoto(avatarRandomSources[avatarRandomIndex]);
      });
    }

    const fileInput = document.getElementById("avatar-upload-input");
    if (fileInput) {
      fileInput.addEventListener("change", () => {
        const file = fileInput.files && fileInput.files[0];
        if (!file) return;
        const isImage = /image\/(jpeg|jpg|png)/i.test(file.type) || /\.(jpe?g|png)$/i.test(file.name);
        if (!isImage) {
          fileInput.value = "";
          return;
        }
        if (file.size > 300 * 1024) {
          fileInput.value = "";
          window.alert("Avatar must be JPEG, JPG or PNG and cannot exceed 300KB.");
          return;
        }
        revokeAvatarObjectUrl();
        avatarObjectUrl = URL.createObjectURL(file);
        setAvatarPhoto(avatarObjectUrl);
      });
    }

    const saveBtn = avatarModal.querySelector(".js-avatar-save");
    if (saveBtn) {
      saveBtn.addEventListener("click", () => {
        const preview = avatarModal.querySelector("[data-avatar-edit-preview]");
        const photo = avatarModal.querySelector("[data-avatar-photo]");
        const nicknameInput = avatarModal.querySelector(".avatar-modal__input");
        const usePhoto = preview && !preview.classList.contains("is-initials") && photo && photo.src;
        const src = usePhoto ? photo.src : "";

        document.querySelectorAll(".profile-card__avatar").forEach((el) => {
          if (src) {
            el.classList.add("has-photo");
            el.innerHTML = "";
            const img = document.createElement("img");
            img.src = src;
            img.alt = "";
            img.width = 80;
            img.height = 80;
            el.appendChild(img);
          } else {
            el.classList.remove("has-photo");
            el.textContent = "SU";
          }
        });

        if (nicknameInput) {
          const nick = nicknameInput.value.trim();
          const nameEl = document.querySelector(".profile-card__name");
          if (nameEl && nick) {
            nameEl.textContent = nick.replace(/^@/, "");
          }
        }

        closeAvatarModal();
      });
    }
  }

  // Auth form submit is handled by /js/auth-newsletter.js (magic-link API).

  document.addEventListener("keydown", (event) => {
    if (event.key !== "Escape") return;

    if (imageLightbox && imageLightbox.classList.contains("is-open")) {
      closeImageLightbox();
      return;
    }

    if (rankModal && rankModal.classList.contains("is-open")) {
      closeRankModal();
      return;
    }

    if (authModal && authModal.classList.contains("is-open")) {
      closeAuthModal();
      return;
    }

    if (avatarModal && avatarModal.classList.contains("is-open")) {
      closeAvatarModal();
      return;
    }

    if (cookieModal && cookieModal.classList.contains("is-open")) {
      closeCookieModal();
      return;
    }

    const openLang = langPairs.find(({ menu }) => !menu.hidden);
    if (openLang) {
      closeLangMenus();
      openLang.toggle.focus();
      return;
    }

    if (isDrawerOpen()) {
      closeMobileDrawer();
      menuToggle.focus();
      return;
    }

    if (profileMenu && !profileMenu.hidden) {
      closeProfileMenu();
      profileToggle.focus();
    }
  });

  /* ===== Rank modal ===== */
  const rankModal = document.getElementById("rank-modal");
  const rankValueEl = document.getElementById("rank-value");
  const rankStars = rankModal
    ? Array.from(rankModal.querySelectorAll(".rank-modal__star"))
    : [];
  const STAR_FILLED = "star";
  const STAR_OUTLINE = "star-outline-orange";
  let rankValue = 4.0;
  let rankModalLastFocus = null;
  let rankModalClosing = false;

  const formatRank = (value) => value.toFixed(1);

  const updateRankUI = () => {
    if (rankValueEl) {
      rankValueEl.textContent = formatRank(rankValue);
    }

    const filledCount = Math.round(rankValue);
    rankStars.forEach((starBtn) => {
      const starVal = Number(starBtn.dataset.value);
      const useEl = starBtn.querySelector("use");
      if (!useEl) return;
      const isFilled = starVal <= filledCount && rankValue > 0;
      const sprite = (useEl.getAttribute("href") || "").split("#")[0];
      useEl.setAttribute("href", `${sprite}#${isFilled ? STAR_FILLED : STAR_OUTLINE}`);
      starBtn.setAttribute("aria-pressed", isFilled ? "true" : "false");
    });
  };

  const setRankValue = (value) => {
    rankValue = Math.min(5, Math.max(1, Math.round(Number(value) || 1)));
    updateRankUI();
  };

  const getScrollbarWidth = () =>
    window.innerWidth - document.documentElement.clientWidth;

  const openRankModal = (trigger) => {
    if (!rankModal) return;
    rankModalLastFocus = trigger || document.activeElement;
    if (rankPrototypeStatus) rankPrototypeStatus.textContent = "";
    const initial = Number(rankModal.dataset.initialRating);
    setRankValue(Number.isFinite(initial) && initial >= 1 ? initial : 4);
    const comment = document.getElementById("rank-comment");
    if (comment) comment.value = rankModal.dataset.initialBody || "";
    const evidence = document.getElementById("rank-evidence");
    if (evidence) evidence.checked = rankModal.dataset.initialPlayed === "1";
    const mode = rankModal.dataset.initialMode || "demo";
    rankModal.querySelectorAll("[data-rating-mode]").forEach((item) => {
      const isActive = item.dataset.ratingMode === mode;
      item.classList.toggle("is-active", isActive);
      item.setAttribute("aria-pressed", isActive ? "true" : "false");
    });
    const fields = document.getElementById("rank-modal-fields");
    const thanks = document.getElementById("rank-modal-thanks");
    if (fields) fields.hidden = false;
    if (thanks) thanks.hidden = true;
    closeProfileMenu();
    closeMobileDrawer();
    const scrollbarWidth = getScrollbarWidth();
    rankModalClosing = false;
    rankModal.hidden = false;
    document.body.style.paddingRight = scrollbarWidth ? `${scrollbarWidth}px` : "";
    document.body.classList.add("rank-modal-open");
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => {
        rankModal.classList.add("is-open");
      });
    });
    const closeBtn = rankModal.querySelector(".rank-modal__close");
    if (closeBtn) closeBtn.focus();
  };

  const closeRankModal = () => {
    if (!rankModal || rankModalClosing) return;
    if (rankModal.hidden && !rankModal.classList.contains("is-open")) return;

    rankModalClosing = true;
    rankModal.classList.remove("is-open");

    const finishClose = () => {
      if (!rankModalClosing) return;
      rankModal.hidden = true;
      document.body.classList.remove("rank-modal-open");
      document.body.style.paddingRight = "";
      rankModalClosing = false;
      if (rankModalLastFocus && typeof rankModalLastFocus.focus === "function") {
        rankModalLastFocus.focus();
      }
    };

    const dialog = rankModal.querySelector(".rank-modal__dialog");
    const onEnd = (event) => {
      if (event.target !== dialog) return;
      dialog.removeEventListener("transitionend", onEnd);
      finishClose();
    };

    if (dialog) {
      dialog.addEventListener("transitionend", onEnd);
    }
    window.setTimeout(finishClose, 340);
  };

  document.querySelectorAll(".js-open-rank-modal").forEach((btn) => {
    btn.addEventListener("click", () => openRankModal(btn));
  });

  document.querySelectorAll(".js-close-rank-modal").forEach((el) => {
    el.addEventListener("click", closeRankModal);
  });

  const rankMinus = document.getElementById("rank-minus");
  const rankPlus = document.getElementById("rank-plus");
  const rankAccept = document.getElementById("rank-accept");
  const rankPrototypeStatus = document.getElementById("rank-prototype-status");

  if (rankMinus) {
    rankMinus.addEventListener("click", () => setRankValue(rankValue - 1));
  }

  if (rankPlus) {
    rankPlus.addEventListener("click", () => setRankValue(rankValue + 1));
  }

  rankStars.forEach((starBtn) => {
    starBtn.addEventListener("click", () => {
      setRankValue(Number(starBtn.dataset.value));
    });
  });

  const ratingModeButtons = Array.from(
    document.querySelectorAll("[data-rating-mode]")
  );

  ratingModeButtons.forEach((button) => {
    button.addEventListener("click", () => {
      ratingModeButtons.forEach((item) => {
        const isActive = item === button;
        item.classList.toggle("is-active", isActive);
        item.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
    });
  });

  if (rankAccept) {
    rankAccept.addEventListener("click", () => {
      if (rankPrototypeStatus) {
        rankPrototypeStatus.textContent =
          "Thanks — your rating was recorded.";
      }
    });
  }

  updateRankUI();

  /* ===== Community live results prototype ===== */
  const trackerRangeButtons = Array.from(
    document.querySelectorAll("[data-live-range]")
  );
  const chartRangeButtons = Array.from(
    document.querySelectorAll("[data-chart-range]")
  );
  const trackerChartPlot = document.getElementById("community-chart-plot");
  const trackerChartTitle = document.getElementById("community-chart-title");
  const trackerChartAxis = document.getElementById("community-chart-axis");
  const trackerNetCard = document.querySelector(".community-kpis__net");
  const trackerPulseCard = document.getElementById("tracker-pulse-card");
  const trackerFields = {
    spins: document.getElementById("tracker-spins"),
    players: document.getElementById("tracker-players"),
    wagered: document.getElementById("tracker-wagered"),
    returned: document.getElementById("tracker-returned"),
    net: document.getElementById("tracker-net"),
    returnRate: document.getElementById("tracker-return-rate"),
    pulseNet: document.getElementById("tracker-pulse-net"),
    pulseLabel: document.getElementById("tracker-pulse-label"),
    pulseSpins: document.getElementById("tracker-pulse-spins"),
    pulseLargest: document.getElementById("tracker-pulse-largest"),
    pulseDry: document.getElementById("tracker-pulse-dry"),
    pulseUsers: document.getElementById("tracker-pulse-users"),
  };

  const trackerData = {
    "1h": {
      label: "Last 1 hour",
      chartLabel: "Last 1 hour",
      spins: 8406,
      players: 312,
      wagered: 12840,
      returned: 11000,
      net: -1840,
      returnRate: 0.8567,
      largest: "742× stake",
      dry: "19 spins",
      axis: ["Window start", "15m", "30m", "45m", "Window end"],
      values: [420, -780, 310, -920, 640, -1100, 280, -650],
      points: [
        { label: "0m – 8m", players: 28, spins: 920 },
        { label: "8m – 15m", players: 34, spins: 1010 },
        { label: "15m – 22m", players: 31, spins: 980 },
        { label: "22m – 30m", players: 42, spins: 1120 },
        { label: "30m – 37m", players: 38, spins: 1060 },
        { label: "37m – 45m", players: 44, spins: 1180 },
        { label: "45m – 52m", players: 40, spins: 1090 },
        { label: "52m – 60m", players: 41, spins: 1046 },
      ],
      outcomes: [63.1, 18.8, 15.2, 2.6, 0.3],
    },
    "6h": {
      label: "Last 6 hours",
      chartLabel: "Last 6 hours",
      spins: 48210,
      players: 684,
      wagered: 74280,
      returned: 69140,
      net: -5140,
      returnRate: 0.9308,
      largest: "742× stake",
      dry: "19 spins",
      axis: ["Window start", "1h", "2h", "3h", "4h", "5h", "Window end"],
      values: [520, -980, 410, -1240, 860, -1120, 640, -740],
      points: [
        { label: "0h – 0.75h", players: 58, spins: 5420 },
        { label: "0.75h – 1.5h", players: 64, spins: 6010 },
        { label: "1.5h – 2.25h", players: 61, spins: 5740 },
        { label: "2.25h – 3h", players: 72, spins: 6480 },
        { label: "3h – 3.75h", players: 68, spins: 6150 },
        { label: "3.75h – 4.5h", players: 74, spins: 6610 },
        { label: "4.5h – 5.25h", players: 70, spins: 6280 },
        { label: "5.25h – 6h", players: 71, spins: 5550 },
      ],
      outcomes: [61.8, 18.5, 16.9, 2.5, 0.3],
    },
    "24h": {
      label: "Last 24 hours",
      chartLabel: "Last 24 hours",
      spins: 186420,
      players: 1284,
      wagered: 284610,
      returned: 266190,
      net: -18420,
      returnRate: 0.9353,
      largest: "742× stake",
      dry: "19 spins",
      axis: ["Window start", "03:00", "06:00", "09:00", "12:00", "15:00", "18:00", "21:00", "Window end"],
      values: [-920, -540, 780, -1420, -680, 520, -310, -1190, 960, -1580, -840, -420, 13348, -730, -1680, 440, -950, -1210, 820, -660, -1310, 610, -790, -1020],
      points: [
        { label: "00:00 - 01:00", players: 48, spins: 6820 },
        { label: "01:00 - 02:00", players: 42, spins: 6410 },
        { label: "02:00 - 03:00", players: 39, spins: 5980 },
        { label: "03:00 - 04:00", players: 36, spins: 5720 },
        { label: "04:00 - 05:00", players: 41, spins: 6100 },
        { label: "05:00 - 06:00", players: 54, spins: 6940 },
        { label: "06:00 - 07:00", players: 61, spins: 7320 },
        { label: "07:00 - 08:00", players: 68, spins: 7610 },
        { label: "08:00 - 09:00", players: 74, spins: 7980 },
        { label: "09:00 - 10:00", players: 81, spins: 8240 },
        { label: "10:00 - 11:00", players: 88, spins: 8510 },
        { label: "11:00 - 12:00", players: 92, spins: 8760 },
        { label: "12:00 - 13:00", players: 436, spins: 8213 },
        { label: "13:00 - 14:00", players: 96, spins: 8890 },
        { label: "14:00 - 15:00", players: 91, spins: 8640 },
        { label: "15:00 - 16:00", players: 85, spins: 8390 },
        { label: "16:00 - 17:00", players: 79, spins: 8120 },
        { label: "17:00 - 18:00", players: 83, spins: 8280 },
        { label: "18:00 - 19:00", players: 90, spins: 8610 },
        { label: "19:00 - 20:00", players: 94, spins: 8840 },
        { label: "20:00 - 21:00", players: 87, spins: 8490 },
        { label: "21:00 - 22:00", players: 76, spins: 8010 },
        { label: "22:00 - 23:00", players: 64, spins: 7420 },
        { label: "23:00 - 24:00", players: 58, spins: 6980 },
      ],
      outcomes: [61.2, 18.4, 17.1, 3.0, 0.3],
    },
    "7d": {
      label: "Last 7 days",
      chartLabel: "Last 7 days",
      spins: 1184260,
      players: 4928,
      wagered: 1810740,
      returned: 1716763,
      net: -93977,
      returnRate: 0.9481,
      largest: "2,840× stake",
      dry: "27 spins",
      axis: ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun · window end"],
      values: [-18400, 6200, -14800, 3900, -22100, -16800, -31960],
      points: [
        { label: "Mon", players: 640, spins: 168420 },
        { label: "Tue", players: 712, spins: 174280 },
        { label: "Wed", players: 698, spins: 171540 },
        { label: "Thu", players: 734, spins: 176810 },
        { label: "Fri", players: 802, spins: 184620 },
        { label: "Sat", players: 846, spins: 189140 },
        { label: "Sun", players: 796, spins: 179450 },
      ],
      outcomes: [60.6, 18.2, 17.8, 3.1, 0.3],
    },
  };

  let activeTrackerRange = "24h";
  let activeChartRange = "24h";

  const formatCount = (value) =>
    Math.max(0, Math.round(value)).toLocaleString("en-US");
  const formatMoney = (value) =>
    `${value < 0 ? "−" : ""}$${Math.abs(Math.round(value)).toLocaleString("en-US")}`;

  const setTrackerPolarity = (element, value) => {
    if (!element) return;
    element.classList.toggle("is-negative", value < 0);
    element.classList.toggle("is-positive", value >= 0);
  };

  const updateOutcomeBreakdown = (outcomes) => {
    const keys = ["zero", "under", "small", "mid", "high"];
    keys.forEach((key, index) => {
      const value = outcomes[index];
      const label = document.getElementById(`outcome-${key}-value`);
      const bar = document.getElementById(`outcome-${key}-bar`);
      if (label) label.textContent = `${value.toFixed(1)}%`;
      if (bar) bar.style.width = `${Math.max(value, 0.5)}%`;
    });
  };

  const figmaChartTips = [
    { x: 59, y: 43, r: 7, value: 920, label: "00:00 - 03:00", players: 48, spins: 6820 },
    { x: 137, y: 150, r: 7, value: -1420, label: "03:00 - 05:00", players: 62, spins: 7240 },
    { x: 184, y: 119, r: 7, value: -840, label: "05:00 - 07:00", players: 71, spins: 7680 },
    { x: 231, y: 175, r: 7, value: -1580, label: "07:00 - 09:00", players: 68, spins: 7510 },
    { x: 282, y: 72, r: 7, value: 520, label: "09:00 - 10:00", players: 81, spins: 8120 },
    { x: 388, y: -1, r: 10, value: 13348, label: "10:00 - 12:00", players: 436, spins: 8213 },
    { x: 471, y: 137, r: 7, value: -1680, label: "12:00 - 15:00", players: 94, spins: 8640 },
    { x: 542, y: 51, r: 7, value: 820, label: "15:00 - 18:00", players: 102, spins: 8910 },
    { x: 611, y: 182, r: 8, value: -2100, label: "18:00 - 21:00", players: 88, spins: 8420 },
    { x: 749, y: 0, r: 7, value: 1480, label: "21:00 - Window end", players: 76, spins: 7980 },
  ];

  // Figma-style closed lobes: each tip is a smooth hill from the zero line.
  const buildFigmaLobes = (tips, zeroY, width) => {
    const lobes = [];
    tips.forEach((tip, index) => {
      if (!tip.value) return;
      const prev = tips[index - 1];
      const next = tips[index + 1];
      const leftX = prev ? (prev.x + tip.x) / 2 : 0;
      const rightX = next ? (tip.x + next.x) / 2 : width;
      const span = Math.max(rightX - leftX, 1);
      const overshoot = (tip.y - zeroY) * 0.12;
      const peakY = tip.y - overshoot;

      const d = [
        `M ${leftX} ${zeroY}`,
        `C ${leftX + span * 0.28} ${zeroY}, ${tip.x - span * 0.18} ${peakY}, ${tip.x} ${peakY}`,
        `C ${tip.x + span * 0.18} ${peakY}, ${rightX - span * 0.28} ${zeroY}, ${rightX} ${zeroY}`,
        "Z",
      ].join(" ");

      lobes.push({
        d,
        positive: tip.value >= 0,
        tip: { ...tip, y: peakY },
        leftX,
        rightX,
      });
    });
    return lobes;
  };

  const buildStrokeAlongLobes = (lobes, zeroY) => {
    if (!lobes.length) return "";
    let d = `M ${lobes[0].leftX} ${zeroY}`;
    lobes.forEach((lobe) => {
      const tip = lobe.tip;
      const span = Math.max(lobe.rightX - lobe.leftX, 1);
      d += ` C ${lobe.leftX + span * 0.28} ${zeroY}, ${tip.x - span * 0.18} ${tip.y}, ${tip.x} ${tip.y}`;
      d += ` C ${tip.x + span * 0.18} ${tip.y}, ${lobe.rightX - span * 0.28} ${zeroY}, ${lobe.rightX} ${zeroY}`;
    });
    return d;
  };

  const bindChartTooltip = (plot, tips, viewWidth, viewHeight) => {
    const canvas = plot.querySelector(".community-chart__canvas") || plot;
    const tooltip = plot.querySelector("#community-chart-tooltip");
    const tipTime = tooltip?.querySelector("[data-tip-time]");
    const tipValue = tooltip?.querySelector("[data-tip-value]");
    const tipValueText = tipValue?.querySelector("span");
    const tipMeta = tooltip?.querySelector("[data-tip-meta]");

    const showTip = (tip) => {
      if (!tooltip || !tipTime || !tipValue || !tipValueText || !tipMeta || !tip) return;
      const rect = canvas.getBoundingClientRect();
      const x = (tip.x / viewWidth) * rect.width;
      const y = (tip.y / viewHeight) * rect.height;
      tipTime.textContent = tip.label;
      tipValue.classList.toggle("is-positive", tip.value >= 0);
      tipValue.classList.toggle("is-negative", tip.value < 0);
      tipValueText.textContent = formatMoney(tip.value);
      tipMeta.textContent = `${formatCount(tip.players || 0)} players - ${formatCount(tip.spins || 0)} spins`;
      tooltip.style.left = `${x}px`;
      tooltip.style.top = `${y}px`;
      tooltip.hidden = false;
      tooltip.classList.add("is-visible");
      tooltip.setAttribute("aria-hidden", "false");
    };

    const hideTip = () => {
      if (!tooltip) return;
      tooltip.classList.remove("is-visible");
      tooltip.hidden = true;
      tooltip.setAttribute("aria-hidden", "true");
    };

    plot.querySelectorAll(".community-chart__hit").forEach((hit) => {
      const index = Number(hit.getAttribute("data-index"));
      const tip = tips[index];
      if (!tip) return;
      hit.setAttribute(
        "aria-label",
        `${tip.label}: ${formatMoney(tip.value)}, ${formatCount(tip.players || 0)} players, ${formatCount(tip.spins || 0)} spins`
      );
      hit.addEventListener("pointerenter", () => showTip(tip));
      hit.addEventListener("pointerleave", hideTip);
      hit.addEventListener("focus", () => showTip(tip));
      hit.addEventListener("blur", hideTip);
      hit.addEventListener("click", (event) => {
        event.preventDefault();
        showTip(tip);
      });
    });

    plot.addEventListener("pointerleave", hideTip);
  };

  const tooltipMarkup = `
      <div class="community-chart__tooltip" id="community-chart-tooltip" hidden aria-hidden="true">
        <div class="community-chart__tooltip-time">
          <svg viewBox="0 0 15 15" fill="none" aria-hidden="true"><circle cx="7.5" cy="7.5" r="5.75" stroke="#e5e5ef" stroke-width="1.2"/><path d="M7.5 4.5V7.5L9.4 8.7" stroke="#e5e5ef" stroke-width="1.2" stroke-linecap="round"/></svg>
          <span data-tip-time></span>
        </div>
        <div class="community-chart__tooltip-value" data-tip-value><i></i><span></span></div>
        <div class="community-chart__tooltip-meta" data-tip-meta></div>
      </div>`;

  const renderFigmaChart = () => {
    if (!trackerChartPlot) return;
    const viewWidth = 791;
    const viewHeight = 186;
    const hits = figmaChartTips
      .map((tip, index) => {
        const polarity = tip.value >= 0 ? "is-positive" : "is-negative";
        const size = (tip.r || 7) >= 10 ? " is-lg" : "";
        return `<button type="button" class="community-chart__hit ${polarity}${size}" data-index="${index}" style="left:${(tip.x / viewWidth) * 100}%;top:${(tip.y / viewHeight) * 100}%"></button>`;
      })
      .join("");

    trackerChartPlot.innerHTML = `
      <div class="community-chart__canvas">
        ${tooltipMarkup}
        <svg class="community-chart__visual" viewBox="0 0 791 186" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" aria-hidden="true">
        <line x1="0" y1="9" x2="791" y2="9" stroke="rgba(19,15,38,0.08)" />
        <line x1="0" y1="51" x2="791" y2="51" stroke="rgba(19,15,38,0.08)" />
        <line x1="0" y1="135" x2="791" y2="135" stroke="rgba(19,15,38,0.08)" />
        <line x1="0" y1="177" x2="791" y2="177" stroke="rgba(19,15,38,0.08)" />
        <path d="M98.3735 89.2328C90.1365 63.5806 80.2455 40.4745 57.1231 44.7328C29.3499 49.8475 22.383 71.8544 0.123075 89.2328H98.3735Z" fill="url(#chart-g0)"/>
        <path d="M262.465 89.2C250.618 119.44 261.289 182.57 228.623 174.235C201.556 167.329 212.017 116.757 184.123 118.235C164.661 119.266 163.178 142.775 144.623 148.735C116.474 157.778 108.731 121.493 98.3731 89.2352L262.465 89.2Z" fill="url(#chart-o1)"/>
        <path d="M320.625 89.2361C304.994 88.1883 299.25 74.8713 283.625 73.7361C272.626 72.9371 266.342 79.3095 262.467 89.2009L320.625 89.2361Z" fill="url(#chart-g2)"/>
        <path d="M381.123 1.73518C417.186 -9.7654 423.416 45.7128 434.623 89.2352H324.786C323.472 89.3311 322.086 89.3333 320.623 89.2352H324.786C360.699 86.6128 342.94 13.9117 381.123 1.73518Z" fill="url(#chart-g3)"/>
        <path d="M507.81 89.239C502.155 108.086 498.141 128.549 478.123 135.739C452.948 144.781 442.543 119.995 434.623 89.239H507.81Z" fill="url(#chart-o4)"/>
        <path d="M570.482 89.2384C564.749 65.4288 555.733 48.163 535.124 54.2384C518.271 59.2063 512.481 73.6706 507.811 89.2384H570.482Z" fill="url(#chart-g5)"/>
        <path d="M670.624 89.239C628.863 96.618 657.612 171.121 617.124 183.739C580.489 195.156 580.721 131.762 570.482 89.239H670.624Z" fill="url(#chart-o6)"/>
        <path d="M790.623 89.2372C790.969 63.3453 774.584 -4.57679 747.623 1.73716C713.623 9.69959 734.973 89.2305 695.123 89.2372H790.623Z" fill="url(#chart-g7)"/>
        <path d="M0.123075 89.2352C22.383 71.8568 29.3499 49.8499 57.1231 44.7352C109.322 35.122 94.0892 164.968 144.623 148.735C163.178 142.775 164.661 119.266 184.123 118.235C212.017 116.757 201.557 167.329 228.623 174.235C271.975 185.297 239 70.4933 283.623 73.7352C299.248 74.8703 304.992 88.1873 320.623 89.2352C362.074 92.0139 341.543 14.3572 381.123 1.73518C442.671 -17.8926 417.324 157.572 478.123 135.735C514.676 122.607 497.869 65.2173 535.123 54.2352C592.539 37.3097 559.975 201.545 617.123 183.735C655.123 171.893 632.132 91.2952 670.623 89.2352C680.623 88.7 686.623 89.2352 695.123 89.2352C734.264 89.2352 715.123 7.69998 747.623 1.73518C776.123 -3.49549 790.969 63.8081 790.623 89.7" stroke="#73777C" stroke-width="0.4"/>
        <line x1="0.623" y1="89.7" x2="789.623" y2="89.7" stroke="#73777C" stroke-linecap="round"/>
        <defs>
          <linearGradient id="chart-g0" x1="49.25" y1="44.22" x2="49.25" y2="89.23" gradientUnits="userSpaceOnUse"><stop stop-color="#50A47E"/><stop offset="1" stop-color="#68D9A7"/></linearGradient>
          <linearGradient id="chart-o1" x1="180.42" y1="89.2" x2="180.42" y2="174.99" gradientUnits="userSpaceOnUse"><stop stop-color="#FFD6BD"/><stop offset="1" stop-color="#FF5E07"/></linearGradient>
          <linearGradient id="chart-g2" x1="291.55" y1="73.67" x2="291.55" y2="89.24" gradientUnits="userSpaceOnUse"><stop stop-color="#50A47E"/><stop offset="1" stop-color="#68D9A7"/></linearGradient>
          <linearGradient id="chart-g3" x1="377.62" y1="0.2" x2="377.62" y2="89.31" gradientUnits="userSpaceOnUse"><stop stop-color="#50A47E"/><stop offset="1" stop-color="#68D9A7"/></linearGradient>
          <linearGradient id="chart-o4" x1="471.22" y1="89.24" x2="471.22" y2="137.62" gradientUnits="userSpaceOnUse"><stop stop-color="#FFD6BD"/><stop offset="1" stop-color="#FF5E07"/></linearGradient>
          <linearGradient id="chart-g5" x1="539.15" y1="53.01" x2="539.15" y2="89.24" gradientUnits="userSpaceOnUse"><stop stop-color="#50A47E"/><stop offset="1" stop-color="#68D9A7"/></linearGradient>
          <linearGradient id="chart-o6" x1="620.55" y1="89.24" x2="620.55" y2="185.09" gradientUnits="userSpaceOnUse"><stop stop-color="#FFD6BD"/><stop offset="1" stop-color="#FF5E07"/></linearGradient>
          <linearGradient id="chart-g7" x1="730.63" y1="1.09" x2="730.63" y2="89.23" gradientUnits="userSpaceOnUse"><stop stop-color="#50A47E"/><stop offset="1" stop-color="#68D9A7"/></linearGradient>
        </defs>
        </svg>
        ${hits}
      </div>
    `;
    bindChartTooltip(trackerChartPlot, figmaChartTips, viewWidth, viewHeight);
  };

  const renderTrackerChart = (data) => {
    if (!trackerChartPlot) return;

    if (activeChartRange === "24h") {
      renderFigmaChart();
      return;
    }

    const values = data.values || [];
    const width = 791;
    const height = 186;
    const padX = 18;
    const maxValue = Math.max(...values.map((value) => Math.abs(value)), 1);
    const zeroY = height / 2;
    const step = values.length > 1 ? (width - padX * 2) / (values.length - 1) : 0;

    const tipPoints = values.map((value, index) => {
      const x = padX + index * step;
      const y = zeroY - (value / maxValue) * (height * 0.42);
      const meta = (data.points && data.points[index]) || {};
      return {
        x,
        y,
        value,
        index,
        r: Math.abs(value) === maxValue ? 10 : 7,
        label: meta.label || `Interval ${index + 1}`,
        players: meta.players || 0,
        spins: meta.spins || 0,
      };
    });

    const lobes = buildFigmaLobes(tipPoints, zeroY, width);
    const tips = lobes.map((lobe) => lobe.tip);
    const lobeMarkup = lobes
      .map((lobe, index) => {
        const gradId = lobe.positive ? `lobe-pos-${index}` : `lobe-neg-${index}`;
        return `<path d="${lobe.d}" fill="url(#${gradId})"></path>`;
      })
      .join("");
    const gradients = lobes
      .map((lobe, index) => {
        if (lobe.positive) {
          return `<linearGradient id="lobe-pos-${index}" x1="${lobe.tip.x}" y1="${lobe.tip.y}" x2="${lobe.tip.x}" y2="${zeroY}" gradientUnits="userSpaceOnUse"><stop stop-color="#50A47E"/><stop offset="1" stop-color="#68D9A7"/></linearGradient>`;
        }
        return `<linearGradient id="lobe-neg-${index}" x1="${lobe.tip.x}" y1="${zeroY}" x2="${lobe.tip.x}" y2="${lobe.tip.y}" gradientUnits="userSpaceOnUse"><stop stop-color="#FFD6BD"/><stop offset="1" stop-color="#FF5E07"/></linearGradient>`;
      })
      .join("");
    const linePath = buildStrokeAlongLobes(lobes, zeroY);
    const hitButtons = tips
      .map((tip, index) => {
        const polarity = tip.value >= 0 ? "is-positive" : "is-negative";
        const size = (tip.r || 7) >= 10 ? " is-lg" : "";
        return `<button type="button" class="community-chart__hit ${polarity}${size}" data-index="${index}" style="left:${(tip.x / width) * 100}%;top:${(tip.y / height) * 100}%"></button>`;
      })
      .join("");

    trackerChartPlot.innerHTML = `
      <div class="community-chart__canvas">
        ${tooltipMarkup}
        <svg class="community-chart__visual" viewBox="0 0 ${width} ${height}" preserveAspectRatio="none" role="presentation">
          <defs>${gradients}</defs>
          <line x1="0" y1="9" x2="${width}" y2="9" stroke="rgba(19,15,38,0.08)" />
          <line x1="0" y1="51" x2="${width}" y2="51" stroke="rgba(19,15,38,0.08)" />
          <line x1="0" y1="135" x2="${width}" y2="135" stroke="rgba(19,15,38,0.08)" />
          <line x1="0" y1="177" x2="${width}" y2="177" stroke="rgba(19,15,38,0.08)" />
          ${lobeMarkup}
          <path d="${linePath}" fill="none" stroke="#73777C" stroke-width="0.4"></path>
          <line x1="0" y1="${zeroY}" x2="${width}" y2="${zeroY}" stroke="#73777C" stroke-linecap="round" />
        </svg>
        ${hitButtons}
      </div>
    `;
    bindChartTooltip(trackerChartPlot, tips, width, height);
  };

  const renderCommunityTracker = () => {
    const snapshotStats = document.getElementById("observed-snapshot-stats");
    const day = trackerData["24h"];
    if (snapshotStats && day) {
      snapshotStats.textContent = `${formatCount(day.spins)} spins, ${formatCount(day.players)} players, observed return ${(day.returnRate * 100).toFixed(2)}%`;
    }

    const kpiData = trackerData[activeTrackerRange];
    const chartData = trackerData[activeChartRange] || kpiData;
    if (!kpiData || !chartData) return;

    const windowLabel = document.getElementById("tracker-window-label");
    const windowPlayers = document.getElementById("tracker-window-players");
    const i18n = window.SlotPage?.i18n || {};
    const windowPhrase = {
      "1h": i18n.window1h || "1 hour",
      "24h": i18n.window24h || "24 hours",
      "7d": i18n.window7d || "7 days",
    }[activeTrackerRange] || kpiData.label.toLowerCase();
    if (windowLabel) {
      windowLabel.textContent = (i18n.liveWindow || "Live sample window · last :window").replace(
        ":window",
        windowPhrase
      );
    }
    if (windowPlayers) {
      windowPlayers.textContent = (i18n.playersLine || ":count opted-in players · anonymized aggregate").replace(
        ":count",
        formatCount(kpiData.players)
      );
    }

    if (trackerFields.spins) trackerFields.spins.textContent = formatCount(kpiData.spins);
    if (trackerFields.players) trackerFields.players.textContent = formatCount(kpiData.players);
    if (trackerFields.wagered) trackerFields.wagered.textContent = formatMoney(kpiData.wagered);
    if (trackerFields.returned) trackerFields.returned.textContent = formatMoney(kpiData.returned);
    if (trackerFields.net) trackerFields.net.textContent = formatMoney(kpiData.net);
    if (trackerFields.returnRate) {
      trackerFields.returnRate.textContent = `${(kpiData.returnRate * 100).toFixed(2)}%`;
    }
    if (trackerFields.pulseNet) trackerFields.pulseNet.textContent = formatMoney(chartData.net);
    if (trackerFields.pulseLabel) {
      trackerFields.pulseLabel.textContent =
        chartData.net < 0 ? "More was wagered than returned" : "More was returned than wagered";
    }
    if (trackerFields.pulseSpins) trackerFields.pulseSpins.textContent = formatCount(chartData.spins);
    if (trackerFields.pulseLargest) trackerFields.pulseLargest.textContent = chartData.largest;
    if (trackerFields.pulseDry) trackerFields.pulseDry.textContent = chartData.dry;
    if (trackerFields.pulseUsers) trackerFields.pulseUsers.textContent = formatCount(chartData.players);

    setTrackerPolarity(trackerNetCard, kpiData.net);
    setTrackerPolarity(trackerPulseCard, chartData.net);

    if (trackerChartTitle) {
      trackerChartTitle.textContent = `Returns minus wagers — ${chartData.chartLabel || chartData.label}`;
    }
    if (trackerChartAxis) {
      trackerChartAxis.innerHTML = chartData.axis.map((label) => `<span>${label}</span>`).join("");
    }
    renderTrackerChart(chartData);
    updateOutcomeBreakdown(kpiData.outcomes);
  };

  trackerRangeButtons.forEach((button) => {
    button.addEventListener("click", () => {
      activeTrackerRange = button.dataset.liveRange || "24h";
      trackerRangeButtons.forEach((item) => {
        const isActive = item === button;
        item.classList.toggle("is-active", isActive);
        item.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
      if (activeTrackerRange === "7d") {
        activeChartRange = "7d";
        chartRangeButtons.forEach((item) => {
          item.classList.remove("is-active");
          item.setAttribute("aria-pressed", "false");
        });
      } else if (["1h", "24h"].includes(activeTrackerRange)) {
        activeChartRange = activeTrackerRange;
        chartRangeButtons.forEach((item) => {
          const isActive = item.dataset.chartRange === activeChartRange;
          item.classList.toggle("is-active", isActive);
          item.setAttribute("aria-pressed", isActive ? "true" : "false");
        });
      }
      renderCommunityTracker();
    });
  });

  chartRangeButtons.forEach((button) => {
    button.addEventListener("click", () => {
      activeChartRange = button.dataset.chartRange || "24h";
      chartRangeButtons.forEach((item) => {
        const isActive = item === button;
        item.classList.toggle("is-active", isActive);
        item.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
      renderCommunityTracker();
    });
  });

  renderCommunityTracker();

  const ratingFilterButtons = Array.from(
    document.querySelectorAll("[data-rating-filter]")
  ).filter((button) => !button.closest("#player-reviews"));
  const playerReports = Array.from(
    document.querySelectorAll(
      ".reader-review[data-rating-source]"
    )
  ).filter((report) => !report.closest("#player-reviews"));

  ratingFilterButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const filter = button.dataset.ratingFilter || "all";

      ratingFilterButtons.forEach((item) => {
        const isActive = item === button;
        item.classList.toggle("is-active", isActive);
        item.setAttribute("aria-pressed", isActive ? "true" : "false");
      });

      playerReports.forEach((report) => {
        const tags = (report.dataset.ratingSource || "").split(/\s+/);
        report.hidden = filter !== "all" && !tags.includes(filter);
      });
    });
  });

  /* ===== Image lightbox ===== */
  const imageLightbox = document.getElementById("image-lightbox");
  const imageLightboxImg = document.getElementById("image-lightbox-img");
  let imageLightboxLastFocus = null;
  let imageLightboxClosing = false;

  const openImageLightbox = (trigger) => {
    if (!imageLightbox || !imageLightboxImg || !trigger) return;
    const thumb = trigger.querySelector("img");
    if (!thumb) return;

    imageLightboxLastFocus = trigger;
    closeProfileMenu();
    closeMobileDrawer();
    if (rankModal && rankModal.classList.contains("is-open")) {
      closeRankModal();
    }

    imageLightboxImg.src = thumb.currentSrc || thumb.src;
    imageLightboxImg.alt = thumb.alt || "";

    const scrollbarWidth = getScrollbarWidth();
    imageLightboxClosing = false;
    imageLightbox.hidden = false;
    document.body.style.paddingRight = scrollbarWidth
      ? `${scrollbarWidth}px`
      : "";
    document.body.classList.add("image-lightbox-open");
    window.requestAnimationFrame(() => {
      window.requestAnimationFrame(() => {
        imageLightbox.classList.add("is-open");
      });
    });

    const closeBtn = imageLightbox.querySelector(".image-lightbox__close");
    if (closeBtn) closeBtn.focus();
  };

  const closeImageLightbox = () => {
    if (!imageLightbox || imageLightboxClosing) return;
    if (imageLightbox.hidden && !imageLightbox.classList.contains("is-open")) {
      return;
    }

    imageLightboxClosing = true;
    imageLightbox.classList.remove("is-open");

    const finishClose = () => {
      if (!imageLightboxClosing) return;
      imageLightbox.hidden = true;
      document.body.classList.remove("image-lightbox-open");
      document.body.style.paddingRight = "";
      if (imageLightboxImg) {
        imageLightboxImg.removeAttribute("src");
        imageLightboxImg.alt = "";
      }
      imageLightboxClosing = false;
      if (
        imageLightboxLastFocus &&
        typeof imageLightboxLastFocus.focus === "function"
      ) {
        imageLightboxLastFocus.focus();
      }
    };

    window.setTimeout(finishClose, 280);
  };

  document.querySelectorAll(".js-open-lightbox").forEach((btn) => {
    btn.addEventListener("click", () => openImageLightbox(btn));
  });

  document.querySelectorAll(".js-close-lightbox").forEach((el) => {
    el.addEventListener("click", closeImageLightbox);
  });

  /* ===== Newsletter form ===== */
  const form = document.getElementById("newsletter-form");
  const emailInput = document.getElementById("newsletter-email");
  const over18 = document.getElementById("check-over18");
  const updatesConsent = document.getElementById("check-updates");
  const messageEl = document.getElementById("newsletter-message");
  const inputWrap = form ? form.querySelector(".footer__input-wrap") : null;

  if (form && emailInput && over18 && updatesConsent && messageEl) {
    const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    const showMessage = (text, type) => {
      messageEl.hidden = false;
      messageEl.textContent = text;
      messageEl.classList.remove("is-error", "is-success");
      messageEl.classList.add(type === "success" ? "is-success" : "is-error");
    };

    const clearMessage = () => {
      messageEl.hidden = true;
      messageEl.textContent = "";
      messageEl.classList.remove("is-error", "is-success");
      if (inputWrap) {
        inputWrap.classList.remove("is-invalid");
      }
    };

    emailInput.addEventListener("input", clearMessage);
    over18.addEventListener("change", clearMessage);
    updatesConsent.addEventListener("change", clearMessage);

    form.addEventListener("submit", (event) => {
      event.preventDefault();
      clearMessage();

      const email = emailInput.value.trim();

      if (!over18.checked) {
        showMessage("Please confirm that you are over 18.", "error");
        over18.focus();
        return;
      }

      if (!updatesConsent.checked) {
        showMessage("Please consent to receive editorial updates.", "error");
        updatesConsent.focus();
        return;
      }

      if (!email) {
        if (inputWrap) inputWrap.classList.add("is-invalid");
        showMessage("Please enter your e-mail address.", "error");
        emailInput.focus();
        return;
      }

      if (!EMAIL_RE.test(email)) {
        if (inputWrap) inputWrap.classList.add("is-invalid");
        showMessage("Please enter a valid e-mail address.", "error");
        emailInput.focus();
        return;
      }

      showMessage(
        "Thanks — you are subscribed to editorial updates.",
        "success"
      );
      form.reset();
    });
  }

  /* ===== TOC active section ===== */
  const tocLinks = Array.from(
    document.querySelectorAll(".toc__link, .toc-mob__link, .fs-toc__link")
  );
  const tocSections = Array.from(
    document.querySelectorAll("[data-toc-section][id]")
  );
  const tocIds = new Set(
    tocLinks
      .map((link) => link.getAttribute("href"))
      .filter((href) => href && href.charAt(0) === "#")
      .map((href) => href.slice(1))
  );

  // Sections without a TOC link inherit the previous TOC item
  // (e.g. #comparison / #mobile / #review-verdict).
  const sectionToTocId = new Map();
  let lastTocId = null;
  tocSections.forEach((section) => {
    if (tocIds.has(section.id)) {
      lastTocId = section.id;
    }
    if (lastTocId) {
      sectionToTocId.set(section.id, lastTocId);
    }
  });

  const getHeaderOffset = () => {
    const raw = getComputedStyle(document.documentElement).getPropertyValue(
      "--header-height"
    );
    const height = parseInt(raw, 10);
    return (Number.isFinite(height) ? height : 100) + 24;
  };

  let activeTocId = null;
  let tocClickLockUntil = 0;

  const setActiveToc = (id) => {
    const resolved = sectionToTocId.get(id) || (tocIds.has(id) ? id : null);
    if (!resolved || resolved === activeTocId) return;
    activeTocId = resolved;

    tocLinks.forEach((link) => {
      const isActive = link.getAttribute("href") === `#${resolved}`;
      link.classList.toggle(
        "toc__link--active",
        isActive && link.classList.contains("toc__link")
      );
      link.classList.toggle(
        "toc-mob__link--active",
        isActive && link.classList.contains("toc-mob__link")
      );
      link.classList.toggle(
        "fs-toc__link--active",
        isActive && link.classList.contains("fs-toc__link")
      );
    });
  };

  const pickActiveSection = (intersecting) => {
    if (!intersecting.size) return null;

    const offset = getHeaderOffset();
    let best = null;
    let bestDistance = Infinity;

    intersecting.forEach((entry) => {
      const top = entry.boundingClientRect.top;
      const distance = Math.abs(top - offset);
      // Prefer sections that have crossed / sit near the sticky header line.
      const score = top <= offset + 8 ? distance : distance + 1000;
      if (score < bestDistance) {
        bestDistance = score;
        best = entry.target.id;
      }
    });

    return best;
  };

  if (tocLinks.length && tocSections.length) {
    const intersecting = new Map();

    const syncActiveFromObserver = () => {
      if (Date.now() < tocClickLockUntil) return;
      const id = pickActiveSection(intersecting);
      if (id) setActiveToc(id);
    };

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            intersecting.set(entry.target.id, entry);
          } else {
            intersecting.delete(entry.target.id);
          }
        });
        syncActiveFromObserver();
      },
      {
        rootMargin: `-${getHeaderOffset()}px 0px -45% 0px`,
        threshold: [0, 0.1, 0.25, 0.5, 0.75, 1],
      }
    );

    tocSections.forEach((section) => observer.observe(section));
    setActiveToc(tocSections[0].id);

    tocLinks.forEach((link) => {
      link.addEventListener("click", (event) => {
        const href = link.getAttribute("href");
        if (!href || href.charAt(0) !== "#") return;
        const target = document.getElementById(href.slice(1));
        if (!target) return;

        event.preventDefault();
        const reduceMotion = window.matchMedia(
          "(prefers-reduced-motion: reduce)"
        ).matches;
        const behavior = reduceMotion ? "auto" : "smooth";

        tocClickLockUntil = Date.now() + (reduceMotion ? 50 : 700);
        setActiveToc(target.id);
        target.scrollIntoView({ behavior, block: "start" });
        history.pushState(null, "", href);

        if (link.classList.contains("toc-mob__link")) {
          link.scrollIntoView({
            behavior,
            inline: "center",
            block: "nearest",
          });
        }
      });
    });
  }

  /* ===== Screenshots galleries ===== */
  const initShotsGallery = (sectionSelector, mainId) => {
    const shotsMain = document.getElementById(mainId);
    const shotThumbs = Array.from(
      document.querySelectorAll(`${sectionSelector} .shots__thumb`)
    );

    if (!shotsMain || !shotThumbs.length) return;

    shotThumbs.forEach((thumb) => {
      thumb.addEventListener("click", () => {
        const src = thumb.getAttribute("data-shot");
        const alt = thumb.getAttribute("data-alt") || "";
        if (!src) return;

        shotsMain.src = src;
        shotsMain.alt = alt;

        shotThumbs.forEach((item) => {
          const active = item === thumb;
          item.classList.toggle("is-active", active);
          item.setAttribute("aria-pressed", active ? "true" : "false");
        });
      });
    });
  };

  initShotsGallery("#screenshots", "shots-main");
  initShotsGallery("#mobile", "mobile-shots-main");

  /* ===== Similar slots carousel (Swiper) ===== */
  const similarSwiperEl = document.getElementById("similar-swiper");
  const similarCarousel = similarSwiperEl
    ? similarSwiperEl.closest(".similar__carousel")
    : null;

  if (similarSwiperEl && similarCarousel && typeof Swiper !== "undefined") {
    const modules =
      typeof SwiperModules !== "undefined" && SwiperModules.Navigation
        ? [SwiperModules.Navigation]
        : [];

    new Swiper(similarSwiperEl, {
      modules,
      slidesPerView: "auto",
      spaceBetween: 20,
      slidesPerGroup: 1,
      watchOverflow: true,
      grabCursor: true,
      speed: 420,
      navigation: {
        prevEl: similarCarousel.querySelector("[data-similar-prev]"),
        nextEl: similarCarousel.querySelector("[data-similar-next]"),
      },
    });
  }

  /* ===== Free Slots: Popular carousel (Swiper) ===== */
  const popularSwiperEl = document.getElementById("popular-swiper");
  const popularCarousel = popularSwiperEl
    ? popularSwiperEl.closest(".popular-carousel")
    : null;

  if (popularSwiperEl && popularCarousel && typeof Swiper !== "undefined") {
    const modules =
      typeof SwiperModules !== "undefined" && SwiperModules.Navigation
        ? [SwiperModules.Navigation]
        : [];

    new Swiper(popularSwiperEl, {
      modules,
      slidesPerView: "auto",
      spaceBetween: 22,
      slidesPerGroup: 1,
      watchOverflow: true,
      grabCursor: true,
      speed: 420,
      navigation: {
        prevEl: popularCarousel.querySelector("[data-popular-prev]"),
        nextEl: popularCarousel.querySelector("[data-popular-next]"),
      },
    });
  }

  /* ===== Casino offers carousel (Swiper) ===== */
  const casinoOffersSwiperEl = document.getElementById("casino-offers-swiper");

  if (casinoOffersSwiperEl && typeof Swiper !== "undefined") {
    new Swiper(casinoOffersSwiperEl, {
      slidesPerView: "auto",
      spaceBetween: 8,
      slidesPerGroup: 1,
      watchOverflow: true,
      grabCursor: true,
      speed: 420,
      resistanceRatio: 0.65,
      breakpoints: {
        961: {
          slidesPerView: 4,
          spaceBetween: 20,
          slidesPerGroup: 1,
        },
      },
    });
  }

  /* ===== Home tournaments carousel ===== */
  const tournamentsSwiperEl = document.getElementById("tournaments-swiper");
  const tournamentsCarousel = tournamentsSwiperEl
    ? tournamentsSwiperEl.closest(".home-tournaments__carousel")
    : null;

  if (tournamentsSwiperEl && tournamentsCarousel && typeof Swiper !== "undefined") {
    const modules = [];
    if (typeof SwiperModules !== "undefined") {
      if (SwiperModules.Navigation) modules.push(SwiperModules.Navigation);
      if (SwiperModules.Pagination) modules.push(SwiperModules.Pagination);
    }

    new Swiper(tournamentsSwiperEl, {
      modules,
      slidesPerView: 1,
      spaceBetween: 14,
      watchOverflow: true,
      grabCursor: true,
      speed: 420,
      navigation: {
        prevEl: tournamentsCarousel.querySelector("[data-tour-prev]"),
        nextEl: tournamentsCarousel.querySelector("[data-tour-next]"),
      },
      pagination: {
        el: tournamentsCarousel.querySelector("[data-tour-pagination]"),
        clickable: true,
      },
      breakpoints: {
        961: {
          slidesPerView: 3,
          spaceBetween: 22,
          allowTouchMove: false,
          grabCursor: false,
        },
      },
    });
  }

  /* ===== FAQ accordion (WAAPI height — smooth on <details>) ===== */
  const faqItems = Array.from(document.querySelectorAll(".faq__item"));
  const FAQ_MS = 480;
  const FAQ_EASE = "cubic-bezier(0.32, 0.72, 0, 1)";

  const prefersReducedMotion = () =>
    window.matchMedia("(prefers-reduced-motion: reduce)").matches;

  const stopFaqAnim = (panel) => {
    const current = panel.getAnimations?.() ?? [];
    current.forEach((anim) => anim.cancel());
  };

  const animateFaqHeight = (panel, from, to) => {
    stopFaqAnim(panel);
    panel.style.height = `${from}px`;
    panel.style.overflow = "hidden";

    const anim = panel.animate(
      [{ height: `${from}px` }, { height: `${to}px` }],
      {
        duration: FAQ_MS,
        easing: FAQ_EASE,
        fill: "forwards",
      }
    );

    return anim.finished.then(() => {
      anim.cancel();
      if (to === 0) {
        panel.style.height = "0px";
      } else {
        panel.style.height = "auto";
      }
    });
  };

  faqItems.forEach((item) => {
    const summary = item.querySelector(".faq__summary");
    const panel = item.querySelector(".faq__panel");
    if (!summary || !panel) return;

    panel.style.height = item.open ? "auto" : "0px";
    panel.style.overflow = "hidden";

    summary.addEventListener("click", (event) => {
      event.preventDefault();
      if (item.dataset.animating === "true") return;

      const reduced = prefersReducedMotion();
      const isOpen = item.hasAttribute("open");

      if (reduced) {
        stopFaqAnim(panel);
        if (isOpen) {
          item.removeAttribute("open");
          panel.style.height = "0px";
        } else {
          item.setAttribute("open", "");
          panel.style.height = "auto";
        }
        return;
      }

      item.dataset.animating = "true";

      if (isOpen) {
        const from = panel.getBoundingClientRect().height || panel.scrollHeight;
        animateFaqHeight(panel, from, 0).then(() => {
          item.removeAttribute("open");
          item.dataset.animating = "false";
        });
        return;
      }

      item.setAttribute("open", "");
      panel.style.height = "0px";
      void panel.offsetHeight;
      const to = panel.scrollHeight;
      animateFaqHeight(panel, 0, to).then(() => {
        item.dataset.animating = "false";
      });
    });
  });

  /* ===== Slot info expand / collapse ===== */
  const slotInfo = document.getElementById("game-info");
  const slotInfoToggle = document.querySelector(".js-slot-info-toggle");

  if (slotInfo && slotInfoToggle) {
    const slotInfoLabel = slotInfoToggle.querySelector(".slot-info__toggle-label");

    slotInfoToggle.addEventListener("click", () => {
      const expanded = slotInfo.classList.toggle("is-expanded");
      slotInfoToggle.setAttribute("aria-expanded", expanded ? "true" : "false");
      if (slotInfoLabel) {
        slotInfoLabel.textContent = expanded
          ? "Show less"
          : "Show all game details";
      }
    });
  }

  const enableSwipe = (scroller) => {
    let pointerId = null;
    let startX = 0;
    let startY = 0;
    let startScroll = 0;
    let dragging = false;
    let suppressClick = false;

    const nearestLeft = () => {
      const edge = scroller.getBoundingClientRect().left;
      let left = scroller.scrollLeft;
      let best = Infinity;
      [...scroller.children].forEach((slide) => {
        if (slide.nodeType !== 1) return;
        const delta = slide.getBoundingClientRect().left - edge;
        if (Math.abs(delta) < best) {
          best = Math.abs(delta);
          left = scroller.scrollLeft + delta;
        }
      });
      return left;
    };

    const finish = (event) => {
      if (event.pointerId !== pointerId) return;
      pointerId = null;
      scroller.classList.remove("is-dragging");
      if (!dragging) return;
      dragging = false;
      suppressClick = true;
      scroller.scrollTo({ left: nearestLeft(), behavior: "smooth" });
    };

    scroller.addEventListener("pointerdown", (event) => {
      if (event.pointerType === "mouse" && event.button !== 0) return;
      pointerId = event.pointerId;
      startX = event.clientX;
      startY = event.clientY;
      startScroll = scroller.scrollLeft;
      dragging = false;
    });

    scroller.addEventListener("pointermove", (event) => {
      if (event.pointerId !== pointerId) return;
      const dx = event.clientX - startX;
      const dy = event.clientY - startY;
      if (!dragging) {
        if (Math.abs(dx) < 8 && Math.abs(dy) < 8) return;
        if (Math.abs(dy) > Math.abs(dx)) {
          pointerId = null;
          return;
        }
        dragging = true;
        scroller.classList.add("is-dragging");
        try {
          scroller.setPointerCapture(event.pointerId);
        } catch (err) {}
      }
      scroller.scrollLeft = startScroll - dx;
    });

    scroller.addEventListener("pointerup", finish);
    scroller.addEventListener("pointercancel", finish);
    scroller.addEventListener("click", (event) => {
      if (!suppressClick) return;
      suppressClick = false;
      event.preventDefault();
      event.stopPropagation();
    }, true);
  };

  const setupSnapDots = (selector) => {
    if (!window.matchMedia("(max-width: 960px)").matches) return;

    document.querySelectorAll(selector).forEach((scroller) => {
      const slides = [...scroller.children].filter((el) => el.nodeType === 1);
      if (slides.length < 2) return;

      enableSwipe(scroller);

      const dots = document.createElement("div");
      dots.className = "snap-dots";
      const buttons = slides.map((slide, index) => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "snap-dots__dot";
        button.setAttribute("aria-label", `Slide ${index + 1}`);
        button.addEventListener("click", () => {
          scroller.scrollTo({ left: slide.offsetLeft, behavior: "smooth" });
        });
        dots.append(button);
        return button;
      });

      scroller.after(dots);

      const mark = () => {
        const edge = scroller.getBoundingClientRect().left;
        let active = 0;
        let best = Infinity;
        slides.forEach((slide, index) => {
          const delta = Math.abs(slide.getBoundingClientRect().left - edge);
          if (delta < best) {
            best = delta;
            active = index;
          }
        });
        buttons.forEach((button, index) => {
          button.classList.toggle("is-active", index === active);
        });
      };

      scroller.addEventListener("scroll", mark, { passive: true });
      mark();
    });
  };

  setupSnapDots(".home-streamers__row");
  setupSnapDots(".news-grid");

  if (window.matchMedia("(max-width: 960px)").matches) {
    document.querySelectorAll(".home-big-wins__grid").forEach(enableSwipe);

    const providers = document.querySelector(".home-providers__logos");
    const providersNext = document.querySelector("[data-providers-next]");
    if (providers && providers.children.length > 1) {
      enableSwipe(providers);
      const updateProvidersNext = () => {
        if (!providersNext) return;
        const max = providers.scrollWidth - providers.clientWidth - 4;
        providersNext.hidden = providers.scrollLeft >= max;
      };
      providersNext?.addEventListener("click", () => {
        const card = providers.querySelector(".provider-card");
        const step = card ? card.getBoundingClientRect().width + 23 : 276;
        providers.scrollBy({ left: step, behavior: "smooth" });
      });
      providers.addEventListener("scroll", updateProvidersNext, { passive: true });
      updateProvidersNext();
    }
  }

  /* ===== page-about reveal (mobile < 768px) ===== */
  const PAGE_ABOUT_REVEAL_SEL = [
    ".page-about__title",
    ".page-about__lead",
    ".page-about__text",
    ".page-about__subtitle",
    ".page-about__banner",
    ".page-about__hero",
    ".page-about__promo",
    ".page-about__tip",
    ".page-about__card",
    ".page-about__checks",
    ".page-about__steps",
    ".page-about__pills",
    ".page-about__guide-card",
    ".page-about__guides-all",
    ".about-feature",
    ".about-article",
  ].join(", ");

  const initPageAboutReveal = () => {
    const mq = window.matchMedia("(max-width: 767.98px)");
    const sections = Array.from(document.querySelectorAll(".page-about"));
    if (!sections.length) return;

    let observer = null;

    const clearReveal = () => {
      observer?.disconnect();
      observer = null;
      sections.forEach((section) => {
        section.querySelectorAll("[data-about-reveal]").forEach((el) => {
          el.removeAttribute("data-about-reveal");
          el.removeAttribute("data-about-delay");
          el.classList.remove("is-inview");
        });
      });
    };

    const setupReveal = () => {
      clearReveal();
      if (!mq.matches || prefersReducedMotion()) return;

      const targets = [];
      sections.forEach((section) => {
        const items = Array.from(section.querySelectorAll(PAGE_ABOUT_REVEAL_SEL));
        items.forEach((el, index) => {
          el.setAttribute("data-about-reveal", "");
          el.setAttribute("data-about-delay", String(Math.min(index % 5, 4)));
          targets.push(el);
        });
      });

      if (!targets.length) return;

      observer = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add("is-inview");
            observer?.unobserve(entry.target);
          });
        },
        { rootMargin: "0px 0px -8% 0px", threshold: 0.12 }
      );

      targets.forEach((el) => observer.observe(el));
    };

    setupReveal();
    mq.addEventListener("change", setupReveal);
  };

  initPageAboutReveal();

  /* ===== Catalog filters (Free Slots) ===== */
  const initCatalogFilters = () => {
    const root = document.querySelector("[data-catalog-filters]");
    if (!root) return;

    const dropdowns = [...root.querySelectorAll(".catalog-filters__dropdown")];
    const resetBtn = root.querySelector("[data-filter-reset]");
    const searchInput = root.querySelector("[data-filter-search]");

    const defaults = {
      sort: { value: "popular", label: "Sort" },
      type: { value: "all", label: "Game Type" },
      provider: { value: "all", label: "Game Provider" },
    };

    const closeAll = (exceptMenu) => {
      dropdowns.forEach((dropdown) => {
        const toggle = dropdown.querySelector("[data-filter-toggle]");
        const menu = dropdown.querySelector("[data-filter-menu]");
        if (!toggle || !menu) return;
        if (exceptMenu && menu === exceptMenu) return;
        menu.hidden = true;
        toggle.setAttribute("aria-expanded", "false");
      });
    };

    const setActiveOption = (menu, option) => {
      menu.querySelectorAll("[data-filter-option]").forEach((btn) => {
        const active = btn === option;
        btn.classList.toggle("is-active", active);
        btn.setAttribute("aria-selected", active ? "true" : "false");
      });
    };

    dropdowns.forEach((dropdown) => {
      const toggle = dropdown.querySelector("[data-filter-toggle]");
      const menu = dropdown.querySelector("[data-filter-menu]");
      const label = dropdown.querySelector("[data-filter-label]");
      if (!toggle || !menu || !label) return;

      toggle.addEventListener("click", (event) => {
        event.stopPropagation();
        const willOpen = menu.hidden;
        closeAll();
        if (!willOpen) return;
        menu.hidden = false;
        toggle.setAttribute("aria-expanded", "true");
      });

      menu.addEventListener("click", (event) => {
        event.stopPropagation();
      });

      menu.querySelectorAll("[data-filter-option]").forEach((option) => {
        option.addEventListener("click", () => {
          setActiveOption(menu, option);
          const key = menu.getAttribute("data-filter-menu");
          const value = option.getAttribute("data-value");
          const text = option.textContent.trim();
          const isDefault =
            defaults[key] &&
            value === defaults[key].value;
          label.textContent = isDefault ? defaults[key].label : text;
          toggle.classList.toggle("is-filtered", !isDefault);
          closeAll();
        });
      });
    });

    if (resetBtn) {
      resetBtn.addEventListener("click", () => {
        dropdowns.forEach((dropdown) => {
          const toggle = dropdown.querySelector("[data-filter-toggle]");
          const menu = dropdown.querySelector("[data-filter-menu]");
          const label = dropdown.querySelector("[data-filter-label]");
          if (!toggle || !menu || !label) return;
          const key = menu.getAttribute("data-filter-menu");
          const def = defaults[key];
          if (!def) return;
          const option = menu.querySelector(`[data-filter-option][data-value="${def.value}"]`);
          if (option) setActiveOption(menu, option);
          label.textContent = def.label;
          toggle.classList.remove("is-filtered");
        });
        if (searchInput) searchInput.value = "";
        closeAll();
      });
    }

    document.addEventListener("click", () => closeAll());
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape") closeAll();
    });
  };

  initCatalogFilters();

  /* ===== Category mosaic: More Categories ===== */
  const initCategoryMosaic = () => {
    const mosaic = document.querySelector(".category-mosaic");
    if (!mosaic) return;
    const toggle = mosaic.querySelector(".category-mosaic__more");
    const extra = mosaic.querySelector(".category-mosaic__extra");
    if (!toggle || !extra) return;

    toggle.addEventListener("click", () => {
      const isOpen = mosaic.classList.toggle("is-expanded");
      toggle.setAttribute("aria-expanded", isOpen ? "true" : "false");
      extra.setAttribute("aria-hidden", isOpen ? "false" : "true");
    });
  };

  initCategoryMosaic();
})();