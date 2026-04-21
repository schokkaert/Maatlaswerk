(function () {
  if (document.querySelector(".maatlas-site-shell")) {
    return;
  }

  var storageKey = "maatlas_legal_notice_closed";
  var shellRoot = document.querySelector(".site-site-blocks") || document.body;
  if (!shellRoot) {
    return;
  }

  var normalizePath = function (path) {
    if (!path) {
      return "/";
    }

    var cleaned = path
      .replace(/index\.(php|html)$/i, "")
      .replace(/\/{2,}/g, "/");

    if (!cleaned.endsWith("/")) {
      cleaned += "/";
    }

    return cleaned;
  };

  var currentPath = normalizePath(window.location.pathname);
  var runtimeSettings = window.maatlasSiteSettings || {};
  var vatNumber = (runtimeSettings.vatNumber || "").trim();
  var facebookUrl = (runtimeSettings.facebookUrl || "").trim();
  var instagramUrl = (runtimeSettings.instagramUrl || "").trim();
  var backToTopEnabled = runtimeSettings.backToTopEnabled !== false;
  var backToTopPosition = runtimeSettings.backToTopPosition || "bottom-right";
  var backToTopMarginX = Number(runtimeSettings.backToTopMarginX || 24);
  var backToTopMarginY = Number(runtimeSettings.backToTopMarginY || 24);

  var links = [
    { href: "/", label: "Home", match: ["/"] },
    { href: "/about/", label: "Over ons", match: ["/about/"] },
    { href: "/services/", label: "Diensten", match: ["/services/"] },
    { href: "/contact/", label: "Contact", match: ["/contact/"] }
  ];
  var legalLinks = [
    { href: "/privacy/", label: "Privacyverklaring" },
    { href: "/cookies/", label: "Cookiebeleid" },
    { href: "https://www.forstersystems.be", label: "Forster Systems", external: true },
    {
      href: "https://www.forstersystems.be/dealers/binnenschrijnwerk-buitenschrijnwerk-9690-kluisbergen/ws-maatlaswerken",
      label: "Dealerpagina",
      external: true
    },
    { href: "/admin/", label: "Admin" }
  ];
  var socialLinks = [
    {
      href: facebookUrl,
      label: "Facebook",
      className: "maatlas-floating-facebook",
      ariaLabel: "Bezoek onze Facebook-pagina",
      icon: "f"
    },
    {
      href: instagramUrl,
      label: "Instagram",
      className: "maatlas-floating-instagram",
      ariaLabel: "Bezoek onze Instagram-pagina",
      icon:
        '<svg viewBox="0 0 24 24" role="img" focusable="false" aria-hidden="true">' +
        '<path d="M7.75 2C4.57 2 2 4.57 2 7.75v8.5C2 19.43 4.57 22 7.75 22h8.5C19.43 22 22 19.43 22 16.25V7.75C22 4.57 19.43 2 16.25 2h-8.5zm0 1.8h8.5c2.19 0 3.95 1.76 3.95 3.95v8.5c0 2.19-1.76 3.95-3.95 3.95h-8.5c-2.19 0-3.95-1.76-3.95-3.95V7.75c0-2.19 1.76-3.95 3.95-3.95zm8.9 1.45a1.15 1.15 0 1 0 0 2.3 1.15 1.15 0 0 0 0-2.3zM12 7a5 5 0 1 0 0 10 5 5 0 0 0 0-10zm0 1.8A3.2 3.2 0 1 1 12 15.2 3.2 3.2 0 0 1 12 8.8z"></path>' +
        "</svg>"
    }
  ].filter(function (link) {
    return link.href !== "";
  });

  var linkMarkup = links
    .map(function (link) {
      var isCurrent = link.match.some(function (matchPath) {
        return currentPath === normalizePath(matchPath);
      });

      return (
        '<a class="maatlas-shell-link' +
        (isCurrent ? " is-current" : "") +
        '" href="' +
        link.href +
        '">' +
        link.label +
        "</a>"
      );
    })
    .join("");

  var headerMarkup =
    '<div class="maatlas-site-shell maatlas-site-shell-header">' +
    '<div class="maatlas-shell-inner maatlas-shell-header-inner">' +
    '<a class="maatlas-shell-brand" href="/">' +
    '<img class="maatlas-shell-brand-logo" src="/assets/uploads/static/MaatLasWerk-13.jpg?v=20260329-3" alt="W&amp;S Maatlaswerk logo">' +
    '<span class="maatlas-shell-brand-text">' +
    '<strong>W&amp;S Maatlaswerk</strong>' +
    '<small>Metaal en glas op maat in Kluisbergen</small>' +
    "</span>" +
    "</a>" +
    '<nav class="maatlas-shell-nav" aria-label="Hoofdnavigatie">' +
    linkMarkup +
    "</nav>" +
    "</div>" +
    "</div>";

  var footerMarkup =
    '<div class="maatlas-site-shell maatlas-site-shell-footer">' +
    '<div class="maatlas-shell-inner maatlas-shell-footer-inner">' +
    '<div class="maatlas-shell-footer-copy">' +
    "<strong>W&amp;S Maatlaswerk</strong>" +
    "<p>Maatwerk in staal, inox, aluminium en glas voor particuliere en professionele projecten.</p>" +
    '<p>Voor de gebruikte materialen werken we met <a href="https://www.forstersystems.be" target="_blank" rel="noopener noreferrer">Forster Systems</a> via <a href="https://www.forstersystems.be/dealers/binnenschrijnwerk-buitenschrijnwerk-9690-kluisbergen/ws-maatlaswerken" target="_blank" rel="noopener noreferrer">WS Maatlaswerken</a>.</p>' +
    (vatNumber ? "<p>BTW: " + vatNumber + "</p>" : "") +
    '<div class="maatlas-shell-footer-legal">' +
    legalLinks
      .map(function (link) {
        return (
          '<a class="maatlas-shell-legal-link" href="' +
          link.href +
          '"' +
          (link.external ? ' target="_blank" rel="noopener noreferrer"' : "") +
          ">" +
          link.label +
          "</a>"
        );
      })
      .join("") +
    "</div>" +
    "</div>" +
    '<nav class="maatlas-shell-footer-nav" aria-label="Footer navigatie">' +
    linkMarkup +
    "</nav>" +
    (socialLinks.length
      ? '<div class="maatlas-shell-footer-socials">' +
        socialLinks
          .map(function (link) {
            return '<a class="maatlas-shell-social" href="' + link.href + '" rel="noopener noreferrer">' + link.label + "</a>";
          })
          .join("") +
        "</div>"
      : "") +
    "</div>" +
    "</div>";

  var legalNoticeMarkup =
    '<div class="maatlas-legal-notice" role="note">' +
    '<p>Deze site gebruikt alleen technisch noodzakelijke cookies. Op de contactpagina wordt een Google Maps-kaart van een externe dienst geladen. Lees onze <a href="/privacy/">privacyverklaring</a> en het <a href="/cookies/">cookiebeleid</a>.</p>' +
    '<button type="button" class="maatlas-legal-notice-close" aria-label="Sluit melding">Sluiten</button>' +
    "</div>";

  var floatingSocials = socialLinks.length
    ? '<div class="maatlas-floating-socials">' +
      socialLinks
        .map(function (link) {
          return (
            '<a class="maatlas-floating-social ' +
            link.className +
            '" href="' +
            link.href +
            '" rel="noopener noreferrer" aria-label="' +
            link.ariaLabel +
            '">' +
            '<span class="maatlas-floating-social-icon" aria-hidden="true">' +
            link.icon +
            "</span>" +
            "</a>"
          );
        })
        .join("") +
      "</div>"
    : "";

  var backToTopInlineStyle = (function () {
    var styles = [];
    var position = String(backToTopPosition).toLowerCase();
    var shiftX = "0";
    var shiftY = "0";

    if (position.indexOf("top-") === 0) {
      styles.push("top:" + backToTopMarginY + "px");
    } else if (position.indexOf("bottom-") === 0) {
      styles.push("bottom:" + backToTopMarginY + "px");
    } else {
      styles.push("top:50%");
      shiftY = "-50%";
    }

    if (position.endsWith("-left")) {
      styles.push("left:" + backToTopMarginX + "px");
    } else if (position.endsWith("-right")) {
      styles.push("right:" + backToTopMarginX + "px");
    } else {
      styles.push("left:50%");
      shiftX = "-50%";
    }

    styles.push("--maatlas-back-to-top-shift-x:" + shiftX);
    styles.push("--maatlas-back-to-top-shift-y:" + shiftY);
    return styles.join(";");
  })();

  var backToTopMarkup =
    '<button type="button" class="maatlas-back-to-top maatlas-back-to-top-' +
    backToTopPosition +
    '" style="' +
    backToTopInlineStyle +
    ';" aria-label="Ga terug naar boven">' +
    '<span class="maatlas-back-to-top-icon" aria-hidden="true">&uarr;</span>' +
    "</button>";

  shellRoot.insertAdjacentHTML("afterbegin", headerMarkup);
  shellRoot.insertAdjacentHTML("beforeend", footerMarkup);
  if (floatingSocials) {
    document.body.insertAdjacentHTML("beforeend", floatingSocials);
  }

  if (backToTopEnabled) {
    document.body.insertAdjacentHTML("beforeend", backToTopMarkup);
  }

  if (!window.localStorage || window.localStorage.getItem(storageKey) !== "1") {
    document.body.insertAdjacentHTML("beforeend", legalNoticeMarkup);
  }

  document.body.classList.add("maatlas-shell-ready");

  var activeLightbox = null;
  var activeLightboxState = null;
  var closeLightbox = function () {
    if (!activeLightbox) {
      return;
    }

    activeLightbox.remove();
    activeLightbox = null;
    activeLightboxState = null;
    document.body.classList.remove("maatlas-lightbox-open");
  };

  var getLightboxItemsForTrigger = function (trigger) {
    if (!trigger) {
      return [];
    }

    var galleryName = trigger.getAttribute("data-lightbox-gallery");
    if (!galleryName) {
      return [trigger];
    }

    return Array.prototype.slice.call(
      document.querySelectorAll('[data-lightbox="image"][data-lightbox-gallery="' + galleryName + '"]')
    );
  };

  var renderLightboxImage = function (state, index) {
    if (!state || !state.items.length) {
      return;
    }

    if (index < 0) {
      index = state.items.length - 1;
    } else if (index >= state.items.length) {
      index = 0;
    }

    state.index = index;

    var item = state.items[index];
    var imageUrl = item.getAttribute("href") || item.getAttribute("data-lightbox-src") || "";
    var captionText = item.getAttribute("data-lightbox-caption") || "";

    if (!imageUrl) {
      return;
    }

    state.media.innerHTML = "";

    var loading = document.createElement("p");
    loading.className = "maatlas-lightbox-loading";
    loading.textContent = "Afbeelding laden...";
    state.media.appendChild(loading);

    state.caption.textContent = captionText;
    state.caption.hidden = captionText === "";
    state.counter.textContent = "Foto " + (index + 1) + "/" + state.items.length;
    state.prev.disabled = state.items.length < 2;
    state.next.disabled = state.items.length < 2;

    var image = new Image();
    image.className = "maatlas-lightbox-image";
    image.alt = captionText || "Vergrote afbeelding";
    image.onload = function () {
      state.media.innerHTML = "";
      state.media.appendChild(image);
    };
    image.src = imageUrl;
  };

  var openLightbox = function (trigger) {
    var items = getLightboxItemsForTrigger(trigger);
    if (!items.length) {
      return;
    }

    closeLightbox();

    var overlay = document.createElement("div");
    overlay.className = "maatlas-lightbox";
    overlay.setAttribute("role", "dialog");
    overlay.setAttribute("aria-modal", "true");

    var dialog = document.createElement("div");
    dialog.className = "maatlas-lightbox-dialog";

    var closeButton = document.createElement("button");
    closeButton.type = "button";
    closeButton.className = "maatlas-lightbox-close";
    closeButton.setAttribute("aria-label", "Sluiten");
    closeButton.textContent = "X";

    var prevButton = document.createElement("button");
    prevButton.type = "button";
    prevButton.className = "maatlas-lightbox-nav maatlas-lightbox-prev";
    prevButton.setAttribute("aria-label", "Vorige foto");
    prevButton.textContent = "‹";

    var nextButton = document.createElement("button");
    nextButton.type = "button";
    nextButton.className = "maatlas-lightbox-nav maatlas-lightbox-next";
    nextButton.setAttribute("aria-label", "Volgende foto");
    nextButton.textContent = "›";

    var media = document.createElement("div");
    media.className = "maatlas-lightbox-media";

    var footer = document.createElement("div");
    footer.className = "maatlas-lightbox-footer";

    var caption = document.createElement("p");
    caption.className = "maatlas-lightbox-caption";

    var counter = document.createElement("p");
    counter.className = "maatlas-lightbox-counter";

    dialog.appendChild(closeButton);
    dialog.appendChild(prevButton);
    dialog.appendChild(nextButton);
    dialog.appendChild(media);
    footer.appendChild(caption);
    footer.appendChild(counter);
    dialog.appendChild(footer);

    overlay.appendChild(dialog);
    document.body.appendChild(overlay);
    document.body.classList.add("maatlas-lightbox-open");
    activeLightbox = overlay;
    activeLightboxState = {
      items: items,
      index: Math.max(items.indexOf(trigger), 0),
      media: media,
      caption: caption,
      counter: counter,
      prev: prevButton,
      next: nextButton
    };

    renderLightboxImage(activeLightboxState, activeLightboxState.index);
  };

  document.addEventListener("click", function (event) {
    var lightboxTrigger = event.target.closest("[data-lightbox='image']");
    if (lightboxTrigger) {
      event.preventDefault();
      openLightbox(lightboxTrigger);
      return;
    }

    var lightboxClose = event.target.closest(".maatlas-lightbox-close");
    if (lightboxClose) {
      closeLightbox();
      return;
    }

    var lightboxPrev = event.target.closest(".maatlas-lightbox-prev");
    if (lightboxPrev && activeLightboxState) {
      renderLightboxImage(activeLightboxState, activeLightboxState.index - 1);
      return;
    }

    var lightboxNext = event.target.closest(".maatlas-lightbox-next");
    if (lightboxNext && activeLightboxState) {
      renderLightboxImage(activeLightboxState, activeLightboxState.index + 1);
      return;
    }

    if (activeLightbox && event.target === activeLightbox) {
      closeLightbox();
      return;
    }

    var backToTopButton = event.target.closest(".maatlas-back-to-top");
    if (backToTopButton) {
      window.scrollTo({ top: 0, behavior: "smooth" });
      return;
    }

    var closeButton = event.target.closest(".maatlas-legal-notice-close");
    if (!closeButton) {
      return;
    }

    var legalNotice = closeButton.closest(".maatlas-legal-notice");
    if (legalNotice) {
      legalNotice.remove();
    }

    if (window.localStorage) {
      window.localStorage.setItem(storageKey, "1");
    }
  });

  var backToTop = document.querySelector(".maatlas-back-to-top");
  if (backToTop) {
    var syncBackToTopVisibility = function () {
      backToTop.classList.toggle("is-visible", window.scrollY > 260);
    };

    syncBackToTopVisibility();
    window.addEventListener("scroll", syncBackToTopVisibility, { passive: true });
  }

  var mapConsentCards = document.querySelectorAll("[data-map-consent]");
  mapConsentCards.forEach(function (card) {
    var button = card.querySelector(".maatlas-map-consent-button");
    var frame = card.parentElement ? card.parentElement.querySelector("[data-map-frame]") : null;
    var src = card.getAttribute("data-map-src");

    if (!button || !frame || !src) {
      return;
    }

    button.addEventListener("click", function () {
      frame.innerHTML =
        '<iframe src="' +
        src +
        '" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Google Maps kaart"></iframe>';
      frame.hidden = false;
      card.hidden = true;
    });
  });

  document.addEventListener("keydown", function (event) {
    if (event.key === "Escape" && activeLightbox) {
      closeLightbox();
      return;
    }

    if (!activeLightboxState) {
      return;
    }

    if (event.key === "ArrowLeft") {
      renderLightboxImage(activeLightboxState, activeLightboxState.index - 1);
      return;
    }

    if (event.key === "ArrowRight") {
      renderLightboxImage(activeLightboxState, activeLightboxState.index + 1);
    }
  });
})();
