(() => {
  const form = document.querySelector("[data-reset-form]");
  if (!form) return;

  const passwordInput = form.querySelector("[data-password]");
  const confirmInput = form.querySelector("[data-confirm-password]");
  const submitButton = form.querySelector("[data-submit]");
  const strengthBox = form.querySelector("[data-strength]");
  const strengthLabel = form.querySelector("[data-strength-label]");
  const strengthValue = form.querySelector("[data-strength-value]");
  const strengthBar = form.querySelector("[data-strength-bar]");
  const mismatchError = form.querySelector("[data-mismatch-error]");
  const matchSuccess = form.querySelector("[data-match-success]");
  const loadingState = document.querySelector("[data-loading-state]");
  const formCard = document.querySelector("[data-form-card]");
  const criteriaItems = Array.from(form.querySelectorAll("[data-criterion]"));
  const visibilityButtons = Array.from(
    form.querySelectorAll("[data-toggle-visibility]"),
  );

  const strengthMeta = [
    { label: "Oso ahula", color: "#EF4444", pct: 20 },
    { label: "Ahula", color: "#F97316", pct: 40 },
    { label: "Nahikoa", color: "#F59E0B", pct: 60 },
    { label: "Ona", color: "#84CC16", pct: 80 },
    { label: "Bikaina", color: "#10B981", pct: 100 },
  ];

  const criteria = {
    length: (value) => value.length >= 6,
    upper: (value) => /[A-Z]/.test(value),
    number: (value) => /[0-9]/.test(value),
    symbol: (value) => /[^A-Za-z0-9]/.test(value),
  };

  const eye =
    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
  const eyeOff =
    '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path><line x1="2" x2="22" y1="2" y2="22"></line></svg>';
  const check =
    '<svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"></path></svg>';

  function getStrength(value) {
    let score = 0;
    if (value.length >= 6) score++;
    if (value.length >= 10) score++;
    if (criteria.upper(value)) score++;
    if (criteria.number(value)) score++;
    if (criteria.symbol(value)) score++;
    return score;
  }

  function refreshCriteria(value) {
    criteriaItems.forEach((item) => {
      const key = item.getAttribute("data-criterion");
      const isValid = criteria[key]?.(value) ?? false;
      item.classList.toggle("is-valid", isValid);
      const bullet = item.querySelector(".criterion-bullet");
      bullet.innerHTML = isValid ? check : "";
    });
  }

  function refreshMatchState() {
    const password = passwordInput.value;
    const confirm = confirmInput.value;
    const mismatch = confirm.length > 0 && password !== confirm;
    const matched = confirm.length > 0 && password === confirm;
    mismatchError.classList.toggle("is-visible", mismatch);
    matchSuccess.classList.toggle("is-visible", matched);
    confirmInput.closest(".input-wrap").classList.toggle("has-error", mismatch);
  }

  function refreshStrength() {
    const value = passwordInput.value;
    const strength = getStrength(value);
    const meta = strengthMeta[Math.max(0, strength - 1)] || strengthMeta[0];
    strengthBox.classList.toggle("is-visible", value.length > 0);
    strengthLabel.textContent = meta.label;
    strengthLabel.style.color = meta.color;
    strengthValue.textContent = `${strength}/5`;
    strengthValue.style.color = meta.color;
    strengthBar.style.width = `${value.length === 0 ? 0 : meta.pct}%`;
    strengthBar.style.background = meta.color;
    refreshCriteria(value);
  }

  function isFormValid() {
    const password = passwordInput.value;
    const confirm = confirmInput.value;
    return password.length >= 6 && password === confirm && confirm.length > 0;
  }

  function refreshSubmit() {
    submitButton.disabled = !isFormValid();
  }

  visibilityButtons.forEach((button) => {
    button.addEventListener("click", () => {
      const input = form.querySelector(
        `#${button.getAttribute("data-target")}`,
      );
      if (!input) return;
      const nextType = input.type === "password" ? "text" : "password";
      input.type = nextType;
      button.innerHTML = nextType === "password" ? eye : eyeOff;
    });
  });

  passwordInput.addEventListener("input", () => {
    refreshStrength();
    refreshMatchState();
    refreshSubmit();
  });

  confirmInput.addEventListener("input", () => {
    refreshMatchState();
    refreshSubmit();
  });

  form.addEventListener("submit", () => {
    if (!isFormValid()) {
      refreshMatchState();
      refreshSubmit();
      return;
    }
    submitButton.disabled = true;
    submitButton.innerHTML = "Pasahitza eguneratzen…";
    if (formCard && loadingState) {
      formCard.classList.add("hidden");
      loadingState.classList.remove("hidden");
    }
  });

  refreshStrength();
  refreshMatchState();
  refreshSubmit();
})();
