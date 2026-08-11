(() => {
  const focusableSelector = [
    "a[href]",
    "button:not([disabled])",
    "input:not([disabled])",
    "select:not([disabled])",
    "textarea:not([disabled])",
    "[tabindex]:not([tabindex='-1'])",
  ].join(",");

  const clearSensitiveFields = (root = document) => {
    root
      .querySelectorAll("input[type='password'], input[data-sensitive-code]")
      .forEach((input) => {
        input.value = "";
      });
  };

  const setupResendCountdowns = () => {
    document.querySelectorAll("[data-resend-button]").forEach((button) => {
      let seconds = Number.parseInt(button.dataset.cooldownSeconds || "0", 10);
      if (seconds <= 0) return;

      const updateButton = () => {
        if (seconds <= 0) {
          button.disabled = false;
          button.textContent = "Resend code";
          return;
        }

        button.disabled = true;
        button.textContent = `Resend code in ${seconds}s`;
        seconds -= 1;
        window.setTimeout(updateButton, 1000);
      };

      updateButton();
    });
  };

  const setupSecureForms = () => {
    document.querySelectorAll("form[data-secure-form]").forEach((form) => {
      form.addEventListener("submit", (event) => {
        const submitter = event.submitter || form.querySelector("button[type='submit']");
        if (!submitter || submitter.dataset.submitting === "true") {
          event.preventDefault();
          return;
        }

        submitter.dataset.submitting = "true";
        submitter.disabled = true;

        if (submitter.dataset.submitLabel) {
          submitter.dataset.originalLabel = submitter.textContent.trim();
          submitter.textContent = submitter.dataset.submitLabel;
        }
      });
    });
  };

  const setupDialogs = () => {
    document.querySelectorAll("[data-auth-dialog]").forEach((dialog) => {
      const firstFocusable = dialog.querySelector("[autofocus], ".concat(focusableSelector));
      if (firstFocusable) {
        window.requestAnimationFrame(() => firstFocusable.focus());
      }

      dialog.addEventListener("keydown", (event) => {
        if (event.key !== "Escape") return;

        const close = dialog.querySelector("[data-auth-dialog-close]");
        if (close) {
          clearSensitiveFields(dialog);
          close.click();
        }
      });
    });
  };

  window.LuminaAuth = {
    clearSensitiveFields,
  };

  window.addEventListener("pageshow", (event) => {
    if (event.persisted) {
      clearSensitiveFields();
    }
  });

  document.addEventListener("DOMContentLoaded", () => {
    setupResendCountdowns();
    setupSecureForms();
    setupDialogs();
  });
})();
