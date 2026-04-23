(() => {
  // Sistema de Rastreo de Paquetes - pakAG

  const searchForm = document.querySelector("form");
  if (!searchForm) return;

  const packageCodeInput = searchForm.querySelector(
    'input[name="package_code"]',
  );
  if (packageCodeInput) {
    packageCodeInput.addEventListener("focus", function () {
      this.setAttribute("data-focused", "true");
    });

    packageCodeInput.addEventListener("blur", function () {
      this.removeAttribute("data-focused");
    });

    // Auto-buscar si viene en URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get("code")) {
      // El servidor ya maneja esto
    }
  }

  // Animaciones suaves para elementos del timeline
  const timelineItems = document.querySelectorAll(".timeline-item");
  if (timelineItems.length > 0) {
    timelineItems.forEach((item, index) => {
      item.style.animationDelay = `${index * 50}ms`;
      item.classList.add("fade-in");
    });
  }

  // Copiar código del paquete al portapapeles
  const trackingHeader = document.querySelector(".tracking-header");
  if (trackingHeader) {
    const packageCode = trackingHeader.querySelector("h2");
    if (packageCode) {
      packageCode.style.cursor = "pointer";
      packageCode.title = "Haz clic para copiar";
      packageCode.addEventListener("click", function () {
        const code = this.textContent.trim();
        navigator.clipboard.writeText(code).then(() => {
          const originalText = this.textContent;
          this.textContent = "✓ Copiado";
          setTimeout(() => {
            this.textContent = originalText;
          }, 2000);
        });
      });
    }
  }

  // Refresh automático
  const trackingCard = document.querySelector(".tracking-card");
  if (trackingCard) {
    // Agregar botón de actualizar estado
    const refreshBtn = document.createElement("button");
    refreshBtn.type = "button";
    refreshBtn.className = "secondary-btn";
    refreshBtn.textContent = "Actualizar estado";
    refreshBtn.style.marginTop = "16px";
    refreshBtn.addEventListener("click", () => {
      location.reload();
    });

    const trackingFooter = trackingCard.querySelector(".tracking-footer");
    if (trackingFooter) {
      const form = trackingFooter.querySelector("form");
      if (form) {
        form.parentNode.insertBefore(refreshBtn, form);
      }
    }
  }
})();
