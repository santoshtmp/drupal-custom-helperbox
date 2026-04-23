document.addEventListener("DOMContentLoaded", () => {
  const hamburgerMenuBar = document.getElementById("hamburger");
  const mmContainer = document.getElementById("mmContainer");
  const overlay = document.getElementById("hamburger-overlay");
  const openBtn = document.getElementById("hamburger");

  // Main Toggle Functions
  const openNav = () => {
    hamburgerMenuBar.classList.add("is-open");
    mmContainer.classList.add("is-active");
    overlay.classList.add("is-visible");
    document.body.style.overflow = "hidden";
  };

  const closeNav = () => {
    hamburgerMenuBar.classList.remove("is-open");
    mmContainer.classList.remove("is-active");
    overlay.classList.remove("is-visible");
    document.body.style.overflow = "";

    // Clean up: Close all submenus after the panel finishes sliding away
    setTimeout(() => {
      document
        .querySelectorAll(".panel")
        .forEach((el) => el.classList.remove("open-level"));
    }, 300);
  };

  openBtn.addEventListener("click", openNav);
  overlay.addEventListener("click", closeNav);

  // ESC key close
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeNav();
    }
  });

  // Precise Class-Based Delegation
  mmContainer.addEventListener("click", (e) => {
    const nextBtn = e.target.closest(".mm-btn--next");
    const backBtn = e.target.closest(".back-btn");

    if (backBtn) {
      const currentPanel = backBtn.closest(".panel");
      if (currentPanel) {
        currentPanel.classList.remove("open-level");
      }
      return;
    }

    if (nextBtn) {
      const parentBtn = nextBtn.closest(".mm-btn");
      if (!parentBtn) return;

      const nextPanel = parentBtn.nextElementSibling;

      if (nextPanel && nextPanel.classList.contains("panel")) {
        nextPanel.classList.add("open-level");
      }
    }
  });
});
