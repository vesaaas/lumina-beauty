const root = document.querySelector("[data-storefront]");
const productsJson = document.querySelector("#storefront-products-json")?.textContent || "[]";
let products = [];

try {
  products = JSON.parse(productsJson);
} catch (error) {
  console.error("Unable to parse storefront product data.", error);
}

const serverFavoritesCount = Number(root?.dataset.storefrontFavoritesCount || 0);
const serverCartCount = Number(root?.dataset.storefrontCartCount || 0);

const formatPrice = (value) =>
  new Intl.NumberFormat("en-IE", {
    style: "currency",
    currency: "EUR",
    maximumFractionDigits: value % 1 === 0 ? 0 : 2,
  }).format(value);

const productById = (id) => products.find((product) => product.id === Number(id));
const activePrice = (product) => product.sale_price || product.price;

const productCard = (product) => `
  <article class="product-card" data-scroll-reveal-item data-product-card data-product-id="${product.id}" data-name="${`${product.name} ${product.brand} ${product.category}`.toLowerCase()}">
    <div class="product-media" data-card-gallery>
      <a class="media-link" href="/products/${product.slug}" aria-label="Open ${product.name}"></a>
      ${product.sale_price ? '<span class="sale-badge">SALE</span>' : ''}
      ${product.images
        .map((image, index) => `<img class="${index === 0 ? "is-active" : ""}" src="${image}" alt="${product.name}" loading="lazy" data-card-image />`)
        .join("")}
    </div>
    <form method="POST" action="/favorites/${product.slug}">
      <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ""}">
      <button class="icon-button favorite-button" type="submit" aria-label="Add ${product.name} to favorites">
        <i data-lucide="heart"></i>
      </button>
    </form>
    <div class="product-info">
      <div class="product-meta">
        <a href="/brands/${slugify(product.brand)}">${product.brand}</a>
        <a href="/categories/${slugify(product.category)}">${product.category}</a>
      </div>
      <h3><a href="/products/${product.slug}">${product.name}</a></h3>
      <strong class="price">${
        product.sale_price
          ? `<span>${formatPrice(product.sale_price)}</span><del>${formatPrice(product.price)}</del>`
          : formatPrice(product.price)
      }</strong>
      <a class="product-read-more" href="/products/${product.slug}">See More</a>
    </div>
  </article>
`;

const slugify = (value) => value.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/(^-|-$)/g, "");

const updateCounters = () => {
  document.querySelectorAll("[data-favorites-count], [data-admin-favorites]").forEach((item) => {
    item.textContent = serverFavoritesCount;
    item.hidden = serverFavoritesCount <= 0;
  });
  document.querySelectorAll(".counter-button [data-cart-count]").forEach((item) => {
    item.textContent = serverCartCount;
    item.hidden = serverCartCount <= 0;
  });
};

const renderFavoritesPage = () => {
};

const renderCartPage = () => {
};

const setupAutoFilters = () => {
  document.querySelectorAll("[data-auto-filter]").forEach((form) => {
    let timeout;
    const submit = () => {
      form.querySelectorAll("input, select").forEach((field) => {
        if (field.value === "") field.disabled = true;
      });
      form.requestSubmit();
    };

    form.querySelectorAll("select").forEach((select) => {
      select.addEventListener("change", submit);
    });

    form.querySelectorAll('input[type="number"]').forEach((input) => {
      input.addEventListener("input", () => {
        clearTimeout(timeout);
        timeout = setTimeout(submit, 650);
      });
    });
  });
};

const setupGallery = () => {
  const gallery = document.querySelector("[data-gallery]");
  if (!gallery) return;

  const images = [...gallery.querySelectorAll("[data-gallery-image]")];
  let activeIndex = images.findIndex((image) => image.classList.contains("is-active"));

  const showImage = (nextIndex) => {
    activeIndex = (nextIndex + images.length) % images.length;
    images.forEach((image, index) => image.classList.toggle("is-active", index === activeIndex));
  };

  gallery.querySelector("[data-image-prev]").addEventListener("click", () => showImage(activeIndex - 1));
  gallery.querySelector("[data-image-next]").addEventListener("click", () => showImage(activeIndex + 1));
};

const setupCardGalleries = () => {
  document.querySelectorAll("[data-card-gallery]").forEach((gallery) => {
    if (gallery.dataset.ready) return;
    gallery.dataset.ready = "true";

    const images = [...gallery.querySelectorAll("[data-card-image]")];
    if (images.length < 2) return;

    let activeIndex = 0;
    const showImage = (nextIndex) => {
      activeIndex = (nextIndex + images.length) % images.length;
      images.forEach((image, index) => image.classList.toggle("is-active", index === activeIndex));
    };

    setInterval(() => showImage(activeIndex + 1), 5000);
  });
};

const setupCarouselProgress = () => {
  document.querySelectorAll("[data-carousel-shell]").forEach((shell) => {
    const carousel = shell.querySelector("[data-product-carousel]");
    const progress = shell.querySelector("[data-carousel-progress]");
    if (!carousel || !progress) return;

    const update = () => {
      const maxScroll = carousel.scrollWidth - carousel.clientWidth;
      const ratio = maxScroll > 0 ? carousel.scrollLeft / maxScroll : 0;
      const width = maxScroll > 0 ? Math.max(14, carousel.clientWidth / carousel.scrollWidth * 100) : 100;
      const travel = 100 - width;
      progress.style.width = `${width}%`;
      progress.style.transform = `translateX(${ratio * travel}%)`;
    };

    carousel.addEventListener("scroll", update, { passive: true });
    window.addEventListener("resize", update);
    update();
  });
};

const setupHeroCarousel = () => {
  const carousel = document.querySelector("[data-hero-carousel]");
  if (!carousel) return;

  const slides = [...carousel.querySelectorAll("img")];
  if (slides.length < 2) return;

  let activeIndex = 0;
  setInterval(() => {
    activeIndex = (activeIndex + 1) % slides.length;
    slides.forEach((slide, index) => slide.classList.toggle("is-active", index === activeIndex));
  }, 5000);
};

const setupSearch = () => {
  const input = document.querySelector("[data-search-input]");
  const results = document.querySelector("[data-search-results]");
  if (!input || !results) return;

  const render = () => {
    const query = input.value.trim().toLowerCase();
    if (query.length < 2) {
      results.classList.remove("is-open");
      results.innerHTML = "";
      return;
    }

    const matches = products
      .filter((product) => `${product.name} ${product.brand} ${product.category}`.toLowerCase().includes(query))
      .slice(0, 6);

    results.innerHTML = matches.length
      ? matches
          .map(
            (product) => `
              <a class="search-result" href="/products/${product.slug}">
                <img src="${product.images[0]}" alt="${product.name}" />
                <span><strong>${product.name}</strong><span>${product.brand} / ${product.category}</span></span>
                <strong class="price">${formatPrice(activePrice(product))}</strong>
              </a>
            `,
          )
          .join("")
      : `<div class="search-empty"><strong>No matching products</strong><span>Press Enter to view the full search page.</span></div>`;

    results.classList.add("is-open");
    if (window.lucide) window.lucide.createIcons();
  };

  input.addEventListener("input", render);
  input.addEventListener("focus", render);
  document.addEventListener("click", (event) => {
    if (!event.target.closest("[data-search-form]")) results.classList.remove("is-open");
  });
};

const setupAccountModal = () => {
  const modal = document.querySelector("[data-account-modal]");
  if (!modal) return;

  const open = () => {
    modal.classList.add("is-open");
    modal.setAttribute("aria-hidden", "false");
  };
  const close = () => {
    modal.classList.remove("is-open");
    modal.setAttribute("aria-hidden", "true");
  };

  document.querySelectorAll("[data-account-open]").forEach((button) => button.addEventListener("click", open));
  document.querySelectorAll("[data-account-close]").forEach((button) => button.addEventListener("click", close));
  modal.addEventListener("click", (event) => {
    if (event.target === modal) close();
  });

  document.querySelectorAll("[data-account-tab]").forEach((tab) => {
    tab.addEventListener("click", () => {
      document.querySelectorAll("[data-account-tab]").forEach((button) => button.classList.toggle("is-active", button === tab));
      document.querySelectorAll("[data-account-panel]").forEach((panel) => {
        panel.classList.toggle("is-active", panel.dataset.accountPanel === tab.dataset.accountTab);
      });
    });
  });

  if (window.location.hash === "#account" || modal.classList.contains("is-open")) {
    open();
  }
};

const setupSmoothProductScroll = () => {
  document.querySelectorAll("[data-scroll-products]").forEach((link) => {
    link.addEventListener("click", (event) => {
      const target = document.querySelector(link.getAttribute("href"));
      if (!target) return;
      event.preventDefault();
      const offset = target.getBoundingClientRect().top + window.scrollY - 92;
      window.scrollTo({ top: offset, behavior: "smooth" });
    });
  });
};

const setupScrollReveal = () => {
  const revealProductSections = [...document.querySelectorAll("[data-scroll-reveal-products]")];
  const revealItems = [
    ...document.querySelectorAll("[data-scroll-reveal], [data-scroll-reveal-item]"),
    ...revealProductSections,
  ];

  revealProductSections.forEach((section) => {
    section.querySelectorAll(".product-card").forEach((card, index) => {
      card.style.setProperty("--reveal-index", index);
    });
  });

  if (!revealItems.length) return;

  document.body.classList.add("js-reveal-enabled");

  if (!("IntersectionObserver" in window)) {
    revealItems.forEach((item) => item.classList.add("is-visible"));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        entry.target.classList.add("is-visible");
        observer.unobserve(entry.target);
      });
    },
    {
      rootMargin: "0px 0px -12% 0px",
      threshold: 0.18,
    },
  );

  revealItems.forEach((item) => observer.observe(item));
};

document.addEventListener("click", (event) => {
  const menuToggle = event.target.closest("[data-menu-toggle]");
  const navLink = event.target.closest(".main-nav a");
  const navTrigger = event.target.closest(".nav-trigger");

  if (menuToggle) {
    document.querySelector("[data-main-nav]").classList.toggle("is-open");
  }

  if (navTrigger && window.matchMedia("(max-width: 1080px)").matches) {
    navTrigger.closest(".nav-dropdown").classList.toggle("is-expanded");
  }

  if (navLink) {
    document.querySelector("[data-main-nav]").classList.remove("is-open");
  }

});

updateCounters();
renderFavoritesPage();
renderCartPage();
setupAutoFilters();
setupGallery();
setupCardGalleries();
setupCarouselProgress();
setupHeroCarousel();
setupSearch();
setupAccountModal();
setupSmoothProductScroll();
setupScrollReveal();

if (window.lucide) {
  window.lucide.createIcons();
}
